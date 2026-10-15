<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Authoritative Cart Validation API Endpoint
 *
 * Endpoint: POST /api/cart_validate.php
 * Verifies live item prices, stock availability, variant/add-on math,
 * promo coupons, loyalty points discount, and dynamic GST calculations.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Core\Money;
use App\Models\MenuItem;
use App\Models\ItemVariant;
use App\Models\ItemAddon;
use App\Models\Coupon;
use App\Models\PointsLedger;
use App\Services\SettingsService;

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$cartItems = (array)($input['items'] ?? []);
$couponCode = strtoupper(trim((string)($input['coupon_code'] ?? '')));
$redeemPoints = max(0, (int)($input['redeem_points'] ?? 0));
$userId = !empty($input['user_id']) ? (int)$input['user_id'] : ($_SESSION['cielo_user']['id'] ?? null);

if (empty($cartItems)) {
    Response::json(false, [], 'Cart is empty.', 422);
    exit;
}

try {
    $lineItems = [];
    $rawSubtotalPaise = 0;

    foreach ($cartItems as $index => $ci) {
        $itemId = (int)($ci['id'] ?? 0);
        $variantId = !empty($ci['variant_id']) ? (int)$ci['variant_id'] : null;
        $addonIds = (array)($ci['addon_ids'] ?? []);
        $quantity = max(1, (int)($ci['quantity'] ?? 1));
        $note = trim((string)($ci['note'] ?? ''));

        // 1. Fetch Item
        $item = MenuItem::find($itemId);
        if (!$item || (int)$item['is_available'] !== 1) {
            Response::json(false, [], "Item '" . ($item['name'] ?? "ID {$itemId}") . "' is currently sold out.", 422);
            exit;
        }

        // 2. Base / Variant Price
        $unitPricePaise = (int)$item['price_paise'];
        $variantName = null;

        if ($variantId !== null) {
            $variant = ItemVariant::find($variantId);
            if ($variant && (int)$variant['item_id'] === $itemId) {
                $unitPricePaise = (int)$variant['price_paise'];
                $variantName = (string)$variant['name'];
            }
        }

        // 3. Add-ons Price
        $addonsSelected = [];
        $addonsExtraPaise = 0;

        foreach ($addonIds as $aid) {
            $addon = ItemAddon::find((int)$aid);
            if ($addon && (int)$addon['item_id'] === $itemId && (int)$addon['is_available'] === 1) {
                $extraPaise = (int)$addon['extra_price_paise'];
                $addonsExtraPaise += $extraPaise;
                $addonsSelected[] = [
                    'id' => (int)$addon['id'],
                    'name' => (string)$addon['name'],
                    'extra_price_paise' => $extraPaise,
                ];
            }
        }

        $singleItemTotalPaise = $unitPricePaise + $addonsExtraPaise;
        $lineItemTotalPaise = $singleItemTotalPaise * $quantity;
        $rawSubtotalPaise += $lineItemTotalPaise;

        $lineItems[] = [
            'item_id' => $itemId,
            'name' => (string)$item['name'],
            'variant_id' => $variantId,
            'variant_name' => $variantName,
            'addons' => $addonsSelected,
            'unit_price_paise' => $unitPricePaise,
            'addons_extra_paise' => $addonsExtraPaise,
            'single_item_total_paise' => $singleItemTotalPaise,
            'quantity' => $quantity,
            'line_total_paise' => $lineItemTotalPaise,
            'note' => $note,
        ];
    }

    // 4. Calculate Coupon Discount
    $couponDiscountPaise = 0;
    $appliedCoupon = null;

    if ($couponCode !== '') {
        $coupon = Coupon::findValidCoupon($couponCode);
        if ($coupon && $rawSubtotalPaise >= (int)$coupon['min_order_paise']) {
            if ($coupon['discount_type'] === 'percentage') {
                $couponDiscountPaise = Money::calculateDiscount(
                    $rawSubtotalPaise,
                    (string)$coupon['discount_type'],
                    (float)$coupon['discount_value'],
                    (int)$coupon['max_discount_paise']
                );
            } else {
                $couponDiscountPaise = min((int)$coupon['discount_value'], $rawSubtotalPaise);
            }
            $appliedCoupon = [
                'id' => (int)$coupon['id'],
                'code' => (string)$coupon['code'],
                'discount_paise' => $couponDiscountPaise,
                'discount_inr' => Money::toRupees($couponDiscountPaise),
            ];
        }
    }

    // 5. Calculate Loyalty Points Discount
    $loyaltyDiscountPaise = 0;
    $appliedPoints = 0;
    $loyaltyConfig = SettingsService::getLoyaltyRules();

    if ($redeemPoints > 0 && $userId !== null) {
        $userBalance = PointsLedger::getBalance($userId);
        $pointsToRedeem = min($redeemPoints, $userBalance);

        if ($pointsToRedeem > 0) {
            $valuePerPointPaise = (int)($loyaltyConfig['redeem_value_per_point_paise'] ?? 100);
            $rawPointsDiscount = $pointsToRedeem * $valuePerPointPaise;

            // Cap at configured max % of remaining subtotal (e.g. 50%)
            $subtotalAfterCoupon = max(0, $rawSubtotalPaise - $couponDiscountPaise);
            $maxAllowedDiscount = (int)round(($subtotalAfterCoupon * ($loyaltyConfig['max_redeem_percentage'] ?? 50)) / 100);
            $loyaltyDiscountPaise = min($rawPointsDiscount, $maxAllowedDiscount);
            $appliedPoints = (int)ceil($loyaltyDiscountPaise / $valuePerPointPaise);
        }
    }

    // 6. Net Subtotal & Dynamic Taxes
    $totalDiscountPaise = $couponDiscountPaise + $loyaltyDiscountPaise;
    $taxableSubtotalPaise = max(0, $rawSubtotalPaise - $totalDiscountPaise);

    $gstRates = SettingsService::getGstRates();
    $cgstPaise = Money::calculateTax($taxableSubtotalPaise, $gstRates['cgst_percent']);
    $sgstPaise = Money::calculateTax($taxableSubtotalPaise, $gstRates['sgst_percent']);
    $totalTaxPaise = $cgstPaise + $sgstPaise;

    $grandTotalPaise = $taxableSubtotalPaise + $totalTaxPaise;

    Response::json(true, [
        'line_items' => $lineItems,
        'item_count' => array_sum(array_column($lineItems, 'quantity')),
        'financials' => [
            'subtotal_paise' => $rawSubtotalPaise,
            'subtotal_inr' => Money::toRupees($rawSubtotalPaise),
            'coupon' => $appliedCoupon,
            'coupon_discount_paise' => $couponDiscountPaise,
            'loyalty_points_used' => $appliedPoints,
            'loyalty_discount_paise' => $loyaltyDiscountPaise,
            'total_discount_paise' => $totalDiscountPaise,
            'taxable_subtotal_paise' => $taxableSubtotalPaise,
            'cgst_percent' => $gstRates['cgst_percent'],
            'cgst_paise' => $cgstPaise,
            'sgst_percent' => $gstRates['sgst_percent'],
            'sgst_paise' => $sgstPaise,
            'total_tax_paise' => $totalTaxPaise,
            'grand_total_paise' => $grandTotalPaise,
            'grand_total_inr' => Money::toRupees($grandTotalPaise),
            'formatted_total' => Money::format($grandTotalPaise),
        ]
    ]);
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to validate cart: ' . $e->getMessage(), 500);
}
