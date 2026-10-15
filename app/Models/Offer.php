<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Offer extends Model
{
    protected static string $table = 'offers';

    public static function getActive(string $orderBy = 'priority ASC, id DESC'): array
    {
        return self::where('`is_active` = 1', [], $orderBy);
    }
}
