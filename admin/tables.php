<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Table & QR Code Management Studio
 *
 * Full Admin CRUD:
 * - Add new tables with custom names as admin wants
 * - Rename tables, set seating capacity, and designate dining floors/sections
 * - Add, remove, or regenerate high-entropy cryptographic QR codes
 * - Print individual or batch luxury tent cards
 * - Enforce tamper-proof session resolution and authoritative pricing
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Models\Table;
use App\Models\AuditLog;
use App\Core\Security;
use App\Services\TableSessionService;

// Require owner or admin role
$staff = AuthService::requireRole(['owner', 'admin'], '/admin/login.php');

$msg = '';
$err = '';

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!Security::verifyCsrfToken($csrf)) {
        $err = 'Security token expired. Please refresh the page.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'add_table') {
            $name = trim((string)($_POST['table_number'] ?? ''));
            $capacity = max(1, (int)($_POST['capacity'] ?? 4));
            $floor = '';
            $generateQr = !empty($_POST['generate_qr']);

            if ($name === '') {
                $err = 'Please enter a name or number for the table.';
            } else {
                // Check if table name already exists
                $existing = Table::findBy('table_number', $name);
                if ($existing) {
                    $err = "A table named '{$name}' already exists. Please choose a unique name.";
                } else {
                    $newId = Table::createTable($name, $capacity, $floor, $generateQr);
                    AuditLog::log((int)$staff['id'], 'table_created', 'tables', (int)$newId, [
                        'name' => $name,
                        'capacity' => $capacity,
                        'qr_generated' => $generateQr,
                    ]);
                    $msg = "Table '{$name}' created successfully" . ($generateQr ? ' with an active QR code.' : '.');
                }
            }
        } elseif ($action === 'edit_table') {
            $tableId = (int)($_POST['table_id'] ?? 0);
            $name = trim((string)($_POST['table_number'] ?? ''));
            $capacity = max(1, (int)($_POST['capacity'] ?? 4));
            $floor = '';
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

            if ($tableId <= 0) {
                $err = 'Invalid table specified.';
            } elseif ($name === '') {
                $err = 'Table name cannot be empty.';
            } else {
                // Check duplicate name on other tables
                $other = Table::first('`table_number` = :num AND `id` != :id', ['num' => $name, 'id' => $tableId]);
                if ($other) {
                    $err = "Another table already uses the name '{$name}'.";
                } else {
                    Table::renameTable($tableId, $name, $capacity, $floor, $isActive);
                    AuditLog::log((int)$staff['id'], 'table_updated', 'tables', $tableId, [
                        'name' => $name,
                        'capacity' => $capacity,
                        'is_active' => $isActive,
                    ]);
                    $msg = "Table '{$name}' updated successfully.";
                }
            }
        } elseif ($action === 'remove_qr') {
            $tableId = (int)($_POST['table_id'] ?? 0);
            $target = Table::find($tableId);

            if ($target) {
                Table::removeQrToken($tableId);
                AuditLog::log((int)$staff['id'], 'table_qr_removed', 'tables', $tableId, [
                    'table_number' => $target['table_number'],
                ]);
                $msg = "QR code removed for Table '{$target['table_number']}'. Any customer scanning the old QR will immediately be prevented from placing orders.";
            } else {
                $err = 'Table not found.';
            }
        } elseif ($action === 'generate_qr' || $action === 'regenerate_qr') {
            $tableId = (int)($_POST['table_id'] ?? 0);
            $target = Table::find($tableId);

            if ($target) {
                $newToken = Table::regenerateQrToken($tableId);
                AuditLog::log((int)$staff['id'], 'table_qr_generated', 'tables', $tableId, [
                    'table_number' => $target['table_number'],
                    'new_token' => $newToken,
                ]);
                $msg = "Fresh cryptographic QR code generated for Table '{$target['table_number']}'. Old QR codes are now deactivated.";
            } else {
                $err = 'Table not found.';
            }
        } elseif ($action === 'toggle_active') {
            $tableId = (int)($_POST['table_id'] ?? 0);
            $target = Table::find($tableId);

            if ($target) {
                $newActive = ((int)$target['is_active'] === 1) ? 0 : 1;
                Table::toggleActive($tableId, (bool)$newActive);
                AuditLog::log((int)$staff['id'], 'table_status_toggled', 'tables', $tableId, [
                    'table_number' => $target['table_number'],
                    'new_status' => $newActive ? 'active' : 'inactive',
                ]);
                $msg = "Table '{$target['table_number']}' has been " . ($newActive ? 'activated' : 'deactivated') . ".";
            } else {
                $err = 'Table not found.';
            }
        } elseif ($action === 'delete_table') {
            $tableId = (int)($_POST['table_id'] ?? 0);
            $result = Table::deleteTableSafe($tableId);
            if ($result['success']) {
                $msg = $result['message'];
            } else {
                $err = $result['message'];
            }
        }
    }
}

