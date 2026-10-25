<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Customer Checkout View
 * Allows customers to review items, choose payment method, and submit table order.
 */

require_once dirname(__DIR__) . '/includes/config.php';

use App\Services\TableSessionService;

$activeTable = TableSessionService::getActiveTable();
$activeUser = $_SESSION['cielo_user'] ?? null;

$pageTitle = "Checkout | Cielo Cafe and Restro";
$pageDesc = "Review and confirm your artisanal dining order at Cielo Cafe Vadodara.";
$pageCss = ['checkout.css'];

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5rem; min-height: 80vh; background-color: var(--bg-cream, #F7F4EE);">
  <div class="checkout-container">

    <!-- Breadcrumb & Table Info -->
    <div class="checkout-header-row">
      <div>
        <a href="/menu" class="checkout-back-link">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          Back to Menu
        </a>
        <h1 class="checkout-page-title">
          Review & Place Order
        </h1>
      </div>

      <div class="checkout-dining-chip">
        <span class="dining-chip-dot" aria-hidden="true"></span>
        <span class="dining-chip-label">
          <?= $activeTable ? "Table " . e($activeTable['table_number']) : "Counter / Takeaway" ?>
        </span>
        <span class="dining-chip-type">
          <?= $activeTable ? e($activeTable['floor']) : "Guest" ?>
        </span>
      </div>
    </div>

    <!-- Empty Cart Notice (if navigated directly) -->
    <div id="checkoutEmptyWarning" style="display: none; background: #FFFFFF; padding: 3.5rem 1.5rem; border-radius: 20px; text-align: center; border: 1px solid rgba(26,20,18,0.1); box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
      <h2 style="font-family: 'Fraunces', serif; font-size: 1.6rem; color: var(--text-espresso, #1A1412); margin: 0 0 0.75rem;">Your Cart is Empty</h2>
      <p style="color: var(--text-muted, #6E6259); margin: 0 0 1.5rem; font-size: 0.95rem; max-width: 24rem; margin-left: auto; margin-right: auto; line-height: 1.5;">Please select delicious handcrafted plates or specialty estate coffee from our menu before proceeding.</p>
      <a href="/menu" class="btn-primary" style="display: inline-flex; padding: 0.9rem 2rem; border-radius: 999px; text-decoration: none; font-weight: 700;">
        Browse Menu Catalog
      </a>
    </div>

    <!-- Main Checkout Grid / Flex Form -->
    <div id="checkoutContentGrid">
      <form id="checkoutForm" class="checkout-form-grid" onsubmit="handlePlaceOrder(event)">

        <!-- Left: Customer Details & Payment Options -->
        <div class="checkout-col-left">

          <!-- Section 1: Customer Contact -->
          <div class="checkout-card">
            <h3 class="checkout-section-title">
              1. Your Contact Details
            </h3>
            <p class="checkout-section-subtitle">
              We send live WhatsApp & SMS preparation updates and credit your loyalty reward points to this number.
            </p>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
              <div class="checkout-input-group">
                <label for="custPhone" class="checkout-input-label">
                  Mobile Number <span style="color: var(--accent-terracotta, #C96A52);">*</span>
                </label>
                <div class="checkout-phone-wrapper">
                  <span class="checkout-phone-prefix">+91</span>
                  <input type="tel" id="custPhone" name="phone" value="<?= e($activeUser['phone'] ?? '') ?>" placeholder="98765 43210" maxlength="10" required pattern="[6-9][0-9]{9}" inputmode="numeric" class="checkout-phone-input">
                </div>
                <span class="checkout-input-hint">Enter 10-digit Indian mobile number</span>
              </div>

              <div class="checkout-input-group">
                <label for="custName" class="checkout-input-label">
                  Your Name <span style="font-weight: 400; color: var(--text-muted, #6E6259);">(Optional)</span>
                </label>
                <input type="text" id="custName" name="name" value="<?= e($activeUser['name'] ?? '') ?>" placeholder="E.g. Dhrumil" class="checkout-text-input">
              </div>
            </div>
          </div>

          <!-- Section 2: Payment Method -->
          <div class="checkout-card">
            <h3 class="checkout-section-title">
              2. Select Payment Method
            </h3>
            <p class="checkout-section-subtitle">
              Choose how you wish to settle the bill upon order completion.
            </p>

            <div class="payment-options-list">
              <!-- Option A: Pay at Counter (Cash) -->
              <label class="payment-option-label">
                <input type="radio" name="payment_method" value="counter_cash" checked>
                <svg class="payment-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="12" x="2" y="6" rx="2"/>
                  <circle cx="12" cy="12" r="2"/>
                  <path d="M6 12h.01M18 12h.01"/>
                </svg>
                <div style="flex: 1; min-width: 0;">
                  <div class="payment-option-title">Pay at Counter (Cash)</div>
                  <div class="payment-option-desc">Settle bill directly with the host/cashier via cash once served.</div>
                </div>
              </label>

              <!-- Option B: Pay at Counter (UPI QR) -->
              <label class="payment-option-label">
                <input type="radio" name="payment_method" value="counter_upi">
                <svg class="payment-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="18" height="18" x="3" y="3" rx="2"/>
                  <path d="M7 7h.01M17 7h.01M7 17h.01M17 17h.01"/>
                </svg>
                <div style="flex: 1; min-width: 0;">
                  <div class="payment-option-title">Pay via Counter UPI QR Code</div>
                  <div class="payment-option-desc">Instant digital scan using GPay, PhonePe, Paytm, or CRED.</div>
                </div>
              </label>

              <!-- Option C: Online Razorpay (Coming Soon) -->
              <div class="payment-option-disabled">
                <input type="radio" disabled style="margin-top: 0.2rem;">
                <svg class="payment-option-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="14" x="2" y="5" rx="2"/>
                  <line x1="2" x2="22" y1="10" y2="10"/>
                </svg>
                <div style="flex: 1; min-width: 0;">
                  <div class="payment-option-title" style="color: var(--text-muted, #6E6259);">
                    Online Cards / NetBanking
                    <span style="font-size: 0.68rem; background: var(--accent-terracotta, #C96A52); color:#FFF; padding: 0.15rem 0.5rem; border-radius: 4px; margin-left: 0.35rem; font-weight: 600;">Coming Soon</span>
                  </div>
                  <div class="payment-option-desc">Direct in-app payment gateway settlement.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Kitchen Notes -->
          <div class="checkout-card">
            <h3 class="checkout-section-title">
              3. Special Kitchen Instructions
            </h3>
            <p class="checkout-section-subtitle">
              Allergies, spice levels, ice preferences, or dining notes for our barista and chefs.
            </p>
            <textarea id="checkoutNotes" rows="2" placeholder="E.g. Table prefers starters served first, please make marinara pasta mild..." class="checkout-text-input" style="resize: vertical;"></textarea>
          </div>
        </div>

        <!-- Right / Bottom: Order Summary & Checkout CTA -->
        <div class="checkout-summary-card">
          <div class="summary-card-header">
            <h3 class="summary-card-title">Order Summary</h3>
            <a href="/menu" class="summary-modify-link">+ Add Items</a>
          </div>

          <!-- Items List Snapshot -->
          <div id="checkoutItemsList" class="checkout-items-list"></div>

          <!-- Financial Breakdown -->
          <div class="checkout-breakdown">
            <div class="breakdown-row">
              <span>Subtotal</span>
              <span id="chkSubtotal" style="font-weight: 600; color: var(--text-espresso, #1A1412);">₹ 0.00</span>
            </div>

            <div id="chkDiscountRow" class="breakdown-row discount" style="display: none;">
              <span>Promo Discount</span>
              <span id="chkDiscount">- ₹ 0.00</span>
            </div>

            <div id="chkRewardRow" class="breakdown-row reward" style="display: none;">
              <span id="chkRewardLabel">Milestone Reward</span>
              <span id="chkRewardVal">- ₹ 0.00</span>
            </div>

            <div class="breakdown-row">
              <span>Restaurant GST (5%)</span>
              <span id="chkTax" style="font-weight: 600; color: var(--text-espresso, #1A1412);">₹ 0.00</span>
            </div>

            <div class="breakdown-row total">
              <span>Total Amount</span>
              <span id="chkGrandTotal">₹ 0.00</span>
            </div>
          </div>

          <div id="checkoutErrMsg" class="checkout-error-banner" style="display: none;"></div>

          <!-- Desktop Place Order Button -->
          <button type="submit" id="btnPlaceOrder" class="btn-place-order-desktop">
            <span>Confirm & Place Order</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>

        <!-- Mobile Sticky Bottom Place Order Dock (< 900px) -->
        <div class="checkout-mobile-sticky-dock" id="checkoutMobileDock">
          <div class="mobile-dock-info">
            <span class="mobile-dock-label">Total to pay</span>
            <span id="chkDockGrandTotal" class="mobile-dock-total">₹ 0.00</span>
          </div>

          <button type="submit" id="btnPlaceOrderMobile" class="btn-place-order-mobile">
            <span>Place Order</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </div>

      </form>
    </div>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const rawCart = localStorage.getItem('cielo_cart_v1');
  const cart = rawCart ? JSON.parse(rawCart) : null;

  if (!cart || !Array.isArray(cart.items) || cart.items.length === 0) {
    document.getElementById('checkoutContentGrid').style.display = 'none';
    const mobileDock = document.getElementById('checkoutMobileDock');
    if (mobileDock) mobileDock.style.display = 'none';
    document.getElementById('checkoutEmptyWarning').style.display = 'block';
    return;
  }

  // Validate cart with server
  try {
    const res = await fetch('/api/cart_validate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        items: cart.items.map(i => ({
          id: i.id,
          variant_id: i.variantId,
          addon_ids: i.addonIds,
          quantity: i.quantity,
          note: i.note
        })),
        coupon_code: cart.couponCode || '',
        reward_id: cart.rewardId || null,
        redeem_points: cart.redeemPoints || 0
      })
    });
    const result = await res.json();

    if (result.success) {
      renderCheckoutSummary(result.data.line_items, result.data.financials);
    }
  } catch (err) {
    console.error('Error validating cart for checkout:', err);
  }
});

