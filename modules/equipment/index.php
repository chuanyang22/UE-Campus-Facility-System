<?php
/**
 * Module 4: Complimentary Equipment Inventory & Loans Dashboard (Retrieve)
 * AMCS2194 Mini Project — Process 4.2
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/EquipmentService.php';

$pageTitle = 'Complimentary Equipment Borrowing Management';
$activeNav = 'equipment';

$eqService   = new EquipmentService();
$currentUser = AuthService::getCurrentUser();
$isAdmin     = AuthService::isAdmin();
$isStaff     = AuthService::isMaintenanceStaff();

// Filter parameters
$filters = [
    'search'     => trim((string)($_GET['search'] ?? '')),
    'category'   => trim((string)($_GET['category'] ?? 'ALL')),
    'status'     => trim((string)($_GET['status'] ?? 'ALL')),
    'booking_id' => trim((string)($_GET['booking_id'] ?? '')),
];

// Role-based visibility
$viewAll = $isStaff && (isset($_GET['view_all']) ? $_GET['view_all'] === '1' : true);
$targetUser = $viewAll ? null : $currentUser['UserID'];

$loans   = $eqService->getAllLoans($filters, $targetUser);
$stats   = $eqService->getStatistics($targetUser);
$catalog = $eqService->getCatalog();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header with Action Buttons -->
<div class="page-header-row">
    <div>
        <h1 class="page-title">Complimentary Equipment Loans & Inventory</h1>
        <p class="page-subtitle">Request, monitor and inspect complimentary sports gear and AV equipment reserved for campus facilities.</p>
    </div>
    <div style="display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap;">
        <a href="borrow.php" class="btn btn-primary-blue" style="font-weight: 600;">
            + Request Equipment
        </a>
        <a href="../booking/index.php" class="btn btn-outline-blue" style="font-weight: 500;">
            📅 Facility Bookings
        </a>
        <a href="../../index.php" class="btn btn-cancel" style="font-weight: 500;">
            ← Home
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-label">Total Loan Records</div>
        <div class="stat-number stat-blue"><?= $stats['total'] ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
            <?= $viewAll ? 'All campus facility loans' : 'Your personal loans' ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Currently Borrowed</div>
        <div class="stat-number stat-yellow"><?= $stats['active'] ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">In custody of users</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Returned in Good Order</div>
        <div class="stat-number stat-completed"><?= $stats['returned'] ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Checked back into inventory</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Damaged / Lost Items</div>
        <div class="stat-number stat-critical"><?= $stats['damaged'] ?></div>
        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Requires repair or retirement</div>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-card" style="margin-bottom: 1.75rem;">
    <form method="GET" action="index.php" class="filter-form">
        <?php if ($isStaff): ?>
            <input type="hidden" name="view_all" value="<?= $viewAll ? '1' : '0' ?>">
        <?php endif; ?>

        <div class="filter-group" style="flex: 2; min-width: 220px;">
            <label class="form-label" for="filterSearch">Search Equipment</label>
            <input type="text" id="filterSearch" name="search" class="form-control" 
                   placeholder="Item name, loan ref (EQ-...), booking ID, borrower..." 
                   value="<?= htmlspecialchars($filters['search']) ?>">
        </div>

        <div class="filter-group" style="flex: 1.2; min-width: 150px;">
            <label class="form-label" for="filterCategory">Category</label>
            <select id="filterCategory" name="category" class="form-select">
                <option value="ALL" <?= $filters['category'] === 'ALL' ? 'selected' : '' ?>>All Categories</option>
                <option value="Sports Gear" <?= $filters['category'] === 'Sports Gear' ? 'selected' : '' ?>>Sports Gear</option>
                <option value="AV Accessories" <?= $filters['category'] === 'AV Accessories' ? 'selected' : '' ?>>AV Accessories</option>
                <option value="Stationery" <?= $filters['category'] === 'Stationery' ? 'selected' : '' ?>>Stationery</option>
            </select>
        </div>

        <div class="filter-group" style="flex: 1.2; min-width: 140px;">
            <label class="form-label" for="filterStatus">Loan Status</label>
            <select id="filterStatus" name="status" class="form-select">
                <option value="ALL" <?= $filters['status'] === 'ALL' ? 'selected' : '' ?>>All Statuses</option>
                <option value="Borrowed" <?= $filters['status'] === 'Borrowed' ? 'selected' : '' ?>>Borrowed (Active)</option>
                <option value="Returned" <?= $filters['status'] === 'Returned' ? 'selected' : '' ?>>Returned</option>
                <option value="Damaged" <?= $filters['status'] === 'Damaged' ? 'selected' : '' ?>>Damaged</option>
                <option value="Lost" <?= $filters['status'] === 'Lost' ? 'selected' : '' ?>>Lost</option>
            </select>
        </div>

        <div class="filter-actions" style="display: flex; gap: 0.5rem; align-items: flex-end;">
            <button type="submit" class="btn btn-primary-blue" style="padding: 0.55rem 1rem;">Filter</button>
            <a href="index.php" class="btn btn-cancel" style="padding: 0.55rem 0.9rem;">Reset</a>
        </div>
    </form>
</div>

<!-- Loan Records Table -->
<div class="ui-card" style="padding: 0; overflow: hidden; margin-bottom: 2rem;">
    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                Equipment Custody & Loan Log (<?= count($loans) ?> records)
            </h3>
            <p style="margin: 0.2rem 0 0 0; font-size: 0.85rem; color: var(--text-muted);">
                Showing <?= $viewAll ? 'all active and past equipment loans campus-wide' : 'your personal borrowed items' ?>.
            </p>
        </div>

        <?php if ($isStaff): ?>
            <div>
                <?php if ($viewAll): ?>
                    <a href="index.php?view_all=0" class="btn btn-outline-blue" style="font-size: 0.82rem; padding: 0.35rem 0.75rem;">
                        👤 View Only My Loans
                    </a>
                <?php else: ?>
                    <a href="index.php?view_all=1" class="btn btn-outline-blue" style="font-size: 0.82rem; padding: 0.35rem 0.75rem; background: #eff6ff;">
                        🌐 View All Campus Loans
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($loans)): ?>
        <div style="padding: 3rem 1.5rem; text-align: center; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📦</div>
            <p style="font-size: 1.05rem; margin-bottom: 1rem;">No equipment loan records match your current filters.</p>
            <a href="borrow.php" class="btn btn-primary-blue">Request Complimentary Equipment</a>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 100px;">Loan Ref</th>
                        <th>Equipment Item</th>
                        <th>Category</th>
                        <th style="text-align: center; width: 60px;">Qty</th>
                        <th>Linked Booking & Venue</th>
                        <th>Borrower</th>
                        <th>Status</th>
                        <th>Return Condition</th>
                        <th style="text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <?php
                        $statusClass = 'badge-confirmed';
                        if ($loan['BorrowStatus'] === 'Returned') $statusClass = 'badge-completed';
                        elseif ($loan['BorrowStatus'] === 'Damaged') $statusClass = 'badge-high';
                        elseif ($loan['BorrowStatus'] === 'Lost') $statusClass = 'badge-critical';
                        ?>
                        <tr>
                            <td>
                                <strong style="color: #2563eb;"><?= htmlspecialchars($loan['EquipmentID']) ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-color);">
                                    <?= htmlspecialchars($loan['ItemName']) ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem; background: #f1f5f9; color: #475569; padding: 2px 7px; border-radius: 4px; font-weight: 600;">
                                    <?= htmlspecialchars($loan['Category']) ?>
                                </span>
                            </td>
                            <td style="text-align: center; font-weight: 700;">
                                <?= (int)$loan['BorrowedQty'] ?>
                            </td>
                            <td>
                                <div>
                                    <a href="../booking/receipt.php?id=<?= urlencode($loan['BookingID']) ?>" style="font-weight: 700; color: #2563eb;">
                                        <?= htmlspecialchars($loan['BookingID']) ?>
                                    </a>
                                </div>
                                <div style="font-size: 0.82rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($loan['FacilityName']) ?> (<?= htmlspecialchars(date('d M', strtotime($loan['BookingDate']))) ?>)
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($loan['BorrowerName']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($loan['UserID']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($loan['BorrowStatus']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: var(--text-color);">
                                    <?= htmlspecialchars($loan['ConditionOnReturn'] ?? '—') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($isStaff && $loan['BorrowStatus'] === 'Borrowed'): ?>
                                    <a href="return.php?id=<?= urlencode($loan['EquipmentID']) ?>" class="btn btn-outline-blue" style="font-size: 0.78rem; padding: 0.35rem 0.65rem;">
                                        🔍 Inspect Return
                                    </a>
                                <?php elseif ($isAdmin && in_array($loan['BorrowStatus'], ['Returned', 'Damaged', 'Lost'], true)): ?>
                                    <a href="retire.php?id=<?= urlencode($loan['EquipmentID']) ?>" 
                                       onclick="return confirm('Retire equipment record <?= htmlspecialchars($loan['EquipmentID']) ?> from the database?');"
                                       class="btn btn-danger-red" style="font-size: 0.78rem; padding: 0.35rem 0.65rem;">
                                        Retire
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.82rem; color: var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Master Equipment Catalog Stock Overview -->
<div class="section-title-wrap" style="margin-bottom: 0.85rem;">
    <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-color);">
        📦 Available Campus Equipment Catalog & Real-Time Stock
    </h2>
    <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">
        Complimentary items available across sports complexes and academic study blocks.
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">
    <?php foreach ($catalog as $groupName => $items): ?>
        <div class="ui-card" style="border-top: 4px solid <?= $groupName === 'Sports Gear' ? '#16a34a' : ($groupName === 'AV Accessories' ? '#2563eb' : '#f59e0b') ?>;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-color);">
                    <?= htmlspecialchars($groupName) ?>
                </h3>
                <span style="font-size: 0.78rem; font-weight: 600; padding: 2px 8px; border-radius: 9999px; background: #f1f5f9; color: #475569;">
                    <?= count($items) ?> items
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <?php foreach ($items as $item): ?>
                    <div style="padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                                <?= htmlspecialchars($item['name']) ?>
                            </div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                                <?= htmlspecialchars($item['desc']) ?>
                            </div>
                        </div>
                        <div style="text-align: right; white-space: nowrap;">
                            <div style="font-size: 1.05rem; font-weight: 800; color: <?= $item['available_stock'] > 0 ? '#16a34a' : '#dc2626' ?>;">
                                <?= (int)$item['available_stock'] ?> / <?= (int)$item['stock'] ?>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?= htmlspecialchars($item['unit']) ?> free
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 1rem; text-align: center;">
                <a href="borrow.php?category=<?= urlencode($groupName) ?>" class="btn btn-outline-blue" style="font-size: 0.85rem; padding: 0.4rem 0.9rem; width: 100%;">
                    Borrow from this Category &rarr;
                </a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
