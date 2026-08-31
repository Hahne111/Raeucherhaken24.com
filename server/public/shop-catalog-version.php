<?php
/* =====================================================================
   RÄUCHERHAKEN24 · KATALOG-REVISION            (shop-catalog-version.php)
   ---------------------------------------------------------------------
   Winziger, öffentlicher Endpunkt. Er beantwortet genau eine Frage:

       "Hat sich am Produktkatalog seit Revision X etwas geändert?"

   Antwort (typisch ~120 Byte):
       {"ok":true,"revision":"…","poll_ms":1500}

   WARUM DIESE DATEI BEWUSST NICHT bootstrap.php EINBINDET
   -------------------------------------------------------
   bootstrap.php startet eine PHP-Session und prüft bei jedem Aufruf den
   Datenbank-Schemastand. Beides ist für eine Abfrage im Sekundentakt zu
   schwer. Schlimmer noch: die Datei-Session von PHP wird während eines
   Requests exklusiv gesperrt. Ein Shop-Tab, der im Sekundentakt pollt,
   würde damit die API-Aufrufe des gleichzeitig geöffneten OrgaBoards
   ausbremsen. Deshalb: eigene, schlanke Verbindung, keine Session.

   SICHERHEIT
   -------------------------------------------------------
   · Es werden ausschliesslich Aggregatwerte gelesen (MAX/COUNT).
   · Es verlässt KEIN Produktinhalt, kein Preis, kein Name diese Datei.
   · Keine Zugangsdaten in der Antwort, keine Fehlerdetails nach aussen.
   · Nur lesend. Es gibt keinen schreibenden Pfad.
   · Kein Zugriff auf Verwaltungsdaten – daher ist auch keine Anmeldung
     nötig und es entsteht kein neuer geschützter Endpunkt.

   LASTSCHUTZ
   -------------------------------------------------------
   Die Revision wird für RH24_REV_CACHE_TTL Sekunden in einer Datei
   zwischengespeichert. Damit entsteht höchstens eine Datenbankabfrage
   pro Sekunde – unabhängig davon, wie viele Besucher gerade pollen.
   ===================================================================== */
declare(strict_types=1);

const RH24_REV_CACHE_TTL  = 1.0;    // Sekunden
const RH24_REV_POLL_MS    = 1500;   // Empfehlung an den Browser
const RH24_REV_CONFIG     = __DIR__ . '/orgaboard/private/db-config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

/** Antwort senden und beenden. Setzt zusätzlich einen ETag, damit
 *  unveränderte Antworten mit 304 (ohne Rumpf) beantwortet werden. */
function rh24_rev_out(array $payload, int $status = 200): never {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($status === 200 && isset($payload['revision'])) {
        $etag = '"' . substr(hash('sha256', (string)$payload['revision']), 0, 24) . '"';
        header('ETag: ' . $etag);
        $sent = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($sent !== '' && ($sent === $etag || $sent === 'W/' . $etag)) {
            http_response_code(304);
            exit;
        }
    }
    http_response_code($status);
    echo $body;
    exit;
}

/* --------------------------------------------------------- Zwischenspeicher */
$cacheFile = sys_get_temp_dir() . '/rh24-catalog-rev-' . substr(hash('sha256', __DIR__), 0, 16) . '.json';

$cached = null;
if (is_file($cacheFile)) {
    $age = microtime(true) - (float)@filemtime($cacheFile);
    if ($age >= 0 && $age < RH24_REV_CACHE_TTL) {
        $raw = @file_get_contents($cacheFile);
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['revision'])) $cached = $decoded;
        }
    }
}
if ($cached !== null) {
    rh24_rev_out([
        'ok'       => true,
        'revision' => (string)$cached['revision'],
        'poll_ms'  => RH24_REV_POLL_MS,
        'cached'   => true,
    ]);
}

/* --------------------------------------------------------- Frische Revision */
try {
    if (!is_file(RH24_REV_CONFIG)) {
        throw new RuntimeException('Datenbank ist noch nicht eingerichtet.');
    }
    $cfg = require RH24_REV_CONFIG;
    if (!is_array($cfg) || !isset($cfg['host'], $cfg['database'], $cfg['user'])) {
        throw new RuntimeException('Unvollständige Datenbankkonfiguration.');
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        (string)$cfg['host'],
        (string)$cfg['database'],
        (string)($cfg['charset'] ?? 'utf8mb4')
    );
    $db = new PDO($dsn, (string)$cfg['user'], (string)($cfg['password'] ?? ''), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3,
    ]);

    /* Die Revision setzt sich aus drei Werten zusammen. Jeder einzelne
       reicht nicht aus:

         · MAX(updated_at)   erkennt Änderungen an Preis, Name, Text,
                             Lager, Sichtbarkeit und Veröffentlichung.
         · COUNT(*)          erkennt endgültig gelöschte Artikel – bei
                             einem DELETE bleibt MAX(updated_at) gleich.
         · veröffentlicht    erkennt zusätzlich das Zurücknehmen aus dem
                             Shop, auch wenn die Uhr nur sekundengenau
                             läuft und mehrere Änderungen zusammenfallen.

       Zusammen ergibt das eine Revision, die sich bei jeder für den Shop
       sichtbaren Veränderung zuverlässig ändert. */
    $row = $db->query(
        "SELECT
            COALESCE(MAX(updated_at), '0') AS last_change,
            COUNT(*)                       AS total,
            SUM(CASE WHEN published_at IS NOT NULL
                      AND COALESCE(product_type,'product') <> 'prototype'
                     THEN 1 ELSE 0 END)    AS published
         FROM products"
    )->fetch();

    if (!is_array($row)) throw new RuntimeException('Keine Katalogdaten.');

    $revision = sprintf(
        '%s.%d.%d',
        str_replace([' ', ':', '-'], '', (string)$row['last_change']),
        (int)$row['total'],
        (int)$row['published']
    );

    // Bestand mitzählen: Lageränderungen sind im Shop sichtbar
    // ("Sofort verfügbar"), stehen aber in einer eigenen Tabelle.
    try {
        $stock = $db->query('SELECT COALESCE(SUM(stock),0), COUNT(*) FROM inventory')->fetch(PDO::FETCH_NUM);
        if (is_array($stock)) $revision .= '.' . (int)$stock[0] . '.' . (int)$stock[1];
    } catch (Throwable $ignore) {
        // Ohne inventory-Tabelle bleibt die Revision trotzdem gültig.
    }

    @file_put_contents(
        $cacheFile,
        json_encode(['revision' => $revision], JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );

    rh24_rev_out(['ok' => true, 'revision' => $revision, 'poll_ms' => RH24_REV_POLL_MS]);

} catch (Throwable $e) {
    // Der Wortlaut geht ins Serverprotokoll, nicht zum Besucher.
    error_log('RH24 Katalogrevision: ' . $e->getMessage());

    /* Steht ein älterer Wert im Zwischenspeicher, wird er ausgeliefert.
       Der Shop bleibt dadurch bei einer kurzen Datenbankstörung ruhig
       und löst keine unnötigen Vollabfragen aus. */
    if (is_file($cacheFile)) {
        $raw = @file_get_contents($cacheFile);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['revision'])) {
            rh24_rev_out([
                'ok'       => true,
                'revision' => (string)$decoded['revision'],
                'poll_ms'  => RH24_REV_POLL_MS,
                'stale'    => true,
            ]);
        }
    }
    rh24_rev_out(['ok' => false, 'error' => 'Katalogstand derzeit nicht abrufbar.'], 503);
}
