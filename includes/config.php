<?php
/**
 * Cielo Cafe and Restro - Core Configuration & Data Loader
 * Production-ready lightweight PHP backend
 */

require_once __DIR__ . '/../app/bootstrap.php';

// Set Indian Standard Time for accurate operating hours calculation
date_default_timezone_set('Asia/Kolkata');

// Load Data Sources
function loadJsonData(string $filename): array {
    $path = __DIR__ . '/../data/' . $filename;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }
    return [];
}

$business = loadJsonData('business.json');
$menu = loadJsonData('menu.json');
$gallery = loadJsonData('gallery.json');
$reviews = loadJsonData('reviews.json');

// 2. Seamlessly load live dynamic data from database
try {
    // A. Dynamic Settings & Business CMS Overrides
    if (class_exists(\App\Models\Setting::class)) {
        $allSettings = \App\Models\Setting::all();
        $settingsMap = [];
        foreach ($allSettings as $s) {
            $settingsMap[$s['key']] = $s['value'];
        }

        if (!empty($settingsMap['cafe_name'])) {
            $business['name'] = (string)$settingsMap['cafe_name'];
        }
        if (!empty($settingsMap['cafe_tagline'])) {
            $business['tagline'] = (string)$settingsMap['cafe_tagline'];
        }
        if (!empty($settingsMap['cafe_phone'])) {
            $phone = (string)$settingsMap['cafe_phone'];
            $rawPhone = preg_replace('/[^\d+]/', '', $phone);
            $cleanDigits = ltrim(preg_replace('/[^\d]/', '', $phone), '0');
            $business['phone'] = $phone;
            $business['phoneDisplay'] = $phone;
            $business['phoneRaw'] = $rawPhone;
            $business['contact']['phone'] = $phone;
            $business['whatsappNumber'] = $cleanDigits;
        }
        if (!empty($settingsMap['cafe_email'])) {
            $email = (string)$settingsMap['cafe_email'];
            $business['email'] = $email;
            $business['contact']['email'] = $email;
        }
        if (!empty($settingsMap['cafe_address'])) {
            $addr = (string)$settingsMap['cafe_address'];
            $business['address']['full'] = $addr;
            $business['address']['line1'] = $addr;
            $business['contact']['address']['formatted'] = $addr;
        }
        if (!empty($settingsMap['cafe_hours'])) {
            $business['hours']['display'] = (string)$settingsMap['cafe_hours'];
        }
        if (!empty($settingsMap['cms_story_heading'])) {
            $business['story']['heading'] = (string)$settingsMap['cms_story_heading'];
        }
        if (!empty($settingsMap['cms_story_quote'])) {
            $business['story']['quote'] = (string)$settingsMap['cms_story_quote'];
        }
        $cmsHeroName = (string)($settingsMap['cms_hero_name'] ?? 'Cielo');
        $cmsHeroTagline = (string)($settingsMap['cms_hero_tagline'] ?? 'Cafe and Restro');
        $cmsHeroBadge = (string)($settingsMap['cms_hero_badge'] ?? 'Vadodara · Italian Sanctuary');
        $cmsHeroCta = (string)($settingsMap['cms_hero_cta'] ?? 'Explore Artisanal Menu');
    }

    // B. Dynamic Menu Categories & Items from Database
    if (class_exists(\App\Models\MenuCategory::class) && class_exists(\App\Models\MenuItem::class)) {
        $dbCategories = \App\Models\MenuCategory::where('`is_active` = 1', [], '`sort_order` ASC, `id` ASC');
        if (!empty($dbCategories)) {
            $catMap = [];
            $formattedCategories = [];
            foreach ($dbCategories as $cat) {
                $slug = (string)$cat['slug'];
                $catMap[(int)$cat['id']] = $slug;
                $formattedCategories[] = [
                    'id' => $slug,
                    'db_id' => (int)$cat['id'],
                    'label' => (string)$cat['name'],
                    'description' => (string)($cat['description'] ?? '')
                ];
            }

            $dbItems = \App\Models\MenuItem::where('`is_available` = 1', [], '`sort_order` ASC, `id` ASC');
            if (!empty($dbItems)) {
                $formattedItems = [];
                foreach ($dbItems as $it) {
                    $catId = (int)$it['category_id'];
                    $catSlug = $catMap[$catId] ?? ('category-' . $catId);
                    $img = (string)($it['image_url'] ?? 'assets/images/cafe/hero-table.webp');
                    $formattedItems[] = [
                        'id' => (int)$it['id'],
                        'slug' => (string)$it['slug'],
                        'categoryId' => $catSlug,
                        'category_id' => $catId,
                        'name' => (string)$it['name'],
                        'description' => (string)($it['description'] ?? ''),
                        'price' => (float)(round((int)$it['price_paise'] / 100)),
                        'price_paise' => (int)$it['price_paise'],
                        'isVeg' => (bool)$it['is_veg'],
                        'tag' => (string)($it['tag'] ?? ''),
                        'image' => $img,
                        'isAvailable' => (bool)$it['is_available']
                    ];
                }
                $menu['categories'] = $formattedCategories;
                $menu['items'] = $formattedItems;
            }
        }
    }

    // C. Dynamic Verified Diner Reviews from Database
    if (class_exists(\App\Models\Review::class)) {
        $stats = \App\Models\Review::getSummaryStats();
        if ($stats['total_approved'] > 0) {
            $reviews['averageRating'] = $stats['average_rating'];
            $reviews['totalReviewsCount'] = $stats['total_approved'] . '+ verified';

            $approvedReviews = \App\Models\Review::getApprovedWithDetails(10);
            if (!empty($approvedReviews)) {
                $dbReviewCards = [];
                foreach ($approvedReviews as $ar) {
                    $dbReviewCards[] = [
                        'id' => 'db-' . $ar['id'],
                        'author' => $ar['user_name'] ?: 'Verified Diner',
                        'badge' => '★ Verified Diner',
                        'rating' => (int)$ar['rating'],
                        'date' => date('M Y', strtotime($ar['created_at'])),
                        'text' => $ar['review_text'],
                        'dishMentioned' => $ar['primary_dish'] ?? 'Artisanal Selection',
                    ];
                }
                $reviews['reviews'] = $dbReviewCards;
            }
        }
    }
} catch (\Throwable $e) {
    // Graceful fallback to local JSON files if database connection is unavailable
}

