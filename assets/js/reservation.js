/**
 * Cielo Cafe and Restro - Table Reservation Controller
 * Form Handling & WhatsApp Integration
 */

document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('reservationModal');
  const openButtons = document.querySelectorAll('.btn-open-reservation');
  const closeBtn = document.getElementById('closeReservationBtn');
  const form = document.getElementById('reservationForm');
  const successState = document.getElementById('reservationSuccessState');
  const formState = document.getElementById('reservationFormState');
  const backToSiteBtn = document.getElementById('backToSiteBtn');

  if (!modal) return;

  function openModal() {
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    if (formState) formState.style.display = 'block';
    if (successState) successState.style.display = 'none';
  }

  function closeModal() {
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  openButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal();
    });
  });

  closeBtn?.addEventListener('click', closeModal);
  backToSiteBtn?.addEventListener('click', closeModal);

  // Click outside window to close
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      closeModal();
    }
  });

  // Escape key to close
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });

  // Form Submission
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const name = document.getElementById('resName')?.value || '';
      const phone = document.getElementById('resPhone')?.value || '';
      const guests = document.getElementById('resGuests')?.value || '2';
      const date = document.getElementById('resDate')?.value || '';
      const time = document.getElementById('resTime')?.value || '';
      const occasion = document.getElementById('resOccasion')?.value || 'Casual Dining';
      const notes = document.getElementById('resNotes')?.value || '';
      const waNumber = form.getAttribute('data-whatsapp') || '919876543210';

      const message = `Hello Cielo Cafe! I'd like to reserve a table:\n• Name: ${name}\n• Phone: ${phone}\n• Guests: ${guests}\n• Date: ${date}\n• Time: ${time}\n• Occasion: ${occasion}${notes ? `\n• Special Requests: ${notes}` : ''}`;
      const waUrl = `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`;

      // Switch to success view
      if (formState) formState.style.display = 'none';
      if (successState) successState.style.display = 'block';

      // Open WhatsApp tab after a short delay
      setTimeout(() => {
        window.open(waUrl, '_blank');
      }, 800);
    });
  }
});
