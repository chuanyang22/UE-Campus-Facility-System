<?php
/**
 * Master Page: Header & Navigation Bar
 * AMCS2194 Mini Project — UE Campus Facility Booking System
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — AMCS2194</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>assets/css/style.css">
</head>
<body>

<header class="navbar-top">
    <div class="nav-container">
        <a href="<?= $baseUrl ?>index.php" class="brand-wrapper">
            <span class="brand-logo-badge">TAR UMT</span>
            <div>
                <div class="brand-title">UE Campus Facility Booking System</div>
                <div class="brand-subtitle">AMCS2194 Mini Project &bull; Modules 5 & 6 Prototype</div>
            </div>
        </a>

        <nav>
            <ul class="nav-links">
                <li><a href="<?= $baseUrl ?>index.php" class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= $baseUrl ?>modules/maintenance/index.php" class="nav-link <?= $activeNav === 'maintenance' ? 'active' : '' ?>">Maintenance (Mod 5)</a></li>
                <li><a href="<?= $baseUrl ?>modules/maintenance/report.php" class="nav-link <?= $activeNav === 'report_defect' ? 'active' : '' ?>">+ Report Defect</a></li>
                <li><a href="<?= $baseUrl ?>modules/feedback/index.php" class="nav-link <?= $activeNav === 'feedback' ? 'active' : '' ?>">Feedback (Mod 6)</a></li>
                <li><a href="<?= $baseUrl ?>modules/feedback/submit.php" class="nav-link <?= $activeNav === 'submit_feedback' ? 'active' : '' ?>">+ Rate Session</a></li>
            </ul>
        </nav>

        <div class="user-badge-wrapper">
            <div>
                <strong><?= htmlspecialchars($currentUser['FullName']) ?></strong>
                <span class="user-badge-role role-<?= strtolower($currentUser['UserRole']) ?>"><?= htmlspecialchars($currentUser['UserRole']) ?></span>
            </div>
            
            <form method="GET" class="user-switcher" style="margin: 0;">
                <label for="switch_user_select" style="display:none;">Switch Demo Role</label>
                <select id="switch_user_select" name="switch_user" onchange="this.form.submit()" title="Quick Switch Demo Persona (Week 7 Demo)">
                    <option value="" disabled selected>Switch User...</option>
                    <?php foreach ($demoUsers as $u): ?>
                        <option value="<?= htmlspecialchars($u['UserID']) ?>" <?= $u['UserID'] === $currentUser['UserID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['FullName']) ?> (<?= htmlspecialchars($u['UserRole']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
</header>

<main class="main-wrapper">

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
        <div><?= htmlspecialchars($flash['message']) ?></div>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;cursor:pointer;font-weight:bold;font-size:1.1rem;color:inherit;">&times;</button>
    </div>
<?php endif; ?>
