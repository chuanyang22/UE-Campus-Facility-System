<?php
/**
 * Auth & Session Management Helper
 * AMCS2194 Mini Project — Supports A240112 (Student), T0001 (Technician) & S0012 (Admin)
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

class AuthService {
    private const REMEMBER_COOKIE = 'ue_remember_token';
    private const REMEMBER_ID_COOKIE = 'ue_remember_id';
    private const REMEMBER_SECRET = 'UE_Campus_Facility_2026_Key';

    public static function ensureDemoUsers(): void {
        try {
            $db = getDB();
            $db->exec("ALTER TABLE user ADD COLUMN IF NOT EXISTS AvatarPath VARCHAR(255) DEFAULT NULL");
            $db->exec("INSERT IGNORE INTO user (UserID, FullName, CampusEmail, PhoneNo, UserRole, AccountStatus, PasswordHash) VALUES
                ('A240112', 'Shivendra (Student)', 'a240112@student.campus.edu', '012-3456789', 'Student', 'Active', '\$2y\$10\$e8wFwO4q49tX6iV0v3cQyeC.kR11GfZo23YhZz6Lqm8.W4E2Z9jKm'),
                ('S0012', 'Facility Administrator', 'admin0012@campus.edu', '019-9988770', 'Admin', 'Active', '\$2y\$10\$e8wFwO4q49tX6iV0v3cQyeC.kR11GfZo23YhZz6Lqm8.W4E2Z9jKm')");
        } catch (Throwable $e) {}
    }

    public static function getDemoUsers(): array {
        self::ensureDemoUsers();
        $db = getDB();
        $stmt = $db->query('SELECT UserID, FullName, CampusEmail, UserRole, AccountStatus FROM user WHERE UserID IN ("A240112", "T0001", "S0012", "U1001", "U1004") ORDER BY UserRole DESC, UserID ASC');
        return $stmt->fetchAll();
    }

    public static function setActiveUser(string $userId): bool {
        self::ensureDemoUsers();
        $db = getDB();
        $stmt = $db->prepare('SELECT UserID, FullName, CampusEmail, PhoneNo, UserRole, AccountStatus, AvatarPath FROM user WHERE UserID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    public static function setRememberCookie(string $userId, string $identifier = ''): void {
        $sig = hash_hmac('sha256', $userId, self::REMEMBER_SECRET);
        $val = base64_encode($userId . '|' . $sig);
        $exp = time() + (30 * 24 * 60 * 60); // 30 days
        setcookie(self::REMEMBER_COOKIE, $val, [
            'expires'  => $exp,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if ($identifier !== '') {
            setcookie(self::REMEMBER_ID_COOKIE, $identifier, [
                'expires'  => $exp,
                'path'     => '/',
                'samesite' => 'Lax',
            ]);
        }
    }

    public static function getRememberedIdentifier(): string {
        return trim((string)($_COOKIE[self::REMEMBER_ID_COOKIE] ?? ''));
    }

    public static function clearRememberCookie(): void {
        setcookie(self::REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
    }

    private static function tryAutoLoginFromCookie(): void {
        if (isset($_SESSION['user']) || empty($_COOKIE[self::REMEMBER_COOKIE])) {
            return;
        }
        $decoded = base64_decode((string)$_COOKIE[self::REMEMBER_COOKIE], true);
        if (!$decoded || !str_contains($decoded, '|')) {
            return;
        }
        [$uid, $sig] = explode('|', $decoded, 2);
        $expected = hash_hmac('sha256', $uid, self::REMEMBER_SECRET);
        if (hash_equals($expected, $sig)) {
            self::setActiveUser($uid);
        }
    }

    public static function isLoggedIn(): bool {
        self::tryAutoLoginFromCookie();
        return isset($_SESSION['user']) && !empty($_SESSION['user']['UserID']);
    }

    public static function getCurrentUser(): array {
        if (!self::isLoggedIn()) {
            return [
                'UserID'        => '',
                'FullName'      => 'Guest',
                'CampusEmail'   => '',
                'PhoneNo'       => '',
                'UserRole'      => 'Guest',
                'AccountStatus' => 'Guest',
            ];
        }
        return $_SESSION['user'];
    }

    public static function logout(): void {
        unset($_SESSION['user']);
        self::clearRememberCookie();
    }

    public static function requireLogin(string $redirectAfter = ''): void {
        if (self::isLoggedIn()) {
            return;
        }
        if ($redirectAfter !== '') {
            $_SESSION['redirect_after_login'] = $redirectAfter;
        }
        self::setFlash('info', 'Please sign in or register an account to continue with your booking.');
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = (strpos($scriptName, '/UE-Campus-Facility-System') !== false)
            ? '/UE-Campus-Facility-System/'
            : '/';
        header('Location: ' . $baseUrl . 'modules/user/login.php');
        exit;
    }

    public static function isAdmin(): bool {
        $user = self::getCurrentUser();
        return ($user['UserRole'] ?? '') === 'Admin';
    }

    public static function isTechnician(): bool {
        $user = self::getCurrentUser();
        return ($user['UserRole'] ?? '') === 'Technician';
    }

    public static function isMaintenanceStaff(): bool {
        $user = self::getCurrentUser();
        return in_array($user['UserRole'] ?? '', ['Admin', 'Technician'], true);
    }

    public static function isStudent(): bool {
        $user = self::getCurrentUser();
        return ($user['UserRole'] ?? '') === 'Student';
    }

    public static function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    public static function getFlash(): ?array {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

// Handle quick switch GET request or logout
if (isset($_GET['logout'])) {
    AuthService::logout();
    AuthService::setFlash('info', 'You have signed out.');
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectUrl);
    exit;
}

if (isset($_GET['switch_user'])) {
    $targetId = trim((string)$_GET['switch_user']);
    if ($targetId === '') {
        AuthService::logout();
    } else {
        AuthService::setActiveUser($targetId);
    }
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectUrl);
    exit;
}
