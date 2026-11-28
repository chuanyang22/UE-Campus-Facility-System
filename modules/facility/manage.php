<?php
/**
 * Admin Add, Update & Decommission Facility
 * AMCS2194 Mini Project — Module 2 (FR-2.1, FR-2.3, FR-2.4)
 * Student 1: Tee Chuan Yang
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FacilityService.php';

if (!AuthService::isAdmin()) {
    AuthService::setFlash('danger', 'Administrator access is required to manage campus facilities.');
    header('Location: index.php');
    exit;
}

$facilityService = new FacilityService();
$mode            = ($_GET['mode'] ?? '') === 'create' ? 'create' : 'edit';
$facilityId      = trim((string)($_GET['id'] ?? ''));

$facility = null;
if ($mode === 'edit') {
    $facility = $facilityService->getFacilityById($facilityId);
    if (!$facility) {
        AuthService::setFlash('danger', 'Selected facility was not found.');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = $mode === 'create'
    ? 'Add New Facility — UE Campus Facility Booking System'
    : "Manage Facility {$facility['FacilityID']} — UE Campus Facility Booking System";
$activeNav = 'facility_dir';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($mode === 'create') {
        $res = $facilityService->addFacility($_POST);
        if ($res['success']) {
            AuthService::setFlash('success', $res['message']);
            header('Location: index.php');
            exit;
        }
        $errors = $res['errors'];
    } elseif ($action === 'decommission') {
        $res = $facilityService->decommissionFacility($facilityId);
        AuthService::setFlash($res['success'] ? 'success' : 'danger', $res['message']);
        header('Location: ' . ($res['success'] ? 'index.php' : 'manage.php?id=' . urlencode($facilityId)));
        exit;
    } else {
        $res = $facilityService->updateFacility($facilityId, $_POST);
        if ($res['success']) {
            AuthService::setFlash('success', $res['message']);
            header('Location: index.php');
            exit;
        }
        $errors = $res['errors'];
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">
            <?= $mode === 'create' ? 'Add New Campus Facility' : 'Manage Facility: ' . htmlspecialchars($facility['FacilityID']) ?>
        </h1>
        <p class="page-subtitle">
            <?= $mode === 'create'
                ? 'Register a new academic room or sports court in the unified campus catalog.'
                : 'Update facility specifications, operating hours, or decommission an inactive venue.' ?>
        </p>
    </div>
    <div>
        <a href="index.php" class="btn btn-cancel">&larr; Back to Facility Directory</a>
    </div>
</div>

<?php if ($mode === 'create'): ?>
    <div class="ui-card" style="max-width: 760px;">
        <?php if (!empty($errors)): ?>
            <div class="alert-banner-red" style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; border-radius: 8px;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 0.35rem 0 0 1.2rem; font-size: 0.86rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="FacilityName">Facility Name *</label>
                    <input type="text" id="FacilityName" name="FacilityName"
                           class="form-control <?= isset($errors['FacilityName']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. Library Discussion Room 3"
                           value="<?= htmlspecialchars($_POST['FacilityName'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="Category">Category *</label>
                    <select id="Category" name="Category" class="form-select">
                        <option value="Academic" <?= ($_POST['Category'] ?? 'Academic') === 'Academic' ? 'selected' : '' ?>>Academic</option>
                        <option value="Sports" <?= ($_POST['Category'] ?? '') === 'Sports' ? 'selected' : '' ?>>Sports</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="LocationBlock">Location Block *</label>
                    <input type="text" id="LocationBlock" name="LocationBlock"
                           class="form-control <?= isset($errors['LocationBlock']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. Library Block A, Level 2"
                           value="<?= htmlspecialchars($_POST['LocationBlock'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="Capacity">Max Capacity (pax) *</label>
                    <input type="number" id="Capacity" name="Capacity" min="1" max="500"
                           class="form-control <?= isset($errors['Capacity']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars((string)($_POST['Capacity'] ?? '6')) ?>" required>
                </div>
            </div>

            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="OperatingHours">Operating Hours *</label>
                    <input type="text" id="OperatingHours" name="OperatingHours"
                           class="form-control <?= isset($errors['OperatingHours']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. 08:00 - 22:00"
                           value="<?= htmlspecialchars($_POST['OperatingHours'] ?? '08:00 - 22:00') ?>" required>
                </div>

                <div class="form-group">
                    <label for="HasAircond">Air-Conditioning *</label>
                    <select id="HasAircond" name="HasAircond" class="form-select">
                        <option value="1" <?= ($_POST['HasAircond'] ?? '1') === '1' ? 'selected' : '' ?>>Yes (Air-Conditioned)</option>
                        <option value="0" <?= ($_POST['HasAircond'] ?? '') === '0' ? 'selected' : '' ?>>No (Non Air-Cond)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <a href="index.php" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-primary-blue">Add Facility</button>
            </div>
        </form>
    </div>
<?php else: ?>
    <?php $activeBookings = $facilityService->getActiveBookingCount($facility['FacilityID']); ?>
    <div class="form-grid-2col" style="align-items: start;">
        <!-- Left Column: Edit Facility Form -->
        <div class="ui-card" style="margin-bottom: 0;">
            <?php if (!empty($errors)): ?>
                <div class="alert-banner-red" style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; border-radius: 8px;">
                    <strong>Validation Error:</strong>
                    <ul style="margin: 0.35rem 0 0 1.2rem; font-size: 0.86rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="action" value="save">

                <div class="form-grid-2col">
                    <div class="form-group">
                        <label for="FacilityName">Facility Name *</label>
                        <input type="text" id="FacilityName" name="FacilityName"
                               class="form-control <?= isset($errors['FacilityName']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['FacilityName'] ?? $facility['FacilityName']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="Category">Category *</label>
                        <select id="Category" name="Category" class="form-select">
                            <option value="Academic" <?= ($_POST['Category'] ?? $facility['Category']) === 'Academic' ? 'selected' : '' ?>>Academic</option>
                            <option value="Sports" <?= ($_POST['Category'] ?? $facility['Category']) === 'Sports' ? 'selected' : '' ?>>Sports</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2col">
                    <div class="form-group">
                        <label for="LocationBlock">Location Block *</label>
                        <input type="text" id="LocationBlock" name="LocationBlock"
                               class="form-control <?= isset($errors['LocationBlock']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['LocationBlock'] ?? $facility['LocationBlock']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="Capacity">Max Capacity (pax) *</label>
                        <input type="number" id="Capacity" name="Capacity" min="1" max="500"
                               class="form-control <?= isset($errors['Capacity']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars((string)($_POST['Capacity'] ?? $facility['Capacity'])) ?>" required>
                    </div>
                </div>

                <div class="form-grid-2col">
                    <div class="form-group">
                        <label for="OperatingHours">Operating Hours *</label>
                        <input type="text" id="OperatingHours" name="OperatingHours"
                               class="form-control <?= isset($errors['OperatingHours']) ? 'is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['OperatingHours'] ?? $facility['OperatingHours']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="HasAircond">Air-Conditioning *</label>
                        <?php $ac = (string)($_POST['HasAircond'] ?? $facility['HasAircond']); ?>
                        <select id="HasAircond" name="HasAircond" class="form-select">
                            <option value="1" <?= $ac === '1' ? 'selected' : '' ?>>Yes (Air-Conditioned)</option>
                            <option value="0" <?= $ac === '0' ? 'selected' : '' ?>>No (Non Air-Cond)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="AvailabilityStatus">Availability Status *</label>
                    <?php $st = $_POST['AvailabilityStatus'] ?? $facility['AvailabilityStatus']; ?>
                    <select id="AvailabilityStatus" name="AvailabilityStatus" class="form-select">
                        <option value="Available" <?= $st === 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Under Maintenance" <?= $st === 'Under Maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                        <option value="Closed" <?= $st === 'Closed' ? 'selected' : '' ?>>Closed / Archived</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                    <a href="index.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-primary-blue">Save Facility Changes</button>
                </div>
            </form>
        </div>

        <!-- Right Column: Metadata & Danger Zone (FR-2.4 Decommission) -->
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="ui-card" style="margin-bottom: 0;">
                <h2 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin-bottom: 0.85rem;">
                    Facility Metadata Summary
                </h2>
                <div style="display: flex; flex-direction: column; gap: 0.55rem; font-size: 0.9rem;">
                    <div><span style="color: var(--text-muted);">Facility ID:</span> <strong><?= htmlspecialchars($facility['FacilityID']) ?></strong></div>
                    <div><span style="color: var(--text-muted);">Current Status:</span> <strong><?= htmlspecialchars($facility['AvailabilityStatus']) ?></strong></div>
                    <div><span style="color: var(--text-muted);">Upcoming Active Bookings:</span> <strong><?= $activeBookings ?></strong></div>
                </div>
            </div>

            <div class="ui-card" style="margin-bottom: 0; border-color: #fecaca; background-color: #fef2f2;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #991b1b; margin-bottom: 0.4rem;">
                    Decommission / Archive Facility (FR-2.4)
                </h3>
                <p style="font-size: 0.86rem; color: #7f1d1d; margin-bottom: 1rem;">
                    Archiving sets the facility status to <strong>Closed</strong>. If there are active upcoming bookings, archival is blocked automatically.
                </p>

                <?php if ($facility['AvailabilityStatus'] !== 'Closed'): ?>
                    <form method="POST" onsubmit="return confirm('Decommission <?= htmlspecialchars($facility['FacilityName']) ?>?');">
                        <input type="hidden" name="action" value="decommission">
                        <button type="submit" class="btn btn-danger-red">
                            Decommission <?= htmlspecialchars($facility['FacilityID']) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <span class="pill-badge pill-critical">Already Closed / Archived</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
