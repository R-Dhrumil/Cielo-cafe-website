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

        return $rows[0] ?? null;
    }
}
