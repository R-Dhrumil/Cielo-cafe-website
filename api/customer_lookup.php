<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Customer Identification & Loyalty Lookup API
 *
 * Endpoint: POST /api/customer_lookup.php
 * Accepts phone (+91 10-digit) and optional name.
 * Returns customer points balance, active rewards, and sets customer session.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Models\User;
use App\Models\PointsLedger;
use App\Models\UserReward;
use App\Services\SettingsService;

// Read JSON input or fallback to $_POST
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$phone = trim((string)($input['phone'] ?? ''));
$name = trim((string)($input['name'] ?? ''));

// Clean phone (strip +91, spaces, dashes)
$cleanPhone = preg_replace('/[^\d]/', '', $phone);
if (str_starts_with($cleanPhone, '91') && strlen($cleanPhone) === 12) {
    $cleanPhone = substr($cleanPhone, 2);
}

// Validate 10-digit Indian phone number (starts with 6-9)
if (!preg_match('/^[6-9]\d{9}$/', $cleanPhone)) {
    Response::json(false, [], 'Please enter a valid 10-digit mobile number.', 422);
    exit;
}

try {
    // Find or create customer
    $user = User::findOrCreateByPhone($cleanPhone, $name !== '' ? $name : 'Valued Guest');

    // Update name if guest provided one
    if ($name !== '' && ($user['name'] === 'Valued Guest' || empty($user['name']))) {
        User::updateById($user['id'], ['name' => $name]);
        $user['name'] = $name;
    }

    // Retrieve live points balance and rewards
    $pointsBalance = PointsLedger::getBalance((int)$user['id']);
    $activeRewards = UserReward::getActiveRewards((int)$user['id']);

    // Loyalty conversion configuration
    $loyaltyConfig = SettingsService::getLoyaltyRules();
    $pointsValueRupees = ($pointsBalance * ($loyaltyConfig['redeem_value_per_point_paise'] ?? 100)) / 100.0;

    // Bind to session
    startCieloSession();
    $_SESSION['cielo_user'] = [
        'id' => (int)$user['id'],
        'name' => (string)$user['name'],
        'phone' => (string)$user['phone'],
        'points_balance' => $pointsBalance,
    ];

    Response::json(true, [
        'user' => [
            'id' => (int)$user['id'],
            'name' => (string)$user['name'],
            'phone' => (string)$user['phone'],
        ],
        'loyalty' => [
            'points_balance' => $pointsBalance,
            'points_value_rupees' => $pointsValueRupees,
            'active_rewards' => $activeRewards,
            'earn_rate' => $loyaltyConfig['earn_points_per_100_inr'] ?? 10,
        ]
    ], 'Welcome to Cielo! Loyalty profile connected.');
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to look up customer profile: ' . $e->getMessage(), 500);
}
