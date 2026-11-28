<?php
/**
 * New User Account Registration Page
 * AMCS2194 Mini Project — Module 1 (FR-1.1 Create)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/UserService.php';

$pageTitle = 'Register Account — UE Campus Facility Booking System';
$activeNav = 'profile';

$userService = new UserService();
$errors = [];
$form = [
    'UserID'      => '',
    'FullName'    => '',
    'CampusEmail' => '',
    'PhoneNo'     => '',
    'UserRole'    => 'Student',
];

if (!empty($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = (string)$_GET['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'UserID'          => strtoupper(trim((string)($_POST['UserID'] ?? ''))),
        'FullName'        => trim((string)($_POST['FullName'] ?? '')),
        'CampusEmail'     => strtolower(trim((string)($_POST['CampusEmail'] ?? ''))),
        'PhoneNo'         => trim((string)($_POST['PhoneNo'] ?? '')),
        'UserRole'        => trim((string)($_POST['UserRole'] ?? 'Student')),
        'Password'        => (string)($_POST['Password'] ?? ''),
        'ConfirmPassword' => (string)($_POST['ConfirmPassword'] ?? ''),
    ];

    $res = $userService->registerUser($form, false);
    if ($res['success']) {
        AuthService::setActiveUser($res['user_id']);
        AuthService::setFlash('success', $res['message']);

        $target = $_SESSION['redirect_after_login'] ?? '';
        unset($_SESSION['redirect_after_login']);

        if ($target !== '') {
            $location = (str_starts_with($target, '/') || str_starts_with($target, 'http'))
                ? $target
                : '../../' . ltrim($target, '/');
            header('Location: ' . $location);
        } else {
            header('Location: ../../index.php');
        }
        exit;
    } else {
        $errors = $res['errors'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">Register a New Campus Account</h1>
        <p class="page-subtitle">Follow the simple steps below to create your account and start booking campus facilities.</p>
    </div>
    <div>
        <a href="../../index.php" class="btn btn-cancel">&larr; Back to Home</a>
    </div>
</div>

<div class="ui-card" style="max-width: 780px; margin: 0 auto;">
    <!-- Clear Mode Switcher Tabs -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.5rem; background: var(--body-bg); padding: 0.35rem; border-radius: 10px; border: 1px solid var(--border-color);">
        <a href="login.php" class="btn btn-cancel" style="text-align: center; font-weight: 600; background: transparent;">
            Sign In (Existing User)
        </a>
        <a href="register.php" class="btn btn-primary-blue" style="text-align: center; font-weight: 700;">
            Register New Account
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-banner-red" style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; border-radius: 8px;">
            <strong>Please check the highlighted fields below:</strong>
            <ul style="margin: 0.4rem 0 0 1.2rem; font-size: 0.88rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="form-grid-2col">
            <div class="form-group">
                <label for="UserID">1. Student or Staff ID *</label>
                <input type="text" id="UserID" name="UserID"
                       class="form-control <?= isset($errors['UserID']) ? 'is-invalid' : '' ?>"
                       placeholder="e.g. A240200 or S0105" maxlength="10"
                       value="<?= htmlspecialchars($form['UserID']) ?>" required>
                <small style="color: <?= isset($errors['UserID']) ? 'var(--danger-red)' : 'var(--text-muted)' ?>; margin-top: 0.3rem; font-size: 0.82rem;">
                    <?= isset($errors['UserID']) ? htmlspecialchars($errors['UserID']) : '3–10 letters/numbers with no spaces (e.g. A240200).' ?>
                </small>
            </div>

            <div class="form-group">
                <label for="UserRole">2. Who Are You? *</label>
                <select id="UserRole" name="UserRole" class="form-select <?= isset($errors['UserRole']) ? 'is-invalid' : '' ?>">
                    <option value="Student" <?= $form['UserRole'] === 'Student' ? 'selected' : '' ?>>Student</option>
                    <option value="Staff" <?= $form['UserRole'] === 'Staff' ? 'selected' : '' ?>>Staff / Lecturer</option>
                </select>
                <small style="color: <?= isset($errors['UserRole']) ? 'var(--danger-red)' : 'var(--text-muted)' ?>; margin-top: 0.3rem; font-size: 0.82rem;">
                    <?= isset($errors['UserRole']) ? htmlspecialchars($errors['UserRole']) : 'Select Student if you are studying, or Staff if you teach/work here.' ?>
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="FullName">3. Your Full Name *</label>
            <input type="text" id="FullName" name="FullName"
                   class="form-control <?= isset($errors['FullName']) ? 'is-invalid' : '' ?>"
                   placeholder="e.g. Tee Chuan Yang" maxlength="100"
                   value="<?= htmlspecialchars($form['FullName']) ?>" required>
            <small style="color: <?= isset($errors['FullName']) ? 'var(--danger-red)' : 'var(--text-muted)' ?>; margin-top: 0.3rem; font-size: 0.82rem;">
                <?= isset($errors['FullName']) ? htmlspecialchars($errors['FullName']) : 'Enter your full name as printed on your student or staff ID card.' ?>
            </small>
        </div>

        <div class="form-grid-2col">
            <div class="form-group">
                <label for="CampusEmail">4. Campus Email Address *</label>
                <input type="email" id="CampusEmail" name="CampusEmail"
                       class="form-control <?= isset($errors['CampusEmail']) ? 'is-invalid' : '' ?>"
                       placeholder="e.g. name@student.campus.edu" maxlength="100"
                       value="<?= htmlspecialchars($form['CampusEmail']) ?>" required>
                <small style="color: <?= isset($errors['CampusEmail']) ? 'var(--danger-red)' : 'var(--text-muted)' ?>; margin-top: 0.3rem; font-size: 0.82rem;">
                    <?= isset($errors['CampusEmail']) ? htmlspecialchars($errors['CampusEmail']) : 'Use a valid email format like name@student.campus.edu.' ?>
                </small>
            </div>

            <div class="form-group">
                <label for="PhoneNo">5. Mobile Phone Number *</label>
                <input type="text" id="PhoneNo" name="PhoneNo"
                       class="form-control <?= isset($errors['PhoneNo']) ? 'is-invalid' : '' ?>"
                       placeholder="e.g. 012-3456789" maxlength="15"
                       value="<?= htmlspecialchars($form['PhoneNo']) ?>" required>
                <small style="color: <?= isset($errors['PhoneNo']) ? 'var(--danger-red)' : 'var(--text-muted)' ?>; margin-top: 0.3rem; font-size: 0.82rem;">
                    <?= isset($errors['PhoneNo']) ? htmlspecialchars($errors['PhoneNo']) : '8–15 digits (e.g. 012-3456789) for booking & maintenance alerts.' ?>
                </small>
            </div>
        </div>

        <div class="form-grid-2col">
            <div class="form-group">
                <label for="Password">6. Create a Password *</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="Password" name="Password"
                           class="form-control <?= isset($errors['Password']) ? 'is-invalid' : '' ?>"
                           style="padding-right: 2.75rem; width: 100%;"
                           placeholder="At least 6 characters" required>
                    <button type="button" onclick="toggleRegEye('Password', this)"
                            aria-label="Show or hide password"
                            style="position: absolute; right: 0.65rem; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <small style="color: <?= isset($errors['Password']) ? 'var(--danger-red)' : 'var(--text-muted)' ?>; margin-top: 0.3rem; font-size: 0.82rem;">
                    <?= isset($errors['Password']) ? htmlspecialchars($errors['Password']) : 'Must be at least 6 characters long.' ?>
                </small>
            </div>

            <div class="form-group">
                <label for="ConfirmPassword">7. Re-type Password to Confirm *</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="ConfirmPassword" name="ConfirmPassword"
                           class="form-control <?= isset($errors['ConfirmPassword']) ? 'is-invalid' : '' ?>"
                           style="padding-right: 2.75rem; width: 100%;"
                           placeholder="Type the same password again" required>
                    <button type="button" onclick="toggleRegEye('ConfirmPassword', this)"
                            aria-label="Show or hide confirm password"
                            style="position: absolute; right: 0.65rem; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <small style="color: <?= isset($errors['ConfirmPassword']) ? 'var(--danger-red)' : 'var(--text-muted)' ?>; margin-top: 0.3rem; font-size: 0.82rem;">
                    <?= isset($errors['ConfirmPassword']) ? htmlspecialchars($errors['ConfirmPassword']) : 'Must match the password you entered on the left.' ?>
                </small>
            </div>
        </div>

        <script>
        function toggleRegEye(inputId, btn) {
            var input = document.getElementById(inputId);
            if (!input) return;
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.style.color = isHidden ? 'var(--primary-blue)' : 'var(--text-muted)';
        }
        </script>

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap;">
            <span style="font-size: 0.86rem; color: var(--text-muted);">
                Already have an account? <a href="login.php" style="color: var(--primary-blue); font-weight: 700;">Sign in here &rarr;</a>
            </span>
            <button type="submit" class="btn btn-primary-blue" style="padding: 0.65rem 1.5rem; font-weight: 700;">
                Create Account &amp; Continue
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
