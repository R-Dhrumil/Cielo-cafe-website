<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function getTable(): string
    {
        return static::$table;
    }

    public static function all(string $orderBy = 'id ASC'): array
    {
        $table = static::$table;
        return db()->fetchAll("SELECT * FROM `{$table}` ORDER BY {$orderBy}");
    }

    public static function find(int|string $id): ?array
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        return db()->fetch("SELECT * FROM `{$table}` WHERE `{$pk}` = :id LIMIT 1", ['id' => $id]);
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        $table = static::$table;
        return db()->fetch("SELECT * FROM `{$table}` WHERE `{$column}` = :val LIMIT 1", ['val' => $value]);
    }

    public static function where(string $whereSql = '1=1', array $params = [], string $orderBy = '', ?int $limit = null): array
    {
        $table = static::$table;
        $sql = "SELECT * FROM `{$table}` WHERE {$whereSql}";

        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        return db()->fetchAll($sql, $params);
    }

    public static function first(string $whereSql = '1=1', array $params = [], string $orderBy = ''): ?array
    {
        $rows = static::where($whereSql, $params, $orderBy, 1);
        return $rows[0] ?? null;
    }

    protected static bool $hasCreatedAt = true;
    protected static bool $hasUpdatedAt = true;

    public static function count(string $whereSql = '1=1', array $params = []): int
    {
        $table = static::$table;
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$whereSql}";
        return (int)db()->fetchColumn($sql, $params);
    }

    public static function create(array $data): int|string
    {
        $table = static::$table;
        $now = date('Y-m-d H:i:s');

        // Automatically set created_at and updated_at if supported by model
        if (static::$hasCreatedAt && !isset($data['created_at'])) {
            $data['created_at'] = $now;
        }
        if (static::$hasUpdatedAt && !isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        return db()->insert($table, $data);
    }

    public static function updateById(int|string $id, array $data): int
    {
        $table = static::$table;
        $pk = static::$primaryKey;

        if (static::$hasUpdatedAt && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return db()->update($table, $data, "`{$pk}` = :id_filter", ['id_filter' => $id]);
    }

    public static function update(int|string $id, array $data): int
    {
        return static::updateById($id, $data);
    }

    public static function deleteById(int|string $id): int
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        return db()->delete($table, "`{$pk}` = :id", ['id' => $id]);
    }

    public static function paginate(int $page = 1, int $perPage = 15, string $whereSql = '1=1', array $params = [], string $orderBy = 'id DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = self::count($whereSql, $params);
        $totalPages = (int)ceil($total / $perPage);

        $table = static::$table;
        $sql = "SELECT * FROM `{$table}` WHERE {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}";
        $items = db()->fetchAll($sql, $params);

        return [
            'items' => $items,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];
    }
}