function renderCheckoutSummary(lineItems, fin) {
  const listEl = document.getElementById('checkoutItemsList');
  listEl.innerHTML = lineItems.map(item => {
    const customs = [];
    if (item.variant_name) customs.push(item.variant_name);
    if (item.addons && item.addons.length > 0) {
      customs.push(item.addons.map(a => a.name).join(', '));
    }
    const price = (item.line_total_paise / 100).toFixed(2);

    return `
      <div class="checkout-item-row">
        <div style="flex: 1; min-width: 0;">
          <div class="checkout-item-name">${item.quantity}x ${item.name}</div>
          ${customs.length > 0 ? `<div class="checkout-item-customs">${customs.join(' · ')}</div>` : ''}
          ${item.note ? `<div style="font-size:0.72rem; color:var(--accent-terracotta, #C96A52); font-style:italic; margin-top:0.2rem;">"${item.note}"</div>` : ''}
        </div>
        <div class="checkout-item-price">₹ ${price}</div>
      </div>
    `;
  }).join('');

  const subtotalStr = `₹ ${fin.subtotal_inr.toFixed(2)}`;
  const grandTotalStr = `₹ ${fin.grand_total_inr.toFixed(2)}`;

  document.getElementById('chkSubtotal').textContent = subtotalStr;

  if (fin.total_discount_paise > 0) {
    document.getElementById('chkDiscountRow').style.display = 'flex';
    document.getElementById('chkDiscount').textContent = `- ₹ ${(fin.total_discount_paise / 100).toFixed(2)}`;
  }

  if (fin.applied_reward && fin.reward_discount_paise > 0) {
    document.getElementById('chkRewardRow').style.display = 'flex';
    document.getElementById('chkRewardLabel').textContent = `★ ${fin.applied_reward.title}`;
    document.getElementById('chkRewardVal').textContent = `- ₹ ${(fin.reward_discount_paise / 100).toFixed(2)}`;
  } else {
    document.getElementById('chkRewardRow').style.display = 'none';
  }

  document.getElementById('chkTax').textContent = `₹ ${(fin.total_tax_paise / 100).toFixed(2)}`;
  document.getElementById('chkGrandTotal').textContent = grandTotalStr;

  // Sync mobile bottom dock grand total
  const dockTotal = document.getElementById('chkDockGrandTotal');
  if (dockTotal) dockTotal.textContent = grandTotalStr;
}

