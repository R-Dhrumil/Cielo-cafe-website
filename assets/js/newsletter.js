/**
 * Cielo Cafe and Restro - Newsletter Subscription Logic
 */

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('newsletterForm');
  const alertEl = document.getElementById('newsletterSuccessAlert');
  const inputEl = document.getElementById('newsletterEmailInput');

  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const email = inputEl?.value.trim() || '';
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!regex.test(email)) {
      alert('Please enter a valid email address.');
      return;
    }

    form.style.display = 'none';
    if (alertEl) alertEl.style.display = 'flex';
  });
});
