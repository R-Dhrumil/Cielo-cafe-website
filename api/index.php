<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - API Directory & Health Check
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;

Response::json(true, [
    'service' => 'Cielo Cafe and Restro API',
    'status' => 'online',
    'version' => '1.0.0',
    'environment' => getenv('APP_ENV') ?: 'production',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'menu' => [
            'method' => 'GET',
            'url' => '/api/menu.php',
            'desc' => 'Fetch dynamic menu catalog with categories, variants, and addons',
        ],
        'customer_lookup' => [
            'method' => 'GET',
            'url' => '/api/customer_lookup.php?phone={10digits}',
            'desc' => 'Identify patron by phone number and retrieve points balance',
        ],
        'cart_validate' => [
            'method' => 'POST',
            'url' => '/api/cart_validate.php',
            'desc' => 'Server-side authoritative cart calculation and tax breakdown',
        ],
        'coupon_validate' => [
            'method' => 'POST',
            'url' => '/api/coupon_validate.php',
            'desc' => 'Verify promo code and calculate discount amount',
        ],
        'order_create' => [
            'method' => 'POST',
            'url' => '/api/order_create.php',
            'desc' => 'Submit order with items, table session, and payment method',
        ],
        'order_status' => [
            'method' => 'GET',
            'url' => '/api/order_status.php?order={order_number}',
            'desc' => 'Live polling status for active customer orders',
        ],
        'kitchen_orders' => [
            'method' => 'GET / POST',
            'url' => '/api/kitchen_orders.php',
            'desc' => 'KDS order queue and status lifecycle transitions',
        ],
        'auth_otp' => [
            'method' => 'GET / POST',
            'url' => '/api/auth_otp.php?action={send_otp|verify_otp|me|update_profile|logout}',
            'desc' => 'Customer mobile OTP login, guest order merging, and profile management',
        ],
        'promotions' => [
            'method' => 'GET',
            'url' => '/api/promotions.php',
            'desc' => 'Active Happy Hours, combos, and public promotional vouchers',
        ],
    ]
], 'Cielo Cafe API is active and operational.');
