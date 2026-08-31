/* =====================================================================
   RÄUCHERHAKEN24 · SMOKY – DIGITALER RÄUCHERBERATER (Oberfläche)
   ---------------------------------------------------------------------
   · Der Assistent ist freiwillig: keine Popups, keine Benachrichtigungen,
     kein automatisches Öffnen. Der Kunde entscheidet.
   · Fachwissen und Produktempfehlungen kommen vom eigenen Backend
     (smoky-api.php). Im Frontend liegen weder Schlüssel noch Geheimnisse.
   · Empfohlen werden ausschliesslich Produkte, die das Backend aus dem
     echten Shop-Katalog liefert – inklusive Originalbild und Originallink.
   ===================================================================== */
(() => {
  'use strict';
  if (window.__RH24_SMOKY_2026__) return;
  window.__RH24_SMOKY_2026__ = true;

  const VERSION   = '2026.1';
  const API       = 'smoky-api.php';
  const SLOT_KEY  = 'rh24_smoky_slots';
  // Bewusst das kleine Markenmotiv: Es wird bereits von der Kopfzeile
  // geladen (220 px, rund 19 KB) und reicht für 32–42 px Darstellung
  // vollkommen aus. Das grosse Maskottchenbild (1254 px, rund 1,8 MB)
  // würde sonst auf jeder Seite zusätzlich geladen.
  const AVATAR    = 'assets/smoky-logo-v1041.jpg';
  const MAX_LEN   = 600;

  const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const esc = s => String(s ?? '').replace(/[&<>"']/g, m =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
  const euro = n => Number(n || 0).toLocaleString('de-DE', { style: 'currency', currency: 'EUR' });

  /* -------------------------------------------------- Gesprächszustand */
  const state = {
    open: false,
    busy: false,
    slots: {},
    lastQuestion: '',
    history: [],
  };
  try {
    const raw = sessionStorage.getItem(SLOT_KEY);
    if (raw) state.slots = JSON.parse(raw) || {};
  } catch (e) { /* Speicher nicht verfügbar – der Chat läuft trotzdem */ }
  const persist = () => {
    try { sessionStorage.setItem(SLOT_KEY, JSON.stringify(state.slots)); } catch (e) {}
  };

  const QUICK = [
    { label: 'Fisch räuchern',     send: 'Ich möchte Fisch räuchern. Was muss ich beachten?' },
    { label: 'Fleisch räuchern',   send: 'Ich möchte Fleisch räuchern. Was muss ich beachten?' },
    { label: 'Räucherhaken finden',send: 'Welcher Räucherhaken passt zu meinem Räuchergut?' },
    { label: 'Problem beim Räuchern', send: 'Ich habe ein Problem beim Räuchern.' },
    { label: 'Räucherholz auswählen', send: 'Welches Räucherholz soll ich nehmen?' },
    { label: 'Anfänger-Anleitung', send: 'Ich bin Anfänger. Gib mir bitte eine Schritt-für-Schritt-Anleitung fürs Forelle räuchern.' },
  ];

  /* ------------------------------------------------------------- DOM */
  let launcher, panel, body, form, input, sendBtn, chipsRow, toLatest;

  function build() {
    // Startknopf – landet in der gemeinsamen Aktionsleiste unten rechts
    launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'smokyLauncher';
    launcher.setAttribute('aria-expanded', 'false');
    launcher.setAttribute('aria-controls', 'smokyPanel');
    launcher.setAttribute('aria-label', 'Smoky, den Räucherberater, öffnen');
    launcher.innerHTML =
      `<img src="${AVATAR}" alt="" width="32" height="32" loading="lazy" decoding="async">` +
      `<span class="sl-text"><span>Smoky fragen</span><small>Räucherberater</small></span>`;
    (document.querySelector('.rhFabStack') || document.body).appendChild(launcher);

    panel = document.createElement('aside');
    panel.className = 'smokyPanel';
    panel.id = 'smokyPanel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-label', 'Smoky – Räucherberater');
    panel.hidden = false;
    panel.innerHTML = `
      <div class="smokyHead">
        <img src="${AVATAR}" alt="" width="42" height="42" loading="lazy" decoding="async">
        <div class="smokyHeadText">
          <b>Smoky</b>
          <span><i class="smokyDot" aria-hidden="true"></i>Räucherberater · antwortet sofort</span>
        </div>
        <button type="button" class="smokyHeadBtn smokyReset" aria-label="Gespräch neu starten" title="Gespräch neu starten">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
        </button>
        <button type="button" class="smokyHeadBtn smokyClose" aria-label="Berater schließen">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="smokyBody" id="smokyBody" role="log" aria-live="polite" aria-relevant="additions"></div>
      <button type="button" class="smokyToLatest" aria-label="Zur neuesten Nachricht springen">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
        Neueste Nachricht
      </button>
      <div class="smokyChips" id="smokyChips" role="group" aria-label="Schnellstart"></div>
      <div class="smokyFoot">
        <form class="smokyForm" id="smokyForm" novalidate>
          <textarea class="smokyInput" id="smokyInput" rows="1" maxlength="${MAX_LEN}"
            placeholder="Was möchtest du räuchern?" aria-label="Frage an Smoky" autocomplete="off"></textarea>
          <button type="submit" class="smokySend" id="smokySend" aria-label="Frage senden" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12h14"/><path d="M12.5 5.5 19 12l-6.5 6.5"/></svg>
          </button>
        </form>
        <small class="smokyHint">Enter senden · Umschalt + Enter neue Zeile</small>
      </div>`;
    document.body.appendChild(panel);

    body     = panel.querySelector('#smokyBody');
    form     = panel.querySelector('#smokyForm');
    input    = panel.querySelector('#smokyInput');
    sendBtn  = panel.querySelector('#smokySend');
    chipsRow = panel.querySelector('#smokyChips');
    toLatest = panel.querySelector('.smokyToLatest');

    launcher.addEventListener('click', () => toggle());
    panel.querySelector('.smokyClose').addEventListener('click', () => toggle(false));
    panel.querySelector('.smokyReset').addEventListener('click', resetChat);
    toLatest.addEventListener('click', () => scrollToEnd(true));

    form.addEventListener('submit', e => { e.preventDefault(); submit(); });
    input.addEventListener('input', () => {
      autoGrow();
      sendBtn.disabled = !input.value.trim() || state.busy;
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submit(); }
    });
    body.addEventListener('scroll', () => {
      const nearBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 90;
      toLatest.classList.toggle('isVisible', !nearBottom);
    }, { passive: true });

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && state.open) toggle(false);
    });

    // Mobile: das Panel folgt der eingeblendeten Tastatur
    if (window.visualViewport) {
      const fit = () => {
        if (!state.open || window.innerWidth > 640) { panel.style.removeProperty('height'); return; }
        panel.style.height = window.visualViewport.height + 'px';
      };
      window.visualViewport.addEventListener('resize', fit);
      window.visualViewport.addEventListener('scroll', fit);
    }

    renderChips(QUICK.map(q => q.label), QUICK);
    greet();
  }

  function autoGrow() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
  }

  /* ------------------------------------------------------- Öffnen/Zu */
  function toggle(force) {
    state.open = (typeof force === 'boolean') ? force : !state.open;
    panel.classList.toggle('isOpen', state.open);
    launcher.setAttribute('aria-expanded', String(state.open));
    launcher.querySelector('.sl-text > span').textContent = state.open ? 'Smoky schließen' : 'Smoky fragen';
    document.body.classList.toggle('smokyLocked', state.open && window.innerWidth <= 640);
    if (state.open) {
      scrollToEnd();
      setTimeout(() => { if (window.innerWidth > 640) input.focus(); }, 320);
    } else {
      launcher.focus();
    }
  }

  /* --------------------------------------------------------- Ausgabe */
  function bubble(who, html) {
    const wrap = document.createElement('div');
    wrap.className = 'smokyMsg ' + who;
    const b = document.createElement('div');
    b.className = 'smokyBubble';
    b.innerHTML = html;
    wrap.appendChild(b);
    body.appendChild(wrap);
    // Eigene Nachrichten ans Ende; lange Antworten an ihren Anfang, damit
    // man von oben zu lesen beginnt statt am Ende zu landen.
    if (who === 'user') scrollToEnd(true);
    else scrollToMessage(wrap);
    return wrap;
  }

  function scrollToMessage(el) {
    requestAnimationFrame(() => {
      const target = Math.max(0, el.offsetTop - 12);
      const fits = el.offsetHeight <= body.clientHeight - 24;
      if (fits) body.scrollTop = body.scrollHeight;
      else body.scrollTop = target;
      toLatest.classList.toggle('isVisible',
        body.scrollHeight - body.scrollTop - body.clientHeight > 90);
    });
  }

  function scrollToEnd(force) {
    const doIt = () => {
      body.scrollTop = body.scrollHeight;
      toLatest.classList.remove('isVisible');
    };
    if (force || body.scrollHeight - body.scrollTop - body.clientHeight < 260) {
      requestAnimationFrame(doIt);
    }
  }

  function greet() {
    bubble('bot',
      `<h4>Moin, ich bin Smoky.</h4>
       <p>Ich berate dich rund ums Räuchern: Fisch und Fleisch, Pökeln und Lake, Holzarten, Temperaturen, den passenden Räucherhaken und typische Probleme.</p>
       <p>Sag mir am besten kurz, was du vorhast – zum Beispiel <em>„Forelle, ca. 400 g, heiß räuchern“</em>.</p>`);
  }

  function renderChips(labels, defs) {
    chipsRow.innerHTML = '';
    (labels || []).forEach((label, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'smokyChip';
      b.textContent = label;
      const send = defs && defs[i] ? defs[i].send : label;
      b.addEventListener('click', () => ask(send));
      chipsRow.appendChild(b);
    });
    chipsRow.style.display = labels && labels.length ? '' : 'none';
  }

  /* ------------------------------------------- Antwort in HTML gießen */
  function renderAnswer(data) {
    let html = '';
    const causes = [];
    const fixes  = [];

    (data.blocks || []).forEach(blk => {
      const v = blk.v;
      switch (blk.t) {
        case 'h':    html += `<h4>${esc(v)}</h4>`; break;
        case 'p':    html += `<p>${esc(v)}</p>`; break;
        case 'note': html += `<div class="smokyNote">${esc(v)}</div>`; break;
        case 'warn': html += `<div class="smokyWarn"><span>${esc(v)}</span></div>`; break;
        case 'ol':
          html += '<ol>' + v.map(x => `<li>${esc(x)}</li>`).join('') + '</ol>';
          break;
        case 'ul':
          html += '<ul>' + v.map(x => `<li>${esc(x)}</li>`).join('') + '</ul>';
          break;
        case 'ul_cause': causes.push(...v); break;
        case 'ul_fix':   fixes.push(...v);  break;
        default:
          if (Array.isArray(v)) html += '<ul>' + v.map(x => `<li>${esc(x)}</li>`).join('') + '</ul>';
          else html += `<p>${esc(v)}</p>`;
      }
    });
    if (causes.length) {
      html += `<div class="smokyList"><b>Mögliche Ursachen</b><ul>${causes.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`;
    }
    if (fixes.length) {
      html += `<div class="smokyList"><b>So löst du es</b><ul>${fixes.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`;
    }

    // Produktempfehlungen – ausschliesslich echte Katalogdaten
    if (Array.isArray(data.products) && data.products.length) {
      html += '<div class="smokyProducts">';
      data.products.forEach(p => {
        const price = Number(p.price) > 0
          ? `<span class="smokyProdPrice">${esc(euro(p.price))}${p.unit ? `<small>/ ${esc(p.unit)}</small>` : ''}</span>` : '';
        const cart = Number(p.price) > 0
          ? `<button type="button" class="smokyProdCart" data-add="${esc(p.id)}">In den Warenkorb</button>` : '';
        const img = p.img
          ? `<img src="${esc(p.img)}" alt="${esc(p.name)}" width="62" height="62" loading="lazy" decoding="async">`
          : '<span style="width:62px;height:62px"></span>';
        html += `<div class="smokyProduct">
            ${img}
            <div class="smokyProdInfo">
              <a class="smokyProdName" href="${esc(p.url)}">${esc(p.name)}</a>
              ${p.why ? `<span class="smokyProdWhy">${esc(p.why)}</span>` : ''}
              <span class="smokyProdFoot">${price}${cart}</span>
            </div>
          </div>`;
      });
      html += '</div>';
    }

    // Gezielte Rückfrage – nur wenn wirklich etwas fehlt
    if (data.followup && Array.isArray(data.followup.opts) && data.followup.opts.length) {
      html += `<div class="smokyFollow"><b>${esc(data.followup.q)}</b><div class="smokyOpts">` +
        data.followup.opts.map(o =>
          `<button type="button" class="smokyOpt" data-send="${esc(o.send)}">${esc(o.label)}</button>`).join('') +
        '</div></div>';
    }
    return html;
  }

  function wireBubble(el) {
    el.querySelectorAll('[data-send]').forEach(b => {
      b.addEventListener('click', () => ask(b.dataset.send));
    });
    el.querySelectorAll('[data-add]').forEach(b => {
      b.addEventListener('click', () => {
        if (typeof window.addToCart === 'function') {
          window.addToCart(b.dataset.add);
          b.textContent = 'Hinzugefügt ✓';
          b.classList.add('isDone');
          b.disabled = true;
        } else {
          // Ohne Warenkorb-Modul führt der Weg über die Produktseite.
          const link = b.closest('.smokyProduct')?.querySelector('.smokyProdName');
          if (link) location.href = link.getAttribute('href');
        }
      });
    });
  }

  /* ----------------------------------------------------------- Fragen */
  async function ask(text) {
    const q = String(text || '').trim();
    if (!q || state.busy) return;
    if (!state.open) toggle(true);

    state.lastQuestion = q;
    bubble('user', esc(q).replace(/\n/g, '<br>'));
    state.history.push({ role: 'user', text: q });
    input.value = '';
    autoGrow();
    setBusy(true);

    const typing = document.createElement('div');
    typing.className = 'smokyMsg bot';
    typing.innerHTML = '<div class="smokyBubble" style="padding:0"><div class="smokyTyping" aria-label="Smoky schreibt"><i></i><i></i><i></i></div></div>';
    body.appendChild(typing);
    scrollToEnd();

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 15000);

    try {
      const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        cache: 'no-store',
        credentials: 'same-origin',
        signal: controller.signal,
        body: JSON.stringify({
          question: q,
          page: (location.pathname.split('/').pop() || 'index.html'),
          slots: state.slots,
          history: state.history.slice(-8),
        }),
      });
      clearTimeout(timer);

      let data = {};
      try { data = await res.json(); } catch (e) { data = {}; }
      typing.remove();

      if (!res.ok || !data.ok) {
        showError(data.error || 'Smoky konnte gerade nicht antworten.');
        return;
      }

      state.slots = data.slots || state.slots;
      persist();

      const el = bubble('bot', renderAnswer(data));
      wireBubble(el);
      state.history.push({ role: 'bot', text: (data.answer || '').slice(0, 400) });
      renderChips(data.chips && data.chips.length ? data.chips : QUICK.map(x => x.label),
                  data.chips && data.chips.length ? null : QUICK);
    } catch (err) {
      clearTimeout(timer);
      typing.remove();
      showError(err && err.name === 'AbortError'
        ? 'Die Antwort hat zu lange gedauert.'
        : 'Es gab ein Verbindungsproblem.');
    } finally {
      setBusy(false);
    }
  }

  function showError(msg) {
    const el = bubble('bot',
      `<div class="smokyError">${esc(msg)} Du kannst es direkt noch einmal versuchen.
         <br><button type="button" data-retry>Erneut versuchen</button></div>`);
    el.querySelector('[data-retry]')?.addEventListener('click', () => {
      const again = state.lastQuestion;
      el.remove();
      if (again) ask(again);
    });
  }

  function setBusy(v) {
    state.busy = v;
    sendBtn.disabled = v || !input.value.trim();
    input.setAttribute('aria-busy', String(v));
  }

  function submit() {
    const v = input.value.trim();
    if (v) ask(v);
  }

  function resetChat() {
    state.slots = {};
    state.history = [];
    state.lastQuestion = '';
    persist();
    body.innerHTML = '';
    greet();
    renderChips(QUICK.map(q => q.label), QUICK);
    input.focus();
  }

  /* ------------------------------------------------------------ Start */
  function boot() {
    if (document.querySelector('.smokyPanel')) return;
    build();

    // Öffentliche, stabile Schnittstelle. Die alten Namen bleiben belegt,
    // damit vorhandene Schaltflächen weiterhin funktionieren.
    window.RH24Smoky = {
      version: VERSION,
      open: () => toggle(true),
      close: () => toggle(false),
      toggle,
      ask,
    };
    window.toggleAI = force => toggle(typeof force === 'boolean' ? force : undefined);
    window.ask = preset => { if (preset) ask(preset); else submit(); };
    window.openSmoky = () => toggle(true);
  }

  // Mit defer geladene Skripte laufen vor DOMContentLoaded. Es wird bewusst
  // gewartet, damit die gemeinsame Aktionsleiste bereits vorhanden ist und
  // der Startknopf dort einsortiert werden kann.
  if (document.readyState === 'complete') {
    setTimeout(boot, 0);
  } else {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  }
})();
