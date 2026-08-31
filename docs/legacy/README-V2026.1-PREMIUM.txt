================================================================================
RÄUCHERHAKEN24 · V2026.1 — PREMIUM-ÜBERARBEITUNG
================================================================================

Diese Version bringt ein neues Designsystem, behebt die gefundenen technischen
Fehler und ersetzt den bisherigen Chat durch einen vollwertigen Räucherberater.

Das bestehende Sortiment ist unverändert. Es wurde kein Produkt entfernt,
umbenannt oder neu erfunden, kein Preis geändert und keine Produktbilddatei
ausgetauscht. Die Prüfung dazu steht weiter unten unter „Produktintegrität“.


--------------------------------------------------------------------------------
1. WAS NEU DAZUGEKOMMEN IST
--------------------------------------------------------------------------------

rh-premium-2026.css     Das neue Designsystem. Wird als letztes Stylesheet
                        geladen und veredelt die bestehende Struktur:
                        Typografie, Farben, Abstände, Karten, Buttons, Header,
                        Footer, Formulare, Produktdarstellung, Animationen,
                        Passwort-Overlay und alle Breakpoints.

rh-premium-2026.js      Die neue Laufzeit. Scroll-Animationen, Aktionsleiste,
                        mobile Navigation, Bildoptimierung, Formularzustände,
                        Absicherung aller Bedienelemente, Canonical-Angabe.

smoky-berater.js        Oberfläche des Räucherberaters.
smoky-berater.css       Gestaltung des Räucherberaters.
smoky-wissen.json       Die Wissensbasis. Hier wird Fachwissen gepflegt.
smoky-api.php           Der Berater-Motor (komplett neu geschrieben).

404.html                Fehlerseite mit Navigation statt Sackgasse.
favicon.svg             Symbol für den Browser-Tab.
.htaccess               Komprimierung, Caching, Sicherheits-Header.
_archiv/                Abgelegte Dateien (siehe Punkt 7).


--------------------------------------------------------------------------------
2. BEHOBENE FEHLER
--------------------------------------------------------------------------------

TECHNIK

  · smoky-api.php enthielt den Ausdruck  preg_replace('/cite[^]*/u', ...) .
    Das ist in PHP ein ungültiger Zeichenklassen-Ausdruck. Die Funktion gab
    deshalb NULL zurück und hat jede Antwort des Chatbots restlos gelöscht.
    Der Chat konnte damit prinzipiell nie eine Live-Antwort ausgeben.

  · Dieselbe fehlerhafte Ersetzung stand auch in smoky-pro-v106.js. In
    JavaScript bedeutet [^] „jedes Zeichen“, der Ausdruck löschte also alles
    ab dem ersten Vorkommen der Buchstabenfolge „cite“.

  · smoky-api.php rief ein KI-Modell auf, das es nicht gibt, und erwartete den
    Zugangsschlüssel in einer Umgebungsvariablen. Beim Webhosting ist das in
    der Regel nicht gesetzt – der Chat lief dauerhaft im Notmodus.

  · Drei Chat-Module (smoky.js, smoky-help-v28.js, smoky-pro-v106.js) haben
    gleichzeitig einen Button und ein Chatfenster in die Seite geschrieben und
    sich gegenseitig überschrieben. Sichtbar war das an den übereinander
    liegenden Schaltflächen unten rechts.

NAVIGATION

  · Fünf Seiten hatten überhaupt keine Navigation und keine Fußzeile:
    thermometer.html, rezepte-anleitungen.html, sonderanfertigung-prototyp.html,
    marktplatz-agb.html und marktplatz-regeln.html. Wer dort landete, kam nur
    über den Zurück-Knopf weiter. Alle fünf haben jetzt Kopfzeile, Warenkorb
    und Fußzeile.

  · checkout.html hatte keine Fußzeile und damit keinen Weg zu Impressum,
    AGB, Datenschutz und Widerruf. Eine schlanke Rechtsleiste ist ergänzt.

  · Für nicht vorhandene Adressen gab es keine Fehlerseite. 404.html führt
    jetzt zurück in den Shop.

