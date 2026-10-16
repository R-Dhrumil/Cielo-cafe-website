<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Luxury POS Command Center with Left Sidebar Navigation
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\SettingsService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\PointsLedger;
use App\Core\Security;
use App\Core\Money;

// Guard role: Owner & Admin only
$staff = AuthService::requireRole(['owner', 'admin'], '/admin/login.php');

$msg = '';
$err = '';

// Handle Actions (Settle Bill, Status Change, Cancel)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $orderId = (int)($_POST['order_id'] ?? 0);
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!Security::verifyCsrfToken($csrf)) {
        $err = 'Security session expired. Please refresh and try again.';
    } else {
        $order = Order::find($orderId);
        if (!$order) {
            $err = 'Selected order not found.';
        } else {
            if ($action === 'settle') {
                $payMethod = in_array($_POST['payment_method'] ?? '', ['counter_cash', 'counter_upi'], true)
                    ? $_POST['payment_method']
                    : 'counter_cash';

                Order::updateById($orderId, [
                    'payment_status' => 'paid',
                    'payment_method' => $payMethod,
                    'status' => 'completed',
                ]);

                // Award Loyalty Points if order has a linked user and wasn't previously settled
                if (!empty($order['user_id']) && $order['payment_status'] !== 'paid') {
                    $loyaltyRules = SettingsService::getLoyaltyRules();
                    $rate = (int)($loyaltyRules['earn_points_per_100_inr'] ?? 10);
                    $subtotalInr = Money::toRupees((int)$order['subtotal_paise']);
                    $earnedPoints = (int)floor(($subtotalInr / 100) * $rate);

                    if ($earnedPoints > 0) {
                        PointsLedger::credit(
                            (int)$order['user_id'],
                            $earnedPoints,
                            "Earned on Order {$order['order_number']}",
                            $orderId
                        );
                    }
                }

                AuditLog::log($staff['id'], "order_settled_{$payMethod}", 'orders', $orderId);
                $msg = "Order {$order['order_number']} successfully marked as PAID & COMPLETED!";
            } elseif ($action === 'cancel') {
                $reason = trim((string)($_POST['reason'] ?? 'Cancelled by Admin'));
                Order::updateById($orderId, [
                    'status' => 'cancelled',
                    'cancelled_reason' => $reason
                ]);
                AuditLog::log($staff['id'], 'order_cancelled', 'orders', $orderId, ['reason' => $reason]);
                $msg = "Order {$order['order_number']} has been cancelled.";
            } elseif ($action === 'status') {
                $newStatus = trim((string)($_POST['new_status'] ?? ''));
                if (in_array($newStatus, ['placed', 'preparing', 'ready', 'completed'], true)) {
                    Order::updateById($orderId, ['status' => $newStatus]);
                    AuditLog::log($staff['id'], "order_status_to_{$newStatus}", 'orders', $orderId);
                    $msg = "Order {$order['order_number']} status changed to " . strtoupper($newStatus);
                }
            }
        }
    }
}

// Fetch Orders with Filter
$statusFilter = trim((string)($_GET['filter'] ?? 'active'));
$queryWhere = '1=1';
$params = [];

if ($statusFilter === 'active') {
    $queryWhere = '`status` IN ("placed", "preparing", "ready")';
} elseif ($statusFilter === 'completed') {
    $queryWhere = '`status` = "completed"';
} elseif ($statusFilter === 'cancelled') {
    $queryWhere = '`status` = "cancelled"';
}

$orders = Order::where($queryWhere, $params, '`created_at` DESC', 100);

// Compute Real-Time Dashboard KPI Metrics (Today)
$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');

$todayPaidOrders = Order::where('`created_at` BETWEEN :ts AND :te AND `payment_status` = "paid"', ['ts' => $todayStart, 'te' => $todayEnd]);
$todayRevenuePaise = 0;
foreach ($todayPaidOrders as $o) {
    $todayRevenuePaise += (int)$o['total_paise'];
}

