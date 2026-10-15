/**
 * Cielo Cafe and Restro - Accessibility Controller
 * Font scaling, High contrast mode, Standard readable font
 */

document.addEventListener('DOMContentLoaded', () => {
  const triggerBtn = document.getElementById('accessibilityTrigger');
  const panel = document.getElementById('accessibilityPanel');
  const closeBtn = document.getElementById('accessibilityClose');

  const fontMinusBtn = document.getElementById('fontMinusBtn');
  const fontPlusBtn = document.getElementById('fontPlusBtn');
  const fontScaleLabel = document.getElementById('fontScaleLabel');

  const contrastToggleBtn = document.getElementById('contrastToggleBtn');
  const headerContrastToggle = document.getElementById('headerContrastToggle');
  const headerThemeText = document.getElementById('headerThemeText');
  const fontToggleBtn = document.getElementById('fontToggleBtn');
  const resetBtn = document.getElementById('resetAccessibilityBtn');

  if (!triggerBtn || !panel) return;

  let isWarmContrast = localStorage.getItem('cielo_warm_contrast') === '1';
  let isReadableFont = localStorage.getItem('cielo_readable_font') === '1';
  let savedScale = parseInt(localStorage.getItem('cielo_font_scale'), 10);
  let currentScale = !isNaN(savedScale) ? savedScale : 100;

  function updateFontScale() {
    document.documentElement.style.fontSize = `${(currentScale / 100) * 16}px`;
    if (fontScaleLabel) fontScaleLabel.textContent = `${currentScale}%`;
    localStorage.setItem('cielo_font_scale', currentScale);
  }

  function syncContrastUI(isWarm) {
    document.body.classList.toggle('warm-contrast', isWarm);
    if (contrastToggleBtn) {
      contrastToggleBtn.textContent = isWarm ? 'ON' : 'OFF';
      contrastToggleBtn.classList.toggle('active', isWarm);
      contrastToggleBtn.setAttribute('aria-pressed', isWarm ? 'true' : 'false');
    }
    if (headerThemeText) {
      headerThemeText.textContent = isWarm ? 'Day' : 'Night';
    }
    if (headerContrastToggle) {
      headerContrastToggle.setAttribute('aria-pressed', isWarm ? 'true' : 'false');
      headerContrastToggle.title = isWarm ? 'Switch to High Contrast (Day)' : 'Switch to Warm Contrast (Night)';
    }
    localStorage.setItem('cielo_warm_contrast', isWarm ? '1' : '0');
    window.dispatchEvent(new Event('scroll'));
  }

  // Restore preferences
  if (currentScale !== 100) updateFontScale();
  if (isWarmContrast) syncContrastUI(true);

  if (isReadableFont) {
    document.body.classList.add('readable-font');
    if (fontToggleBtn) {
      fontToggleBtn.textContent = 'ON';
      fontToggleBtn.classList.add('active');
      fontToggleBtn.setAttribute('aria-pressed', 'true');
    }
  }

  // Toggle Panel
  triggerBtn.addEventListener('click', () => {
    panel.classList.toggle('is-active');
  });

  closeBtn?.addEventListener('click', () => {
    panel.classList.remove('is-active');
  });

  // Font Scaling
  fontMinusBtn?.addEventListener('click', () => {
    if (currentScale > 85) {
      currentScale -= 5;
      updateFontScale();
    }
  });

  fontPlusBtn?.addEventListener('click', () => {
    if (currentScale < 130) {
      currentScale += 5;
      updateFontScale();
    }
  });

  // Warm Contrast Toggles (Toolbar button & Header Night/Day button)
  contrastToggleBtn?.addEventListener('click', () => {
    isWarmContrast = !isWarmContrast;
    syncContrastUI(isWarmContrast);
  });

  headerContrastToggle?.addEventListener('click', () => {
    isWarmContrast = !isWarmContrast;
    syncContrastUI(isWarmContrast);
  });

  // Readable Font
  fontToggleBtn?.addEventListener('click', () => {
    isReadableFont = !isReadableFont;
    document.body.classList.toggle('readable-font', isReadableFont);
    fontToggleBtn.textContent = isReadableFont ? 'ON' : 'OFF';
    fontToggleBtn.classList.toggle('active', isReadableFont);
    fontToggleBtn.setAttribute('aria-pressed', isReadableFont ? 'true' : 'false');
    localStorage.setItem('cielo_readable_font', isReadableFont ? '1' : '0');
  });

  // Reset
  resetBtn?.addEventListener('click', () => {
    currentScale = 100;
    isWarmContrast = false;
    isReadableFont = false;
    updateFontScale();
    syncContrastUI(false);
    document.body.classList.remove('high-contrast');
    document.body.classList.remove('readable-font');
    localStorage.removeItem('cielo_warm_contrast');
    localStorage.removeItem('cielo_font_scale');
    localStorage.removeItem('cielo_readable_font');
    if (fontToggleBtn) {
      fontToggleBtn.textContent = 'OFF';
      fontToggleBtn.classList.remove('active');
      fontToggleBtn.setAttribute('aria-pressed', 'false');
    }
  });
});
