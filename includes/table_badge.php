<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Active Table & Loyalty Header Widget
 * Include in header or customer-facing pages (e.g. menu.php)
 */

use App\Services\TableSessionService;

$activeTable = TableSessionService::getActiveTable();
$activeUser = $_SESSION['cielo_user'] ?? null;
?>

<!-- Table Session & Loyalty Floating Header Bar -->
<div id="cieloTableWidget" class="cielo-table-widget" data-has-table="<?= $activeTable ? 'true' : 'false' ?>">
  <div class="table-widget-container">
    <?php if ($activeTable): ?>
      <!-- Seated at Active Table -->
      <div class="table-badge-active">
        <span class="pulse-indicator" aria-hidden="true"></span>
        <div class="table-details">
          <svg class="table-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect width="18" height="12" x="3" y="4" rx="2"/>
            <line x1="6" x2="6" y1="16" y2="20"/>
            <line x1="18" x2="18" y1="16" y2="20"/>
          </svg>
          <span class="table-title">Table <?= e($activeTable['table_number']) ?></span>
          <span class="table-floor"><?= e($activeTable['floor']) ?></span>
        </div>

        <!-- Loyalty Customer Tag -->
        <div class="customer-loyalty-tag" id="customerLoyaltyTag">
          <?php if ($activeUser): ?>
            <span class="loyalty-pill" title="<?= e($activeUser['phone']) ?>">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <span><?= e($activeUser['name']) ?></span>
              <span class="points-badge"><?= (int)($activeUser['points_balance'] ?? 0) ?> pts</span>
            </span>
          <?php else: ?>
            <button type="button" class="btn-earn-points" onclick="openLoyaltyModal()">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <span>Earn Points</span>
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <!-- Guest Browsing (No Table QR Scanned Yet) -->
      <div class="table-badge-guest">
        <svg class="qr-hint-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect width="18" height="18" x="3" y="3" rx="2"/>
          <path d="M7 7h.01M17 7h.01M7 17h.01M17 17h.01"/>
        </svg>
        <span class="guest-text">Seated at Cielo? Scan the sticker on your table to order directly.</span>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Loyalty Identification Modal -->
<div id="loyaltyModal" class="loyalty-modal-overlay" style="display: none;" onclick="if(event.target === this) closeLoyaltyModal()">
  <div class="loyalty-modal-content">
    <button type="button" class="modal-close-btn" onclick="closeLoyaltyModal()" aria-label="Close modal">&times;</button>
    
    <div class="modal-header">
      <div class="modal-star-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      </div>
      <h3 class="modal-title">Cielo Loyalty Rewards</h3>
      <p class="modal-subtitle">Earn 10 points for every ₹100 spent, redeem vouchers, and unlock exclusive cafe perks.</p>
    </div>

    <form id="loyaltyForm" onsubmit="handleLoyaltySubmit(event)" class="loyalty-form">
      <div class="input-group">
        <label for="loyaltyPhone">Mobile Number</label>
        <div class="phone-input-wrapper">
          <span class="phone-prefix">+91</span>
          <input type="tel" id="loyaltyPhone" name="phone" placeholder="98765 43210" maxlength="10" required pattern="[6-9][0-9]{9}" inputmode="numeric">
        </div>
      </div>

      <div class="input-group">
        <label for="loyaltyName">Your Name <span class="optional-label">(Optional)</span></label>
        <input type="text" id="loyaltyName" name="name" placeholder="E.g. Dhrumil">
      </div>

      <div id="loyaltyStatusMessage" class="status-msg" style="display: none;"></div>

      <button type="submit" id="loyaltySubmitBtn" class="btn-modal-submit">
        <span>Connect & Unlock Rewards</span>
      </button>

      <button type="button" class="btn-modal-skip" onclick="closeLoyaltyModal()">
        Continue as Guest
      </button>
    </form>
  </div>
</div>

