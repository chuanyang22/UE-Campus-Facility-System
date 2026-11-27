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
            if (localStorage.getItem('ue_sidebar_collapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed-init');
            }
        })();
    </script>
</head>
<body>

<!-- Top Slim Bar -->
<header class="navbar-top">
    <div class="nav-container" style="max-width: 100%; padding: 0;">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <button type="button" id="sidebarToggleBtn" onclick="toggleUeSidebar()" class="theme-toggle-btn" title="<?= htmlspecialchars(t('sidebar_toggle')) ?>" style="font-size: 1.05rem; padding: 4px 10px;">
                &#9776;
            </button>
            <a href="<?= $isTechnician ? $baseUrl . 'modules/maintenance/index.php' : $baseUrl . 'index.php' ?>" class="brand-title">
                <?= htmlspecialchars(t('brand_title')) ?>
            </a>
        </div>

        <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
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

            <!-- Language Switcher -->
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
        </div>
    </div>
</header>

<!-- App Shell: Collapsible Left Sidebar + Main Content -->
<div class="app-shell">
    <aside id="appSidebar" class="app-sidebar">
        <nav class="sidebar-nav">
            <?php if ($isTechnician): ?>
                <a href="<?= $baseUrl ?>modules/maintenance/index.php" class="sidebar-link <?= $activeNav === 'maintenance' ? 'active' : '' ?>">
                    <span>&#128295;</span> <?= htmlspecialchars(t('nav_maintenance')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/equipment/index.php" class="sidebar-link <?= $activeNav === 'equipment' ? 'active' : '' ?>">
                    <span>&#127890;</span> <?= htmlspecialchars(t('nav_equipment')) ?>
                </a>
            <?php elseif ($isAdmin): ?>
                <a href="<?= $baseUrl ?>index.php" class="sidebar-link <?= $activeNav === 'facilities' ? 'active' : '' ?>">
                    <span>&#127970;</span> <?= htmlspecialchars(t('nav_facilities')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/booking/index.php?view_all=1" class="sidebar-link <?= $activeNav === 'bookings' ? 'active' : '' ?>">
                    <span>&#128197;</span> <?= htmlspecialchars(t('nav_my_bookings')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/equipment/index.php" class="sidebar-link <?= $activeNav === 'equipment' ? 'active' : '' ?>">
                    <span>&#127890;</span> <?= htmlspecialchars(t('nav_equipment')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/maintenance/index.php" class="sidebar-link <?= $activeNav === 'maintenance' ? 'active' : '' ?>">
                    <span>&#128295;</span> <?= htmlspecialchars(t('nav_maintenance')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/feedback/index.php" class="sidebar-link <?= $activeNav === 'feedback' ? 'active' : '' ?>">
                    <span>&#11088;</span> <?= htmlspecialchars(t('nav_feedback')) ?>
                </a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>index.php" class="sidebar-link <?= $activeNav === 'facilities' ? 'active' : '' ?>">
                    <span>&#127970;</span> <?= htmlspecialchars(t('nav_facilities')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/booking/index.php" class="sidebar-link <?= $activeNav === 'bookings' ? 'active' : '' ?>">
                    <span>&#128197;</span> <?= htmlspecialchars(t('nav_my_bookings')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/equipment/index.php" class="sidebar-link <?= $activeNav === 'equipment' ? 'active' : '' ?>">
                    <span>&#127890;</span> <?= htmlspecialchars(t('nav_equipment')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/feedback/index.php" class="sidebar-link <?= $activeNav === 'feedback' ? 'active' : '' ?>">
                    <span>&#11088;</span> <?= htmlspecialchars(t('nav_feedback')) ?>
                </a>
                <a href="<?= $baseUrl ?>modules/maintenance/report.php" class="sidebar-link <?= $activeNav === 'report_defect' ? 'active' : '' ?>">
                    <span>&#128295;</span> <?= htmlspecialchars(t('nav_report_defect')) ?>
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <div class="app-content">
        <main class="main-wrapper">

<?php if ($flash): ?>
    <div style="background-color: <?= $flash['type'] === 'success' ? '#ecfdf5' : ($flash['type'] === 'danger' ? '#fef2f2' : '#eff6ff') ?>; border: 1px solid <?= $flash['type'] === 'success' ? '#a7f3d0' : ($flash['type'] === 'danger' ? '#fecdd3' : '#bfdbfe') ?>; color: <?= $flash['type'] === 'success' ? '#065f46' : ($flash['type'] === 'danger' ? '#991b1b' : '#1e40af') ?>; border-radius: 8px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.92rem;">
        <div><?= htmlspecialchars($flash['message']) ?></div>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:inherit; font-weight:bold;">&times;</button>
    </div>
<?php endif; ?>
