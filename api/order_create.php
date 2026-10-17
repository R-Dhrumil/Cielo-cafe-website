<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Order Creation API Endpoint
 *
 * Endpoint: POST /api/order_create.php
 * Creates authoritative order, itemized line records, applies coupons,
 * logs daily customer visits, and returns order tracking details.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Core\Database;
use App\Core\Money;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\ItemVariant;
use App\Models\ItemAddon;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use App\Models\Visit;
use App\Models\UserReward;
use App\Models\PointsLedger;
use App\Services\TableSessionService;
use App\Services\SettingsService;
use App\Services\LoyaltyService;

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$cartItems = (array)($input['items'] ?? []);
$phone = trim((string)($input['phone'] ?? ''));
$name = trim((string)($input['name'] ?? ''));
$paymentMethod = trim((string)($input['payment_method'] ?? 'counter_cash'));
$couponCode = strtoupper(trim((string)($input['coupon_code'] ?? '')));
$rewardId = !empty($input['reward_id']) ? (int)$input['reward_id'] : null;
$redeemPoints = max(0, (int)($input['redeem_points'] ?? 0));
$kitchenNotes = trim((string)($input['kitchen_notes'] ?? ''));

// 1. Validation
if (empty($cartItems)) {
    Response::json(false, [], 'Cannot place an order with an empty cart.', 422);
    exit;
}

// Clean and validate phone number
$cleanPhone = preg_replace('/[^\d]/', '', $phone);
if (str_starts_with($cleanPhone, '91') && strlen($cleanPhone) === 12) {
    $cleanPhone = substr($cleanPhone, 2);
}

if (!preg_match('/^[6-9]\d{9}$/', $cleanPhone)) {
    Response::json(false, [], 'Please enter a valid 10-digit mobile number for order confirmation.', 422);
    exit;
}

// Check Table Session
$activeTable = TableSessionService::getActiveTable();
$tableId = !empty($activeTable['id']) ? (int)$activeTable['id'] : 1; // Default to Table 1 if counter order

// Allowed payment methods for V1
$allowedMethods = ['counter_cash', 'counter_upi', 'razorpay', 'cashfree'];
if (!in_array($paymentMethod, $allowedMethods, true)) {
    $paymentMethod = 'counter_cash';
}

$db = Database::getInstance();

