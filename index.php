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
<div class="ui-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 2.25rem 2.5rem; margin-bottom: 2rem; border-color: #334155; border-radius: 12px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div style="max-width: 720px;">
            <div style="display: inline-block; background: rgba(56, 189, 248, 0.15); color: #38bdf8; font-weight: 700; font-size: 0.8rem; padding: 0.25rem 0.75rem; border-radius: 9999px; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                TAR UMT Centralized Campus Facility Booking Portal
            </div>
            <h1 style="font-size: 2.2rem; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; margin-bottom: 0.6rem;">
                UE Campus Facility Booking System
            </h1>
            <p style="color: #94a3b8; font-size: 1rem; line-height: 1.6; margin: 0 0 1.25rem 0;">
                Reserve sports courts and academic discussion rooms in real time. Use your dynamic QR code for swift on-site entrance verification, or lodge maintenance defect reports and feedback to keep campus facilities in top condition.
            </p>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="#facility-directory" class="btn btn-primary-blue" style="font-weight: 700; padding: 0.7rem 1.4rem;">
                    Browse Available Facilities &darr;
                </a>
                <a href="#my-reservations" class="btn btn-cancel" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #ffffff; font-weight: 600; padding: 0.7rem 1.2rem;">
                    My Reservations &amp; QR Check-In &darr;
                </a>
            </div>
        </div>

        <!-- Quick Portal Navigation Tile -->
        <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 1.25rem 1.5rem; min-width: 270px;">
            <div style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 0.6rem;">Quick Actions</div>
            <div style="display: flex; flex-direction: column; gap: 0.55rem;">
                <a href="modules/maintenance/report.php" class="btn btn-primary-blue" style="font-size: 0.88rem; text-align: left; padding: 0.55rem 0.9rem;">
                    + Report Facility Defect
                </a>
                <a href="modules/feedback/submit.php" class="btn btn-cancel" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #ffffff; font-size: 0.88rem; text-align: left; padding: 0.55rem 0.9rem;">
                    + Rate Completed Session
                </a>
                <a href="modules/feedback/index.php" class="btn btn-cancel" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.15); color: #94a3b8; font-size: 0.85rem; text-align: left; padding: 0.5rem 0.9rem;">
                    Browse Facility Reviews &rarr;
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
            <h2 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 0.2rem;">
                My Active &amp; Upcoming Reservations
            </h2>
            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">
                View your confirmed booking sessions and tap <strong>QR Check-In</strong> upon arriving at the venue.
            </p>
        </div>
        <div>
            <span style="font-size: 0.85rem; font-weight: 600; color: #475569; background: #f1f5f9; padding: 0.35rem 0.75rem; border-radius: 9999px;">
                User: <?= htmlspecialchars($currentUser['FullName'] ?? 'Student') ?> (<?= htmlspecialchars($currentUser['UserID']) ?>)
            </span>
        </div>
    </div>

    <?php if (empty($userBookings)): ?>
        <div class="ui-card" style="text-align: center; padding: 2.5rem;">
            <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 1rem;">You currently have no active facility bookings.</p>
            <a href="#facility-directory" class="btn btn-primary-blue">Book a Campus Facility Now</a>
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
                $isToday = ($b['BookingDate'] === date('Y-m-d'));
            ?>
                <div class="ui-card" style="margin: 0; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid <?= $status === 'Checked-In' ? '#10b981' : ($status === 'Confirmed' ? '#2563eb' : '#94a3b8') ?>;">
                    <div>
                        <!-- Header with Status -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                            <div>
                                <span style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase;">
                                    Booking #<?= htmlspecialchars($b['BookingID']) ?>
                                </span>
                                <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0.15rem 0 0 0;">
                                    <?= htmlspecialchars($b['FacilityName']) ?>
                                </h3>
                            </div>
                            <span class="pill-badge <?= $statusBadgeClass ?>" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </div>

                        <!-- Venue Details -->
                        <div style="font-size: 0.88rem; color: #475569; margin-bottom: 0.85rem; line-height: 1.5;">
                            <div>&#128205; <strong>Location:</strong> <?= htmlspecialchars($b['LocationBlock']) ?></div>
                            <div>&#128197; <strong>Date:</strong> <?= htmlspecialchars(date('D, d M Y', strtotime($b['BookingDate']))) ?> <?= $isToday ? '<strong style="color: #2563eb;">(Today)</strong>' : '' ?></div>
                            <div>&#128337; <strong>Time:</strong> <?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> &ndash; <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?></div>
                            <div style="color: #64748b; font-size: 0.82rem; margin-top: 0.25rem;">Purpose: <?= htmlspecialchars($b['Purpose']) ?></div>
                        </div>

                        <!-- Check-In Status Information -->
                        <?php if ($status === 'Checked-In' && !empty($b['CheckInTime'])): ?>
                            <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.85rem; font-size: 0.82rem; color: #065f46;">
                                <strong>&#10003; Attendance Verified:</strong> Checked in at <?= htmlspecialchars(date('d M Y, H:i', strtotime($b['CheckInTime']))) ?>
                            </div>
                        <?php elseif ($status === 'No-Show'): ?>
                            <div style="background-color: #fef2f2; border: 1px solid #fecdd3; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.85rem; font-size: 0.82rem; color: #991b1b;">
                                <strong>&#9888; Expired No-Show:</strong> Check-in window passed (15-min limit). Facility released.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Bar -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 0.85rem; margin-top: 0.5rem;">
                        <?php if ($status === 'Confirmed'): ?>
                            <button type="button" 
                                    class="btn btn-primary-blue" 
                                    onclick="openQrModal('<?= htmlspecialchars($b['BookingID']) ?>', '<?= htmlspecialchars($b['FacilityName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['BookingDate']) ?>', '<?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> - <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?>')" 
                                    style="flex: 1; padding: 0.55rem; font-size: 0.85rem; font-weight: 700; display: inline-flex; justify-content: center; align-items: center; gap: 0.4rem;">
                                &#128241; QR Check-In
                            </button>
                        <?php elseif ($status === 'Checked-In'): ?>
                            <button type="button" 
                                    class="btn btn-cancel" 
                                    onclick="openQrModal('<?= htmlspecialchars($b['BookingID']) ?>', '<?= htmlspecialchars($b['FacilityName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($b['BookingDate']) ?>', '<?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> - <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?>', true)" 
                                    style="flex: 1; padding: 0.55rem; font-size: 0.85rem;">
                                View Pass / QR
                            </button>
                        <?php elseif ($status === 'Completed'): ?>
                            <a href="modules/feedback/submit.php?booking_id=<?= urlencode($b['BookingID']) ?>" class="btn btn-outline-blue" style="flex: 1; padding: 0.55rem; font-size: 0.85rem; text-align: center;">
                                &#9997; Rate &amp; Review
                            </a>
                        <?php endif; ?>

                        <a href="modules/maintenance/report.php?facility_id=<?= urlencode($b['FacilityID']) ?>" class="btn btn-cancel" style="padding: 0.55rem 0.75rem; font-size: 0.82rem;" title="Report defect for this venue">
                            Report Defect
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
<div id="no-show-engine" class="ui-card" style="background: #faf5ff; border: 1.5px solid #e9d5ff; border-radius: 12px; padding: 1.5rem 1.75rem; margin-bottom: 2.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
        <div style="max-width: 750px;">
            <div style="display: inline-block; background: #f3e8ff; color: #7e22ce; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 9999px; margin-bottom: 0.5rem; text-transform: uppercase;">
                Unique Feature Demonstration (Student 2: Tie Sing Wee)
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #581c87; margin: 0 0 0.4rem 0;">
                15-Minute No-Show Auto-Release Engine
            </h3>
            <p style="color: #6b21a8; font-size: 0.88rem; line-height: 1.5; margin: 0;">
                Prevents venue hoarding and idle courts. If a student does not perform a QR check-in within <strong>15 minutes</strong> of their reserved session start time, the auto-release engine cancels the reservation as <strong>No-Show</strong> and unlocks the time slot for other students.
            </p>
        </div>

        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
            <form method="POST" action="index.php" style="margin: 0;">
                <input type="hidden" name="action" value="seed_overdue_demo">
                <button type="submit" class="btn btn-cancel" style="font-size: 0.82rem; padding: 0.55rem 0.9rem;" title="Seed a test session scheduled 25 minutes ago">
                    + Seed Overdue Test Session
                </button>
            </form>

            <form method="POST" action="index.php" style="margin: 0;">
                <input type="hidden" name="action" value="run_auto_release">
                <button type="submit" class="btn btn-primary-blue" style="background-color: #7e22ce; border-color: #6b21a8; font-size: 0.82rem; padding: 0.55rem 1.1rem; font-weight: 700;">
                    &#9889; Run Auto-Release Engine Now
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
            <h2 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-bottom: 0.2rem;">
                Browse Campus Facilities
            </h2>
            <p style="color: #64748b; font-size: 0.9rem; margin: 0;">
                Explore sports courts and academic study rooms. Check verified ratings and availability before booking.
            </p>
        </div>

        <!-- Filter & Search Controls -->
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <a href="index.php?category=ALL#facility-directory" class="btn <?= $categoryFilter === 'ALL' ? 'btn-primary-blue' : 'btn-cancel' ?>" style="font-size: 0.82rem; padding: 0.45rem 0.85rem;">
                All Facilities
            </a>
            <a href="index.php?category=Sports#facility-directory" class="btn <?= $categoryFilter === 'Sports' ? 'btn-primary-blue' : 'btn-cancel' ?>" style="font-size: 0.82rem; padding: 0.45rem 0.85rem;">
                &#127992; Sports Facilities
            </a>
            <a href="index.php?category=Academic#facility-directory" class="btn <?= $categoryFilter === 'Academic' ? 'btn-primary-blue' : 'btn-cancel' ?>" style="font-size: 0.82rem; padding: 0.45rem 0.85rem;">
                &#128218; Academic Facilities
            </a>
        </div>
    </div>

    <!-- Facility Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
        <?php foreach ($facilities as $f): 
            $isAvailable = ($f['AvailabilityStatus'] === 'Available');
            $avgScore = $f['AvgScore'] ? number_format((float)$f['AvgScore'], 1) : 'New';
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
                                &#10003; Available
                            </span>
                        <?php else: ?>
                            <span class="pill-badge pill-critical" style="font-size: 0.78rem;">
                                &#9888; Under Maintenance
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Facility Name & ID -->
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0 0 0.35rem 0;">
                        <?= htmlspecialchars($f['FacilityName']) ?>
                    </h3>
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.75rem;">
                        <?= htmlspecialchars($f['FacilityID']) ?> &bull; <?= htmlspecialchars($f['LocationBlock']) ?>
                    </div>

                    <!-- Details: Capacity & Reviews -->
                    <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.85rem; color: #475569; margin-bottom: 1.25rem; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; padding: 0.6rem 0;">
                        <div>
                            <strong>Capacity:</strong> <?= htmlspecialchars((string)$f['Capacity']) ?> pax
                        </div>
                        <div>&bull;</div>
                        <div>
                            <span style="color: #d97706; font-weight: 700;">★ <?= $avgScore ?></span>
                            <span style="color: #94a3b8; font-size: 0.8rem;">(<?= $reviewsCount ?> reviews)</span>
                        </div>
                    </div>
                </div>

                <!-- Action Controls -->
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php if ($isAvailable): ?>
                        <button type="button" onclick="alert('Facility Booking Module (Module 1/3): Ready for reservations! In a full multi-user deployment, choosing this initiates slot selection for <?= htmlspecialchars($f['FacilityName'], ENT_QUOTES) ?>.');" class="btn btn-primary-blue" style="flex: 1; padding: 0.5rem 0.75rem; font-size: 0.85rem; font-weight: 600;">
                            Quick Book
                        </button>
                    <?php else: ?>
                        <button type="button" disabled class="btn btn-cancel" style="flex: 1; padding: 0.5rem 0.75rem; font-size: 0.85rem; cursor: not-allowed; opacity: 0.6;">
                            Locked for Maintenance
                        </button>
                    <?php endif; ?>

                    <a href="modules/feedback/index.php?facility_id=<?= urlencode($f['FacilityID']) ?>" class="btn btn-cancel" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" title="View verified reviews">
                        Reviews
                    </a>
                    <a href="modules/maintenance/report.php?facility_id=<?= urlencode($f['FacilityID']) ?>" class="btn btn-cancel" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;" title="Report defect">
                        Report
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ======================================================== -->
<!-- SECTION 3: QUICK SERVICES TILES (MODULES 5 & 6 ACCESS)    -->
<!-- ======================================================== -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
    <!-- Service 1: Report Defect -->
    <div class="ui-card" style="padding: 1.5rem; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">&#128295;</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.4rem;">
                Report Facility Defect
            </h3>
            <p style="font-size: 0.88rem; color: #64748b; line-height: 1.5; margin: 0 0 1rem 0;">
                Found broken equipment, faulty lighting, or an electrical issue? Lodge a maintenance ticket with category filtering and real-time technician tracking.
            </p>
        </div>
        <a href="modules/maintenance/report.php" class="btn btn-primary-blue" style="font-size: 0.85rem; text-align: center;">
            Lodge Defect Ticket &rarr;
        </a>
    </div>

    <!-- Service 2: Rate Session -->
    <div class="ui-card" style="padding: 1.5rem; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">&#11088;</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.4rem;">
                Rate &amp; Review Session
            </h3>
            <p style="font-size: 0.88rem; color: #64748b; line-height: 1.5; margin: 0 0 1rem 0;">
                Completed your booking session? Share your feedback on venue cleanliness and equipment quality to guide other students and campus facilities staff.
            </p>
        </div>
        <a href="modules/feedback/submit.php" class="btn btn-primary-blue" style="font-size: 0.85rem; text-align: center;">
            Submit Session Review &rarr;
        </a>
    </div>

    <!-- Service 3: Maintenance & Moderation Hub -->
    <div class="ui-card" style="padding: 1.5rem; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="font-size: 1.75rem; margin-bottom: 0.5rem;">&#128203;</div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 0.4rem;">
                Facility Logs &amp; Moderation
            </h3>
            <p style="font-size: 0.88rem; color: #64748b; line-height: 1.5; margin: 0 0 1rem 0;">
                Access the Maintenance Ticket Log to monitor repair progress, or inspect official administrator replies and review moderation records.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="modules/maintenance/index.php" class="btn btn-cancel" style="flex: 1; font-size: 0.85rem; text-align: center;">
                Ticket Log
            </a>
            <a href="modules/feedback/index.php" class="btn btn-cancel" style="flex: 1; font-size: 0.85rem; text-align: center;">
                All Reviews
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
