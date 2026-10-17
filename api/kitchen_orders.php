<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Kitchen Display System (KDS) API
 *
 * Endpoint:
 * - GET  /api/kitchen_orders.php (fetch active kitchen orders)
 * - POST /api/kitchen_orders.php (update order status: prepare, ready, complete)
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Response;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\AuditLog;
use App\Services\AuthService;
use App\Services\LoyaltyService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Status update action
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;

    $orderId = (int)($input['order_id'] ?? 0);
    $action = trim((string)($input['action'] ?? ''));

    if ($orderId <= 0) {
        Response::json(false, [], 'Missing order ID', 422);
        exit;
    }

    $order = Order::find($orderId);
    if (!$order) {
        Response::json(false, [], 'Order not found', 404);
        exit;
    }

    $allowedTransitions = [
        'prepare' => 'preparing',
        'ready' => 'ready',
        'complete' => 'completed',
        'cancel' => 'cancelled',
    ];

    if (!isset($allowedTransitions[$action])) {
        Response::json(false, [], 'Invalid kitchen action', 422);
        exit;
    }

    $newStatus = $allowedTransitions[$action];
    $updateData = ['status' => $newStatus];

    Order::updateById($orderId, $updateData);

    if ($newStatus === 'completed' && $order['payment_status'] === 'paid') {
        LoyaltyService::processOrderCompletion($orderId);
    } elseif ($newStatus === 'cancelled') {
        LoyaltyService::reverseOrderLoyalty($orderId, 'Cancelled via Kitchen KDS');
    }

    $staff = AuthService::user();
    AuditLog::log(
        $staff['id'] ?? null,
        "kds_status_change_{$newStatus}",
        'orders',
        $orderId,
        ['from' => $order['status'], 'to' => $newStatus]
    );

    Response::json(true, [
        'order_id' => $orderId,
        'old_status' => $order['status'],
        'new_status' => $newStatus,
    ], "Order marked as {$newStatus}");
    exit;
}

// GET: Fetch Active Kitchen Tickets
try {
    $activeOrders = Order::where(
        '`status` IN ("placed", "preparing", "ready")',
        [],
        'CASE `status` WHEN "placed" THEN 1 WHEN "preparing" THEN 2 WHEN "ready" THEN 3 END ASC, `created_at` ASC'
    );

    $tickets = [];
    $now = time();

    foreach ($activeOrders as $order) {
        $oid = (int)$order['id'];
        $table = Table::find((int)$order['table_id']);
        $items = OrderItem::where('`order_id` = :oid', ['oid' => $oid]);

        $createdTimestamp = strtotime((string)$order['created_at']);
        $elapsedMinutes = (int)floor(($now - $createdTimestamp) / 60);

        $lineItems = [];
        foreach ($items as $it) {
            $addons = !empty($it['addons_json']) ? json_decode((string)$it['addons_json'], true) : [];
            $lineItems[] = [
                'name' => (string)$it['item_name'],
                'quantity' => (int)$it['quantity'],
                'addons' => $addons,
                'note' => (string)($it['item_note'] ?? ''),
            ];
        }

        $tickets[] = [
            'id' => $oid,
            'order_number' => (string)$order['order_number'],
            'status' => (string)$order['status'],
            'table_number' => (string)($table['table_number'] ?? 'Counter'),
            'floor' => (string)($table['floor'] ?? 'Ground Floor'),
            'created_at' => (string)$order['created_at'],
            'created_time' => date('h:i A', $createdTimestamp),
            'elapsed_minutes' => $elapsedMinutes,
            'kitchen_notes' => (string)($order['kitchen_notes'] ?? ''),
            'items' => $lineItems,
        ];
    }

    Response::json(true, [
        'ticket_count' => count($tickets),
        'tickets' => $tickets,
    ]);
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to fetch kitchen tickets: ' . $e->getMessage(), 500);
}
