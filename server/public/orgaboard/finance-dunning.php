<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';rh24_require_permission('view_finance');
$orderNo=trim((string)($_GET['order_no']??''));if($orderNo===''){http_response_code(400);exit('Bestellung fehlt.');}
try{$pdf=rh24_finance_dunning_pdf($orderNo);$r=rh24_finance_dunning_record($orderNo);$level=max(1,(int)($r['dunning']['dunning_level']??1));$title=rh24_finance_dunning_title($level);$filename=preg_replace('/[^A-Za-z0-9._-]+/','-',$title.'-'.$orderNo).'.pdf';header('Content-Type: application/pdf');header('Content-Length: '.strlen($pdf));header('Content-Disposition: '.(!empty($_GET['download'])?'attachment':'inline').'; filename="'.$filename.'"');header('Cache-Control: private, no-store');echo $pdf;}catch(Throwable $e){http_response_code(404);header('Content-Type: text/plain; charset=UTF-8');echo $e->getMessage();}
