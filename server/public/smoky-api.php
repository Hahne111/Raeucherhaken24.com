<?php
/* =====================================================================
   RÄUCHERHAKEN24 · SMOKY – DIGITALER RÄUCHERBERATER (Backend)
   ---------------------------------------------------------------------
   Der Berater arbeitet vollständig serverseitig mit der gepflegten
   Wissensbasis smoky-wissen.json. Es wird kein externer KI-Dienst
   aufgerufen, es gibt keinen API-Schlüssel und keine Daten verlassen
   den Server.

   Produktempfehlungen stammen ausschliesslich aus dem echten
   Shop-Katalog (app-v12.js, optional ergänzt um die Produktdatenbank).
   Es werden keine Produkte, Preise, Bilder oder Eigenschaften erfunden.

   Erweiterung: Neue Fischarten, Fleischsorten, Hölzer, Probleme oder
   FAQ-Einträge werden in smoky-wissen.json ergänzt – hier ist keine
   Änderung nötig.
   ===================================================================== */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

const SMOKY_VERSION      = '2026.1';
const SMOKY_MAX_LEN      = 600;
const SMOKY_RATE_WINDOW  = 60;   // Sekunden
const SMOKY_RATE_MAX     = 25;   // Anfragen je Fenster

/* ----------------------------------------------------------- Antwort */
function smoky_out(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function smoky_fail(string $msg, int $status = 400): void {
    // Nach aussen niemals interne Details, Pfade oder Stacktraces.
    smoky_out(['ok' => false, 'error' => $msg], $status);
}

/* ------------------------------------------------------------ Methode */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    smoky_fail('Nur POST-Anfragen werden verarbeitet.', 405);
}

/* -------------------------------------------------------- Rate-Limit */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$now = time();
$bucket = $_SESSION['smoky_rate'] ?? ['start' => $now, 'count' => 0];
if (($now - (int)$bucket['start']) > SMOKY_RATE_WINDOW) {
    $bucket = ['start' => $now, 'count' => 0];
}
$bucket['count']++;
$_SESSION['smoky_rate'] = $bucket;
if ($bucket['count'] > SMOKY_RATE_MAX) {
    smoky_fail('Kleinen Moment bitte – es kamen sehr viele Anfragen kurz hintereinander.', 429);
}

/* ----------------------------------------------------------- Eingabe */
$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 20000) {
    smoky_fail('Die Anfrage konnte nicht gelesen werden.');
}
$body = json_decode($raw ?: '{}', true);
if (!is_array($body)) $body = [];

$question = trim((string)($body['question'] ?? ''));
$page     = preg_replace('/[^a-z0-9._-]/i', '', (string)($body['page'] ?? '')) ?: '';
$slotsIn  = is_array($body['slots'] ?? null) ? $body['slots'] : [];
$history  = is_array($body['history'] ?? null) ? array_slice($body['history'], -8) : [];

$len = function_exists('mb_strlen') ? mb_strlen($question, 'UTF-8') : strlen($question);
if ($question === '') {
    smoky_fail('Bitte stellen Sie eine Frage.');
}
if ($len > SMOKY_MAX_LEN) {
    smoky_fail('Die Frage ist zu lang. Bitte auf ' . SMOKY_MAX_LEN . ' Zeichen kürzen.');
}
// Steuerzeichen entfernen, Rest unverändert lassen.
$question = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $question) ?? '';

/* -------------------------------------------------------- Wissensbasis */
$kbPath = __DIR__ . '/smoky-wissen.json';
$KB = is_readable($kbPath) ? json_decode((string)file_get_contents($kbPath), true) : null;
if (!is_array($KB)) {
    smoky_fail('Der Räucherberater ist gerade nicht verfügbar. Bitte versuchen Sie es später noch einmal.', 503);
}

/* ------------------------------------------------ Echter Produktkatalog */
/**
 * Liest den Produktkatalog aus app-v12.js. Das ist dieselbe Liste, die
 * auch Warenkorb und Shop verwenden – dadurch kann der Berater keine
 * Produkte, Preise oder Bilder erfinden.
 */
function smoky_catalog(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];

    $file = __DIR__ . '/app-v12.js';
    if (is_readable($file)) {
        $js = (string)file_get_contents($file);
        $start = strpos($js, 'const CATALOG=');
        if ($start !== false) {
            $open = strpos($js, '[', $start);
            $end  = strpos($js, '];', $open === false ? $start : $open);
            if ($open !== false && $end !== false) {
                $lit = substr($js, $open, $end - $open + 1);
                // Unquotierte Schlüssel in gültiges JSON überführen.
                $json = preg_replace('/([\{,])\s*([A-Za-z_][A-Za-z0-9_]*)\s*:/', '$1"$2":', $lit);
                $rows = json_decode((string)$json, true);
                if (is_array($rows)) {
                    foreach ($rows as $r) {
                        if (!is_array($r) || empty($r['id'])) continue;
                        $cache[(string)$r['id']] = [
                            'id'    => (string)$r['id'],
                            'name'  => (string)($r['name'] ?? $r['id']),
                            'price' => (float)($r['price'] ?? 0),
                            'img'   => (string)($r['img'] ?? ''),
                            'url'   => (string)($r['url'] ?? ''),
                            'unit'  => (string)($r['unit'] ?? ''),
                            'art'   => (string)($r['article_no'] ?? ''),
                        ];
                    }
                }
            }
        }
    }

    // Optionale Ergänzung aus der Produktdatenbank. Fällt sie aus,
    // bleibt der statische Katalog vollständig nutzbar.
    try {
        $boot = __DIR__ . '/orgaboard/bootstrap.php';
        if (is_readable($boot) && function_exists('rh24_db') === false) {
            require_once $boot;
        }
        if (function_exists('rh24_db')) {
            $db = rh24_db();
            $sql = "SELECT id,name,base_price,image_path,article_no,unit FROM products
                    WHERE COALESCE(product_type,'product')<>'prototype'
                      AND status='active' AND shop_visible=1";
            foreach ($db->query($sql) as $r) {
                $id = (string)($r['id'] ?? '');
                if ($id === '' || isset($cache[$id])) continue;
                $cache[$id] = [
                    'id'    => $id,
                    'name'  => (string)($r['name'] ?? $id),
                    'price' => (float)($r['base_price'] ?? 0),
                    'img'   => (string)($r['image_path'] ?? ''),
                    'url'   => 'artikel.php?id=' . rawurlencode($id),
                    'unit'  => (string)($r['unit'] ?? ''),
                    'art'   => (string)($r['article_no'] ?? ''),
                ];
            }
        }
    } catch (Throwable $ignore) {
        // Datenbank nicht verfügbar – statischer Katalog genügt.
    }

    return $cache;
}

function smoky_product(string $id): ?array {
    $c = smoky_catalog();
    return $c[$id] ?? null;
}

/** Produkt mit Begründung für die Antwort aufbereiten. */
function smoky_pick(string $id, string $why): ?array {
    $p = smoky_product($id);
    if (!$p) return null;           // Lieber nichts empfehlen als etwas erfinden.
    $p['why'] = $why;
    return $p;
}

