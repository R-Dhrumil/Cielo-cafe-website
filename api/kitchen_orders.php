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
        'recall' => 'preparing',
    ];

    if (!isset($allowedTransitions[$action])) {
        Response::json(false, [], 'Invalid kitchen action', 422);
        exit;
    }

    $newStatus = $allowedTransitions[$action];
    $updateData = ['status' => $newStatus];

    Order::updateById($orderId, $updateData);

    if ($newStatus === 'completed' && ($order['payment_status'] ?? '') === 'paid') {
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
        ['from' => $order['status'], 'to' => $newStatus, 'action' => $action]
    );

    Response::json(true, [
        'order_id' => $orderId,
        'old_status' => $order['status'],
        'new_status' => $newStatus,
    ], "Order marked as {$newStatus}");
    exit;
}

// GET: Fetch Active Kitchen Tickets & Recent Completed Orders
try {
    $activeOrders = Order::where(
        '`status` IN ("placed", "preparing", "ready")',
        [],
        'CASE `status` WHEN "placed" THEN 1 WHEN "preparing" THEN 2 WHEN "ready" THEN 3 END ASC, `created_at` ASC'
    );

    // Fetch recent completed orders for Recall drawer
    $completedOrders = Order::where(
        '`status` = "completed"',
        [],
        '`updated_at` DESC',
        10
    );

    $now = time();

    $formatTicket = function(array $order) use ($now): array {
        $oid = (int)$order['id'];
        $table = Table::find((int)($order['table_id'] ?? 0));
        $items = OrderItem::where('`order_id` = :oid', ['oid' => $oid]);

        $createdTimestamp = strtotime((string)$order['created_at']);
        $elapsedMinutes = (int)floor(($now - $createdTimestamp) / 60);

        $lineItems = [];
        $totalItemsCount = 0;
        foreach ($items as $it) {
            $addons = !empty($it['addons_json']) ? json_decode((string)$it['addons_json'], true) : [];
            $qty = (int)$it['quantity'];
            $totalItemsCount += $qty;
            $lineItems[] = [
                'id' => (int)$it['id'],
                'name' => (string)$it['item_name'],
                'quantity' => $qty,
                'addons' => $addons,
                'note' => (string)($it['item_note'] ?? ''),
            ];
        }

        $tableNum = (string)($table['table_number'] ?? 'Counter');
        $floorName = (string)($table['floor'] ?? '');
        if (empty($floorName)) {
            $lower = strtolower($tableNum);
            $floorName = (str_contains($lower, 'counter') || str_contains($lower, 'takeaway') || str_contains($lower, 'delivery'))
                ? 'Takeaway'
                : 'Dine-In';
        }

        return [
            'id' => $oid,
            'order_number' => (string)$order['order_number'],
            'status' => (string)$order['status'],
            'table_number' => $tableNum,
            'floor' => $floorName,
            'created_at' => (string)$order['created_at'],
            'created_ts' => $createdTimestamp,
            'created_time' => date('h:i A', $createdTimestamp),
            'elapsed_minutes' => $elapsedMinutes,
            'kitchen_notes' => (string)($order['kitchen_notes'] ?? ''),
            'payment_status' => (string)($order['payment_status'] ?? 'pending'),
            'total_quantity' => $totalItemsCount,
            'items' => $lineItems,
        ];
    };

    $tickets = [];
    $prepSummary = [];

    foreach ($activeOrders as $order) {
        $ticket = $formatTicket($order);
        $tickets[] = $ticket;

        // Collect prep summary
        foreach ($ticket['items'] as $item) {
            $name = $item['name'];
            if (!isset($prepSummary[$name])) {
                $prepSummary[$name] = [
                    'name' => $name,
                    'total_qty' => 0,
                    'ticket_count' => 0,
                ];
            }
            $prepSummary[$name]['total_qty'] += $item['quantity'];
            $prepSummary[$name]['ticket_count']++;
        }
    }

    $recentCompleted = [];
    foreach ($completedOrders as $order) {
        $recentCompleted[] = $formatTicket($order);
    }

    // Sort prep summary by highest quantity first
    usort($prepSummary, fn($a, $b) => $b['total_qty'] <=> $a['total_qty']);

    Response::json(true, [
        'ticket_count' => count($tickets),
        'tickets' => $tickets,
        'prep_summary' => array_values($prepSummary),
        'recent_completed' => $recentCompleted,
        'server_time' => $now,
    ]);
} catch (\Throwable $e) {
    Response::json(false, [], 'Failed to fetch kitchen tickets: ' . $e->getMessage(), 500);
}
