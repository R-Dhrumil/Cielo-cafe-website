<?php
/**
 * Cielo Cafe and Restro - Production Front-Controller & Central Router
 * 
 * Directs all public web requests to clean controllers inside pages/
 */

declare(strict_types=1);

// 1. Support PHP Built-in Web Server static asset pass-through (images, css, js, fonts)
if (php_sapi_name() === 'cli-server') {
    $urlPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $requestedFile = __DIR__ . $urlPath;
    if (is_file($requestedFile) && pathinfo($requestedFile, PATHINFO_EXTENSION) !== 'php') {
        return false; // Serve static file directly
    }
}

// 2. Bootstrap Core Services, Config & Models
require_once __DIR__ . '/includes/config.php';

// 3. Resolve Request Path
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?? '/';
$cleanPath = trim(strtolower($path), '/');

// Normalize /index.php or subfolder/index.php to root or clean route
if ($cleanPath === 'index.php' || str_ends_with($cleanPath, '/index.php')) {
    $cleanPath = trim((string)preg_replace('#(^|/)index\.php$#i', '', $cleanPath), '/');
}

// Support menu images legacy fallback
if (preg_match('#^menu/(.+\.(?:png|jpg|jpeg|webp|svg|gif))$#i', $cleanPath, $imgMatch)) {
    $imagePath = __DIR__ . '/assets/images/menu/' . $imgMatch[1];
    if (file_exists($imagePath)) {
        $mime = match (pathinfo($imagePath, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };
        header("Content-Type: {$mime}");
        readfile($imagePath);
        exit;
    }
}

// 4. Central Page Routing Table
switch (true) {
    // Homepage
    case $cleanPath === '' || $cleanPath === 'home' || $cleanPath === 'index' || $cleanPath === 'index.php':
        require CIELO_PAGES . '/home.php';
        break;

    // Menu Catalog
    case $cleanPath === 'menu' || $cleanPath === 'menu.php':
        require CIELO_PAGES . '/menu.php';
        break;

    // About Us / Philosophy
    case $cleanPath === 'about' || $cleanPath === 'about.php' || $cleanPath === 'story':
        require CIELO_PAGES . '/about.php';
        break;

    // Contact & Reservations
    case $cleanPath === 'contact' || $cleanPath === 'contact.php':
        require CIELO_PAGES . '/contact.php';
        break;

    // Events & Celebrations
    case $cleanPath === 'events' || $cleanPath === 'events.php':
        require CIELO_PAGES . '/events.php';
        break;

    // Culinary & Ambience Gallery
    case $cleanPath === 'gallery' || $cleanPath === 'gallery.php':
        require CIELO_PAGES . '/gallery.php';
        break;

    // Checkout Flow
    case $cleanPath === 'checkout' || $cleanPath === 'checkout.php':
        require CIELO_PAGES . '/checkout.php';
        break;

    // Order Live Tracking
    case $cleanPath === 'order-status' || $cleanPath === 'order_status' || $cleanPath === 'order_status.php':
        require CIELO_PAGES . '/order_status.php';
        break;

    // Patron Profile & Rewards Hub
    case $cleanPath === 'profile' || $cleanPath === 'profile.php' || $cleanPath === 'club' || $cleanPath === 'loyalty':
        require CIELO_PAGES . '/profile.php';
        break;

    // Table QR Resolver: /t/{token} or /table/{token} or /t.php?token=...
    case preg_match('#^(?:t|table)/([a-zA-Z0-9_-]+)$#i', $cleanPath, $matches) === 1:
        $_GET['token'] = $matches[1];
        require CIELO_PAGES . '/table.php';
        break;

    case $cleanPath === 't.php' || $cleanPath === 'table.php':
        require CIELO_PAGES . '/table.php';
        break;

    // 404 Not Found Page
    default:
        http_response_code(404);
        $pageTitle = "Page Not Found | Cielo Cafe and Restro";
        $pageDesc = "The page you are looking for could not be found at Cielo Cafe and Restro.";
        $pageCss = ['home.css'];
        $pageJs = [];

        require CIELO_INCLUDES . '/header.php';
        ?>
        <main id="main-content" style="padding: 8rem 1.5rem 6rem; text-align: center; min-height: 60vh; display: flex; align-items: center; justify-content: center; background: var(--bg-cream, #F7F4EE);">
          <div style="max-width: 520px; margin: 0 auto; background: #ffffff; padding: 3rem 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(26,20,18,0.06); border: 1px solid rgba(26,20,18,0.08);">
            <span style="display: inline-block; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: var(--accent-terracotta, #C96A52); margin-bottom: 0.75rem;">Error 404</span>
            <h1 style="font-size: 2.25rem; font-family: var(--font-display, 'Playfair Display', serif); color: var(--text-espresso, #1A1412); margin-bottom: 1rem;">Table Not Found</h1>
            <p style="color: rgba(26,20,18,0.7); font-size: 1rem; line-height: 1.6; margin-bottom: 2rem;">
              The page or dish you are looking for seems to have moved or does not exist. Let's guide you back to our artisanal creations.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
              <a href="/menu" style="display: inline-flex; align-items: center; justify-content: center; padding: 0.85rem 1.75rem; background: var(--text-espresso, #1A1412); color: #ffffff; text-decoration: none; border-radius: 9999px; font-weight: 600; font-size: 0.95rem; transition: transform 0.2s, background 0.2s;">
                Explore Menu
              </a>
              <a href="/" style="display: inline-flex; align-items: center; justify-content: center; padding: 0.85rem 1.75rem; background: rgba(26,20,18,0.06); color: var(--text-espresso, #1A1412); text-decoration: none; border-radius: 9999px; font-weight: 600; font-size: 0.95rem; transition: transform 0.2s, background 0.2s;">
                Back Home
              </a>
            </div>
          </div>
        </main>
        <?php
        require CIELO_INCLUDES . '/footer.php';
        break;
}
