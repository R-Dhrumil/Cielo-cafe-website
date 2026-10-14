<?php
/**
 * Floating Accessibility Toolbar Component
 */
?>
<div class="accessibility-container">
  <div id="accessibilityPanel" class="accessibility-panel" role="region" aria-label="Accessibility Options">
    <div class="accessibility-panel-header">
      <span class="accessibility-panel-title">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent-sky-deep);">
          <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
        </svg>
        Accessibility
      </span>
      <button id="accessibilityClose" class="accessibility-panel-close" aria-label="Close accessibility toolbar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <div class="accessibility-options">
      <!-- Font Scale -->
      <div class="accessibility-row">
        <span>Font Size (<span id="fontScaleLabel">100%</span>)</span>
        <div style="display: flex; gap: 0.25rem;">
          <button id="fontMinusBtn" class="btn-accessibility-step" aria-label="Decrease text size">−</button>
          <button id="fontPlusBtn" class="btn-accessibility-step" aria-label="Increase text size">+</button>
        </div>
      </div>

      <!-- Warm Contrast (Current Cream Palette) -->
      <div class="accessibility-row">
        <span>Warm Contrast</span>
        <button id="contrastToggleBtn" class="btn-accessibility-toggle" aria-pressed="false" title="Switch to warm cream editorial contrast">OFF</button>
      </div>

      <!-- Readable Font -->
      <div class="accessibility-row">
        <span>Standard Font</span>
        <button id="fontToggleBtn" class="btn-accessibility-toggle" aria-pressed="false">OFF</button>
      </div>
    </div>

    <button id="resetAccessibilityBtn" class="btn-accessibility-reset">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>
      </svg>
      Reset Preferences
    </button>
  </div>

  <button id="accessibilityTrigger" class="accessibility-trigger-btn" aria-label="Open accessibility options" title="Accessibility options">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
    </svg>
  </button>
</div>
