<?php
/**
 * Admin User Account Directory & Management Dashboard
 * AMCS2194 Mini Project — Module 1 (FR-1.2 Retrieve / Admin Directory)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/UserService.php';

$pageTitle = 'User Account Directory — UE Campus Facility Booking System';
$activeNav = 'users';

if (!AuthService::isAdmin()) {
    AuthService::setFlash('danger', 'Administrator access is required to manage all user accounts.');
    header('Location: profile.php');
    exit;
}

$userService = new UserService();

$filters = [
    'search' => $_GET['search'] ?? '',
    'role'   => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
];

$users = $userService->getUsers($filters);
$stats = $userService->getStatistics();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">User Account &amp; Access Directory</h1>
        <p class="page-subtitle">Search, create, update roles, or deactivate campus user accounts while preserving historical records.</p>
    </div>
    <div>
        <a href="manage.php?mode=create" class="btn btn-primary-blue">+ Add New User Account</a>
    </div>
</div>

<!-- 4 Stat Metric Counters -->
<div class="stat-row-4">
    <div class="stat-box">
        <div class="stat-box-title">Total Registered Users</div>
        <div class="stat-box-num num-black"><?= $stats['total_users'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Active Accounts</div>
        <div class="stat-box-num num-green"><?= $stats['active_users'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Suspended Accounts</div>
        <div class="stat-box-num num-amber"><?= $stats['suspended_users'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Inactive / Deactivated</div>
        <div class="stat-box-num num-red"><?= $stats['inactive_users'] ?></div>
    </div>
</div>

<!-- Table Card Container -->
<div class="ui-card">
    <form method="GET" class="filter-bar-row">
        <div style="flex: 2; min-width: 240px;">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by User ID, full name, or campus email..."
                   value="<?= htmlspecialchars($filters['search']) ?>">
        </div>
        <div style="flex: 1; min-width: 150px;">
            <select name="role" class="form-select">
                <option value="">Role: All Roles</option>
                <option value="Student" <?= $filters['role'] === 'Student' ? 'selected' : '' ?>>Student</option>
                <option value="Staff" <?= $filters['role'] === 'Staff' ? 'selected' : '' ?>>Staff</option>
                <option value="Technician" <?= $filters['role'] === 'Technician' ? 'selected' : '' ?>>Technician</option>
                <option value="Admin" <?= $filters['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 150px;">
            <select name="status" class="form-select">
                <option value="">Status: All Statuses</option>
                <option value="Active" <?= $filters['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Suspended" <?= $filters['status'] === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                <option value="Inactive" <?= $filters['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
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
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Campus Email</th>
                    <th>Phone No</th>
                    <th>Role</th>
                    <th>Account Status</th>
                    <th style="white-space: nowrap; width: 140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No user accounts found matching your filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                            $roleBadge = match ($u['UserRole']) {
                                'Admin'      => 'pill-critical',
                                'Technician' => 'pill-high',
                                'Staff'      => 'pill-in-progress',
                                default      => 'pill-open',
                            };
                            $statusBadge = match ($u['AccountStatus']) {
                                'Active'    => 'pill-resolved',
                                'Suspended' => 'pill-medium',
                                default     => 'pill-critical',
                            };
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['UserID']) ?></strong></td>
                            <td><?= htmlspecialchars($u['FullName']) ?></td>
                            <td><?= htmlspecialchars($u['CampusEmail']) ?></td>
                            <td><?= htmlspecialchars($u['PhoneNo']) ?></td>
                            <td><span class="pill-badge <?= $roleBadge ?>"><?= htmlspecialchars($u['UserRole']) ?></span></td>
                            <td><span class="pill-badge <?= $statusBadge ?>"><?= htmlspecialchars($u['AccountStatus']) ?></span></td>
                            <td>
                                <a href="manage.php?id=<?= urlencode($u['UserID']) ?>" class="btn btn-outline-blue" style="padding: 0.35rem 0.75rem; font-size: 0.82rem;">
                                    Manage / Edit
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
