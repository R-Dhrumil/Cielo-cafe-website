<?php
/**
 * Cielo Cafe and Restro - Database Configuration
 * Supports MySQL 8.0+ (Production) and SQLite (Local / Testing)
 */

return [
    'default' => getenv('DB_CONNECTION') ?: 'sqlite',

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => (int)(getenv('DB_PORT') ?: 3306),
            'database' => getenv('DB_DATABASE') ?: 'cielo_cafe',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                (defined('Pdo\Mysql::ATTR_INIT_COMMAND') ? \Pdo\Mysql::ATTR_INIT_COMMAND : 1002) => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ],
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => (function() {
                $raw = (string)(getenv('DB_DATABASE') ?: '');
                if (empty($raw) || $raw === 'cielo_cafe') {
                    return (defined('CIELO_DATABASE') ? CIELO_DATABASE : __DIR__ . '/../database') . '/cielo.sqlite';
                }
                if (str_starts_with($raw, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $raw)) {
                    return $raw;
                }
                $root = defined('CIELO_ROOT') ? CIELO_ROOT : dirname(__DIR__);
                return $root . '/' . ltrim($raw, '/');
            })(),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
