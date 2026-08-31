<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
rh24_require_login();
if(!rh24_can('view_documents')){http_response_code(403);exit('Keine Berechtigung.');}
$id=trim((string)($_GET['id']??''));if($id===''){http_response_code(422);exit('Dokument-ID fehlt.');}
$doc=rh24_document_row($id);if(!$doc){http_response_code(404);exit('Dokument nicht gefunden.');}
$pdf=rh24_document_pdf_content($doc);$filename=rh24_document_pdf_filename($doc);$download=!empty($_GET['download']);
header('Content-Type: application/pdf');header('Content-Length: '.strlen($pdf));header('Cache-Control: private, no-store, max-age=0');header('X-Content-Type-Options: nosniff');header('Content-Disposition: '.($download?'attachment':'inline').'; filename="'.$filename.'"');echo $pdf;
