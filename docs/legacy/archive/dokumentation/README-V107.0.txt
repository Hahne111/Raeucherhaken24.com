RÄUCHERHAKEN24 V107.0 · SHOP-PUBLISH-FIX

Behoben:
- Neue, im Orgaboard veröffentlichte Räucherhaken werden im eigentlichen Räucherhaken-Raster des Shops ergänzt statt nur in einer generischen Zusatzsektion.
- Nach Klick auf „Veröffentlichen“ prüft das Orgaboard den öffentlichen shop-products.php-Katalog. Eine Erfolgsmeldung erscheint erst, wenn der Artikel dort wirklich vorhanden ist.
- Bereits geöffnete Shop-Tabs erhalten ein Refresh-Signal; zusätzlich bleibt Fokus-/Sichtbarkeits-Refresh aktiv.
- Shop-Daten werden mit no-store und Zeitstempel neu geladen, damit kein veralteter Produktkatalog angezeigt wird.
- Cache-Busting für Shop-Sync und Orgaboard auf V107.0.
- Shopdesign bleibt unverändert; geändert wurde nur die Daten-/Veröffentlichungssynchronisierung.

Upload:
Direktupdate in das bestehende Webroot hochladen und vorhandene Dateien ersetzen. Den Ordner uploads/products nicht löschen. Danach Orgaboard und Shop jeweils einmal hart neu laden.
