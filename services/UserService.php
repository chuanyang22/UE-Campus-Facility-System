<?php
/**
 * Business Services Tier: User Account & Profile Management Service
 * AMCS2194 Mini Project — Module 1 (FR-1.1 to FR-1.4)
 * Student 1: Tee Chuan Yang
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

class UserService {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
        $this->ensureSchemaReady();
    }

    private function ensureSchemaReady(): void {
        try {
            $this->db->exec("ALTER TABLE user ADD COLUMN IF NOT EXISTS AvatarPath VARCHAR(255) DEFAULT NULL");
            $this->db->exec("ALTER TABLE user ADD COLUMN IF NOT EXISTS DateOfBirth DATE DEFAULT NULL");
            $this->db->exec("ALTER TABLE user ADD COLUMN IF NOT EXISTS Gender VARCHAR(20) DEFAULT NULL");
        } catch (Throwable $e) {}
    }

    /**
     * 1.1 Register / Create New User Account (FR-1.1)
     */
    public function registerUser(array $data, bool $allowAdminRoles = false): array {
        $errors = $this->validateRegistrationInput($data, $allowAdminRoles);
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please correct the validation errors in the registration form.',
                'errors'  => $errors,
                'user_id' => null,
            ];
        }

        $userId      = strtoupper(trim((string)$data['UserID']));
        $fullName    = trim((string)$data['FullName']);
        $campusEmail = strtolower(trim((string)$data['CampusEmail']));
        $phoneNo     = trim((string)$data['PhoneNo']);
        $userRole    = trim((string)($data['UserRole'] ?? 'Student'));
        $password    = (string)$data['Password'];
        $status      = $allowAdminRoles ? trim((string)($data['AccountStatus'] ?? 'Active')) : 'Active';

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO user (UserID, FullName, CampusEmail, PhoneNo, UserRole, AccountStatus, PasswordHash)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $fullName,
                $campusEmail,
                $phoneNo,
                $userRole,
                $status,
                $passwordHash,
            ]);

            return [
                'success' => true,
                'message' => "Account {$userId} ({$fullName}) registered successfully.",
                'errors'  => [],
                'user_id' => $userId,
            ];
        } catch (PDOException $e) {
            error_log('UserService::registerUser error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error while creating user account.',
                'errors'  => ['general' => 'Could not create account. Please try again.'],
                'user_id' => null,
            ];
        }
    }

    /**
     * Validate registration / creation fields
     */
    private function validateRegistrationInput(array $data, bool $allowAdminRoles): array {
        $errors = [];

        $userId = strtoupper(trim((string)($data['UserID'] ?? '')));
        if ($userId === '') {
            $errors['UserID'] = 'Student / Staff ID is required.';
        } elseif (!preg_match('/^[A-Z0-9]{3,10}$/', $userId)) {
            $errors['UserID'] = 'ID must be 3–10 alphanumeric characters (e.g., A240115 or S0020).';
        } elseif ($this->userIdExists($userId)) {
            $errors['UserID'] = "User ID '{$userId}' is already registered.";
        }

        $fullName = trim((string)($data['FullName'] ?? ''));
        if ($fullName === '' || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors['FullName'] = 'Full name must be between 2 and 100 characters.';
        }

        $email = strtolower(trim((string)($data['CampusEmail'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['CampusEmail'] = 'Please enter a valid institutional email address.';
        } elseif (mb_strlen($email) > 100) {
            $errors['CampusEmail'] = 'Email must not exceed 100 characters.';
        } elseif ($this->emailExists($email)) {
            $errors['CampusEmail'] = 'This campus email is already registered.';
        }

        $phone = trim((string)($data['PhoneNo'] ?? ''));
        if ($phone === '' || !preg_match('/^[0-9+\-\s]{8,15}$/', $phone)) {
            $errors['PhoneNo'] = 'Phone number must be 8–15 digits (e.g., 012-3456789).';
        }

        $allowedRoles = $allowAdminRoles
            ? ['Student', 'Staff', 'Admin', 'Technician']
            : ['Student', 'Staff'];
        $role = trim((string)($data['UserRole'] ?? ''));
        if (!in_array($role, $allowedRoles, true)) {
            $errors['UserRole'] = 'Please select a valid user role.';
        }

        $password = (string)($data['Password'] ?? '');
        $confirm  = (string)($data['ConfirmPassword'] ?? '');
        if (strlen($password) < 6) {
            $errors['Password'] = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm) {
            $errors['ConfirmPassword'] = 'Password confirmation does not match.';
        }

        return $errors;
    }

    /**
     * 1.2 Authenticate User by UserID or CampusEmail (FR-1.2)
     */
    public function authenticate(string $identifier, string $password): array {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Please enter both your ID/Email and password.',
                'user'    => null,
            ];
        }

        $stmt = $this->db->prepare(
            'SELECT UserID, FullName, CampusEmail, PhoneNo, UserRole, AccountStatus, BookingBanUntil, AvatarPath, PasswordHash
             FROM user
             WHERE UserID = ? OR LOWER(CampusEmail) = LOWER(?)
             LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['PasswordHash'])) {
            return [
                'success' => false,
                'message' => 'Invalid login credentials. Please check your ID/Email and password.',
                'user'    => null,
            ];
        }

        if ($user['AccountStatus'] !== 'Active') {
            return [
                'success' => false,
                'message' => "Your account is currently {$user['AccountStatus']}. Please contact a Facility Administrator.",
                'user'    => null,
            ];
        }

        unset($user['PasswordHash']);

        return [
            'success' => true,
            'message' => 'Login successful. Welcome back, ' . $user['FullName'] . '!',
            'user'    => $user,
        ];
    }

    /**
     * 1.2 Retrieve Single User Profile (FR-1.2)
     */
    public function getUserById(string $userId): ?array {
        $stmt = $this->db->prepare(
            'SELECT UserID, FullName, CampusEmail, PhoneNo, UserRole, AccountStatus, BookingBanUntil, StrikeResetAt, AvatarPath, DateOfBirth, Gender
             FROM user
             WHERE UserID = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * 1.2 Retrieve Booking History Summary for Profile Page (FR-1.2)
     */
    public function getUserBookingSummary(string $userId): array {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total_bookings,
                SUM(CASE WHEN BookingStatus IN ('Confirmed', 'Checked-In') THEN 1 ELSE 0 END) AS active_bookings,
                SUM(CASE WHEN BookingStatus = 'Completed' THEN 1 ELSE 0 END) AS completed_bookings,
                SUM(CASE WHEN BookingStatus = 'No-Show' THEN 1 ELSE 0 END) AS no_show_count,
                SUM(CASE WHEN BookingStatus = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_bookings
             FROM booking
             WHERE UserID = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return [
            'total_bookings'     => (int)($row['total_bookings'] ?? 0),
            'active_bookings'    => (int)($row['active_bookings'] ?? 0),
            'completed_bookings' => (int)($row['completed_bookings'] ?? 0),
            'no_show_count'      => (int)($row['no_show_count'] ?? 0),
            'cancelled_bookings' => (int)($row['cancelled_bookings'] ?? 0),
        ];
    }

    /**
     * 1.2 Admin Retrieve Filtered User List (FR-1.2)
     */
    public function getUsers(array $filters = []): array {
        $sql = 'SELECT UserID, FullName, CampusEmail, PhoneNo, UserRole, AccountStatus, BookingBanUntil
                FROM user
                WHERE 1=1';
        $params = [];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= ' AND (UserID LIKE ? OR FullName LIKE ? OR CampusEmail LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $role = trim((string)($filters['role'] ?? ''));
        if ($role !== '' && in_array($role, ['Student', 'Staff', 'Admin', 'Technician'], true)) {
            $sql .= ' AND UserRole = ?';
            $params[] = $role;
        }

        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '' && in_array($status, ['Active', 'Suspended', 'Inactive'], true)) {
            $sql .= ' AND AccountStatus = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY FIELD(AccountStatus, "Active", "Suspended", "Inactive"), UserRole ASC, UserID ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Admin KPI Statistics
     */
    public function getStatistics(): array {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN AccountStatus = 'Active' THEN 1 ELSE 0 END) AS active_users,
                SUM(CASE WHEN AccountStatus = 'Suspended' THEN 1 ELSE 0 END) AS suspended_users,
                SUM(CASE WHEN AccountStatus = 'Inactive' THEN 1 ELSE 0 END) AS inactive_users
             FROM user"
        );
        $row = $stmt->fetch();

        return [
            'total_users'     => (int)($row['total_users'] ?? 0),
            'active_users'    => (int)($row['active_users'] ?? 0),
            'suspended_users' => (int)($row['suspended_users'] ?? 0),
            'inactive_users'  => (int)($row['inactive_users'] ?? 0),
        ];
    }

    /**
     * 1.3 Update Personal Profile Details & Optional Password (FR-1.3)
     */
    public function updateProfile(string $userId, array $data): array {
        $errors = [];

        $fullName = trim((string)($data['FullName'] ?? ''));
        if ($fullName === '' || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors['FullName'] = 'Full name must be between 2 and 100 characters.';
        }

        $phoneNo = trim((string)($data['PhoneNo'] ?? ''));
        if ($phoneNo === '' || !preg_match('/^[0-9+\-\s]{8,15}$/', $phoneNo)) {
            $errors['PhoneNo'] = 'Phone number must be 8–15 digits (e.g., 012-3456789).';
        }

        $dob = trim((string)($data['DateOfBirth'] ?? ''));
        if ($dob !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob) {
                $errors['DateOfBirth'] = 'Please enter a valid date of birth (YYYY-MM-DD).';
            } elseif ($dob > date('Y-m-d')) {
                $errors['DateOfBirth'] = 'Date of birth cannot be in the future.';
            }
        } else {
            $dob = null;
        }

        $gender = trim((string)($data['Gender'] ?? ''));
        if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other'], true)) {
            $errors['Gender'] = 'Please select a valid gender option.';
        } elseif ($gender === '') {
            $gender = null;
        }

        $currentPassword = (string)($data['CurrentPassword'] ?? '');
        $newPassword     = (string)($data['NewPassword'] ?? '');
        $confirmPassword = (string)($data['ConfirmPassword'] ?? '');
        $changePassword  = ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '');

        $newHash = null;
        if ($changePassword) {
            $stmt = $this->db->prepare('SELECT PasswordHash FROM user WHERE UserID = ? LIMIT 1');
            $stmt->execute([$userId]);
            $existingHash = $stmt->fetchColumn();

            if (!$existingHash || !password_verify($currentPassword, (string)$existingHash)) {
                $errors['CurrentPassword'] = 'Current password is incorrect.';
            }
            if (strlen($newPassword) < 6) {
                $errors['NewPassword'] = 'New password must be at least 6 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors['ConfirmPassword'] = 'New password confirmation does not match.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            }
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please fix the highlighted validation errors.',
                'errors'  => $errors,
                'user'    => null,
            ];
        }

        if ($newHash !== null) {
            $stmt = $this->db->prepare(
                'UPDATE user SET FullName = ?, PhoneNo = ?, DateOfBirth = ?, Gender = ?, PasswordHash = ? WHERE UserID = ?'
            );
            $stmt->execute([$fullName, $phoneNo, $dob, $gender, $newHash, $userId]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE user SET FullName = ?, PhoneNo = ?, DateOfBirth = ?, Gender = ? WHERE UserID = ?'
            );
            $stmt->execute([$fullName, $phoneNo, $dob, $gender, $userId]);
        }

        return [
            'success' => true,
            'message' => $newHash !== null
                ? 'Profile details and password updated successfully.'
                : 'Profile details updated successfully.',
            'errors'  => [],
            'user'    => $this->getUserById($userId),
        ];
    }

    /**
     * 1.3 Admin Update User Account Details, Role & Status (FR-1.3)
     */
    public function adminUpdateUser(string $userId, array $data): array {
        $existing = $this->getUserById($userId);
        if (!$existing) {
            return [
                'success' => false,
                'message' => "User account '{$userId}' not found.",
                'errors'  => ['general' => 'User not found.'],
            ];
        }

        $errors = [];
        $fullName = trim((string)($data['FullName'] ?? ''));
        if ($fullName === '' || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
            $errors['FullName'] = 'Full name must be between 2 and 100 characters.';
        }

        $email = strtolower(trim((string)($data['CampusEmail'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['CampusEmail'] = 'Please enter a valid institutional email address.';
        } elseif ($this->emailExists($email, $userId)) {
            $errors['CampusEmail'] = 'Another account is already using this campus email.';
        }

        $phoneNo = trim((string)($data['PhoneNo'] ?? ''));
        if ($phoneNo === '' || !preg_match('/^[0-9+\-\s]{8,15}$/', $phoneNo)) {
            $errors['PhoneNo'] = 'Phone number must be 8–15 digits.';
        }

        $role = trim((string)($data['UserRole'] ?? ''));
        if (!in_array($role, ['Student', 'Staff', 'Admin', 'Technician'], true)) {
            $errors['UserRole'] = 'Invalid user role selected.';
        }

        $status = trim((string)($data['AccountStatus'] ?? ''));
        if (!in_array($status, ['Active', 'Suspended', 'Inactive'], true)) {
            $errors['AccountStatus'] = 'Invalid account status selected.';
        }

        $newPassword = (string)($data['NewPassword'] ?? '');
        if ($newPassword !== '' && strlen($newPassword) < 6) {
            $errors['NewPassword'] = 'Reset password must be at least 6 characters long.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Please correct the validation errors.',
                'errors'  => $errors,
            ];
        }

        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare(
                'UPDATE user SET FullName = ?, CampusEmail = ?, PhoneNo = ?, UserRole = ?, AccountStatus = ?, PasswordHash = ? WHERE UserID = ?'
            );
            $stmt->execute([$fullName, $email, $phoneNo, $role, $status, $hash, $userId]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE user SET FullName = ?, CampusEmail = ?, PhoneNo = ?, UserRole = ?, AccountStatus = ? WHERE UserID = ?'
            );
            $stmt->execute([$fullName, $email, $phoneNo, $role, $status, $userId]);
        }

        return [
            'success' => true,
            'message' => "User account {$userId} updated successfully.",
            'errors'  => [],
        ];
    }

    /**
     * 1.4 Deactivate / Soft-Delete User Account (FR-1.4)
     * Preserves historical booking/maintenance/feedback referential integrity.
     */
    public function deactivateUser(string $userId, string $actingAdminId): array {
        if ($userId === $actingAdminId) {
            return [
                'success' => false,
                'message' => 'You cannot deactivate your own currently active administrator account.',
            ];
        }

        $existing = $this->getUserById($userId);
        if (!$existing) {
            return [
                'success' => false,
                'message' => "User account '{$userId}' not found.",
            ];
        }

        if ($existing['AccountStatus'] === 'Inactive') {
            return [
                'success' => false,
                'message' => "User account {$userId} is already Inactive.",
            ];
        }

        $stmt = $this->db->prepare("UPDATE user SET AccountStatus = 'Inactive' WHERE UserID = ?");
        $stmt->execute([$userId]);

        return [
            'success' => true,
            'message' => "Account {$userId} ({$existing['FullName']}) has been deactivated. Historical booking records are preserved.",
        ];
    }

    /**
     * Reactivate an Inactive or Suspended Account
     */
    public function reactivateUser(string $userId): array {
        $existing = $this->getUserById($userId);
        if (!$existing) {
            return [
                'success' => false,
                'message' => "User account '{$userId}' not found.",
            ];
        }

        $stmt = $this->db->prepare("UPDATE user SET AccountStatus = 'Active', BookingBanUntil = NULL WHERE UserID = ?");
        $stmt->execute([$userId]);

        return [
            'success' => true,
            'message' => "Account {$userId} ({$existing['FullName']}) has been reactivated.",
        ];
    }

    /**
     * Save Cropped Profile Avatar Image (base64 PNG/JPEG/WebP from Canvas Cropper)
     */
    public function saveCroppedAvatar(string $userId, string $dataUrl): array {
        if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/', $dataUrl, $matches)) {
            return [
                'success' => false,
                'message' => 'Invalid image format. Please upload a PNG, JPG, or WebP image.',
                'path'    => null,
            ];
        }

        $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode($matches[2], true);
        if ($binary === false || strlen($binary) > 2 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Image could not be decoded or exceeds 2MB size limit.',
                'path'    => null,
            ];
        }

        $uploadDir = __DIR__ . '/../assets/uploads/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        // Remove previous avatar if exists
        $existing = $this->getUserById($userId);
        if (!empty($existing['AvatarPath'])) {
            $oldFull = __DIR__ . '/../' . ltrim($existing['AvatarPath'], '/');
            if (is_file($oldFull)) {
                @unlink($oldFull);
            }
        }

        $safeId = preg_replace('/[^A-Za-z0-9_-]/', '', $userId);
        $fileName = $safeId . '_' . time() . '.' . $ext;
        $relPath  = 'assets/uploads/avatars/' . $fileName;
        $fullPath = $uploadDir . '/' . $fileName;

        if (file_put_contents($fullPath, $binary) === false) {
            return [
                'success' => false,
                'message' => 'Failed to save cropped profile picture on server.',
                'path'    => null,
            ];
        }

        $stmt = $this->db->prepare('UPDATE user SET AvatarPath = ? WHERE UserID = ?');
        $stmt->execute([$relPath, $userId]);

        return [
            'success' => true,
            'message' => 'Profile picture updated successfully!',
            'path'    => $relPath,
        ];
    }

    private function userIdExists(string $userId): bool {
        $stmt = $this->db->prepare('SELECT 1 FROM user WHERE UserID = ? LIMIT 1');
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    private function emailExists(string $email, ?string $excludeUserId = null): bool {
        if ($excludeUserId !== null) {
            $stmt = $this->db->prepare('SELECT 1 FROM user WHERE LOWER(CampusEmail) = LOWER(?) AND UserID != ? LIMIT 1');
            $stmt->execute([$email, $excludeUserId]);
        } else {
            $stmt = $this->db->prepare('SELECT 1 FROM user WHERE LOWER(CampusEmail) = LOWER(?) LIMIT 1');
            $stmt->execute([$email]);
        }
        return (bool)$stmt->fetchColumn();
    }
}
