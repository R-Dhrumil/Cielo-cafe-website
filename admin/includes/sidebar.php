<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Admin Left Navigation Sidebar
 */

use App\Models\Order;

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$activeNav = $activeNav ?? match ($currentScript) {
    'orders.php' => 'orders',
    'menu.php' => 'menu',
    'reports.php' => 'reports',
    'reviews.php' => 'reviews',
    'cms.php' => 'cms',
    'staff.php' => 'staff',
    'tables_print.php' => 'tables',
    default => 'orders'
};

$activeOrdersCount = count(Order::where('`status` IN ("placed", "preparing", "ready")'));
$staffUser = $staff ?? (\App\Services\AuthService::user() ?? ['name' => 'Staff Member', 'role' => 'staff']);
?>
<style>
/* Universal Responsive Admin Sidebar & Mobile Topbar Styles */
.admin-sidebar {
  scrollbar-width: thin;
  scrollbar-color: rgba(247, 244, 238, 0.2) transparent;
}
.sidebar-nav-container::-webkit-scrollbar {
  width: 5px;
}
.sidebar-nav-container::-webkit-scrollbar-track {
  background: transparent;
}
.sidebar-nav-container::-webkit-scrollbar-thumb {
  background: rgba(247, 244, 238, 0.2);
  border-radius: 999px;
}
.sidebar-nav-container::-webkit-scrollbar-thumb:hover {
  background: rgba(247, 244, 238, 0.4);
}
@media (max-width: 900px) {
  .admin-mobile-topbar {
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    padding: 0.85rem 1.25rem;
    background: #1A1412;
    color: #F7F4EE;
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 999;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  }
  .admin-sidebar {
    transform: translateX(-100%) !important;
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    z-index: 10000 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    bottom: 0 !important;
    height: 100vh !important;
  }
  .admin-sidebar.open {
    transform: translateX(0) !important;
  }
  .admin-sidebar-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(26,20,18,0.55);
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
    z-index: 9999;
  }
  .admin-sidebar-backdrop.open {
    display: block !important;
  }
  .admin-main {
    margin-left: 0 !important;
    width: 100% !important;
    padding: 1.25rem 1rem !important;
    box-sizing: border-box;
  }
}
@media (min-width: 901px) {
  .admin-mobile-topbar, .admin-sidebar-backdrop {
    display: none !important;
  }
}
</style>

<!-- Mobile Sticky Topbar for Tablet/Phone Screens -->
<div class="admin-mobile-topbar" style="display: none;">
  <div style="display: flex; align-items: center; gap: 0.75rem;">
    <button type="button" onclick="toggleAdminSidebar()" style="background: rgba(247,244,238,0.1); border: 1px solid rgba(247,244,238,0.15); color: #F7F4EE; border-radius: 8px; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; cursor: pointer;" aria-label="Open navigation menu">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <span style="font-family: 'Fraunces', serif; font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; color: #F7F4EE;">Cielo Studio</span>
  </div>

  <div style="display: flex; align-items: center; gap: 0.65rem;">
    <?php if ($activeOrdersCount > 0): ?>
      <span style="background: #C96A52; color: #FFFFFF; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 999px;">
        <?= $activeOrdersCount ?> Orders
      </span>
    <?php endif; ?>
    <div style="width: 32px; height: 32px; border-radius: 50%; background: #C96A52; color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
      <?= strtoupper(substr((string)($staffUser['name'] ?? 'U'), 0, 1)) ?>
    </div>
  </div>
</div>

<!-- Backdrop Overlay for Mobile Sidebar -->
<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" onclick="closeAdminSidebar()"></div>

