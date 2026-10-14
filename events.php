<?php
/**
 * Cielo Cafe and Restro - Dedicated Events & Celebrations Page
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = "Private Events & Celebrations | Cielo Cafe and Restro, Vadodara";
$pageDesc = "Host your birthdays, kitty parties, acoustic live evenings, and family gatherings at Cielo Cafe and Restro, Vadodara. Customized menus and warm ambient arches.";
$pageCss = ['events.css'];
$pageJs = [];

require __DIR__ . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5rem;">
  <!-- Events Hero Banner -->
  <section style="background-color: var(--text-espresso); color: var(--bg-cream); padding: 5rem 0 3.5rem 0; text-align: center;">
    <div class="container">
      <span class="eyebrow" style="color: var(--accent-sky);">CELEBRATE UNDER THE CIELO SKY</span>
      <h1 class="font-serif" style="font-size: 3rem; color: var(--bg-cream); margin-top: 0.5rem; margin-bottom: 0.75rem;">
        Private Events & Gatherings
      </h1>
      <p style="font-size: 0.9375rem; color: rgba(246, 239, 227, 0.85); max-width: 38rem; margin: 0 auto; line-height: 1.6;">
        From intimate birthday parties and joyful family reunions to corporate coffee mixers and acoustic evenings. We tailor every detail to perfection.
      </p>
    </div>
  </section>

  <!-- Events Promo Section -->
  <section id="events" class="events-section">
    <div class="container">
      <div class="events-grid">
        <div class="events-col-left events-content">
          <div class="events-eyebrow-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-sky);">
              <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
            </svg>
            <span class="eyebrow"><?= e($business['events']['eyebrow']) ?></span>
          </div>

          <h2 class="events-heading">
            <?= e($business['events']['heading']) ?>
          </h2>

          <p class="events-desc">
            <?= e($business['events']['subheading']) ?>
          </p>

          <div class="events-features-grid">
            <?php foreach ($business['events']['features'] as $feature): ?>
              <div class="events-feature-item">
                <svg class="events-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span class="events-feature-text"><?= e($feature) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="events-actions">
            <a href="https://wa.me/<?= e($business['whatsappNumber']) ?>?text=<?= urlencode("Hello Cielo Cafe! I would like to enquire about hosting a private event / birthday / kitty party.") ?>" target="_blank" rel="noopener noreferrer" class="btn-terracotta" style="font-size: 0.75rem;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/>
                <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>
              </svg>
              Enquire on WhatsApp
            </a>
            <span class="events-footnote">Personalized party packages start from 10 guests. Instant date checking.</span>
          </div>
        </div>

        <div class="events-col-right">
          <div class="events-composition">
            <div class="events-img-primary image-zoom-container">
              <img src="assets/images/cafe/hero-table.jpg" alt="Celebration Table Spread at Cielo Cafe">
            </div>
            <div class="events-img-secondary image-zoom-container">
              <img src="assets/images/cafe/menu-cover-spaghetti.jpg" alt="Cielo Cafe Signature Spaghetti Pomodoro and Brand Booklet">
            </div>
            <div class="events-booking-pill">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
              </svg>
              Private Booking
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