// Fetch all tables sorted by number/name
$allTables = Table::all('CAST(table_number AS UNSIGNED) ASC, table_number ASC');

// Calculate KPIs
$totalTables = count($allTables);
$activeQrCount = 0;
$disabledQrCount = 0;
$totalCapacity = 0;

foreach ($allTables as $t) {
    if ((int)($t['is_active'] ?? 0) === 1 && Table::hasActiveQr($t)) {
        $activeQrCount++;
    } else {
        $disabledQrCount++;
    }
    $totalCapacity += (int)($t['capacity'] ?? 0);
}

$csrfToken = Security::generateCsrfToken();
$activeNav = 'tables';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Table & QR Code Studio | Cielo Cafe and Restro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..800;1,9..144,400..800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --card-bg: #FFFFFF;
      --text-espresso: #1A1412;
      --accent-terracotta: #C96A52;
      --accent-sky: #6B9AC4;
      --accent-green: #2E7D32;
      --accent-gold: #B38F4D;
      --text-muted: #6E6259;
      --border-warm: rgba(26, 20, 18, 0.12);
      --shadow-sm: 0 4px 12px rgba(26, 20, 18, 0.04);
      --shadow-md: 0 10px 25px rgba(26, 20, 18, 0.08);
      --radius-lg: 16px;
      --radius-full: 999px;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 0;
      background: var(--bg-cream);
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
    }

    /* Admin Layout */
    .admin-main {
      margin-left: 260px;
      width: calc(100% - 260px);
      padding: 2.25rem 2.5rem;
      min-height: 100vh;
    }
    @media (max-width: 900px) {
      .admin-main {
        margin-left: 0;
        width: 100%;
        padding: 1.25rem 1rem;
      }
    }

    /* Top Studio Header */
    .studio-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .studio-eyebrow {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-terracotta);
      font-weight: 700;
      margin-bottom: 0.25rem;
    }
    .studio-title {
      font-family: 'Fraunces', serif;
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      letter-spacing: -0.02em;
    }
    .studio-actions {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    /* Action Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.65rem 1.25rem;
      border-radius: var(--radius-full);
      font-size: 0.88rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.2s ease;
      border: 1px solid transparent;
    }
    .btn-primary {
      background: var(--text-espresso);
      color: #FFFFFF;
    }
    .btn-primary:hover {
      background: #2C221F;
      transform: translateY(-1px);
      box-shadow: var(--shadow-sm);
    }
    .btn-terracotta {
      background: var(--accent-terracotta);
      color: #FFFFFF;
    }
    .btn-terracotta:hover {
      background: #B55B44;
      transform: translateY(-1px);
    }
    .btn-outline {
      background: #FFFFFF;
      color: var(--text-espresso);
      border-color: var(--border-warm);
    }
    .btn-outline:hover {
      background: rgba(26, 20, 18, 0.04);
      border-color: rgba(26, 20, 18, 0.2);
    }
    .btn-sm {
      padding: 0.45rem 0.85rem;
      font-size: 0.8rem;
    }
    .btn-danger-outline {
      background: #FFFFFF;
      color: #C62828;
      border-color: rgba(198, 40, 40, 0.25);
    }
    .btn-danger-outline:hover {
      background: rgba(198, 40, 40, 0.06);
    }

    /* KPI Summary Row */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2rem;
    }
    .kpi-card {
      background: var(--card-bg);
      border: 1px solid var(--border-warm);
      border-radius: var(--radius-lg);
      padding: 1.25rem 1.5rem;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }
    .kpi-label {
      font-size: 0.82rem;
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .kpi-value {
      font-family: 'Fraunces', serif;
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-espresso);
      line-height: 1;
    }
    .kpi-sub {
      font-size: 0.75rem;
      color: var(--text-muted);
    }

    /* Search & Filter Toolbar */
    .toolbar-row {
      background: var(--card-bg);
      border: 1px solid var(--border-warm);
      border-radius: var(--radius-lg);
      padding: 0.85rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1.75rem;
      box-shadow: var(--shadow-sm);
    }
    .search-box {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      background: rgba(26, 20, 18, 0.04);
      padding: 0.5rem 1rem;
      border-radius: var(--radius-full);
      border: 1px solid var(--border-warm);
      min-width: 260px;
    }
    .search-box input {
      border: none;
      background: transparent;
      outline: none;
      font-family: inherit;
      font-size: 0.88rem;
      color: var(--text-espresso);
      width: 100%;
    }
    .filter-pills {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    .filter-pill {
      background: transparent;
      border: 1px solid var(--border-warm);
      color: var(--text-muted);
      padding: 0.4rem 0.9rem;
      border-radius: var(--radius-full);
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .filter-pill.active, .filter-pill:hover {
      background: var(--text-espresso);
      color: #FFFFFF;
      border-color: var(--text-espresso);
    }

    /* Tables Grid */
    .tables-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
      gap: 1.5rem;
    }

    /* Table Card */
    .table-card {
      background: var(--card-bg);
      border: 1px solid var(--border-warm);
      border-radius: 20px;
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      position: relative;
    }
    .table-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }
    .table-card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }
    .table-title {
      font-family: 'Fraunces', serif;
      font-size: 1.45rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .table-floor-tag {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--accent-sky);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-top: 0.25rem;
      display: block;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.25rem 0.65rem;
      border-radius: var(--radius-full);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.02em;
    }
    .status-active {
      background: rgba(46, 125, 50, 0.12);
      color: var(--accent-green);
    }
    .status-disabled {
      background: rgba(201, 106, 82, 0.12);
      color: var(--accent-terracotta);
    }
    .status-inactive {
      background: rgba(26, 20, 18, 0.08);
      color: var(--text-muted);
    }

    /* QR Preview Section */
    .qr-preview-box {
      background: #FAF8F5;
      border: 1px dashed rgba(26, 20, 18, 0.18);
      border-radius: 14px;
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.25rem;
      min-height: 220px;
      text-align: center;
    }
    .qr-preview-img {
      width: 140px;
      height: 140px;
      background: #FFFFFF;
      padding: 8px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      margin-bottom: 0.75rem;
    }
    .qr-empty-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: rgba(201, 106, 82, 0.12);
      color: var(--accent-terracotta);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 0.75rem;
    }
    .qr-empty-title {
      font-weight: 700;
      font-size: 0.95rem;
      margin: 0 0 0.35rem;
      color: var(--text-espresso);
    }
    .qr-empty-desc {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin: 0 0 1rem;
      max-width: 220px;
      line-height: 1.4;
    }
    .qr-url-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: #FFFFFF;
      border: 1px solid var(--border-warm);
      padding: 0.35rem 0.75rem;
      border-radius: var(--radius-full);
      font-size: 0.74rem;
      color: var(--text-muted);
      font-family: monospace;
      cursor: pointer;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      transition: all 0.15s ease;
    }
    .qr-url-chip:hover {
      background: rgba(26, 20, 18, 0.05);
      color: var(--text-espresso);
    }

    /* Card Details List */
    .table-meta-list {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.65rem 0;
      border-top: 1px solid rgba(26, 20, 18, 0.06);
      border-bottom: 1px solid rgba(26, 20, 18, 0.06);
      margin-bottom: 1rem;
      font-size: 0.82rem;
    }
    .table-meta-item {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      color: var(--text-muted);
    }
    .table-meta-item strong {
      color: var(--text-espresso);
    }

    /* Card Actions Menu */
    .table-card-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.6rem;
    }
    .table-card-actions-full {
      grid-column: 1 / -1;
      display: flex;
      gap: 0.5rem;
    }

    /* Banner Alerts */
    .alert {
      padding: 1rem 1.5rem;
      border-radius: var(--radius-lg);
      margin-bottom: 1.75rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.9rem;
      font-weight: 500;
    }
    .alert-success {
      background: #E8F5E9;
      color: #2E7D32;
      border: 1px solid rgba(46, 125, 50, 0.2);
    }
    .alert-error {
      background: #FFEBEE;
      color: #C62828;
      border: 1px solid rgba(198, 40, 40, 0.2);
    }

    /* Modal Component */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(26, 20, 18, 0.65);
      backdrop-filter: blur(4px);
      -webkit-backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      padding: 1.5rem;
    }
    .modal-overlay.open {
      display: flex;
    }
    .modal-dialog {
      background: #FFFFFF;
      border-radius: 24px;
      max-width: 480px;
      width: 100%;
      padding: 2.25rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.25);
      position: relative;
      animation: modalSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes modalSlide {
      from { transform: translateY(15px) scale(0.98); opacity: 0; }
      to { transform: translateY(0) scale(1); opacity: 1; }
    }
    .modal-title {
      font-family: 'Fraunces', serif;
      font-size: 1.5rem;
      margin: 0 0 0.5rem;
      color: var(--text-espresso);
    }
    .modal-subtitle {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin: 0 0 1.5rem;
    }
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-label {
      display: block;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin-bottom: 0.4rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .form-input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border-warm);
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.92rem;
      color: var(--text-espresso);
      outline: none;
      transition: border-color 0.2s ease;
    }
    .form-input:focus {
      border-color: var(--text-espresso);
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .form-checkbox-row {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      margin-top: 0.5rem;
      font-size: 0.88rem;
      color: var(--text-espresso);
      cursor: pointer;
    }
    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      margin-top: 1.75rem;
    }
  </style>
