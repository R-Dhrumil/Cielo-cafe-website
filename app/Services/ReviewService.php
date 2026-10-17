<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Review;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointsLedger;
use App\Models\RewardRule;
use App\Models\AuditLog;
use App\Models\Setting;
use InvalidArgumentException;
use RuntimeException;

class ReviewService
{
    /**
     * Profanity and inappropriate content dictionary
     */
    protected static array $profanityList = [
        'abuse', 'asshole', 'bastard', 'bitch', 'bloody', 'bullshit', 'crap', 'cunt',
        'damn', 'dick', 'fuck', 'fucking', 'motherfucker', 'nigger', 'piss', 'pussy',
        'scam', 'shit', 'shitty', 'slut', 'whore', 'chutiya', 'harami', 'bhenchod',
        'madarchod', 'gandu', 'saala', 'kamina', 'randi'
    ];

    /**
     * Check if a text contains vulgarity or prohibited terms
     */
    public static function containsProfanity(string $text): bool
    {
        $normalized = strtolower(trim($text));
        // Remove non-alphanumeric characters except spaces
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized);
        $words = preg_split('/\s+/', $normalized);

        foreach (self::$profanityList as $badWord) {
            foreach ($words as $word) {
                if ($word === $badWord) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determine if a user is eligible to review an order
     */
    public static function canReviewOrder(int $orderId, int $userId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [
                'eligible' => false,
                'reason' => 'Order not found.'
            ];
        }

        if ((int)$order['user_id'] !== $userId) {
            return [
                'eligible' => false,
                'reason' => 'This order does not belong to your account.'
            ];
        }

        if ($order['status'] !== 'completed') {
            return [
                'eligible' => false,
                'reason' => 'Only completed dining orders can be reviewed.'
            ];
        }

        $existing = Review::findByOrderId($orderId);
        if ($existing) {
            return [
                'eligible' => false,
                'reason' => 'You have already reviewed this order. Thank you!',
                'existing_review' => $existing
            ];
        }

        $items = OrderItem::where('order_id = ?', [$orderId], 'id ASC');

        return [
            'eligible' => true,
            'order' => $order,
            'items' => array_map(function ($it) {
                return [
                    'id' => (int)$it['id'],
                    'item_name' => $it['item_name'],
                ];
            }, $items)
        ];
    }

    /**
     * Submit a verified customer review
     */
    public static function submitReview(
        int $orderId,
        int $userId,
        int $rating,
        string $reviewText,
        ?string $photoUrl = null
    ): array {
        $eligibility = self::canReviewOrder($orderId, $userId);
        if (!$eligibility['eligible']) {
            throw new RuntimeException($eligibility['reason'] ?? 'Not eligible to review this order.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5 stars.');
        }

        $cleanText = trim($reviewText);
        if (mb_strlen($cleanText) < 5) {
            throw new InvalidArgumentException('Review must be at least 5 characters long.');
        }
        if (mb_strlen($cleanText) > 1000) {
            throw new InvalidArgumentException('Review cannot exceed 1000 characters.');
        }

        if (self::containsProfanity($cleanText)) {
            throw new InvalidArgumentException('Your review contains inappropriate or offensive language. Please keep feedback respectful.');
        }

        // Clean photo URL if provided
        $cleanPhoto = null;
        if (!empty($photoUrl)) {
            $cleanPhoto = filter_var(trim($photoUrl), FILTER_SANITIZE_URL);
            if (!filter_var($cleanPhoto, FILTER_VALIDATE_URL) && !str_starts_with($cleanPhoto, 'assets/')) {
                $cleanPhoto = null;
            }
        }

        // Determine initial status:
        // By default, 4 or 5 star reviews without profanity are auto-approved to reward genuine patrons immediately.
        // 1-3 star reviews can be placed in 'pending' moderation for quality assurance, or configurable via Setting.
        $autoApproveHigh = (bool)(Setting::get('reviews_auto_approve_positive', true));
        $initialStatus = ($autoApproveHigh && $rating >= 4) ? 'approved' : 'pending';

        $reviewId = (int)Review::create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'rating' => $rating,
            'review_text' => $cleanText,
            'photo_url' => $cleanPhoto,
            'status' => $initialStatus,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Award loyalty points according to active review_submit rule
        $pointsAwarded = 0;
        $reviewRule = RewardRule::first('trigger_type = ? AND is_active = 1', ['review_submit']);
        if ($reviewRule) {
            $rewardConfig = !empty($reviewRule['reward_json']) ? json_decode($reviewRule['reward_json'], true) : [];
            $pointsToAward = (int)($rewardConfig['points'] ?? 50);

            if ($pointsToAward > 0) {
                // Ensure idempotency: check if already awarded for this order
                $alreadyAwarded = PointsLedger::first(
                    'user_id = ? AND order_id = ? AND type = "credit" AND reason LIKE ?',
                    [$userId, $orderId, '%Verified dining review%']
                );

                if (!$alreadyAwarded) {
                    $orderNumber = $eligibility['order']['order_number'] ?? "#{$orderId}";
                    PointsLedger::credit(
                        $userId,
                        $pointsToAward,
                        "Verified dining review reward: Order #{$orderNumber}",
                        $orderId
                    );
                    $pointsAwarded = $pointsToAward;
                }
            }
        }

        AuditLog::log(null, 'review_submitted', 'review', $reviewId, [
            'user_id' => $userId,
            'rating' => $rating,
            'order_id' => $orderId,
            'status' => $initialStatus,
            'points_awarded' => $pointsAwarded
        ]);

        return [
            'success' => true,
            'review_id' => $reviewId,
            'status' => $initialStatus,
            'points_awarded' => $pointsAwarded,
            'current_balance' => PointsLedger::getBalance($userId),
            'message' => $pointsAwarded > 0 
                ? "Review submitted successfully! +{$pointsAwarded} loyalty points added to your balance."
                : "Thank you for your valuable feedback!"
        ];
    }

    /**
     * Moderate a review (Approve, Hide, or set Pending)
     */
    public static function moderateReview(int $reviewId, string $status, ?int $staffId = null): array
    {
        $allowed = ['approved', 'hidden', 'pending'];
        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException("Invalid moderation status '{$status}'. Allowed: " . implode(', ', $allowed));
        }

        $review = Review::find($reviewId);
        if (!$review) {
            throw new RuntimeException("Review #{$reviewId} not found.");
        }

        $previousStatus = $review['status'];
        if ($previousStatus === $status) {
            return ['success' => true, 'review' => $review, 'changed' => false];
        }

        Review::update($reviewId, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $orderId = (int)$review['order_id'];
        $reviewUserId = (int)$review['user_id'];

        // Points handling on hide / restore:
        // If hidden/abusive, reverse the points awarded
        if ($status === 'hidden') {
            $ledgerEntry = PointsLedger::first(
                'user_id = ? AND order_id = ? AND type = "credit" AND reason LIKE ?',
                [$reviewUserId, $orderId, '%Verified dining review%']
            );

            if ($ledgerEntry && (int)$ledgerEntry['points'] > 0) {
                // Check if already reversed
                $alreadyReversed = PointsLedger::first(
                    'user_id = ? AND order_id = ? AND type = "debit" AND reason LIKE ?',
                    [$reviewUserId, $orderId, '%reversal for removed review%']
                );

                if (!$alreadyReversed) {
                    PointsLedger::debit(
                        $reviewUserId,
                        (int)$ledgerEntry['points'],
                        "Points reversal for removed review #{$reviewId}",
                        $orderId
                    );
                }
            }
        } elseif ($status === 'approved' && $previousStatus === 'hidden') {
            // If restored from hidden, check if we need to restore points
            $reversalEntry = PointsLedger::first(
                'user_id = ? AND order_id = ? AND type = "debit" AND reason LIKE ?',
                [$reviewUserId, $orderId, '%reversal for removed review%']
            );

            if ($reversalEntry) {
                $points = abs((int)$reversalEntry['points']);
                PointsLedger::credit(
                    $reviewUserId,
                    $points,
                    "Verified dining review reward: Restored review #{$reviewId}",
                    $orderId
                );
            }
        }

        AuditLog::log($staffId, 'review_moderated', 'review', $reviewId, [
            'previous_status' => $previousStatus,
            'new_status' => $status,
        ]);

        return [
            'success' => true,
            'review' => Review::find($reviewId),
            'changed' => true
        ];
    }

    /**
     * Get approved public reviews for display on homepage or landing pages
     */
    public static function getPublicReviews(int $limit = 6): array
    {
        return Review::getApprovedWithDetails($limit);
    }

    /**
     * Get review summary stats
     */
    public static function getStats(): array
    {
        return Review::getSummaryStats();
    }
}
