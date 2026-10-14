<?php
/**
 * Lightbox Modal Component
 */
?>
<div id="lightboxModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-label="Image preview">
  <div class="lightbox-window">
    <div class="lightbox-header">
      <div>
        <span id="lightboxCategory" class="lightbox-category">Experience</span>
        <h3 id="lightboxTitle" class="lightbox-title">Gallery View</h3>
      </div>
      <button id="closeLightboxBtn" class="lightbox-close" aria-label="Close image preview">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <div class="lightbox-viewport">
      <img id="lightboxImg" class="lightbox-img" src="" alt="Cielo Cafe Photo Preview">

      <button id="prevLightboxBtn" class="lightbox-nav-btn prev" aria-label="Previous photo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>

      <button id="nextLightboxBtn" class="lightbox-nav-btn next" aria-label="Next photo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>

    <div class="lightbox-caption-bar">
      <p id="lightboxCaption" class="lightbox-caption-text"></p>
    </div>
  </div>
</div>
