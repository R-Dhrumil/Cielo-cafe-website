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
$pageCss = ['menu.css', 'cart-drawer.css'];

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5.5rem; min-height: 80vh; background-color: var(--bg-cream, #F7F4EE);">
  <div class="container" style="max-width: 960px; margin: 0 auto; padding: 2rem 1rem;">

    <!-- Breadcrumb & Table Info -->
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
      <div>
        <a href="/menu" style="color: var(--accent-sky, #6B9AC4); font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          Back to Menu
        </a>
        <h1 style="font-family: 'Fraunces', serif; font-size: 2rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin: 0.5rem 0 0;">
          Review & Place Order
        </h1>
      </div>

      <div style="background: #FFFFFF; border: 1px solid rgba(26,20,18,0.12); padding: 0.6rem 1.25rem; border-radius: 999px; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(26,20,18,0.04);">
        <span style="width: 8px; height: 8px; background: #2E7D32; border-radius: 50%; display: inline-block;"></span>
        <span style="font-family: 'Fraunces', serif; font-weight: 700; font-size: 0.95rem; color: var(--text-espresso, #1A1412);">
          <?= $activeTable ? "Table " . e($activeTable['table_number']) : "Counter / Takeaway" ?>
        </span>
        <span style="font-size: 0.75rem; color: var(--text-muted, #6E6259); text-transform: uppercase;">
          <?= $activeTable ? e($activeTable['floor']) : "Guest" ?>
        </span>
      </div>
    </div>

    <!-- Empty Cart Notice (if navigated directly) -->
    <div id="checkoutEmptyWarning" style="display: none; background: #FFFFFF; padding: 3rem 2rem; border-radius: 20px; text-align: center; border: 1px solid rgba(26,20,18,0.1);">
      <h2 style="font-family: 'Fraunces', serif; font-size: 1.5rem; color: var(--text-espresso, #1A1412); margin: 0 0 0.75rem;">Your Cart is Empty</h2>
      <p style="color: var(--text-muted, #6E6259); margin: 0 0 1.5rem;">Please select delicious items from our menu before proceeding to checkout.</p>
      <a href="/menu" class="btn-primary" style="display: inline-block; padding: 0.85rem 1.75rem; border-radius: 999px; text-decoration: none; font-weight: 600;">
        Browse Menu
      </a>
    </div>

    <!-- Main Checkout Grid -->
    <div id="checkoutContentGrid" style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
      <form id="checkoutForm" onsubmit="handlePlaceOrder(event)" style="display: grid; grid-template-columns: 1fr 380px; gap: 2rem; align-items: start;">

        <!-- Left: Customer Details & Payment Options -->
        <div style="display: flex; flex-direction: column; gap: 1.75rem;">

          <!-- Section 1: Customer Contact -->
          <div style="background: #FFFFFF; border-radius: 18px; padding: 1.75rem; border: 1px solid rgba(26,20,18,0.12); box-shadow: 0 4px 15px rgba(26,20,18,0.03);">
            <h3 style="font-family: 'Fraunces', serif; font-size: 1.25rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin: 0 0 1.25rem;">
              1. Your Contact Details
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
              <div>
                <label for="custPhone" style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-espresso, #1A1412); margin-bottom: 0.35rem;">
                  Mobile Number <span style="color: var(--accent-terracotta, #C96A52);">*</span>
                </label>
                <div style="display: flex; align-items: center; border: 1px solid rgba(26,20,18,0.15); border-radius: 10px; overflow: hidden; background: #FAFAFA;">
                  <span style="padding: 0.75rem 0.9rem; background: rgba(26,20,18,0.05); font-weight: 600; font-size: 0.9rem; color: var(--text-espresso, #1A1412); border-right: 1px solid rgba(26,20,18,0.15);">+91</span>
                  <input type="tel" id="custPhone" name="phone" value="<?= e($activeUser['phone'] ?? '') ?>" placeholder="98765 43210" maxlength="10" required pattern="[6-9][0-9]{9}" style="width: 100%; border: none; padding: 0.75rem; outline: none; font-size: 0.95rem; font-family: inherit; background: transparent;">
                </div>
                <span style="font-size: 0.75rem; color: var(--text-muted, #6E6259); margin-top: 0.35rem; display: block;">Used for kitchen order status and loyalty rewards point collection.</span>
              </div>

              <div>
                <label for="custName" style="display: block; font-size: 0.82rem; font-weight: 600; color: var(--text-espresso, #1A1412); margin-bottom: 0.35rem;">
                  Your Name <span style="font-weight: 400; color: var(--text-muted, #6E6259);">(Optional)</span>
                </label>
                <input type="text" id="custName" name="name" value="<?= e($activeUser['name'] ?? '') ?>" placeholder="E.g. Dhrumil" style="width: 100%; box-sizing: border-box; border: 1px solid rgba(26,20,18,0.15); border-radius: 10px; padding: 0.75rem 0.9rem; outline: none; font-size: 0.95rem; font-family: inherit; background: #FAFAFA;">
              </div>
            </div>
          </div>

          <!-- Section 2: Payment Method -->
          <div style="background: #FFFFFF; border-radius: 18px; padding: 1.75rem; border: 1px solid rgba(26,20,18,0.12); box-shadow: 0 4px 15px rgba(26,20,18,0.03);">
            <h3 style="font-family: 'Fraunces', serif; font-size: 1.25rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin: 0 0 1.25rem;">
              2. Select Payment Method
            </h3>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              <!-- Option A: Pay at Counter (Cash) -->
              <label style="display: flex; align-items: flex-start; gap: 0.85rem; padding: 1rem; border: 2px solid var(--text-espresso, #1A1412); border-radius: 12px; cursor: pointer; background: rgba(26,20,18,0.02);">
                <input type="radio" name="payment_method" value="counter_cash" checked style="margin-top: 0.25rem;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-espresso, #1A1412);">Pay at Counter (Cash)</div>
                  <div style="font-size: 0.82rem; color: var(--text-muted, #6E6259); margin-top: 0.2rem;">Settle bill with the cashier via cash when order is served.</div>
                </div>
              </label>

              <!-- Option B: Pay at Counter (UPI QR) -->
              <label style="display: flex; align-items: flex-start; gap: 0.85rem; padding: 1rem; border: 1px solid rgba(26,20,18,0.15); border-radius: 12px; cursor: pointer;">
                <input type="radio" name="payment_method" value="counter_upi" style="margin-top: 0.25rem;">
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-espresso, #1A1412);">Pay via Counter UPI QR Code</div>
                  <div style="font-size: 0.82rem; color: var(--text-muted, #6E6259); margin-top: 0.2rem;">Scan our counter QR using GPay, PhonePe, Paytm, or CRED.</div>
                </div>
              </label>

              <!-- Option C: Online Razorpay (Coming Soon) -->
              <div style="display: flex; align-items: flex-start; gap: 0.85rem; padding: 1rem; border: 1px solid rgba(26,20,18,0.08); border-radius: 12px; opacity: 0.5; background: #FAFAFA;">
                <input type="radio" disabled style="margin-top: 0.25rem;">
                <div>
                  <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-muted, #6E6259);">
                    Online Cards / NetBanking / Razorpay <span style="font-size: 0.7rem; background: var(--accent-terracotta, #C96A52); color:#FFF; padding: 0.15rem 0.5rem; border-radius: 4px; margin-left: 0.4rem;">Coming Soon</span>
                  </div>
                  <div style="font-size: 0.8rem; color: var(--text-muted, #6E6259); margin-top: 0.2rem;">Integrated payment gateway for instant online settlement.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Kitchen Notes -->
          <div style="background: #FFFFFF; border-radius: 18px; padding: 1.75rem; border: 1px solid rgba(26,20,18,0.12); box-shadow: 0 4px 15px rgba(26,20,18,0.03);">
            <h3 style="font-family: 'Fraunces', serif; font-size: 1.25rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin: 0 0 0.5rem;">
              3. Special Kitchen Instructions
            </h3>
            <p style="font-size: 0.82rem; color: var(--text-muted, #6E6259); margin: 0 0 1rem;">Allergies, spice adjustments, or preparation notes for our kitchen team.</p>
            <textarea id="checkoutNotes" rows="2" placeholder="E.g. Table prefers all starters served together, please make marinara mild..." style="width: 100%; box-sizing: border-box; border: 1px solid rgba(26,20,18,0.15); border-radius: 10px; padding: 0.75rem 0.9rem; font-family: inherit; font-size: 0.9rem; resize: vertical;"></textarea>
          </div>
        </div>

        <!-- Right: Order Summary & Checkout CTA -->
        <div style="position: sticky; top: 90px; background: #FFFFFF; border-radius: 18px; padding: 1.75rem; border: 1px solid rgba(26,20,18,0.12); box-shadow: 0 10px 30px rgba(26,20,18,0.06); display: flex; flex-direction: column; gap: 1.25rem;">
          <h3 style="font-family: 'Fraunces', serif; font-size: 1.25rem; font-weight: 700; color: var(--text-espresso, #1A1412); margin: 0; border-bottom: 1px solid rgba(26,20,18,0.08); padding-bottom: 0.75rem;">
            Order Summary
          </h3>

          <!-- Items List Snapshot -->
          <div id="checkoutItemsList" style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 240px; overflow-y: auto; padding-right: 0.25rem;"></div>

          <!-- Financial Breakdown -->
          <div style="border-top: 1px solid rgba(26,20,18,0.08); padding-top: 1rem; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.88rem;">
            <div style="display: flex; justify-content: space-between; color: var(--text-muted, #6E6259);">
              <span>Subtotal</span>
              <span id="chkSubtotal" style="font-weight: 600; color: var(--text-espresso, #1A1412);">₹ 0.00</span>
            </div>

            <div id="chkDiscountRow" style="display: none; justify-content: space-between; color: #2E7D32; font-weight: 600;">
              <span>Promo Discount</span>
              <span id="chkDiscount">- ₹ 0.00</span>
            </div>

            <div id="chkRewardRow" style="display: none; justify-content: space-between; color: var(--accent-terracotta, #C96A52); font-weight: 600;">
              <span id="chkRewardLabel">Milestone Reward</span>
              <span id="chkRewardVal">- ₹ 0.00</span>
            </div>

            <div style="display: flex; justify-content: space-between; color: var(--text-muted, #6E6259);">
              <span>Restaurant GST (5%)</span>
              <span id="chkTax" style="font-weight: 600; color: var(--text-espresso, #1A1412);">₹ 0.00</span>
            </div>

            <div style="display: flex; justify-content: space-between; font-family: 'Fraunces', serif; font-size: 1.3rem; font-weight: 700; color: var(--text-espresso, #1A1412); border-top: 1px solid rgba(26,20,18,0.12); padding-top: 0.75rem;">
              <span>Total Amount</span>
              <span id="chkGrandTotal">₹ 0.00</span>
            </div>
          </div>

          <div id="checkoutErrMsg" style="display: none; background: rgba(201,106,82,0.1); border: 1px solid rgba(201,106,82,0.25); color: #C96A52; padding: 0.75rem; border-radius: 8px; font-size: 0.85rem; text-align: center;"></div>

          <button type="submit" id="btnPlaceOrder" style="background: var(--text-espresso, #1A1412); color: #FFFFFF; border: none; padding: 1.1rem; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: background 0.15s ease; box-shadow: 0 4px 14px rgba(26,20,18,0.2);">
            <span>Confirm & Place Order</span>
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
      <div style="display:flex; justify-content:space-between; align-items:flex-start; font-size:0.85rem; border-bottom:1px dashed rgba(26,20,18,0.06); padding-bottom:0.5rem;">
        <div>
          <div style="font-weight:600; color:var(--text-espresso, #1A1412);">${item.quantity}x ${item.name}</div>
          ${customs.length > 0 ? `<div style="font-size:0.75rem; color:var(--text-muted, #6E6259);">${customs.join(' · ')}</div>` : ''}
          ${item.note ? `<div style="font-size:0.72rem; color:var(--accent-terracotta, #C96A52); font-style:italic;">"${item.note}"</div>` : ''}
        </div>
        <div style="font-family:'Fraunces', serif; font-weight:700; color:var(--text-espresso, #1A1412);">₹ ${price}</div>
      </div>
    `;
  }).join('');

  document.getElementById('chkSubtotal').textContent = `₹ ${fin.subtotal_inr.toFixed(2)}`;

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
  document.getElementById('chkGrandTotal').textContent = `₹ ${fin.grand_total_inr.toFixed(2)}`;
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

  const btn = document.getElementById('btnPlaceOrder');
  const errEl = document.getElementById('checkoutErrMsg');
  btn.disabled = true;
  btn.innerHTML = 'Placing Order...';
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
      btn.disabled = false;
      btn.innerHTML = '<span>Confirm & Place Order</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    }
  } catch (err) {
    errEl.textContent = 'Network error while placing order. Please check connection.';
    errEl.style.display = 'block';
    btn.disabled = false;
    btn.innerHTML = '<span>Confirm & Place Order</span>';
  }
}
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
