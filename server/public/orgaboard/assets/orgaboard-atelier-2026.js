/* =====================================================================
   RÄUCHERHAKEN24 · ATELIER RUNTIME 2026.3  ·  ORGABOARD
   (orgaboard/assets/orgaboard-atelier-2026.js)
   ---------------------------------------------------------------------
   Ergänzt die Oberfläche des OrgaBoards. Es wird KEIN Modul ersetzt,
   keine Berechtigung ausgewertet, keine Geschäftslogik dupliziert.

   Was diese Datei tut
     1  Seitennavigation: gleitende Markierung, Vertriebskanäle, Schublade
     2  Kopfleiste: Tastaturhinweis, Aktualisieren-Rückmeldung
     3  Kennzahlen: Symbole, Hochzählen echter Werte
     4  Diagramme: Umsatzverlauf und Kanäle aus ECHTEN Cockpit-Daten
     5  Optimistic UI beim Speichern und Veröffentlichen
     6  Tabellen: Beschriftungen für die Kartenansicht auf dem Handy
     7  Reveal-Bewegung und Skelett-Ladezustände
     8  Anbindung an die Near-Realtime-Schicht

   GRUNDSÄTZE
     · Es werden ausschliesslich Zahlen dargestellt, die bereits in der
       Oberfläche stehen oder direkt aus cockpit-api.php stammen.
       Es wird nichts geschätzt, hochgerechnet oder erfunden.
     · Rechte werden weiterhin ausschliesslich serverseitig geprüft.
       Diese Datei verlässt sich auf das, was der Server ausliefert.
     · Ohne Daten erscheint ein ehrlicher Leerzustand.
     · prefers-reduced-motion schaltet Bewegung ab, niemals Funktion.
   ===================================================================== */