/* ---------------------------------------------------------- Textsuche */
function smoky_norm(string $s): string {
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    $map = ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','á'=>'a','à'=>'a','é'=>'e','è'=>'e','í'=>'i','ó'=>'o','ú'=>'u'];
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/u', ' ', $s) ?? '';
    return trim(preg_replace('/\s+/', ' ', $s) ?? '');
}
function smoky_has(string $hay, array $needles): bool {
    foreach ($needles as $n) {
        if ($n !== '' && strpos($hay, $n) !== false) return true;
    }
    return false;
}

/* ------------------------------------------------------- Erkennung */
$q = smoky_norm($question);

$FISH_WORDS = [
    'forelle'  => ['forelle','forellen','regenbogenforelle','bachforelle'],
    'lachs'    => ['lachs','lachse','graved','graved lachs'],
    'aal'      => ['aal','aale','raeucheraal'],
    'makrele'  => ['makrele','makrelen'],
    'hering'   => ['hering','heringe','bueckling','buckling'],
    'saibling' => ['saibling','saiblinge'],
    'karpfen'  => ['karpfen'],
    'zander'   => ['zander'],
    'barsch'   => ['barsch','barsche','flussbarsch'],
    'heilbutt' => ['heilbutt'],
    'wels'     => ['wels','waller'],
];
$MEAT_WORDS = [
    'schinken'     => ['schinken','rohschinken','keule','nuss','oberschale'],
    'speck'        => ['speck','bauch','schweinebauch','baucherl'],
    'lachsschinken'=> ['lachsschinken','nacken','ruecken','kasseler'],
    'rippchen'     => ['rippchen','ribs','spareribs','rippe'],
    'pulled_pork'  => ['pulled pork','pulledpork','schweinenacken'],
    'brisket'      => ['brisket','rinderbrust'],
    'gefluegel'    => ['gefluegel','haehnchen','hahnchen','huhn','pute','ente','gans','haenchen','chicken'],
    'wild'         => ['wild','reh','hirsch','wildschwein','damwild'],
    'rind'         => ['rind','rinder','beef'],
    'wurst'        => ['wurst','wuerste','rohwurst','bruehwurst','salami','mettwurst'],
    'filet'        => ['filet','filets','lende'],
];
$WOOD_WORDS = [
    'buche'   => ['buche','buchen'],
    'erle'    => ['erle','erlen'],
    'birke'   => ['birke','birken'],
    'eiche'   => ['eiche','eichen'],
    'kirsche' => ['kirsche','kirsch','kirschholz'],
    'apfel'   => ['apfel','apfelholz'],
    'hickory' => ['hickory'],
    'ahorn'   => ['ahorn'],
];
$WOOD_FORBIDDEN = ['fichte','kiefer','tanne','laerche','nadelholz','nadelhoelzer','douglasie','zeder',
                   'spanplatte','palette','lackiert','geleimt','impraegniert','gebeizt','behandeltes holz','osb','mdf'];
/* Probleme werden über Wortkombinationen erkannt, damit auch freie
   Formulierungen wie „mein fisch ist leider zu trocken geworden“ greifen.
   Aufbau je Eintrag: [Pflichtwörter, mindestens eines davon], [Pflichtwörter, mindestens eines davon], ... */
$PROBLEM_RULES = [
    'zu_salzig'          => [['salzig','versalzen','salzt']],
    'zu_wenig_salz'      => [['fade','fad','labberig'],['schmeckt','geschmack','ist','zu wenig salz']],
    'rauch_bitter'       => [['bitter','bitteren','bitterer','bitterkeit','beissend']],
    'schimmel'           => [['schimmel','schimmlig','schimmelig','schmierig','belag','muffig']],
    'kondenswasser'      => [['kondenswasser','kondensat','schwitzwasser','beschlagen','tropft']],
    'mehl_geht_aus'      => [['mehl','sparbrand','glut','glimmt','spaene'],['geht aus','geht immer','immer aus','wieder aus','ausgegangen','erlischt','glimmt nicht','brennt nicht','bleibt nicht','verlischt']],
    'fisch_faellt'       => [['faellt','fallt','rutscht','haelt nicht','runtergefallen','abgefallen'],['haken','fisch','runter','ofen']],
    'temperatur_schwankt'=> [['temperatur','hitze','grad'],['schwankt','schwankungen','haelt nicht','steigt','faellt ab','unregelmaessig','zu heiss']],
    'oberflaeche_schwarz'=> [['schwarz','russ','verrusst','dunkle flecken','rußig']],
    'zu_wenig_aroma'     => [['zu wenig','kaum','kein','wenig','blass','hell geblieben'],['rauch','raucharoma','rauchgeschmack','aroma','farbe']],
    'fisch_trocken'      => [['trocken','trockener','ausgetrocknet','strohig'],['fisch','forelle','lachs','makrele','aal','saibling','zander','barsch','hering','karpfen','wels','heilbutt']],
    'fleisch_trocken'    => [['trocken','trockener','ausgetrocknet','strohig'],['fleisch','schinken','speck','nacken','filet','rippchen','wurst','pork','brisket']],
    'konsistenz'         => [['konsistenz','matschig','zaeh','gummi','innen roh','aussen fest','glasig','weich geworden']],
];

$detected = ['fish' => '', 'meat' => '', 'wood' => '', 'problem' => '', 'method' => '', 'weight' => 0, 'form' => ''];

foreach ($FISH_WORDS as $key => $words) { if (smoky_has($q, $words)) { $detected['fish'] = $key; break; } }
foreach ($MEAT_WORDS as $key => $words) { if (smoky_has($q, $words)) { $detected['meat'] = $key; break; } }
foreach ($WOOD_WORDS as $key => $words) { if (smoky_has($q, $words)) { $detected['wood'] = $key; break; } }
$forbiddenWood = smoky_has($q, $WOOD_FORBIDDEN);
foreach ($PROBLEM_RULES as $key => $groups) {
    $all = true;
    foreach ($groups as $group) { if (!smoky_has($q, $group)) { $all = false; break; } }
    if ($all) { $detected['problem'] = $key; break; }
}
// Ein reines „trocken“ ohne Räuchergut auf den Trocknungsschritt beziehen, nicht auf ein Problem.
if ($detected['problem'] === '' && smoky_has($q, ['trocknen','antrocknen','trocknungszeit'])) {
    $detected['problem'] = '';
}

if (smoky_has($q, ['kaltraeuchern','kalt raeuchern','kaltgeraeuchert','kalt geraeuchert'])) $detected['method'] = 'kalt';
elseif (smoky_has($q, ['warmraeuchern','warm raeuchern','warmgeraeuchert']))                 $detected['method'] = 'warm';
elseif (smoky_has($q, ['heissraeuchern','heiss raeuchern','heissgeraeuchert','heiss geraeuchert'])) $detected['method'] = 'heiss';

if (smoky_has($q, ['filet','filets','seite','tranchen'])) $detected['form'] = 'filet';
elseif (smoky_has($q, ['ganz','ganze','ganzer','im ganzen'])) $detected['form'] = 'ganz';

