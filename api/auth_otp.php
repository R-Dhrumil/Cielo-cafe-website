<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Customer Mobile OTP Authentication API
 * 
 * Endpoints:
 * - POST /api/auth_otp.php?action=send_otp
 * - POST /api/auth_otp.php?action=verify_otp
 * - GET  /api/auth_otp.php?action=me
 * - POST /api/auth_otp.php?action=update_profile
 * - POST /api/auth_otp.php?action=logout
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Services\CustomerAuthService;

// Read JSON input or POST form data
$rawBody = file_get_contents('php://input');
$jsonBody = [];
if ($rawBody !== false && $rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $jsonBody = $decoded;
    }
}

$input = array_merge($_POST, $jsonBody);
$action = (string)($_GET['action'] ?? ($input['action'] ?? ''));
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

switch ($action) {
    case 'send_otp':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(false, null, 'Method not allowed. Use POST.', 405);
        }

        $phone = (string)($input['phone'] ?? '');
        $result = CustomerAuthService::sendOtp($phone, $ip);

        if (!$result['success']) {
            $code = !empty($result['rate_limited']) ? 429 : 422;
            Response::json(false, $result, $result['error'], $code);
        }

        Response::json(true, $result, $result['message'], 200);
        break;

    case 'verify_otp':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(false, null, 'Method not allowed. Use POST.', 405);
        }

        $phone = (string)($input['phone'] ?? '');
        $otp = (string)($input['otp'] ?? '');
        $name = isset($input['name']) && trim((string)$input['name']) !== '' ? trim((string)$input['name']) : null;

        $result = CustomerAuthService::verifyOtp($phone, $otp, $ip, $name);

        if (!$result['success']) {
            Response::json(false, $result, $result['error'], 422);
        }

        Response::json(true, $result, $result['message'], 200);
        break;

    case 'me':
        $user = CustomerAuthService::getCurrentUser();
        if (!$user) {
            Response::json(true, ['authenticated' => false, 'user' => null], 'Guest patron (not logged in)');
        }

        $metrics = CustomerAuthService::getProfileMetrics((int)$user['id']);

        Response::json(true, [
            'authenticated' => true,
            'user' => $user,
            'points' => $metrics['points'] ?? null,
            'tier' => $metrics['points']['tier'] ?? null,
            'visits' => $metrics['visits'] ?? null,
            'rewards' => $metrics['rewards'] ?? [],
            'coupons' => $metrics['coupons'] ?? [],
        ], 'Customer authenticated');
        break;

    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(false, null, 'Method not allowed. Use POST.', 405);
        }

        $user = CustomerAuthService::getCurrentUser();
        if (!$user) {
            Response::json(false, null, 'Authentication required to update profile.', 401);
        }

        $result = CustomerAuthService::updateProfile((int)$user['id'], $input);
        if (!$result['success']) {
            Response::json(false, $result, $result['error'], 422);
        }

        Response::json(true, $result, $result['message'], 200);
        break;

    case 'logout':
        CustomerAuthService::logout();
        Response::json(true, null, 'Logged out successfully.');
        break;

    default:
        Response::json(false, [
            'available_actions' => ['send_otp', 'verify_otp', 'me', 'update_profile', 'logout']
        ], 'Action parameter missing or invalid. Use ?action=send_otp, ?action=verify_otp, or ?action=me', 400);
}
