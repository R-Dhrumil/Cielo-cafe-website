<?php
/**
 * Cielo Cafe and Restro - Dedicated Photo Gallery Page with 3D Infinite Canvas
 */
require_once __DIR__ . '/includes/config.php';

$pageTitle = "Visual Diary & 3D Gallery | Cielo Cafe and Restro, Vadodara";
$pageDesc = "Explore photos of handcrafted pizzas, estate espresso brews, and cozy ambient cafe interiors at Cielo Cafe and Restro, Vadodara in an interactive 3D canvas.";
$pageCss = ['gallery.css', 'gallery-3d.css'];
$pageJs = [
    'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
    'lightbox.js',
    'gallery-3d.js'
];

require __DIR__ . '/includes/header.php';

// Prepare images and config for 3D Infinite Gallery
$gallery3dImages = [];
if (!empty($gallery['items']) && is_array($gallery['items'])) {
    foreach ($gallery['items'] as $item) {
        $gallery3dImages[] = [
            'src' => asset_url($item['image']),
            'alt' => $item['title']
        ];
    }
}

// Add extra cafe ambience photos to make 3D infinite gallery even richer
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
$configJson = json_encode($galleryConfig, JSON_UNESCAPED_SLASHES);
?>

<main id="main-content" style="padding-top: 5rem;">
  <!-- 3D Infinite Gallery Stage -->
  <section class="gallery-page-container">
    <div class="container">
      <div id="gallery3d" class="gallery3d" data-config='<?= htmlspecialchars($configJson, ENT_QUOTES, 'UTF-8') ?>'>
        <div id="gallery3dCanvas" class="gallery3d__canvas"></div>

        <!-- Center Title Overlay in Black Background -->
        <div class="gallery-title-overlay">
          <h1>Moments at <span>Cielo</span></h1>
        </div>

        <!-- Interaction Hint Cue (Desktop) -->
        <div class="gallery-hint-cue">
          <p>Scroll, drag or swipe to explore the 3D gallery</p>
          <p class="gallery-hint__dim">Hover over photos to trigger wave animation</p>
        </div>

        <!-- Mobile Interactive Mode Trigger & Done Controls -->
        <div class="gallery3d-mobile-controls" id="galleryMobileControls" aria-label="3D gallery mobile controls">
          <button type="button" class="btn-gallery-unlock" id="galleryUnlockBtn" aria-label="Tap to interact with 3D gallery">
            <span class="unlock-pulse" aria-hidden="true"></span>
            <span>Tap to Explore 3D</span>
          </button>

          <div class="gallery-active-bar" id="galleryActiveBar" style="display: none;">
            <button type="button" class="btn-gallery-nav" id="galleryNavPrev" aria-label="Previous photo">‹</button>
            <button type="button" class="btn-gallery-done" id="galleryDoneBtn" aria-label="Done exploring, resume page scroll">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color: #34d399;">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span>Done · Scroll Page</span>
            </button>
            <button type="button" class="btn-gallery-nav" id="galleryNavNext" aria-label="Next photo">›</button>
          </div>
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

  <!-- High-Res Gallery Grid with Lightbox Modal -->
  <section id="gallery" class="gallery-section">
    <div class="container">
      <div class="gallery-header">
        <div>
          <span class="eyebrow" style="color: var(--accent-sky-deep);">EXPLORE</span>
          <h2 class="gallery-title">Every Dish Tells a Story</h2>
          <p class="gallery-subtitle" style="margin-top: 0.5rem;">
            Click on any dish or moment to view full high-resolution details in the lightbox.
          </p>
        </div>
        <div>
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

      <div class="gallery-grid">
        <?php foreach ($gallery['items'] as $gItem): ?>
          <div class="gallery-card" data-title="<?= e($gItem['title']) ?>" data-category="<?= e($gItem['category']) ?>" data-caption="<?= e($gItem['caption']) ?>" data-image="<?= e(asset_url($gItem['image'])) ?>">
            <img src="<?= e(asset_url($gItem['image'])) ?>" alt="<?= e($gItem['title']) ?>" loading="lazy">
            <div class="gallery-card-overlay">
              <span class="gallery-zoom-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
                </svg>
              </span>
              <div class="gallery-card-meta">
                <span class="gallery-card-category"><?= e($gItem['category']) ?></span>
                <h3 class="gallery-card-title"><?= e($gItem['title']) ?></h3>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
