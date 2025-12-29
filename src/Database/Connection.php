<?php

namespace Redium\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    /**
     * Get singleton PDO connection instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * Create new PDO connection from environment variables
     */
    private static function createConnection(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $pdo;
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Reset connection (useful for testing)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Fetch single row from PDOStatement
     */
    public static function fetch(\PDOStatement $statement): ?array
    {
        $response = $statement->fetch(PDO::FETCH_ASSOC);
        return $response ?: null;
    }

    /**
     * Fetch all rows from PDOStatement
     */
    public static function fetchAll(\PDOStatement $statement): array
    {
        $response = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $response ?: [];
    }
}
