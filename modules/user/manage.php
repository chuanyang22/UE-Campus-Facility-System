<?php
/**
 * Admin Create, Update Role/Status & Deactivate User Account
 * AMCS2194 Mini Project — Module 1 (FR-1.1, FR-1.3, FR-1.4)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/UserService.php';

if (!AuthService::isAdmin()) {
    AuthService::setFlash('danger', 'Administrator access is required.');
    header('Location: profile.php');
    exit;
}

$userService = new UserService();
$currentUser = AuthService::getCurrentUser();

$mode   = ($_GET['mode'] ?? '') === 'create' ? 'create' : 'edit';
$userId = trim((string)($_GET['id'] ?? ''));

$user = null;
if ($mode === 'edit') {
    $user = $userService->getUserById($userId);
    if (!$user) {
        AuthService::setFlash('danger', 'Selected user account was not found.');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = $mode === 'create'
    ? 'Add New User Account — UE Campus Facility Booking System'
    : "Manage User {$user['UserID']} — UE Campus Facility Booking System";
$activeNav = 'users';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($mode === 'create') {
        $res = $userService->registerUser($_POST, true);
        if ($res['success']) {
            AuthService::setFlash('success', $res['message']);
            header('Location: index.php');
            exit;
        }
        $errors = $res['errors'];
    } elseif ($action === 'deactivate') {
        $res = $userService->deactivateUser($userId, $currentUser['UserID']);
        AuthService::setFlash($res['success'] ? 'success' : 'danger', $res['message']);
        header('Location: ' . ($res['success'] ? 'index.php' : 'manage.php?id=' . urlencode($userId)));
        exit;
    } elseif ($action === 'reactivate') {
        $res = $userService->reactivateUser($userId);
        AuthService::setFlash($res['success'] ? 'success' : 'danger', $res['message']);
        header('Location: manage.php?id=' . urlencode($userId));
        exit;
    } else {
        $res = $userService->adminUpdateUser($userId, $_POST);
        if ($res['success']) {
            if ($userId === $currentUser['UserID']) {
                AuthService::setActiveUser($userId);
            }
            AuthService::setFlash('success', $res['message']);
            header('Location: index.php');
            exit;
        }
        $errors = $res['errors'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">
            <?= $mode === 'create' ? 'Create New User Account (FR-1.1)' : 'Manage User Account: ' . htmlspecialchars($user['UserID']) ?>
        </h1>
        <p class="page-subtitle">
            <?= $mode === 'create'
                ? 'Provision a new Student, Staff, Technician, or Administrator account.'
                : 'Update account details, assign access roles, or deactivate the account (soft-delete).' ?>
        </p>
    </div>
    <div>
        <a href="index.php" class="btn btn-cancel">&larr; Back to User Directory</a>
    </div>
</div>

<?php if ($mode === 'create'): ?>
    <div class="ui-card" style="max-width: 780px;">
        <?php if (!empty($errors)): ?>
            <div class="alert-banner-red" style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; border-radius: 8px;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 0.35rem 0 0 1.2rem; font-size: 0.86rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="UserID">User ID * <span class="field-code">(UserID)</span></label>
                    <input type="text" id="UserID" name="UserID"
                           class="form-control <?= isset($errors['UserID']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. A240200 or T0002" maxlength="10"
                           value="<?= htmlspecialchars($_POST['UserID'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="UserRole">User Role * <span class="field-code">(UserRole)</span></label>
                    <select id="UserRole" name="UserRole" class="form-select">
                        <?php foreach (['Student', 'Staff', 'Technician', 'Admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= ($_POST['UserRole'] ?? 'Student') === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="FullName">Full Name * <span class="field-code">(FullName)</span></label>
                <input type="text" id="FullName" name="FullName"
                       class="form-control <?= isset($errors['FullName']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($_POST['FullName'] ?? '') ?>" required>
            </div>

            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="CampusEmail">Campus Email * <span class="field-code">(CampusEmail)</span></label>
                    <input type="email" id="CampusEmail" name="CampusEmail"
                           class="form-control <?= isset($errors['CampusEmail']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($_POST['CampusEmail'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="PhoneNo">Phone Number * <span class="field-code">(PhoneNo)</span></label>
                    <input type="text" id="PhoneNo" name="PhoneNo"
                           class="form-control <?= isset($errors['PhoneNo']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($_POST['PhoneNo'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="Password">Initial Password * <span class="field-code">(Min 6 chars)</span></label>
                    <input type="password" id="Password" name="Password"
                           class="form-control <?= isset($errors['Password']) ? 'is-invalid' : '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="ConfirmPassword">Confirm Password *</label>
                    <input type="password" id="ConfirmPassword" name="ConfirmPassword"
                           class="form-control <?= isset($errors['ConfirmPassword']) ? 'is-invalid' : '' ?>" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <a href="index.php" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-primary-blue">Create User Account</button>
            </div>
        </form>
    </div>
<?php else: ?>
    <?php $summary = $userService->getUserBookingSummary($user['UserID']); ?>
    <div class="form-grid-2col" style="align-items: start;">
        <!-- Left Column: Edit User Form -->
        <div class="ui-card" style="margin-bottom: 0;">
            <?php if (!empty($errors)): ?>
                <div class="alert-banner-red" style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; border-radius: 8px;">
                    <strong>Validation Error:</strong>
                    <ul style="margin: 0.35rem 0 0 1.2rem; font-size: 0.86rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="action" value="save">

                <div class="form-group">
                    <label for="FullName">Full Name * <span class="field-code">(FullName)</span></label>
                    <input type="text" id="FullName" name="FullName"
                           class="form-control <?= isset($errors['FullName']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($_POST['FullName'] ?? $user['FullName']) ?>" required>
                </div>

                <div class="form-grid-2col">
                    <div class="form-group">
                        <label for="CampusEmail">Campus Email * <span class="field-code">(CampusEmail)</span></label>
                        <input type="email" id="CampusEmail" name="CampusEmail"
                               class="form-control <?= isset($errors['CampusEmail']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['CampusEmail'] ?? $user['CampusEmail']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="PhoneNo">Phone Number * <span class="field-code">(PhoneNo)</span></label>
                        <input type="text" id="PhoneNo" name="PhoneNo"
                               class="form-control <?= isset($errors['PhoneNo']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['PhoneNo'] ?? $user['PhoneNo']) ?>" required>
                    </div>
                </div>

                <div class="form-grid-2col">
                    <div class="form-group">
                        <label for="UserRole">Access Role * <span class="field-code">(UserRole)</span></label>
                        <select id="UserRole" name="UserRole" class="form-select">
                            <?php foreach (['Student', 'Staff', 'Technician', 'Admin'] as $r): ?>
                                <option value="<?= $r ?>" <?= ($_POST['UserRole'] ?? $user['UserRole']) === $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="AccountStatus">Account Status * <span class="field-code">(AccountStatus)</span></label>
                        <select id="AccountStatus" name="AccountStatus" class="form-select">
                            <?php foreach (['Active', 'Suspended', 'Inactive'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($_POST['AccountStatus'] ?? $user['AccountStatus']) === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="NewPassword">Reset Password <span class="field-code">(Optional — leave blank to keep current)</span></label>
                    <input type="password" id="NewPassword" name="NewPassword"
                           class="form-control <?= isset($errors['NewPassword']) ? 'is-invalid' : '' ?>"
                           placeholder="Enter new password (min 6 chars)">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                    <a href="index.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-primary-blue">Save User Changes</button>
                </div>
            </form>
        </div>

        <!-- Right Column: Metadata & Danger Zone (FR-1.4 Deactivate) -->
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="ui-card" style="margin-bottom: 0;">
                <h2 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.85rem;">
                    Account Metadata Summary
                </h2>
                <div style="display: flex; flex-direction: column; gap: 0.55rem; font-size: 0.9rem;">
                    <div><span style="color: var(--text-muted);">User ID:</span> <strong><?= htmlspecialchars($user['UserID']) ?></strong></div>
                    <div><span style="color: var(--text-muted);">Total Bookings:</span> <strong><?= $summary['total_bookings'] ?></strong></div>
                    <div><span style="color: var(--text-muted);">Completed Sessions:</span> <strong><?= $summary['completed_bookings'] ?></strong></div>
                    <div><span style="color: var(--text-muted);">No-Show Strikes:</span> <strong><?= $summary['no_show_count'] ?> / 3</strong></div>
                </div>
            </div>

            <div class="ui-card" style="margin-bottom: 0; border-color: #fecaca; background-color: #fef2f2;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #991b1b; margin-bottom: 0.4rem;">
                    Account Lifecycle Control (FR-1.4)
                </h3>
                <p style="font-size: 0.86rem; color: #7f1d1d; margin-bottom: 1rem;">
                    Deactivating an account sets <code>AccountStatus = 'Inactive'</code> while preserving historical booking and maintenance records.
                </p>

                <?php if ($user['AccountStatus'] !== 'Inactive'): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to deactivate account <?= htmlspecialchars($user['UserID']) ?>? Historical bookings will be preserved.');">
                        <input type="hidden" name="action" value="deactivate">
                        <button type="submit" class="btn btn-danger-red">
                            Deactivate Account <?= htmlspecialchars($user['UserID']) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="reactivate">
                        <button type="submit" class="btn btn-primary-blue">
                            Reactivate Account <?= htmlspecialchars($user['UserID']) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