// Gewicht: "500 g", "1,2 kg", "450g"
if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|kilo|g|gramm)\b/u', $q, $m)) {
    $val = (float)str_replace(',', '.', $m[1]);
    $detected['weight'] = in_array($m[2], ['kg','kilo'], true) ? (int)round($val * 1000) : (int)round($val);
}

// Bestehende Gesprächsangaben übernehmen, neue haben Vorrang.
$slots = [
    'fish'   => (string)($slotsIn['fish']   ?? ''),
    'meat'   => (string)($slotsIn['meat']   ?? ''),
    'wood'   => (string)($slotsIn['wood']   ?? ''),
    'method' => (string)($slotsIn['method'] ?? ''),
    'form'   => (string)($slotsIn['form']   ?? ''),
    'weight' => (int)   ($slotsIn['weight'] ?? 0),
];
foreach (['fish','meat','wood','method','form'] as $k) {
    if ($detected[$k] !== '') $slots[$k] = $detected[$k];
}
if ($detected['weight'] > 0) $slots['weight'] = $detected['weight'];

// Wenn ein neues Räuchergut genannt wird, wird das alte ersetzt.
if ($detected['fish'] !== '') $slots['meat'] = '';
if ($detected['meat'] !== '') $slots['fish'] = '';

// Seitenkontext: auf einer Produktseite ist das geöffnete Produkt gemeint.
$pageProduct = null;
if ($page !== '') {
    foreach (smoky_catalog() as $p) {
        if ($p['url'] !== '' && strcasecmp($p['url'], $page) === 0) { $pageProduct = $p; break; }
    }
}

/* --------------------------------------------------- Hakenempfehlung */
/**
 * Wählt anhand von Räuchergut, Form und Gewicht einen im Shop
 * tatsächlich vorhandenen Haken aus.
 */
function smoky_hook_for(array $slots, array $KB): array {
    $fish = $slots['fish']; $meat = $slots['meat'];
    $form = $slots['form']; $w = (int)$slots['weight'];

    if ($meat !== '') {
        return ['fleisch', 'Für Schinken und schwere Fleischstücke ist die massive S-Form mit 5 mm ausgelegt.'];
    }
    if ($form === 'filet' || in_array($fish, ['lachs','heilbutt','wels'], true)) {
        return ['filet', 'Die flache Bauform ist auf Filets und flachere Räucherstücke ausgelegt.'];
    }
    if ($fish === 'aal') {
        return ['aal', 'Der kleinere Hakenbogen ist gezielt für Aal und andere schlanke Fische gedacht.'];
    }
    if ($w > 0) {
        if ($w >= 5000) return ['ultra',  'Bei diesem Gewicht ist die stabilere Ultra-Ausführung mit zwei Dornen die sinnvolle Reserve.'];
        if ($w >= 3000) return ['kralle', 'Ab etwa 3 kg verteilt der Mehrpunkt-Halt das Gewicht auf mehrere Punkte.'];
        if ($w >= 1000) return ['doppel', 'Zwei Dornen geben kräftigeren Fischen in diesem Gewichtsbereich zusätzliche Stabilität.'];
        return ['std', 'Für dieses Gewicht ist der Standardhaken die einfache und passende Grundwahl.'];
    }
    if ($fish === 'karpfen')  return ['kralle', 'Karpfen sind meist schwer und kompakt – der Mehrpunkt-Halt ist hier die sicherere Wahl.'];
    if ($fish === 'makrele')  return ['doppel', 'Zwei Haltepunkte geben der kräftigen Makrele mehr Stabilität.'];
    if ($fish !== '')         return ['std',    'Für klassische Fische in diesem Format ist der Standardhaken die unkomplizierte Wahl.'];
    return ['std', 'Der Standardhaken ist der Allrounder für klassisches Räuchergut.'];
}

function smoky_wood_for(array $slots): array {
    $fish = $slots['fish']; $meat = $slots['meat'];
    if ($meat !== '') {
        if (in_array($meat, ['schinken','speck','wild','rind','brisket'], true)) {
            return ['mehl-eiche', 'Eiche gibt Schinken, Speck und Wild das kräftige, würzige Profil.'];
        }
        if (in_array($meat, ['gefluegel','pulled_pork','rippchen'], true)) {
            return ['mehl-kirsche', 'Kirsche bringt eine mild-fruchtige Note und eine schöne Färbung.'];
        }
        return ['mehl-buche', 'Buche ist der ausgewogene Klassiker für Fleisch.'];
    }
    if (in_array($fish, ['zander','barsch'], true)) {
        return ['mehl-erle', 'Erle ist mild und lässt den feinen Eigengeschmack magerer Fische bestehen.'];
    }
    if ($fish === 'lachs')  return ['mehl-erle', 'Erle ist die klassische Wahl für Lachs.'];
    if ($fish !== '')       return ['mehl-buche', 'Buche ist der bewährte Allrounder – für Fisch ebenso wie für Fleisch.'];
    return ['mehl-buche', 'Buche passt zu Fisch, Fleisch und Schinken und verzeiht Fehler.'];
}

/**
 * Räucherlaugen gibt es im Shop für Forelle und für Aal. Für Fische,
 * die üblicherweise als Filet trocken gebeizt werden, wird bewusst
 * keine Lauge empfohlen – lieber keine Empfehlung als eine unpassende.
 */
function smoky_brine_for(array $slots): array {
    if ($slots['fish'] === 'aal') {
        return ['lauge-aal-0', 'Auf Aal abgestimmte Lauge – Dosierung und Einlegezeit nach Packungsangabe.'];
    }
    $forellenTyp = ['forelle','saibling','makrele','hering','barsch','zander','karpfen'];
    if (in_array($slots['fish'], $forellenTyp, true) && $slots['form'] !== 'filet') {
        return ['lauge-forelle-0', 'Klassische Räucherlauge für ganze Fische – Dosierung und Zeit nach Packungsangabe.'];
    }
    return ['', ''];
}

/* --------------------------------------------------- Antwort bauen */
$blocks   = [];
$products = [];
$followup = null;
$chips    = [];
$topic    = 'allgemein';

$addP  = function (string $t, string $v) use (&$blocks) { if (trim($v) !== '') $blocks[] = ['t' => $t, 'v' => $v]; };
$addL  = function (string $t, array $v)  use (&$blocks) { $v = array_values(array_filter($v)); if ($v) $blocks[] = ['t' => $t, 'v' => $v]; };
$addProd = function (?array $p) use (&$products) { if ($p) $products[] = $p; };

$foodLabel = '';
if ($slots['fish'] !== '' && isset($KB['fisch'][$slots['fish']]))   $foodLabel = $KB['fisch'][$slots['fish']]['name'];
if ($slots['meat'] !== '' && isset($KB['fleisch'][$slots['meat']])) $foodLabel = $KB['fleisch'][$slots['meat']]['name'];

