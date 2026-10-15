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
use App\Models\Coupon;
use App\Models\CouponUsage;

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$code = strtoupper(trim((string)($input['code'] ?? '')));
$subtotalPaise = (int)($input['subtotal_paise'] ?? 0);
$userId = !empty($input['user_id']) ? (int)$input['user_id'] : ($_SESSION['cielo_user']['id'] ?? null);

if ($code === '') {
    Response::json(false, [], 'Please enter a coupon code.', 422);
    exit;
}

if ($subtotalPaise <= 0) {
    Response::json(false, [], 'Order subtotal must be greater than zero.', 422);
    exit;
}

try {
    $coupon = Coupon::findValidCoupon($code);

    if (!$coupon) {
        Response::json(false, [], "Coupon code '{$code}' is invalid or expired.", 404);
        exit;
    }

    // Check minimum order requirement
    $minOrderPaise = (int)$coupon['min_order_paise'];
    if ($subtotalPaise < $minOrderPaise) {
        $neededInr = Money::toRupees($minOrderPaise);
        Response::json(false, [], "This coupon requires a minimum order of ₹{$neededInr}.", 422);
        exit;
    }

    // Check user usage cap
    if ($userId !== null && !empty($coupon['max_uses_per_user'])) {
        $userUses = CouponUsage::getUserUsageCount((int)$coupon['id'], $userId);
        if ($userUses >= (int)$coupon['max_uses_per_user']) {
            Response::json(false, [], "You have already reached the maximum usage limit for coupon '{$code}'.", 422);
            exit;
        }
    }

    // Check total campaign usage cap
    if (!empty($coupon['max_uses_total'])) {
        $totalUses = CouponUsage::getTotalUsageCount((int)$coupon['id']);
        if ($totalUses >= (int)$coupon['max_uses_total']) {
            Response::json(false, [], "Coupon '{$code}' has reached its maximum redemptions limit.", 422);
            exit;
        }
    }

    // Calculate discount
    $discountPaise = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $percent = (float)$coupon['discount_value'];
        $maxDiscountPaise = (int)$coupon['max_discount_paise'];
        $discountPaise = Money::calculateDiscount($subtotalPaise, 'percentage', $percent, $maxDiscountPaise);
    } else {
        // Flat discount
        $flatPaise = (int)$coupon['discount_value'];
        $discountPaise = min($flatPaise, $subtotalPaise);
    }

    $finalSubtotalPaise = max(0, $subtotalPaise - $discountPaise);

    Response::json(true, [
        'coupon_id' => (int)$coupon['id'],
        'code' => (string)$coupon['code'],
        'discount_type' => (string)$coupon['discount_type'],
        'discount_value' => (float)$coupon['discount_value'],
        'discount_paise' => $discountPaise,
        'discount_inr' => Money::toRupees($discountPaise),
        'formatted_discount' => Money::format($discountPaise),
        'subtotal_before_paise' => $subtotalPaise,
        'subtotal_after_paise' => $finalSubtotalPaise,
        'message' => "Coupon '{$code}' applied successfully!"
    ]);
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to validate coupon: ' . $e->getMessage(), 500);
}
