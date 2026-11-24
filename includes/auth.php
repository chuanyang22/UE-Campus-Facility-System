<?php
/**
 * Auth & Session Management Helper
 * AMCS2194 Mini Project — UE Campus Facility Booking System
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

class AuthService {
    /**
     * Get available demo accounts for easy switching during tutor practical demonstrations
     */
    public static function getDemoUsers(): array {
        $db = getDB();
        $stmt = $db->query('SELECT UserID, FullName, CampusEmail, UserRole, AccountStatus FROM user ORDER BY UserRole DESC, UserID ASC');
        return $stmt->fetchAll();
    }

    /**
     * Set active user session
     */
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

    /**
     * Get current user
     */
    public static function getCurrentUser(): array {
        if (!isset($_SESSION['user'])) {
            // Default to Student U1001 for seamless prototype testing
            self::setActiveUser('U1001');
        }
        return $_SESSION['user'];
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool {
        $user = self::getCurrentUser();
        return ($user['UserRole'] ?? '') === 'Admin';
    }

    /**
     * Check if user is student
     */
    public static function isStudent(): bool {
        $user = self::getCurrentUser();
        return ($user['UserRole'] ?? '') === 'Student';
    }

    /**
     * Set a flash message
     */
    public static function setFlash(string $type, string $message): void {
        $_SESSION['flash'] = [
            'type'    => $type, // 'success', 'danger', 'warning', 'info'
            'message' => $message,
        ];
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash(): ?array {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

// Handle switch-user GET request if triggered from navbar
if (isset($_GET['switch_user'])) {
    $targetId = trim((string)$_GET['switch_user']);
    if (AuthService::setActiveUser($targetId)) {
        AuthService::setFlash('success', 'Switched active session to ' . htmlspecialchars($_SESSION['user']['FullName']) . ' (' . htmlspecialchars($_SESSION['user']['UserRole']) . ').');
    }
    // Redirect back without switch_user param
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $redirectUrl);
    exit;
}