$wantsGuide = smoky_has($q, ['anleitung','schritt fuer schritt','schritt f','wie gehe ich vor','wie mache ich','ablauf','anfaenger','einsteiger','komplett','von anfang']);
$wantsHook  = smoky_has($q, ['haken','aufhaengen','aufhangen','einhaengen','einhangen','traglast','tragfaehig','aufhaengung','sicherer halt','festen halt']);
$wantsWood  = smoky_has($q, ['holz','raeuchermehl','mehl','spaene','chips','chunks','koernung','aroma holz']);
$wantsSafe  = smoky_has($q, ['sicher','gefaehrlich','risiko','botulismus','keime','hygiene','schwanger','haltbar','lager','aufbewahr','einfrier','verderb','kuehlschrank','wie lange haelt']);
$wantsCure  = smoky_has($q, ['poekeln','pokeln','npS','nitrit','poekelsalz','pokelsalz','lake','salzlake','beizen','salzen','durchbrennen']);
$wantsTemp  = smoky_has($q, ['kerntemperatur','temperatur','grad','wie heiss','wie warm']);
$wantsTime  = smoky_has($q, ['wie lange','dauer','zeit','stunden','minuten']);

/* ---- 0) Ungeeignetes Holz: Sicherheitshinweis hat immer Vorrang ---- */
if ($forbiddenWood) {
    $topic = 'holz';
    $addP('h', 'Dieses Holz ist zum Räuchern nicht geeignet');
    $addP('warn', $KB['holz']['warnung']['nadelholz']);
    $addP('warn', $KB['holz']['warnung']['behandelt']);
    $addP('p', 'Geeignet sind ausschliesslich unbehandelte Laubhölzer. Im Sortiment sind Buche, Erle, Birke, Eiche und Kirsche.');
    [$wid, $why] = smoky_wood_for($slots);
    $addProd(smoky_pick($wid, $why));
    $chips = ['Welches Holz passt zu Fisch?', 'Welches Holz passt zu Schinken?', 'Warum schmeckt der Rauch bitter?'];
}

/* ---- 1) Problemlösung hat Vorrang ---- */
elseif ($detected['problem'] !== '' && isset($KB['probleme'][$detected['problem']])) {
    $topic = 'problem';
    $pr = $KB['probleme'][$detected['problem']];
    $addP('h', $pr['titel']);
    $addL('ul_cause', $pr['ursachen'] ?? []);
    $addL('ul_fix',   $pr['loesungen'] ?? []);
    if (!empty($pr['warnung'])) $addP('warn', $pr['warnung']);

    if ($detected['problem'] === 'fisch_faellt') {
        [$hid, $why] = smoky_hook_for($slots, $KB);
        $addProd(smoky_pick($hid, $why));
    }
    if (in_array($detected['problem'], ['rauch_bitter','mehl_geht_aus','zu_wenig_aroma'], true)) {
        [$wid, $why] = smoky_wood_for($slots);
        $addProd(smoky_pick($wid, $why));
    }
    $chips = ['Wie erkenne ich guten Rauch?', 'Wie lange muss ich trocknen lassen?', 'Welches Räuchermehl passt?'];
}

/* ---- 2) Vollständige Anleitung ---- */
elseif ($wantsGuide && ($slots['fish'] !== '' || $slots['meat'] !== '')) {
    $topic = 'anleitung';
    if ($slots['fish'] !== '') {
        $f = $KB['fisch'][$slots['fish']] ?? [];
        $method = $slots['method'] ?: 'heiss';
        $addP('h', ($f['name'] ?? 'Fisch') . ' räuchern – Schritt für Schritt');
        $addP('p', ($f['besonderheit'] ?? $f['typ'] ?? ''));
        $steps = [
            'Vorbereiten: Fisch ausnehmen, den Bauchraum gründlich säubern und die dunkle Niere entlang der Wirbelsäule entfernen. Kalt abspülen.',
            'Salzen: ' . ($f['lake'] ?? $KB['lake']['konzentration']) . ' Bei fertiger Räucherlauge gilt die Packungsangabe.',
            'Einlegezeit: Fisch vollständig bedeckt und kühl einlegen. Aufschwimmende Stücke beschweren.',
            'Abspülen: Nach der Lake gründlich mit klarem Wasser abspülen, damit keine Salzränder entstehen.',
            'Trocknen: ' . ($f['trocknen'] ?? $KB['trocknen']['dauer']) . ' Die Haut soll sich matt und griffig anfühlen.',
            'Ofen vorbereiten: Ofen vorheizen, damit sich kein Kondenswasser auf dem Fisch bildet.',
            'Aufhängen: ' . $KB['haken']['aufhaengen_fisch'] . ' ' . $KB['haken']['abstand'],
            'Garphase: ' . ($f['ablauf'] ?? $KB['methoden']['heiss']['temperatur']),
            'Räucherphase: Räuchermehl auflegen und bei etwa 70–80 °C räuchern, bis Farbe und Aroma stimmen.',
            'Fertig erkennen: ' . ($f['fertig'] ?? $KB['grundsaetze']['kerntemperatur_fisch']),
            'Abkühlen: ' . $KB['lagerung']['abkuehlen'],
            'Lagern: ' . $KB['lagerung']['heissgeraeuchert'],
        ];
        if ($method === 'kalt') {
            $steps[7] = 'Kalträuchern: ' . $KB['methoden']['kalt']['temperatur'] . ' ' . $KB['methoden']['kalt']['ablauf'];
            $steps[8] = 'Wichtig: ' . $KB['methoden']['kalt']['voraussetzung'];
            $steps[9] = 'Kaltgeräucherter Fisch bleibt roh. Nur einwandfreie Rohware verwenden und die Kühlkette lückenlos halten.';
        }
        $addL('ol', $steps);
        [$hid, $hwhy] = smoky_hook_for($slots, $KB);
        [$wid, $wwhy] = smoky_wood_for($slots);
        [$bid, $bwhy] = smoky_brine_for($slots);
        $addProd(smoky_pick($hid, $hwhy));
        $addProd(smoky_pick($bid, $bwhy));
        $addProd(smoky_pick($wid, $wwhy));
    } else {
        $m = $KB['fleisch'][$slots['meat']] ?? [];
        $addP('h', ($m['name'] ?? 'Fleisch') . ' räuchern – Schritt für Schritt');
        if (!empty($m['besonderheit'])) $addP('p', $m['besonderheit']);
        $isCold = (($m['methode'] ?? '') !== '' && stripos($m['methode'], 'kalt') !== false) || $slots['method'] === 'kalt';
        if ($isCold) {
            $addL('ol', [
                'Fleisch zuschneiden: Sehnen und lose Fettteile entfernen, damit die Pökelmischung überall gleichmäßig anliegt.',
                'Pökeln: ' . ($m['poekeln'] ?? $KB['poekeln']['trocken']['ablauf']) . ' Die Dosierung des Pökelsalzes steht auf der Packung und ist verbindlich.',
                'Pökelzeit: ' . $KB['poekeln']['trocken']['zeit'],
                'Durchbrennen: ' . $KB['poekeln']['durchbrennen']['was'] . ' ' . $KB['poekeln']['durchbrennen']['warum'],
                'Trocknen: ' . $KB['trocknen']['wie'] . ' Vor dem Kalträuchern eher ein bis mehrere Tage.',
                'Aufhängen: Stücke so hängen, dass sie sich nicht berühren.',
                'Kalträuchern: ' . $KB['methoden']['kalt']['temperatur'] . ' ' . $KB['methoden']['kalt']['ablauf'],
                'Reifen: ' . ($m['reifen'] ?? 'Nach dem Räuchern kühl, luftig und dunkel reifen lassen.'),
                'Lagern: ' . $KB['lagerung']['kaltgeraeuchert'],
            ]);
            $addP('warn', $KB['methoden']['kalt']['warnung']);
        } else {
            $addL('ol', [
                'Vorbereiten: Fleisch zuschneiden und trocken tupfen.',
                'Würzen bzw. pökeln: ' . ($m['poekeln'] ?? 'Nach Rezept würzen oder pökeln. Bei Fertigmischungen gilt die Packungsangabe.'),
                'Ruhen lassen: Gewürztes Fleisch kühl durchziehen lassen.',
                'Ofen vorbereiten: ' . ($m['temperatur'] ?? $KB['methoden']['heiss']['temperatur']),
                'Aufhängen oder auflegen: Auf Abstand achten, damit der Rauch überall hinkommt.',
                'Räuchern und Garen: Räuchermehl auflegen und die Kerntemperatur begleiten.',
                'Fertig erkennen: ' . ($m['kerntemperatur'] ?? $m['fertig'] ?? $KB['grundsaetze']['kerntemperatur_fisch']),
                'Ruhen und Abkühlen: ' . $KB['lagerung']['abkuehlen'],
                'Lagern: ' . $KB['lagerung']['heissgeraeuchert'],
            ]);
            if ($slots['meat'] === 'gefluegel') $addP('warn', $KB['fleisch']['gefluegel']['warnung']);
        }
        [$hid, $hwhy] = smoky_hook_for($slots, $KB);
        [$wid, $wwhy] = smoky_wood_for($slots);
        $addProd(smoky_pick($hid, $hwhy));
        $addProd(smoky_pick($wid, $wwhy));
    }
    $chips = ['Welches Holz passt dazu?', 'Welcher Haken hält das?', 'Wie lagere ich das Ergebnis?'];
}

