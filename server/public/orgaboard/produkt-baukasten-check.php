<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');require_once __DIR__.'/bootstrap.php';
if (!rh24_is_logged_in()) { header('Location: index.php'); exit; }if (!rh24_is_admin()) { http_response_code(403); exit('Nur Administrator.'); }
$index=(string)@file_get_contents(__DIR__.'/index.php');$js=(string)@file_get_contents(__DIR__.'/admin-v9911.js');$css=(string)@file_get_contents(__DIR__.'/assets/product-builder-v83.css');$api=(string)@file_get_contents(__DIR__.'/api.php');$img=(string)@file_get_contents(__DIR__.'/product-image.php');
$checks=[
 ['Laufzeitdatei',str_contains($index,'admin-v9911.js'),'index.php lädt die aktuelle Laufzeitdatei (V9911)'],
 ['Eigener Menüpunkt',str_contains($index,'data-view="productbuilder"'),'Produkt-Baukasten steht direkt im Menü'],
 ['Live-Vorschau',str_contains($js,'builderPreviewImage'),'Vorschau ist im aktiven Baukasten enthalten'],
 ['Cross-Selling Oberfläche',str_contains($js,'crossSellBuilder')&&str_contains($js,'prCrossSearch')&&str_contains($js,'crossAutoSuggest'),'Suche, Mehrfachauswahl und Vorschlagslogik vorhanden'],
 ['Cross-Selling Funktionen',str_contains($js,'cross_sell_reciprocal')&&str_contains($js,'cross_sell_auto')&&str_contains($js,'data-cross-priority'),'Auto-Fill, gegenseitige Verknüpfung und Priorität vorhanden'],
 ['Cross-Selling API',str_contains($api,'cross_sell_json')&&str_contains($api,'rh24_cross_sell_clean'),'Backend-Persistenz und Validierung vorhanden'],
 ['Bildformate',str_contains($js,'.avif')&&str_contains($js,'.heic')&&str_contains($js,'.heif')&&str_contains($img,"'image/avif'")&&str_contains($img,"'image/heic'"),'JPG/JPEG, PNG, WebP, AVIF, HEIC und HEIF'],
 ['Browser-Optimierung',str_contains($js,'prepareProductImage')&&str_contains($js,"canvas.toBlob(resolve,'image/webp',0.86)")&&str_contains($js,'const max=2000'),'automatisch WebP, Qualität 86 %, max. 2000 px'],
 ['Server-Fallback',str_contains($img,'rh24_imagick_to_webp')&&str_contains($img,'rh24_gd_to_webp'),'Imagick/GD-Fallback und HEIC-Hinweis vorhanden'],
 ['Artikel löschen',str_contains($js,'data-delete-product')&&str_contains($js,'productDeleteModal'),'Löschbutton und Sicherheitsdialog vorhanden'],
 ['Lösch-API',str_contains($api,"if(\$action==='product_delete')"),'Backend für endgültiges Löschen vorhanden'],
 ['Nur eine aktive Produktzentrale',substr_count($js,'function renderProducts(')===1,'renderProducts() genau 1×'],
 ['Nur ein aktiver Baukasten',substr_count($js,'function productModal(')===1,'productModal() genau 1×'],
 ['Baukasten-Styling',strlen($css)>500,'separate Baukasten-CSS geladen'],
];$ok=count(array_filter($checks,fn($x)=>$x[1]));
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Produkt-Baukasten V86 Check</title><style>body{font-family:system-ui;background:#f5f2ed;color:#27180f;padding:24px}.wrap{max-width:900px;margin:auto}.card{background:#fff;border:1px solid #ded4ca;border-radius:18px;padding:22px;margin:14px 0;box-shadow:0 10px 30px #0001}.ok{color:#16723c}.bad{color:#a72e2a}.row{display:grid;grid-template-columns:1.3fr .5fr 2fr;gap:14px;padding:11px 0;border-bottom:1px solid #eee}.row:last-child{border:0}.score{font-size:30px;font-weight:900}.btn{display:inline-block;padding:12px 15px;border-radius:10px;background:#4d2b18;color:#fff;text-decoration:none;margin:5px 7px 5px 0}@media(max-width:650px){.row{grid-template-columns:1fr}}</style></head><body><div class="wrap"><div class="card"><small>RÄUCHERHAKEN24 · V86</small><h1>Produkt-Baukasten Prüfung</h1><div class="score <?=$ok===count($checks)?'ok':'bad'?>"><?=$ok?> / <?=count($checks)?> Prüfungen OK</div><p>Prüft Produkt-Baukasten, Cross-Selling, Bildformate und automatische WebP-Optimierung.</p></div><div class="card"><?php foreach($checks as [$name,$pass,$detail]):?><div class="row"><b><?=htmlspecialchars($name)?></b><strong class="<?=$pass?'ok':'bad'?>"><?=$pass?'OK':'FEHLER'?></strong><span><?=htmlspecialchars($detail)?></span></div><?php endforeach;?></div><div class="card"><a class="btn" href="index.php?view=productbuilder&v=86">Produkt-Baukasten direkt öffnen</a><a class="btn" href="index.php?view=products&v=86">Produktzentrale öffnen</a><a class="btn" href="diagnose.php?v=86">Systemdiagnose</a></div></div></body></html>
