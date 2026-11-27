<?php
/**
 * Module 3: Reserve a Campus Facility & Intelligent Slot Suggestion (Create)
 * AMCS2194 Mini Project — Process 3.1 + Unique Feature
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/BookingService.php';

$pageTitle = 'Reserve a Campus Facility';
$activeNav = 'bookings';

$bookingService = new BookingService();
$currentUser    = AuthService::getCurrentUser();

if (AuthService::isTechnician()) {
    header('Location: ../maintenance/index.php');
    exit;
}

// Check penalty ban
$penalty = $bookingService->getUserPenaltyStatus($currentUser['UserID']);

// Retrieve facilities for dropdown
$db = getDB();
$facStmt = $db->query("SELECT * FROM facility ORDER BY Category DESC, FacilityID ASC");
$facilities = $facStmt->fetchAll();

// Group facilities by Category
$academicFacilities = [];
$sportsFacilities   = [];
foreach ($facilities as $f) {
    if ($f['Category'] === 'Sports') {
        $sportsFacilities[] = $f;
    } else {
        $academicFacilities[] = $f;
    }
}

// Form defaults
$formData = [
    'FacilityID'  => trim((string)($_GET['facility_id'] ?? '')),
    'BookingDate' => trim((string)($_GET['date'] ?? date('Y-m-d'))),
    'StartTime'   => trim((string)($_GET['start_time'] ?? '14:00')),
    'EndTime'     => trim((string)($_GET['end_time'] ?? '16:00')),
    'PartySize'   => (int)($_GET['party_size'] ?? 2),
    'Purpose'     => trim((string)($_GET['purpose'] ?? '')),
];

// If no facility is preselected, default to first available
if ($formData['FacilityID'] === '' && !empty($facilities)) {
    $formData['FacilityID'] = $facilities[0]['FacilityID'];
}

$errors      = [];
$conflict    = null;
$suggestions = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['FacilityID']  = trim((string)($_POST['FacilityID'] ?? ''));
    $formData['BookingDate'] = trim((string)($_POST['BookingDate'] ?? ''));
    $formData['StartTime']   = trim((string)($_POST['StartTime'] ?? ''));
    $formData['EndTime']     = trim((string)($_POST['EndTime'] ?? ''));
    $formData['PartySize']   = (int)($_POST['PartySize'] ?? 1);
    $formData['Purpose']     = trim((string)($_POST['Purpose'] ?? ''));
    $formData['UserID']      = $currentUser['UserID'];

    $result = $bookingService->createBookingWithValidation($formData);

    if ($result['success']) {
        AuthService::setFlash('success', $result['message']);
        header('Location: receipt.php?id=' . urlencode($result['booking_id']));
        exit;
    } else {
        $errors = $result['errors'];
        if (!empty($result['conflict'])) {
            $conflict    = $result['conflict_with'] ?? null;
            $suggestions = $result['suggestions'] ?? null;
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header -->
<div class="page-header-row">
    <div>
        <h1 class="page-title">Reserve a Campus Facility</h1>
        <p class="page-subtitle">Schedule sports courts or academic discussion rooms in real time with automated conflict checking.</p>
    </div>
    <div>
        <a href="index.php" class="btn btn-cancel" style="padding: 0.55rem 1rem;">
            ← Back to Bookings
        </a>
    </div>
</div>

<!-- 3-Day Penalty Warning -->
<?php if ($penalty['is_banned']): ?>
    <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 1.25rem; margin-bottom: 1.75rem;">
        <h3 style="color: #991b1b; margin: 0 0 0.5rem 0; font-size: 1.1rem;">
            🚫 Booking Privileges Suspended
        </h3>
        <p style="color: #7f1d1d; margin: 0 0 0.75rem 0; font-size: 0.95rem;">
            You have received 3 unexcused no-show strikes. New bookings are suspended until 
            <strong><?= htmlspecialchars(date('d M Y, H:i', strtotime((string)$penalty['ban_until']))) ?></strong>.
        </p>
        <a href="../../index.php" class="btn btn-outline-blue" style="font-size: 0.85rem; padding: 0.4rem 0.8rem; background: #fff;">
            Return to Home & Reset Penalty Demo
        </a>
    </div>
<?php endif; ?>

<!-- UNIQUE FEATURE: CONFLICT DETECTION & INTELLIGENT SUGGESTION CARD -->
<?php if ($conflict && $suggestions): ?>
    <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 2px solid #f59e0b; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
            <span style="font-size: 1.75rem;">⚠️</span>
            <div>
                <h3 style="margin: 0; color: #92400e; font-size: 1.2rem; font-weight: 700;">
                    Time-Slot Conflict Detected! (Unique Feature Activated)
                </h3>
                <p style="margin: 0.2rem 0 0 0; color: #78350f; font-size: 0.92rem;">
                    <strong><?= htmlspecialchars($suggestions['target_venue']['FacilityName']) ?></strong> is already reserved on 
                    <strong><?= htmlspecialchars(date('d M Y', strtotime($formData['BookingDate']))) ?></strong> 
                    from <strong><?= htmlspecialchars(substr($conflict['StartTime'], 0, 5)) ?></strong> to 
                    <strong><?= htmlspecialchars(substr($conflict['EndTime'], 0, 5)) ?></strong> 
                    by <em><?= htmlspecialchars($conflict['UserName'] ?? 'another student') ?></em>.
                </p>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #fde68a; margin: 1rem 0;">

        <!-- Recommendation 1: Nearest Free Slots at this Venue -->
        <div style="margin-bottom: 1.25rem;">
            <div style="font-weight: 700; color: #92400e; font-size: 0.98rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                <span>⏱️</span> Nearest Free Time Slots for <?= htmlspecialchars($suggestions['target_venue']['FacilityName']) ?>:
            </div>
            <?php if (empty($suggestions['nearest_slots'])): ?>
                <div style="font-size: 0.88rem; color: #78350f; font-style: italic;">
                    No alternative free slots available for this venue on <?= htmlspecialchars($formData['BookingDate']) ?>.
                </div>
            <?php else: ?>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <?php foreach ($suggestions['nearest_slots'] as $slot): ?>
                        <button type="button" 
                                onclick="applySlotSuggestion('<?= htmlspecialchars($slot['StartTime']) ?>', '<?= htmlspecialchars($slot['EndTime']) ?>')"
                                style="background: #ffffff; border: 1px solid #f59e0b; border-radius: 8px; padding: 0.6rem 0.95rem; cursor: pointer; text-align: left; transition: all 0.15s ease;"
                                onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='#ffffff'">
                            <div style="font-weight: 700; color: #92400e; font-size: 0.95rem;">
                                <?= htmlspecialchars($slot['Label']) ?>
                            </div>
                            <div style="font-size: 0.78rem; color: #b45309; margin-top: 2px;">
                                👉 Click to select this time slot
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recommendation 2: Alternative Similar Venues at Same Requested Time -->
        <div>
            <div style="font-weight: 700; color: #92400e; font-size: 0.98rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                <span>🏢</span> Alternative Available <?= htmlspecialchars($suggestions['target_venue']['Category']) ?> Venues at <?= htmlspecialchars(substr($formData['StartTime'], 0, 5) . ' – ' . substr($formData['EndTime'], 0, 5)) ?>:
            </div>
            <?php if (empty($suggestions['alternative_venues'])): ?>
                <div style="font-size: 0.88rem; color: #78350f; font-style: italic;">
                    No alternative <?= htmlspecialchars($suggestions['target_venue']['Category']) ?> venues are free at this exact time.
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.75rem;">
                    <?php foreach ($suggestions['alternative_venues'] as $alt): ?>
                        <div style="background: #ffffff; border: 1px solid #f59e0b; border-radius: 8px; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <div>
                                <div style="font-weight: 700; color: #1e3a8a; font-size: 0.95rem;">
                                    <?= htmlspecialchars($alt['FacilityName']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                    📍 <?= htmlspecialchars($alt['LocationBlock']) ?> • Max <?= (int)$alt['Capacity'] ?> pax
                                </div>
                            </div>
                            <button type="button" 
                                    onclick="applyVenueSuggestion('<?= htmlspecialchars($alt['FacilityID']) ?>')"
                                    class="btn btn-outline-blue" 
                                    style="padding: 0.4rem 0.75rem; font-size: 0.8rem; white-space: nowrap; font-weight: 600;">
                                Switch Venue
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Main Booking Form Card -->
<div class="ui-card" style="max-width: 820px; margin: 0 auto;">
    <form method="POST" action="create.php" id="bookingForm">
        
        <!-- General error alert if present -->
        <?php if (!empty($errors['general'])): ?>
            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
                <?= htmlspecialchars($errors['general']) ?>
            </div>
        <?php endif; ?>

        <!-- Facility Selection -->
        <div class="form-group" style="margin-bottom: 1.35rem;">
            <label class="form-label" for="FacilityID" style="font-weight: 700;">
                Campus Facility <span style="color: #dc2626;">*</span>
            </label>
            <select id="FacilityID" name="FacilityID" class="form-select <?= isset($errors['FacilityID']) ? 'is-invalid' : '' ?>" onchange="updateVenueMetadata()" required>
                <optgroup label="Academic Discussion & Study Rooms">
                    <?php foreach ($academicFacilities as $f): ?>
                        <option value="<?= htmlspecialchars($f['FacilityID']) ?>" 
                                data-category="Academic"
                                data-capacity="<?= (int)$f['Capacity'] ?>"
                                data-status="<?= htmlspecialchars($f['AvailabilityStatus']) ?>"
                                data-block="<?= htmlspecialchars($f['LocationBlock']) ?>"
                                <?= $formData['FacilityID'] === $f['FacilityID'] ? 'selected' : '' ?>
                                <?= $f['AvailabilityStatus'] === 'Under Maintenance' ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($f['FacilityName']) ?> (<?= htmlspecialchars($f['LocationBlock']) ?>) — Cap: <?= (int)$f['Capacity'] ?> pax <?= $f['AvailabilityStatus'] === 'Under Maintenance' ? '[LOCKED: UNDER MAINTENANCE]' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Sports Courts & Complex">
                    <?php foreach ($sportsFacilities as $f): ?>
                        <option value="<?= htmlspecialchars($f['FacilityID']) ?>" 
                                data-category="Sports"
                                data-capacity="<?= (int)$f['Capacity'] ?>"
                                data-status="<?= htmlspecialchars($f['AvailabilityStatus']) ?>"
                                data-block="<?= htmlspecialchars($f['LocationBlock']) ?>"
                                <?= $formData['FacilityID'] === $f['FacilityID'] ? 'selected' : '' ?>
                                <?= $f['AvailabilityStatus'] === 'Under Maintenance' ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($f['FacilityName']) ?> (<?= htmlspecialchars($f['LocationBlock']) ?>) — Cap: <?= (int)$f['Capacity'] ?> pax <?= $f['AvailabilityStatus'] === 'Under Maintenance' ? '[LOCKED: UNDER MAINTENANCE]' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <?php if (isset($errors['FacilityID'])): ?>
                <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                    <?= htmlspecialchars($errors['FacilityID']) ?>
                </div>
            <?php endif; ?>
            <div id="venueInfoPill" style="margin-top: 0.5rem; font-size: 0.85rem; color: #2563eb;"></div>
        </div>

        <!-- Date & Party Size (Row) -->
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.25rem; margin-bottom: 1.35rem;">
            <div class="form-group">
                <label class="form-label" for="BookingDate" style="font-weight: 700;">
                    Reservation Date <span style="color: #dc2626;">*</span>
                </label>
                <input type="date" id="BookingDate" name="BookingDate" 
                       class="form-control <?= isset($errors['BookingDate']) ? 'is-invalid' : '' ?>"
                       min="<?= date('Y-m-d') ?>" 
                       value="<?= htmlspecialchars($formData['BookingDate']) ?>" required>
                <?php if (isset($errors['BookingDate'])): ?>
                    <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                        <?= htmlspecialchars($errors['BookingDate']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="PartySize" style="font-weight: 700;">
                    Party Size (Pax) <span style="color: #dc2626;">*</span>
                </label>
                <input type="number" id="PartySize" name="PartySize" 
                       class="form-control <?= isset($errors['PartySize']) ? 'is-invalid' : '' ?>"
                       min="1" max="50" 
                       value="<?= (int)$formData['PartySize'] ?>" required>
                <div id="capacityHint" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Checking venue capacity...
                </div>
                <?php if (isset($errors['PartySize'])): ?>
                    <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                        <?= htmlspecialchars($errors['PartySize']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Time Slots (Row) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1rem;">
            <div class="form-group">
                <label class="form-label" for="StartTime" style="font-weight: 700;">
                    Start Time <span style="color: #dc2626;">*</span>
                </label>
                <input type="time" id="StartTime" name="StartTime" 
                       class="form-control <?= isset($errors['StartTime']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($formData['StartTime']) ?>" required>
                <?php if (isset($errors['StartTime'])): ?>
                    <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                        <?= htmlspecialchars($errors['StartTime']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="EndTime" style="font-weight: 700;">
                    End Time <span style="color: #dc2626;">*</span>
                </label>
                <input type="time" id="EndTime" name="EndTime" 
                       class="form-control <?= isset($errors['EndTime']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($formData['EndTime']) ?>" required>
                <?php if (isset($errors['EndTime'])): ?>
                    <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                        <?= htmlspecialchars($errors['EndTime']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Slot Selector Chips -->
        <div style="margin-bottom: 1.35rem;">
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.4rem; font-weight: 600;">
                Quick Standard Slots:
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline-blue" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="setTimes('10:00', '12:00')">10:00 – 12:00</button>
                <button type="button" class="btn btn-outline-blue" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="setTimes('14:00', '16:00')">14:00 – 16:00</button>
                <button type="button" class="btn btn-outline-blue" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="setTimes('16:00', '18:00')">16:00 – 18:00</button>
                <button type="button" class="btn btn-outline-blue" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="setTimes('18:00', '20:00')">18:00 – 20:00</button>
                <button type="button" class="btn btn-outline-blue" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;" onclick="setTimes('20:00', '22:00')">20:00 – 22:00</button>
            </div>
        </div>

        <!-- Purpose -->
        <div class="form-group" style="margin-bottom: 1.75rem;">
            <label class="form-label" for="Purpose" style="font-weight: 700;">
                Session Purpose / Description <span style="color: #dc2626;">*</span>
            </label>
            <input type="text" id="Purpose" name="Purpose" 
                   class="form-control <?= isset($errors['Purpose']) ? 'is-invalid' : '' ?>"
                   placeholder="e.g., Mini Project Group Discussion, Badminton Friendly Match, Society Meeting..."
                   value="<?= htmlspecialchars($formData['Purpose']) ?>" required>
            <?php if (isset($errors['Purpose'])): ?>
                <div style="color: #dc2626; font-size: 0.85rem; margin-top: 0.35rem;">
                    <?= htmlspecialchars($errors['Purpose']) ?>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.45rem;">
                <span style="font-size: 0.78rem; color: var(--text-muted);">Quick presets:</span>
                <a href="javascript:void(0)" onclick="setPurpose('Mini Project Group Discussion')" style="font-size: 0.78rem; color: #2563eb;">Group Discussion</a> •
                <a href="javascript:void(0)" onclick="setPurpose('Weekly Badminton Practice')" style="font-size: 0.78rem; color: #2563eb;">Badminton Practice</a> •
                <a href="javascript:void(0)" onclick="setPurpose('Committee Meeting & Presentation')" style="font-size: 0.78rem; color: #2563eb;">Committee Meeting</a> •
                <a href="javascript:void(0)" onclick="setPurpose('Exam Revision Study Group')" style="font-size: 0.78rem; color: #2563eb;">Exam Revision</a>
            </div>
        </div>

        <!-- Submit Actions -->
        <div style="display: flex; gap: 1rem; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <button type="submit" class="btn btn-primary-blue" style="padding: 0.7rem 1.6rem; font-size: 1rem; font-weight: 700;" <?= $penalty['is_banned'] ? 'disabled' : '' ?>>
                Confirm & Reserve Venue
            </button>
            <a href="index.php" class="btn btn-cancel" style="padding: 0.7rem 1.3rem;">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function setTimes(start, end) {
    document.getElementById('StartTime').value = start;
    document.getElementById('EndTime').value = end;
}

function setPurpose(text) {
    document.getElementById('Purpose').value = text;
}

function applySlotSuggestion(startTime, endTime) {
    document.getElementById('StartTime').value = startTime;
    document.getElementById('EndTime').value = endTime;
    window.scrollTo({ top: document.getElementById('bookingForm').offsetTop - 60, behavior: 'smooth' });
}

function applyVenueSuggestion(facilityId) {
    const sel = document.getElementById('FacilityID');
    sel.value = facilityId;
    updateVenueMetadata();
    window.scrollTo({ top: document.getElementById('bookingForm').offsetTop - 60, behavior: 'smooth' });
}

function updateVenueMetadata() {
    const sel = document.getElementById('FacilityID');
    const opt = sel.options[sel.selectedIndex];
    if (!opt) return;

    const cap = opt.getAttribute('data-capacity') || '0';
    const block = opt.getAttribute('data-block') || '';
    const status = opt.getAttribute('data-status') || '';
    const cat = opt.getAttribute('data-category') || '';

    const hint = document.getElementById('capacityHint');
    if (hint) {
        hint.innerHTML = `Max capacity for this venue: <strong>${cap} persons</strong>. Location: ${block}.`;
    }

    const partyInput = document.getElementById('PartySize');
    if (partyInput && parseInt(partyInput.value, 10) > parseInt(cap, 10)) {
        partyInput.value = cap;
    }
}

document.addEventListener('DOMContentLoaded', updateVenueMetadata);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
