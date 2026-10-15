<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Dynamic Menu API Endpoint
 *
 * Endpoint: GET /api/menu.php
 * Returns active categories, dishes, variants, add-ons, table session & tax configuration.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Core\Money;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\ItemVariant;
use App\Models\ItemAddon;
use App\Services\TableSessionService;
use App\Services\SettingsService;

try {
    // 1. Fetch Active Categories
    $categories = MenuCategory::where('`is_active` = 1', [], '`sort_order` ASC, `id` ASC');

    // 2. Fetch Active Items
    $items = MenuItem::where('`is_available` = 1', [], '`category_id` ASC, `sort_order` ASC, `id` ASC');

    // 3. Fetch All Active Variants & Addons
    $variants = ItemVariant::all('`item_id` ASC, `sort_order` ASC');
    $addons = ItemAddon::where('`is_available` = 1', [], '`item_id` ASC, `id` ASC');

    // Group variants and addons by item_id
    $variantsByItem = [];
    foreach ($variants as $v) {
        $itemId = (int)$v['item_id'];
        $variantsByItem[$itemId][] = [
            'id' => (int)$v['id'],
            'name' => (string)$v['name'],
            'price_paise' => (int)$v['price_paise'],
            'price_inr' => Money::toRupees((int)$v['price_paise']),
            'formatted' => Money::format((int)$v['price_paise']),
        ];
    }

    $addonsByItem = [];
    foreach ($addons as $a) {
        $itemId = (int)$a['item_id'];
        $addonsByItem[$itemId][] = [
            'id' => (int)$a['id'],
            'name' => (string)$a['name'],
            'extra_price_paise' => (int)$a['extra_price_paise'],
            'extra_price_inr' => Money::toRupees((int)$a['extra_price_paise']),
            'formatted' => '+' . Money::format((int)$a['extra_price_paise']),
        ];
    }

    // Available menu folder images for elegant visual fallbacks
    $menuImages = [
        'menu/unnamed.png',
        'menu/unnamed-2.png',
        'menu/unnamed-3.png',
        'menu/unnamed-4.png',
        'menu/unnamed-5.png',
        'menu/unnamed-6.png',
        'menu/unnamed-7.png',
        'menu/unnamed-8.png',
        'menu/unnamed-9.png',
        'menu/unnamed-10.png',
    ];

    // Group items by category
    $itemsByCategory = [];
    foreach ($items as $idx => $it) {
        $catId = (int)$it['category_id'];
        $itemId = (int)$it['id'];

        // Determine image URL (prioritize menu/ folder or valid assets)
        $imageUrl = (string)($it['image_url'] ?? '');
        if ($imageUrl === '' || (!file_exists(CIELO_ROOT . '/' . ltrim($imageUrl, '/')) && !str_starts_with($imageUrl, 'http'))) {
            // Assign from menu/ folder based on item ID rotation
            $imageUrl = $menuImages[$idx % count($menuImages)];
        }

        $itemsByCategory[$catId][] = [
            'id' => $itemId,
            'category_id' => $catId,
            'name' => (string)$it['name'],
            'slug' => (string)$it['slug'],
            'description' => (string)($it['description'] ?? ''),
            'price_paise' => (int)$it['price_paise'],
            'price_inr' => Money::toRupees((int)$it['price_paise']),
            'formatted_price' => Money::format((int)$it['price_paise']),
            'is_veg' => (int)$it['is_veg'] === 1,
            'tag' => (string)($it['tag'] ?? ''),
            'image_url' => $imageUrl,
            'variants' => $variantsByItem[$itemId] ?? [],
            'addons' => $addonsByItem[$itemId] ?? [],
            'has_customization' => !empty($variantsByItem[$itemId]) || !empty($addonsByItem[$itemId]),
        ];
    }

    // Attach items to categories
    $structuredMenu = [];
    foreach ($categories as $cat) {
        $catId = (int)$cat['id'];
        $structuredMenu[] = [
            'id' => $catId,
            'slug' => (string)$cat['slug'],
            'name' => (string)$cat['name'],
            'description' => (string)($cat['description'] ?? ''),
            'time_slot' => (string)($cat['time_slot'] ?? 'all_day'),
            'item_count' => count($itemsByCategory[$catId] ?? []),
            'items' => $itemsByCategory[$catId] ?? [],
        ];
    }

    // 4. Table Context & Dynamic Configuration
    $activeTable = TableSessionService::getActiveTable();
    $activeUser = $_SESSION['cielo_user'] ?? null;
    $gstRates = SettingsService::getGstRates();
    $loyaltyRules = SettingsService::getLoyaltyRules();

    Response::json(true, [
        'categories' => $structuredMenu,
        'table' => $activeTable,
        'user' => $activeUser,
        'config' => [
            'currency' => '₹',
            'cgst_percent' => $gstRates['cgst_percent'],
            'sgst_percent' => $gstRates['sgst_percent'],
            'earn_points_per_100_inr' => $loyaltyRules['earn_points_per_100_inr'],
            'redeem_value_per_point_paise' => $loyaltyRules['redeem_value_per_point_paise'],
            'max_redeem_percentage' => $loyaltyRules['max_redeem_percentage'],
        ]
    ]);
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to load menu: ' . $e->getMessage(), 500);
}
