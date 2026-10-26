<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Luxury Dynamic Website CMS Studio with Left Sidebar
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Models\Setting;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Offer;
use App\Core\Security;

// Require owner or admin role
$staff = AuthService::requireRole(['owner', 'admin'], '/admin/login.php');

$msg = '';
$err = '';

// Handle Settings Update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!Security::verifyCsrfToken($csrf)) {
        $err = 'Security session expired. Please refresh and try again.';
    } else {
        $posted = $_POST['settings'] ?? [];

        foreach ($posted as $key => $val) {
            $key = trim((string)$key);

            // Determine type casting
            $type = 'string';
            if (in_array($key, ['gst_enabled', 'announcement_enabled', 'loyalty_enabled', 'payment_counter_cash', 'payment_counter_upi', 'payment_online_razorpay', 'reviews_auto_approve_positive'], true)) {
                $type = 'bool';
                $val = ($val === '1' || $val === 'true' || $val === 1);
            } elseif (in_array($key, ['gst_cgst_percent', 'gst_sgst_percent', 'loyalty_max_redeem_percent'], true)) {
                $type = 'float';
                $val = (float)$val;
            } elseif (in_array($key, ['loyalty_points_per_100', 'loyalty_point_value_paise', 'loyalty_min_redeem_points'], true)) {
                $type = 'int';
                $val = (int)$val;
            } elseif ($key === 'promo_stacking_mode') {
                $type = 'string';
                $val = in_array($val, ['stackable', 'exclusive'], true) ? $val : 'stackable';
            }

            Setting::set($key, $val, 'system', $type);
        }

        AuditLog::log($staff['id'], 'cms_settings_updated', 'settings', null, ['keys_count' => count($posted)]);
        $msg = 'Website CMS content & system configurations saved successfully! Changes reflect on the website immediately.';
    }
}

// Read current settings
$allSettings = Setting::all();
$current = [];
foreach ($allSettings as $s) {
    $current[$s['key']] = Setting::castValue($s['value'], $s['type']);
}

// Read active coupons and automated promotional offers
$couponsList = Coupon::all('id ASC');
$offersList = Offer::all('priority ASC, id ASC');