$activeOrdersAll = Order::where('`status` IN ("placed", "preparing", "ready")');
$activeCount = count($activeOrdersAll);

$completedTodayOrders = Order::where('`created_at` BETWEEN :ts AND :te AND `status` = "completed"', ['ts' => $todayStart, 'te' => $todayEnd]);
$completedTodayCount = count($completedTodayOrders);

// Points issued today
$todayPointsSum = db()->fetchColumn(
    "SELECT COALESCE(SUM(points), 0) FROM `points_ledger` WHERE `type` = 'credit' AND `created_at` BETWEEN :ts AND :te",
    ['ts' => $todayStart, 'te' => $todayEnd]
);

$csrfToken = Security::generateCsrfToken();
$activeNav = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Orders & POS Queue | Cielo Cafe Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --bg-surface: #FFFFFF;
      --text-espresso: #1A1412;
      --text-muted: #6E6259;
      --accent-terracotta: #C96A52;
      --accent-terracotta-soft: rgba(201, 106, 82, 0.1);
      --accent-sky: #6B9AC4;
      --accent-sky-soft: rgba(107, 154, 196, 0.12);
      --accent-sky-deep: #4A7A9F;
      --border-warm: rgba(26, 20, 18, 0.08);
      --shadow-card: 0 4px 18px rgba(26, 20, 18, 0.04);
      --shadow-card-hover: 0 10px 30px rgba(26, 20, 18, 0.08);
      --radius-sm: 8px;
      --radius-md: 14px;
      --radius-lg: 20px;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      background: #F4F1EA;
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
    }

    /* Left Sidebar Styles */
    .admin-sidebar {
      width: 260px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: #1A1412;
      color: #F7F4EE;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid rgba(247, 244, 238, 0.08);
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
    }

    .sidebar-header {
      padding: 1.75rem 1.5rem 1.25rem;
      border-bottom: 1px solid rgba(247, 244, 238, 0.06);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      text-decoration: none;
      color: #F7F4EE;
    }

    .sidebar-logo-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(201, 106, 82, 0.15);
      border: 1px solid rgba(201, 106, 82, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
    }

    .sidebar-title {
      font-family: 'Fraunces', serif;
      font-size: 1.45rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      display: block;
      line-height: 1;
    }

    .sidebar-sub {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-sky);
      font-weight: 600;
      margin-top: 0.25rem;
      display: block;
    }

    .sidebar-nav-container {
      padding: 1.5rem 1rem;
      flex: 1;
      overflow-y: auto;
    }

    .sidebar-section-label {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      color: rgba(247, 244, 238, 0.4);
      font-weight: 700;
      padding: 0 0.75rem;
      margin-bottom: 0.6rem;
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .sidebar-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 0.85rem;
      border-radius: 12px;
      text-decoration: none;
      color: rgba(247, 244, 238, 0.75);
      font-size: 0.88rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .sidebar-link:hover {
      color: #F7F4EE;
      background: rgba(247, 244, 238, 0.08);
    }

    .sidebar-link.active {
      color: #FFFFFF;
      background: var(--accent-terracotta);
      box-shadow: 0 4px 15px rgba(201, 106, 82, 0.35);
    }

    .sidebar-link-left {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .sidebar-badge-pulse {
      background: var(--accent-sky-deep);
      color: #FFFFFF;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.15rem 0.55rem;
      border-radius: 999px;
    }

    .sidebar-link.active .sidebar-badge-pulse {
      background: #FFFFFF;
      color: var(--accent-terracotta);
    }

    .sidebar-link-external {
      font-size: 0.85rem;
      opacity: 0.5;
    }

    .sidebar-footer {
      padding: 1.25rem;
      border-top: 1px solid rgba(247, 244, 238, 0.06);
      background: rgba(0, 0, 0, 0.25);
    }

    .staff-card {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .staff-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--accent-sky-deep);
      color: #FFFFFF;
      font-weight: 700;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .staff-info {
      display: flex;
      flex-direction: column;
    }

    .staff-name {
      font-size: 0.86rem;
      font-weight: 700;
      color: #F7F4EE;
      line-height: 1.2;
    }

    .staff-role-badge {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.72rem;
      color: rgba(247, 244, 238, 0.6);
      margin-top: 2px;
    }

    .role-indicator {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #2D6A4F;
      box-shadow: 0 0 0 2px rgba(45, 106, 79, 0.4);
    }

    .btn-sidebar-signout {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      width: 100%;
      padding: 0.65rem;
      border-radius: 10px;
      background: rgba(201, 106, 82, 0.15);
      border: 1px solid rgba(201, 106, 82, 0.3);
      color: #EFA694;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .btn-sidebar-signout:hover {
      background: var(--accent-terracotta);
      color: #FFFFFF;
    }

    /* Right Content Area */
    .admin-main-wrapper {
      margin-left: 260px;
      flex: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      width: calc(100% - 260px);
    }

    /* Top Action Bar */
    .admin-topbar {
      background: #FFFFFF;
      border-bottom: 1px solid var(--border-warm);
      padding: 1.15rem 2.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 900;
      box-shadow: 0 2px 10px rgba(26, 20, 18, 0.02);
    }

    .topbar-title-wrap {
      display: flex;
      flex-direction: column;
    }

    .topbar-heading {
      font-family: 'Fraunces', serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      line-height: 1.1;
    }

    .topbar-subtext {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .topbar-right-tools {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .search-box {
      position: relative;
      display: flex;
      align-items: center;
    }

    .search-input {
      padding: 0.6rem 1rem 0.6rem 2.25rem;
      border: 1px solid var(--border-warm);
      border-radius: 999px;
      font-size: 0.85rem;
      font-family: inherit;
      outline: none;
      width: 250px;
      background: #FAFAFA;
      transition: all 0.2s ease;
    }

    .search-input:focus {
      background: #FFFFFF;
      border-color: var(--accent-sky);
      width: 300px;
      box-shadow: 0 0 0 3px rgba(107, 154, 196, 0.12);
    }

    .search-icon {
      position: absolute;
      left: 12px;
      color: var(--text-muted);
      pointer-events: none;
    }

    .live-clock-pill {
      font-family: monospace;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-espresso);
      background: var(--bg-cream);
      padding: 0.5rem 0.95rem;
      border-radius: 999px;
      border: 1px solid var(--border-warm);
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }

    .btn-refresh-pill {
      background: #FAFAFA;
      border: 1px solid var(--border-warm);
      padding: 0.55rem 0.95rem;
      border-radius: 999px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-espresso);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.4rem;
      transition: all 0.2s ease;
      font-family: inherit;
    }

    .btn-refresh-pill:hover {
      background: var(--bg-cream);
      border-color: rgba(26, 20, 18, 0.2);
    }

    /* Content Area */
    .dashboard-content {
      padding: 2.25rem 2.5rem 4rem;
      flex: 1;
    }

    /* Toast Alert */
    .toast-alert {
      background: #FFFFFF;
      border-left: 4px solid var(--accent-sky);
      border-radius: 12px;
      padding: 1rem 1.5rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--shadow-card);
      font-size: 0.92rem;
      font-weight: 500;
    }

    .toast-alert.error {
      border-left-color: var(--accent-terracotta);
      color: var(--accent-terracotta);
    }

    /* KPI Summary Row */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      margin-bottom: 2rem;
    }

    .kpi-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-card-hover);
    }

    .kpi-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.85rem;
    }

    .kpi-label {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-muted);
    }

    .kpi-icon-wrap {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: var(--bg-cream);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-sky-deep);
    }

    .kpi-value {
      font-family: 'Fraunces', serif;
      font-size: 1.85rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      letter-spacing: -0.01em;
    }

    .kpi-sub {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-top: 0.35rem;
    }

    /* Filter Bar */
    .filter-section {
      margin-bottom: 1.75rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .filter-pills {
      display: flex;
      background: #FFFFFF;
      padding: 0.3rem;
      border-radius: 999px;
      border: 1px solid var(--border-warm);
      gap: 0.25rem;
      box-shadow: var(--shadow-card);
    }

    .filter-pill {
      padding: 0.5rem 1.15rem;
      border-radius: 999px;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-muted);
      transition: all 0.2s ease;
    }

    .filter-pill:hover {
      color: var(--text-espresso);
    }

    .filter-pill.active {
      background: var(--text-espresso);
      color: #FFFFFF;
      box-shadow: 0 2px 8px rgba(26, 20, 18, 0.2);
    }

    /* Orders Grid */
    .orders-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(460px, 1fr));
      gap: 1.5rem;
    }

    .order-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.2s ease;
    }

    .order-card:hover {
      box-shadow: var(--shadow-card-hover);
      border-color: rgba(107, 154, 196, 0.4);
    }

    /* Order Card Header */
    .order-card-header {
      padding: 1.25rem 1.5rem;
      background: #FAFAFA;
      border-bottom: 1px solid var(--border-warm);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
    }

    .order-header-left {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .table-badge {
      background: var(--text-espresso);
      color: #F7F4EE;
      font-family: 'Fraunces', serif;
      font-size: 1.15rem;
      font-weight: 700;
      padding: 0.4rem 0.85rem;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      line-height: 1;
    }

    .table-sub {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.55rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: rgba(247, 244, 238, 0.65);
      margin-top: 2px;
    }

    .order-identity {
      display: flex;
      flex-direction: column;
    }

    .order-num-text {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text-espresso);
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .order-time-text {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: 2px;
    }

    /* Status Badges */
    .status-badge {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 0.35rem 0.75rem;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .status-placed {
      background: rgba(201, 106, 82, 0.12);
      color: var(--accent-terracotta);
      border: 1px solid rgba(201, 106, 82, 0.3);
      animation: pulseAlert 2s infinite;
    }

    @keyframes pulseAlert {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.75; }
    }

    .status-preparing {
      background: rgba(107, 154, 196, 0.14);
      color: var(--accent-sky-deep);
      border: 1px solid rgba(107, 154, 196, 0.3);
    }

    .status-ready {
      background: rgba(206, 152, 60, 0.14);
      color: #9E6B08;
      border: 1px solid rgba(206, 152, 60, 0.3);
    }

    .status-completed {
      background: rgba(45, 106, 79, 0.12);
      color: #2D6A4F;
      border: 1px solid rgba(45, 106, 79, 0.25);
    }

    .status-cancelled {
      background: #F3F3F3;
      color: #777777;
    }

    /* Card Body */
    .order-card-body {
      padding: 1.25rem 1.5rem;
      flex: 1;
    }

    .patron-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 0.85rem;
      margin-bottom: 0.85rem;
      border-bottom: 1px dashed var(--border-warm);
      font-size: 0.82rem;
    }

    .patron-name {
      font-weight: 600;
      color: var(--text-espresso);
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .patron-phone {
      color: var(--accent-sky-deep);
      text-decoration: none;
      font-weight: 500;
    }

    /* Items List */
    .items-container {
      display: flex;
      flex-direction: column;
      gap: 0.65rem;
      margin-bottom: 1rem;
    }

    .item-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      font-size: 0.88rem;
    }

    .item-left {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
    }

    .item-qty-tag {
      background: var(--bg-cream);
      border: 1px solid var(--border-warm);
      color: var(--text-espresso);
      font-weight: 700;
      font-size: 0.75rem;
      padding: 0.1rem 0.4rem;
      border-radius: 6px;
      margin-top: 1px;
    }

    .item-title-wrap {
      display: flex;
      flex-direction: column;
    }

    .item-title {
      font-weight: 600;
      color: var(--text-espresso);
    }

    .item-addon-tag {
      font-size: 0.72rem;
      color: var(--accent-terracotta);
      font-weight: 500;
      margin-top: 2px;
    }

    .item-note-text {
      font-size: 0.72rem;
      color: var(--text-muted);
      font-style: italic;
      margin-top: 1px;
    }

    .item-price {
      font-weight: 600;
      color: var(--text-espresso);
      font-size: 0.88rem;
    }

    /* Kitchen Special Instructions */
    .kitchen-callout {
      background: var(--accent-terracotta-soft);
      border-left: 3px solid var(--accent-terracotta);
      border-radius: 6px;
      padding: 0.55rem 0.85rem;
      margin-top: 0.75rem;
      font-size: 0.78rem;
      color: #963D27;
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }

    /* Card Footer: Financials & Action Buttons */
    .order-card-footer {
      padding: 1.25rem 1.5rem;
      background: #FAFAFA;
      border-top: 1px solid var(--border-warm);
    }

    .financials-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .payment-tag {
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.25rem 0.65rem;
      border-radius: 6px;
      text-transform: uppercase;
    }

    .payment-unpaid {
      background: rgba(201, 106, 82, 0.15);
      color: var(--accent-terracotta);
    }

    .payment-paid {
      background: rgba(45, 106, 79, 0.15);
      color: #2D6A4F;
    }

    .grand-total-wrap {
      text-align: right;
    }

    .total-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-muted);
    }

    .total-amount {
      font-family: 'Fraunces', serif;
      font-size: 1.45rem;
      font-weight: 700;
      color: var(--text-espresso);
      line-height: 1.1;
    }

    /* Action Buttons */
    .actions-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .btn-action {
      padding: 0.65rem 0.85rem;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      border: 1px solid transparent;
      transition: all 0.15s ease;
      text-decoration: none;
    }

    .btn-settle-cash {
      background: var(--text-espresso);
      color: #FFFFFF;
    }

    .btn-settle-cash:hover {
      background: #2C221F;
      transform: translateY(-1px);
    }

    .btn-settle-upi {
      background: #2D6A4F;
      color: #FFFFFF;
    }

    .btn-settle-upi:hover {
      background: #23543E;
      transform: translateY(-1px);
    }

    .secondary-actions {
      display: flex;
      gap: 0.5rem;
    }

    .btn-print-action {
      flex: 1;
      background: #FFFFFF;
      border: 1px solid var(--border-warm);
      color: var(--text-espresso);
      padding: 0.5rem;
      font-size: 0.75rem;
      font-weight: 600;
      border-radius: 8px;
      text-decoration: none;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      transition: all 0.15s ease;
    }

    .btn-print-action:hover {
      background: var(--bg-cream);
      border-color: rgba(26, 20, 18, 0.2);
    }

    .empty-state {
      grid-column: 1 / -1;
      background: #FFFFFF;
      border-radius: var(--radius-lg);
      padding: 4rem 2rem;
      text-align: center;
      border: 1px solid var(--border-warm);
    }

    .empty-icon {
      width: 56px;
      height: 56px;
      margin: 0 auto 1rem;
      border-radius: 50%;
      background: var(--bg-cream);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
    }

    @media (max-width: 1200px) {
      .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      .orders-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 900px) {
      .admin-sidebar {
        transform: translateX(-100%);
      }
      .admin-sidebar.open {
        transform: translateX(0);
      }
      .admin-main-wrapper {
        margin-left: 0;
        width: 100%;
      }
    }
  </style>
