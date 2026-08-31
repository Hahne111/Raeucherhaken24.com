RÄUCHERHAKEN24 V106.3 · KI-VERBINDUNG DIREKT IM ORGABOARD

Behoben:
- Produkt-KI funktioniert nicht mehr nur mit einer extern gesetzten OPENAI_API_KEY-Umgebungsvariable.
- Administrator kann den OpenAI API-Schlüssel direkt im Produkt-Baukasten über „KI verbinden“ sicher einrichten.
- Speicherung erfolgt serverseitig in /orgaboard/private/openai-config.php.
- /orgaboard/private wird zusätzlich per .htaccess gegen Webzugriff geschützt.
- API-Schlüssel wird nach dem Speichern niemals wieder im Klartext an den Browser gesendet.
- Verbindungstest prüft API-Schlüssel und ausgewähltes Modell.
- Fehlermeldungen der OpenAI API werden verständlicher ausgegeben.
- Standardmodell: gpt-5.6-luna.
- Vorhandene Server-Umgebungsvariable OPENAI_API_KEY hat weiterhin Vorrang.

WICHTIG:
Ein API-Schlüssel, der bereits in einem Chat oder Screenshot veröffentlicht wurde, darf nicht weiterverwendet werden. Im OpenAI-Portal widerrufen und einen neuen Schlüssel erzeugen.

BEDIENUNG:
1. Orgaboard → Produkte → Produkt bearbeiten → Schritt 3.
2. „KI verbinden“ anklicken.
3. NEUEN OpenAI API-Schlüssel eintragen.
4. „Sicher speichern“ anklicken.
5. „Verbindung testen“ anklicken.
6. Danach „Beschreibung optimieren“ oder „Beschreibung + SEO“ verwenden.