BEDIENELEMENTE

  · Warenkorb-Knopf, Berater-Knopf, Nach-oben-Pfeil und Cookie-Hinweis lagen
    übereinander. Sie sitzen jetzt in einer klaren Aktionsleiste; auf dem Handy
    zusätzlich oberhalb der mobilen Fußleiste.

  · Der schwebende Warenkorb-Knopf war doppelt: Der Warenkorb steht bereits in
    der mitlaufenden Kopfzeile. Auf Seiten mit Kopfzeile entfällt er.

  · Zwei identische Kategorie-Umschalter auf den Produktseiten – einer entfernt.

  · Warenkorb-Schaltflächen zeigten zwei Symbole gleichzeitig: ein Emoji aus
    einer alten Regel und das eingesetzte SVG-Symbol.

  · Nach dem Austausch der Symbole hatten mehrere Schaltflächen keinen
    vorlesbaren Namen mehr (etwa das „×“ im Warenkorb). Sie sind jetzt benannt.

DARSTELLUNG

  · Produktbilder hatten einen Schlagschatten. Da die Originalfotos einen
    deckend weißen Bildbereich enthalten, zeichnete der Schatten dessen
    rechteckige Kante nach – es sah aus wie ein grauer Kasten hinter dem
    Produkt. Die Bilddateien selbst sind unverändert.

  · Auf den Kategoriekacheln lag zusätzlich eine helle Hintergrundfarbe auf dem
    Bild selbst, was denselben Kasten erzeugte.

  · Im Hintergrund der Preisbox stand eine sehr große „10“, die wie ein
    Darstellungsfehler wirkte.

  · Die Produktsuche in der Kopfzeile zerfiel in zwei getrennte Kästchen.

  · Auf schmalen Bildschirmen war in der oberen Leiste nur der Halbsatz
    „· schnell & zuverlässig“ zu sehen.

  · Das Hero-Motiv war bei 320 px breiter als der Inhaltsbereich.

  · Unterstreichungen in Navigation, Karten und Seitenleiste sind entfernt.
    Im Fließtext bleiben sie erhalten, dort gehören sie hin.

  · Doppelte H1-Überschriften auf 13 Seiten. Die zweite ist jetzt eine H2 –
    optisch identisch, aber sauber in der Gliederung.

  · Drei verschiedene Basis-Stylesheets sorgten für unterschiedliche Optik.
    Die neue Designebene liegt auf allen Seiten und vereinheitlicht sie.


--------------------------------------------------------------------------------
3. DER RÄUCHERBERATER
--------------------------------------------------------------------------------

Der Berater arbeitet vollständig auf dem eigenen Server. Es wird kein externer
KI-Dienst aufgerufen, es gibt keinen Zugangsschlüssel und es verlassen keine
Kundendaten den Server. Damit entstehen keine laufenden Kosten und der Berater
funktioniert ohne weitere Einrichtung.

Fachwissen

  Fisch:    Forelle, Lachs, Aal, Makrele, Hering, Saibling, Karpfen, Zander,
            Barsch, Heilbutt, Wels – jeweils mit Charakter, Verfahren, Lake,
            Vorbereitung, Ablauf, Fertigmerkmal, Holzempfehlung.
  Fleisch:  Rohschinken, Speck, Lachsschinken, Rippchen, Pulled Pork, Brisket,
            Geflügel, Wild, Rind, Würste, Filet.
  Methoden: Heiß-, Warm- und Kalträuchern mit Temperaturbereichen, Wirkung,
            Voraussetzungen und Haltbarkeit.
  Pökeln:   Trocken- und Nasspökeln, Durchbrennen, Nitritpökelsalz.
  Lake:     Konzentration, Einlegezeit, Temperatur, Abspülen, Wässern.
  Holz:     Buche, Erle, Birke, Eiche, Kirsche, Apfel, Hickory, Ahorn –
            Aroma, Intensität, passende Lebensmittel; Warnung vor Nadelholz
            und behandeltem Holz; Körnungen von Mehl bis Chunks.
  Haken:    Auswahlkriterien, Aufhängung, Abstand, Material, Pflege.
  Öfen:     Ofenarten, Sparbrand, Luftzufuhr, Rauchqualität, Luftfeuchte.
  Weiteres: Hygiene, Lebensmittelsicherheit, Lagerung, 13 typische Probleme
            mit Ursachen und Lösungen, 12 häufige Fragen.

