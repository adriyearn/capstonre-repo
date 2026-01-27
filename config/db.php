<?php
// config/db.php
declare(strict_types=1);

/**
 * DB connection helper using PDO.
 * Edit $dbHost, $dbName, $dbUser, $dbPass for your environment.
 */

class DB {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $dbHost = '127.0.0.1';
            $dbName = 'capstonerepo';
            $dbUser = 'root';
            $dbPass = ''; 
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            } catch (PDOException $e) {
                // In development you may echo the error; in production log it.
                error_log('DB Connection error: ' . $e->getMessage());
                throw $e;
            }
        }
        return self::$pdo;
    }
}