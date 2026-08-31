/* =====================================================================
   RÄUCHERHAKEN24 · NEAR-REALTIME KATALOG-SYNC   (rh-realtime-2026.js)
   ---------------------------------------------------------------------
   Verbindet OrgaBoard und Shop. Wird auf BEIDEN Seiten geladen:

     · im Shop        → hört zu und aktualisiert die Produktdarstellung
     · im OrgaBoard   → meldet Änderungen (RH24Realtime.announce())

   VIER EBENEN, absteigend nach Geschwindigkeit
   ---------------------------------------------------------------------
   1. BroadcastChannel("rh24-shop-sync")
      Gleicher Browser, beliebig viele Tabs. Wirkt praktisch sofort.
   2. localStorage-Schlüssel "rh24-shop-catalog-refresh"
      Rückfallebene für Browser ohne BroadcastChannel und für Tabs,
      die den Kanal nicht erhalten haben.
   3. Serverseitige Katalogrevision (shop-catalog-version.php)
      Deckt andere Geräte und andere Browser ab. Es wird ausschliesslich
      eine winzige Versionsantwort geholt – keine Produktdaten.
   4. Fokus / Sichtbarkeitswechsel
      Sofortige Prüfung, sobald ein Tab wieder in den Vordergrund kommt.

   Die vollständigen Produktdaten werden NUR geladen, wenn sich die
   Revision tatsächlich geändert hat. Es gibt keinen Seitenreload.

   WARUM KEINE WEBSOCKETS UND KEIN SSE
   ---------------------------------------------------------------------
   Klassisches PHP-Shared-Hosting (STRATO) hält keine dauerhaft
   laufenden Prozesse bereit und begrenzt gleichzeitige Verbindungen je
   Konto. Ein WebSocket-Server oder ein dauerhaft offener SSE-Strom wäre
   dort nicht verlässlich und würde im Zweifel Verbindungen blockieren.
   Die hier gewählte Lösung erreicht dasselbe Ziel (1–2 Sekunden) mit
   einer Antwort von rund 120 Byte und ohne offene Verbindung.
   Sollte SSE auf dem Zielhosting nachweislich stabil laufen, lässt es
   sich über RH24Realtime.attachStream() ergänzen, ohne dass an dieser
   Datei etwas geändert werden muss.
   ===================================================================== */
