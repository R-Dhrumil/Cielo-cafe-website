<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Menu, Variant & Stock Studio
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\ImageService;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use App\Models\AuditLog;
use App\Core\Security;
use App\Core\Response;

// Require authorized staff role
$staff = AuthService::requireRole(['owner', 'admin', 'manager'], '/admin/login.php');

$msg = '';
$err = '';

// Handle AJAX or Form Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!Security::verifyCsrfToken($csrf)) {
        if ($isAjax) {
            Response::json(false, null, 'Security session expired. Please refresh.', 403);
        }
        $err = 'Security session expired. Please refresh.';
    } else {
        if ($action === 'toggle_stock') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $item = MenuItem::find($itemId);

            if ($item) {
                $newStatus = MenuItem::toggleAvailability($itemId);
                AuditLog::log((int)$staff['id'], 'menu_stock_toggled', 'menu_item', $itemId, [
                    'item_name' => $item['name'],
                    'new_available' => $newStatus ? 1 : 0
                ]);

                if ($isAjax) {
                    Response::json(true, [
                        'item_id' => $itemId,
                        'is_available' => $newStatus ? 1 : 0,
                    ], $newStatus ? "'{$item['name']}' is now marked IN STOCK." : "'{$item['name']}' is now marked SOLD OUT (86ed).");
                }
                $msg = $newStatus ? "'{$item['name']}' is now IN STOCK." : "'{$item['name']}' is now SOLD OUT (86ed).";
            } else {
                if ($isAjax) Response::json(false, null, 'Dish not found.', 404);
                $err = 'Dish not found.';
            }
        } elseif ($action === 'update_item') {
            $itemId = (int)($_POST['item_id'] ?? 0);
            $priceRupees = (float)($_POST['price_rupees'] ?? 0);
            $pricePaise = (int)round($priceRupees * 100);
            $isVeg = (int)($_POST['is_veg'] ?? 1);
            $name = trim((string)($_POST['name'] ?? ''));
            $desc = trim((string)($_POST['description'] ?? ''));

            if ($itemId > 0 && !empty($name) && $pricePaise >= 0) {
                $updateData = [
                    'name' => $name,
                    'price_paise' => $pricePaise,
                    'is_veg' => $isVeg,
                    'description' => $desc,
                ];

                // Process uploaded photo (converted to .webp) or preset
                $uploadedWebp = ImageService::saveAsWebp(
                    $_FILES['dish_photo'] ?? null,
                    !empty($_POST['image_base64_webp']) ? (string)$_POST['image_base64_webp'] : null,
                    'assets/images/dishes',
                    $name
                );

                if ($uploadedWebp !== null) {
                    $updateData['image_url'] = $uploadedWebp;
                } elseif (!empty($_POST['preset_image_url'])) {
                    $updateData['image_url'] = trim((string)$_POST['preset_image_url']);
                }

                MenuItem::updateById($itemId, $updateData);

                AuditLog::log((int)$staff['id'], 'menu_item_updated', 'menu_item', $itemId, [
                    'name' => $name,
                    'price_paise' => $pricePaise,
                ]);
                $msg = "Dish '{$name}' updated successfully.";
            } else {
                $err = 'Please provide valid dish name and price.';
            }
        } elseif ($action === 'create_item') {
            $catId = (int)($_POST['category_id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $priceRupees = (float)($_POST['price_rupees'] ?? 0);
            $pricePaise = (int)round($priceRupees * 100);
            $isVeg = (int)($_POST['is_veg'] ?? 1);
            $desc = trim((string)($_POST['description'] ?? ''));

            if ($catId > 0 && !empty($name) && $pricePaise > 0) {
                $slug = strtolower(trim((string)preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

                // Convert and save photo as .webp
                $uploadedWebp = ImageService::saveAsWebp(
                    $_FILES['dish_photo'] ?? null,
                    !empty($_POST['image_base64_webp']) ? (string)$_POST['image_base64_webp'] : null,
                    'assets/images/dishes',
                    $slug
                );

                if ($uploadedWebp !== null) {
                    $imageUrl = $uploadedWebp;
                } elseif (!empty($_POST['preset_image_url'])) {
                    $imageUrl = trim((string)$_POST['preset_image_url']);
                } else {
                    $imageUrl = trim((string)($_POST['image_url'] ?? 'assets/images/dishes/pesto-pizza.webp'));
                }

                $newId = MenuItem::create([
                    'category_id' => $catId,
                    'name' => $name,
                    'slug' => $slug . '-' . uniqid(),
                    'description' => $desc,
                    'price_paise' => $pricePaise,
                    'is_veg' => $isVeg,
                    'image_url' => $imageUrl,
                    'is_available' => 1,
                    'sort_order' => 99,
                ]);

                AuditLog::log((int)$staff['id'], 'menu_item_created', 'menu_item', $newId, [
                    'name' => $name,
                    'price_paise' => $pricePaise,
                    'image_url' => $imageUrl,
                ]);
                $msg = "New artisanal dish '{$name}' published with optimized WebP photo!";
            } else {
                $err = 'Please select a valid category, name, and positive price.';
            }
        }
    }
}

// Filters
$selectedCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = trim((string)($_GET['q'] ?? ''));

$categories = MenuCategory::all('sort_order ASC');
$items = MenuItem::getAllWithCategory($selectedCat > 0 ? $selectedCat : null, $search);
$dishPresets = ImageService::getDishPresets();

$csrfToken = Security::generateCsrfToken();
$activeNav = 'menu';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu, Variant & Stock Studio | Cielo Cafe Admin</title>
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
    }

    /* Left Sidebar */
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
      background: var(--accent-terracotta);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.15rem 0.55rem;
      border-radius: 9999px;
    }

    .sidebar-footer {
      padding: 1.25rem;
      border-top: 1px solid rgba(247, 244, 238, 0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
    }

    .staff-card {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      overflow: hidden;
    }

    .staff-avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: rgba(201, 106, 82, 0.2);
      color: var(--accent-terracotta);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
      flex-shrink: 0;
    }

    .staff-name {
      font-size: 0.82rem;
      font-weight: 600;
      color: #F7F4EE;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .staff-role-badge {
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.65rem;
      color: var(--accent-sky);
      text-transform: capitalize;
    }

    .role-indicator {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #10B981;
    }

    .btn-sidebar-signout {
      background: rgba(247, 244, 238, 0.08);
      color: rgba(247, 244, 238, 0.7);
      border: none;
      border-radius: 8px;
      padding: 0.45rem 0.65rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.75rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .btn-sidebar-signout:hover {
      background: rgba(201, 106, 82, 0.2);
      color: var(--accent-terracotta);
    }

    /* Main Content */
    .admin-main {
      margin-left: 260px;
      flex: 1;
      padding: 2.5rem 3rem 4rem;
      max-width: 1400px;
    }

    .header-bar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--border-warm);
      flex-wrap: wrap;
      gap: 1rem;
    }

    .page-title {
      font-family: 'Fraunces', serif;
      font-size: 2.2rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      margin: 0;
      color: var(--text-espresso);
    }

    .page-sub {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin: 0.35rem 0 0;
    }

    .btn-add-dish {
      background: var(--text-espresso);
      color: #FFFFFF;
      border: none;
      border-radius: 9999px;
      padding: 0.65rem 1.35rem;
      font-size: 0.85rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      transition: all 0.2s ease;
    }
    .btn-add-dish:hover {
      background: #000;
      transform: translateY(-1px);
    }

    /* Toolbar / Filters */
    .toolbar-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.75rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .cat-pills {
      display: flex;
      gap: 0.45rem;
      background: rgba(26, 20, 18, 0.04);
      padding: 0.35rem;
      border-radius: 9999px;
      overflow-x: auto;
      max-width: 800px;
    }

    .cat-pill {
      padding: 0.45rem 1.15rem;
      border-radius: 9999px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-muted);
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .cat-pill.active {
      background: #FFFFFF;
      color: var(--text-espresso);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .search-box {
      display: flex;
      align-items: center;
      background: #FFFFFF;
      border: 1px solid var(--border-warm);
      border-radius: 9999px;
      padding: 0.35rem 1rem;
      box-shadow: var(--shadow-card);
    }

    .search-box input {
      border: none;
      outline: none;
      font-family: inherit;
      font-size: 0.85rem;
      background: transparent;
      width: 200px;
    }

    /* Menu Cards Grid */
    .dishes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
    }

    .dish-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: all 0.25s ease;
      position: relative;
    }

    .dish-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(26, 20, 18, 0.08);
      border-color: rgba(107, 154, 196, 0.4);
    }

    .dish-card.sold-out {
      opacity: 0.75;
      background: #FAF8F5;
      border-style: dashed;
    }

    /* Dish Card Image Banner with WEBP Tag */
    .dish-img-banner {
      width: 100%;
      height: 140px;
      position: relative;
      background: #EAE6DE;
      overflow: hidden;
    }

    .dish-img-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.4s ease;
    }

    .dish-card:hover .dish-img-thumb {
      transform: scale(1.05);
    }

    .dish-img-overlay {
      position: absolute;
      top: 0.75rem;
      left: 0.75rem;
      right: 0.75rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      pointer-events: none;
    }

    .dish-cat-pill {
      background: rgba(26, 20, 18, 0.82);
      color: #FFFFFF;
      backdrop-filter: blur(4px);
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 0.22rem 0.65rem;
      border-radius: 999px;
    }

    .dish-webp-badge {
      background: rgba(45, 106, 79, 0.9);
      color: #FFFFFF;
      font-size: 0.62rem;
      font-weight: 800;
      letter-spacing: 0.06em;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      backdrop-filter: blur(4px);
    }

    .dish-body-wrap {
      padding: 1.25rem 1.35rem;
      display: flex;
      flex-direction: column;
      flex: 1;
      justify-content: space-between;
    }

    .dish-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 0.6rem;
      gap: 0.75rem;
    }

    .dish-name {
      font-family: 'Fraunces', serif;
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      line-height: 1.25;
    }

    .diet-badge {
      width: 16px;
      height: 16px;
      border: 1.5px solid;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      flex-shrink: 0;
    }
    .diet-veg { border-color: #10B981; }
    .diet-veg::after { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #10B981; }
    .diet-nonveg { border-color: #EF4444; }
    .diet-nonveg::after { content: ''; width: 0; height: 0; border-left: 4px solid transparent; border-right: 4px solid transparent; border-bottom: 8px solid #EF4444; }

    .dish-desc {
      font-size: 0.82rem;
      color: var(--text-muted);
      line-height: 1.5;
      margin-bottom: 1.15rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .dish-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid var(--border-warm);
      padding-top: 0.9rem;
    }

    .dish-price {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--text-espresso);
      font-family: monospace;
    }

    .stock-toggle-btn {
      border: none;
      border-radius: 9999px;
      padding: 0.45rem 1rem;
      font-size: 0.78rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      transition: all 0.15s ease;
    }

    .btn-in-stock {
      background: rgba(16, 185, 129, 0.12);
      color: #059669;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .btn-in-stock:hover {
      background: #10B981;
      color: #ffffff;
    }

    .btn-sold-out {
      background: rgba(239, 68, 68, 0.12);
      color: #DC2626;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .btn-sold-out:hover {
      background: #DC2626;
      color: #ffffff;
    }

    .btn-quick-edit {
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      padding: 0.25rem;
      border-radius: 6px;
      display: inline-flex;
      align-items: center;
    }
    .btn-quick-edit:hover {
      color: var(--accent-terracotta);
      background: rgba(26, 20, 18, 0.05);
    }

    .alert {
      padding: 1rem 1.25rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .alert-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
    .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }

    /* Modal Form Styles */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(26, 20, 18, 0.65);
      backdrop-filter: blur(4px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
    }

    .modal-box {
      background: #ffffff;
      border-radius: var(--radius-lg);
      max-width: 540px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      padding: 2rem;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .form-group {
      margin-bottom: 1.15rem;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .form-group label {
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--text-espresso);
    }

    .form-group input, .form-group select, .form-group textarea {
      padding: 0.75rem 0.85rem;
      border: 1px solid var(--border-warm);
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.9rem;
      outline: none;
      background: var(--bg-cream);
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
      border-color: var(--accent-sky);
      background: #FFFFFF;
    }

    /* WebP Photo Upload Box Styles */
    .photo-upload-zone {
      border: 2px dashed rgba(26, 20, 18, 0.15);
      border-radius: 12px;
      padding: 1.25rem;
      text-align: center;
      background: var(--bg-cream);
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
    }
    .photo-upload-zone:hover {
      border-color: var(--accent-terracotta);
      background: #FFFFFF;
    }
    .upload-icon-circle {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(201, 106, 82, 0.12);
      color: var(--accent-terracotta);
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .photo-preview-wrap {
      display: flex;
      align-items: center;
      gap: 1rem;
      text-align: left;
    }
    .photo-preview-img {
      width: 90px;
      height: 70px;
      border-radius: 8px;
      object-fit: cover;
      border: 1px solid var(--border-warm);
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      flex-shrink: 0;
    }
    .photo-preview-info {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      min-width: 0;
    }
    .preview-badge-webp {
      font-size: 0.72rem;
      font-weight: 700;
      color: #2D6A4F;
      background: rgba(45, 106, 79, 0.12);
      padding: 2px 8px;
      border-radius: 999px;
      display: inline-block;
      width: fit-content;
    }
    .btn-remove-photo {
      background: none;
      border: none;
      color: var(--accent-terracotta);
      font-size: 0.75rem;
      font-weight: 600;
      cursor: pointer;
      padding: 0;
      text-align: left;
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="admin-main">

    <div class="header-bar">
      <div>
        <h1 class="page-title">Menu & Stock Studio</h1>
        <p class="page-sub">Live 1-click out-of-stock toggles (86ing), price overrides, and artisanal dish catalog.</p>
      </div>

      <button type="button" onclick="openAddDishModal()" class="btn-add-dish">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Artisanal Dish
      </button>
    </div>

    <?php if (!empty($msg)): ?>
      <div class="alert alert-success">✔ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
      <div class="alert alert-error">✘ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <!-- Category Toolbar & Search -->
    <div class="toolbar-bar">
      <div class="cat-pills">
        <a href="menu.php" class="cat-pill <?= $selectedCat === 0 ? 'active' : '' ?>">All Dishes (<?= count($items) ?>)</a>
        <?php foreach ($categories as $cat): ?>
          <a href="menu.php?cat=<?= (int)$cat['id'] ?>" class="cat-pill <?= $selectedCat === (int)$cat['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($cat['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <form method="GET" class="search-box">
        <?php if ($selectedCat > 0): ?>
          <input type="hidden" name="cat" value="<?= $selectedCat ?>">
        <?php endif; ?>
        <input type="text" name="q" placeholder="Search dish..." value="<?= htmlspecialchars($search) ?>">
      </form>
    </div>

    <!-- Dish Cards Grid -->
    <?php if (empty($items)): ?>
      <div style="background:#fff; border-radius:var(--radius-lg); padding:4rem 2rem; text-align:center; color:var(--text-muted); border:1px dashed var(--border-warm);">
        <h3>No dishes found</h3>
        <p>Try clearing filters or search query.</p>
      </div>
    <?php else: ?>
      <div class="dishes-grid">
        <?php foreach ($items as $dish): ?>
          <?php 
            $isAvail = (int)($dish['is_available'] ?? 1) === 1; 
            $imgUrl = !empty($dish['image_url']) ? (string)$dish['image_url'] : 'assets/images/dishes/pesto-pizza.webp';
            $isWebp = str_ends_with(strtolower($imgUrl), '.webp');
          ?>
          <div class="dish-card <?= $isAvail ? '' : 'sold-out' ?>" id="dish-card-<?= (int)$dish['id'] ?>">
            
            <!-- Dish Photo Banner with Format Tag -->
            <div class="dish-img-banner">
              <img src="/<?= e($imgUrl) ?>" alt="<?= e($dish['name']) ?>" class="dish-img-thumb" loading="lazy">
              <div class="dish-img-overlay">
                <span class="dish-cat-pill"><?= htmlspecialchars((string)($dish['category_name'] ?? 'Artisanal')) ?></span>
                <span class="dish-webp-badge"><?= $isWebp ? 'WEBP' : 'PHOTO' ?></span>
              </div>
            </div>

            <div class="dish-body-wrap">
              <div>
                <div class="dish-top">
                  <h3 class="dish-name"><?= htmlspecialchars($dish['name']) ?></h3>
                  <div style="display:flex; align-items:center; gap:0.5rem;">
                    <button type="button" class="btn-quick-edit" title="Edit Price, Details & Photo" onclick="openEditDishModal(<?= (int)$dish['id'] ?>, '<?= htmlspecialchars(addslashes($dish['name']), ENT_QUOTES) ?>', <?= (float)($dish['price_paise'] / 100) ?>, <?= (int)$dish['is_veg'] ?>, '<?= htmlspecialchars(addslashes($dish['description'] ?? ''), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($imgUrl), ENT_QUOTES) ?>')">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <span class="diet-badge <?= ((int)$dish['is_veg'] === 1) ? 'diet-veg' : 'diet-nonveg' ?>" title="<?= ((int)$dish['is_veg'] === 1) ? 'Vegetarian' : 'Non-Vegetarian' ?>"></span>
                  </div>
                </div>

                <p class="dish-desc"><?= htmlspecialchars((string)($dish['description'] ?? 'Artisanal preparation with premium estate ingredients.')) ?></p>
              </div>

              <div class="dish-footer">
                <div class="dish-price">₹<?= number_format((int)$dish['price_paise'] / 100, 2) ?></div>

                <form method="POST" style="margin:0;" onsubmit="handleStockToggle(event, <?= (int)$dish['id'] ?>)">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="action" value="toggle_stock">
                  <input type="hidden" name="item_id" value="<?= (int)$dish['id'] ?>">

                  <button type="submit" id="toggle-btn-<?= (int)$dish['id'] ?>" class="stock-toggle-btn <?= $isAvail ? 'btn-in-stock' : 'btn-sold-out' ?>">
                    <span><?= $isAvail ? '● In Stock' : '✕ Sold Out (86ed)' ?></span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>

  <!-- ADD DISH MODAL WITH WEBP PHOTO UPLOAD -->
  <div id="modalAddDish" class="modal-backdrop">
    <div class="modal-box">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
          <h2 style="font-family:'Fraunces', serif; font-size:1.4rem; color:var(--text-espresso); margin:0;">Add Artisanal Dish</h2>
          <span style="font-size:0.75rem; color:var(--text-muted);">With native .webp lightweight image compression</span>
        </div>
        <button type="button" onclick="closeAddDishModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <form method="POST" enctype="multipart/form-data" id="formAddDish">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="create_item">
        <input type="hidden" name="image_base64_webp" id="addDishWebpBase64">

        <div class="form-group">
          <label>Category</label>
          <select name="category_id" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Dish Name</label>
          <input type="text" name="name" placeholder="e.g. Truffle Penne Rigate" required>
        </div>

        <!-- Photo Upload Section (Auto-Converts to .webp) -->
        <div class="form-group">
          <label style="display:flex; justify-content:space-between; align-items:center;">
            <span>Dish Photo</span>
            <span style="font-size:0.7rem; font-weight:700; color:#2D6A4F; background:rgba(45,106,79,0.1); padding:2px 8px; border-radius:999px;">
              Auto-converts to WebP
            </span>
          </label>
          
          <div class="photo-upload-zone" id="addPhotoDropzone" onclick="document.getElementById('addDishPhotoInput').click()">
            <input type="file" id="addDishPhotoInput" name="dish_photo" accept="image/*" style="display:none;" onchange="handleImageFileSelect(this, 'add')">
            
            <div id="addPhotoPrompt">
              <div class="upload-icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
              <div style="font-weight:700; font-size:0.86rem; color:var(--text-espresso); margin-top:0.4rem;">
                Click to upload dish photo
              </div>
              <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px;">
                JPG, PNG or WEBP &bull; Automatically compressed to lightweight .webp
              </div>
            </div>

            <!-- Live WebP Preview -->
            <div id="addPhotoPreviewWrap" class="photo-preview-wrap" style="display:none;">
              <img id="addPhotoPreviewImg" src="" alt="Dish Preview" class="photo-preview-img">
              <div class="photo-preview-info">
                <span class="preview-badge-webp">✓ Converted to .webp</span>
                <span id="addPhotoSizeInfo" style="font-size:0.72rem; color:var(--text-muted); font-weight:600;"></span>
                <button type="button" class="btn-remove-photo" onclick="removeSelectedPhoto(event, 'add')">Change Photo</button>
              </div>
            </div>
          </div>

          <!-- Preset Library Selector -->
          <div style="margin-top:0.45rem; display:flex; align-items:center; gap:0.5rem;">
            <span style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap;">Or pick library preset:</span>
            <select name="preset_image_url" id="addPresetSelect" onchange="handlePresetSelect(this, 'add')" style="padding:0.4rem 0.6rem; font-size:0.78rem;">
              <option value="">-- Choose existing photo --</option>
              <?php foreach ($dishPresets as $preset): ?>
                <option value="<?= htmlspecialchars($preset) ?>"><?= htmlspecialchars(basename($preset)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
          <div class="form-group">
            <label>Price (₹ Rupees)</label>
            <input type="number" step="1" name="price_rupees" placeholder="450" required>
          </div>
          <div class="form-group">
            <label>Diet Type</label>
            <select name="is_veg">
              <option value="1">🌱 Vegetarian</option>
              <option value="0">🍖 Non-Vegetarian</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Description & Tasting Notes</label>
          <textarea name="description" rows="3" placeholder="Hand-tossed stone baked sourdough with Fior di Latte..."></textarea>
        </div>

        <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
          <button type="button" onclick="closeAddDishModal()" style="flex:1; background:rgba(26,20,18,0.06); border:none; border-radius:10px; padding:0.85rem; font-weight:600; cursor:pointer;">Cancel</button>
          <button type="submit" style="flex:1.5; background:var(--accent-terracotta); color:#fff; border:none; border-radius:10px; padding:0.85rem; font-weight:700; cursor:pointer;">Save & Publish</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT DISH MODAL WITH PHOTO REPLACEMENT -->
  <div id="modalEditDish" class="modal-backdrop">
    <div class="modal-box">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <div>
          <h2 style="font-family:'Fraunces', serif; font-size:1.4rem; color:var(--text-espresso); margin:0;">Edit Dish & Photo</h2>
          <span style="font-size:0.75rem; color:var(--text-muted);">Update details, pricing, or replace photo with WebP</span>
        </div>
        <button type="button" onclick="closeEditDishModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
      </div>

      <form method="POST" enctype="multipart/form-data" id="formEditDish">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="update_item">
        <input type="hidden" name="item_id" id="editItemId" value="">
        <input type="hidden" name="image_base64_webp" id="editDishWebpBase64">

        <div class="form-group">
          <label>Dish Name</label>
          <input type="text" name="name" id="editName" required>
        </div>

        <!-- Photo Section in Edit Modal -->
        <div class="form-group">
          <label style="display:flex; justify-content:space-between; align-items:center;">
            <span>Dish Photo</span>
            <span style="font-size:0.7rem; font-weight:700; color:#2D6A4F; background:rgba(45,106,79,0.1); padding:2px 8px; border-radius:999px;">
              Auto-converts to WebP
            </span>
          </label>
          
          <div class="photo-upload-zone" id="editPhotoDropzone" onclick="document.getElementById('editDishPhotoInput').click()">
            <input type="file" id="editDishPhotoInput" name="dish_photo" accept="image/*" style="display:none;" onchange="handleImageFileSelect(this, 'edit')">
            
            <div id="editPhotoPrompt">
              <img id="editCurrentPhotoImg" src="" alt="Current Dish" class="photo-preview-img" style="margin:0 auto 0.4rem; width:100px; height:75px; display:block;">
              <div style="font-weight:700; font-size:0.84rem; color:var(--text-espresso);">
                Click to replace photo
              </div>
              <div style="font-size:0.7rem; color:var(--text-muted);">
                Upload new JPG, PNG or WEBP (auto-converts to .webp)
              </div>
            </div>

            <!-- Live WebP Preview for Edit -->
            <div id="editPhotoPreviewWrap" class="photo-preview-wrap" style="display:none;">
              <img id="editPhotoPreviewImg" src="" alt="New Dish Preview" class="photo-preview-img">
              <div class="photo-preview-info">
                <span class="preview-badge-webp">✓ New .webp ready</span>
                <span id="editPhotoSizeInfo" style="font-size:0.72rem; color:var(--text-muted); font-weight:600;"></span>
                <button type="button" class="btn-remove-photo" onclick="removeSelectedPhoto(event, 'edit')">Keep Current Photo</button>
              </div>
            </div>
          </div>

          <!-- Preset Library Selector for Edit -->
          <div style="margin-top:0.45rem; display:flex; align-items:center; gap:0.5rem;">
            <span style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap;">Or pick preset:</span>
            <select name="preset_image_url" id="editPresetSelect" onchange="handlePresetSelect(this, 'edit')" style="padding:0.4rem 0.6rem; font-size:0.78rem;">
              <option value="">-- Keep current photo --</option>
              <?php foreach ($dishPresets as $preset): ?>
                <option value="<?= htmlspecialchars($preset) ?>"><?= htmlspecialchars(basename($preset)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
          <div class="form-group">
            <label>Price (₹ Rupees)</label>
            <input type="number" step="1" name="price_rupees" id="editPrice" required>
          </div>
          <div class="form-group">
            <label>Diet Type</label>
            <select name="is_veg" id="editVeg">
              <option value="1">🌱 Vegetarian</option>
              <option value="0">🍖 Non-Vegetarian</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="editDesc" rows="3"></textarea>
        </div>

        <div style="display:flex; gap:0.75rem; margin-top:1.5rem;">
          <button type="button" onclick="closeEditDishModal()" style="flex:1; background:rgba(26,20,18,0.06); border:none; border-radius:10px; padding:0.85rem; font-weight:600; cursor:pointer;">Cancel</button>
          <button type="submit" style="flex:1.5; background:var(--text-espresso); color:#fff; border:none; border-radius:10px; padding:0.85rem; font-weight:700; cursor:pointer;">Update Dish</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openAddDishModal() {
    document.getElementById('modalAddDish').style.display = 'flex';
  }
  function closeAddDishModal() {
    document.getElementById('modalAddDish').style.display = 'none';
  }

  function openEditDishModal(id, name, price, isVeg, desc, imgUrl) {
    document.getElementById('editItemId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editPrice').value = price;
    document.getElementById('editVeg').value = isVeg;
    document.getElementById('editDesc').value = desc;

    const currentImg = document.getElementById('editCurrentPhotoImg');
    if (currentImg) {
      currentImg.src = '/' + (imgUrl || 'assets/images/dishes/pesto-pizza.webp');
    }

    // Reset previous selection
    document.getElementById('editDishPhotoInput').value = '';
    document.getElementById('editDishWebpBase64').value = '';
    document.getElementById('editPhotoPreviewWrap').style.display = 'none';
    document.getElementById('editPhotoPrompt').style.display = 'block';
    document.getElementById('editPresetSelect').value = '';

    document.getElementById('modalEditDish').style.display = 'flex';
  }
  function closeEditDishModal() {
    document.getElementById('modalEditDish').style.display = 'none';
  }

  // Client-Side Canvas WebP Converter: Converts any PNG/JPG/WEBP into optimized .webp
  function handleImageFileSelect(input, prefix) {
    const file = input.files && input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
      const img = new Image();
      img.onload = function() {
        // Offscreen canvas for downscaling & WebP encoding
        const canvas = document.createElement('canvas');
        const maxDim = 1000;
        let width = img.width;
        let height = img.height;

        if (width > maxDim || height > maxDim) {
          if (width > height) {
            height = Math.round((height * maxDim) / width);
            width = maxDim;
          } else {
            width = Math.round((width * maxDim) / height);
            height = maxDim;
          }
        }

        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        // Convert to WebP format with 0.85 quality
        const webpDataUrl = canvas.toDataURL('image/webp', 0.85);

        // Populate hidden base64 field
        const hiddenField = document.getElementById(`${prefix}DishWebpBase64`);
        if (hiddenField) hiddenField.value = webpDataUrl;

        // Display live preview
        const previewWrap = document.getElementById(`${prefix}PhotoPreviewWrap`);
        const previewImg = document.getElementById(`${prefix}PhotoPreviewImg`);
        const prompt = document.getElementById(`${prefix}PhotoPrompt`);
        const sizeInfo = document.getElementById(`${prefix}PhotoSizeInfo`);

        if (previewImg) previewImg.src = webpDataUrl;
        const sizeKb = Math.round((webpDataUrl.length * 3 / 4) / 1024);
        if (sizeInfo) sizeInfo.textContent = `${width}x${height} · ~${sizeKb} KB .webp`;

        if (prompt) prompt.style.display = 'none';
        if (previewWrap) previewWrap.style.display = 'flex';
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  function removeSelectedPhoto(e, prefix) {
    e.stopPropagation();
    const fileInput = document.getElementById(`${prefix}DishPhotoInput`);
    if (fileInput) fileInput.value = '';

    const hiddenField = document.getElementById(`${prefix}DishWebpBase64`);
    if (hiddenField) hiddenField.value = '';

    const previewWrap = document.getElementById(`${prefix}PhotoPreviewWrap`);
    const prompt = document.getElementById(`${prefix}PhotoPrompt`);
    if (previewWrap) previewWrap.style.display = 'none';
    if (prompt) prompt.style.display = 'block';
  }

  function handlePresetSelect(select, prefix) {
    const val = select.value;
    if (!val) return;

    // Reset file inputs
    const fileInput = document.getElementById(`${prefix}DishPhotoInput`);
    if (fileInput) fileInput.value = '';
    const hiddenField = document.getElementById(`${prefix}DishWebpBase64`);
    if (hiddenField) hiddenField.value = '';

    const previewWrap = document.getElementById(`${prefix}PhotoPreviewWrap`);
    const previewImg = document.getElementById(`${prefix}PhotoPreviewImg`);
    const prompt = document.getElementById(`${prefix}PhotoPrompt`);
    const sizeInfo = document.getElementById(`${prefix}PhotoSizeInfo`);

    if (previewImg) previewImg.src = '/' + val;
    if (sizeInfo) sizeInfo.textContent = 'Selected Library Preset';

    if (prompt) prompt.style.display = 'none';
    if (previewWrap) previewWrap.style.display = 'flex';
  }

  async function handleStockToggle(e, itemId) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById(`toggle-btn-${itemId}`);
    const card = document.getElementById(`dish-card-${itemId}`);
    const formData = new FormData(form);

    btn.disabled = true;

    try {
      const res = await fetch('menu.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });
      const data = await res.json();

      if (data.success) {
        const isAvail = data.data.is_available === 1;
        if (isAvail) {
          btn.className = 'stock-toggle-btn btn-in-stock';
          btn.innerHTML = '<span>● In Stock</span>';
          card.classList.remove('sold-out');
        } else {
          btn.className = 'stock-toggle-btn btn-sold-out';
          btn.innerHTML = '<span>✕ Sold Out (86ed)</span>';
          card.classList.add('sold-out');
        }
      } else {
        alert(data.message || 'Failed to update stock status.');
      }
    } catch (err) {
      // Fallback submit traditional form
      form.submit();
    } finally {
      btn.disabled = false;
    }
  }
  </script>

</body>
</html>
