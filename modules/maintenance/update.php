<?php
/**
 * Update Repair Status, Assign Technician & Close/Delete Ticket
 * AMCS2194 Mini Project — Process 5.3 (Update), 5.4 (Delete) & 5.5 (Venue Lock/Unlock)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/MaintenanceService.php';

$pageTitle = 'Update Repair Status, Assign Technician & Close/Delete Ticket';
$activeNav = 'maintenance';

$service  = new MaintenanceService();
$ticketId = $_GET['id'] ?? 'MT-1042';

$ticket = $service->getTicketById($ticketId);
if (!$ticket) {
    AuthService::setFlash('danger', 'Maintenance ticket not found.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_ticket'])) {
        $delRes = $service->deleteTicket($ticket['MaintenanceID']);
        if ($delRes['success']) {
            AuthService::setFlash('success', $delRes['message']);
            header('Location: index.php');
            exit;
        } else {
            AuthService::setFlash('danger', $delRes['message']);
        }
    } elseif (isset($_POST['approve_lockdown'])) {
        $appRes = $service->approveVenueLockdown($ticket['MaintenanceID']);
        if ($appRes['success']) {
            AuthService::setFlash('success', $appRes['message']);
        } else {
            AuthService::setFlash('danger', $appRes['message']);
        }
        header('Location: update.php?id=' . urlencode($ticket['MaintenanceID']));
        exit;
    } elseif (isset($_POST['reject_lockdown'])) {
        $rejRes = $service->rejectVenueLockdown($ticket['MaintenanceID']);
        if ($rejRes['success']) {
            AuthService::setFlash('info', $rejRes['message']);
        } else {
            AuthService::setFlash('danger', $rejRes['message']);
        }
        header('Location: update.php?id=' . urlencode($ticket['MaintenanceID']));
        exit;
    } else {
        $result = $service->updateTicket($ticket['MaintenanceID'], $_POST);
        if ($result['success']) {
            AuthService::setFlash('success', $result['message']);
            header('Location: index.php');
            exit;
        } else {
            AuthService::setFlash('danger', $result['message']);
        }
    }
}

// Reload ticket state for accurate display
$ticket = $service->getTicketById($ticketId);
$cleanId = '#' . ltrim($ticket['MaintenanceID'], '#');
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header with Back Button -->
<div class="page-header-row">
    <div>
        <h1 class="page-title">Update Repair Status, Assign Technician &amp; Close/Delete Ticket</h1>
        <p class="page-subtitle">
            Ticket <?= htmlspecialchars($cleanId) ?> | <?= htmlspecialchars($ticket['FacilityName']) ?> (<?= htmlspecialchars($ticket['FacilityID']) ?>) — Reported by <?= htmlspecialchars($ticket['ReportedByUserID']) ?> on <?= htmlspecialchars(date('d M Y', strtotime($ticket['ScheduledDate']))) ?>
        </p>
    </div>
    <div>
        <a href="index.php" class="btn btn-cancel">&larr; Back to Ticket Log</a>
    </div>
</div>

<!-- Admin Lockdown Approval Banner (FR-5.5) -->
<?php if ($ticket['UrgencyLevel'] === 'Critical' && ($ticket['LockdownStatus'] ?? 'None') === 'Pending_Review'): ?>
    <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 1.15rem 1.35rem; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.85rem;">
            <div>
                <strong style="color: #991b1b; font-size: 1rem;">🚨 Critical Hazard Reported: Awaiting Admin Lockdown Approval (FR-5.5)</strong>
                <p style="margin: 0.25rem 0 0 0; color: #7f1d1d; font-size: 0.88rem;">
                    A student lodged an urgent safety report for <?= htmlspecialchars($ticket['FacilityName']) ?>. To protect students and prevent accidents, approve emergency lockdown to immediately update the venue status to <strong>Under Maintenance</strong>.
                </p>
            </div>
            <div style="display: flex; gap: 0.6rem; align-items: center;">
                <form method="POST" action="update.php?id=<?= urlencode($ticket['MaintenanceID']) ?>" style="margin: 0;">
                    <input type="hidden" name="approve_lockdown" value="1">
                    <button type="submit" class="btn btn-danger-red" style="padding: 0.55rem 1.15rem; font-size: 0.85rem;">
                        Approve &amp; Lock Venue (FR-5.5)
                    </button>
                </form>
                <form method="POST" action="update.php?id=<?= urlencode($ticket['MaintenanceID']) ?>" style="margin: 0;">
                    <input type="hidden" name="reject_lockdown" value="1">
                    <button type="submit" class="btn btn-cancel" style="padding: 0.55rem 0.9rem; font-size: 0.85rem;">
                        Dismiss (Keep Available)
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Two-Column Layout -->
<div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 1.5rem; align-items: flex-start;">
    
    <!-- Left Column: Form -->
    <div class="ui-card" style="margin-bottom: 0;">
        <form method="POST" action="update.php?id=<?= urlencode($ticket['MaintenanceID']) ?>">
            
            <!-- Assigned Maintenance Technician -->
            <div class="form-group">
                <label for="AssignedTechnician">Assigned Maintenance Technician *</label>
                <select id="AssignedTechnician" name="AssignedTechnician" class="form-select" required>
                    <option value="Encik Hafiz Rahman (Electrical Unit)" <?= str_contains($ticket['AssignedTechnician'] ?? '', 'Hafiz') ? 'selected' : '' ?>>Encik Hafiz Rahman (Electrical Unit)</option>
                    <option value="Mr. Tan Wei Ming (General Maintenance)" <?= str_contains($ticket['AssignedTechnician'] ?? '', 'Tan Wei Ming') ? 'selected' : '' ?>>Mr. Tan Wei Ming (General Maintenance)</option>
                    <option value="Mr. Rajan (AV & IT Specialist)" <?= str_contains($ticket['AssignedTechnician'] ?? '', 'Rajan') ? 'selected' : '' ?>>Mr. Rajan (AV &amp; IT Specialist)</option>
                </select>
            </div>

            <!-- Ticket Repair Status -->
            <div class="form-group">
                <label for="TicketRepairStatus">Ticket Repair Status *</label>
                <select id="TicketRepairStatus" name="TicketRepairStatus" class="form-select" required>
                    <option value="Resolved" <?= $ticket['MaintenanceStatus'] === 'Completed' ? 'selected' : '' ?>>Resolved (Completed Repair &amp; Safety Inspection)</option>
                    <option value="In Progress" <?= $ticket['MaintenanceStatus'] === 'In-Progress' ? 'selected' : '' ?>>In Progress (Repairs Underway)</option>
                    <option value="Open" <?= $ticket['MaintenanceStatus'] === 'Pending' ? 'selected' : '' ?>>Open (Awaiting Inspection)</option>
                </select>
            </div>

            <!-- Technician Resolution Notes -->
            <div class="form-group">
                <label for="TechnicianResolutionNotes">Technician Resolution Notes *</label>
                <textarea id="TechnicianResolutionNotes" name="TechnicianResolutionNotes" rows="3" class="form-control" placeholder="Describe actions performed, safety tests conducted, and parts replaced..." required><?= htmlspecialchars($ticket['IssueDescription'] ?? 'Replaced damaged wall socket outlet and insulated projector power line. Tested voltage stability; room cleared for student use.') ?></textarea>
            </div>

            <!-- Automated Venue Unlock Banner (FR-5.5) -->
            <div class="alert-banner-green">
                <span style="font-weight: 700;">&#10003;</span>
                <div>
                    <strong>Automated Venue Unlock (FR-5.5):</strong> Marking this ticket as <em>Resolved</em> automatically restores <?= htmlspecialchars($ticket['FacilityID']) ?> status to <strong>Available</strong> in the FACILITY table.
                </div>
            </div>

            <!-- Buttons -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="index.php" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-primary-blue">Save Repair Update</button>
            </div>

        </form>
    </div>

    <!-- Right Column: Metadata & Delete Box -->
    <div>
        
        <!-- Ticket Metadata Summary -->
        <div class="metadata-panel">
            <h4>Ticket Metadata Summary</h4>
            <div class="metadata-item">
                Facility: <strong><?= htmlspecialchars($ticket['FacilityName']) ?> (<?= htmlspecialchars($ticket['LocationBlock']) ?>)</strong>
            </div>
            <div class="metadata-item">
                Category: <strong><?= htmlspecialchars($ticket['DefectCategory'] ?? 'Electrical & Power Supply') ?></strong>
            </div>
            <div class="metadata-item">
                Urgency: <strong style="color: <?= $ticket['UrgencyLevel'] === 'Critical' ? '#dc2626' : '#c2410c' ?>;"><?= htmlspecialchars($ticket['UrgencyLevel'] === 'Critical' ? 'Critical / Hazardous' : $ticket['UrgencyLevel']) ?></strong>
            </div>
            <div class="metadata-item">
                Current Venue Lock: 
                <?php if ($ticket['FacilityStatus'] === 'Under Maintenance'): ?>
                    <strong style="color: #dc2626;">Under Maintenance (Locked)</strong>
                <?php elseif (($ticket['LockdownStatus'] ?? 'None') === 'Pending_Review'): ?>
                    <strong style="color: #c2410c;">Awaiting Admin Lockdown Approval</strong>
                <?php else: ?>
                    <strong style="color: #16a34a;">Available (Open for Booking)</strong>
                <?php endif; ?>
            </div>
            <div class="metadata-item">
                Reported Date: <?= htmlspecialchars(date('d M Y', strtotime($ticket['ScheduledDate']))) ?>
            </div>
            <?php if (!empty($ticket['ResolutionDate'])): ?>
                <div class="metadata-item">
                    Resolved Timestamp: <?= htmlspecialchars(date('d M Y, 16:15', strtotime($ticket['ResolutionDate']))) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Close / Delete Duplicate or Voided Ticket (FR-5.4) -->
        <div class="alert-banner-red" style="margin-top: 0;">
            <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.35rem;">
                Close / Delete Duplicate or Voided Ticket (FR-5.4)
            </div>
            <p style="font-size: 0.85rem; color: #7f1d1d; margin-bottom: 1rem; line-height: 1.4;">
                Permanently delete invalid, duplicate, or resolved archived tickets from the MAINTENANCE table.
            </p>
            <form method="POST" action="update.php?id=<?= urlencode($ticket['MaintenanceID']) ?>" onsubmit="return confirm('Permanently delete ticket <?= htmlspecialchars($cleanId) ?>? (FR-5.4)');">
                <input type="hidden" name="delete_ticket" value="1">
                <button type="submit" class="btn btn-danger-red" style="width: 100%;">
                    Delete / Purge Ticket <?= htmlspecialchars($cleanId) ?>
                </button>
            </form>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
