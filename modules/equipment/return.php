<?php
/**
 * Module 4: Equipment Return & Condition Inspection Panel (Update)
 * AMCS2194 Mini Project — Process 4.3
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/EquipmentService.php';

$pageTitle = 'Equipment Return & Condition Inspection';
$activeNav = 'equipment';

if (!AuthService::isMaintenanceStaff()) {
    AuthService::setFlash('danger', 'Unauthorized: Only Facility Staff or Administrators can inspect equipment returns.');
    header('Location: index.php');
    exit;
}

$eqService   = new EquipmentService();
$equipmentId = trim((string)($_GET['id'] ?? $_POST['EquipmentID'] ?? ''));
$loan        = $eqService->getLoanById($equipmentId);

if (!$loan) {
    AuthService::setFlash('danger', "Equipment record '{$equipmentId}' not found.");
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status    = trim((string)($_POST['BorrowStatus'] ?? 'Returned'));
    $condition = trim((string)($_POST['ConditionOnReturn'] ?? 'Good'));

    $res = $eqService->inspectAndReturnEquipment($equipmentId, $status, $condition, AuthService::isAdmin());

    if ($res['success']) {
        AuthService::setFlash('success', $res['message']);
        header('Location: index.php');
        exit;
    } else {
        AuthService::setFlash('danger', $res['message']);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header -->
<div class="page-header-row">
    <div>
        <h1 class="page-title">Equipment Return & Condition Inspection</h1>
        <p class="page-subtitle">Inspect returned campus gear, verify physical condition, and update usable stock levels.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-cancel" style="padding: 0.55rem 1rem;">
            ← Back to Equipment Log
        </a>
    </div>
</div>

<div class="ui-card" style="max-width: 720px; margin: 0 auto;">
    
    <!-- Loan Info Summary Card -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <span style="font-size: 0.8rem; font-weight: 700; color: #2563eb; text-transform: uppercase;">LOAN RECORD</span>
                <h3 style="margin: 0; font-size: 1.3rem; color: #1e293b;">
                    <?= htmlspecialchars($loan['ItemName']) ?>
                </h3>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">
                    Ref: <strong><?= htmlspecialchars($loan['EquipmentID']) ?></strong> • Category: <?= htmlspecialchars($loan['Category']) ?> • Qty: <strong><?= (int)$loan['BorrowedQty'] ?></strong>
                </div>
            </div>
            <div>
                <span class="badge <?= $loan['BorrowStatus'] === 'Borrowed' ? 'badge-confirmed' : 'badge-completed' ?>">
                    Current: <?= htmlspecialchars($loan['BorrowStatus']) ?>
                </span>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0.85rem 0;">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem 1.25rem; font-size: 0.88rem;">
            <div>
                <span style="color: var(--text-muted);">Borrower:</span>
                <strong style="color: var(--text-color); margin-left: 0.25rem;">
                    <?= htmlspecialchars($loan['BorrowerName']) ?> (<?= htmlspecialchars($loan['UserID']) ?>)
                </strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Linked Booking:</span>
                <strong style="color: var(--text-color); margin-left: 0.25rem;">
                    <?= htmlspecialchars($loan['BookingID']) ?>
                </strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Facility:</span>
                <strong style="color: var(--text-color); margin-left: 0.25rem;">
                    <?= htmlspecialchars($loan['FacilityName']) ?>
                </strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Session Time:</span>
                <strong style="color: var(--text-color); margin-left: 0.25rem;">
                    <?= htmlspecialchars(date('d M Y', strtotime($loan['BookingDate']))) ?> (<?= htmlspecialchars(substr($loan['StartTime'], 0, 5) . '–' . substr($loan['EndTime'], 0, 5)) ?>)
                </strong>
            </div>
        </div>
    </div>

    <!-- Return Form -->
    <form method="POST" action="return.php">
        <input type="hidden" name="EquipmentID" value="<?= htmlspecialchars($loan['EquipmentID']) ?>">

        <div class="form-group" style="margin-bottom: 1.35rem;">
            <label class="form-label" for="BorrowStatus" style="font-weight: 700;">
                Return Status <span style="color: #dc2626;">*</span>
            </label>
            <select id="BorrowStatus" name="BorrowStatus" class="form-select" required>
                <option value="Returned" <?= $loan['BorrowStatus'] === 'Returned' ? 'selected' : '' ?>>
                    Returned (Good order / Re-stock into inventory)
                </option>
                <option value="Damaged" <?= $loan['BorrowStatus'] === 'Damaged' ? 'selected' : '' ?>>
                    Damaged (Needs repair / Prompt defect ticket)
                </option>
                <option value="Lost" <?= $loan['BorrowStatus'] === 'Lost' ? 'selected' : '' ?>>
                    Lost (Not returned by student / Write-off)
                </option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" for="ConditionOnReturn" style="font-weight: 700;">
                Condition Inspection Notes <span style="color: #dc2626;">*</span>
            </label>
            <textarea id="ConditionOnReturn" name="ConditionOnReturn" class="form-control" rows="3" 
                      placeholder="Detail physical inspection: check frame, cables, pins, or missing accessories..." required><?= htmlspecialchars($loan['ConditionOnReturn'] ?? 'Good condition upon return.') ?></textarea>
            
            <div style="margin-top: 0.5rem; display: flex; gap: 0.4rem; flex-wrap: wrap; font-size: 0.8rem;">
                <span style="color: var(--text-muted);">Quick inspection presets:</span>
                <a href="javascript:void(0)" onclick="setNote('Good condition - inspected and returned to counter shelf.')" style="color: #2563eb;">Good / Clean</a> •
                <a href="javascript:void(0)" onclick="setNote('Minor scuffs - fully operational.')" style="color: #2563eb;">Minor Wear</a> •
                <a href="javascript:void(0)" onclick="setNote('Broken / Cracked frame during match.')" style="color: #2563eb;">Damaged Frame</a> •
                <a href="javascript:void(0)" onclick="setNote('Cable connection intermittent / flickering HDMI signal.')" style="color: #2563eb;">Faulty Cable</a> •
                <a href="javascript:void(0)" onclick="setNote('Item not returned upon venue checkout.')" style="color: #2563eb;">Item Lost</a>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem;">
            <button type="submit" class="btn btn-primary-blue" style="padding: 0.65rem 1.5rem; font-weight: 700;">
                Record Inspection & Update Inventory
            </button>
            <a href="index.php" class="btn btn-cancel" style="padding: 0.65rem 1.2rem;">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function setNote(text) {
    document.getElementById('ConditionOnReturn').value = text;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
