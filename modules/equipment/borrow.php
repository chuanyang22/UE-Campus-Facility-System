<?php
/**
 * Module 4: Request Complimentary Equipment Loan (Create)
 * AMCS2194 Mini Project — Process 4.1
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/BookingService.php';
require_once __DIR__ . '/../../services/EquipmentService.php';

$pageTitle = 'Request Complimentary Equipment';
$activeNav = 'equipment';

$bookingService = new BookingService();
$eqService      = new EquipmentService();
$currentUser    = AuthService::getCurrentUser();
$isAdmin        = AuthService::isAdmin();

// Eligible bookings for this user (Confirmed or Checked-In)
$targetUserId = $isAdmin && isset($_GET['all_users']) ? '' : $currentUser['UserID'];
$activeBookings = $targetUserId !== '' 
    ? $bookingService->getUserBookings($targetUserId, true)
    : $bookingService->getFilteredBookings(['status' => 'Confirmed']);

$preselectedBookingId = trim((string)($_GET['booking_id'] ?? ''));
$preselectedCategory  = trim((string)($_GET['category'] ?? ''));

// Detect facility category if booking is preselected
$selectedVenueCategory = null;
if ($preselectedBookingId !== '') {
    foreach ($activeBookings as $ab) {
        if ($ab['BookingID'] === $preselectedBookingId) {
            $selectedVenueCategory = $ab['FacilityCategory'] ?? null;
            break;
        }
    }
}

$catalog = $eqService->getCatalog($selectedVenueCategory);

$formData = [
    'BookingID'   => $preselectedBookingId,
    'ItemName'    => '',
    'Category'    => $preselectedCategory,
    'BorrowedQty' => 1,
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['BookingID']   = trim((string)($_POST['BookingID'] ?? ''));
    $formData['ItemName']    = trim((string)($_POST['ItemName'] ?? ''));
    $formData['Category']    = trim((string)($_POST['Category'] ?? ''));
    $formData['BorrowedQty'] = (int)($_POST['BorrowedQty'] ?? 1);

    $res = $eqService->requestEquipmentLoan($formData, $currentUser['UserID'], $isAdmin);

    if ($res['success']) {
        AuthService::setFlash('success', $res['message']);
        header('Location: ../booking/receipt.php?id=' . urlencode($formData['BookingID']));
        exit;
    } else {
        $errors = $res['errors'];
        AuthService::setFlash('danger', $res['message']);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header -->
<div class="page-header-row">
    <div>
        <h1 class="page-title">Request Complimentary Equipment</h1>
        <p class="page-subtitle">Borrow sports gear, AV adapters, and presentation stationery for your confirmed facility booking.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-cancel" style="padding: 0.55rem 1rem;">
            ← Back to Equipment Log
        </a>
    </div>
</div>

<div class="ui-card" style="max-width: 760px; margin: 0 auto;">
    
    <?php if (empty($activeBookings)): ?>
        <div style="text-align: center; padding: 2.5rem 1.5rem; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📅</div>
            <h3 style="margin: 0 0 0.5rem 0; color: var(--text-color);">No Active Facility Bookings Found</h3>
            <p style="font-size: 0.95rem; margin-bottom: 1.25rem;">
                Complimentary equipment must be tied to a confirmed facility reservation (e.g. Badminton Court or Discussion Room).
            </p>
            <a href="../booking/create.php" class="btn btn-primary-blue" style="font-weight: 600;">
                + Book a Campus Facility First
            </a>
        </div>
    <?php else: ?>
        <form method="POST" action="borrow.php">
            
            <!-- Booking Selection -->
            <div class="form-group" style="margin-bottom: 1.35rem;">
                <label class="form-label" for="BookingID" style="font-weight: 700;">
                    Select Confirmed Facility Booking <span style="color: #dc2626;">*</span>
                </label>
                <select id="BookingID" name="BookingID" class="form-select <?= isset($errors['BookingID']) ? 'is-invalid' : '' ?>" onchange="onBookingChange()" required>
                    <option value="">-- Choose your active reservation --</option>
                    <?php foreach ($activeBookings as $b): ?>
                        <option value="<?= htmlspecialchars($b['BookingID']) ?>" 
                                data-category="<?= htmlspecialchars($b['FacilityCategory']) ?>"
                                data-venue="<?= htmlspecialchars($b['FacilityName']) ?>"
                                <?= $formData['BookingID'] === $b['BookingID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['BookingID']) ?> — <?= htmlspecialchars($b['FacilityName']) ?> (<?= htmlspecialchars(date('D, d M', strtotime($b['BookingDate']))) ?> <?= htmlspecialchars(substr($b['StartTime'], 0, 5) . '–' . substr($b['EndTime'], 0, 5)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['BookingID'])): ?>
                    <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                        <?= htmlspecialchars($errors['BookingID']) ?>
                    </div>
                <?php endif; ?>
                <div id="bookingHelp" style="font-size: 0.82rem; color: #2563eb; margin-top: 0.35rem;"></div>
            </div>

            <!-- Equipment Item Selection -->
            <div class="form-group" style="margin-bottom: 1.35rem;">
                <label class="form-label" for="ItemName" style="font-weight: 700;">
                    Complimentary Equipment Item <span style="color: #dc2626;">*</span>
                </label>
                <select id="ItemName" name="ItemName" class="form-select <?= isset($errors['ItemName']) ? 'is-invalid' : '' ?>" onchange="onItemChange()" required>
                    <option value="">-- Choose item to borrow --</option>
                    <?php foreach ($catalog as $catName => $items): ?>
                        <optgroup label="<?= htmlspecialchars($catName) ?>">
                            <?php foreach ($items as $name => $item): ?>
                                <option value="<?= htmlspecialchars($name) ?>" 
                                        data-category="<?= htmlspecialchars($catName) ?>"
                                        data-stock="<?= (int)$item['available_stock'] ?>"
                                        data-unit="<?= htmlspecialchars($item['unit']) ?>"
                                        <?= $formData['ItemName'] === $name ? 'selected' : '' ?>
                                        <?= $item['available_stock'] <= 0 ? 'disabled' : '' ?>>
                                    <?= htmlspecialchars($name) ?> (Available: <?= (int)$item['available_stock'] ?> <?= htmlspecialchars($item['unit']) ?>) <?= $item['available_stock'] <= 0 ? '[OUT OF STOCK]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" id="Category" name="Category" value="<?= htmlspecialchars($formData['Category']) ?>">
                <?php if (isset($errors['ItemName'])): ?>
                    <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                        <?= htmlspecialchars($errors['ItemName']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quantity Selection -->
            <div class="form-group" style="margin-bottom: 1.5rem; max-width: 260px;">
                <label class="form-label" for="BorrowedQty" style="font-weight: 700;">
                    Quantity to Borrow <span style="color: #dc2626;">*</span>
                </label>
                <input type="number" id="BorrowedQty" name="BorrowedQty" 
                       class="form-control <?= isset($errors['BorrowedQty']) ? 'is-invalid' : '' ?>"
                       min="1" max="10" 
                       value="<?= (int)$formData['BorrowedQty'] ?>" required>
                <div id="stockHint" style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Max 10 units per booking.
                </div>
                <?php if (isset($errors['BorrowedQty'])): ?>
                    <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                        <?= htmlspecialchars($errors['BorrowedQty']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Loan Policy Banner -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.9rem 1.15rem; margin-bottom: 1.75rem; font-size: 0.85rem; color: #475569;">
                <strong>Loan Terms:</strong> Complimentary equipment is issued for the duration of your facility session only and must be returned to the campus sports / library counter upon session end in good condition.
            </div>

            <!-- Submit Actions -->
            <div style="display: flex; gap: 1rem; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
                <button type="submit" class="btn btn-primary-blue" style="padding: 0.65rem 1.5rem; font-weight: 700;">
                    Confirm & Reserve Equipment
                </button>
                <a href="index.php" class="btn btn-cancel" style="padding: 0.65rem 1.2rem;">
                    Cancel
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function onBookingChange() {
    const sel = document.getElementById('BookingID');
    const opt = sel.options[sel.selectedIndex];
    const help = document.getElementById('bookingHelp');
    if (!opt || !opt.value) {
        if (help) help.textContent = '';
        return;
    }
    const cat = opt.getAttribute('data-category') || '';
    const venue = opt.getAttribute('data-venue') || '';
    if (help) {
        help.textContent = `Selected: ${venue} (${cat}). Gear catalog below is matched for this venue.`;
    }
}

function onItemChange() {
    const sel = document.getElementById('ItemName');
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    const cat = opt.getAttribute('data-category') || '';
    const stock = opt.getAttribute('data-stock') || '10';
    const unit = opt.getAttribute('data-unit') || 'units';

    document.getElementById('Category').value = cat;

    const stockHint = document.getElementById('stockHint');
    if (stockHint) {
        stockHint.innerHTML = `Stock available: <strong>${stock} ${unit}</strong>.`;
    }

    const qtyInput = document.getElementById('BorrowedQty');
    if (qtyInput) {
        qtyInput.max = Math.min(10, parseInt(stock, 10));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    onBookingChange();
    onItemChange();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
