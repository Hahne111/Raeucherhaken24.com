/* =====================================================================
   RÄUCHERHAKEN24 · ATELIER RUNTIME 2026.3   (rh-atelier-2026.js)
   ---------------------------------------------------------------------
   Darstellung, Bewegung und Bedienbarkeit des öffentlichen Shops.

   Diese Datei ersetzt KEINE Geschäftslogik. Warenkorb, Preise, Produkt-
   daten, Suche und Checkout bleiben vollständig in den vorhandenen
   Modulen (app-v12.js, modules-v12.js, product-sync-v1061.js …).

   Grundsätze
     · Bewegung ausschliesslich über transform und opacity
     · ein einziger IntersectionObserver für alle Reveals
     · Scroll- und Pointer-Handler laufen über requestAnimationFrame
     · prefers-reduced-motion schaltet Bewegung ab, niemals Funktion
     · es werden keine Produktbilddateien verändert, ersetzt oder
       neu erzeugt – ausschliesslich deren Darstellung
     · es werden keine Zahlen, Bewertungen oder Kennzahlen erfunden
   ===================================================================== */
(() => {
  'use strict';
  if (window.__RH24_ATELIER_2026__) return;
  window.__RH24_ATELIER_2026__ = true;

  const VERSION = '2026.3';

  /* Die Gestaltungsschicht wird sofort scharf geschaltet. Das passiert
     zusätzlich als Inline-Schnipsel im <head>, damit es kein Aufblitzen
     der alten Gestaltung gibt. Hier steht es als Sicherheitsnetz. */
  document.documentElement.classList.add('rh-atelier');

  /* Null-sicher: Auf Seiten ohne Kopfzeile (z. B. der bewusst reduzierten
     Kasse) darf ein fehlender Wurzelknoten keinen TypeError auslösen. */
  const $  = (s, r = document) => (r ? r.querySelector(s) : null);
  const $$ = (s, r = document) => (r ? Array.from(r.querySelectorAll(s)) : []);
  const mqReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  const reduced  = () => mqReduce.matches;
  const onReady  = fn => {
    if (document.readyState !== 'loading') { setTimeout(fn, 0); return; }
    document.addEventListener('DOMContentLoaded', fn, { once: true });
  };

  /* ===================================================================
     1 · ZENTRALER SCROLL-TAKT
     Alle scrollabhängigen Effekte hängen an EINEM passiven Listener,
     der pro Frame höchstens einmal arbeitet. Damit entstehen keine
     konkurrierenden Handler und keine Layout-Reflows.
  =================================================================== */
  const scrollJobs = new Set();
  let scrollTicking = false;
  function runScrollJobs() {
    scrollTicking = false;
    const y = window.scrollY || window.pageYOffset || 0;
    scrollJobs.forEach(fn => { try { fn(y); } catch (e) { /* ein Fehler darf die anderen nicht stoppen */ } });
  }
  function requestScroll() {
    if (scrollTicking) return;
    scrollTicking = true;
    requestAnimationFrame(runScrollJobs);
  }
  window.addEventListener('scroll', requestScroll, { passive: true });
  window.addEventListener('resize', requestScroll, { passive: true });

  /* ===================================================================
     2 · KOPFZEILE
     Beim Scrollen fährt hinter der Navigation weich eine transluzente
     Fläche ein. Der Zustandswechsel hat eine Hysterese, damit die
     Leiste an der Umschaltgrenze nicht flackert.
  =================================================================== */
  function header() {
    const shell = $('.rh24NavShell');
    if (!shell) return;
    let on = false;
    scrollJobs.add(y => {
      const next = on ? y > 6 : y > 18;
      if (next === on) return;
      on = next;
      shell.classList.toggle('isScrolled', on);
    });
    requestScroll();
  }

  /* ===================================================================
     3 · SUCHE
     Das Suchfeld ist vorhanden und voll funktionsfähig; es wird nur
     platzsparend eingeklappt und über einen Knopf geöffnet – wie in
     der Gestaltungsvorlage. Ohne JavaScript bleibt es sichtbar.
  =================================================================== */
  function searchToggle() {
    const shell = $('.rh24NavShell');
    if (!shell) return;
    const tools = $('.rh24NavTools', shell);
    const dock  = $('.rh24SearchDock', shell);
    const input = $('#rh104SearchInput', shell);
    if (!tools || !dock || $('#rhSearchToggle')) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'rhSearchToggle';
    btn.className = 'rh-searchToggle rhIconBtn';
    btn.setAttribute('aria-label', 'Produktsuche öffnen');
    btn.setAttribute('aria-expanded', 'false');
    btn.setAttribute('aria-controls', 'rh104Search');
    /* Liniensymbol wie Konto und Warenkorb – erste Position, wie in
       der Gestaltungsvorlage: Suche · Konto · Warenkorb. */
    btn.innerHTML = '<span class="ico" aria-hidden="true">' +
      '<svg viewBox="0 0 24 24" focusable="false"><circle cx="11" cy="11" r="6.2"></circle><path d="M15.6 15.6 20 20"></path></svg></span>';
    tools.insertBefore(btn, tools.firstChild);

    const set = open => {
      shell.classList.toggle('searchOpen', open);
      btn.setAttribute('aria-expanded', String(open));
      btn.setAttribute('aria-label', open ? 'Produktsuche schließen' : 'Produktsuche öffnen');
      if (open && input) setTimeout(() => input.focus(), 180);
    };
    btn.addEventListener('click', () => set(!shell.classList.contains('searchOpen')));
    input?.addEventListener('keydown', e => {
      if (e.key === 'Escape') { set(false); btn.focus(); }
    });

    // Auf Ergebnis-/Shopseiten direkt geöffnet lassen, wenn schon getippt wurde.
    if (input && input.value.trim()) set(true);
  }

  /* ===================================================================
     4 · MOBILE NAVIGATION
     Die Schublade bekommt einen abdunkelnden Hintergrund, sperrt das
     Scrollen dahinter und hält den Tastaturfokus fest.
  =================================================================== */
  function mobileNav() {
    const shell = $('.rh24NavShell');
    if (!shell) return;
    const btn   = $('#rh24MobileMenu', shell);
    if (!btn || $('.rh-navScrim')) return;

    const scrim = document.createElement('div');
    scrim.className = 'rh-navScrim';
    scrim.setAttribute('aria-hidden', 'true');
    shell.insertAdjacentElement('afterend', scrim);

    let lastFocus = null;
    const sync = () => {
      const open = shell.classList.contains('mobileOpen');
      scrim.classList.toggle('is-on', open);
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) { lastFocus = document.activeElement; $('.rh24NavLinks a, .rh24NavLinks button', shell)?.focus(); }
      else if (lastFocus) { try { lastFocus.focus(); } catch (e) {} lastFocus = null; }
    };
    const close = () => {
      if (!shell.classList.contains('mobileOpen')) return;
      shell.classList.remove('mobileOpen');
      btn.setAttribute('aria-expanded', 'false');
      btn.textContent = '☰';
      sync();
    };

    // Das Umschalten selbst macht weiterhin site-v104.js. Hier wird nur beobachtet.
    new MutationObserver(sync).observe(shell, { attributes: true, attributeFilter: ['class'] });
    scrim.addEventListener('click', close);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    $$('.rh24NavLinks a', shell).forEach(a => a.addEventListener('click', close));
    window.addEventListener('resize', () => { if (window.innerWidth > 900) close(); }, { passive: true });
  }

  /* ===================================================================
     5 · SCROLL-REVEAL
     Ein Observer für die ganze Seite. Elemente werden nach dem
     Einblenden abgemeldet und ihre GPU-Ebene wieder freigegeben.
     Der Stagger richtet sich nach der Position innerhalb der Gruppe,
     nicht nach der Reihenfolge im Dokument – dadurch wirkt er ruhig.
  =================================================================== */
  const REVEAL_GROUPS = [
    '.rh104CategoryGrid > *', '.rh66CategoryGrid > *',
    '.rh104Products > *', '.dbProductGrid > *', '.rh66ProductGrid > *',
    '.rh104Knowledge > *', '.rh104TrustCards > *', '.rh104BenefitsGrid > *',
    '.moduleCards > *', '.featureGrid > *', '.rh1072FooterGrid > *',
    /* V2026.4 · neue Startseiten-Bereiche */
    '.rhCatRow > *', '.rhServiceRow > *', '.rhEdiGrid > *',
    '.rhGuideSide > *', '.rhFootGrid > *'
  ];
  const REVEAL_SINGLES = [
    '.rh104SectionHead', '.sectionHead', '.rh104TrustTitle',
    '.rh104Smoky', '.moduleBand', '.featurePanel', '.priceExplain',
    '.productPage', '.woodProductHero', '.rhReviews', '.knowledgeCard',
    '.rhGuideMain', '.rhBrandRowInner'
  ];

  let revealIO = null;
  function reveal(root = document) {
    if (reduced()) return;                       // Bei reduzierter Bewegung: alles sofort sichtbar
    if (!('IntersectionObserver' in window)) return;

    if (!revealIO) {
      revealIO = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const el = entry.target;
          el.classList.add('is-in');
          revealIO.unobserve(el);
          const done = () => el.classList.add('is-settled');
          el.addEventListener('transitionend', done, { once: true });
          setTimeout(done, 1400);                // Sicherheitsnetz
        });
      }, { threshold: 0.08, rootMargin: '0px 0px -8% 0px' });
    }

    const seen = new Set();
    const arm = (el, delay) => {
      if (!el || seen.has(el) || el.classList.contains('rh-reveal')) return;
      seen.add(el);
      el.classList.add('rh-reveal');
      if (delay) el.style.setProperty('--rh-reveal-delay', delay + 'ms');
      revealIO.observe(el);
    };

    REVEAL_GROUPS.forEach(sel => {
      const items = $$(sel, root);
      // Gruppen werden je Elternteil gestaffelt, maximal 8 Stufen.
      const byParent = new Map();
      items.forEach(el => {
        const p = el.parentElement;
        if (!byParent.has(p)) byParent.set(p, []);
        byParent.get(p).push(el);
      });
      byParent.forEach(list => list.forEach((el, i) => arm(el, Math.min(i, 7) * 65)));
    });
    REVEAL_SINGLES.forEach(sel => $$(sel, root).forEach(el => arm(el, 0)));
  }

  /* ===================================================================
     6 · HERO · V2026.4
     Ladesequenz nach Vorgabe:
       Eyebrow → Headline (zweite Zeile verzögert) → Text → Buttons →
       Benefits, Bild blendet mit scale 1,03 → 1 ein, Qualitätskarte
       schwebt zuletzt herein. Die Zustände liegen im CSS; hier wird
       nur die Startklasse gesetzt – nach dem ersten Frame, damit die
       Übergänge sicher greifen.
     Danach: extrem subtiler Parallax (±10 px, nur transform).
  =================================================================== */
  function hero() {
    /* Die Ladesequenz ist reines CSS (Klasse rh-atelier-js aus dem
       <head>). Hier läuft nur der Parallax – und erst NACH der
       Einblendanimation, damit sich beide Bewegungen nicht mischen. */
    const media = $('[data-rh-hero-media]');
    const img = media && media.querySelector('img');
    if (!media || !img || reduced()) return;
    const MAX = 10;                       /* „Bewegung maximal wenige Pixel" */
    let last = null;
    const start = () => {
      scrollJobs.add(y => {
        const h = media.offsetHeight || 1;
        const top = media.getBoundingClientRect().top + y;
        const progress = Math.max(-1, Math.min(1, (y - top + window.innerHeight * .5) / (h + window.innerHeight)));
        const shift = Math.round(progress * MAX * 10) / 10;
        if (shift === last) return;
        last = shift;
        img.style.transform = `translate3d(0, ${shift}px, 0)`;
      });
      requestScroll();
    };
    setTimeout(start, 1600);              /* nach rh-hero-zoom (1300 ms + Verzögerung) */
  }

  /* ===================================================================
     6b · ECHTE „ab“-PREISE der Kategorie-Reihe
     Die statischen Werte stammen aus den bestehenden Produktseiten.
     Sobald der Katalog geladen ist, werden sie durch die tagesaktuellen
     Minima aus der Datenbank ersetzt – ohne erfundene Zahlen: findet
     eine Gruppe keine Produkte mit Preis, bleibt der Text unverändert.
  =================================================================== */
  const ABPRICE_GROUPS = {
    hooks:   p => (p.type === 'hook' || /räucherhaken|raeucherhaken/i.test(p.category || '')) && !/fleisch/i.test(p.name || ''),
    fleisch: p => /fleischerhaken/i.test(p.name || '') || /fleischerhaken/i.test(p.category || ''),
    mehl:    p => /räuchermehl|raeuchermehl/i.test((p.category || '') + ' ' + (p.name || '')),
    lauge:   p => /lauge/i.test((p.category || '') + ' ' + (p.name || '')),
    gewuerz: p => /naturgew|gewürz|gewuerz/i.test((p.category || '') + ' ' + (p.name || ''))
  };
  const euroAb = v => 'ab ' + Number(v).toLocaleString('de-DE', { style: 'currency', currency: 'EUR' });
  async function abPrices() {
    const slots = $$('[data-rh-abprice]');
    if (!slots.length || !window.RH24ShopData) return;
    try {
      const d = await window.RH24ShopData.get();
      const products = (d && d.products) || [];
      slots.forEach(slot => {
        const match = ABPRICE_GROUPS[slot.dataset.rhAbprice];
        if (!match) return;
        const prices = products.filter(p => match(p) && Number(p.price) > 0).map(p => Number(p.price));
        if (!prices.length) return;       /* ehrlich: nichts erfinden */
        slot.textContent = euroAb(Math.min.apply(null, prices));
      });
    } catch (e) { /* Katalog nicht erreichbar → statische echte Werte bleiben */ }
  }

  /* ===================================================================
     7 · VERTRAUENSPLAKETTE IM HERO — BEWUSST NICHT UMGESETZT

     Die Gestaltungsvorlage zeigt über dem Hero-Bild eine Plakette mit
     Sternebewertung und Kundenzahl ("4,9 / 5 · über 10.000 zufriedene
     Kunden"). Beides lässt sich in diesem Projekt nicht belegen:

       · reviews-v24.js arbeitet mit Beispielbewertungen, die im Text
         selbst als "Demo-Bewertung, noch keine echte Kundenrezension"
         gekennzeichnet sind.
       · Eine echte Bewertungssumme oder Kundenzahl liefert weder die
         Datenbank noch eine Schnittstelle.

     Eine Plakette mit erfundenen Zahlen wäre eine falsche Aussage
     gegenüber Kundinnen und Kunden – und widerspricht der Vorgabe
     "KEINE erfundenen Zahlen". Die Vertrauensaussagen stehen statt-
     dessen als echte, belegte Angaben in der Zeile unter dem Hero und
     in der Vertrauensleiste über der Navigation.

     Sobald echte Bewertungsdaten vorliegen, genügt es, sie unter
     window.RH24_TRUST bereitzustellen; die Plakette lässt sich dann
     mit wenigen Zeilen ergänzen.
  =================================================================== */
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g,
      c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  /* ===================================================================
     8 · WARENKORB-ANZEIGE

     FEHLERBEHEBUNG (bestand bereits vor diesem Update):
     app-v12.js schreibt die Artikelanzahl beim Speichern in alle
     Elemente mit [data-cart-count]. Das einzige solche Element sass im
     alten schwebenden Warenkorb-Knopf (.cartBtn). Dieser Knopf wird
     seit V2026.1 von rh-premium-2026.js entfernt, weil er sich mit der
     neuen Aktionsleiste doppelte. Damit gab es kein Ziel mehr:

        → Die Zahl neben dem Warenkorb in der Kopfzeile blieb dauerhaft
          auf 0 stehen, egal wie viel im Warenkorb lag.

     Hier wird ein unsichtbarer Zähler wiederhergestellt. Die
     Warenkorblogik selbst bleibt vollständig unangetastet – sie
     schreibt weiterhin genau dorthin, wo sie es immer getan hat.
     Zusätzlich wird der Stand aus dem Speicher gelesen, sodass die
     Anzeige auch beim Seitenwechsel und in weiteren Tabs stimmt.
  =================================================================== */
  const CART_KEY = 'rh24cart';

  function cartCountFromStorage() {
    try {
      const raw = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
      if (!Array.isArray(raw)) return 0;
      return raw.reduce((s, x) => s + (Number(x && x.qty) || 0), 0);
    } catch (e) { return 0; }
  }

  function cart() {
    // 1 · Schreibziel für die bestehende Logik sicherstellen
    let sink = $('[data-cart-count]');
    if (!sink) {
      sink = document.createElement('span');
      sink.setAttribute('data-cart-count', '');
      sink.className = 'rh-sr-only';
      sink.dataset.rhCartSink = '1';
      sink.textContent = String(cartCountFromStorage());
      document.body.appendChild(sink);
    }

    const badges = () => $$('.rh24NavCartCount');
    let prev = null;

    const paint = value => {
      const text = String(value);
      badges().forEach(b => {
        if (b.textContent === text) return;
        b.textContent = text;
        if (reduced()) return;
        b.classList.remove('is-bumped');
        void b.offsetWidth;                      // Übergang neu starten
        b.classList.add('is-bumped');
        setTimeout(() => b.classList.remove('is-bumped'), 320);
      });
      // Barrierefreie Ansage für Screenreader
      const nav = badges()[0];
      if (nav) {
        const btn = nav.closest('button, a');
        if (btn) btn.setAttribute('aria-label',
          'Warenkorb öffnen, ' + text + (text === '1' ? ' Artikel' : ' Artikel'));
      }
    };

    const sync = () => {
      const fromSink = Number((sink.textContent || '').trim());
      const value = Number.isFinite(fromSink) ? fromSink : cartCountFromStorage();
      if (value === prev) return;
      prev = value;
      paint(value);

      /* Liegt die Schublade gerade offen und wird ein weiterer Artikel
         hinzugefügt, zeigte sie bisher den alten Stand: addToCart()
         speichert zwar, zeichnet die Liste aber nicht neu. Sie wird
         hier aktualisiert – mit der vorhandenen Funktion, ohne eigene
         Warenkorblogik. */
      const drawer = $('#cartDrawer');
      if (drawer && drawer.classList.contains('open') && typeof window.renderCart === 'function') {
        try { window.renderCart(); } catch (e) {}
      }
    };

    sync();
    if (window.MutationObserver) {
      new MutationObserver(sync).observe(sink, { childList: true, characterData: true, subtree: true });
    }
    // Warenkorb in einem anderen Tab geändert
    window.addEventListener('storage', e => {
      if (e.key !== CART_KEY) return;
      const n = cartCountFromStorage();
      sink.textContent = String(n);
      sync();
    });
    window.addEventListener('pageshow', () => { sink.textContent = String(cartCountFromStorage()); sync(); });
  }

  /* ===================================================================
     9 · PRODUKTBILDER · NUR DARSTELLUNG
     Sicherheitsnetz gegen Überdeckungen: Bilder ohne feste Rahmengrösse
     bekommen `contain` und dürfen niemals über ihren Container hinaus.
     Es wird nichts an den Dateien verändert – kein Filter, keine Farbe,
     kein Beschnitt, kein Format, keine Neuerzeugung.
  =================================================================== */
  const MEDIA_SEL = '.rh104ProductImg,.dbProductImage,.rh66ProductCard .imgBox,' +
                    '.woodShopCard > a:first-child,.natureImage,.laugeVisual,' +
                    '.thermoImage,.productImage,.woodProductImage,.rh104CategoryMedia';
  function fitMedia(root = document) {
    const boxes = root.querySelectorAll ? root.querySelectorAll(MEDIA_SEL) : [];
    boxes.forEach(box => {
      if (box.dataset.rhFit === '1') return;
      box.dataset.rhFit = '1';
      const img = box.querySelector('img');
      if (!img) return;
      img.style.maxWidth = '100%';
      img.style.maxHeight = '100%';
      // Redaktionelle Fotos dürfen die Fläche füllen, Produktfreisteller nicht.
      if (!box.classList.contains('rh104HeroVisual')) img.style.objectFit = 'contain';
      img.style.objectPosition = 'center';
      if (!img.getAttribute('alt')) {
        const t = box.closest('article,.card,a')?.querySelector('h2,h3,b')?.textContent;
        if (t) img.setAttribute('alt', t.trim());
      }
    });
  }

  /* ===================================================================
     10 · BARRIEREFREIHEIT
  =================================================================== */
  function a11y() {
    // Sprungmarke zum Inhalt.
    // rh-premium-2026.js bringt bereits eine mit (.rhSkipLink). Zwei
    // Sprungmarken hintereinander wären für Tastatur- und Screenreader-
    // Nutzung eher hinderlich – deshalb nur eine.
    if (!$('.rh-skipLink') && !$('.rhSkipLink') && !$('[data-rh-skip]')) {
      const main = $('main') || $('.content') || $('.rh104Hero');
      if (main) {
        if (!main.id) main.id = 'rhMainContent';
        const skip = document.createElement('a');
        skip.className = 'rh-skipLink';
        skip.href = '#' + main.id;
        skip.textContent = 'Direkt zum Inhalt';
        document.body.insertBefore(skip, document.body.firstChild);
      }
    }
    // Symbol-Schaltflächen ohne Beschriftung nachrüsten
    $$('button, a').forEach(el => {
      if (el.getAttribute('aria-label') || el.getAttribute('title')) return;
      const txt = (el.textContent || '').replace(/\s+/g, '').trim();
      if (txt.length && txt.length > 2) return;
      const map = { '⌕': 'Suchen', '×': 'Schließen', '🛒': 'Warenkorb öffnen',
                    '→': 'Weiter', '☰': 'Menü öffnen', '⌄': 'Untermenü' };
      if (map[txt]) el.setAttribute('aria-label', map[txt]);
    });
    // Externe Ziele absichern
    $$('a[target="_blank"]').forEach(a => { if (!a.rel) a.rel = 'noopener noreferrer'; });
  }

  /* ===================================================================
     11 · NEUE INHALTE BEOBACHTEN
     product-sync-v1061.js hängt Produktkarten nach. Diese bekommen
     dieselbe Darstellung und dieselben Reveals.
  =================================================================== */
  function watch() {
    if (!window.MutationObserver) return;
    let queued = false;
    const mo = new MutationObserver(muts => {
      const added = muts.some(m => m.addedNodes.length);
      if (!added || queued) return;
      queued = true;
      requestAnimationFrame(() => {
        queued = false;
        fitMedia(document);
        reveal(document);
      });
    });
    mo.observe(document.body, { childList: true, subtree: true });

    // Nach jedem Katalogabgleich einmal sauber nachziehen.
    window.addEventListener('rh24:catalog-synced', () => {
      fitMedia(document);
      reveal(document);
      abPrices();                      /* „ab“-Preise folgen der Datenbank */
    });
  }

  /* ===================================================================
     START
  =================================================================== */
  function boot() {
    try { header(); }       catch (e) { console.warn('[RH24 Atelier] Kopfzeile:', e); }
    try { searchToggle(); } catch (e) { console.warn('[RH24 Atelier] Suche:', e); }
    try { mobileNav(); }    catch (e) { console.warn('[RH24 Atelier] Mobile Navigation:', e); }
    try { hero(); }         catch (e) { console.warn('[RH24 Atelier] Hero:', e); }
    try { abPrices(); }     catch (e) { console.warn('[RH24 Atelier] ab-Preise:', e); }
    try { fitMedia(); }     catch (e) { console.warn('[RH24 Atelier] Bilddarstellung:', e); }
    try { reveal(); }       catch (e) { console.warn('[RH24 Atelier] Reveal:', e); }
    try { cart(); }         catch (e) { console.warn('[RH24 Atelier] Warenkorb:', e); }
    try { a11y(); }         catch (e) { console.warn('[RH24 Atelier] Barrierefreiheit:', e); }
    try { watch(); }        catch (e) { console.warn('[RH24 Atelier] Beobachter:', e); }
    document.documentElement.dataset.rhAtelier = VERSION;
  }

  // Ändert die Person ihre Bewegungseinstellung, wird sofort reagiert.
  const onMotionChange = () => {
    if (reduced()) {
      $$('.rh-reveal').forEach(el => el.classList.add('is-in', 'is-settled'));
      const img = $('.rh104HeroVisual img');
      if (img) img.style.transform = '';
    } else {
      reveal(document);
    }
  };
  if (mqReduce.addEventListener) mqReduce.addEventListener('change', onMotionChange);
  else if (mqReduce.addListener) mqReduce.addListener(onMotionChange);

  // site-v104.js baut Kopfzeile und Navigation erst bei DOMContentLoaded.
  // Deshalb wird hier bewusst danach gestartet.
  onReady(() => setTimeout(boot, 0));
})();
