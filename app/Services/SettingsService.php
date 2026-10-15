<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    /**
     * Get Restaurant GST breakdown configuration
     */
    public static function getGstRates(): array
    {
        return [
            'cgst' => (float)Setting::get('gst_cgst_percent', 2.5),
            'sgst' => (float)Setting::get('gst_sgst_percent', 2.5),
            'gstin' => (string)Setting::get('cafe_gstin', '24ABCDE1234F1Z5'),
            'is_enabled' => (bool)Setting::get('gst_enabled', true),
        ];
    }

    /**
     * Get loyalty point rules
     */
    public static function getLoyaltyConfig(): array
    {
        return [
            'is_enabled' => (bool)Setting::get('loyalty_enabled', true),
            'points_per_100_spent' => (int)Setting::get('loyalty_points_per_100', 10),
            'point_value_paise' => (int)Setting::get('loyalty_point_value_paise', 100), // 1 point = ₹1 (100 paise)
            'min_redeem_points' => (int)Setting::get('loyalty_min_redeem_points', 50),
            'max_redeem_percent' => (float)Setting::get('loyalty_max_redeem_percent', 50.0), // max 50% of bill via points
            'review_points' => (int)Setting::get('loyalty_review_points', 50),
        ];
    }

    /**
     * Get payment modes enabled
     */
    public static function getPaymentConfig(): array
    {
        return [
            'counter_cash' => (bool)Setting::get('payment_counter_cash', true),
            'counter_upi' => (bool)Setting::get('payment_counter_upi', true),
            'online_razorpay' => (bool)Setting::get('payment_online_razorpay', false), // user specified: make project ready for that will implement later
            'upi_id' => (string)Setting::get('payment_upi_id', 'cielocafebistro@icici'),
        ];
    }

    /**
     * Get Dynamic Website CMS content
     */
    public static function getCmsContent(string $section = ''): array
    {
        if ($section !== '') {
            return Setting::get("cms_{$section}", []);
        }
        return Setting::getGroup('cms');
    }

    /**
     * Get Cafe Contact & Location details
     */
    public static function getCafeDetails(): array
    {
        return [
            'name' => Setting::get('cafe_name', 'Cielo Cafe and Restro'),
            'tagline' => Setting::get('cafe_tagline', 'Italian-inspired Cafe & All-day Bistro'),
            'phone' => Setting::get('cafe_phone', '+91 98765 43210'),
            'email' => Setting::get('cafe_email', 'hello@cielocafebistro.in'),
            'address' => Setting::get('cafe_address', 'Ground Floor, Trisha Square, Subhanpura, Vadodara, Gujarat 390023'),
            'opening_hours' => Setting::get('cafe_hours', '11:00 AM - 11:30 PM'),
        ];
    }
}
