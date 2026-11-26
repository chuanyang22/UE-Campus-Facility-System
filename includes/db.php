<?php
/**
 * Data Tier: Database Connection (PDO)
 * AMCS2194 Mini Project — UE Campus Facility Booking System
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

class Database {
    private static ?PDO $instance = null;

    private const DB_HOST = '127.0.0.1';
    private const DB_PORT = '3306';
    private const DB_NAME = 'campus_facility_db';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_PORT,
                self::DB_NAME,
                self::DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            } catch (PDOException $e) {
                // Log and show user-friendly message
                error_log('Database Connection Error: ' . $e->getMessage());
                die('Database connection failed. Please ensure the MariaDB service in XAMPP is running.');
            }
        }

        return self::$instance;
    }
}

// Convenient helper function
function getDB(): PDO {
    return Database::getConnection();
}
