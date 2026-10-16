<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Luxury Staff & Admin Authentication Portal
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;
use App\Core\Security;

// Redirect if already logged in
if (AuthService::check()) {
    $user = AuthService::user();
    if ($user && ($user['role'] ?? '') === 'kitchen') {
        header('Location: /kitchen/index.php');
    } else {
        header('Location: /admin/orders.php');
    }
    exit;
}

$error = '';
$redirect = trim((string)($_GET['redirect'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!Security::verifyCsrfToken($csrfToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        $staff = AuthService::attempt($email, $password);
        if ($staff) {
            $dest = $redirect !== '' ? $redirect : (($staff['role'] ?? '') === 'kitchen' ? '/kitchen/index.php' : '/admin/orders.php');
            header("Location: {$dest}");
            exit;
        } else {
            $error = 'Invalid credentials or inactive staff account.';
        }
    }
}

$csrf = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Portal Sign In | Cielo Cafe & Restro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #F7F4EE;
      --bg-cream-card: #FFFFFF;
      --text-espresso: #1A1412;
      --text-muted: #6E6259;
      --accent-terracotta: #C96A52;
      --accent-sky: #6B9AC4;
      --accent-sky-deep: #4A7A9F;
      --border-warm: rgba(26, 20, 18, 0.1);
      --border-focus: #6B9AC4;
      --shadow-luxury: 0 20px 45px -12px rgba(26, 20, 18, 0.08), 0 1px 3px rgba(26, 20, 18, 0.04);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 1.5rem;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--bg-cream);
      color: var(--text-espresso);
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-image: 
        radial-gradient(circle at 15% 15%, rgba(107, 154, 196, 0.12) 0%, transparent 45%),
        radial-gradient(circle at 85% 85%, rgba(201, 106, 82, 0.12) 0%, transparent 45%);
    }

    .login-container {
      max-width: 960px;
      width: 100%;
      background: var(--bg-cream-card);
      border-radius: 28px;
      border: 1px solid var(--border-warm);
      box-shadow: var(--shadow-luxury);
      overflow: hidden;
      display: grid;
      grid-template-columns: 1fr 1.15fr;
      animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Left Brand Showcase */
    .brand-sidebar {
      background: linear-gradient(145deg, #1A1412 0%, #261E1B 100%);
      color: #F7F4EE;
      padding: 3.5rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }

    .brand-sidebar::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 160px;
      height: 160px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(201, 106, 82, 0.25) 0%, transparent 70%);
      pointer-events: none;
    }

    .brand-logo-wrap {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .brand-logo-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: rgba(247, 244, 238, 0.1);
      border: 1px solid rgba(247, 244, 238, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-terracotta);
    }

    .brand-name {
      font-family: 'Fraunces', serif;
      font-size: 1.85rem;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: #F7F4EE;
      line-height: 1;
    }

    .brand-sub {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.16em;
      color: var(--accent-sky);
      font-weight: 600;
      margin-top: 0.35rem;
    }

    .brand-quote {
      margin: 2.5rem 0;
    }

    .brand-quote p {
      font-family: 'Fraunces', serif;
      font-size: 1.25rem;
      font-style: italic;
      line-height: 1.5;
      color: rgba(247, 244, 238, 0.9);
      margin: 0;
    }

    .brand-quote span {
      display: block;
      margin-top: 0.75rem;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.75rem;
      font-style: normal;
      color: rgba(247, 244, 238, 0.55);
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .demo-pills-box {
      background: rgba(247, 244, 238, 0.05);
      border: 1px solid rgba(247, 244, 238, 0.1);
      border-radius: 16px;
      padding: 1.25rem;
    }

    .demo-pills-title {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--accent-sky);
      font-weight: 700;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .demo-buttons {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .btn-demo-pill {
      background: rgba(247, 244, 238, 0.08);
      border: 1px solid rgba(247, 244, 238, 0.12);
      color: #F7F4EE;
      padding: 0.6rem 0.85rem;
      border-radius: 10px;
      font-size: 0.82rem;
      font-weight: 500;
      cursor: pointer;
      text-align: left;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all 0.2s ease;
      font-family: inherit;
    }

    .btn-demo-pill:hover {
      background: rgba(247, 244, 238, 0.16);
      border-color: var(--accent-sky);
      transform: translateX(3px);
    }

    .btn-demo-pill span {
      font-size: 0.7rem;
      color: rgba(247, 244, 238, 0.6);
    }

    /* Right Login Form */
    .form-panel {
      padding: 3.5rem 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-header {
      margin-bottom: 2rem;
    }

    .form-title {
      font-family: 'Fraunces', serif;
      font-size: 1.85rem;
      font-weight: 700;
      color: var(--text-espresso);
      margin: 0;
      letter-spacing: -0.01em;
    }

    .form-desc {
      color: var(--text-muted);
      font-size: 0.9rem;
      margin-top: 0.35rem;
    }

    .error-banner {
      background: rgba(201, 106, 82, 0.1);
      border: 1px solid rgba(201, 106, 82, 0.25);
      color: var(--accent-terracotta);
      padding: 0.85rem 1.15rem;
      border-radius: 12px;
      font-size: 0.88rem;
      font-weight: 500;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.65rem;
    }

    .form-group {
      margin-bottom: 1.35rem;
    }

    .form-label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text-espresso);
      margin-bottom: 0.45rem;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-field {
      width: 100%;
      padding: 0.85rem 1.15rem;
      background: #FAFAFA;
      border: 1px solid var(--border-warm);
      border-radius: 12px;
      font-size: 0.95rem;
      font-family: inherit;
      color: var(--text-espresso);
      outline: none;
      transition: all 0.2s ease;
    }

    .input-field:focus {
      background: #FFFFFF;
      border-color: var(--accent-sky);
      box-shadow: 0 0 0 4px rgba(107, 154, 196, 0.14);
    }

    .btn-toggle-pass {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      padding: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-submit {
      width: 100%;
      background: var(--text-espresso);
      color: #FFFFFF;
      border: none;
      padding: 1rem;
      border-radius: 14px;
      font-size: 0.98rem;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      box-shadow: 0 8px 20px -4px rgba(26, 20, 18, 0.2);
      transition: all 0.2s ease;
      margin-top: 0.5rem;
    }

    .btn-submit:hover {
      background: #2E2522;
      transform: translateY(-2px);
      box-shadow: 0 12px 24px -4px rgba(26, 20, 18, 0.28);
    }

    .form-footer {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border-warm);
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.78rem;
      color: var(--text-muted);
    }

    .form-footer a {
      color: var(--accent-terracotta);
      text-decoration: none;
      font-weight: 600;
    }

    .form-footer a:hover {
      text-decoration: underline;
    }

    @media (max-width: 820px) {
      .login-container {
        grid-template-columns: 1fr;
        max-width: 480px;
      }
      .brand-sidebar {
        padding: 2rem;
      }
      .form-panel {
        padding: 2.25rem 2rem;
      }
    }
  </style>
</head>
<body>

  <div class="login-container">
    
    <!-- Left Column: Editorial Atmosphere & Quick Demo Fillers -->
    <div class="brand-sidebar">
      <div>
        <div class="brand-logo-wrap">
          <div class="brand-logo-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
              <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
              <line x1="6" y1="1" x2="6" y2="4"/>
              <line x1="10" y1="1" x2="10" y2="4"/>
              <line x1="14" y1="1" x2="14" y2="4"/>
            </svg>
          </div>
          <div>
            <div class="brand-name">Cielo</div>
            <div class="brand-sub">Operations & POS Console</div>
          </div>
        </div>

        <div class="brand-quote">
          <p>&ldquo;L'arte del gusto autentico&rdquo;</p>
          <span>Italian Sanctuary · Vadodara</span>
        </div>
      </div>

      <div class="demo-pills-box">
        <div class="demo-pills-title">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
          Quick Demo Credentials
        </div>
        <div class="demo-buttons">
          <button type="button" class="btn-demo-pill" onclick="fillCreds('admin@cielocafe.com', 'CieloAdmin@2026')">
            <div>
              <strong>Super Admin / Owner</strong>
              <div style="font-size:0.72rem; color:rgba(247,244,238,0.7);">admin@cielocafe.com</div>
            </div>
            <span>Auto Fill ➔</span>
          </button>
          <button type="button" class="btn-demo-pill" onclick="fillCreds('kitchen@cielocafe.com', 'Kitchen@2026')">
            <div>
              <strong>Kitchen Display Chef</strong>
              <div style="font-size:0.72rem; color:rgba(247,244,238,0.7);">kitchen@cielocafe.com</div>
            </div>
            <span>Auto Fill ➔</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Right Column: Clean Login Form -->
    <div class="form-panel">
      <div class="form-header">
        <h1 class="form-title">Staff Sign In</h1>
        <div class="form-desc">Enter your verified staff email and passcode to proceed.</div>
      </div>

      <?php if ($error !== ''): ?>
        <div class="error-banner">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="/admin/login.php<?= $redirect !== '' ? '?redirect=' . urlencode($redirect) : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <div class="form-group">
          <label class="form-label" for="email">Staff Work Email</label>
          <div class="input-wrapper">
            <input type="email" id="email" name="email" class="input-field" required placeholder="admin@cielocafe.com" autocomplete="email">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Security Passcode</label>
          <div class="input-wrapper">
            <input type="password" id="password" name="password" class="input-field" required placeholder="••••••••••••" autocomplete="current-password">
            <button type="button" class="btn-toggle-pass" onclick="togglePass()" aria-label="Toggle password view">
              <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <span>Authorize Access</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
          </svg>
        </button>
      </form>

      <div class="form-footer">
        <span>Protected by Cielo Session Engine</span>
        <a href="/index.php">Back to Public Site ➔</a>
      </div>
    </div>

  </div>

  <script>
    function fillCreds(email, pass) {
      const emailInput = document.getElementById('email');
      const passInput = document.getElementById('password');
      emailInput.value = email;
      passInput.value = pass;
      emailInput.focus();
    }

    function togglePass() {
      const passInput = document.getElementById('password');
      const isPass = passInput.type === 'password';
      passInput.type = isPass ? 'text' : 'password';
    }
  </script>

</body>
</html>
