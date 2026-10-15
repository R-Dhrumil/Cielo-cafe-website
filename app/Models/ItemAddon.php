<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ItemAddon extends Model
{
    protected static string $table = 'item_addons';

    public static function getByItem(int|string $itemId): array
    {
        return self::where('`item_id` = :item_id AND `is_available` = 1', ['item_id' => $itemId], 'sort_order ASC, id ASC');
    }
}
