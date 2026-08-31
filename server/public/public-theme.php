<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$allowed=['standard','weihnachten','nikolaus','ostern','advent','black_week','black_friday','silvester','neujahr'];
$theme='standard';$source='default';
try{
  require __DIR__ . '/orgaboard/bootstrap.php';
  $candidate=(string)rh24_setting_get('active_theme','standard');
  if(in_array($candidate,$allowed,true)){$theme=$candidate;$source='database';}
}catch(Throwable $e){
  $f=__DIR__.'/data/active-theme.json';
  if(is_file($f)){
    $d=json_decode((string)@file_get_contents($f),true);
    $candidate=(string)($d['theme']??'');
    if(in_array($candidate,$allowed,true)){$theme=$candidate;$source='file-fallback';}
  }
}
echo json_encode(['ok'=>true,'theme'=>$theme,'source'=>$source,'version'=>'55'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
