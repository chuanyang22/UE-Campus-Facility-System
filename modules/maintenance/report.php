<?php
/**
 * Module 5: Report Facility Defect (Create) & Feature 5.5 Auto-Locking
 * AMCS2194 Mini Project — Process 5.1 & 5.5
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/MaintenanceService.php';

$pageTitle = 'Report Facility Defect — Module 5';
$activeNav = 'report_defect';

$service    = new MaintenanceService();
$facilities = $service->getAllFacilities();
$currentUser = AuthService::getCurrentUser();

$errors = [];
$formData = [
    'FacilityID'       => $_GET['facility_id'] ?? '',
    'ReportedByUserID' => $currentUser['UserID'],
    'IssueTitle'       => '',
    'IssueDescription' => '',
    'UrgencyLevel'     => 'Medium',
    'ScheduledDate'    => date('Y-m-d'),
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'FacilityID'       => $_POST['FacilityID'] ?? '',
        'ReportedByUserID' => $_POST['ReportedByUserID'] ?? '',
        'IssueTitle'       => $_POST['IssueTitle'] ?? '',
        'IssueDescription' => $_POST['IssueDescription'] ?? '',
        'UrgencyLevel'     => $_POST['UrgencyLevel'] ?? '',
        'ScheduledDate'    => $_POST['ScheduledDate'] ?? '',
    ];

    $result = $service->reportDefect($formData);

    if ($result['success']) {
        AuthService::setFlash('success', $result['message']);
        header('Location: index.php');
        exit;
    } else {
        $errors = $result['errors'];
        AuthService::setFlash('danger', $result['message']);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 850px; margin: 0 auto;">
    <div class="card-header">
        <h2><span>&#128221;</span> Log Facility Defect Ticket (Process 5.1)</h2>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Ticket Log</a>
    </div>

    <div class="card-body">
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
            Report damaged equipment, safety hazards, or facility defects. The system enforces strict validation rules (presence, bounds, format, and data integrity).
        </p>

        <!-- Real-time Feature 5.5 Dynamic Warning -->
        <div id="hazardWarningBox" class="alert alert-danger" style="display: none; align-items: flex-start; gap: 0.75rem;">
            <span style="font-size: 1.5rem;">&#9888;</span>
            <div>
                <strong>CRITICAL HAZARD DETECTED (Unique Feature 5.5):</strong>
                <p style="margin: 0.25rem 0 0 0;">Submitting this defect report with <em>Critical</em> urgency will automatically engage safety protocol: the venue status will immediately switch to <strong>'Under Maintenance'</strong>, preventing student booking conflicts until repairs are concluded.</p>
            </div>
        </div>

        <form method="POST" action="report.php" novalidate>
            <div class="form-grid">
                <!-- Facility Selection -->
                <div class="form-group">
                    <label for="FacilityID">Target Facility <span class="required-star">*</span></label>
                    <select id="FacilityID" name="FacilityID" class="form-select <?= isset($errors['FacilityID']) ? 'is-invalid' : '' ?>" required>
                        <option value="">-- Choose Venue / Facility --</option>
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?= htmlspecialchars($f['FacilityID']) ?>" <?= $formData['FacilityID'] === $f['FacilityID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['FacilityName']) ?> (<?= htmlspecialchars($f['LocationBlock']) ?>) — [<?= htmlspecialchars($f['AvailabilityStatus']) ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['FacilityID'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['FacilityID']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">Must exist in FACILITY table (Existence Check)</span>
                    <?php endif; ?>
                </div>

                <!-- Reporter ID -->
                <div class="form-group">
                    <label for="ReportedByUserID">Reported By User ID <span class="required-star">*</span></label>
                    <input type="text" id="ReportedByUserID" name="ReportedByUserID" class="form-control <?= isset($errors['ReportedByUserID']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($formData['ReportedByUserID']) ?>" required>
                    <?php if (isset($errors['ReportedByUserID'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['ReportedByUserID']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">Defaulted to active session: <?= htmlspecialchars($currentUser['FullName']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Issue Title -->
                <div class="form-group full-width">
                    <label for="IssueTitle">Defect / Issue Title <span class="required-star">*</span></label>
                    <input type="text" id="IssueTitle" name="IssueTitle" class="form-control <?= isset($errors['IssueTitle']) ? 'is-invalid' : '' ?>" placeholder="e.g., Damaged Badminton Net / HDMI Cable Shorted" value="<?= htmlspecialchars($formData['IssueTitle']) ?>" required maxlength="100">
                    <?php if (isset($errors['IssueTitle'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['IssueTitle']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">5 to 100 characters (Size & Limit Check)</span>
                    <?php endif; ?>
                </div>

                <!-- Urgency Level -->
                <div class="form-group">
                    <label for="UrgencyLevel">Urgency Level <span class="required-star">*</span></label>
                    <select id="UrgencyLevel" name="UrgencyLevel" class="form-select <?= isset($errors['UrgencyLevel']) ? 'is-invalid' : '' ?>" required>
                        <option value="Low" <?= $formData['UrgencyLevel'] === 'Low' ? 'selected' : '' ?>>Low (Minor cosmetic issue)</option>
                        <option value="Medium" <?= $formData['UrgencyLevel'] === 'Medium' ? 'selected' : '' ?>>Medium (Partial degradation)</option>
                        <option value="High" <?= $formData['UrgencyLevel'] === 'High' ? 'selected' : '' ?>>High (Functionally unusable)</option>
                        <option value="Critical" <?= $formData['UrgencyLevel'] === 'Critical' ? 'selected' : '' ?>>Critical &bull; Safety Hazard (Triggers Venue Lock)</option>
                    </select>
                    <?php if (isset($errors['UrgencyLevel'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['UrgencyLevel']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">Select 'Critical' to trigger Unique Feature 5.5 auto-locking</span>
                    <?php endif; ?>
                </div>

                <!-- Scheduled Inspection Date -->
                <div class="form-group">
                    <label for="ScheduledDate">Scheduled Inspection Date <span class="required-star">*</span></label>
                    <input type="date" id="ScheduledDate" name="ScheduledDate" class="form-control <?= isset($errors['ScheduledDate']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($formData['ScheduledDate']) ?>" required>
                    <?php if (isset($errors['ScheduledDate'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['ScheduledDate']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">Format YYYY-MM-DD (Format & Null Check)</span>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="form-group full-width">
                    <label for="IssueDescription">Detailed Issue Description <span class="required-star">*</span></label>
                    <textarea id="IssueDescription" name="IssueDescription" rows="4" class="form-control <?= isset($errors['IssueDescription']) ? 'is-invalid' : '' ?>" placeholder="Provide specific defect details, exact physical location, and any immediate hazards..." required><?= htmlspecialchars($formData['IssueDescription']) ?></textarea>
                    <?php if (isset($errors['IssueDescription'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['IssueDescription']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">At least 10 characters required (Boundary & Limit Check)</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Defect Ticket</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
