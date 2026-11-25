<?php
/**
 * Module 6: Facility Reviews & Average Rating Summary (Retrieve)
 * AMCS2194 Mini Project — Process 6.2
 * Matches UI Mockup UI 6.2 pixel-for-pixel
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$pageTitle = 'Facility Reviews & Average Rating Summary';
$activeNav = 'feedback';

$service = new FeedbackService();
$currentUser = AuthService::getCurrentUser();
$isAdmin = AuthService::isAdmin();

if (AuthService::isTechnician()) {
    header('Location: ../maintenance/index.php');
    exit;
}

// Determine filtered facility (default to FAC-04 to match mockup UI 6.2, or all)
$selectedFacilityId = $_GET['facility_id'] ?? 'FAC-04';

// Retrieve all reviews and facility summaries
$summaries = $service->getFacilityRatingsSummary();

// Prepare filter arguments
$filters = [];
if (!empty($selectedFacilityId) && $selectedFacilityId !== 'ALL') {
    $filters['facility_id'] = $selectedFacilityId;
}
$reviews = $service->getReviews($filters);

// Find metadata and aggregates for the selected facility (or All)
$activeSummary = null;
if ($selectedFacilityId === 'ALL') {
    $displayFacilityName = 'All Campus Facilities';
    $reviewCount = count($reviews);
    if ($reviewCount > 0) {
        $sum = array_sum(array_map(fn($r) => (float)$r['OverallScore'], $reviews));
        $avgOverall = rtrim(rtrim(number_format(round($sum / $reviewCount, 2), 2, '.', ''), '0'), '.');
    } else {
        $avgOverall = '0';
    }
} else {
    foreach ($summaries as $s) {
        if ($s['FacilityID'] === $selectedFacilityId) {
            $activeSummary = $s;
            break;
        }
    }

    $displayFacilityName = $activeSummary ? $activeSummary['FacilityName'] . ' (' . $activeSummary['LocationBlock'] . ')' : 'Discussion Room B2 (Library Block B)';
    $reviewCount = $activeSummary ? (int)$activeSummary['TotalReviews'] : 0;
    $avgOverall = $activeSummary && $activeSummary['AvgOverallScore'] !== null
        ? rtrim(rtrim(number_format((float)$activeSummary['AvgOverallScore'], 2, '.', ''), '0'), '.')
        : '0';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header Block -->
<div class="page-header-row">
    <div>
        <h1 class="page-title"><?= htmlspecialchars(t('fb_title')) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars(t('fb_sub')) ?></p>
    </div>

    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <!-- Facility Filter Dropdown -->
        <form method="GET" id="facilityFilterForm" style="margin: 0;">
            <select name="facility_id" class="form-select" onchange="this.form.submit()" style="font-weight: 600; min-width: 290px; cursor: pointer; padding: 0.6rem 0.85rem;">
                <option value="ALL" <?= $selectedFacilityId === 'ALL' ? 'selected' : '' ?>>Filter: All Campus Facilities</option>
                <?php foreach ($summaries as $fac): ?>
                    <option value="<?= htmlspecialchars($fac['FacilityID']) ?>" <?= $selectedFacilityId === $fac['FacilityID'] ? 'selected' : '' ?>>
                        Filter: <?= htmlspecialchars($fac['FacilityName']) ?> (<?= htmlspecialchars($fac['FacilityID']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($isAdmin): ?>
            <a href="edit.php" class="btn btn-outline-blue" style="padding: 0.55rem 1rem; font-size: 0.88rem;">
                <?= htmlspecialchars(t('btn_admin_mod')) ?>
            </a>
        <?php else: ?>
            <a href="completed.php" class="btn btn-primary-blue" style="padding: 0.55rem 1.1rem; font-size: 0.88rem;">
                <?= htmlspecialchars(t('btn_submit_feedback')) ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Highlight Summary Card matching UI 6.2 -->
<div class="facility-summary-box">
    <div>
        <div class="facility-summary-title"><?= htmlspecialchars($displayFacilityName) ?></div>
        <div style="color: #64748b; font-size: 0.88rem; margin-top: 0.15rem;">
            Aggregated from <?= $reviewCount ?> verified post-session reviews
        </div>
    </div>

    <div class="facility-summary-scores">
        <div class="score-col">
            <div class="score-num-blue"><?= htmlspecialchars((string)$avgOverall) ?> / 5</div>
            <div class="score-label"><?= htmlspecialchars(t('avg_rating_label')) ?></div>
        </div>
    </div>
</div>

<!-- Review Cards List matching UI 6.2 -->
<?php if (empty($reviews)): ?>
    <div class="ui-card" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
        <p style="font-size: 1.05rem; margin-bottom: 1rem;">No reviews have been submitted for this facility yet.</p>
        <a href="completed.php" class="btn btn-primary-blue">+ Be the first to submit a review</a>
    </div>
<?php else: ?>
    <?php foreach ($reviews as $rev): 
        $isOwnReview = ($rev['UserID'] === ($currentUser['UserID'] ?? ''));
        $overallVal = (float)$rev['OverallScore'];
        $submissionDateFormatted = date('d M Y', strtotime($rev['SubmissionDate']));
    ?>
        <div class="review-item-card">
            <!-- Review Header Row -->
            <div class="review-item-header">
                <div>
                    <span class="review-author">
                        <?= htmlspecialchars($rev['ReviewerName'] ?? 'Campus User') ?>
                        <?php if ($isOwnReview): ?>
                            <span style="color: var(--text-muted); font-weight: 600;"><?= htmlspecialchars(t('your_review_tag')) ?></span>
                        <?php endif; ?>
                        &bull; <?= htmlspecialchars($submissionDateFormatted) ?>
                    </span>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <span class="review-stars-text">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php
                                $starClass = '';
                                if ($overallVal >= $i) {
                                    $starClass = 'full';
                                } elseif ($overallVal >= $i - 0.5) {
                                    $starClass = 'half';
                                }
                            ?>
                            <span class="review-star-icon <?= $starClass ?>">&#9733;</span>
                        <?php endfor; ?>
                        (<?= rtrim(rtrim(number_format($overallVal, 2, '.', ''), '0'), '.') ?>/5)
                    </span>

                    <?php if ($isOwnReview): ?>
                        <a href="edit.php?id=<?= urlencode($rev['FeedbackID']) ?>" class="btn btn-cancel" style="padding: 0.35rem 0.85rem; font-size: 0.82rem; border-color: #cbd5e1; font-weight: 600;">
                            <?= htmlspecialchars(t('btn_edit_my_review')) ?>
                        </a>
                    <?php elseif ($isAdmin): ?>
                        <a href="edit.php?id=<?= urlencode($rev['FeedbackID']) ?>" class="btn btn-outline-blue" style="padding: 0.35rem 0.85rem; font-size: 0.82rem;">
                            <?= htmlspecialchars(t('btn_moderate_reply')) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Review Body Text -->
            <p style="margin: 0.5rem 0 0 0; color: #334155; font-size: 0.92rem; line-height: 1.55;">
                <?= nl2br(htmlspecialchars($rev['UserComments'] ?? 'No comments provided.')) ?>
            </p>

            <!-- Official Admin Reply Box if present -->
            <?php if (!empty($rev['AdminRemarks'])): ?>
                <div class="admin-reply-box">
                    <strong>Official Facility Admin Reply (Admin S0012):</strong> <?= htmlspecialchars($rev['AdminRemarks']) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
