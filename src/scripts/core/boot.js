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

  /* V2026.9 · Jede Hauptkategorie besitzt eine eigene Seite.
     Alte Dropdown-Reiter werden nach dem Aufbau sofort in normale Links
     umgewandelt. Damit ist der Klick auf die Kategorie selbst die einzige
     Aktion; es gibt keine aufklappende Artikelmaske mehr. */
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
    ['sets', 'raeucherhaken.html'],
    ['zubehör', 'shop.html'],
    ['zubehoer', 'shop.html'],
    ['wissen', 'wissen.html'],
    ['über uns', 'ueber-uns.html'],
    ['ueber uns', 'ueber-uns.html'],
    ['service', 'ueber-uns.html']
  ]);

  const navLabel = button => String(button?.textContent || '')
    .replace(/[⌄▾▼]/g, '')
    .replace(/\s+/g, ' ')
    .trim();

  const navKey = label => label.toLocaleLowerCase('de-DE');

  function directifyMainNav() {
    const shell = document.querySelector('.rh24NavShell');
    if (!shell) return false;

    const drops = shell.querySelectorAll('.rh24NavLinks .rh24NavDrop');
    if (!drops.length) return false;

    drops.forEach(drop => {
      if (drop.classList.contains('rh24AccountDrop') || drop.dataset.rh24Direct === '2026.9') return;
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
      drop.dataset.rh24Direct = '2026.9';
      button.replaceWith(link);
    });

    return true;
  }

  const directObserver = new MutationObserver(() => {
    if (directifyMainNav()) {
      const unresolved = document.querySelector('.rh24NavLinks .rh24NavDrop:not([data-rh24-direct="2026.9"])');
      if (!unresolved) directObserver.disconnect();
    }
  });
  directObserver.observe(document.documentElement, { childList: true, subtree: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', directifyMainNav, { once: true });
  } else {
    directifyMainNav();
  }
  window.setTimeout(directifyMainNav, 80);
  window.setTimeout(directifyMainNav, 250);
  window.setTimeout(directifyMainNav, 900);

  window.setTimeout(() => root.classList.remove('rh82Boot'), 2500);
})();