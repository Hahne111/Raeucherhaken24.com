# Räucherhaken24.com

Der bestehende Shop wurde als klar getrenntes JavaScript-Webprojekt für GitHub Codespaces eingerichtet. Gestaltung, Inhalte, Produktdaten, Bilddateien und die vorhandenen PHP-Funktionen bleiben erhalten. Die 35 öffentlichen HTML-Seiten enthalten keine Inline-Styles, Inline-Skripte oder Inline-Eventhandler mehr.

## Schnellstart in GitHub Codespaces

1. Repository in einem Codespace öffnen.
2. Die Einrichtung installiert Node.js, PHP und die Projektpakete automatisch.
3. Im Codespace-Terminal `npm run dev` ausführen. Die Vorschau öffnet sich auf Port `5173`.

Falls der Start manuell nötig ist:

```bash
npm install
npm run dev
```

Die Website anschließend über den weitergeleiteten Port `5173` öffnen. Der interne PHP-Port `8000` wird nur vom Vite-Entwicklungsserver verwendet.

## Nützliche Befehle

```bash
npm run dev       # Vite-Frontend und PHP-Backend gemeinsam starten
npm run check     # HTML, JavaScript-Syntax und lokale Verweise prüfen
npm run check:php # Syntax aller PHP-Dateien prüfen
npm run build     # vollständiges Paket in dist/ erzeugen
npm test          # Prüfung und Build nacheinander ausführen
npm run preview   # statische Build-Ausgabe auf Port 4173 ansehen
```

Für die lokale Entwicklung außerhalb von Codespaces werden Node.js ab 22.12 und PHP 8.3 benötigt.

## Projektstruktur

```text
.
├── .devcontainer/       # reproduzierbare Codespaces-Umgebung
├── .github/workflows/   # automatische Prüfung bei Push und Pull Request
├── docs/legacy/         # ursprüngliche Prüfberichte und archivierte Altdateien
├── public/              # Bilder, Favicon und öffentlich geladene JSON-Daten
├── scripts/             # Build- und Qualitätsprüfungen
├── server/public/       # PHP-Endpunkte, Uploadbereiche und Orgaboard
├── src/scripts/         # Browser-JavaScript, Kernlogik und Seitenskripte
├── src/styles/          # globale, komponentenbezogene und seitenspezifische Styles
├── *.html               # schlanke Mehrseiten-Einstiege mit bestehenden URLs
├── package.json         # npm-Skripte und Entwicklungswerkzeuge
└── vite.config.js       # Mehrseiten-Build und PHP-Weiterleitung
```

## Technischer Aufbau

- Vite übernimmt Entwicklungsserver und Mehrseiten-Build.
- PHP bleibt für Shop-Endpunkte und das Orgaboard erhalten; eine riskante Neuimplementierung der Geschäftslogik wurde bewusst vermieden.
- Frontend-Skripte und Styles liegen getrennt unter `src/`.
- Bilder und JSON-Inhalte liegen unter `public/` und behalten ihre bisherigen Browser-URLs.
- `npm run build` erzeugt `dist/` inklusive PHP-Backend. Zum produktiven Betrieb muss dieser Ordner auf einem PHP-fähigen Webserver bereitgestellt werden; `vite preview` zeigt nur den statischen Teil.
- Die ursprünglichen Versions- und Prüfberichte befinden sich unter `docs/legacy/`.