Verhalten

  · Er merkt sich im Gespräch, worum es geht. „Meine Forelle wiegt 450 Gramm“
    und danach „Wie lange soll sie rein?“ bezieht er korrekt aufeinander.
  · Er stellt höchstens eine Rückfrage, und nur wenn sie die Empfehlung
    tatsächlich verändert.
  · Er erkennt, auf welcher Produktseite der Kunde steht. Auf der Seite des
    Standardhakens beantwortet er „Ist dieser Haken für Lachs geeignet?“ mit
    Bezug auf genau dieses Produkt.
  · Er erstellt auf Wunsch vollständige Schritt-für-Schritt-Anleitungen,
    passend zum jeweiligen Lebensmittel.
  · Sicherheitsrelevante Punkte werden hervorgehoben: Geflügel und Hackfleisch
    nie kalt räuchern, Kalträuchern nur mit Pökeln, Nadelholz und behandeltes
    Holz sind tabu.

Produktempfehlungen

  Der Berater empfiehlt ausschließlich Produkte aus dem echten Katalog. Dieser
  wird serverseitig aus app-v12.js gelesen – derselben Liste, die auch der
  Warenkorb verwendet. Optional werden Artikel aus der Produktdatenbank
  ergänzt. Er kann daher keine Produkte, Preise, Bilder oder Eigenschaften
  erfinden. Wo im Sortiment nichts Passendes existiert, sagt er das offen.
  Empfohlen werden höchstens drei Artikel, und nur wenn sie zur Frage passen.

Sicherheit

  · Kein Schlüssel im Browser-Code.
  · Nur POST, Eingaben werden geprüft, maximal 600 Zeichen.
  · Begrenzung auf 25 Anfragen je Minute und Sitzung.
  · Keine internen Meldungen, Pfade oder Debug-Daten in der Antwort.

Erweitern

  Neues Wissen wird in smoky-wissen.json ergänzt. Am Motor muss dafür nichts
  geändert werden. Eine neue Fischart etwa wird einfach im Abschnitt "fisch"
  nach dem Muster der vorhandenen Einträge angelegt.


--------------------------------------------------------------------------------
4. HINWEIS ZUM PASSWORTSCHUTZ
--------------------------------------------------------------------------------

Der Zugangsschutz über access.js bleibt wie gewünscht bestehen und ist im neuen
Design gestaltet. Das Passwort ist unverändert.

Bitte im Hinterkopf behalten: Ein Passwort, das im JavaScript steht, ist für
jeden im Quelltext lesbar. Für eine Vorschau ist das in Ordnung, als echter
Zugriffsschutz reicht es nicht.

Für einen echten Schutz eignet sich der Verzeichnisschutz bei STRATO
(Kundenbereich → Verzeichnisschutz). Der greift, bevor überhaupt eine Datei
ausgeliefert wird. Vor dem Livegang wird der Zeilenblock

    <script src="access.js" defer></script>

aus den HTML-Seiten entfernt; die Website ist dann öffentlich erreichbar.


