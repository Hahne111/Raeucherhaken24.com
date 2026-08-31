<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
rh24_require_admin();
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$token=(string)rh24_setting_get('appointment_cron_token','');
if($token===''){
    $token=bin2hex(random_bytes(24));
    rh24_setting_set('appointment_cron_token',$token);
}
$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');
$scheme=$https?'https':'http';
$host=(string)($_SERVER['HTTP_HOST']??'');
$dir=rtrim(str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/orgaboard/termin-erinnerungen-einrichten.php'))),'/');
$url=$host!==''?$scheme.'://'.$host.$dir.'/appointment-reminders.php?token='.rawurlencode($token):'appointment-reminders.php?token='.rawurlencode($token);
function eh(string $v): string{return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>V92 · E-Mail-Erinnerungen</title><style>body{font-family:system-ui,-apple-system,sans-serif;background:#f5f2ed;color:#2d211a;margin:0;padding:28px}main{max-width:900px;margin:auto}.hero,.card{border-radius:20px;padding:24px;margin-bottom:16px}.hero{background:#2c2018;color:#fff}.card{background:#fff;border:1px solid #e2d9cf}.url{display:block;word-break:break-all;padding:14px;background:#f7f2ec;border-radius:12px;font-family:ui-monospace,monospace}.notice{padding:14px;border-left:4px solid #c75b26;background:#fff6ed;border-radius:10px}.actions{display:flex;gap:10px;flex-wrap:wrap}.actions a{padding:11px 15px;border-radius:10px;background:#34251c;color:#fff;text-decoration:none}</style></head><body><main><section class="hero"><small>ORGABOARD · V92 · ADMIN</small><h1>Automatische E-Mail-Erinnerungen</h1><p>Der Terminplaner kann fällige E-Mail-Erinnerungen automatisch über einen Server-Cronjob auslösen.</p></section><section class="card"><h2>Cron-Endpunkt</h2><p>Richte auf dem Webserver einen Cronjob ein, der diese URL regelmäßig aufruft. Ein Intervall von 5 bis 15 Minuten ist für Terminerinnerungen sinnvoll.</p><code class="url"><?=eh($url)?></code><p class="notice"><b>Vertraulich:</b> Die URL enthält einen geheimen Zugriffstoken. Nicht öffentlich weitergeben oder in Webseiten/Foren veröffentlichen.</p><p>Der Endpunkt versendet nur fällige Erinnerungen, protokolliert den Versand und verhindert Doppelversand über separate Versandzeitpunkte für Erinnerung 1 und 2.</p></section><section class="card"><h2>Ohne Cronjob</h2><p>Administratoren können im Termin-Cockpit jederzeit <b>„E-Mail-Erinnerungen prüfen“</b> anklicken. In-App-Erinnerungen im Orgaboard funktionieren unabhängig vom Cronjob.</p><div class="actions"><a href="index.php?v=92">Termin-Cockpit öffnen</a><a href="termin-diagnose.php?v=92">Termin-Diagnose</a></div></section></main></body></html>
