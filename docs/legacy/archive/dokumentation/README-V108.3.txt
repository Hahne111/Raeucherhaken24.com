V108.3 · PRODUKT-TRANSFER – EIN QUELLSTATUS

Nur die Veröffentlichungs-/Transferlogik wurde geändert. Design, Karten, Buttons, Footer, Icons und Produktdarstellung bleiben unverändert.

Kernfix:
- published_at in products ist jetzt die einzige verbindliche Veröffentlichungsquelle.
- Keine Abhängigkeit mehr von der zusätzlichen Tabelle product_publications.
- Dadurch funktioniert Veröffentlichung auch auf Webhosting, wenn CREATE TABLE eingeschränkt ist.
- Veröffentlichen setzt published_at + aktiv + online.
- Entwurf/Aus-Shop-nehmen löscht published_at.
- shop-products.php liest direkt aus products.
- Art.-Nr. 20005 / Dreifachdorn wird gezielt wieder auf veröffentlicht gesetzt, sofern der Datensatz vorhanden ist.
- Bei erneutem Fehler zeigt das Orgaboard jetzt den echten Serverstatus (aktiv/offline/published_at), statt nur eine allgemeine Meldung.
