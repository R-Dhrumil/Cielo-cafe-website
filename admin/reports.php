<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Sales, Taxes & GST Reports Hub
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Services\ReportService;
use App\Models\Setting;

// Require owner, admin, or manager role
$staff = AuthService::requireRole(['owner', 'admin', 'manager'], '/admin/login.php');

// Determine date range filter
$preset = (string)($_GET['preset'] ?? 'month');
$today = date('Y-m-d');

switch ($preset) {
    case 'today':
        $startDate = $today;
        $endDate = $today;
        break;
    case 'yesterday':
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = $startDate;
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = $today;
        break;
    case 'custom':
        $startDate = (string)($_GET['start_date'] ?? date('Y-m-01'));
        $endDate = (string)($_GET['end_date'] ?? $today);
        break;
    case 'month':
    default:
        $preset = 'month';
        $startDate = date('Y-m-01');
        $endDate = $today;
        break;
}

// Handle CSV Export
if (($_GET['action'] ?? '') === 'export_csv') {
    $csv = ReportService::generateCsv($startDate, $endDate);
    $filename = "cielo_sales_report_{$startDate}_to_{$endDate}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    echo $csv;
    exit;
}

// Retrieve data
$summary = ReportService::getFinancialSummary($startDate, $endDate);
$topItems = ReportService::getTopSellingItems($startDate, $endDate, 8);
$dailyTrend = ReportService::getDailyTrend($startDate, $endDate);

