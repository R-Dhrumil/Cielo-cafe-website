<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Offer;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Visit;
use App\Models\PointsLedger;

class PromotionService
{
    /**
     * Validate coupon code against subtotal, usage limits, and audience guardrails
     */
    public static function validateCoupon(string $code, int $subtotalPaise, ?int $userId = null): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['success' => false, 'error' => 'Please enter a coupon code.'];
        }

        if ($subtotalPaise <= 0) {
            return ['success' => false, 'error' => 'Order subtotal must be greater than zero.'];
        }

        $coupon = Coupon::findValidCoupon($code);
        if (!$coupon) {
            return ['success' => false, 'error' => "Coupon code '{$code}' is invalid or expired."];
        }

        // 1. Minimum order requirement
        $minOrderPaise = (int)($coupon['min_order_paise'] ?? 0);
        if ($subtotalPaise < $minOrderPaise) {
            $neededRupees = (int)ceil($minOrderPaise / 100);
            return [
                'success' => false,
                'error' => "This coupon requires a minimum order of ₹{$neededRupees}.",
                'min_order_paise' => $minOrderPaise,
            ];
        }

        // 2. Audience Guardrails ('all', 'first_time', 'vip')
        $audience = strtolower($coupon['audience'] ?? 'all');
        if ($userId !== null && $audience !== 'all') {
            if ($audience === 'first_time') {
                // Check if user has any completed / paid orders
                $pastOrdersCount = (int)db()->fetchColumn(
                    "SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('completed', 'ready', 'served', 'placed')",
                    [$userId]
                );
                if ($pastOrdersCount > 0) {
                    return [
                        'success' => false,
                        'error' => "The '{$code}' coupon is exclusively reserved for first-time cafe guests.",
                        'audience' => 'first_time',
                    ];
                }
            } elseif ($audience === 'vip') {
                // Check if user has at least 5 visits or 500 points
                $visitCount = (int)db()->fetchColumn(
                    "SELECT COUNT(*) FROM visits WHERE user_id = ? AND is_counted = 1",
                    [$userId]
                );
                $points = (int)db()->fetchColumn(
                    "SELECT COALESCE(SUM(points), 0) FROM points_ledger WHERE user_id = ?",
                    [$userId]
                );

                if ($visitCount < 5 && $points < 500) {
                    return [
                        'success' => false,
                        'error' => "The '{$code}' voucher is reserved for VIP Patrons (5+ visits or 500+ loyalty points).",
                        'audience' => 'vip',
                        'current_visits' => $visitCount,
                        'required_visits' => 5,
                    ];
                }
            }
        }

        // 3. User usage cap
        if ($userId !== null && !empty($coupon['max_uses_per_user'])) {
            $userUses = CouponUsage::getUserUsageCount((int)$coupon['id'], $userId);
            if ($userUses >= (int)$coupon['max_uses_per_user']) {
                return [
                    'success' => false,
                    'error' => "You have already reached the maximum usage limit for coupon '{$code}'.",
                ];
            }
        }

        // 4. Total campaign limit
        if (!empty($coupon['max_uses_total'])) {
            $totalUses = CouponUsage::getTotalUsageCount((int)$coupon['id']);
            if ($totalUses >= (int)$coupon['max_uses_total']) {
                return [
                    'success' => false,
                    'error' => "Coupon '{$code}' has reached its total campaign redemption limit.",
                ];
            }
        }

        // 5. Calculate discount amount
        $discountPaise = 0;
        $discountType = $coupon['discount_type'] ?? 'percentage';
        $discountValue = (float)($coupon['discount_value'] ?? 0);
        $maxDiscountPaise = (int)($coupon['max_discount_paise'] ?? 0);

        if ($discountType === 'percentage') {
            $calc = (int)round($subtotalPaise * ($discountValue / 100.0));
            $discountPaise = $maxDiscountPaise > 0 ? min($calc, $maxDiscountPaise) : $calc;
        } else {
            // Flat discount: if value <= 1000, assume rupees (e.g. 50 -> 5000 paise)
            $flatPaise = $discountValue < 500 ? (int)round($discountValue * 100) : (int)$discountValue;
            $discountPaise = min($flatPaise, $subtotalPaise);
        }

        return [
            'success' => true,
            'coupon' => [
                'id' => (int)$coupon['id'],
                'code' => (string)$coupon['code'],
                'title' => (string)$coupon['title'],
                'type' => $discountType,
                'value' => $discountValue,
                'discount_paise' => $discountPaise,
                'discount_formatted' => Money::format($discountPaise),
                'discount_rupees' => (float)($discountPaise / 100),
                'max_discount_paise' => $maxDiscountPaise,
                'audience' => $audience,
            ],
            'message' => "Coupon '{$code}' applied successfully!",
        ];
    }

    /**
     * Check which time-based offers (Happy Hours, Combos) are active for given timestamp
     */
    public static function getActiveOffers(?string $currentDateTime = null): array
    {
        $dt = $currentDateTime ? new \DateTime($currentDateTime) : new \DateTime('now', new \DateTimeZone('Asia/Kolkata'));
        $dayName = $dt->format('l'); // e.g. "Monday"
        $currentTime = $dt->format('H:i'); // e.g. "16:30"
        $todayDate = $dt->format('Y-m-d');

        $rawOffers = Offer::getActive();
        $active = [];

        foreach ($rawOffers as $off) {
            $rules = !empty($off['rules_json']) ? json_decode($off['rules_json'], true) : [];
            $schedule = !empty($off['schedule_json']) ? json_decode($off['schedule_json'], true) : [];

            // 1. Check schedule (days of week and time window)
            if (!empty($schedule)) {
                if (!empty($schedule['days']) && is_array($schedule['days'])) {
                    if (!in_array($dayName, $schedule['days'], true)) {
                        continue; // Not active today
                    }
                }

                if (!empty($schedule['start_time']) && $currentTime < $schedule['start_time']) {
                    continue; // Too early
                }

                if (!empty($schedule['end_time']) && $currentTime > $schedule['end_time']) {
                    continue; // Too late
                }
            }

            $active[] = [
                'id' => (int)$off['id'],
                'title' => $off['title'],
                'type' => $off['type'],
                'rules' => $rules,
                'schedule' => $schedule,
                'priority' => (int)($off['priority'] ?? 0),
                'banner_url' => $off['banner_url'] ?? null,
            ];
        }

        return $active;
    }

    /**
     * Authoritative Cart Evaluator: applies Happy Hours, Combos, and Coupon with Stacking Rules
     * 
     * @param array $cartItems  Array of ['item_id' => int, 'variant_id' => ?int, 'quantity' => int, 'price_paise' => int, 'addons' => array]
     * @param string|null $couponCode
     * @param int|null $userId
     * @param string|null $testDateTime
     * @return array
     */
    public static function evaluateCart(
        array $cartItems,
        ?string $couponCode = null,
        ?int $userId = null,
        ?string $testDateTime = null
    ): array {
        // 1. Compute Base Line Items and Gross Subtotal
        $grossSubtotalPaise = 0;
        $processedItems = [];

        foreach ($cartItems as $ci) {
            $qty = max(1, (int)($ci['quantity'] ?? 1));
            $basePrice = (int)($ci['price_paise'] ?? 0);
            
            // Fetch live dish category to support category-based Happy Hours
            $itemId = (int)($ci['item_id'] ?? 0);
            $menuItem = MenuItem::find($itemId);
            $categorySlug = '';
            if ($menuItem) {
                $categorySlug = (string)db()->fetchColumn(
                    "SELECT slug FROM menu_categories WHERE id = ?",
                    [$menuItem['category_id']]
                );
            }

            $lineTotal = $basePrice * $qty;
            $grossSubtotalPaise += $lineTotal;

            $processedItems[] = [
                'item_id' => $itemId,
                'item_name' => $menuItem['name'] ?? ($ci['name'] ?? 'Dish'),
                'category_slug' => $categorySlug,
                'quantity' => $qty,
                'unit_price_paise' => $basePrice,
                'line_total_paise' => $lineTotal,
                'discount_paise' => 0,
            ];
        }

        // 2. Evaluate Time-Based Offers (Happy Hour & Combos)
        $activeOffers = self::getActiveOffers($testDateTime);
        $appliedOffers = [];
        $totalOfferDiscountsPaise = 0;

        foreach ($activeOffers as $offer) {
            $rules = $offer['rules'];
            $offerType = $offer['type']; // 'happy_hour' | 'combo' | 'discount'

            if ($offerType === 'happy_hour') {
                // e.g. 20% off all beverages / coffee category
                $targetCategories = $rules['categories'] ?? [];
                $percent = (float)($rules['discount_percent'] ?? 0);

                if ($percent > 0 && !empty($targetCategories)) {
                    $offerSavings = 0;
                    foreach ($processedItems as &$pItem) {
                        if (in_array($pItem['category_slug'], $targetCategories, true)) {
                            $itemDiscount = (int)round($pItem['line_total_paise'] * ($percent / 100.0));
                            $pItem['discount_paise'] += $itemDiscount;
                            $offerSavings += $itemDiscount;
                        }
                    }
                    unset($pItem);

                    if ($offerSavings > 0) {
                        $totalOfferDiscountsPaise += $offerSavings;
                        $appliedOffers[] = [
                            'offer_id' => $offer['id'],
                            'title' => $offer['title'],
                            'type' => 'happy_hour',
                            'discount_paise' => $offerSavings,
                            'discount_formatted' => Money::format($offerSavings),
                        ];
                    }
                }
            } elseif ($offerType === 'combo') {
                // e.g. Combo bundle savings: Any Pasta + Any Cold Brew = ₹50 savings
                $comboCategories = $rules['required_categories'] ?? []; // e.g. ['pastas', 'specialty-coffee']
                $bundleSavingsPaise = (int)($rules['bundle_discount_paise'] ?? 0);

                if (!empty($comboCategories) && $bundleSavingsPaise > 0) {
                    // Check if cart contains at least 1 item from each category
                    $categoriesFound = [];
                    foreach ($processedItems as $pItem) {
                        if (in_array($pItem['category_slug'], $comboCategories, true)) {
                            $categoriesFound[$pItem['category_slug']] = true;
                        }
                    }

                    if (count($categoriesFound) === count($comboCategories)) {
                        $totalOfferDiscountsPaise += $bundleSavingsPaise;
                        $appliedOffers[] = [
                            'offer_id' => $offer['id'],
                            'title' => $offer['title'],
                            'type' => 'combo',
                            'discount_paise' => $bundleSavingsPaise,
                            'discount_formatted' => Money::format($bundleSavingsPaise),
                        ];
                    }
                }
            }
        }

        // Subtotal after automated offers
        $subtotalAfterOffers = max(0, $grossSubtotalPaise - $totalOfferDiscountsPaise);

        // 3. Stacking Rules & Coupon Evaluation
        $couponResult = null;
        $couponDiscountPaise = 0;
        $stackingMode = 'stackable'; // 'stackable' or 'exclusive'

        // Check global setting for promo stacking
        if (class_exists(\App\Models\Setting::class)) {
            $settingStack = \App\Models\Setting::get('promo_stacking_mode');
            if ($settingStack === 'exclusive') {
                $stackingMode = 'exclusive';
            }
        }

        if (!empty($couponCode)) {
            // Under 'exclusive' mode: if happy hour/combo is already applied, coupon cannot stack
            if ($stackingMode === 'exclusive' && $totalOfferDiscountsPaise > 0) {
                $couponResult = [
                    'success' => false,
                    'error' => 'Automated Happy Hour discounts cannot be combined with coupon codes under exclusive offer rules.',
                    'stacked' => false,
                ];
            } else {
                $val = self::validateCoupon($couponCode, $subtotalAfterOffers, $userId);
                if ($val['success']) {
                    $couponResult = $val;
                    $couponDiscountPaise = (int)$val['coupon']['discount_paise'];
                } else {
                    $couponResult = $val;
                }
            }
        }

        // 4. Net Taxable Subtotal & GST Calculation
        $totalDiscountPaise = $totalOfferDiscountsPaise + $couponDiscountPaise;
        $netTaxablePaise = max(0, $grossSubtotalPaise - $totalDiscountPaise);

        // Fetch dynamic GST rates
        $cgstRate = 2.5;
        $sgstRate = 2.5;
        if (class_exists(\App\Models\Setting::class)) {
            $cgstRate = (float)(\App\Models\Setting::get('tax_cgst_percent') ?: 2.5);
            $sgstRate = (float)(\App\Models\Setting::get('tax_sgst_percent') ?: 2.5);
        }

        $cgstPaise = (int)round($netTaxablePaise * ($cgstRate / 100.0));
        $sgstPaise = (int)round($netTaxablePaise * ($sgstRate / 100.0));
        $totalTaxPaise = $cgstPaise + $sgstPaise;

        $grandTotalPaise = $netTaxablePaise + $totalTaxPaise;

        return [
            'gross_subtotal_paise' => $grossSubtotalPaise,
            'gross_subtotal_formatted' => Money::format($grossSubtotalPaise),
            'applied_offers' => $appliedOffers,
            'offer_discounts_paise' => $totalOfferDiscountsPaise,
            'offer_discounts_formatted' => Money::format($totalOfferDiscountsPaise),
            'coupon' => $couponResult,
            'coupon_discount_paise' => $couponDiscountPaise,
            'coupon_discount_formatted' => Money::format($couponDiscountPaise),
            'total_discount_paise' => $totalDiscountPaise,
            'total_discount_formatted' => Money::format($totalDiscountPaise),
            'net_taxable_paise' => $netTaxablePaise,
            'net_taxable_formatted' => Money::format($netTaxablePaise),
            'taxes' => [
                'cgst_percent' => $cgstRate,
                'cgst_paise' => $cgstPaise,
                'cgst_formatted' => Money::format($cgstPaise),
                'sgst_percent' => $sgstRate,
                'sgst_paise' => $sgstPaise,
                'sgst_formatted' => Money::format($sgstPaise),
                'total_tax_paise' => $totalTaxPaise,
                'total_tax_formatted' => Money::format($totalTaxPaise),
            ],
            'grand_total_paise' => $grandTotalPaise,
            'grand_total_formatted' => Money::format($grandTotalPaise),
            'items' => $processedItems,
        ];
    }
}
