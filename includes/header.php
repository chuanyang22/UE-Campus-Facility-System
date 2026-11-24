<?php
/**
 * Master Page: Header & Navigation Bar
 * AMCS2194 Mini Project — Exact Match to UI Mockups
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

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

$isAdmin = ($currentUser['UserRole'] ?? '') === 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
</head>
<body>

<header class="navbar-top">
    <div class="nav-container">
        <a href="<?= $baseUrl ?>index.php" class="brand-title">
            UE Campus Facility Booking System
        </a>

        <nav>
            <ul class="nav-links">
                <li><a href="<?= $baseUrl ?>index.php" class="nav-link <?= $activeNav === 'facilities' ? 'active' : '' ?>">Facilities</a></li>
                <li><a href="<?= $baseUrl ?>index.php" class="nav-link <?= $activeNav === 'bookings' ? 'active' : '' ?>">My Bookings</a></li>
                <li><a href="<?= $baseUrl ?>index.php" class="nav-link <?= $activeNav === 'equipment' ? 'active' : '' ?>">Equipment</a></li>
                <li><a href="<?= $baseUrl ?>modules/maintenance/index.php" class="nav-link <?= $activeNav === 'maintenance' ? 'active' : '' ?>">Maintenance</a></li>
                <li><a href="<?= $baseUrl ?>modules/feedback/index.php" class="nav-link <?= $activeNav === 'feedback' ? 'active' : '' ?>">Feedback</a></li>
                
                <!-- User / Admin Persona Display -->
                <li style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="user-badge-text">
                        [<?= $isAdmin ? 'Admin: ' . htmlspecialchars($currentUser['UserID']) : 'User: ' . htmlspecialchars($currentUser['UserID']) ?>]
                    </span>
                    <form method="GET" style="margin: 0; display: inline;">
                        <select name="switch_user" onchange="this.form.submit()" style="background: #1e293b; color: #94a3b8; border: 1px solid #334155; border-radius: 4px; padding: 2px 6px; font-size: 0.75rem; cursor: pointer;" title="Toggle persona for testing">
                            <option value="A240112" <?= $currentUser['UserID'] === 'A240112' ? 'selected' : '' ?>>User: A240112</option>
                            <option value="S0012" <?= $currentUser['UserID'] === 'S0012' ? 'selected' : '' ?>>Admin: S0012</option>
                        </select>
                    </form>
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