<style>
  /* Cielo Floating Table & Loyalty Styling */
  .cielo-table-widget {
    position: sticky;
    top: 70px;
    z-index: 90;
    width: 100%;
    background: rgba(247, 244, 238, 0.95);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-bottom: 1px solid rgba(26, 20, 18, 0.08);
    padding: 0.5rem 1rem;
    box-shadow: 0 4px 20px rgba(26, 20, 18, 0.03);
    font-family: 'Plus Jakarta Sans', sans-serif;
  }
  .table-widget-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .table-badge-active {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    width: 100%;
    justify-content: space-between;
  }
  .table-details {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }
  .pulse-indicator {
    width: 8px;
    height: 8px;
    background: #2E7D32;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.2);
    animation: pulseAnim 2s infinite;
  }
  @keyframes pulseAnim {
    0% { box-shadow: 0 0 0 0 rgba(46, 125, 50, 0.4); }
    70% { box-shadow: 0 0 0 6px rgba(46, 125, 50, 0); }
    100% { box-shadow: 0 0 0 0 rgba(46, 125, 50, 0); }
  }
  .table-title {
    font-family: 'Fraunces', serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-espresso, #1A1412);
  }
  .table-floor {
    font-size: 0.75rem;
    color: var(--text-muted, #6E6259);
    background: rgba(26, 20, 18, 0.05);
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  .customer-loyalty-tag {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .loyalty-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(201, 106, 82, 0.1);
    color: var(--accent-terracotta, #C96A52);
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    border: 1px solid rgba(201, 106, 82, 0.2);
  }
  .points-badge {
    background: var(--accent-terracotta, #C96A52);
    color: #FFFFFF;
    font-size: 0.7rem;
    padding: 0.1rem 0.4rem;
    border-radius: 999px;
    margin-left: 0.2rem;
  }
  .btn-earn-points {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: var(--text-espresso, #1A1412);
    color: #FFFFFF;
    border: none;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    cursor: pointer;
    transition: transform 0.15s ease, background 0.15s ease;
  }
  .btn-earn-points:hover {
    background: #2C221F;
    transform: translateY(-1px);
  }
  .table-badge-guest {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.82rem;
    color: var(--text-muted, #6E6259);
    width: 100%;
    justify-content: center;
  }
  .qr-hint-icon {
    color: var(--accent-sky, #6B9AC4);
    flex-shrink: 0;
  }

  /* Loyalty Modal */
  .loyalty-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(26, 20, 18, 0.6);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
  }
  .loyalty-modal-content {
    background: #FFFFFF;
    border-radius: 20px;
    max-width: 400px;
    width: 100%;
    padding: 2rem 1.75rem;
    position: relative;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(26, 20, 18, 0.1);
    animation: modalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
  }
  .modal-close-btn {
    position: absolute;
    top: 1rem;
    right: 1.25rem;
    background: transparent;
    border: none;
    font-size: 1.5rem;
    line-height: 1;
    color: var(--text-muted, #6E6259);
    cursor: pointer;
  }
  .modal-header {
    text-align: center;
    margin-bottom: 1.5rem;
  }
  .modal-star-icon {
    width: 48px;
    height: 48px;
    background: rgba(201, 106, 82, 0.12);
    color: var(--accent-terracotta, #C96A52);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.75rem;
  }
  .modal-title {
    font-family: 'Fraunces', serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--text-espresso, #1A1412);
    margin: 0 0 0.4rem;
  }
  .modal-subtitle {
    font-size: 0.85rem;
    color: var(--text-muted, #6E6259);
    line-height: 1.45;
    margin: 0;
  }
  .loyalty-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  .input-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    text-align: left;
  }
  .input-group label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-espresso, #1A1412);
  }
  .optional-label {
    font-weight: 400;
    color: var(--text-muted, #6E6259);
  }
  .phone-input-wrapper {
    display: flex;
    align-items: center;
    border: 1px solid rgba(26, 20, 18, 0.16);
    border-radius: 10px;
    overflow: hidden;
    background: #FAFAFA;
  }
  .phone-prefix {
    padding: 0.65rem 0.85rem;
    background: rgba(26, 20, 18, 0.05);
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-espresso, #1A1412);
    border-right: 1px solid rgba(26, 20, 18, 0.16);
  }
  .phone-input-wrapper input, .input-group input[type="text"] {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border: none;
    outline: none;
    font-size: 0.95rem;
    font-family: inherit;
    color: var(--text-espresso, #1A1412);
    background: transparent;
  }
  .input-group input[type="text"] {
    border: 1px solid rgba(26, 20, 18, 0.16);
    border-radius: 10px;
    background: #FAFAFA;
  }
  .phone-input-wrapper:focus-within, .input-group input[type="text"]:focus {
    border-color: var(--accent-sky, #6B9AC4);
    box-shadow: 0 0 0 3px rgba(107, 154, 196, 0.15);
  }
  .btn-modal-submit {
    background: var(--text-espresso, #1A1412);
    color: #FFFFFF;
    border: none;
    padding: 0.85rem;
    border-radius: 10px;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
    margin-top: 0.5rem;
  }
  .btn-modal-submit:hover {
    background: #2C221F;
  }
  .btn-modal-skip {
    background: transparent;
    border: none;
    color: var(--text-muted, #6E6259);
    font-size: 0.82rem;
    cursor: pointer;
    text-decoration: underline;
    padding: 0.25rem;
  }
  .status-msg {
    padding: 0.6rem 0.8rem;
    border-radius: 8px;
    font-size: 0.85rem;
    text-align: center;
  }
  .status-msg.error {
    background: rgba(201, 106, 82, 0.1);
    color: var(--accent-terracotta, #C96A52);
    border: 1px solid rgba(201, 106, 82, 0.2);
  }
  .status-msg.success {
    background: rgba(46, 125, 50, 0.1);
    color: #2E7D32;
    border: 1px solid rgba(46, 125, 50, 0.2);
  }
</style>

<script>
function openLoyaltyModal() {
  const modal = document.getElementById('loyaltyModal');
  if (modal) {
    modal.style.display = 'flex';
    document.getElementById('loyaltyPhone')?.focus();
  }
}

function closeLoyaltyModal() {
  const modal = document.getElementById('loyaltyModal');
  if (modal) modal.style.display = 'none';
}

async function handleLoyaltySubmit(event) {
  event.preventDefault();
  const phoneInput = document.getElementById('loyaltyPhone');
  const nameInput = document.getElementById('loyaltyName');
  const submitBtn = document.getElementById('loyaltySubmitBtn');
  const msgEl = document.getElementById('loyaltyStatusMessage');

  const phone = phoneInput.value.trim();
  const name = nameInput.value.trim();

  if (!phone.match(/^[6-9]\d{9}$/)) {
    msgEl.className = 'status-msg error';
    msgEl.textContent = 'Please enter a valid 10-digit mobile number.';
    msgEl.style.display = 'block';
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Connecting...';
  msgEl.style.display = 'none';

  try {
    const res = await fetch('/api/customer_lookup.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone, name })
    });
    const result = await res.json();

    if (result.success) {
      msgEl.className = 'status-msg success';
      msgEl.textContent = `Welcome ${result.data.user.name}! Points: ${result.data.loyalty.points_balance}`;
      msgEl.style.display = 'block';

      // Update widget UI
      const tagEl = document.getElementById('customerLoyaltyTag');
      if (tagEl) {
        tagEl.innerHTML = `
          <span class="loyalty-pill">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span>${result.data.user.name}</span>
            <span class="points-badge">${result.data.loyalty.points_balance} pts</span>
          </span>
        `;
      }

      setTimeout(() => {
        closeLoyaltyModal();
      }, 1200);
    } else {
      msgEl.className = 'status-msg error';
      msgEl.textContent = result.message || 'Could not verify mobile number.';
      msgEl.style.display = 'block';
    }
  } catch (err) {
    msgEl.className = 'status-msg error';
    msgEl.textContent = 'Network error. Please try again.';
    msgEl.style.display = 'block';
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Connect & Unlock Rewards';
  }
}
</script>
