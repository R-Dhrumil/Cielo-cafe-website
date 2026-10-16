<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Thermal Customer Bill Receipt
 * Formatted for 58mm & 80mm thermal receipt roll printers.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use App\Core\Money;
use App\Services\SettingsService;
use App\Core\QrCodeGenerator;

$orderId = (int)($_GET['order_id'] ?? 0);
$orderNumber = trim((string)($_GET['order'] ?? ''));

$order = null;
if ($orderId > 0) {
    $order = Order::find($orderId);
} elseif ($orderNumber !== '') {
    $order = Order::findBy('order_number', $orderNumber);
}

if (!$order) {
    die("Order not found for bill print.");
}

$table = Table::find((int)$order['table_id']);
$items = OrderItem::where('`order_id` = :oid', ['oid' => (int)$order['id']]);
$user = !empty($order['user_id']) ? User::find((int)$order['user_id']) : null;

$cafeDetails = SettingsService::getCafeDetails();
$gstRates = SettingsService::getGstRates();

// Generate UPI QR code for bill payment
$upiId = 'cielocafebistro@icici';
$upiAmount = number_format(Money::toRupees((int)$order['total_paise']), 2, '.', '');
$upiUri = "upi://pay?pa={$upiId}&pn=" . urlencode($cafeDetails['name']) . "&am={$upiAmount}&cu=INR&tn=" . urlencode("Bill " . $order['order_number']);
$upiQrSvg = QrCodeGenerator::generateSvg($upiUri, 140, '#000000', '#FFFFFF', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bill Receipt - <?= e($order['order_number']) ?></title>
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
      font-size: 12px;
      line-height: 1.35;
    }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .brand-title {
      font-size: 16px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .brand-sub {
      font-size: 10px;
      line-height: 1.3;
      margin-top: 2px;
    }
    .divider {
      border-top: 1px dashed #000;
      margin: 6px 0;
    }
    .double-divider {
      border-top: 1px double #000;
      margin: 6px 0;
    }
    .meta-row {
      display: flex;
      justify-content: space-between;
      font-size: 11px;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin: 6px 0;
      font-size: 12px;
    }
    .items-table th {
      border-bottom: 1px dashed #000;
      padding-bottom: 4px;
      font-size: 11px;
    }
    .items-table td {
      padding: 3px 0;
      vertical-align: top;
    }
    .item-custom {
      font-size: 10px;
      color: #333;
      padding-left: 8px;
    }
    .total-banner {
      font-size: 15px;
      font-weight: 900;
      display: flex;
      justify-content: space-between;
      padding: 4px 0;
    }
    .upi-box {
      margin: 10px auto;
      text-align: center;
    }
    .upi-box svg {
      margin: 0 auto;
      display: block;
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
    <button class="btn-print" onclick="window.print()">Print Bill Receipt</button>
  </div>

  <div class="text-center">
    <div class="brand-title"><?= e($cafeDetails['name']) ?></div>
    <div class="brand-sub">
      <?= e($cafeDetails['address']) ?><br>
      Phone: <?= e($cafeDetails['phone']) ?><br>
      GSTIN: <?= e($gstRates['gstin']) ?>
    </div>
  </div>

  <div class="divider"></div>

  <div class="meta-row">
    <span><strong>Bill #:</strong> <?= e($order['order_number']) ?></span>
    <span><strong>Table:</strong> <?= e($table['table_number'] ?? 'Counter') ?></span>
  </div>
  <div class="meta-row">
    <span><strong>Date:</strong> <?= date('d-M-y h:i A', strtotime($order['created_at'])) ?></span>
    <span><?= e($table['floor'] ?? '') ?></span>
  </div>
  <?php if ($user): ?>
    <div class="meta-row">
      <span><strong>Guest:</strong> <?= e($user['name']) ?></span>
      <span><?= e($user['phone']) ?></span>
    </div>
  <?php endif; ?>

  <div class="divider"></div>

  <!-- Itemized Table -->
  <table class="items-table">
    <thead>
      <tr>
        <th align="left">ITEM</th>
        <th align="center" width="28">QTY</th>
        <th align="right" width="55">PRICE</th>
        <th align="right" width="60">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <?php 
          $addons = !empty($it['addons_json']) ? json_decode((string)$it['addons_json'], true) : [];
        ?>
        <tr>
          <td>
            <div class="bold"><?= e($it['item_name']) ?></div>
            <?php if (!empty($addons)): ?>
              <?php foreach ($addons as $ad): ?>
                <div class="item-custom">+ <?= e($ad['name']) ?></div>
              <?php endforeach; ?>
            <?php endif; ?>
          </td>
          <td align="center" class="bold"><?= (int)$it['quantity'] ?></td>
          <td align="right"><?= number_format((int)$it['unit_price_paise'] / 100, 2) ?></td>
          <td align="right" class="bold"><?= number_format((int)$it['total_price_paise'] / 100, 2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="divider"></div>

  <!-- Financial Breakdown -->
  <div class="meta-row">
    <span>Subtotal:</span>
    <span>₹ <?= number_format((int)$order['subtotal_paise'] / 100, 2) ?></span>
  </div>
  <?php if ((int)$order['discount_paise'] > 0): ?>
    <div class="meta-row" style="color: #000;">
      <span>Discount:</span>
      <span>- ₹ <?= number_format((int)$order['discount_paise'] / 100, 2) ?></span>
    </div>
  <?php endif; ?>
  <div class="meta-row">
    <span>CGST (<?= $gstRates['cgst'] ?>%):</span>
    <span>₹ <?= number_format((int)$order['tax_paise'] / 200, 2) ?></span>
  </div>
  <div class="meta-row">
    <span>SGST (<?= $gstRates['sgst'] ?>%):</span>
    <span>₹ <?= number_format((int)$order['tax_paise'] / 200, 2) ?></span>
  </div>

  <div class="double-divider"></div>

  <div class="total-banner">
    <span>GRAND TOTAL:</span>
    <span>₹ <?= number_format((int)$order['total_paise'] / 100, 2) ?></span>
  </div>

  <div class="double-divider"></div>

  <div class="meta-row">
    <span>Payment Mode:</span>
    <span class="bold"><?= strtoupper(str_replace('_', ' ', (string)$order['payment_method'])) ?></span>
  </div>
  <div class="meta-row">
    <span>Payment Status:</span>
    <span class="bold"><?= strtoupper((string)$order['payment_status']) ?></span>
  </div>

  <?php if ($order['payment_status'] !== 'paid'): ?>
    <div class="upi-box">
      <div style="font-size: 10px; font-weight: bold; margin-bottom: 4px;">SCAN & PAY VIA UPI</div>
      <?= $upiQrSvg ?>
      <div style="font-size: 10px; margin-top: 2px;">UPI ID: <?= e($upiId) ?></div>
    </div>
  <?php endif; ?>

  <div class="divider"></div>

  <div class="text-center" style="font-size: 11px; margin-top: 8px;">
    <strong>Thank You For Dining At Cielo!</strong><br>
    Handcrafted Italian Delights & Slow Brews<br>
    Follow us @cielocafebistro<br>
    ================================
  </div>

  <script>
    window.addEventListener('load', () => {
      if (window.location.search.includes('autoprint=1')) {
        window.print();
      }
    });
  </script>

</body>
</html>
