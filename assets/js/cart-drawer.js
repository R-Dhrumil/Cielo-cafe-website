/**
 * Cielo Cafe & Restro - Client-Side Cart Drawer & Customization Controller
 * Zero-dependency modern ES6 script with LocalStorage and Server Sync
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'cielo_cart_v1';
  let menuData = null;
  let activeCustomItem = null;

  // Cart State
  const state = {
    items: [], // Array of { cartItemId, id, name, variantId, variantName, addonIds, addons, note, unitPricePaise, quantity, lineTotalPaise }
    couponCode: '',
    appliedCoupon: null,
    redeemPoints: 0,
    serverFinancials: null,
  };

  // Load persisted cart from localStorage
  function loadPersistedCart() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed.items)) {
          state.items = parsed.items;
          state.couponCode = parsed.couponCode || '';
          state.redeemPoints = parsed.redeemPoints || 0;
        }
      }
    } catch (e) {
      console.warn('Could not restore cart from storage:', e);
    }
  }

  function savePersistedCart() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        items: state.items,
        couponCode: state.couponCode,
        redeemPoints: state.redeemPoints,
      }));
    } catch (e) {
      console.warn('Could not save cart to storage:', e);
    }
  }

  // Fetch Menu from API
  async function fetchMenu() {
    try {
      const res = await fetch('/api/menu.php');
      const data = await res.json();
      if (data.success) {
        menuData = data.data;
        updateUI();
        if (state.items.length > 0) {
          validateCartWithServer();
        }
      }
    } catch (err) {
      console.error('Failed to load menu data:', err);
    }
  }

  // Find item in menu data
  function findMenuItem(itemId) {
    if (!menuData || !menuData.categories) return null;
    for (const cat of menuData.categories) {
      const found = cat.items.find(i => i.id === Number(itemId));
      if (found) return found;
    }
    return null;
  }

  // Generate unique ID for cart line items (hash of item_id + variant_id + sorted addons)
  function makeCartItemId(itemId, variantId, addonIds) {
    const sortedAddons = [...(addonIds || [])].sort().join(',');
    return `${itemId}_${variantId || 'base'}_${sortedAddons}`;
  }

  // Open Customization Modal
  function openCustomizer(itemId) {
    const item = findMenuItem(itemId);
    if (!item) return;

    activeCustomItem = item;
    const modal = document.getElementById('cieloCustomModal');
    if (!modal) return;

    document.getElementById('customDishName').textContent = item.name;
    document.getElementById('customDishDesc').textContent = item.description || '';
    const imgEl = document.getElementById('customDishImg');
    if (imgEl) imgEl.src = item.image_url || 'menu/unnamed.png';

    // Render Variants
    const variantsContainer = document.getElementById('customVariantsContainer');
    const variantsSection = document.getElementById('customVariantsSection');
    variantsContainer.innerHTML = '';

    if (item.variants && item.variants.length > 0) {
      variantsSection.style.display = 'block';
      item.variants.forEach((v, idx) => {
        const row = document.createElement('label');
        row.className = 'custom-option-row';
        row.innerHTML = `
          <div class="custom-option-label">
            <input type="radio" name="customVariant" value="${v.id}" data-price="${v.price_paise}" data-name="${v.name}" ${idx === 0 ? 'checked' : ''} onchange="window.CieloCart.recalcCustomPrice()">
            <span>${v.name}</span>
          </div>
          <span class="custom-option-price">${v.formatted}</span>
        `;
        variantsContainer.appendChild(row);
      });
    } else {
      variantsSection.style.display = 'none';
    }

    // Render Add-ons
    const addonsContainer = document.getElementById('customAddonsContainer');
    const addonsSection = document.getElementById('customAddonsSection');
    addonsContainer.innerHTML = '';

    if (item.addons && item.addons.length > 0) {
      addonsSection.style.display = 'block';
      item.addons.forEach(a => {
        const row = document.createElement('label');
        row.className = 'custom-option-row';
        row.innerHTML = `
          <div class="custom-option-label">
            <input type="checkbox" name="customAddon" value="${a.id}" data-price="${a.extra_price_paise}" data-name="${a.name}" onchange="window.CieloCart.recalcCustomPrice()">
            <span>${a.name}</span>
          </div>
          <span class="custom-option-price">${a.formatted}</span>
        `;
        addonsContainer.appendChild(row);
      });
    } else {
      addonsSection.style.display = 'none';
    }

    document.getElementById('customKitchenNote').value = '';
    recalcCustomPrice();
    modal.style.display = 'flex';
  }

  function closeCustomizer() {
    const modal = document.getElementById('cieloCustomModal');
    if (modal) modal.style.display = 'none';
    activeCustomItem = null;
  }

  // Recalculate price in customization modal footer
  function recalcCustomPrice() {
    if (!activeCustomItem) return;

    let basePaise = activeCustomItem.price_paise;
    const selectedVariant = document.querySelector('input[name="customVariant"]:checked');
    if (selectedVariant) {
      basePaise = Number(selectedVariant.getAttribute('data-price')) || basePaise;
    }

    let extraPaise = 0;
    document.querySelectorAll('input[name="customAddon"]:checked').forEach(cb => {
      extraPaise += Number(cb.getAttribute('data-price')) || 0;
    });

    const totalPaise = basePaise + extraPaise;
    const priceEl = document.getElementById('customTotalPrice');
    if (priceEl) {
      priceEl.textContent = `₹ ${(totalPaise / 100).toFixed(totalPaise % 100 === 0 ? 0 : 2)}`;
    }
  }

  // Add customized item to cart
  function addCustomizedToCart() {
    if (!activeCustomItem) return;

    let variantId = null;
    let variantName = null;
    let unitPricePaise = activeCustomItem.price_paise;

    const selectedVariant = document.querySelector('input[name="customVariant"]:checked');
    if (selectedVariant) {
      variantId = Number(selectedVariant.value);
      variantName = selectedVariant.getAttribute('data-name');
      unitPricePaise = Number(selectedVariant.getAttribute('data-price'));
    }

    const addonIds = [];
    const addons = [];
    document.querySelectorAll('input[name="customAddon"]:checked').forEach(cb => {
      const aid = Number(cb.value);
      const price = Number(cb.getAttribute('data-price'));
      const name = cb.getAttribute('data-name');
      addonIds.push(aid);
      addons.push({ id: aid, name, extra_price_paise: price });
      unitPricePaise += price;
    });

    const note = document.getElementById('customKitchenNote')?.value.trim() || '';
    const cartItemId = makeCartItemId(activeCustomItem.id, variantId, addonIds);

    const existingIndex = state.items.findIndex(i => i.cartItemId === cartItemId && i.note === note);
    if (existingIndex > -1) {
      state.items[existingIndex].quantity += 1;
      state.items[existingIndex].lineTotalPaise = state.items[existingIndex].quantity * state.items[existingIndex].unitPricePaise;
    } else {
      state.items.push({
        cartItemId,
        id: activeCustomItem.id,
        name: activeCustomItem.name,
        variantId,
        variantName,
        addonIds,
        addons,
        note,
        unitPricePaise,
        quantity: 1,
        lineTotalPaise: unitPricePaise,
      });
    }

    closeCustomizer();
    savePersistedCart();
    updateUI();
    validateCartWithServer();
    openCartDrawer();
  }

  // Quick Add for items without customization
  function quickAdd(itemId) {
    const item = findMenuItem(itemId);
    if (!item) return;

    if (item.has_customization) {
      openCustomizer(itemId);
      return;
    }

    const cartItemId = makeCartItemId(item.id, null, []);
    const existingIndex = state.items.findIndex(i => i.cartItemId === cartItemId);

    if (existingIndex > -1) {
      state.items[existingIndex].quantity += 1;
      state.items[existingIndex].lineTotalPaise = state.items[existingIndex].quantity * state.items[existingIndex].unitPricePaise;
    } else {
      state.items.push({
        cartItemId,
        id: item.id,
        name: item.name,
        variantId: null,
        variantName: null,
        addonIds: [],
        addons: [],
        note: '',
        unitPricePaise: item.price_paise,
        quantity: 1,
        lineTotalPaise: item.price_paise,
      });
    }

    savePersistedCart();
    updateUI();
    validateCartWithServer();
  }

  // Update Item Quantity
  function updateQuantity(cartItemId, delta) {
    const idx = state.items.findIndex(i => i.cartItemId === cartItemId);
    if (idx === -1) return;

    state.items[idx].quantity += delta;
    if (state.items[idx].quantity <= 0) {
      state.items.splice(idx, 1);
    } else {
      state.items[idx].lineTotalPaise = state.items[idx].quantity * state.items[idx].unitPricePaise;
    }

    savePersistedCart();
    updateUI();
    validateCartWithServer();
  }

  // Clear Cart
  function clearCart() {
    state.items = [];
    state.couponCode = '';
    state.appliedCoupon = null;
    state.redeemPoints = 0;
    state.serverFinancials = null;
    savePersistedCart();
    updateUI();
  }

  // Drawer Controls
  function openCartDrawer() {
    const overlay = document.getElementById('cieloDrawerOverlay');
    if (overlay) overlay.classList.add('is-open');
    validateCartWithServer();
  }

  function closeCartDrawer() {
    const overlay = document.getElementById('cieloDrawerOverlay');
    if (overlay) overlay.classList.remove('is-open');
  }

  // Authoritative Server-side Cart Validation
  async function validateCartWithServer() {
    if (state.items.length === 0) {
      state.serverFinancials = null;
      updateUI();
      return;
    }

    const payload = {
      items: state.items.map(i => ({
        id: i.id,
        variant_id: i.variantId,
        addon_ids: i.addonIds,
        quantity: i.quantity,
        note: i.note,
      })),
      coupon_code: state.couponCode,
      redeem_points: state.redeemPoints,
    };

    try {
      const res = await fetch('/api/cart_validate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const result = await res.json();

      if (result.success) {
        state.serverFinancials = result.data.financials;
        state.appliedCoupon = result.data.financials.coupon;
        updateUI();
      }
    } catch (e) {
      console.warn('Server cart validation error:', e);
    }
  }

  // Apply Coupon
  async function applyCoupon() {
    const input = document.getElementById('cartCouponInput');
    const msgEl = document.getElementById('cartCouponMsg');
    if (!input) return;

    const code = input.value.trim().toUpperCase();
    if (!code) return;

    const subtotalPaise = state.items.reduce((sum, i) => sum + i.lineTotalPaise, 0);

    try {
      const res = await fetch('/api/coupon_validate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, subtotal_paise: subtotalPaise }),
      });
      const result = await res.json();

      if (result.success) {
        state.couponCode = code;
        state.appliedCoupon = result.data;
        savePersistedCart();
        validateCartWithServer();
        if (msgEl) {
          msgEl.className = 'status-msg success';
          msgEl.textContent = result.data.message;
          msgEl.style.display = 'block';
        }
      } else {
        if (msgEl) {
          msgEl.className = 'status-msg error';
          msgEl.textContent = result.message || 'Invalid coupon';
          msgEl.style.display = 'block';
        }
      }
    } catch (e) {
      if (msgEl) {
        msgEl.className = 'status-msg error';
        msgEl.textContent = 'Failed to validate coupon.';
        msgEl.style.display = 'block';
      }
    }
  }

  function removeCoupon() {
    state.couponCode = '';
    state.appliedCoupon = null;
    savePersistedCart();
    validateCartWithServer();
  }

  // Update UI Elements
  function updateUI() {
    const totalCount = state.items.reduce((sum, i) => sum + i.quantity, 0);
    const rawSubtotalPaise = state.items.reduce((sum, i) => sum + i.lineTotalPaise, 0);

    // 1. Floating Cart Bar
    const bar = document.getElementById('cieloFloatingCartBar');
    if (bar) {
      if (totalCount > 0) {
        bar.classList.add('is-visible');
        const countBadge = document.getElementById('floatingCartCount');
        const totalEl = document.getElementById('floatingCartTotal');
        if (countBadge) countBadge.textContent = `${totalCount} item${totalCount > 1 ? 's' : ''}`;
        if (totalEl) {
          const grandTotal = state.serverFinancials ? state.serverFinancials.grand_total_inr : (rawSubtotalPaise / 100);
          totalEl.textContent = `₹ ${grandTotal.toFixed(grandTotal % 1 === 0 ? 0 : 2)}`;
        }
      } else {
        bar.classList.remove('is-visible');
      }
    }

    // 2. Drawer Line Items
    const itemsContainer = document.getElementById('drawerItemsContainer');
    const emptyState = document.getElementById('drawerEmptyState');
    const footer = document.getElementById('drawerFooter');
    const perksBox = document.getElementById('drawerPerksBox');

    if (itemsContainer && emptyState && footer) {
      if (state.items.length === 0) {
        itemsContainer.innerHTML = '';
        emptyState.style.display = 'block';
        footer.style.display = 'none';
        if (perksBox) perksBox.style.display = 'none';
      } else {
        emptyState.style.display = 'none';
        footer.style.display = 'flex';
        if (perksBox) perksBox.style.display = 'flex';

        itemsContainer.innerHTML = state.items.map(i => {
          const customs = [];
          if (i.variantName) customs.push(i.variantName);
          if (i.addons && i.addons.length > 0) {
            customs.push(i.addons.map(a => `+ ${a.name}`).join(', '));
          }
          const priceInr = (i.lineTotalPaise / 100).toFixed(i.lineTotalPaise % 100 === 0 ? 0 : 2);

          return `
            <div class="cart-item-card">
              <div class="cart-item-top">
                <div>
                  <h4 class="cart-item-title">${i.name}</h4>
                  ${customs.length > 0 ? `<div class="cart-item-customs">${customs.join(' · ')}</div>` : ''}
                  ${i.note ? `<div class="cart-item-note">Note: "${i.note}"</div>` : ''}
                </div>
                <div class="cart-item-price">₹ ${priceInr}</div>
              </div>
              <div class="cart-item-bottom">
                <div class="cart-stepper">
                  <button type="button" class="btn-stepper" onclick="window.CieloCart.updateQuantity('${i.cartItemId}', -1)">−</button>
                  <span class="stepper-val">${i.quantity}</span>
                  <button type="button" class="btn-stepper" onclick="window.CieloCart.updateQuantity('${i.cartItemId}', 1)">+</button>
                </div>
                <button type="button" style="background:none; border:none; color:#C96A52; font-size:0.75rem; cursor:pointer;" onclick="window.CieloCart.updateQuantity('${i.cartItemId}', -${i.quantity})">Remove</button>
              </div>
            </div>
          `;
        }).join('');

        // 3. Update Bill Breakdown
        const fin = state.serverFinancials;
        const subtotalInr = fin ? fin.subtotal_inr : (rawSubtotalPaise / 100);
        const grandTotalInr = fin ? fin.grand_total_inr : (rawSubtotalPaise / 100);

        document.getElementById('billSubtotal').textContent = `₹ ${subtotalInr.toFixed(2)}`;
        
        // Coupon Row
        const couponRow = document.getElementById('billCouponRow');
        const couponVal = document.getElementById('billCouponVal');
        if (fin && fin.coupon_discount_paise > 0) {
          couponRow.style.display = 'flex';
          couponVal.textContent = `- ₹ ${(fin.coupon_discount_paise / 100).toFixed(2)}`;
        } else {
          couponRow.style.display = 'none';
        }

        // Taxes Row
        const taxVal = document.getElementById('billTaxVal');
        if (fin && fin.total_tax_paise > 0) {
          taxVal.textContent = `₹ ${(fin.total_tax_paise / 100).toFixed(2)} (5% GST)`;
        } else {
          taxVal.textContent = `₹ ${(rawSubtotalPaise * 0.05 / 100).toFixed(2)} (5% GST)`;
        }

        // Grand Total
        document.getElementById('billGrandTotal').textContent = `₹ ${grandTotalInr.toFixed(2)}`;
        document.getElementById('checkoutBtnAmount').textContent = `₹ ${grandTotalInr.toFixed(2)}`;

        // Coupon Applied Tag in Perks Box
        const couponTagContainer = document.getElementById('couponAppliedTag');
        if (couponTagContainer) {
          if (state.appliedCoupon) {
            couponTagContainer.innerHTML = `
              <div class="coupon-applied-pill">
                <span>Coupon '${state.appliedCoupon.code}' applied</span>
                <button type="button" style="background:none; border:none; cursor:pointer; font-weight:700;" onclick="window.CieloCart.removeCoupon()">&times;</button>
              </div>
            `;
          } else {
            couponTagContainer.innerHTML = '';
          }
        }
      }
    }
  }

  // Global Cart API
  window.CieloCart = {
    quickAdd,
    openCustomizer,
    closeCustomizer,
    recalcCustomPrice,
    addCustomizedToCart,
    updateQuantity,
    clearCart,
    openCartDrawer,
    closeCartDrawer,
    applyCoupon,
    removeCoupon,
  };

  // Init on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    loadPersistedCart();
    fetchMenu();
  });

})();
