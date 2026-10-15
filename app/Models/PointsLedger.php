<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

class PointsLedger extends Model
{
    protected static string $table = 'points_ledger';
    protected static bool $hasUpdatedAt = false;

    /**
     * Calculate user's active points balance from the immutable ledger
     */
    public static function getBalance(int|string $userId): int
    {
        $sum = db()->fetchColumn(
            "SELECT COALESCE(SUM(points), 0) FROM `" . self::$table . "` WHERE `user_id` = :uid",
            ['uid' => $userId]
        );
        return (int)$sum;
    }

    /**
     * Credit loyalty points to user
     */
    public static function credit(int|string $userId, int $points, string $reason, ?int $orderId = null): int|string
    {
        if ($points <= 0) {
            throw new RuntimeException("Credit points must be positive.");
        }

        return self::create([
            'user_id' => $userId,
            'points' => $points,
            'type' => 'credit',
            'reason' => $reason,
            'order_id' => $orderId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Debit loyalty points from user (points stored as negative integer)
     */
    public static function debit(int|string $userId, int $points, string $reason, ?int $orderId = null): int|string
    {
        if ($points <= 0) {
            throw new RuntimeException("Debit points must be positive.");
        }

        $currentBalance = self::getBalance($userId);
        if ($currentBalance < $points) {
            throw new RuntimeException("Insufficient points balance: {$currentBalance} available, {$points} required.");
        }

        return self::create([
            'user_id' => $userId,
            'points' => -$points,
            'type' => 'debit',
            'reason' => $reason,
            'order_id' => $orderId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Disallow update and delete on immutable ledger
     */
    public static function updateById(int|string $id, array $data): int
    {
        throw new RuntimeException("Points ledger entries are immutable and cannot be updated.");
    }

    public static function deleteById(int|string $id): int
    {
        throw new RuntimeException("Points ledger entries are immutable and cannot be deleted.");
    }
}
