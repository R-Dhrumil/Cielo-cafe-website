<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Visit extends Model
{
    protected static string $table = 'visits';
    protected static bool $hasUpdatedAt = false;

    /**
     * Log a customer visit for today (Idempotent: 1 visit per calendar day)
     */
    public static function logVisit(int|string $userId, int|string $orderId, bool $isCounted = true, ?string $visitDate = null): bool
    {
        $today = $visitDate ?: date('Y-m-d');

        // Check if visit already recorded for this user today
        $existing = self::where('`user_id` = :uid AND `visit_date` = :vdate', [
            'uid' => $userId,
            'vdate' => $today,
        ], '', 1);

        if (!empty($existing)) {
            return false; // Already recorded today
        }

        self::create([
            'user_id' => $userId,
            'visit_date' => $today,
            'order_id' => $orderId,
            'is_counted' => $isCounted ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Count qualified visits for a user within a rolling day window (e.g. 30 days)
     */
    public static function countVisitsInWindow(int|string $userId, int $daysWindow = 30): int
    {
        $startDate = date('Y-m-d', strtotime("-{$daysWindow} days"));
        return self::count('`user_id` = :uid AND `visit_date` >= :sdate AND `is_counted` = 1', [
            'uid' => $userId,
            'sdate' => $startDate,
        ]);
    }
}
