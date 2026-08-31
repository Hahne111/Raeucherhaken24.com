<?php
declare(strict_types=1);

/* Räucherhaken24 Orgaboard V94 · POS & Kasse Pro */

function rh24_pos_required_tables(): array {
    return ['pos_registers','pos_shifts','pos_sales','pos_sale_items','pos_payments','pos_cash_movements','pos_daily_closings','pos_tse_events'];
}
function rh24_pos_schema_health(PDO $db): array {
    $missing=[];
    foreach(rh24_pos_required_tables() as $t){
        try{$q=$db->prepare('SHOW TABLES LIKE ?');$q->execute([$t]);if(!$q->fetchColumn())$missing[]=$t;}catch(Throwable){$missing[]=$t;}
    }
    return ['ready'=>!$missing,'missing'=>$missing,'version'=>(int)rh24_setting_get('pos_schema_version','0')];
}
function rh24_ensure_v94_pos_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS pos_registers (
      id VARCHAR(50) NOT NULL PRIMARY KEY,
      code VARCHAR(30) NOT NULL,
      name VARCHAR(160) NOT NULL,
      location VARCHAR(220) NOT NULL DEFAULT '',
      status VARCHAR(20) NOT NULL DEFAULT 'active',
      currency CHAR(3) NOT NULL DEFAULT 'EUR',
      software_serial VARCHAR(100) NOT NULL DEFAULT '',
      receipt_prefix VARCHAR(20) NOT NULL DEFAULT 'KASSE',
      next_receipt_no BIGINT UNSIGNED NOT NULL DEFAULT 1,
      tse_provider VARCHAR(40) NOT NULL DEFAULT 'gateway',
      tse_serial VARCHAR(180) NOT NULL DEFAULT '',
      tse_client_id VARCHAR(180) NOT NULL DEFAULT '',
      printer_profile_json LONGTEXT NULL,
      scanner_profile_json LONGTEXT NULL,
      cash_drawer_profile_json LONGTEXT NULL,
      notes VARCHAR(500) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      UNIQUE KEY uq_pos_register_code(code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_shifts (
      id VARCHAR(60) NOT NULL PRIMARY KEY,
      register_id VARCHAR(50) NOT NULL,
      user_id VARCHAR(60) NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'open',
      opened_at DATETIME NOT NULL,
      closed_at DATETIME NULL,
      opening_cash DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      expected_cash DECIMAL(14,2) NULL,
      counted_cash DECIMAL(14,2) NULL,
      cash_difference DECIMAL(14,2) NULL,
      opening_note VARCHAR(500) NOT NULL DEFAULT '',
      closing_note VARCHAR(500) NOT NULL DEFAULT '',
      closed_by VARCHAR(60) NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      KEY idx_pos_shift_register(register_id,status,opened_at),
      KEY idx_pos_shift_user(user_id,status,opened_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_sales (
      id VARCHAR(60) NOT NULL PRIMARY KEY,
      receipt_no VARCHAR(80) NULL,
      register_id VARCHAR(50) NOT NULL,
      shift_id VARCHAR(60) NOT NULL,
      user_id VARCHAR(60) NOT NULL,
      customer_id VARCHAR(40) NULL,
      order_no VARCHAR(80) NULL,
      original_sale_id VARCHAR(60) NULL,
      sale_type VARCHAR(20) NOT NULL DEFAULT 'sale',
      status VARCHAR(24) NOT NULL DEFAULT 'draft',
      fiscal_mode VARCHAR(20) NOT NULL DEFAULT 'training',
      currency CHAR(3) NOT NULL DEFAULT 'EUR',
      subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      net_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      gross_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      tendered_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      change_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      customer_json LONGTEXT NULL,
      note VARCHAR(700) NOT NULL DEFAULT '',
      fiscal_transaction_no VARCHAR(180) NOT NULL DEFAULT '',
      fiscal_signature_counter VARCHAR(120) NOT NULL DEFAULT '',
      fiscal_start_at DATETIME NULL,
      fiscal_end_at DATETIME NULL,
      fiscal_signature TEXT NULL,
      fiscal_tse_serial VARCHAR(180) NOT NULL DEFAULT '',
      fiscal_client_serial VARCHAR(180) NOT NULL DEFAULT '',
      fiscal_qr_payload TEXT NULL,
      fiscal_status VARCHAR(30) NOT NULL DEFAULT 'pending',
      cancellation_reason VARCHAR(500) NOT NULL DEFAULT '',
      completed_at DATETIME NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      UNIQUE KEY uq_pos_receipt_no(receipt_no),
      UNIQUE KEY uq_pos_order_no(order_no),
      KEY idx_pos_sale_shift(shift_id,status,created_at),
      KEY idx_pos_sale_register(register_id,created_at),
      KEY idx_pos_sale_user(user_id,created_at),
      KEY idx_pos_sale_original(original_sale_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_sale_items (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      sale_id VARCHAR(60) NOT NULL,
      original_item_id BIGINT UNSIGNED NULL,
      line_no SMALLINT UNSIGNED NOT NULL,
      product_id VARCHAR(80) NULL,
      sku VARCHAR(100) NOT NULL DEFAULT '',
      article_no VARCHAR(40) NOT NULL DEFAULT '',
      barcode VARCHAR(100) NOT NULL DEFAULT '',
      name VARCHAR(220) NOT NULL,
      qty DECIMAL(12,3) NOT NULL DEFAULT 1.000,
      unit VARCHAR(80) NOT NULL DEFAULT 'Stück',
      unit_price DECIMAL(14,2) NOT NULL,
      discount_percent DECIMAL(6,2) NOT NULL DEFAULT 0.00,
      discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      tax_rate DECIMAL(6,2) NOT NULL DEFAULT 19.00,
      net_amount DECIMAL(14,2) NOT NULL,
      tax_amount DECIMAL(14,2) NOT NULL,
      gross_amount DECIMAL(14,2) NOT NULL,
      meta_json LONGTEXT NULL,
      created_at DATETIME NOT NULL,
      KEY idx_pos_item_sale(sale_id,line_no),
      KEY idx_pos_item_original(original_item_id),
      KEY idx_pos_item_product(product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_payments (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      sale_id VARCHAR(60) NOT NULL,
      payment_method VARCHAR(40) NOT NULL,
      provider VARCHAR(60) NOT NULL DEFAULT '',
      amount DECIMAL(14,2) NOT NULL,
      transaction_ref VARCHAR(180) NOT NULL DEFAULT '',
      status VARCHAR(30) NOT NULL DEFAULT 'captured',
      created_at DATETIME NOT NULL,
      KEY idx_pos_payment_sale(sale_id),
      KEY idx_pos_payment_method(payment_method,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_cash_movements (
      id VARCHAR(60) NOT NULL PRIMARY KEY,
      register_id VARCHAR(50) NOT NULL,
      shift_id VARCHAR(60) NOT NULL,
      user_id VARCHAR(60) NOT NULL,
      sale_id VARCHAR(60) NULL,
      movement_type VARCHAR(30) NOT NULL,
      amount DECIMAL(14,2) NOT NULL,
      reason VARCHAR(300) NOT NULL DEFAULT '',
      finance_entry_id VARCHAR(60) NULL,
      created_at DATETIME NOT NULL,
      KEY idx_pos_cash_shift(shift_id,created_at),
      KEY idx_pos_cash_register(register_id,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_daily_closings (
      id VARCHAR(60) NOT NULL PRIMARY KEY,
      register_id VARCHAR(50) NOT NULL,
      shift_id VARCHAR(60) NOT NULL,
      business_date DATE NOT NULL,
      user_id VARCHAR(60) NOT NULL,
      gross_sales DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      refunds DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      net_sales DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      tax_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      cash_expected DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      cash_counted DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      cash_difference DECIMAL(14,2) NOT NULL DEFAULT 0.00,
      payments_json LONGTEXT NOT NULL,
      z_report_no VARCHAR(80) NOT NULL,
      closed_at DATETIME NOT NULL,
      created_at DATETIME NOT NULL,
      UNIQUE KEY uq_pos_closing_shift(shift_id),
      UNIQUE KEY uq_pos_z_report(z_report_no),
      KEY idx_pos_closing_date(business_date,register_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pos_tse_events (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      sale_id VARCHAR(60) NULL,
      register_id VARCHAR(50) NOT NULL,
      event_type VARCHAR(40) NOT NULL,
      provider VARCHAR(40) NOT NULL DEFAULT '',
      request_json LONGTEXT NULL,
      response_json LONGTEXT NULL,
      success TINYINT(1) NOT NULL DEFAULT 0,
      message VARCHAR(700) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL,
      KEY idx_pos_tse_sale(sale_id,created_at),
      KEY idx_pos_tse_register(register_id,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try{$cols=$db->query("SHOW COLUMNS FROM pos_sale_items LIKE 'original_item_id'")->fetchAll();if(!$cols)$db->exec("ALTER TABLE pos_sale_items ADD COLUMN original_item_id BIGINT UNSIGNED NULL AFTER sale_id, ADD KEY idx_pos_item_original(original_item_id)");}catch(Throwable){}

    try{$q=$db->query('SELECT COUNT(*) FROM pos_registers');if((int)$q->fetchColumn()===0){
        $db->prepare("INSERT INTO pos_registers(id,code,name,location,status,currency,software_serial,receipt_prefix,next_receipt_no,tse_provider,created_at,updated_at) VALUES('REG-01','KASSE-01','Hauptkasse','', 'active','EUR',?,'KASSE',1,'gateway',NOW(),NOW())")
           ->execute(['RH24-POS-'.strtoupper(substr(hash('sha256',__DIR__),0,16))]);
    }}catch(Throwable){}
    rh24_setting_set('pos_schema_version','94');
    if((string)rh24_setting_get('pos_fiscal_mode','')==='')rh24_setting_set('pos_fiscal_mode','training');
    if((string)rh24_setting_get('pos_tse_gateway_url','')==='')rh24_setting_set('pos_tse_gateway_url','');
}
function rh24_pos_ensure_ready(): array {
    $db=rh24_db();$h=rh24_pos_schema_health($db);if(!$h['ready']){rh24_ensure_v94_pos_schema($db);$h=rh24_pos_schema_health($db);}return $h;
}
function rh24_pos_config(): array {
    return [
      'fiscal_mode'=>(string)rh24_setting_get('pos_fiscal_mode','training'),
      'tse_gateway_url'=>(string)rh24_setting_get('pos_tse_gateway_url',''),
      'tse_gateway_configured'=>(string)rh24_setting_get('pos_tse_gateway_url','')!=='',
      'tse_gateway_token_set'=>(string)rh24_setting_get('pos_tse_gateway_token','')!=='',
      'allow_negative_stock'=>(string)rh24_setting_get('pos_allow_negative_stock','0')==='1',
      'receipt_footer'=>(string)rh24_setting_get('pos_receipt_footer','Vielen Dank für Ihren Einkauf bei Räucherhaken24.'),
      'default_tax_rate'=>(float)rh24_setting_get('vat_rate','19'),
      'software_brand'=>'Räucherhaken24 Orgaboard POS',
      'software_version'=>'94.0',
    ];
}
function rh24_pos_registers(): array {
    $rows=rh24_db()->query("SELECT * FROM pos_registers ORDER BY status='active' DESC,name")->fetchAll();
    foreach($rows as &$r){$r['printer_profile']=rh24_json_decode($r['printer_profile_json']??'',[]);$r['scanner_profile']=rh24_json_decode($r['scanner_profile_json']??'',[]);$r['cash_drawer_profile']=rh24_json_decode($r['cash_drawer_profile_json']??'',[]);unset($r['printer_profile_json'],$r['scanner_profile_json'],$r['cash_drawer_profile_json']);}unset($r);return $rows;
}
function rh24_pos_users(): array {
    $rows=rh24_db()->query("SELECT id,username,display_name,email,role,status FROM users WHERE status='active' ORDER BY display_name")->fetchAll();
    return array_values(array_filter($rows,fn($u)=>($u['role']??'')==='admin'||in_array('operate_pos',rh24_user_permissions_by_id((string)$u['id']),true)));
}
function rh24_user_permissions_by_id(string $id): array {
    try{$q=rh24_db()->prepare('SELECT role,permissions_json FROM users WHERE id=?');$q->execute([$id]);$r=$q->fetch();if(!$r)return[];if((string)$r['role']==='admin')return['*'];$p=rh24_json_decode($r['permissions_json']??'',null);return is_array($p)?$p:rh24_default_permissions_for_role((string)$r['role']);}catch(Throwable){return[];}
}
function rh24_pos_open_shift(string $registerId='',string $userId=''): ?array {
    $db=rh24_db();$where=[];$vals=[];if($registerId!==''){$where[]='s.register_id=?';$vals[]=$registerId;}if($userId!==''){$where[]='s.user_id=?';$vals[]=$userId;}$sql="SELECT s.*,r.name register_name,r.code register_code,u.display_name user_name FROM pos_shifts s JOIN pos_registers r ON r.id=s.register_id LEFT JOIN users u ON u.id=s.user_id WHERE s.status='open'".($where?' AND '.implode(' AND ',$where):'').' ORDER BY s.opened_at DESC LIMIT 1';$q=$db->prepare($sql);$q->execute($vals);$r=$q->fetch();return $r?:null;
}
function rh24_pos_shift_stats(string $shiftId): array {
    $db=rh24_db();$q=$db->prepare("SELECT s.*,r.name register_name,r.code register_code,u.display_name user_name FROM pos_shifts s JOIN pos_registers r ON r.id=s.register_id LEFT JOIN users u ON u.id=s.user_id WHERE s.id=?");$q->execute([$shiftId]);$shift=$q->fetch();if(!$shift)throw new RuntimeException('Schicht nicht gefunden.');
    $q=$db->prepare("SELECT COALESCE(SUM(CASE WHEN sale_type='sale' AND status='completed' THEN gross_amount ELSE 0 END),0) gross_sales,COALESCE(SUM(CASE WHEN sale_type='refund' AND status='completed' THEN ABS(gross_amount) ELSE 0 END),0) refunds,COALESCE(SUM(CASE WHEN status='completed' THEN tax_amount ELSE 0 END),0) tax_total,COUNT(CASE WHEN status='completed' THEN 1 END) receipts FROM pos_sales WHERE shift_id=?");$q->execute([$shiftId]);$sales=$q->fetch()?:[];
    $q=$db->prepare("SELECT payment_method,SUM(amount) amount FROM pos_payments p JOIN pos_sales s ON s.id=p.sale_id WHERE s.shift_id=? AND s.status='completed' GROUP BY payment_method");$q->execute([$shiftId]);$payments=$q->fetchAll();
    // Zahlungsarten im Z-Bericht zeigen den tatsächlich vereinnahmten Betrag. Bei Barzahlung darf ausgezahltes Rückgeld nicht als Umsatz erscheinen.
    $q=$db->prepare("SELECT COALESCE(SUM(change_amount),0) FROM pos_sales WHERE shift_id=? AND status='completed' AND sale_type='sale'");$q->execute([$shiftId]);$cashChange=(float)$q->fetchColumn();
    if($cashChange>0){foreach($payments as &$payment){if((string)($payment['payment_method']??'')==='cash'){$payment['amount']=round((float)$payment['amount']-$cashChange,2);break;}}unset($payment);}
    $q=$db->prepare('SELECT COALESCE(SUM(amount),0) FROM pos_cash_movements WHERE shift_id=?');$q->execute([$shiftId]);$movement=(float)$q->fetchColumn();$expected=round((float)$shift['opening_cash']+$movement,2);
    return ['shift'=>$shift,'gross_sales'=>(float)($sales['gross_sales']??0),'refunds'=>(float)($sales['refunds']??0),'net_sales'=>round((float)($sales['gross_sales']??0)-(float)($sales['refunds']??0),2),'tax_total'=>(float)($sales['tax_total']??0),'receipts'=>(int)($sales['receipts']??0),'payments'=>$payments,'cash_movement'=>$movement,'expected_cash'=>$expected];
}
function rh24_pos_sales(int $limit=250): array {
    $limit=max(1,min(1000,$limit));$rows=rh24_db()->query("SELECT s.*,r.name register_name,u.display_name user_name,c.name customer_name FROM pos_sales s JOIN pos_registers r ON r.id=s.register_id LEFT JOIN users u ON u.id=s.user_id LEFT JOIN customers c ON c.id=s.customer_id ORDER BY s.created_at DESC LIMIT ".$limit)->fetchAll();
    foreach($rows as &$r){$r['customer']=rh24_json_decode($r['customer_json']??'',[]);unset($r['customer_json']);$r['subtotal']=(float)$r['subtotal'];$r['discount_amount']=(float)$r['discount_amount'];$r['net_amount']=(float)$r['net_amount'];$r['tax_amount']=(float)$r['tax_amount'];$r['gross_amount']=(float)$r['gross_amount'];$r['tendered_amount']=(float)$r['tendered_amount'];$r['change_amount']=(float)$r['change_amount'];}unset($r);return $rows;
}
function rh24_pos_sale_detail(string $id): array {
    $db=rh24_db();$q=$db->prepare("SELECT s.*,r.name register_name,r.code register_code,r.software_serial,r.tse_serial register_tse_serial,u.display_name user_name,c.name customer_name,c.email customer_email FROM pos_sales s JOIN pos_registers r ON r.id=s.register_id LEFT JOIN users u ON u.id=s.user_id LEFT JOIN customers c ON c.id=s.customer_id WHERE s.id=?");$q->execute([$id]);$sale=$q->fetch();if(!$sale)throw new RuntimeException('Kassenvorgang nicht gefunden.');$sale['customer']=rh24_json_decode($sale['customer_json']??'',[]);unset($sale['customer_json']);$q=$db->prepare('SELECT * FROM pos_sale_items WHERE sale_id=? ORDER BY line_no,id');$q->execute([$id]);$items=$q->fetchAll();foreach($items as &$i){$i['meta']=rh24_json_decode($i['meta_json']??'',[]);unset($i['meta_json']);}unset($i);$q=$db->prepare('SELECT * FROM pos_payments WHERE sale_id=? ORDER BY id');$q->execute([$id]);$payments=$q->fetchAll();return ['sale'=>$sale,'items'=>$items,'payments'=>$payments];
}
function rh24_pos_next_receipt_no(PDO $db,string $registerId): string {
    $q=$db->prepare('SELECT receipt_prefix,next_receipt_no FROM pos_registers WHERE id=? FOR UPDATE');$q->execute([$registerId]);$r=$q->fetch();if(!$r)throw new RuntimeException('Kasse nicht gefunden.');$n=max(1,(int)$r['next_receipt_no']);$db->prepare('UPDATE pos_registers SET next_receipt_no=?,updated_at=NOW() WHERE id=?')->execute([$n+1,$registerId]);return trim((string)$r['receipt_prefix']).'-'.date('Ymd').'-'.str_pad((string)$n,6,'0',STR_PAD_LEFT);
}
function rh24_pos_product_rows(): array {
    $out=[];foreach(rh24_catalog() as $id=>$p){if(($p['status']??'active')!=='active'||$id==='prototype-project')continue;$out[]=['id'=>$id,'sku'=>$p['sku']??'','article_no'=>$p['article_no']??'','barcode'=>$p['barcode']??'','name'=>$p['name']??$id,'category'=>$p['category']??'Sonstiges','price'=>(float)($p['effective_price']??$p['base']??0),'base'=>(float)($p['base']??0),'unit'=>$p['unit']??'Stück','type'=>$p['type']??'product','stock'=>(int)($p['stock']??0),'minimum'=>(int)($p['minimum']??0),'image'=>$p['image']??'','shop_visible'=>(bool)($p['shop_visible']??false),'tax_rate'=>(float)($p['tax_rate']??$p['vat_rate']??rh24_pos_config()['default_tax_rate'])];}return $out;
}
function rh24_pos_customer_rows(): array {
    $rows=rh24_customers();return array_map(fn($c)=>['id'=>$c['id'],'name'=>$c['name'],'company'=>$c['company']??'','email'=>$c['email']??'','phone'=>$c['phone']??'','discount_percent'=>(float)($c['discount_percent']??0),'status'=>$c['status']??'active'],$rows);
}
function rh24_pos_drafts_for_user(): array {
    $q=rh24_db()->prepare("SELECT id,register_id,shift_id,customer_id,subtotal,discount_amount,gross_amount,note,created_at,updated_at FROM pos_sales WHERE status='draft' AND user_id=? ORDER BY updated_at DESC LIMIT 20");$q->execute([rh24_user_id()]);return $q->fetchAll();
}
function rh24_pos_closings(int $limit=120): array {
    $limit=max(1,min(500,$limit));return rh24_db()->query("SELECT z.*,r.name register_name,r.code register_code,u.display_name user_name FROM pos_daily_closings z JOIN pos_registers r ON r.id=z.register_id LEFT JOIN users u ON u.id=z.user_id ORDER BY z.closed_at DESC LIMIT ".$limit)->fetchAll();
}
function rh24_pos_shift_history(int $limit=120): array {
    $limit=max(1,min(500,$limit));return rh24_db()->query("SELECT s.*,r.name register_name,r.code register_code,u.display_name user_name FROM pos_shifts s JOIN pos_registers r ON r.id=s.register_id LEFT JOIN users u ON u.id=s.user_id ORDER BY s.opened_at DESC LIMIT ".$limit)->fetchAll();
}
function rh24_pos_data(): array {
    rh24_pos_ensure_ready();$shift=rh24_pos_open_shift('',rh24_user_id());$shiftStats=$shift?rh24_pos_shift_stats((string)$shift['id']):null;$reports=rh24_can('pos_reports')||rh24_is_admin();return ['config'=>rh24_pos_config(),'registers'=>rh24_pos_registers(),'open_shift'=>$shift,'shift_stats'=>$shiftStats,'products'=>rh24_pos_product_rows(),'customers'=>rh24_pos_customer_rows(),'sales'=>$reports?rh24_pos_sales(300):array_values(array_filter(rh24_pos_sales(150),fn($s)=>(string)$s['user_id']===rh24_user_id())),'drafts'=>rh24_pos_drafts_for_user(),'cashiers'=>rh24_is_admin()?rh24_pos_users():[],'closings'=>$reports?rh24_pos_closings(150):[],'shifts'=>$reports?rh24_pos_shift_history(150):[],'permissions'=>rh24_permissions()];
}
function rh24_pos_tax_breakdown(array $items): array {
    $map=[];foreach($items as $i){$r=number_format((float)$i['tax_rate'],2,'.','');if(!isset($map[$r]))$map[$r]=['tax_rate'=>(float)$i['tax_rate'],'net'=>0.0,'tax'=>0.0,'gross'=>0.0];$map[$r]['net']+=(float)$i['net_amount'];$map[$r]['tax']+=(float)$i['tax_amount'];$map[$r]['gross']+=(float)$i['gross_amount'];}foreach($map as &$x){foreach(['net','tax','gross'] as $k)$x[$k]=round($x[$k],2);}unset($x);return array_values($map);
}
function rh24_pos_save_items(string $saleId,array $rows,float $cartDiscount=0): array {
    $db=rh24_db();$q=$db->prepare("SELECT * FROM pos_sales WHERE id=? AND status='draft'");$q->execute([$saleId]);$sale=$q->fetch();if(!$sale)throw new RuntimeException('Offener Kassenvorgang nicht gefunden.');if((string)$sale['user_id']!==rh24_user_id()&&!rh24_is_admin())throw new RuntimeException('Dieser Vorgang gehört zu einem anderen Mitarbeiter.');$catalog=rh24_catalog();$clean=[];$subtotal=0.0;$lineNo=0;$vatDefault=(float)rh24_pos_config()['default_tax_rate'];$stockNeed=[];
    foreach($rows as $row){if(!is_array($row))continue;$pid=trim((string)($row['product_id']??$row['id']??''));if($pid===''||!isset($catalog[$pid]))throw new InvalidArgumentException('Unbekannter Artikel: '.$pid);$p=$catalog[$pid];if(($p['status']??'active')!=='active')throw new InvalidArgumentException('Artikel ist nicht aktiv: '.$p['name']);$qty=max(0.001,min(9999,(float)str_replace(',','.',(string)($row['qty']??1))));$meta=is_array($row['meta']??null)?$row['meta']:[];$unitPrice=rh24_resolve_price($pid,$meta);$disc=max(0,min(100,(float)str_replace(',','.',(string)($row['discount_percent']??0))));$grossBefore=round($unitPrice*$qty,2);$discAmount=round($grossBefore*$disc/100,2);$gross=round($grossBefore-$discAmount,2);$taxRate=max(0,min(100,(float)($p['tax_rate']??$p['vat_rate']??$vatDefault)));$net=$taxRate>0?round($gross/(1+$taxRate/100),2):$gross;$tax=round($gross-$net,2);$subtotal+=$grossBefore;$lineNo++;$stockNeed[$pid]=($stockNeed[$pid]??0)+$qty;$clean[]=['line_no'=>$lineNo,'product_id'=>$pid,'sku'=>(string)($p['sku']??''),'article_no'=>(string)($p['article_no']??''),'barcode'=>(string)($p['barcode']??''),'name'=>(string)$p['name'],'qty'=>$qty,'unit'=>(string)($p['unit']??'Stück'),'unit_price'=>$unitPrice,'discount_percent'=>$disc,'discount_amount'=>$discAmount,'tax_rate'=>$taxRate,'net_amount'=>$net,'tax_amount'=>$tax,'gross_amount'=>$gross,'meta'=>$meta];}
    if(!$clean)throw new InvalidArgumentException('Mindestens ein Artikel ist erforderlich.');if(!rh24_pos_config()['allow_negative_stock'])foreach($stockNeed as $pid=>$need){$available=(float)($catalog[$pid]['stock']??0);if($need>$available+0.0001)throw new RuntimeException('Nicht genügend Lagerbestand: '.($catalog[$pid]['name']??$pid).' · verfügbar '.$available);}
    $cartDiscount=max(0,min(round(array_sum(array_column($clean,'gross_amount')),2),round($cartDiscount,2)));if($cartDiscount>0){$base=array_sum(array_column($clean,'gross_amount'));$left=$cartDiscount;foreach($clean as $idx=>&$i){$share=$idx===array_key_last($clean)?$left:round($cartDiscount*((float)$i['gross_amount']/$base),2);$left=round($left-$share,2);$newGross=max(0,round((float)$i['gross_amount']-$share,2));$newNet=(float)$i['tax_rate']>0?round($newGross/(1+(float)$i['tax_rate']/100),2):$newGross;$i['discount_amount']=round((float)$i['discount_amount']+$share,2);$i['gross_amount']=$newGross;$i['net_amount']=$newNet;$i['tax_amount']=round($newGross-$newNet,2);}unset($i);}
    $gross=round(array_sum(array_column($clean,'gross_amount')),2);$net=round(array_sum(array_column($clean,'net_amount')),2);$tax=round(array_sum(array_column($clean,'tax_amount')),2);$discount=round($subtotal-$gross,2);
    $db->beginTransaction();try{$db->prepare('DELETE FROM pos_sale_items WHERE sale_id=?')->execute([$saleId]);$ins=$db->prepare("INSERT INTO pos_sale_items(sale_id,line_no,product_id,sku,article_no,barcode,name,qty,unit,unit_price,discount_percent,discount_amount,tax_rate,net_amount,tax_amount,gross_amount,meta_json,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");foreach($clean as $i)$ins->execute([$saleId,$i['line_no'],$i['product_id'],$i['sku'],$i['article_no'],$i['barcode'],$i['name'],$i['qty'],$i['unit'],$i['unit_price'],$i['discount_percent'],$i['discount_amount'],$i['tax_rate'],$i['net_amount'],$i['tax_amount'],$i['gross_amount'],rh24_json_encode($i['meta'])]);$db->prepare('UPDATE pos_sales SET subtotal=?,discount_amount=?,net_amount=?,tax_amount=?,gross_amount=?,updated_at=NOW() WHERE id=?')->execute([$subtotal,$discount,$net,$tax,$gross,$saleId]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}return rh24_pos_sale_detail($saleId);
}
function rh24_pos_gateway_call(string $event,array $sale,array $items=[]): array {
    $cfg=rh24_pos_config();$mode=$cfg['fiscal_mode'];$registerId=(string)$sale['register_id'];$request=['event'=>$event,'software'=>'Räucherhaken24 Orgaboard POS','version'=>'94.0','register_id'=>$registerId,'sale_id'=>$sale['id'],'receipt_no'=>$sale['receipt_no']??null,'amount'=>(float)($sale['gross_amount']??0),'currency'=>$sale['currency']??'EUR','tax_breakdown'=>rh24_pos_tax_breakdown($items),'timestamp'=>date('c')];
    if($mode==='training'){$response=['transaction_number'=>'TRAIN-'.$sale['id'],'signature_counter'=>'0','start_time'=>$sale['fiscal_start_at']??date('Y-m-d H:i:s'),'end_time'=>$event==='finish'?date('Y-m-d H:i:s'):null,'signature'=>'TRAINING-NOT-A-TSE-SIGNATURE','tse_serial'=>'TRAINING','client_serial'=>'RH24-POS-V94','qr_payload'=>'TRAINING|'.$sale['id'].'|'.number_format((float)($sale['gross_amount']??0),2,'.','')];rh24_pos_tse_log($sale['id'],$registerId,$event,'training',$request,$response,true,'Trainingsmodus');return $response;}
    $url=trim((string)$cfg['tse_gateway_url']);$token=(string)rh24_setting_get('pos_tse_gateway_token','');if($url==='')throw new RuntimeException('Live-Kassenbetrieb blockiert: Es ist noch kein zertifizierter TSE-Gateway angebunden.');if(!function_exists('curl_init'))throw new RuntimeException('TSE-Gateway benötigt die PHP-cURL-Erweiterung auf dem Server.');$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>array_values(array_filter(['Content-Type: application/json',$token!==''?'Authorization: Bearer '.$token:null])),CURLOPT_POSTFIELDS=>json_encode($request,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);$response=json_decode((string)$raw,true);$ok=$status>=200&&$status<300&&is_array($response)&&!empty($response['transaction_number']);rh24_pos_tse_log($sale['id'],$registerId,$event,'gateway',$request,is_array($response)?$response:['raw'=>(string)$raw],$ok,$ok?'OK':($err?:'HTTP '.$status));if(!$ok)throw new RuntimeException('TSE-Gateway fehlgeschlagen: '.($err?:'HTTP '.$status));return $response;
}
function rh24_pos_tse_log(?string $saleId,string $registerId,string $event,string $provider,array $request,array $response,bool $success,string $message=''): void {try{rh24_db()->prepare("INSERT INTO pos_tse_events(sale_id,register_id,event_type,provider,request_json,response_json,success,message,created_at) VALUES(?,?,?,?,?,?,?,?,NOW())")->execute([$saleId,$registerId,$event,$provider,rh24_json_encode($request),rh24_json_encode($response),$success?1:0,substr($message,0,700)]);}catch(Throwable){}}
function rh24_pos_apply_fiscal_start(string $saleId): void {$d=rh24_pos_sale_detail($saleId);$sale=$d['sale'];$resp=rh24_pos_gateway_call('start',$sale,$d['items']);rh24_db()->prepare("UPDATE pos_sales SET fiscal_transaction_no=?,fiscal_signature_counter=?,fiscal_start_at=COALESCE(fiscal_start_at,NOW()),fiscal_tse_serial=?,fiscal_client_serial=?,fiscal_status='started',updated_at=NOW() WHERE id=?")->execute([(string)($resp['transaction_number']??''),(string)($resp['signature_counter']??''),(string)($resp['tse_serial']??''),(string)($resp['client_serial']??''),$saleId]);}
function rh24_pos_apply_fiscal_finish(string $saleId): void {$d=rh24_pos_sale_detail($saleId);$sale=$d['sale'];$resp=rh24_pos_gateway_call('finish',$sale,$d['items']);rh24_db()->prepare("UPDATE pos_sales SET fiscal_transaction_no=?,fiscal_signature_counter=?,fiscal_end_at=NOW(),fiscal_signature=?,fiscal_tse_serial=?,fiscal_client_serial=?,fiscal_qr_payload=?,fiscal_status=?,updated_at=NOW() WHERE id=?")->execute([(string)($resp['transaction_number']??$sale['fiscal_transaction_no']??''),(string)($resp['signature_counter']??$sale['fiscal_signature_counter']??''),(string)($resp['signature']??''),(string)($resp['tse_serial']??''),(string)($resp['client_serial']??''),(string)($resp['qr_payload']??''),rh24_pos_config()['fiscal_mode']==='training'?'training':'signed',$saleId]);}
function rh24_pos_customer_snapshot(?string $customerId): array {if(!$customerId)return['name'=>'Laufkundschaft','email'=>'','phone'=>'','company'=>'','street'=>'','zip'=>'','city'=>''];$q=rh24_db()->prepare('SELECT id,name,email,phone,mobile,company,street,zip,city,country FROM customers WHERE id=?');$q->execute([$customerId]);$c=$q->fetch();if(!$c)throw new RuntimeException('Kunde nicht gefunden.');return $c;}
function rh24_pos_create_order_from_sale(string $saleId): string {
    $db=rh24_db();$d=rh24_pos_sale_detail($saleId);$s=$d['sale'];if(!empty($s['order_no']))return(string)$s['order_no'];$orderNo='RH24-POS-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));$customer=$s['customer']?:rh24_pos_customer_snapshot($s['customer_id']?:null);$items=[];foreach($d['items'] as $i)$items[]=['id'=>$i['product_id'],'article_no'=>$i['article_no'],'name'=>$i['name'],'unit'=>$i['unit'],'qty'=>(float)$i['qty'],'meta'=>$i['meta']??[],'unit_price'=>(float)$i['unit_price'],'line_total'=>(float)$i['gross_amount']];$totals=['subtotal'=>(float)$s['subtotal'],'shipping'=>0.0,'net'=>(float)$s['net_amount'],'vat'=>(float)$s['tax_amount'],'tax'=>(float)$s['tax_amount'],'vat_rate'=>(float)rh24_pos_config()['default_tax_rate'],'gross'=>(float)$s['gross_amount'],'pos_receipt_no'=>$s['receipt_no']];$history=[['at'=>date('c'),'type'=>'created','value'=>'POS / Kasse · '.($s['receipt_no']??$saleId)],['at'=>date('c'),'type'=>'payment','value'=>'paid']];$paymentMethods=array_map(fn($p)=>$p['payment_method'],$d['payments']);$paymentMethod=implode(' + ',array_values(array_unique($paymentMethods)));$status=((string)$s['sale_type']==='refund')?'complete':'paid';$label=((string)$s['sale_type']==='refund')?'Retoure / Erstattung':'Bezahlt';
    $db->prepare('INSERT INTO orders(order_no,source,sales_channel,status,status_label,payment_status,payment_method,carrier,tracking,internal_note,customer_id,sales_rep_id,commission_sales_rep_id,commission_attribution,commission_note,commission_assigned_at,consultation_id,customer_json,items_json,totals_json,customer_note,history_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$orderNo,(string)$s['sale_type']==='refund'?'pos_refund':'pos','walk_in',$status,$label,'paid',$paymentMethod,'Abholung','','POS-Beleg '.$s['receipt_no'],empty($s['customer_id'])?null:$s['customer_id'],null,null,'pos','Kassenverkauf ohne Außendienst-Provision',date('Y-m-d H:i:s'),null,rh24_json_encode($customer),rh24_json_encode($items),rh24_json_encode($totals),(string)$s['note'],rh24_json_encode($history),$s['completed_at']?:date('Y-m-d H:i:s'),date('Y-m-d H:i:s')]);$db->prepare('UPDATE pos_sales SET order_no=?,updated_at=NOW() WHERE id=?')->execute([$orderNo,$saleId]);return$orderNo;
}
function rh24_pos_finance_cash_entry(string $saleId,float $amount,string $type='income'): ?string {
    if(abs($amount)<0.005)return null;try{if(function_exists('rh24_finance_ensure_ready'))rh24_finance_ensure_ready();$d=rh24_pos_sale_detail($saleId);$s=$d['sale'];$date=substr((string)($s['completed_at']?:date('Y-m-d')),0,10);if(function_exists('rh24_finance_assert_period_open'))rh24_finance_assert_period_open($date);$id=rh24_random_id('CASH-');$no=function_exists('rh24_finance_next_no')?rh24_finance_next_no('finance_next_cash_no','KA'):('KA-'.date('YmdHis'));$taxRate=(float)rh24_pos_config()['default_tax_rate'];$tax=round(abs($amount)-abs($amount)/(1+$taxRate/100),2);rh24_db()->prepare("INSERT INTO finance_cash_entries(id,entry_no,entry_date,entry_type,amount,description,receipt_no,account_code,tax_rate,tax_amount,cost_center_id,created_by,locked_at,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,NULL,?,NULL,NOW())")->execute([$id,$no,$date,$type,abs($amount),($type==='income'?'POS Barverkauf ':'POS Barauszahlung ').($s['receipt_no']??$saleId),(string)($s['receipt_no']??''),'',$taxRate,$tax,rh24_user_id()]);return$id;}catch(Throwable $e){error_log('POS finance cash integration: '.$e->getMessage());return null;}
}
function rh24_pos_receipt_text(string $saleId): string {$d=rh24_pos_sale_detail($saleId);$s=$d['sale'];$cfg=rh24_config();$lines=[];$lines[]=(string)($cfg['invoice_company_name']??'Räucherhaken24');$lines[]=trim((string)($cfg['invoice_street']??'').' '.(string)($cfg['invoice_zip']??'').' '.(string)($cfg['invoice_city']??''));$lines[]=str_repeat('-',32);$lines[]='Beleg: '.($s['receipt_no']??$saleId);$lines[]=date('d.m.Y H:i',strtotime((string)($s['completed_at']??$s['created_at']))).'  '.$s['user_name'];$lines[]=str_repeat('-',32);foreach($d['items'] as $i){$lines[]=$i['name'];$lines[]=number_format((float)$i['qty'],2,',','.').' x '.number_format((float)$i['unit_price'],2,',','.').' = '.number_format((float)$i['gross_amount'],2,',','.').' EUR';}$lines[]=str_repeat('-',32);$lines[]='SUMME       '.number_format((float)$s['gross_amount'],2,',','.').' EUR';foreach($d['payments'] as $p)$lines[]=strtoupper((string)$p['payment_method']).' '.number_format((float)$p['amount'],2,',','.').' EUR';if((float)$s['change_amount']>0)$lines[]='Rueckgeld    '.number_format((float)$s['change_amount'],2,',','.').' EUR';$lines[]='USt enthalten '.number_format((float)$s['tax_amount'],2,',','.').' EUR';$lines[]=str_repeat('-',32);$lines[]='TSE: '.($s['fiscal_tse_serial']?:'nicht konfiguriert');$lines[]='Transaktion: '.($s['fiscal_transaction_no']?:'–');$lines[]='Signaturzaehler: '.($s['fiscal_signature_counter']?:'–');$lines[]='Pruefwert: '.($s['fiscal_signature']?:'–');$lines[]='Vorgang: '.($s['sale_type']?:'sale');if($s['fiscal_mode']==='training')$lines[]='*** TRAININGSBELEG ***';$lines[]=(string)rh24_pos_config()['receipt_footer'];return implode("\n",$lines)."\n";}
