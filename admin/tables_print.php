<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Admin Printable Table QR Studio
 * Generates luxury print-ready table tent cards with SVG vector QR codes.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Table;
use App\Services\TableSessionService;
use App\Core\QrCodeGenerator;

$filterId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($filterId) {
    $table = Table::find($filterId);
    $tables = $table ? [$table] : [];
} else {
    $tables = Table::where('`is_active` = 1', [], 'CAST(table_number AS UNSIGNED) ASC, table_number ASC');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Printable Table QR Cards | Cielo Cafe and Restro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..800;1,9..144,300..800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --text-espresso: #1A1412;
      --accent-terracotta: #C96A52;
      --accent-sky: #6B9AC4;
      --text-muted: #6E6259;
      --border-warm: rgba(26, 20, 18, 0.15);
    }
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 2rem;
      background: #EFECE6;
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .studio-header {
      max-width: 1000px;
      margin: 0 auto 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #FFFFFF;
      padding: 1.25rem 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .studio-title {
      margin: 0;
      font-family: 'Fraunces', serif;
      font-size: 1.5rem;
      color: var(--text-espresso);
    }
    .studio-subtitle {
      margin: 0.25rem 0 0;
      font-size: 0.85rem;
      color: var(--text-muted);
    }
    .studio-actions {
      display: flex;
      gap: 0.75rem;
    }
    .btn-print {
      background: var(--text-espresso);
      color: #FFFFFF;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 999px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 10px rgba(26, 20, 18, 0.15);
      transition: background 0.15s ease;
    }
    .btn-print:hover {
      background: #2C221F;
    }
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
      gap: 2rem;
      max-width: 1200px;
      margin: 0 auto;
    }

    /* Table Standee / Tent Card (A6 / 4x6 proportion) */
    .tent-card {
      background: var(--bg-cream);
      border: 2px solid var(--text-espresso);
      border-radius: 20px;
      padding: 2.25rem 2rem;
      text-align: center;
      position: relative;
      box-shadow: 0 10px 25px rgba(26, 20, 18, 0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      min-height: 520px;
      page-break-inside: avoid;
    }
    .card-ornament {
      font-size: 0.75rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--accent-terracotta);
      font-weight: 700;
      margin-bottom: 0.25rem;
    }
    .cafe-brand {
      font-family: 'Fraunces', serif;
      font-size: 2.1rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--text-espresso);
      margin: 0;
      line-height: 1.1;
    }
    .cafe-tagline {
      font-size: 0.8rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.12em;
      margin: 0.25rem 0 1.5rem;
    }
    .qr-container {
      background: #FFFFFF;
      padding: 1.25rem;
      border-radius: 16px;
      border: 1px solid var(--border-warm);
      box-shadow: 0 8px 20px rgba(26, 20, 18, 0.06);
      display: inline-flex;
      margin-bottom: 1.25rem;
    }
    .qr-container svg {
      display: block;
      width: 200px;
      height: 200px;
    }
    .table-headline {
      font-family: 'Fraunces', serif;
      font-size: 1.85rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
    }
    .table-location {
      display: inline-block;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--accent-sky);
      background: rgba(107, 154, 196, 0.12);
      padding: 0.2rem 0.65rem;
      border-radius: 6px;
      margin: 0.35rem 0 1rem;
    }
    .instruction-text {
      font-size: 0.88rem;
      line-height: 1.45;
      color: var(--text-muted);
      margin: 0 0 1.25rem;
      max-width: 280px;
    }
    .card-footer-info {
      width: 100%;
      border-top: 1px dashed rgba(26, 20, 18, 0.2);
      padding-top: 1rem;
      display: flex;
      justify-content: space-between;
      font-size: 0.75rem;
      color: var(--text-muted);
      font-weight: 500;
    }
    .wifi-pill, .insta-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
    }

    /* Print Stylesheet */
    @media print {
      body {
        background: #FFFFFF;
        padding: 0;
      }
      .no-print {
        display: none !important;
      }
      .cards-grid {
        display: block;
      }
      .tent-card {
        box-shadow: none;
        border: 2px solid #000000;
        margin: 0 auto 3rem;
        page-break-after: always;
        width: 100%;
        max-width: 420px;
      }
    }
  </style>
</head>
<body>

  <!-- Screen Header Toolbar (Hidden during print) -->
  <header class="studio-header no-print">
    <div>
      <h1 class="studio-title">Cielo Table QR Print Studio</h1>
      <p class="studio-subtitle">
        Showing <?= count($tables) ?> table standees · Scalable vector QR codes with tamper-proof tokens
      </p>
    </div>
    <div class="studio-actions">
      <button type="button" class="btn-print" onclick="window.print()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
        <span>Print Standees</span>
      </button>
    </div>
  </header>

  <!-- Table Tent Cards Grid -->
  <main class="cards-grid">
    <?php foreach ($tables as $t): ?>
      <?php 
        $scanUrl = TableSessionService::generateScanUrl((string)$t['qr_token']);
        $svgMarkup = QrCodeGenerator::generateSvg($scanUrl, 200, '#1A1412', '#FFFFFF', 1);
      ?>
      <article class="tent-card">
        <div>
          <div class="card-ornament">· Artisanal Italian Sanctuary ·</div>
          <h2 class="cafe-brand">CIELO</h2>
          <div class="cafe-tagline">Restro & Cafe · Vadodara</div>
        </div>

        <div class="qr-container">
          <?= $svgMarkup ?>
        </div>

        <div>
          <h3 class="table-headline">TABLE <?= e($t['table_number']) ?></h3>
          <span class="table-location"><?= e($t['floor'] ?? ((int)$t['table_number'] > 6 ? 'Upper Patio' : 'Ground Floor')) ?> · Capacity <?= (int)$t['capacity'] ?></span>
          <p class="instruction-text">
            Point your phone camera at the QR code to explore the artisanal menu, order directly & earn loyalty points.
          </p>
        </div>

        <div class="card-footer-info">
          <span class="wifi-pill">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/></svg>
            WiFi: Cielo_Guest / cielo2026
          </span>
          <span class="insta-pill">
            @cielocafebistro
          </span>
        </div>
      </article>
    <?php endforeach; ?>
  </main>

</body>
</html>
