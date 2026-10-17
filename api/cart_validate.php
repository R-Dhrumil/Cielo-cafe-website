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
use App\Models\UserReward;
use App\Models\PointsLedger;
use App\Services\SettingsService;

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$cartItems = (array)($input['items'] ?? []);
$couponCode = strtoupper(trim((string)($input['coupon_code'] ?? '')));
$rewardId = !empty($input['reward_id']) ? (int)$input['reward_id'] : null;
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

    // 4. Authoritative Promotional Engine (Happy Hours, Combos, Stacking Rules & Coupon)
    $evalItems = array_map(function ($li) {
        return [
            'item_id' => $li['item_id'],
            'name' => $li['name'],
            'price_paise' => $li['single_item_total_paise'],
            'quantity' => $li['quantity'],
        ];
    }, $lineItems);

    $promoEval = \App\Services\PromotionService::evaluateCart($evalItems, $couponCode !== '' ? $couponCode : null, $userId);

    $appliedOffers = $promoEval['applied_offers'] ?? [];
    $offerDiscountsPaise = (int)($promoEval['offer_discounts_paise'] ?? 0);
    $couponDiscountPaise = (int)($promoEval['coupon_discount_paise'] ?? 0);
    $appliedCoupon = null;

    if (!empty($promoEval['coupon']['success']) && !empty($promoEval['coupon']['coupon'])) {
        $cData = $promoEval['coupon']['coupon'];
        $appliedCoupon = [
            'id' => (int)$cData['id'],
            'code' => (string)$cData['code'],
            'discount_paise' => (int)$cData['discount_paise'],
            'discount_inr' => (float)($cData['discount_paise'] / 100),
        ];
    }

    // 5. Milestone Reward Voucher Deduction (Free dish / treat voucher)
    $rewardDiscountPaise = 0;
    $appliedReward = null;

    if ($rewardId !== null && $userId !== null) {
        $userReward = UserReward::findActiveById($rewardId, $userId);
        if ($userReward) {
            $rVal = !empty($userReward['reward_value_json']) ? json_decode($userReward['reward_value_json'], true) : [];
            $valPaise = (int)($rVal['discount_paise'] ?? 24000);
            $subtotalAfterPromos = max(0, $rawSubtotalPaise - $offerDiscountsPaise - $couponDiscountPaise);
            $rewardDiscountPaise = min($valPaise, $subtotalAfterPromos);

            $appliedReward = [
                'id' => (int)$userReward['id'],
                'title' => (string)($rVal['title'] ?? 'Milestone Reward'),
                'item_name' => (string)($rVal['item_name'] ?? 'Complimentary Dish'),
                'discount_paise' => $rewardDiscountPaise,
                'discount_inr' => (float)($rewardDiscountPaise / 100),
            ];
        }
    }

    // 6. Calculate Loyalty Points Discount (after promotional, coupon, and reward discounts)
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
            $subtotalAfterPromos = max(0, $rawSubtotalPaise - $offerDiscountsPaise - $couponDiscountPaise - $rewardDiscountPaise);
            $maxAllowedDiscount = (int)round(($subtotalAfterPromos * ($loyaltyConfig['max_redeem_percentage'] ?? 50)) / 100);
            $loyaltyDiscountPaise = min($rawPointsDiscount, $maxAllowedDiscount);
            $appliedPoints = (int)ceil($loyaltyDiscountPaise / $valuePerPointPaise);
        }
    }

    // 7. Net Subtotal & Dynamic Taxes
    $totalDiscountPaise = $offerDiscountsPaise + $couponDiscountPaise + $rewardDiscountPaise + $loyaltyDiscountPaise;
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
            'applied_offers' => $appliedOffers,
            'offer_discounts_paise' => $offerDiscountsPaise,
            'coupon' => $appliedCoupon,
            'coupon_eval' => $promoEval['coupon'] ?? null,
            'coupon_discount_paise' => $couponDiscountPaise,
            'applied_reward' => $appliedReward,
            'reward_discount_paise' => $rewardDiscountPaise,
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
