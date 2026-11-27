<?php
/**
 * Module 3: Booking Receipt & Dynamic QR Entrance Pass (Retrieve)
 * AMCS2194 Mini Project — Process 3.2 Receipt
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/BookingService.php';

$bookingId = trim((string)($_GET['id'] ?? ''));
$service   = new BookingService();
$booking   = $service->getBookingDetails($bookingId);

if (!$booking) {
    AuthService::setFlash('danger', "Booking reference '{$bookingId}' not found.");
    header('Location: index.php');
    exit;
}

$pageTitle = "Receipt — {$booking['BookingID']} ({$booking['FacilityName']})";
$activeNav = 'bookings';

$currentUser = AuthService::getCurrentUser();
$isAdmin     = AuthService::isAdmin();

// Determine status badge class
$status = $booking['BookingStatus'];
$badgeClass = 'badge-confirmed';
if ($status === 'Checked-In') $badgeClass = 'badge-completed';
elseif ($status === 'Completed') $badgeClass = 'badge-archived';
elseif ($status === 'Cancelled') $badgeClass = 'badge-high';
elseif ($status === 'No-Show')   $badgeClass = 'badge-critical';

// Generate QR Code SVG
$qrSvg = BookingService::generateSvgQrCode($booking['BookingID'] . '|' . $booking['FacilityID'] . '|' . $booking['BookingDate'] . '|' . $booking['StartTime'], 180);

$canCancel = in_array($status, ['Confirmed'], true) && ($booking['BookingDate'] >= date('Y-m-d'));
$canBorrow = in_array($status, ['Confirmed', 'Checked-In'], true);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header -->
<div class="page-header-row">
    <div>
        <h1 class="page-title">Booking Confirmation & Entrance Pass</h1>
        <p class="page-subtitle">Official verified reservation receipt for campus access and equipment loan.</p>
    </div>
    <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
        <button type="button" onclick="window.print()" class="btn btn-outline-blue" style="font-weight: 600;">
            🖨️ Print Receipt
        </button>
        <a href="index.php" class="btn btn-cancel" style="padding: 0.55rem 1rem;">
            ← All Bookings
        </a>
    </div>
</div>

<!-- Receipt Main Card -->
<div class="ui-card" style="max-width: 820px; margin: 0 auto; padding: 2.25rem; border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); position: relative;">
    
    <!-- Top Receipt Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px dashed #e2e8f0; padding-bottom: 1.5rem; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 700; margin-bottom: 0.25rem;">
                TAR UMT CAMPUS FACILITY SYSTEM
            </div>
            <h2 style="margin: 0; font-size: 1.6rem; color: #1e3a8a; font-weight: 800;">
                <?= htmlspecialchars($booking['FacilityName']) ?>
            </h2>
            <div style="color: var(--text-muted); font-size: 0.92rem; margin-top: 0.25rem;">
                📍 <?= htmlspecialchars($booking['LocationBlock']) ?> • Category: <strong><?= htmlspecialchars($booking['FacilityCategory']) ?></strong>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.8rem; color: #64748b; font-weight: 600;">BOOKING REF</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #2563eb; letter-spacing: 0.5px;">
                <?= htmlspecialchars($booking['BookingID']) ?>
            </div>
            <div style="margin-top: 0.35rem;">
                <span class="badge <?= $badgeClass ?>" style="font-size: 0.85rem; padding: 0.3rem 0.8rem;">
                    <?= htmlspecialchars($status) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Center Section: Details & QR Pass -->
    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; align-items: center; margin-bottom: 1.75rem;">
        <div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 1.25rem; font-size: 0.92rem;">
                <div>
                    <div style="font-size: 0.78rem; color: #64748b; font-weight: 600;">DATE</div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: 1.05rem;">
                        🗓️ <?= htmlspecialchars(date('D, d M Y', strtotime($booking['BookingDate']))) ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.78rem; color: #64748b; font-weight: 600;">TIME SLOT</div>
                    <div style="font-weight: 700; color: var(--text-color); font-size: 1.05rem;">
                        ⏰ <?= htmlspecialchars(substr($booking['StartTime'], 0, 5) . ' – ' . substr($booking['EndTime'], 0, 5)) ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.78rem; color: #64748b; font-weight: 600;">RESERVED BY</div>
                    <div style="font-weight: 700; color: var(--text-color);">
                        👤 <?= htmlspecialchars($booking['UserName']) ?> (<?= htmlspecialchars($booking['UserID']) ?>)
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.78rem; color: #64748b; font-weight: 600;">PARTY SIZE</div>
                    <div style="font-weight: 700; color: var(--text-color);">
                        👥 <?= (int)($booking['PartySize'] ?? 1) ?> Persons (Max <?= (int)$booking['Capacity'] ?>)
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.25rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem 1rem;">
                <div style="font-size: 0.78rem; color: #64748b; font-weight: 600;">SESSION PURPOSE</div>
                <div style="font-weight: 600; color: #1e293b; margin-top: 2px;">
                    <?= htmlspecialchars($booking['Purpose']) ?>
                </div>
            </div>

            <?php if (!empty($booking['CheckInTime'])): ?>
                <div style="margin-top: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 0.6rem 0.9rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                    ✓ Attendance Verified: Checked in at <?= htmlspecialchars(date('d M Y, H:i:s', strtotime($booking['CheckInTime']))) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dynamic QR Code Pass Container -->
        <div style="text-align: center; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 12px; padding: 1.25rem;">
            <div style="display: inline-block;">
                <?= $qrSvg ?>
            </div>
            <div style="font-size: 0.78rem; font-weight: 700; color: #475569; margin-top: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                Dynamic Entrance Pass
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                Scan with scanner tablet at venue door
            </div>
        </div>
    </div>

    <!-- Complimentary Equipment Section -->
    <div style="border-top: 2px dashed #e2e8f0; padding-top: 1.5rem; margin-top: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b;">
                    📦 Complimentary Equipment Reserved (<?= count($booking['EquipmentList']) ?>)
                </h3>
                <p style="margin: 0.15rem 0 0 0; font-size: 0.82rem; color: var(--text-muted);">
                    Free loan accessories associated with this booking. Pick up from campus facility desk.
                </p>
            </div>
            <?php if ($canBorrow): ?>
                <a href="../equipment/borrow.php?booking_id=<?= urlencode($booking['BookingID']) ?>" class="btn btn-outline-blue" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">
                    + Request More Gear
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($booking['EquipmentList'])): ?>
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 1.1rem; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                No complimentary equipment requested yet. 
                <?php if ($canBorrow): ?>
                    <a href="../equipment/borrow.php?booking_id=<?= urlencode($booking['BookingID']) ?>" style="color: #2563eb; font-weight: 600; margin-left: 0.35rem;">
                        Add sports gear or AV accessories &rarr;
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.75rem;">
                <?php foreach ($booking['EquipmentList'] as $eq): ?>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.92rem; color: #1e293b;">
                                <?= htmlspecialchars($eq['ItemName']) ?>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                Ref: <?= htmlspecialchars($eq['EquipmentID']) ?> • Qty: <?= (int)$eq['BorrowedQty'] ?>
                            </div>
                        </div>
                        <span class="badge <?= $eq['BorrowStatus'] === 'Returned' ? 'badge-completed' : 'badge-confirmed' ?>" style="font-size: 0.75rem;">
                            <?= htmlspecialchars($eq['BorrowStatus']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Venue Usage Policy Note -->
    <div style="margin-top: 1.5rem; background: #eff6ff; border-left: 4px solid #2563eb; padding: 0.75rem 1rem; font-size: 0.82rem; color: #1e40af; border-radius: 0 8px 8px 0;">
        <strong>Campus Facility Policy:</strong> Please arrive within 15 minutes of scheduled start time. Reservations without QR check-in after 15 minutes will be auto-released for other students.
    </div>

    <!-- Bottom Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; gap: 0.65rem;">
            <a href="index.php" class="btn btn-outline-blue" style="font-size: 0.85rem; padding: 0.45rem 0.95rem;">
                ← Back to Dashboard
            </a>
            <?php if ($canBorrow): ?>
                <a href="../equipment/borrow.php?booking_id=<?= urlencode($booking['BookingID']) ?>" class="btn btn-primary-blue" style="font-size: 0.85rem; padding: 0.45rem 0.95rem;">
                    📦 Borrow Equipment
                </a>
            <?php endif; ?>
        </div>

        <?php if ($canCancel): ?>
            <a href="cancel.php?id=<?= urlencode($booking['BookingID']) ?>" 
               onclick="return confirm('Are you sure you want to cancel booking <?= htmlspecialchars($booking['BookingID']) ?>? This action is permanent.');" 
               class="btn btn-danger-red" style="font-size: 0.85rem; padding: 0.45rem 0.95rem;">
                Cancel This Reservation
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
