(() => {
  const root = document.documentElement;
  root.classList.add('rh82Boot', 'rh-atelier', 'rh-atelier-js');

  /* V2026.5 · Menü-Stabilitätsfix */
  if (!document.getElementById('rh24-nav-stability-v2026-5')) {
    const style = document.createElement('style');
    style.id = 'rh24-nav-stability-v2026-5';
    style.textContent = `
      @media (min-width: 901px) {
        html.rh-atelier .rh24NavDrop { position: relative !important; }
        html.rh-atelier .rh24Mega {
          top: 100% !important;
          margin-top: 0 !important;
          display: grid !important;
          animation: none !important;
        }
        html.rh-atelier .rh24NavDrop:not(.open) .rh24Mega {
          opacity: 0 !important;
          visibility: hidden !important;
          pointer-events: none !important;
          animation: none !important;
        }
        html.rh-atelier .rh24NavDrop.open .rh24Mega {
          opacity: 1 !important;
          visibility: visible !important;
          pointer-events: auto !important;
          animation: none !important;
        }
      }
      html.rh-atelier .rh24Mega a[href],
      html.rh-atelier .rh24Mega [data-href],
      html.rh-atelier .rh24Mega [data-url] {
        position: relative;
        z-index: 1;
        pointer-events: auto !important;
        cursor: pointer !important;
      }
      .sidebar .sideMain[data-rh24-category-href] {
        cursor: pointer !important;
      }
      .sidebar .sideMain[data-rh24-category-href]:focus-visible {
        outline: 3px solid rgba(215,111,43,.35);
        outline-offset: -3px;
      }
    `;
    document.head.appendChild(style);
  }

  /* V2026.6 · Funktionsfix für Karteikarten im Mega-Menü.
     Bleibt nur als Rückwärtskompatibilität für Konto/alte Seiten bestehen. */
  const megaCardSelector = '.rh24Mega a[href], .rh24Mega [data-href], .rh24Mega [data-url]';
  const hrefOf = card => {
    if (!card) return '';
    if (card.matches('a[href]')) return (card.getAttribute('href') || '').trim();
    return (card.getAttribute('data-href') || card.getAttribute('data-url') || '').trim();
  };
  const usableHref = href => href && href !== '#' && !/^javascript:/i.test(href);

  document.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    const card = event.target.closest(megaCardSelector);
    if (!card || !card.closest('.rh24Mega')) return;
    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    if (card.matches('a[target]') && (card.getAttribute('target') || '').toLowerCase() !== '_self') return;

    let href = hrefOf(card);
    if (!usableHref(href)) {
      const holder = card.closest('.rh24Mega [data-href], .rh24Mega [data-url]');
      if (holder && holder !== card) href = hrefOf(holder);
    }
    if (!usableHref(href)) return;

    let target;
    try { target = new URL(href, document.baseURI); } catch (e) { return; }
    if (!['http:', 'https:', 'file:'].includes(target.protocol)) return;

    event.preventDefault();
    event.stopImmediatePropagation();

    const shell = card.closest('.rh24NavShell');
    const drop = card.closest('.rh24NavDrop');
    if (drop) {
      drop.classList.remove('open');
      const button = drop.querySelector('.rh24DropBtn');
      if (button) button.setAttribute('aria-expanded', 'false');
    }
    if (shell) {
      shell.classList.remove('mobileOpen');
      const mobileButton = shell.querySelector('#rh24MobileMenu');
      if (mobileButton) {
        mobileButton.setAttribute('aria-expanded', 'false');
        mobileButton.textContent = '☰';
      }
    }

    window.location.assign(target.href);
  }, true);

  /* V2026.11 · Kategorie = vollständige Kategorieseite.
     Entscheidend: Der Klick wird jetzt DIREKT in der Capture-Phase auf
     die Kategorieseite geleitet. Damit hängt die Funktion nicht mehr davon
     ab, ob ein später geladenes Script den Menüpunkt vorher in einen Link
     umwandeln konnte. Das gilt auch für die Kategoriekarten der Startseite. */
  const navRoutes = new Map([
    ['räucherhaken', 'raeucherhaken.html'],
    ['raeucherhaken', 'raeucherhaken.html'],
    ['räucherlauge', 'raeucherlaugen.html'],
    ['raeucherlauge', 'raeucherlaugen.html'],
    ['räucherlaugen', 'raeucherlaugen.html'],
    ['raeucherlaugen', 'raeucherlaugen.html'],
    ['räuchermehl', 'raeuchermehl.html'],
    ['raeuchermehl', 'raeuchermehl.html'],
    ['räucherholz', 'raeuchermehl.html'],
    ['raeucherholz', 'raeuchermehl.html'],
    ['fleischerhaken', 'fleischerhaken.html'],
    ['fischgewürze', 'fischgewuerze.html'],
    ['fischgewuerze', 'fischgewuerze.html'],
    ['naturgewürze', 'naturgewuerze.html'],
    ['naturgewuerze', 'naturgewuerze.html'],
    ['thermometer', 'thermometer.html'],
    ['sets', 'raeucherhaken.html'],
    ['zubehör', 'shop.html'],
    ['zubehoer', 'shop.html'],
    ['wissen', 'wissen.html'],
    ['wissen & rezepte', 'wissen.html'],
    ['räucherwissen', 'wissen.html'],
    ['raeucherwissen', 'wissen.html'],
    ['über uns', 'ueber-uns.html'],
    ['ueber uns', 'ueber-uns.html'],
    ['service', 'ueber-uns.html']
  ]);

  const navLabel = node => String(node?.textContent || '')
    .replace(/[⌄▾▼]/g, '')
    .replace(/\s+/g, ' ')
    .trim();

  const navKey = label => label.toLocaleLowerCase('de-DE');
  const routeFor = node => navRoutes.get(navKey(navLabel(node))) || '';

  const categoryTargetFor = element => {
    if (!element) return '';
    if (element.matches('a.rhCatCard')) return routeFor(element.querySelector('.rhCatName') || element);
    if (element.matches('a.rh66CategoryCard')) return routeFor(element.querySelector('b') || element);
    return routeFor(element);
  };

  /* Primärer Funktionsweg: sofortiger Kategorie-Klick.
     Erfasst die obere Navigation sowie die Kategorie-Karten auf Startseite
     und Shopseite. Konto bleibt ausdrücklich ein Dropdown. */
  document.addEventListener('click', event => {
    if (!(event.target instanceof Element)) return;
    const entrance = event.target.closest(
      '.rh24NavLinks .rh24DropBtn, .rh24NavLinks .rh24DirectTab, a.rhCatCard, a.rh66CategoryCard'
    );
    if (!entrance || entrance.closest('.rh24AccountDrop')) return;
    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

    const href = categoryTargetFor(entrance);
    if (!href) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    window.location.assign(new URL(href, document.baseURI).href);
  }, true);

  function directifyMainNav() {
    const shell = document.querySelector('.rh24NavShell');
    if (!shell) return false;

    const drops = shell.querySelectorAll('.rh24NavLinks .rh24NavDrop');
    if (!drops.length) return false;

    drops.forEach(drop => {
      if (drop.classList.contains('rh24AccountDrop') || drop.dataset.rh24Direct === '2026.11') return;
      const button = drop.querySelector(':scope > .rh24DropBtn');
      if (!button) return;

      const label = navLabel(button);
      const href = navRoutes.get(navKey(label));
      if (!href) return;

      const link = document.createElement('a');
      link.className = button.className + ' rh24DirectTab';
      link.href = href;
      link.textContent = label;
      if (button.getAttribute('aria-label')) link.setAttribute('aria-label', button.getAttribute('aria-label'));

      drop.querySelector(':scope > .rh24Mega')?.remove();
      drop.classList.remove('open');
      drop.classList.add('rh24DirectDrop');
      drop.removeAttribute('data-rh24-drop');
      drop.dataset.rh24Direct = '2026.11';
      button.replaceWith(link);
    });

    return true;
  }

  function directifyCategoryEntrances() {
    let changed = false;

    /* Jetzt ausdrücklich BEIDE Kategoriekarten-Systeme:
       .rhCatCard = Startseite, .rh66CategoryCard = Shopseite. */
    document.querySelectorAll('a.rh66CategoryCard, a.rhCatCard').forEach(card => {
      const href = categoryTargetFor(card);
      if (!href || card.getAttribute('href') === href) return;
      card.setAttribute('href', href);
      card.dataset.rh24CategoryDirect = '2026.11';
      changed = true;
    });

    /* Alte Seitenleisten behalten ihr Aussehen. Die Kategorieüberschrift
       selbst wird aber zu einem eindeutigen Einstieg "Alle Artikel". */
    document.querySelectorAll('.sidebar .sideMain').forEach(item => {
      const href = routeFor(item);
      if (!href) return;
      if (item.dataset.rh24CategoryHref === href) return;
      item.dataset.rh24CategoryHref = href;
      item.setAttribute('role', 'link');
      item.setAttribute('tabindex', '0');
      item.setAttribute('aria-label', `${navLabel(item)} – alle Artikel öffnen`);
      changed = true;
    });

    return changed;
  }

  const openSidebarCategory = event => {
    if (!(event.target instanceof Element)) return;
    const item = event.target.closest('.sidebar .sideMain[data-rh24-category-href]');
    if (!item) return;
    if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
    if (event.type === 'click' && (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey)) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    const href = item.dataset.rh24CategoryHref;
    if (href) window.location.assign(new URL(href, document.baseURI).href);
  };

  document.addEventListener('click', openSidebarCategory, true);
  document.addEventListener('keydown', openSidebarCategory, true);

  const directObserver = new MutationObserver(() => {
    directifyMainNav();
    directifyCategoryEntrances();
  });
  directObserver.observe(document.documentElement, { childList: true, subtree: true });

  const applyDirectRoutes = () => {
    directifyMainNav();
    directifyCategoryEntrances();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyDirectRoutes, { once: true });
  } else {
    applyDirectRoutes();
  }
  window.setTimeout(applyDirectRoutes, 80);
  window.setTimeout(applyDirectRoutes, 250);
  window.setTimeout(applyDirectRoutes, 900);
  window.setTimeout(() => directObserver.disconnect(), 3500);

  window.setTimeout(() => root.classList.remove('rh82Boot'), 2500);
})();