/* ---- 3) Hakenberatung ---- */
elseif ($wantsHook || ($pageProduct && smoky_has($q, ['geeignet','passt','dieser','diesen','das hier']))) {
    $topic = 'haken';
    [$hid, $why] = smoky_hook_for($slots, $KB);

    if ($pageProduct && smoky_has($q, ['geeignet','passt','dieser','diesen','das hier'])) {
        $addP('h', 'Zum geöffneten Produkt: ' . $pageProduct['name']);
        if ($pageProduct['id'] === $hid) {
            $addP('p', 'Ja – für ' . ($foodLabel ?: 'dieses Räuchergut') . ' ist genau dieser Haken die passende Wahl. ' . $why);
        } elseif ($foodLabel !== '') {
            $addP('p', 'Für ' . $foodLabel . ' würde ich einen anderen Haken bevorzugen. ' . $why);
            $addProd(smoky_pick($hid, $why));
        } else {
            $addP('p', 'Das kann ich genauer sagen, wenn ich weiß, was aufgehängt werden soll.');
        }
    } else {
        $addP('h', 'Passender Räucherhaken');
        $addP('p', $foodLabel !== ''
            ? 'Für ' . $foodLabel . ($slots['weight'] > 0 ? ' mit rund ' . number_format($slots['weight'] / 1000, 2, ',', '.') . ' kg' : '') . ' gilt: ' . $why
            : 'Worauf es ankommt:');
        $addL('ul', $KB['haken']['auswahl_kriterien']);
        $addProd(smoky_pick($hid, $why));
    }
    $addP('p', $KB['haken']['abstand']);
    $addP('note', 'Material: ' . $KB['haken']['material']['v2a'] . ' ' . $KB['haken']['material']['v4a']);

    if ($foodLabel === '') {
        $followup = [
            'q' => 'Was möchtest du aufhängen?',
            'opts' => [
                ['label' => 'Ganze Forellen',   'send' => 'Ich möchte ganze Forellen räuchern.'],
                ['label' => 'Aal',              'send' => 'Ich möchte Aal räuchern.'],
                ['label' => 'Lachs oder Filet', 'send' => 'Ich möchte Lachsfilet räuchern.'],
                ['label' => 'Schinken/Fleisch', 'send' => 'Ich möchte Schinken räuchern.'],
            ],
        ];
    } elseif ($slots['weight'] === 0 && $slots['fish'] !== '' && $slots['form'] !== 'filet') {
        $followup = [
            'q' => 'Wie schwer sind die Fische ungefähr?',
            'opts' => [
                ['label' => 'bis 1 kg',   'send' => 'Die Fische wiegen etwa 500 g.'],
                ['label' => '1–3 kg',     'send' => 'Die Fische wiegen etwa 2 kg.'],
                ['label' => '3–5 kg',     'send' => 'Die Fische wiegen etwa 4 kg.'],
                ['label' => 'über 5 kg',  'send' => 'Die Fische wiegen über 5 kg.'],
            ],
        ];
    }
    $chips = ['Welches Räuchermehl passt?', 'V2A oder V4A?', 'Wie hänge ich richtig ein?'];
}

/* ---- 4) Holzberatung ---- */
elseif ($wantsWood || $detected['wood'] !== '') {
    $topic = 'holz';
    if ($detected['wood'] !== '' && isset($KB['holz'][$detected['wood']])) {
        $w = $KB['holz'][$detected['wood']];
        $addP('h', $w['name'] . ' als Räucherholz');
        $addL('ul', [
            'Aroma: ' . $w['aroma'],
            'Intensität: ' . $w['intensitaet'],
            'Passt zu: ' . $w['passt'],
            !empty($w['farbe']) ? 'Farbe: ' . $w['farbe'] : '',
        ]);
        if (!empty($w['hinweis'])) $addP('note', $w['hinweis']);
        $map = ['buche'=>'mehl-buche','erle'=>'mehl-erle','birke'=>'mehl-birke','eiche'=>'mehl-eiche','kirsche'=>'mehl-kirsche'];
        if (isset($map[$detected['wood']])) {
            $addProd(smoky_pick($map[$detected['wood']], 'Diese Holzart ist im Shop als Räuchermehl erhältlich.'));
        } else {
            $addP('note', 'Diese Holzart führen wir derzeit nicht als Räuchermehl. Im Sortiment sind Buche, Erle, Birke, Eiche und Kirsche.');
        }
    } else {
        [$wid, $why] = smoky_wood_for($slots);
        $rec = smoky_product($wid);
        $addP('h', $foodLabel !== '' ? 'Räucherholz für ' . $foodLabel : 'Räucherholz auswählen');
        if ($foodLabel !== '' && $rec) {
            $addP('p', 'Meine Empfehlung: ' . $rec['name'] . '. ' . $why);
        }
        $rows = [];
        foreach (['buche','erle','birke','eiche','kirsche'] as $k) {
            $w = $KB['holz'][$k];
            $rows[] = $w['name'] . ': ' . $w['aroma'] . ' – passt zu ' . $w['passt'] . '.';
        }
        $addL('ul', $rows);
        $addProd(smoky_pick($wid, $why));
    }
    $addP('warn', $KB['holz']['warnung']['nadelholz'] . ' ' . $KB['holz']['warnung']['behandelt']);
    if (smoky_has($q, ['koernung','spaene','chips','chunks','mehl oder'])) {
        $addL('ul', array_values($KB['holz']['koernung']));
    }
    $chips = ['Buche oder Erle für Forelle?', 'Warum schmeckt der Rauch bitter?', 'Welcher Haken passt dazu?'];
}

