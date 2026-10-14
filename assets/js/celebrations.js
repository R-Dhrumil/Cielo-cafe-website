/**
 * Cielo Cafe and Restro - Celebrations & Events Interactive Controller
 * Interactive Tab Switcher, Audio Visualizer Pace, Dynamic Planner & WhatsApp Sync
 */

document.addEventListener('DOMContentLoaded', () => {
  const tabBtns = document.querySelectorAll('.celebrations-tab-btn');
  const stageImages = document.querySelectorAll('.celebrations-stage-img-wrap');
  const contentPanels = document.querySelectorAll('.celebrations-content-panel');
  const soundwaveBars = document.querySelectorAll('.soundwave-bar');
  const specGuestValue = document.getElementById('specGuestValue');

  if (!tabBtns.length) return;

  // Tab Switching Logic
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const guestCapacity = btn.getAttribute('data-guests') || 'Up to 60 Guests';
      const tempo = btn.getAttribute('data-tempo') || '0.8s';

      // 1. Update active tab button
      tabBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      // 2. Update stage images
      stageImages.forEach(img => {
        if (img.getAttribute('data-id') === targetId) {
          img.classList.add('is-visible');
        } else {
          img.classList.remove('is-visible');
        }
      });

      // 3. Update content panels
      contentPanels.forEach(panel => {
        if (panel.getAttribute('data-id') === targetId) {
          panel.classList.add('is-active');
        } else {
          panel.classList.remove('is-active');
        }
      });

      // 4. Update floating guest capacity badge
      if (specGuestValue) {
        specGuestValue.textContent = guestCapacity;
      }

      // 5. Adjust equalizer tempo to match energy
      soundwaveBars.forEach(bar => {
        bar.style.animationDuration = tempo;
      });
    });
  });
});
