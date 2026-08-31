/* =====================================================================
   ORGABOARD · PREMIUM-OBERFLÄCHE 2026.2
   ---------------------------------------------------------------------
   Ergänzt das bestehende Orgaboard, ohne es zu ersetzen:

     · Navigationssuche, zusammenklappbare Gruppen, Favoriten
     · Befehlspalette (Strg/Cmd + K) mit echter Datenbanksuche
     · Chef-Cockpit auf der Startseite (Kennzahlen, Aufmerksamkeits-
       liste, Lager, Finanzen, Systemzustand, Erkenntnisse)
     · Smoky als Geschäftsassistent – ausschliesslich lesend

   Grundsätze:
     · Nichts Bestehendes wird überschrieben. Fällt diese Datei aus,
       läuft das Orgaboard unverändert weiter.
     · Es wird nichts angezeigt, was nicht aus der Datenbank kommt.
       Fehlen Daten, erscheint ein ehrlicher Hinweis statt einer Zahl.
     · Der Assistent liest. Jede Änderung bleibt eine Handlung des
       Benutzers in der zuständigen Maske.
     · Alle Anfragen laufen über cockpit-api.php; dort prüft der Server
       Anmeldung, Rechte und Herkunft.
   ===================================================================== */
(() => {
  'use strict';

  /* ---------------------------------------------------------- Grundlage */
  const UI = () => window.RH24_UI || null;
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  const STORE = 'rh24.ob.ui.v1';
  let prefs = { pinned: [], collapsed: [], recent: [] };
  try {
    const raw = localStorage.getItem(STORE);
    if (raw) Object.assign(prefs, JSON.parse(raw) || {});
  } catch (_e) { /* Speicher gesperrt – dann eben ohne Merkfunktion */ }
  const savePrefs = () => {
    try { localStorage.setItem(STORE, JSON.stringify(prefs)); } catch (_e) {}
  };

  function esc(v) {
    return String(v == null ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  const money = v => (Number(v) || 0).toLocaleString('de-DE', { style: 'currency', currency: 'EUR' });
  const num   = v => (Number(v) || 0).toLocaleString('de-DE');

  function svg(path, size) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
      + 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"'
      + (size ? ' width="' + size + '" height="' + size + '"' : '') + '>' + path + '</svg>';
  }
  const ICON = {
    search:  '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    chevron: '<path d="m6 9 6 6 6-6"/>',
    star:    '<path d="m12 3 2.6 5.6 6 .8-4.4 4.2 1.1 6L12 16.8 6.7 19.6l1.1-6L3.4 9.4l6-.8Z"/>',
    send:    '<path d="M4 12h15M13 6l6 6-6 6"/>',
    spark:   '<path d="M12 3v3M12 18v3M3 12h3M18 12h3M6 6l2 2M16 16l2 2M6 18l2-2M16 8l2-2"/><circle cx="12" cy="12" r="3.2"/>',
  };

  /* ------------------------------------------------------ Serveranfrage */
  let csrf = (window.RH24_ORGABOARD && window.RH24_ORGABOARD.csrf) || '';

  async function ask(payload) {
    const res = await fetch('cockpit-api.php?v=2026.2', {
      method: 'POST',
      cache: 'no-store',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(payload),
    });
    let data = null;
    try { data = await res.json(); } catch (_e) { /* keine JSON-Antwort */ }
    if (!res.ok || !data || data.ok !== true) {
      const msg = (data && data.error) ? data.error : 'Die Daten konnten nicht geladen werden.';
      const err = new Error(msg);
      err.status = res.status;
      throw err;
    }
    if (data.csrf) csrf = data.csrf;
    return data;
  }

  /* =====================================================================
     1 · NAVIGATION: Suche, Gruppen, Favoriten, zuletzt benutzt
     ===================================================================== */
  function navLabel(btn) {
    const b = btn.querySelector('b');
    return (b ? b.textContent : btn.textContent || '').trim();
  }

  function buildNavTools() {
    const nav = $('#sideNav nav');
    if (!nav || $('.obNavTools')) return;

    const tools = document.createElement('div');
    tools.className = 'obNavTools';
    tools.innerHTML =
      '<label class="obNavSearch">' + svg(ICON.search) +
      '<input type="search" id="obNavSearch" placeholder="Bereich suchen …" '
      + 'aria-label="Bereich suchen" autocomplete="off">' +
      '<kbd>⌘K</kbd></label>' +
      '<div class="obNavEmpty" id="obNavEmpty" hidden>Kein Bereich gefunden.</div>';
    nav.insertBefore(tools, nav.firstChild);

    /* Gruppen zusammenklappbar machen. Die Beschriftung wird zur
       Schaltfläche – die vorhandene Struktur bleibt erhalten. */
    $$('.navSection').forEach((sec, i) => {
      const label = sec.querySelector('.navSectionLabel');
      if (!label || label.dataset.obReady) return;
      label.dataset.obReady = '1';
      const key = 'sec' + i;
      label.dataset.obKey = key;
      label.setAttribute('role', 'button');
      label.setAttribute('tabindex', '0');
      label.insertAdjacentHTML('beforeend', '<span class="obNavChevron">' + svg(ICON.chevron) + '</span>');
      if (prefs.collapsed.includes(key)) sec.classList.add('obCollapsed');
      const toggle = () => {
        sec.classList.toggle('obCollapsed');
        const idx = prefs.collapsed.indexOf(key);
        if (sec.classList.contains('obCollapsed')) { if (idx < 0) prefs.collapsed.push(key); }
        else if (idx >= 0) prefs.collapsed.splice(idx, 1);
        savePrefs();
        label.setAttribute('aria-expanded', String(!sec.classList.contains('obCollapsed')));
      };
      label.setAttribute('aria-expanded', String(!sec.classList.contains('obCollapsed')));
      label.addEventListener('click', toggle);
      label.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
      });
    });

    /* Favoritenstern je Eintrag. */
    $$('.navItem[data-view]').forEach(btn => {
      if (btn.querySelector('.obPinBtn')) return;
      const view = btn.dataset.view;
      /* Lange Bezeichnungen werden nach zwei Zeilen gekürzt. Der volle
         Wortlaut bleibt über den Mauszeiger erreichbar. */
      const full = navLabel(btn);
      if (full.length > 22 && !btn.title) btn.title = full;
      const pin = document.createElement('span');
      pin.className = 'obPinBtn' + (prefs.pinned.includes(view) ? ' obPinned' : '');
      pin.setAttribute('role', 'button');
      pin.setAttribute('tabindex', '-1');
      pin.title = prefs.pinned.includes(view) ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufügen';
      pin.innerHTML = svg(ICON.star);
      pin.addEventListener('click', e => {
        e.stopPropagation();
        const i = prefs.pinned.indexOf(view);
        if (i >= 0) prefs.pinned.splice(i, 1); else prefs.pinned.push(view);
        pin.classList.toggle('obPinned', i < 0);
        pin.title = i < 0 ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufügen';
        savePrefs();
        renderFavourites();
      });
      btn.appendChild(pin);
    });

    /* Suche filtert die vorhandenen Einträge – es wird nichts erzeugt. */
    const input = $('#obNavSearch');
    const none  = $('#obNavEmpty');
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      let hits = 0;
      $$('.navItem[data-view]').forEach(btn => {
        if (btn.dataset.obHidden === '1') return;      // Rechte gehen vor
        const match = !q || navLabel(btn).toLowerCase().includes(q);
        btn.style.display = match ? '' : 'none';
        if (match) hits++;
      });
      $$('.navSection').forEach(sec => {
        const any = $$('.navItem', sec).some(b => b.style.display !== 'none');
        sec.style.display = (q && !any) ? 'none' : '';
        sec.classList.toggle('obSearching', Boolean(q));
        if (q) sec.classList.remove('obCollapsed');
        else if (prefs.collapsed.includes(sec.querySelector('.navSectionLabel')?.dataset.obKey))
          sec.classList.add('obCollapsed');
      });
      none.hidden = hits > 0 || !q;
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Escape') { input.value = ''; input.dispatchEvent(new Event('input')); }
      if (e.key === 'Enter') {
        const first = $$('.navItem[data-view]').find(b => b.style.display !== 'none');
        if (first) first.click();
      }
    });
  }

  /* Favoriten und zuletzt benutzte Bereiche als eigene Gruppe ganz oben. */
  function renderFavourites() {
    const nav = $('#sideNav nav');
    if (!nav) return;
    let sec = $('#obFavSection');
    const items = [];

    prefs.pinned.forEach(v => {
      const src = $('.navItem[data-view="' + CSS.escape(v) + '"]');
      if (src && src.dataset.obHidden !== '1') items.push({ view: v, label: navLabel(src), icon: src.querySelector('span')?.textContent || '•' });
    });

    if (!items.length) { if (sec) sec.remove(); return; }

    if (!sec) {
      sec = document.createElement('div');
      sec.id = 'obFavSection';
      sec.className = 'navSection';
      const tools = $('.obNavTools');
      nav.insertBefore(sec, tools ? tools.nextSibling : nav.firstChild);
    }
    sec.innerHTML =
      '<div class="navSectionLabel"><span>FAVORITEN</span></div>' +
      items.map(it =>
        '<button class="navItem obFavItem" data-fav-view="' + esc(it.view) + '">'
        + '<span>' + esc(it.icon) + '</span><b>' + esc(it.label) + '</b></button>'
      ).join('');
    $$('.obFavItem', sec).forEach(b => {
      b.addEventListener('click', () => go(b.dataset.favView));
    });
    markActive();
  }

  function markActive() {
    const cur = UI() ? UI().view : '';
    $$('.obFavItem').forEach(b => b.classList.toggle('active', b.dataset.favView === cur));
  }

  function go(view, focus) {
    const ui = UI();
    if (!ui) return;
    ui.setView(view);
    if (focus) setTimeout(() => focusRecord(view, focus), 220);
    const i = prefs.recent.indexOf(view);
    if (i >= 0) prefs.recent.splice(i, 1);
    prefs.recent.unshift(view);
    prefs.recent = prefs.recent.slice(0, 6);
    savePrefs();
    markActive();
  }

  /* Öffnet einen konkreten Datensatz, wenn das Orgaboard dafür bereits
     eine Maske hat. Gibt es keine, bleibt es beim Bereichswechsel –
     es wird keine Schaltfläche angeboten, die nichts tut. */
  function focusRecord(view, id) {
    const ui = UI();
    if (!ui || !id) return;
    try {
      if (view === 'orders' && ui.can('view_orders')) return ui.openOrder(id);
      if (view === 'customers' && ui.can('view_customers')) return ui.openCustomer(id);
      if (view === 'products' && ui.can('view_products')) return ui.openProduct(id);
    } catch (_e) { /* Maske nicht verfügbar – Bereich ist offen, das genügt */ }
  }

  /* Sichtbarkeit nach Rechten merken, damit die Suche sie nicht zurückholt. */
  function noteHidden() {
    $$('.navItem[data-view]').forEach(b => {
      if (b.style.display === 'none' && !$('#obNavSearch')?.value) b.dataset.obHidden = '1';
    });
  }

  /* =====================================================================
     2 · BEFEHLSPALETTE
     ===================================================================== */
  let palette = null, palItems = [], palIndex = 0, palTimer = null, palSeq = 0;
  /* Merkt, ob der Benutzer schon selbst ausgewählt hat. Trifft danach
     eine Serverantwort ein, darf sie die Auswahl nicht zurückspringen
     lassen – sonst öffnet ein Enter plötzlich den falschen Eintrag. */
  let palMoved = false;
  /* Läuft gerade eine Serversuche? Solange sie läuft, darf nicht
     „nichts gefunden“ dastehen – das wäre schlicht nicht wahr. */
  let palSearching = false;

  function buildPalette() {
    if (palette) return palette;
    palette = document.createElement('div');
    palette.className = 'obPalette';
    palette.setAttribute('role', 'dialog');
    palette.setAttribute('aria-modal', 'true');
    palette.setAttribute('aria-label', 'Schnellsuche und Bereichswechsel');
    palette.innerHTML =
      '<div class="obPaletteBox">' +
        '<div class="obPaletteInput">' + svg(ICON.search) +
          '<input type="text" id="obPalInput" placeholder="Bereich, Bestellung, Kunde oder Produkt …" '
          + 'autocomplete="off" spellcheck="false">' +
          '<span class="obPaletteSpin" id="obPalSpin"></span>' +
        '</div>' +
        '<div class="obPaletteList" id="obPalList" role="listbox"></div>' +
        '<div class="obPaletteFoot">' +
          '<span><kbd>↑</kbd><kbd>↓</kbd> wählen</span>' +
          '<span><kbd>↵</kbd> öffnen</span>' +
          '<span><kbd>Esc</kbd> schliessen</span>' +
        '</div>' +
      '</div>';
    document.body.appendChild(palette);

    palette.addEventListener('click', e => { if (e.target === palette) closePalette(); });
    const input = $('#obPalInput', palette);
    input.addEventListener('input', () => { palIndex = 0; palMoved = false; queryPalette(input.value); });
    input.addEventListener('keydown', e => {
      if (e.key === 'ArrowDown') { e.preventDefault(); movePalette(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); movePalette(-1); }
      else if (e.key === 'Enter')  { e.preventDefault(); runPalette(palIndex); }
      else if (e.key === 'Escape') { e.preventDefault(); closePalette(); }
    });
    return palette;
  }

  function openPalette() {
    buildPalette().classList.add('obOpen');
    const input = $('#obPalInput');
    input.value = '';
    palIndex = 0;
    palMoved = false;
    queryPalette('');
    setTimeout(() => input.focus(), 30);
  }
  function closePalette() {
    if (palette) palette.classList.remove('obOpen');
  }
  function movePalette(d) {
    if (!palItems.length) return;
    palMoved = true;
    palIndex = (palIndex + d + palItems.length) % palItems.length;
    paintPalette();
    const el = $$('.obPaletteItem', palette)[palIndex];
    if (el) el.scrollIntoView({ block: 'nearest' });
  }
  function runPalette(i) {
    const it = palItems[i];
    if (!it) return;
    closePalette();
    if (it.run) it.run();
  }

  /* Bereiche kommen aus der vorhandenen Navigation, Datensätze vom
     Server. Es wird nur angeboten, was das Konto auch sehen darf. */
  function localMatches(q) {
    const needle = q.trim().toLowerCase();
    const out = [];
    $$('.navItem[data-view]').forEach(btn => {
      if (btn.dataset.obHidden === '1') return;
      const label = navLabel(btn);
      if (needle && !label.toLowerCase().includes(needle)) return;
      out.push({
        group: 'Bereiche',
        icon: btn.querySelector('span')?.textContent || '•',
        label,
        hint: prefs.pinned.includes(btn.dataset.view) ? 'Favorit' : '',
        run: () => go(btn.dataset.view),
      });
    });
    return out.slice(0, needle ? 8 : 7);
  }

  /* Der Server liefert zu jedem Treffer bereits den Zielbereich und den
     Datensatz. Hier wird nur noch ein Symbol dazugestellt. Treffer ohne
     Zielbereich werden gar nicht erst angeboten – lieber kein Eintrag
     als einer, der beim Anklicken nichts tut. */
  const VIEW_ICON = {
    orders: '▣', customers: '♙', products: '▦', inventory: '▤',
    dealers: '♜', prototypes: '◇', documents: '▧', shipping: '➜',
  };

  function queryPalette(q) {
    const local = localMatches(q);
    palItems = local.slice();
    paintPalette();

    clearTimeout(palTimer);
    const term = q.trim();
    const spin = $('#obPalSpin');
    if (term.length < 2) {
      palSearching = false;
      if (spin) spin.classList.remove('obBusy');
      paintPalette();
      return;
    }

    const seq = ++palSeq;
    palSearching = true;
    if (spin) spin.classList.add('obBusy');
    paintPalette();
    palTimer = setTimeout(async () => {
      try {
        const data = await ask({ action: 'search', q: term });
        if (seq !== palSeq) return;                     // veraltete Antwort
        const remote = (data.results || [])
          .filter(r => r && r.view)
          .map(r => ({
            group: 'Datensätze',
            icon: VIEW_ICON[r.view] || '•',
            label: r.title || '–',
            hint: r.subtitle || r.type || '',
            run: () => go(r.view, r.focus),
          }));
        /* Die bisher gewählte Zeile behalten, wenn der Benutzer schon
           navigiert hat; die Bereichstreffer stehen ohnehin vorn. */
        const keep = palMoved ? palItems[palIndex] : null;
        palItems = local.concat(remote);
        if (keep) {
            const again = palItems.findIndex(x => x.label === keep.label && x.group === keep.group);
            palIndex = again >= 0 ? again : Math.min(palIndex, palItems.length - 1);
        } else {
            palIndex = 0;
        }
        paintPalette();
      } catch (_e) {
        if (seq === palSeq) {
          palItems = local.concat([{ group: 'Datensätze', icon: '!', label: 'Suche nicht erreichbar',
            hint: 'Bereiche lassen sich weiter öffnen', run: null }]);
          paintPalette();
        }
      } finally {
        if (seq === palSeq) {
          palSearching = false;
          if (spin) spin.classList.remove('obBusy');
          if (!palItems.length) paintPalette();
        }
      }
    }, 240);
  }

  function paintPalette() {
    const list = $('#obPalList');
    if (!list) return;
    if (!palItems.length) {
      list.innerHTML = palSearching
        ? '<div class="obPaletteNone">Wird gesucht …</div>'
        : '<div class="obPaletteNone">Nichts gefunden. Bereichsname, Bestellnummer, '
          + 'Kunde oder Produkt eingeben.</div>';
      return;
    }
    let html = '', lastGroup = '';
    palItems.forEach((it, i) => {
      if (it.group !== lastGroup) {
        html += '<div class="obPaletteGroup">' + esc(it.group) + '</div>';
        lastGroup = it.group;
      }
      html += '<button type="button" class="obPaletteItem' + (i === palIndex ? ' obActive' : '') + '" '
        + 'role="option" aria-selected="' + (i === palIndex) + '" data-i="' + i + '">'
        + '<i>' + esc(it.icon) + '</i><b>' + esc(it.label) + '</b>'
        + (it.hint ? '<small>' + esc(it.hint) + '</small>' : '') + '</button>';
    });
    list.innerHTML = html;
    $$('.obPaletteItem', list).forEach(b => {
      b.addEventListener('click', () => runPalette(Number(b.dataset.i)));
      b.addEventListener('mousemove', e => {
        /* Nur echte Mausbewegungen – ein Neuzeichnen unter dem
           stehenden Zeiger darf die Auswahl nicht verschieben. */
        if (!e.movementX && !e.movementY) return;
        const i = Number(b.dataset.i);
        if (i !== palIndex) { palIndex = i; palMoved = true; paintPalette(); }
      });
    });
  }

  /* =====================================================================
     3 · CHEF-COCKPIT
     ===================================================================== */
  let cockpit = null, cockpitAt = 0, cockpitBusy = false, attFilter = 'all';

  function greetingText() {
    const h = new Date().getHours();
    if (h < 5)  return 'Gute Nacht';
    if (h < 11) return 'Guten Morgen';
    if (h < 14) return 'Guten Tag';
    if (h < 18) return 'Guten Nachmittag';
    return 'Guten Abend';
  }

  function firstName() {
    const u = (UI() && UI().state && UI().state.current_user) || (window.RH24_ORGABOARD || {}).user || {};
    const n = String(u.display_name || u.username || '').trim();
    return n ? n.split(/[\s.]+/)[0].replace(/^./, c => c.toUpperCase()) : '';
  }

  function deltaHtml(delta, suffix) {
    if (delta === null || delta === undefined) {
      return '<span class="obKpiNote">Kein Vergleichswert vorhanden</span>';
    }
    const d = Number(delta);
    const cls = d > 0.5 ? 'obUp' : (d < -0.5 ? 'obDown' : 'obFlat');
    const sign = d > 0 ? '+' : '';
    const arrow = d > 0.5 ? '↑' : (d < -0.5 ? '↓' : '→');
    return '<span class="obKpiDelta ' + cls + '">' + arrow + ' ' + sign
      + d.toLocaleString('de-DE', { maximumFractionDigits: 1 }) + ' %'
      + (suffix ? ' ' + esc(suffix) : '') + '</span>';
  }

  /* Verlaufslinie aus echten Tageswerten. Ohne Werte: keine Linie. */
  function sparkHtml(points) {
    if (!Array.isArray(points) || points.length < 2) return '';
    const vals = points.map(p => Number(p.value) || 0);
    const max = Math.max.apply(null, vals);
    const min = Math.min.apply(null, vals);
    const span = (max - min) || 1;
    const w = 100, h = 30;
    const step = w / (vals.length - 1);
    const xy = vals.map((v, i) => [i * step, h - ((v - min) / span) * (h - 4) - 2]);
    const line = xy.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    const area = line + ' L' + w + ' ' + h + ' L0 ' + h + ' Z';
    const last = xy[xy.length - 1];
    return '<svg class="obSpark" viewBox="0 0 ' + w + ' ' + h + '" preserveAspectRatio="none" aria-hidden="true">'
      + '<path class="obSparkArea" d="' + area + '"/>'
      + '<path class="obSparkLine" d="' + line + '"/>'
      + '<circle class="obSparkDot" cx="' + last[0].toFixed(1) + '" cy="' + last[1].toFixed(1) + '" r="1.7"/>'
      + '</svg>';
  }

  function kpiCard(o) {
    const clickable = o.view ? ' obClickable' : '';
    const tag = o.view ? 'button' : 'div';
    const attr = o.view ? ' type="button" data-go="' + esc(o.view) + '"' : '';
    return '<' + tag + ' class="obKpi' + clickable + '"' + attr + '>'
      + '<span class="obKpiLabel">' + esc(o.label) + '</span>'
      + '<strong class="obKpiValue">' + esc(o.value) + '</strong>'
      + (o.sub ? '<span class="obKpiSub">' + esc(o.sub) + '</span>' : '')
      + (o.delta !== undefined ? deltaHtml(o.delta, o.deltaNote) : '')
      + (o.spark ? sparkHtml(o.spark) : '')
      + '</' + tag + '>';
  }

  function val(k) {
    if (!k) return null;
    return (k && typeof k === 'object' && 'value' in k) ? k.value : k;
  }

  function kpiSection(kpi) {
    if (!kpi || !Object.keys(kpi).length) {
      return '<div class="obNotice"><div><b>Keine Kennzahlen verfügbar</b>'
        + '<p>Für dieses Konto sind keine Umsatzdaten freigegeben.</p></div></div>';
    }
    const cards = [];
    const rToday = kpi.revenue_today;
    if (rToday) {
      cards.push(kpiCard({
        label: 'Umsatz heute', value: money(val(rToday)),
        sub: num(val(kpi.orders_today)) + ' ' + (val(kpi.orders_today) === 1 ? 'Bestellung' : 'Bestellungen'),
        delta: rToday.compare?.yesterday?.delta,
        deltaNote: 'ggü. gestern',
        spark: rToday.spark, view: 'orders',
      }));
    }
    if (kpi.revenue_7) {
      cards.push(kpiCard({
        label: 'Umsatz 7 Tage', value: money(val(kpi.revenue_7)),
        sub: 'gleitendes Fenster',
        delta: kpi.revenue_7.compare?.previous?.delta, deltaNote: 'ggü. Vorwoche',
        view: 'orders',
      }));
    }
    if (kpi.revenue_30) {
      cards.push(kpiCard({
        label: 'Umsatz 30 Tage', value: money(val(kpi.revenue_30)),
        sub: kpi.basket_30 ? 'Ø Warenkorb ' + money(val(kpi.basket_30)) : '',
        delta: kpi.revenue_30.compare?.previous?.delta, deltaNote: 'ggü. Vorperiode',
        view: 'orders',
      }));
    }
    if (kpi.revenue_year) {
      cards.push(kpiCard({
        label: 'Laufendes Jahr', value: money(val(kpi.revenue_year)),
        sub: 'seit 1. Januar',
        delta: kpi.revenue_year.compare?.previous?.delta, deltaNote: 'ggü. Vorjahr',
        view: 'orders',
      }));
    }
    const plain = [
      ['open_orders',   'Offene Aufträge',  'noch nicht abgeschlossen', 'orders'],
      ['receivables',   'Offene Zahlungen', 'noch nicht bezahlt',       'orders', true],
      ['to_ship',       'Versandbereit',    'warten auf Übergabe',      'shipping'],
      ['in_production', 'In Fertigung',     'inkl. Qualitätsprüfung',   'production'],
      ['stock_value',   'Lagerwert',        'Bestand × Verkaufspreis',  'inventory', true],
      ['new_customers', 'Neue Kunden',      'in 30 Tagen',              'customers'],
    ];
    plain.forEach(([key, label, sub, view, isMoney]) => {
      if (kpi[key] === undefined || kpi[key] === null) return;
      const v = val(kpi[key]);
      cards.push(kpiCard({ label, value: isMoney ? money(v) : num(v), sub, view }));
    });
    return '<div class="obKpiGrid">' + cards.join('') + '</div>';
  }

  function briefSection(brief) {
    if (!Array.isArray(brief) || !brief.length) return '';
    return '<div class="obBrief"><ul class="obBriefList">' + brief.map(b => {
      const dot = '<span class="obBriefDot' + (b.icon === 'ok' ? ' obOk' : '') + '"></span>';
      return '<li>' + (b.view
        ? '<button type="button" data-go="' + esc(b.view) + '">' + dot + esc(b.text) + '</button>'
        : '<button type="button" disabled>' + dot + esc(b.text) + '</button>') + '</li>';
    }).join('') + '</ul></div>';
  }

  function attentionSection(items) {
    const all = Array.isArray(items) ? items : [];
    const counts = {
      all: all.length,
      critical: all.filter(a => a.level === 'critical').length,
      warning:  all.filter(a => a.level === 'warning').length,
      info:     all.filter(a => a.level === 'info').length,
    };
    const shown = attFilter === 'all' ? all : all.filter(a => a.level === attFilter);
    const chip = (k, l) => '<button type="button" class="obChip' + (attFilter === k ? ' obOn' : '')
      + '" data-att="' + k + '">' + l + ' ' + counts[k] + '</button>';

    let body;
    if (!all.length) {
      body = '<div class="obEmptyState"><b>Nichts liegen geblieben</b>'
        + '<p>Zurzeit gibt es keinen Vorgang, der Aufmerksamkeit braucht. '
        + 'Neue Punkte erscheinen hier automatisch.</p></div>';
    } else if (!shown.length) {
      body = '<div class="obEmptyState"><b>Keine Einträge in dieser Auswahl</b>'
        + '<p>Über die Schaltflächen oben lässt sich die Auswahl ändern.</p></div>';
    } else {
      const lvl = { critical: 'Dringend', warning: 'Bald', info: 'Hinweis' };
      body = shown.slice(0, 40).map((a, i) => {
        const act = (a.actions && a.actions[0]) || null;
        const tag = act ? 'button' : 'div';
        const attr = act ? ' type="button" data-att-go="' + i + '"' : '';
        return '<' + tag + ' class="obAttItem"' + attr + '>'
          + '<span class="obAttBadge ob' + (a.level ? a.level.charAt(0).toUpperCase() + a.level.slice(1) : 'Info') + '">'
          + esc(lvl[a.level] || 'Hinweis') + '</span>'
          + '<span class="obAttBody"><b>' + esc(a.title || '') + '</b>'
          + '<span>' + esc(a.detail || '') + '</span></span>'
          + (act ? '<span class="obAttGo">›</span>' : '')
          + '</' + tag + '>';
      }).join('');
    }
    return '<section class="obAttention"><div class="obAttHead">'
      + '<h3>Was Aufmerksamkeit braucht</h3>'
      + '<div class="obAttFilters">' + chip('all', 'Alle') + chip('critical', 'Dringend')
      + chip('warning', 'Bald') + chip('info', 'Hinweise') + '</div></div>'
      + body + '</section>';
  }

  function insightSection(insights) {
    if (!Array.isArray(insights) || !insights.length) return '';
    const map = { positive: 'obPositive', attention: 'obAttentionLevel', critical: 'obCriticalLevel' };
    return '<section class="obCard"><div class="obCardHead"><h3>Was auffällt</h3>'
      + '<small>aus den eigenen Zahlen</small></div>'
      + '<div class="obCardBody obFlush">' + insights.map(x =>
        '<div class="obInsight ' + (map[x.level] || '') + '"><span class="obInsightMark"></span>'
        + '<div><p>' + esc(x.text) + '</p>'
        + (x.basis ? '<small>' + esc(x.basis) + '</small>' : '') + '</div></div>'
      ).join('') + '</div></section>';
  }

  function productSection(products) {
    if (!products || !Array.isArray(products.top_revenue) || !products.top_revenue.length) {
      if (!products) return '';
      return '<section class="obCard"><div class="obCardHead"><h3>Meistverkauft</h3></div>'
        + '<div class="obEmptyState"><b>Noch keine Verkäufe im Zeitraum</b>'
        + '<p>Sobald Bestellungen eingehen, erscheint hier die Rangfolge.</p></div></section>';
    }
    const rows = products.top_revenue.slice(0, 6);
    const max = Math.max.apply(null, rows.map(r => Number(r.revenue) || 0)) || 1;
    const anyUnknown = rows.some(r => r.revenue === null || r.revenue === undefined);
    return '<section class="obCard"><div class="obCardHead"><h3>Meistverkauft</h3>'
      + '<small>' + esc(String(products.days || 30)) + ' Tage</small></div>'
      + '<div class="obCardBody"><div class="obBars">' + rows.map(r => {
        const known = r.revenue !== null && r.revenue !== undefined;
        return '<div class="obBar"><div class="obBarTop"><b>' + esc(r.name) + '</b>'
          + '<span>' + (known ? money(r.revenue) : num(r.qty) + ' Stück') + '</span></div>'
          + '<div class="obBarTrack"><div class="obBarFill" style="width:'
          + (known ? ((Number(r.revenue) || 0) / max * 100).toFixed(1) : '0') + '%"></div></div></div>';
      }).join('') + '</div>'
      + (anyUnknown ? '<p style="margin:12px 0 0;font-size:11.5px;color:var(--ob-faint);line-height:1.5">'
          + 'Bei einzelnen Positionen ist in der Bestellung kein Betrag hinterlegt. '
          + 'Dort steht die Menge statt eines Umsatzes.</p>' : '')
      + '</div></section>';
  }

  function inventorySection(inv) {
    if (!inv || inv.available === false) return '';
    const rows = (inv.rows || []).filter(r => r.level === 'critical' || r.level === 'warning').slice(0, 8);
    let body;
    if (!rows.length) {
      body = '<div class="obEmptyState"><b>Bestände in Ordnung</b>'
        + '<p>Kein Artikel liegt unter dem Mindestbestand.</p></div>';
    } else {
      body = rows.map(r =>
        '<button type="button" class="obStockRow" data-go="inventory" data-focus="' + esc(r.id) + '">'
        + '<span class="obStockName"><b>' + esc(r.name) + '</b>'
        + '<small>' + (r.reach_days !== null && r.reach_days !== undefined && Number(r.stock) > 0
            ? 'Reichweite ca. ' + num(r.reach_days) + (Number(r.reach_days) === 1 ? ' Tag' : ' Tage')
            : (Number(r.stock) <= 0 ? 'ausverkauft' : 'Reichweite nicht berechenbar'))
        + (r.suggestion ? ' · Vorschlag: ' + num(r.suggestion) + ' ' + esc(r.unit || '') : '')
        + '</small></span>'
        + '<span class="obStockPill ' + (r.level === 'critical' ? 'obCritical' : 'obWarning') + '">'
        + num(r.stock) + ' / ' + num(r.minimum) + '</span></button>'
      ).join('');
    }
    const s = inv.summary || {};
    return '<section class="obCard"><div class="obCardHead"><h3>Lager</h3>'
      + '<small>' + num(s.items || 0) + ' Artikel · Wert ' + money(s.value || 0) + '</small></div>'
      + '<div class="obCardBody obFlush">' + body + '</div></section>';
  }

  function financeSection(fin) {
    if (!fin || fin.available === false) return '';
    const ps = fin.payment_sums || {}, ex = fin.expenses || {};
    const rows = [];
    if (ps.pending !== undefined) rows.push(['Offene Forderungen', money(ps.pending), ps.pending > 0 ? 'warn' : '']);
    if (ps.paid !== undefined)    rows.push(['Bezahlt eingegangen', money(ps.paid), '']);
    if (ex.open !== undefined)    rows.push(['Offene Ausgaben', money(ex.open), '']);
    if (ex.overdue)               rows.push(['davon überfällig', money(ex.overdue), 'warn']);
    if (!rows.length) return '';

    const methods = (fin.payment_methods || []).slice(0, 4);
    const maxM = Math.max.apply(null, methods.map(m => Number(m.count) || 0)) || 1;

    return '<section class="obCard"><div class="obCardHead"><h3>Zahlungslage</h3>'
      + '<small>30 Tage</small></div>'
      + '<div class="obCardBody obFlush">' + rows.map(([l, v, w]) =>
          '<div class="obHealthRow"><span class="obHealthDot' + (w ? ' obWarning' : '') + '"></span>'
          + '<b>' + esc(l) + '</b><span>' + esc(v) + '</span></div>'
        ).join('') + '</div>'
      + (methods.length
          ? '<div class="obCardBody"><h5 style="margin:0 0 10px;font-size:10.5px;letter-spacing:.1em;'
            + 'text-transform:uppercase;color:var(--ob-faint);font-weight:700">Zahlungsarten</h5>'
            + '<div class="obBars">' + methods.map(m =>
                '<div class="obBar"><div class="obBarTop"><b>' + esc(m.label || '–') + '</b>'
                + '<span>' + num(m.count) + '</span></div>'
                + '<div class="obBarTrack"><div class="obBarFill" style="width:'
                + ((Number(m.count) || 0) / maxM * 100).toFixed(1) + '%"></div></div></div>'
              ).join('') + '</div></div>'
          : '')
      + '</section>';
  }

  function healthSection(health) {
    if (!health || !Array.isArray(health.checks) || !health.checks.length) return '';
    const bad = health.checks.filter(c => c.state === 'critical').length;
    const warn = health.checks.filter(c => c.state === 'warning').length;
    const summary = bad ? bad + ' Punkt' + (bad === 1 ? '' : 'e') + ' kritisch'
                  : (warn ? warn + ' Hinweis' + (warn === 1 ? '' : 'e') : 'alles in Ordnung');
    /* Kritisches nach oben – wer hinsieht, soll es zuerst sehen. */
    const rank = { critical: 0, warning: 1, ok: 2 };
    const rows = health.checks.slice().sort((a, b) =>
      (rank[a.state] ?? 3) - (rank[b.state] ?? 3));
    return '<section class="obCard"><div class="obCardHead"><h3>Systemzustand</h3>'
      + '<small>' + esc(summary) + '</small></div>'
      + '<div class="obCardBody obFlush">' + rows.map(c =>
        '<div class="obHealthRow"><span class="obHealthDot ob'
        + (c.state === 'critical' ? 'Critical' : (c.state === 'warning' ? 'Warning' : 'Ok'))
        + '"></span><b>' + esc(c.label) + '</b><span>' + esc(c.detail || '') + '</span></div>'
      ).join('') + '</div></section>';
  }

  function skeleton() {
    return '<div class="obCockpit">'
      + '<div class="obGreet"><div><h2>' + esc(greetingText()) + (firstName() ? ', ' + esc(firstName()) : '')
      + '</h2><p>Zahlen werden geladen …</p></div></div>'
      + '<div class="obKpiGrid">' + Array(4).fill('<div class="obSkeleton obSkelKpi"></div>').join('') + '</div>'
      + '<div class="obCard"><div class="obCardBody">'
      + Array(4).fill('<div class="obSkeleton obSkelLine"></div>').join('')
      + '</div></div></div>';
  }

  function errorBox(msg) {
    return '<div class="obCockpit"><div class="obNotice obError"><div>'
      + '<b>Cockpit konnte nicht geladen werden</b>'
      + '<p>' + esc(msg || 'Unbekannter Fehler.') + '</p>'
      + '<button type="button" class="btn" id="obRetry">Erneut versuchen</button>'
      + '</div></div></div>';
  }

  function paintCockpit(host) {
    if (!cockpit) return;
    const c = cockpit;
    const name = firstName();
    host.innerHTML = '<div class="obCockpit">'
      + '<div class="obGreet"><div>'
        + '<h2>' + esc(greetingText()) + (name ? ', ' + esc(name) : '') + '</h2>'
        + '<p>' + esc(c.ranges?.weekday_full || c.ranges?.weekday || '') + ', ' + esc(c.ranges?.today || '')
        + ' · Stand ' + esc(new Date(c.generated_at || Date.now()).toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' })) + ' Uhr</p>'
      + '</div><div class="obGreetActions">'
        + '<button type="button" class="btn" id="obCockpitRefresh">Aktualisieren</button>'
        + '<button type="button" class="btn" id="obCockpitSearch">Suchen ⌘K</button>'
      + '</div></div>'
      + briefSection(c.brief)
      + kpiSection(c.kpi)
      + attentionSection(c.attention)
      + '<div class="obTwo"><div style="display:grid;gap:var(--ob-5)">'
        + productSection(c.products) + insightSection(c.insights)
      + '</div><div style="display:grid;gap:var(--ob-5)">'
        + inventorySection(c.inventory) + financeSection(c.finance) + healthSection(c.health)
      + '</div></div>'
      + '</div>';

    /* Verknüpfungen – jede Schaltfläche führt zu einem Bereich, den es gibt. */
    $$('[data-go]', host).forEach(b => {
      b.addEventListener('click', () => go(b.dataset.go, b.dataset.focus));
    });
    $$('[data-att]', host).forEach(b => {
      b.addEventListener('click', () => { attFilter = b.dataset.att; paintCockpit(host); });
    });
    $$('[data-att-go]', host).forEach(b => {
      b.addEventListener('click', () => {
        const list = attFilter === 'all' ? c.attention : c.attention.filter(a => a.level === attFilter);
        const item = list[Number(b.dataset.attGo)];
        const act = item && item.actions && item.actions[0];
        if (act) go(act.view, act.focus);
      });
    });
    const rf = $('#obCockpitRefresh', host);
    if (rf) rf.addEventListener('click', () => loadCockpit(host, true));
    const sb = $('#obCockpitSearch', host);
    if (sb) sb.addEventListener('click', openPalette);
  }

  async function loadCockpit(host, force) {
    if (cockpitBusy) return;
    const fresh = cockpit && (Date.now() - cockpitAt < 60000);
    if (fresh && !force) { paintCockpit(host); return; }
    cockpitBusy = true;
    if (!cockpit) host.innerHTML = skeleton();
    try {
      const data = await ask({ action: 'cockpit', product_days: 30 });
      cockpit = data.cockpit;
      cockpitAt = Date.now();
      paintCockpit(host);
    } catch (e) {
      if (cockpit) {
        paintCockpit(host);
        if (UI()) UI().toast('Die Zahlen konnten nicht aufgefrischt werden.', true);
      } else {
        host.innerHTML = errorBox(e && e.message);
        const r = $('#obRetry', host);
        if (r) r.addEventListener('click', () => { cockpitBusy = false; loadCockpit(host, true); });
      }
    } finally {
      cockpitBusy = false;
    }
  }

  /* Das Cockpit ersetzt die Startseite erst, wenn die Daten da sind.
     Schlägt es fehl, bleibt die bisherige Startseite stehen. */
  function onViewRendered(view) {
    markActive();
    noteHidden();
    if (view !== 'dashboard') return;
    const ws = $('#workspace');
    if (!ws) return;
    if (ws.dataset.obCockpit === '1' && cockpit) { paintCockpit(ws); return; }
    ws.dataset.obCockpit = '1';
    loadCockpit(ws, false);
  }

  /* =====================================================================
     4 · ASSISTENT
     ===================================================================== */
  let assistBuilt = false, assistBusy = false;

  const CHIPS = [
    'Was muss heute erledigt werden?',
    'Was muss nachbestellt werden?',
    'Welche Zahlungen sind überfällig?',
    'Was verkauft sich am besten?',
    'Wie ist der Umsatz?',
  ];

  function buildAssistant() {
    if (assistBuilt || !UI() || !UI().can('view_dashboard')) return;
    assistBuilt = true;
    const wrap = document.createElement('div');
    wrap.className = 'obAssist';
    wrap.innerHTML =
      '<section class="obAssistPanel" id="obAssistPanel" role="dialog" aria-label="Smoky Geschäftsassistent">' +
        '<div class="obAssistHead"><div style="flex:1"><b>Smoky</b>'
          + '<small>Fragen zum laufenden Betrieb</small></div>' +
          '<button type="button" class="obAssistClose" id="obAssistClose" aria-label="Schliessen">×</button>' +
        '</div>' +
        '<div class="obAssistBody" id="obAssistBody" aria-live="polite"></div>' +
        '<div class="obAssistFoot">' +
          '<div class="obAssistChips" id="obAssistChips"></div>' +
          '<form class="obAssistForm" id="obAssistForm">' +
            '<textarea class="obAssistInput" id="obAssistInput" rows="1" maxlength="400" '
            + 'placeholder="Frage eingeben …" aria-label="Frage an Smoky"></textarea>' +
            '<button type="submit" class="obAssistSend" id="obAssistSend" aria-label="Absenden">'
            + svg(ICON.send) + '</button>' +
          '</form>' +
          '<p class="obAssistHint">Smoky liest nur. Änderungen nimmst du im jeweiligen Bereich selbst vor.</p>' +
        '</div>' +
      '</section>' +
      '<button type="button" class="obAssistBtn" id="obAssistBtn">' + svg(ICON.spark) + 'Smoky fragen</button>';
    document.body.appendChild(wrap);

    const panel = $('#obAssistPanel');
    const body  = $('#obAssistBody');
    const input = $('#obAssistInput');

    $('#obAssistChips').innerHTML = CHIPS.map(c =>
      '<button type="button" class="obAssistChip">' + esc(c) + '</button>').join('');
    $$('#obAssistChips .obAssistChip').forEach(b => {
      b.addEventListener('click', () => { input.value = b.textContent; sendQuestion(); });
    });

    $('#obAssistBtn').addEventListener('click', () => {
      const open = panel.classList.toggle('obOpen');
      if (open) {
        if (!body.children.length) {
          body.insertAdjacentHTML('beforeend',
            '<div class="obAssistA"><h4>Womit kann ich helfen?</h4>'
            + '<p>Ich beantworte Fragen zu Bestellungen, Lager, Zahlungen und Verkäufen – '
            + 'immer mit den Zahlen, die gerade in der Datenbank stehen.</p></div>');
        }
        setTimeout(() => input.focus(), 60);
      }
    });
    $('#obAssistClose').addEventListener('click', () => panel.classList.remove('obOpen'));

    $('#obAssistForm').addEventListener('submit', e => { e.preventDefault(); sendQuestion(); });
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendQuestion(); }
      if (e.key === 'Escape') panel.classList.remove('obOpen');
    });
    input.addEventListener('input', () => {
      input.style.height = 'auto';
      input.style.height = Math.min(110, input.scrollHeight) + 'px';
    });
  }

  function answerHtml(a) {
    let h = '<h4>' + esc(a.title || '') + '</h4>';
    (a.blocks || []).forEach(b => {
      if (!b || !b.t) return;
      if (b.t === 'p')     h += '<p>' + esc(b.v) + '</p>';
      else if (b.t === 'h') h += '<h5>' + esc(b.v) + '</h5>';
      else if (b.t === 'ul' && Array.isArray(b.v))
        h += '<ul>' + b.v.map(x => '<li>' + esc(x) + '</li>').join('') + '</ul>';
      else if (b.t === 'note') h += '<div class="obAssistNote">' + esc(b.v) + '</div>';
      else if (b.t === 'facts' && Array.isArray(b.v))
        h += '<div class="obAssistFacts">' + b.v.map(f =>
          '<div class="obAssistFact"><b>' + esc(f.label) + '</b><span>' + esc(f.value) + '</span></div>'
        ).join('') + '</div>';
    });
    if (Array.isArray(a.rows) && a.rows.length) {
      h += '<div class="obAssistRows">' + a.rows.map((r, i) => {
        const act = r.action || null;
        const tag = act ? 'button' : 'div';
        const attr = act ? ' type="button" data-row="' + i + '"' : '';
        return '<' + tag + ' class="obAssistRow"' + attr + '>'
          + (r.a ? '<em>' + esc(r.a) + '</em>' : '')
          + '<b>' + esc(r.b || '') + '</b>'
          + (r.c ? '<span>' + esc(r.c) + '</span>' : '') + '</' + tag + '>';
      }).join('') + '</div>';
    }
    if (Array.isArray(a.actions) && a.actions.length) {
      h += '<div class="obAssistActions">' + a.actions.map((x, i) =>
        '<button type="button" class="obAssistAction" data-act="' + i + '">' + esc(x.label) + '</button>'
      ).join('') + '</div>';
    }
    return h;
  }

  async function sendQuestion() {
    if (assistBusy) return;
    const input = $('#obAssistInput');
    const body  = $('#obAssistBody');
    const send  = $('#obAssistSend');
    const q = (input.value || '').trim();
    if (!q) return;

    assistBusy = true;
    send.disabled = true;
    input.value = '';
    input.style.height = 'auto';

    body.insertAdjacentHTML('beforeend', '<div class="obAssistQ">' + esc(q) + '</div>');
    const typing = document.createElement('div');
    typing.className = 'obAssistA obAssistTyping';
    typing.innerHTML = '<i></i><i></i><i></i>';
    body.appendChild(typing);
    body.scrollTop = body.scrollHeight;

    try {
      const data = await ask({ action: 'assistant', question: q });
      typing.remove();
      const box = document.createElement('div');
      box.className = 'obAssistA';
      box.innerHTML = answerHtml(data.answer || {});
      body.appendChild(box);

      const a = data.answer || {};
      $$('[data-act]', box).forEach(b => b.addEventListener('click', () => {
        const x = (a.actions || [])[Number(b.dataset.act)];
        if (x && x.view) { go(x.view, x.focus); $('#obAssistPanel').classList.remove('obOpen'); }
      }));
      $$('[data-row]', box).forEach(b => b.addEventListener('click', () => {
        const r = (a.rows || [])[Number(b.dataset.row)];
        const x = r && r.action;
        if (x && x.view) { go(x.view, x.focus); $('#obAssistPanel').classList.remove('obOpen'); }
      }));
    } catch (e) {
      typing.remove();
      body.insertAdjacentHTML('beforeend',
        '<div class="obAssistA"><h4>Das hat nicht geklappt</h4><p>'
        + esc((e && e.message) || 'Die Anfrage konnte nicht beantwortet werden.')
        + '</p></div>');
    } finally {
      assistBusy = false;
      send.disabled = false;
      body.scrollTop = body.scrollHeight;
      input.focus();
    }
  }

  /* =====================================================================
     5 · START
     ===================================================================== */
  function bindShortcuts() {
    document.addEventListener('keydown', e => {
      const mod = e.metaKey || e.ctrlKey;
      if (mod && (e.key === 'k' || e.key === 'K')) {
        e.preventDefault();
        if (palette && palette.classList.contains('obOpen')) closePalette(); else openPalette();
        return;
      }
      if (e.key === 'Escape' && palette && palette.classList.contains('obOpen')) closePalette();
      /* "/" öffnet die Suche, sofern gerade kein Feld beschrieben wird. */
      if (e.key === '/' && !mod) {
        const t = e.target;
        const typing = t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable);
        if (!typing) { e.preventDefault(); openPalette(); }
      }
    });
  }

  function start() {
    if (!document.body.classList.contains('ob-app')) return;   // Anmeldeseite
    buildNavTools();
    renderFavourites();
    bindShortcuts();

    document.addEventListener('rh24:view', e => {
      try { onViewRendered(e.detail && e.detail.view); } catch (err) { console.warn('RH24 Cockpit:', err); }
    });

    /* Auf die Bereitschaft des Hauptprogramms warten – ohne es zu verändern. */
    let tries = 0;
    const wait = setInterval(() => {
      tries++;
      if (UI() && UI().state) {
        clearInterval(wait);
        noteHidden();
        renderFavourites();
        buildAssistant();
        if (UI().view === 'dashboard') onViewRendered('dashboard');
      } else if (tries > 100) {          // nach ~20 s aufgeben
        clearInterval(wait);
      }
    }, 200);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
