<?php

declare(strict_types=1);

namespace App\Core;

class Money
{
    /**
     * Convert Rupees to Paise integer (e.g. 240.50 -> 24050)
     */
    public static function toPaise(float|int|string $rupees): int
    {
        $floatVal = (float)$rupees;
        return (int)round($floatVal * 100);
    }

    /**
     * Convert Paise integer to Rupees float (e.g. 24050 -> 240.50)
     */
    public static function toRupees(int $paise): float
    {
        return $paise / 100.0;
    }

    /**
     * Format paise to currency string (e.g. ₹240 or ₹240.50)
     */
    public static function format(int $paise, bool $withSymbol = true): string
    {
        $rupees = $paise / 100.0;
        $symbol = $withSymbol ? (Config::get('app.currency.symbol', '₹') . ' ') : '';

        // If whole rupees, format without decimal places (e.g. ₹240), else with 2 decimals (₹240.50)
        if (fmod($rupees, 1.0) == 0.0) {
            return $symbol . number_format($rupees, 0);
        }

        return $symbol . number_format($rupees, 2);
    }

    /**
     * Calculate tax in paise
     */
    public static function calculateTax(int $subtotalPaise, float $ratePercent): int
    {
        if ($ratePercent <= 0) return 0;
        return (int)round(($subtotalPaise * $ratePercent) / 100.0);
    }

    /**
     * Calculate discount amount in paise
     * @param string $type 'percentage' or 'flat'
     * @param float $value percentage value (e.g. 15 for 15%) or flat rupees
     * @param int $maxDiscountPaise maximum discount cap in paise
     */
    public static function calculateDiscount(int $subtotalPaise, string $type, float $value, int $maxDiscountPaise = 0): int
    {
        if ($subtotalPaise <= 0 || $value <= 0) {
            return 0;
        }

        $discountPaise = 0;

        if ($type === 'percentage') {
            $discountPaise = (int)round(($subtotalPaise * $value) / 100.0);
        } else {
            // Flat discount: $value is in rupees
            $discountPaise = self::toPaise($value);
        }

        // Apply maximum discount cap if set
        if ($maxDiscountPaise > 0 && $discountPaise > $maxDiscountPaise) {
            $discountPaise = $maxDiscountPaise;
        }

        // Discount cannot exceed subtotal
        if ($discountPaise > $subtotalPaise) {
            $discountPaise = $subtotalPaise;
        }

        return $discountPaise;
    }
}
