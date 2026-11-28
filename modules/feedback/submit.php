<?php
/**
 * Submit Session Feedback & Star Rating
 * AMCS2194 Mini Project — Process 6.1 (Create)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$pageTitle = 'Submit Session Feedback & Star Rating';
$activeNav = 'feedback';

$service     = new FeedbackService();
AuthService::requireLogin($_SERVER['REQUEST_URI'] ?? '');
$currentUser = AuthService::getCurrentUser();

if (AuthService::isTechnician()) {
    header('Location: ../maintenance/index.php');
    exit;
}

$selectedBookingId = trim((string)($_GET['booking_id'] ?? $_POST['BookingID'] ?? ''));

// Require selecting a completed session from the Completed Sessions dashboard first
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $selectedBookingId === '') {
    header('Location: completed.php');
    exit;
}

$availableBookings = $service->getCompletedBookingsWithoutFeedback(
    AuthService::isAdmin() ? null : $currentUser['UserID']
);

$errors = [];
$formData = [
    'BookingID'         => $selectedBookingId,
    'OverallRating'     => 4.0,
    'CleanlinessRating' => 4.0,
    'UserComments'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ratingVal = (float)($_POST['OverallRating'] ?? 4);
    $formData = [
        'BookingID'         => $_POST['BookingID'] ?? '',
        'ConditionRating'   => $ratingVal,
        'CleanlinessRating' => $ratingVal,
        'UserComments'      => $_POST['UserComments'] ?? '',
    ];

    $result = $service->submitFeedback($formData);

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

<!-- Header Block -->
<div class="page-header-row">
    <div>
        <h1 class="page-title"><?= htmlspecialchars(t('sub_fb_title')) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars(t('sub_fb_sub')) ?></p>
    </div>
</div>

<div class="ui-card">
    <form method="POST" action="submit.php" novalidate>

        <!-- Completed Booking Session Selector -->
        <div class="form-group">
            <label for="BookingID">Completed Booking Session *</label>
            <select id="BookingID" name="BookingID" class="form-select <?= isset($errors['BookingID']) ? 'is-invalid' : '' ?>" required>
                <?php if (empty($availableBookings)): ?>
                    <option value="">-- No pending completed sessions to review --</option>
                <?php else: ?>
                    <?php foreach ($availableBookings as $b): ?>
                        <option value="<?= htmlspecialchars($b['BookingID']) ?>" <?= $formData['BookingID'] === $b['BookingID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['FacilityName']) ?> (<?= htmlspecialchars(date('d M Y', strtotime($b['BookingDate']))) ?>, <?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?>–<?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <?php if (isset($errors['BookingID'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['BookingID']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Single Facility Star Rating Card (0.5 intervals) -->
        <div class="rating-card-box" style="margin-bottom: 1.25rem;">
            <label style="font-size: 0.88rem; font-weight: 700; color: var(--text-heading); display: block; margin-bottom: 0.75rem;">
                Facility Star Rating *
            </label>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="stars-interactive" id="overallStarsGroup">
                    <span class="star-span active" data-val="1">&#9733;</span>
                    <span class="star-span active" data-val="2">&#9733;</span>
                    <span class="star-span active" data-val="3">&#9733;</span>
                    <span class="star-span active" data-val="4">&#9733;</span>
                    <span class="star-span" data-val="5">&#9733;</span>
                </div>
                <strong id="overallText" style="font-size: 0.95rem; color: #1e293b;">(4.0 / 5)</strong>
                <input type="hidden" name="OverallRating" id="OverallRatingInput" value="4.0">
            </div>
        </div>

        <!-- Review Comment with Dynamic Character Counter -->
        <div class="form-group">
            <label for="UserComments">Review Comment *</label>
            <textarea id="UserComments" name="UserComments" rows="3" class="form-control <?= isset($errors['UserComments']) ? 'is-invalid' : '' ?>" placeholder="Share your experience regarding facility cleanliness, equipment condition, or temperature..." maxlength="300" required><?= htmlspecialchars($formData['UserComments']) ?></textarea>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.35rem;">
                <div>
                    <?php if (isset($errors['UserComments'])): ?>
                        <div class="invalid-feedback" style="margin: 0;"><?= htmlspecialchars($errors['UserComments']) ?></div>
                    <?php endif; ?>
                </div>
                <div style="text-align: right; margin-left: auto;">
                    <span id="feedbackCharCounter" style="font-size: 0.82rem; font-weight: 600; color: #64748b;">0 / 300</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
            <a href="completed.php" class="btn btn-cancel">Cancel</a>
            <button type="submit" class="btn btn-primary-blue">Submit Feedback</button>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic character counter for review comments
    const commentArea = document.getElementById('UserComments');
    const commentCounter = document.getElementById('feedbackCharCounter');
    
    function updateCommentCounter() {
        if (!commentArea || !commentCounter) return;
        const len = commentArea.value.length;
        commentCounter.textContent = len + ' / 300';

        if (len === 0) {
            commentCounter.style.color = '#64748b'; // Neutral gray when empty
        } else if (len < 5) {
            commentCounter.style.color = '#64748b'; // Neutral gray until minimum 5 reached
        } else if (len >= 5 && len < 260) {
            commentCounter.style.color = '#10b981'; // Green for normal valid length
        } else if (len >= 260 && len <= 285) {
            commentCounter.style.color = '#d97706'; // Amber warning from 260 to 285
        } else {
            commentCounter.style.color = '#dc2626'; // Red from 286 to 300
        }
    }

    updateCommentCounter();
    commentArea.addEventListener('input', updateCommentCounter);
    function renderStars(stars, val) {
        stars.forEach(function(s) {
            const sVal = parseInt(s.getAttribute('data-val'), 10);
            s.classList.remove('active', 'half');
            if (val >= sVal) {
                s.classList.add('active');
            } else if (val >= sVal - 0.5) {
                s.classList.add('half');
            }
        });
    }

    function setupStarRating(groupEl, textEl, inputEl) {
        const stars = groupEl.querySelectorAll('.star-span');
        let currentVal = parseFloat(inputEl.value) || 4;
        renderStars(stars, currentVal);

        function getHoverVal(star, event) {
            const rect = star.getBoundingClientRect();
            const isLeftHalf = (event.clientX - rect.left) < (rect.width / 2);
            const baseVal = parseInt(star.getAttribute('data-val'), 10);
            return isLeftHalf ? Math.max(1, baseVal - 0.5) : baseVal;
        }

        stars.forEach(function(star) {
            star.addEventListener('mousemove', function(e) {
                const previewVal = getHoverVal(this, e);
                renderStars(stars, previewVal);
                textEl.textContent = '(' + previewVal.toFixed(1) + ' / 5)';
            });

            star.addEventListener('click', function(e) {
                currentVal = getHoverVal(this, e);
                inputEl.value = currentVal.toFixed(1);
                textEl.textContent = '(' + currentVal.toFixed(1) + ' / 5)';
                renderStars(stars, currentVal);
            });
        });

        groupEl.addEventListener('mouseleave', function() {
            renderStars(stars, currentVal);
            textEl.textContent = '(' + currentVal.toFixed(1) + ' / 5)';
        });
    }

    setupStarRating(
        document.getElementById('overallStarsGroup'),
        document.getElementById('overallText'),
        document.getElementById('OverallRatingInput')
    );
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
