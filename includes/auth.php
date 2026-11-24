<?php
/**
 * Auth & Session Management Helper
 * AMCS2194 Mini Project — Supports A240112 (Student) & S0012 (Admin)
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

class AuthService {
    public static function getDemoUsers(): array {
        $db = getDB();
        $stmt = $db->query('SELECT UserID, FullName, CampusEmail, UserRole, AccountStatus FROM user WHERE UserID IN ("A240112", "S0012", "U1001", "U1004") ORDER BY UserRole DESC, UserID ASC');
        return $stmt->fetchAll();
    }

    public static function setActiveUser(string $userId): bool {
        $db = getDB();
        $stmt = $db->prepare('SELECT UserID, FullName, CampusEmail, PhoneNo, UserRole, AccountStatus FROM user WHERE UserID = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    public static function getCurrentUser(): array {
        if (!isset($_SESSION['user'])) {
            // Default to mockup student persona A240112
            if (!self::setActiveUser('A240112')) {
                self::setActiveUser('U1001');
            }
        }
        return $_SESSION['user'];
    }

    public static function isAdmin(): bool {
        $user = self::getCurrentUser();
        return ($user['UserRole'] ?? '') === 'Admin';
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

// Handle quick switch GET request
if (isset($_GET['switch_user'])) {
    $targetId = trim((string)$_GET['switch_user']);
    AuthService::setActiveUser($targetId);
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectUrl);
    exit;
}
