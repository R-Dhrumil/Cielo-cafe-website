<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Thermal Kitchen Order Ticket (KOT)
 * Formatted for 58mm & 80mm thermal receipt printers.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;

$orderId = (int)($_GET['order_id'] ?? 0);
$orderNumber = trim((string)($_GET['order'] ?? ''));

$order = null;
if ($orderId > 0) {
    $order = Order::find($orderId);
} elseif ($orderNumber !== '') {
    $order = Order::findBy('order_number', $orderNumber);
}

if (!$order) {
    die("Order not found for KOT print.");
}

$table = Table::find((int)$order['table_id']);
$items = OrderItem::where('`order_id` = :oid', ['oid' => (int)$order['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>KOT - <?= e($order['order_number']) ?></title>
  <style>
    @page {
      margin: 0;
      size: 80mm auto;
    }
    body {
      margin: 0;
      padding: 10px 8px;
      width: 72mm;
      font-family: 'Courier New', Courier, monospace;
      color: #000000;
      background: #FFFFFF;
      font-size: 13px;
      line-height: 1.35;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .title {
      font-size: 16px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .table-banner {
      font-size: 18px;
      font-weight: 900;
      border: 2px dashed #000;
      padding: 6px;
      margin: 8px 0;
      text-align: center;
    }
    .divider {
      border-top: 1px dashed #000;
      margin: 6px 0;
    }
    .item-row {
      margin: 8px 0;
    }
    .item-header {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      font-weight: bold;
    }
    .item-custom {
      font-size: 12px;
      padding-left: 14px;
    }
    .item-note {
      font-size: 12px;
      font-style: italic;
      padding-left: 14px;
    }
    .no-print {
      margin-bottom: 15px;
      text-align: center;
    }
    .btn-print {
      background: #000;
      color: #FFF;
      border: none;
      padding: 8px 16px;
      font-weight: bold;
      cursor: pointer;
      border-radius: 4px;
    }
    @media print {
      .no-print { display: none !important; }
      body { width: 100%; }
    }
  </style>
</head>
<body>

  <div class="no-print">
    <button class="btn-print" onclick="window.print()">Print KOT Ticket</button>
  </div>

  <div class="text-center">
    <div class="title">CIELO KITCHEN TICKET</div>
    <div>================================</div>
  </div>

  <div class="table-banner">
    TABLE: <?= e($table['table_number'] ?? 'Counter') ?>
    <div style="font-size: 12px; font-weight: normal;">Dine In</div>
  </div>

  <div>
    <div><strong>Order #:</strong> <?= e($order['order_number']) ?></div>
    <div><strong>Time:</strong> <?= date('d-M-Y h:i A', strtotime($order['created_at'])) ?></div>
    <?php if (!empty($order['kitchen_notes'])): ?>
      <div style="margin-top: 4px; border: 1px solid #000; padding: 4px;">
        <strong>NOTE:</strong> <?= e($order['kitchen_notes']) ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="divider"></div>

  <!-- Items -->
  <?php foreach ($items as $it): ?>
    <?php 
      $addons = !empty($it['addons_json']) ? json_decode((string)$it['addons_json'], true) : [];
    ?>
    <div class="item-row">
      <div class="item-header">
        <span>[ <?= (int)$it['quantity'] ?> ] <?= e($it['item_name']) ?></span>
      </div>
      <?php if (!empty($addons)): ?>
        <?php foreach ($addons as $ad): ?>
          <div class="item-custom">+ <?= e($ad['name']) ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if (!empty($it['item_note'])): ?>
        <div class="item-note">>> "<?= e($it['item_note']) ?>"</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="divider"></div>
  <div class="text-center" style="font-size: 11px; margin-top: 8px;">
    *** END OF KOT ***
  </div>

  <script>
    // Automatically open print dialog if opened in popup
    window.addEventListener('load', () => {
      if (window.location.search.includes('autoprint=1')) {
        window.print();
      }
    });
  </script>

</body>
</html>
