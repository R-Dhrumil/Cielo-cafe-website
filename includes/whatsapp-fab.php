<?php
/**
 * Floating WhatsApp Quick Action Button
 */
$waDefaultMsg = urlencode($business['whatsappDefaultMessage'] ?? 'Hello Cielo Cafe & Restro, I would like to reserve a table / enquire about catering.');
$waFabUrl = "https://wa.me/" . e($business['whatsappNumber']) . "?text=" . $waDefaultMsg;
?>
<aside class="whatsapp-fab" aria-label="Quick contact">
  <a href="<?= $waFabUrl ?>" target="_blank" rel="noopener noreferrer" class="whatsapp-fab-link" aria-label="Chat with Cielo Cafe on WhatsApp">
    <svg class="whatsapp-fab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21" />
      <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1" />
    </svg>
    <span class="whatsapp-fab-label">Order / Reserve</span>
  </a>
</aside>
