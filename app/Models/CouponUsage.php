<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class CouponUsage extends Model
{
    protected static string $table = 'coupon_usage';
    protected static bool $hasCreatedAt = false;
    protected static bool $hasUpdatedAt = false;

    public static function getUserUsageCount(int|string $couponId, int|string $userId): int
    {
        return self::count('`coupon_id` = :cid AND `user_id` = :uid', ['cid' => $couponId, 'uid' => $userId]);
    }

    public static function getTotalUsageCount(int|string $couponId): int
    {
        return self::count('`coupon_id` = :cid', ['cid' => $couponId]);
    }

    public static function recordUsage(int|string $couponId, int|string $orderId, int|string $userId, int $discountPaise): int
    {
        return (int)self::create([
            'coupon_id' => $couponId,
            'order_id' => $orderId,
            'user_id' => $userId,
            'discount_paise' => $discountPaise,
            'used_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
