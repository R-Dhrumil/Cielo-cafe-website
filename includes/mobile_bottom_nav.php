<?php

declare(strict_types=1);

/**
 * Cielo Cafe & Restro - Mobile Bottom App Navigation Bar (Thumb Dock)
 * Luxury mobile-first sticky navigation for smartphones and tablets (< 768px).
 */

use App\Services\TableSessionService;
use App\Services\CustomerAuthService;

$navActiveTable = class_exists(TableSessionService::class) ? TableSessionService::getActiveTable() : null;
$navUser = class_exists(CustomerAuthService::class) ? CustomerAuthService::getCurrentUser() : ($_SESSION['cielo_user'] ?? null);

$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$currentPath = trim(strtolower((string)$currentUri), '/');

$isNavHome = ($currentPath === '' || $currentPath === 'home' || $currentPath === 'index.php');
$isNavMenu = ($currentPath === 'menu' || $currentPath === 'menu.php');
$isNavProfile = ($currentPath === 'profile' || $currentPath === 'profile.php' || $currentPath === 'club');
$isNavOrder = ($currentPath === 'order-status' || $currentPath === 'order_status' || $currentPath === 'order_status.php' || $currentPath === 'checkout');

// Hide generic bottom nav on checkout flow (dedicated sticky payment CTA dock is used instead)
if ($currentPath === 'checkout' || $currentPath === 'checkout.php') {
    return;
}
?>

<!-- Scoped Bulletproof Rules: Mobile Bottom Dock is NEVER displayed on desktop (>=768px) under any circumstance -->
<style>
#cieloMobileBottomNav {
  display: none !important;
}
@media (max-width: 767.98px) {
  #cieloMobileBottomNav {
    display: block !important;
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 998 !important;
    background: rgba(255, 255, 255, 0.96) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
    box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.08) !important;
    padding: 0.35rem 0.5rem !important;
    padding-bottom: max(0.4rem, env(safe-area-inset-bottom, 0px)) !important;
  }
}
#cieloMobileBottomNav .mobile-nav-icon {
  width: 22px !important;
  height: 22px !important;
  max-width: 22px !important;
  max-height: 22px !important;
  display: block !important;
}
#cieloMobileBottomNav .mobile-nav-item,
#cieloMobileBottomNav .mobile-nav-item:visited {
  color: rgba(26, 20, 18, 0.65) !important;
  text-decoration: none !important;
}
#cieloMobileBottomNav .mobile-nav-item.is-active {
  color: #B5623B !important;
}
#cieloMobileBottomNav .mobile-nav-item.is-seated {
  color: #2E7D32 !important;
}
</style>

<!-- Mobile App Bottom Navigation Bar (< 768px) -->
<nav id="cieloMobileBottomNav" class="cielo-mobile-bottom-nav" aria-label="Mobile Navigation" style="display: none;">
  <div class="mobile-nav-container">
    
    <!-- 1. Home / Explore -->
    <a href="/" class="mobile-nav-item <?= $isNavHome ? 'is-active' : '' ?>">
      <span class="mobile-nav-icon-wrap">
        <svg class="mobile-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px; max-width:22px; max-height:22px;">
          <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </span>
      <span class="mobile-nav-label">Home</span>
    </a>

    <!-- 2. Menu Catalog -->
    <a href="/menu" class="mobile-nav-item <?= $isNavMenu ? 'is-active' : '' ?>">
      <span class="mobile-nav-icon-wrap">
        <svg class="mobile-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px; max-width:22px; max-height:22px;">
          <path d="M18 2v6a3 3 0 0 1-3 3 3 3 0 0 1-3-3V2"/>
          <path d="M15 2v19"/>
          <path d="M7 2v20"/>
          <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
        </svg>
      </span>
      <span class="mobile-nav-label">Menu</span>
    </a>

    <!-- 3. Active Table / Dine-In Session -->
    <?php if ($navActiveTable): ?>
      <a href="/menu" class="mobile-nav-item mobile-nav-table is-seated" title="Active Table <?= e($navActiveTable['table_number']) ?>">
        <span class="mobile-nav-icon-wrap">
          <span class="table-nav-pulse-dot" aria-hidden="true"></span>
          <svg class="mobile-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px; max-width:22px; max-height:22px;">
            <rect width="18" height="12" x="3" y="4" rx="2"/>
            <line x1="6" x2="6" y1="16" y2="20"/>
            <line x1="18" x2="18" y1="16" y2="20"/>
          </svg>
        </span>
        <span class="mobile-nav-label">Table <?= e($navActiveTable['table_number']) ?></span>
      </a>
    <?php else: ?>
      <button type="button" class="mobile-nav-item btn-open-reservation" title="Reserve a table">
        <span class="mobile-nav-icon-wrap">
          <svg class="mobile-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px; max-width:22px; max-height:22px;">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
            <line x1="16" x2="16" y1="2" y2="6"/>
            <line x1="8" x2="8" y1="2" y2="6"/>
            <line x1="3" x2="21" y1="10" y2="10"/>
            <circle cx="12" cy="15" r="1.5" fill="currentColor"/>
          </svg>
        </span>
        <span class="mobile-nav-label">Book Table</span>
      </button>
    <?php endif; ?>

    <!-- 4. Slide-out Cart Trigger with Live Badge -->
    <button type="button" class="mobile-nav-item mobile-nav-cart" onclick="window.CieloCart ? window.CieloCart.openCartDrawer() : (window.location.href='/menu')" aria-label="Open Cart">
      <span class="mobile-nav-icon-wrap">
        <svg class="mobile-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px; max-width:22px; max-height:22px;">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
          <path d="M3 6h18"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <span id="mobileNavCartBadge" class="mobile-nav-badge" style="display: none;">0</span>
      </span>
      <span class="mobile-nav-label">Order</span>
    </button>

    <!-- 5. Patron Club & Loyalty Profile -->
    <a href="/profile" class="mobile-nav-item <?= $isNavProfile ? 'is-active' : '' ?>">
      <span class="mobile-nav-icon-wrap">
        <?php if ($navUser): ?>
          <span class="profile-logged-star">★</span>
        <?php else: ?>
          <svg class="mobile-nav-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px; max-width:22px; max-height:22px;">
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        <?php endif; ?>
      </span>
      <span class="mobile-nav-label"><?= $navUser ? 'Rewards' : 'Club' ?></span>
    </a>

  </div>
</nav>
