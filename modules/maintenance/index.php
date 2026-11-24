<?php
/**
 * Module 5: Maintenance Ticket Log (Retrieve) & Dashboard
 * AMCS2194 Mini Project — Process 5.2
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/MaintenanceService.php';

$pageTitle = 'Module 5: Facility Maintenance & Damage Reporting';
$activeNav = 'maintenance';

$service = new MaintenanceService();

// Handle search and filters
$filters = [
    'status'      => $_GET['status'] ?? '',
    'urgency'     => $_GET['urgency'] ?? '',
    'facility_id' => $_GET['facility_id'] ?? '',
    'search'      => $_GET['search'] ?? '',
];

$tickets    = $service->getTickets($filters);
$stats      = $service->getStatistics();
$facilities = $service->getAllFacilities();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Module 5 Header & Unique Feature Callout -->
<div class="unique-feature-banner">
    <div class="unique-feature-icon">&#9888;</div>
    <div class="unique-feature-content">
        <h4>Module 5 Unique Feature: Automated Venue Locking on Hazardous Defect (Process 5.5)</h4>
        <p>When a defect report is submitted with <strong>Urgency = 'Critical'</strong>, the business logic automatically sets the facility status to <strong>'Under Maintenance'</strong> to protect students. Once marked <strong>'Completed'</strong> in Process 5.3, the venue is automatically restored to <strong>'Available'</strong>.</p>
    </div>
</div>

<!-- Key Performance Stat Counters -->
<div class="stat-grid">
    <div class="stat-card stat-crimson">
        <span class="stat-label">Total Tickets</span>
        <span class="stat-value"><?= $stats['total'] ?></span>
        <span class="stat-sub">All logged reports</span>
    </div>
    <div class="stat-card stat-amber">
        <span class="stat-label">Pending Review</span>
        <span class="stat-value"><?= $stats['pending'] ?></span>
        <span class="stat-sub">Awaiting technician</span>
    </div>
    <div class="stat-card stat-navy">
        <span class="stat-label">In-Progress Repairs</span>
        <span class="stat-value"><?= $stats['in_progress'] ?></span>
        <span class="stat-sub">Active work orders</span>
    </div>
    <div class="stat-card stat-emerald">
        <span class="stat-label">Resolved / Completed</span>
        <span class="stat-value"><?= $stats['completed'] ?></span>
        <span class="stat-sub">Finished repairs</span>
    </div>
    <div class="stat-card stat-red">
        <span class="stat-label">Critical Hazards</span>
        <span class="stat-value"><?= $stats['critical'] ?></span>
        <span class="stat-sub"><?= $stats['locked_venues'] ?> Venue(s) Locked</span>
    </div>
</div>

<!-- Ticket Log Card (Process 5.2 Retrieve) -->
<div class="card">
    <div class="card-header">
        <h2>
            <span>&#128295;</span> Maintenance Ticket Log (Process 5.2)
        </h2>
        <a href="report.php" class="btn btn-primary btn-sm">+ Log New Defect (Process 5.1)</a>
    </div>

    <div class="card-body">
        <!-- Filter Bar -->
        <form method="GET" class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_search">Search Keyword</label>
                <input type="text" id="filter_search" name="search" class="form-control" placeholder="Ticket ID, title, facility..." value="<?= htmlspecialchars($filters['search']) ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_status">Status</label>
                <select id="filter_status" name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $filters['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In-Progress" <?= $filters['status'] === 'In-Progress' ? 'selected' : '' ?>>In-Progress</option>
                    <option value="Completed" <?= $filters['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Archived" <?= $filters['status'] === 'Archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_urgency">Urgency</label>
                <select id="filter_urgency" name="urgency" class="form-select">
                    <option value="">All Urgency Levels</option>
                    <option value="Low" <?= $filters['urgency'] === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $filters['urgency'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= $filters['urgency'] === 'High' ? 'selected' : '' ?>>High</option>
                    <option value="Critical" <?= $filters['urgency'] === 'Critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_facility">Facility</label>
                <select id="filter_facility" name="facility_id" class="form-select">
                    <option value="">All Facilities</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?= htmlspecialchars($f['FacilityID']) ?>" <?= $filters['facility_id'] === $f['FacilityID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['FacilityName']) ?> (<?= htmlspecialchars($f['FacilityID']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                <a href="index.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Ticket Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Facility Details</th>
                        <th>Issue Summary</th>
                        <th>Urgency</th>
                        <th>Reported By</th>
                        <th>Dates (Sched / Res)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No maintenance tickets found matching the selected criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($t['MaintenanceID']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($t['FacilityName']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($t['LocationBlock']) ?> &bull; <?= htmlspecialchars($t['FacilityCategory']) ?></small><br>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '-', $t['FacilityStatus'])) ?>">
                                        Venue: <?= htmlspecialchars($t['FacilityStatus']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($t['IssueTitle']) ?></strong><br>
                                    <small style="color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?= htmlspecialchars($t['IssueDescription']) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($t['UrgencyLevel']) ?>">
                                        <?= htmlspecialchars($t['UrgencyLevel']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($t['ReporterName']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($t['ReportedByUserID']) ?> (<?= htmlspecialchars($t['ReporterRole']) ?>)</small>
                                </td>
                                <td>
                                    <small><strong>Sched:</strong> <?= htmlspecialchars($t['ScheduledDate']) ?></small><br>
                                    <small><strong>Res:</strong> <?= $t['ResolutionDate'] ? htmlspecialchars($t['ResolutionDate']) : '<em>Pending</em>' ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '-', $t['MaintenanceStatus'])) ?>">
                                        <?= htmlspecialchars($t['MaintenanceStatus']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <a href="update.php?id=<?= urlencode($t['MaintenanceID']) ?>" class="btn btn-secondary btn-sm" title="Edit / Update Ticket Status">
                                            Update
                                        </a>
                                        <a href="javascript:void(0)" onclick="confirmAction('Are you sure you want to permanently delete ticket <?= htmlspecialchars($t['MaintenanceID']) ?>?', 'delete.php?id=<?= urlencode($t['MaintenanceID']) ?>')" class="btn btn-danger btn-sm" title="Delete Voided Ticket">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