(() => {
  'use strict';
  if (window.RH24Realtime) return;

  const VERSION   = '2026.3';
  const CHANNEL   = 'rh24-shop-sync';
  const LS_KEY    = 'rh24-shop-catalog-refresh';
  const LS_REV    = 'rh24-shop-catalog-revision';
  const EVENT     = 'rh24:catalog-changed';

  /* Der Endpunkt liegt im Web-Wurzelverzeichnis. Aus dem OrgaBoard
     heraus (Unterordner /orgaboard/) wird eine Ebene höher gezeigt. */
  const BASE = /\/orgaboard\//i.test(location.pathname) ? '../' : '';
  const ENDPOINT = BASE + 'shop-catalog-version.php';

  /* Taktung ------------------------------------------------------------
     aktiv:      1,5 s  – erfüllt die Vorgabe von 1–2 Sekunden
     im Hintergrund: gar nicht (Polling wird angehalten)
     nach Fehlern: schrittweise langsamer bis maximal 30 s          */
  const POLL_ACTIVE_MS = 1500;
  const POLL_IDLE_MS   = 15000;   // Seite offen, aber lange keine Eingabe
  const POLL_MAX_MS    = 30000;
  const IDLE_AFTER_MS  = 120000;  // ab 2 Min ohne Interaktion: ruhiger takten

  let revision   = null;
  let timer      = 0;
  let inFlight   = false;
  let failures   = 0;
  let lastAction = Date.now();
  let started    = false;
  const listeners = new Set();

  /* ===================================================================
     KANAL
  =================================================================== */
  let channel = null;
  try {
    if ('BroadcastChannel' in window) channel = new BroadcastChannel(CHANNEL);
  } catch (e) { channel = null; }

  if (channel) {
    channel.onmessage = ev => {
      const msg = ev && ev.data;
      if (!msg || msg.type !== 'catalog-updated') return;
      // Eine fremde Revision zählt als Hinweis, nicht als Wahrheit:
      // der Server bleibt die einzige verbindliche Quelle.
      apply(msg.revision || null, 'broadcast');
    };
  }

  window.addEventListener('storage', e => {
    if (e.key === LS_KEY) apply(readStoredRevision(), 'storage');
  });

  function readStoredRevision() {
    try { return localStorage.getItem(LS_REV) || null; } catch (e) { return null; }
  }

  /* ===================================================================
     MELDEN  (wird vom OrgaBoard nach Speichern/Veröffentlichen genutzt)
     Es werden bewusst KEINE Inhalte übertragen – nur ein Hinweis und
     eine Revisionskennung. Keine Preise, keine Namen, keine Kundendaten,
     keine Zugangsdaten.
  =================================================================== */
  function announce(rev) {
    const payload = {
      type: 'catalog-updated',
      revision: rev ? String(rev) : null,
      timestamp: Date.now()
    };
    try { channel && channel.postMessage(payload); } catch (e) {}
    try {
      if (payload.revision) localStorage.setItem(LS_REV, payload.revision);
      localStorage.setItem(LS_KEY, String(payload.timestamp));
    } catch (e) {}
    // Auch im eigenen Tab weitergeben (BroadcastChannel spricht nicht mit sich selbst).
    try { window.dispatchEvent(new CustomEvent(EVENT, { detail: payload })); } catch (e) {}
  }

  /* ===================================================================
     ANWENDEN
     Nur wenn die Revision wirklich neu ist, wird der Katalog gelesen.
  =================================================================== */
  function apply(nextRevision, source) {
    if (nextRevision && revision && String(nextRevision) === String(revision)) return false;
    if (nextRevision) {
      revision = String(nextRevision);
      try { localStorage.setItem(LS_REV, revision); } catch (e) {}
    }
    notify(source);
    return true;
  }

  function notify(source) {
    const detail = { revision, source, version: VERSION, timestamp: Date.now() };
    listeners.forEach(fn => { try { fn(detail); } catch (e) { console.warn('[RH24 Realtime]', e); } });
    try { window.dispatchEvent(new CustomEvent(EVENT, { detail })); } catch (e) {}
  }

  /* ===================================================================
     ABFRAGE
  =================================================================== */
  async function check(force) {
    if (inFlight) return;
    if (document.hidden && !force) return;
    inFlight = true;
    try {
      const r = await fetch(ENDPOINT + '?_=' + Date.now(), {
        cache: 'no-store',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      const d = await r.json();
      if (!d || d.ok !== true || !d.revision) throw new Error('Ungültige Antwort');

      failures = 0;
      const first = revision === null;
      if (first) {
        // Erster Aufruf: Stand nur merken, nicht neu laden – die Seite
        // hat ihre Produktdaten beim Laden bereits frisch geholt.
        revision = String(d.revision);
        try { localStorage.setItem(LS_REV, revision); } catch (e) {}
      } else if (String(d.revision) !== String(revision)) {
        revision = String(d.revision);
        try { localStorage.setItem(LS_REV, revision); } catch (e) {}
        notify('poll');
      }
    } catch (e) {
      failures = Math.min(failures + 1, 6);
    } finally {
      inFlight = false;
    }
  }

  function nextDelay() {
    if (failures) return Math.min(POLL_ACTIVE_MS * Math.pow(2, failures), POLL_MAX_MS);
    if (Date.now() - lastAction > IDLE_AFTER_MS) return POLL_IDLE_MS;
    return POLL_ACTIVE_MS;
  }

  function schedule() {
    clearTimeout(timer);
    if (document.hidden) return;                 // Im Hintergrund wird nicht gepollt
    timer = setTimeout(async () => {
      await check(false);
      schedule();
    }, nextDelay());
  }

  function wake() {
    lastAction = Date.now();
    failures = 0;
    check(true).finally(schedule);
  }

  /* ===================================================================
     PROGRESSIVE ERWEITERUNG · SSE
     Bleibt bewusst ungenutzt, solange nicht belegt ist, dass das
     Hosting dauerhafte Verbindungen verlässlich trägt. Wer es aktiviert,
     behält das Polling als Rückfallebene automatisch bei.
  =================================================================== */
  function attachStream(url) {
    if (!('EventSource' in window) || !url) return false;
    try {
      const es = new EventSource(url);
      es.addEventListener('catalog', ev => {
        try { apply(JSON.parse(ev.data || '{}').revision, 'sse'); } catch (e) {}
      });
      es.onerror = () => { es.close(); schedule(); };   // Rückfall auf Polling
      return true;
    } catch (e) { return false; }
  }

  /* ===================================================================
     START
  =================================================================== */
  function start() {
    if (started) return;
    started = true;
    revision = readStoredRevision();
    check(true).finally(schedule);

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) { clearTimeout(timer); return; }
      wake();                                    // sofort prüfen, sobald sichtbar
    });
    window.addEventListener('focus', wake);
    window.addEventListener('pageshow', wake);
    ['pointerdown', 'keydown', 'scroll'].forEach(t =>
      window.addEventListener(t, () => { lastAction = Date.now(); }, { passive: true })
    );
    window.addEventListener('beforeunload', () => clearTimeout(timer));
  }

  window.RH24Realtime = {
    version: VERSION,
    start,
    announce,
    attachStream,
    checkNow: () => check(true),
    get revision() { return revision; },
    /** Rückgabewert: Funktion zum Abmelden. */
    onChange(fn) { listeners.add(fn); return () => listeners.delete(fn); }
  };

  // Im Shop sofort starten. Im OrgaBoard übernimmt das die dortige
  // Oberflächenschicht, damit dort nicht unnötig gepollt wird.
  if (!/\/orgaboard\//i.test(location.pathname)) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
      start();
    }
  }
})();
