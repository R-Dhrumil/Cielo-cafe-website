<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Models\PointsLedger;
use App\Models\Visit;
use App\Models\RewardRule;
use App\Models\UserReward;
use App\Models\Setting;
use App\Models\AuditLog;
use RuntimeException;

/**
 * Cielo Cafe and Restro - Dual-Track Loyalty Retention Engine
 * 
 * Manages:
 * - Spend-based points accrual & ledger debits
 * - Anti-misuse single-visit-per-day tracking with minimum spend qualification
 * - 5-visit milestone detection & automated reward voucher generation
 * - Concurrency-protected reward voucher redemption at checkout
 * - Full reversal & refund safeguards on order cancellations
 */
class LoyaltyService
{
    /**
     * Calculate points earned for a given net subtotal in Paise
     */
    public static function calculateEarnedPoints(int $netSpendPaise): int
    {
        if ($netSpendPaise <= 0) {
            return 0;
        }

        $rate = 10; // Default 10 points per ₹100 INR
        if (class_exists(Setting::class)) {
            $settingRate = Setting::get('loyalty_points_per_100');
            if ($settingRate !== null && $settingRate !== '') {
                $rate = (int)$settingRate;
            }
        }

        return (int)floor(($netSpendPaise / 10000) * $rate);
    }

    /**
     * Award loyalty points for a paid/completed order
     * Prevents double-accrual by checking existing order credits in points_ledger.
     */
    public static function awardOrderPoints(int $orderId, ?int $userId = null): ?int
    {
        $order = Order::find($orderId);
        if (!$order) {
            return null;
        }

        $userId = $userId ?: (!empty($order['user_id']) ? (int)$order['user_id'] : null);
        if (!$userId) {
            return null;
        }

        // Prevent duplicate crediting for same order
        $existing = db()->fetchColumn(
            "SELECT COUNT(*) FROM points_ledger WHERE order_id = ? AND type = 'credit' AND points > 0",
            [$orderId]
        );
        if ((int)$existing > 0) {
            return 0; // Already credited
        }

        // Calculate points based on net spent (subtotal minus discount)
        $netPaise = max(0, (int)$order['subtotal_paise'] - (int)$order['discount_paise']);
        $earnedPoints = self::calculateEarnedPoints($netPaise);

        if ($earnedPoints > 0) {
            PointsLedger::credit(
                $userId,
                $earnedPoints,
                "Earned on Order {$order['order_number']}",
                $orderId
            );
        }

        return $earnedPoints;
    }

    /**
     * Record a customer visit for an order with anti-misuse rules:
     * - Exactly 1 visit per patron per calendar day
     * - Minimum spend qualification (default ₹200 / 20000 paise)
     */
    public static function recordOrderVisit(int $orderId, ?int $userId = null, ?string $visitDate = null): bool
    {
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }

        $userId = $userId ?: (!empty($order['user_id']) ? (int)$order['user_id'] : null);
        if (!$userId) {
            return false;
        }

        // Check minimum qualifying spend for visit point (e.g. ₹200)
        $minSpendPaise = 20000;
        $rule = RewardRule::first('`trigger_type` = "visit_milestone" AND `is_active` = 1');
        if ($rule && !empty($rule['config_json'])) {
            $cfg = json_decode($rule['config_json'], true);
            if (!empty($cfg['min_order_paise'])) {
                $minSpendPaise = (int)$cfg['min_order_paise'];
            }
        }

        $isCounted = ((int)$order['total_paise'] >= $minSpendPaise);

