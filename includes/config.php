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

// Seamlessly load dynamic CMS overrides from DB if available
try {
    if (class_exists(\App\Models\Setting::class)) {
        $dbPhone = \App\Models\Setting::get('cafe_phone');
        if (!empty($dbPhone)) {
            $business['contact']['phone'] = (string)$dbPhone;
        }
        $dbEmail = \App\Models\Setting::get('cafe_email');
        if (!empty($dbEmail)) {
            $business['contact']['email'] = (string)$dbEmail;
        }
        $dbAddress = \App\Models\Setting::get('cafe_address');
        if (!empty($dbAddress)) {
            $business['contact']['address']['formatted'] = (string)$dbAddress;
        }
        $dbHours = \App\Models\Setting::get('cafe_hours');
        if (!empty($dbHours)) {
            $business['hours']['display'] = (string)$dbHours;
        }
        $dbWhatsapp = \App\Models\Setting::get('cafe_whatsapp');
        if (!empty($dbWhatsapp)) {
            $business['contact']['whatsapp'] = (string)$dbWhatsapp;
        }
        $storyHeading = \App\Models\Setting::get('cms_story_heading');
        if (!empty($storyHeading)) {
            $business['story']['heading'] = (string)$storyHeading;
        }
        $storyQuote = \App\Models\Setting::get('cms_story_quote');
        if (!empty($storyQuote)) {
            $business['story']['quote'] = (string)$storyQuote;
        }
        $cmsHeroName = (string)(\App\Models\Setting::get('cms_hero_name') ?: 'Cielo');
        $cmsHeroTagline = (string)(\App\Models\Setting::get('cms_hero_tagline') ?: 'Cafe and Restro');
        $cmsHeroBadge = (string)(\App\Models\Setting::get('cms_hero_badge') ?: 'Vadodara · Italian Sanctuary');
        $cmsHeroCta = (string)(\App\Models\Setting::get('cms_hero_cta') ?: 'Explore Artisanal Menu');

        if (class_exists(\App\Models\Review::class)) {
            $approvedReviews = \App\Models\Review::getApprovedWithDetails(6);
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
                $reviews['reviews'] = array_merge($dbReviewCards, $reviews['reviews'] ?? []);
            }
        }
    }
} catch (\Throwable $e) {
    // Graceful fallback to static JSON files
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
