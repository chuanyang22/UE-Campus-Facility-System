<?php
/**
 * My Profile & Account View Page
 * AMCS2194 Mini Project — Module 1 (FR-1.2 Retrieve)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/UserService.php';

$pageTitle = 'My Profile — UE Campus Facility Booking System';
$activeNav = 'profile';

$userService = new UserService();
AuthService::requireLogin($_SERVER['REQUEST_URI'] ?? '');
$currentUser = AuthService::getCurrentUser();
$userId      = $currentUser['UserID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_avatar') {
    $croppedData = (string)($_POST['cropped_avatar'] ?? '');
    $res = $userService->saveCroppedAvatar($userId, $croppedData);
    if ($res['success']) {
        $_SESSION['user']['AvatarPath'] = $res['path'];
        AuthService::setFlash('success', $res['message']);
    } else {
        AuthService::setFlash('danger', $res['message']);
    }
    header('Location: profile.php');
    exit;
}

$profile = $userService->getUserById($userId) ?? $currentUser;
$summary = $userService->getUserBookingSummary($userId);

$statusBadgeClass = match ($profile['AccountStatus'] ?? 'Active') {
    'Active'    => 'pill-resolved',
    'Suspended' => 'pill-medium',
    default     => 'pill-critical',
};

// Mask email and phone by default for privacy
$rawEmail = (string)($profile['CampusEmail'] ?? '');
$maskedEmail = $rawEmail;
if (str_contains($rawEmail, '@')) {
    [$local, $domain] = explode('@', $rawEmail, 2);
    $maskedEmail = substr($local, 0, 1) . str_repeat('•', max(4, strlen($local) - 1)) . '@' . $domain;
}

$rawPhone = (string)($profile['PhoneNo'] ?? '');
$maskedPhone = $rawPhone;
if (strlen($rawPhone) > 4) {
    $maskedPhone = substr($rawPhone, 0, 3) . str_repeat('•', max(4, strlen($rawPhone) - 5)) . substr($rawPhone, -2);
}

$dobDisplay = !empty($profile['DateOfBirth'])
    ? date('d M Y', strtotime($profile['DateOfBirth']))
    : '-';
$genderDisplay = !empty($profile['Gender'])
    ? $profile['Gender']
    : '-';

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">View your profile details, booking summary, and customize your profile picture.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="edit_profile.php" class="btn btn-primary-blue">Edit Profile</a>
        <?php if (AuthService::isAdmin()): ?>
            <a href="index.php" class="btn btn-outline-blue">Manage All Users &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<!-- 4 Stat Metric Counters (Booking History Summary) -->
<div class="stat-row-4">
    <div class="stat-box">
        <div class="stat-box-title">Total Bookings</div>
        <div class="stat-box-num num-black"><?= $summary['total_bookings'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Active / Upcoming</div>
        <div class="stat-box-num num-amber"><?= $summary['active_bookings'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Completed Sessions</div>
        <div class="stat-box-num num-green"><?= $summary['completed_bookings'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">No-Show Strikes</div>
        <div class="stat-box-num <?= $summary['no_show_count'] >= 3 ? 'num-red' : 'num-black' ?>">
            <?= $summary['no_show_count'] ?> / 3
        </div>
    </div>
</div>

<!-- Clean Profile Card -->
<div style="max-width: 720px; margin: 0 auto;">
    <div class="ui-card">
        <!-- Avatar Preview & Upload Trigger -->
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; flex-wrap: wrap; padding: 1.1rem; background: var(--body-bg); border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.15rem;">
                <?php if (!empty($profile['AvatarPath'])): ?>
                    <img src="../../<?= htmlspecialchars($profile['AvatarPath']) ?>" alt="Profile Picture"
                         style="width: 82px; height: 82px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-blue); flex-shrink: 0;">
                <?php else: ?>
                    <div style="width: 82px; height: 82px; border-radius: 50%; background: var(--primary-blue); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.85rem; font-weight: 800; flex-shrink: 0;">
                        <?= htmlspecialchars(strtoupper(substr($profile['FullName'] ?? 'U', 0, 1))) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <div style="font-weight: 800; color: var(--text-heading); font-size: 1.1rem; margin-bottom: 0.2rem;">
                        <?= htmlspecialchars($profile['FullName']) ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;">
                        <span class="pill-badge pill-open"><?= htmlspecialchars($profile['UserRole']) ?></span>
                        <span class="pill-badge <?= $statusBadgeClass ?>"><?= htmlspecialchars($profile['AccountStatus']) ?></span>
                    </div>
                </div>
            </div>
            <div>
                <input type="file" id="avatarFileInput" accept="image/png,image/jpeg,image/webp" style="display: none;">
                <button type="button" onclick="document.getElementById('avatarFileInput').click()" class="btn btn-outline-blue" style="padding: 0.45rem 0.95rem; font-size: 0.85rem;">
                    Upload &amp; Crop Photo
                </button>
            </div>
        </div>

        <!-- Section Header with Clickable Edit Word -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
            <h2 style="font-size: 1.05rem; font-weight: 700; color: var(--text-heading); margin: 0;">
                Personal Information
            </h2>
            <a href="edit_profile.php" style="font-size: 0.88rem; font-weight: 700; color: var(--primary-blue); text-decoration: none;">
                Edit &rarr;
            </a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.92rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.55rem;">
                <span style="color: var(--text-muted);">User ID</span>
                <strong><?= htmlspecialchars($profile['UserID']) ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.55rem;">
                <span style="color: var(--text-muted);">Full Name</span>
                <strong><?= htmlspecialchars($profile['FullName']) ?></strong>
            </div>

            <!-- Email with Eye Toggle -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.55rem; gap: 0.75rem;">
                <span style="color: var(--text-muted);">Email</span>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <strong id="profileEmailVal"
                            data-masked="<?= htmlspecialchars($maskedEmail) ?>"
                            data-real="<?= htmlspecialchars($rawEmail) ?>"
                            data-visible="0"><?= htmlspecialchars($maskedEmail) ?></strong>
                    <button type="button" onclick="toggleProfileField('profileEmailVal', this)"
                            aria-label="Show or hide email"
                            style="background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.15rem; display: flex; align-items: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <!-- Phone Number with Eye Toggle -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.55rem; gap: 0.75rem;">
                <span style="color: var(--text-muted);">Phone Number</span>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <strong id="profilePhoneVal"
                            data-masked="<?= htmlspecialchars($maskedPhone) ?>"
                            data-real="<?= htmlspecialchars($rawPhone) ?>"
                            data-visible="0"><?= htmlspecialchars($maskedPhone) ?></strong>
                    <button type="button" onclick="toggleProfileField('profilePhoneVal', this)"
                            aria-label="Show or hide phone number"
                            style="background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.15rem; display: flex; align-items: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <!-- Date of Birth -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.55rem;">
                <span style="color: var(--text-muted);">Date of Birth</span>
                <strong><?= htmlspecialchars($dobDisplay) ?></strong>
            </div>

            <!-- Gender -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.55rem;">
                <span style="color: var(--text-muted);">Gender</span>
                <strong><?= htmlspecialchars($genderDisplay) ?></strong>
            </div>

            <?php if (!empty($profile['BookingBanUntil'])): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.55rem;">
                    <span style="color: var(--text-muted);">Booking Ban Until</span>
                    <strong style="color: var(--danger-red);"><?= htmlspecialchars($profile['BookingBanUntil']) ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
            <a href="../../index.php" class="btn btn-outline-blue" style="flex: 1; text-align: center; font-size: 0.88rem;">
                Browse Facilities &rarr;
            </a>
            <a href="../booking/index.php" class="btn btn-primary-blue" style="flex: 1; text-align: center; font-size: 0.88rem;">
                My Bookings &rarr;
            </a>
        </div>
    </div>
</div>

<!-- Interactive Profile Picture Cropper Modal -->
<div id="avatarCropModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(4px); z-index: 9999; justify-content: center; align-items: center; padding: 1rem;">
    <div style="background: var(--card-bg); color: var(--text-heading); border: 1px solid var(--border-color); border-radius: 16px; max-width: 420px; width: 100%; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.25);">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem;">Crop Your Profile Picture</h3>
        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 1rem;">Drag the image to position and use the slider to zoom.</p>

        <div style="display: flex; justify-content: center; margin-bottom: 1rem;">
            <div style="position: relative; width: 260px; height: 260px; border-radius: 12px; overflow: hidden; border: 2px solid var(--border-color); background: #0f172a; cursor: grab;">
                <canvas id="avatarCropCanvas" width="260" height="260" style="display: block; touch-action: none;"></canvas>
                <div style="pointer-events: none; position: absolute; inset: 0; border-radius: 50%; box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.48); border: 2px dashed rgba(255,255,255,0.85);"></div>
            </div>
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label for="avatarZoomSlider" style="display: flex; justify-content: space-between; font-size: 0.84rem; font-weight: 600; margin-bottom: 0.35rem;">
                <span>Zoom</span>
                <span id="avatarZoomLabel">1.0x</span>
            </label>
            <input type="range" id="avatarZoomSlider" min="1" max="3" step="0.05" value="1" style="width: 100%; cursor: pointer;">
        </div>

        <form method="POST" id="avatarUploadForm">
            <input type="hidden" name="action" value="upload_avatar">
            <input type="hidden" name="cropped_avatar" id="croppedAvatarInput" value="">
            <div style="display: flex; gap: 0.75rem;">
                <button type="button" onclick="closeAvatarCropModal()" class="btn btn-cancel" style="flex: 1;">Cancel</button>
                <button type="button" onclick="submitCroppedAvatar()" class="btn btn-primary-blue" style="flex: 2; font-weight: 700;">Save Cropped Photo</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleProfileField(spanId, btn) {
    var el = document.getElementById(spanId);
    if (!el) return;
    var isVisible = el.getAttribute('data-visible') === '1';
    if (isVisible) {
        el.textContent = el.getAttribute('data-masked');
        el.setAttribute('data-visible', '0');
        btn.style.color = 'var(--text-muted)';
    } else {
        el.textContent = el.getAttribute('data-real');
        el.setAttribute('data-visible', '1');
        btn.style.color = 'var(--primary-blue)';
    }
}

(function() {
    var fileInput = document.getElementById('avatarFileInput');
    var modal     = document.getElementById('avatarCropModal');
    var canvas    = document.getElementById('avatarCropCanvas');
    var ctx       = canvas ? canvas.getContext('2d') : null;
    var zoomEl    = document.getElementById('avatarZoomSlider');
    var zoomLbl   = document.getElementById('avatarZoomLabel');

    var img = null;
    var baseScale = 1;
    var zoom = 1;
    var offsetX = 0;
    var offsetY = 0;
    var dragging = false;
    var lastX = 0;
    var lastY = 0;

    function drawCropCanvas() {
        if (!ctx || !img) return;
        var w = canvas.width;
        var h = canvas.height;
        ctx.clearRect(0, 0, w, h);

        var scale = baseScale * zoom;
        var drawW = img.width * scale;
        var drawH = img.height * scale;
        var x = (w - drawW) / 2 + offsetX;
        var y = (h - drawH) / 2 + offsetY;

        ctx.drawImage(img, x, y, drawW, drawH);
    }

    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            var file = e.target.files && e.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(ev) {
                img = new Image();
                img.onload = function() {
                    baseScale = Math.max(canvas.width / img.width, canvas.height / img.height);
                    zoom = 1;
                    offsetX = 0;
                    offsetY = 0;
                    zoomEl.value = '1';
                    zoomLbl.textContent = '1.0x';
                    drawCropCanvas();
                    modal.style.display = 'flex';
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    if (zoomEl) {
        zoomEl.addEventListener('input', function() {
            zoom = parseFloat(zoomEl.value) || 1;
            zoomLbl.textContent = zoom.toFixed(1) + 'x';
            drawCropCanvas();
        });
    }

    if (canvas) {
        canvas.addEventListener('pointerdown', function(e) {
            dragging = true;
            lastX = e.clientX;
            lastY = e.clientY;
            canvas.setPointerCapture(e.pointerId);
        });
        canvas.addEventListener('pointermove', function(e) {
            if (!dragging) return;
            offsetX += (e.clientX - lastX);
            offsetY += (e.clientY - lastY);
            lastX = e.clientX;
            lastY = e.clientY;
            drawCropCanvas();
        });
        canvas.addEventListener('pointerup', function() { dragging = false; });
        canvas.addEventListener('pointercancel', function() { dragging = false; });
    }

    window.closeAvatarCropModal = function() {
        modal.style.display = 'none';
        fileInput.value = '';
    };

    window.submitCroppedAvatar = function() {
        if (!img) return;
        var out = document.createElement('canvas');
        out.width = 256;
        out.height = 256;
        var octx = out.getContext('2d');
        var ratio = out.width / canvas.width;
        var scale = baseScale * zoom * ratio;
        var drawW = img.width * scale;
        var drawH = img.height * scale;
        var x = (out.width - drawW) / 2 + offsetX * ratio;
        var y = (out.height - drawH) / 2 + offsetY * ratio;
        octx.drawImage(img, x, y, drawW, drawH);

        document.getElementById('croppedAvatarInput').value = out.toDataURL('image/png');
        document.getElementById('avatarUploadForm').submit();
    };
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
