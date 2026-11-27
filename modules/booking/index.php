<?php
/**
 * Module 3: Central Facility Booking Management Dashboard (Retrieve)
 * AMCS2194 Mini Project — Process 3.2
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/BookingService.php';

$pageTitle = 'Central Facility Booking Management';
$activeNav = 'bookings';

$bookingService = new BookingService();
$currentUser    = AuthService::getCurrentUser();
$isAdmin        = AuthService::isAdmin();
$isTech         = AuthService::isTechnician();

// Technicians redirect to maintenance
if ($isTech) {
    header('Location: ../maintenance/index.php');
    exit;
}

// Prepare filters
$filters = [
    'search'   => trim((string)($_GET['search'] ?? '')),
    'status'   => trim((string)($_GET['status'] ?? 'ALL')),
    'date'     => trim((string)($_GET['date'] ?? '')),
    'category' => trim((string)($_GET['category'] ?? 'ALL')),
];

// Admins can see all bookings; Students see their own
$targetUserId = $isAdmin && isset($_GET['view_all']) && $_GET['view_all'] === '1' ? null : $currentUser['UserID'];
$bookings     = $bookingService->getFilteredBookings($filters, $targetUserId);
$penaltyInfo  = $bookingService->getUserPenaltyStatus($currentUser['UserID']);

// Calculate summary stats
$todayDate     = date('Y-m-d');
$totalCount    = count($bookings);
$upcomingCount = 0;
$todayCount    = 0;
$pastCount     = 0;

$groupedUpcoming = [];
$groupedToday    = [];
$groupedPast     = [];

foreach ($bookings as $b) {
    $bDate = $b['BookingDate'];
    $bEnd  = $b['EndTime'];
    $isPastOrEnded = ($bDate < $todayDate) || ($bDate === $todayDate && $bEnd <= date('H:i:s'));

    if ($bDate === $todayDate && in_array($b['BookingStatus'], ['Confirmed', 'Checked-In'], true) && !$isPastOrEnded) {
        $todayCount++;
        $groupedToday[] = $b;
    } elseif ($bDate > $todayDate && in_array($b['BookingStatus'], ['Confirmed'], true)) {
        $upcomingCount++;
        $groupedUpcoming[] = $b;
    } else {
        $pastCount++;
        $groupedPast[] = $b;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header with Action Buttons -->
<div class="page-header-row">
    <div>
        <h1 class="page-title"><?= htmlspecialchars(t('nav_my_bookings')) ?> — Central Facility Management</h1>
        <p class="page-subtitle">Reserve campus sports courts and academic study rooms, manage reservations, and track status.</p>
    </div>
    <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
        <?php if ($penaltyInfo['is_banned']): ?>
            <span class="badge badge-high" style="padding: 0.55rem 0.9rem;">
                ⚠️ Booking Suspended (3-Day Penalty)
            </span>
        <?php else: ?>
            <a href="create.php" class="btn btn-primary-blue" style="font-weight: 600;">
                + Book Campus Facility
            </a>
        <?php endif; ?>
        <a href="../equipment/index.php" class="btn btn-outline-blue" style="font-weight: 500;">
            📦 Equipment Catalog
        </a>
        <a href="../../index.php" class="btn btn-cancel" style="font-weight: 500;">
            ← Home
        </a>
    </div>
</div>

<!-- 3-Day Penalty Alert (if active) -->
<?php if ($penaltyInfo['is_banned']): ?>
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 1.1rem 1.35rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <strong style="color: #991b1b; font-size: 1rem;">🚨 Booking Suspension Active (3-Day Penalty):</strong>
            <p style="color: #7f1d1d; margin: 0.25rem 0 0 0; font-size: 0.9rem;">
                You have accumulated <?= (int)$penaltyInfo['no_show_count'] ?> unexcused no-show strikes. New bookings are suspended until 
                <strong><?= htmlspecialchars(date('d M Y, H:i', strtotime((string)$penaltyInfo['ban_until']))) ?></strong>.
            </p>
        </div>
        <form method="POST" action="../../index.php" style="margin: 0;">
            <input type="hidden" name="action" value="clear_penalty">
            <button type="submit" class="btn btn-outline-blue" style="padding: 0.4rem 0.8rem; font-size: 0.82rem; background: #fff;">
                Reset Penalty (Demo)
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- KPI Quick Overview Cards -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-label">Total Reservations</div>
        <div class="stat-number stat-blue"><?= $totalCount ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
            <?= $isAdmin && isset($_GET['view_all']) ? 'All campus users' : 'For user ' . htmlspecialchars($currentUser['UserID']) ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Upcoming Confirmed</div>
        <div class="stat-number stat-completed"><?= $upcomingCount ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Future reserved slots</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Today's Sessions</div>
        <div class="stat-number stat-yellow"><?= $todayCount ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Eligible for QR check-in</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Past / Completed / Cancelled</div>
        <div class="stat-number stat-archived"><?= $pastCount ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Completed history</div>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-card" style="margin-bottom: 1.75rem;">
    <form method="GET" action="index.php" class="filter-form">
        <?php if ($isAdmin && isset($_GET['view_all'])): ?>
            <input type="hidden" name="view_all" value="1">
        <?php endif; ?>

        <div class="filter-group" style="flex: 1.8; min-width: 220px;">
            <label class="form-label" for="filterSearch">Search Keywords</label>
            <input type="text" id="filterSearch" name="search" class="form-control" 
                   placeholder="Search booking ID, venue name, purpose..." 
                   value="<?= htmlspecialchars($filters['search']) ?>">
        </div>

        <div class="filter-group" style="flex: 1; min-width: 140px;">
            <label class="form-label" for="filterStatus">Booking Status</label>
            <select id="filterStatus" name="status" class="form-select">
                <option value="ALL" <?= $filters['status'] === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                <option value="Confirmed" <?= $filters['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="Checked-In" <?= $filters['status'] === 'Checked-In' ? 'selected' : '' ?>>Checked-In</option>
                <option value="Completed" <?= $filters['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Cancelled" <?= $filters['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                <option value="No-Show" <?= $filters['status'] === 'No-Show' ? 'selected' : '' ?>>No-Show</option>
            </select>
        </div>

        <div class="filter-group" style="flex: 1; min-width: 140px;">
            <label class="form-label" for="filterCategory">Category</label>
            <select id="filterCategory" name="category" class="form-select">
                <option value="ALL" <?= $filters['category'] === 'ALL' ? 'selected' : '' ?>>All Categories</option>
                <option value="Sports" <?= $filters['category'] === 'Sports' ? 'selected' : '' ?>>Sports Facilities</option>
                <option value="Academic" <?= $filters['category'] === 'Academic' ? 'selected' : '' ?>>Academic Facilities</option>
            </select>
        </div>

        <div class="filter-group" style="flex: 1; min-width: 140px;">
            <label class="form-label" for="filterDate">Filter Date</label>
            <input type="date" id="filterDate" name="date" class="form-control" 
                   value="<?= htmlspecialchars($filters['date']) ?>">
        </div>

        <div class="filter-actions" style="display: flex; gap: 0.5rem; align-items: flex-end;">
            <button type="submit" class="btn btn-primary-blue" style="padding: 0.55rem 1rem;">Filter</button>
            <a href="index.php" class="btn btn-cancel" style="padding: 0.55rem 0.9rem;">Reset</a>
        </div>
    </form>
</div>

<!-- SECTION 1: TODAY'S ACTIVE SESSIONS (Priority) -->
<div class="section-title-wrap" style="margin-bottom: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-color);">
            📅 Today's Sessions (<?= count($groupedToday) ?>)
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
            Immediate bookings for today. Verify attendance with dynamic QR code upon arrival.
        </p>
    </div>
</div>

<?php if (empty($groupedToday)): ?>
    <div class="ui-card" style="padding: 1.75rem; text-align: center; color: var(--text-muted); margin-bottom: 2rem;">
        No bookings scheduled for today.
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2.25rem;">
        <?php foreach ($groupedToday as $b): ?>
            <?php renderBookingCard($b, $isAdmin, $currentUser); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- SECTION 2: UPCOMING RESERVATIONS -->
<div class="section-title-wrap" style="margin-bottom: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-color);">
            ⏳ Upcoming Reservations (<?= count($groupedUpcoming) ?>)
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
            Confirmed sessions scheduled for future dates. Complimentary equipment may be requested in advance.
        </p>
    </div>
</div>

<?php if (empty($groupedUpcoming)): ?>
    <div class="ui-card" style="padding: 1.75rem; text-align: center; color: var(--text-muted); margin-bottom: 2rem;">
        No upcoming reservations found.
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2.25rem;">
        <?php foreach ($groupedUpcoming as $b): ?>
            <?php renderBookingCard($b, $isAdmin, $currentUser); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- SECTION 3: PAST & COMPLETED RESERVATIONS -->
<div class="section-title-wrap" style="margin-bottom: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-color);">
            📜 Past & Completed Sessions (<?= count($groupedPast) ?>)
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
            Completed sessions, cancellations, or expired no-shows.
        </p>
    </div>
</div>

<?php if (empty($groupedPast)): ?>
    <div class="ui-card" style="padding: 1.75rem; text-align: center; color: var(--text-muted); margin-bottom: 2rem;">
        No past reservation history.
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
        <?php foreach ($groupedPast as $b): ?>
            <?php renderBookingCard($b, $isAdmin, $currentUser); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
/**
 * Helper function to render a single booking card
 */
