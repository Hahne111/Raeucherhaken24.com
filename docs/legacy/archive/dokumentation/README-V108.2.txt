V108.2 · VERÖFFENTLICHUNGS-REGISTER
Nur die Veröffentlichungslogik wurde geändert. Design, Produktkarten, Buttons, Footer und bestehende Shopgestaltung bleiben unverändert.

Kernfix:
- product_publications ist ab jetzt die verbindliche Liste veröffentlichter Produkte.
- Veröffentlichen trägt den Artikel dort ein.
- Entwurf/Aus-Shop-nehmen entfernt ihn dort.
- shop-products.php liest diese Liste direkt und nicht mehr zwei anfällige Flags als alleinige Quelle.
- Bestehende Online-Artikel werden automatisch übernommen.
- Art.-Nr. 20005 wird, sofern vorhanden, gezielt wieder registriert.
