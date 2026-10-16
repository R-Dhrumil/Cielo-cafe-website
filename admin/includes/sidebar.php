<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Admin Left Navigation Sidebar
 */

use App\Models\Order;

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$activeNav = $activeNav ?? match ($currentScript) {
    'orders.php' => 'orders',
    'cms.php' => 'cms',
    'tables_print.php' => 'tables',
    default => 'orders'
};

$activeOrdersCount = count(Order::where('`status` IN ("placed", "preparing", "ready")'));
$staffUser = $staff ?? (\App\Services\AuthService::user() ?? ['name' => 'Staff Member', 'role' => 'staff']);
?>
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
