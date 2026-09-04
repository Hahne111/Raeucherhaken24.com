(() => {
  const root = document.documentElement;
  root.classList.add('rh82Boot', 'rh-atelier', 'rh-atelier-js');

  /* V2026.5 · Menü-Stabilitätsfix
     Problem: Das Mega-Menü lag im Desktop-Modus mit einem kleinen
     Abstand unter dem Auslöser. Beim Überfahren dieses Zwischenraums
     verließ der Mauszeiger kurz die Menügruppe; zusammen mit der alten
     display/animation-Kombination konnte das Menü dadurch schließen
     und sichtbar flackern.

     Dieser gezielte Override betrifft ausschließlich die Hauptnavigation:
       · kein toter Zwischenraum zwischen Button und Mega-Menü
       · Mega-Menü bleibt im DOM; Sichtbarkeit nur über opacity/visibility
       · keine konkurrierende Keyframe-Animation beim Öffnen
     Mobile Darstellung und alle Shop-/Produktfunktionen bleiben unberührt.
  */
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
    `;
    document.head.appendChild(style);
  }

  window.setTimeout(() => root.classList.remove('rh82Boot'), 2500);
})();
