<?php
/**
 * Module 5: Update Repair Status & Assign Technician (Update)
 * AMCS2194 Mini Project — Process 5.3 & 5.5
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/MaintenanceService.php';

$pageTitle = 'Update Repair Ticket — Module 5';
$activeNav = 'maintenance';

$service = new MaintenanceService();
$ticketId = $_GET['id'] ?? '';

$ticket = $service->getTicketById($ticketId);
if (!$ticket) {
    AuthService::setFlash('danger', 'Maintenance ticket not found.');
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status         = $_POST['MaintenanceStatus'] ?? '';
    $resolutionDate = !empty($_POST['ResolutionDate']) ? $_POST['ResolutionDate'] : null;

    $result = $service->updateTicketStatus($ticketId, $status, $resolutionDate);

    if ($result['success']) {
        AuthService::setFlash('success', $result['message']);
        header('Location: index.php');
        exit;
    } else {
        AuthService::setFlash('danger', $result['message']);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2><span>&#128295;</span> Update Repair Status: <?= htmlspecialchars($ticket['MaintenanceID']) ?> (Process 5.3)</h2>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Ticket Log</a>
    </div>

    <div class="card-body">
        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
            <div class="form-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 0.5rem;">
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Facility</span><br>
                    <strong><?= htmlspecialchars($ticket['FacilityName']) ?></strong> (<?= htmlspecialchars($ticket['LocationBlock']) ?>)<br>
                    <span class="badge badge-<?= strtolower(str_replace(' ', '-', $ticket['FacilityStatus'])) ?>">
                        Current Venue Status: <?= htmlspecialchars($ticket['FacilityStatus']) ?>
                    </span>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Reported By</span><br>
                    <strong><?= htmlspecialchars($ticket['ReporterName']) ?></strong> (<?= htmlspecialchars($ticket['ReportedByUserID']) ?>)<br>
                    <span class="badge badge-<?= strtolower($ticket['UrgencyLevel']) ?>">Urgency: <?= htmlspecialchars($ticket['UrgencyLevel']) ?></span>
                </div>
            </div>
            <div style="margin-top: 0.75rem;">
                <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Issue Summary</span><br>
                <strong><?= htmlspecialchars($ticket['IssueTitle']) ?></strong>
                <p style="margin-top: 0.25rem; font-size: 0.9rem; color: #475569;"><?= nl2br(htmlspecialchars($ticket['IssueDescription'])) ?></p>
            </div>
        </div>

        <?php if ($ticket['FacilityStatus'] === 'Under Maintenance' && $ticket['UrgencyLevel'] === 'Critical'): ?>
            <div class="alert alert-warning" style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.3rem;">&#9888;</span>
                <div>
                    <strong>Automated Venue Lock Active:</strong> Setting this ticket status to <strong>'Completed'</strong> will automatically unlock <em><?= htmlspecialchars($ticket['FacilityName']) ?></em> back to <strong>'Available'</strong>!
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="update.php?id=<?= urlencode($ticket['MaintenanceID']) ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="MaintenanceStatus">Repair Status <span class="required-star">*</span></label>
                    <select id="MaintenanceStatus" name="MaintenanceStatus" class="form-select" required>
                        <option value="Pending" <?= $ticket['MaintenanceStatus'] === 'Pending' ? 'selected' : '' ?>>Pending (Awaiting Technician)</option>
                        <option value="In-Progress" <?= $ticket['MaintenanceStatus'] === 'In-Progress' ? 'selected' : '' ?>>In-Progress (Repairs Underway)</option>
                        <option value="Completed" <?= $ticket['MaintenanceStatus'] === 'Completed' ? 'selected' : '' ?>>Completed (Issue Resolved & Auto-Unlock)</option>
                        <option value="Archived" <?= $ticket['MaintenanceStatus'] === 'Archived' ? 'selected' : '' ?>>Archived (Closed Out)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ResolutionDate">Resolution Date</label>
                    <input type="date" id="ResolutionDate" name="ResolutionDate" class="form-control" value="<?= htmlspecialchars($ticket['ResolutionDate'] ?? date('Y-m-d')) ?>">
                    <span class="form-hint">Automatically set to current date upon marking Completed</span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Repair Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