</head>
<body>

  <!-- Left Sidebar -->
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Main Content Area -->
  <main class="admin-main">

    <!-- Alerts -->
    <?php if ($msg !== ''): ?>
      <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span><?= e($msg) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($err !== ''): ?>
      <div class="alert alert-error">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?= e($err) ?></span>
      </div>
    <?php endif; ?>

    <!-- Studio Header -->
    <div class="studio-header">
      <div>
        <div class="studio-eyebrow">Dining Room Operations</div>
        <h1 class="studio-title">Table & QR Code Studio</h1>
      </div>

      <div class="studio-actions">
        <button type="button" onclick="openAddTableModal()" class="btn btn-primary">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span>Add New Table</span>
        </button>

        <a href="/admin/tables_print.php" target="_blank" class="btn btn-outline">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          <span>Print All Standees</span>
        </a>
      </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <span class="kpi-label">Total Tables</span>
        <span class="kpi-value"><?= $totalTables ?></span>
        <span class="kpi-sub">Registered dining spots</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">Active QR Codes</span>
        <span class="kpi-value" style="color: var(--accent-green);"><?= $activeQrCount ?></span>
        <span class="kpi-sub">Ready for live diner scans</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">QR Codes Disabled</span>
        <span class="kpi-value" style="color: var(--accent-terracotta);"><?= $disabledQrCount ?></span>
        <span class="kpi-sub">Removed or unassigned</span>
      </div>
      <div class="kpi-card">
        <span class="kpi-label">Seating Capacity</span>
        <span class="kpi-value"><?= $totalCapacity ?></span>
        <span class="kpi-sub">Total simultaneous patrons</span>
      </div>
    </div>

    <!-- Search & Filter Row -->
    <div class="toolbar-row">
      <div class="search-box">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="tableSearch" placeholder="Search table name or number..." onkeyup="filterTables()">
      </div>

      <div class="filter-pills">
        <button type="button" class="filter-pill active" onclick="setFilter('all', this)">All (<?= $totalTables ?>)</button>
        <button type="button" class="filter-pill" onclick="setFilter('active-qr', this)">Active QR (<?= $activeQrCount ?>)</button>
        <button type="button" class="filter-pill" onclick="setFilter('no-qr', this)">QR Disabled (<?= $disabledQrCount ?>)</button>
      </div>
    </div>

    <!-- Tables Grid -->
    <div class="tables-grid" id="tablesContainer">
      <?php foreach ($allTables as $tbl): ?>
        <?php
          $tblId = (int)$tbl['id'];
          $name = (string)$tbl['table_number'];
          $capacity = (int)($tbl['capacity'] ?? 4);
          $isActive = (int)($tbl['is_active'] ?? 0) === 1;
          $hasQr = Table::hasActiveQr($tbl);
          $token = (string)($tbl['qr_token'] ?? '');
          $orderCount = Table::getOrderCount($tblId);
          $scanUrl = $hasQr ? TableSessionService::generateScanUrl($token) : '';

          $statusClass = $hasQr && $isActive ? 'active-qr' : 'no-qr';
        ?>
        <div class="table-card" data-table-name="<?= strtolower(e($name)) ?>" data-status="<?= $statusClass ?>">
          
          <!-- Top Row -->
          <div>
            <div class="table-card-top">
              <div>
                <h3 class="table-title">
                  <?= e($name) ?>
                </h3>
              </div>

              <div>
                <?php if ($hasQr && $isActive): ?>
                  <span class="status-badge status-active">
                    <span style="width: 7px; height: 7px; border-radius: 50%; background: currentColor;"></span>
                    QR Active
                  </span>
                <?php elseif (!$hasQr): ?>
                  <span class="status-badge status-disabled">
                    QR Removed
                  </span>
                <?php else: ?>
                  <span class="status-badge status-inactive">
                    Table Inactive
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <!-- QR Preview Area -->
            <div class="qr-preview-box">
              <?php if ($hasQr): ?>
                <img src="/admin/table_qr.php?id=<?= $tblId ?>&size=200" alt="QR Code for <?= e($name) ?>" class="qr-preview-img" loading="lazy">
                
                <div class="qr-url-chip" title="Click to copy scan link" onclick="copyScanLink('<?= e($scanUrl) ?>')">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                  <span><?= e(substr($scanUrl, strpos($scanUrl, '/t.php') ?: 0)) ?></span>
                </div>
              <?php else: ?>
                <div class="qr-empty-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
                </div>
                <div class="qr-empty-title">QR Code Removed</div>
                <p class="qr-empty-desc">Old QR link is dead. Customers cannot order until a new QR is generated.</p>
                
                <form method="POST" style="margin: 0;">
                  <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="generate_qr">
                  <input type="hidden" name="table_id" value="<?= $tblId ?>">
                  <button type="submit" class="btn btn-terracotta btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    <span>Generate QR Code</span>
                  </button>
                </form>
              <?php endif; ?>
            </div>

            <!-- Table Meta Row -->
            <div class="table-meta-list">
              <div class="table-meta-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>Capacity: <strong><?= $capacity ?> Guests</strong></span>
              </div>
              <div class="table-meta-item">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                <span>Orders: <strong><?= $orderCount ?></strong></span>
              </div>
            </div>
          </div>

          <!-- Bottom Actions -->
          <div class="table-card-actions">
            
            <!-- Edit / Rename Table -->
            <button type="button" class="btn btn-outline btn-sm" onclick="openEditModal(<?= $tblId ?>, '<?= e(addslashes($name)) ?>', <?= $capacity ?>, <?= $isActive ? 1 : 0 ?>)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              <span>Rename / Edit</span>
            </button>

            <!-- Print Standee -->
            <?php if ($hasQr): ?>
              <a href="/admin/tables_print.php?id=<?= $tblId ?>" target="_blank" class="btn btn-outline btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                <span>Print Standee</span>
              </a>
            <?php else: ?>
              <button type="button" class="btn btn-outline btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">
                <span>Print Disabled</span>
              </button>
            <?php endif; ?>

            <!-- QR Specific Actions -->
            <?php if ($hasQr): ?>
              <!-- Regenerate QR -->
              <form method="POST" onsubmit="return confirm('Regenerate QR Code for Table \'<?= e(addslashes($name)) ?>\'? This will immediately invalidate the current printed QR code.');" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="regenerate_qr">
                <input type="hidden" name="table_id" value="<?= $tblId ?>">
                <button type="submit" class="btn btn-outline btn-sm" style="width: 100%;" title="Issue fresh token if current QR compromised or reprinted">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                  <span>Reset QR</span>
                </button>
              </form>

              <!-- Remove QR -->
              <form method="POST" onsubmit="return confirm('Remove QR Code from Table \'<?= e(addslashes($name)) ?>\'? Diners will no longer be able to scan or place orders from this table.');" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="remove_qr">
                <input type="hidden" name="table_id" value="<?= $tblId ?>">
                <button type="submit" class="btn btn-danger-outline btn-sm" style="width: 100%;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                  <span>Remove QR</span>
                </button>
              </form>
            <?php else: ?>
              <!-- Delete Table Option when no QR -->
              <form method="POST" onsubmit="return confirm('Are you sure you want to delete Table \'<?= e(addslashes($name)) ?>\'?');" style="margin: 0; grid-column: 1 / -1;">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="delete_table">
                <input type="hidden" name="table_id" value="<?= $tblId ?>">
                <button type="submit" class="btn btn-danger-outline btn-sm" style="width: 100%;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  <span>Delete Table</span>
                </button>
              </form>
            <?php endif; ?>

          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </main>

  <!-- Add Table Modal -->
  <div class="modal-overlay" id="addTableModal" onclick="closeModalOnBackdrop(event, this)">
    <div class="modal-dialog">
      <h2 class="modal-title">Add Dining Table</h2>
      <p class="modal-subtitle">Give the table a name or number, set seating capacity, and issue an active QR code.</p>

      <form method="POST" action="/admin/tables.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="add_table">

        <div class="form-group">
          <label class="form-label" for="addTableName">Table Name or Number *</label>
          <input type="text" id="addTableName" name="table_number" class="form-input" placeholder="E.g. Table 11, Table A, Booth 3..." required>
        </div>

        <div class="form-group">
          <label class="form-label" for="addTableCapacity">Seating Capacity</label>
          <input type="number" id="addTableCapacity" name="capacity" class="form-input" min="1" max="50" value="4">
        </div>

        <div class="form-group">
          <label class="form-checkbox-row">
            <input type="checkbox" name="generate_qr" value="1" checked style="width: 18px; height: 18px; accent-color: var(--text-espresso);">
            <span>Generate Active QR Code Immediately</span>
          </label>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeAddTableModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Table</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Table Modal -->
  <div class="modal-overlay" id="editTableModal" onclick="closeModalOnBackdrop(event, this)">
    <div class="modal-dialog">
      <h2 class="modal-title">Rename & Edit Table</h2>
      <p class="modal-subtitle">Update table name, seating capacity, and active status.</p>

      <form method="POST" action="/admin/tables.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="edit_table">
        <input type="hidden" name="table_id" id="editTableId" value="0">

        <div class="form-group">
          <label class="form-label" for="editTableName">Table Name or Number *</label>
          <input type="text" id="editTableName" name="table_number" class="form-input" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="editTableCapacity">Seating Capacity</label>
          <input type="number" id="editTableCapacity" name="capacity" class="form-input" min="1" max="50">
        </div>

        <div class="form-group">
          <label class="form-label" for="editTableStatus">Table Operational Status</label>
          <select id="editTableStatus" name="is_active" class="form-input">
            <option value="1">Active (Open for dining)</option>
            <option value="0">Inactive / Closed</option>
          </select>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal Helpers
    function openAddTableModal() {
      document.getElementById('addTableModal').classList.add('open');
      setTimeout(() => document.getElementById('addTableName').focus(), 100);
    }
    function closeAddTableModal() {
      document.getElementById('addTableModal').classList.remove('open');
    }
    function openEditModal(id, name, capacity, isActive) {
      document.getElementById('editTableId').value = id;
      document.getElementById('editTableName').value = name;
      document.getElementById('editTableCapacity').value = capacity;
      document.getElementById('editTableStatus').value = isActive ? '1' : '0';
      document.getElementById('editTableModal').classList.add('open');
      setTimeout(() => document.getElementById('editTableName').focus(), 100);
    }
    function closeEditModal() {
      document.getElementById('editTableModal').classList.remove('open');
    }
    function closeModalOnBackdrop(e, modal) {
      if (e.target === modal) {
        modal.classList.remove('open');
      }
    }

    // Client-side instant filter & search
    let currentFilter = 'all';

    function setFilter(type, btn) {
      currentFilter = type;
      document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      filterTables();
    }

    function filterTables() {
      const q = document.getElementById('tableSearch').value.toLowerCase().trim();
      const cards = document.querySelectorAll('.table-card');

      cards.forEach(card => {
        const name = card.getAttribute('data-table-name') || '';
        const status = card.getAttribute('data-status') || '';

        const matchesQuery = name.includes(q);
        const matchesFilter = (currentFilter === 'all') || (status === currentFilter);

        if (matchesQuery && matchesFilter) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Copy Scan Link Helper
    function copyScanLink(url) {
      if (!url) return;
      navigator.clipboard.writeText(url).then(() => {
        alert('Table scan link copied to clipboard:\n' + url);
      }).catch(() => {
        prompt('Copy Table Scan URL:', url);
      });
    }

    // Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeAddTableModal();
        closeEditModal();
      }
    });
  </script>
</body>
</html>