/* ---- 5) Pökeln, Lake, Salz ---- */
elseif ($wantsCure) {
    $topic = 'poekeln';
    if (smoky_has($q, ['nitrit','poekelsalz','pokelsalz','nps'])) {
        $addP('h', $KB['poekeln']['nps']['name']);
        $addP('p', $KB['poekeln']['nps']['was'] . ' ' . $KB['poekeln']['nps']['warum']);
        $addP('warn', $KB['poekeln']['nps']['dosierung']);
        $addL('ul', $KB['poekeln']['nps']['regeln']);
        $addP('note', $KB['poekeln']['nps']['ohne_nps']);
    } elseif (smoky_has($q, ['lake','salzlake','salzen','einlegen','beizen']) || $slots['fish'] !== '') {
        $addP('h', $foodLabel !== '' ? 'Salzen und Einlegen: ' . $foodLabel : 'Salzen und Einlegen bei Fisch');
        if ($slots['fish'] !== '' && isset($KB['fisch'][$slots['fish']])) {
            $f = $KB['fisch'][$slots['fish']];
            $spec = [];
            foreach (['lake','vorbereitung','zeit','besonderheit'] as $k) {
                if (!empty($f[$k])) $spec[] = $f[$k];
            }
            if ($spec) $addL('ul', $spec);
        }
        $addP('p', 'Allgemein gilt beim Salzen von Fisch:');
        $addL('ul', [
            $KB['lake']['konzentration'],
            $KB['lake']['zeit'],
            $KB['lake']['temperatur'],
            $KB['lake']['menge'],
            $KB['lake']['abspuelen'],
        ]);
        $addP('note', $KB['lake']['fertigmischung']);
        [$bid, $bwhy] = smoky_brine_for($slots);
        if ($bid !== '') {
            $addProd(smoky_pick($bid, $bwhy));
        } elseif ($slots['fish'] !== '') {
            $addP('note', 'Fertige Räucherlaugen führen wir für Forelle und für Aal. Für ' . ($foodLabel ?: 'diesen Fisch') . ' arbeitest du am besten mit einer selbst angesetzten Salzlake oder einer Trockenbeize.');
        }
    } else {
        $addP('h', 'Pökeln');
        $addP('p', $KB['poekeln']['zweck']);
        $addL('ul', [
            $KB['poekeln']['trocken']['name'] . ': ' . $KB['poekeln']['trocken']['ablauf'] . ' ' . $KB['poekeln']['trocken']['zeit'],
            $KB['poekeln']['nass']['name'] . ': ' . $KB['poekeln']['nass']['ablauf'] . ' ' . $KB['poekeln']['nass']['zeit'],
            $KB['poekeln']['durchbrennen']['name'] . ': ' . $KB['poekeln']['durchbrennen']['warum'] . ' ' . $KB['poekeln']['durchbrennen']['dauer'],
        ]);
        $addP('warn', $KB['poekeln']['nps']['dosierung']);
    }
    $chips = ['Wie lange muss Schinken pökeln?', 'Was ist Durchbrennen?', 'Wie stark soll die Lake sein?'];
}

/* ---- 6) Sicherheit, Hygiene, Lagerung ---- */
elseif ($wantsSafe) {
    $topic = 'sicherheit';
    if (smoky_has($q, ['lager','haltbar','einfrier','aufbewahr','kuehlschrank','wie lange haelt','verderb'])) {
        $addP('h', 'Lagerung und Haltbarkeit');
        $addL('ul', [
            $KB['lagerung']['heissgeraeuchert'],
            $KB['lagerung']['kaltgeraeuchert'],
            $KB['lagerung']['abkuehlen'],
            $KB['lagerung']['einfrieren'],
        ]);
        $addP('warn', $KB['lagerung']['warnzeichen']);
    } else {
        $addP('h', 'Lebensmittelsicherheit beim Räuchern');
        $addL('ul', $KB['sicherheit']['grundregeln']);
        $addP('warn', $KB['sicherheit']['kalt_risiko']);
        $addL('ul', [
            $KB['sicherheit']['gefluegel'],
            $KB['sicherheit']['hack'],
            $KB['sicherheit']['risikogruppen'],
            $KB['sicherheit']['gefrierware'],
        ]);
        $addP('note', $KB['sicherheit']['brand']);
    }
    $chips = ['Wie lange ist Räucherfisch haltbar?', 'Kerntemperatur bei Geflügel?', 'Darf ich Fisch kalträuchern?'];
}

/* ---- 7) Temperatur und Zeit ---- */
elseif ($wantsTemp || $wantsTime) {
    $topic = 'temperatur';
    $addP('h', $wantsTemp ? 'Temperaturen beim Räuchern' : 'Räucherzeiten richtig einschätzen');
    $addP('p', $KB['grundsaetze']['zeit_abhaengigkeit']);
    $addL('ul', [
        $KB['methoden']['heiss']['name'] . ': ' . $KB['methoden']['heiss']['temperatur'],
        $KB['methoden']['warm']['name'] . ': ' . $KB['methoden']['warm']['temperatur'],
        $KB['methoden']['kalt']['name'] . ': ' . $KB['methoden']['kalt']['temperatur'],
    ]);
    $addL('ul', [
        $KB['grundsaetze']['kerntemperatur_fisch'],
        $KB['grundsaetze']['kerntemperatur_gefluegel'],
        $KB['grundsaetze']['kerntemperatur_hack'],
    ]);
    if ($slots['fish'] !== '' && isset($KB['fisch'][$slots['fish']])) {
        $f = $KB['fisch'][$slots['fish']];
        $addP('note', $f['name'] . ': ' . ($f['ablauf'] ?? '') . ' ' . ($f['fertig'] ?? ''));
    }
    if ($slots['meat'] !== '' && isset($KB['fleisch'][$slots['meat']])) {
        $m = $KB['fleisch'][$slots['meat']];
        $addP('note', $m['name'] . ': ' . ($m['temperatur'] ?? '') . ' ' . ($m['kerntemperatur'] ?? $m['fertig'] ?? ''));
    }
    $chips = ['Wie erkenne ich, dass der Fisch fertig ist?', 'Warum schwankt meine Temperatur?', 'Brauche ich ein Thermometer?'];
}

