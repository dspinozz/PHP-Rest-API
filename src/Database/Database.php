<?php

declare(strict_types=1);

namespace RestApi\Database;

use PDO;
use PDOException;

/**
 * Simple Database Abstraction
 * 
 * Works with any PDO-compatible database (MySQL, PostgreSQL, SQLite)
 * Provides prepared statements to prevent SQL injection
 */
class Database
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Create from DSN string
     */
    public static function fromDsn(string $dsn, ?string $username = null, ?string $password = null): self
    {
        try {
            $pdo = new PDO($dsn, $username, $password);
            return new self($pdo);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Create SQLite connection
     */
    public static function sqlite(string $path): self
    {
        return self::fromDsn('sqlite:' . $path);
    }

    /**
     * Create MySQL connection
     */
    public static function mysql(
        string $host,
        string $database,
        string $username,
        string $password,
        int $port = 3306
    ): self {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
        return self::fromDsn($dsn, $username, $password);
    }

    /**
     * Create PostgreSQL connection
     */
    public static function postgresql(
        string $host,
        string $database,
        string $username,
        string $password,
        int $port = 5432
    ): self {
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database);
        return self::fromDsn($dsn, $username, $password);
    }

    /**
     * Execute a query and return all results
     * 
     * @return array<int, array<string, mixed>> Array of associative arrays
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        return $results !== false ? $results : [];
    }

    /**
     * Execute a query and return first result
     * 
     * @return array<string, mixed>|null Associative array or null if not found
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Execute a query (INSERT, UPDATE, DELETE)
     * 
     * @return int Number of affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->rowCount();
        return $count !== false ? $count : 0;
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId(?string $name = null): string
    {
        $id = $this->pdo->lastInsertId($name);
        return $id !== false ? $id : '0';
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get underlying PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
