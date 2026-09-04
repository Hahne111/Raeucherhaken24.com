(() => {
  'use strict';
  if (window.__RH24_CATEGORY_PAGE_V20269__) return;
  window.__RH24_CATEGORY_PAGE_V20269__ = true;

  const body = document.body;
  if (!body || !body.dataset.rhCategory) return;

  const key = body.dataset.rhCategory;
  const grid = document.getElementById('rhCategoryGrid');
  const count = document.getElementById('rhCategoryCount');
  const search = document.getElementById('rhCategorySearch');
  const empty = document.getElementById('rhCategoryEmpty');

  const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[ch]));

  const euro = value => Number(value || 0).toLocaleString('de-DE', {
    style: 'currency',
    currency: 'EUR'
  });

  const normalize = value => String(value ?? '').toLocaleLowerCase('de-DE');
  const haystack = p => normalize(`${p.category || ''} ${p.name || ''}`);

  const filters = {
    hooks: p => /räucherhaken|raeucherhaken/.test(haystack(p)) && !/fleischerhaken/.test(haystack(p)),
    mehl: p => /räuchermehl|raeuchermehl/.test(haystack(p)),
    fleischer: p => /fleischerhaken/.test(haystack(p)),
    fischgewuerze: p => /fischgewürz|fischgewuerz/.test(haystack(p))
  };

  const matches = filters[key] || (() => false);
  let products = [];

  const imageMarkup = p => {
    const src = String(p.image || '').trim();
    if (!src) return '<div class="rhCategoryNoImage">Produktbild folgt</div>';
    return `<img src="${esc(src)}" alt="${esc(p.name)}" loading="lazy" decoding="async">`;
  };

  const card = p => {
    const url = String(p.url || (p.id ? `artikel.php?id=${encodeURIComponent(p.id)}` : '#')).trim();
    const available = Number(p.stock || 0) > 0;
    const description = String(p.short_description || p.description || '').trim();
    const unit = String(p.unit_price?.label || p.unit || '').trim();

    return `<a class="rhCategoryCard" href="${esc(url)}">
      <span class="rhCategoryMedia">${imageMarkup(p)}</span>
      <span class="rhCategoryBody">
        <span class="rhCategoryEyebrow">${esc(p.category || 'Kategorie')}</span>
        <strong>${esc(p.name || 'Produkt')}</strong>
        <small class="rhCategoryArticle">Art.-Nr. ${esc(p.article_no || p.sku || p.id || '')}</small>
        ${description ? `<span class="rhCategoryDesc">${esc(description)}</span>` : ''}
        <span class="rhCategoryMeta">
          <b>${Number(p.price || 0) > 0 ? euro(p.price) : 'Preis folgt'}</b>
          ${unit ? `<small>${esc(unit)}</small>` : ''}
        </span>
        <span class="rhCategoryStock ${available ? 'is-available' : ''}">
          ${available ? 'Sofort verfügbar' : 'Verfügbarkeit prüfen'}
        </span>
        <span class="rhCategoryOpen">Produkt ansehen <i aria-hidden="true">→</i></span>
      </span>
    </a>`;
  };

  const render = list => {
    if (!grid) return;
    if (count) count.textContent = String(list.length);
    grid.innerHTML = list.map(card).join('');
    if (empty) empty.hidden = list.length !== 0;
  };

  const applySearch = () => {
    const q = normalize(search?.value || '').trim();
    if (!q) {
      render(products);
      return;
    }
    render(products.filter(p => normalize([
      p.name, p.article_no, p.sku, p.category, p.short_description, p.description
    ].join(' ')).includes(q)));
  };

  search?.addEventListener('input', applySearch);

  async function load() {
    if (!window.RH24ShopData?.get) {
      if (grid) grid.innerHTML = '<div class="rhCategoryError">Produktdaten konnten nicht geladen werden.</div>';
      return;
    }
    try {
      const data = await window.RH24ShopData.get();
      products = (Array.isArray(data?.products) ? data.products : [])
        .filter(matches)
        .sort((a, b) => String(a.article_no || '').localeCompare(String(b.article_no || ''), 'de', { numeric: true }));
      render(products);
    } catch (e) {
      if (grid) grid.innerHTML = '<div class="rhCategoryError">Produktkatalog vorübergehend nicht verfügbar.</div>';
    }
  }

  load();
})();