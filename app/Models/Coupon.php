<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Coupon extends Model
{
    protected static string $table = 'coupons';

    public static function findActiveByCode(string $code): ?array
    {
        $today = date('Y-m-d');
        $code = strtoupper(trim($code));

        $rows = self::where(
            '`code` = :code AND `is_active` = 1 AND `start_date` <= :today1 AND `end_date` >= :today2',
            ['code' => $code, 'today1' => $today, 'today2' => $today],
            'id DESC',
            1
        );

        if (empty($rows[0])) {
            return null;
        }

        $row = $rows[0];
        // Standardize attribute aliases
        $row['discount_type'] = $row['type'] ?? 'percentage';
        $row['discount_value'] = (float)($row['value'] ?? 0);
        $row['max_uses_per_user'] = (int)($row['usage_limit_per_user'] ?? 1);
        $row['max_uses_total'] = (int)($row['usage_limit_total'] ?? 0);

        return $row;
    }

    public static function findValidCoupon(string $code): ?array
    {
        return self::findActiveByCode($code);
    }
}