$csrfToken = Security::generateCsrfToken();
$activeNav = 'cms';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website CMS & Dynamic Studio | Cielo Cafe Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --bg-surface: #FFFFFF;
      --text-espresso: #1A1412;
      --text-muted: #6E6259;
      --accent-terracotta: #C96A52;
      --accent-terracotta-soft: rgba(201, 106, 82, 0.1);
      --accent-sky: #6B9AC4;
      --accent-sky-soft: rgba(107, 154, 196, 0.12);
      --accent-sky-deep: #4A7A9F;
      --border-warm: rgba(26, 20, 18, 0.08);
      --shadow-card: 0 4px 18px rgba(26, 20, 18, 0.04);
      --radius-sm: 8px;
      --radius-md: 14px;
      --radius-lg: 20px;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      background: #F4F1EA;
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
      max-width: 100vw;
    }

    /* Left Sidebar Styles */
    .admin-sidebar {
      width: 260px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: #1A1412;
      color: #F7F4EE;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid rgba(247, 244, 238, 0.08);
      z-index: 1000;
      box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
    }

    .sidebar-header {
      padding: 1.75rem 1.5rem 1.25rem;
      border-bottom: 1px solid rgba(247, 244, 238, 0.06);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      text-decoration: none;
      color: #F7F4EE;
    }

    .sidebar-logo-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: rgba(201, 106, 82, 0.15);
      border: 1px solid rgba(201, 106, 82, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
    }

    .sidebar-title {
      font-family: 'Fraunces', serif;
      font-size: 1.45rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      display: block;
      line-height: 1;
    }

    .sidebar-sub {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-sky);
      font-weight: 600;
      margin-top: 0.25rem;
      display: block;
    }

    .sidebar-nav-container {
      padding: 1.5rem 1rem;
      flex: 1;
      overflow-y: auto;
    }

    .sidebar-section-label {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.14em;
      color: rgba(247, 244, 238, 0.4);
      font-weight: 700;
      padding: 0 0.75rem;
      margin-bottom: 0.6rem;
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .sidebar-link {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 0.85rem;
      border-radius: 12px;
      text-decoration: none;
      color: rgba(247, 244, 238, 0.75);
      font-size: 0.88rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .sidebar-link:hover {
      color: #F7F4EE;
      background: rgba(247, 244, 238, 0.08);
    }

    .sidebar-link.active {
      color: #FFFFFF;
      background: var(--accent-terracotta);
      box-shadow: 0 4px 15px rgba(201, 106, 82, 0.35);
    }

    .sidebar-link-left {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .sidebar-badge-pulse {
      background: var(--accent-sky-deep);
      color: #FFFFFF;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.15rem 0.55rem;
      border-radius: 999px;
    }

    .sidebar-link.active .sidebar-badge-pulse {
      background: #FFFFFF;
      color: var(--accent-terracotta);
    }

    .sidebar-link-external {
      font-size: 0.85rem;
      opacity: 0.5;
    }

    .sidebar-footer {
      padding: 1.25rem;
      border-top: 1px solid rgba(247, 244, 238, 0.06);
      background: rgba(0, 0, 0, 0.25);
    }

    .staff-card {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .staff-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--accent-sky-deep);
      color: #FFFFFF;
      font-weight: 700;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .staff-info {
      display: flex;
      flex-direction: column;
    }

    .staff-name {
      font-size: 0.86rem;
      font-weight: 700;
      color: #F7F4EE;
      line-height: 1.2;
    }

    .staff-role-badge {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.72rem;
      color: rgba(247, 244, 238, 0.6);
      margin-top: 2px;
    }

    .role-indicator {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #2D6A4F;
      box-shadow: 0 0 0 2px rgba(45, 106, 79, 0.4);
    }

    .btn-sidebar-signout {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      width: 100%;
      padding: 0.65rem;
      border-radius: 10px;
      background: rgba(201, 106, 82, 0.15);
      border: 1px solid rgba(201, 106, 82, 0.3);
      color: #EFA694;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .btn-sidebar-signout:hover {
      background: var(--accent-terracotta);
      color: #FFFFFF;
    }

    /* Right Content Area - Zero Horizontal Overflow */
    .admin-main-wrapper {
      margin-left: 260px;
      flex: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      width: calc(100% - 260px);
      max-width: calc(100% - 260px);
      min-width: 0;
      overflow-x: hidden;
      box-sizing: border-box;
    }

    /* Top Action Bar */
    .admin-topbar {
      background: #FFFFFF;
      border-bottom: 1px solid var(--border-warm);
      padding: 1.15rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 900;
      box-shadow: 0 2px 10px rgba(26, 20, 18, 0.02);
      width: 100%;
      box-sizing: border-box;
    }

    .topbar-title-wrap {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .topbar-heading {
      font-family: 'Fraunces', serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      line-height: 1.1;
    }

    .topbar-subtext {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .topbar-right-tools {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-shrink: 0;
    }

    /* Content Area */
    .cms-content-area {
      padding: 1.75rem 2rem 6rem;
      flex: 1;
      min-width: 0;
      max-width: 100%;
      box-sizing: border-box;
    }

    .toast-alert {
      background: #FFFFFF;
      border-left: 4px solid var(--accent-sky);
      border-radius: 12px;
      padding: 1rem 1.5rem;
      margin-bottom: 1.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: var(--shadow-card);
      font-size: 0.92rem;
      font-weight: 500;
    }

    .toast-alert.error {
      border-left-color: var(--accent-terracotta);
      color: var(--accent-terracotta);
    }

    /* Studio Layout (Adaptive 2-Column with Zero Overflow) */
    .studio-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 350px;
      gap: 1.75rem;
      align-items: start;
      width: 100%;
      max-width: 100%;
      min-width: 0;
      box-sizing: border-box;
    }

    .form-sections {
      min-width: 0;
      max-width: 100%;
      width: 100%;
    }

    /* Form Section Cards */
    .cms-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      padding: 1.75rem 2rem;
      margin-bottom: 1.75rem;
      transition: all 0.2s ease;
      min-width: 0;
      max-width: 100%;
      box-sizing: border-box;
      overflow: hidden;
    }

    .cms-card:hover {
      border-color: rgba(107, 154, 196, 0.3);
    }

    .section-header {
      border-bottom: 1px solid var(--border-warm);
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .section-icon {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: var(--bg-cream);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
    }

    .section-title {
      font-family: 'Fraunces', serif;
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
    }

    .section-desc {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin: 2px 0 0;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1.25rem;
      width: 100%;
      min-width: 0;
      box-sizing: border-box;
    }

    .form-grid.single-col {
      grid-template-columns: minmax(0, 1fr);
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
      min-width: 0;
      max-width: 100%;
      box-sizing: border-box;
    }

    .form-group label {
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-espresso);
      white-space: normal;
      word-break: break-word;
    }

    .form-group input, .form-group textarea, .form-group select {
      width: 100%;
      max-width: 100%;
      box-sizing: border-box;
      min-width: 0;
      padding: 0.8rem 1rem;
      border: 1px solid var(--border-warm);
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.9rem;
      color: var(--text-espresso);
      background: #FAFAFA;
      outline: none;
      transition: all 0.2s ease;
    }

    .form-group select {
      text-overflow: ellipsis;
      white-space: nowrap;
      overflow: hidden;
    }

    .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
      border-color: var(--accent-sky);
      background: #FFFFFF;
      box-shadow: 0 0 0 3px rgba(107, 154, 196, 0.12);
    }

    .help-hint {
      font-size: 0.74rem;
      color: var(--text-muted);
      line-height: 1.4;
      word-break: break-word;
    }

    /* Live Preview Sticky Sidebar */
    .preview-sidebar {
      position: sticky;
      top: 5rem;
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      min-width: 0;
      width: 100%;
      box-sizing: border-box;
    }

    .preview-card {
      background: #FFFFFF;
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      width: 100%;
      box-sizing: border-box;
    }

    .preview-header {
      background: #1A1412;
      color: #F7F4EE;
      padding: 0.85rem 1.25rem;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .preview-body {
      padding: 1.5rem;
      box-sizing: border-box;
    }

    .preview-mock-hero {
      background: radial-gradient(circle at 10% 20%, rgba(107, 154, 196, 0.12) 0%, transparent 40%), var(--bg-cream);
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
      border: 1px dashed var(--border-warm);
      word-break: break-word;
    }

    .mock-badge {
      font-size: 0.65rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-sky-deep);
      font-weight: 700;
      margin-bottom: 0.5rem;
      display: block;
    }

    .mock-title {
      font-family: 'Fraunces', serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      line-height: 1.1;
      word-break: break-word;
    }

    .mock-tagline {
      font-family: 'Fraunces', serif;
      font-size: 1.15rem;
      font-style: italic;
      color: var(--accent-terracotta);
      margin-top: 0.25rem;
      display: block;
      word-break: break-word;
    }

    .mock-btn {
      display: inline-block;
      margin-top: 1rem;
      background: var(--text-espresso);
      color: #FFFFFF;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 0.5rem 1rem;
      border-radius: 999px;
      word-break: break-word;
    }

    .preview-quote-box {
      margin-top: 1rem;
      padding: 1rem;
      background: var(--bg-cream);
      border-radius: 10px;
      font-family: 'Fraunces', serif;
      font-style: italic;
      font-size: 0.88rem;
      color: var(--text-espresso);
      border-left: 3px solid var(--accent-terracotta);
      word-break: break-word;
    }

    /* Floating Save Bar */
    .floating-save-bar {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      z-index: 999;
      filter: drop-shadow(0 10px 25px rgba(26, 20, 18, 0.25));
    }

    .btn-save-primary {
      background: var(--text-espresso);
      color: #FFFFFF;
      border: none;
      padding: 1.1rem 2.25rem;
      border-radius: 999px;
      font-size: 1rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.65rem;
      box-shadow: 0 12px 30px rgba(26, 20, 18, 0.35);
      transition: all 0.2s ease;
    }

    .btn-save-primary:hover {
      background: #2C221F;
      transform: translateY(-2px);
      box-shadow: 0 16px 36px rgba(26, 20, 18, 0.45);
    }

    @media (max-width: 1260px) {
      .studio-layout {
        grid-template-columns: minmax(0, 1fr);
      }
      .preview-sidebar {
        position: static;
        top: auto;
        order: 2;
        width: 100%;
        max-width: 480px;
        margin: 0 auto;
      }
    }

    @media (max-width: 900px) {
      .form-grid {
        grid-template-columns: minmax(0, 1fr);
      }
      .admin-sidebar {
        transform: translateX(-100%);
      }
      .admin-sidebar.open {
        transform: translateX(0);
      }
      .admin-main-wrapper {
        margin-left: 0;
        width: 100%;
        max-width: 100%;
      }
      .cms-content-area {
        padding: 1.25rem 1rem 6rem;
      }
      .floating-save-bar {
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
      }
      .btn-save-primary {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <!-- Left Sidebar Navigation Component -->
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Right Content Wrapper -->
  <div class="admin-main-wrapper">

    <!-- Top Action Bar -->
    <header class="admin-topbar">
      <div class="topbar-title-wrap">
        <h1 class="topbar-heading">Website CMS Studio</h1>
        <span class="topbar-subtext">Dynamic Website Copy, GST Tax Rates & Loyalty Engine Configuration</span>
      </div>

      <div class="topbar-right-tools">
        <a href="/index.php" target="_blank" class="btn-refresh-pill" style="text-decoration:none;">
          <span>Preview Public Website ↗</span>
        </a>
      </div>
    </header>

    <!-- Content Body -->
    <main class="cms-content-area">

      <!-- Alert Notifications -->
      <?php if ($msg !== ''): ?>
        <div class="toast-alert">
          <div style="display:flex; align-items:center; gap:0.6rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2D6A4F" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <span><?= e($msg) ?></span>
          </div>
          <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--text-muted);">&times;</button>
        </div>
      <?php elseif ($err !== ''): ?>
        <div class="toast-alert error">
          <span><?= e($err) ?></span>
          <button onclick="this.parentElement.style.display='none'" style="background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--accent-terracotta);">&times;</button>
        </div>
      <?php endif; ?>

      <form method="POST" action="/admin/cms.php" id="cmsForm">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="studio-layout">

          <!-- Left Form Sections -->
          <div class="form-sections">

            <!-- 1. Hero & Branding -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Hero Section & Branding</h2>
                  <div class="section-desc">Grand editorial entrance text displayed at the top of the homepage.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Brand Main Title</label>
                  <input type="text" id="input_hero_name" name="settings[cms_hero_name]" value="<?= e((string)($current['cms_hero_name'] ?? 'Cielo')) ?>" oninput="updatePreview()">
                  <span class="help-hint">Primary Fraunces serif display heading.</span>
                </div>

                <div class="form-group">
                  <label>Brand Tagline Subtitle</label>
                  <input type="text" id="input_hero_tagline" name="settings[cms_hero_tagline]" value="<?= e((string)($current['cms_hero_tagline'] ?? 'Cafe and Restro')) ?>" oninput="updatePreview()">
                  <span class="help-hint">Italic second line underneath brand name.</span>
                </div>

                <div class="form-group">
                  <label>Top Accent Location Badge</label>
                  <input type="text" id="input_hero_badge" name="settings[cms_hero_badge]" value="<?= e((string)($current['cms_hero_badge'] ?? 'Vadodara · Italian Sanctuary')) ?>" oninput="updatePreview()">
                </div>

                <div class="form-group">
                  <label>Hero CTA Button Label</label>
                  <input type="text" id="input_hero_cta" name="settings[cms_hero_cta]" value="<?= e((string)($current['cms_hero_cta'] ?? 'Explore Artisanal Menu')) ?>" oninput="updatePreview()">
                </div>
              </div>
            </div>

            <!-- 2. Story & Heritage -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-sky-deep);">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Story & Italian Heritage</h2>
                  <div class="section-desc">The artisanal narrative, quote, and sourdough baking philosophy.</div>
                </div>
              </div>

              <div class="form-grid single-col">
                <div class="form-group">
                  <label>Story Main Heading</label>
                  <input type="text" name="settings[cms_story_heading]" value="<?= e((string)($current['cms_story_heading'] ?? 'An Italian Sanctuary Born from Sourdough and Slow Brews')) ?>">
                </div>

                <div class="form-group">
                  <label>Editorial Pull Quote</label>
                  <textarea id="input_story_quote" name="settings[cms_story_quote]" rows="2" oninput="updatePreview()"><?= e((string)($current['cms_story_quote'] ?? 'Where unhurried coffee meets wood-fired warmth in Gujarat.')) ?></textarea>
                </div>
              </div>
            </div>

            <!-- 3. Contact & Hours -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:#2D6A4F;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Contact, Location & Operating Hours</h2>
                  <div class="section-desc">Phone, WhatsApp hotline, address, and daily opening timings.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Public Phone Hotline</label>
                  <input type="text" name="settings[cafe_phone]" value="<?= e((string)($current['cafe_phone'] ?? '+91 90818 88944')) ?>">
                </div>

                <div class="form-group">
                  <label>WhatsApp Number (Digits only)</label>
                  <input type="text" name="settings[cafe_whatsapp]" value="<?= e((string)($current['cafe_whatsapp'] ?? '919081888944')) ?>">
                </div>

                <div class="form-group">
                  <label>Contact Email</label>
                  <input type="email" name="settings[cafe_email]" value="<?= e((string)($current['cafe_email'] ?? 'cielocafebistro@gmail.com')) ?>">
                </div>

                <div class="form-group">
                  <label>Operating Hours Text</label>
                  <input type="text" name="settings[cafe_hours]" value="<?= e((string)($current['cafe_hours'] ?? '11:00 AM - 11:30 PM')) ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                  <label>Cafe Physical Address (Formatted)</label>
                  <input type="text" name="settings[cafe_address]" value="<?= e((string)($current['cafe_address'] ?? 'GF-01, Trisha Square, Opp. Yash Complex, Subhanpura, Vadodara, Gujarat 390023')) ?>">
                </div>
              </div>
            </div>

            <!-- 4. GST & Taxes -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:#C08A3E;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <div>
                  <h2 class="section-title">GST & Financial Rules</h2>
                  <div class="section-desc">Government tax breakdown automatically applied to all digital orders and thermal bills.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>CGST Rate (%)</label>
                  <input type="number" step="0.1" name="settings[gst_cgst_percent]" value="<?= e((string)($current['gst_cgst_percent'] ?? 2.5)) ?>">
                  <span class="help-hint">Standard restaurant CGST is 2.5%.</span>
                </div>

                <div class="form-group">
                  <label>SGST Rate (%)</label>
                  <input type="number" step="0.1" name="settings[gst_sgst_percent]" value="<?= e((string)($current['gst_sgst_percent'] ?? 2.5)) ?>">
                  <span class="help-hint">Standard restaurant SGST is 2.5%.</span>
                </div>

                <div class="form-group">
                  <label>Cafe GSTIN Number</label>
                  <input type="text" name="settings[cafe_gstin]" value="<?= e((string)($current['cafe_gstin'] ?? '24ABCDE1234F1Z5')) ?>">
                </div>

                <div class="form-group">
                  <label>GST Calculation Status</label>
                  <select name="settings[gst_enabled]">
                    <option value="1" <?= (!empty($current['gst_enabled'])) ? 'selected' : '' ?>>Enabled (Tax is added to subtotal)</option>
                    <option value="0" <?= (empty($current['gst_enabled'])) ? 'selected' : '' ?>>Disabled (Inclusive / Zero Tax)</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- 5. Loyalty Points System -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-terracotta);">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Loyalty Points & Rewards Engine</h2>
                  <div class="section-desc">Points accrual and redemption limits credited directly to customer double-entry ledgers.</div>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>Points Earned per ₹100 Spent</label>
                  <input type="number" name="settings[loyalty_points_per_100]" value="<?= e((string)($current['loyalty_points_per_100'] ?? 10)) ?>">
                  <span class="help-hint">e.g. 10 points per ₹100 = 10% effective earning rate.</span>
                </div>

                <div class="form-group">
                  <label>Point Redemption Value (in Paise)</label>
                  <input type="number" name="settings[loyalty_point_value_paise]" value="<?= e((string)($current['loyalty_point_value_paise'] ?? 100)) ?>">
                  <span class="help-hint">100 paise = ₹1.00 per loyalty point.</span>
                </div>

                <div class="form-group">
                  <label>Minimum Points Required to Redeem</label>
                  <input type="number" name="settings[loyalty_min_redeem_points]" value="<?= e((string)($current['loyalty_min_redeem_points'] ?? 50)) ?>">
                </div>

                <div class="form-group">
                  <label>Maximum Bill Discount Cap (%)</label>
                  <input type="number" step="1" name="settings[loyalty_max_redeem_percent]" value="<?= e((string)($current['loyalty_max_redeem_percent'] ?? 50)) ?>">
                  <span class="help-hint">Protects margins (e.g. points cannot cover >50% of bill).</span>
                </div>
              </div>
            </div>

            <!-- 6. Promotional Engine: Coupons, Happy Hours & Stacking Rules -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:var(--accent-sky-deep);">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Promotions, Happy Hours & Stacking Engine</h2>
                  <div class="section-desc">Audience targeting, automated time-window offers, sourdough combos, and coupon stacking policy.</div>
                </div>
              </div>

              <!-- Promo Stacking Configuration -->
              <div class="form-grid single-col" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                  <label>Promotional Stacking Policy</label>
                  <select name="settings[promo_stacking_mode]" style="width:100%; max-width:100%;">
                    <option value="stackable" <?= (($current['promo_stacking_mode'] ?? 'stackable') === 'stackable') ? 'selected' : '' ?>>
                      Stackable Mode (Recommended: Offers & Combos combine with Coupon Codes)
                    </option>
                    <option value="exclusive" <?= (($current['promo_stacking_mode'] ?? 'stackable') === 'exclusive') ? 'selected' : '' ?>>
                      Exclusive Mode (Margin Guard: Automated Offers block Coupon Codes)
                    </option>
                  </select>
                  <span class="help-hint">Controls how the authoritative cart evaluator applies multiple promotions at checkout.</span>
                </div>
              </div>

              <!-- Automated Time-Based Offers (Happy Hours & Combos) -->
              <div style="margin-top: 1.25rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
                  <h3 style="font-size:0.95rem; font-weight:700; color:var(--text-espresso); margin:0;">Automated Offers & Combos</h3>
                  <span style="font-size:0.75rem; color:var(--text-muted);"><?= count($offersList) ?> Active Schedules</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                  <?php foreach ($offersList as $off): ?>
                    <?php 
                      $rules = !empty($off['rules_json']) ? json_decode($off['rules_json'], true) : [];
                      $sched = !empty($off['schedule_json']) ? json_decode($off['schedule_json'], true) : [];
                      $daysText = !empty($sched['days']) ? implode(', ', array_slice($sched['days'], 0, 4)) . (count($sched['days']) > 4 ? '...' : '') : 'Everyday';
                      $timeText = (!empty($sched['start_time']) && !empty($sched['end_time'])) ? ($sched['start_time'] . ' - ' . $sched['end_time']) : 'All Day';
                    ?>
                    <div style="background:var(--bg-cream); border:1px solid var(--border-warm); border-radius:12px; padding:0.9rem 1.15rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; min-width:0;">
                      <div style="min-width:0; flex:1;">
                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem; flex-wrap:wrap;">
                          <span style="font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; padding:2px 8px; border-radius:4px; background:<?= $off['type'] === 'happy_hour' ? 'rgba(107, 154, 196, 0.2)' : 'rgba(201, 106, 82, 0.18)' ?>; color:<?= $off['type'] === 'happy_hour' ? '#3B6A8F' : '#A74A34' ?>; white-space:nowrap;">
                            <?= $off['type'] === 'happy_hour' ? 'Happy Hour' : 'Sourdough Combo' ?>
                          </span>
                          <strong style="font-size:0.9rem; color:var(--text-espresso); word-break:break-word;"><?= e($off['title']) ?></strong>
                        </div>
                        <div style="font-size:0.78rem; color:var(--text-muted); word-break:break-word;">
                          Schedule: <span style="color:var(--text-espresso); font-weight:600;"><?= e($daysText) ?> (<?= e($timeText) ?>)</span>
                        </div>
                      </div>
                      <span style="font-size:0.75rem; font-weight:700; color:#2D6A4F; background:rgba(45,106,79,0.1); padding:4px 10px; border-radius:999px; flex-shrink:0;">
                        Active
                      </span>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Active Coupons & Audience Targeting -->
              <div style="margin-top: 1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; flex-wrap:wrap; gap:0.5rem;">
                  <h3 style="font-size:0.95rem; font-weight:700; color:var(--text-espresso); margin:0;">Vouchers & Audience Targeting</h3>
                  <span style="font-size:0.75rem; color:var(--text-muted);"><?= count($couponsList) ?> Configured Vouchers</span>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                  <?php foreach ($couponsList as $cp): ?>
                    <?php
                      $audBadgeColor = match($cp['audience']) {
                        'vip' => 'background:rgba(26,20,18,0.08); color:var(--text-espresso); border:1px solid rgba(26,20,18,0.2);',
                        'first_time' => 'background:rgba(201,106,82,0.15); color:var(--accent-terracotta); border:1px solid rgba(201,106,82,0.3);',
                        default => 'background:rgba(107,154,196,0.15); color:var(--accent-sky-deep); border:1px solid rgba(107,154,196,0.3);',
                      };
                      $audLabel = match($cp['audience']) {
                        'vip' => 'VIP Patrons (5+ visits)',
                        'first_time' => 'First-Time Guests',
                        default => 'All Dining Guests',
                      };
                      $discountDisplay = ($cp['type'] === 'percentage') 
                        ? ($cp['value'] . '% Off (Capped at ₹' . ($cp['max_discount_paise'] / 100) . ')')
                        : ('Flat ₹' . $cp['value'] . ' Off');
                    ?>
                    <div style="background:#FFFFFF; border:1px solid var(--border-warm); border-radius:12px; padding:0.9rem 1.15rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; min-width:0;">
                      <div style="min-width:0; flex:1;">
                        <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.3rem; flex-wrap:wrap;">
                          <span style="font-family:monospace; font-weight:800; font-size:0.92rem; background:rgba(26,20,18,0.06); padding:2px 8px; border-radius:6px; letter-spacing:0.05em; color:var(--text-espresso); white-space:nowrap;">
                            <?= e($cp['code']) ?>
                          </span>
                          <span style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:999px; white-space:nowrap; <?= $audBadgeColor ?>">
                            <?= e($audLabel) ?>
                          </span>
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-espresso); font-weight:600; word-break:break-word;">
                          <?= e($cp['title']) ?> &middot; <span style="color:var(--accent-terracotta);"><?= e($discountDisplay) ?></span>
                        </div>
                        <div style="font-size:0.74rem; color:var(--text-muted); margin-top:2px;">
                          Min Order: ₹<?= number_format($cp['min_order_paise'] / 100) ?> &middot; Limit: <?= $cp['usage_limit_per_user'] ?>x per patron
                        </div>
                      </div>
                      <div style="text-align:right; flex-shrink:0;">
                        <span style="font-size:0.75rem; font-weight:700; color:#2D6A4F; background:rgba(45,106,79,0.1); padding:4px 10px; border-radius:999px;">
                          <?= (!empty($cp['is_active'])) ? 'Active' : 'Inactive' ?>
                        </span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

            </div>

            <!-- 7. Verified Customer Reviews & Social Proof -->
            <div class="cms-card">
              <div class="section-header">
                <div class="section-icon" style="color:#F59E0B;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
                <div>
                  <h2 class="section-title">Verified Customer Reviews & Social Proof</h2>
                  <div class="section-desc">Patron dining feedback, automated +50 points reward gate, and homepage social proof showcases.</div>
                </div>
              </div>

              <div class="form-grid single-col" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                  <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                    <input type="hidden" name="settings[reviews_auto_approve_positive]" value="0">
                    <input type="checkbox" name="settings[reviews_auto_approve_positive]" value="1" <?= (!isset($current['reviews_auto_approve_positive']) || $current['reviews_auto_approve_positive']) ? 'checked' : '' ?> style="width:18px; height:18px; accent-color:var(--accent-terracotta);">
                    <span>Auto-Publish Positive Reviews (4 & 5 Stars)</span>
                  </label>
                  <span class="help-hint">When enabled, genuine 4 and 5 star reviews with no profanity are published immediately to the website homepage showcase and +50 points are credited to the patron. Lower ratings (1-3 stars) are queued in moderation.</span>
                </div>
              </div>

              <div style="background:var(--bg-cream); border-radius:12px; padding:1.25rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div>
                  <div style="font-size:0.85rem; font-weight:700; color:var(--text-espresso);">Review Moderation Queue</div>
                  <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">
                    Inspect, approve, or hide customer dining testimonials and photos.
                  </div>
                </div>
                <a href="/admin/reviews.php" class="btn-action" style="text-decoration:none; background:var(--accent-terracotta); color:#ffffff; padding:0.6rem 1.25rem; border-radius:8px; font-size:0.82rem; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem;">
                  <span>Open Moderation Hub →</span>
                </a>
              </div>

            </div>

          </div>

          <!-- Right Sticky Live Preview Mockup -->
          <div class="preview-sidebar">
            <div class="preview-card">
              <div class="preview-header">
                <span>Live Website Preview</span>
                <span style="font-size:0.65rem; color:var(--accent-sky);">Real-time</span>
              </div>
              <div class="preview-body">
                <div class="preview-mock-hero">
                  <span class="mock-badge" id="preview_badge"><?= e((string)($current['cms_hero_badge'] ?? 'Vadodara · Italian Sanctuary')) ?></span>
                  <h3 class="mock-title" id="preview_name"><?= e((string)($current['cms_hero_name'] ?? 'Cielo')) ?></h3>
                  <span class="mock-tagline" id="preview_tagline"><?= e((string)($current['cms_hero_tagline'] ?? 'Cafe and Restro')) ?></span>
                  <span class="mock-btn" id="preview_cta"><?= e((string)($current['cms_hero_cta'] ?? 'Explore Artisanal Menu')) ?></span>
                </div>

                <div class="preview-quote-box">
                  &ldquo;<span id="preview_quote"><?= e((string)($current['cms_story_quote'] ?? 'Where unhurried coffee meets wood-fired warmth in Gujarat.')) ?></span>&rdquo;
                </div>

                <div style="margin-top:1.25rem; font-size:0.75rem; color:var(--text-muted); line-height:1.6; padding-top:1rem; border-top:1px dashed var(--border-warm);">
                  <strong>Instant Effect:</strong> When you click save, your changes are written to the database and displayed instantly on the public website without restarting any server.
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Floating Save Bar -->
        <div class="floating-save-bar">
          <button type="submit" class="btn-save-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span>Save All Settings</span>
          </button>
        </div>

      </form>

    </main>

  </div>

  <script>
    function updatePreview() {
      const name = document.getElementById('input_hero_name').value || 'Cielo';
      const tagline = document.getElementById('input_hero_tagline').value || 'Cafe and Restro';
      const badge = document.getElementById('input_hero_badge').value || 'Vadodara · Italian Sanctuary';
      const cta = document.getElementById('input_hero_cta').value || 'Explore Artisanal Menu';
      const quote = document.getElementById('input_story_quote').value || 'Where unhurried coffee meets wood-fired warmth in Gujarat.';

      document.getElementById('preview_name').textContent = name;
      document.getElementById('preview_tagline').textContent = tagline;
      document.getElementById('preview_badge').textContent = badge;
      document.getElementById('preview_cta').textContent = cta;
      document.getElementById('preview_quote').textContent = quote;
    }
  </script>

</body>
</html>
