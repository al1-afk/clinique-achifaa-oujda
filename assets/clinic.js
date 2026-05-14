/* =========================================
   Clinique Achifaa Oujda — Shared JS
   Used by service pages + master services page
========================================= */
(function() {
  'use strict';

  // Navbar scroll effect
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('is-scrolled', window.scrollY > 8);
    }, { passive: true });
  }

  // Mobile drawer
  const burger = document.getElementById('navBurger');
  const drawer = document.getElementById('mobileNav');
  const close  = document.getElementById('mobileNavClose');
  const backdrop = document.getElementById('mobileNavBackdrop');
  if (burger && drawer) {
    const openDrawer = () => {
      drawer.hidden = false;
      requestAnimationFrame(() => drawer.classList.add('is-open'));
      burger.setAttribute('aria-expanded', 'true');
      document.body.classList.add('no-scroll');
    };
    const closeDrawer = () => {
      drawer.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('no-scroll');
      setTimeout(() => { drawer.hidden = true; }, 320);
    };
    burger.addEventListener('click', () => {
      if (burger.getAttribute('aria-expanded') === 'true') closeDrawer();
      else openDrawer();
    });
    if (close)    close.addEventListener('click', closeDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') closeDrawer();
    });
  }

  // FAQ accordion (button-driven for a11y)
  const faqList = document.getElementById('faqList');
  if (faqList) {
    faqList.addEventListener('click', (e) => {
      const btn = e.target.closest('.faq-q');
      if (!btn) return;
      const item = btn.closest('.faq-item');
      const wasOpen = item.classList.contains('is-open');
      faqList.querySelectorAll('.faq-item').forEach(i => {
        i.classList.remove('is-open');
        const b = i.querySelector('.faq-q');
        if (b) b.setAttribute('aria-expanded', 'false');
      });
      if (!wasOpen) {
        item.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  }

  // Persist the page's language as the user preference, so when they
  // navigate to the homepage we can auto-apply Arabic again.
  try {
    const pageLang = document.documentElement.lang === 'ar' ? 'ar' : 'fr';
    localStorage.setItem('lang', pageLang);
  } catch (e) {}

  // Service-page language toggle — anchor link redirects between
  // /services/X.html and /services/ar/X.html. We just intercept the
  // click to persist the user's choice in localStorage before navigating.
  const langAnchor = document.querySelector('a.lang-toggle');
  if (langAnchor) {
    langAnchor.addEventListener('click', () => {
      const goingToAr = /\bar\//.test(langAnchor.getAttribute('href') || '');
      try { localStorage.setItem('lang', goingToAr ? 'ar' : 'fr'); } catch (e) {}
    });
  }

  // Fallback button toggle (kept for compatibility with any page using <button id="langToggle">)
  const langToggleBtn = document.getElementById('langToggle');
  if (langToggleBtn && langToggleBtn.tagName === 'BUTTON') {
    langToggleBtn.addEventListener('click', () => {
      const path = window.location.pathname;
      const isAr = /\/services\/ar\//.test(path) || document.documentElement.lang === 'ar';
      let nextHref;
      if (isAr) {
        nextHref = path.replace(/\/services\/ar\//, '/services/');
      } else if (/\/services\//.test(path)) {
        nextHref = path.replace(/\/services\//, '/services/ar/');
      } else {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', 'ar');
        nextHref = url.toString();
      }
      try { localStorage.setItem('lang', isAr ? 'fr' : 'ar'); } catch (e) {}
      window.location.href = nextHref;
    });
    const label = document.getElementById('langLabel');
    if (label) {
      const isAr = document.documentElement.lang === 'ar';
      label.textContent = isAr ? 'Français' : 'العربية';
    }
  }
})();
