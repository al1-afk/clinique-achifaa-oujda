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

  // Lang toggle (page-level — sends back to home with ?lang=ar)
  const langToggle = document.getElementById('langToggle');
  if (langToggle) {
    langToggle.addEventListener('click', () => {
      const url = new URL(window.location.href);
      const isAr = url.searchParams.get('lang') === 'ar' || document.documentElement.lang === 'ar';
      if (isAr) url.searchParams.delete('lang');
      else      url.searchParams.set('lang', 'ar');
      window.location.href = url.toString();
    });
    // Update label based on current lang
    const label = document.getElementById('langLabel');
    if (label) {
      const isAr = new URLSearchParams(window.location.search).get('lang') === 'ar' || document.documentElement.lang === 'ar';
      label.textContent = isAr ? 'Français' : 'العربية';
    }
  }

  // Apply ?lang=ar — switch direction + use Arabic translations injected via data-ar
  (function applyLangFromUrl() {
    const lang = new URLSearchParams(window.location.search).get('lang');
    if (lang !== 'ar') return;
    document.documentElement.lang = 'ar';
    document.documentElement.dir = 'rtl';
    document.querySelectorAll('[data-ar]').forEach(el => {
      const ar = el.getAttribute('data-ar');
      if (ar) el.innerHTML = ar;
    });
  })();
})();
