/* Räucherhaken24 V2026.12 · feste, datenbankgestützte Kategorie-Reihenfolge
   Die Hauptnavigation wird ausschließlich aus shop_categories geladen.
   Produktanzahl, Anlagedatum und Produktbearbeitungen beeinflussen die Reihenfolge nicht. */
(() => {
  'use strict';
  if (window.__RH24_FIXED_CATEGORY_NAV__) return;
  window.__RH24_FIXED_CATEGORY_NAV__ = true;

  const RH24_CATEGORY_FALLBACK = [
    {slug:'raeucherhaken',  name:'Räucherhaken',   url:'raeucherhaken.html',   sort_order:10},
    {slug:'raeucherlauge',  name:'Räucherlauge',   url:'raeucherlaugen.html',  sort_order:20},
    {slug:'raeuchermehl',   name:'Räuchermehl',    url:'raeuchermehl.html',    sort_order:30},
    {slug:'fleischerhaken', name:'Fleischerhaken', url:'fleischerhaken.html',  sort_order:40},
    {slug:'fischgewuerze',  name:'Fischgewürze',   url:'fischgewuerze.html',   sort_order:50},
    {slug:'naturgewuerze',  name:'Naturgewürze',   url:'naturgewuerze.html',   sort_order:60},
    {slug:'wissen',         name:'Wissen',          url:'wissen.html',          sort_order:70},
    {slug:'ueber-uns',      name:'Über uns',        url:'ueber-uns.html',       sort_order:80}
  ];

  let desiredCategories = RH24_CATEGORY_FALLBACK.slice();
  let navigationSource = 'fallback';
  const currentPage = (location.pathname.split('/').pop() || 'index.html').toLowerCase();

  function cleanUrl(value) {
    const url = String(value || '').trim();
    if (!url || /^javascript:/i.test(url)) return '';
    try {
      const parsed = new URL(url, document.baseURI);
      if (parsed.origin !== location.origin) return '';
      return url;
    } catch (e) {
      return '';
    }
  }

  function normalizeCategories(payload) {
    const source = Array.isArray(payload)
      ? payload
      : (Array.isArray(payload?.categories) ? payload.categories : []);
    const seen = new Set();
    return source
      .map((row, index) => ({
        id: Number(row?.id || 0),
        slug: String(row?.slug || '').trim(),
        name: String(row?.name || '').trim(),
        url: cleanUrl(row?.url),
        sort_order: Number.isFinite(Number(row?.sort_order)) ? Number(row.sort_order) : (1000 + index)
      }))
      .filter(row => row.name && row.url && !seen.has(row.slug || row.name.toLocaleLowerCase('de-DE')) && seen.add(row.slug || row.name.toLocaleLowerCase('de-DE')))
      .sort((a, b) => a.sort_order - b.sort_order || a.id - b.id || a.name.localeCompare(b.name, 'de'));
  }

  function signature(rows) {
    return rows.map(row => `${row.slug}|${row.name}|${row.url}|${row.sort_order}`).join('||');
  }

  function buildTab(row) {
    const drop = document.createElement('div');
    drop.className = 'rh24NavDrop rh24DirectDrop rh24FixedCategory';
    drop.dataset.rh24Direct = '2026.11'; // bestehender Boot-Code lässt diesen festen Tab unverändert
    drop.dataset.rh24CategorySlug = row.slug || '';

    const link = document.createElement('a');
    link.className = 'rh24DropBtn rh24DirectTab';
    link.href = row.url;
    link.textContent = row.name;
    link.dataset.rh24SortOrder = String(row.sort_order);

    try {
      const targetPage = (new URL(row.url, document.baseURI).pathname.split('/').pop() || '').toLowerCase();
      if (targetPage && targetPage === currentPage) {
        link.classList.add('active');
        link.setAttribute('aria-current', 'page');
      }
    } catch (e) {}

    drop.appendChild(link);
    return drop;
  }

  function applyNavigation() {
    const host = document.querySelector('#rh24DynamicNav .rh24NavLinks, .rh24NavShell .rh24NavLinks');
    if (!host) return false;

    const rows = normalizeCategories(desiredCategories);
    if (!rows.length) return false;
    const sig = signature(rows);
    if (host.dataset.rh24FixedCategorySignature === sig) return true;

    const fragment = document.createDocumentFragment();
    rows.forEach(row => fragment.appendChild(buildTab(row)));
    host.replaceChildren(fragment);
    host.dataset.rh24FixedCategorySignature = sig;
    host.dataset.rh24FixedCategorySource = navigationSource;
    return true;
  }

  function waitForNavigation(attempt = 0) {
    if (applyNavigation()) return;
    if (attempt >= 100) return;
    window.setTimeout(() => waitForNavigation(attempt + 1), 50);
  }

  async function loadDatabaseOrder() {
    try {
      const response = await fetch('/shop-categories.php?v=2026.12', {
        method: 'GET',
        cache: 'no-store',
        credentials: 'same-origin',
        headers: {'Accept': 'application/json'}
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const payload = await response.json();
      const rows = normalizeCategories(payload);
      if (!rows.length) throw new Error('Keine sichtbaren Kategorien erhalten');
      desiredCategories = rows;
      navigationSource = 'database';
      applyNavigation();
    } catch (error) {
      console.warn('Räucherhaken24: feste Fallback-Navigation aktiv.', error);
    }
  }

  waitForNavigation();
  loadDatabaseOrder();
})();