async function handlePlaceOrder(event) {
  event.preventDefault();

  const phone = document.getElementById('custPhone').value.trim();
  const name = document.getElementById('custName').value.trim();
  const notes = document.getElementById('checkoutNotes').value.trim();
  const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'counter_cash';

  const rawCart = localStorage.getItem('cielo_cart_v1');
  const cart = rawCart ? JSON.parse(rawCart) : null;

  if (!cart || !cart.items || cart.items.length === 0) {
    alert('Your cart is empty.');
    window.location.href = '/menu.php';
    return;
  }

  const btnDesktop = document.getElementById('btnPlaceOrder');
  const btnMobile = document.getElementById('btnPlaceOrderMobile');
  const errEl = document.getElementById('checkoutErrMsg');

  const setButtonsLoading = (isLoading) => {
    if (btnDesktop) {
      btnDesktop.disabled = isLoading;
      btnDesktop.innerHTML = isLoading ? 'Placing Order...' : '<span>Confirm & Place Order</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    }
    if (btnMobile) {
      btnMobile.disabled = isLoading;
      btnMobile.innerHTML = isLoading ? 'Placing Order...' : '<span>Place Order</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    }
  };

  setButtonsLoading(true);
  errEl.style.display = 'none';

  try {
    const res = await fetch('/api/order_create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        phone,
        name,
        payment_method: paymentMethod,
        kitchen_notes: notes,
        coupon_code: cart.couponCode || '',
        reward_id: cart.rewardId || null,
        redeem_points: cart.redeemPoints || 0,
        items: cart.items.map(i => ({
          id: i.id,
          variant_id: i.variantId,
          addon_ids: i.addonIds,
          quantity: i.quantity,
          note: i.note
        }))
      })
    });
    const result = await res.json();

    if (result.success) {
      // Clear localStorage cart
      localStorage.removeItem('cielo_cart_v1');
      // Redirect to live tracking screen
      window.location.href = result.data.redirect_url;
    } else {
      errEl.textContent = result.message || 'Failed to place order. Please try again.';
      errEl.style.display = 'block';
      setButtonsLoading(false);
      errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  } catch (err) {
    errEl.textContent = 'Network error while placing order. Please check connection.';
    errEl.style.display = 'block';
    setButtonsLoading(false);
  }
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