<aside class="admin-sidebar" id="adminSidebar">
  
  <!-- Sidebar Header: Brand & Tagline -->
  <div class="sidebar-header">
    <a href="/admin/orders.php" class="sidebar-brand">
      <div class="sidebar-logo-icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
          <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
          <line x1="6" y1="1" x2="6" y2="4"/>
          <line x1="10" y1="1" x2="10" y2="4"/>
          <line x1="14" y1="1" x2="14" y2="4"/>
        </svg>
      </div>
      <div class="sidebar-brand-text">
        <span class="sidebar-title">CIELO</span>
        <span class="sidebar-sub">POS & Admin Studio</span>
      </div>
    </a>
  </div>

  <!-- Navigation Links -->
  <div class="sidebar-nav-container">
    <div class="sidebar-section-label">Operations</div>
    <nav class="sidebar-nav">
      
      <!-- Live Orders (POS) -->
      <a href="/admin/orders.php" class="sidebar-link <?= $activeNav === 'orders' ? 'active' : '' ?>">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
          </svg>
          <span>Live Orders (POS)</span>
        </div>
        <?php if ($activeOrdersCount > 0): ?>
          <span class="sidebar-badge-pulse"><?= $activeOrdersCount ?></span>
        <?php endif; ?>
      </a>

      <!-- Kitchen Display Screen (KDS) -->
      <a href="/kitchen/index.php" target="_blank" class="sidebar-link">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2v4"/>
            <path d="M6.3 3.7l2.8 2.8"/>
            <path d="M17.7 3.7l-2.8 2.8"/>
            <path d="M18 10h1a4 4 0 0 1 0 8h-1"/>
            <path d="M2 10h16v7a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4v-7z"/>
          </svg>
          <span>Kitchen KDS</span>
        </div>
        <span class="sidebar-link-external">↗</span>
      </a>

      <div class="sidebar-section-label" style="margin-top: 1.5rem;">Management</div>

      <!-- Menu & Stock Studio -->
      <a href="/admin/menu.php" class="sidebar-link <?= $activeNav === 'menu' ? 'active' : '' ?>">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 20V10"/>
            <path d="M12 20V4"/>
            <path d="M6 20v-6"/>
          </svg>
          <span>Menu & Stock Studio</span>
        </div>
      </a>

      <!-- Sales & GST Reports -->
      <a href="/admin/reports.php" class="sidebar-link <?= $activeNav === 'reports' ? 'active' : '' ?>">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
          <span>Sales & GST Reports</span>
        </div>
      </a>

      <!-- Customer Reviews Moderation -->
      <?php $pendingReviewsCount = \App\Models\Review::countPending(); ?>
      <a href="/admin/reviews.php" class="sidebar-link <?= $activeNav === 'reviews' ? 'active' : '' ?>">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
          </svg>
          <span>Customer Reviews</span>
        </div>
        <?php if ($pendingReviewsCount > 0): ?>
          <span class="sidebar-badge-pulse" style="background: var(--accent-terracotta);"><?= $pendingReviewsCount ?></span>
        <?php endif; ?>
      </a>

      <!-- Website Dynamic CMS Studio -->
      <a href="/admin/cms.php" class="sidebar-link <?= $activeNav === 'cms' ? 'active' : '' ?>">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          <span>Website CMS Studio</span>
        </div>
      </a>

      <!-- Staff & Roles -->
      <a href="/admin/staff.php" class="sidebar-link <?= $activeNav === 'staff' ? 'active' : '' ?>">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          <span>Staff Team & Roles</span>
        </div>
      </a>

      <!-- Table QR Stands & Printouts -->
      <a href="/admin/tables_print.php" target="_blank" class="sidebar-link <?= $activeNav === 'tables' ? 'active' : '' ?>">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"/>
            <rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/>
          </svg>
          <span>Table QR Standees</span>
        </div>
        <span class="sidebar-link-external">↗</span>
      </a>

      <!-- Public Site Preview -->
      <a href="/index.php" target="_blank" class="sidebar-link">
        <div class="sidebar-link-left">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="2" y1="12" x2="22" y2="12"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
          </svg>
          <span>View Live Cafe Site</span>
        </div>
        <span class="sidebar-link-external">↗</span>
      </a>

    </nav>
  </div>

  <!-- Sidebar Footer: Logged in Staff & Sign Out -->
  <div class="sidebar-footer">
    <div class="staff-card">
      <div class="staff-avatar">
        <?= strtoupper(substr((string)($staffUser['name'] ?? 'U'), 0, 1)) ?>
      </div>
      <div class="staff-info">
        <div class="staff-name"><?= e($staffUser['name'] ?? 'Admin User') ?></div>
        <div class="staff-role-badge">
          <span class="role-indicator"></span>
          <span><?= ucfirst((string)($staffUser['role'] ?? 'Staff')) ?></span>
        </div>
      </div>
    </div>
    
    <a href="/admin/logout.php" class="btn-sidebar-signout" title="Sign out of staff portal">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      <span>Sign Out</span>
    </a>
  </div>
</aside>

<script>
function toggleAdminSidebar() {
  var sb = document.getElementById('adminSidebar');
  var bd = document.getElementById('adminSidebarBackdrop');
  if (sb) sb.classList.toggle('open');
  if (bd) bd.classList.toggle('open');
}
function closeAdminSidebar() {
  var sb = document.getElementById('adminSidebar');
  var bd = document.getElementById('adminSidebarBackdrop');
  if (sb) sb.classList.remove('open');
  if (bd) bd.classList.remove('open');
}
</script>
