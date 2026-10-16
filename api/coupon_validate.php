<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Coupon Validation API Endpoint
 *
 * Endpoint: POST /api/coupon_validate.php
 * Validates promo coupon against subtotal, usage caps, and validity windows.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Core\Money;
use App\Services\PromotionService;

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$code = strtoupper(trim((string)($input['code'] ?? '')));
$subtotalPaise = (int)($input['subtotal_paise'] ?? 0);
$userId = !empty($input['user_id']) 
    ? (int)$input['user_id'] 
    : ($_SESSION['cielo_customer_id'] ?? ($_SESSION['cielo_user']['id'] ?? null));

if ($code === '') {
    Response::json(false, [], 'Please enter a coupon code.', 422);
    exit;
}

if ($subtotalPaise <= 0) {
    Response::json(false, [], 'Order subtotal must be greater than zero.', 422);
    exit;
}

try {
    $result = PromotionService::validateCoupon($code, $subtotalPaise, $userId !== null ? (int)$userId : null);

    if (!$result['success']) {
        Response::json(false, [], $result['error'], 422);
        exit;
    }

    $cpn = $result['coupon'];
    $finalSubtotalPaise = max(0, $subtotalPaise - $cpn['discount_paise']);

    Response::json(true, [
        'coupon_id' => $cpn['id'],
        'code' => $cpn['code'],
        'discount_type' => $cpn['type'],
        'discount_value' => $cpn['value'],
        'discount_paise' => $cpn['discount_paise'],
        'discount_inr' => Money::toRupees($cpn['discount_paise']),
        'formatted_discount' => $cpn['discount_formatted'],
        'subtotal_before_paise' => $subtotalPaise,
        'subtotal_after_paise' => $finalSubtotalPaise,
        'audience' => $cpn['audience'] ?? 'all',
        'message' => $result['message'],
    ]);
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to validate coupon: ' . $e->getMessage(), 500);
}
