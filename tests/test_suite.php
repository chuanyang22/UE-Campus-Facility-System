<?php
/**
 * Automated Verification & Test Suite
 * AMCS2194 Mini Project — Validates all 9 functions & boundary cases
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../services/MaintenanceService.php';
require_once __DIR__ . '/../services/FeedbackService.php';

echo "========================================================\n";
echo "AMCS2194 MINI PROJECT: MODULES 5 & 6 TEST SUITE\n";
echo "========================================================\n\n";

$db = getDB();
$maintService = new MaintenanceService();
$fbService = new FeedbackService();

$passed = 0;
$failed = 0;

function assertTest(string $testName, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] {$testName}\n";
    } else {
        $failed++;
        echo "[FAIL] {$testName} - Details: {$details}\n";
    }
}

// -----------------------------------------------------------------
// 1. MODULE 5: 5.1 Validation Check (Invalid, Boundary, Blank)
// -----------------------------------------------------------------
echo "\n--- Testing 5.1 Validation Rules ---\n";

$invalidData = [
    'FacilityID'       => '', // Blank
    'ReportedByUserID' => 'U9999', // Non-existent user
    'IssueTitle'       => 'No', // Too short (< 5 chars)
    'IssueDescription' => 'Short', // Too short (< 10 chars)
    'UrgencyLevel'     => 'Extreme', // Invalid whitelist
    'ScheduledDate'    => 'invalid-date', // Bad format
];

$res = $maintService->reportDefect($invalidData);
assertTest('5.1 Validation Rejection for invalid inputs', $res['success'] === false);
assertTest('5.1 FacilityID Null Check caught', isset($res['errors']['FacilityID']));
assertTest('5.1 UserID Existence Check caught', isset($res['errors']['ReportedByUserID']));
assertTest('5.1 IssueTitle Limit Check caught', isset($res['errors']['IssueTitle']));
assertTest('5.1 IssueDescription Boundary Check caught', isset($res['errors']['IssueDescription']));
assertTest('5.1 UrgencyLevel Whitelist Check caught', isset($res['errors']['UrgencyLevel']));
assertTest('5.1 ScheduledDate Format Check caught', isset($res['errors']['ScheduledDate']));

// -----------------------------------------------------------------
// 2. MODULE 5: 5.1 & 5.5 Create Defect + Automated Venue Locking
// -----------------------------------------------------------------
echo "\n--- Testing 5.1 Create & 5.5 Automated Venue Locking ---\n";

// Ensure FAC-02 is Available initially
$db->query("UPDATE facility SET AvailabilityStatus = 'Available' WHERE FacilityID = 'FAC-02'");

$criticalDefect = [
    'FacilityID'       => 'FAC-02',
    'ReportedByUserID' => 'U1001',
    'IssueTitle'       => 'Ceiling Water Leakage Hazard',
    'IssueDescription' => 'Severe ceiling water leak above electrical socket board near window.',
    'UrgencyLevel'     => 'Critical',
    'ScheduledDate'    => date('Y-m-d'),
];

$resCritical = $maintService->reportDefect($criticalDefect);
assertTest('5.1 Normal Create Defect Success', $resCritical['success'] === true);
$createdTicketId = $resCritical['id'];

// Check that Critical defect is initially pending admin approval (preventing accidental student lockdown)
assertTest('5.5 Critical Ticket flagged for Admin Review', $resCritical['lockdown_status'] === 'Pending_Review');

// Admin approves lockdown
$approveRes = $maintService->approveVenueLockdown($createdTicketId);
assertTest('5.5 Admin Lockdown Approval Execution', $approveRes['success'] === true);

// Check if FAC-02 is now locked to 'Under Maintenance' following admin approval
$venueStatus = $db->query("SELECT AvailabilityStatus FROM facility WHERE FacilityID = 'FAC-02'")->fetchColumn();
assertTest('5.5 Facility Locked to Under Maintenance upon Admin Approval', $venueStatus === 'Under Maintenance');

// -----------------------------------------------------------------
// 3. MODULE 5: 5.2 Retrieve Ticket Log & Filters
// -----------------------------------------------------------------
echo "\n--- Testing 5.2 Retrieve Ticket Log & Filters ---\n";

$allTickets = $maintService->getTickets();
assertTest('5.2 Retrieve All Tickets', count($allTickets) >= 3);

$filteredCritical = $maintService->getTickets(['urgency' => 'Critical']);
assertTest('5.2 Filter by Critical Urgency', count($filteredCritical) >= 1);

$searchTickets = $maintService->getTickets(['search' => 'Ceiling Water']);
assertTest('5.2 Keyword Search filter', count($searchTickets) >= 1 && $searchTickets[0]['MaintenanceID'] === $createdTicketId);

// -----------------------------------------------------------------
// 4. MODULE 5: 5.3 Update Status & 5.5 Auto-Unlock
// -----------------------------------------------------------------
echo "\n--- Testing 5.3 Update Status & 5.5 Auto-Unlock ---\n";

$updateRes = $maintService->updateTicketStatus($createdTicketId, 'Completed', date('Y-m-d'));
assertTest('5.3 Update Status to Completed', $updateRes['success'] === true);

// Verify venue FAC-02 auto-unlocked back to Available
$restoredStatus = $db->query("SELECT AvailabilityStatus FROM facility WHERE FacilityID = 'FAC-02'")->fetchColumn();
assertTest('5.5 Automated Venue Unlock to Available on Completion', $restoredStatus === 'Available');

// -----------------------------------------------------------------
// 5. MODULE 5: 5.4 Delete / Close Ticket
// -----------------------------------------------------------------
echo "\n--- Testing 5.4 Delete Ticket ---\n";

$delRes = $maintService->deleteTicket($createdTicketId);
assertTest('5.4 Delete Ticket Success', $delRes['success'] === true);
$checkDeleted = $maintService->getTicketById($createdTicketId);
assertTest('5.4 Ticket Verified Deleted from DB', $checkDeleted === null);

// -----------------------------------------------------------------
// 6. MODULE 6: 6.1 Submit Feedback & Validations
// -----------------------------------------------------------------
echo "\n--- Testing 6.1 Feedback Input Validation & Submission ---\n";

// Test Range checks on ratings
$invalidFb = [
    'BookingID'         => 'BK-3001', // Already has feedback FB-6001
    'CleanlinessRating' => '7', // Out of bounds (> 5)
    'ConditionRating'   => '0', // Out of bounds (< 1)
    'UserComments'      => 'Test comment',
];
$fbFail = $fbService->submitFeedback($invalidFb);
assertTest('6.1 Validation Rejection for invalid range/duplicate', $fbFail['success'] === false);

// Create a completed booking without feedback for testing 6.1
$testBookingId = 'BK-TEST99';
$db->exec("DELETE FROM feedback WHERE BookingID = '{$testBookingId}'");
$db->exec("DELETE FROM equipment WHERE BookingID = '{$testBookingId}'");
$db->exec("DELETE FROM booking WHERE BookingID = '{$testBookingId}'");

$db->prepare("INSERT INTO booking (BookingID, UserID, FacilityID, BookingDate, StartTime, EndTime, Purpose, BookingStatus) VALUES (?, 'U1002', 'FAC-03', CURDATE(), '10:00:00', '12:00:00', 'Tournament Test Match', 'Completed')")
   ->execute([$testBookingId]);

$validFb = [
    'BookingID'         => $testBookingId,
    'CleanlinessRating' => '5',
    'ConditionRating'   => '4',
    'UserComments'      => 'Excellent court condition, clean floor and pristine nets.',
];
$fbSubmitRes = $fbService->submitFeedback($validFb);
assertTest('6.1 Submit Valid Feedback', $fbSubmitRes['success'] === true);
$createdFbId = $fbSubmitRes['id'];

// Check score calculation (5 + 4) / 2 = 4.5
$savedFb = $fbService->getReviewById($createdFbId);
assertTest('6.1 Computed Overall Score Calculation ((5+4)/2 = 4.5)', (float)$savedFb['OverallScore'] === 4.5);

// -----------------------------------------------------------------
// 7. MODULE 6: 6.2 Retrieve Reviews & Facility Aggregates
// -----------------------------------------------------------------
echo "\n--- Testing 6.2 Retrieve Reviews & Facility Ratings ---\n";

$reviews = $fbService->getReviews(['facility_id' => 'FAC-03']);
assertTest('6.2 Retrieve Reviews filtered by Facility FAC-03', count($reviews) >= 1);

$summaries = $fbService->getFacilityRatingsSummary();
assertTest('6.2 Facility Star Ratings Summary calculation', count($summaries) >= 3);

// -----------------------------------------------------------------
// 8. MODULE 6: 6.3 Update Review / Admin Reply
// -----------------------------------------------------------------
echo "\n--- Testing 6.3 Edit Review & Post Admin Reply ---\n";

// Student update
$studentUpdate = $fbService->updateReview($createdFbId, [
    'CleanlinessRating' => 5,
    'ConditionRating'   => 5,
    'UserComments'      => 'Updated: Court floor was spotless and lighting was bright.',
], false);
assertTest('6.3 Student Edit Review', $studentUpdate['success'] === true);

// Admin response
$adminUpdate = $fbService->updateReview($createdFbId, [
    'AdminRemarks' => 'Thank you for your review! We will keep maintaining Hall 1.',
], true);
assertTest('6.3 Admin Post Official Remarks', $adminUpdate['success'] === true);

$verifiedFb = $fbService->getReviewById($createdFbId);
assertTest('6.3 Admin Remarks saved in DB', strpos($verifiedFb['AdminRemarks'], 'Thank you') !== false);
assertTest('6.3 Updated Overall Score ((5+5)/2 = 5.0)', (float)$verifiedFb['OverallScore'] === 5.0);

// -----------------------------------------------------------------
// 9. MODULE 6: 6.4 Delete Invalid Review
// -----------------------------------------------------------------
echo "\n--- Testing 6.4 Delete Review ---\n";

$delFbRes = $fbService->deleteReview($createdFbId);
assertTest('6.4 Admin Delete Review Success', $delFbRes['success'] === true);
assertTest('6.4 Review Verified Deleted', $fbService->getReviewById($createdFbId) === null);

// Cleanup test booking
$db->exec("DELETE FROM booking WHERE BookingID = '{$testBookingId}'");

echo "\n========================================================\n";
echo "TEST RESULTS: {$passed} PASSED, {$failed} FAILED\n";
echo "========================================================\n";