(() => {
  'use strict';
  if (window.__RH24_OB_ATELIER_2026__) return;
  window.__RH24_OB_ATELIER_2026__ = true;

  const VERSION = '2026.3';
  document.documentElement.classList.add('ob-atelier');

  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const mqReduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  const reduced  = () => mqReduce.matches;
  const cfg = window.RH24_ORGABOARD || {};

  const esc = v => String(v == null ? '' : v)
    .replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = v => Number(v || 0).toLocaleString('de-DE', { style: 'currency', currency: 'EUR' });

  /* ===================================================================
     1 · SEITENNAVIGATION
  =================================================================== */
  function navGlide() {
    const side = $('#sideNav');
    const nav  = side && side.querySelector('nav');
    if (!side || !nav || $('.obNavGlide', side)) return;

    const glide = document.createElement('span');
    glide.className = 'obNavGlide';
    glide.setAttribute('aria-hidden', 'true');
    nav.style.position = nav.style.position || 'relative';
    nav.appendChild(glide);

    const place = () => {
      const active = nav.querySelector('.navItem.active');
      if (!active) { glide.classList.remove('is-on'); return; }
      const top = active.offsetTop + 7;
      const h   = Math.max(0, active.offsetHeight - 14);
      glide.style.height = h + 'px';
      glide.style.transform = 'translateY(' + top + 'px)';
      glide.classList.add('is-on');
    };
    // Die aktive Markierung setzt weiterhin admin-v9911.js. Hier wird sie nur beobachtet.
    new MutationObserver(() => requestAnimationFrame(place))
      .observe(nav, { attributes: true, subtree: true, attributeFilter: ['class'] });
    window.addEventListener('resize', () => requestAnimationFrame(place), { passive: true });
    nav.addEventListener('scroll', () => requestAnimationFrame(place), { passive: true });
    requestAnimationFrame(place);
  }

  /* Vertriebskanäle: nur Ziele, die es tatsächlich gibt.
     Es wird kein Kanal erfunden (z. B. kein Amazon ohne Anbindung). */
  function salesChannels() {
    const side = $('#sideNav');
    const nav  = side && side.querySelector('nav');
    if (!side || !nav || $('.obChannelSection', nav)) return;

    const items = [
      { icon: '◫', label: 'Online-Shop', href: '../index.html', hint: '↗' }
    ];
    // Der Marktplatz erscheint nur, wenn der Server den Bereich freigegeben hat.
    if (nav.querySelector('[data-view="marketplace"]')) {
      items.push({ icon: '♢', label: 'An- & Verkaufen', href: '../ankauf-verkauf.php', hint: '↗' });
    }

    const sec = document.createElement('div');
    sec.className = 'navSection obChannelSection';
    sec.innerHTML =
      '<div class="navSectionLabel"><span>VERTRIEBSKANÄLE</span></div>' +
      items.map(i =>
        '<a class="obChannelLink" href="' + esc(i.href) + '" target="_blank" rel="noopener noreferrer">' +
          '<span aria-hidden="true">' + esc(i.icon) + '</span>' +
          '<b>' + esc(i.label) + '</b>' +
          '<i aria-hidden="true">' + esc(i.hint) + '</i>' +
        '</a>').join('');

    // Vor "MEIN KONTO" einhängen, sonst ans Ende.
    const account = $$('.navSectionLabel span', nav).find(s => /MEIN KONTO/i.test(s.textContent || ''));
    const anchor  = account ? account.closest('.navSection') : null;
    if (anchor) nav.insertBefore(sec, anchor); else nav.appendChild(sec);
  }

  /* Schublade auf dem Handy: Abdunklung, Scrollsperre, Escape */
  function mobileDrawer() {
    const side = $('#sideNav');
    const btn  = $('#mobileMenu');
    if (!side || !btn || $('.obNavScrim')) return;

    const scrim = document.createElement('div');
    scrim.className = 'obNavScrim';
    scrim.setAttribute('aria-hidden', 'true');
    document.body.appendChild(scrim);

    const isOpen = () => side.classList.contains('open') || document.body.classList.contains('navOpen');
    const sync = () => {
      const open = isOpen();
      scrim.classList.toggle('is-on', open);
      document.body.style.overflow = open && window.innerWidth <= 860 ? 'hidden' : '';
      btn.setAttribute('aria-expanded', String(open));
    };
    const close = () => {
      side.classList.remove('open');
      document.body.classList.remove('navOpen');
      sync();
    };
    new MutationObserver(sync).observe(side, { attributes: true, attributeFilter: ['class'] });
    new MutationObserver(sync).observe(document.body, { attributes: true, attributeFilter: ['class'] });
    scrim.addEventListener('click', close);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    side.addEventListener('click', e => {
      if (window.innerWidth <= 860 && e.target.closest('.navItem, .obChannelLink')) setTimeout(close, 60);
    });
    window.addEventListener('resize', () => { if (window.innerWidth > 860) close(); }, { passive: true });
    btn.setAttribute('aria-controls', 'sideNav');
    sync();
  }

  /* ===================================================================
     2 · KOPFLEISTE
  =================================================================== */
  function topBar() {
    const search = $('.globalSearch');
    if (search && !$('.obKbdHint', search)) {
      const hint = document.createElement('kbd');
      hint.className = 'obKbdHint';
      hint.setAttribute('aria-hidden', 'true');
      hint.textContent = /Mac|iPhone|iPad/i.test(navigator.platform || navigator.userAgent) ? '⌘K' : 'Strg K';
      search.appendChild(hint);
    }
    const refresh = $('#refreshBtn');
    if (refresh && refresh.dataset.obSpin !== '1') {
      refresh.dataset.obSpin = '1';
      refresh.addEventListener('click', () => {
        if (reduced()) return;
        refresh.classList.remove('is-spinning');
        void refresh.offsetWidth;
        refresh.classList.add('is-spinning');
        setTimeout(() => refresh.classList.remove('is-spinning'), 760);
      });
    }
  }

  /* ===================================================================
     3 · KENNZAHLEN
     Die Werte selbst kommen unverändert aus der Oberfläche. Hier wird
     ausschliesslich die Darstellung animiert: der bereits gerenderte
     Text wird geparst und von 0 auf genau diesen Wert hochgezählt.
     Formatierung, Währung und Nachkommastellen bleiben erhalten.
  =================================================================== */
  const COUNT_SELECTORS = '.metric strong, .executiveKpiGrid > article strong, .obKpiValue, .productionMetric strong';

  function parseNumber(text) {
    const raw = String(text || '');
    // Deutsche Schreibweise: 18.742,89 €  →  18742.89
    const m = raw.match(/-?[\d.]+(?:,\d+)?/);
    if (!m) return null;
    const n = Number(m[0].replace(/\./g, '').replace(',', '.'));
    return Number.isFinite(n) ? { value: n, token: m[0], raw } : null;
  }
  function formatLike(token, value) {
    const decimals = (token.split(',')[1] || '').length;
    return value.toLocaleString('de-DE', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    });
  }

  function countUp(el) {
    if (el.dataset.obCountup === 'done') return;
    const parsed = parseNumber(el.textContent);
    if (!parsed || parsed.value === 0) { el.dataset.obCountup = 'done'; return; }
    el.dataset.obCountup = 'done';
    if (reduced()) return;

    const final = parsed.raw;
    const start = performance.now();
    const dur   = 900;
    const ease  = t => 1 - Math.pow(1 - t, 3);   // sanftes Ausklingen

    const step = now => {
      const t = Math.min(1, (now - start) / dur);
      if (t >= 1) { el.textContent = final; return; }
      const cur = parsed.value * ease(t);
      el.textContent = final.replace(parsed.token, formatLike(parsed.token, cur));
      requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  const METRIC_ICONS = [
    [/umsatz|erlös|einnahm/i, '€', 'accent'],
    [/bestell|auftrag|auftr/i, '▣', ''],
    [/warenkorb|korb/i,        '▤', ''],
    [/kunde/i,                 '♙', ''],
    [/lager|bestand/i,         '▦', ''],
    [/versand|paket/i,         '➜', ''],
    [/produktion|fertigung/i,  '⇢', ''],
    [/zahlung|forderung/i,     '◷', ''],
    [/gebiet|vertrieb/i,       '⌖', ''],
  ];
  function metricIcons(root = document) {
    $$('.metric, .executiveKpiGrid > article, .obKpi', root).forEach(card => {
      if (card.dataset.obIcon === '1') return;
      card.dataset.obIcon = '1';
      const label = (card.querySelector('small, .obKpiLabel')?.textContent || '').trim();
      if (!label) return;
      const hit = METRIC_ICONS.find(([re]) => re.test(label));
      if (!hit) return;
      const span = document.createElement('span');
      span.className = 'obMetricIcon' + (hit[2] ? ' ' + hit[2] : '');
      span.setAttribute('aria-hidden', 'true');
      span.textContent = hit[1];
      card.appendChild(span);
    });
  }

  /* ===================================================================
     4 · DIAGRAMME AUS ECHTEN COCKPIT-DATEN
     cockpit-api.php liefert bereits `finance.revenue_series` (30 echte
     Tageswerte) und – seit V2026.3 – `finance.channels`. Beides wurde
     bisher nicht dargestellt. Es wird hier ergänzt, ohne bestehende
     Abschnitte anzufassen.
  =================================================================== */
  let cockpitCache = null, cockpitBusy = false;

  async function askCockpit() {
    if (cockpitCache && Date.now() - cockpitCache.at < 60000) return cockpitCache.data;
    if (cockpitBusy) return null;
    cockpitBusy = true;
    try {
      const r = await fetch('cockpit-api.php?v=' + VERSION, {
        method: 'POST',
        cache: 'no-store',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrf || '' },
        body: JSON.stringify({ action: 'cockpit', product_days: 30 })
      });
      const d = await r.json();
      if (!r.ok || !d || d.ok !== true) return null;
      cockpitCache = { at: Date.now(), data: d.cockpit || null };
      return cockpitCache.data;
    } catch (e) {
      return null;
    } finally {
      cockpitBusy = false;
    }
  }

  /** Linien-/Flächendiagramm aus echten Tageswerten.
   *  Ohne mindestens zwei Werte wird kein Diagramm gezeichnet. */
  function lineChart(points) {
    const vals = points.map(p => Number(p.value) || 0);
    if (vals.length < 2) return '';
    const W = 720, H = 200, PAD_L = 8, PAD_R = 8, PAD_T = 14, PAD_B = 26;
    const max = Math.max.apply(null, vals);
    const min = 0;                                  // Nulllinie – ehrliche Skala
    const span = (max - min) || 1;
    const iw = W - PAD_L - PAD_R;
    const ih = H - PAD_T - PAD_B;
    const step = iw / (vals.length - 1);
    const xy = vals.map((v, i) => [
      PAD_L + i * step,
      PAD_T + ih - ((v - min) / span) * ih
    ]);
    const line = xy.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    const area = line + ' L' + (PAD_L + iw).toFixed(1) + ' ' + (PAD_T + ih) + ' L' + PAD_L + ' ' + (PAD_T + ih) + ' Z';

    // Gitterlinien: vier Höhen, damit die Grössenordnung ablesbar bleibt
    const grid = [0, .25, .5, .75, 1].map(f => {
      const y = (PAD_T + ih - f * ih).toFixed(1);
      return '<line class="obChartGrid" x1="' + PAD_L + '" y1="' + y + '" x2="' + (PAD_L + iw).toFixed(1) + '" y2="' + y + '"/>';
    }).join('');

    // Beschriftung: erster, mittlerer und letzter Tag
    const label = i => {
      const d = new Date(points[i].date + 'T00:00:00');
      return Number.isNaN(d.getTime()) ? '' : d.toLocaleDateString('de-DE', { day: '2-digit', month: 'short' });
    };
    const marks = [0, Math.floor((vals.length - 1) / 2), vals.length - 1];
    const axis = marks.map((i, n) =>
      '<text class="obChartLabel" x="' + xy[i][0].toFixed(1) + '" y="' + (H - 7) +
      '" text-anchor="' + (n === 0 ? 'start' : n === 2 ? 'end' : 'middle') + '">' + esc(label(i)) + '</text>'
    ).join('');

    const dots = xy.map((p, i) =>
      '<circle class="obChartDot" style="--rh-dot-i:' + i + '" cx="' + p[0].toFixed(1) + '" cy="' + p[1].toFixed(1) + '" r="2.6"/>'
    ).join('');

    // Die Linienlänge wird gebraucht, damit sich die Linie einzeichnen kann.
    let len = 0;
    for (let i = 1; i < xy.length; i++) {
      len += Math.hypot(xy[i][0] - xy[i - 1][0], xy[i][1] - xy[i - 1][1]);
    }

    return '<svg class="obChart" viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" role="img" ' +
      'aria-label="Umsatzverlauf der letzten ' + vals.length + ' Tage" style="--rh-path-len:' + Math.ceil(len) + '">' +
      '<defs><linearGradient id="obChartFade" x1="0" y1="0" x2="0" y2="1">' +
        '<stop offset="0%"   stop-color="var(--rh-accent)" stop-opacity=".18"/>' +
        '<stop offset="100%" stop-color="var(--rh-accent)" stop-opacity="0"/>' +
      '</linearGradient></defs>' +
      grid +
      '<path class="obChartArea" d="' + area + '"/>' +
      '<path class="obChartLine" d="' + line + '"/>' +
      dots + axis +
      '</svg>';
  }

  function channelBars(channels, total) {
    if (!Array.isArray(channels) || !channels.length) {
      return '<div class="empty"><b>Noch keine Bestellungen im Zeitraum</b>' +
             'Sobald Aufträge eingehen, erscheint hier die Verteilung nach Vertriebskanal.</div>';
    }
    const max = Math.max.apply(null, channels.map(c => Number(c.value) || 0)) || 1;
    return '<div class="execBars">' + channels.map((c, i) => {
      const v = Number(c.value) || 0;
      return '<div class="execBarRow">' +
        '<span>' + esc(c.label) + ' · <span style="color:var(--rh-text-muted)">' +
          esc(String(c.count)) + ' ' + (c.count === 1 ? 'Auftrag' : 'Aufträge') + '</span></span>' +
        '<b style="font-variant-numeric:tabular-nums">' + esc(money(v)) +
          ' <span style="color:var(--rh-text-muted);font-weight:500">' +
          esc(Number(c.share || 0).toLocaleString('de-DE', { maximumFractionDigits: 1 })) + ' %</span></b>' +
        '<span class="obBarTrack"><i class="obBarFill" style="--rh-bar-value:' +
          (v / max).toFixed(4) + ';--rh-bar-delay:' + (i * 90) + 'ms"></i></span>' +
      '</div>';
    }).join('') +
    '<p style="margin:6px 0 0;font-size:12px;color:var(--rh-text-muted)">Gesamt ' +
      esc(money(total)) + ' · letzte 30 Tage · Stornierungen ausgenommen</p></div>';
  }

  /** Hängt die beiden Diagramm-Panels unter das bestehende Cockpit. */
  async function revenuePanels(host) {
    if (!host || host.dataset.obCharts === '1') return;
    const cockpit = $('.obCockpit', host) || host;
    if (!cockpit) return;
    host.dataset.obCharts = '1';

    const mount = document.createElement('div');
    mount.className = 'executiveGrid obChartsMount';
    mount.innerHTML =
      '<section class="panel"><div class="panelTitle"><h3>Umsatzentwicklung</h3>' +
        '<span>letzte 30 Tage</span></div>' +
        '<div class="obSkeleton" style="margin:16px;height:180px"></div></section>' +
      '<section class="panel"><div class="panelTitle"><h3>Umsatz nach Kanälen</h3>' +
        '<span>letzte 30 Tage</span></div>' +
        '<div class="obSkeleton" style="margin:16px;height:180px"></div></section>';
    cockpit.appendChild(mount);

    const data = await askCockpit();
    const fin  = data && data.finance;

    if (!fin) {
      // Kein Zugriff oder keine Daten: Panel wieder entfernen statt etwas zu behaupten.
      mount.remove();
      host.dataset.obCharts = '';
      return;
    }

    const series = Array.isArray(fin.revenue_series) ? fin.revenue_series : [];
    const hasRevenue = series.some(p => Number(p.value) > 0);
    const sum = series.reduce((a, p) => a + (Number(p.value) || 0), 0);

    mount.innerHTML =
      '<section class="panel obReveal"><div class="panelTitle">' +
        '<h3>Umsatzentwicklung</h3><span>letzte 30 Tage · ' + esc(money(sum)) + '</span></div>' +
        (hasRevenue
          ? '<div style="padding:18px 20px 8px">' + lineChart(series) + '</div>'
          : '<div class="empty"><b>Noch keine Umsätze im Zeitraum</b>' +
            'Sobald Bestellungen eingehen, zeichnet sich der Verlauf hier automatisch.</div>') +
      '</section>' +
      '<section class="panel obReveal"><div class="panelTitle">' +
        '<h3>Umsatz nach Kanälen</h3><span>letzte 30 Tage</span></div>' +
        channelBars(fin.channels, fin.channels_total || 0) +
      '</section>';

    armReveal(mount);
    armCharts(mount);
    orderCockpit(host);
  }

  /* Reihenfolge der Startseite nach Vorgabe:
       Begrüssung → Kennzahlen → Umsatzentwicklung & Kanäle →
       Produkte/Bestände (obTwo, enthält auch Top-Produkte) →
       „Was jetzt Aufmerksamkeit braucht" (Hinweise) ganz am Ende.
     Es werden nur vorhandene Abschnitte VERSCHOBEN – nichts wird
     entfernt oder doppelt aufgebaut. */
  function orderCockpit(host) {
    const cockpit = $('.obCockpit', host);
    if (!cockpit) return;
    const kpi    = $('.obKpiGrid', cockpit);
    const charts = $('.obChartsMount', cockpit);
    const two    = $('.obTwo', cockpit);
    const att    = $$('.obCard', cockpit).find(c => $('.obAttItem', c)) ||
                   $('.obAttention', cockpit);
    try {
      if (kpi && charts) kpi.insertAdjacentElement('afterend', charts);
      if (att && two) two.insertAdjacentElement('afterend', att);
    } catch (e) { /* Reihenfolge ist Komfort, niemals Pflicht */ }
  }

  /* ===================================================================
     5 · OPTIMISTIC UI
     Speichern und Veröffentlichen bekommen sichtbare Zustände:
       Speichern → Ladezustand → „Gespeichert“ bzw. „Im Shop veröffentlicht“
     Bei einem Fehler kehrt die Schaltfläche in ihren Ausgangszustand
     zurück; die Eingaben bleiben unangetastet. Es wird KEIN alert()
     erzeugt und keine bestehende Fehlerbehandlung ersetzt – die
     ursprüngliche Klickbehandlung läuft unverändert weiter.
  =================================================================== */
  const SAVE_RE    = /^(speichern|sichern|übernehmen|anlegen|aktualisieren)$/i;
  const PUBLISH_RE = /(veröffentlichen|im shop veröffentlichen|online stellen)/i;

  function bindOptimistic(root = document) {
    $$('button.btn', root).forEach(btn => {
      if (btn.dataset.obOpt === '1') return;
      const text = (btn.textContent || '').trim();
      const isSave    = SAVE_RE.test(text);
      const isPublish = PUBLISH_RE.test(text);
      if (!isSave && !isPublish) return;
      btn.dataset.obOpt = '1';

      btn.addEventListener('click', () => {
        if (btn.classList.contains('is-busy') || btn.disabled) return;
        const original = btn.textContent;
        const width = btn.getBoundingClientRect().width;
        btn.style.minWidth = Math.ceil(width) + 'px';   // kein Springen im Layout
        btn.classList.add('is-busy');

        /* Der Ausgang wird am tatsächlichen Verhalten der Oberfläche
           abgelesen: schliesst sich das Fenster oder erscheint eine
           Rückmeldung, gilt der Vorgang als abgeschlossen. So wird die
           bestehende Speicherlogik weder umgangen noch nachgebaut. */
        const toastEl = $('#toast');
        let settled = false;
        const finish = ok => {
          if (settled) return;
          settled = true;
          clearTimeout(guard);
          obs && obs.disconnect();
          btn.classList.remove('is-busy');
          if (ok) {
            btn.classList.add('is-done');
            btn.textContent = isPublish ? '✓ Im Shop veröffentlicht' : '✓ Gespeichert';
            setTimeout(() => {
              btn.classList.remove('is-done');
              btn.textContent = original;
              btn.style.minWidth = '';
            }, 2000);
          } else {
            btn.classList.add('is-failed');
            btn.textContent = original;
            setTimeout(() => { btn.classList.remove('is-failed'); btn.style.minWidth = ''; }, 1400);
          }
        };

        const obs = toastEl && window.MutationObserver
          ? new MutationObserver(() => {
              if (!toastEl.classList.contains('show')) return;
              finish(!toastEl.classList.contains('error'));
            })
          : null;
        obs && obs.observe(toastEl, { attributes: true, attributeFilter: ['class'] });

        // Sicherheitsnetz: nach 12 s wird der Ausgangszustand wiederhergestellt.
        const guard = setTimeout(() => finish(false), 12000);
      }, { capture: false });
    });
  }

  /* ===================================================================
     6 · TABELLEN AUF DEM HANDY
     Jede Zelle bekommt die Beschriftung ihrer Spalte. Das CSS blendet
     auf schmalen Geräten den Tabellenkopf aus und zeigt Karten.
     Der Tabelleninhalt selbst bleibt vollständig unverändert.
  =================================================================== */
  function labelTables(root = document) {
    $$('table.dataTable', root).forEach(table => {
      if (table.dataset.obLabels === '1') return;
      const heads = $$('thead th', table).map(th => (th.textContent || '').trim());
      if (!heads.length || heads.length > 9) return;   // sehr breite Tabellen scrollen weiter
      table.dataset.obLabels = '1';
      table.classList.add('obCards');
      $$('tbody tr', table).forEach(tr => {
        $$('td', tr).forEach((td, i) => {
          if (heads[i] && !td.hasAttribute('data-label')) td.setAttribute('data-label', heads[i]);
        });
      });
    });
  }

  /* ===================================================================
     7 · BEWEGUNG
  =================================================================== */
  const REVEAL_SEL = '.panel, .metric, .executiveKpiGrid > article, .obKpi, ' +
                     '.roleBanner, .quickCreateBar, .executiveHero, .obCard, .obNotice';
  let revealIO = null, chartIO = null;

  function armReveal(root = document) {
    if (reduced() || !('IntersectionObserver' in window)) {
      $$(REVEAL_SEL, root).forEach(el => el.classList.add('is-in'));
      $$(COUNT_SELECTORS, root).forEach(countUp);
      return;
    }
    if (!revealIO) {
      revealIO = new IntersectionObserver(entries => {
        entries.forEach(e => {
          if (!e.isIntersecting) return;
          const el = e.target;
          el.classList.add('is-in');
          revealIO.unobserve(el);
          $$(COUNT_SELECTORS, el).forEach(countUp);
          if (el.matches(COUNT_SELECTORS)) countUp(el);
          const done = () => el.classList.add('is-settled');
          el.addEventListener('transitionend', done, { once: true });
          setTimeout(done, 1200);
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -5% 0px' });
    }
    const byParent = new Map();
    $$(REVEAL_SEL, root).forEach(el => {
      if (el.classList.contains('obReveal')) return;
      el.classList.add('obReveal');
      const p = el.parentElement;
      if (!byParent.has(p)) byParent.set(p, 0);
      const i = byParent.get(p);
      byParent.set(p, i + 1);
      el.style.setProperty('--rh-reveal-delay', Math.min(i, 7) * 55 + 'ms');
      revealIO.observe(el);
    });
  }

  function armCharts(root = document) {
    const targets = $$('.obChart, .obBarFill', root).filter(el => !el.dataset.obArmed);
    if (!targets.length) return;
    if (reduced() || !('IntersectionObserver' in window)) {
      targets.forEach(el => { el.dataset.obArmed = '1'; el.classList.add('is-in'); });
      return;
    }
    if (!chartIO) {
      chartIO = new IntersectionObserver(entries => {
        entries.forEach(e => {
          if (!e.isIntersecting) return;
          e.target.classList.add('is-in');
          chartIO.unobserve(e.target);
        });
      }, { threshold: 0.25 });
    }
    targets.forEach(el => { el.dataset.obArmed = '1'; chartIO.observe(el); });
  }

  /* ===================================================================
     8 · NEAR-REALTIME
     Das OrgaBoard pollt bewusst NICHT. Es meldet nur eigene Änderungen
     weiter, damit geöffnete Shop-Tabs sofort reagieren.
  =================================================================== */
  function realtime() {
    if (!window.RH24Realtime) return;
    // Nach jedem erfolgreichen Schreibvorgang eine Meldung schicken.
    if (!window.__RH24_OB_FETCH_WRAPPED__ && typeof window.fetch === 'function') {
      window.__RH24_OB_FETCH_WRAPPED__ = true;
      const nativeFetch = window.fetch.bind(window);
      const WRITE = /^(product_save|product_quick_update|product_publish_repair|product_delete|content_save|settings_save|theme_save)$/;
      window.fetch = function (input, init) {
        let action = '';
        try {
          const url = typeof input === 'string' ? input : (input && input.url) || '';
          if (/api\.php/.test(url) && init && typeof init.body === 'string') {
            action = String((JSON.parse(init.body) || {}).action || '');
          }
        } catch (e) { action = ''; }
        const p = nativeFetch(input, init);
        if (action && WRITE.test(action)) {
          p.then(r => {
            if (r && r.ok) {
              try { window.RH24Realtime.announce(String(Date.now())); } catch (e) {}
            }
          }).catch(() => {});
        }
        return p;
      };
    }
  }

  /* ===================================================================
     BEOBACHTUNG DER ARBEITSFLÄCHE
     admin-v9911.js tauscht den Inhalt von #workspace bei jedem
     Bereichswechsel aus. Danach werden alle Ergänzungen neu gesetzt.
  =================================================================== */
  function watchWorkspace() {
    const ws = $('#workspace');
    if (!ws || !window.MutationObserver) return;
    let queued = false;
    const run = () => {
      queued = false;
      try { metricIcons(ws); }     catch (e) {}
      try { labelTables(ws); }     catch (e) {}
      try { bindOptimistic(ws); }  catch (e) {}
      try { armReveal(ws); }       catch (e) {}
      try { armCharts(ws); }       catch (e) {}
      // Diagramme nur auf der Startseite und nur, wenn das Cockpit steht.
      if ($('.obCockpit', ws) && !$('.obChartsMount', ws)) {
        revenuePanels(ws).catch(() => {});
      }
    };
    new MutationObserver(() => {
      if (queued) return;
      queued = true;
      requestAnimationFrame(run);
    }).observe(ws, { childList: true, subtree: true });
    run();
  }

  /* ===================================================================
     START
  =================================================================== */
  function boot() {
    try { navGlide(); }       catch (e) { console.warn('[RH24 OrgaBoard] Navigation:', e); }
    try { salesChannels(); }  catch (e) { console.warn('[RH24 OrgaBoard] Kanäle:', e); }
    try { mobileDrawer(); }   catch (e) { console.warn('[RH24 OrgaBoard] Schublade:', e); }
    try { topBar(); }         catch (e) { console.warn('[RH24 OrgaBoard] Kopfleiste:', e); }
    try { realtime(); }       catch (e) { console.warn('[RH24 OrgaBoard] Realtime:', e); }
    try { watchWorkspace(); } catch (e) { console.warn('[RH24 OrgaBoard] Arbeitsfläche:', e); }
    document.documentElement.dataset.obAtelier = VERSION;
  }

  const onMotionChange = () => {
    if (!reduced()) return;
    $$('.obReveal').forEach(el => el.classList.add('is-in', 'is-settled'));
    $$('.obChart, .obBarFill').forEach(el => el.classList.add('is-in'));
  };
  if (mqReduce.addEventListener) mqReduce.addEventListener('change', onMotionChange);
  else if (mqReduce.addListener) mqReduce.addListener(onMotionChange);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(boot, 0), { once: true });
  } else {
    setTimeout(boot, 0);
  }
})();
