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
}
