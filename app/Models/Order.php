<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Order extends Model
{
    protected static string $table = 'orders';

    public static function generateOrderNumber(): string
    {
        $date = date('ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        return "CIELO-{$date}-{$random}";
    }

    public static function getWithItems(int|string $orderId): ?array
    {
        $order = self::find($orderId);
        if (!$order) return null;

        $order['items'] = OrderItem::where('`order_id` = :oid', ['oid' => $orderId], 'id ASC');
        $order['table'] = Table::find($order['table_id']);
        if (!empty($order['user_id'])) {
            $order['user'] = User::find($order['user_id']);
        }

        return $order;
    }
}
