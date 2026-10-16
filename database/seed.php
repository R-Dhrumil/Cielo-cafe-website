<?php
/**
 * Cielo Cafe and Restro - Database Seeder
 * Populates initial settings, admin users, tables with secure tokens,
 * categories and items from menu.json, and starter loyalty rules.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Core\Money;
use App\Core\Security;
use App\Models\Setting;
use App\Models\StaffUser;
use App\Models\Table;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\ItemVariant;
use App\Models\ItemAddon;
use App\Models\RewardRule;
use App\Models\Coupon;
use App\Models\Offer;

echo "========================================================\n";
echo " Cielo Cafe & Restro - Database Seeder\n";
echo "========================================================\n";

try {
    $db = Database::getInstance();

    // 1. Seed Dynamic Settings (CMS, GST, Loyalty, Cafe Info)
    echo "\n1. Seeding Dynamic Settings & CMS...\n";
    $settings = [
        // Tax / GST (Managed from Admin Panel per user request)
        ['key' => 'gst_enabled', 'value' => '1', 'group' => 'tax', 'type' => 'bool'],
        ['key' => 'gst_cgst_percent', 'value' => '2.5', 'group' => 'tax', 'type' => 'float'],
        ['key' => 'gst_sgst_percent', 'value' => '2.5', 'group' => 'tax', 'type' => 'float'],
        ['key' => 'cafe_gstin', 'value' => '24AAACC1234F1Z5', 'group' => 'tax', 'type' => 'string'],

        // Loyalty Points (Managed from Admin Panel per user request)
        ['key' => 'loyalty_enabled', 'value' => '1', 'group' => 'loyalty', 'type' => 'bool'],
        ['key' => 'loyalty_points_per_100', 'value' => '10', 'group' => 'loyalty', 'type' => 'int'],
        ['key' => 'loyalty_point_value_paise', 'value' => '100', 'group' => 'loyalty', 'type' => 'int'], // 1 point = ₹1
        ['key' => 'loyalty_min_redeem_points', 'value' => '50', 'group' => 'loyalty', 'type' => 'int'],
        ['key' => 'loyalty_max_redeem_percent', 'value' => '50.0', 'group' => 'loyalty', 'type' => 'float'],
        ['key' => 'loyalty_review_points', 'value' => '50', 'group' => 'loyalty', 'type' => 'int'],

        // Payments (Project ready for online gateway, active with Counter/UPI)
        ['key' => 'payment_counter_cash', 'value' => '1', 'group' => 'payment', 'type' => 'bool'],
        ['key' => 'payment_counter_upi', 'value' => '1', 'group' => 'payment', 'type' => 'bool'],
        ['key' => 'payment_online_razorpay', 'value' => '0', 'group' => 'payment', 'type' => 'bool'],
        ['key' => 'payment_upi_id', 'value' => 'cielocafebistro@icici', 'group' => 'payment', 'type' => 'string'],

        // Cafe Details
        ['key' => 'cafe_name', 'value' => 'Cielo Cafe and Restro', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_tagline', 'value' => 'Italian-inspired All-Day Sanctuary in Vadodara', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_phone', 'value' => '+91 90818 88944', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_email', 'value' => 'cielocafebistro@gmail.com', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_address', 'value' => 'GF-01, Trisha Square, Opp. Yash Complex, Subhanpura, Vadodara, Gujarat 390023', 'group' => 'cafe', 'type' => 'string'],
        ['key' => 'cafe_hours', 'value' => '11:00 AM - 11:30 PM', 'group' => 'cafe', 'type' => 'string'],

        // Dynamic Website CMS (Admin can change everything per user request)
        ['key' => 'cms_hero_name', 'value' => 'Cielo', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_hero_tagline', 'value' => 'Cafe and Restro', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_hero_badge', 'value' => 'Vadodara · Italian Sanctuary', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_story_heading', 'value' => 'An Italian Sanctuary Born from Sourdough and Slow Brews', 'group' => 'cms', 'type' => 'string'],
        ['key' => 'cms_story_quote', 'value' => 'Where unhurried coffee meets wood-fired warmth in Gujarat.', 'group' => 'cms', 'type' => 'string'],
    ];

    foreach ($settings as $s) {
        Setting::set($s['key'], $s['value'], $s['group'], $s['type']);
    }
    echo "  ✔ Settings & CMS configurations seeded (" . count($settings) . " entries)\n";

    // 2. Seed Default Staff & Admin Users
    echo "\n2. Seeding Staff Accounts...\n";
    $staffData = [
        ['name' => 'Cielo Owner', 'email' => 'admin@cielocafe.com', 'password' => 'CieloAdmin@2026', 'role' => 'owner'],
        ['name' => 'Kitchen Head', 'email' => 'kitchen@cielocafe.com', 'password' => 'Kitchen@2026', 'role' => 'kitchen'],
    ];

    foreach ($staffData as $st) {
        $existing = StaffUser::findBy('email', $st['email']);
        if (!$existing) {
            StaffUser::createStaff($st['name'], $st['email'], $st['password'], $st['role']);
            echo "  ✔ Created staff account: {$st['email']} ({$st['role']})\n";
        } else {
            echo "  ℹ Staff account {$st['email']} already exists\n";
        }
    }

    // 3. Seed Cafe Tables with Secure QR Tokens
    echo "\n3. Seeding Cafe Tables & Tokens...\n";
    for ($i = 1; $i <= 10; $i++) {
        $tblNum = sprintf('%02d', $i);
        $existing = Table::findBy('table_number', $tblNum);
        if (!$existing) {
            $token = Security::generateTableToken(16);
            Table::create([
                'table_number' => $tblNum,
                'qr_token' => $token,
                'capacity' => ($i <= 4) ? 2 : (($i <= 8) ? 4 : 8),
                'is_active' => 1,
            ]);
            echo "  ✔ Created Table {$tblNum} [Token: {$token}]\n";
        }
    }

    // 4. Seed Menu Categories & Items from data/menu.json
    echo "\n4. Seeding Menu from data/menu.json...\n";
    $menuJsonFile = CIELO_ROOT . '/data/menu.json';
    if (file_exists($menuJsonFile)) {
        $menuData = json_decode(file_get_contents($menuJsonFile), true) ?: [];

        // Categories
        $categoryMap = [];
        foreach ($menuData['categories'] ?? [] as $index => $cat) {
            $existing = MenuCategory::findBy('slug', $cat['id']);
            if (!$existing) {
                $catId = MenuCategory::create([
                    'slug' => $cat['id'],
                    'name' => $cat['label'],
                    'description' => $cat['description'] ?? '',
                    'sort_order' => $index,
                    'time_slot' => 'all_day',
                    'is_active' => 1,
                ]);
                $categoryMap[$cat['id']] = (int)$catId;
                echo "  ✔ Category created: {$cat['label']}\n";
            } else {
                $categoryMap[$cat['id']] = (int)$existing['id'];
            }
        }

        // Menu Items
        $itemCount = 0;
        foreach ($menuData['items'] ?? [] as $index => $item) {
            $catId = $categoryMap[$item['categoryId']] ?? null;
            if (!$catId) continue;

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['name'])));
            $existing = MenuItem::findBy('slug', $slug);

            if (!$existing) {
                $pricePaise = Money::toPaise($item['price']);
                $newId = MenuItem::create([
                    'category_id' => $catId,
                    'name' => $item['name'],
                    'slug' => $slug,
                    'description' => $item['description'] ?? '',
                    'price_paise' => $pricePaise,
                    'is_veg' => !empty($item['isVeg']) ? 1 : 0,
                    'tag' => $item['tag'] ?? null,
                    'image_url' => $item['image'] ?? 'menu/unnamed.png',
                    'is_available' => 1,
                    'sort_order' => $index,
                ]);

                // Sample variants for beverages & pizzas
                if (str_contains($slug, 'cold-brew') || str_contains($slug, 'white') || str_contains($slug, 'espresso')) {
                    ItemVariant::create(['item_id' => $newId, 'name' => 'Regular', 'price_paise' => $pricePaise, 'sort_order' => 1]);
                    ItemVariant::create(['item_id' => $newId, 'name' => 'Large (Grande)', 'price_paise' => $pricePaise + 4000, 'sort_order' => 2]);
                    ItemAddon::create(['item_id' => $newId, 'name' => 'Extra Espresso Shot', 'extra_price_paise' => 5000, 'is_available' => 1]);
                    ItemAddon::create(['item_id' => $newId, 'name' => 'Vanilla Bean Syrup', 'extra_price_paise' => 4000, 'is_available' => 1]);
                } elseif (str_contains($slug, 'pizza')) {
                    ItemVariant::create(['item_id' => $newId, 'name' => '10-inch Medium', 'price_paise' => $pricePaise, 'sort_order' => 1]);
                    ItemVariant::create(['item_id' => $newId, 'name' => '12-inch Large', 'price_paise' => $pricePaise + 12000, 'sort_order' => 2]);
                    ItemAddon::create(['item_id' => $newId, 'name' => 'Extra Fresh Burrata', 'extra_price_paise' => 9000, 'is_available' => 1]);
                    ItemAddon::create(['item_id' => $newId, 'name' => 'Truffle Oil Glaze', 'extra_price_paise' => 6000, 'is_available' => 1]);
                }

                $itemCount++;
            }
        }
        echo "  ✔ Seeded {$itemCount} dishes with variants & add-ons\n";
    }

    // 5. Seed Starter Loyalty Rules
    echo "\n5. Seeding Starter Loyalty Rules...\n";
    $rules = [
        [
            'name' => '5 Visits in 30 Days Milestone',
            'trigger_type' => 'visit_milestone',
            'config_json' => json_encode([
                'visits_required' => 5,
                'window_days' => 30,
                'min_order_paise' => 20000, // ₹200 min order
                'cooldown_days' => 30,
            ]),
            'reward_json' => json_encode([
                'type' => 'free_item',
                'title' => 'Complimentary Specialty Coffee / Dessert',
                'item_name' => 'Citrus Cold Brew or Venetian Tiramisu',
            ]),
            'validity_days' => 30,
            'is_repeatable' => 1,
            'cooldown_days' => 30,
            'is_active' => 1,
        ],
        [
            'name' => 'Verified Review Reward',
            'trigger_type' => 'review_submit',
            'config_json' => json_encode(['points' => 50]),
            'reward_json' => json_encode(['type' => 'points', 'points' => 50]),
            'validity_days' => 365,
            'is_repeatable' => 1,
            'cooldown_days' => 0,
            'is_active' => 1,
        ],
        [
            'name' => 'Spend Milestone Rewards',
            'trigger_type' => 'order_spend',
            'config_json' => json_encode(['points_per_100_spent' => 10]),
            'reward_json' => json_encode(['type' => 'points_rate', 'rate' => 10]),
            'validity_days' => 365,
            'is_repeatable' => 1,
            'cooldown_days' => 0,
            'is_active' => 1,
        ]
    ];

    foreach ($rules as $r) {
        $existing = RewardRule::findBy('name', $r['name']);
        if (!$existing) {
            RewardRule::create($r);
            echo "  ✔ Loyalty Rule created: {$r['name']}\n";
        }
    }

    // 6. Seed Starter Coupons
    echo "\n6. Seeding Starter Coupons...\n";
    $coupons = [
        [
            'code' => 'CIELO10',
            'title' => '10% Off All Dining',
            'type' => 'percentage',
            'value' => 10.0,
            'min_order_paise' => 30000, // ₹300 min
            'max_discount_paise' => 10000, // ₹100 max cap
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => date('Y-m-d', strtotime('+90 days')),
            'usage_limit_total' => 1000,
            'usage_limit_per_user' => 3,
            'audience' => 'all',
            'is_active' => 1,
        ],
        [
            'code' => 'WELCOME50',
            'title' => 'Flat ₹50 Off First Table Order',
            'type' => 'flat',
            'value' => 50.0,
            'min_order_paise' => 25000, // ₹250 min
            'max_discount_paise' => 5000,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => date('Y-m-d', strtotime('+90 days')),
            'usage_limit_total' => 500,
            'usage_limit_per_user' => 1,
            'audience' => 'first_time',
            'is_active' => 1,
        ],
        [
            'code' => 'CIELOVIP',
            'title' => 'VIP Connoisseur Flat ₹100 Off',
            'type' => 'flat',
            'value' => 100.0,
            'min_order_paise' => 60000, // ₹600 min
            'max_discount_paise' => 10000,
            'start_date' => date('Y-m-d', strtotime('-1 day')),
            'end_date' => date('Y-m-d', strtotime('+90 days')),
            'usage_limit_total' => 200,
            'usage_limit_per_user' => 5,
            'audience' => 'vip',
            'is_active' => 1,
        ],
    ];

    foreach ($coupons as $c) {
        $existing = Coupon::findBy('code', $c['code']);
        if (!$existing) {
            Coupon::create($c);
            echo "  ✔ Coupon created: {$c['code']} ({$c['title']})\n";
        }
    }

    // 7. Seed Time-Based Offers (Happy Hours & Combos)
    echo "\n7. Seeding Promotional Offers...\n";
    $offers = [
        [
            'title' => 'Aperitivo Happy Hour - 20% Off Beverages',
            'type' => 'happy_hour',
            'rules_json' => json_encode([
                'categories' => ['coffee-beverages', 'specialty-coffee', 'beverages', 'coffee'],
                'discount_percent' => 20.0,
            ]),
            'schedule_json' => json_encode([
                'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday'],
                'start_time' => '15:00',
                'end_time' => '18:00',
            ]),
            'banner_url' => 'assets/images/cafe/happy-hour.jpg',
            'priority' => 1,
            'is_active' => 1,
        ],
        [
            'title' => 'Milano Sourdough Combo (Pasta + Coffee)',
            'type' => 'combo',
            'rules_json' => json_encode([
                'required_categories' => ['pizza-pasta', 'coffee-beverages'],
                'bundle_discount_paise' => 5000, // ₹50 savings
            ]),
            'schedule_json' => json_encode([
                'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'start_time' => '11:00',
                'end_time' => '23:30',
            ]),
            'banner_url' => 'assets/images/cafe/combo-promo.jpg',
            'priority' => 2,
            'is_active' => 1,
        ],
    ];

    foreach ($offers as $off) {
        $existing = Offer::findBy('title', $off['title']);
        if (!$existing) {
            Offer::create($off);
            echo "  ✔ Offer created: {$off['title']}\n";
        }
    }

    echo "\n✔ Seeding completed successfully!\n";
} catch (Throwable $e) {
    echo "\n✖ Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