$activeNav = 'reports';
$cafeGstin = Setting::get('cafe_gstin', '24AAAAA0000A1Z5');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sales & GST Financial Studio | Cielo Cafe Admin</title>
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

    /* Main Container */
    .admin-main {
      margin-left: 260px;
      flex: 1;
      padding: 2.5rem 3rem 4rem;
      max-width: 1350px;
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

    /* Filter Bar */
    .filter-section {
      background: var(--bg-surface);
      border-radius: var(--radius-md);
      padding: 1rem 1.5rem;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      margin-bottom: 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .preset-pills {
      display: flex;
      gap: 0.5rem;
      background: var(--bg-cream);
      padding: 0.35rem;
      border-radius: 9999px;
    }

    .preset-pill {
      text-decoration: none;
      padding: 0.45rem 1.15rem;
      border-radius: 9999px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-muted);
      transition: all 0.2s ease;
    }

    .preset-pill.active {
      background: #FFFFFF;
      color: var(--text-espresso);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .custom-range-form {
      display: flex;
      align-items: center;
      gap: 0.65rem;
    }

    .custom-range-form input {
      border: 1px solid var(--border-warm);
      border-radius: 8px;
      padding: 0.45rem 0.75rem;
      font-family: inherit;
      font-size: 0.82rem;
      background: var(--bg-cream);
      outline: none;
    }

    .btn-apply {
      background: var(--text-espresso);
      color: #FFFFFF;
      border: none;
      border-radius: 8px;
      padding: 0.5rem 1rem;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-export-csv {
      background: var(--accent-terracotta);
      color: #FFFFFF;
      text-decoration: none;
      border-radius: 9999px;
      padding: 0.55rem 1.35rem;
      font-size: 0.85rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      box-shadow: 0 4px 15px rgba(201, 106, 82, 0.25);
      transition: all 0.2s ease;
    }

    .btn-export-csv:hover {
      background: #B35841;
      transform: translateY(-1px);
    }

    /* Financial Metrics Grid */
    .metric-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 1.25rem;
      margin-bottom: 2rem;
    }

    .metric-card {
      background: var(--bg-surface);
      border-radius: var(--radius-md);
      padding: 1.5rem;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      position: relative;
      overflow: hidden;
    }

    .metric-label {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-muted);
      margin-bottom: 0.4rem;
      display: block;
    }

    .metric-value {
      font-family: 'Fraunces', serif;
      font-size: 2.1rem;
      font-weight: 700;
      color: var(--text-espresso);
      line-height: 1.1;
      margin-bottom: 0.35rem;
    }

    .metric-sub {
      font-size: 0.78rem;
      color: var(--text-muted);
    }

    /* GST Card Special Highlight */
    .metric-card.gst-highlight {
      background: linear-gradient(135deg, #1A1412 0%, #2E2522 100%);
      color: #FFFFFF;
      border: none;
    }
    .metric-card.gst-highlight .metric-label { color: #D4AF37; }
    .metric-card.gst-highlight .metric-value { color: #FFFFFF; }
    .metric-card.gst-highlight .metric-sub { color: rgba(255,255,255,0.7); }

    /* Content 2-column layout */
    .content-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 2rem;
      align-items: start;
    }

    .panel-card {
      background: var(--bg-surface);
      border-radius: var(--radius-lg);
      padding: 1.75rem;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-card);
      margin-bottom: 2rem;
    }

    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      padding-bottom: 0.85rem;
      border-bottom: 1px solid var(--border-warm);
    }

    .panel-title {
      font-family: 'Fraunces', serif;
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0;
      color: var(--text-espresso);
    }

    /* Tables */
    .report-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
    }

    .report-table th {
      text-align: left;
      padding: 0.75rem 1rem;
      font-weight: 700;
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      background: var(--bg-cream);
      border-bottom: 1px solid var(--border-warm);
    }

    .report-table td {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid var(--border-warm);
      color: var(--text-espresso);
    }

    .report-table tr:last-child td {
      border-bottom: none;
    }

    /* Tender Bars */
    .tender-item {
      margin-bottom: 1.15rem;
    }
    .tender-header {
      display: flex;
      justify-content: space-between;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 0.35rem;
    }
    .tender-bar-bg {
      height: 8px;
      border-radius: 9999px;
      background: var(--bg-cream);
      overflow: hidden;
    }
    .tender-bar-fill {
      height: 100%;
      border-radius: 9999px;
      background: var(--accent-terracotta);
    }

    @media print {
      .admin-sidebar, .filter-section, .btn-export-csv { display: none !important; }
      .admin-main { margin: 0 !important; padding: 0 !important; }
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
        <h1 class="page-title">Sales & GST Financial Studio</h1>
        <p class="page-sub">
          Official Restaurant GST Records, Sales Audits & Revenue Analytics &middot; GSTIN: <strong><?= htmlspecialchars($cafeGstin) ?></strong>
        </p>
      </div>

      <div>
        <a href="?action=export_csv&preset=<?= urlencode($preset) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn-export-csv">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export Accountant CSV
        </a>
      </div>
    </div>

    <!-- Date Range & Preset Filter Bar -->
    <div class="filter-section">
      <div class="preset-pills">
        <a href="?preset=today" class="preset-pill <?= $preset === 'today' ? 'active' : '' ?>">Today</a>
        <a href="?preset=yesterday" class="preset-pill <?= $preset === 'yesterday' ? 'active' : '' ?>">Yesterday</a>
        <a href="?preset=week" class="preset-pill <?= $preset === 'week' ? 'active' : '' ?>">Last 7 Days</a>
        <a href="?preset=month" class="preset-pill <?= $preset === 'month' ? 'active' : '' ?>">Month to Date</a>
      </div>

      <form method="GET" class="custom-range-form">
        <input type="hidden" name="preset" value="custom">
        <label style="font-size:0.78rem; font-weight:700; color:var(--text-muted);">From:</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
        <label style="font-size:0.78rem; font-weight:700; color:var(--text-muted);">To:</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
        <button type="submit" class="btn-apply">Filter</button>
      </form>
    </div>

    <!-- Key Metrics Grid -->
    <div class="metric-grid">
      <!-- 1. Grand Total Sales -->
      <div class="metric-card">
        <span class="metric-label">Gross Revenue Billed</span>
        <div class="metric-value"><?= $summary['grand_total_formatted'] ?></div>
        <div class="metric-sub"><?= number_format($summary['orders_count']) ?> settled dining orders</div>
      </div>

      <!-- 2. Taxable Turnover -->
      <div class="metric-card">
        <span class="metric-label">Net Taxable Turnover</span>
        <div class="metric-value"><?= $summary['taxable_turnover_formatted'] ?></div>
        <div class="metric-sub">Gross Subtotal minus applied discounts</div>
      </div>

      <!-- 3. GST 5% Split Card (Special Highlight) -->
      <div class="metric-card gst-highlight">
        <span class="metric-label">5% Restaurant GST Collected</span>
        <div class="metric-value" style="color:#D4AF37;"><?= $summary['total_tax_formatted'] ?></div>
        <div class="metric-sub">
          CGST (2.5%): <strong><?= $summary['cgst_formatted'] ?></strong> &middot; SGST (2.5%): <strong><?= $summary['sgst_formatted'] ?></strong>
        </div>
      </div>

      <!-- 4. Discounts Absorbed -->
      <div class="metric-card">
        <span class="metric-label">Promotional Discounts</span>
        <div class="metric-value" style="color:var(--accent-terracotta);"><?= $summary['total_discount_formatted'] ?></div>
        <div class="metric-sub">Coupons & milestone vouchers applied</div>
      </div>

      <!-- 5. Average Order Value -->
      <div class="metric-card">
        <span class="metric-label">Average Order Value (AOV)</span>
        <div class="metric-value"><?= $summary['aov_formatted'] ?></div>
        <div class="metric-sub">Per completed customer bill</div>
      </div>
    </div>

    <!-- Two Column Analysis Layout -->
    <div class="content-grid">
      
      <!-- Left Column: Day-by-Day Financial Ledger -->
      <div>
        <div class="panel-card">
          <div class="panel-header">
            <h2 class="panel-title">Daily Sales & Tax Ledger</h2>
            <span style="font-size:0.8rem; color:var(--text-muted);"><?= count($dailyTrend) ?> Active Days</span>
          </div>

          <?php if (empty($dailyTrend)): ?>
            <div style="padding:2.5rem; text-align:center; color:var(--text-muted);">
              No completed dining transactions recorded in this period.
            </div>
          <?php else: ?>
            <table class="report-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Orders</th>
                  <th>Net Taxable (₹)</th>
                  <th>Total 5% GST (₹)</th>
                  <th style="text-align:right;">Total Billed (₹)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dailyTrend as $dt): ?>
                  <?php $dayTaxable = max(0, $dt['subtotal_paise'] - $dt['discount_paise']); ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($dt['formatted_date']) ?></strong></td>
                    <td><?= $dt['orders_count'] ?></td>
                    <td>₹<?= number_format($dayTaxable / 100, 2) ?></td>
                    <td>₹<?= number_format($dt['tax_paise'] / 100, 2) ?></td>
                    <td style="text-align:right; font-weight:700;"><?= $dt['total_formatted'] ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right Column: Payment Tenders & Top Selling Dishes -->
      <div>
        
        <!-- Payment Mode Distribution -->
        <div class="panel-card">
          <div class="panel-header">
            <h2 class="panel-title">Tender Breakdown</h2>
          </div>

          <?php foreach ($summary['payment_tenders'] as $pt): ?>
            <div class="tender-item">
              <div class="tender-header">
                <span><?= htmlspecialchars($pt['name']) ?></span>
                <span><?= $pt['formatted'] ?> <small style="color:var(--text-muted);">(<?= $pt['percent'] ?>%)</small></span>
              </div>
              <div class="tender-bar-bg">
                <div class="tender-bar-fill" style="width: <?= $pt['percent'] ?>%;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Top Selling Dishes Leaderboard -->
        <div class="panel-card">
          <div class="panel-header">
            <h2 class="panel-title">Top Selling Dishes</h2>
          </div>

          <?php if (empty($topItems)): ?>
            <div style="padding:1.5rem; text-align:center; color:var(--text-muted); font-size:0.85rem;">
              No sales logged in this period.
            </div>
          <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:0.85rem;">
              <?php foreach ($topItems as $idx => $it): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem;">
                  <div style="display:flex; align-items:center; gap:0.6rem;">
                    <span style="font-weight:800; font-family:'Fraunces', serif; color:var(--accent-terracotta); width:18px;">#<?= $idx + 1 ?></span>
                    <div>
                      <div style="font-weight:700; color:var(--text-espresso);"><?= htmlspecialchars($it['item_name']) ?></div>
                      <div style="font-size:0.72rem; color:var(--text-muted);"><?= htmlspecialchars($it['category_name']) ?></div>
                    </div>
                  </div>
                  <div style="text-align:right;">
                    <div style="font-weight:700;"><?= $it['total_revenue_formatted'] ?></div>
                    <div style="font-size:0.72rem; color:var(--text-muted);"><?= $it['total_qty'] ?> sold</div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>

    </div>

  </main>

</body>
</html>