function renderBookingCard(array $b, bool $isAdmin, array $currentUser): void {
    $status = $b['BookingStatus'];
    $badgeClass = 'badge-confirmed';
    if ($status === 'Checked-In') $badgeClass = 'badge-completed';
    elseif ($status === 'Completed') $badgeClass = 'badge-archived';
    elseif ($status === 'Cancelled') $badgeClass = 'badge-high';
    elseif ($status === 'No-Show')   $badgeClass = 'badge-critical';

    $isSports = ($b['FacilityCategory'] ?? '') === 'Sports';
    $catBadgeStyle = $isSports ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;';
    $canCancel = in_array($status, ['Confirmed'], true) && ($b['BookingDate'] >= date('Y-m-d'));
    $eqCount = (int)($b['EquipmentCount'] ?? 0);
    ?>
    <div class="ui-card" style="border-left: 5px solid <?= $status === 'Confirmed' ? '#2563eb' : ($status === 'Checked-In' ? '#16a34a' : ($status === 'Cancelled' ? '#dc2626' : '#94a3b8')) ?>;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                    <span style="font-weight: 700; color: #2563eb; font-size: 1.05rem;">
                        <?= htmlspecialchars($b['BookingID']) ?>
                    </span>
                    <span style="padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; <?= $catBadgeStyle ?>">
                        <?= htmlspecialchars($b['FacilityCategory']) ?>
                    </span>
                    <span class="badge <?= $badgeClass ?>">
                        <?= htmlspecialchars($status) ?>
                    </span>
                    <?php if ($isAdmin): ?>
                        <span style="font-size: 0.8rem; color: var(--text-muted); background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">
                            User: <?= htmlspecialchars($b['UserName'] ?? $b['UserID']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 style="margin: 0 0 0.4rem 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                    <?= htmlspecialchars($b['FacilityName']) ?>
                </h3>
                <div style="display: flex; gap: 1.25rem; font-size: 0.88rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 0.6rem;">
                    <div>📍 <?= htmlspecialchars($b['LocationBlock']) ?></div>
                    <div>🗓️ <?= htmlspecialchars(date('D, d M Y', strtotime($b['BookingDate']))) ?></div>
                    <div>⏰ <?= htmlspecialchars(substr($b['StartTime'], 0, 5) . ' – ' . substr($b['EndTime'], 0, 5)) ?></div>
                    <div>👥 <?= (int)($b['PartySize'] ?? 1) ?> pax (Max <?= (int)$b['Capacity'] ?>)</div>
                </div>
                <div style="font-size: 0.9rem; color: var(--text-color); margin-bottom: 0.5rem;">
                    <strong>Purpose:</strong> <?= htmlspecialchars($b['Purpose']) ?>
                </div>

                <!-- Equipment Badge -->
                <?php if ($eqCount > 0): ?>
                    <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: #faf5ff; border: 1px solid #e9d5ff; color: #6b21a8; padding: 3px 10px; border-radius: 6px; font-size: 0.82rem; font-weight: 600;">
                        📦 <?= $eqCount ?> complimentary item(s) reserved
                    </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <a href="receipt.php?id=<?= urlencode($b['BookingID']) ?>" class="btn btn-outline-blue" style="font-size: 0.85rem; padding: 0.45rem 0.9rem;">
                    View Pass / Receipt
                </a>
                
                <?php if (in_array($status, ['Confirmed', 'Checked-In'], true)): ?>
                    <a href="../equipment/borrow.php?booking_id=<?= urlencode($b['BookingID']) ?>" class="btn btn-outline-blue" style="font-size: 0.85rem; padding: 0.45rem 0.9rem; background: #faf5ff; color: #6b21a8; border-color: #d8b4fe;">
                        + Equipment
                    </a>
                <?php endif; ?>

                <?php if ($status === 'Completed'): ?>
                    <a href="../feedback/submit.php?booking_id=<?= urlencode($b['BookingID']) ?>" class="btn btn-primary-blue" style="font-size: 0.85rem; padding: 0.45rem 0.9rem;">
                        ★ Rate Session
                    </a>
                <?php endif; ?>

                <?php if ($canCancel): ?>
                    <a href="cancel.php?id=<?= urlencode($b['BookingID']) ?>" 
                       onclick="return confirm('Are you sure you want to cancel booking <?= htmlspecialchars($b['BookingID']) ?>? This will release your time slot and all equipment holds.');" 
                       class="btn btn-danger-red" style="font-size: 0.85rem; padding: 0.45rem 0.85rem;">
                        Cancel
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

require_once __DIR__ . '/../../includes/footer.php';