// Helper to escape HTML safely
function e(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Helper to resolve asset URLs safely
function asset_url(?string $path): string {
    if (!$path) return '';
    $cleanPath = ltrim($path, '/');
    if (str_starts_with($cleanPath, 'assets/')) {
        return $cleanPath;
    }
    return 'assets/' . $cleanPath;
}

// Versioned asset URL for CSS/JS cache-busting (busts 30-day Apache cache on updates)
function cielo_asset(?string $path): string {
    if (!$path) return '';
    $cleanPath = ltrim($path, '/');
    $relative = str_starts_with($cleanPath, 'assets/') ? $cleanPath : 'assets/' . $cleanPath;
    $fullPath = dirname(__DIR__) . '/' . $relative;
    $v = file_exists($fullPath) ? (string)filemtime($fullPath) : '2.0';
    return $relative . '?v=' . $v;
}

// Backward-compatible alias
function asset(string $path): string {
    return asset_url($path);
}

// Live calculation of Cafe Open/Closed status
function isCieloOpen(): array {
    $currentHour = (float)date('H') + ((float)date('i') / 60);
    // 11:00 AM (11.0) to 11:30 PM (23.5)
    $isOpen = ($currentHour >= 11.0 && $currentHour <= 23.5);
    return [
        'isOpen' => $isOpen,
        'statusText' => $isOpen ? 'Open Now' : 'Closed Now',
        'badgeClass' => $isOpen ? 'badge-open' : 'badge-closed',
        'timeFormatted' => date('h:i A')
    ];
}
