/**
 * Cielo Cafe and Restro - Menu Category Filter
 */

document.addEventListener('DOMContentLoaded', () => {
  const tabButtons = document.querySelectorAll('.menu-tab-btn');
  const dishCards = document.querySelectorAll('.dish-card');
  const catDescEl = document.getElementById('activeCategoryDescription');

  if (!tabButtons.length) return;

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetCategory = btn.getAttribute('data-category');
      const descText = btn.getAttribute('data-desc');

      // Update active tab button style
      tabButtons.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      // Update description text
      if (catDescEl && descText) {
        catDescEl.textContent = descText;
      }

      // Filter dish cards with instant animation
      dishCards.forEach(card => {
        const cardCat = card.getAttribute('data-category');
        if (targetCategory === 'all' || cardCat === targetCategory) {
          card.style.display = 'flex';
          card.style.animation = 'fadeIn 300ms ease forwards';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
});
