<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected static string $table = 'settings';

    private static array $cache = [];

    /**
     * Cast string database value according to setting type
     */
    public static function castValue(mixed $val, ?string $type = 'string'): mixed
    {
        return match ($type) {
            'json', 'array' => json_decode((string)$val ?: '[]', true) ?: [],
            'int', 'integer' => (int)$val,
            'float' => (float)$val,
            'bool', 'boolean' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
            default => $val,
        };
    }

    /**
     * Retrieve a setting value by key with optional default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $row = self::findBy('key', $key);
        if (!$row) {
            return $default;
        }

        $parsed = self::castValue($row['value'], $row['type'] ?? 'string');

        self::$cache[$key] = $parsed;
        return $parsed;
    }

    /**
     * Set / update a setting key-value pair
     */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        $serializedVal = match ($type) {
            'json', 'array' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'bool', 'boolean' => $value ? '1' : '0',
            default => (string)$value,
        };

        $existing = self::findBy('key', $key);
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            db()->update(self::$table, [
                'value' => $serializedVal,
                'type' => $type,
                'group' => $group,
                'updated_at' => $now,
            ], '`key` = :k', ['k' => $key]);
        } else {
            db()->insert(self::$table, [
                'key' => $key,
                'value' => $serializedVal,
                'group' => $group,
                'type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        self::$cache[$key] = $value;
    }

    /**
     * Get all settings grouped by category (e.g. 'cms', 'tax', 'loyalty', 'cafe')
     */
    public static function getGroup(string $group): array
    {
        $rows = self::where('`group` = :grp', ['grp' => $group], '`key` ASC');
        $result = [];
        foreach ($rows as $row) {
            $key = $row['key'];
            $result[$key] = self::get($key);
        }
        return $result;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
