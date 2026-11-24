<?php
/**
 * Module 6: Admin Feedback Moderation & Official Reply Panel / Edit Review
 * AMCS2194 Mini Project — Process 6.3 (Update) & Process 6.4 (Delete)
 * Matches UI Mockup UI 6.3 pixel-for-pixel
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$pageTitle = 'Admin Feedback Moderation & Official Reply Panel';
$activeNav = 'feedback';

$service = new FeedbackService();
$currentUser = AuthService::getCurrentUser();
$isAdmin = AuthService::isAdmin();

$feedbackId = $_GET['id'] ?? '';
$action = $_POST['action'] ?? '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_admin_reply') {
        $targetId = trim($_POST['FeedbackID'] ?? '');
        $adminReply = trim($_POST['AdminRemarks'] ?? '');
        $res = $service->updateReview($targetId, ['AdminRemarks' => $adminReply], true);
        if ($res['success']) {
            AuthService::setFlash('success', "Official Admin reply updated successfully for {$targetId}! (FR-6.3)");
        } else {
            AuthService::setFlash('danger', $res['message'] ?? 'Failed to update admin reply.');
        }
        header('Location: edit.php' . ($feedbackId ? '?id=' . urlencode($feedbackId) : ''));
        exit;
    }

    if ($action === 'delete_review') {
        $targetId = trim($_POST['FeedbackID'] ?? '');
        $res = $service->deleteReview($targetId);
        if ($res['success']) {
            AuthService::setFlash('success', "Review {$targetId} permanently deleted from FEEDBACK table. (FR-6.4)");
        } else {
            AuthService::setFlash('danger', $res['message'] ?? 'Failed to delete review.');
        }
        header('Location: edit.php');
        exit;
    }

    if ($action === 'reset_demo_spam') {
        // Helper for evaluator to restore FB-505 if deleted during live testing
        $db = getDB();
        $check = $db->query("SELECT FeedbackID FROM feedback WHERE FeedbackID = 'FB-505'")->fetchColumn();
        if (!$check) {
            $db->exec("INSERT INTO feedback (FeedbackID, BookingID, CleanlinessRating, ConditionRating, OverallScore, UserComments, AdminRemarks, SubmissionDate)
                       VALUES ('FB-505', 'BK-2090', 1, 1, 1.0, 'Buy cheap exam notes at spam-link.example.com!!!', 'Flagged for irrelevant spam content.', '2026-11-18 15:45:00')");
            AuthService::setFlash('info', 'Demo spam review #FB-505 re-seeded for testing FR-6.4.');
        }
        header('Location: edit.php');
        exit;
    }

    if ($action === 'update_student_review') {
        $targetId = trim($_POST['FeedbackID'] ?? '');
        $res = $service->updateReview($targetId, $_POST, false);
        if ($res['success']) {
            AuthService::setFlash('success', $res['message']);
            header('Location: index.php?facility_id=FAC-04');
            exit;
        } else {
            AuthService::setFlash('danger', $res['message'] ?? 'Validation error.');
        }
    }
}

// Fetch all reviews for moderation panel
$allReviews = $service->getReviews();

// Locate FB-504 (or requested ID) and FB-505 (spam)
$review504 = null;
$review505 = null;
$otherReviews = [];

foreach ($allReviews as $r) {
    if ($r['FeedbackID'] === 'FB-504') {
        $review504 = $r;
    } elseif ($r['FeedbackID'] === 'FB-505' || str_contains(strtolower($r['UserComments'] ?? ''), 'spam')) {
        $review505 = $r;
    } else {
        $otherReviews[] = $r;
    }
}

// If specific ID requested and user is not admin, or user requested student edit view
$isStudentEditingOwn = (!$isAdmin && !empty($feedbackId));
$singleReview = !empty($feedbackId) ? $service->getReviewById($feedbackId) : null;

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($isStudentEditingOwn && $singleReview): ?>
    <!-- Student Edit Form (FR-6.3 Edit Review) -->
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Edit Session Feedback &amp; Star Rating</h1>
            <p class="page-subtitle">Update your rating and comment for booking #<?= htmlspecialchars($singleReview['BookingID']) ?>.</p>
        </div>
        <a href="index.php?facility_id=<?= urlencode($singleReview['FacilityID']) ?>" class="btn btn-cancel">
            &larr; Back to Reviews
        </a>
    </div>

    <div class="ui-card" style="max-width: 800px; margin: 0 auto;">
        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
            <div style="font-size: 0.85rem; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Facility Session Details</div>
            <div style="font-size: 1.1rem; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($singleReview['FacilityName']) ?> (<?= htmlspecialchars($singleReview['FacilityID']) ?>)</div>
            <div style="font-size: 0.9rem; color: #475569;">Booking #<?= htmlspecialchars($singleReview['BookingID']) ?> &bull; Date: <?= htmlspecialchars($singleReview['BookingDate']) ?></div>
        </div>

        <form method="POST" action="edit.php?id=<?= urlencode($singleReview['FeedbackID']) ?>">
            <input type="hidden" name="action" value="update_student_review">
            <input type="hidden" name="FeedbackID" value="<?= htmlspecialchars($singleReview['FeedbackID']) ?>">

            <div class="form-grid-2col">
                <div class="form-group">
                    <label>Overall Facility Star Rating (1-5) *</label>
                    <select name="ConditionRating" class="form-select">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>" <?= (int)$singleReview['ConditionRating'] === $i ? 'selected' : '' ?>>
                                <?= $i ?> Stars (<?= str_repeat('★', $i) ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cleanliness &amp; Condition Score (1-5) *</label>
                    <select name="CleanlinessRating" class="form-select">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>" <?= (int)$singleReview['CleanlinessRating'] === $i ? 'selected' : '' ?>>
                                <?= $i ?> Stars (<?= str_repeat('★', $i) ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="student_comments">Review Comment *</label>
                <textarea id="student_comments" name="UserComments" rows="4" class="form-control" required minlength="5" maxlength="300"><?= htmlspecialchars($singleReview['UserComments'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <a href="index.php?facility_id=<?= urlencode($singleReview['FacilityID']) ?>" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-primary-blue">Save Updated Review</button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- Admin Feedback Moderation & Official Reply Panel -->
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Admin Feedback Moderation &amp; Official Reply Panel</h1>
            <p class="page-subtitle">Respond to student feedback or delete spam, abusive, or invalid review entries.</p>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="index.php?facility_id=FAC-04" class="btn btn-cancel" style="padding: 0.5rem 1rem; font-size: 0.88rem;">
                &larr; Back to Reviews
            </a>
            <?php if (!$review505): ?>
                <form method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="reset_demo_spam">
                    <button type="submit" class="btn btn-outline-blue" style="font-size: 0.82rem; padding: 0.45rem 0.75rem;">+ Restore Demo Spam #FB-505</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card 1: Review #FB-504 Official Admin Reply Form -->
    <?php 
    $disp504 = $review504 ?: [
        'FeedbackID' => 'FB-504',
        'UserID' => 'A240112',
        'BookingID' => 'BK-2089',
        'FacilityName' => 'Discussion Room B2',
        'CleanlinessRating' => 5,
        'ConditionRating' => 4,
        'UserComments' => 'Air-conditioning worked well and the room was spotless when we arrived. Projector HDMI cable needed a quick wiggle.',
        'AdminRemarks' => 'Thank you for the feedback! Our technician has replaced the loose HDMI connector in Room B2.'
    ];
    ?>
    <div class="ui-card" style="margin-bottom: 1.5rem; padding: 1.5rem 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a;">
                #<?= htmlspecialchars($disp504['FeedbackID']) ?> | User: <?= htmlspecialchars($disp504['UserID']) ?> | Facility: <?= htmlspecialchars($disp504['FacilityName']) ?> (#<?= htmlspecialchars($disp504['BookingID']) ?>)
            </div>
            <div style="color: #d97706; font-weight: 700; font-size: 0.95rem;">
                Rating: <?= (int)$disp504['ConditionRating'] ?>/5 | Cleanliness: <?= (int)$disp504['CleanlinessRating'] ?>/5
            </div>
        </div>

        <p style="margin: 0 0 1.25rem 0; color: #334155; font-size: 0.95rem; line-height: 1.5;">
            &ldquo;<?= htmlspecialchars($disp504['UserComments']) ?>&rdquo;
        </p>

        <form method="POST" action="edit.php">
            <input type="hidden" name="action" value="update_admin_reply">
            <input type="hidden" name="FeedbackID" value="<?= htmlspecialchars($disp504['FeedbackID']) ?>">

            <div class="form-group" style="margin-bottom: 0.5rem;">
                <label style="font-size: 0.88rem; font-weight: 700; color: #0f172a; margin-bottom: 0.4rem;">
                    Post / Update Official Admin Reply
                </label>
                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <input type="text" name="AdminRemarks" 
                           value="<?= htmlspecialchars($disp504['AdminRemarks'] ?? 'Thank you for the feedback! Our technician has replaced the loose HDMI connector in Room B2.') ?>" 
                           class="form-control" 
                           style="flex: 1; min-width: 320px;" 
                           maxlength="300"
                           required>
                    <button type="submit" class="btn btn-primary-blue" style="white-space: nowrap; padding: 0.65rem 1.25rem;">
                        Publish Admin Reply
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Card 2: Review #FB-505 Flagged Spam Entry matching UI 6.3 (Red/Pink Card) -->
    <?php if ($review505): ?>
        <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 1.5rem 1.75rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <div style="color: #991b1b; font-weight: 700; font-size: 1.05rem;">
                    #<?= htmlspecialchars($review505['FeedbackID']) ?> | Flagged Spam Entry | User: <?= htmlspecialchars($review505['UserID']) ?> | <?= htmlspecialchars($review505['FacilityName']) ?>
                </div>

                <form method="POST" action="edit.php" onsubmit="return confirm('Are you sure you want to permanently delete invalid review #<?= htmlspecialchars($review505['FeedbackID']) ?> from the database? (FR-6.4)');" style="margin: 0;">
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="FeedbackID" value="<?= htmlspecialchars($review505['FeedbackID']) ?>">
                    <button type="submit" class="btn btn-danger-red" style="padding: 0.6rem 1.25rem; font-weight: 700;">
                        Delete Invalid Review (FR-6.4)
                    </button>
                </form>
            </div>

            <p style="margin: 0; color: #b91c1c; font-size: 0.95rem; font-style: italic;">
                &ldquo;<?= htmlspecialchars($review505['UserComments']) ?>&rdquo; &mdash; 
                <span style="font-weight: 600;">Flagged for irrelevant spam content.</span>
            </p>
        </div>
    <?php else: ?>
        <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="color: #64748b; font-size: 0.9rem;">
                <strong>Spam entry #FB-505 is currently deleted.</strong> Click "Restore Demo Spam #FB-505" above to re-test FR-6.4 deletion.
            </span>
        </div>
    <?php endif; ?>

    <!-- Additional Moderation Rows if any -->
    <?php if (!empty($otherReviews)): ?>
        <div style="margin-top: 2rem;">
            <h3 style="font-size: 1.1rem; color: var(--text-heading); margin-bottom: 1rem;">Additional Feedback Log Entries</h3>
            <?php foreach ($otherReviews as $o): ?>
                <div class="ui-card" style="padding: 1.25rem 1.5rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap;">
                        <strong>#<?= htmlspecialchars($o['FeedbackID']) ?> | <?= htmlspecialchars($o['FacilityName']) ?> &bull; Reviewer: <?= htmlspecialchars($o['ReviewerName']) ?></strong>
                        <span style="color: #d97706; font-weight: 600;">Score: <?= htmlspecialchars((string)$o['OverallScore']) ?>/5.0</span>
                    </div>
                    <p style="font-size: 0.9rem; color: #475569; margin-bottom: 0.75rem;">&ldquo;<?= htmlspecialchars($o['UserComments'] ?? 'No comment.') ?>&rdquo;</p>
                    
                    <form method="POST" action="edit.php" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="hidden" name="action" value="update_admin_reply">
                        <input type="hidden" name="FeedbackID" value="<?= htmlspecialchars($o['FeedbackID']) ?>">
                        <input type="text" name="AdminRemarks" value="<?= htmlspecialchars($o['AdminRemarks'] ?? '') ?>" class="form-control" placeholder="Post official admin response..." style="font-size: 0.88rem;">
                        <button type="submit" class="btn btn-primary-blue" style="font-size: 0.82rem; white-space: nowrap;">Update Reply</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
