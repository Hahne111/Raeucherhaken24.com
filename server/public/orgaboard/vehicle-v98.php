<?php
declare(strict_types=1);

/* V98 · Fahrzeug-Assistent Pro · manuell + HSN/TSN */
function rh24_vehicle_schema_health(PDO $db): array {
    $required=['vehicle_type','manufacturer','model','variant_name','engine_name','hsn','tsn','vin','first_registration','fuel_type','power_kw','displacement_cc','transmission','color','owner_name','insurance_company','insurance_policy_no','hu_due','au_due','service_due','cost_center','usage_type','notes','lookup_source'];
    try{
        $cols=$db->query('SHOW COLUMNS FROM trip_vehicles')->fetchAll(PDO::FETCH_COLUMN);
        $missing=array_values(array_diff($required,$cols));
        $catalog=(bool)$db->query("SHOW TABLES LIKE 'vehicle_key_catalog'")->fetchColumn();
        $v=(int)(rh24_setting_get('vehicle_schema_version','0')?:0);
        return ['ready'=>!$missing&&$catalog&&$v>=98,'version'=>$v,'missing'=>$missing,'catalog'=>$catalog];
    }catch(Throwable $e){return ['ready'=>false,'version'=>0,'missing'=>$required,'catalog'=>false,'error'=>$e->getMessage()];}
}
function rh24_ensure_v98_vehicle_schema(PDO $db): void {
    $columns=[
      'vehicle_type'=>"VARCHAR(24) NOT NULL DEFAULT 'car' AFTER sales_rep_id",
      'manufacturer'=>"VARCHAR(120) NULL AFTER make_model",
      'model'=>"VARCHAR(120) NULL AFTER manufacturer",
      'variant_name'=>"VARCHAR(160) NULL AFTER model",
      'engine_name'=>"VARCHAR(160) NULL AFTER variant_name",
      'hsn'=>"VARCHAR(4) NULL AFTER engine_name",
      'tsn'=>"VARCHAR(3) NULL AFTER hsn",
      'vin'=>"VARCHAR(32) NULL AFTER tsn",
      'first_registration'=>"DATE NULL AFTER vin",
      'fuel_type'=>"VARCHAR(40) NULL AFTER first_registration",
      'power_kw'=>"DECIMAL(8,1) NULL AFTER fuel_type",
      'displacement_cc'=>"INT UNSIGNED NULL AFTER power_kw",
      'transmission'=>"VARCHAR(60) NULL AFTER displacement_cc",
      'color'=>"VARCHAR(80) NULL AFTER transmission",
      'owner_name'=>"VARCHAR(160) NULL AFTER color",
      'insurance_company'=>"VARCHAR(160) NULL AFTER owner_name",
      'insurance_policy_no'=>"VARCHAR(100) NULL AFTER insurance_company",
      'hu_due'=>"DATE NULL AFTER insurance_policy_no",
      'au_due'=>"DATE NULL AFTER hu_due",
      'service_due'=>"DATE NULL AFTER au_due",
      'cost_center'=>"VARCHAR(80) NULL AFTER service_due",
      'usage_type'=>"VARCHAR(24) NOT NULL DEFAULT 'mixed' AFTER cost_center",
      'notes'=>"TEXT NULL AFTER usage_type",
      'lookup_source'=>"VARCHAR(40) NULL AFTER notes"
    ];
    $existing=[];foreach($db->query('SHOW COLUMNS FROM trip_vehicles')->fetchAll() as $r)$existing[]=(string)$r['Field'];
    foreach($columns as $name=>$def){if(!in_array($name,$existing,true)){$db->exec("ALTER TABLE trip_vehicles ADD COLUMN `$name` $def");$existing[]=$name;}}
    try{$db->exec("ALTER TABLE trip_vehicles ADD KEY idx_trip_vehicle_hsn_tsn (hsn,tsn)");}catch(Throwable $e){}
    try{$db->exec("ALTER TABLE trip_vehicles ADD KEY idx_trip_vehicle_plate (license_plate)");}catch(Throwable $e){}
    $db->exec("CREATE TABLE IF NOT EXISTS vehicle_key_catalog (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      hsn VARCHAR(4) NOT NULL,
      tsn VARCHAR(3) NOT NULL,
      vehicle_type VARCHAR(24) NOT NULL DEFAULT 'car',
      manufacturer VARCHAR(120) NOT NULL,
      model VARCHAR(160) NOT NULL,
      variant_name VARCHAR(180) NULL,
      years_label VARCHAR(80) NULL,
      fuel_type VARCHAR(60) NULL,
      power_kw DECIMAL(8,1) NULL,
      displacement_cc INT UNSIGNED NULL,
      engine_name VARCHAR(180) NULL,
      transmission VARCHAR(80) NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'local',
      raw_json LONGTEXT NULL,
      updated_at DATETIME NOT NULL,
      UNIQUE KEY uq_vehicle_key (hsn,tsn),
      KEY idx_vehicle_key_make (manufacturer,model)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // One documented demo pair keeps the lookup path testable before a paid/live catalogue is connected.
    $db->prepare("INSERT INTO vehicle_key_catalog(hsn,tsn,vehicle_type,manufacturer,model,variant_name,years_label,source,updated_at) VALUES('0603','COB','car','Volkswagen','Touareg','','2018 - 2023','demo',NOW()) ON DUPLICATE KEY UPDATE updated_at=updated_at")->execute();
    rh24_setting_set('vehicle_schema_version','98');
    try{rh24_audit('schema_upgrade','system','v98_vehicle',['features'=>['vehicle_masterdata','hsn_tsn_lookup','vehicle_lookup_provider','fleet_dates']],'system');}catch(Throwable $e){}
}
function rh24_vehicle_lookup_secret(): array {
    $raw=(string)rh24_setting_get('vehicle_lookup_credentials','');
    if($raw==='')return [];
    try{return rh24_decrypt_secret($raw);}catch(Throwable $e){return [];}
}
function rh24_vehicle_lookup_config(): array {
    $s=rh24_vehicle_lookup_secret();
    $last=(string)rh24_setting_get('vehicle_lookup_last_test','');
    $base=(string)rh24_setting_get('vehicle_lookup_base_url','https://api4cars.com/api/v1/');
    if(!preg_match('~^https://api4cars\\.com/~i',$base))$base='https://api4cars.com/api/v1/';
    $key=trim((string)($s['api_key']??''));$secret=trim((string)($s['api_secret']??''));
    return [
      'provider'=>'api4cars','configured'=>$key!==''&&$secret!=='','base_url'=>$base,
      'key_hint'=>$key!==''?(substr($key,0,min(12,strlen($key))).'…'):'',
      'last_test'=>$last,'live_enabled'=>$key!==''&&$secret!==''
    ];
}
function rh24_vehicle_lookup_config_save(string $key,string $secret): array {
    $old=rh24_vehicle_lookup_secret();
    if(trim($key)!=='')$old['api_key']=trim($key);
    if(trim($secret)!=='')$old['api_secret']=trim($secret);
    if(empty($old['api_key'])||empty($old['api_secret']))throw new InvalidArgumentException('API-Key und API-Secret sind erforderlich.');
    rh24_setting_set('vehicle_lookup_credentials',rh24_encrypt_secret($old));
    rh24_setting_set('vehicle_lookup_base_url','https://api4cars.com/api/v1/');
    try{rh24_audit('vehicle_lookup_config_save','settings','vehicle_lookup',['provider'=>'api4cars','configured'=>true]);}catch(Throwable $e){}
    return rh24_vehicle_lookup_config();
}
function rh24_vehicle_clean_hsn(string $v): string {return preg_replace('/\\D+/','',trim($v))??'';}
function rh24_vehicle_clean_tsn(string $v): string {return strtoupper(preg_replace('/[^A-Z0-9]+/i','',trim($v))??'');}
function rh24_vehicle_catalog_row(string $hsn,string $tsn): ?array {
    $q=rh24_db()->prepare('SELECT * FROM vehicle_key_catalog WHERE hsn=? AND tsn=? LIMIT 1');$q->execute([$hsn,$tsn]);$r=$q->fetch();if(!$r)return null;
    foreach(['power_kw'] as $k)if($r[$k]!==null)$r[$k]=(float)$r[$k];foreach(['displacement_cc'] as $k)if($r[$k]!==null)$r[$k]=(int)$r[$k];unset($r['raw_json']);return $r;
}
function rh24_vehicle_provider_http(string $url,string $key,string $secret): array {
    $raw='';$status=0;$err='';
    if(function_exists('curl_init')){
      $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>7,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Raeucherhaken24-Orgaboard/99.1','X-API-Key: '.$key,'X-API-Secret: '.$secret]]);
      $res=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=(string)curl_error($ch);curl_close($ch);if($res===false)throw new RuntimeException('Fahrzeugdaten-Schnittstelle nicht erreichbar'.($err?': '.$err:''));$raw=(string)$res;
    } else {
      $ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>15,'ignore_errors'=>true,'header'=>"Accept: application/json\r\nUser-Agent: Raeucherhaken24-Orgaboard/99.1\r\nX-API-Key: $key\r\nX-API-Secret: $secret\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
      $res=@file_get_contents($url,false,$ctx);if($res===false)throw new RuntimeException('Fahrzeugdaten-Schnittstelle nicht erreichbar. Bitte ausgehende HTTPS-Verbindungen auf dem Server prüfen.');$raw=(string)$res;if(isset($http_response_header[0])&&preg_match('/\\s(\\d{3})\\s/',$http_response_header[0],$m))$status=(int)$m[1];
    }
    $d=json_decode($raw,true);if(!is_array($d))throw new RuntimeException('Fahrzeugdaten-Schnittstelle hat keine gültigen JSON-Daten geliefert.');
    return [$status,$d,$raw];
}
function rh24_vehicle_provider_request(string $hsn,string $tsn): array {
    $s=rh24_vehicle_lookup_secret();$key=trim((string)($s['api_key']??''));$secret=trim((string)($s['api_secret']??''));
    if($key===''||$secret==='')throw new RuntimeException('Die HSN/TSN-Live-Schnittstelle ist noch nicht eingerichtet. Als Administrator einmal API-Key und API-Secret hinterlegen.');
    $bases=['https://api4cars.com/api/v1/','https://api4cars.com/wp-json/carapi/v1/'];$lastError='';
    foreach($bases as $base){
      $url=$base.'vehicle?hsn='.rawurlencode($hsn).'&tsn='.rawurlencode($tsn);
      try{[$status,$d,$raw]=rh24_vehicle_provider_http($url,$key,$secret);}catch(Throwable $e){$lastError=$e->getMessage();continue;}
      if($status===404){$lastError='Für diese HSN/TSN-Kombination wurde kein Fahrzeug gefunden.';continue;}
      if($status===401)throw new RuntimeException('API4Cars lehnt die Zugangsdaten ab (HTTP 401). API-Key und API-Secret prüfen.');
      if($status===403)throw new RuntimeException('API4Cars-Zugang ist nicht freigeschaltet oder das Anfragekontingent ist erschöpft (HTTP 403).');
      if($status>=400){$msg=(string)($d['message']??($d['error']['message']??''));$lastError=$msg!==''?$msg:('Fahrzeugdaten-Schnittstelle HTTP '.$status);continue;}
      $v=(isset($d['vehicle'])&&is_array($d['vehicle']))?$d['vehicle']:(isset($d['data'])&&is_array($d['data'])?$d['data']:$d);
      $brand=trim((string)($v['brand']??$v['manufacturer']??''));$model=trim((string)($v['model']??$v['type']??''));if($brand===''&&$model===''){$lastError='API4Cars lieferte keinen verwertbaren Fahrzeugdatensatz.';continue;}
      $details=is_array($v['vehicle_data']??null)?($v['vehicle_data'][0]??[]):[];
      $power=$v['power_kw']??$details['Leistung_kW']??$details['kw']??null;if($power===null&&isset($details['Leistung'])&&preg_match('/([0-9.,]+)\\s*kW/i',(string)$details['Leistung'],$m))$power=(float)str_replace(',','.',$m[1]);
      $cc=$v['displacement_cc']??$details['Hubraum']??null;if(is_string($cc)&&preg_match('/([0-9.]+)/',$cc,$m))$cc=(int)str_replace('.','',$m[1]);
      rh24_setting_set('vehicle_lookup_base_url',$base);
      return ['hsn'=>$hsn,'tsn'=>$tsn,'vehicle_type'=>'car','manufacturer'=>$brand,'model'=>$model,'variant_name'=>trim((string)($v['display_vehicle']??$v['trim']??$v['variant']??'')),'years_label'=>trim((string)($v['years']??'')),'fuel_type'=>trim((string)($v['fuel']??$details['Kraftstoff']??'')),'power_kw'=>$power!==null?(float)$power:null,'displacement_cc'=>$cc!==null?(int)$cc:null,'engine_name'=>trim((string)($v['engine']??$details['Motor']??'')),'transmission'=>trim((string)($v['transmission']??$details['Getriebe']??'')),'source'=>'api4cars_live','provider_endpoint'=>$base,'raw_json'=>$raw];
    }
    throw new RuntimeException($lastError!==''?$lastError:'Fahrzeugdaten-Schnittstelle konnte keinen Treffer liefern.');
}
function rh24_vehicle_lookup_test(string $hsn='0603',string $tsn='COB'): array {
    $hsn=rh24_vehicle_clean_hsn($hsn);$tsn=rh24_vehicle_clean_tsn($tsn);if(!preg_match('/^\\d{4}$/',$hsn)||!preg_match('/^[A-Z0-9]{3}$/',$tsn))throw new InvalidArgumentException('Für den Verbindungstest gültige HSN (4-stellig) und TSN (3-stellig) angeben.');
    $t=microtime(true);$r=rh24_vehicle_provider_request($hsn,$tsn);$ms=(int)round((microtime(true)-$t)*1000);$stamp=date('c');rh24_setting_set('vehicle_lookup_last_test',$stamp);
    try{rh24_audit('vehicle_lookup_test','settings','vehicle_lookup',['hsn'=>$hsn,'tsn'=>$tsn,'ms'=>$ms,'vehicle'=>trim($r['manufacturer'].' '.$r['model'])]);}catch(Throwable $e){}
    unset($r['raw_json']);return ['ok'=>true,'latency_ms'=>$ms,'tested_at'=>$stamp,'vehicle'=>$r,'config'=>rh24_vehicle_lookup_config()];
}
function rh24_vehicle_lookup(string $hsn,string $tsn,bool $forceLive=false): array {
    $hsn=rh24_vehicle_clean_hsn($hsn);$tsn=rh24_vehicle_clean_tsn($tsn);
    if(!preg_match('/^\\d{4}$/',$hsn))throw new InvalidArgumentException('HSN muss 4-stellig sein (Feld 2.1 im Fahrzeugschein).');
    if(!preg_match('/^[A-Z0-9]{3}$/',$tsn))throw new InvalidArgumentException('TSN muss 3-stellig sein (erste 3 Zeichen aus Feld 2.2).');
    $local=rh24_vehicle_catalog_row($hsn,$tsn);$cfg=rh24_vehicle_lookup_config();
    if(!$forceLive&&$local&&($local['source']??'')!=='demo')return ['found'=>true,'vehicle'=>$local,'source'=>'local_cache','config'=>$cfg,'notice'=>'Zwischengespeicherter Live-Treffer. „Live neu laden“ aktualisiert die Daten beim Anbieter.'];
    if($cfg['configured']){
      $r=rh24_vehicle_provider_request($hsn,$tsn);$db=rh24_db();$db->prepare("INSERT INTO vehicle_key_catalog(hsn,tsn,vehicle_type,manufacturer,model,variant_name,years_label,fuel_type,power_kw,displacement_cc,engine_name,transmission,source,raw_json,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE vehicle_type=VALUES(vehicle_type),manufacturer=VALUES(manufacturer),model=VALUES(model),variant_name=VALUES(variant_name),years_label=VALUES(years_label),fuel_type=VALUES(fuel_type),power_kw=VALUES(power_kw),displacement_cc=VALUES(displacement_cc),engine_name=VALUES(engine_name),transmission=VALUES(transmission),source=VALUES(source),raw_json=VALUES(raw_json),updated_at=NOW()")
        ->execute([$r['hsn'],$r['tsn'],$r['vehicle_type'],$r['manufacturer'],$r['model'],$r['variant_name'],$r['years_label'],$r['fuel_type'],$r['power_kw'],$r['displacement_cc'],$r['engine_name'],$r['transmission'],$r['source'],$r['raw_json']]);unset($r['raw_json']);return ['found'=>true,'vehicle'=>$r,'source'=>'live','config'=>rh24_vehicle_lookup_config(),'notice'=>'Live von API4Cars geladen. Fahrzeugdaten bitte mit Zulassungsbescheinigung abgleichen.'];
    }
    if($local)return ['found'=>true,'vehicle'=>$local,'source'=>'demo','config'=>$cfg,'notice'=>'Demo-Datensatz. Für vollständige HSN/TSN-Suche API-Zugangsdaten hinterlegen.'];
    return ['found'=>false,'vehicle'=>null,'source'=>'none','config'=>$cfg,'notice'=>'Kein lokaler Treffer. API-Zugangsdaten hinterlegen oder Fahrzeug manuell erfassen.'];
}

function rh24_vehicle_date_or_null(mixed $v): ?string {$s=trim((string)$v);return preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)?$s:null;}
function rh24_vehicle_type_value(mixed $v): string {$s=(string)$v;return in_array($s,['car','truck','motorcycle','trailer'],true)?$s:'car';}
function rh24_vehicle_usage_value(mixed $v): string {$s=(string)$v;return in_array($s,['business','mixed','private'],true)?$s:'mixed';}
