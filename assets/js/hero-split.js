/**
 * Cielo Cafe and Restro - Split Hero Video & Media Controller
 * Supports live ambient video, audio toggle, play/pause, and photo switcher
 */

document.addEventListener('DOMContentLoaded', () => {
  const video = document.getElementById('heroSplitVideo');
  const mainImg = document.getElementById('heroMainImage');
  const thumbBtns = document.querySelectorAll('.hero-thumb-btn');
  const soundBtn = document.getElementById('heroCardSoundBtn');
  const playBtn = document.getElementById('heroCardPlayBtn');
  const videoControls = document.getElementById('heroCardVideoControls');
  const floatingTitle = document.getElementById('heroFloatingTitle');
  const floatingSub = document.getElementById('heroFloatingSub');
  const liveBadge = document.getElementById('heroLiveBadge');

  if (!video || !thumbBtns.length) return;

  // 1. Sound Toggle (Mute / Unmute)
  if (soundBtn) {
    soundBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      video.muted = !video.muted;
      const soundIcon = soundBtn.querySelector('svg');

      if (video.muted) {
        soundBtn.title = 'Turn audio on';
        soundBtn.classList.remove('is-active');
        if (soundIcon) {
          soundIcon.innerHTML = `<path d="M11 4.702a.75.75 0 0 1 1.28-.53l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 0 1-1.28-.53V4.702ZM3.75 9A.75.75 0 0 0 3 9.75v4.5c0 .414.336.75.75.75h2.586l3.957 3.957A.75.75 0 0 0 11.5 18.5V5.5a.75.75 0 0 0-1.207-.593L6.336 9H3.75Zm18.03 2.22a.75.75 0 0 0-1.06-1.06L19 11.88l-1.72-1.72a.75.75 0 0 0-1.06 1.06L17.94 13l-1.72 1.72a.75.75 0 1 0 1.06 1.06L19 14.12l1.72 1.72a.75.75 0 0 0 1.06-1.06L20.06 13l1.72-1.72Z"/>`;
        }
      } else {
        soundBtn.title = 'Turn audio off';
        soundBtn.classList.add('is-active');
        if (soundIcon) {
          soundIcon.innerHTML = `<path d="M11 4.702a.75.75 0 0 1 1.28-.53l4 4a.75.75 0 0 1 0 1.06l-4 4a.75.75 0 0 1-1.28-.53V4.702ZM3.75 9A.75.75 0 0 0 3 9.75v4.5c0 .414.336.75.75.75h2.586l3.957 3.957A.75.75 0 0 0 11.5 18.5V5.5a.75.75 0 0 0-1.207-.593L6.336 9H3.75Zm14.28 1.47a.75.75 0 0 0-1.06 1.06 2.5 2.5 0 0 1 0 3.54.75.75 0 1 0 1.06 1.06 4 4 0 0 0 0-5.66Z"/>`;
        }
      }
    });
  }

  // 2. Play / Pause Toggle
  if (playBtn) {
    playBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const playIcon = playBtn.querySelector('svg');

      if (video.paused) {
        video.play();
        playBtn.title = 'Pause video';
        if (playIcon) {
          playIcon.innerHTML = `<path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>`;
        }
      } else {
        video.pause();
        playBtn.title = 'Play video';
        if (playIcon) {
          playIcon.innerHTML = `<path d="M8 5v14l11-7z"/>`;
        }
      }
    });
  }

  // 3. Thumbnail Switcher (Switches between Live Video and Featured Food Photos)
  thumbBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.getAttribute('data-type') || 'image';
      const newSrc = btn.getAttribute('data-src');
      const newAlt = btn.getAttribute('data-alt');
      const newTitle = btn.getAttribute('data-title');
      const newSub = btn.getAttribute('data-sub');

      thumbBtns.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      if (type === 'video') {
        // Show video, hide photo
        if (mainImg) mainImg.style.display = 'none';
        video.style.display = 'block';
        video.play().catch(() => {});
        if (videoControls) videoControls.style.display = 'flex';
        if (liveBadge) liveBadge.style.display = 'inline-flex';
        if (floatingTitle && newTitle) floatingTitle.textContent = newTitle;
        if (floatingSub && newSub) floatingSub.textContent = newSub;
      } else {
        // Show photo, hide video
        video.style.display = 'none';
        if (videoControls) videoControls.style.display = 'none';
        if (liveBadge) liveBadge.style.display = 'none';

        if (mainImg && newSrc) {
          mainImg.style.display = 'block';
          mainImg.style.opacity = '0';
          mainImg.style.transform = 'scale(0.98)';

          setTimeout(() => {
            mainImg.src = newSrc;
            if (newAlt) mainImg.alt = newAlt;
            if (floatingTitle && newTitle) floatingTitle.textContent = newTitle;
            if (floatingSub && newSub) floatingSub.textContent = newSub;

            mainImg.style.opacity = '1';
            mainImg.style.transform = 'scale(1)';
          }, 140);
        }
      }
    });
  });
});