</head>
<body>

  <!-- Left Sidebar Navigation Component -->
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Right Content Wrapper -->
  <div class="admin-main-wrapper">
    
    <!-- Top Action Bar -->
    <header class="admin-topbar">
      <div class="topbar-title-wrap">
        <h1 class="topbar-heading">POS Live Orders</h1>
        <span class="topbar-subtext">Table Ordering Queue & Instant Bill Settlement</span>
      </div>

      <div class="topbar-right-tools">
        <div class="search-box">
          <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="orderSearch" class="search-input" placeholder="Search order #, table, phone..." oninput="filterCards()">
        </div>

        <div class="live-clock-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          <span id="liveClock"><?= date('H:i:s') ?> IST</span>
        </div>

        <button class="btn-refresh-pill" onclick="window.location.reload()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
          <span>Refresh</span>
        </button>
      </div>
    </header>

    <!-- Content Body -->
    <main class="dashboard-content">

      <!-- Action Message Banner -->
      <?php if ($msg !== ''): ?>
        <div class="toast-alert">
          <div style="display:flex; align-items:center; gap:0.6rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D6A4F" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= e($msg) ?></span>
          </div>
          <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--text-muted);">&times;</button>
        </div>
      <?php elseif ($err !== ''): ?>
        <div class="toast-alert error">
          <span><?= e($err) ?></span>
          <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--accent-terracotta);">&times;</button>
        </div>
      <?php endif; ?>

      <!-- KPI Summary Row -->
      <section class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-top">
            <span class="kpi-label">Today's Revenue</span>
            <div class="kpi-icon-wrap">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
          </div>
          <div>
            <h2 class="kpi-value"><?= Money::format($todayRevenuePaise) ?></h2>
            <div class="kpi-sub"><?= count($todayPaidOrders) ?> bills settled today</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-top">
            <span class="kpi-label">Active Kitchen Queue</span>
            <div class="kpi-icon-wrap" style="color:var(--accent-terracotta);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
          </div>
          <div>
            <h2 class="kpi-value" style="color:var(--accent-terracotta);"><?= $activeCount ?></h2>
            <div class="kpi-sub">Placed / In Prep / Ready</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-top">
            <span class="kpi-label">Completed Tables</span>
            <div class="kpi-icon-wrap" style="color:#2D6A4F;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
          </div>
          <div>
            <h2 class="kpi-value" style="color:#2D6A4F;"><?= $completedTodayCount ?></h2>
            <div class="kpi-sub">Satisfied diners served today</div>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-top">
            <span class="kpi-label">Loyalty Points Issued</span>
            <div class="kpi-icon-wrap" style="color:#C08A3E;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
          </div>
          <div>
            <h2 class="kpi-value" style="color:#C08A3E;">+<?= number_format((int)$todayPointsSum) ?></h2>
            <div class="kpi-sub">Credited directly to customer ledgers</div>
          </div>
        </div>
      </section>

      <!-- Filter Tabs -->
      <section class="filter-section">
        <div class="filter-pills">
          <a href="?filter=active" class="filter-pill <?= $statusFilter === 'active' ? 'active' : '' ?>">
            Active Queue (<?= $activeCount ?>)
          </a>
          <a href="?filter=all" class="filter-pill <?= $statusFilter === 'all' ? 'active' : '' ?>">
            All Orders
          </a>
          <a href="?filter=completed" class="filter-pill <?= $statusFilter === 'completed' ? 'active' : '' ?>">
            Completed
          </a>
          <a href="?filter=cancelled" class="filter-pill <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
            Cancelled
          </a>
        </div>
      </section>

      <!-- Live Orders Grid -->
      <section class="orders-grid" id="ordersGrid">
        <?php if (empty($orders)): ?>
          <div class="empty-state">
            <div class="empty-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/></svg>
            </div>
            <h3 style="font-family:'Fraunces', serif; font-size:1.35rem; margin:0 0 0.5rem;">No Orders in this View</h3>
            <p style="color:var(--text-muted); font-size:0.88rem; margin:0;">
              <?= $statusFilter === 'active' ? 'Kitchen queue is all caught up!' : 'No orders matched this status filter.' ?>
            </p>
          </div>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <?php
              $table = Table::find((int)$order['table_id']);
              $user = !empty($order['user_id']) ? User::find((int)$order['user_id']) : null;
              $items = OrderItem::where('`order_id` = :oid', ['oid' => (int)$order['id']]);
              $isPaid = ($order['payment_status'] === 'paid');
              $statusClass = 'status-' . htmlspecialchars((string)$order['status']);
              $orderAgeMin = max(0, (int)floor((time() - strtotime((string)$order['created_at'])) / 60));
            ?>
            <div class="order-card" 
                 data-order-num="<?= e($order['order_number']) ?>" 
                 data-table="<?= e($table['table_number'] ?? '00') ?>" 
                 data-phone="<?= e($user['phone'] ?? '') ?>">
              
              <!-- Card Header -->
              <div class="order-card-header">
                <div class="order-header-left">
                  <div class="table-badge">
                    <span><?= e($table['table_number'] ?? 'T-??') ?></span>
                    <span class="table-sub"><?= e($table['floor'] ?? 'Floor') ?></span>
                  </div>
                  <div class="order-identity">
                    <span class="order-num-text">
                      #<?= e($order['order_number']) ?>
                    </span>
                    <span class="order-time-text">
                      <?= date('h:i A', strtotime((string)$order['created_at'])) ?> · <strong><?= $orderAgeMin ?>m ago</strong>
                    </span>
                  </div>
                </div>

                <span class="status-badge <?= $statusClass ?>">
                  <?php if ($order['status'] === 'placed'): ?>
                    ● New Order
                  <?php elseif ($order['status'] === 'preparing'): ?>
                    🍳 Preparing
                  <?php elseif ($order['status'] === 'ready'): ?>
                    🔔 Ready to Serve
                  <?php elseif ($order['status'] === 'completed'): ?>
                    ✔ Completed
                  <?php else: ?>
                    <?= ucfirst((string)$order['status']) ?>
                  <?php endif; ?>
                </span>
              </div>

              <!-- Card Body -->
              <div class="order-card-body">
                <div class="patron-row">
                  <div class="patron-name">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span><?= e($user['name'] ?? 'Guest Diner') ?></span>
                  </div>
                  <?php if (!empty($user['phone'])): ?>
                    <a href="https://wa.me/91<?= preg_replace('/[^0-9]/', '', $user['phone']) ?>" target="_blank" class="patron-phone" title="Chat on WhatsApp">
                      +91 <?= e($user['phone']) ?> ↗
                    </a>
                  <?php else: ?>
                    <span style="color:var(--text-muted); font-size:0.75rem;">Walk-in</span>
                  <?php endif; ?>
                </div>

                <!-- Items Breakdown -->
                <div class="items-container">
                  <?php foreach ($items as $it): ?>
                    <?php 
                      $addons = !empty($it['addons_json']) ? json_decode((string)$it['addons_json'], true) : [];
                    ?>
                    <div class="item-row">
                      <div class="item-left">
                        <span class="item-qty-tag"><?= (int)$it['quantity'] ?>x</span>
                        <div class="item-title-wrap">
                          <span class="item-title"><?= e($it['item_name']) ?></span>
                          <?php if (!empty($addons)): ?>
                            <div class="item-addon-tag">
                              + <?= implode(', ', array_map(fn($a) => e($a['name'] ?? ''), $addons)) ?>
                            </div>
                          <?php endif; ?>
                          <?php if (!empty($it['item_note'])): ?>
                            <div class="item-note-text"><?= e($it['item_note']) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      <span class="item-price"><?= Money::format((int)$it['total_price_paise']) ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>

                <!-- Kitchen Special Instructions -->
                <?php if (!empty($order['kitchen_notes'])): ?>
                  <div class="kitchen-callout">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><strong>Chef Note:</strong> <?= e($order['kitchen_notes']) ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Card Footer -->
              <div class="order-card-footer">
                <div class="financials-row">
                  <span class="payment-tag <?= $isPaid ? 'payment-paid' : 'payment-unpaid' ?>">
                    <?= $isPaid ? ('PAID · ' . strtoupper(str_replace('_', ' ', (string)$order['payment_method']))) : 'UNSETTLED' ?>
                  </span>
                  <div class="grand-total-wrap">
                    <div class="total-label">Total Amount</div>
                    <div class="total-amount"><?= Money::format((int)$order['total_paise']) ?></div>
                  </div>
                </div>

                <!-- Quick Settlement Buttons (If not yet paid) -->
                <?php if (!$isPaid && $order['status'] !== 'cancelled'): ?>
                  <div class="actions-grid">
                    <form method="POST" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                      <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                      <input type="hidden" name="action" value="settle">
                      <input type="hidden" name="payment_method" value="counter_cash">
                      <button type="submit" class="btn-action btn-settle-cash" onclick="return confirm('Settle Order #<?= e($order['order_number']) ?> with CASH payment?')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                        <span>Settle Cash</span>
                      </button>
                    </form>

                    <form method="POST" style="margin:0;">
                      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                      <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                      <input type="hidden" name="action" value="settle">
                      <input type="hidden" name="payment_method" value="counter_upi">
                      <button type="submit" class="btn-action btn-settle-upi" onclick="return confirm('Settle Order #<?= e($order['order_number']) ?> with UPI payment?')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="3 10 12 15 21 10"/></svg>
                        <span>Settle UPI</span>
                      </button>
                    </form>
                  </div>
                <?php endif; ?>

                <!-- Print & Status Transitions -->
                <div class="secondary-actions">
                  <a href="/admin/print_bill.php?order_id=<?= (int)$order['id'] ?>&autoprint=1" target="_blank" class="btn-print-action" title="Print Customer Tax Bill">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    <span>Bill Receipt</span>
                  </a>

                  <a href="/kitchen/print_kot.php?order_id=<?= (int)$order['id'] ?>&autoprint=1" target="_blank" class="btn-print-action" title="Print Kitchen Order Ticket">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>KOT</span>
                  </a>

                  <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                    <form method="POST" style="margin:0; flex:1;">
                      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                      <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                      <input type="hidden" name="action" value="status">
                      <?php if ($order['status'] === 'placed'): ?>
                        <input type="hidden" name="new_status" value="preparing">
                        <button type="submit" class="btn-print-action" style="width:100%; color:var(--accent-sky-deep);">
                          Start Prep ➔
                        </button>
                      <?php elseif ($order['status'] === 'preparing'): ?>
                        <input type="hidden" name="new_status" value="ready">
                        <button type="submit" class="btn-print-action" style="width:100%; color:#9E6B08;">
                          Mark Ready 🔔
                        </button>
                      <?php endif; ?>
                    </form>
                  <?php endif; ?>
                </div>

              </div>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

    </main>

  </div>

  <script>
    // Live Digital Clock (IST)
    setInterval(() => {
      const now = new Date();
      const timeStr = now.toLocaleTimeString('en-US', { hour12: false });
      const clockEl = document.getElementById('liveClock');
      if (clockEl) {
        clockEl.textContent = timeStr + ' IST';
      }
    }, 1000);

    // Instant Filter / Search
    function filterCards() {
      const query = (document.getElementById('orderSearch').value || '').toLowerCase().trim();
      const cards = document.querySelectorAll('.order-card');
      
      cards.forEach(card => {
        const orderNum = (card.getAttribute('data-order-num') || '').toLowerCase();
        const table = (card.getAttribute('data-table') || '').toLowerCase();
        const phone = (card.getAttribute('data-phone') || '').toLowerCase();
        
        if (!query || orderNum.includes(query) || table.includes(query) || phone.includes(query)) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Auto-refresh every 15s to keep active kitchen queue sync
    let secondsLeft = 15;
    setInterval(() => {
      secondsLeft--;
      if (secondsLeft <= 0) {
        const searchVal = document.getElementById('orderSearch').value;
        if (!searchVal) {
          window.location.reload();
        }
        secondsLeft = 15;
      }
    }, 1000);
  </script>

</body>
</html>
