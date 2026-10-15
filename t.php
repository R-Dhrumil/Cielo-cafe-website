<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Table QR Landing & Session Resolver
 *
 * Route: /t.php?token={qr_token} or /t/{qr_token}
 * Verifies table cryptographic token, binds table session, and redirects to menu.
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\Services\TableSessionService;
use App\Models\Table;

$token = trim((string)($_GET['token'] ?? ''));

// Validate and start session
$tableSession = null;
if ($token !== '') {
    $tableSession = TableSessionService::startSession($token);
}

// If valid, immediately redirect to menu with table bound
if ($tableSession !== null) {
    header('Location: /menu.php?table_welcome=1');
    exit;
}

// If invalid or missing, render branded friendly error page
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invalid Table QR | Cielo Cafe and Restro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..800;1,9..144,300..800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --text-espresso: #1A1412;
      --accent-terracotta: #C96A52;
      --accent-sky: #6B9AC4;
      --text-muted: #6E6259;
      --card-bg: rgba(255, 255, 255, 0.85);
      --border-warm: rgba(26, 20, 18, 0.12);
    }
    body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--bg-cream);
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-image: 
        radial-gradient(circle at 10% 20%, rgba(107, 154, 196, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(201, 106, 82, 0.08) 0%, transparent 40%);
    }
    .error-card {
      max-width: 440px;
      margin: 1.5rem;
      padding: 2.5rem 2rem;
      background: var(--card-bg);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid var(--border-warm);
      border-radius: 20px;
      text-align: center;
      box-shadow: 0 20px 40px rgba(26, 20, 18, 0.06);
    }
    .brand-mark {
      font-family: 'Fraunces', serif;
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-espresso);
      letter-spacing: -0.02em;
      margin-bottom: 0.25rem;
    }
    .brand-subtitle {
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent-sky);
      font-weight: 600;
      margin-bottom: 2rem;
    }
    .icon-box {
      width: 64px;
      height: 64px;
      margin: 0 auto 1.5rem;
      background: rgba(201, 106, 82, 0.12);
      color: var(--accent-terracotta);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .error-card h1 {
      font-family: 'Fraunces', serif;
      font-size: 1.4rem;
      font-weight: 600;
      margin: 0 0 0.75rem;
      color: var(--text-espresso);
    }
    .error-card p {
      font-size: 0.95rem;
      line-height: 1.55;
      color: var(--text-muted);
      margin: 0 0 2rem;
    }
    .btn-group {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .btn-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.85rem 1.5rem;
      border-radius: 999px;
      font-weight: 600;
      font-size: 0.9rem;
      text-decoration: none;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    .btn-primary {
      background: var(--text-espresso);
      color: #FFFFFF;
      border: 1px solid var(--text-espresso);
    }
    .btn-primary:hover {
      background: #2C221F;
      transform: translateY(-1px);
    }
    .btn-secondary {
      background: transparent;
      color: var(--text-espresso);
      border: 1px solid var(--border-warm);
    }
    .btn-secondary:hover {
      background: rgba(26, 20, 18, 0.04);
    }
  </style>
</head>
<body>
  <div class="error-card">
    <div class="brand-mark">CIELO</div>
    <div class="brand-subtitle">Cafe & Restro · Vadodara</div>

    <div class="icon-box">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="18" height="18" x="3" y="3" rx="2"/>
        <path d="m9 9 6 6"/>
        <path d="m15 9-6 6"/>
      </svg>
    </div>

    <h1>Table QR Unrecognized</h1>
    <p>
      We could not locate an active dining table for this code. If you are seated at Cielo, please ask our host or scan the sticker on your table again.
    </p>

    <div class="btn-group">
      <a href="/menu.php" class="btn-action btn-primary">
        <span>Browse Menu as Guest</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
      <a href="/index.php" class="btn-action btn-secondary">
        Return to Home
      </a>
    </div>
  </div>
</body>
</html>
