<?php
/**
 * Completed Sessions Dashboard — Rate & Review
 * AMCS2194 Mini Project — Process 6.1 Entry View
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$pageTitle = 'Completed Sessions — Rate & Review';
$activeNav = 'feedback';

$service     = new FeedbackService();
$currentUser = AuthService::getCurrentUser();
$isAdmin     = AuthService::isAdmin();

if (AuthService::isTechnician()) {
    header('Location: ../maintenance/index.php');
    exit;
}

$completedBookings = $service->getCompletedBookingsWithFeedbackStatus(
    $isAdmin ? null : $currentUser['UserID']
);

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Header Block -->
<div class="page-header-row">
    <div>
        <h1 class="page-title"><?= htmlspecialchars(t('comp_title')) ?></h1>
        <p class="page-subtitle"><?= htmlspecialchars(t('comp_sub')) ?></p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <a href="index.php" class="btn btn-outline-blue" style="padding: 0.55rem 1rem; font-size: 0.88rem;">
            <?= htmlspecialchars(t('btn_view_all_reviews')) ?>
        </a>
        <a href="../../index.php" class="btn btn-cancel" style="padding: 0.55rem 1rem; font-size: 0.88rem;">
            <?= htmlspecialchars(t('btn_back_home')) ?>
        </a>
    </div>
</div>

<?php if (empty($completedBookings)): ?>
    <div class="ui-card" style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
        <p style="font-size: 1.05rem; margin-bottom: 1rem;"><?= htmlspecialchars(t('comp_empty')) ?></p>
        <a href="../../index.php" class="btn btn-primary-blue"><?= htmlspecialchars(t('comp_browse')) ?></a>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem;">
        <?php foreach ($completedBookings as $b): ?>
            <?php
                $hasReview = !empty($b['FeedbackID']);
                $scoreLabel = $hasReview ? rtrim(rtrim(number_format((float)$b['OverallScore'], 2, '.', ''), '0'), '.') : null;
            ?>
            <div class="ui-card" style="padding: 1.35rem; margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; border-radius: 12px;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.4px;">
                                <?= htmlspecialchars(t('lbl_booking')) ?> #<?= htmlspecialchars($b['BookingID']) ?>
                            </div>
                            <div style="font-size: 1.15rem; font-weight: 700; color: var(--text-heading); margin-top: 0.15rem;">
                                <?= htmlspecialchars($b['FacilityName']) ?>
                            </div>
                        </div>
                        <span class="pill-badge pill-resolved"><?= htmlspecialchars(t('status_completed')) ?></span>
                    </div>

                    <div style="font-size: 0.86rem; color: var(--text-body); margin-bottom: 1rem; line-height: 1.6;">
                        <div>&#128205; <?= htmlspecialchars(t('lbl_location')) ?> <?= htmlspecialchars($b['LocationBlock']) ?></div>
                        <div>&#128197; <?= htmlspecialchars(t('lbl_date')) ?> <?= htmlspecialchars(date('D, d M Y', strtotime($b['BookingDate']))) ?></div>
                        <div>&#128338; <?= htmlspecialchars(t('lbl_time')) ?> <?= htmlspecialchars(substr($b['StartTime'], 0, 5)) ?> &ndash; <?= htmlspecialchars(substr($b['EndTime'], 0, 5)) ?></div>
                        <div style="color: var(--text-muted); margin-top: 0.35rem;"><?= htmlspecialchars(t('lbl_purpose')) ?> <?= htmlspecialchars($b['Purpose']) ?></div>
                    </div>

                    <?php if ($hasReview): ?>
                        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 6px; padding: 0.45rem 0.7rem; font-size: 0.82rem; font-weight: 600; margin-bottom: 0.9rem;">
                            &#10003; <?= htmlspecialchars(t('comp_reviewed')) ?> (<?= htmlspecialchars($scoreLabel) ?> / 5)
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 0.55rem; align-items: center;">
                    <?php if ($hasReview): ?>
                        <a href="edit.php?id=<?= urlencode($b['FeedbackID']) ?>&amp;return_to=completed" class="btn btn-outline-blue" style="flex: 1; padding: 0.55rem; font-size: 0.85rem;">
                            <?= htmlspecialchars(t('btn_edit_my_review')) ?>
                        </a>
                    <?php else: ?>
                        <a href="submit.php?booking_id=<?= urlencode($b['BookingID']) ?>" class="btn btn-primary-blue" style="flex: 1; padding: 0.55rem; font-size: 0.85rem;">
                            &#9997; <?= htmlspecialchars(t('btn_rate_review')) ?>
                        </a>
                    <?php endif; ?>
                    <a href="../maintenance/report.php?facility_id=<?= urlencode($b['FacilityID']) ?>" class="btn btn-cancel" style="padding: 0.55rem 0.8rem; font-size: 0.82rem;">
                        <?= htmlspecialchars(t('btn_report_short')) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
