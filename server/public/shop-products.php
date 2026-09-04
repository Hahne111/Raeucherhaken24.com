<?php
declare(strict_types=1);
require __DIR__ . '/orgaboard/bootstrap.php';
require_once __DIR__ . '/orgaboard/avo-naturgewuerze-v2026.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
function rh24_public_plain($v): string {$t=html_entity_decode(strip_tags((string)$v),ENT_QUOTES,'UTF-8');$t=preg_replace('/\s+/u',' ',(string)$t);return trim((string)$t);}
try{
  $db=rh24_db();
  // V2026.6: Einmaliger, artikelgenauer Abgleich der acht beauftragten
  // AVO-BIO-Naturgewürze. Andere Produkte und spätere Bestandsbewegungen
  // bleiben vollständig unberührt.
  rh24_apply_avo_naturgewuerze_v2026($db);
  // V108.3: Ein einziges, dauerhaftes Veröffentlichungsmerkmal.
  // published_at ist die verbindliche Quelle. Keine zusätzliche Register-Tabelle
  // und damit keine Abhängigkeit von CREATE-TABLE-Rechten beim Webhosting.
  $hasPublishedAt=false;
  try{$cq=$db->query("SHOW COLUMNS FROM products LIKE 'published_at'");$hasPublishedAt=(bool)$cq->fetch();}catch(Throwable $ignore){}
  if($hasPublishedAt){
    // Bestehende aktive/online Produkte sauber migrieren.
    try{$db->exec("UPDATE products SET published_at=COALESCE(published_at,NOW()) WHERE COALESCE(product_type,'product')<>'prototype' AND status='active' AND shop_visible=1 AND published_at IS NULL");}catch(Throwable $ignore){}
    // Gewünschten Dreifachdorn gezielt wieder veröffentlichen, sofern er im Produktstamm existiert.
    try{$repair=$db->prepare("UPDATE products SET status='active',shop_visible=1,published_at=COALESCE(published_at,NOW()),updated_at=NOW() WHERE (article_no=? OR name LIKE ?) AND COALESCE(product_type,'product')<>'prototype'");$repair->execute(['20005','%Dreifachdorn%']);}catch(Throwable $ignore){}
    $sql="SELECT p.*,i.stock FROM products p LEFT JOIN inventory i ON i.id=p.id WHERE COALESCE(p.product_type,'product')<>'prototype' AND p.published_at IS NOT NULL ORDER BY CASE WHEN p.is_new=1 AND (p.new_until IS NULL OR p.new_until>=NOW()) THEN 0 ELSE 1 END,p.category,p.name";
  }else{
    // Rückwärtskompatibler Fallback für alte Installationen ohne published_at-Spalte.
    $sql="SELECT p.*,i.stock FROM products p LEFT JOIN inventory i ON i.id=p.id WHERE COALESCE(p.product_type,'product')<>'prototype' AND p.status='active' AND p.shop_visible=1 ORDER BY p.category,p.name";
  }
  $rows=$db->query($sql)->fetchAll();$out=[];
  foreach($rows as $r){
    $c=['id'=>(string)$r['id'],'sku'=>(string)$r['sku'],'article_no'=>(string)$r['article_no'],'barcode'=>(string)($r['barcode']??''),'name'=>(string)$r['name'],'category'=>(string)$r['category'],'type'=>(string)$r['product_type'],'base'=>(float)$r['base_price'],'unit'=>(string)$r['unit'],'product_weight_g'=>(int)($r['product_weight_g']??0),'shipping_weight_g'=>(int)($r['shipping_weight_g']??0),'description'=>rh24_public_plain($r['description']??''),'description_rich'=>(string)($r['description']??''),'short_description'=>rh24_public_plain($r['short_description']??''),'short_description_rich'=>(string)($r['short_description']??''),'features'=>rh24_json_decode((string)($r['features_json']??''),[]),'features_rich'=>(string)($r['features_rich']??''),'seo_title'=>(string)($r['seo_title']??''),'seo_description'=>(string)($r['seo_description']??''),'seo_keywords'=>(string)($r['seo_keywords']??''),'image'=>(string)($r['image_path']??''),'updated_at'=>(string)($r['updated_at']??''),'stock'=>(int)($r['stock']??0),'is_popular'=>(bool)($r['is_popular']??0),'is_offer'=>(bool)($r['is_offer']??0),'is_new'=>(bool)($r['is_new']??0),'new_until'=>(string)($r['new_until']??''),'published_at'=>(string)($r['published_at']??$r['registry_published_at']??''),'old_price'=>(float)($r['old_price']??0),'sale_price'=>(float)($r['sale_price']??0),'sale_start_at'=>(string)($r['sale_start_at']??''),'sale_end_at'=>(string)($r['sale_end_at']??''),'price_basis'=>(string)($r['price_basis']??'auto'),'pack_quantity'=>max(1,(int)($r['pack_quantity']??1))];
    $saleActive=rh24_sale_is_active($c);$newActive=rh24_product_is_new_active($c);$effective=rh24_effective_base($c);$unitMeta=rh24_unit_price_meta($c,$effective);
    $out[]=$c+['is_new_active'=>$newActive,'price'=>$effective,'normal_price'=>(float)$r['base_price'],'sale_active'=>$saleActive,'display_old_price'=>($saleActive?(float)($r['old_price']??0):0),'unit_price'=>$unitMeta,'url'=>rh24_product_public_url((string)$r['id'])];
  }

  $catalogUpdatedAt=(string)($db->query("SELECT COALESCE(MAX(updated_at),'') FROM products")->fetchColumn()?:'');
  echo json_encode(['ok'=>true,'data_version'=>'2026.6','generated_at'=>date('c'),'catalog_updated_at'=>$catalogUpdatedAt,'theme'=>(string)rh24_setting_get('active_theme','standard'),'shop'=>['shipping_threshold'=>(float)rh24_setting_get('shipping_threshold','39'),'shipping_cost'=>(float)rh24_setting_get('shipping_cost','7'),'vat_rate'=>(float)rh24_setting_get('vat_rate','19')],'products'=>$out],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){http_response_code(503);echo json_encode(['ok'=>false,'error'=>'Produktkatalog vorübergehend nicht verfügbar'],JSON_UNESCAPED_UNICODE);}
