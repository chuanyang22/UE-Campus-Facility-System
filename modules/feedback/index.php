<?php
/**
 * Module 6: Facility Reviews & Average Ratings (Retrieve)
 * AMCS2194 Mini Project — Process 6.2
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$pageTitle = 'Module 6: User Feedback & Reviews';
$activeNav = 'feedback';

$service = new FeedbackService();

// Filters
$filters = [
    'facility_id' => $_GET['facility_id'] ?? '',
    'min_score'   => $_GET['min_score'] ?? '',
    'search'      => $_GET['search'] ?? '',
];

$reviews   = $service->getReviews($filters);
$summaries = $service->getFacilityRatingsSummary();

// Compute global stats
$totalReviews = count($reviews);
$globalAvgScore = 0;
if ($totalReviews > 0) {
    $scores = array_column($reviews, 'OverallScore');
    $globalAvgScore = round(array_sum($scores) / count($scores), 1);
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Module 6 Header Banner -->
<div class="card" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-color: #bfdbfe;">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="color: var(--secondary-color); margin-bottom: 0.35rem;">&#11088; Facility Quality & Feedback Management</h2>
            <p style="color: #334155; margin: 0; font-size: 0.95rem;">
                Student session ratings directly drive campus facility maintenance and service accountability.
            </p>
        </div>
        <a href="submit.php" class="btn btn-primary">+ Submit Session Rating (Process 6.1)</a>
    </div>
</div>

<!-- Aggregated Rating Cards per Facility -->
<h3 style="font-size: 1.15rem; color: var(--secondary-color); margin-bottom: 1rem;">&#127979; Facility Average Score Overview</h3>
<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <?php foreach ($summaries as $s): ?>
        <div class="stat-card stat-navy">
            <span class="stat-label"><?= htmlspecialchars($s['Category']) ?> &bull; <?= htmlspecialchars($s['LocationBlock']) ?></span>
            <strong style="font-size: 1.05rem; margin-top: 0.3rem;"><?= htmlspecialchars($s['FacilityName']) ?></strong>
            
            <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-top: 0.5rem;">
                <span class="stat-value" style="font-size: 1.6rem; color: #d97706;">
                    <?= $s['AvgOverallScore'] !== null ? htmlspecialchars((string)$s['AvgOverallScore']) . '★' : 'No ratings' ?>
                </span>
                <span class="stat-sub">(<?= $s['TotalReviews'] ?> reviews)</span>
            </div>

            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem; display: flex; justify-content: space-between;">
                <span>Cleanliness: <strong><?= $s['AvgCleanliness'] ?? 'N/A' ?>/5</strong></span>
                <span>Condition: <strong><?= $s['AvgCondition'] ?? 'N/A' ?>/5</strong></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Reviews Table Card (Process 6.2 Retrieve) -->
<div class="card">
    <div class="card-header">
        <h2><span>&#128172;</span> Detailed Review Log (Process 6.2)</h2>
        <span class="badge badge-medium"><?= count($reviews) ?> Review(s) Displayed</span>
    </div>

    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_search">Search Feedback</label>
                <input type="text" id="filter_search" name="search" class="form-control" placeholder="ID, reviewer, keywords..." value="<?= htmlspecialchars($filters['search']) ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_facility">Filter Facility</label>
                <select id="filter_facility" name="facility_id" class="form-select">
                    <option value="">All Facilities</option>
                    <?php foreach ($summaries as $fac): ?>
                        <option value="<?= htmlspecialchars($fac['FacilityID']) ?>" <?= $filters['facility_id'] === $fac['FacilityID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fac['FacilityName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="filter_score">Min Overall Score</label>
                <select id="filter_score" name="min_score" class="form-select">
                    <option value="">All Scores</option>
                    <option value="4.0" <?= $filters['min_score'] === '4.0' ? 'selected' : '' ?>>4.0+ Stars (High)</option>
                    <option value="3.0" <?= $filters['min_score'] === '3.0' ? 'selected' : '' ?>>3.0+ Stars (Satisfactory)</option>
                    <option value="2.0" <?= $filters['min_score'] === '2.0' ? 'selected' : '' ?>>2.0+ Stars</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                <a href="index.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <!-- Reviews Table -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Feedback ID</th>
                        <th>Facility & Booking</th>
                        <th>Reviewer</th>
                        <th>Ratings Breakdown</th>
                        <th>User Comments</th>
                        <th>Admin Official Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No facility reviews match the selected filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($r['FeedbackID']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($r['SubmissionDate']))) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($r['FacilityName']) ?></strong><br>
                                    <small class="text-muted">Booking: <?= htmlspecialchars($r['BookingID']) ?> (<?= htmlspecialchars($r['BookingDate']) ?>)</small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($r['ReviewerName']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($r['ReviewerRole']) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-completed" style="font-size: 0.85rem;">
                                        Overall: <?= htmlspecialchars((string)$r['OverallScore']) ?> / 5.0
                                    </span><br>
                                    <small style="color: var(--text-muted);">
                                        Cleanliness: <strong><?= htmlspecialchars((string)$r['CleanlinessRating']) ?>/5</strong> &bull;
                                        Condition: <strong><?= htmlspecialchars((string)$r['ConditionRating']) ?>/5</strong>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($r['UserComments'])): ?>
                                        <p style="margin: 0; font-size: 0.9rem;"><?= nl2br(htmlspecialchars($r['UserComments'])) ?></p>
                                    <?php else: ?>
                                        <em class="text-muted">No comment provided.</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['AdminRemarks'])): ?>
                                        <div style="background: #f1f5f9; border-left: 3px solid var(--secondary-color); padding: 0.4rem 0.6rem; border-radius: 4px; font-size: 0.85rem;">
                                            <strong>Facility Admin:</strong> <?= nl2br(htmlspecialchars($r['AdminRemarks'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <em class="text-muted">No admin remarks posted yet.</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem; flex-direction: column;">
                                        <a href="edit.php?id=<?= urlencode($r['FeedbackID']) ?>" class="btn btn-secondary btn-sm" title="Edit Review or Post Admin Reply (Process 6.3)">
                                            <?= AuthService::isAdmin() ? 'Admin Reply' : 'Edit Review' ?>
                                        </a>
                                        <?php if (AuthService::isAdmin()): ?>
                                            <a href="javascript:void(0)" onclick="confirmAction('Are you sure you want to permanently delete review <?= htmlspecialchars($r['FeedbackID']) ?>? This action is strictly reserved for administrative moderation.', 'delete.php?id=<?= urlencode($r['FeedbackID']) ?>')" class="btn btn-danger btn-sm" title="Moderate / Delete Review (Process 6.4)">
                                                Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
