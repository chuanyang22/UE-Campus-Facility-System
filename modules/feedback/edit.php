<?php
/**
 * Module 6: Edit Review / Post Admin Reply (Update)
 * AMCS2194 Mini Project — Process 6.3
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$pageTitle = 'Edit Review / Post Reply — Module 6';
$activeNav = 'feedback';

$service    = new FeedbackService();
$feedbackId = $_GET['id'] ?? '';
$isAdmin    = AuthService::isAdmin();

$review = $service->getReviewById($feedbackId);
if (!$review) {
    AuthService::setFlash('danger', 'Review not found.');
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $service->updateReview($feedbackId, $_POST, $isAdmin);

    if ($result['success']) {
        AuthService::setFlash('success', $result['message']);
        header('Location: index.php');
        exit;
    } else {
        $errors = $result['errors'] ?? [];
        AuthService::setFlash('danger', $result['message']);
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2>
            <span>&#128394;</span> <?= $isAdmin ? 'Post Admin Official Reply' : 'Edit Review' ?>: <?= htmlspecialchars($review['FeedbackID']) ?> (Process 6.3)
        </h2>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Feedback Log</a>
    </div>

    <div class="card-body">
        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Facility & Session</span><br>
                    <strong><?= htmlspecialchars($review['FacilityName']) ?></strong><br>
                    <small class="text-muted"><?= htmlspecialchars($review['LocationBlock']) ?> &bull; Booking: <?= htmlspecialchars($review['BookingID']) ?></small>
                </div>
                <div>
                    <span class="text-muted" style="font-size: 0.8rem; text-transform: uppercase;">Reviewer</span><br>
                    <strong><?= htmlspecialchars($review['ReviewerName']) ?></strong> (<?= htmlspecialchars($review['ReviewerRole']) ?>)<br>
                    <small class="text-muted"><?= htmlspecialchars($review['SubmissionDate']) ?></small>
                </div>
            </div>
        </div>

        <form method="POST" action="edit.php?id=<?= urlencode($review['FeedbackID']) ?>" novalidate>
            <?php if ($isAdmin): ?>
                <!-- Admin replying mode -->
                <div class="alert alert-info">
                    You are logged in as <strong>Facility Administrator</strong>. You can post an official administrative response or resolution status to this feedback.
                </div>

                <div class="form-group">
                    <label>Existing User Comments</label>
                    <div style="padding: 0.85rem; background: #f1f5f9; border-radius: 6px; font-size: 0.95rem;">
                        <?= nl2br(htmlspecialchars($review['UserComments'] ?? 'No comments provided.')) ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="AdminRemarks">Official Admin Remarks / Action Taken <span class="required-star">*</span></label>
                    <textarea id="AdminRemarks" name="AdminRemarks" rows="4" class="form-control <?= isset($errors['AdminRemarks']) ? 'is-invalid' : '' ?>" placeholder="e.g. Issue acknowledged. Dispatched maintenance technician under MT-5002." maxlength="300"><?= htmlspecialchars($_POST['AdminRemarks'] ?? $review['AdminRemarks'] ?? '') ?></textarea>
                    <?php if (isset($errors['AdminRemarks'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['AdminRemarks']) ?></div>
                    <?php else: ?>
                        <span class="form-hint">Max 300 characters matching database field size (Size Check)</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Student editing own rating/comments mode -->
                <div class="form-grid">
                    <div class="form-group">
                        <label>Cleanliness Rating (1 to 5 Stars) <span class="required-star">*</span></label>
                        <div class="star-rating-control">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="clean_<?= $i ?>" name="CleanlinessRating" value="<?= $i ?>" <?= (int)($_POST['CleanlinessRating'] ?? $review['CleanlinessRating']) === $i ? 'checked' : '' ?>>
                                <label for="clean_<?= $i ?>" title="<?= $i ?> Stars">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Facility Condition Rating (1 to 5 Stars) <span class="required-star">*</span></label>
                        <div class="star-rating-control">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="cond_<?= $i ?>" name="ConditionRating" value="<?= $i ?>" <?= (int)($_POST['ConditionRating'] ?? $review['ConditionRating']) === $i ? 'checked' : '' ?>>
                                <label for="cond_<?= $i ?>" title="<?= $i ?> Stars">&#9733;</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="UserComments">User Comments</label>
                    <textarea id="UserComments" name="UserComments" rows="4" class="form-control <?= isset($errors['UserComments']) ? 'is-invalid' : '' ?>" maxlength="1000"><?= htmlspecialchars($_POST['UserComments'] ?? $review['UserComments'] ?? '') ?></textarea>
                    <?php if (isset($errors['UserComments'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['UserComments']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
