<?php
/**
 * Main Home Page / Unified Portal Dashboard
 * AMCS2194 Mini Project — UE Campus Facility Booking System
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/services/MaintenanceService.php';
require_once __DIR__ . '/services/FeedbackService.php';

$pageTitle = 'Home — UE Campus Facility Booking System';
$activeNav = 'home';

$maintService    = new MaintenanceService();
$feedbackService = new FeedbackService();

$maintStats   = $maintService->getStatistics();
$recentTickets = $maintService->getTickets();
$recentTickets = array_slice($recentTickets, 0, 4);

$facilityRatings = $feedbackService->getFacilityRatingsSummary();
$recentReviews   = $feedbackService->getReviews();
$recentReviews   = array_slice($recentReviews, 0, 3);

$currentUser = AuthService::getCurrentUser();
$isAdmin = AuthService::isAdmin();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Portal Header Banner -->
<div class="ui-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 2.25rem 2.5rem; margin-bottom: 2rem; border-color: #334155;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div style="max-width: 780px;">
            <div style="display: inline-block; background: rgba(56, 189, 248, 0.15); color: #38bdf8; font-weight: 700; font-size: 0.8rem; padding: 0.25rem 0.75rem; border-radius: 9999px; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                AMCS2194 Mini Project &bull; Three-Tier Web Prototype
            </div>
            <h1 style="font-size: 2.1rem; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; margin-bottom: 0.6rem;">
                UE Campus Facility Booking System
            </h1>
            <p style="color: #94a3b8; font-size: 1rem; line-height: 1.6; margin: 0;">
                Centralized management for campus facility maintenance (Module 5) with automated hazard locking, and verified student feedback loops (Module 6) for facility quality and admin moderation.
            </p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.6rem; min-width: 240px;">
            <a href="modules/maintenance/report.php" class="btn btn-primary-blue" style="padding: 0.75rem 1.25rem; font-weight: 700;">
                + Report Defect (UI 5.1)
            </a>
            <a href="modules/maintenance/index.php" class="btn btn-cancel" style="padding: 0.65rem 1.25rem; font-weight: 600;">
                Maintenance Ticket Log (UI 5.2)
            </a>
            <a href="modules/feedback/index.php?facility_id=FAC-04" class="btn btn-cancel" style="padding: 0.65rem 1.25rem; font-weight: 600;">
                Facility Reviews (UI 6.2)
            </a>
        </div>
    </div>
</div>

<!-- Evaluation & Demonstration Quick Switcher Notice -->
<div class="alert-banner-amber" style="margin-top: 0; margin-bottom: 2rem;">
    <span style="font-size: 1.25rem;">&#128161;</span>
    <div style="flex: 1;">
        <strong>Demonstration Guide for Evaluators:</strong>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.88rem; color: #92400e;">
            Currently active session: <strong><?= htmlspecialchars($currentUser['name']) ?> (<?= htmlspecialchars($currentUser['id']) ?> &mdash; <?= htmlspecialchars($currentUser['role']) ?>)</strong>. 
            Use the <strong>[ Switch Demo Role ]</strong> dropdown in the top-right navigation bar to seamlessly switch between Student persona <code>A240112</code> and Facility Admin <code>S0012</code> without re-logging in.
        </p>
    </div>
</div>

<!-- Stat Grid matching UI 5.2 summary metrics -->
<div class="stat-row-4">
    <div class="stat-box">
        <div class="stat-box-title">Total Active Tickets</div>
        <div class="stat-box-num num-black"><?= $maintStats['total_active'] ?></div>
    </div>

    <div class="stat-box">
        <div class="stat-box-title">Critical / Locked Venues (FR-5.5)</div>
        <div class="stat-box-num num-red"><?= $maintStats['locked_venues'] ?> Venues</div>
    </div>

    <div class="stat-box">
        <div class="stat-box-title">In Progress Repairs</div>
        <div class="stat-box-num num-amber"><?= $maintStats['in_progress'] ?></div>
    </div>

    <div class="stat-box">
        <div class="stat-box-title">Resolved This Month</div>
        <div class="stat-box-num num-green"><?= $maintStats['resolved_month'] ?></div>
    </div>
</div>

<!-- Two Main Module Dashboard Columns -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 1.75rem; margin-top: 1.5rem;">
    
    <!-- Module 5 Card -->
    <div class="ui-card" style="padding: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <div>
                <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a;">
                    Module 5: Facility Maintenance &amp; Damage Reporting
                </h2>
                <span style="font-size: 0.82rem; color: #64748b;">CRUD (5.1&ndash;5.4) &bull; Automated Venue Locking (5.5)</span>
            </div>
            <a href="modules/maintenance/index.php" class="btn btn-outline-blue" style="font-size: 0.82rem;">View Full Log &rarr;</a>
        </div>

        <p style="font-size: 0.9rem; color: #475569; margin-bottom: 1rem;">
            Recent defect tickets and technician assignments:
        </p>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
            <?php foreach ($recentTickets as $t): 
                $urgencyClass = match(strtolower($t['UrgencyLevel'])) {
                    'critical' => 'pill-critical',
                    'high' => 'pill-high',
                    'medium' => 'pill-medium',
                    default => 'pill-low'
                };
            ?>
                <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; background: #ffffff;">
                    <div>
                        <strong style="color: #0f172a;">#<?= htmlspecialchars($t['MaintenanceID']) ?></strong> &mdash; 
                        <?= htmlspecialchars($t['FacilityName']) ?><br>
                        <small style="color: #64748b; font-size: 0.82rem;"><?= htmlspecialchars($t['DefectCategory'] ?? $t['IssueTitle']) ?> &bull; Tech: <?= htmlspecialchars($t['AssignedTechnician'] ?? 'Unassigned') ?></small>
                    </div>
                    <div style="text-align: right;">
                        <span class="pill-badge <?= $urgencyClass ?>"><?= htmlspecialchars($t['UrgencyLevel']) ?></span><br>
                        <a href="modules/maintenance/update.php?id=<?= urlencode($t['MaintenanceID']) ?>" style="font-size: 0.8rem; color: #2563eb; text-decoration: none; font-weight: 600;">Manage &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <a href="modules/maintenance/report.php" class="btn btn-primary-blue" style="flex: 1; font-size: 0.85rem;">+ Log Defect (UI 5.1)</a>
            <a href="modules/maintenance/update.php?id=MT-1042" class="btn btn-cancel" style="flex: 1; font-size: 0.85rem;">Update Repair (UI 5.3)</a>
        </div>
    </div>

    <!-- Module 6 Card -->
    <div class="ui-card" style="padding: 1.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <div>
                <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a;">
                    Module 6: User Feedback &amp; Review Management
                </h2>
                <span style="font-size: 0.82rem; color: #64748b;">CRUD (6.1&ndash;6.4) &bull; Aggregated Star Ratings</span>
            </div>
            <a href="modules/feedback/index.php?facility_id=FAC-04" class="btn btn-outline-blue" style="font-size: 0.82rem;">View Reviews &rarr;</a>
        </div>

        <p style="font-size: 0.9rem; color: #475569; margin-bottom: 1rem;">
            Student post-session reviews and administrator official replies:
        </p>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
            <?php foreach ($recentReviews as $r): ?>
                <div style="border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem 1rem; background: #ffffff;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <strong style="color: #0f172a; font-size: 0.92rem;"><?= htmlspecialchars($r['FacilityName']) ?></strong>
                        <span style="color: #d97706; font-weight: 700; font-size: 0.88rem;">
                            <?= htmlspecialchars((string)$r['OverallScore']) ?>★ (Cleanliness: <?= (int)$r['CleanlinessRating'] ?>/5)
                        </span>
                    </div>
                    <p style="margin: 0; color: #475569; font-size: 0.85rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        &ldquo;<?= htmlspecialchars($r['UserComments'] ?? 'No comment.') ?>&rdquo;
                    </p>
                    <?php if (!empty($r['AdminRemarks'])): ?>
                        <div style="margin-top: 0.4rem; font-size: 0.8rem; color: #2563eb; background: #eff6ff; padding: 0.35rem 0.6rem; border-radius: 4px;">
                            <strong>Admin Reply:</strong> <?= htmlspecialchars($r['AdminRemarks']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <a href="modules/feedback/submit.php" class="btn btn-primary-blue" style="flex: 1; font-size: 0.85rem;">+ Rate Session (UI 6.1)</a>
            <a href="modules/feedback/edit.php" class="btn btn-cancel" style="flex: 1; font-size: 0.85rem;">Moderation Panel (UI 6.3)</a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
