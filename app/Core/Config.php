<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $items = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$items[$file])) {
            $path = CIELO_CONFIG . '/' . $file . '.php';
            if (file_exists($path)) {
                self::$items[$file] = require $path;
            } else {
                return $default;
            }
        }

        $current = self::$items[$file];
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }
}