        return Visit::logVisit($userId, $orderId, $isCounted, $visitDate);
    }

    /**
     * Evaluate visit milestones and automatically grant rewards when milestone thresholds are reached.
     * e.g. 5 qualifying visits within 30 days -> award free pizza or dessert voucher.
     * 
     * @param int $userId
     * @param string|null $referenceDate
     * @return array Array of newly granted reward vouchers
     */
    public static function checkAndGrantMilestoneRewards(int $userId, ?string $referenceDate = null): array
    {
        $db = db();
        $rules = RewardRule::getActive('visit_milestone');
        $granted = [];

        foreach ($rules as $rule) {
            $config = !empty($rule['config_json']) ? json_decode($rule['config_json'], true) : [];
            $rewardDef = !empty($rule['reward_json']) ? json_decode($rule['reward_json'], true) : [];

            $visitsRequired = (int)($config['visits_required'] ?? 5);
            $windowDays = (int)($config['window_days'] ?? 30);
            $cooldownDays = (int)($config['cooldown_days'] ?? 30);
            $validityDays = (int)($rule['validity_days'] ?? 30);

            // 1. Count qualified visits in rolling window
            $startDate = date('Y-m-d', strtotime("-{$windowDays} days", $referenceDate ? strtotime($referenceDate) : time()));
            $qualifiedInWindow = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM visits WHERE user_id = ? AND visit_date >= ? AND is_counted = 1",
                [$userId, $startDate]
            );

            // 2. Count total lifetime qualified visits
            $lifetimeQualified = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM visits WHERE user_id = ? AND is_counted = 1",
                [$userId]
            );

            // 3. Count how many rewards have been granted under this rule
            $existingGrantsCount = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM user_rewards WHERE user_id = ? AND rule_id = ?",
                [$userId, $rule['id']]
            );

            // 4. Cooldown verification: check last granted reward date
            $lastGrant = UserReward::first(
                '`user_id` = :uid AND `rule_id` = :rid ORDER BY `id` DESC',
                ['uid' => $userId, 'rid' => $rule['id']]
            );

            if ($lastGrant) {
                $lastGrantTime = strtotime($lastGrant['created_at']);
                $cooldownLimit = strtotime("-{$cooldownDays} days", $referenceDate ? strtotime($referenceDate) : time());
                if ($lastGrantTime > $cooldownLimit) {
                    continue; // Still within cooldown window
                }
            }

            // 5. Check if user has satisfied the milestone threshold for next reward
            $targetVisitsNeeded = ($existingGrantsCount + 1) * $visitsRequired;
            if ($qualifiedInWindow >= $visitsRequired && $lifetimeQualified >= $targetVisitsNeeded) {
                // Milestone Met! Generate new reward voucher
                $rewardTitle = $rewardDef['title'] ?? 'Complimentary Milestone Reward';
                $rewardItem = $rewardDef['item_name'] ?? 'Citrus Cold Brew or Venetian Tiramisu';
                $rewardType = $rewardDef['type'] ?? 'free_item';

                $rewardValue = [
                    'title' => $rewardTitle,
                    'item_name' => $rewardItem,
                    'type' => $rewardType,
                    'milestone_cycle' => $existingGrantsCount + 1,
                    'discount_paise' => (int)($rewardDef['discount_paise'] ?? 24000), // Default ₹240 value
                ];

                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$validityDays} days", $referenceDate ? strtotime($referenceDate) : time()));

                $newRewardId = UserReward::create([
                    'user_id' => $userId,
                    'rule_id' => (int)$rule['id'],
                    'reward_type' => $rewardType,
                    'reward_value_json' => json_encode($rewardValue),
                    'status' => 'active',
                    'expires_at' => $expiresAt,
                    'redeemed_at' => null,
                    'created_at' => $referenceDate ?: date('Y-m-d H:i:s'),
                ]);

                AuditLog::log(
                    null,
                    'milestone_reward_granted',
                    'user_rewards',
                    (int)$newRewardId,
                    [
                        'user_id' => $userId,
                        'rule_id' => $rule['id'],
                        'cycle' => $existingGrantsCount + 1,
                        'visits' => $lifetimeQualified,
                    ]
                );

                $granted[] = [
                    'id' => (int)$newRewardId,
                    'title' => $rewardTitle,
                    'item_name' => $rewardItem,
                    'expires_at' => $expiresAt,
                    'cycle' => $existingGrantsCount + 1,
                ];
            }
        }

        return $granted;
    }

    /**
     * Unified Order Settlement & Lifecycle Processor
     * Awards points, logs visit, and triggers milestone reward evaluation.
     */
    public static function processOrderCompletion(int $orderId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found',
            ];
        }

        $userId = !empty($order['user_id']) ? (int)$order['user_id'] : null;

        $points = 0;
        $visitLogged = false;
        $rewards = [];

        if ($userId) {
            $points = (int)self::awardOrderPoints($orderId, $userId);
            $visitLogged = self::recordOrderVisit($orderId, $userId);
            $rewards = self::checkAndGrantMilestoneRewards($userId);
        }

        return [
            'success' => true,
            'order_id' => $orderId,
            'user_id' => $userId,
            'points_awarded' => $points,
            'visit_recorded' => $visitLogged,
            'rewards_granted' => $rewards,
        ];
    }

    /**
     * Authoritative Milestone Reward Voucher Redemption
     * Concurrency protected via database transaction.
     */
    public static function redeemReward(int $userRewardId, int $orderId, int $userId): array
    {
        $db = Database::getInstance();

        return $db->transaction(function () use ($userRewardId, $orderId, $userId) {
            $reward = UserReward::findActiveById($userRewardId, $userId);
            if (!$reward) {
                return [
                    'success' => false,
                    'error' => 'Reward voucher not found, already redeemed, or expired.',
                ];
            }

            $success = UserReward::markUsed($userRewardId, $orderId);
            if (!$success) {
                throw new RuntimeException("Failed to update reward voucher status.");
            }

            AuditLog::log(
                null,
                'reward_voucher_redeemed',
                'user_rewards',
                $userRewardId,
                ['order_id' => $orderId, 'user_id' => $userId]
            );

            return [
                'success' => true,
                'reward_id' => $userRewardId,
                'reward' => $reward,
            ];
        });
    }

    /**
     * Reversal & Refund Safeguard
     * If an order is cancelled or refunded, reverses any earned points, visit records, and restores redeemed vouchers.
     */
    public static function reverseOrderLoyalty(int $orderId, string $reason = 'order_cancelled'): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $userId = !empty($order['user_id']) ? (int)$order['user_id'] : null;
        $db = db();

        $pointsRevoked = 0;
        $pointsRefunded = 0;
        $visitRevoked = false;
        $rewardRestored = false;

        // 1. Reverse Points Earned on this Order
        $creditRow = $db->fetch(
            "SELECT * FROM points_ledger WHERE order_id = ? AND type = 'credit' AND points > 0 LIMIT 1",
            [$orderId]
        );

        if ($creditRow && $userId) {
            $earned = (int)$creditRow['points'];
            // Create reversing debit
            PointsLedger::create([
                'user_id' => $userId,
                'points' => -$earned,
                'type' => 'debit',
                'reason' => "Reversal: {$reason} (Order {$order['order_number']})",
                'order_id' => $orderId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $pointsRevoked = $earned;
        }

        // 2. Refund Points Redeemed on this Order (if any)
        $debitRow = $db->fetch(
            "SELECT * FROM points_ledger WHERE order_id = ? AND type = 'debit' AND points < 0 AND reason LIKE '%Redeem%' LIMIT 1",
            [$orderId]
        );

        if ($debitRow && $userId) {
            $spent = abs((int)$debitRow['points']);
            PointsLedger::create([
                'user_id' => $userId,
                'points' => $spent,
                'type' => 'credit',
                'reason' => "Refund: Points restored from {$reason} (Order {$order['order_number']})",
                'order_id' => $orderId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $pointsRefunded = $spent;
        }

        // 3. Revoke Visit Count
        $visitStmt = $db->query(
            "UPDATE visits SET is_counted = 0 WHERE order_id = ?",
            [$orderId]
        );
        $visitRevoked = ($visitStmt->rowCount() > 0);

        // 4. Restore Redeemed Reward Voucher (if any)
        $usedReward = $db->fetch(
            "SELECT * FROM user_rewards WHERE status = 'used' AND reward_value_json LIKE ? LIMIT 1",
            ['%"redeemed_order_id":' . $orderId . '%']
        );

        if ($usedReward) {
            UserReward::restoreActive($usedReward['id']);
            $rewardRestored = true;
        }

        AuditLog::log(
            null,
            'loyalty_reversal_processed',
            'orders',
            $orderId,
            [
                'reason' => $reason,
                'points_revoked' => $pointsRevoked,
                'points_refunded' => $pointsRefunded,
                'visit_revoked' => $visitRevoked,
                'reward_restored' => $rewardRestored,
            ]
        );

        return [
            'success' => true,
            'order_id' => $orderId,
            'points_revoked' => $pointsRevoked,
            'points_refunded' => $pointsRefunded,
            'visit_revoked' => $visitRevoked,
            'reward_restored' => $rewardRestored,
        ];
    }
}