/* ---- 8) Konkretes Räuchergut ---- */
elseif ($slots['fish'] !== '' && isset($KB['fisch'][$slots['fish']])) {
    $topic = 'fisch';
    $f = $KB['fisch'][$slots['fish']];
    $addP('h', $f['name'] . ' räuchern');
    $lines = [];
    if (!empty($f['typ']))          $lines[] = 'Charakter: ' . $f['typ'] . '.';
    if (!empty($f['methode']))      $lines[] = 'Verfahren: ' . $f['methode'];
    if (!empty($f['lake']))         $lines[] = 'Salzen: ' . $f['lake'];
    if (!empty($f['vorbereitung'])) $lines[] = 'Vorbereitung: ' . $f['vorbereitung'];
    if (!empty($f['trocknen']))     $lines[] = 'Trocknen: ' . $f['trocknen'];
    if (!empty($f['ablauf']))       $lines[] = 'Ablauf: ' . $f['ablauf'];
    if (!empty($f['besonderheit'])) $lines[] = 'Besonderheit: ' . $f['besonderheit'];
    if (!empty($f['fertig']))       $lines[] = 'Fertig: ' . $f['fertig'];
    if (!empty($f['holz']))         $lines[] = 'Holz: ' . $f['holz'];
    $addL('ul', $lines);
    if (!empty($f['hinweis'])) $addP('note', $f['hinweis']);

    [$hid, $hwhy] = smoky_hook_for($slots, $KB);
    [$wid, $wwhy] = smoky_wood_for($slots);
    [$bid, $bwhy] = smoky_brine_for($slots);
    $addProd(smoky_pick($hid, $hwhy));
    $addProd(smoky_pick($bid, $bwhy));
    $addProd(smoky_pick($wid, $wwhy));

    if ($slots['weight'] === 0 && $slots['form'] === '') {
        $followup = [
            'q' => 'Damit die Hakenempfehlung genau passt: wie schwer sind die Fische etwa?',
            'opts' => [
                ['label' => 'bis 1 kg',  'send' => 'Etwa 500 g pro Fisch.'],
                ['label' => '1–3 kg',    'send' => 'Etwa 2 kg pro Fisch.'],
                ['label' => 'über 3 kg', 'send' => 'Über 3 kg pro Fisch.'],
                ['label' => 'Als Filet', 'send' => 'Ich räuchere Filets.'],
            ],
        ];
    }
    $chips = ['Schritt-für-Schritt-Anleitung', 'Welches Holz passt?', 'Wie lange in die Lake?'];
}
elseif ($slots['meat'] !== '' && isset($KB['fleisch'][$slots['meat']])) {
    $topic = 'fleisch';
    $m = $KB['fleisch'][$slots['meat']];
    $addP('h', $m['name'] . ' räuchern');
    $lines = [];
    foreach (['teil'=>'Teilstück','methode'=>'Verfahren','poekeln'=>'Pökeln','temperatur'=>'Temperatur',
              'kerntemperatur'=>'Kerntemperatur','fertig'=>'Fertig','raeuchern'=>'Räuchern','reifen'=>'Reifen',
              'besonderheit'=>'Besonderheit','holz'=>'Holz','rohwurst'=>'Rohwurst','bruehwurst'=>'Brühwurst'] as $k => $lbl) {
        if (!empty($m[$k])) $lines[] = $lbl . ': ' . $m[$k];
    }
    $addL('ul', $lines);
    if (!empty($m['warnung']))    $addP('warn', $m['warnung']);
    if (!empty($m['sicherheit'])) $addP('warn', $m['sicherheit']);

    [$hid, $hwhy] = smoky_hook_for($slots, $KB);
    [$wid, $wwhy] = smoky_wood_for($slots);
    $addProd(smoky_pick($hid, $hwhy));
    $addProd(smoky_pick($wid, $wwhy));
    $chips = ['Schritt-für-Schritt-Anleitung', 'Wie lange pökeln?', 'Welches Holz passt?'];
}

/* ---- 9) Methodenfrage ---- */
elseif ($slots['method'] !== '' && isset($KB['methoden'][$slots['method']])) {
    $topic = 'methode';
    $mm = $KB['methoden'][$slots['method']];
    $addP('h', $mm['name']);
    $lines = [];
    foreach (['temperatur','wirkung','eignung','voraussetzung','ablauf','haltbarkeit','hinweis','jahreszeit'] as $k) {
        if (!empty($mm[$k])) $lines[] = $mm[$k];
    }
    $addL('ul', $lines);
    if (!empty($mm['warnung'])) $addP('warn', $mm['warnung']);
    $chips = ['Was brauche ich dafür?', 'Welches Holz passt?', 'Wie lange dauert das?'];
}

/* ---- 8b) Anleitung gewünscht, aber noch kein Räuchergut genannt ---- */
elseif ($wantsGuide) {
    $topic = 'anleitung';
    $addP('h', 'Einstieg ins Räuchern');
    $addP('p', 'Der Ablauf ist bei fast allem gleich: vorbereiten, salzen, abspülen, trocknen, Ofen vorheizen, aufhängen, garen, räuchern, abkühlen, kühl lagern. Die Zeiten und Temperaturen richten sich nach dem Räuchergut.');
    $addL('ol', [
        'Vorbereiten und sauber arbeiten – Kühlkette einhalten.',
        'Salzen oder pökeln: ' . $KB['lake']['konzentration'],
        'Abspülen, damit keine Salzränder bleiben.',
        'Trocknen: ' . $KB['trocknen']['warum'],
        'Ofen vorheizen: ' . $KB['oefen']['vorheizen'],
        'Aufhängen mit Abstand: ' . $KB['haken']['abstand'],
        'Garen und räuchern: ' . $KB['methoden']['heiss']['temperatur'],
        'Fertig prüfen: ' . $KB['grundsaetze']['kerntemperatur_fisch'],
        'Abkühlen und lagern: ' . $KB['lagerung']['abkuehlen'],
    ]);
    $addP('note', 'Für den Einstieg ist die Forelle im Heißrauch die dankbarste Wahl: überschaubare Zeiten, klare Erfolgskontrolle, geringe Sicherheitsanforderungen.');
    $followup = [
        'q' => 'Für welches Räuchergut soll ich die Anleitung konkret machen?',
        'opts' => [
            ['label' => 'Forelle',  'send' => 'Schritt-für-Schritt-Anleitung für Forelle.'],
            ['label' => 'Makrele',  'send' => 'Schritt-für-Schritt-Anleitung für Makrele.'],
            ['label' => 'Lachs',    'send' => 'Schritt-für-Schritt-Anleitung für Lachs.'],
            ['label' => 'Schinken', 'send' => 'Schritt-für-Schritt-Anleitung für Rohschinken.'],
        ],
    ];
    $chips = ['Welches Holz für den Anfang?', 'Welcher Haken für Forelle?', 'Wie stark muss die Lake sein?'];
}

