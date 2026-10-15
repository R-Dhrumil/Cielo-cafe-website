<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class MenuItem extends Model
{
    protected static string $table = 'menu_items';

    public static function getByCategory(int|string $categoryId): array
    {
        return self::where('`category_id` = :cat_id AND `is_available` = 1', ['cat_id' => $categoryId], 'sort_order ASC, id ASC');
    }

    public static function getWithCustomizations(int|string $itemId): ?array
    {
        $item = self::find($itemId);
        if (!$item) return null;

        $item['variants'] = ItemVariant::getByItem($itemId);
        $item['addons'] = ItemAddon::getByItem($itemId);

        return $item;
    }
}
