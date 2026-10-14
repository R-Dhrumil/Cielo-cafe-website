/**
 * Cielo Cafe and Restro - Lightbox Modal Controller
 */

document.addEventListener('DOMContentLoaded', () => {
  const lightboxBackdrop = document.getElementById('lightboxModal');
  const galleryCards = document.querySelectorAll('.gallery-card');
  const closeBtn = document.getElementById('closeLightboxBtn');
  const prevBtn = document.getElementById('prevLightboxBtn');
  const nextBtn = document.getElementById('nextLightboxBtn');

  const imgEl = document.getElementById('lightboxImg');
  const titleEl = document.getElementById('lightboxTitle');
  const catEl = document.getElementById('lightboxCategory');
  const captionEl = document.getElementById('lightboxCaption');

  if (!lightboxBackdrop || !galleryCards.length) return;

  let currentIndex = 0;
  const items = Array.from(galleryCards).map(card => ({
    title: card.getAttribute('data-title') || '',
    category: card.getAttribute('data-category') || '',
    caption: card.getAttribute('data-caption') || '',
    image: card.getAttribute('data-image') || ''
  }));

  function openLightbox(index) {
    currentIndex = index;
    updateContent();
    lightboxBackdrop.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightboxBackdrop.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function updateContent() {
    const item = items[currentIndex];
    if (!item) return;

    if (imgEl) imgEl.src = item.image;
    if (imgEl) imgEl.alt = item.title;
    if (titleEl) titleEl.textContent = item.title;
    if (catEl) catEl.textContent = item.category;
    if (captionEl) captionEl.textContent = item.caption;
  }

  function showNext() {
    currentIndex = (currentIndex + 1) % items.length;
    updateContent();
  }

  function showPrev() {
    currentIndex = (currentIndex - 1 + items.length) % items.length;
    updateContent();
  }

  // Click card to open
  galleryCards.forEach((card, index) => {
    card.addEventListener('click', () => openLightbox(index));
  });

  // Buttons
  closeBtn?.addEventListener('click', closeLightbox);
  nextBtn?.addEventListener('click', showNext);
  prevBtn?.addEventListener('click', showPrev);

  // Click outside modal content to close
  lightboxBackdrop.addEventListener('click', (e) => {
    if (e.target === lightboxBackdrop) {
      closeLightbox();
    }
  });

  // Keyboard navigation
  window.addEventListener('keydown', (e) => {
    if (!lightboxBackdrop.classList.contains('is-open')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') showNext();
    if (e.key === 'ArrowLeft') showPrev();
  });
});
