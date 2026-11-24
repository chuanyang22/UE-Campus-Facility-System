<?php
/**
 * Module 6: Submit Session Feedback & Star Rating (Create)
 * AMCS2194 Mini Project — Process 6.1
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$pageTitle = 'Submit Session Feedback — Module 6';
$activeNav = 'submit_feedback';

$service     = new FeedbackService();
$currentUser = AuthService::getCurrentUser();

// Get completed bookings that can be reviewed
$availableBookings = $service->getCompletedBookingsWithoutFeedback(
    AuthService::isAdmin() ? null : $currentUser['UserID']
);

$errors = [];
$formData = [
    'BookingID'         => $_GET['booking_id'] ?? '',
    'CleanlinessRating' => '',
    'ConditionRating'   => '',
    'UserComments'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'BookingID'         => $_POST['BookingID'] ?? '',
        'CleanlinessRating' => $_POST['CleanlinessRating'] ?? '',
        'ConditionRating'   => $_POST['ConditionRating'] ?? '',
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

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2><span>&#11088;</span> Submit Session Feedback (Process 6.1)</h2>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Feedback Log</a>
    </div>

    <div class="card-body">
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
            Provide your honest evaluation of the campus facility condition and cleanliness following your completed booking session.
        </p>

        <?php if (empty($availableBookings) && empty($formData['BookingID'])): ?>
            <div class="alert alert-info">
                <div>
                    <strong>No Unreviewed Completed Bookings:</strong> You currently have no completed booking sessions awaiting review. Bookings must be marked 'Completed' before a review can be submitted (Business Rule constraint).
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="submit.php" novalidate>
            <!-- Target Booking -->
            <div class="form-group">
                <label for="BookingID">Completed Booking Session <span class="required-star">*</span></label>
                <select id="BookingID" name="BookingID" class="form-select <?= isset($errors['BookingID']) ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Select Completed Session --</option>
                    <?php foreach ($availableBookings as $b): ?>
                        <option value="<?= htmlspecialchars($b['BookingID']) ?>" <?= $formData['BookingID'] === $b['BookingID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['BookingID']) ?> — <?= htmlspecialchars($b['FacilityName']) ?> (<?= htmlspecialchars($b['BookingDate']) ?> <?= htmlspecialchars($b['StartTime']) ?>-<?= htmlspecialchars($b['EndTime']) ?>) [<?= htmlspecialchars($b['Purpose']) ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['BookingID'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['BookingID']) ?></div>
                <?php else: ?>
                    <span class="form-hint">Must belong to BOOKING table with status 'Completed' (Uniqueness & Business Rule Check)</span>
                <?php endif; ?>
            </div>

            <!-- Calculated Score Display -->
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.25rem; margin: 1.5rem 0; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="color: var(--secondary-color);">Real-Time Calculated Overall Score:</strong>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Computed dynamically as: <code>(Cleanliness + Condition) / 2</code></div>
                </div>
                <div>
                    <span id="calculatedScoreDisplay" class="badge badge-archived" style="font-size: 1.1rem; padding: 0.5rem 1rem;">Select both ratings</span>
                </div>
            </div>

            <div class="form-grid">
                <!-- Cleanliness Rating -->
                <div class="form-group">
                    <label>Cleanliness Rating (1 to 5 Stars) <span class="required-star">*</span></label>
                    <div class="star-rating-control">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="clean_<?= $i ?>" name="CleanlinessRating" value="<?= $i ?>" <?= (string)$formData['CleanlinessRating'] === (string)$i ? 'checked' : '' ?>>
                            <label for="clean_<?= $i ?>" title="<?= $i ?> Stars">&#9733;</label>
                        <?php endfor; ?>
                    </div>
                    <?php if (isset($errors['CleanlinessRating'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['CleanlinessRating']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">Range Check: Integer from 1 (Poor) to 5 (Pristine)</span>
                    <?php endif; ?>
                </div>

                <!-- Facility Condition Rating -->
                <div class="form-group">
                    <label>Facility Condition Rating (1 to 5 Stars) <span class="required-star">*</span></label>
                    <div class="star-rating-control">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="cond_<?= $i ?>" name="ConditionRating" value="<?= $i ?>" <?= (string)$formData['ConditionRating'] === (string)$i ? 'checked' : '' ?>>
                            <label for="cond_<?= $i ?>" title="<?= $i ?> Stars">&#9733;</label>
                        <?php endfor; ?>
                    </div>
                    <?php if (isset($errors['ConditionRating'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['ConditionRating']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">Range Check: Integer from 1 (Broken) to 5 (Excellent)</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments -->
            <div class="form-group">
                <label for="UserComments">User Comments & Constructive Remarks</label>
                <textarea id="UserComments" name="UserComments" rows="4" class="form-control <?= isset($errors['UserComments']) ? 'is-invalid' : '' ?>" placeholder="Share your experience regarding lighting, equipment condition, room temperature, cleanliness, or suggestions for improvement..." maxlength="1000"><?= htmlspecialchars($formData['UserComments']) ?></textarea>
                <?php if (isset($errors['UserComments'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['UserComments']) ?></div>
                <?php else: ?>
                    <span class="form-hint">Max 1000 characters (Size Check)</span>
                <?php endif; ?>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
