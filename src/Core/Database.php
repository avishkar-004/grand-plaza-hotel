<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Database Connection and Query Builder
 *
 * Supports both vulnerable (direct queries) and secure (prepared statements) modes
 *
 * @package App\Core
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;
    private bool $secureMode;

    private function __construct(array $config)
    {
        $this->config = $config;
        $this->secureMode = ($config['security_mode'] ?? 'secure') === 'secure';
        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     */
    private function connect(): void
    {
        $dbConfig = $this->config['database'] ?? [];
        $driver = $dbConfig['driver'] ?? 'sqlite';

        try {
            if ($driver === 'sqlite') {
                // SQLite connection
                $database = $dbConfig['database'] ?? __DIR__ . '/../../storage/database.sqlite';
                $dsn = "sqlite:$database";

                $this->connection = new PDO($dsn, null, null, $dbConfig['options'] ?? []);
                // Enable foreign keys for SQLite
                $this->connection->exec('PRAGMA foreign_keys = ON;');

            } else {
                // MySQL connection
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                    $dbConfig['host'] ?? '127.0.0.1',
                    $dbConfig['port'] ?? 3306,
                    $dbConfig['database'] ?? 'hotel_management_db',
                    $dbConfig['charset'] ?? 'utf8mb4'
                );

                $this->connection = new PDO(
                    $dsn,
                    $dbConfig['username'] ?? 'root',
                    $dbConfig['password'] ?? '',
                    $dbConfig['options'] ?? []
                );
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Database connection failed: " . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Execute a query (VULNERABLE MODE AVAILABLE)
     *
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement|false
     */
    public function query(string $query, array $params = []): PDOStatement|false
    {
        try {
            if (!$this->secureMode && empty($params)) {
                // VULNERABLE: Direct query execution (for demo purposes)
                return $this->connection->query($query);
            }

            // SECURE: Prepared statement
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database Query Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute a query and fetch all results
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Execute a query and fetch single row
     */
    public function fetchOne(string $query, array $params = []): array|false
    {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Execute a query and return affected rows
     */
    public function execute(string $query, array $params = []): int
    {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->rowCount() : 0;
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Check if in secure mode
     */
    public function isSecureMode(): bool
    {
        return $this->secureMode;
    }

    /**
     * Get the database driver name (sqlite or mysql)
     */
    public function getDriver(): string
    {
        return $this->config['database']['driver'] ?? 'sqlite';
    }

    /**
     * Get SQL for current timestamp based on driver
     */
    public function now(): string
    {
        return $this->getDriver() === 'sqlite' ? "datetime('now')" : "NOW()";
    }

    /**
     * Get SQL for current date based on driver
     */
    public function today(): string
    {
        return $this->getDriver() === 'sqlite' ? "date('now')" : "CURDATE()";
    }

    /**
     * Get SQL for date interval based on driver
     */
    public function dateAdd(string $interval, int $value): string
    {
        if ($this->getDriver() === 'sqlite') {
            return "datetime('now', '+{$value} {$interval}')";
        }
        $mysqlInterval = strtoupper(rtrim($interval, 's')); // minutes -> MINUTE
        return "DATE_ADD(NOW(), INTERVAL {$value} {$mysqlInterval})";
    }

    /**
     * Get SQL for date subtract based on driver
     */
    public function dateSub(string $interval, int $value): string
    {
        if ($this->getDriver() === 'sqlite') {
            return "datetime('now', '-{$value} {$interval}')";
        }
        $mysqlInterval = strtoupper(rtrim($interval, 's'));
        return "DATE_SUB(NOW(), INTERVAL {$value} {$mysqlInterval})";
    }

    /**
     * Close connection
     */
    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }
}
