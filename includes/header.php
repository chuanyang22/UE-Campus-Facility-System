<?php
/**
 * Master Page: Header & Navigation Bar
 * AMCS2194 Mini Project — Exact Match to UI Mockups
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/i18n.php';

$currentLang = I18n::getLang();
$currentUser = AuthService::getCurrentUser();
$demoUsers   = AuthService::getDemoUsers();
$flash       = AuthService::getFlash();

// Determine Base URL dynamically
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$pos = strpos($scriptName, '/UE-Campus-Facility-System');
if ($pos !== false) {
    $baseUrl = '/UE-Campus-Facility-System/';
} else {
    $baseUrl = '/';
}

if (!isset($pageTitle)) {
    $pageTitle = 'UE Campus Facility Booking System';
}
if (!isset($activeNav)) {
    $activeNav = '';
}

$isAdmin      = ($currentUser['UserRole'] ?? '') === 'Admin';
$isTechnician = ($currentUser['UserRole'] ?? '') === 'Technician';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
    <script>
        (function() {
            var savedTheme = localStorage.getItem('ue_theme') || 'light';
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>

<header class="navbar-top">
    <div class="nav-container">
        <a href="<?= $isTechnician ? $baseUrl . 'modules/maintenance/index.php' : $baseUrl . 'index.php' ?>" class="brand-title">
            <?= htmlspecialchars(t('brand_title')) ?>
        </a>

        <nav>
            <ul class="nav-links">
                <?php if ($isTechnician): ?>
                    <li><a href="<?= $baseUrl ?>modules/maintenance/index.php" class="nav-link <?= $activeNav === 'maintenance' ? 'active' : '' ?>"><?= htmlspecialchars(t('nav_maintenance')) ?></a></li>
                <?php elseif ($isAdmin): ?>
                    <li><a href="<?= $baseUrl ?>index.php#equipment" class="nav-link <?= $activeNav === 'equipment' ? 'active' : '' ?>"><?= htmlspecialchars(t('nav_equipment')) ?></a></li>
                    <li><a href="<?= $baseUrl ?>modules/maintenance/index.php" class="nav-link <?= $activeNav === 'maintenance' ? 'active' : '' ?>"><?= htmlspecialchars(t('nav_maintenance')) ?></a></li>
                    <li><a href="<?= $baseUrl ?>modules/feedback/index.php" class="nav-link <?= $activeNav === 'feedback' ? 'active' : '' ?>"><?= htmlspecialchars(t('nav_feedback')) ?></a></li>
                <?php else: ?>
                    <li><a href="<?= $baseUrl ?>index.php" class="nav-link <?= $activeNav === 'facilities' ? 'active' : '' ?>"><?= htmlspecialchars(t('nav_facilities')) ?></a></li>
                    <li><a href="<?= $baseUrl ?>my_bookings.php" class="nav-link <?= $activeNav === 'bookings' ? 'active' : '' ?>"><?= htmlspecialchars(t('nav_my_bookings')) ?></a></li>
                    <li><a href="<?= $baseUrl ?>modules/feedback/index.php" class="nav-link <?= $activeNav === 'feedback' ? 'active' : '' ?>"><?= htmlspecialchars(t('nav_feedback')) ?></a></li>
                <?php endif; ?>
                
                <!-- User / Technician / Admin Persona, Language & Theme Controls -->
                <li style="display: flex; align-items: center; gap: 0.45rem;">
                    <span class="user-badge-text">
                        <?php
                            $rolePrefix = $isAdmin ? 'Admin' : ($isTechnician ? 'Tech' : 'User');
                            echo '[' . $rolePrefix . ': ' . htmlspecialchars($currentUser['UserID']) . ']';
                        ?>
                    </span>
                    <form method="GET" style="margin: 0; display: inline;">
                        <select name="switch_user" onchange="this.form.submit()" class="nav-select-compact" title="Toggle persona for testing">
                            <option value="A240112" <?= $currentUser['UserID'] === 'A240112' ? 'selected' : '' ?>>User: A240112</option>
                            <option value="T0001" <?= $currentUser['UserID'] === 'T0001' ? 'selected' : '' ?>>Tech: T0001</option>
                            <option value="S0012" <?= $currentUser['UserID'] === 'S0012' ? 'selected' : '' ?>>Admin: S0012</option>
                        </select>
                    </form>

                    <!-- Language Switcher (Auto-detected from device, overridable) -->
                    <form method="GET" style="margin: 0; display: inline;">
                        <?php foreach ($_GET as $gk => $gv): ?>
                            <?php if ($gk !== 'lang' && !is_array($gv)): ?>
                                <input type="hidden" name="<?= htmlspecialchars((string)$gk) ?>" value="<?= htmlspecialchars((string)$gv) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <select name="lang" onchange="this.form.submit()" class="nav-select-compact" title="<?= htmlspecialchars(t('lang_label')) ?>">
                            <option value="en" <?= $currentLang === 'en' ? 'selected' : '' ?>>🌐 EN</option>
                            <option value="zh" <?= $currentLang === 'zh' ? 'selected' : '' ?>>🌐 中文</option>
                            <option value="ms" <?= $currentLang === 'ms' ? 'selected' : '' ?>>🌐 BM</option>
                        </select>
                    </form>

                    <!-- Light / Dark Theme Toggle -->
                    <button type="button" id="themeToggleBtn" onclick="toggleUeTheme()" class="theme-toggle-btn" title="<?= htmlspecialchars(t('theme_toggle')) ?>">
                        🌙
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</header>

<main class="main-wrapper">

<?php if ($flash): ?>
    <div style="background-color: <?= $flash['type'] === 'success' ? '#ecfdf5' : ($flash['type'] === 'danger' ? '#fef2f2' : '#eff6ff') ?>; border: 1px solid <?= $flash['type'] === 'success' ? '#a7f3d0' : ($flash['type'] === 'danger' ? '#fecdd3' : '#bfdbfe') ?>; color: <?= $flash['type'] === 'success' ? '#065f46' : ($flash['type'] === 'danger' ? '#991b1b' : '#1e40af') ?>; border-radius: 8px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.92rem;">
        <div><?= htmlspecialchars($flash['message']) ?></div>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:inherit; font-weight:bold;">&times;</button>
    </div>
<?php endif; ?>
