<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Live Order Status API
 *
 * Endpoint: GET /api/order_status.php?order={order_number}
 * Returns live status stepper, payment status, and order item breakdown.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Core\Money;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;

$orderNumber = trim((string)($_GET['order'] ?? ''));
$orderId = (int)($_GET['id'] ?? 0);

$order = null;
if ($orderNumber !== '') {
    $order = Order::findBy('order_number', $orderNumber);
} elseif ($orderId > 0) {
    $order = Order::find($orderId);
}

if (!$order) {
    Response::json(false, [], 'Order not found.', 404);
    exit;
}

try {
    $orderId = (int)$order['id'];
    $items = OrderItem::where('`order_id` = :oid', ['oid' => $orderId]);
    $table = Table::find((int)$order['table_id']);

    // Map order status to stepper index (1-4)
    $statusMap = [
        'placed' => 1,
        'preparing' => 2,
        'ready' => 3,
        'completed' => 4,
        'cancelled' => 0,
    ];

    $statusLabels = [
        'placed' => 'Order Received',
        'preparing' => 'Cooking in Kitchen',
        'ready' => 'Ready to Serve',
        'completed' => 'Served & Settled',
        'cancelled' => 'Cancelled',
    ];

    $formattedItems = [];
    foreach ($items as $item) {
        $addons = !empty($item['addons_json']) ? json_decode((string)$item['addons_json'], true) : [];
        $formattedItems[] = [
            'name' => (string)$item['item_name'],
            'variant' => (string)($item['variant_name'] ?? ''),
            'addons' => $addons,
            'quantity' => (int)$item['quantity'],
            'unit_price_inr' => Money::toRupees((int)$item['unit_price_paise']),
            'line_total_inr' => Money::toRupees((int)$item['total_price_paise']),
            'formatted_line_total' => Money::format((int)$item['total_price_paise']),
            'notes' => (string)($item['item_note'] ?? ''),
        ];
    }

    Response::json(true, [
        'order_id' => $orderId,
        'order_number' => (string)$order['order_number'],
        'status' => (string)$order['status'],
        'status_label' => $statusLabels[$order['status']] ?? ucfirst((string)$order['status']),
        'step_index' => $statusMap[$order['status']] ?? 1,
        'payment_status' => (string)$order['payment_status'],
        'payment_method' => (string)$order['payment_method'],
        'table_number' => (string)($table['table_number'] ?? 'Counter'),
        'floor' => (string)($table['floor'] ?? 'Ground Floor'),
        'created_at' => (string)$order['created_at'],
        'created_time' => date('h:i A', strtotime((string)$order['created_at'])),
        'financials' => [
            'subtotal_paise' => (int)$order['subtotal_paise'],
            'subtotal_inr' => Money::toRupees((int)$order['subtotal_paise']),
            'discount_paise' => (int)$order['discount_paise'],
            'discount_inr' => Money::toRupees((int)$order['discount_paise']),
            'tax_paise' => (int)$order['tax_paise'],
            'tax_inr' => Money::toRupees((int)$order['tax_paise']),
            'total_paise' => (int)$order['total_paise'],
            'total_inr' => Money::toRupees((int)$order['total_paise']),
            'formatted_total' => Money::format((int)$order['total_paise']),
        ],
        'items' => $formattedItems,
    ]);
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to fetch order status: ' . $e->getMessage(), 500);
}
