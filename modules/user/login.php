<?php
/**
 * User Authentication / Login Page
 * AMCS2194 Mini Project — Module 1 (FR-1.2)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/UserService.php';

$pageTitle = 'Sign In — UE Campus Facility Booking System';
$activeNav = 'profile';

$userService = new UserService();
$errorMsg    = '';
$identifier  = AuthService::getRememberedIdentifier();
$rememberMe  = ($identifier !== '');

if (!empty($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = (string)$_GET['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string)($_POST['identifier'] ?? ''));
    $password   = (string)($_POST['password'] ?? '');
    $rememberMe = isset($_POST['remember_me']);

    $res = $userService->authenticate($identifier, $password);
    if ($res['success']) {
        $_SESSION['user'] = $res['user'];
        if ($rememberMe) {
            AuthService::setRememberCookie($res['user']['UserID'], $identifier);
        } else {
            AuthService::clearRememberCookie();
        }
        AuthService::setFlash('success', $res['message']);

        $target = $_SESSION['redirect_after_login'] ?? '';
        unset($_SESSION['redirect_after_login']);

        if ($res['user']['UserRole'] === 'Technician') {
            header('Location: ../maintenance/index.php');
        } elseif ($target !== '') {
            $location = (str_starts_with($target, '/') || str_starts_with($target, 'http'))
                ? $target
                : '../../' . ltrim($target, '/');
            header('Location: ' . $location);
        } else {
            header('Location: ../../index.php');
        }
        exit;
    } else {
        $errorMsg = $res['message'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">Sign In</h1>
        <p class="page-subtitle">Sign in to book campus facilities or manage your reservations.</p>
    </div>
    <div>
        <a href="../../index.php" class="btn btn-cancel">&larr; Back to Home</a>
    </div>
</div>

<div style="max-width: 480px; margin: 0 auto;">
    <div class="ui-card">
        <!-- Mode Switcher Tabs -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.5rem; background: var(--body-bg); padding: 0.35rem; border-radius: 10px; border: 1px solid var(--border-color);">
            <a href="login.php" class="btn btn-primary-blue" style="text-align: center; font-weight: 700;">
                Sign In
            </a>
            <a href="register.php" class="btn btn-cancel" style="text-align: center; font-weight: 600; background: transparent;">
                Register
            </a>
        </div>

        <?php if ($errorMsg !== ''): ?>
            <div class="alert-banner-red" style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; border-radius: 8px;">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="identifier">Student ID or Email</label>
                <input type="text" id="identifier" name="identifier" class="form-control"
                       placeholder="e.g. A240112 or abc@campus.edu"
                       value="<?= htmlspecialchars($identifier) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="password" name="password" class="form-control"
                           style="padding-right: 2.75rem; width: 100%;"
                           placeholder="Enter password" required>
                    <button type="button" onclick="togglePasswordEye('password', this)"
                            aria-label="Show or hide password"
                            style="position: absolute; right: 0.65rem; background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <label style="display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.88rem; color: var(--text-body); cursor: pointer; font-weight: 500; margin: 0;">
                    <input type="checkbox" name="remember_me" value="1" <?= $rememberMe ? 'checked' : '' ?> style="width: 16px; height: 16px; cursor: pointer;">
                    Remember me
                </label>
                <a href="register.php" style="font-size: 0.86rem; color: var(--primary-blue); font-weight: 600;">Create account &rarr;</a>
            </div>

            <button type="submit" class="btn btn-primary-blue" style="width: 100%; padding: 0.7rem; font-weight: 700;">
                Sign In
            </button>
        </form>
    </div>
</div>

<script>
function togglePasswordEye(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.style.color = isHidden ? 'var(--primary-blue)' : 'var(--text-muted)';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
