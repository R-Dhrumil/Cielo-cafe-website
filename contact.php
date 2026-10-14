<?php
/**
 * Cielo Cafe and Restro - Dedicated Contact & Location Page
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = "Visit Us & Contact | Cielo Cafe and Restro, Vadodara";
$pageDesc = "Find Cielo Cafe and Restro in Vadodara, Gujarat. Operating hours, direct contact numbers, Google Maps location, and table reservation details.";
$pageCss = ['contact.css'];
$pageJs = [];

require __DIR__ . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5rem;">
  <!-- Contact Hero Banner -->
  <section style="background-color: var(--text-espresso); color: var(--bg-cream); padding: 5rem 0 3.5rem 0; text-align: center;">
    <div class="container">
      <span class="eyebrow" style="color: var(--accent-sky);">VISIT CIELO</span>
      <h1 class="font-serif" style="font-size: 3rem; color: var(--bg-cream); margin-top: 0.5rem; margin-bottom: 0.75rem;">
        Location & Timings
      </h1>
      <p style="font-size: 0.9375rem; color: rgba(246, 239, 227, 0.85); max-width: 38rem; margin: 0 auto; line-height: 1.6;">
        Nestled in Vadodara, Gujarat. Join us for early morning artisanal roasts or unwind with friends over late-night Italian comfort plates.
      </p>
    </div>
  </section>

  <!-- Location & Map Section -->
  <section id="location" class="location-section">
    <div class="container">
      <div class="location-grid">
        <div class="location-col-left location-info">
          <div>
            <span class="eyebrow" style="color: var(--accent-sky-deep);">FIND US</span>
            <h2 class="location-title">Visit Cielo</h2>
            <p class="location-desc">
              Located in the heart of Vadodara. Drop in for quiet morning coffee or gather with friends for late-night feasts.
            </p>
          </div>

          <!-- Address Card -->
          <div class="address-card">
            <div class="address-row">
              <div class="address-icon-wrap">
                <svg class="address-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                </svg>
              </div>
              <div class="address-text-wrap">
                <h3 class="address-title">Address</h3>
                <p class="address-text"><?= e($business['address']['full']) ?></p>
              </div>
            </div>

            <div class="address-phone-row">
              <div class="phone-icon-wrap">
                <svg class="phone-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
              </div>
              <div class="phone-info">
                <span class="phone-label">Call &amp; Enquiries:</span>
                <a href="tel:<?= e($business['phoneRaw']) ?>" class="phone-link"><?= e($business['phone']) ?></a>
              </div>
            </div>
          </div>

          <!-- Opening Hours Schedule Card -->
          <div class="hours-card">
            <div class="hours-header">
              <div class="hours-header-left">
                <div class="hours-icon-wrap">
                  <svg class="hours-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                </div>
                <h3 class="hours-title">Opening Hours</h3>
              </div>
              <?php $openStatus = isCieloOpen(); ?>
              <span class="hours-status-pill <?= $openStatus['isOpen'] ? 'is-open' : 'is-closed' ?>">
                <span class="status-pulse-dot"></span>
                <?= e($openStatus['statusText']) ?>
              </span>
            </div>

            <div class="hours-main-row">
              <div class="hours-timing-group">
                <span class="hours-days-highlight">Monday – Sunday</span>
                <span class="hours-time-highlight">11:00 AM – 11:30 PM</span>
              </div>
            </div>

            <p class="hours-footnote">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 1 0 10 10H12V2z"/>
                <path d="M12 12 2.1 7.1"/>
                <path d="M12 12v9"/>
              </svg>
              Dine-in, Takeaway &amp; Restro Dining throughout the day
            </p>
          </div>

          <div class="location-actions">
            <a href="<?= e($business['googleMapsUrl']) ?>" target="_blank" rel="noopener noreferrer" class="btn-primary" style="font-size: 0.8125rem; padding: 0.85rem 1.6rem;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="3 11 22 2 13 21 11 13 3 11"/>
              </svg>
              Get Directions
            </a>
            <a href="tel:<?= e($business['phoneRaw']) ?>" class="btn-outline" style="font-size: 0.8125rem; padding: 0.85rem 1.6rem;">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
              Call Cafe
            </a>
          </div>
        </div>

        <!-- Embedded Lazy Google Map -->
        <div class="location-col-right">
          <div class="map-container">
            <iframe
              title="Cielo Cafe & Restro Location Map"
              src="https://maps.google.com/maps?q=<?= e((string)$business['coordinates']['lat']) ?>,<?= e((string)$business['coordinates']['lng']) ?>&hl=en&z=16&output=embed"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade">
            </iframe>
            <div class="map-floating-badge">
              <span class="map-pin-pulse"></span>
              Cielo Cafe & Restro · Vadodara
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
