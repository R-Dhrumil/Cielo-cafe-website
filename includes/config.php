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
