/**
 * Cielo Cafe and Restro - Main App Logic
 * Sticky Navigation, Dynamic Live Operating Status, Mobile Menu
 */

document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.cielo-header');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const openDrawerBtn = document.getElementById('openDrawerBtn');
  const closeDrawerBtn = document.getElementById('closeDrawerBtn');

  // 1. Sticky Navigation & Dynamic Header Contrast Alteration (White/Black background detection)
  const heroSection = document.getElementById('heroSection');
  const footer = document.querySelector('.cielo-footer');

  function updateHeaderContrast() {
    if (!header) return;

    const scrollY = window.scrollY || window.pageYOffset;

    // Toggle sticky scrolled state
    if (scrollY > 20) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }

    const headerHeight = header.offsetHeight || 60;

    // Check if the header is currently overlapping a dark background section
    let isOverDark = false;

    // A. Check Hero Section on homepage (dark night entrance photo)
    if (heroSection) {
      const heroRect = heroSection.getBoundingClientRect();
      // As long as hero bottom is below the top part of the header, we're over the dark hero
      if (heroRect.bottom > (headerHeight * 0.45)) {
        isOverDark = true;
      }
    }

    // B. Check dark footer
    if (!isOverDark && footer) {
      const footerRect = footer.getBoundingClientRect();
      if (footerRect.top <= (headerHeight * 0.55) && footerRect.bottom > 0) {
        isOverDark = true;
      }
    }

    // C. Check any other section explicitly flagged as dark
    if (!isOverDark) {
      const darkSections = document.querySelectorAll('[data-header-theme="dark"]');
      for (let sec of darkSections) {
        if (sec === heroSection || sec === footer) continue;
        const rect = sec.getBoundingClientRect();
        if (rect.top <= (headerHeight * 0.55) && rect.bottom >= (headerHeight * 0.45)) {
          isOverDark = true;
          break;
        }
      }
    }

    // Apply the alternating contrast classes
    if (isOverDark) {
      header.classList.remove('theme-light-bg');
      header.classList.add('theme-dark-bg');
    } else {
      header.classList.remove('theme-dark-bg');
      header.classList.add('theme-light-bg');
    }
  }

  window.addEventListener('scroll', updateHeaderContrast, { passive: true });
  window.addEventListener('resize', updateHeaderContrast, { passive: true });
  updateHeaderContrast();

  // 2. Cinematic Page Transitions (Full-Screen Luxury Curtain)
  const transitionScreen = document.getElementById('cieloTransitionScreen');
  let isTransitioning = false;

  // Arrival animation: If landing from a page transition, glide curtain away smoothly
  try {
    if (sessionStorage.getItem('cielo_page_transition') === '1') {
      sessionStorage.removeItem('cielo_page_transition');
      if (transitionScreen) {
        // Ensure state-hold is applied on arrival
        transitionScreen.className = 'cielo-transition-screen state-hold';
        isTransitioning = true;

        // Brief delay for DOM & fonts to settle cleanly, then reveal the page
        setTimeout(() => {
          transitionScreen.className = 'cielo-transition-screen state-exit';
          setTimeout(() => {
            transitionScreen.className = 'cielo-transition-screen';
            isTransitioning = false;
          }, 600);
        }, 120);
      }
    }
  } catch (err) {}

  // Reset transition screen on browser back/forward navigation (bfcache)
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      try {
        sessionStorage.removeItem('cielo_page_transition');
      } catch (err) {}
      if (transitionScreen) {
        transitionScreen.className = 'cielo-transition-screen';
        isTransitioning = false;
      }
    }
  });

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

  // Close drawer with Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileDrawer?.classList.contains('is-active')) {
      mobileDrawer.classList.remove('is-active');
      document.body.style.overflow = '';
    }
  });

  // Close drawer if clicking on the overlay backdrop
  if (mobileDrawer) {
    mobileDrawer.addEventListener('click', (e) => {
      if (e.target === mobileDrawer) {
        mobileDrawer.classList.remove('is-active');
        document.body.style.overflow = '';
      }
    });
  }

  // Path normalization helper to reliably detect same-page vs cross-page navigation
  function normalizePath(pathname) {
    if (!pathname) return '';
    try {
      let p = decodeURIComponent(pathname).toLowerCase().trim();
      p = p.replace(/\/+$/, '');
      p = p.replace(/\/(index\.(php|html?))$/, '');
      return p;
    } catch (e) {
      return pathname;
    }
  }

  // Navigation Links Transition Handler
  const navLinkSelector = [
    '.drawer-nav-link',
    '.brand-logo',
    '.drawer-brand-logo',
    '.footer-brand-title',
    '.footer-nav-link',
    '.hero-scroll-cue',
    'a.btn-primary[href="menu.php"]',
    'a[href*="index.php"]',
    'a[href*="menu.php"]',
    'a[href*="about.php"]',
    'a[href*="gallery.php"]',
    'a[href*="events.php"]',
    'a[href*="contact.php"]'
  ].join(', ');

  const navLinks = document.querySelectorAll(navLinkSelector);

  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const rawHref = link.getAttribute('href');
      if (
        !rawHref ||
        rawHref === '#' ||
        rawHref.startsWith('tel:') ||
        rawHref.startsWith('mailto:') ||
        link.target === '_blank' ||
        link.hasAttribute('download')
      ) {
        return;
      }

      // Check same-origin
      let targetUrl;
      try {
        targetUrl = new URL(link.href, window.location.href);
      } catch (err) {
        return;
      }

      if (targetUrl.origin !== window.location.origin) {
        return;
      }

      if (isTransitioning) {
        e.preventDefault();
        return;
      }

      const currentNorm = normalizePath(window.location.pathname);
      const targetNorm = normalizePath(targetUrl.pathname);
      const isSamePage = (currentNorm === targetNorm);
      const hasHash = Boolean(targetUrl.hash && targetUrl.hash !== '#' && targetUrl.hash !== '#top');

      // -------------------------------------------------------------
      // Case 1: In-Page Navigation (same page)
      // -------------------------------------------------------------
      if (isSamePage) {
        // A. Clicking a section anchor on the same page (e.g. index.php#story)
        if (hasHash) {
          const targetEl = document.querySelector(targetUrl.hash);
          if (targetEl) {
            e.preventDefault();
            isTransitioning = true;

            // Slide curtain up
            transitionScreen.className = 'cielo-transition-screen state-enter';

            setTimeout(() => {
              // Screen is now fully covered
              transitionScreen.className = 'cielo-transition-screen state-hold';

              // Close drawer under the curtain
              if (mobileDrawer?.classList.contains('is-active')) {
                mobileDrawer.classList.remove('is-active');
                document.body.style.overflow = '';
              }

              // Scroll smoothly under cover to target element
              targetEl.scrollIntoView({ behavior: 'auto' });
              try {
                history.replaceState(null, '', targetUrl.hash);
              } catch (err) {}

              // Hold briefly and slide curtain out
              setTimeout(() => {
                transitionScreen.className = 'cielo-transition-screen state-exit';
                setTimeout(() => {
                  transitionScreen.className = 'cielo-transition-screen';
                  isTransitioning = false;
                }, 590);
              }, 160);
            }, 520);
            return;
          }
        }

        // B. Same page without hash (or clicking logo while on homepage, or clicking Menu on menu page)
        // If already at the top, just close drawer cleanly
        if (window.scrollY <= 40) {
          if (mobileDrawer?.classList.contains('is-active')) {
            mobileDrawer.classList.remove('is-active');
            document.body.style.overflow = '';
          }
          return;
        }

        // If scrolled down, curtain-scroll back to the top
        e.preventDefault();
        isTransitioning = true;
        transitionScreen.className = 'cielo-transition-screen state-enter';

        setTimeout(() => {
          transitionScreen.className = 'cielo-transition-screen state-hold';

          if (mobileDrawer?.classList.contains('is-active')) {
            mobileDrawer.classList.remove('is-active');
            document.body.style.overflow = '';
          }

          window.scrollTo({ top: 0, behavior: 'auto' });
          try {
            history.replaceState(null, '', window.location.pathname);
          } catch (err) {}

          setTimeout(() => {
            transitionScreen.className = 'cielo-transition-screen state-exit';
            setTimeout(() => {
              transitionScreen.className = 'cielo-transition-screen';
              isTransitioning = false;
            }, 590);
          }, 160);
        }, 520);
        return;
      }

      // -------------------------------------------------------------
      // Case 2: Cross-Page Navigation (different page)
      // -------------------------------------------------------------
      e.preventDefault();
      isTransitioning = true;

      try {
        sessionStorage.setItem('cielo_page_transition', '1');
      } catch (err) {}

      // Slide curtain up
      transitionScreen.className = 'cielo-transition-screen state-enter';

      // Safety timeout in case navigation stalls or is aborted
      setTimeout(() => {
        if (isTransitioning) {
          try {
            sessionStorage.removeItem('cielo_page_transition');
          } catch (err) {}
          if (transitionScreen) {
            transitionScreen.className = 'cielo-transition-screen';
            isTransitioning = false;
          }
        }
      }, 3500);

      setTimeout(() => {
        // Full cover reached
        transitionScreen.className = 'cielo-transition-screen state-hold';

        // Close drawer while covered so it's clean on destination or return
        if (mobileDrawer?.classList.contains('is-active')) {
          mobileDrawer.classList.remove('is-active');
          document.body.style.overflow = '';
        }

        // Navigate to the target page (curtain stays covering until new page loads)
        window.location.href = link.href;
      }, 520);
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
