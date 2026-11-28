<?php
/**
 * Facility Catalog & Directory Management
 * AMCS2194 Mini Project — Module 2 (FR-2.2 Retrieve)
 * Student 1: Tee Chuan Yang
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FacilityService.php';

$pageTitle = 'Facility Catalog & Directory — UE Campus Facility Booking System';
$activeNav = 'facility_dir';

$facilityService = new FacilityService();
$isLoggedIn      = AuthService::isLoggedIn();
$isAdmin         = AuthService::isAdmin();

$filters = [
    'search'       => $_GET['search'] ?? '',
    'category'     => $_GET['category'] ?? '',
    'min_capacity' => $_GET['min_capacity'] ?? '',
    'aircond'      => $_GET['aircond'] ?? '',
    'status'       => $_GET['status'] ?? '',
];

$facilities = $facilityService->getFacilities($filters);
$stats      = $facilityService->getStatistics();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">Facility Catalog &amp; Directory</h1>
        <p class="page-subtitle">Browse all campus academic rooms and sports courts, filter by capacity or air-conditioning, and view live status.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="floor_plan.php" class="btn btn-outline-blue">&#128506; Interactive Floor Plan</a>
        <?php if ($isAdmin): ?>
            <a href="manage.php?mode=create" class="btn btn-primary-blue">+ Add New Facility</a>
        <?php endif; ?>
    </div>
</div>

<!-- 4 Stat Metric Counters -->
<div class="stat-row-4">
    <div class="stat-box">
        <div class="stat-box-title">Total Facilities</div>
        <div class="stat-box-num num-black"><?= $stats['total_facilities'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Available</div>
        <div class="stat-box-num num-green"><?= $stats['available_count'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Under Maintenance</div>
        <div class="stat-box-num num-amber"><?= $stats['maintenance_count'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Closed / Archived</div>
        <div class="stat-box-num num-red"><?= $stats['closed_count'] ?></div>
    </div>
</div>

<!-- Table Card Container -->
<div class="ui-card">
    <form method="GET" class="filter-bar-row">
        <div style="flex: 2; min-width: 220px;">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by ID, facility name, or block..."
                   value="<?= htmlspecialchars($filters['search']) ?>">
        </div>
        <div style="flex: 1; min-width: 130px;">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <option value="Academic" <?= $filters['category'] === 'Academic' ? 'selected' : '' ?>>Academic</option>
                <option value="Sports" <?= $filters['category'] === 'Sports' ? 'selected' : '' ?>>Sports</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 130px;">
            <select name="min_capacity" class="form-select">
                <option value="">Min Capacity: Any</option>
                <option value="4" <?= $filters['min_capacity'] === '4' ? 'selected' : '' ?>>4+ pax</option>
                <option value="6" <?= $filters['min_capacity'] === '6' ? 'selected' : '' ?>>6+ pax</option>
                <option value="8" <?= $filters['min_capacity'] === '8' ? 'selected' : '' ?>>8+ pax</option>
                <option value="10" <?= $filters['min_capacity'] === '10' ? 'selected' : '' ?>>10+ pax</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 130px;">
            <select name="aircond" class="form-select">
                <option value="">Air-Cond: Any</option>
                <option value="1" <?= $filters['aircond'] === '1' ? 'selected' : '' ?>>Air-Conditioned</option>
                <option value="0" <?= $filters['aircond'] === '0' ? 'selected' : '' ?>>Non Air-Cond</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 140px;">
            <select name="status" class="form-select">
                <option value="">Status: All</option>
                <option value="Available" <?= $filters['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                <option value="Under Maintenance" <?= $filters['status'] === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                <option value="Closed" <?= $filters['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-dark">Apply Filter</button>
        </div>
    </form>

    <div class="ui-table-container">
        <table class="ui-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Facility Name</th>
                    <th>Category</th>
                    <th>Location Block</th>
                    <th>Capacity</th>
                    <th>Air-Cond</th>
                    <th>Operating Hours</th>
                    <th>Status</th>
                    <th style="white-space: nowrap; width: 150px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facilities)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No facilities found matching your filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($facilities as $f): ?>
                        <?php
                            $catBadge = $f['Category'] === 'Sports' ? 'pill-high' : 'pill-open';
                            $statusBadge = match ($f['AvailabilityStatus']) {
                                'Available'         => 'pill-resolved',
                                'Under Maintenance' => 'pill-medium',
                                default             => 'pill-critical',
                            };
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($f['FacilityID']) ?></strong></td>
                            <td><strong><?= htmlspecialchars($f['FacilityName']) ?></strong></td>
                            <td><span class="pill-badge <?= $catBadge ?>"><?= htmlspecialchars($f['Category']) ?></span></td>
                            <td><?= htmlspecialchars($f['LocationBlock']) ?></td>
                            <td><?= (int)$f['Capacity'] ?> pax</td>
                            <td><?= ((int)$f['HasAircond'] === 1) ? 'Yes' : 'No' ?></td>
                            <td><?= htmlspecialchars($f['OperatingHours']) ?></td>
                            <td><span class="pill-badge <?= $statusBadge ?>"><?= htmlspecialchars($f['AvailabilityStatus']) ?></span></td>
                            <td>
                                <?php if ($isAdmin): ?>
                                    <a href="manage.php?id=<?= urlencode($f['FacilityID']) ?>" class="btn btn-outline-blue" style="padding: 0.35rem 0.75rem; font-size: 0.82rem;">
                                        Manage / Edit
                                    </a>
                                <?php elseif ($f['AvailabilityStatus'] === 'Available'): ?>
                                    <?php if ($isLoggedIn): ?>
                                        <a href="../booking/create.php?facility_id=<?= urlencode($f['FacilityID']) ?>" class="btn btn-primary-blue" style="padding: 0.35rem 0.75rem; font-size: 0.82rem;">
                                            Book Now
                                        </a>
                                    <?php else: ?>
                                        <a href="../user/login.php?redirect=<?= urlencode('modules/booking/create.php?facility_id=' . $f['FacilityID']) ?>" class="btn btn-primary-blue" style="padding: 0.35rem 0.75rem; font-size: 0.82rem;">
                                            Book Now
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">Unavailable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
