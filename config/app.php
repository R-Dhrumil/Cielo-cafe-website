<?php
/**
 * Cielo Cafe and Restro - Application Configuration
 */

return [
    'name' => 'Cielo Cafe & Restro',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => (bool)(getenv('APP_DEBUG') ?: true),
    'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'timezone' => 'Asia/Kolkata',

    'currency' => [
        'code' => 'INR',
        'symbol' => '₹',
        'fraction' => 'Paise',
    ],

    'paths' => [
        'root' => CIELO_ROOT,
        'app' => CIELO_APP,
        'config' => CIELO_CONFIG,
        'database' => CIELO_DATABASE,
        'storage' => CIELO_STORAGE,
        'logs' => CIELO_STORAGE . '/logs',
        'cache' => CIELO_STORAGE . '/cache',
        'backups' => CIELO_STORAGE . '/backups',
    ],

    'security' => [
        'session_lifetime' => 86400 * 7, // 7 days
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                           (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
                           (getenv('APP_ENV') === 'production'),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'csrf_token_name' => 'cielo_csrf_token',
    ],
];
