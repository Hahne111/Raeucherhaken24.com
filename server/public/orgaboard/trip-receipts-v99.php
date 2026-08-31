<?php
declare(strict_types=1);

/* Räucherhaken24 Orgaboard V99 · Fahrtenbuch Belegcenter Pro */
function rh24_trip_receipt_types(): array {
    return [
      'fuel'=>['label'=>'Tankbeleg / Kraftstoff','category'=>'Fahrzeug · Kraftstoff','tax_rate'=>19.0,'icon'=>'⛽'],
      'repair'=>['label'=>'Reparatur / Werkstatt','category'=>'Fahrzeug · Reparatur/Werkstatt','tax_rate'=>19.0,'icon'=>'🔧'],
      'service'=>['label'=>'Wartung / Service','category'=>'Fahrzeug · Wartung/Service','tax_rate'=>19.0,'icon'=>'⚙'],
      'tires'=>['label'=>'Reifen / Räder','category'=>'Fahrzeug · Reifen','tax_rate'=>19.0,'icon'=>'◉'],
      'hu_au'=>['label'=>'HU / AU / Prüfung','category'=>'Fahrzeug · HU/AU','tax_rate'=>19.0,'icon'=>'✓'],
      'parking'=>['label'=>'Parken / Parkhaus','category'=>'Fahrzeug · Parken','tax_rate'=>19.0,'icon'=>'P'],
      'toll'=>['label'=>'Maut / Fähre / Straße','category'=>'Fahrzeug · Maut','tax_rate'=>19.0,'icon'=>'⇄'],
      'wash'=>['label'=>'Waschen / Pflege','category'=>'Fahrzeug · Fahrzeugpflege','tax_rate'=>19.0,'icon'=>'✦'],
      'insurance'=>['label'=>'Versicherung','category'=>'Fahrzeug · Versicherung','tax_rate'=>0.0,'icon'=>'◆'],
      'tax'=>['label'=>'Kfz-Steuer / Gebühr','category'=>'Fahrzeug · Steuer/Gebühr','tax_rate'=>0.0,'icon'=>'€'],
      'parts'=>['label'=>'Ersatzteile / Zubehör','category'=>'Fahrzeug · Ersatzteile','tax_rate'=>19.0,'icon'=>'＋'],
      'other'=>['label'=>'Sonstiger Fahrzeugbeleg','category'=>'Fahrzeug · Sonstiges','tax_rate'=>19.0,'icon'=>'▧'],
    ];
}
function rh24_trip_receipt_schema_health(PDO $db): array {
    try{
      $table=(bool)$db->query("SHOW TABLES LIKE 'trip_receipts'")->fetchColumn();
      $cols=$table?$db->query('SHOW COLUMNS FROM trip_receipts')->fetchAll(PDO::FETCH_COLUMN):[];
      $required=['id','sales_rep_id','vehicle_id','receipt_type','receipt_date','gross_amount','receipt_path','receipt_sha256','finance_status','finance_expense_id'];
      $missing=array_values(array_diff($required,$cols));
      $v=(int)(rh24_setting_get('trip_receipt_schema_version','0')?:0);
      return ['ready'=>$table&&!$missing&&$v>=99,'version'=>$v,'missing'=>$missing];
    }catch(Throwable $e){return ['ready'=>false,'version'=>0,'missing'=>['trip_receipts'],'error'=>$e->getMessage()];}
}
function rh24_v99_column_exists(PDO $db,string $table,string $column): bool {
    $q=$db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");$q->execute([$column]);return (bool)$q->fetchColumn();
}
function rh24_ensure_v99_trip_receipt_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS trip_receipts (
      id VARCHAR(60) PRIMARY KEY,
      sales_rep_id VARCHAR(40) NOT NULL,
      vehicle_id VARCHAR(40) NOT NULL,
      trip_id VARCHAR(40) NULL,
      receipt_type VARCHAR(32) NOT NULL DEFAULT 'other',
      receipt_date DATE NOT NULL,
      supplier_name VARCHAR(220) NOT NULL DEFAULT '',
      invoice_no VARCHAR(100) NOT NULL DEFAULT '',
      description VARCHAR(500) NOT NULL DEFAULT '',
      gross_amount DECIMAL(14,2) NOT NULL,
      net_amount DECIMAL(14,2) NOT NULL,
      tax_amount DECIMAL(14,2) NOT NULL,
      tax_rate DECIMAL(6,2) NOT NULL DEFAULT 19.00,
      payment_status VARCHAR(20) NOT NULL DEFAULT 'paid',
      payment_method VARCHAR(60) NOT NULL DEFAULT 'Karte',
      paid_at DATE NULL,
      odometer_km DECIMAL(12,1) NULL,
      fuel_liters DECIMAL(10,3) NULL,
      fuel_unit_price DECIMAL(10,4) NULL,
      notes TEXT NULL,
      receipt_path VARCHAR(255) NOT NULL,
      receipt_name VARCHAR(255) NOT NULL,
      receipt_mime VARCHAR(100) NOT NULL DEFAULT '',
      receipt_sha256 CHAR(64) NOT NULL,
      finance_status VARCHAR(24) NOT NULL DEFAULT 'pending',
      finance_expense_id VARCHAR(60) NULL,
      finance_error VARCHAR(700) NOT NULL DEFAULT '',
      record_status VARCHAR(24) NOT NULL DEFAULT 'active',
      cancelled_at DATETIME NULL,
      cancelled_by VARCHAR(60) NULL,
      cancel_reason VARCHAR(500) NOT NULL DEFAULT '',
      created_by VARCHAR(60) NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      UNIQUE KEY uq_trip_receipt_hash (receipt_sha256),
      KEY idx_trip_receipt_rep_date (sales_rep_id,receipt_date),
      KEY idx_trip_receipt_vehicle_date (vehicle_id,receipt_date),
      KEY idx_trip_receipt_finance (finance_status,finance_expense_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    try{rh24_finance_ensure_ready($db);}catch(Throwable $e){}
    try{
      if(!rh24_v99_column_exists($db,'finance_expenses','source_module'))$db->exec("ALTER TABLE finance_expenses ADD COLUMN source_module VARCHAR(40) NOT NULL DEFAULT '' AFTER notes");
      if(!rh24_v99_column_exists($db,'finance_expenses','source_ref'))$db->exec("ALTER TABLE finance_expenses ADD COLUMN source_ref VARCHAR(80) NULL AFTER source_module");
      try{$db->exec("ALTER TABLE finance_expenses ADD UNIQUE KEY uq_finance_expense_source (source_module,source_ref)");}catch(Throwable $e){}
    }catch(Throwable $e){}
    rh24_setting_set('trip_receipt_schema_version','99');
    try{rh24_audit('schema_upgrade','system','v99_trip_receipts',['features'=>['vehicle_receipts','photo_upload','duplicate_hash','finance_auto_sync','fuel_metrics']],'system');}catch(Throwable $e){}
}
function rh24_trip_receipt_cost_center_id(PDO $db,string $value): ?string {
    $value=trim($value);if($value==='')return null;
    try{$q=$db->prepare("SELECT id FROM finance_cost_centers WHERE active=1 AND (code=? OR name=?) LIMIT 1");$q->execute([$value,$value]);$id=$q->fetchColumn();return $id!==false?(string)$id:null;}catch(Throwable $e){return null;}
}
function rh24_trip_receipt_supplier_id(PDO $db,string $name): ?string {
    $name=trim($name);if($name==='')return null;
    $q=$db->prepare("SELECT id FROM finance_suppliers WHERE active=1 AND name=? LIMIT 1");$q->execute([$name]);$id=$q->fetchColumn();if($id!==false)return (string)$id;
    $id=rh24_random_id('SUP-');$no=rh24_finance_next_no('finance_next_supplier_no','K');
    $db->prepare("INSERT INTO finance_suppliers(id,supplier_no,name,active,created_at,updated_at) VALUES(?,?,?,1,NOW(),NOW())")->execute([$id,$no,$name]);
    return $id;
}
function rh24_trip_receipt_sync_finance(string $receiptId,bool $throw=false): array {
    $db=rh24_db();
    try{
      rh24_finance_ensure_ready($db);rh24_ensure_v99_trip_receipt_schema($db);
      $q=$db->prepare("SELECT r.*,v.label vehicle_label,v.license_plate,v.cost_center,s.name rep_name FROM trip_receipts r JOIN trip_vehicles v ON v.id=r.vehicle_id LEFT JOIN sales_reps s ON s.id=r.sales_rep_id WHERE r.id=? LIMIT 1");$q->execute([$receiptId]);$r=$q->fetch();if(!$r)throw new RuntimeException('Fahrzeugbeleg nicht gefunden.');if((string)$r['record_status']!=='active')throw new RuntimeException('Stornierter Fahrzeugbeleg wird nicht gebucht.');
      rh24_finance_assert_period_open((string)$r['receipt_date']);
      $types=rh24_trip_receipt_types();$type=$types[(string)$r['receipt_type']]??$types['other'];
      $supplier=rh24_trip_receipt_supplier_id($db,(string)$r['supplier_name']);$cc=rh24_trip_receipt_cost_center_id($db,(string)($r['cost_center']??''));
      $project=trim('Fahrzeug: '.($r['license_plate']?:$r['vehicle_label']).' · Mitarbeiter: '.($r['rep_name']?:$r['sales_rep_id']));
      $notes='Automatisch aus Fahrtenbuch-Belegcenter · '.$type['label'];if(trim((string)$r['description'])!=='')$notes.=' · '.trim((string)$r['description']);if(trim((string)$r['notes'])!=='')$notes.=' · '.trim((string)$r['notes']);
      $existingId='';
      if(rh24_v99_column_exists($db,'finance_expenses','source_ref')){$e=$db->prepare("SELECT id,record_status FROM finance_expenses WHERE source_module='triplog' AND source_ref=? LIMIT 1");$e->execute([$receiptId]);$ex=$e->fetch();if($ex){if((string)$ex['record_status']!=='active')throw new RuntimeException('Der verknüpfte Buchhaltungsbeleg wurde storniert. Bitte zuerst in der Buchhaltung klären.');$existingId=(string)$ex['id'];}}
      if($existingId==='')$existingId=(string)($r['finance_expense_id']??'');
      $paid=(string)$r['payment_status']==='paid'?(string)($r['paid_at']?:$r['receipt_date']):null;$due=(string)$r['receipt_date'];
      if($existingId!==''){
        $sql="UPDATE finance_expenses SET supplier_id=?,invoice_no=?,invoice_date=?,due_date=?,gross_amount=?,net_amount=?,tax_amount=?,tax_rate=?,category=?,cost_center_id=?,project=?,payment_status=?,paid_at=?,payment_method=?,receipt_path=?,receipt_name=?,receipt_sha256=?,notes=?,source_module='triplog',source_ref=?,updated_at=NOW() WHERE id=? AND record_status='active'";
        $db->prepare($sql)->execute([$supplier,$r['invoice_no'],$r['receipt_date'],$due,$r['gross_amount'],$r['net_amount'],$r['tax_amount'],$r['tax_rate'],$type['category'],$cc,$project,$r['payment_status'],$paid,$r['payment_method'],$r['receipt_path'],$r['receipt_name'],$r['receipt_sha256'],$notes,$receiptId,$existingId]);$expenseId=$existingId;
      }else{
        $expenseId=rh24_random_id('EXP-');$voucher=rh24_finance_next_no('finance_next_voucher_no','BE');
        $sql="INSERT INTO finance_expenses(id,voucher_no,supplier_id,invoice_no,invoice_date,due_date,gross_amount,net_amount,tax_amount,tax_rate,account_code,category,cost_center_id,project,payment_status,paid_at,payment_method,receipt_path,receipt_name,receipt_sha256,notes,source_module,source_ref,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,'',?,?,?,?,?,?,?,?,?,?,'triplog',?,?,NOW(),NOW())";
        $db->prepare($sql)->execute([$expenseId,$voucher,$supplier,$r['invoice_no'],$r['receipt_date'],$due,$r['gross_amount'],$r['net_amount'],$r['tax_amount'],$r['tax_rate'],$type['category'],$cc,$project,$r['payment_status'],$paid,$r['payment_method'],$r['receipt_path'],$r['receipt_name'],$r['receipt_sha256'],$notes,$receiptId,$r['created_by']]);
      }
      $db->prepare("UPDATE trip_receipts SET finance_status='synced',finance_expense_id=?,finance_error='',updated_at=NOW() WHERE id=?")->execute([$expenseId,$receiptId]);
      try{rh24_audit('trip_receipt_finance_sync','trip_receipt',$receiptId,['finance_expense_id'=>$expenseId,'gross'=>(float)$r['gross_amount'],'category'=>$type['category']]);}catch(Throwable $e){}
      return ['ok'=>true,'expense_id'=>$expenseId];
    }catch(Throwable $e){
      try{$db->prepare("UPDATE trip_receipts SET finance_status='error',finance_error=?,updated_at=NOW() WHERE id=?")->execute([(function_exists('mb_substr')?mb_substr($e->getMessage(),0,700):substr($e->getMessage(),0,700)),$receiptId]);}catch(Throwable $ignored){}
      if($throw)throw $e;return ['ok'=>false,'error'=>$e->getMessage()];
    }
}
function rh24_trip_receipt_rows(string $repId,string $period): array {
    if($repId==='')return [];[$period,$start,$end]=rh24_triplog_period_bounds($period);$db=rh24_db();
    $q=$db->prepare("SELECT r.*,v.label vehicle_label,v.license_plate,t.trip_date linked_trip_date FROM trip_receipts r LEFT JOIN trip_vehicles v ON v.id=r.vehicle_id LEFT JOIN trip_log t ON t.id=r.trip_id WHERE r.sales_rep_id=? AND r.receipt_date>=? AND r.receipt_date<? ORDER BY r.receipt_date DESC,r.created_at DESC");$q->execute([$repId,$start,$end]);$rows=$q->fetchAll();$types=rh24_trip_receipt_types();
    foreach($rows as &$r){foreach(['gross_amount','net_amount','tax_amount','tax_rate','odometer_km','fuel_liters','fuel_unit_price'] as $f)$r[$f]=$r[$f]!==null?(float)$r[$f]:null;$meta=$types[$r['receipt_type']]??$types['other'];$r['type_label']=$meta['label'];$r['type_icon']=$meta['icon'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_trip_receipt_stats(array $rows): array {
    $gross=0.0;$fuel=0.0;$liters=0.0;$synced=0;$errors=0;$active=0;
    foreach($rows as $r){if(($r['record_status']??'active')!=='active')continue;$active++;$gross+=(float)$r['gross_amount'];if(($r['receipt_type']??'')==='fuel'){$fuel+=(float)$r['gross_amount'];$liters+=(float)($r['fuel_liters']??0);}if(($r['finance_status']??'')==='synced')$synced++;if(($r['finance_status']??'')==='error')$errors++;}
    return ['count'=>$active,'gross'=>round($gross,2),'fuel_gross'=>round($fuel,2),'fuel_liters'=>round($liters,3),'finance_synced'=>$synced,'finance_errors'=>$errors];
}
