<?php
/**
 * Cielo Cafe and Restro - Homepage
 * Production-ready PHP Migration
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = "Cielo Cafe and Restro | Cafe & Restaurant in Vadodara";
$pageDesc = "Experience Italian-inspired cafe culture and vibrant dining at Cielo Cafe and Restro, Vadodara. Handcrafted estate coffee, stone-baked pizzas, artisanal pasta, and late-night restro vibes.";
$pageCss = ['home.css', 'celebrations.css', 'menu.css', 'events.css', 'gallery.css', 'gallery-3d.css', 'reviews.css', 'contact.css', 'newsletter.css'];
$pageJs = [
    'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
    'celebrations.js',
    'menu-tabs.js',
    'lightbox.js',
    'gallery-3d.js',
    'newsletter.js'
];

require __DIR__ . '/includes/header.php';

// Prepare dynamic image configuration for 3D Gallery
$gallery3dImages = [];
if (!empty($gallery['items']) && is_array($gallery['items'])) {
    foreach ($gallery['items'] as $item) {
        $gallery3dImages[] = [
            'src' => asset_url($item['image']),
            'alt' => $item['title']
        ];
    }
}
$ambientPhotos = [
    ['src' => 'assets/images/cafe/hero-table.jpg', 'alt' => 'Cielo Evening Table Spread'],
    ['src' => 'assets/images/cafe/cielo-night-entrance.jpg', 'alt' => 'Cielo Night Entrance & Patio'],
    ['src' => 'assets/images/cafe/menu-cover-spaghetti.jpg', 'alt' => 'Cielo Italian Pasta & Ambiance']
];
foreach ($ambientPhotos as $ap) {
    $gallery3dImages[] = $ap;
}
$galleryConfig = [
    'images' => $gallery3dImages,
    'visibleCount' => 8,
    'speed' => 1,
    'depthRange' => 50,
    'fadeSettings' => [
        'fadeIn' => ['start' => 0.05, 'end' => 0.25],
        'fadeOut' => ['start' => 0.75, 'end' => 0.95]
    ],
    'blurSettings' => [
        'maxBlur' => 3.0,
        'blurIn' => ['start' => 0.0, 'end' => 0.15],
        'blurOut' => ['start' => 0.85, 'end' => 1.0]
    ]
];
$galleryConfigJson = json_encode($galleryConfig, JSON_UNESCAPED_SLASHES);
?>

<main id="main-content">
  <!-- 1. Cinematic Hero Section Inspired by Villa Aurelia -->
  <section id="heroSection" class="hero-cinematic-section">
    <!-- Smooth Cinematic Vignette Overlay -->
    <div class="hero-cinematic-vignette" aria-hidden="true"></div>

    <div class="container hero-cinematic-container">
      <div class="hero-cinematic-content">
        <!-- Italian Greeting Accent (Villa Aurelia Style: Benvenuti) -->
      

        <!-- Grand Editorial Brand Heading in Fraunces -->
        <h1 class="hero-brand-title">
          <span class="hero-brand-name">Cielo</span>
          <span class="hero-brand-tagline">Cafe and Restro</span>
        </h1>

        <!-- Scroll Cue Right Aligned -->
        <div class="hero-scroll-wrap">
          <a href="#marquee-strip" class="hero-scroll-cue" aria-label="Scroll to explore">
            <span class="hero-scroll-text">Scroll to explore</span>
            <span class="hero-scroll-line" aria-hidden="true">
              <span class="hero-scroll-pulse"></span>
            </span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. Slogan Marquee Strip -->
  <div id="marquee-strip" class="marquee-wrapper">
    <div class="marquee-content">
      <?php
      $marqueeItems = [
          "Brewed fresh.",
          "Served with love.",
          "Stone-baked sourdough.",
          "Open till late.",
          "Vadodara's unhurried table.",
          "Artisan coffee & restro."
      ];
      ?>
      <?php for ($g = 0; $g < 3; $g++): ?>
        <div class="marquee-group">
          <?php foreach ($marqueeItems as $item): ?>
            <div class="marquee-item">
              <span class="marquee-text"><?= e($item) ?></span>
              <span class="marquee-brand">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-terracotta);">
                  <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                </svg>
                Cielo.
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- 3. Split Sections: Story & Fresh Ingredients -->
  <div class="container split-sections">
    <!-- Story Section -->
    <section id="story" class="split-row">
      <div class="split-col-7 split-text-content">
        <span class="eyebrow" style="color: var(--accent-sky-deep);">
          <?= e($business['story']['eyebrow']) ?>
        </span>
        <h2 class="split-heading">
          <?= e($business['story']['heading']) ?>
        </h2>
        <div class="split-paragraphs">
          <p><?= e($business['story']['paragraph1']) ?></p>
          <p><?= e($business['story']['paragraph2']) ?></p>
        </div>
        <div class="quote-badge">
          <div class="quote-line"></div>
          <blockquote class="quote-text">
            &ldquo;<?= e($business['story']['quote']) ?>&rdquo;
          </blockquote>
        </div>
        <div style="padding-top: 0.5rem;">
          <a href="menu.php" class="btn-primary">View Full Menu</a>
        </div>
      </div>

      <div class="split-col-5">
        <div class="split-image-card image-zoom-container">
          <img src="assets/images/cafe/interior-atmosphere.jpg" alt="Cielo Cafe Interior and Ambient Dining Vibe">
          <div class="atmosphere-pill">
            <span class="atmosphere-title">Atmosphere & Ambiance</span>
            <p class="atmosphere-caption">Cane-back armchairs, warm arched lighting & artisanal cold brew.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Fresh Ingredients Section -->
    <section class="split-row reverse-desktop">
      <div class="split-col-5">
        <div class="split-image-card aspect-4-5 image-zoom-container">
          <img src="assets/images/cafe/all-day-spread.jpg" alt="Fresh Gourmet Pizza, Pasta and Sourdough Spread">
          <div class="farm-fresh-tag">Farm Fresh</div>
        </div>
      </div>

      <div class="split-col-7 split-text-content">
        <span class="eyebrow" style="color: var(--accent-terracotta);">
          <?= e($business['qualityIngredients']['eyebrow']) ?>
        </span>
        <h2 class="split-heading">
          <?= e($business['qualityIngredients']['heading']) ?>
        </h2>
        <p class="split-paragraphs" style="font-size: 0.9375rem;">
          <?= e($business['qualityIngredients']['paragraph']) ?>
        </p>

        <!-- Stats Grid -->
        <div class="stats-grid">
          <?php foreach ($business['qualityIngredients']['stats'] as $st): ?>
            <div class="stat-box">
              <span class="stat-box-value"><?= e($st['value']) ?></span>
              <span class="stat-box-label"><?= e($st['label']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Ordering Delivery Options -->
        <div class="order-partners-block">
          <span class="order-partners-title">Order Online / Delivery Options:</span>
          <div class="order-buttons-row">
            <a href="https://wa.me/<?= e($business['whatsappNumber']) ?>?text=<?= urlencode($business['orderWhatsAppMessage']) ?>" target="_blank" rel="noopener noreferrer" class="btn-terracotta" style="font-size: 0.75rem; padding: 0.75rem 1.5rem;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>
              </svg>
              Order via WhatsApp
            </a>
            <a href="<?= e($business['links']['zomato']) ?>" target="_blank" rel="noopener noreferrer" class="btn-delivery-badge">
              Zomato
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
              </svg>
            </a>
            <a href="<?= e($business['links']['swiggy']) ?>" target="_blank" rel="noopener noreferrer" class="btn-delivery-badge">
              Swiggy
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
              </svg>
            </a>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- 4. Flagship Interactive Celebrations, DJ & Gatherings Experience Section -->
  <section id="celebrations" class="celebrations-section">
    <!-- Ambient Floating Background Glow Orbs -->
    <div class="celebrations-glow-orb celebrations-glow-orb-sky" aria-hidden="true"></div>
    <div class="celebrations-glow-orb celebrations-glow-orb-terracotta" aria-hidden="true"></div>

    <div class="container">
      <!-- Section Header -->
      <div class="celebrations-header">
        <div class="celebrations-eyebrow-wrap">
          <span class="live-beacon" aria-hidden="true"></span>
          <span class="celebrations-eyebrow-text">EVENTS & CELEBRATION HOSTING</span>
          <div class="audio-visualizer-bar" aria-hidden="true">
            <span class="visualizer-col"></span>
            <span class="visualizer-col"></span>
            <span class="visualizer-col"></span>
            <span class="visualizer-col"></span>
            <span class="visualizer-col"></span>
          </div>
        </div>
        <h2 class="celebrations-title">Italian Ambiance, Electric Evenings</h2>
        <p class="celebrations-subtitle">
          From high-energy DJ sets and milestone birthday bashes to cozy kitty parties and corporate mixers. Transform our arched Italian restro into your exclusive private venue in Vadodara.
        </p>
      </div>

      <!-- Interactive Mode Navigation Tabs -->
      <div class="celebrations-tabs-nav" role="tablist" aria-label="Event Types">
        <button type="button" class="celebrations-tab-btn is-active" data-target="dj-night" data-guests="Up to 60 Guests" data-tempo="0.6s" role="tab" aria-selected="true">
          <svg class="celebrations-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
          </svg>
          Live DJ & Electric Nights
        </button>

        <button type="button" class="celebrations-tab-btn" data-target="private-party" data-guests="15 – 50 Guests" data-tempo="1.0s" role="tab" aria-selected="false">
          <svg class="celebrations-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
          </svg>
          Private Parties & Birthdays
        </button>

        <button type="button" class="celebrations-tab-btn" data-target="gatherings" data-guests="8 – 25 Guests" data-tempo="1.4s" role="tab" aria-selected="false">
          <svg class="celebrations-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          Small Gatherings & Kitty Parties
        </button>
      </div>

      <!-- Main Showcase Grid -->
      <div class="celebrations-showcase-grid">
        <!-- Visual Interactive Stage (Left) -->
        <div class="celebrations-stage">
          <!-- Slide 1: DJ Night -->
          <div class="celebrations-stage-img-wrap is-visible" data-id="dj-night">
            <img src="assets/images/cafe/cielo-dj-night.jpg" alt="Live DJ Night & Restro Party at Cielo Cafe Vadodara" class="celebrations-stage-img" loading="lazy">
          </div>

          <!-- Slide 2: Private Party -->
          <div class="celebrations-stage-img-wrap" data-id="private-party">
            <img src="assets/images/cafe/cielo-private-party.jpg" alt="Private Birthday Party and Celebration Table at Cielo Cafe" class="celebrations-stage-img" loading="lazy">
          </div>

          <!-- Slide 3: Gatherings -->
          <div class="celebrations-stage-img-wrap" data-id="gatherings">
            <img src="assets/images/cafe/cielo-gatherings.jpg" alt="Intimate Gatherings and Kitty Party by Arched Windows at Cielo" class="celebrations-stage-img" loading="lazy">
          </div>

          <div class="celebrations-stage-overlay"></div>

          <!-- Floating Live Badge -->
          <div class="stage-floating-badge-top">
            <span class="pulse-dot"></span>
            <span>AVAILABLE FOR EXCLUSIVE VENUE HIRE</span>
          </div>

          <!-- Floating Spec Pill Bottom -->
          <div class="stage-floating-spec-bottom">
            <div class="spec-guest-pill">
              <div class="spec-guest-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
              </div>
              <div>
                <span class="spec-guest-label">Guest Capacity</span>
                <span id="specGuestValue" class="spec-guest-value">Up to 60 Guests</span>
              </div>
            </div>

            <div class="stage-soundwave-wrap">
              <div class="soundwave-bars" aria-hidden="true" title="Dynamic Beat Visualizer">
                <span class="soundwave-bar"></span>
                <span class="soundwave-bar"></span>
                <span class="soundwave-bar"></span>
                <span class="soundwave-bar"></span>
                <span class="soundwave-bar"></span>
                <span class="soundwave-bar"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Information & Action Card (Right) -->
        <div class="celebrations-card">
          <!-- Panel 1: DJ Night -->
          <div class="celebrations-content-panel is-active" data-id="dj-night">
            <div class="panel-header-badge">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
              </svg>
              <span>Vibrant Restro Energy</span>
            </div>
            <h3 class="panel-title">Live DJ & Weekend Music Evenings</h3>
            <p class="panel-desc">
              Electrify your celebration with pro DJ console space, high-fidelity sound, and club-inspired ambient lighting. Perfect for milestone birthdays, graduation parties, and private music jams under the Cielo sky.
            </p>

            <div class="panel-tags-grid">
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>PRO DJ SETUP READY</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>HI-FIDELITY AUDIO</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>DANCE & LOUNGE ZONES</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>MOCKTAIL PITCHERS</span>
            </div>

            <div class="panel-amenities-row">
              <div class="amenity-item">
                <div class="amenity-icon-box">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/>
                  </svg>
                </div>
                <span class="amenity-text">Wireless Mics & Aux Input</span>
              </div>
              <div class="amenity-item">
                <div class="amenity-icon-box">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/>
                  </svg>
                </div>
                <span class="amenity-text">Tapas & Finger-Food Platters</span>
              </div>
            </div>

            <div class="panel-action-wrap">
              <a href="https://wa.me/<?= e($business['whatsappNumber']) ?>?text=<?= urlencode("Hello Cielo Cafe! I would like to enquire about hosting a Live DJ Night / Party for 40-60 guests.") ?>" target="_blank" rel="noopener noreferrer" class="btn-book-celebration">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/>
                  <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>
                </svg>
                Enquire for DJ Party on WhatsApp
              </a>
              <span class="panel-helper-text">Sound setup & dedicated technician included. Instant response.</span>
            </div>
          </div>

          <!-- Panel 2: Private Party -->
          <div class="celebrations-content-panel" data-id="private-party">
            <div class="panel-header-badge">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
              <span>Celebratory Milestones</span>
            </div>
            <h3 class="panel-title">Private Parties, Birthdays & Anniversaries</h3>
            <p class="panel-desc">
              Celebrate in style with long-table Italian feasts under fairy lights, customized multi-course menus, cake cutting rituals, and dedicated hospitality captains for your special occasions.
            </p>

            <div class="panel-tags-grid">
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>CUSTOM CAKE CEREMONY</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>FLORAL TABLE STYLING</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>MULTI-COURSE DINING</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>PHOTO BACKDROP ARCH</span>
            </div>

            <div class="panel-amenities-row">
              <div class="amenity-item">
                <div class="amenity-icon-box">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                  </svg>
                </div>
                <span class="amenity-text">Dedicated Captain & Staff</span>
              </div>
              <div class="amenity-item">
                <div class="amenity-icon-box">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                  </svg>
                </div>
                <span class="amenity-text">Curated Background Playlists</span>
              </div>
            </div>

            <div class="panel-action-wrap">
              <a href="https://wa.me/<?= e($business['whatsappNumber']) ?>?text=<?= urlencode("Hello Cielo Cafe! I would like to plan a Private Birthday Party / Celebration for approx 25 guests.") ?>" target="_blank" rel="noopener noreferrer" class="btn-book-celebration">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/>
                  <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>
                </svg>
                Plan Private Celebration on WhatsApp
              </a>
              <span class="panel-helper-text">Packages start from 15 guests. Custom Italian food courses.</span>
            </div>
          </div>

          <!-- Panel 3: Small Gatherings -->
          <div class="celebrations-content-panel" data-id="gatherings">
            <div class="panel-header-badge">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/>
              </svg>
              <span>Intimate & Unhurried</span>
            </div>
            <h3 class="panel-title">Small Gatherings, Kitty Parties & Brunches</h3>
            <p class="panel-desc">
              Sunlit arched corners, cozy cane seating, and warm artisanal aromas. Host intimate reunions, book clubs, team catch-ups, or lively kitty parties over specialty coffees and freshly baked pizzas.
            </p>

            <div class="panel-tags-grid">
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>RESERVED ARCH SEATING</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>ARTISANAL COLD BREWS</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>FRESH SOURDOUGH PIZZAS</span>
              <span class="panel-tag-pill"><span class="panel-tag-dot"></span>HIGH-SPEED WI-FI</span>
            </div>

            <div class="panel-amenities-row">
              <div class="amenity-item">
                <div class="amenity-icon-box">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                </div>
                <span class="amenity-text">Flexible Hours & Seating</span>
              </div>
              <div class="amenity-item">
                <div class="amenity-icon-box">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
                  </svg>
                </div>
                <span class="amenity-text">Individual Bill Splitting</span>
              </div>
            </div>

            <div class="panel-action-wrap">
              <a href="https://wa.me/<?= e($business['whatsappNumber']) ?>?text=<?= urlencode("Hello Cielo Cafe! I would like to book a table for an Intimate Gathering / Kitty Party for 10-15 guests.") ?>" target="_blank" rel="noopener noreferrer" class="btn-book-celebration">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/>
                  <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>
                </svg>
                Reserve Gathering on WhatsApp
              </a>
              <span class="panel-helper-text">Ideal for 8 to 25 guests. Zero hassle reservation.</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- 6. 3D Infinite Gallery (Default Experience) -->
  <section id="gallery" class="gallery-section gallery-section-3d">
    <div class="container">
      <div class="gallery-header" style="margin-bottom: 2rem;">
        <div>
          <span class="eyebrow" style="color: var(--accent-sky-deep);">VISUAL DIARY</span>
          <h2 class="gallery-title">Moments at Cielo</h2>
          <p class="gallery-subtitle">
            An interactive 3D flight through sunlit morning coffees, freshly tossed sourdough, and ambient twilight feasts.
          </p>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
          <a href="<?= e($gallery['instagramUrl']) ?>" target="_blank" rel="noopener noreferrer" class="btn-outline" style="font-size: 0.75rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-sky-deep);">
              <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
              <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
            </svg>
            Follow on Instagram (<?= e($gallery['instagramHandle']) ?>)
          </a>
        </div>
      </div>

      <div id="gallery3d" class="gallery3d" data-config='<?= htmlspecialchars($galleryConfigJson, ENT_QUOTES, 'UTF-8') ?>'>
        <div id="gallery3dCanvas" class="gallery3d__canvas"></div>

        <!-- Center Big Gray Title Overlay in Black Background -->
        <div class="gallery-title-overlay">
          <h2>Moments at <span>Cielo</span></h2>
        </div>

        <!-- Interaction Hint Cue -->
        <div class="gallery-hint-cue">
          <p>Scroll, drag or swipe to explore the 3D gallery</p>
          <p class="gallery-hint__dim">Hover over photos to trigger wave animation</p>
        </div>

        <!-- WebGL Fallback Grid -->
        <div class="gallery3d__fallback">
          <div class="gallery3d__grid">
            <?php foreach ($gallery3dImages as $img): ?>
              <img src="<?= e($img['src']) ?>" alt="<?= e($img['alt']) ?>" loading="lazy">
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 7. Diner Reviews Carousel / Grid -->
  <section id="reviews" class="reviews-section">
    <div class="container">
      <div class="reviews-header">
        <div>
          <span class="eyebrow" style="color: var(--accent-terracotta);">COMMUNITY VOICES</span>
          <h2 class="reviews-title">Words From Our Diners</h2>
          <div class="rating-summary-bar">
            <div class="stars-row">
              <?php for ($s = 0; $s < 5; $s++): ?>
                <svg class="star-icon" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <?php endfor; ?>
            </div>
            <span class="rating-score"><?= e((string)$reviews['averageRating']) ?> / 5.0 Rating</span>
            <span class="rating-count">(<?= e($reviews['totalReviewsCount']) ?> Google Reviews)</span>
          </div>
        </div>

        <div>
          <a href="<?= e($reviews['googleReviewLink']) ?>" target="_blank" rel="noopener noreferrer" class="btn-primary" style="font-size: 0.75rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Review Us on Google
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
            </svg>
          </a>
        </div>
      </div>

      <div class="reviews-grid">
        <?php foreach (array_slice($reviews['reviews'], 0, 3) as $rev): ?>
          <div class="review-card">
            <div>
              <div class="review-card-top">
                <div class="stars-row">
                  <?php for ($i = 0; $i < ($rev['rating'] ?? 5); $i++): ?>
                    <svg class="star-icon" viewBox="0 0 24 24" style="width: 0.875rem; height: 0.875rem;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                  <?php endfor; ?>
                </div>
                <span class="review-date"><?= e($rev['date']) ?></span>
              </div>
              <p class="review-quote">&ldquo;<?= e($rev['text']) ?>&rdquo;</p>
            </div>

            <div class="review-card-footer">
              <div>
                <h4 class="review-author"><?= e($rev['author']) ?></h4>
                <span class="review-badge"><?= e($rev['badge']) ?></span>
              </div>
              <?php if (!empty($rev['dishMentioned'])): ?>
                <span class="review-dish-tag" title="<?= e($rev['dishMentioned']) ?>"><?= e($rev['dishMentioned']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 8. Find Us & Embedded Google Map Section -->
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

  <!-- 9. Newsletter Band (The Cielo Club) -->
  <section class="newsletter-section">
    <div class="container">
      <div class="newsletter-box">
        <div class="newsletter-eyebrow-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-sky);">
            <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
          </svg>
          <span class="eyebrow">THE CIELO CLUB</span>
        </div>

        <h2 class="newsletter-heading">Get Our Latest Offers and Event Invites</h2>

        <p class="newsletter-desc">
          Be the first to hear about seasonal tasting menus, acoustic live evenings, and exclusive dining privileges in Vadodara.
        </p>

        <!-- Success Alert (Toggled via JS) -->
        <div id="newsletterSuccessAlert" class="newsletter-success-alert" style="display: none;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          <p>Thank you for subscribing! You are on our VIP guestlist for seasonal menus and private invites.</p>
        </div>

        <!-- Subscription Form -->
        <form id="newsletterForm" class="newsletter-form">
          <div class="newsletter-input-group">
            <div class="newsletter-field-wrapper">
              <svg class="newsletter-field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
              <input type="email" id="newsletterEmailInput" class="newsletter-input" required placeholder="Enter your email address">
            </div>
            <button type="submit" class="btn-terracotta newsletter-submit-btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              Subscribe
            </button>
          </div>
          <p class="newsletter-privacy-text">We respect your inbox. Unsubscribe anytime with a single click.</p>
        </form>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
