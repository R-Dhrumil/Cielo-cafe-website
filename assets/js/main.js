/**
 * Cielo Cafe and Restro - Main App Logic
 * Sticky Navigation, Dynamic Live Operating Status, Mobile Menu
 */

document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.cielo-header');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const openDrawerBtn = document.getElementById('openDrawerBtn');
  const closeDrawerBtn = document.getElementById('closeDrawerBtn');

  // 1. Sticky Navigation on Scroll
  window.addEventListener('scroll', () => {
    if (window.scrollY > 30) {
      header?.classList.add('is-scrolled');
    } else {
      header?.classList.remove('is-scrolled');
    }
  }, { passive: true });

  // 2. Full-Screen Cinematic Loading Transition Screen
  const transitionScreen = document.getElementById('cieloTransitionScreen');
  let isTransitioning = false;

  function runTransition(onCovered) {
    if (!transitionScreen) {
      if (onCovered) onCovered();
      return;
    }
    if (isTransitioning) return;
    isTransitioning = true;

    // Reset immediately with zero transition
    transitionScreen.className = 'cielo-transition-screen';
    void transitionScreen.offsetWidth; // force reflow

    // 1. Enter: Slide in smoothly upwards from bottom
    transitionScreen.className = 'cielo-transition-screen state-enter';

    // 2. When completely arrived at center (580ms)
    setTimeout(() => {
      transitionScreen.className = 'cielo-transition-screen state-hold';

      if (onCovered) onCovered();

      // 3. Luxury stay pause (260ms)
      setTimeout(() => {
        // 4. Exit: Continue sliding upwards to the top smoothly with matching curve
        transitionScreen.className = 'cielo-transition-screen state-exit';

        // 5. When completely exited off-screen (580ms)
        setTimeout(() => {
          // Reset cleanly in hidden state
          transitionScreen.className = 'cielo-transition-screen';
          isTransitioning = false;
        }, 590);
      }, 260);
    }, 580);
  }

  // 3. Mobile Drawer Navigation
  if (openDrawerBtn && mobileDrawer) {
    openDrawerBtn.addEventListener('click', () => {
      mobileDrawer.classList.add('is-active');
      document.body.style.overflow = 'hidden';
    });
  }

  if (closeDrawerBtn && mobileDrawer) {
    closeDrawerBtn.addEventListener('click', () => {
      mobileDrawer.classList.remove('is-active');
      document.body.style.overflow = '';
    });
  }

  // Navigation Links Transition Handler
  const navLinks = document.querySelectorAll(
    '.drawer-nav-link, .brand-logo, .cielo-navbar a[href*="#"]'
  );

  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const rawHref = link.getAttribute('href');
      if (!rawHref || rawHref === '#' || rawHref.startsWith('tel:') || rawHref.startsWith('mailto:') || link.target === '_blank') {
        return;
      }

      // Check if link contains an in-page section anchor (#)
      const hashIndex = rawHref.indexOf('#');
      const hasHash = hashIndex !== -1;
      const hash = hasHash ? rawHref.substring(hashIndex) : '';
      const pathPart = hasHash ? rawHref.substring(0, hashIndex) : rawHref;

      // Determine if destination is an in-page section on the current page
      const currentPath = window.location.pathname.replace(/\/$/, '');
      const isHomepage = currentPath.endsWith('index.php') || currentPath === '' || currentPath.endsWith('/Cielo cafe website');
      
      const isLogoClickOnHomepage = link.classList.contains('brand-logo') && isHomepage;

      const isTargetOnSamePage = (hasHash && (
        pathPart === '' ||
        pathPart === '#' ||
        (isHomepage && (pathPart === 'index.php' || pathPart === './' || pathPart === './index.php'))
      )) || isLogoClickOnHomepage;

      e.preventDefault();

      runTransition(() => {
        // Close drawer while screen is covered so user never sees drawer jerk
        if (mobileDrawer?.classList.contains('is-active')) {
          mobileDrawer.classList.remove('is-active');
          document.body.style.overflow = '';
        }

        if (isLogoClickOnHomepage) {
          // Logo clicked on homepage: scroll cleanly to top under the curtain
          window.scrollTo({ top: 0, behavior: 'auto' });
          try {
            history.replaceState(null, '', window.location.pathname);
          } catch (err) {}
        } else if (isTargetOnSamePage) {
          const targetEl = document.querySelector(hash);
          if (targetEl) {
            targetEl.scrollIntoView({ behavior: 'auto' });
            try {
              history.replaceState(null, '', hash);
            } catch (err) {}
          }
        } else {
          window.location.href = link.href;
        }
      });
    });
  });

  // 3. Dynamic Real-time Cafe Hours Calculator (11:00 AM - 11:30 PM IST)
  function updateHoursStatus() {
    const now = new Date();
    // Convert to Indian Standard Time (UTC+5:30)
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const istDate = new Date(utc + (3600000 * 5.5));

    const hours = istDate.getHours();
    const minutes = istDate.getMinutes();
    const decimal = hours + (minutes / 60);

    const isOpen = (decimal >= 11.0 && decimal <= 23.5);
    const statusBadges = document.querySelectorAll('.topbar-status');

    statusBadges.forEach(badge => {
      const textEl = badge.querySelector('.status-text');
      if (isOpen) {
        badge.classList.remove('is-closed');
        badge.classList.add('is-open');
        if (textEl) textEl.textContent = 'Open Now · 11 AM - 11:30 PM';
      } else {
        badge.classList.remove('is-open');
        badge.classList.add('is-closed');
        if (textEl) textEl.textContent = 'Closed Now · Opens at 11 AM';
      }
    });
  }

  updateHoursStatus();
  setInterval(updateHoursStatus, 60000);
});
