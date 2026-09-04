(() => {
  const handlers = window.RH24EventHandlers = window.RH24EventHandlers || {};
  handlers["index-click-001"] = function (event) { addToCart('std') };
  handlers["index-click-002"] = function (event) { addToCart('ultra') };
  handlers["index-click-003"] = function (event) { addToCart('filet') };
  handlers["index-click-004"] = function (event) { addToCart('lauge-forelle-0') };
  handlers["index-click-005"] = function (event) { addToCart('mehl-buche') };
  handlers["index-click-006"] = function (event) { addToCart('kralle') };
  handlers["index-click-007"] = function (event) { addSmokyBundle() };
  handlers["index-click-008"] = function (event) { openCart() };
  handlers["index-click-009"] = function (event) { closeCart() };
  handlers["index-click-010"] = function (event) { closeCart() };
  handlers["index-click-011"] = function (event) { checkout() };
  handlers["index-click-012"] = function (event) { closeZoom() };

  /* V2026.7 · Hauptseite: Mega-Menü stabil und vollständig klickbar.
     Dieser Hotfix läuft ausschließlich auf index.html und greift weder
     in Warenkorb noch Produktdaten oder OrgaBoard-Synchronisierung ein.

     Behobene Fehler:
       · Untermenü schließt/flackert beim Übergang vom Reiter zur Karte.
       · Dynamisch aus dem OrgaBoard erzeugte Karten ohne direkten href
         reagieren nicht auf Klick.
       · Überlagernde Seitenelemente können die Karte nicht mehr abfangen.
  */
  if (window.__RH24_INDEX_MENU_FIX_V20267__) return;
  window.__RH24_INDEX_MENU_FIX_V20267__ = true;

  const isDesktop = () => window.matchMedia('(min-width: 901px)').matches;
  const validHref = href => !!href && href !== '#' && !/^javascript:/i.test(href);
  const closeAll = shell => {
    shell?.querySelectorAll('.rh24NavDrop.open').forEach(drop => {
      drop.classList.remove('open');
      drop.querySelector('.rh24DropBtn')?.setAttribute('aria-expanded', 'false');
    });
  };

  const injectCss = () => {
    if (document.getElementById('rh24-index-menu-fix-v2026-7')) return;
    const style = document.createElement('style');
    style.id = 'rh24-index-menu-fix-v2026-7';
    style.textContent = `
      @media (min-width: 901px) {
        body.rh104Home .rh24NavShell { z-index: 10000 !important; isolation: isolate; }
        body.rh104Home .rh24NavDrop { position: relative !important; }
        body.rh104Home .rh24NavDrop .rh24Mega {
          top: 100% !important;
          margin-top: 0 !important;
          z-index: 10001 !important;
          pointer-events: auto;
          animation: none !important;
        }
        body.rh104Home .rh24NavDrop.open .rh24Mega {
          display: grid !important;
          opacity: 1 !important;
          visibility: visible !important;
          pointer-events: auto !important;
          transform: none !important;
          animation: none !important;
        }
        body.rh104Home .rh24Mega > a[href],
        body.rh104Home .rh24Mega > [data-href],
        body.rh104Home .rh24Mega > [data-url],
        body.rh104Home .rh24Mega > :not(.rh24MegaHead) {
          position: relative;
          z-index: 1;
          pointer-events: auto !important;
          cursor: pointer !important;
        }
      }
    `;
    document.head.appendChild(style);
  };

  const staticFallback = text => {
    const t = String(text || '').toLowerCase();
    if (/standard\s+aal/.test(t)) return 'raeucherhaken-standard-aal.html';
    if (/doppeldorn/.test(t)) return 'raeucherhaken-doppeldorn.html';
    if (/dreifach|3[-\s]?dorn/.test(t)) return '';
    if (/\bfilet\b/.test(t)) return 'raeucherhaken-filet.html';
    if (/\bkralle\b/.test(t)) return 'raeucherhaken-kralle.html';
    if (/\bultra\b/.test(t)) return 'raeucherhaken-ultra.html';
    if (/\bstandard\b/.test(t)) return 'raeucherhaken-standard.html';
    if (/naturgew/.test(t)) return 'naturgewuerze.html';
    if (/räucherlauge|raeucherlauge/.test(t)) return 'raeucherlaugen.html';
    return '';
  };

  async function resolveHref(card) {
    if (!card) return '';
    const ownLink = card.matches?.('a[href]') ? card : card.querySelector?.('a[href]');
    let href = (ownLink?.getAttribute('href') || card.getAttribute?.('data-href') || card.getAttribute?.('data-url') || '').trim();
    if (validHref(href)) return href;

    const text = String(card.textContent || '').replace(/\s+/g, ' ').trim();
    const articleNo = (text.match(/Art\.?[-\s]*Nr\.?\s*[:.]?\s*([A-Z0-9-]+)/i) || [])[1] || '';

    try {
      if (window.RH24ShopData?.get) {
        const data = await window.RH24ShopData.get();
        const products = Array.isArray(data?.products) ? data.products : [];
        let product = null;
        if (articleNo) product = products.find(p => String(p?.article_no || '').trim().toLowerCase() === articleNo.toLowerCase());
        if (!product) {
          const normalized = text.toLowerCase();
          product = products.find(p => {
            const name = String(p?.name || '').trim().toLowerCase();
            return name && (normalized.includes(name) || name.includes(normalized));
          });
        }
        if (product) {
          href = String(product.url || '').trim();
          if (!validHref(href) && product.id) href = 'artikel.php?id=' + encodeURIComponent(String(product.id));
          if (validHref(href)) return href;
        }
      }
    } catch (e) {}

    return staticFallback(text);
  }

  const armMenu = () => {
    const shell = document.querySelector('.rh24NavShell');
    if (!shell || shell.dataset.rhIndexMenuFix === '2026.7') return;
    shell.dataset.rhIndexMenuFix = '2026.7';
    injectCss();

    let shellCloseTimer = 0;
    const cancelShellClose = () => {
      if (shellCloseTimer) {
        clearTimeout(shellCloseTimer);
        shellCloseTimer = 0;
      }
    };

    shell.querySelectorAll('.rh24NavDrop').forEach(drop => {
      /* site-v104.js schließt bisher jede Gruppe auf mouseleave. Auf der
         Hauptseite wird genau dieser Handler vor der Zielphase abgefangen.
         Geschlossen wird erst, wenn die gesamte Navigation verlassen wird. */
      drop.addEventListener('mouseleave', event => {
        if (!isDesktop()) return;
        event.stopImmediatePropagation();
      }, true);

      drop.addEventListener('mouseenter', () => {
        if (!isDesktop()) return;
        cancelShellClose();
        shell.querySelectorAll('.rh24NavDrop').forEach(other => {
          const open = other === drop;
          other.classList.toggle('open', open);
          other.querySelector('.rh24DropBtn')?.setAttribute('aria-expanded', String(open));
        });
      });
    });

    shell.addEventListener('mouseenter', cancelShellClose);
    shell.addEventListener('mouseleave', () => {
      if (!isDesktop()) return;
      cancelShellClose();
      shellCloseTimer = window.setTimeout(() => closeAll(shell), 420);
    });

    document.addEventListener('pointerdown', event => {
      if (!shell.contains(event.target)) closeAll(shell);
    }, true);

    document.addEventListener('click', async event => {
      if (!(event.target instanceof Element)) return;
      const mega = event.target.closest('.rh24Mega');
      if (!mega || !shell.contains(mega)) return;
      if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

      let card = event.target.closest('.rh24Mega a[href], .rh24Mega [data-href], .rh24Mega [data-url]');
      if (!card) {
        let node = event.target;
        while (node && node.parentElement !== mega) node = node.parentElement;
        if (node && node !== mega && !node.classList.contains('rh24MegaHead')) card = node;
      }
      if (!card) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      cancelShellClose();

      const href = await resolveHref(card);
      if (!validHref(href)) return;
      closeAll(shell);
      window.location.assign(new URL(href, document.baseURI).href);
    }, true);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      armMenu();
      setTimeout(armMenu, 250);
      setTimeout(armMenu, 900);
    }, { once: true });
  } else {
    armMenu();
    setTimeout(armMenu, 250);
    setTimeout(armMenu, 900);
  }
})();