--------------------------------------------------------------------------------
5. STRATO
--------------------------------------------------------------------------------

  · Alle Pfade sind relativ. Es gibt keine absoluten Server- oder Domainpfade.
  · Es kam nichts hinzu, was einen anderen Serverstack voraussetzt: kein Build,
    kein Node, keine externen Abhängigkeiten, keine neue Datenbanktabelle.
  · Der Berater benötigt PHP mit Sessions. Beides ist bei STRATO Standard.
  · Groß- und Kleinschreibung der Dateinamen wurde nicht verändert.
  · Die beiliegende .htaccess ist bewusst vorsichtig gebaut: Jeder Block ist in
    <IfModule> gekapselt. Fehlt ein Modul, wird der Block übersprungen.

  Zwei Blöcke in der .htaccess sind absichtlich deaktiviert und werden erst
  eingeschaltet, wenn die endgültige Domain feststeht: die HTTPS-Erzwingung und
  die Vereinheitlichung auf eine Wunschdomain. Im Impressum stehen aktuell
  zwei Domains (.de und .com). Solange beide erreichbar sind, wird die
  Canonical-Angabe zur Laufzeit aus der tatsächlich aufgerufenen Adresse
  gebildet – eine fest eingetragene Adresse wäre auf einer der beiden falsch.


--------------------------------------------------------------------------------
6. PRODUKTINTEGRITÄT — PRÜFERGEBNIS
--------------------------------------------------------------------------------

Verglichen wurde die Endfassung mit der ursprünglichen Projektversion:

  Bilddateien              42 vorher, 42 unverändert vorhanden
  davon inhaltlich geändert 0  (Prüfung über Prüfsummen je Datei)
  entfernte Bilder          0
  neu hinzugekommen         1  (favicon.svg – Browser-Symbol, kein Produktbild)

  Produktseiten            alle vorhanden
  Produktnamen             alle vorhanden
  Preise                   alle vorhanden, keine Änderung
  Produktvarianten         alle vorhanden
  Warenkorb-Kennungen      alle vorhanden
  interne Links            alle vorhanden
  Produktkatalog           app-v12.js unverändert
                           hook-config-v51.js unverändert

  Entfernte Seite          keine
                           (passwort-test.html lag ohne Verlinkung im Projekt
                            und liegt jetzt unter _archiv/)
  Neue Seite               404.html


--------------------------------------------------------------------------------
6b. TESTERGEBNISSE
--------------------------------------------------------------------------------

Automatisiert geprüft wurde mit einem echten Browser (Chromium) über alle
36 Seiten und neun Breiten: 320, 375, 390, 430, 768, 1024, 1280, 1440 und
1920 Pixel. Das sind 324 Seiten-Breiten-Kombinationen.

  JavaScript-Fehler                       0
  Konsolenfehler                          0
  Waagerechtes Scrollen / Überlauf        0
  Tote Links (href="#", leer, javascript:) 0
  Bedienelemente ohne vorlesbaren Namen   0
  Fehlende Dateien (Bilder, CSS, JS)      0
  Doppelte IDs                            0
  Elemente ausserhalb des Sichtbereichs   0

  PHP-Syntaxprüfung        55 Dateien fehlerfrei
  JavaScript-Syntaxprüfung 41 Dateien fehlerfrei
  JSON-Prüfung             alle Dateien gültig

Nutzerwege (jeweils vollständig durchgeklickt, 41 Prüfpunkte, 0 Fehler):

  Startseite → Kategorie → Produktdetail → in den Warenkorb → Warenkorb
  öffnen → zur Kasse → Rechtslinks in der Fußzeile
  Produktseite → Berater öffnen → Beratung mit Gewicht → Rückfrage im
  Gespräch → Produktempfehlung → Produktlink öffnen → in den Warenkorb
  Suche in der Kopfzeile → passender Treffer
  Bisherige Sackgassenseiten → Navigation, Fußzeile, Berater vorhanden
  Checkout-Formular → Pflichtfelder, E-Mail-Prüfung, Erfolgszustand
  Mobiles Menü → öffnen, Aufklappmenü, Escape schließt
  Berater auf dem Handy → passt exakt in den Bildschirm

