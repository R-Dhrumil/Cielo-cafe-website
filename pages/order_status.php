<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Live Order Tracking Page
 * Displays real-time 4-step kitchen progress, receipt breakdown, and counter payment directions.
 */

require_once dirname(__DIR__) . '/includes/config.php';

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Core\Money;

$orderNumber = trim((string)($_GET['order'] ?? ''));

$order = null;
if ($orderNumber !== '') {
    $order = Order::findBy('order_number', $orderNumber);
}

$pageTitle = "Order Tracking | Cielo Cafe and Restro";
$pageDesc = "Track your live kitchen order status at Cielo Cafe Vadodara.";
$pageCss = ['menu.css'];

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5.5rem; min-height: 85vh; background: var(--bg-cream, #F7F4EE);">
  <div class="container" style="max-width: 680px; margin: 0 auto; padding: 2rem 1rem;">

    <?php if (!$order): ?>
      <!-- Order Not Found Screen -->
      <div style="background: #FFFFFF; border-radius: 20px; padding: 3rem 2rem; text-align: center; border: 1px solid rgba(26,20,18,0.1); box-shadow: 0 10px 30px rgba(26,20,18,0.04);">
        <h2 style="font-family: 'Fraunces', serif; font-size: 1.5rem; color: var(--text-espresso, #1A1412); margin: 0 0 0.75rem;">
          Order Not Found
        </h2>
        <p style="color: var(--text-muted, #6E6259); margin: 0 0 1.5rem;">
          We could not locate this order. Please check the order link or scan your table QR code.
        </p>
        <a href="/menu.php" class="btn-primary" style="display: inline-block; padding: 0.85rem 1.75rem; border-radius: 999px; text-decoration: none; font-weight: 600;">
          Return to Menu
        </a>
      </div>
    <?php else: ?>
      <?php
        $table = Table::find((int)$order['table_id']);
        $items = OrderItem::where('`order_id` = :oid', ['oid' => (int)$order['id']]);
        $status = (string)$order['status'];
      ?>

      <!-- Order Tracking Card -->
      <div style="background: #FFFFFF; border-radius: 24px; padding: 2rem; border: 1px solid rgba(26,20,18,0.12); box-shadow: 0 12px 35px rgba(26,20,18,0.06);">
        
        <!-- Top Order Info -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid rgba(26,20,18,0.08); padding-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
          <div>
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent-sky, #6B9AC4); font-weight: 700;">
              LIVE ORDER STATUS
            </span>
            <h1 style="font-family: 'Fraunces', serif; font-size: 1.6rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin: 0.25rem 0 0;">
              <?= e($order['order_number']) ?>
            </h1>
            <span style="font-size: 0.8rem; color: var(--text-muted, #6E6259);">
              Placed at <?= date('h:i A', strtotime($order['created_at'])) ?> · <?= date('M d, Y', strtotime($order['created_at'])) ?>
            </span>
          </div>

          <div style="text-align: right;">
            <span style="display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(26,20,18,0.05); padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.82rem; font-weight: 600; color: var(--text-espresso, #1A1412);">
              Table <?= e($table['table_number'] ?? 'Counter') ?> · <?= e($table['floor'] ?? 'Ground Floor') ?>
            </span>
          </div>
        </div>

        <!-- 4-Step Visual Progress Stepper -->
        <div style="margin: 2rem 0; padding: 1.5rem 1rem; background: rgba(247,244,238,0.7); border-radius: 16px; border: 1px solid rgba(26,20,18,0.06);">
          <div id="statusTrackerGrid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; text-align: center; position: relative;">

            <!-- Step 1: Placed -->
            <div class="step-node" data-step="1">
              <div class="step-circle is-active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <div class="step-label">Received</div>
            </div>

            <!-- Step 2: Preparing -->
            <div class="step-node" data-step="2">
              <div class="step-circle <?= in_array($status, ['preparing', 'ready', 'completed']) ? 'is-active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c2 3 5 4 5 8a5 5 0 0 1-10 0c0-4 3-5 5-8Z"/><path d="M12 18v4"/><path d="M8 22h8"/></svg>
              </div>
              <div class="step-label">Kitchen Preparing</div>
            </div>

            <!-- Step 3: Ready -->
            <div class="step-node" data-step="3">
              <div class="step-circle <?= in_array($status, ['ready', 'completed']) ? 'is-active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              </div>
              <div class="step-label">Ready to Serve</div>
            </div>

            <!-- Step 4: Completed -->
            <div class="step-node" data-step="4">
              <div class="step-circle <?= ($status === 'completed') ? 'is-active' : '' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              </div>
              <div class="step-label">Completed</div>
            </div>
          </div>

          <div id="liveStatusBanner" style="text-align: center; margin-top: 1.5rem; font-size: 0.95rem; font-weight: 600; color: var(--text-espresso, #1A1412);">
            <span class="live-pulse-dot"></span>
            <span id="liveStatusText">
              <?php if ($status === 'placed'): ?>
                Kitchen received your order. Preparing fresh ingredients...
              <?php elseif ($status === 'preparing'): ?>
                Chef is cooking your dishes in the kitchen!
              <?php elseif ($status === 'ready'): ?>
                Your order is freshly plated and on its way to your table!
              <?php elseif ($status === 'completed'): ?>
                Order complete! Hope you enjoyed your dining experience.
              <?php else: ?>
                Status: <?= ucfirst($status) ?>
              <?php endif; ?>
            </span>
          </div>
        </div>

        <!-- Payment Instructions Box -->
        <div style="background: #FFFFFF; border: 1px solid rgba(26,20,18,0.1); border-radius: 16px; padding: 1.25rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
          <div>
            <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted, #6E6259); font-weight: 600;">Payment Status</div>
            <div style="font-weight: 700; font-size: 1rem; color: var(--text-espresso, #1A1412); margin-top: 0.2rem;">
              <?php if ($order['payment_status'] === 'paid'): ?>
                <span style="color: #2E7D32;">✔ Settled (Paid)</span>
              <?php else: ?>
                <span style="color: var(--accent-terracotta, #C96A52);">Pending · Pay at Counter (Cash/UPI)</span>
              <?php endif; ?>
            </div>
            <div style="font-size: 0.78rem; color: var(--text-muted, #6E6259); margin-top: 0.2rem;">
              Present order <strong><?= e($order['order_number']) ?></strong> at front counter.
            </div>
          </div>

          <div style="text-align: right;">
            <div style="font-size: 0.78rem; color: var(--text-muted, #6E6259);">Total Amount</div>
            <div style="font-family: 'Fraunces', serif; font-size: 1.35rem; font-weight: 700; color: var(--text-espresso, #1A1412);">
              <?= Money::format((int)$order['total_paise']) ?>
            </div>
          </div>
        </div>

        <!-- Itemized Receipt -->
        <div>
          <h3 style="font-family: 'Fraunces', serif; font-size: 1.15rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin: 0 0 1rem;">
            Order Items (<?= count($items) ?>)
          </h3>

          <div style="display: flex; flex-direction: column; gap: 0.65rem;">
            <?php foreach ($items as $it): ?>
              <?php 
                $addons = !empty($it['addons_json']) ? json_decode((string)$it['addons_json'], true) : [];
              ?>
              <div style="display: flex; justify-content: space-between; align-items: flex-start; padding: 0.65rem 0; border-bottom: 1px dashed rgba(26,20,18,0.08); font-size: 0.88rem;">
                <div>
                  <div style="font-weight: 600; color: var(--text-espresso, #1A1412);">
                    <?= (int)$it['quantity'] ?>x <?= e($it['item_name']) ?>
                  </div>
                  <?php if (!empty($it['variant_name'])): ?>
                    <div style="font-size: 0.75rem; color: var(--text-muted, #6E6259);"><?= e($it['variant_name']) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($addons)): ?>
                    <div style="font-size: 0.75rem; color: var(--accent-sky, #6B9AC4);">
                      <?= implode(', ', array_map(fn($a) => '+ ' . e($a['name']), $addons)) ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($it['item_note'])): ?>
                    <div style="font-size: 0.72rem; color: var(--accent-terracotta, #C96A52); font-style: italic;">
                      "<?= e($it['item_note']) ?>"
                    </div>
                  <?php endif; ?>
                </div>

                <div style="font-family: 'Fraunces', serif; font-weight: 700; color: var(--text-espresso, #1A1412);">
                  <?= Money::format((int)$it['total_price_paise']) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Financial Breakdown Summary -->
          <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid rgba(26,20,18,0.12); display: flex; flex-direction: column; gap: 0.45rem; font-size: 0.85rem; color: var(--text-muted, #6E6259);">
            <div style="display: flex; justify-content: space-between;">
              <span>Subtotal</span>
              <span><?= Money::format((int)$order['subtotal_paise']) ?></span>
            </div>
            <?php if ((int)$order['discount_paise'] > 0): ?>
              <div style="display: flex; justify-content: space-between; color: #2E7D32; font-weight: 600;">
                <span>Discount Applied</span>
                <span>- <?= Money::format((int)$order['discount_paise']) ?></span>
              </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between;">
              <span>Restaurant GST (5%)</span>
              <span><?= Money::format((int)$order['tax_paise']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-family: 'Fraunces', serif; font-size: 1.15rem; font-weight: 700; color: var(--text-espresso, #1A1412); padding-top: 0.5rem; border-top: 1px solid rgba(26,20,18,0.1);">
              <span>Grand Total</span>
              <span><?= Money::format((int)$order['total_paise']) ?></span>
            </div>
          </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
          <a href="/menu.php" style="flex: 1; text-align: center; background: rgba(26,20,18,0.06); color: var(--text-espresso, #1A1412); text-decoration: none; padding: 0.85rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem;">
            Order More Dishes
          </a>
        </div>

      </div>

    <?php endif; ?>

  </div>
</main>

<style>
  .step-circle {
    width: 44px;
    height: 44px;
    margin: 0 auto 0.5rem;
    border-radius: 50%;
    background: #FFFFFF;
    border: 2px solid rgba(26,20,18,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted, #6E6259);
    transition: all 0.3s ease;
  }
  .step-circle.is-active {
    background: var(--text-espresso, #1A1412);
    border-color: var(--text-espresso, #1A1412);
    color: #FFFFFF;
    box-shadow: 0 4px 12px rgba(26,20,18,0.2);
  }
  .step-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-muted, #6E6259);
  }
  .step-node:has(.step-circle.is-active) .step-label {
    color: var(--text-espresso, #1A1412);
    font-weight: 700;
  }
  .live-pulse-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #2E7D32;
    border-radius: 50%;
    margin-right: 0.4rem;
    animation: pulseDot 1.8s infinite;
  }
  @keyframes pulseDot {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(46,125,50,0.5); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(46,125,50,0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(46,125,50,0); }
  }
</style>

<script>
// Live Status Polling Engine (Every 6 seconds)
const orderNum = "<?= e($orderNumber) ?>";
if (orderNum) {
  setInterval(async () => {
    try {
      const res = await fetch(`/api/order_status.php?order=${encodeURIComponent(orderNum)}`);
      const data = await res.json();
      if (data.success) {
        updateLiveStatusUI(data.data);
      }
    } catch (e) {
      console.warn('Polling error:', e);
    }
  }, 6000);
}

function updateLiveStatusUI(orderData) {
  const stepIdx = orderData.step_index;
  document.querySelectorAll('.step-node').forEach(node => {
    const s = Number(node.getAttribute('data-step'));
    const circle = node.querySelector('.step-circle');
    if (s <= stepIdx) {
      circle.classList.add('is-active');
    } else {
      circle.classList.remove('is-active');
    }
  });

  const txtEl = document.getElementById('liveStatusText');
  if (txtEl) {
    if (orderData.status === 'placed') {
      txtEl.textContent = 'Kitchen received your order. Preparing fresh ingredients...';
    } else if (orderData.status === 'preparing') {
      txtEl.textContent = 'Chef is cooking your dishes in the kitchen!';
    } else if (orderData.status === 'ready') {
      txtEl.textContent = 'Your order is freshly plated and on its way to your table!';
    } else if (orderData.status === 'completed') {
      txtEl.textContent = 'Order complete! Hope you enjoyed your dining experience.';
    }
  }
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