/* ---- 9a) Allgemeine Problemfrage: Auswahl der häufigsten Fälle ---- */
elseif (smoky_has($q, ['problem','klappt nicht','geht schief','hilfe','fehler','misslungen','schiefgelaufen','was mache ich falsch'])) {
    $topic = 'problem';
    $addP('h', 'Wobei hakt es gerade?');
    $addP('p', 'Sag mir kurz, was schiefgelaufen ist. Ich nenne dir die wahrscheinlichen Ursachen und die sicheren Gegenmassnahmen.');
    $followup = [
        'q' => 'Häufige Fälle:',
        'opts' => [
            ['label' => 'Zu trocken',        'send' => 'Mein Fisch ist zu trocken geworden.'],
            ['label' => 'Rauch schmeckt bitter', 'send' => 'Der Rauch schmeckt bitter.'],
            ['label' => 'Zu wenig Aroma',    'send' => 'Ich habe zu wenig Raucharoma und die Farbe ist blass.'],
            ['label' => 'Fällt vom Haken',   'send' => 'Der Fisch fällt vom Haken.'],
        ],
    ];
    $chips = ['Zu salzig', 'Räuchermehl geht aus', 'Kondenswasser im Ofen', 'Temperatur schwankt', 'Schwarze Oberfläche', 'Schimmel'];
}

/* ---- 9b) Allgemein „Fisch“ oder „Fleisch“ ohne konkrete Art ---- */
elseif ($slots['fish'] === '' && $slots['meat'] === '' && smoky_has($q, ['fisch','fische','fischen'])) {
    $topic = 'fisch';
    $addP('h', 'Fisch räuchern – das Wichtigste vorab');
    $addL('ul', [
        $KB['methoden']['heiss']['name'] . ': ' . $KB['methoden']['heiss']['temperatur'],
        'Salzen: ' . $KB['lake']['konzentration'],
        'Trocknen: ' . $KB['trocknen']['warum'],
        'Fertig: ' . $KB['grundsaetze']['kerntemperatur_fisch'],
    ]);
    $addP('note', $KB['grundsaetze']['zeit_abhaengigkeit']);
    $followup = [
        'q' => 'Welchen Fisch möchtest du räuchern? Dann werde ich konkret.',
        'opts' => [
            ['label' => 'Forelle',  'send' => 'Ich möchte Forelle räuchern.'],
            ['label' => 'Lachs',    'send' => 'Ich möchte Lachs räuchern.'],
            ['label' => 'Aal',      'send' => 'Ich möchte Aal räuchern.'],
            ['label' => 'Makrele',  'send' => 'Ich möchte Makrele räuchern.'],
        ],
    ];
    $chips = ['Saibling', 'Zander', 'Karpfen', 'Hering', 'Barsch', 'Heilbutt', 'Wels'];
}
elseif ($slots['fish'] === '' && $slots['meat'] === '' && smoky_has($q, ['fleisch','fleisches'])) {
    $topic = 'fleisch';
    $addP('h', 'Fleisch räuchern – das Wichtigste vorab');
    $addL('ul', [
        'Heiss oder kalt: ' . $KB['methoden']['heiss']['wirkung'] . ' ' . $KB['methoden']['kalt']['wirkung'],
        'Voraussetzung fürs Kalträuchern: ' . $KB['methoden']['kalt']['voraussetzung'],
        'Pökeln: ' . $KB['poekeln']['zweck'],
        'Durchbrennen: ' . $KB['poekeln']['durchbrennen']['warum'],
    ]);
    $addP('warn', $KB['sicherheit']['gefluegel'] . ' ' . $KB['sicherheit']['hack']);
    $followup = [
        'q' => 'Was genau möchtest du räuchern?',
        'opts' => [
            ['label' => 'Schinken',    'send' => 'Ich möchte Rohschinken räuchern.'],
            ['label' => 'Speck/Bauch', 'send' => 'Ich möchte Speck räuchern.'],
            ['label' => 'Rippchen',    'send' => 'Ich möchte Rippchen räuchern.'],
            ['label' => 'Geflügel',    'send' => 'Ich möchte Geflügel räuchern.'],
        ],
    ];
    $chips = ['Pulled Pork', 'Brisket', 'Wild', 'Lachsschinken', 'Würste'];
}

/* ---- 10) FAQ-Treffer ---- */
else {
    $bestScore = 0; $best = null;
    foreach (($KB['faq'] ?? []) as $item) {
        $words = array_filter(explode(' ', smoky_norm($item['frage'] ?? '')), fn($w) => strlen($w) > 3);
        $hits = 0;
        foreach ($words as $w) if (strpos($q, $w) !== false) $hits++;
        $score = (count($words) && $hits >= 2) ? $hits / count($words) : 0;
        if ($score > $bestScore) { $bestScore = $score; $best = $item; }
    }
    if ($best && $bestScore >= 0.45) {
        $topic = 'faq';
        $addP('h', $best['frage']);
        $addP('p', $best['antwort']);
        $chips = ['Schritt-für-Schritt-Anleitung', 'Welcher Haken passt zu mir?', 'Welches Räuchermehl nehme ich?'];
    } else {
        /* ---- 11) Einstieg / nichts erkannt ---- */
        $topic = 'einstieg';
        $addP('h', 'Ich helfe dir gern weiter');
        $addP('p', 'Damit die Empfehlung wirklich passt, sag mir bitte kurz, was du räuchern möchtest – zum Beispiel „Forelle, etwa 400 g, heiß räuchern“. Fragen zu Haken, Räuchermehl, Lake, Pökeln, Temperaturen, Lagerung und typischen Problemen beantworte ich ebenfalls.');
        $followup = [
            'q' => 'Womit fangen wir an?',
            'opts' => [
                ['label' => 'Fisch räuchern',      'send' => 'Ich möchte Fisch räuchern.'],
                ['label' => 'Fleisch räuchern',    'send' => 'Ich möchte Fleisch räuchern.'],
                ['label' => 'Räucherhaken finden', 'send' => 'Welcher Räucherhaken passt zu mir?'],
                ['label' => 'Problem lösen',       'send' => 'Ich habe ein Problem beim Räuchern.'],
            ],
        ];
        $chips = ['Anfänger-Anleitung', 'Welches Räucherholz?', 'Wie stark muss die Lake sein?'];
    }
}

/* ---- Doppelte Produkte entfernen, maximal drei empfehlen ---- */
$seen = [];
$products = array_values(array_filter($products, function ($p) use (&$seen) {
    if (isset($seen[$p['id']])) return false;
    $seen[$p['id']] = true;
    return true;
}));
$products = array_slice($products, 0, 3);

/* ---- Reiner Text als Fallback (Vorlesen, Kopieren, Barrierefreiheit) ---- */
$plain = [];
foreach ($blocks as $b) {
    if (is_array($b['v'])) {
        foreach ($b['v'] as $i => $line) $plain[] = ($b['t'] === 'ol' ? ($i + 1) . '. ' : '· ') . $line;
    } else {
        $plain[] = $b['v'];
    }
}

smoky_out([
    'ok'       => true,
    'version'  => SMOKY_VERSION,
    'topic'    => $topic,
    'blocks'   => $blocks,
    'answer'   => implode("\n", $plain),
    'products' => $products,
    'followup' => $followup,
    'chips'    => $chips,
    'slots'    => $slots,
]);
