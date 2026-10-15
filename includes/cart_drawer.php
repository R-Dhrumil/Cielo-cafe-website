<?php

declare(strict_types=1);

/**
 * Cielo Cafe & Restro - Cart Drawer & Product Customizer Markup
 * Embeds the luxury slide-out cart drawer, bottom floating cart bar, and item customizer.
 */

use App\Services\TableSessionService;

$activeTable = TableSessionService::getActiveTable();
?>

<!-- Floating Bottom Cart Bar -->
<div id="cieloFloatingCartBar" class="cielo-floating-cart-bar" onclick="window.CieloCart.openCartDrawer()">
  <div class="cart-bar-left">
    <span id="floatingCartCount" class="cart-badge-count">0 items</span>
    <span id="floatingCartTotal" class="cart-bar-total">₹ 0</span>
  </div>
  <div class="cart-bar-right">
    <span>View Cart</span>
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
  </div>
</div>

<!-- Slide-Out Luxury Cart Drawer -->
<div id="cieloDrawerOverlay" class="cielo-drawer-overlay" onclick="if(event.target === this) window.CieloCart.closeCartDrawer()">
  <aside class="cielo-drawer" aria-label="Shopping Cart">
    <div class="drawer-header">
      <div class="drawer-title-group">
        <h3>Your Order</h3>
        <span class="drawer-table-tag">
          <?php if ($activeTable): ?>
            Table <?= e($activeTable['table_number']) ?> · <?= e($activeTable['floor']) ?>
          <?php else: ?>
            Guest / Counter Order
          <?php endif; ?>
        </span>
      </div>
      <button type="button" class="btn-drawer-close" onclick="window.CieloCart.closeCartDrawer()" aria-label="Close cart">&times;</button>
    </div>

    <div class="drawer-body">
      <!-- Empty Cart State -->
      <div id="drawerEmptyState" class="drawer-empty-state">
        <svg class="drawer-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="8" cy="21" r="1"/>
          <circle cx="19" cy="21" r="1"/>
          <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
        </svg>
        <p style="font-family:'Fraunces', serif; font-size:1.15rem; color:var(--cielo-espresso); margin:0 0 0.5rem;">Your cart is empty</p>
        <p style="font-size:0.85rem; margin:0;">Explore our artisanal Italian sourdough pizzas, bronze pasta, and slow cold brews.</p>
      </div>

      <!-- Line Items List -->
      <div id="drawerItemsContainer"></div>

      <!-- Promo Coupon & Perks Box -->
      <div id="drawerPerksBox" class="cart-perks-box" style="display: none;">
        <div id="couponAppliedTag"></div>
        <div class="coupon-input-group">
          <input type="text" id="cartCouponInput" placeholder="PROMO CODE (E.G. WELCOME50)">
          <button type="button" class="btn-apply-coupon" onclick="window.CieloCart.applyCoupon()">Apply</button>
        </div>
        <div id="cartCouponMsg" class="status-msg" style="display: none;"></div>
      </div>
    </div>

    <!-- Drawer Footer & Bill Breakdown -->
    <div id="drawerFooter" class="drawer-footer" style="display: none;">
      <div class="bill-row">
        <span>Item Subtotal</span>
        <span id="billSubtotal">₹ 0.00</span>
      </div>
      <div id="billCouponRow" class="bill-row discount-row" style="display: none;">
        <span>Promo Discount</span>
        <span id="billCouponVal">- ₹ 0.00</span>
      </div>
      <div class="bill-row">
        <span>Taxes & GST (5%)</span>
        <span id="billTaxVal">₹ 0.00</span>
      </div>
      <div class="bill-row total-row">
        <span>Total Payable</span>
        <span id="billGrandTotal">₹ 0.00</span>
      </div>

      <button type="button" id="btnDrawerCheckout" class="btn-checkout-cta" onclick="window.location.href='/checkout.php'">
        <span>Proceed to Place Order</span>
        <span id="checkoutBtnAmount" style="font-weight:400; opacity:0.9;">₹ 0.00</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </div>
  </aside>
</div>

<!-- Product Customization Modal -->
<div id="cieloCustomModal" class="cielo-custom-modal-overlay" style="display: none;" onclick="if(event.target === this) window.CieloCart.closeCustomizer()">
  <div class="cielo-custom-modal">
    <div class="custom-modal-hero">
      <img id="customDishImg" src="/menu/unnamed.png" alt="Dish preview">
      <button type="button" class="btn-custom-close" onclick="window.CieloCart.closeCustomizer()" aria-label="Close dialog">&times;</button>
    </div>

    <div class="custom-modal-body">
      <div>
        <h3 id="customDishName" class="custom-dish-name">Dish Name</h3>
        <p id="customDishDesc" class="custom-dish-desc">Artisanal description of ingredients and culinary notes.</p>
      </div>

      <!-- Variants (Single Selection Radio) -->
      <div id="customVariantsSection" style="display: none;">
        <h4 class="custom-section-title">Select Size / Style</h4>
        <div id="customVariantsContainer"></div>
      </div>

      <!-- Addons (Multi Selection Checkbox) -->
      <div id="customAddonsSection" style="display: none;">
        <h4 class="custom-section-title">Add Extra Customizations</h4>
        <div id="customAddonsContainer"></div>
      </div>

      <!-- Chef Instructions -->
      <div>
        <h4 class="custom-section-title">Special Kitchen Instructions</h4>
        <textarea id="customKitchenNote" class="kitchen-notes-input" rows="2" placeholder="E.g. Extra crispy crust, less spicy, dressing on the side..."></textarea>
      </div>
    </div>

    <div class="custom-modal-footer">
      <button type="button" class="btn-add-custom-cart" onclick="window.CieloCart.addCustomizedToCart()">
        <span>Add to Order</span>
        <span id="customTotalPrice">₹ 0</span>
      </button>
    </div>
  </div>
</div>
