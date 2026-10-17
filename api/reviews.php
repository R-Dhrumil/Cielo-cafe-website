<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Verified Customer Review API
 * 
 * Endpoints:
 * - GET  /api/reviews.php                       (Public approved reviews & rating metrics)
 * - GET  /api/reviews.php?action=check_eligibility&order_id=X (Verify patron order eligibility)
 * - POST /api/reviews.php?action=submit         (Submit verified review, earn +50 points)
 * - POST /api/reviews.php?action=moderate       (Staff moderation: approve/hide)
 */

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Services\ReviewService;
use App\Services\CustomerAuthService;
use App\Services\AuthService;

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
$action = (string)($_GET['action'] ?? ($input['action'] ?? 'list'));

switch ($action) {
    case 'list':
    case '':
        // Public approved reviews & stats
        $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
        $reviews = ReviewService::getPublicReviews($limit);
        $stats = ReviewService::getStats();

        Response::json(true, [
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
        break;

    case 'check_eligibility':
        $user = CustomerAuthService::user();
        if (!$user) {
            Response::json(false, null, 'Please sign in to check review eligibility.', 401);
        }

        $orderId = (int)($_GET['order_id'] ?? ($input['order_id'] ?? 0));
        if ($orderId <= 0) {
            Response::json(false, null, 'Invalid order ID provided.', 400);
        }

        $eligibility = ReviewService::canReviewOrder($orderId, (int)$user['id']);
        Response::json($eligibility['eligible'], $eligibility, $eligibility['reason'] ?? null);
        break;

    case 'submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(false, null, 'Method not allowed. Use POST.', 405);
        }

        $user = CustomerAuthService::user();
        if (!$user) {
            Response::json(false, null, 'Please sign in to submit a review.', 401);
        }

        $orderId = (int)($input['order_id'] ?? 0);
        $rating = (int)($input['rating'] ?? 0);
        $reviewText = (string)($input['review_text'] ?? '');
        $photoUrl = isset($input['photo_url']) ? (string)$input['photo_url'] : null;

        if ($orderId <= 0) {
            Response::json(false, null, 'Please specify a valid order ID.', 400);
        }

        try {
            $result = ReviewService::submitReview($orderId, (int)$user['id'], $rating, $reviewText, $photoUrl);
            Response::json(true, $result, $result['message']);
        } catch (Throwable $e) {
            Response::json(false, null, $e->getMessage(), 400);
        }
        break;

    case 'moderate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::json(false, null, 'Method not allowed. Use POST.', 405);
        }

        // Verify staff access
        $staff = AuthService::user();
        if (!$staff || !in_array($staff['role'] ?? '', ['owner', 'admin', 'manager'], true)) {
            Response::json(false, null, 'Unauthorized. Staff credentials required.', 403);
        }

        $reviewId = (int)($input['review_id'] ?? 0);
        $status = (string)($input['status'] ?? '');

        if ($reviewId <= 0 || empty($status)) {
            Response::json(false, null, 'Review ID and status are required.', 400);
        }

        try {
            $result = ReviewService::moderateReview($reviewId, $status, (int)$staff['id']);
            Response::json(true, $result, "Review #{$reviewId} updated to {$status}.");
        } catch (Throwable $e) {
            Response::json(false, null, $e->getMessage(), 400);
        }
        break;

    default:
        Response::json(false, null, "Unknown action '{$action}'.", 400);
        break;
}
