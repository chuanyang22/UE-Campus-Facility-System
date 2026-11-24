<?php
/**
 * Business Services Tier: Maintenance & Damage Reporting Service
 * AMCS2194 Mini Project — Module 5: Facility Maintenance & Damage Reporting
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

class MaintenanceService {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Generate the next sequential MaintenanceID (e.g., MT-5003)
     */
    public function generateNextMaintenanceId(): string {
        $stmt = $this->db->query("SELECT MaintenanceID FROM maintenance ORDER BY MaintenanceID DESC LIMIT 1");
        $lastId = $stmt->fetchColumn();

        if ($lastId && preg_match('/MT-(\d+)/', (string)$lastId, $matches)) {
            $nextNum = (int)$matches[1] + 1;
            return sprintf('MT-%04d', $nextNum);
        }

        return 'MT-5001';
    }

    /**
     * 5.1 Report Facility Defect (Create)
     * Includes 5.5 Automated Venue Locking if UrgencyLevel is 'Critical'
     * 
     * @return array [success => bool, message => string, errors => array, id => string|null, locked => bool]
     */
    public function reportDefect(array $data): array {
        $errors = $this->validateDefectInput($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please correct the validation errors in the form.',
                'errors'  => $errors,
                'id'      => null,
                'locked'  => false,
            ];
        }

        $facilityId     = trim($data['FacilityID']);
        $reporterUserId = trim($data['ReportedByUserID']);
        $issueTitle     = trim($data['IssueTitle']);
        $issueDesc      = trim($data['IssueDescription']);
        $urgency        = trim($data['UrgencyLevel']);
        $scheduledDate  = trim($data['ScheduledDate']);
        $maintenanceId  = $this->generateNextMaintenanceId();

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO maintenance (MaintenanceID, FacilityID, ReportedByUserID, IssueTitle, IssueDescription, UrgencyLevel, ScheduledDate, MaintenanceStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $stmt->execute([
                $maintenanceId,
                $facilityId,
                $reporterUserId,
                $issueTitle,
                $issueDesc,
                $urgency,
                $scheduledDate
            ]);

            // Feature 5.5: Automated Venue Locking on Hazardous Defect
            $locked = false;
            if ($urgency === 'Critical') {
                $lockStmt = $this->db->prepare(
                    "UPDATE facility SET AvailabilityStatus = 'Under Maintenance' WHERE FacilityID = ?"
                );
                $lockStmt->execute([$facilityId]);
                $locked = true;
            }

            $this->db->commit();

            $successMsg = "Defect ticket {$maintenanceId} successfully logged.";
            if ($locked) {
                $successMsg .= " [UNIQUE FEATURE TRIGGERED]: Facility {$facilityId} has been AUTOMATICALLY LOCKED ('Under Maintenance') due to Critical Urgency!";
            }

            return [
                'success' => true,
                'message' => $successMsg,
                'errors'  => [],
                'id'      => $maintenanceId,
                'locked'  => $locked,
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error logging defect: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error while saving ticket: ' . $e->getMessage(),
                'errors'  => ['general' => $e->getMessage()],
                'id'      => null,
                'locked'  => false,
            ];
        }
    }

    /**
     * 5.2 View Maintenance Ticket Log (Retrieve)
     */
    public function getTickets(array $filters = []): array {
        $sql = "SELECT m.*, f.FacilityName, f.Category AS FacilityCategory, f.LocationBlock, f.AvailabilityStatus AS FacilityStatus,
                       u.FullName AS ReporterName, u.UserRole AS ReporterRole, u.CampusEmail AS ReporterEmail
                FROM maintenance m
                JOIN facility f ON m.FacilityID = f.FacilityID
                JOIN user u ON m.ReportedByUserID = u.UserID
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND m.MaintenanceStatus = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['urgency'])) {
            $sql .= " AND m.UrgencyLevel = ?";
            $params[] = $filters['urgency'];
        }

        if (!empty($filters['facility_id'])) {
            $sql .= " AND m.FacilityID = ?";
            $params[] = $filters['facility_id'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= " AND (m.MaintenanceID LIKE ? OR m.IssueTitle LIKE ? OR f.FacilityName LIKE ? OR u.FullName LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY CASE m.UrgencyLevel 
                    WHEN 'Critical' THEN 1 
                    WHEN 'High' THEN 2 
                    WHEN 'Medium' THEN 3 
                    ELSE 4 END ASC, m.ScheduledDate DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get a single maintenance ticket by ID
     */
    public function getTicketById(string $maintenanceId): ?array {
        $stmt = $this->db->prepare(
            "SELECT m.*, f.FacilityName, f.Category AS FacilityCategory, f.LocationBlock, f.AvailabilityStatus AS FacilityStatus,
                    u.FullName AS ReporterName, u.UserRole AS ReporterRole, u.CampusEmail AS ReporterEmail
             FROM maintenance m
             JOIN facility f ON m.FacilityID = f.FacilityID
             JOIN user u ON m.ReportedByUserID = u.UserID
             WHERE m.MaintenanceID = ?"
        );
        $stmt->execute([$maintenanceId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * 5.3 Update Repair Status & Assign Technician (Update)
     * If marked 'Completed', auto-unlocks facility back to 'Available' if no other active critical tickets exist.
     */
    public function updateTicketStatus(string $maintenanceId, string $status, ?string $resolutionDate = null, ?string $technicianNotes = null): array {
        $allowedStatuses = ['Pending', 'In-Progress', 'Completed', 'Archived'];
        if (!in_array($status, $allowedStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid status value selected.'];
        }

        $ticket = $this->getTicketById($maintenanceId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Maintenance ticket not found.'];
        }

        $facilityId = $ticket['FacilityID'];
        if ($status === 'Completed' && empty($resolutionDate)) {
            $resolutionDate = date('Y-m-d');
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "UPDATE maintenance 
                 SET MaintenanceStatus = ?, 
                     ResolutionDate = ?
                 WHERE MaintenanceID = ?"
            );
            $stmt->execute([$status, $resolutionDate, $maintenanceId]);

            // Unique Feature 5.5 Auto-Unlock:
            // When resolved to 'Completed' or 'Archived', check if the facility can be restored to 'Available'
            $unlocked = false;
            if (in_array($status, ['Completed', 'Archived'], true)) {
                // Check if any other unresolved tickets are on this facility
                $checkStmt = $this->db->prepare(
                    "SELECT COUNT(*) FROM maintenance 
                     WHERE FacilityID = ? AND MaintenanceID != ? AND MaintenanceStatus IN ('Pending', 'In-Progress') AND UrgencyLevel = 'Critical'"
                );
                $checkStmt->execute([$facilityId, $maintenanceId]);
                $remainingCritical = (int)$checkStmt->fetchColumn();

                if ($remainingCritical === 0 && $ticket['FacilityStatus'] === 'Under Maintenance') {
                    $restoreStmt = $this->db->prepare(
                        "UPDATE facility SET AvailabilityStatus = 'Available' WHERE FacilityID = ?"
                    );
                    $restoreStmt->execute([$facilityId]);
                    $unlocked = true;
                }
            }

            $this->db->commit();

            $msg = "Ticket {$maintenanceId} updated to '{$status}'.";
            if ($unlocked) {
                $msg .= " [UNIQUE FEATURE TRIGGERED]: Facility {$facilityId} has been AUTOMATICALLY UNLOCKED back to 'Available'!";
            }

            return ['success' => true, 'message' => $msg, 'unlocked' => $unlocked];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error updating ticket: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * 5.4 Close / Delete Ticket (Delete)
     */
    public function deleteTicket(string $maintenanceId): array {
        $ticket = $this->getTicketById($maintenanceId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket does not exist.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM maintenance WHERE MaintenanceID = ?");
            $stmt->execute([$maintenanceId]);

            // Check if deleting this ticket clears a critical issue and restores venue
            $facilityId = $ticket['FacilityID'];
            $checkStmt = $this->db->prepare(
                "SELECT COUNT(*) FROM maintenance 
                 WHERE FacilityID = ? AND MaintenanceStatus IN ('Pending', 'In-Progress') AND UrgencyLevel = 'Critical'"
            );
            $checkStmt->execute([$facilityId]);
            $remaining = (int)$checkStmt->fetchColumn();

            $unlocked = false;
            if ($remaining === 0 && $ticket['FacilityStatus'] === 'Under Maintenance') {
                $restoreStmt = $this->db->prepare(
                    "UPDATE facility SET AvailabilityStatus = 'Available' WHERE FacilityID = ?"
                );
                $restoreStmt->execute([$facilityId]);
                $unlocked = true;
            }

            $this->db->commit();

            $msg = "Ticket {$maintenanceId} has been successfully deleted.";
            if ($unlocked) {
                $msg .= " Facility {$facilityId} was restored to 'Available'.";
            }

            return ['success' => true, 'message' => $msg];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error deleting ticket: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Cannot delete ticket: ' . $e->getMessage()];
        }
    }

    /**
     * Get maintenance summary statistics
     */
    public function getStatistics(): array {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM maintenance")->fetchColumn();
        $pending = (int)$this->db->query("SELECT COUNT(*) FROM maintenance WHERE MaintenanceStatus = 'Pending'")->fetchColumn();
        $inProgress = (int)$this->db->query("SELECT COUNT(*) FROM maintenance WHERE MaintenanceStatus = 'In-Progress'")->fetchColumn();
        $completed = (int)$this->db->query("SELECT COUNT(*) FROM maintenance WHERE MaintenanceStatus = 'Completed'")->fetchColumn();
        $critical = (int)$this->db->query("SELECT COUNT(*) FROM maintenance WHERE UrgencyLevel = 'Critical' AND MaintenanceStatus IN ('Pending', 'In-Progress')")->fetchColumn();
        $lockedVenues = (int)$this->db->query("SELECT COUNT(*) FROM facility WHERE AvailabilityStatus = 'Under Maintenance'")->fetchColumn();

        return [
            'total'        => $total,
            'pending'      => $pending,
            'in_progress'  => $inProgress,
            'completed'    => $completed,
            'critical'     => $critical,
            'locked_venues'=> $lockedVenues,
        ];
    }

    /**
     * Get all facilities for drop-down selection
     */
    public function getAllFacilities(): array {
        $stmt = $this->db->query("SELECT * FROM facility ORDER BY FacilityName ASC");
        return $stmt->fetchAll();
    }

    /**
     * Input Validation according to assignment guidelines:
     * - Null value check (mandatory fields)
     * - Existence check (FacilityID & ReportedByUserID exist)
     * - Size / limit check (IssueTitle 5-100 chars, IssueDescription min 10 chars)
     * - Format check (ScheduledDate format YYYY-MM-DD)
     * - Whitelist check (UrgencyLevel in enum values)
     */
    public function validateDefectInput(array $data): array {
        $errors = [];

        // 1. FacilityID existence & presence check
        if (empty($data['FacilityID'])) {
            $errors['FacilityID'] = 'Facility selection is mandatory (Null Value Check failed).';
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM facility WHERE FacilityID = ?");
            $stmt->execute([trim($data['FacilityID'])]);
            if ((int)$stmt->fetchColumn() === 0) {
                $errors['FacilityID'] = 'Selected Facility does not exist in database (Existence Check failed).';
            }
        }

        // 2. ReportedByUserID existence & presence check
        if (empty($data['ReportedByUserID'])) {
            $errors['ReportedByUserID'] = 'Reporter ID is required (Null Value Check failed).';
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM user WHERE UserID = ?");
            $stmt->execute([trim($data['ReportedByUserID'])]);
            if ((int)$stmt->fetchColumn() === 0) {
                $errors['ReportedByUserID'] = 'Reporter UserID not registered in system (Existence Check failed).';
            }
        }

        // 3. IssueTitle checks: presence, length (5 to 100)
        $title = trim($data['IssueTitle'] ?? '');
        if ($title === '') {
            $errors['IssueTitle'] = 'Issue Title cannot be blank (Null Value Check failed).';
        } elseif (mb_strlen($title) < 5) {
            $errors['IssueTitle'] = 'Issue Title must be at least 5 characters long (Limit/Boundary Check failed).';
        } elseif (mb_strlen($title) > 100) {
            $errors['IssueTitle'] = 'Issue Title cannot exceed 100 characters (Size Check failed).';
        }

        // 4. IssueDescription checks: presence, min 10 chars
        $desc = trim($data['IssueDescription'] ?? '');
        if ($desc === '') {
            $errors['IssueDescription'] = 'Issue Description is required (Null Value Check failed).';
        } elseif (mb_strlen($desc) < 10) {
            $errors['IssueDescription'] = 'Please provide sufficient details (at least 10 characters) describing the defect (Boundary Check failed).';
        }

        // 5. UrgencyLevel whitelist check
        $urgency = trim($data['UrgencyLevel'] ?? '');
        $validUrgency = ['Low', 'Medium', 'High', 'Critical'];
        if (!in_array($urgency, $validUrgency, true)) {
            $errors['UrgencyLevel'] = 'Urgency Level must be one of: Low, Medium, High, Critical (Whitelist/Format Check failed).';
        }

        // 6. ScheduledDate format & boundary check
        $date = trim($data['ScheduledDate'] ?? '');
        if ($date === '') {
            $errors['ScheduledDate'] = 'Inspection/Scheduled Date is mandatory (Null Value Check failed).';
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) {
                $errors['ScheduledDate'] = 'Inspection Date must follow format YYYY-MM-DD (Format Check failed).';
            }
        }

        return $errors;
    }
}
