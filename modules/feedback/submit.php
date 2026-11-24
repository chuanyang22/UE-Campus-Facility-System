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
$currentUser = AuthService::getCurrentUser();

$availableBookings = $service->getCompletedBookingsWithoutFeedback(
    AuthService::isAdmin() ? null : $currentUser['UserID']
);

$errors = [];
$formData = [
    'BookingID'         => 'BK-2089',
    'OverallRating'     => 4,
    'CleanlinessRating' => 5,
    'UserComments'      => 'Air-conditioning worked well and the room was spotless when we arrived. Projector HDMI cable needed a quick wiggle, otherwise great study space!',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'BookingID'         => $_POST['BookingID'] ?? '',
        'ConditionRating'   => (int)($_POST['OverallRating'] ?? 4),
        'CleanlinessRating' => (int)($_POST['CleanlinessRating'] ?? 5),
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
        <h1 class="page-title">Submit Session Feedback &amp; Star Rating</h1>
        <p class="page-subtitle">Rate your completed facility booking experience to help improve campus spaces.</p>
    </div>
</div>

<div class="ui-card">
    <form method="POST" action="submit.php" novalidate>

        <!-- Completed Booking Session Selector -->
        <div class="form-group">
            <label for="BookingID">Select Completed Booking Session *</label>
            <select id="BookingID" name="BookingID" class="form-select <?= isset($errors['BookingID']) ? 'is-invalid' : '' ?>" required>
                <option value="BK-2089" selected>#BK-2089 — Discussion Room B2 (18 Nov 2026, 10:00–12:00) [Checked-In]</option>
                <?php foreach ($availableBookings as $b): ?>
                    <?php if ($b['BookingID'] !== 'BK-2089'): ?>
                        <option value="<?= htmlspecialchars($b['BookingID']) ?>">
                            #<?= htmlspecialchars($b['BookingID']) ?> — <?= htmlspecialchars($b['FacilityName']) ?> (<?= htmlspecialchars($b['BookingDate']) ?>, <?= htmlspecialchars($b['StartTime']) ?>–<?= htmlspecialchars($b['EndTime']) ?>) [Checked-In]
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['BookingID'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['BookingID']) ?></div>
            <?php endif; ?>
        </div>

        <!-- 2 Side-by-Side Rating Cards -->
        <div class="form-grid-2col" style="margin-bottom: 1.25rem;">
            
            <!-- Overall Facility Star Rating (1-5) -->
            <div class="rating-card-box">
                <label style="font-size: 0.88rem; font-weight: 700; color: var(--text-heading); display: block; margin-bottom: 0.75rem;">
                    Overall Facility Star Rating *
                </label>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="stars-interactive" id="overallStarsGroup">
                        <span class="star-span active" data-val="1">&#9733;</span>
                        <span class="star-span active" data-val="2">&#9733;</span>
                        <span class="star-span active" data-val="3">&#9733;</span>
                        <span class="star-span active" data-val="4">&#9733;</span>
                        <span class="star-span" data-val="5">&#9733;</span>
                    </div>
                    <strong id="overallText" style="font-size: 0.95rem; color: #1e293b;">(4 / 5)</strong>
                    <input type="hidden" name="OverallRating" id="OverallRatingInput" value="4">
                </div>
            </div>

            <!-- Cleanliness & Condition Score (1-5) -->
            <div class="rating-card-box">
                <label style="font-size: 0.88rem; font-weight: 700; color: var(--text-heading); display: block; margin-bottom: 0.75rem;">
                    Cleanliness &amp; Condition Score *
                </label>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="stars-interactive" id="cleanlinessStarsGroup">
                        <span class="star-span active" data-val="1">&#9733;</span>
                        <span class="star-span active" data-val="2">&#9733;</span>
                        <span class="star-span active" data-val="3">&#9733;</span>
                        <span class="star-span active" data-val="4">&#9733;</span>
                        <span class="star-span active" data-val="5">&#9733;</span>
                    </div>
                    <strong id="cleanlinessText" style="font-size: 0.95rem; color: #1e293b;">(5 / 5)</strong>
                    <input type="hidden" name="CleanlinessRating" id="CleanlinessRatingInput" value="5">
                </div>
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
            <a href="index.php" class="btn btn-cancel">Cancel</a>
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
    function setupStarRating(groupEl, textEl, inputEl) {
        const stars = groupEl.querySelectorAll('.star-span');
        
        stars.forEach(function(star) {
            star.addEventListener('click', function() {
                const val = parseInt(this.getAttribute('data-val'), 10);
                inputEl.value = val;
                textEl.textContent = '(' + val + ' / 5)';
                
                stars.forEach(function(s) {
                    const sVal = parseInt(s.getAttribute('data-val'), 10);
                    if (sVal <= val) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
    }

    setupStarRating(
        document.getElementById('overallStarsGroup'),
        document.getElementById('overallText'),
        document.getElementById('OverallRatingInput')
    );

    setupStarRating(
        document.getElementById('cleanlinessStarsGroup'),
        document.getElementById('cleanlinessText'),
        document.getElementById('CleanlinessRatingInput')
    );
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
