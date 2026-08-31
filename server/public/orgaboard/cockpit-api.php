<?php
/* =====================================================================
   ORGABOARD · COCKPIT-ENDPUNKT   (cockpit-api.php)
   ---------------------------------------------------------------------
   Liefert die Daten für das Unternehmens-Cockpit und beantwortet die
   Fragen des internen Assistenten.

   · Anmeldung, CSRF-Prüfung und Rechteprüfung wie im übrigen Orgaboard.
   · Ausschliesslich lesende Zugriffe. Es wird nichts verändert.
   · Fehler werden nach aussen ohne technische Einzelheiten gemeldet;
     der Wortlaut geht ins Serverprotokoll.
   ===================================================================== */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
rh24_require_login();
rh24_require_permission('view_dashboard');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function cp_out(array $d, int $status = 200): never {
    http_response_code($status);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
if (strlen($raw) > 20000) cp_out(['ok' => false, 'error' => 'Anfrage zu gross.'], 413);
$data = json_decode($raw, true);
if (!is_array($data)) $data = [];

rh24_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf'] ?? null));

/* ------------------------------------------------------- Anfragebremse */
if (!isset($_SESSION['rh24_cockpit_rate'])) $_SESSION['rh24_cockpit_rate'] = ['start' => time(), 'count' => 0];
$rate = $_SESSION['rh24_cockpit_rate'];
if (time() - (int)$rate['start'] > 60) $rate = ['start' => time(), 'count' => 0];
$rate['count']++;
$_SESSION['rh24_cockpit_rate'] = $rate;
if ($rate['count'] > 120) {
    cp_out(['ok' => false, 'error' => 'Zu viele Anfragen in kurzer Zeit. Bitte einen Moment warten.'], 429);
}

$action = (string)($data['action'] ?? 'cockpit');

try {
    require_once __DIR__ . '/cockpit.php';

    if ($action === 'cockpit') {
        $days = (int)($data['product_days'] ?? 30);
        if (!in_array($days, [7, 30, 90, 365], true)) $days = 30;
        cp_out(['ok' => true, 'cockpit' => rh24_cockpit_payload($days), 'csrf' => rh24_csrf()]);
    }

    if ($action === 'inventory') {
        rh24_require_permission('view_inventory');
        cp_out(['ok' => true, 'inventory' => rh24_cockpit_inventory(rh24_db(), rh24_cp_ranges())]);
    }

    if ($action === 'products') {
        rh24_require_permission('view_products');
        $days = (int)($data['days'] ?? 30);
        if (!in_array($days, [7, 30, 90, 365], true)) $days = 30;
        cp_out(['ok' => true, 'products' => rh24_cockpit_products(rh24_db(), rh24_cp_ranges(), $days)]);
    }

    if ($action === 'health') {
        rh24_require_admin();
        cp_out(['ok' => true, 'health' => rh24_cockpit_health(rh24_db())]);
    }

    if ($action === 'assistant') {
        require_once __DIR__ . '/assistant.php';
        $question = trim((string)($data['question'] ?? ''));
        $len = function_exists('mb_strlen') ? mb_strlen($question, 'UTF-8') : strlen($question);
        if ($question === '') cp_out(['ok' => false, 'error' => 'Bitte eine Frage eingeben.'], 422);
        if ($len > 400)      cp_out(['ok' => false, 'error' => 'Die Frage ist zu lang (maximal 400 Zeichen).'], 422);
        cp_out(['ok' => true, 'answer' => rh24_assistant_answer($question)]);
    }

    if ($action === 'search') {
        $q = trim((string)($data['q'] ?? ''));
        if ($q === '') cp_out(['ok' => true, 'results' => []]);
        require_once __DIR__ . '/assistant.php';
        cp_out(['ok' => true, 'results' => rh24_cockpit_search($q)]);
    }

    cp_out(['ok' => false, 'error' => 'Unbekannte Aktion.'], 400);

} catch (Throwable $e) {
    error_log('RH24 Cockpit: ' . $e->getMessage());
    cp_out(['ok' => false, 'error' => 'Die Daten konnten nicht geladen werden.'], 500);
}
