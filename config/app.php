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

    'security' => [
        'session_lifetime' => 86400 * 7, // 7 days
        'cookie_secure' => false, // Set true in production with HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'csrf_token_name' => 'cielo_csrf_token',
    ],
];
