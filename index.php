<?php
/**
 * Student / User Facility Booking Home Page
 * AMCS2194 Mini Project — UE Campus Facility Booking System
 * Student 2: Tie Sing Wee (Module 5 + Module 6 + Unique Feature: QR Check-In & Auto-Release)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/services/BookingService.php';
require_once __DIR__ . '/services/MaintenanceService.php';
require_once __DIR__ . '/services/FeedbackService.php';

$pageTitle = 'Home — UE Campus Facility Booking System';
$activeNav = 'facilities';

$bookingService  = new BookingService();
$maintService    = new MaintenanceService();
$feedbackService = new FeedbackService();

$currentUser = AuthService::getCurrentUser();
$isAdmin = AuthService::isAdmin();

// Technicians only view Maintenance
if (AuthService::isTechnician()) {
    header('Location: modules/maintenance/index.php');
    exit;
}

// Handle QR Check-in or Auto-Release actions
$checkInResult = null;
$autoReleaseResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'qr_check_in') {
        $bId = trim($_POST['booking_id'] ?? '');
        $checkInResult = $bookingService->processQrCheckIn($bId);
        if ($checkInResult['success']) {
            AuthService::setFlash('success', $checkInResult['message']);
        } else {
            AuthService::setFlash('danger', $checkInResult['message']);
        }
        header('Location: index.php#my-reservations');
        exit;
    }

    if ($action === 'run_auto_release') {
        $autoReleaseResult = $bookingService->runNoShowAutoRelease();
        AuthService::setFlash('info', $autoReleaseResult['message']);
        header('Location: index.php#no-show-engine');
        exit;
    }

    if ($action === 'seed_overdue_demo') {
        $demoId = $bookingService->seedOverdueDemoBooking();
        AuthService::setFlash('info', "Seeded unclaimed overdue booking #{$demoId} (scheduled 25 mins ago). Click 'Run Auto-Release Engine Now' to test no-show cancellation.");
        header('Location: index.php#no-show-engine');
        exit;
    }
}

// Filters for Facility Directory
$categoryFilter = $_GET['category'] ?? 'ALL';
$searchQuery    = trim($_GET['search'] ?? '');

$userBookings = $bookingService->getUserBookings($currentUser['UserID']);
$facilities   = $bookingService->getAllFacilitiesWithStats($categoryFilter, $searchQuery);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Admin Quick Redirect Notice if logged in as Admin -->
<?php if ($isAdmin): ?>
    <div style="background-color: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <strong style="color: #0f172a;">Administrator Session (<?= htmlspecialchars($currentUser['UserID']) ?>):</strong>
            <span style="color: #475569; font-size: 0.9rem; margin-left: 0.25rem;">You are previewing the student booking portal. Access administrative management tools below:</span>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="modules/maintenance/index.php" class="btn btn-primary-blue" style="font-size: 0.82rem; padding: 0.4rem 0.85rem;">Maintenance Ticket Log &rarr;</a>
            <a href="modules/feedback/edit.php" class="btn btn-cancel" style="font-size: 0.82rem; padding: 0.4rem 0.85rem;">Feedback Moderation &rarr;</a>
        </div>
    </div>
<?php endif; ?>

<!-- Hero Section: Campus Facility Booking Portal -->
<div class="ui-card hero-banner-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div style="max-width: 720px;">
            <div style="display: inline-block; background: #ffffff; color: #2563eb; border: 1px solid #bfdbfe; font-weight: 700; font-size: 0.8rem; padding: 0.25rem 0.75rem; border-radius: 9999px; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                <?= htmlspecialchars(t('hero_badge')) ?>
            </div>
            <h1 style="font-size: 2.2rem; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; margin-bottom: 0.6rem;">
                <?= htmlspecialchars(t('hero_title')) ?>
            </h1>
            <p style="color: #334155; font-size: 1rem; line-height: 1.6; margin: 0 0 1.25rem 0;">
                <?= htmlspecialchars(t('hero_subtitle')) ?>
            </p>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="#facility-directory" class="btn btn-primary-blue" style="font-weight: 700; padding: 0.7rem 1.4rem;">
                    <?= htmlspecialchars(t('btn_browse_facilities')) ?>
                </a>
                <a href="#my-reservations" class="btn btn-outline-blue" style="font-weight: 600; padding: 0.7rem 1.2rem; font-size: 0.9rem;">
                    <?= htmlspecialchars(t('btn_my_reservations')) ?>
                </a>
            </div>
        </div>

        <!-- Quick Portal Navigation Tile -->
        <div class="hero-quick-actions">
            <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 0.6rem;"><?= htmlspecialchars(t('quick_actions')) ?></div>
            <div style="display: flex; flex-direction: column; gap: 0.55rem;">
                <a href="modules/maintenance/report.php" class="btn btn-primary-blue" style="font-size: 0.88rem; text-align: left; padding: 0.55rem 0.9rem;">
                    <?= htmlspecialchars(t('btn_report_defect')) ?>
                </a>
                <a href="modules/feedback/completed.php" class="btn btn-outline-blue" style="font-size: 0.88rem; text-align: left; padding: 0.55rem 0.9rem;">
                    <?= htmlspecialchars(t('btn_rate_completed')) ?>
                </a>
                <a href="modules/feedback/index.php" class="btn btn-cancel" style="font-size: 0.85rem; text-align: left; padding: 0.5rem 0.9rem;">
                    <?= htmlspecialchars(t('btn_view_reviews')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- SECTION 1: MY ACTIVE RESERVATIONS & DYNAMIC QR CHECK-IN  -->
<!-- ======================================================== -->
<div id="my-reservations" style="margin-bottom: 2.5rem;">
    <div class="page-header-row" style="margin-bottom: 1rem;">
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-heading); margin-bottom: 0.2rem;">
                <?= htmlspecialchars(t('sec_reservations')) ?>
            </h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
                <?= htmlspecialchars(t('sec_reservations_sub')) ?>
            </p>
        </div>
        <div>
            <span class="user-pill-chip">
                <?= htmlspecialchars($currentUser['FullName'] ?? 'Student') ?> (<?= htmlspecialchars($currentUser['UserID']) ?>)
            </span>
        </div>
    </div>

    <?php if (empty($userBookings)): ?>
        <div class="ui-card" style="text-align: center; padding: 2.5rem;">
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1rem;"><?= htmlspecialchars(t('no_bookings_msg')) ?></p>
            <a href="#facility-directory" class="btn btn-primary-blue"><?= htmlspecialchars(t('btn_book_now')) ?></a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.25rem;">
            <?php foreach ($userBookings as $b): 
                $status = $b['BookingStatus'];
                $statusBadgeClass = match($status) {
                    'Confirmed'   => 'pill-high',
                    'Checked-In'  => 'pill-low',
                    'Completed'   => 'pill-completed',
                    'No-Show'     => 'pill-critical',
                    default       => 'pill-medium'
                };
                $statusLabel = match($status) {
                    'Confirmed'   => t('status_confirmed'),
                    'Checked-In'  => t('status_checked_in'),
                    'Completed'   => t('status_completed'),
                    'No-Show'     => t('status_no_show'),
                    default       => $status
                };
                $isToday = ($b['BookingDate'] === date('Y-m-d'));
            ?>
                <div class="ui-card" style="margin: 0; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid <?= $status === 'Checked-In' ? '#10b981' : ($status === 'Confirmed' ? '#2563eb' : '#94a3b8') ?>;">
                    <div>
                        <!-- Header with Status -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                            <div>
                                <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    <?= htmlspecialchars(t('lbl_booking')) ?> #<?= htmlspecialchars($b['BookingID']) ?>
                                </span>
                                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-heading); margin: 0.15rem 0 0 0;">
                                    <?= htmlspecialchars($b['FacilityName']) ?>
                                </h3>
                            </div>
                            <span class="pill-badge <?= $statusBadgeClass ?>" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>
                        </div>

                        <!-- Venue Details -->
                        <div style="font-size: 0.88rem; color: var(--text-body); margin-bottom: 0.85rem; line-height: 1.5;">
                            <div>&#128205; <strong><?= htmlspecialchars(t('lbl_location')) ?></strong> <?= htmlspecialchars($b['LocationBlock']) ?></div>
                            <div>&#128197; <strong><?= htmlspecialchars(t('lbl_date')) ?></strong> <?= htmlspecialchars(date('D, d M Y', strtotime($b['BookingDate']))) ?> <?= $isToday ? '<strong style="color: #3b82f6;">' . htmlspecialchars(t('lbl_today')) . '</strong>' : '' ?></div>
                            <div>&#128337; <strong><?= htmlspecialchars(t('lbl_time')) ?></strong> <?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> &ndash; <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?></div>
                            <div style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.25rem;"><?= htmlspecialchars(t('lbl_purpose')) ?> <?= htmlspecialchars($b['Purpose']) ?></div>
                        </div>

                        <!-- Check-In Status Information -->
                        <?php if ($status === 'Checked-In' && !empty($b['CheckInTime'])): ?>
                            <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.85rem; font-size: 0.82rem; color: #065f46;">
                                <strong>&#10003; <?= htmlspecialchars(t('msg_checked_in_at')) ?></strong> <?= htmlspecialchars(date('d M Y, H:i', strtotime($b['CheckInTime']))) ?>
                            </div>
                        <?php elseif ($status === 'No-Show'): ?>
                            <div style="background-color: #fef2f2; border: 1px solid #fecdd3; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.85rem; font-size: 0.82rem; color: #991b1b;">
                                <strong>&#9888;</strong> <?= htmlspecialchars(t('msg_expired_noshow')) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Bar -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; border-top: 1px solid var(--border-color); padding-top: 0.85rem; margin-top: 0.5rem;">
                        <?php if ($status === 'Confirmed'): ?>
                            <button type="button" 
                                    class="btn btn-primary-blue" 
                                    onclick="openQrModal('<?= htmlspecialchars($b['BookingID']) ?>', '<?= htmlspecialchars($b['FacilityName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['BookingDate']) ?>', '<?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> - <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?>')" 
                                    style="flex: 1; padding: 0.55rem; font-size: 0.85rem; font-weight: 700; display: inline-flex; justify-content: center; align-items: center; gap: 0.4rem;">
                                &#128241; <?= htmlspecialchars(t('btn_qr_checkin')) ?>
                            </button>
                        <?php elseif ($status === 'Checked-In'): ?>
                            <button type="button" 
                                    class="btn btn-cancel" 
                                    onclick="openQrModal('<?= htmlspecialchars($b['BookingID']) ?>', '<?= htmlspecialchars($b['FacilityName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['BookingDate']) ?>', '<?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> - <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?>', true)" 
                                    style="flex: 1; padding: 0.55rem; font-size: 0.85rem;">
                                <?= htmlspecialchars(t('btn_view_qr')) ?>
                            </button>
                        <?php elseif ($status === 'Completed'): ?>
                            <a href="modules/feedback/submit.php?booking_id=<?= urlencode($b['BookingID']) ?>" class="btn btn-outline-blue" style="flex: 1; padding: 0.55rem; font-size: 0.85rem; text-align: center;">
                                &#9997; <?= htmlspecialchars(t('btn_rate_review')) ?>
                            </a>
                        <?php endif; ?>

                        <a href="modules/maintenance/report.php?facility_id=<?= urlencode($b['FacilityID']) ?>" class="btn btn-cancel" style="padding: 0.55rem 0.75rem; font-size: 0.82rem;">
                            <?= htmlspecialchars(t('btn_report_short')) ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ======================================================== -->
<!-- UNIQUE FEATURE: 15-MINUTE NO-SHOW AUTO-RELEASE DEMO      -->
<!-- ======================================================== -->
<div id="no-show-engine" class="ui-card auto-release-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div style="max-width: 750px;">
            <div class="auto-release-badge">
                <?= htmlspecialchars(t('uf_badge')) ?>
            </div>
            <h3 class="auto-release-title">
                <?= htmlspecialchars(t('uf_title')) ?>
            </h3>
            <p class="auto-release-desc">
                <?= htmlspecialchars(t('uf_desc')) ?>
            </p>
        </div>

        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
            <form method="POST" action="index.php" style="margin: 0;">
                <input type="hidden" name="action" value="seed_overdue_demo">
                <button type="submit" class="btn btn-cancel" style="font-size: 0.82rem; padding: 0.55rem 0.9rem;">
                    <?= htmlspecialchars(t('btn_seed_overdue')) ?>
                </button>
            </form>

            <form method="POST" action="index.php" style="margin: 0;">
                <input type="hidden" name="action" value="run_auto_release">
                <button type="submit" class="btn btn-primary-blue" style="background-color: #7e22ce; border-color: #6b21a8; font-size: 0.82rem; padding: 0.55rem 1.1rem; font-weight: 700;">
                    <?= htmlspecialchars(t('btn_run_autorelease')) ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- SECTION 2: CAMPUS FACILITY BOOKING DIRECTORY             -->
<!-- ======================================================== -->
<div id="facility-directory" style="margin-bottom: 2.5rem;">
    <div class="page-header-row" style="margin-bottom: 1.25rem;">
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-heading); margin-bottom: 0.2rem;">
                <?= htmlspecialchars(t('sec_facilities')) ?>
            </h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
                <?= htmlspecialchars(t('sec_facilities_sub')) ?>
            </p>
        </div>

        <!-- Filter & Search Controls -->
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="index.php?category=ALL#facility-directory" class="btn <?= $categoryFilter === 'ALL' ? 'btn-primary-blue' : 'btn-cancel' ?>" style="font-size: 0.82rem; padding: 0.45rem 0.85rem;">
                <?= htmlspecialchars(t('filter_all')) ?>
            </a>
            <a href="index.php?category=Sports#facility-directory" class="btn <?= $categoryFilter === 'Sports' ? 'btn-primary-blue' : 'btn-cancel' ?>" style="font-size: 0.82rem; padding: 0.45rem 0.85rem;">
                &#127992; <?= htmlspecialchars(t('filter_sports')) ?>
            </a>
            <a href="index.php?category=Academic#facility-directory" class="btn <?= $categoryFilter === 'Academic' ? 'btn-primary-blue' : 'btn-cancel' ?>" style="font-size: 0.82rem; padding: 0.45rem 0.85rem;">
                &#128218; <?= htmlspecialchars(t('filter_academic')) ?>
            </a>
        </div>
    </div>

    <!-- Facility Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
        <?php foreach ($facilities as $f): 
            $isAvailable = ($f['AvailabilityStatus'] === 'Available');
            $avgScore = $f['AvgScore'] ? rtrim(rtrim(number_format((float)$f['AvgScore'], 2, '.', ''), '0'), '.') : 'New';
            $reviewsCount = (int)$f['ReviewCount'];
        ?>
            <div class="ui-card" style="margin: 0; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; border-radius: 10px;">
                <div>
                    <!-- Top Category & Status Badge -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span style="font-size: 0.78rem; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 0.2rem 0.6rem; border-radius: 4px;">
                            <?= htmlspecialchars($f['Category']) ?>
                        </span>

                        <?php if ($isAvailable): ?>
                            <span class="pill-badge pill-low" style="font-size: 0.78rem;">
                                &#10003; <?= htmlspecialchars(t('status_available')) ?>
                            </span>
                        <?php else: ?>
                            <span class="pill-badge pill-critical" style="font-size: 0.78rem;">
                                &#9888; <?= htmlspecialchars(t('status_maintenance')) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Facility Name & ID -->
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-heading); margin: 0 0 0.35rem 0;">
                        <?= htmlspecialchars($f['FacilityName']) ?>
                    </h3>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                        <?= htmlspecialchars($f['FacilityID']) ?> &bull; <?= htmlspecialchars($f['LocationBlock']) ?>
                    </div>

                    <!-- Details: Capacity & Reviews -->
                    <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem; color: var(--text-body); margin-bottom: 1.25rem; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 0.6rem 0;">
                        <div>
                            <strong><?= htmlspecialchars(t('lbl_capacity')) ?></strong> <?= htmlspecialchars((string)$f['Capacity']) ?> <?= htmlspecialchars(t('lbl_pax')) ?>
                        </div>
                        <div>&bull;</div>
                        <div>
                            <span style="color: #d97706; font-weight: 700;">★ <?= $avgScore ?></span>
                            <span style="color: var(--text-muted); font-size: 0.8rem;">(<?= $reviewsCount ?> <?= htmlspecialchars(t('lbl_reviews_count')) ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php if ($isAvailable): ?>
                        <button type="button" onclick="alert('Facility Booking Module: Ready for reservations! Initiating slot selection for <?= htmlspecialchars($f['FacilityName'], ENT_QUOTES) ?>.');" class="btn btn-primary-blue" style="flex: 1; padding: 0.5rem 0.75rem; font-size: 0.85rem; font-weight: 600;">
                            <?= htmlspecialchars(t('btn_quick_book')) ?>
                        </button>
                    <?php else: ?>
                        <button type="button" disabled class="btn btn-cancel" style="flex: 1; padding: 0.5rem 0.75rem; font-size: 0.85rem; cursor: not-allowed; opacity: 0.6;">
                            <?= htmlspecialchars(t('btn_locked_maint')) ?>
                        </button>
                    <?php endif; ?>

                    <a href="modules/feedback/index.php?facility_id=<?= urlencode($f['FacilityID']) ?>" class="btn btn-cancel" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                        <?= htmlspecialchars(t('btn_reviews')) ?>
                    </a>
                    <a href="modules/maintenance/report.php?facility_id=<?= urlencode($f['FacilityID']) ?>" class="btn btn-cancel" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                        <?= htmlspecialchars(t('btn_report')) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ======================================================== -->
<!-- SECTION 3: QUICK SERVICES TILES                          -->
<!-- ======================================================== -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
    <!-- Service 1: Report Defect -->
    <div class="ui-card" style="padding: 1.5rem; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">&#128295;</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.4rem;">
                <?= htmlspecialchars(t('srv_defect_title')) ?>
            </h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                <?= htmlspecialchars(t('srv_defect_desc')) ?>
            </p>
        </div>
        <a href="modules/maintenance/report.php" class="btn btn-primary-blue" style="font-size: 0.85rem; text-align: center;">
            <?= htmlspecialchars(t('srv_defect_btn')) ?>
        </a>
    </div>

    <!-- Service 2: Rate Session -->
    <div class="ui-card" style="padding: 1.5rem; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">&#11088;</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.4rem;">
                <?= htmlspecialchars(t('srv_rate_title')) ?>
            </h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                <?= htmlspecialchars(t('srv_rate_desc')) ?>
            </p>
        </div>
        <a href="modules/feedback/completed.php" class="btn btn-primary-blue" style="font-size: 0.85rem; text-align: center;">
            <?= htmlspecialchars(t('srv_rate_btn')) ?>
        </a>
    </div>

    <!-- Service 3: Maintenance & Moderation Hub -->
    <div class="ui-card" style="padding: 1.5rem; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">&#128203;</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.4rem;">
                <?= htmlspecialchars(t('srv_logs_title')) ?>
            </h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem 0;">
                <?= htmlspecialchars(t('srv_logs_desc')) ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="modules/maintenance/index.php" class="btn btn-cancel" style="flex: 1; font-size: 0.85rem; text-align: center;">
                <?= htmlspecialchars(t('srv_ticket_log')) ?>
            </a>
            <a href="modules/feedback/index.php" class="btn btn-cancel" style="flex: 1; font-size: 0.85rem; text-align: center;">
                <?= htmlspecialchars(t('srv_all_reviews')) ?>
            </a>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- INTERACTIVE QR CODE CHECK-IN MODAL (UNIQUE FEATURE)      -->
<!-- ======================================================== -->
<div id="qrModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
    <div style="background: #ffffff; border-radius: 16px; max-width: 440px; width: 100%; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); position: relative; text-align: center;">
        
        <!-- Close Button -->
        <button type="button" onclick="closeQrModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>

        <div style="display: inline-block; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.65rem; border-radius: 9999px; margin-bottom: 0.5rem; text-transform: uppercase;">
            Dynamic QR Check-In Pass
        </div>

        <h3 id="modalVenueTitle" style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem 0;">
            Facility Name
        </h3>
        <p id="modalBookingSubtitle" style="color: #64748b; font-size: 0.85rem; margin: 0 0 1.25rem 0;">
            Booking #BK-XXXX &bull; Date &bull; Time
        </p>

        <!-- Dynamic QR Code Container -->
        <div id="modalQrCodeBox" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.25rem; display: inline-block; margin-bottom: 1.25rem;">
            <!-- Generated SVG injected here -->
        </div>

        <p style="font-size: 0.82rem; color: #475569; margin: 0 0 1.5rem 0; line-height: 1.4;">
            Present this pass at the entrance scanner upon arrival. Check-in must be completed within <strong>15 minutes</strong> of session start.
        </p>

        <!-- Simulated Scan Action -->
        <form method="POST" action="index.php" id="modalScanForm">
            <input type="hidden" name="action" value="qr_check_in">
            <input type="hidden" name="booking_id" id="modalBookingIdInput" value="">
            <div style="display: flex; gap: 0.75rem;">
                <button type="button" onclick="closeQrModal()" class="btn btn-cancel" style="flex: 1;">
                    Close
                </button>
                <button type="submit" id="modalScanBtn" class="btn btn-primary-blue" style="flex: 2; font-weight: 700;">
                    &#128247; Simulate QR Scan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// QR Code Generator & Modal Handler
function generateSvgQr(bookingId) {
    const size = 180;
    const gridSize = 25;
    const cellSize = size / gridSize;
    
    // Hash simulation based on bookingId
    let hash = 0;
    for (let i = 0; i < bookingId.length; i++) {
        hash = ((hash << 5) - hash) + bookingId.charCodeAt(i);
        hash |= 0;
    }
    const hashStr = Math.abs(hash).toString(16).padStart(8, '0') + 'abcdef1234567890';

    let svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '">';
    svg += '<rect width="100%" height="100%" fill="#ffffff" rx="8"/>';

    function drawFinder(startX, startY) {
        const x = startX * cellSize;
        const y = startY * cellSize;
        const w = 7 * cellSize;
        svg += '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + w + '" fill="#0f172a" rx="4"/>';
        svg += '<rect x="' + (x + cellSize) + '" y="' + (y + cellSize) . '" width="' + (5 * cellSize) + '" height="' + (5 * cellSize) + '" fill="#ffffff" rx="2"/>';
        svg += '<rect x="' + (x + 2 * cellSize) + '" y="' + (y + 2 * cellSize) + '" width="' + (3 * cellSize) + '" height="' + (3 * cellSize) + '" fill="#0f172a" rx="2"/>';
    }

    drawFinder(1, 1);
    drawFinder(17, 1);
    drawFinder(1, 17);

    for (let r = 0; r < gridSize; r++) {
        for (let c = 0; c < gridSize; c++) {
            if ((r < 9 && c < 9) || (r < 9 && c >= 16) || (r >= 16 && c < 9)) continue;
            const bit = (r * gridSize + c) % hashStr.length;
            if (hashStr.charCodeAt(bit) % 2 === 0) {
                svg += '<rect x="' + (c * cellSize + 0.5) + '" y="' + (r * cellSize + 0.5) + '" width="' + (cellSize - 1) + '" height="' + (cellSize - 1) + '" fill="#1e293b" rx="1"/>';
            }
        }
    }

    // Center emblem
    const emSize = 6 * cellSize;
    const emOffset = (size - emSize) / 2;
    svg += '<rect x="' + emOffset + '" y="' + emOffset + '" width="' + emSize + '" height="' + emSize + '" fill="#ffffff" stroke="#2563eb" stroke-width="2" rx="4"/>';
    svg += '<text x="' + (size / 2) + '" y="' + (size / 2 + 4) + '" fill="#2563eb" font-family="system-ui, sans-serif" font-size="11" font-weight="800" text-anchor="middle">UE</text>';
    svg += '</svg>';
    return svg;
}

function openQrModal(bookingId, venueName, dateStr, timeStr, isAlreadyCheckedIn = false) {
    document.getElementById('modalVenueTitle').textContent = venueName;
    document.getElementById('modalBookingSubtitle').textContent = 'Booking #' + bookingId + ' • ' + dateStr + ' • ' + timeStr;
    document.getElementById('modalBookingIdInput').value = bookingId;
    document.getElementById('modalQrCodeBox').innerHTML = generateSvgQr(bookingId);

    const scanBtn = document.getElementById('modalScanBtn');
    if (isAlreadyCheckedIn) {
        scanBtn.disabled = true;
        scanBtn.textContent = 'Already Checked In ✓';
        scanBtn.className = 'btn btn-cancel';
    } else {
        scanBtn.disabled = false;
        scanBtn.textContent = '📷 Simulate QR Scan';
        scanBtn.className = 'btn btn-primary-blue';
    }

    const modal = document.getElementById('qrModalOverlay');
    modal.style.display = 'flex';
}

function closeQrModal() {
    document.getElementById('qrModalOverlay').style.display = 'none';
}

// Close modal on click outside
window.addEventListener('click', function(e) {
    const modal = document.getElementById('qrModalOverlay');
    if (e.target === modal) {
        closeQrModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
