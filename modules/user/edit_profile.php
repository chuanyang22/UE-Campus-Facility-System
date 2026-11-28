<?php
/**
 * Edit Personal Profile & Password Page
 * AMCS2194 Mini Project — Module 1 (FR-1.3 Update)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/UserService.php';

$pageTitle = 'Edit Profile — UE Campus Facility Booking System';
$activeNav = 'profile';

$userService = new UserService();
AuthService::requireLogin($_SERVER['REQUEST_URI'] ?? '');
$currentUser = AuthService::getCurrentUser();
$userId      = $currentUser['UserID'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $userService->updateProfile($userId, $_POST);
    if ($res['success']) {
        $_SESSION['user'] = $res['user'];
        AuthService::setFlash('success', $res['message']);
        header('Location: profile.php');
        exit;
    } else {
        $errors = $res['errors'];
    }
}

$profile = $userService->getUserById($userId) ?? $currentUser;

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">Edit Profile Details</h1>
        <p class="page-subtitle">Update your personal information, date of birth, gender, or change your password.</p>
    </div>
    <div>
        <a href="profile.php" class="btn btn-cancel">&larr; Back to Profile</a>
    </div>
</div>

<div style="max-width: 680px; margin: 0 auto;">
    <div class="ui-card">
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
                    <label for="FullName">Full Name *</label>
                    <input type="text" id="FullName" name="FullName"
                           class="form-control <?= isset($errors['FullName']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($_POST['FullName'] ?? ($profile['FullName'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="PhoneNo">Phone Number *</label>
                    <input type="text" id="PhoneNo" name="PhoneNo"
                           class="form-control <?= isset($errors['PhoneNo']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. 012-3456789"
                           value="<?= htmlspecialchars($_POST['PhoneNo'] ?? ($profile['PhoneNo'] ?? '')) ?>" required>
                </div>
            </div>

            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="DateOfBirth">Date of Birth</label>
                    <input type="date" id="DateOfBirth" name="DateOfBirth"
                           class="form-control <?= isset($errors['DateOfBirth']) ? 'is-invalid' : '' ?>"
                           max="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['DateOfBirth'] ?? ($profile['DateOfBirth'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label for="Gender">Gender</label>
                    <?php $selectedGender = $_POST['Gender'] ?? ($profile['Gender'] ?? ''); ?>
                    <select id="Gender" name="Gender" class="form-select <?= isset($errors['Gender']) ? 'is-invalid' : '' ?>">
                        <option value="">-- Select Gender --</option>
                        <option value="Male" <?= $selectedGender === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $selectedGender === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $selectedGender === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border-color); margin: 1.25rem 0;">

            <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.75rem;">
                Change Password <span style="font-weight: 400; color: var(--text-muted);">(Leave blank to keep current password)</span>
            </div>

            <div class="form-group">
                <label for="CurrentPassword">Current Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="CurrentPassword" name="CurrentPassword"
                           class="form-control <?= isset($errors['CurrentPassword']) ? 'is-invalid' : '' ?>"
                           style="padding-right: 2.75rem; width: 100%;"
                           placeholder="Required only when changing password">
                    <button type="button" onclick="toggleEditEye('CurrentPassword', this)"
                            aria-label="Show or hide current password"
                            style="position: absolute; right: 0.65rem; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="NewPassword">New Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="NewPassword" name="NewPassword"
                               class="form-control <?= isset($errors['NewPassword']) ? 'is-invalid' : '' ?>"
                               style="padding-right: 2.75rem; width: 100%;"
                               placeholder="Min 6 characters">
                        <button type="button" onclick="toggleEditEye('NewPassword', this)"
                                aria-label="Show or hide new password"
                                style="position: absolute; right: 0.65rem; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ConfirmPassword">Confirm New Password</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="ConfirmPassword" name="ConfirmPassword"
                               class="form-control <?= isset($errors['ConfirmPassword']) ? 'is-invalid' : '' ?>"
                               style="padding-right: 2.75rem; width: 100%;"
                               placeholder="Repeat new password">
                        <button type="button" onclick="toggleEditEye('ConfirmPassword', this)"
                                aria-label="Show or hide confirm password"
                                style="position: absolute; right: 0.65rem; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <a href="profile.php" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-primary-blue" style="font-weight: 700;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleEditEye(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.style.color = isHidden ? 'var(--primary-blue)' : 'var(--text-muted)';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
