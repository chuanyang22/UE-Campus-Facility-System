<?php
/**
 * Maintenance Ticket Log & Work Order Dashboard
 * AMCS2194 Mini Project — Process 5.2 (Retrieve)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/MaintenanceService.php';

$pageTitle = 'Maintenance Ticket Log & Work Order Dashboard';
$activeNav = 'maintenance';

$service = new MaintenanceService();

$filters = [
    'search'  => $_GET['search'] ?? '',
    'urgency' => $_GET['urgency'] ?? '',
    'status'  => $_GET['status'] ?? '',
];

$tickets = $service->getTickets($filters);
$stats   = $service->getStatistics();
$pendingLockdowns = $service->getPendingLockdowns();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header with Action Button -->
<div class="page-header-row">
    <div>
        <h1 class="page-title">Maintenance Ticket Log &amp; Work Order Dashboard</h1>
        <p class="page-subtitle">Monitor reported facility defects, filter by urgency or repair status, and track technician assignments.</p>
    </div>
    <div>
        <a href="report.php" class="btn btn-primary-blue">+ Log New Defect Ticket</a>
    </div>
</div>

<!-- Pending Lockdown Alert Banner if critical defects need approval -->
<?php if (!empty($pendingLockdowns)): ?>
    <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <strong style="color: #991b1b;">🚨 Emergency Hazard Alert:</strong>
            <span style="color: #7f1d1d; font-size: 0.9rem; margin-left: 0.35rem;">
                <?= count($pendingLockdowns) ?> venue(s) have critical safety defect reports awaiting Admin Lockdown Approval.
            </span>
        </div>
        <a href="update.php?id=<?= urlencode($pendingLockdowns[0]['MaintenanceID']) ?>" class="btn btn-danger-red" style="padding: 0.45rem 0.9rem; font-size: 0.82rem;">
            Review Urgent Lockdown &rarr;
        </a>
    </div>
<?php endif; ?>

<!-- 4 Stat Metric Counters -->
<div class="stat-row-4">
    <div class="stat-box">
        <div class="stat-box-title">Total Active Tickets</div>
        <div class="stat-box-num num-black"><?= $stats['total_active'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Critical / Locked Venues</div>
        <div class="stat-box-num num-red"><?= $stats['locked_venues'] ?> Venues</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">In Progress Repairs</div>
        <div class="stat-box-num num-amber"><?= $stats['in_progress'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Resolved This Month</div>
        <div class="stat-box-num num-green"><?= $stats['resolved_month'] ?></div>
    </div>
</div>

<!-- Table Card Container -->
<div class="ui-card">
    
    <!-- Filter Bar -->
    <form method="GET" class="filter-bar-row">
        <div style="flex: 2; min-width: 250px;">
            <input type="text" name="search" class="form-control" placeholder="Search by Ticket ID, facility name, or keyword..." value="<?= htmlspecialchars($filters['search']) ?>">
        </div>
        <div style="flex: 1; min-width: 160px;">
            <select name="urgency" class="form-select">
                <option value="">Urgency: All Levels</option>
                <option value="Critical" <?= $filters['urgency'] === 'Critical' ? 'selected' : '' ?>>Critical / Hazardous</option>
                <option value="High" <?= $filters['urgency'] === 'High' ? 'selected' : '' ?>>High</option>
                <option value="Medium" <?= $filters['urgency'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                <option value="Low" <?= $filters['urgency'] === 'Low' ? 'selected' : '' ?>>Low</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 160px;">
            <select name="status" class="form-select">
                <option value="">Status: All Tickets</option>
                <option value="Open" <?= $filters['status'] === 'Open' ? 'selected' : '' ?>>Open</option>
                <option value="In-Progress" <?= $filters['status'] === 'In-Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Resolved" <?= $filters['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-dark">Apply Filter</button>
        </div>
    </form>

    <!-- Data Table -->
    <div class="ui-table-container">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Facility</th>
                    <th>Defect Category</th>
                    <th>Urgency</th>
                    <th>Assigned Technician</th>
                    <th>Status</th>
                    <th>Reported At</th>
                    <th style="white-space: nowrap; width: 140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No maintenance tickets found matching the search criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                        <?php
                            $tid = '#' . ltrim($t['MaintenanceID'], '#');
                            $urgencyText = $t['UrgencyLevel'] === 'Critical' ? 'Critical / Hazardous' : $t['UrgencyLevel'];
                            $urgencyClass = strtolower($t['UrgencyLevel']);
                            
                            $statusDisplay = 'Open';
                            $statusClass = 'open';
                            if ($t['MaintenanceStatus'] === 'Completed') {
                                $statusDisplay = 'Resolved';
                                $statusClass = 'resolved';
                            } elseif ($t['MaintenanceStatus'] === 'In-Progress') {
                                $statusDisplay = 'In Progress';
                                $statusClass = 'in-progress';
                            }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($tid) ?></strong></td>
                            <td>
                                <?= htmlspecialchars($t['FacilityName']) ?> 
                                <span style="color: #64748b; font-size: 0.82rem;">(<?= htmlspecialchars($t['FacilityID']) ?>)</span>
                            </td>
                            <td><?= htmlspecialchars($t['DefectCategory'] ?? $t['IssueTitle']) ?></td>
                            <td>
                                <span class="pill-badge pill-<?= $urgencyClass ?>">
                                    <?= htmlspecialchars($urgencyText) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($t['AssignedTechnician'] ?? 'Unassigned') ?></td>
                            <td>
                                <span class="pill-badge pill-<?= $statusClass ?>">
                                    <?= htmlspecialchars($statusDisplay) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($t['ScheduledDate']))) ?></td>
                            <td style="white-space: nowrap; width: 140px;">
                                <a href="update.php?id=<?= urlencode($t['MaintenanceID']) ?>" class="btn btn-outline-blue" style="white-space: nowrap;">
                                    <?= $t['MaintenanceStatus'] === 'Completed' ? 'View Details' : 'Manage / Update' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
