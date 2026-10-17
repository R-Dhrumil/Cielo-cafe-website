<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class UserReward extends Model
{
    protected static string $table = 'user_rewards';
    protected static bool $hasUpdatedAt = false;

    public static function getActiveRewards(int|string $userId): array
    {
        $today = date('Y-m-d H:i:s');
        return self::where(
            '`user_id` = :uid AND `status` = "active" AND (`expires_at` IS NULL OR `expires_at` >= :now)',
            ['uid' => $userId, 'now' => $today],
            'id DESC'
        );
    }

    /**
     * Find a specific active reward for a user
     */
    public static function findActiveById(int|string $id, int|string $userId): ?array
    {
        $today = date('Y-m-d H:i:s');
        return self::first(
            '`id` = :id AND `user_id` = :uid AND `status` = "active" AND (`expires_at` IS NULL OR `expires_at` >= :now)',
            ['id' => $id, 'uid' => $userId, 'now' => $today]
        );
    }

    /**
     * Mark reward as redeemed/used on an order
     */
    public static function markUsed(int|string $id, int|string $orderId): bool
    {
        $reward = self::find($id);
        if (!$reward) {
            return false;
        }

        $val = !empty($reward['reward_value_json']) ? json_decode($reward['reward_value_json'], true) : [];
        $val['redeemed_order_id'] = (int)$orderId;

        $db = db();
        $stmt = $db->query(
            "UPDATE `" . self::$table . "` SET `status` = 'used', `redeemed_at` = ?, `reward_value_json` = ? WHERE `id` = ?",
            [date('Y-m-d H:i:s'), json_encode($val), $id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Restore a reward back to active (e.g. on order cancellation/refund)
     */
    public static function restoreActive(int|string $id): bool
    {
        $reward = self::find($id);
        if (!$reward) {
            return false;
        }

        $val = !empty($reward['reward_value_json']) ? json_decode($reward['reward_value_json'], true) : [];
        unset($val['redeemed_order_id']);

        $db = db();
        $stmt = $db->query(
            "UPDATE `" . self::$table . "` SET `status` = 'active', `redeemed_at` = NULL, `reward_value_json` = ? WHERE `id` = ?",
            [json_encode($val), $id]
        );
        return $stmt->rowCount() > 0;
    }
}
