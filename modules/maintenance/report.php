<?php
/**
 * Report Facility Defect / Damage
 * AMCS2194 Mini Project — Process 5.1 (Create) & Process 5.5 (Automated Hazard Alert)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/MaintenanceService.php';

$pageTitle = 'Report Facility Defect / Damage';
$activeNav = AuthService::isMaintenanceStaff() ? 'maintenance' : 'report_defect';

$service    = new MaintenanceService();
$facilities = $service->getAllFacilities();
$currentUser = AuthService::getCurrentUser();

// Helper to categorize facility as Academic or Sports based on venue nature
function getVenueCategoryType(array $f): string {
    $name = strtolower($f['FacilityName'] ?? '');
    if (str_contains($name, 'discussion') || str_contains($name, 'lecture') || str_contains($name, 'lab') || str_contains($name, 'hall') || str_contains($name, 'room') || str_contains($name, 'class')) {
        return 'academic';
    }
    if (str_contains($name, 'badminton') || str_contains($name, 'futsal') || str_contains($name, 'court') || str_contains($name, 'pitch') || str_contains($name, 'sports') || str_contains($name, 'pool') || str_contains($name, 'gym')) {
        return 'sports';
    }
    // Fallback to Category column
    $cat = strtolower($f['Category'] ?? '');
    return str_contains($cat, 'sport') ? 'sports' : 'academic';
}

$academicCategories = [
    'Electrical & Power Supply',
    'Lighting & Fixtures',
    'Air-Conditioning',
    'AV & Projector',
    'Furniture & Structural',
    'Computer Hardware / Network',
];

$sportsCategories = [
    'Sports Equipment / Nets',
    'Court Surface & Flooring',
    'Lighting & Fixtures',
    'Ventilation & Exhaust',
    'Electrical & Power Supply',
    'Structural / Goal Posts / Perimeter',
];

$errors = [];
$formData = [
    'FacilityID'       => $_GET['facility_id'] ?? '',
    'DefectCategory'   => $_GET['category'] ?? '',
    'ReportedByUserID' => $currentUser['UserID'],
    'IssueDescription' => '',
    'UrgencyLevel'     => 'Low',
    'ScheduledDate'    => date('Y-m-d'),
];

// If facility_id is passed with prefill flag, pre-fill sample defect for evaluator convenience
if (!empty($_GET['facility_id']) && $_GET['facility_id'] === 'FAC-04' && isset($_GET['prefill'])) {
    $formData['FacilityID'] = 'FAC-04';
    $formData['DefectCategory'] = 'Electrical & Power Supply';
    $formData['UrgencyLevel'] = 'Critical';
    $formData['IssueDescription'] = 'Exposed wiring and sparking wall socket near the projector table. Unsafe for student occupancy.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedUrgency = $_POST['UrgencyLevel'] ?? 'Low';
    if (!in_array($postedUrgency, ['Low', 'Medium', 'High', 'Critical'], true)) {
        $postedUrgency = 'Low';
    }

    // If submitting an empty defect form, ensure severity level defaults strictly to Low
    if (empty($_POST['FacilityID']) && empty($_POST['IssueDescription'])) {
        $postedUrgency = 'Low';
    }

    $formData = [
        'FacilityID'       => $_POST['FacilityID'] ?? '',
        'DefectCategory'   => $_POST['DefectCategory'] ?? '',
        'ReportedByUserID' => $currentUser['UserID'],
        'IssueDescription' => $_POST['IssueDescription'] ?? '',
        'UrgencyLevel'     => $postedUrgency,
        'ScheduledDate'    => date('Y-m-d'),
    ];

    if (empty($formData['DefectCategory'])) {
        $errors['DefectCategory'] = 'Defect category must be selected.';
    }

    $result = $service->reportDefect($formData);

    if ($result['success'] && empty($errors)) {
        AuthService::setFlash('success', $result['message']);
        header('Location: ' . (AuthService::isMaintenanceStaff() ? 'index.php' : '../../index.php'));
        exit;
    } else {
        $errors = array_merge($errors, $result['errors'] ?? []);
        // Note: Top flash alert omitted so only clean field-level validation messages are displayed
    }
}

// Identify currently selected venue type if any
$selectedVenueType = null;
if (!empty($formData['FacilityID'])) {
    foreach ($facilities as $f) {
        if ($f['FacilityID'] === $formData['FacilityID']) {
            $selectedVenueType = getVenueCategoryType($f);
            break;
        }
    }
}

// Compute initial background class based on UrgencyLevel (defaults to Low)
$initialBoxClass = match($formData['UrgencyLevel']) {
    'Critical' => 'urgency-box-critical',
    'High'     => 'urgency-box-high',
    'Medium'   => 'urgency-box-medium',
    default    => 'urgency-box-low',
};

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title"><?= htmlspecialchars(t('report_title')) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars(t('report_sub')) ?></p>
    </div>
</div>

<div class="ui-card">
    <form method="POST" action="report.php" id="defectReportForm" autocomplete="off" novalidate>
        
        <!-- Affected Facility (Select First) -->
        <div class="form-group">
            <label for="FacilityID">Affected Facility *</label>
            <select id="FacilityID" name="FacilityID" class="form-select <?= isset($errors['FacilityID']) ? 'is-invalid' : '' ?>" required>
                <option value="">-- Select Affected Venue --</option>
                <?php foreach ($facilities as $f): 
                    $vType = getVenueCategoryType($f);
                ?>
                    <option value="<?= htmlspecialchars($f['FacilityID']) ?>" 
                            data-type="<?= $vType ?>"
                            <?= $formData['FacilityID'] === $f['FacilityID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['FacilityID']) ?> — <?= htmlspecialchars($f['FacilityName']) ?> (<?= htmlspecialchars($f['LocationBlock']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['FacilityID'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['FacilityID']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Defect Category (Dependent on Venue Selection) -->
        <div class="form-group">
            <label for="DefectCategory">Defect Category *</label>
            <select id="DefectCategory" name="DefectCategory" 
                    class="form-select <?= isset($errors['DefectCategory']) ? 'is-invalid' : '' ?>" 
                    <?= empty($selectedVenueType) ? 'disabled' : '' ?>
                    required>
                <?php if (empty($selectedVenueType)): ?>
                    <option value="">-- Please Select Affected Venue First --</option>
                <?php else: ?>
                    <?php 
                    $categoryList = ($selectedVenueType === 'sports') ? $sportsCategories : $academicCategories;
                    $groupLabel = ($selectedVenueType === 'sports') ? 'Sports Venue' : 'Academic Venue';
                    ?>
                    <option value="">-- Select <?= $groupLabel ?> Category --</option>
                    <?php foreach ($categoryList as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $formData['DefectCategory'] === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <div id="categoryHint" style="font-size: 0.82rem; color: #64748b; margin-top: 0.35rem;">
                <?php if (empty($selectedVenueType)): ?>
                    <span style="font-weight: 600;">&#8505;</span> Select a venue above to view category.
                <?php else: ?>
                    <span style="color: #0284c7; font-weight: 600;">&#10003;</span> Showing maintenance categories for <strong><?= ($selectedVenueType === 'sports') ? 'Sports Venue' : 'Academic Venue' ?></strong>.
                <?php endif; ?>
            </div>
            <?php if (isset($errors['DefectCategory'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['DefectCategory']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Severity Level with Dynamic Background -->
        <div class="form-group">
            <label>Severity Level *</label>
            <div id="urgencyBox" class="urgency-radio-box <?= $initialBoxClass ?>">
                <label class="radio-label">
                    <input type="radio" name="UrgencyLevel" value="Low" <?= $formData['UrgencyLevel'] === 'Low' ? 'checked' : '' ?> autocomplete="off">
                    Low
                </label>
                <label class="radio-label">
                    <input type="radio" name="UrgencyLevel" value="Medium" <?= $formData['UrgencyLevel'] === 'Medium' ? 'checked' : '' ?> autocomplete="off">
                    Medium
                </label>
                <label class="radio-label">
                    <input type="radio" name="UrgencyLevel" value="High" <?= $formData['UrgencyLevel'] === 'High' ? 'checked' : '' ?> autocomplete="off">
                    High
                </label>
                <label class="radio-label critical-label">
                    <input type="radio" name="UrgencyLevel" value="Critical" <?= $formData['UrgencyLevel'] === 'Critical' ? 'checked' : '' ?> autocomplete="off">
                    Critical / Hazardous
                </label>
            </div>
            <?php if (isset($errors['UrgencyLevel'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['UrgencyLevel']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Defect Description with Dynamic Character Counter -->
        <div class="form-group">
            <label for="IssueDescription">Defect Description *</label>
            <textarea id="IssueDescription" name="IssueDescription" rows="3" class="form-control <?= isset($errors['IssueDescription']) ? 'is-invalid' : '' ?>" maxlength="500" placeholder="Describe the defect or safety issue in detail..." required><?= htmlspecialchars($formData['IssueDescription']) ?></textarea>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.35rem;">
                <div>
                    <?php if (isset($errors['IssueDescription'])): ?>
                        <div class="invalid-feedback" style="margin: 0;"><?= htmlspecialchars($errors['IssueDescription']) ?></div>
                    <?php endif; ?>
                </div>
                <div style="text-align: right; margin-left: auto;">
                    <span id="charCounter" style="font-size: 0.82rem; font-weight: 600; color: #64748b;">0 / 500</span>
                </div>
            </div>
        </div>

        <!-- Dynamic Safety Notice Banner -->
        <div id="safetyBanner" class="alert-banner-amber">
            <span id="safetyIcon" style="font-size: 1.25rem;">&#9888;</span>
            <div id="safetyText">
                <strong>Safety Protocol:</strong> Selecting <em>Critical / Hazardous</em> dispatches an immediate safety alert to Facility Administrators to review and approve emergency venue lockdown, preventing accidental facility closures.
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
            <a href="<?= AuthService::isMaintenanceStaff() ? 'index.php' : '../../index.php' ?>" class="btn btn-cancel">Cancel</a>
            <button type="submit" class="btn btn-primary-blue">Submit Defect Ticket</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const venueSelect = document.getElementById('FacilityID');
    const categorySelect = document.getElementById('DefectCategory');
    const categoryHint = document.getElementById('categoryHint');
    const defectForm = document.getElementById('defectReportForm');

    const academicCategories = [
        'Electrical & Power Supply',
        'Lighting & Fixtures',
        'Air-Conditioning',
        'AV & Projector',
        'Furniture & Structural',
        'Computer Hardware / Network'
    ];

    const sportsCategories = [
        'Sports Equipment / Nets',
        'Court Surface & Flooring',
        'Lighting & Fixtures',
        'Ventilation & Exhaust',
        'Electrical & Power Supply',
        'Structural / Goal Posts / Perimeter'
    ];

    function updateCategoryOptions(venueType, preserveValue) {
        if (!venueType) {
            categorySelect.disabled = true;
            categorySelect.style.cursor = 'not-allowed';
            categorySelect.innerHTML = '<option value="">-- Please Select Affected Venue First --</option>';
            if (categoryHint) {
                categoryHint.innerHTML = '<span style="font-weight: 600;">&#8505;</span> Select a venue above to view category.';
            }
            return;
        }

        categorySelect.disabled = false;
        categorySelect.style.cursor = 'pointer';

        const list = (venueType === 'sports') ? sportsCategories : academicCategories;
        const label = (venueType === 'sports') ? 'Sports Venue' : 'Academic Venue';

        let html = '<option value="">-- Select ' + label + ' Category --</option>';
        list.forEach(function(cat) {
            const selected = (preserveValue && preserveValue === cat) ? 'selected' : '';
            html += '<option value="' + cat + '" ' + selected + '>' + cat + '</option>';
        });

        categorySelect.innerHTML = html;

        if (categoryHint) {
            categoryHint.innerHTML = '<span style="color: #0284c7; font-weight: 600;">&#10003;</span> Showing maintenance categories for <strong>' + label + '</strong>.';
        }
    }

    venueSelect.addEventListener('change', function() {
        const selectedOpt = this.options[this.selectedIndex];
        const vType = selectedOpt ? selectedOpt.getAttribute('data-type') : '';
        updateCategoryOptions(vType, '');
    });

    // Ensure categorySelect is enabled before form submit so browser passes its value to POST
    defectForm.addEventListener('submit', function() {
        categorySelect.disabled = false;
    });

    const urgencyBox = document.getElementById('urgencyBox');
    const radios = document.querySelectorAll('input[name="UrgencyLevel"]');
    const safetyBanner = document.getElementById('safetyBanner');
    const safetyText = document.getElementById('safetyText');
    const safetyIcon = document.getElementById('safetyIcon');
    const descTextarea = document.getElementById('IssueDescription');
    const charCounter = document.getElementById('charCounter');

    // Live Dynamic Character Counter
    function updateCharCounter() {
        if (!descTextarea || !charCounter) return;
        const len = descTextarea.value.length;
        charCounter.textContent = len + ' / 500';

        if (len === 0) {
            charCounter.style.color = '#64748b'; // Neutral gray when empty
        } else if (len < 10) {
            charCounter.style.color = '#64748b'; // Neutral gray until minimum 10 reached
        } else if (len >= 10 && len < 450) {
            charCounter.style.color = '#10b981'; // Green for normal valid length
        } else if (len >= 450 && len <= 475) {
            charCounter.style.color = '#d97706'; // Amber warning from 450 to 475
        } else {
            charCounter.style.color = '#dc2626'; // Red from 476 to 500 (approaching limit)
        }
    }

    // Initialize character counter
    updateCharCounter();
    descTextarea.addEventListener('input', updateCharCounter);

    // Enforce initial severity level (defaults to Low)
    let currentUrgency = '<?= $formData['UrgencyLevel'] ?>' || 'Low';

    function applyInitialUrgency() {
        const targetUrgency = '<?= $formData['UrgencyLevel'] ?>' || 'Low';
        radios.forEach(function(r) {
            r.checked = (r.value === targetUrgency);
        });
        currentUrgency = targetUrgency;
        updateUrgencyTheme(targetUrgency);
    }

    function updateUrgencyTheme(level) {
        // Remove all previous urgency classes
        urgencyBox.classList.remove('urgency-box-low', 'urgency-box-medium', 'urgency-box-high', 'urgency-box-critical');

        if (level === 'Low') {
            urgencyBox.classList.add('urgency-box-low');
            safetyBanner.style.backgroundColor = '#ecfdf5';
            safetyBanner.style.borderColor = '#a7f3d0';
            safetyBanner.style.color = '#065f46';
            safetyIcon.innerHTML = '&#9989;';
            safetyText.innerHTML = '<strong>Standard Defect (Low):</strong> Non-critical issue (e.g. cosmetic wear or loose fittings). Scheduled in the regular weekly maintenance routine.';
        } else if (level === 'Medium') {
            urgencyBox.classList.add('urgency-box-medium');
            safetyBanner.style.backgroundColor = '#eff6ff';
            safetyBanner.style.borderColor = '#bfdbfe';
            safetyBanner.style.color = '#1e40af';
            safetyIcon.innerHTML = '&#128161;';
            safetyText.innerHTML = '<strong>Moderate Issue (Medium):</strong> Functional defect (e.g. AC cooling or projector flicker). Scheduled for technician check within 48 hours.';
        } else if (level === 'High') {
            urgencyBox.classList.add('urgency-box-high');
            safetyBanner.style.backgroundColor = '#fff7ed';
            safetyBanner.style.borderColor = '#fed7aa';
            safetyBanner.style.color = '#c2410c';
            safetyIcon.innerHTML = '&#9888;';
            safetyText.innerHTML = '<strong>Urgent Defect (High):</strong> Impairs room function (e.g. lighting outage). Flagged for immediate priority inspection by maintenance staff.';
        } else if (level === 'Critical') {
            urgencyBox.classList.add('urgency-box-critical');
            safetyBanner.style.backgroundColor = '#fff1f2';
            safetyBanner.style.borderColor = '#fecdd3';
            safetyBanner.style.color = '#991b1b';
            safetyIcon.innerHTML = '&#128680;';
            safetyText.innerHTML = '<strong>🚨 Critical Safety Hazard:</strong> Submitting as Critical dispatches an emergency alert to Facility Administrators to review and approve an immediate venue lockdown, preventing student injury.';
        }
    }

    // Initialize display and enforce on pageshow (overriding browser form state cache)
    applyInitialUrgency();
    window.addEventListener('pageshow', applyInitialUrgency);

    radios.forEach(function(radio) {
        radio.addEventListener('click', function(e) {
            const selectedLevel = this.value;

            if (selectedLevel === 'Critical') {
                // Confirm with user to prevent accidental lockdowns due to human error
                const confirmed = confirm(
                    "⚠️ Critical / Hazardous Defect Confirmation\n\n" +
                    "Are you sure you want to flag this defect as Critical / Hazardous?\n\n" +
                    "This will dispatch an emergency hazard alert to Facility Administrators to review and approve an immediate venue lockdown.\n\n" +
                    "Please ensure this defect poses an immediate safety risk (e.g. exposed live wiring, gas leak, ceiling structural damage)."
                );

                if (!confirmed) {
                    // Revert to previously selected level
                    e.preventDefault();
                    document.querySelector(`input[name="UrgencyLevel"][value="${currentUrgency}"]`).checked = true;
                    return false;
                }
            }

            currentUrgency = selectedLevel;
            updateUrgencyTheme(currentUrgency);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
