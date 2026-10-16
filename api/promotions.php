<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Promotions & Happy Hours API
 * 
 * Endpoint: GET /api/promotions.php
 * Returns active Happy Hours, Combos, and featured public vouchers.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Services\PromotionService;
use App\Models\Coupon;

$testTime = isset($_GET['time']) ? (string)$_GET['time'] : null;
$activeOffers = PromotionService::getActiveOffers($testTime);

$happyHour = null;
foreach ($activeOffers as $off) {
    if ($off['type'] === 'happy_hour') {
        $happyHour = $off;
        break;
    }
}

// Featured public coupons
$today = date('Y-m-d');
$publicCoupons = Coupon::where(
    "`is_active` = 1 AND `start_date` <= :today1 AND `end_date` >= :today2 AND `audience` = 'all'",
    ['today1' => $today, 'today2' => $today],
    'value DESC',
    5
);

Response::json(true, [
    'happy_hour_active' => $happyHour !== null,
    'active_happy_hour' => $happyHour,
    'active_offers' => $activeOffers,
    'featured_coupons' => array_map(function ($c) {
        return [
            'code' => $c['code'],
            'title' => $c['title'],
            'type' => $c['type'],
            'value' => (float)$c['value'],
            'min_order_rupees' => (int)($c['min_order_paise'] / 100),
            'end_date' => $c['end_date'],
        ];
    }, $publicCoupons),
], 'Active promotions retrieved successfully.');
