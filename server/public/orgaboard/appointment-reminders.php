<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/bootstrap.php';

function reminder_out(array $data,int $status=200): never {
    http_response_code($status);
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $configured=(string)rh24_setting_get('appointment_cron_token','');
    $provided=trim((string)($_GET['token']??$_POST['token']??''));
    $authorizedToken=$configured!==''&&$provided!==''&&hash_equals($configured,$provided);
    $authorizedAdmin=rh24_is_admin();
    if(!$authorizedToken&&!$authorizedAdmin) reminder_out(['ok'=>false,'error'=>'Nicht autorisiert.'],403);
    if($authorizedAdmin && $_SERVER['REQUEST_METHOD']==='POST') {
        $body=json_decode((string)file_get_contents('php://input'),true)?:$_POST;
        rh24_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??($body['csrf']??null));
    }
    $rep=trim((string)($_GET['sales_rep_id']??$_POST['sales_rep_id']??''));
    $result=rh24_appointment_send_due_reminders($rep!==''?$rep:null);
    reminder_out(['ok'=>true,'result'=>$result]);
} catch(Throwable $e) {
    reminder_out(['ok'=>false,'error'=>$e->getMessage()],500);
}
