<?php
/**
 * Business Services Tier: Facility Catalog & Interactive Floor Plan Service
 * AMCS2194 Mini Project — Module 2 (FR-2.1 to FR-2.4 + Interactive SVG Floor Plan)
 * Student 1: Tee Chuan Yang
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

class FacilityService {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
        $this->ensureSchemaReady();
    }

    private function ensureSchemaReady(): void {
        try {
            $this->db->exec("ALTER TABLE facility ADD COLUMN IF NOT EXISTS HasAircond TINYINT(1) NOT NULL DEFAULT 1");
            $this->db->exec("ALTER TABLE facility ADD COLUMN IF NOT EXISTS OperatingHours VARCHAR(50) NOT NULL DEFAULT '08:00 - 22:00'");
        } catch (Throwable $e) {}
    }

    /**
     * Generate next sequential Facility ID (e.g., FAC-06)
     */
    public function generateNextFacilityId(): string {
        $stmt = $this->db->query("SELECT FacilityID FROM facility ORDER BY FacilityID DESC LIMIT 1");
        $lastId = $stmt->fetchColumn();

        if ($lastId && preg_match('/FAC-(\d+)/', (string)$lastId, $matches)) {
            $nextNum = (int)$matches[1] + 1;
            return sprintf('FAC-%02d', $nextNum);
        }
        return 'FAC-06';
    }

    /**
     * 2.2 Search & Filter Facility Catalog (FR-2.2)
     */
    public function getFacilities(array $filters = []): array {
        $sql = 'SELECT FacilityID, FacilityName, Category, LocationBlock, Capacity, AvailabilityStatus, HasAircond, OperatingHours
                FROM facility
                WHERE 1=1';
        $params = [];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (FacilityID LIKE ? OR FacilityName LIKE ? OR LocationBlock LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $category = trim((string)($filters['category'] ?? ''));
        if ($category !== '' && in_array($category, ['Sports', 'Academic'], true)) {
            $sql .= ' AND Category = ?';
            $params[] = $category;
        }

        $minCap = (int)($filters['min_capacity'] ?? 0);
        if ($minCap > 0) {
            $sql .= ' AND Capacity >= ?';
            $params[] = $minCap;
        }

        $aircond = trim((string)($filters['aircond'] ?? ''));
        if ($aircond === '1' || $aircond === '0') {
            $sql .= ' AND HasAircond = ?';
            $params[] = (int)$aircond;
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '' && in_array($status, ['Available', 'Under Maintenance', 'Closed'], true)) {
            $sql .= ' AND AvailabilityStatus = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY FIELD(AvailabilityStatus, "Available", "Under Maintenance", "Closed"), FacilityID ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFacilityById(string $facilityId): ?array {
        $stmt = $this->db->prepare(
            'SELECT FacilityID, FacilityName, Category, LocationBlock, Capacity, AvailabilityStatus, HasAircond, OperatingHours
             FROM facility
             WHERE FacilityID = ?
             LIMIT 1'
        );
        $stmt->execute([$facilityId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getStatistics(): array {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total_facilities,
                SUM(CASE WHEN AvailabilityStatus = 'Available' THEN 1 ELSE 0 END) AS available_count,
                SUM(CASE WHEN AvailabilityStatus = 'Under Maintenance' THEN 1 ELSE 0 END) AS maintenance_count,
                SUM(CASE WHEN AvailabilityStatus = 'Closed' THEN 1 ELSE 0 END) AS closed_count
             FROM facility"
        );
        $row = $stmt->fetch();

        return [
            'total_facilities'  => (int)($row['total_facilities'] ?? 0),
            'available_count'   => (int)($row['available_count'] ?? 0),
            'maintenance_count' => (int)($row['maintenance_count'] ?? 0),
            'closed_count'      => (int)($row['closed_count'] ?? 0),
        ];
    }

    /**
     * 2.1 Add New Facility (FR-2.1)
     */
    public function addFacility(array $data): array {
        $errors = $this->validateFacilityInput($data, null);
        if (!empty($errors)) {
            return [
                'success'     => false,
                'message'     => 'Please correct the validation errors.',
                'errors'      => $errors,
                'facility_id' => null,
            ];
        }

        $facilityId     = $this->generateNextFacilityId();
        $name           = trim((string)$data['FacilityName']);
        $category       = trim((string)$data['Category']);
        $locationBlock  = trim((string)$data['LocationBlock']);
        $capacity       = (int)$data['Capacity'];
        $hasAircond     = isset($data['HasAircond']) && (string)$data['HasAircond'] === '1' ? 1 : 0;
        $operatingHours = trim((string)($data['OperatingHours'] ?? '08:00 - 22:00'));
        $status         = trim((string)($data['AvailabilityStatus'] ?? 'Available'));

        $stmt = $this->db->prepare(
            'INSERT INTO facility (FacilityID, FacilityName, Category, LocationBlock, Capacity, AvailabilityStatus, HasAircond, OperatingHours)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $facilityId,
            $name,
            $category,
            $locationBlock,
            $capacity,
            $status,
            $hasAircond,
            $operatingHours,
        ]);

        return [
            'success'     => true,
            'message'     => "Facility {$facilityId} ({$name}) added to the catalog.",
            'errors'      => [],
            'facility_id' => $facilityId,
        ];
    }

    /**
     * 2.3 Update Facility Specs (FR-2.3)
     */
    public function updateFacility(string $facilityId, array $data): array {
        $existing = $this->getFacilityById($facilityId);
        if (!$existing) {
            return [
                'success' => false,
                'message' => "Facility '{$facilityId}' not found.",
                'errors'  => ['general' => 'Facility not found.'],
            ];
        }

        $errors = $this->validateFacilityInput($data, $facilityId);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please correct the validation errors.',
                'errors'  => $errors,
            ];
        }

        $name           = trim((string)$data['FacilityName']);
        $category       = trim((string)$data['Category']);
        $locationBlock  = trim((string)$data['LocationBlock']);
        $capacity       = (int)$data['Capacity'];
        $hasAircond     = isset($data['HasAircond']) && (string)$data['HasAircond'] === '1' ? 1 : 0;
        $operatingHours = trim((string)($data['OperatingHours'] ?? '08:00 - 22:00'));
        $status         = trim((string)($data['AvailabilityStatus'] ?? 'Available'));

        $stmt = $this->db->prepare(
            'UPDATE facility
             SET FacilityName = ?, Category = ?, LocationBlock = ?, Capacity = ?, AvailabilityStatus = ?, HasAircond = ?, OperatingHours = ?
             WHERE FacilityID = ?'
        );
        $stmt->execute([
            $name,
            $category,
            $locationBlock,
            $capacity,
            $status,
            $hasAircond,
            $operatingHours,
            $facilityId,
        ]);

        return [
            'success' => true,
            'message' => "Facility {$facilityId} ({$name}) updated successfully.",
            'errors'  => [],
        ];
    }

    /**
     * 2.4 Decommission / Archive Facility (FR-2.4)
     * Blocks decommissioning if future active bookings exist (Activity Diagram 2.5).
     */
    public function decommissionFacility(string $facilityId): array {
        $existing = $this->getFacilityById($facilityId);
        if (!$existing) {
            return [
                'success' => false,
                'message' => "Facility '{$facilityId}' not found.",
            ];
        }

        if ($existing['AvailabilityStatus'] === 'Closed') {
            return [
                'success' => false,
                'message' => "Facility {$facilityId} is already Closed / Archived.",
            ];
        }

        // Check future active bookings in BOOKING
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM booking
             WHERE FacilityID = ?
               AND BookingDate >= CURDATE()
               AND BookingStatus IN ('Confirmed', 'Checked-In')"
        );
        $stmt->execute([$facilityId]);
        $activeCount = (int)$stmt->fetchColumn();

        if ($activeCount > 0) {
            return [
                'success' => false,
                'message' => "Cannot Archive: {$existing['FacilityName']} has {$activeCount} active upcoming booking(s).",
            ];
        }

        $upd = $this->db->prepare("UPDATE facility SET AvailabilityStatus = 'Closed' WHERE FacilityID = ?");
        $upd->execute([$facilityId]);

        return [
            'success' => true,
            'message' => "Facility {$facilityId} ({$existing['FacilityName']}) has been decommissioned (Closed).",
        ];
    }

    public function getActiveBookingCount(string $facilityId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM booking
             WHERE FacilityID = ?
               AND BookingDate >= CURDATE()
               AND BookingStatus IN ('Confirmed', 'Checked-In')"
        );
        $stmt->execute([$facilityId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Unique Feature: Live Floor Plan Occupancy Data (Activity Diagram 2.1)
     */
    public function getFloorPlanData(string $date, string $startTime, string $endTime): array {
        $facilities = $this->getFacilities([]);
        $sTime = strlen($startTime) === 5 ? $startTime . ':00' : $startTime;
        $eTime = strlen($endTime) === 5 ? $endTime . ':00' : $endTime;

        $stmt = $this->db->prepare(
            "SELECT FacilityID, BookingID, StartTime, EndTime
             FROM booking
             WHERE BookingDate = ?
               AND BookingStatus IN ('Confirmed', 'Checked-In')
               AND StartTime < ?
               AND EndTime > ?"
        );
        $stmt->execute([$date, $eTime, $sTime]);
        $conflicts = [];
        foreach ($stmt->fetchAll() as $row) {
            $conflicts[$row['FacilityID']] = $row;
        }

        $result = [];
        foreach ($facilities as $f) {
            $fid = $f['FacilityID'];
            if ($f['AvailabilityStatus'] !== 'Available') {
                $liveState = 'maintenance';
                $liveLabel = $f['AvailabilityStatus'];
            } elseif (isset($conflicts[$fid])) {
                $liveState = 'occupied';
                $liveLabel = 'Occupied (' . substr($conflicts[$fid]['StartTime'], 0, 5) . ' - ' . substr($conflicts[$fid]['EndTime'], 0, 5) . ')';
            } else {
                $liveState = 'available';
                $liveLabel = 'Available';
            }
            $f['live_state'] = $liveState;
            $f['live_label'] = $liveLabel;
            $result[$fid] = $f;
        }

        return $result;
    }

    private function validateFacilityInput(array $data, ?string $excludeFacilityId): array {
        $errors = [];

        $name = trim((string)($data['FacilityName'] ?? ''));
        if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors['FacilityName'] = 'Facility name must be between 3 and 100 characters.';
        } elseif ($this->facilityNameExists($name, $excludeFacilityId)) {
            $errors['FacilityName'] = "A facility named '{$name}' already exists.";
        }

        $category = trim((string)($data['Category'] ?? ''));
        if (!in_array($category, ['Sports', 'Academic'], true)) {
            $errors['Category'] = 'Please select either Sports or Academic.';
        }

        $location = trim((string)($data['LocationBlock'] ?? ''));
        if ($location === '' || mb_strlen($location) < 2 || mb_strlen($location) > 50) {
            $errors['LocationBlock'] = 'Location block must be between 2 and 50 characters.';
        }

        $capacity = (int)($data['Capacity'] ?? 0);
        if ($capacity < 1 || $capacity > 500) {
            $errors['Capacity'] = 'Capacity must be between 1 and 500.';
        }

        $hours = trim((string)($data['OperatingHours'] ?? ''));
        if ($hours === '' || mb_strlen($hours) > 50) {
            $errors['OperatingHours'] = 'Operating hours are required (e.g., 08:00 - 22:00).';
        }

        $status = trim((string)($data['AvailabilityStatus'] ?? 'Available'));
        if (!in_array($status, ['Available', 'Under Maintenance', 'Closed'], true)) {
            $errors['AvailabilityStatus'] = 'Invalid availability status.';
        }

        return $errors;
    }

    private function facilityNameExists(string $name, ?string $excludeId): bool {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT 1 FROM facility WHERE LOWER(FacilityName) = LOWER(?) AND FacilityID != ? LIMIT 1');
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT 1 FROM facility WHERE LOWER(FacilityName) = LOWER(?) LIMIT 1');
            $stmt->execute([$name]);
        }
        return (bool)$stmt->fetchColumn();
    }
}