Berater-Backend (Sicherheitsprüfung):

  GET-Anfrage                    → 405, keine Verarbeitung
  Leere Frage                    → 405/400 mit verständlicher Meldung
  Über 600 Zeichen               → 400 mit Hinweis
  Skript-Einschleusung im Text   → wird nicht ausgegeben
  Frage nach internen Anweisungen→ keine Preisgabe (es gibt keine)
  Ungültiges JSON                → sauber abgefangen
  26. Anfrage in einer Minute    → 429, Begrenzung greift

Messwerte (lokal, ohne Netzverzögerung):

  Layoutverschiebung (CLS)   0,000 auf allen geprüften Seiten
  Größter Bildaufbau (LCP)   0,76 – 0,93 s
  Erster Bildaufbau (FCP)    0,65 – 0,75 s

  Übertragene Datenmenge:
    Startseite      4 499 KB  →  2 727 KB   (−39 %)
    Produktseite    4 153 KB  →  2 381 KB   (−43 %)
    Kontaktseite    2 300 KB  →    528 KB   (−77 %)

  Der Rückgang entsteht dadurch, dass für das kleine Beratersymbol jetzt
  das ohnehin geladene Markenmotiv verwendet wird statt einer zweiten,
  1,8 MB großen Bilddatei. Es wurde keine Bilddatei verändert.

Was performancemäßig offen bleibt (bewusst nicht angefasst):

  · Die Produktfotos sind 1200 × 1200 Pixel groß und werden auf etwa
    250 Pixel dargestellt. Eine zusätzliche, kleinere Fassung würde noch
    einmal deutlich Datenmenge sparen. Da hier ausdrücklich keine
    Produktbilder verändert werden sollten, ist nichts geschehen. Falls
    gewünscht, lässt sich das nachträglich so lösen, dass die
    Originaldateien unverändert erhalten bleiben und nur zusätzlich eine
    kleinere Fassung ausgeliefert wird.
  · Pro Seite werden weiterhin 16–21 Stylesheets und 18–21 Skripte
    geladen. Ein Zusammenfassen wäre möglich, hätte aber das Risiko,
    die über viele Versionen gewachsene Reihenfolge zu stören. Da
    Stabilität hier Vorrang hatte, blieb die Struktur unverändert. Die
    beiliegende .htaccess sorgt für Komprimierung und Zwischenspeicherung,
    was den Großteil des Effekts abfängt.

--------------------------------------------------------------------------------
7. ARCHIV
--------------------------------------------------------------------------------

Unter _archiv/ liegen Dateien, die für den Betrieb nicht mehr gebraucht werden.
Gelöscht wurde nichts.

  _archiv/dokumentation/       Alle bisherigen Versionshinweise, Prüfberichte
                               und Anleitungen. Sie lagen bisher im
                               Web-Stammverzeichnis und waren damit öffentlich
                               abrufbar.
  _archiv/abgeloeste-dateien/  passwort-test.html, product-runtime-v82.js und
                               shop-data-v82.js. Alle drei waren nirgends
                               eingebunden.

Der Ordner kann vom Server entfernt werden.


--------------------------------------------------------------------------------
8. WAS BEWUSST NICHT GEÄNDERT WURDE
--------------------------------------------------------------------------------

  · Sortiment, Produktnamen, Preise, Varianten und Produktbilder.
  · Warenkorb-, Bestell- und Checkout-Logik.
  · Rechtstexte, Unternehmens- und Kontaktdaten.
  · Bestehende Adressen der Seiten.
  · Das Orgaboard. Es ist Ihr internes Werkzeug und wurde nicht angefasst.
  · Die gewachsenen Stylesheets. Sie bleiben vollständig erhalten; die neue
    Designebene liegt darüber. Falls eine Einzelheit doch einmal anders
    aussehen soll, genügt eine Anpassung in rh-premium-2026.css.
