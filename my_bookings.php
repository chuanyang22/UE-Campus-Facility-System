<?php
/**
 * My Bookings — Active Reservations, Dynamic QR Check-In & 15-Minute Auto-Release
 * AMCS2194 Mini Project — UE Campus Facility Booking System
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/services/BookingService.php';

$pageTitle = 'My Bookings — UE Campus Facility Booking System';
$activeNav = 'bookings';

$bookingService = new BookingService();
$currentUser    = AuthService::getCurrentUser();

if (AuthService::isTechnician()) {
    header('Location: modules/maintenance/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'qr_check_in') {
        $bookingId = trim((string)($_POST['booking_id'] ?? ''));
        $res = $bookingService->processQrCheckIn($bookingId);
        AuthService::setFlash($res['success'] ? 'success' : 'danger', $res['message']);
        header('Location: my_bookings.php');
        exit;
    }

    if ($action === 'run_auto_release') {
        $res = $bookingService->runNoShowAutoRelease();
        AuthService::setFlash('info', $res['message']);
        header('Location: my_bookings.php#no-show-engine');
        exit;
    }

    if ($action === 'seed_overdue_demo') {
        $demoId = $bookingService->seedOverdueDemoBooking();
        AuthService::setFlash('info', "Seeded unclaimed overdue booking (scheduled 25 mins ago). Click 'Run Auto-Release Engine Now' to test no-show cancellation.");
        header('Location: my_bookings.php#no-show-engine');
        exit;
    }

    if ($action === 'clear_penalty') {
        $bookingService->clearUserPenalty($currentUser['UserID']);
        AuthService::setFlash('info', 'Booking penalty cleared for testing.');
        header('Location: my_bookings.php');
        exit;
    }
}

$userBookings  = $bookingService->getUserBookings($currentUser['UserID']);
$penaltyStatus = $bookingService->getUserPenaltyStatus($currentUser['UserID']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header-row">
    <div>
        <h1 class="page-title"><?= htmlspecialchars(t('sec_reservations')) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars(t('sec_reservations_sub')) ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <span class="user-pill-chip" style="<?= $penaltyStatus['no_show_count'] >= 3 ? 'background:#fee2e2; color:#991b1b; border:1px solid #fecaca;' : '' ?>">
            <?= htmlspecialchars(t('strikes_label')) ?> <?= (int)$penaltyStatus['no_show_count'] ?> / 3
        </span>
        <a href="modules/feedback/completed.php" class="btn btn-outline-blue" style="padding: 0.5rem 0.95rem; font-size: 0.85rem;">
            <?= htmlspecialchars(t('btn_rate_completed')) ?>
        </a>
        <a href="index.php" class="btn btn-cancel" style="padding: 0.5rem 0.95rem; font-size: 0.85rem;">
            <?= htmlspecialchars(t('btn_back_home')) ?>
        </a>
    </div>
</div>

<!-- 3-Strike No-Show 3-Day Penalty Banner (If Active) -->
<?php if ($penaltyStatus['is_banned']): ?>
    <div style="background-color: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 10px; padding: 1.15rem 1.4rem; margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="color: #991b1b; font-weight: 800; font-size: 1rem; margin-bottom: 0.25rem;">
                &#9940; <?= htmlspecialchars(t('ban_title')) ?>
            </div>
            <div style="color: #7f1d1d; font-size: 0.9rem;">
                <?= htmlspecialchars(t('ban_desc')) ?>
                <strong><?= htmlspecialchars(date('d M Y, H:i', strtotime($penaltyStatus['ban_until']))) ?></strong>
            </div>
        </div>
        <form method="POST" action="my_bookings.php" style="margin: 0;">
            <input type="hidden" name="action" value="clear_penalty">
            <button type="submit" class="btn btn-cancel" style="font-size: 0.8rem; padding: 0.45rem 0.85rem;">
                <?= htmlspecialchars(t('btn_clear_penalty')) ?>
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Reservations Grid -->
<?php if (empty($userBookings)): ?>
    <div class="ui-card" style="text-align: center; padding: 2.5rem; margin-bottom: 2rem;">
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1rem;"><?= htmlspecialchars(t('no_bookings_msg')) ?></p>
        <a href="index.php#facility-directory" class="btn btn-primary-blue"><?= htmlspecialchars(t('btn_book_now')) ?></a>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
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
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-heading); margin: 0;">
                                <?= htmlspecialchars($b['FacilityName']) ?>
                            </h3>
                        </div>
                        <span class="pill-badge <?= $statusBadgeClass ?>" style="font-size: 0.8rem; padding: 0.25rem 0.65rem;">
                            <?= htmlspecialchars($statusLabel) ?>
                        </span>
                    </div>

                    <div style="font-size: 0.88rem; color: var(--text-body); margin-bottom: 0.85rem; line-height: 1.5;">
                        <div>&#128205; <strong><?= htmlspecialchars(t('lbl_location')) ?></strong> <?= htmlspecialchars($b['LocationBlock']) ?></div>
                        <div>&#128197; <strong><?= htmlspecialchars(t('lbl_date')) ?></strong> <?= htmlspecialchars(date('D, d M Y', strtotime($b['BookingDate']))) ?> <?= $isToday ? '<strong style="color: #3b82f6;">' . htmlspecialchars(t('lbl_today')) . '</strong>' : '' ?></div>
                        <div>&#128337; <strong><?= htmlspecialchars(t('lbl_time')) ?></strong> <?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> &ndash; <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?></div>
                        <div style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.25rem;"><?= htmlspecialchars(t('lbl_purpose')) ?> <?= htmlspecialchars($b['Purpose']) ?></div>
                    </div>

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

<!-- 15-Minute No-Show Auto-Release Engine -->
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
            <form method="POST" action="my_bookings.php" style="margin: 0;">
                <input type="hidden" name="action" value="seed_overdue_demo">
                <button type="submit" class="btn btn-cancel" style="font-size: 0.82rem; padding: 0.55rem 0.9rem;">
                    <?= htmlspecialchars(t('btn_seed_overdue')) ?>
                </button>
            </form>

            <form method="POST" action="my_bookings.php" style="margin: 0;">
                <input type="hidden" name="action" value="run_auto_release">
                <button type="submit" class="btn btn-primary-blue" style="background-color: #7e22ce; border-color: #6b21a8; font-size: 0.82rem; padding: 0.55rem 1.1rem; font-weight: 700;">
                    <?= htmlspecialchars(t('btn_run_autorelease')) ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Interactive QR Code Check-In Modal -->
<div id="qrModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
    <div style="background: var(--card-bg); color: var(--text-heading); border-radius: 16px; max-width: 440px; width: 100%; padding: 2rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); position: relative; text-align: center;">
        <button type="button" onclick="closeQrModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>

        <div style="display: inline-block; background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.65rem; border-radius: 9999px; margin-bottom: 0.5rem; text-transform: uppercase;">
            Dynamic QR Check-In Pass
        </div>

        <h3 id="modalVenueTitle" style="font-size: 1.25rem; font-weight: 800; color: var(--text-heading); margin: 0 0 0.25rem 0;">
            Facility Name
        </h3>
        <p id="modalBookingSubtitle" style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 1.25rem 0;">
            Date &bull; Time
        </p>

        <div id="modalQrCodeBox" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.25rem; display: inline-block; margin-bottom: 1.25rem;"></div>

        <p style="font-size: 0.82rem; color: var(--text-body); margin: 0 0 1.5rem 0; line-height: 1.4;">
            Present this pass at the entrance scanner upon arrival. Check-in must be completed within <strong>15 minutes</strong> of session start.
        </p>

        <form method="POST" action="my_bookings.php" id="modalScanForm">
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
function generateSvgQr(bookingId) {
    const size = 180;
    const gridSize = 25;
    const cellSize = size / gridSize;
    let hash = 0;
    for (let i = 0; i < bookingId.length; i++) {
        hash = ((hash << 5) - hash) + bookingId.charCodeAt(i);
        hash |= 0;
    }
    let svg = '<svg width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '" xmlns="http://www.w3.org/2000/svg">';
    svg += '<rect width="' + size + '" height="' + size + '" fill="#ffffff"/>';
    function drawFinder(ox, oy) {
        svg += '<rect x="' + (ox*cellSize) + '" y="' + (oy*cellSize) + '" width="' + (7*cellSize) + '" height="' + (7*cellSize) + '" fill="#0f172a"/>';
        svg += '<rect x="' + ((ox+1)*cellSize) + '" y="' + ((oy+1)*cellSize) + '" width="' + (5*cellSize) + '" height="' + (5*cellSize) + '" fill="#ffffff"/>';
        svg += '<rect x="' + ((ox+2)*cellSize) + '" y="' + ((oy+2)*cellSize) + '" width="' + (3*cellSize) + '" height="' + (3*cellSize) + '" fill="#0f172a"/>';
    }
    drawFinder(0, 0);
    drawFinder(gridSize - 7, 0);
    drawFinder(0, gridSize - 7);
    for (let r = 0; r < gridSize; r++) {
        for (let c = 0; c < gridSize; c++) {
            const inTopLeft = (r < 8 && c < 8);
            const inTopRight = (r < 8 && c >= gridSize - 8);
            const inBottomLeft = (r >= gridSize - 8 && c < 8);
            if (inTopLeft || inTopRight || inBottomLeft) continue;
            const seed = (hash + (r * 31) + (c * 17)) ^ (r * c);
            if ((Math.abs(seed) % 3) === 0 || (r === 6 && c % 2 === 0) || (c === 6 && r % 2 === 0)) {
                svg += '<rect x="' + (c * cellSize) + '" y="' + (r * cellSize) + '" width="' + cellSize + '" height="' + cellSize + '" fill="#0f172a"/>';
            }
        }
    }
    const emSize = cellSize * 5;
    const emOffset = (size - emSize) / 2;
    svg += '<rect x="' + emOffset + '" y="' + emOffset + '" width="' + emSize + '" height="' + emSize + '" fill="#ffffff" stroke="#2563eb" stroke-width="2" rx="4"/>';
    svg += '<text x="' + (size / 2) + '" y="' + (size / 2 + 4) + '" fill="#2563eb" font-family="system-ui, sans-serif" font-size="11" font-weight="800" text-anchor="middle">UE</text>';
    svg += '</svg>';
    return svg;
}

function openQrModal(bookingId, venueName, dateStr, timeStr, isAlreadyCheckedIn = false) {
    document.getElementById('modalVenueTitle').textContent = venueName;
    document.getElementById('modalBookingSubtitle').textContent = dateStr + ' • ' + timeStr;
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

    document.getElementById('qrModalOverlay').style.display = 'flex';
}

function closeQrModal() {
    document.getElementById('qrModalOverlay').style.display = 'none';
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('qrModalOverlay');
    if (e.target === modal) {
        closeQrModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
