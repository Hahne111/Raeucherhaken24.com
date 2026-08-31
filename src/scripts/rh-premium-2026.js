/* =====================================================================
   RÄUCHERHAKEN24 · PREMIUM RUNTIME 2026  (rh-premium-2026.js)
   ---------------------------------------------------------------------
   Ergänzt die bestehende Shop-Logik. Es wird keine Geschäftslogik
   ersetzt: Warenkorb, Preise, Produktdaten und Checkout bleiben in den
   vorhandenen Modulen. Diese Datei kümmert sich um Darstellung,
   Bewegung, Bedienbarkeit und um das Aufräumen doppelter Oberflächen.
   ===================================================================== */
(() => {
  'use strict';
  if (window.__RH24_PREMIUM_2026__) return;
  window.__RH24_PREMIUM_2026__ = true;

  const VERSION = '2026.1';
  const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  // Diese Datei wird mit defer geladen und läuft damit vor DOMContentLoaded.
  // Deshalb wird bewusst auf dieses Ereignis gewartet: So haben die übrigen
  // Module (Kopfzeile, Warenkorb, Icons) ihre Bausteine bereits eingehängt.
  const onReady = fn => {
    if (document.readyState === 'complete') { setTimeout(fn, 0); return; }
    document.addEventListener('DOMContentLoaded', fn, { once: true });
  };

  /* ---------------------------------------------------------------
     1 · Canonical- und Social-Meta zur Laufzeit
     Die Seite läuft unter zwei Domains (.de und .com). Ein fest
     eingetragener Canonical wäre auf einer der beiden falsch, deshalb
     wird er aus der tatsächlich aufgerufenen Adresse gebildet.
  ----------------------------------------------------------------*/
  function seoMeta() {
    try {
      if (location.protocol === 'file:') return;
      const clean = location.origin + location.pathname.replace(/\/index\.html$/i, '/');
      let link = document.querySelector('link[rel="canonical"]');
      if (!link) {
        link = document.createElement('link');
        link.rel = 'canonical';
        document.head.appendChild(link);
      }
      link.href = clean;

      const put = (attr, key, val) => {
        if (!val) return;
        let m = document.head.querySelector(`meta[${attr}="${key}"]`);
        if (!m) { m = document.createElement('meta'); m.setAttribute(attr, key); document.head.appendChild(m); }
        if (!m.content) m.content = val;
      };
      const title = document.title || 'Räucherhaken24';
      const desc  = document.querySelector('meta[name="description"]')?.content || '';
      put('property', 'og:type', /index\.html$|\/$/.test(location.pathname) ? 'website' : 'article');
      put('property', 'og:site_name', 'Räucherhaken24');
      put('property', 'og:locale', 'de_DE');
      put('property', 'og:title', title);
      put('property', 'og:description', desc);
      put('property', 'og:url', clean);
      put('property', 'og:image', location.origin + location.pathname.replace(/[^/]*$/, '') + 'assets/smoky-logo-v1041.jpg');
      put('name', 'twitter:card', 'summary_large_image');
      put('name', 'twitter:title', title);
      put('name', 'twitter:description', desc);
    } catch (e) { /* SEO-Meta ist optional – niemals blockierend */ }
  }

  /* ---------------------------------------------------------------
     2 · Doppelte Oberflächen entfernen
     Historisch haben drei Chat-Module gleichzeitig einen Button und
     ein Panel in den Body geschrieben. Übrig bleibt genau eines.
  ----------------------------------------------------------------*/
  function dedupeLegacyUi() {
    // Alte Chat-Panels der Vorversionen entfernen (der neue Berater
    // baut seine eigene Oberfläche auf).
    $$('.aiPanel, .smokyProPanel, .smokyNudge').forEach(el => el.remove());
    $$('button.ai, .mobileSmoky, .smokyProLauncher').forEach(el => el.remove());

    // Doppelte Kategorie-Umschalter auf Produkt-/Kategorieseiten
    const toggles = $$('.rh24CategoryMobileToggle');
    toggles.slice(1).forEach(t => t.remove());
    const inlineToggle = $$('button, a').find(b =>
      /Kategorien\s*&\s*Bereiche/i.test(b.textContent || '') && !b.classList.contains('rh24CategoryMobileToggle'));
    if (inlineToggle && toggles.length) inlineToggle.remove();

    // Mehrfach eingehängte Warenkorb-Buttons
    const carts = $$('button.cartBtn');
    carts.slice(1).forEach(c => c.remove());

    // Der Warenkorb sitzt bereits gut sichtbar in der mitlaufenden
    // Kopfzeile. Ein zweiter schwebender Warenkorb-Knopf wäre doppelt
    // und würde Inhalte verdecken – er bleibt daher nur auf Seiten
    // ohne Kopfnavigation erhalten.
    if ($('#rh24NavCart, #rh24DynamicNav .rh104Cart')) {
      $$('button.cartBtn').forEach(c => c.remove());
    }

    // Zweiter Nach-oben-Knopf aus einem Altmodul: er läge unten links
    // über dem Cookie-Hinweis. Der Knopf in der Aktionsleiste bleibt.
    $$('.backTop').forEach(el => el.remove());

    // Zwei Icon-Module setzen teilweise in dasselbe Bedienelement ein
    // Symbol. Sichtbar war das an Schaltflächen mit zwei Warenkörben.
    // Das erste Symbol bleibt, weitere werden entfernt.
    $$('button, a').forEach(el => {
      const icons = $$(':scope > svg, :scope > .rh1076ButtonIcon, :scope > .v17IconWrap', el);
      if (icons.length > 1) icons.slice(1).forEach(i => i.remove());
    });

    // Bedienelemente, die bereits ein SVG-Icon tragen, bekommen eine
    // Markierung. Damit lassen sich zusätzliche Emoji-Pseudoelemente
    // aus den Altversionen gezielt abschalten.
    $$('button, a.btn, .detailLink').forEach(el => {
      if (el.querySelector('svg')) el.classList.add('rhIconized');
      else el.classList.remove('rhIconized');
    });
  }

  /* ---------------------------------------------------------------
     3 · Aktionsleiste unten rechts
     Warenkorb, Berater und Nach-oben-Knopf lagen übereinander.
     Sie werden in einen sauberen, kollisionsfreien Stapel gesetzt.
  ----------------------------------------------------------------*/
  function buildFabStack() {
    if ($('.rhFabStack')) return $('.rhFabStack');
    const stack = document.createElement('div');
    stack.className = 'rhFabStack';
    stack.setAttribute('role', 'complementary');
    stack.setAttribute('aria-label', 'Schnellzugriff');
    document.body.appendChild(stack);

    const toTop = document.createElement('button');
    toTop.type = 'button';
    toTop.className = 'rhToTop';
    toTop.setAttribute('aria-label', 'Zurück nach oben');
    toTop.innerHTML = '<svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>';
    toTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: reduceMotion() ? 'auto' : 'smooth' });
      $('.rh104Brand, h1, main')?.focus?.({ preventScroll: true });
    });
    stack.appendChild(toTop);

    const cart = $('button.cartBtn');
    if (cart) stack.appendChild(cart);

    let ticking = false;
    const sync = () => {
      toTop.classList.toggle('isVisible', window.scrollY > 620);
      ticking = false;
    };
    window.addEventListener('scroll', () => {
      if (!ticking) { ticking = true; requestAnimationFrame(sync); }
    }, { passive: true });
    sync();

    // Alte, frei schwebende Nach-oben-Pfeile der Vorversionen ausblenden
    $$('a,button').forEach(el => {
      if (el.closest('.rhFabStack')) return;
      const t = (el.textContent || '').trim();
      const cs = getComputedStyle(el);
      if (t === '↑' && cs.position === 'fixed') el.style.display = 'none';
    });
    return stack;
  }

  /* ---------------------------------------------------------------
     4 · Scroll-Reveal mit gestaffeltem Einblenden
  ----------------------------------------------------------------*/
  const REVEAL_SELECTOR = [
    '.rh104Category', '.rh104Product', '.rh104Article', '.rh104TrustCard', '.rh104Benefit',
    '.card', '.rh66ProductCard', '.woodShopCard', '.dbProductCard', '.natureCard',
    '.laugeCard', '.thermoCard', '.knowledgeCard', '.featurePanel', '.moduleBand',
    '.wizard', '.rh104Smoky', '.trustModule', '.rh104SectionHead', '.sectionHead',
    '.rh66CockpitHead', '.legal > h2', '.priceExplain', '.rh66CategoryCockpit a',
    '.recipeCard', '.productPage'
  ].join(',');

  let revealObserver = null;
  function reveal(root = document) {
    const els = $$(REVEAL_SELECTOR, root).filter(el => !el.dataset.rhReveal);
    if (!els.length) return;
    els.forEach(el => { el.dataset.rhReveal = '1'; });

    if (reduceMotion() || !('IntersectionObserver' in window)) {
      els.forEach(el => el.classList.add('rhReveal', 'isIn'));
      return;
    }
    // Bereits sichtbare Elemente sofort zeigen (kein Aufblitzen beim Laden)
    const vh = window.innerHeight;
    const groups = new Map();
    els.forEach(el => {
      el.classList.add('rhReveal');
      if (el.matches('.rh104Product,.rh66ProductCard,.rh104Category,.rh104Article,.woodShopCard,.dbProductCard,.rh104TrustCard,.rh104Benefit')) {
        el.classList.add('rhRevealScale');
      }
      const parent = el.parentElement;
      if (parent) {
        if (!groups.has(parent)) groups.set(parent, 0);
        const i = groups.get(parent);
        if (i < 6) el.dataset.d = String(i + 1);
        groups.set(parent, i + 1);
      }
    });

    if (!revealObserver) {
      revealObserver = new IntersectionObserver(entries => {
        entries.forEach(e => {
          if (e.isIntersecting) { e.target.classList.add('isIn'); revealObserver.unobserve(e.target); }
        });
      }, { threshold: 0.04, rootMargin: '0px 0px -40px 0px' });
    }
    els.forEach(el => {
      if (el.getBoundingClientRect().top < vh * 0.9) el.classList.add('isIn');
      else revealObserver.observe(el);
    });
  }

  /* ---------------------------------------------------------------
     5 · Dezenter Parallax des Hero-Motivs (nur transform)
  ----------------------------------------------------------------*/
  function heroParallax() {
    const media = $('.rh104HeroVisual');
    if (!media || reduceMotion() || window.innerWidth < 900) return;
    media.classList.add('rhParallax');
    let raf = 0;
    const run = () => {
      raf = 0;
      const y = Math.min(window.scrollY, 640);
      media.style.transform = `translate3d(0, ${(y * 0.045).toFixed(2)}px, 0)`;
    };
    window.addEventListener('scroll', () => { if (!raf) raf = requestAnimationFrame(run); }, { passive: true });
  }

  /* ---------------------------------------------------------------
     6 · Mobile Navigation: Escape, Scroll-Sperre, Klick nach außen
  ----------------------------------------------------------------*/
  function mobileNav() {
    const shell = $('#rh24DynamicNav');
    if (!shell) return;
    const btn = $('#rh24MobileMenu', shell);
    const close = () => {
      shell.classList.remove('mobileOpen');
      btn?.setAttribute('aria-expanded', 'false');
      if (btn) btn.textContent = '☰';
      document.body.style.removeProperty('overflow');
    };
    const observer = new MutationObserver(() => {
      const open = shell.classList.contains('mobileOpen');
      document.body.style.overflow = open ? 'hidden' : '';
    });
    observer.observe(shell, { attributes: true, attributeFilter: ['class'] });

    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    shell.querySelectorAll('.rh24NavLinks a').forEach(a => a.addEventListener('click', close));
    window.addEventListener('resize', () => { if (window.innerWidth > 900) close(); });
  }

  /* ---------------------------------------------------------------
     7 · Bilder: Layout-Sprünge vermeiden, spätes Laden aktivieren
  ----------------------------------------------------------------*/
  const PRODUCT_MEDIA = '.rh104ProductImg,.rh66ProductCard .imgBox,.dbProductImage,' +
    '.woodProductImage,.natureImage,.laugeVisual,.thermoImage,.rh104UnifiedMedia,' +
    '.rh104CategoryMedia,.productImage,.imgBox';

  /** Markiert Produktbilder, damit Altstile gezielt neutralisiert werden können. */
  function markProductImages(root = document) {
    $$(PRODUCT_MEDIA, root).forEach(box => {
      box.querySelectorAll('img').forEach(img => img.classList.add('rhProductImg'));
    });
  }

  function imagePerf() {
    markProductImages();
    const imgs = $$('img');
    imgs.forEach((img, i) => {
      if (!img.hasAttribute('decoding')) img.decoding = 'async';
      const isHero = i === 0 || img.closest('.rh104HeroVisual, .hero, header');
      if (isHero) {
        img.setAttribute('fetchpriority', 'high');
        img.loading = 'eager';
      } else if (!img.hasAttribute('loading')) {
        img.loading = 'lazy';
      }
      if (!img.getAttribute('alt')) img.setAttribute('alt', '');
      img.addEventListener('error', () => {
        const box = img.closest('.imgBox, .rh104ProductImg, .dbProductImage, .rh104CategoryMedia');
        if (box && !box.querySelector('.rhImgFallback')) {
          const f = document.createElement('span');
          f.className = 'rhImgFallback';
          f.textContent = 'Bild derzeit nicht verfügbar';
          f.style.cssText = 'font-size:12.5px;color:#a89f96;text-align:center;padding:12px';
          img.style.display = 'none';
          box.appendChild(f);
        }
      }, { once: true });
    });
  }

  /* ---------------------------------------------------------------
     8 · Produktkatalog: verständlicher Zustand statt Dauerladen
  ----------------------------------------------------------------*/
  function catalogState() {
    const grid = $('#dbProductGrid');
    const section = $('#dbProductSection');
    if (!grid) return;
    let settled = false;
    window.addEventListener('rh24:catalog-synced', () => { settled = true; }, { once: false });

    setTimeout(() => {
      if (settled) return;
      const still = (grid.textContent || '').includes('werden geladen');
      if (!still) return;
      // Der Katalog ist nicht erreichbar (z. B. Datenbank noch nicht
      // eingerichtet). Der Bereich verschwindet, statt dauerhaft zu laden.
      if (section) section.style.display = 'none';
    }, 9000);
  }

  /* ---------------------------------------------------------------
     9 · Formulare: klare Fokus-, Fehler- und Erfolgszustände
  ----------------------------------------------------------------*/
  function forms() {
    $$('form').forEach(form => {
      if (form.dataset.rhForm) return;
      form.dataset.rhForm = '1';
      if (form.closest('#rh24PasswordGate')) return;

      const fields = $$('input,textarea,select', form)
        .filter(f => !['hidden', 'submit', 'button'].includes(f.type));

      const showError = (field, msg) => {
        field.classList.add('isInvalid');
        field.classList.remove('isValid');
        field.setAttribute('aria-invalid', 'true');
        let e = field.parentElement?.querySelector(':scope > .fieldError');
        if (!e) {
          e = document.createElement('span');
          e.className = 'fieldError';
          e.setAttribute('role', 'alert');
          field.insertAdjacentElement('afterend', e);
        }
        e.textContent = msg;
      };
      const clearError = field => {
        field.classList.remove('isInvalid');
        field.removeAttribute('aria-invalid');
        const e = field.parentElement?.querySelector(':scope > .fieldError');
        if (e) e.remove();
        if (field.value.trim()) field.classList.add('isValid');
        else field.classList.remove('isValid');
      };
      const messageFor = field => {
        const v = field.validity;
        if (v.valueMissing) return 'Bitte ausfüllen.';
        if (v.typeMismatch && field.type === 'email') return 'Bitte eine gültige E-Mail-Adresse eingeben.';
        if (v.typeMismatch && field.type === 'tel') return 'Bitte eine gültige Telefonnummer eingeben.';
        if (v.tooShort) return `Mindestens ${field.minLength} Zeichen.`;
        if (v.patternMismatch) return 'Das Format stimmt noch nicht.';
        return 'Bitte prüfen Sie diese Eingabe.';
      };

      fields.forEach(f => {
        f.addEventListener('blur', () => {
          if (!f.value.trim() && !f.required) { clearError(f); return; }
          if (f.checkValidity()) clearError(f); else showError(f, messageFor(f));
        });
        f.addEventListener('input', () => { if (f.classList.contains('isInvalid') && f.checkValidity()) clearError(f); });
      });

      form.addEventListener('submit', e => {
        let firstBad = null;
        fields.forEach(f => {
          if (f.checkValidity()) clearError(f);
          else { showError(f, messageFor(f)); if (!firstBad) firstBad = f; }
        });
        if (firstBad) {
          e.preventDefault();
          firstBad.focus();
          firstBad.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'center' });
          if (typeof window.toast === 'function') window.toast('Bitte prüfen Sie die markierten Felder.');
        }
      }, true);
    });
  }

  /* ---------------------------------------------------------------
    10 · Interaktive Elemente absichern
    Kein sichtbarer Knopf darf ins Leere führen.
  ----------------------------------------------------------------*/
  function guardInteractives() {
    $$('a[href="#"], a[href=""], a[href^="javascript:"]').forEach(a => {
      if (a.closest('.rh24Mega')) return;
      const label = (a.textContent || '').trim();
      // Bekannte Muster sinnvoll verdrahten statt tote Links stehen zu lassen
      if (/nach oben|top/i.test(label)) {
        a.addEventListener('click', e => { e.preventDefault(); window.scrollTo({ top: 0, behavior: reduceMotion() ? 'auto' : 'smooth' }); });
        return;
      }
      if (/beratung starten|30-sekunden/i.test(label)) {
        a.addEventListener('click', e => { e.preventDefault(); window.RH24Smoky?.open?.('guide'); });
        return;
      }
      if (/warenkorb/i.test(label)) {
        a.addEventListener('click', e => { e.preventDefault(); window.openCart?.(); });
        return;
      }
      // Ohne erkennbare Aufgabe: kein Link, sondern reiner Text.
      a.addEventListener('click', e => e.preventDefault());
      a.removeAttribute('href');
      a.setAttribute('role', 'presentation');
    });

    // Klickbare Karten für Tastatur nutzbar machen
    $$('[onclick]:not(a):not(button):not(input)').forEach(el => {
      if (el.hasAttribute('tabindex')) return;
      el.setAttribute('tabindex', '0');
      if (!el.getAttribute('role')) el.setAttribute('role', 'button');
      el.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
      });
    });
  }

  /* ---------------------------------------------------------------
    10b · Zugängliche Namen für reine Symbol-Schaltflächen
    Das Icon-Modul ersetzt Zeichen wie „×“ durch SVG-Grafiken. Danach
    haben manche Schaltflächen keinen vorlesbaren Namen mehr.
  ----------------------------------------------------------------*/
  const NAME_MAP = [
    ['.drawerClose', 'Warenkorb schließen'],
    ['.zoomCard .close', 'Ansicht schließen'],
    ['.zoom', 'Produktbild vergrößern'],
    ['.rh104HeroZoom', 'Bild vergrößern'],
    ['.moduleDrawer .close', 'Bereich schließen'],
    ['.cookieFab', 'Cookie-Einstellungen öffnen'],
    ['.rh24MobileMenuBtn', 'Menü öffnen'],
    ['.backTop', 'Zurück nach oben'],
  ];

  function accessibleName(el) {
    const aria = (el.getAttribute('aria-label') || '').trim();
    if (aria) return aria;
    const txt = (el.textContent || '').trim();
    if (txt) return txt;
    const img = el.querySelector('img[alt]');
    if (img && (img.getAttribute('alt') || '').trim()) return img.getAttribute('alt').trim();
    const title = (el.getAttribute('title') || '').trim();
    if (title) return title;
    return '';
  }

  function ensureNames(root = document) {
    NAME_MAP.forEach(([sel, name]) => {
      $$(sel, root).forEach(el => {
        if (!accessibleName(el)) el.setAttribute('aria-label', name);
      });
    });
    // Verbleibende Symbol-Schaltflächen aus dem Titel benennen.
    $$('button, a[href]', root).forEach(el => {
      if (accessibleName(el)) return;
      const t = (el.getAttribute('title') || '').trim();
      if (t) { el.setAttribute('aria-label', t); return; }
      const fn = (el.getAttribute('onclick') || '').match(/([A-Za-z_$][\w$]*)\s*\(/);
      if (fn) {
        const guess = {
          closeCart: 'Warenkorb schließen', openCart: 'Warenkorb öffnen',
          closeZoom: 'Ansicht schließen', zoom: 'Bild vergrößern',
          openZoom: 'Bild vergrößern', checkout: 'Zur Kasse',
        }[fn[1]];
        if (guess) el.setAttribute('aria-label', guess);
      }
    });
  }

  /* ---------------------------------------------------------------
    10c · Telefonnummern und E-Mail-Adressen anklickbar machen
    Es wird kein Text ergänzt oder geändert – vorhandene Angaben
    werden lediglich als Verweis nutzbar gemacht.
  ----------------------------------------------------------------*/
  function linkifyContacts(root = document) {
    const scopes = $$('.legal, .recipePage, .protoPage, .thermoPage, footer', root);
    const mailRe = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/;
    const telRe  = /(?:\+49[\s/()-]*|\b0)\d[\d\s/()-]{5,}\d/;

    scopes.forEach(scope => {
      const walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
          if (!node.nodeValue || node.nodeValue.length < 6) return NodeFilter.FILTER_REJECT;
          const p = node.parentElement;
          if (!p) return NodeFilter.FILTER_REJECT;
          if (p.closest('a,script,style,textarea,input,button,.smokyPanel')) return NodeFilter.FILTER_REJECT;
          return (mailRe.test(node.nodeValue) || telRe.test(node.nodeValue))
            ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
        },
      });
      const nodes = [];
      let n; while ((n = walker.nextNode())) nodes.push(n);

      nodes.forEach(node => {
        const text = node.nodeValue;
        const m = mailRe.exec(text) || telRe.exec(text);
        if (!m) return;
        const isMail = m[0].includes('@');
        const digits = m[0].replace(/[^0-9+]/g, '');
        if (!isMail && digits.length < 7) return;
        const a = document.createElement('a');
        a.href = isMail ? 'mailto:' + m[0] : 'tel:' + (digits.startsWith('+') ? digits : '+49' + digits.replace(/^0/, ''));
        a.textContent = m[0];
        a.className = 'rhContactLink';
        const after = node.splitText(m.index);
        after.nodeValue = after.nodeValue.slice(m[0].length);
        node.parentNode.insertBefore(a, after);
      });
    });
  }

  /* ---------------------------------------------------------------
    11 · Warenkorb-Rückmeldung
  ----------------------------------------------------------------*/
  function cartFeedback() {
    const badge = () => Array.from(document.querySelectorAll('[data-cart-count]'))
      .reduce((n, e) => n || parseInt(e.textContent || '0', 10) || 0, 0);
    let last = badge();
    const pulse = () => {
      const n = badge();
      if (n !== last) {
        last = n;
        const btn = $('.cartBtn');
        if (btn && !reduceMotion()) {
          btn.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.09)' }, { transform: 'scale(1)' }],
            { duration: 380, easing: 'cubic-bezier(.22,.61,.36,1)' }
          );
        }
      }
    };
    const src = $('[data-cart-count]');
    if (src && window.MutationObserver) {
      new MutationObserver(pulse).observe(src, { childList: true, subtree: true, characterData: true });
    }
  }

  /* ---------------------------------------------------------------
    12 · Externe Links absichern
  ----------------------------------------------------------------*/
  function safeExternal() {
    $$('a[target="_blank"]').forEach(a => {
      const rel = (a.getAttribute('rel') || '').split(/\s+/).filter(Boolean);
      if (!rel.includes('noopener')) rel.push('noopener');
      if (!rel.includes('noreferrer')) rel.push('noreferrer');
      a.setAttribute('rel', rel.join(' '));
    });
  }

  /* ---------------------------------------------------------------
    13 · Sprungmarke zum Inhalt (Barrierefreiheit)
  ----------------------------------------------------------------*/
  function skipLink() {
    if ($('.rhSkipLink')) return;
    const main = $('main') || $('.content') || $('.legal');
    if (!main) return;
    if (!main.id) main.id = 'inhalt';
    const a = document.createElement('a');
    a.className = 'rhSkipLink';
    a.href = '#' + main.id;
    a.textContent = 'Direkt zum Inhalt';
    a.style.cssText = 'position:absolute;left:-9999px;top:0;z-index:100000;background:#17161a;color:#fff;padding:12px 18px;border-radius:0 0 12px 0;font-size:14px;font-weight:600';
    a.addEventListener('focus', () => { a.style.left = '0'; });
    a.addEventListener('blur', () => { a.style.left = '-9999px'; });
    document.body.insertBefore(a, document.body.firstChild);
    main.setAttribute('tabindex', '-1');
  }

  /* ---------------------------------------------------------------
    14 · Start
  ----------------------------------------------------------------*/
  function boot() {
    seoMeta();
    dedupeLegacyUi();
    skipLink();
    buildFabStack();
    mobileNav();
    imagePerf();
    guardInteractives();
    ensureNames();
    linkifyContacts();
    forms();
    cartFeedback();
    safeExternal();
    catalogState();
    reveal();
    heroParallax();
    requestAnimationFrame(() => document.body.classList.add('rhReady'));

    // Nachgeladene Inhalte (Produktsynchronisierung) ebenfalls einbinden
    window.addEventListener('rh24:catalog-synced', () => {
      dedupeLegacyUi();
      markProductImages();
      imagePerf();
      guardInteractives();
      ensureNames();
      reveal();
    });
    // Sicherheitsnetz für Module, die erst spät rendern
    setTimeout(() => { reveal(); dedupeLegacyUi(); ensureNames(); }, 1400);
    document.documentElement.dataset.rhPremium = VERSION;
  }

  onReady(boot);
  window.RH24Premium = { version: VERSION, reveal, dedupeLegacyUi };
})();
