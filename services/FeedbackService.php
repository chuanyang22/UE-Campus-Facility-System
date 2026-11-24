<?php
/**
 * Business Services Tier: User Feedback & Review Management Service
 * AMCS2194 Mini Project — Module 6: User Feedback & Review Management
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

class FeedbackService {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Generate the next sequential FeedbackID (e.g., FB-6002)
     */
    public function generateNextFeedbackId(): string {
        $stmt = $this->db->query("SELECT FeedbackID FROM feedback ORDER BY FeedbackID DESC LIMIT 1");
        $lastId = $stmt->fetchColumn();

        if ($lastId && preg_match('/FB-(\d+)/', (string)$lastId, $matches)) {
            $nextNum = (int)$matches[1] + 1;
            return sprintf('FB-%04d', $nextNum);
        }

        return 'FB-6001';
    }

    /**
     * 6.1 Submit Session Feedback & Star Rating (Create)
     * 
     * @return array [success => bool, message => string, errors => array, id => string|null]
     */
    public function submitFeedback(array $data): array {
        $errors = $this->validateFeedbackInput($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please fix the feedback validation errors.',
                'errors'  => $errors,
                'id'      => null,
            ];
        }

        $bookingId    = trim($data['BookingID']);
        $cleanliness  = (int)$data['CleanlinessRating'];
        $condition    = (int)$data['ConditionRating'];
        $overallScore = round(($cleanliness + $condition) / 2.0, 1);
        $userComments = !empty($data['UserComments']) ? trim($data['UserComments']) : null;
        $feedbackId   = $this->generateNextFeedbackId();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO feedback (FeedbackID, BookingID, CleanlinessRating, ConditionRating, OverallScore, UserComments, SubmissionDate)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $feedbackId,
                $bookingId,
                $cleanliness,
                $condition,
                $overallScore,
                $userComments,
            ]);

            return [
                'success' => true,
                'message' => "Feedback {$feedbackId} submitted successfully with an overall score of {$overallScore}/5.0! Thank you for rating campus facilities.",
                'errors'  => [],
                'id'      => $feedbackId,
            ];
        } catch (PDOException $e) {
            error_log('Error saving feedback: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error while recording feedback: ' . $e->getMessage(),
                'errors'  => ['general' => $e->getMessage()],
                'id'      => null,
            ];
        }
    }

    /**
     * 6.2 View Facility Reviews & Average Ratings (Retrieve)
     */
    public function getReviews(array $filters = []): array {
        $sql = "SELECT fb.*, b.BookingDate, b.StartTime, b.EndTime, b.Purpose, b.UserID,
                       f.FacilityID, f.FacilityName, f.Category AS FacilityCategory, f.LocationBlock,
                       u.FullName AS ReviewerName, u.CampusEmail AS ReviewerEmail, u.UserRole AS ReviewerRole
                FROM feedback fb
                JOIN booking b ON fb.BookingID = b.BookingID
                JOIN facility f ON b.FacilityID = f.FacilityID
                JOIN user u ON b.UserID = u.UserID
                WHERE 1=1";
        $params = [];

        if (!empty($filters['facility_id'])) {
            $sql .= " AND f.FacilityID = ?";
            $params[] = $filters['facility_id'];
        }

        if (!empty($filters['min_score'])) {
            $sql .= " AND fb.OverallScore >= ?";
            $params[] = (float)$filters['min_score'];
        }

        if (!empty($filters['booking_id'])) {
            $sql .= " AND fb.BookingID = ?";
            $params[] = $filters['booking_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $sql .= " AND (fb.FeedbackID LIKE ? OR f.FacilityName LIKE ? OR u.FullName LIKE ? OR fb.UserComments LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY fb.SubmissionDate DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get review by FeedbackID
     */
    public function getReviewById(string $feedbackId): ?array {
        $stmt = $this->db->prepare(
            "SELECT fb.*, b.BookingDate, b.StartTime, b.EndTime, b.Purpose, b.UserID,
                    f.FacilityID, f.FacilityName, f.Category AS FacilityCategory, f.LocationBlock,
                    u.FullName AS ReviewerName, u.CampusEmail AS ReviewerEmail, u.UserRole AS ReviewerRole
             FROM feedback fb
             JOIN booking b ON fb.BookingID = b.BookingID
             JOIN facility f ON b.FacilityID = f.FacilityID
             JOIN user u ON b.UserID = u.UserID
             WHERE fb.FeedbackID = ?"
        );
        $stmt->execute([$feedbackId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * 6.3 Edit Review / Post Admin Reply (Update)
     */
    public function updateReview(string $feedbackId, array $data, bool $isAdmin = false): array {
        $review = $this->getReviewById($feedbackId);
        if (!$review) {
            return ['success' => false, 'message' => 'Feedback record not found.'];
        }

        $errors = [];

        if ($isAdmin) {
            // Admin updating AdminRemarks
            $adminRemarks = trim($data['AdminRemarks'] ?? '');
            if (mb_strlen($adminRemarks) > 300) {
                $errors['AdminRemarks'] = 'Admin Remarks cannot exceed 300 characters.';
            }

            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation error.', 'errors' => $errors];
            }

            try {
                $stmt = $this->db->prepare("UPDATE feedback SET AdminRemarks = ? WHERE FeedbackID = ?");
                $stmt->execute([$adminRemarks ?: null, $feedbackId]);
                return ['success' => true, 'message' => "Admin response saved for feedback {$feedbackId}."];
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }
        } else {
            // Student updating Cleanliness, Condition, and UserComments
            $cleanliness = (int)($data['CleanlinessRating'] ?? 0);
            $condition   = (int)($data['ConditionRating'] ?? 0);

            if ($cleanliness < 1 || $cleanliness > 5) {
                $errors['CleanlinessRating'] = 'Cleanliness Rating must be an integer between 1 and 5.';
            }
            if ($condition < 1 || $condition > 5) {
                $errors['ConditionRating'] = 'Facility Condition Rating must be between 1 and 5.';
            }

            $comments = trim($data['UserComments'] ?? '');
            if (mb_strlen($comments) > 1000) {
                $errors['UserComments'] = 'Comments cannot exceed 1000 characters.';
            }

            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Validation error.', 'errors' => $errors];
            }

            $overall = round(($cleanliness + $condition) / 2.0, 1);

            try {
                $stmt = $this->db->prepare(
                    "UPDATE feedback 
                     SET CleanlinessRating = ?, ConditionRating = ?, OverallScore = ?, UserComments = ? 
                     WHERE FeedbackID = ?"
                );
                $stmt->execute([$cleanliness, $condition, $overall, $comments ?: null, $feedbackId]);
                return ['success' => true, 'message' => "Your review {$feedbackId} has been successfully updated with new score {$overall}/5.0."];
            } catch (PDOException $e) {
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }
        }
    }

    /**
     * 6.4 Delete Invalid Review (Delete)
     */
    public function deleteReview(string $feedbackId): array {
        $review = $this->getReviewById($feedbackId);
        if (!$review) {
            return ['success' => false, 'message' => 'Feedback record not found.'];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM feedback WHERE FeedbackID = ?");
            $stmt->execute([$feedbackId]);
            return ['success' => true, 'message' => "Review {$feedbackId} has been successfully deleted by administrator."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error deleting review: ' . $e->getMessage()];
        }
    }

    /**
     * Get Facility Star Aggregates (Average overall score, Cleanliness, Condition, Total Reviews)
     */
    public function getFacilityRatingsSummary(): array {
        $sql = "SELECT f.FacilityID, f.FacilityName, f.Category, f.LocationBlock, f.AvailabilityStatus,
                       COUNT(fb.FeedbackID) AS TotalReviews,
                       ROUND(AVG(fb.CleanlinessRating), 1) AS AvgCleanliness,
                       ROUND(AVG(fb.ConditionRating), 1) AS AvgCondition,
                       ROUND(AVG(fb.OverallScore), 1) AS AvgOverallScore
                FROM facility f
                LEFT JOIN booking b ON f.FacilityID = b.FacilityID
                LEFT JOIN feedback fb ON b.BookingID = fb.BookingID
                GROUP BY f.FacilityID, f.FacilityName, f.Category, f.LocationBlock, f.AvailabilityStatus
                ORDER BY AvgOverallScore DESC, TotalReviews DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get bookings available for user to review
     * Checks completed bookings that have not yet been reviewed.
     */
    public function getCompletedBookingsWithoutFeedback(?string $userId = null): array {
        $sql = "SELECT b.*, f.FacilityName, f.LocationBlock, u.FullName AS UserName
                FROM booking b
                JOIN facility f ON b.FacilityID = f.FacilityID
                JOIN user u ON b.UserID = u.UserID
                LEFT JOIN feedback fb ON b.BookingID = fb.BookingID
                WHERE b.BookingStatus = 'Completed' AND fb.FeedbackID IS NULL";
        $params = [];

        if ($userId !== null) {
            $sql .= " AND b.UserID = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY b.BookingDate DESC, b.EndTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Input validation for Feedback submission
     */
    public function validateFeedbackInput(array $data): array {
        $errors = [];

        // 1. BookingID presence & existence check
        $bookingId = trim($data['BookingID'] ?? '');
        if ($bookingId === '') {
            $errors['BookingID'] = 'Completed booking session must be selected.';
        } else {
            // Existence check
            $stmt = $this->db->prepare("SELECT BookingStatus FROM booking WHERE BookingID = ?");
            $stmt->execute([$bookingId]);
            $status = $stmt->fetchColumn();

            if ($status === false) {
                $errors['BookingID'] = "Booking ID '{$bookingId}' does not exist.";
            } elseif ($status !== 'Completed') {
                $errors['BookingID'] = "Reviews can only be submitted for completed sessions. Current status is '{$status}'.";
            } else {
                // Uniqueness check (1 feedback per booking)
                $dupStmt = $this->db->prepare("SELECT FeedbackID FROM feedback WHERE BookingID = ?");
                $dupStmt->execute([$bookingId]);
                $existing = $dupStmt->fetchColumn();
                if ($existing) {
                    $errors['BookingID'] = "Feedback ({$existing}) has already been submitted for booking {$bookingId}.";
                }
            }
        }

        // 2. CleanlinessRating range check (1 to 5)
        if (!isset($data['CleanlinessRating']) || $data['CleanlinessRating'] === '') {
            $errors['CleanlinessRating'] = 'Cleanliness Rating is mandatory.';
        } else {
            $cleanliness = filter_var($data['CleanlinessRating'], FILTER_VALIDATE_INT);
            if ($cleanliness === false || $cleanliness < 1 || $cleanliness > 5) {
                $errors['CleanlinessRating'] = 'Cleanliness Rating must be an integer between 1 and 5.';
            }
        }

        // 3. ConditionRating range check (1 to 5)
        if (!isset($data['ConditionRating']) || $data['ConditionRating'] === '') {
            $errors['ConditionRating'] = 'Facility Condition Rating is mandatory.';
        } else {
            $condition = filter_var($data['ConditionRating'], FILTER_VALIDATE_INT);
            if ($condition === false || $condition < 1 || $condition > 5) {
                $errors['ConditionRating'] = 'Facility Condition Rating must be an integer between 1 and 5.';
            }
        }

        // 4. UserComments size check (max 1000)
        $comments = trim($data['UserComments'] ?? '');
        if (mb_strlen($comments) > 1000) {
            $errors['UserComments'] = 'Comments cannot exceed 1000 characters.';
        }

        return $errors;
    }
}
