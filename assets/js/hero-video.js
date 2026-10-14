/**
 * Cielo Cafe and Restro - Hero Video Controller
 * Audio toggle, Play/Pause, Clear View mode
 */

document.addEventListener('DOMContentLoaded', () => {
  const video = document.getElementById('heroVideo');
  const heroSection = document.getElementById('heroSection');
  const toggleSoundBtn = document.getElementById('toggleSoundBtn');
  const togglePlayBtn = document.getElementById('togglePlayBtn');
  const toggleClearViewBtn = document.getElementById('toggleClearViewBtn');

  if (!video) return;

  // Sound Toggle
  if (toggleSoundBtn) {
    toggleSoundBtn.addEventListener('click', () => {
      video.muted = !video.muted;
      const soundLabel = toggleSoundBtn.querySelector('.media-control-label');
      const soundIcon = toggleSoundBtn.querySelector('.media-control-icon');

      if (video.muted) {
        if (soundLabel) soundLabel.textContent = 'Audio Off';
        toggleSoundBtn.title = 'Turn audio on';
        // Muted icon (VolumeX)
        if (soundIcon) {
          soundIcon.innerHTML = `<path d="M11 4.702a.75.75 0 0 1 1.28-.53l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 0 1-1.28-.53V4.702ZM3.75 9A.75.75 0 0 0 3 9.75v4.5c0 .414.336.75.75.75h2.586l3.957 3.957A.75.75 0 0 0 11.5 18.5V5.5a.75.75 0 0 0-1.207-.593L6.336 9H3.75Zm18.03 2.22a.75.75 0 0 0-1.06-1.06L19 11.88l-1.72-1.72a.75.75 0 0 0-1.06 1.06L17.94 13l-1.72 1.72a.75.75 0 1 0 1.06 1.06L19 14.12l1.72 1.72a.75.75 0 0 0 1.06-1.06L20.06 13l1.72-1.72Z"/>`;
        }
      } else {
        if (soundLabel) soundLabel.textContent = 'Audio On';
        toggleSoundBtn.title = 'Turn audio off';
        // Sound icon (Volume2)
        if (soundIcon) {
          soundIcon.innerHTML = `<path d="M11 4.702a.75.75 0 0 1 1.28-.53l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 0 1-1.28-.53V4.702ZM3.75 9A.75.75 0 0 0 3 9.75v4.5c0 .414.336.75.75.75h2.586l3.957 3.957A.75.75 0 0 0 11.5 18.5V5.5a.75.75 0 0 0-1.207-.593L6.336 9H3.75Zm14.28 1.47a.75.75 0 0 0-1.06 1.06 2.5 2.5 0 0 1 0 3.54.75.75 0 1 0 1.06 1.06 4 4 0 0 0 0-5.66Z"/>`;
        }
      }
    });
  }

  // Play / Pause Toggle
  if (togglePlayBtn) {
    togglePlayBtn.addEventListener('click', () => {
      const playIcon = togglePlayBtn.querySelector('.media-control-icon');
      if (video.paused) {
        video.play();
        togglePlayBtn.title = 'Pause video';
        // Pause icon
        if (playIcon) {
          playIcon.innerHTML = `<path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>`;
        }
      } else {
        video.pause();
        togglePlayBtn.title = 'Play video';
        // Play icon
        if (playIcon) {
          playIcon.innerHTML = `<path d="M8 5v14l11-7z"/>`;
        }
      }
    });
  }

  // Clear View Mode Toggle
  if (toggleClearViewBtn && heroSection) {
    toggleClearViewBtn.addEventListener('click', () => {
      heroSection.classList.toggle('clear-view-mode');
      const isClear = heroSection.classList.contains('clear-view-mode');
      const viewLabel = toggleClearViewBtn.querySelector('.media-control-label');
      if (viewLabel) {
        viewLabel.textContent = isClear ? 'Show Text' : 'Clear Video View';
      }
    });
  }
});
