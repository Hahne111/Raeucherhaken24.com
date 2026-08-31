<?php
declare(strict_types=1);

require __DIR__ . '/orgaboard/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function status_out(array $payload, int $code=200): never {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function status_clean_order_no(string $value): string {
    $value = strtoupper(trim($value));
    return (strlen($value) >= 10 && strlen($value) <= 60 && preg_match('/^[A-Z0-9-]+$/', $value)) ? $value : '';
}
function status_progress(array $row): int {
    $fallback = [
        'planung'=>0,'material'=>15,'cut'=>28,'form'=>42,'point'=>55,
        'solder'=>65,'clean'=>72,'quality'=>80,'pack'=>90,'ready'=>100
    ];
    $step = (string)($row['production_step'] ?? 'planung');
    $stored = max(0, min(100, (int)($row['production_progress'] ?? 0)));
    $progress = max($stored, (int)($fallback[$step] ?? 0));
    if (in_array((string)($row['status'] ?? ''), ['ready','shipped','complete'], true)) $progress = 100;
    if ((string)($row['status'] ?? '') === 'cancelled') $progress = 0;
    return $progress;
}
function status_phase(array $row, int $progress): array {
    $status = (string)($row['status'] ?? '');
    $payment = (string)($row['payment_status'] ?? 'pending');
    $step = (string)($row['production_step'] ?? 'planung');
    $steps = [
        'planung'=>['Vorbereitung','Ihre Bestellung wird für die Fertigung vorbereitet.'],
        'material'=>['Materialvorbereitung','Das benötigte Material wird vorbereitet und dem Auftrag zugeordnet.'],
        'cut'=>['Zuschnitt','Das Material wird auf die benötigten Maße zugeschnitten.'],
        'form'=>['Formgebung','Ihre Artikel befinden sich in der Formgebung.'],
        'point'=>['Feinschliff','Spitzen und Oberflächen werden präzise bearbeitet.'],
        'solder'=>['Fertigung','Verbindungen und Fertigungsschritte werden ausgeführt.'],
        'clean'=>['Reinigung','Die gefertigten Teile werden gereinigt und vorbereitet.'],
        'quality'=>['Qualitätskontrolle','Ihr Auftrag wird auf Ausführung und Qualität geprüft.'],
        'pack'=>['Verpackung','Ihre Bestellung wird sicher für den Versand verpackt.'],
        'ready'=>['Versandbereit','Ihre Bestellung ist fertig und wartet auf den Versand.'],
    ];
    if ($status === 'cancelled') return ['Storniert','Diese Bestellung wurde storniert.'];
    if ($status === 'complete') return ['Abgeschlossen','Ihre Bestellung wurde vollständig abgeschlossen.'];
    if ($status === 'shipped') return ['Versendet','Ihre Bestellung wurde an den Versanddienstleister übergeben.'];
    if ($payment !== 'paid' && in_array($status, ['new','payment_pending'], true)) {
        return ['Bestellung eingegangen','Die Bestellung ist erfasst. Die Fertigung startet nach bestätigtem Zahlungseingang.'];
    }
    if ($progress <= 0 && $payment === 'paid') {
        return ['Zahlung bestätigt','Ihre Zahlung ist bestätigt. Der Auftrag wartet auf den Produktionsstart.'];
    }
    return $steps[$step] ?? ['In Bearbeitung','Ihre Bestellung wird aktuell bearbeitet.'];
}
function status_timeline(array $row, int $progress): array {
    $status = (string)($row['status'] ?? '');
    $paymentPaid = (string)($row['payment_status'] ?? '') === 'paid' || in_array($status, ['production','quality','packing','ready','shipped','complete'], true);
    $started = !empty($row['production_started_at']) || $progress > 0 || in_array($status, ['production','quality','packing','ready','shipped','complete'], true);
    $quality = $progress >= 80 || in_array($status, ['quality','packing','ready','shipped','complete'], true);
    $packing = $progress >= 90 || in_array($status, ['packing','ready','shipped','complete'], true);
    $ready = $progress >= 100 || in_array($status, ['ready','shipped','complete'], true);
    $shipped = in_array($status, ['shipped','complete'], true);
    return [
        ['label'=>'Bestellung eingegangen','done'=>true],
        ['label'=>'Zahlung bestätigt','done'=>$paymentPaid],
        ['label'=>'Arbeit begonnen','done'=>$started],
        ['label'=>'Qualitätsprüfung','done'=>$quality],
        ['label'=>'Verpackung','done'=>$packing],
        ['label'=>'Versandbereit','done'=>$ready],
        ['label'=>'Versendet','done'=>$shipped],
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') status_out(['ok'=>false,'error'=>'Nur POST-Anfragen sind erlaubt.'], 405);

$now = time();
$attempts = is_array($_SESSION['rh24_order_status_attempts'] ?? null) ? $_SESSION['rh24_order_status_attempts'] : [];
$attempts = array_values(array_filter($attempts, static fn($ts) => is_int($ts) && $ts > $now - 300));
if (count($attempts) >= 20) status_out(['ok'=>false,'error'=>'Zu viele Abfragen. Bitte versuchen Sie es in einigen Minuten erneut.'], 429);
$attempts[] = $now;
$_SESSION['rh24_order_status_attempts'] = $attempts;

$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true);
if (!is_array($data)) $data = $_POST;
$orderNo = status_clean_order_no((string)($data['order_no'] ?? ''));
$email = strtolower(trim((string)($data['email'] ?? '')));
if ($orderNo === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    status_out(['ok'=>false,'error'=>'Bitte Bestellnummer und die bei der Bestellung verwendete E-Mail-Adresse eingeben.'], 422);
}

try {
    $db = rh24_db();
    $q = $db->prepare('SELECT order_no,status,status_label,payment_status,carrier,tracking,customer_json,items_json,totals_json,production_due_at,production_step,production_progress,production_started_at,production_finished_at,created_at,updated_at FROM orders WHERE order_no=? LIMIT 1');
    $q->execute([$orderNo]);
    $row = $q->fetch();
    if (!$row) status_out(['ok'=>false,'error'=>'Bestellung nicht gefunden oder E-Mail-Adresse stimmt nicht.'], 404);

    $customer = rh24_json_decode((string)($row['customer_json'] ?? ''), []);
    $storedEmail = strtolower(trim((string)($customer['email'] ?? '')));
    if ($storedEmail === '' || !hash_equals($storedEmail, $email)) {
        status_out(['ok'=>false,'error'=>'Bestellung nicht gefunden oder E-Mail-Adresse stimmt nicht.'], 404);
    }

    $progress = status_progress($row);
    [$phase, $message] = status_phase($row, $progress);
    $itemsRaw = rh24_json_decode((string)($row['items_json'] ?? ''), []);
    $items = [];
    if (is_array($itemsRaw)) {
        foreach ($itemsRaw as $it) {
            if (!is_array($it)) continue;
            $meta = []; if (is_array($it['meta'] ?? null)) { foreach ($it['meta'] as $mv) { if (is_scalar($mv) && trim((string)$mv) !== '') $meta[] = trim((string)$mv); } }
            $items[] = [
                'name'=>(string)($it['name'] ?? $it['id'] ?? 'Artikel'),
                'article_no'=>(string)($it['article_no'] ?? ''),
                'qty'=>max(1, (int)($it['qty'] ?? 1)),
                'meta'=>array_slice($meta, 0, 8),
            ];
        }
    }
    $totals = rh24_json_decode((string)($row['totals_json'] ?? ''), []);
    $gross = is_array($totals) ? (float)($totals['gross'] ?? 0) : 0.0;
    $tracking = trim((string)($row['tracking'] ?? ''));
    $carrier = trim((string)($row['carrier'] ?? ''));
    $documents=[];
    try{
      $dq=$db->prepare("SELECT document_type,document_no,issued_at FROM documents WHERE order_no=? AND document_type IN ('invoice','delivery_note') AND status='issued' ORDER BY document_type");$dq->execute([$orderNo]);
      foreach($dq->fetchAll() as $dr)$documents[(string)$dr['document_type']]=['document_no'=>(string)$dr['document_no'],'issued_at'=>rh24_iso($dr['issued_at']??null)];
    }catch(Throwable $e){}

    status_out(['ok'=>true,'order'=>[
        'order_no'=>(string)$row['order_no'],
        'status'=>(string)$row['status'],
        'phase'=>$phase,
        'message'=>$message,
        'progress'=>$progress,
        'created_at'=>rh24_iso($row['created_at'] ?? null),
        'updated_at'=>rh24_iso($row['updated_at'] ?? null),
        'production_started_at'=>rh24_iso($row['production_started_at'] ?? null),
        'production_finished_at'=>rh24_iso($row['production_finished_at'] ?? null),
        'production_due_at'=>rh24_iso($row['production_due_at'] ?? null),
        'items'=>$items,
        'gross'=>round($gross, 2),
        'carrier'=>$tracking !== '' ? $carrier : '',
        'tracking'=>$tracking,
        'documents'=>$documents,
        'timeline'=>status_timeline($row, $progress),
    ]]);
} catch (Throwable $e) {
    status_out(['ok'=>false,'error'=>'Der Bestellstatus ist momentan nicht erreichbar. Bitte versuchen Sie es erneut.'], 503);
}
