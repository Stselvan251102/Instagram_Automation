<?php
/**
 * Database Connection via PDO
 * Hostinger Shared Hosting (MySQL/MariaDB)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static ?string $lastError = null;

    /**
     * Get the PDO connection singleton
     * @return PDO|null Returns PDO instance or null if not configured/failed
     */
    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Check if database credentials have been configured
        if (empty(DB_NAME) || empty(DB_USER)) {
            self::$lastError = "Database credentials have not been configured in .env yet.";
            return null;
        }

        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
            return self::$instance;
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if database connection is active and healthy
     */
    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }

    /**
     * Get last connection error message if any
     */
    public static function getLastError(): ?string {
        return self::$lastError;
    }
}
