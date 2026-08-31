<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
rh24_require_login();
if(!rh24_can('view_appointments')){http_response_code(403);exit('Keine Berechtigung.');}
$id=trim((string)($_GET['id']??''));
if($id===''){http_response_code(400);exit('Termin fehlt.');}
$q=rh24_db()->prepare('SELECT * FROM advisor_appointments WHERE id=? LIMIT 1');$q->execute([$id]);$a=$q->fetch();
if(!$a){http_response_code(404);exit('Termin nicht gefunden.');}
rh24_appointment_assert_owned_rep((string)$a['sales_rep_id']);
$ics=rh24_appointment_ics_content($a);
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="termin-'.preg_replace('/[^A-Za-z0-9_-]/','-',$id).'.ics"');
header('Cache-Control: no-store');echo $ics;
