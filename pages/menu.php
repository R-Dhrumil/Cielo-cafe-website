<?php
/**
 * Cielo Cafe and Restro - Dedicated Menu Page
 */
require_once dirname(__DIR__) . '/includes/config.php';

$pageTitle = "Artisanal Menu | Cielo Cafe and Restro, Vadodara";
$pageDesc = "Browse our Italian-inspired menu featuring 48-hour cold-fermented pizzas, bronze-cut pasta, signature cold brews, and velvety desserts in Vadodara.";
$pageCss = ['menu.css', 'cart-drawer.css'];
$pageJs = ['menu-tabs.js', 'cart-drawer.js'];

require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" style="padding-top: 5rem;">
  <?php require dirname(__DIR__) . '/includes/table_badge.php'; ?>

  <!-- Menu Page Banner -->
  <section class="subpage-hero-banner">
    <div class="container">
      <span class="eyebrow" style="color: var(--accent-sky);">CIELO RESTRO & CAFE</span>
      <h1 class="subpage-hero-title">
        Artisanal Dining Menu
      </h1>
      <p class="subpage-hero-subtitle">
        From 18-hour cold brews and crispy crostinis to 48-hour fermented crust pizzas and classic Venetian tiramisu. Handcrafted with honest ingredients in Vadodara.
      </p>
    </div>
  </section>

  <!-- Signature Menu Section -->
  <section id="menu" class="menu-section">
    <div class="container">

      <!-- Mobile-First Menu Controls: Live Search & Dietary Filters -->
      <div class="menu-controls-wrapper">
        <div class="menu-search-box">
          <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
          </svg>
          <input type="search" id="menuSearchInput" class="menu-search-input" placeholder="Search coffee, sourdough pizza, pasta, tiramisu..." autocomplete="off">
          <button type="button" id="menuSearchClear" class="menu-search-clear" style="display: none;" aria-label="Clear search">&times;</button>
        </div>

        <div class="menu-filters-row">
          <button type="button" id="menuVegToggle" class="menu-filter-chip" data-active="false">
            <span class="veg-chip-indicator"></span>
            <span>Veg Only</span>
          </button>

          <button type="button" id="menuViewToggle" class="menu-filter-chip view-toggle-chip" title="Toggle view between compact list and photo cards">
            <svg class="icon-list-view" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>
              <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
            </svg>
            <span id="menuViewToggleText">Compact View</span>
          </button>
        </div>
      </div>

      <!-- Sticky Category Filter Tabs -->
      <div class="menu-tabs-sticky-wrapper">
        <div class="menu-tabs-nav" id="menuTabsNav">
          <?php foreach ($menu['categories'] as $index => $category): ?>
            <button type="button" class="menu-tab-btn <?= $index === 0 ? 'is-active' : '' ?>" data-category="<?= e($category['id']) ?>" data-desc="<?= e($category['description']) ?>">
              <svg class="menu-tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <?php if ($category['id'] === 'coffee-beverages'): ?>
                  <path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" x2="6" y1="2" y2="4"/><line x1="10" x2="10" y1="2" y2="4"/><line x1="14" x2="14" y1="2" y2="4"/>
                <?php elseif ($category['id'] === 'starters'): ?>
                  <path d="M18 2v6a3 3 0 0 1-3 3 3 3 0 0 1-3-3V2"/><path d="M15 2v19"/><path d="M7 2v20"/><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
                <?php elseif ($category['id'] === 'mains'): ?>
                  <path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V21H6Z"/><line x1="6" x2="18" y1="17" y2="17"/>
                <?php elseif ($category['id'] === 'pizza-pasta'): ?>
                  <path d="m19 12-7-10-7 10Z"/><circle cx="12" cy="7" r="1"/><circle cx="10" cy="11" r="1"/><circle cx="14" cy="11" r="1"/><path d="M5 12a7 7 0 0 0 14 0"/>
                <?php elseif ($category['id'] === 'desserts'): ?>
                  <path d="m7 11 5 11 5-11Z"/><path d="M12 2a4 4 0 0 0-4 4c0 1.6 1 3.1 2.3 3.7.5.3 1.1.3 1.4 0A4 4 0 0 0 16 6a4 4 0 0 0-4-4Z"/>
                <?php else: ?>
                  <circle cx="12" cy="12" r="10"/>
                <?php endif; ?>
              </svg>
              <?= e($category['label']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Active Category Description -->
      <p id="activeCategoryDescription" class="category-description">
        <?= e($menu['categories'][0]['description'] ?? '') ?>
      </p>

      <!-- Search & Filter Live Summary Count / Reset -->
      <div id="menuFilterAlert" class="menu-filter-alert" style="display: none;">
        <span id="menuFilterAlertText">Showing search results</span>
        <button type="button" id="menuFilterResetBtn" class="menu-filter-reset">Show All</button>
      </div>

      <!-- Dishes Grid / List Container -->
      <div id="dishGridContainer" class="dish-grid">
        <?php foreach ($menu['items'] as $index => $item): ?>
          <?php
          $dishId = (int)$item['id'];
          $initialDisplay = ($item['categoryId'] === $menu['categories'][0]['id']) ? 'flex' : 'none';
          ?>
          <article class="dish-card" 
                   data-id="<?= $dishId ?>"
                   data-category="<?= e($item['categoryId']) ?>" 
                   data-veg="<?= $item['isVeg'] ? '1' : '0' ?>"
                   data-name="<?= e(strtolower($item['name'])) ?>"
                   data-desc="<?= e(strtolower($item['description'] ?? '')) ?>"
                   style="display: <?= $initialDisplay ?>;">
            
            <div class="dish-card-media" onclick="window.CieloCart.quickAdd(<?= $dishId ?>)" style="cursor: pointer;">
              <img src="<?= e(asset_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" class="dish-card-img" loading="lazy">
              <div class="dish-badges-top">
                <div class="veg-indicator" title="<?= $item['isVeg'] ? 'Vegetarian' : 'Non-Vegetarian' ?>">
                  <span class="veg-indicator-dot <?= $item['isVeg'] ? '' : 'non-veg' ?>"></span>
                </div>
                <?php if (!empty($item['tag'])): ?>
                  <span class="dish-tag"><?= e($item['tag']) ?></span>
                <?php endif; ?>
              </div>
              <div class="dish-price-pill">₹<?= e((string)$item['price']) ?></div>
            </div>

            <div class="dish-card-body">
              <div class="dish-card-main-info">
                <div class="dish-card-header-mobile">
                  <div class="veg-indicator-mobile" title="<?= $item['isVeg'] ? 'Vegetarian' : 'Non-Vegetarian' ?>">
                    <span class="veg-indicator-dot <?= $item['isVeg'] ? '' : 'non-veg' ?>"></span>
                  </div>
                  <h3 class="dish-title"><?= e($item['name']) ?></h3>
                </div>
                <div class="dish-price-mobile">₹<?= e((string)$item['price']) ?></div>
                <p class="dish-desc"><?= e($item['description']) ?></p>
              </div>

              <div class="dish-card-footer">
                <span class="dish-footer-note"><?= !empty($item['tag']) ? e($item['tag']) : 'Handcrafted · Fresh' ?></span>
                <button type="button" class="dish-order-btn" onclick="window.CieloCart.quickAdd(<?= $dishId ?>)" aria-label="Add <?= e($item['name']) ?> to order">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                  </svg>
                  <span>Add</span>
                </button>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <!-- Empty Search Results Placeholder -->
      <div id="menuEmptySearchMsg" class="menu-empty-search" style="display: none;">
        <div class="menu-empty-search-icon">🔍</div>
        <h3 class="menu-empty-search-title">No matching dishes found</h3>
        <p class="menu-empty-search-desc">Try searching for other artisanal items like "Cold Brew", "Sourdough", "Pasta", or clear your filters.</p>
        <button type="button" onclick="clearMenuFilters()" class="btn-primary" style="margin-top: 1rem; padding: 0.65rem 1.5rem; font-size: 0.82rem;">
          Reset Filters
        </button>
      </div>

      <!-- Download Full Menu PDF Link Slot -->
      <div class="menu-download-box">
        <div class="menu-download-card">
          <div class="menu-download-info">
            <span class="menu-download-title">Full Physical Cafe Menu</span>
            <p class="menu-download-text">Browse our complete list of 80+ seasonal dishes, coolers, and specialty bakes.</p>
          </div>
          <a href="<?= e($menu['downloadMenuUrl']) ?>" class="btn-outline" style="font-size: 0.75rem; white-space: nowrap;" download onclick="if(this.getAttribute('href').startsWith('#')) { event.preventDefault(); alert('PDF Menu slot ready! Drop your final menu PDF into /assets and link it.'); }">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Download Full Menu (PDF)
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Interactive Slide-out Cart Drawer & Customizer -->
  <?php require dirname(__DIR__) . '/includes/cart_drawer.php'; ?>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
