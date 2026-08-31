RÄUCHERHAKEN24 V106.1 · ORGABOARD → SHOP LIVE-SYNCHRONISIERUNG

Was wurde geändert?
- Orgaboard-/MariaDB-Produktstamm ist die führende Quelle für Shop-Inhalte.
- Öffentliche Produktkarten übernehmen live: Bild, Name, Preis/Aktionspreis, Kurzbeschreibung, Artikelnummer, Einheit, Gewichte, Beliebt/Neu/Angebot und Sichtbarkeit.
- Die bestehenden festen Shoplayouts bleiben erhalten.
- Bekannte individuelle Produktseiten werden weiter benutzt; ihre Inhalte werden nach Laden mit den aktuellen Produktdaten synchronisiert.
- Hochgeladene Produktbilder werden über image_path aus der Datenbank übernommen.
- Bild-URLs erhalten einen updated_at-basierten Versionsparameter gegen Browsercache.
- Beim Zurückwechseln zum Shop-Tab wird der Produktkatalog automatisch erneut geprüft.
- „Beliebte Produkte“ auf der Startseite kommt aus is_popular im Orgaboard; falls noch nichts markiert ist, greift eine sichere Standardauswahl.
- Produkte mit shop_visible=0/status!=active werden nicht mehr in statischen Shopkarten angezeigt.

Wichtig beim Upload:
- Direktupdate in das bestehende Webroot hochladen und vorhandene Dateien ersetzen.
- Den Ordner uploads/products NICHT löschen. Dort liegen die über das Orgaboard hochgeladenen Produktbilder.
- Danach Shop einmal hart neu laden. Künftige Produktänderungen kommen aus der Datenbank und benötigen kein erneutes Code-Update.
