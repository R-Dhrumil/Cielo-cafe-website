<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ItemVariant extends Model
{
    protected static string $table = 'item_variants';

    public static function getByItem(int|string $itemId): array
    {
        return self::where('`item_id` = :item_id', ['item_id' => $itemId], 'sort_order ASC, id ASC');
    }
}
