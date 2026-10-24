<?php
/**
 * Table Reservation Modal Component
 */
?>
<div id="reservationModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="reservationModalTitle">
  <div class="reservation-modal-window">
    <button id="closeReservationBtn" class="modal-close-btn" aria-label="Close reservation modal">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>

    <!-- Success State -->
    <div id="reservationSuccessState" style="display: none; text-align: center; padding: 2rem 0;">
      <div style="width: 4rem; height: 4rem; margin: 0 auto 1.5rem auto; border-radius: 50%; background-color: rgba(143, 184, 216, 0.2); display: flex; align-items: center; justify-content: center; color: var(--accent-sky-deep);">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h3 class="font-serif" style="font-size: 1.5rem; color: var(--text-espresso); margin-bottom: 0.5rem;">Reservation Request Prepared!</h3>
      <p style="font-size: 0.8125rem; color: var(--text-espresso-muted); max-width: 20rem; margin: 0 auto 1.5rem auto;">
        Connecting you directly to Cielo's manager on WhatsApp to confirm table availability instantly.
      </p>
      <button id="backToSiteBtn" class="btn-primary" style="margin-top: 0.5rem;">
        Back to Website
      </button>
    </div>

    <!-- Form State -->
    <div id="reservationFormState">
      <div class="modal-header-centered">
        <span class="eyebrow" style="color: var(--accent-sky-deep);">TABLE BOOKING</span>
        <h2 id="reservationModalTitle" class="modal-title">Reserve Your Table</h2>
        <p class="modal-subtext">Join us for morning brew, artisanal bites, or a late-night candlelit dinner.</p>
      </div>

      <form id="reservationForm" class="reservation-form" data-whatsapp="<?= e($business['whatsappNumber']) ?>">
        <div class="form-row-2">
          <div class="form-field">
            <label class="form-label" for="resName">Your Name *</label>
            <input type="text" id="resName" class="form-input" required placeholder="e.g. Aarav Patel">
          </div>
          <div class="form-field">
            <label class="form-label" for="resPhone">Phone Number *</label>
            <input type="tel" id="resPhone" class="form-input" required placeholder="e.g. +91 98250 12345">
          </div>
        </div>

        <div class="form-row-3">
          <div class="form-field">
            <label class="form-label" for="resGuests">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Guests
            </label>
            <select id="resGuests" class="form-select">
              <option value="1">1 Guest</option>
              <option value="2" selected>2 Guests</option>
              <option value="3">3 Guests</option>
              <option value="4">4 Guests</option>
              <option value="5">5 Guests</option>
              <option value="6">6 Guests</option>
              <option value="8">8 Guests</option>
              <option value="10">10 Guests</option>
              <option value="12">12 Guests</option>
              <option value="15+">15+ (Private Event)</option>
            </select>
          </div>

          <div class="form-field">
            <label class="form-label" for="resDate">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
              Date *
            </label>
            <input type="date" id="resDate" class="form-input" required value="<?= date('Y-m-d') ?>">
          </div>

          <div class="form-field">
            <label class="form-label" for="resTime">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Time *
            </label>
            <select id="resTime" class="form-select">
              <option value="11:30 AM">11:30 AM (Brunch)</option>
              <option value="01:00 PM">01:00 PM (Lunch)</option>
              <option value="04:00 PM">04:00 PM (Coffee/Snacks)</option>
              <option value="06:30 PM">06:30 PM (Evening)</option>
              <option value="07:30 PM" selected>07:30 PM (Dinner)</option>
              <option value="08:30 PM">08:30 PM (Dinner)</option>
              <option value="09:30 PM">09:30 PM (Late Night)</option>
            </select>
          </div>
        </div>

        <div class="form-field">
          <label class="form-label" for="resOccasion">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
            Occasion / Preference
          </label>
          <select id="resOccasion" class="form-select">
            <option value="Casual Dining">Casual Dining / Coffee Hangout</option>
            <option value="Birthday Celebration">Birthday Celebration</option>
            <option value="Anniversary Date">Romantic Anniversary Date</option>
            <option value="Kitty Party / Gathering">Kitty Party / Family Reunion</option>
            <option value="Business Meeting / Workspace">Business Meeting / Workspace</option>
          </select>
        </div>

        <div class="form-field">
          <label class="form-label" for="resNotes">Special Notes / Dietary Preferences (Optional)</label>
          <textarea id="resNotes" class="form-textarea" placeholder="e.g. Jain food preferences, quiet corner table requested..."></textarea>
        </div>

        <div style="padding-top: 0.5rem;">
          <button type="submit" class="btn-terracotta" style="width: 100%; padding: 0.9rem;">
            Confirm Table Request
          </button>
          <p style="text-align: center; font-size: 0.625rem; color: var(--text-espresso-faint); margin-top: 0.5rem;">
            No advance fee required. Direct instant confirmation via Cielo WhatsApp desk.
          </p>
        </div>
      </form>
    </div>
  </div>
</div>
