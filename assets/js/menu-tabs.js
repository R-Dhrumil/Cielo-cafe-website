/**
 * Cielo Cafe and Restro - Mobile-First Menu Controller
 * Seamless Category Tabs, Instant Live Search, Veg-Only Filter, & Responsive Layout Switcher
 */

document.addEventListener('DOMContentLoaded', () => {
  const tabButtons = document.querySelectorAll('.menu-tab-btn');
  const dishCards = document.querySelectorAll('.dish-card');
  const catDescEl = document.getElementById('activeCategoryDescription');
  const searchInput = document.getElementById('menuSearchInput');
  const searchClearBtn = document.getElementById('menuSearchClear');
  const vegToggleBtn = document.getElementById('menuVegToggle');
  const viewToggleBtn = document.getElementById('menuViewToggle');
  const viewToggleText = document.getElementById('menuViewToggleText');
  const dishGridContainer = document.getElementById('dishGridContainer');
  const filterAlert = document.getElementById('menuFilterAlert');
  const filterAlertText = document.getElementById('menuFilterAlertText');
  const filterResetBtn = document.getElementById('menuFilterResetBtn');
  const emptySearchMsg = document.getElementById('menuEmptySearchMsg');

  let currentCategory = tabButtons.length > 0 ? (tabButtons[0].getAttribute('data-category') || 'coffee-beverages') : 'all';
  let isVegOnly = false;
  let searchQuery = '';
  let isCompactView = false;

  // Restore compact view preference on mobile
  try {
    const savedView = localStorage.getItem('cielo_menu_view');
    if (savedView === 'compact' || (savedView === null && window.innerWidth < 640)) {
      isCompactView = true;
      dishGridContainer?.classList.add('is-compact-list');
      if (viewToggleText) viewToggleText.textContent = 'Card View';
      viewToggleBtn?.classList.add('is-active');
    }
  } catch (e) {}

  // Master Filter Function
  function applyFilters() {
    let visibleCount = 0;
    const query = searchQuery.trim().toLowerCase();
    const isSearching = query.length > 0;

    dishCards.forEach(card => {
      const cardCat = card.getAttribute('data-category') || '';
      const cardVeg = card.getAttribute('data-veg') === '1';
      const cardName = card.getAttribute('data-name') || '';
      const cardDesc = card.getAttribute('data-desc') || '';

      // Category check: if searching, search across all items; otherwise check current category
      const matchesCategory = isSearching ? true : (cardCat === currentCategory);

      // Veg-only check
      const matchesVeg = !isVegOnly || cardVeg;

      // Search query check
      const matchesSearch = !isSearching || (cardName.includes(query) || cardDesc.includes(query) || cardCat.includes(query));

      if (matchesCategory && matchesVeg && matchesSearch) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    // Update filter alert banner
    if (filterAlert && filterAlertText) {
      if (isSearching || isVegOnly) {
        filterAlert.style.display = 'flex';
        let alertMsg = `Showing ${visibleCount} dish${visibleCount === 1 ? '' : 'es'}`;
        if (isVegOnly) alertMsg += ' (Vegetarian Only)';
        if (isSearching) alertMsg += ` matching "${searchQuery}"`;
        filterAlertText.textContent = alertMsg;
      } else {
        filterAlert.style.display = 'none';
      }
    }

    // Empty state
    if (emptySearchMsg) {
      emptySearchMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
  }

  // 1. Category Tabs Navigation
  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      currentCategory = btn.getAttribute('data-category') || '';
      const descText = btn.getAttribute('data-desc');

      tabButtons.forEach(b => b.classList.remove('is-active'));
      btn.classList.add('is-active');

      // Scroll clicked tab into horizontal center on mobile
      btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

      if (catDescEl && descText) {
        catDescEl.textContent = descText;
      }

      // If user clicks a category while searching, clear search to focus on that category
      if (searchQuery.length > 0 && searchInput) {
        searchQuery = '';
        searchInput.value = '';
        if (searchClearBtn) searchClearBtn.style.display = 'none';
      }

      applyFilters();
    });
  });

  // 2. Search Input Handling
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      searchQuery = e.target.value;
      if (searchClearBtn) {
        searchClearBtn.style.display = searchQuery.length > 0 ? 'inline-flex' : 'none';
      }
      applyFilters();
    });
  }

  if (searchClearBtn && searchInput) {
    searchClearBtn.addEventListener('click', () => {
      searchQuery = '';
      searchInput.value = '';
      searchClearBtn.style.display = 'none';
      searchInput.focus();
      applyFilters();
    });
  }

  // 3. Veg-Only Filter Toggle
  if (vegToggleBtn) {
    vegToggleBtn.addEventListener('click', () => {
      isVegOnly = !isVegOnly;
      vegToggleBtn.setAttribute('data-active', isVegOnly ? 'true' : 'false');
      vegToggleBtn.classList.toggle('is-active', isVegOnly);
      applyFilters();
    });
  }

  // 4. View Switcher (Compact List vs Large Photo Cards)
  if (viewToggleBtn && dishGridContainer) {
    viewToggleBtn.addEventListener('click', () => {
      isCompactView = !isCompactView;
      dishGridContainer.classList.toggle('is-compact-list', isCompactView);
      viewToggleBtn.classList.toggle('is-active', isCompactView);
      if (viewToggleText) {
        viewToggleText.textContent = isCompactView ? 'Card View' : 'Compact View';
      }
      try {
        localStorage.setItem('cielo_menu_view', isCompactView ? 'compact' : 'cards');
      } catch (e) {}
    });
  }

  // 5. Global Reset Helper
  window.clearMenuFilters = function() {
    searchQuery = '';
    isVegOnly = false;
    if (searchInput) {
      searchInput.value = '';
      if (searchClearBtn) searchClearBtn.style.display = 'none';
    }
    if (vegToggleBtn) {
      vegToggleBtn.setAttribute('data-active', 'false');
      vegToggleBtn.classList.remove('is-active');
    }
    if (tabButtons.length > 0) {
      tabButtons[0].click();
    } else {
      applyFilters();
    }
  };

  if (filterResetBtn) {
    filterResetBtn.addEventListener('click', window.clearMenuFilters);
  }

  // Initial filter run
  applyFilters();
});
