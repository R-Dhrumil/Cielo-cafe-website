<?php
/**
 * Cielo Cafe and Restro - Our Story & Philosophy Page
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = "Our Story | Cielo Cafe and Restro, Vadodara";
$pageDesc = "Discover the story behind Cielo Cafe and Restro. Named after the Italian sky, bringing artisanal Italian cooking and vibrant cafe culture to Vadodara.";
$pageCss = ['home.css', 'about.css'];
$pageJs = [];

require __DIR__ . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5rem;">
  <!-- About Hero Banner -->
  <section class="page-hero-banner">
    <div class="container">
      <span class="eyebrow" style="color: var(--accent-sky);">OUR PHILOSOPHY</span>
      <h1 class="page-hero-title">Your Sky, Your Table</h1>
      <p class="page-hero-subtitle">
        Named after the Italian word for &lsquo;Sky&rsquo;, Cielo is an unhurried sanctuary where artisanal coffee, slow-crafted stone-baked pizzas, and heartwarming conversations converge under warm ambient arches.
      </p>
    </div>
  </section>

  <!-- Story & Ingredients Split Sections -->
  <div class="container split-sections">
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

    <!-- Core Values Cards Grid -->
    <div>
      <div style="text-align: center; max-width: 32rem; margin: 0 auto 1.5rem auto;">
        <span class="eyebrow" style="color: var(--accent-terracotta);">CRAFT & CARE</span>
        <h3 class="font-serif" style="font-size: 2.25rem; color: var(--text-espresso); margin-top: 0.25rem;">
          The Pillars of Cielo
        </h3>
      </div>

      <div class="about-values-grid">
        <div class="about-value-card">
          <div class="about-value-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <h4 class="about-value-title">48-Hour Cold Fermentation</h4>
          <p class="about-value-desc">
            We allow our handcrafted sourdough dough to rest and develop naturally for two whole days, resulting in an airy, fragrant, and gentle blistered crust.
          </p>
        </div>

        <div class="about-value-card">
          <div class="about-value-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/>
            </svg>
          </div>
          <h4 class="about-value-title">100% Estate Arabica</h4>
          <p class="about-value-desc">
            Sourced directly from certified ethical estates in South India, freshly roasted in micro-batches to bring out bright citrus and rich chocolate notes.
          </p>
        </div>

        <div class="about-value-card">
          <div class="about-value-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
            </svg>
          </div>
          <h4 class="about-value-title">Unhurried Hospitality</h4>
          <p class="about-value-desc">
            Whether you come with your laptop for a focused morning work session or stay late for candlelit dinner with friends, you are always welcome to linger.
          </p>
        </div>
      </div>
    </div>

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
        <p class="split-paragraphs">
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

        <div style="padding-top: 1rem;">
          <button type="button" class="btn-terracotta btn-open-reservation">
            Reserve Your Experience
          </button>
        </div>
      </div>
    </section>
  </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
