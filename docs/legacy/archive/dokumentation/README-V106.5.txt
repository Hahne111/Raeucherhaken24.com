RÄUCHERHAKEN24 · V106.5
KI-FIX + BUTTON-ZEILE + SMOKY TEXTASSISTENT
Stand: 30.08.2026

ÄNDERUNGEN
1. Die KI-Aktionsbuttons stehen jetzt bewusst eine komplette Zeile unter „Zielgruppe“ und „Ton“.
2. „KI verbinden / testen“ bleibt sichtbar und wird nicht mehr unübersichtlich in das Raster gedrückt.
3. Der KI-Status wird beim Öffnen des Produkt-Baukastens automatisch geprüft.
4. „Verbindung testen“ führt jetzt einen echten Minimaltest über die OpenAI Responses API aus.
   Dadurch werden nicht nur Schlüssel und Modellname, sondern auch Modellzugriff und API-Abrechnung getestet.
5. Verständlichere Fehler:
   - ungültiger/widerrufener API-Schlüssel
   - fehlender Modellzugriff
   - API-Abrechnung/Guthaben oder Nutzungslimit
   - fehlendes PHP-cURL
   - nicht schreibbarer /orgaboard/private-Ordner
6. Die Produktoptimierung nutzt Structured Outputs (JSON-Schema), damit die KI-Antwort stabil als
   Kurzbeschreibung, Beschreibung, Merkmale und SEO-Felder verarbeitet werden kann.
7. Smokys Textassistent aus V106.4 bleibt vollständig erhalten.
8. Shopdesign und Produktdarstellung bleiben unverändert.

NACH DEM UPDATE
- Browser/Orgaboard neu laden.
- Produkt öffnen -> Schritt 3 „Inhalt“.
- „KI verbinden / testen“ öffnen.
- Falls noch kein sicherer Schlüssel gespeichert ist: neuen Schlüssel speichern.
- „Verbindung testen“ anklicken.
- Nur wenn dort „OpenAI Responses API funktioniert“ erscheint, die Textoptimierung verwenden.

WICHTIG
Ein API-Schlüssel, der bereits öffentlich oder in einem Chat geteilt wurde, darf nicht weiterverwendet werden.
Der Schlüssel gehört ausschließlich in die serverseitige geschützte Orgaboard-Konfiguration.
