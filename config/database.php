<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'sms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $connection = null;

    /**
     * Get the database connection (singleton).
     * 
     * @return PDO
     */
    public static function getConnection(): PDO {
        if (self::$connection === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                // Set MySQL timezone to match PHP (important for OTP expiry)
                $phpTimezone = date_default_timezone_get();
                $offset = (new DateTimeZone($phpTimezone))->getOffset(new DateTime());
                $hours = $offset / 3600;
                $sign = $hours >= 0 ? '+' : '-';
                $offsetStr = $sign . str_pad(abs($hours), 2, '0', STR_PAD_LEFT) . ':00';
                self::$connection->exec("SET time_zone = '$offsetStr'");
            } catch (PDOException $e) {
                die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$connection;
    }

    // Prevent instantiation
    private function __construct() {}
    private function __clone() {}
}