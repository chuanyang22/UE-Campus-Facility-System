<?php
/**
 * Booking Service & Unique Feature Engine
 * AMCS2194 Mini Project — Dynamic QR Code Check-in & 15-Minute No-Show Auto-Release Engine
 * Student 2: Tie Sing Wee (Module 5 + Module 6 + Unique Feature)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

class BookingService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDB();
    }

    /**
     * Retrieve active & upcoming bookings for a user (or all bookings if $activeOnly = false)
     */
    public function getUserBookings(string $userId, bool $activeOnly = true): array {
        $sql = "SELECT b.*, f.FacilityName, f.LocationBlock, f.Category AS FacilityCategory, f.Capacity, f.AvailabilityStatus
                FROM booking b
                JOIN facility f ON b.FacilityID = f.FacilityID
                WHERE b.UserID = ?";

        if ($activeOnly) {
            $sql .= " AND b.BookingStatus IN ('Confirmed', 'Checked-In')
                      AND CONCAT(b.BookingDate, ' ', b.EndTime) > NOW()
                      ORDER BY b.BookingDate ASC, b.StartTime ASC";
        } else {
            $sql .= " ORDER BY b.BookingDate DESC, b.StartTime DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Retrieve all campus facilities with review count and average rating
     */
    public function getAllFacilitiesWithStats(string $categoryFilter = '', string $search = ''): array {
        $sql = "SELECT f.*, 
                       COUNT(fb.FeedbackID) AS ReviewCount,
                       ROUND(AVG(fb.OverallScore), 2) AS AvgScore
                FROM facility f
                LEFT JOIN booking b ON f.FacilityID = b.FacilityID
                LEFT JOIN feedback fb ON b.BookingID = fb.BookingID
                WHERE 1=1";
        $params = [];

        if (!empty($categoryFilter) && $categoryFilter !== 'ALL') {
            if ($categoryFilter === 'Sports') {
                $sql .= " AND (f.Category = 'Sports' OR f.FacilityName LIKE '%Badminton%' OR f.FacilityName LIKE '%Futsal%')";
            } elseif ($categoryFilter === 'Academic') {
                $sql .= " AND (f.Category = 'Academic' OR f.FacilityName LIKE '%Discussion%' OR f.FacilityName LIKE '%Lab%' OR f.FacilityName LIKE '%Lecture%')";
            }
        }

        if (!empty($search)) {
            $sql .= " AND (f.FacilityName LIKE ? OR f.FacilityID LIKE ? OR f.LocationBlock LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " GROUP BY f.FacilityID ORDER BY f.FacilityID ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retrieve single booking
     */
    public function getBookingById(string $bookingId): ?array {
        $stmt = $this->db->prepare(
            "SELECT b.*, f.FacilityName, f.LocationBlock, f.Category AS FacilityCategory, u.FullName AS UserName, u.CampusEmail
             FROM booking b
             JOIN facility f ON b.FacilityID = f.FacilityID
             JOIN user u ON b.UserID = u.UserID
             WHERE b.BookingID = ?"
        );
        $stmt->execute([$bookingId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Unique Feature: Dynamic QR Code Check-in Verification
     * Verifies arrival, updates BookingStatus to 'Checked-In' and logs CheckInTime
     */
    public function processQrCheckIn(string $bookingId): array {
        $booking = $this->getBookingById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => "Booking {$bookingId} not found."];
        }

        if ($booking['BookingStatus'] === 'Checked-In') {
            return [
                'success' => true,
                'already' => true,
                'message' => "Session {$bookingId} is already checked in at " . date('H:i', strtotime($booking['CheckInTime'])),
                'checkInTime' => $booking['CheckInTime'],
            ];
        }

        if ($booking['BookingStatus'] === 'Cancelled') {
            return ['success' => false, 'message' => "Cannot check in: Booking {$bookingId} was cancelled."];
        }

        if ($booking['BookingStatus'] === 'No-Show') {
            return ['success' => false, 'message' => "Cannot check in: Booking {$bookingId} was marked as No-Show due to 15-minute expiration."];
        }

        if ($booking['BookingStatus'] === 'Completed') {
            return ['success' => false, 'message' => "Session {$bookingId} has already ended and is marked Completed."];
        }

        $now = date('Y-m-d H:i:s');
        try {
            $stmt = $this->db->prepare(
                "UPDATE booking 
                 SET BookingStatus = 'Checked-In', CheckInTime = ? 
                 WHERE BookingID = ?"
            );
            $stmt->execute([$now, $bookingId]);

            return [
                'success' => true,
                'already' => false,
                'message' => "QR Check-in successful for {$booking['FacilityName']}! Status updated to Checked-In.",
                'checkInTime' => $now,
                'bookingId' => $bookingId,
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error during check-in: ' . $e->getMessage()];
        }
    }

    /**
     * Unique Feature: 15-Minute No-Show Auto-Release Engine
     * Automatically scans for Confirmed bookings that remained unclaimed 15 minutes past StartTime
     * and auto-releases them to 'No-Show', freeing the venue for other students.
     */
    public function runNoShowAutoRelease(): array {
        // Look for unclaimed bookings whose start time was at least 15 minutes ago
        $stmt = $this->db->query(
            "SELECT b.BookingID, b.FacilityID, b.UserID, b.BookingDate, b.StartTime, f.FacilityName
             FROM booking b
             JOIN facility f ON b.FacilityID = f.FacilityID
             WHERE b.BookingStatus = 'Confirmed'
               AND b.CheckInTime IS NULL
               AND (CONCAT(b.BookingDate, ' ', b.StartTime) <= DATE_SUB(NOW(), INTERVAL 15 MINUTE))"
        );
        $expiredBookings = $stmt->fetchAll();

        $releasedCount = 0;
        $releasedIds = [];
        $affectedUsers = [];
        $penalizedUsers = [];

        if (!empty($expiredBookings)) {
            $updateStmt = $this->db->prepare(
                "UPDATE booking 
                 SET BookingStatus = 'No-Show' 
                 WHERE BookingID = ?"
            );

            foreach ($expiredBookings as $exp) {
                $updateStmt->execute([$exp['BookingID']]);
                $releasedCount++;
                $releasedIds[] = $exp['BookingID'];
                $affectedUsers[$exp['UserID']] = true;
            }

            foreach (array_keys($affectedUsers) as $uid) {
                $pen = $this->evaluateNoShowPenalty((string)$uid);
                if ($pen['penalized']) {
                    $penalizedUsers[] = $uid;
                }
            }
        }

        $msg = $releasedCount > 0
            ? "Auto-Release Engine executed: {$releasedCount} unclaimed booking(s) expired past the 15-minute grace period and marked as 'No-Show'."
            : "Auto-Release Engine executed: No unclaimed bookings exceed the 15-minute grace period.";

        if (!empty($penalizedUsers)) {
            $msg .= ' 3-day booking penalty applied to: ' . implode(', ', $penalizedUsers) . ' (3+ missed check-ins).';
        }

        return [
            'success' => true,
            'released_count' => $releasedCount,
            'released_ids' => $releasedIds,
            'penalized_users' => $penalizedUsers,
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => $msg,
        ];
    }

    /**
     * Evaluate if a user has reached 3+ No-Show strikes (since last reset) and apply a 3-day booking ban.
     */
    public function evaluateNoShowPenalty(string $userId): array {
        $userStmt = $this->db->prepare("SELECT BookingBanUntil, StrikeResetAt FROM user WHERE UserID = ?");
        $userStmt->execute([$userId]);
        $userRow = $userStmt->fetch();
        $banUntil = $userRow['BookingBanUntil'] ?? null;
        $resetAt  = $userRow['StrikeResetAt'] ?? null;

        $noShowCount = $this->countActiveNoShowStrikes($userId, $resetAt);
        $isCurrentlyBanned = $banUntil && strtotime((string)$banUntil) > time();

        if ($noShowCount >= 3 && !$isCurrentlyBanned) {
            $newBanUntil = date('Y-m-d H:i:s', strtotime('+3 days'));
            $upd = $this->db->prepare("UPDATE user SET BookingBanUntil = ? WHERE UserID = ?");
            $upd->execute([$newBanUntil, $userId]);
            return ['penalized' => true, 'no_show_count' => $noShowCount, 'ban_until' => $newBanUntil];
        }

        return ['penalized' => false, 'no_show_count' => $noShowCount, 'ban_until' => $banUntil ?: null];
    }

    /**
     * Get current No-Show strike count and 3-day booking ban status for a user.
     */
    public function getUserPenaltyStatus(string $userId): array {
        $userStmt = $this->db->prepare("SELECT BookingBanUntil, StrikeResetAt FROM user WHERE UserID = ?");
        $userStmt->execute([$userId]);
        $userRow = $userStmt->fetch();
        $banUntil = $userRow['BookingBanUntil'] ?? null;
        $resetAt  = $userRow['StrikeResetAt'] ?? null;

        $noShowCount = $this->countActiveNoShowStrikes($userId, $resetAt);
        $isBanned = $banUntil && strtotime((string)$banUntil) > time();

        return [
            'no_show_count' => $noShowCount,
            'is_banned'     => (bool)$isBanned,
            'ban_until'     => $isBanned ? (string)$banUntil : null,
        ];
    }

    /**
     * Count No-Show bookings occurring after StrikeResetAt (if set).
     */
    private function countActiveNoShowStrikes(string $userId, ?string $resetAt): int {
        if ($resetAt) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM booking
                 WHERE UserID = ? AND BookingStatus = 'No-Show'
                   AND CONCAT(BookingDate, ' ', StartTime) > ?"
            );
            $stmt->execute([$userId, $resetAt]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM booking WHERE UserID = ? AND BookingStatus = 'No-Show'");
            $stmt->execute([$userId]);
        }
        return (int)$stmt->fetchColumn();
    }

    /**
     * Clear a user's active booking penalty and reset strike counter to 0 (for Admin/Demo reset).
     */
    public function clearUserPenalty(string $userId): bool {
        $stmt = $this->db->prepare("UPDATE user SET BookingBanUntil = NULL, StrikeResetAt = NOW() WHERE UserID = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Helper for Demonstration: seed an overdue booking so evaluators can test the 15-min engine live
     */
    public function seedOverdueDemoBooking(): string {
        $demoId = 'BK-OVD01';
        $today = date('Y-m-d');
        // Start time 25 minutes ago
        $startTime = date('H:i:s', strtotime('-25 minutes'));
        $endTime = date('H:i:s', strtotime('+95 minutes'));

        $this->db->exec("DELETE FROM booking WHERE BookingID = '{$demoId}'");

        $stmt = $this->db->prepare(
            "INSERT INTO booking (BookingID, UserID, FacilityID, BookingDate, StartTime, EndTime, Purpose, BookingStatus, CheckInTime, CheckOutTime)
             VALUES (?, 'A240112', 'FAC-05', ?, ?, ?, 'Evaluator Demo: Unclaimed Booking', 'Confirmed', NULL, NULL)"
        );
        $stmt->execute([$demoId, $today, $startTime, $endTime]);

        return $demoId;
    }

    /**
     * Create a Confirmed booking (also enforces 3-day No-Show penalty and facility availability)
     */
    public function createQuickBooking(
        string $userId,
        string $facilityId,
        string $bookingDate,
        string $startTime,
        string $endTime,
        string $purpose
    ): array {
        $penalty = $this->getUserPenaltyStatus($userId);
        if ($penalty['is_banned']) {
            return [
                'success' => false,
                'message' => 'Booking blocked: You are under a 3-day No-Show suspension until ' . date('d M Y, H:i', strtotime((string)$penalty['ban_until'])) . '.',
            ];
        }

        $facStmt = $this->db->prepare("SELECT FacilityName, AvailabilityStatus FROM facility WHERE FacilityID = ?");
        $facStmt->execute([$facilityId]);
        $facility = $facStmt->fetch();
        if (!$facility) {
            return ['success' => false, 'message' => 'Selected facility not found.'];
        }
        if ($facility['AvailabilityStatus'] !== 'Available') {
            return ['success' => false, 'message' => 'Facility is currently unavailable (' . $facility['AvailabilityStatus'] . ').'];
        }

        $purpose = trim($purpose) !== '' ? trim($purpose) : 'Campus Facility Session';

        // Generate next BK-XXXX (max 10 chars)
        $idStmt = $this->db->query("SELECT BookingID FROM booking WHERE BookingID REGEXP '^BK-[0-9]+$' ORDER BY CAST(SUBSTRING(BookingID, 4) AS UNSIGNED) DESC LIMIT 1");
        $lastId = $idStmt->fetchColumn();
        $nextNum = 3001;
        if ($lastId && preg_match('/^BK-(\d+)$/', (string)$lastId, $m)) {
            $nextNum = (int)$m[1] + 1;
        }
        $bookingId = 'BK-' . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);

        try {
            $ins = $this->db->prepare(
                "INSERT INTO booking (BookingID, UserID, FacilityID, BookingDate, StartTime, EndTime, Purpose, BookingStatus, CheckInTime)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmed', NULL)"
            );
            $ins->execute([$bookingId, $userId, $facilityId, $bookingDate, $startTime, $endTime, $purpose]);

            return [
                'success'    => true,
                'booking_id' => $bookingId,
                'message'    => "Booking confirmed for {$facility['FacilityName']}! You can now test QR Check-In below.",
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error creating booking: ' . $e->getMessage()];
        }
    }

    /**
     * Generate inline SVG QR Code placeholder representation
     */
    public static function generateSvgQrCode(string $data, int $size = 180): string {
        // High quality vector SVG QR code visual with 3 finder patterns and dynamic data matrix
        $hash = md5($data);
        $gridSize = 25;
        $cellSize = $size / $gridSize;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">';
        $svg .= '<rect width="100%" height="100%" fill="#ffffff" rx="8"/>';

        // Helper to draw QR finder corners
        $drawFinder = function($startX, $startY) use (&$svg, $cellSize) {
            $x = $startX * $cellSize;
            $y = $startY * $cellSize;
            $w = 7 * $cellSize;
            $innerW = 3 * $cellSize;
            $innerOffset = 2 * $cellSize;

            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $w . '" fill="#0f172a" rx="4"/>';
            $svg .= '<rect x="' . ($x + $cellSize) . '" y="' . ($y + $cellSize) . '" width="' . (5 * $cellSize) . '" height="' . (5 * $cellSize) . '" fill="#ffffff" rx="2"/>';
            $svg .= '<rect x="' . ($x + $innerOffset) . '" y="' . ($y + $innerOffset) . '" width="' . $innerW . '" height="' . $innerW . '" fill="#0f172a" rx="2"/>';
        };

        $drawFinder(1, 1);
        $drawFinder(17, 1);
        $drawFinder(1, 17);

        // Generate deterministic matrix dots based on data hash
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                // Skip finder patterns
                if (($row < 9 && $col < 9) || ($row < 9 && $col >= 16) || ($row >= 16 && $col < 9)) {
                    continue;
                }
                $bitIndex = ($row * $gridSize + $col) % 32;
                $char = $hash[$bitIndex];
                if (hexdec($char) % 2 === 0) {
                    $x = $col * $cellSize;
                    $y = $row * $cellSize;
                    $svg .= '<rect x="' . ($x + 0.5) . '" y="' . ($y + 0.5) . '" width="' . ($cellSize - 1) . '" height="' . ($cellSize - 1) . '" fill="#1e293b" rx="1"/>';
                }
            }
        }

        // Center branding emblem
        $emblemSize = 6 * $cellSize;
        $emblemOffset = ($size - $emblemSize) / 2;
        $svg .= '<rect x="' . $emblemOffset . '" y="' . $emblemOffset . '" width="' . $emblemSize . '" height="' . $emblemSize . '" fill="#ffffff" stroke="#2563eb" stroke-width="2" rx="4"/>';
        $svg .= '<text x="' . ($size / 2) . '" y="' . ($size / 2 + 4) . '" fill="#2563eb" font-family="system-ui, sans-serif" font-size="10" font-weight="800" text-anchor="middle">UE</text>';

        $svg .= '</svg>';
        return $svg;
    }
}
