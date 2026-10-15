<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    private string $driver = 'mysql';

    private function __construct()
    {
        $defaultConnection = Config::get('database.default', 'sqlite');
        $config = Config::get("database.connections.{$defaultConnection}");

        if (!$config) {
            throw new RuntimeException("Database connection config for '{$defaultConnection}' not found.");
        }

        $this->driver = $config['driver'] ?? 'mysql';

        try {
            if ($this->driver === 'sqlite') {
                $dbPath = $config['database'];
                // Ensure parent directory exists
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $dsn = "sqlite:{$dbPath}";
                $this->pdo = new PDO($dsn, null, null, $config['options'] ?? []);
                // Enable foreign keys in SQLite
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset'] ?? 'utf8mb4'
                );
                $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed [{$this->driver}]: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function isSqlite(): bool
    {
        return $this->driver === 'sqlite';
    }

    public function isMysql(): bool
    {
        return $this->driver === 'mysql';
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException("Query error: {$e->getMessage()} | SQL: {$sql}", (int)$e->getCode(), $e);
        }
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetchColumn($column);
        return $result === false ? null : $result;
    }

    public function insert(string $table, array $data): int|string
    {
        $keys = array_keys($data);
        $fields = implode(', ', array_map(fn($k) => "`{$k}`", $keys));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", $keys));

        $sql = "INSERT INTO `{$table}` ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, $data);

        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        $params = [];

        foreach ($data as $key => $val) {
            $placeholder = "set_{$key}";
            $setClauses[] = "`{$key}` = :{$placeholder}";
            $params[$placeholder] = $val;
        }

        $setString = implode(', ', $setClauses);
        $sql = "UPDATE `{$table}` SET {$setString} WHERE {$where}";

        $mergedParams = array_merge($params, $whereParams);
        $stmt = $this->query($sql, $mergedParams);

        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);

        return $stmt->rowCount();
    }

    /**
     * Execute a callback wrapped in an ACID database transaction.
     * Automatically commits on success, and rolls back on any exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }
            throw $e;
        }
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(): string|int
    {
        return $this->pdo->lastInsertId();
    }
}