try {
    $orderResult = $db->transaction(function () use (
        $cleanPhone,
        $name,
        $cartItems,
        $tableId,
        $paymentMethod,
        $couponCode,
        $rewardId,
        $redeemPoints,
        $kitchenNotes
    ) {
        // 2. Identify or Create Customer
        $user = User::findOrCreateByPhone($cleanPhone, $name !== '' ? $name : 'Valued Guest');
        $userId = (int)$user['id'];

        if ($name !== '' && ($user['name'] === 'Valued Guest' || empty($user['name']))) {
            User::updateById($userId, ['name' => $name]);
        }

        // 3. Authoritative Item Pricing Calculation
        $verifiedLineItems = [];
        $rawSubtotalPaise = 0;

        foreach ($cartItems as $ci) {
            $itemId = (int)($ci['id'] ?? 0);
            $variantId = !empty($ci['variant_id']) ? (int)$ci['variant_id'] : null;
            $addonIds = (array)($ci['addon_ids'] ?? []);
            $qty = max(1, (int)($ci['quantity'] ?? 1));
            $note = trim((string)($ci['note'] ?? ''));

            $item = MenuItem::find($itemId);
            if (!$item || (int)$item['is_available'] !== 1) {
                throw new \RuntimeException("Item '" . ($item['name'] ?? "ID {$itemId}") . "' is sold out.");
            }

            $unitPricePaise = (int)$item['price_paise'];
            $variantName = null;
            if ($variantId !== null) {
                $variant = ItemVariant::find($variantId);
                if ($variant && (int)$variant['item_id'] === $itemId) {
                    $unitPricePaise = (int)$variant['price_paise'];
                    $variantName = (string)$variant['name'];
                }
            }

            $addonsSelected = [];
            $addonsExtraPaise = 0;
            foreach ($addonIds as $aid) {
                $addon = ItemAddon::find((int)$aid);
                if ($addon && (int)$addon['item_id'] === $itemId && (int)$addon['is_available'] === 1) {
                    $addonsExtraPaise += (int)$addon['extra_price_paise'];
                    $addonsSelected[] = [
                        'id' => (int)$addon['id'],
                        'name' => (string)$addon['name'],
                        'price_paise' => (int)$addon['extra_price_paise'],
                    ];
                }
            }

            $totalUnitPaise = $unitPricePaise + $addonsExtraPaise;
            $lineTotalPaise = $totalUnitPaise * $qty;
            $rawSubtotalPaise += $lineTotalPaise;

            $verifiedLineItems[] = [
                'item_id' => $itemId,
                'name' => (string)$item['name'],
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'unit_price_paise' => $unitPricePaise,
                'addons_json' => !empty($addonsSelected) ? json_encode($addonsSelected, JSON_UNESCAPED_UNICODE) : null,
                'quantity' => $qty,
                'total_price_paise' => $lineTotalPaise,
                'notes' => $note !== '' ? $note : null,
            ];
        }

        // 4. Coupon Calculation
        $couponDiscountPaise = 0;
        $couponId = null;
        if ($couponCode !== '') {
            $coupon = Coupon::findValidCoupon($couponCode);
            if ($coupon && $rawSubtotalPaise >= (int)$coupon['min_order_paise']) {
                $couponId = (int)$coupon['id'];
                if ($coupon['discount_type'] === 'percentage') {
                    $couponDiscountPaise = Money::calculateDiscount(
                        $rawSubtotalPaise,
                        'percentage',
                        (float)$coupon['discount_value'],
                        (int)$coupon['max_discount_paise']
                    );
                } else {
                    $couponDiscountPaise = min((int)$coupon['discount_value'], $rawSubtotalPaise);
                }
            }
        }

        // 5. Milestone Reward Voucher Deduction
        $rewardDiscountPaise = 0;
        $validReward = null;
        if ($rewardId !== null) {
            $userReward = UserReward::findActiveById($rewardId, $userId);
            if ($userReward) {
                $rVal = !empty($userReward['reward_value_json']) ? json_decode($userReward['reward_value_json'], true) : [];
                $valPaise = (int)($rVal['discount_paise'] ?? 24000);
                $rewardDiscountPaise = min($valPaise, max(0, $rawSubtotalPaise - $couponDiscountPaise));
                $validReward = $userReward;
            }
        }

        // 6. Loyalty Points Discount
        $loyaltyDiscountPaise = 0;
        $pointsUsed = 0;
        $loyaltyRules = SettingsService::getLoyaltyRules();
        if ($redeemPoints > 0) {
            $userBalance = PointsLedger::getBalance($userId);
            $pointsToRedeem = min($redeemPoints, $userBalance);
            if ($pointsToRedeem > 0) {
                $valuePerPointPaise = (int)($loyaltyRules['redeem_value_per_point_paise'] ?? 100);
                $rawPointsDiscount = $pointsToRedeem * $valuePerPointPaise;
                $subtotalAfterPromos = max(0, $rawSubtotalPaise - $couponDiscountPaise - $rewardDiscountPaise);
                $maxAllowedDiscount = (int)round(($subtotalAfterPromos * ($loyaltyRules['max_redeem_percentage'] ?? 50)) / 100);
                $loyaltyDiscountPaise = min($rawPointsDiscount, $maxAllowedDiscount);
                $pointsUsed = (int)ceil($loyaltyDiscountPaise / $valuePerPointPaise);

                if ($pointsUsed > 0) {
                    PointsLedger::debit($userId, $pointsUsed, "Redeemed for order discount");
                }
            }
        }

        $totalDiscountPaise = $couponDiscountPaise + $rewardDiscountPaise + $loyaltyDiscountPaise;
        $taxableSubtotalPaise = max(0, $rawSubtotalPaise - $totalDiscountPaise);

        // 7. Dynamic GST calculation
        $gstRates = SettingsService::getGstRates();
        $cgstPaise = Money::calculateTax($taxableSubtotalPaise, $gstRates['cgst_percent']);
        $sgstPaise = Money::calculateTax($taxableSubtotalPaise, $gstRates['sgst_percent']);
        $totalTaxPaise = $cgstPaise + $sgstPaise;
        $grandTotalPaise = $taxableSubtotalPaise + $totalTaxPaise;

        // 8. Create Order Record
        $orderNumber = Order::generateOrderNumber();
        $orderId = (int)Order::create([
            'order_number' => $orderNumber,
            'user_id' => $userId,
            'table_id' => $tableId,
            'status' => 'placed',
            'subtotal_paise' => $rawSubtotalPaise,
            'discount_paise' => $totalDiscountPaise,
            'tax_paise' => $totalTaxPaise,
            'total_paise' => $grandTotalPaise,
            'payment_status' => 'pending',
            'payment_method' => $paymentMethod,
            'kitchen_notes' => $kitchenNotes !== '' ? $kitchenNotes : null,
        ]);

        // 9. Create Order Item Line Records
        foreach ($verifiedLineItems as $line) {
            $displayName = $line['name'] . (!empty($line['variant_name']) ? " ({$line['variant_name']})" : '');
            OrderItem::create([
                'order_id' => $orderId,
                'item_id' => $line['item_id'],
                'variant_id' => $line['variant_id'],
                'item_name' => $displayName,
                'unit_price_paise' => $line['unit_price_paise'],
                'addons_json' => $line['addons_json'],
                'quantity' => $line['quantity'],
                'total_price_paise' => $line['total_price_paise'],
                'item_note' => $line['notes'],
            ]);
        }

        // 10. Record Coupon Usage
        if ($couponId !== null && $couponDiscountPaise > 0) {
            CouponUsage::create([
                'coupon_id' => $couponId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'discount_paise' => $couponDiscountPaise,
                'used_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 11. Redeem Milestone Reward Voucher (if applied)
        if ($validReward !== null) {
            LoyaltyService::redeemReward((int)$validReward['id'], $orderId, $userId);
        }

        // 12. Record Customer Visit via Loyalty Retention Engine
        LoyaltyService::recordOrderVisit($orderId, $userId);

        // Update Session user
        startCieloSession();
        $_SESSION['cielo_user'] = [
            'id' => $userId,
            'name' => $user['name'],
            'phone' => $cleanPhone,
            'points_balance' => PointsLedger::getBalance($userId),
        ];

        return [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_paise' => $grandTotalPaise,
            'total_inr' => Money::toRupees($grandTotalPaise),
            'formatted_total' => Money::format($grandTotalPaise),
            'payment_method' => $paymentMethod,
            'redirect_url' => "/order_status.php?order={$orderNumber}",
        ];
    });

    Response::json(true, $orderResult, 'Order confirmed! Sent to Cielo kitchen.');
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to place order: ' . $e->getMessage(), 500);
}
