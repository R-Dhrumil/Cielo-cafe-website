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

    /**
     * Toggle item stock availability (86ing items)
     */
    public static function toggleAvailability(int|string $itemId): bool
    {
        $item = self::find($itemId);
        if (!$item) return false;

        $newVal = (int)($item['is_available'] ?? 1) === 1 ? 0 : 1;
        self::updateById($itemId, ['is_available' => $newVal]);
        return (bool)$newVal;
    }

    /**
     * Update dish price in paise
     */
    public static function updatePrice(int|string $itemId, int $pricePaise): bool
    {
        if ($pricePaise < 0) return false;
        return (bool)self::updateById($itemId, ['price_paise' => $pricePaise]);
    }

    /**
     * Fetch all menu items joined with category name for admin catalog
     */
    public static function getAllWithCategory(?int $categoryId = null, ?string $search = null): array
    {
        $db = db();
        $params = [];
        $where = "1=1";

        if ($categoryId !== null && $categoryId > 0) {
            $where .= " AND mi.category_id = ?";
            $params[] = $categoryId;
        }

        if ($search !== null && trim($search) !== '') {
            $where .= " AND (mi.name LIKE ? OR mi.description LIKE ?)";
            $params[] = '%' . trim($search) . '%';
            $params[] = '%' . trim($search) . '%';
        }

        $sql = "SELECT mi.*, mc.name AS category_name, mc.slug AS category_slug
                FROM menu_items mi
                LEFT JOIN menu_categories mc ON mi.category_id = mc.id
                WHERE {$where}
                ORDER BY mc.sort_order ASC, mi.sort_order ASC, mi.id ASC";

        return $db->fetchAll($sql, $params);
    }
}
