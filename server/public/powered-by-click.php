<?php
declare(strict_types=1);
$destination='https://www.hd-hahne.de/';
$page=trim((string)($_GET['page']??$_POST['page']??''));
if(strlen($page)>255)$page=substr($page,0,255);
try {
    require_once __DIR__.'/orgaboard/bootstrap.php';
    if(rh24_db_configured()){
        $db=rh24_db();
        if(rh24_hd_click_table_ready($db)){
            $key='hd-'.bin2hex(random_bytes(18));
            $st=$db->prepare("INSERT IGNORE INTO external_link_clicks(click_key,target,page,created_at) VALUES(?,?,?,NOW())");
            $st->execute([$key,'hd_hahne',$page]);
        }
    }
} catch(Throwable $e) { /* Tracking darf die Zielnavigation niemals blockieren. */ }
if(($_GET['go']??'')==='1'){
    header('Cache-Control: no-store');
    header('Location: '.$destination, true, 302);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['ok'=>true],JSON_UNESCAPED_SLASHES);
