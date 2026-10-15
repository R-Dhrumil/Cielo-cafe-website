<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MenuCategory extends Model
{
    protected static string $table = 'menu_categories';

    public static function getActive(string $orderBy = 'sort_order ASC'): array
    {
        return self::where('`is_active` = 1', [], $orderBy);
    }
}
