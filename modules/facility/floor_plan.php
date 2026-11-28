<?php
/**
 * Unique Feature: Interactive SVG Campus Floor Plan & Visual Selector
 * AMCS2194 Mini Project — Module 2 (FR-1.5 / Activity Diagram 2.1)
 * Student 1: Tee Chuan Yang
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FacilityService.php';

$pageTitle = 'Interactive Campus Floor Plan — UE Campus Facility Booking System';
$activeNav = 'floor_plan';

$facilityService = new FacilityService();
$isLoggedIn      = AuthService::isLoggedIn();

$targetDate = trim((string)($_GET['date'] ?? date('Y-m-d')));
$startTime  = trim((string)($_GET['start_time'] ?? '10:00'));
$endTime    = trim((string)($_GET['end_time'] ?? '12:00'));
$zoneFilter = trim((string)($_GET['zone'] ?? 'ALL'));

$floorData = $facilityService->getFloorPlanData($targetDate, $startTime, $endTime);

// Layout coordinates for the primary 5 campus venues + dynamic overflow slots for newly added facilities
$slotPositions = [
    'FAC-01' => ['x' => 45,  'y' => 75,  'w' => 195, 'h' => 125, 'zone' => 'Academic'],
    'FAC-02' => ['x' => 260, 'y' => 75,  'w' => 195, 'h' => 125, 'zone' => 'Academic'],
    'FAC-03' => ['x' => 515, 'y' => 75,  'w' => 185, 'h' => 125, 'zone' => 'Sports'],
    'FAC-04' => ['x' => 720, 'y' => 75,  'w' => 185, 'h' => 125, 'zone' => 'Sports'],
    'FAC-05' => ['x' => 515, 'y' => 225, 'w' => 390, 'h' => 115, 'zone' => 'Sports'],
];

$dynamicIndex = 0;
foreach ($floorData as $fid => &$fac) {
    if (isset($slotPositions[$fid])) {
        $fac['svg'] = $slotPositions[$fid];
    } else {
        $col = $dynamicIndex % 2;
        $row = intdiv($dynamicIndex, 2);
        $fac['svg'] = [
            'x'    => 45 + ($col * 215),
            'y'    => 225 + ($row * 115),
            'w'    => 195,
            'h'    => 100,
            'zone' => $fac['Category'],
        ];
        $dynamicIndex++;
    }
}
unset($fac);

$extraRows = max(0, intdiv(max(0, $dynamicIndex - 1), 2));
$svgHeight = 370 + ($extraRows * 115);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">Interactive Campus Floor Plan &amp; Visual Selector</h1>
        <p class="page-subtitle">Select a date and time window to inspect live room occupancy, then click any room or court to preview and book.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="index.php" class="btn btn-cancel">&larr; Facility Directory</a>
    </div>
</div>

<!-- Time Window & Zone Filter Bar -->
<div class="ui-card" style="padding: 1.25rem 1.5rem;">
    <form method="GET" class="filter-bar-row" style="margin-bottom: 0;">
        <div style="flex: 1; min-width: 150px;">
            <label for="fpDate" style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Target Date</label>
            <input type="date" id="fpDate" name="date" class="form-control" value="<?= htmlspecialchars($targetDate) ?>" required>
        </div>
        <div style="flex: 1; min-width: 130px;">
            <label for="fpStart" style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Start Time</label>
            <input type="time" id="fpStart" name="start_time" class="form-control" value="<?= htmlspecialchars($startTime) ?>" required>
        </div>
        <div style="flex: 1; min-width: 130px;">
            <label for="fpEnd" style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">End Time</label>
            <input type="time" id="fpEnd" name="end_time" class="form-control" value="<?= htmlspecialchars($endTime) ?>" required>
        </div>
        <div style="flex: 1; min-width: 150px;">
            <label for="fpZone" style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.25rem;">Building Zone</label>
            <select id="fpZone" name="zone" class="form-select">
                <option value="ALL" <?= $zoneFilter === 'ALL' ? 'selected' : '' ?>>All Campus Zones</option>
                <option value="Academic" <?= $zoneFilter === 'Academic' ? 'selected' : '' ?>>Library Block A (Academic)</option>
                <option value="Sports" <?= $zoneFilter === 'Sports' ? 'selected' : '' ?>>Sports Complex &amp; Outdoor</option>
            </select>
        </div>
        <div style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn btn-primary-blue" style="height: 40px;">Check Live Map</button>
        </div>
    </form>
</div>

<!-- Interactive SVG Map + Legend -->
<div class="ui-card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
        <div style="font-weight: 700; color: var(--text-heading); font-size: 1rem;">
            Live Campus Map &mdash; <?= htmlspecialchars(date('d M Y', strtotime($targetDate))) ?> (<?= htmlspecialchars($startTime) ?> &ndash; <?= htmlspecialchars($endTime) ?>)
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.84rem; font-weight: 600;">
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                <span style="width: 12px; height: 12px; border-radius: 3px; background: #dcfce7; border: 2px solid #16a34a; display: inline-block;"></span>
                Available
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                <span style="width: 12px; height: 12px; border-radius: 3px; background: #fee2e2; border: 2px solid #dc2626; display: inline-block;"></span>
                Occupied
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                <span style="width: 12px; height: 12px; border-radius: 3px; background: #fef3c7; border: 2px solid #d97706; display: inline-block;"></span>
                Maintenance / Closed
            </span>
        </div>
    </div>

    <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 12px; background: var(--body-bg); padding: 0.75rem;">
        <svg viewBox="0 0 950 <?= $svgHeight ?>" style="width: 100%; min-width: 720px; height: auto; display: block;" xmlns="http://www.w3.org/2000/svg">
            <!-- Zone 1: Library Block A -->
            <rect x="20" y="20" width="460" height="<?= $svgHeight - 40 ?>" rx="14" fill="#ffffff" stroke="#cbd5e1" stroke-width="2" />
            <text x="45" y="52" font-family="Segoe UI, sans-serif" font-size="15" font-weight="700" fill="#0f172a">
                Library Block A (Academic Rooms)
            </text>

            <!-- Zone 2: Sports Complex & Outdoor -->
            <rect x="495" y="20" width="435" height="<?= $svgHeight - 40 ?>" rx="14" fill="#ffffff" stroke="#cbd5e1" stroke-width="2" />
            <text x="515" y="52" font-family="Segoe UI, sans-serif" font-size="15" font-weight="700" fill="#0f172a">
                Sports Complex &amp; Outdoor Zone
            </text>

            <?php foreach ($floorData as $fid => $f):
                $dimmed = ($zoneFilter !== 'ALL' && $f['Category'] !== $zoneFilter);
                $colors = match ($f['live_state']) {
                    'available'   => ['fill' => '#dcfce7', 'stroke' => '#16a34a', 'text' => '#14532d'],
                    'occupied'    => ['fill' => '#fee2e2', 'stroke' => '#dc2626', 'text' => '#7f1d1d'],
                    default       => ['fill' => '#fef3c7', 'stroke' => '#d97706', 'text' => '#78350f'],
                };
                $svg = $f['svg'];
                $payload = htmlspecialchars(json_encode([
                    'id'          => $f['FacilityID'],
                    'name'        => $f['FacilityName'],
                    'category'    => $f['Category'],
                    'block'       => $f['LocationBlock'],
                    'capacity'    => (int)$f['Capacity'],
                    'aircond'     => ((int)$f['HasAircond'] === 1) ? 'Yes' : 'No',
                    'hours'       => $f['OperatingHours'],
                    'live_state'  => $f['live_state'],
                    'live_label'  => $f['live_label'],
                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
            ?>
                <g class="svg-room-node"
                   onclick="openFloorPreview(<?= $payload ?>)"
                   style="cursor: pointer; opacity: <?= $dimmed ? '0.25' : '1' ?>; transition: opacity 0.2s;">
                    <rect x="<?= $svg['x'] ?>" y="<?= $svg['y'] ?>" width="<?= $svg['w'] ?>" height="<?= $svg['h'] ?>" rx="10"
                          fill="<?= $colors['fill'] ?>" stroke="<?= $colors['stroke'] ?>" stroke-width="2.5" />
                    <text x="<?= $svg['x'] + 14 ?>" y="<?= $svg['y'] + 28 ?>" font-family="Segoe UI, sans-serif" font-size="13" font-weight="800" fill="<?= $colors['text'] ?>">
                        <?= htmlspecialchars($f['FacilityID']) ?>
                    </text>
                    <text x="<?= $svg['x'] + 14 ?>" y="<?= $svg['y'] + 50 ?>" font-family="Segoe UI, sans-serif" font-size="13" font-weight="700" fill="#0f172a">
                        <?= htmlspecialchars(mb_strimwidth($f['FacilityName'], 0, 24, '...')) ?>
                    </text>
                    <text x="<?= $svg['x'] + 14 ?>" y="<?= $svg['y'] + 72 ?>" font-family="Segoe UI, sans-serif" font-size="11.5" fill="#475569">
                        Capacity: <?= (int)$f['Capacity'] ?> pax &bull; <?= ((int)$f['HasAircond'] === 1) ? 'A/C' : 'Open-Air' ?>
                    </text>
                    <text x="<?= $svg['x'] + 14 ?>" y="<?= $svg['y'] + 94 ?>" font-family="Segoe UI, sans-serif" font-size="11.5" font-weight="700" fill="<?= $colors['stroke'] ?>">
                        <?= htmlspecialchars($f['live_label']) ?>
                    </text>
                </g>
            <?php endforeach; ?>
        </svg>
    </div>
</div>

<!-- Room Preview Modal (Activity Diagram 2.1) -->
<div id="floorPreviewModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
    <div style="background: var(--card-bg); color: var(--text-heading); border: 1px solid var(--border-color); border-radius: 16px; max-width: 460px; width: 100%; padding: 1.65rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 1rem;">
            <div>
                <div id="fpModalId" style="font-size: 0.8rem; font-weight: 700; color: var(--primary-blue);">FAC-01</div>
                <h3 id="fpModalName" style="font-size: 1.2rem; font-weight: 800; margin: 0.15rem 0 0 0;">Room Name</h3>
            </div>
            <span id="fpModalBadge" class="pill-badge pill-resolved">Available</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.55rem; font-size: 0.9rem; margin-bottom: 1.35rem; background: var(--body-bg); padding: 1rem; border-radius: 10px; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Category</span>
                <strong id="fpModalCategory">-</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Location</span>
                <strong id="fpModalBlock">-</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Capacity</span>
                <strong id="fpModalCapacity">-</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Air-Conditioning</span>
                <strong id="fpModalAircond">-</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Operating Hours</span>
                <strong id="fpModalHours">-</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Selected Slot</span>
                <strong><?= htmlspecialchars($targetDate) ?> (<?= htmlspecialchars($startTime) ?> - <?= htmlspecialchars($endTime) ?>)</strong>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <button type="button" onclick="closeFloorPreview()" class="btn btn-cancel" style="flex: 1;">Close</button>
            <a id="fpProceedBookBtn" href="#" class="btn btn-primary-blue" style="flex: 2; text-align: center; font-weight: 700;">
                Proceed to Book &rarr;
            </a>
        </div>
    </div>
</div>

<script>
var fpIsLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
var fpDate       = <?= json_encode($targetDate) ?>;
var fpStart      = <?= json_encode($startTime) ?>;
var fpEnd        = <?= json_encode($endTime) ?>;

function openFloorPreview(data) {
    document.getElementById('fpModalId').textContent = data.id;
    document.getElementById('fpModalName').textContent = data.name;
    document.getElementById('fpModalCategory').textContent = data.category;
    document.getElementById('fpModalBlock').textContent = data.block;
    document.getElementById('fpModalCapacity').textContent = data.capacity + ' pax';
    document.getElementById('fpModalAircond').textContent = data.aircond;
    document.getElementById('fpModalHours').textContent = data.hours;

    var badge = document.getElementById('fpModalBadge');
    badge.textContent = data.live_label;
    badge.className = 'pill-badge ' + (
        data.live_state === 'available' ? 'pill-resolved' :
        (data.live_state === 'occupied' ? 'pill-critical' : 'pill-medium')
    );

    var bookBtn = document.getElementById('fpProceedBookBtn');
    if (data.live_state === 'available') {
        var bookingQuery = 'modules/booking/create.php?facility_id=' + encodeURIComponent(data.id)
            + '&date=' + encodeURIComponent(fpDate)
            + '&start_time=' + encodeURIComponent(fpStart)
            + '&end_time=' + encodeURIComponent(fpEnd);
        bookBtn.href = fpIsLoggedIn
            ? '../booking/create.php?facility_id=' + encodeURIComponent(data.id)
                + '&date=' + encodeURIComponent(fpDate)
                + '&start_time=' + encodeURIComponent(fpStart)
                + '&end_time=' + encodeURIComponent(fpEnd)
            : '../user/login.php?redirect=' + encodeURIComponent(bookingQuery);
        bookBtn.style.display = 'inline-block';
    } else {
        bookBtn.style.display = 'none';
    }

    document.getElementById('floorPreviewModal').style.display = 'flex';
}

function closeFloorPreview() {
    document.getElementById('floorPreviewModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
