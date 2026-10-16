<?php
/**
 * Cielo Cafe and Restro - Global Footer Template
 */
?>
  <!-- Global Footer -->
  <footer class="cielo-footer" data-header-theme="dark">
    <div class="container footer-content">
      <!-- Centered Brand Mark -->
      <div class="footer-brand">
        <a href="/" class="footer-brand-title">CIELO</a>
        <span class="footer-brand-subtitle">Cafe & Restro · Vadodara</span>
        <p class="footer-tagline">
          &ldquo;<?= e($business['secondaryTagline']) ?>&rdquo; — An Italian-inspired all-day sanctuary in Gujarat.
        </p>
      </div>

      <!-- Quick Nav Links -->
      <nav class="footer-nav">
        <a href="/menu" class="footer-nav-link">Menu</a>
        <a href="/#story" class="footer-nav-link">Our Story</a>
        <a href="/#celebrations" class="footer-nav-link">Events & DJ</a>
        <a href="/#gallery" class="footer-nav-link">Gallery</a>
        <a href="/#reviews" class="footer-nav-link">Reviews</a>
        <a href="/#location" class="footer-nav-link">Contact</a>
      </nav>

      <!-- Social and Delivery Platform Badges -->
      <div class="footer-socials">
        <!-- Instagram -->
        <a href="<?= e($business['links']['instagram']) ?>" target="_blank" rel="noopener noreferrer" class="footer-icon-btn" aria-label="Follow Cielo Cafe on Instagram">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
          </svg>
        </a>

        <!-- Facebook -->
        <a href="<?= e($business['links']['facebook']) ?>" target="_blank" rel="noopener noreferrer" class="footer-icon-btn" aria-label="Follow Cielo Cafe on Facebook">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
          </svg>
        </a>

        <!-- WhatsApp -->
        <a href="https://wa.me/<?= e($business['whatsappNumber']) ?>" target="_blank" rel="noopener noreferrer" class="footer-icon-btn whatsapp" aria-label="Chat with Cielo Cafe on WhatsApp">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21"/>
            <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>
          </svg>
        </a>

        <!-- Zomato -->
        <a href="<?= e($business['links']['zomato']) ?>" target="_blank" rel="noopener noreferrer" class="footer-partner-badge">
          Zomato
        </a>

        <!-- Swiggy -->
        <a href="<?= e($business['links']['swiggy']) ?>" target="_blank" rel="noopener noreferrer" class="footer-partner-badge">
          Swiggy
        </a>
      </div>

      <!-- Back to Top -->
      <a href="#" class="footer-back-to-top">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline>
        </svg>
        Back to top
      </a>
    </div>

    <!-- Sub-footer Bar -->
    <div class="container sub-footer">
      <p>© <?= date('Y') ?> <?= e($business['name']) ?>. All rights reserved. Vadodara, Gujarat.</p>
      <div class="sub-footer-links">
        <span class="sub-footer-link">Privacy Policy</span>
        <span>·</span>
        <span class="sub-footer-link">Terms & Conditions</span>
        <span>·</span>
        <span class="sub-footer-link">FSSAI Lic. #10723026000123</span>
      </div>
    </div>
  </footer>

  <!-- Modals & Utility Components -->
  <?php require __DIR__ . '/reservation-modal.php'; ?>
  <?php require __DIR__ . '/lightbox-modal.php'; ?>
  <?php require __DIR__ . '/accessibility-widget.php'; ?>

  <!-- Core JavaScript Bundles -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/reservation.js"></script>
  <script src="assets/js/accessibility.js"></script>

  <!-- Page-Specific JavaScript Files -->
  <?php if (!empty($pageJs) && is_array($pageJs)): ?>
    <?php foreach ($pageJs as $jsFile): ?>
      <?php if (str_starts_with($jsFile, 'http://') || str_starts_with($jsFile, 'https://') || str_starts_with($jsFile, '//')): ?>
        <script src="<?= e($jsFile) ?>"></script>
      <?php else: ?>
        <script src="assets/js/<?= e($jsFile) ?>"></script>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
