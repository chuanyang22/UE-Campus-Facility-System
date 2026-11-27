<?php
/**
 * Business Services Tier: Complimentary Equipment Borrowing Management Service
 * AMCS2194 Mini Project — Module 4 (4.1 - 4.4)
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

class EquipmentService {
    private PDO $db;

    /**
     * Standard complimentary equipment catalog definitions and initial stock
     */
    public const MASTER_CATALOG = [
        'Sports Gear' => [
            'Badminton Racket Pair' => [
                'name'     => 'Badminton Racket Pair',
                'category' => 'Sports Gear',
                'stock'    => 12,
                'unit'     => 'pairs',
                'suitable' => 'Sports',
                'desc'     => 'Two tournament-grade graphite badminton rackets with protective case.',
            ],
            'Badminton Shuttlecock Tube (6 pcs)' => [
                'name'     => 'Badminton Shuttlecock Tube (6 pcs)',
                'category' => 'Sports Gear',
                'stock'    => 20,
                'unit'     => 'tubes',
                'suitable' => 'Sports',
                'desc'     => 'Nylon durable training shuttlecocks suitable for indoor sports halls.',
            ],
            'Futsal Training Ball (Size 4)' => [
                'name'     => 'Futsal Training Ball (Size 4)',
                'category' => 'Sports Gear',
                'stock'    => 8,
                'unit'     => 'balls',
                'suitable' => 'Sports',
                'desc'     => 'Low-bounce synthetic leather futsal ball for outdoor/indoor court games.',
            ],
            'Volleyball Official (Size 5)' => [
                'name'     => 'Volleyball Official (Size 5)',
                'category' => 'Sports Gear',
                'stock'    => 6,
                'unit'     => 'balls',
                'suitable' => 'Sports',
                'desc'     => 'Soft-touch cushioned volleyball suitable for multi-purpose hall play.',
            ],
        ],
        'AV Accessories' => [
            'Type-C to HDMI Converter' => [
                'name'     => 'Type-C to HDMI Converter',
                'category' => 'AV Accessories',
                'stock'    => 15,
                'unit'     => 'units',
                'suitable' => 'Academic',
                'desc'     => 'Plug-and-play USB-C to 4K HDMI adapter for Mac, Windows, and iPad presentation.',
            ],
            'Wireless Presentation Clicker' => [
                'name'     => 'Wireless Presentation Clicker',
                'category' => 'AV Accessories',
                'stock'    => 10,
                'unit'     => 'units',
                'suitable' => 'Academic',
                'desc'     => '2.4GHz wireless slide presenter with red laser pointer and USB receiver dongle.',
            ],
            'HDMI 2.0 Ultra-HD Cable (3m)' => [
                'name'     => 'HDMI 2.0 Ultra-HD Cable (3m)',
                'category' => 'AV Accessories',
                'stock'    => 14,
                'unit'     => 'cables',
                'suitable' => 'Academic',
                'desc'     => 'High-speed braided HDMI cable for connecting laptops to wall projector plates.',
            ],
            'Wireless Lapel Microphone Set' => [
                'name'     => 'Wireless Lapel Microphone Set',
                'category' => 'AV Accessories',
                'stock'    => 4,
                'unit'     => 'sets',
                'suitable' => 'Academic',
                'desc'     => 'Rechargeable wireless lavalier mic with receiver for lecture rooms and presentations.',
            ],
        ],
        'Stationery' => [
            'Whiteboard Marker Set (4 colors)' => [
                'name'     => 'Whiteboard Marker Set (4 colors)',
                'category' => 'Stationery',
                'stock'    => 25,
                'unit'     => 'sets',
                'suitable' => 'Academic',
                'desc'     => 'Low-odor dry erase markers in Black, Blue, Red and Green with bullet tips.',
            ],
            'Magnetic Whiteboard Duster' => [
                'name'     => 'Magnetic Whiteboard Duster',
                'category' => 'Stationery',
                'stock'    => 18,
                'unit'     => 'pieces',
                'suitable' => 'Academic',
                'desc'     => 'Ergonomic felt magnetic whiteboard eraser with marker holder slot.',
            ],
            'Flipchart Paper Pad & Stand' => [
                'name'     => 'Flipchart Paper Pad & Stand',
                'category' => 'Stationery',
                'stock'    => 6,
                'unit'     => 'sets',
                'suitable' => 'Academic',
                'desc'     => '50-sheet plain easel paper pad on portable tripod stand for group brainstorming.',
            ],
        ],
    ];

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDB();
    }

    /**
     * Generate the next sequential EquipmentID (e.g. EQ-4004)
     */
    public function generateNextEquipmentId(): string {
        $stmt = $this->db->query("SELECT EquipmentID FROM equipment WHERE EquipmentID REGEXP '^EQ-[0-9]+$' ORDER BY CAST(SUBSTRING(EquipmentID, 4) AS UNSIGNED) DESC LIMIT 1");
        $lastId = $stmt->fetchColumn();

        $nextNum = 4001;
        if ($lastId && preg_match('/^EQ-(\d+)$/', (string)$lastId, $m)) {
            $nextNum = (int)$m[1] + 1;
        }

        return 'EQ-' . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Retrieve available equipment catalog, optionally filtered by venue suitability
     */
    public function getCatalog(?string $facilityCategory = null): array {
        $result = [];
        foreach (self::MASTER_CATALOG as $catGroup => $items) {
            foreach ($items as $name => $item) {
                if ($facilityCategory !== null && $facilityCategory !== '') {
                    if ($facilityCategory === 'Sports' && $item['suitable'] === 'Academic') {
                        continue;
                    }
                }
                $item['available_stock'] = $this->calculateAvailableStock($name);
                $result[$catGroup][$name] = $item;
            }
        }
        return $result;
    }

    /**
     * Calculate currently available (unloaned) stock for a specific item
     */
    public function calculateAvailableStock(string $itemName): int {
        $baseStock = 10;
        foreach (self::MASTER_CATALOG as $group) {
            if (isset($group[$itemName])) {
                $baseStock = (int)$group[$itemName]['stock'];
                break;
            }
        }

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(BorrowedQty), 0) 
             FROM equipment 
             WHERE ItemName = ? AND BorrowStatus = 'Borrowed'"
        );
        $stmt->execute([$itemName]);
        $currentlyBorrowed = (int)$stmt->fetchColumn();

        return max(0, $baseStock - $currentlyBorrowed);
    }

    /**
     * Process 4.1: Request Complimentary Equipment Loan
     */
    public function requestEquipmentLoan(array $data, string $currentUserId, bool $isAdmin = false): array {
        $bookingId = trim((string)($data['BookingID'] ?? ''));
        $itemName  = trim((string)($data['ItemName'] ?? ''));
        $category  = trim((string)($data['Category'] ?? ''));
        $qty       = (int)($data['BorrowedQty'] ?? 1);

        $errors = [];

        if ($bookingId === '') {
            $errors['BookingID'] = 'Please select a confirmed facility booking.';
        }

        if ($itemName === '') {
            $errors['ItemName'] = 'Please select an equipment item.';
        }

        if ($qty < 1 || $qty > 10) {
            $errors['BorrowedQty'] = 'Borrowed quantity must be between 1 and 10 units.';
        }

        // Validate booking eligibility
        $booking = null;
        if ($bookingId !== '') {
            $bStmt = $this->db->prepare(
                "SELECT b.*, f.FacilityName, f.Category AS FacilityCategory 
                 FROM booking b
                 JOIN facility f ON b.FacilityID = f.FacilityID
                 WHERE b.BookingID = ?"
            );
            $bStmt->execute([$bookingId]);
            $booking = $bStmt->fetch();

            if (!$booking) {
                $errors['BookingID'] = 'Selected booking was not found.';
            } else {
                if (!$isAdmin && $booking['UserID'] !== $currentUserId) {
                    $errors['BookingID'] = 'You can only request equipment for your own facility bookings.';
                }

                if (!in_array($booking['BookingStatus'], ['Confirmed', 'Checked-In'], true)) {
                    $errors['BookingID'] = "Cannot loan equipment: Booking status is '{$booking['BookingStatus']}'. Only Confirmed or Checked-In sessions are eligible.";
                }

                if ($booking['BookingDate'] < date('Y-m-d')) {
                    $errors['BookingID'] = 'Cannot loan equipment for past sessions.';
                }
            }
        }

        // Validate Category
        if (!in_array($category, ['Sports Gear', 'AV Accessories', 'Stationery'], true)) {
            // Auto detect from catalog if possible
            $foundCat = null;
            foreach (self::MASTER_CATALOG as $catGroup => $items) {
                if (isset($items[$itemName])) {
                    $foundCat = $catGroup;
                    break;
                }
            }
            if ($foundCat) {
                $category = $foundCat;
            } else {
                $errors['Category'] = 'Invalid equipment category.';
            }
        }

        // Check stock availability
        if ($itemName !== '') {
            $available = $this->calculateAvailableStock($itemName);
            if ($qty > $available) {
                $errors['BorrowedQty'] = "Stock shortage: Only {$available} unit(s) of '{$itemName}' currently available.";
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please correct the validation errors below.',
                'errors'  => $errors,
                'id'      => null,
            ];
        }

        $equipmentId = $this->generateNextEquipmentId();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO equipment (EquipmentID, BookingID, ItemName, Category, BorrowedQty, BorrowStatus, ConditionOnReturn)
                 VALUES (?, ?, ?, ?, ?, 'Borrowed', 'Pending Return')"
            );
            $stmt->execute([
                $equipmentId,
                $bookingId,
                $itemName,
                $category,
                $qty,
            ]);

            return [
                'success' => true,
                'message' => "Complimentary loan approved! {$qty}x '{$itemName}' (Ref: {$equipmentId}) assigned to Booking {$bookingId}.",
                'errors'  => [],
                'id'      => $equipmentId,
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error recording equipment loan: ' . $e->getMessage(),
                'errors'  => ['general' => $e->getMessage()],
                'id'      => null,
            ];
        }
    }

    /**
     * Process 4.2: Retrieve Equipment Loans with Multi-Criteria Filtering
     */
    public function getAllLoans(array $filters = [], ?string $userId = null): array {
        $sql = "SELECT eq.*, b.BookingDate, b.StartTime, b.EndTime, b.Purpose, b.BookingStatus,
                       f.FacilityID, f.FacilityName, f.LocationBlock, f.Category AS FacilityCategory,
                       u.UserID, u.FullName AS BorrowerName, u.CampusEmail, u.PhoneNo
                FROM equipment eq
                JOIN booking b ON eq.BookingID = b.BookingID
                JOIN facility f ON b.FacilityID = f.FacilityID
                JOIN user u ON b.UserID = u.UserID
                WHERE 1=1";
        $params = [];

        if (!empty($userId)) {
            $sql .= " AND u.UserID = ?";
            $params[] = $userId;
        }

        if (!empty($filters['status']) && $filters['status'] !== 'ALL') {
            $sql .= " AND eq.BorrowStatus = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['category']) && $filters['category'] !== 'ALL') {
            $sql .= " AND eq.Category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['booking_id'])) {
            $sql .= " AND eq.BookingID = ?";
            $params[] = $filters['booking_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (eq.EquipmentID LIKE ? OR eq.ItemName LIKE ? OR eq.BookingID LIKE ? OR u.FullName LIKE ? OR f.FacilityName LIKE ?)";
            $term = "%" . trim($filters['search']) . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY eq.EquipmentID DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Retrieve single loan record by EquipmentID
     */
    public function getLoanById(string $equipmentId): ?array {
        $stmt = $this->db->prepare(
            "SELECT eq.*, b.BookingDate, b.StartTime, b.EndTime, b.Purpose, b.BookingStatus,
                    f.FacilityID, f.FacilityName, f.LocationBlock, f.Category AS FacilityCategory,
                    u.UserID, u.FullName AS BorrowerName, u.CampusEmail, u.PhoneNo, u.UserRole
             FROM equipment eq
             JOIN booking b ON eq.BookingID = b.BookingID
             JOIN facility f ON b.FacilityID = f.FacilityID
             JOIN user u ON b.UserID = u.UserID
             WHERE eq.EquipmentID = ?"
        );
        $stmt->execute([$equipmentId]);
        $loan = $stmt->fetch();
        return $loan ?: null;
    }

    /**
     * Process 4.3: Update Return & Condition Inspection
     */
    public function inspectAndReturnEquipment(
        string $equipmentId,
        string $status,
        string $conditionNotes,
        bool $isAdmin = false
    ): array {
        if (!$isAdmin && !AuthService::isMaintenanceStaff()) {
            return ['success' => false, 'message' => 'Unauthorized: Only Facility Staff or Administrators can inspect equipment returns.'];
        }

        $loan = $this->getLoanById($equipmentId);
        if (!$loan) {
            return ['success' => false, 'message' => "Equipment record {$equipmentId} not found."];
        }

        $validStatuses = ['Borrowed', 'Returned', 'Damaged', 'Lost'];
        if (!in_array($status, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid return status specified.'];
        }

        $conditionNotes = trim($conditionNotes);
        if ($conditionNotes === '') {
            $conditionNotes = ($status === 'Returned') ? 'Good' : 'Needs attention';
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE equipment 
                 SET BorrowStatus = ?, ConditionOnReturn = ? 
                 WHERE EquipmentID = ?"
            );
            $stmt->execute([$status, $conditionNotes, $equipmentId]);

            return [
                'success' => true,
                'message' => "Equipment {$equipmentId} ({$loan['ItemName']}) updated to status '{$status}'. Inspection condition recorded: '{$conditionNotes}'.",
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error updating equipment: ' . $e->getMessage()];
        }
    }

    /**
     * Process 4.4: Remove / Retire Damaged or Obsolete Equipment
     */
    public function retireEquipment(string $equipmentId, bool $isAdmin = false): array {
        if (!$isAdmin && !AuthService::isAdmin()) {
            return ['success' => false, 'message' => 'Unauthorized: Only Facility Administrators can retire or delete equipment records.'];
        }

        $loan = $this->getLoanById($equipmentId);
        if (!$loan) {
            return ['success' => false, 'message' => "Equipment record {$equipmentId} not found."];
        }

        if ($loan['BorrowStatus'] === 'Borrowed') {
            return [
                'success' => false,
                'message' => "Cannot remove equipment {$equipmentId}: The item is currently actively borrowed by {$loan['BorrowerName']} for booking {$loan['BookingID']}. It must be returned first.",
            ];
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM equipment WHERE EquipmentID = ?");
            $stmt->execute([$equipmentId]);

            return [
                'success' => true,
                'message' => "Equipment record {$equipmentId} ({$loan['ItemName']}) has been successfully retired from the database.",
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error retiring equipment: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve statistics summary for dashboard KPIs
     */
    public function getStatistics(?string $userId = null): array {
        $sql = "SELECT 
                    COUNT(*) AS TotalLoans,
                    SUM(CASE WHEN BorrowStatus = 'Borrowed' THEN 1 ELSE 0 END) AS ActiveLoans,
                    SUM(CASE WHEN BorrowStatus = 'Returned' THEN 1 ELSE 0 END) AS ReturnedLoans,
                    SUM(CASE WHEN BorrowStatus IN ('Damaged', 'Lost') THEN 1 ELSE 0 END) AS DamagedOrLost
                FROM equipment eq
                JOIN booking b ON eq.BookingID = b.BookingID
                WHERE 1=1";
        $params = [];
        if (!empty($userId)) {
            $sql .= " AND b.UserID = ?";
            $params[] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return [
            'total'     => (int)($row['TotalLoans'] ?? 0),
            'active'    => (int)($row['ActiveLoans'] ?? 0),
            'returned'  => (int)($row['ReturnedLoans'] ?? 0),
            'damaged'   => (int)($row['DamagedOrLost'] ?? 0),
        ];
    }
}
