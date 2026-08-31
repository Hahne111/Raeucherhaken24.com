<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('RH24_MARKETPLACE');
    session_set_cookie_params([
        'httponly'=>true,
        'samesite'=>'Lax',
        'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'path'=>'/'
    ]);
    session_start();
}

const RH24_MARKET_DB_CONFIG = __DIR__ . '/orgaboard/private/db-config.php';
const RH24_MARKET_FEE_GROSS = 19.99;
const RH24_MARKET_VAT_RATE = 19.0;
const RH24_MARKET_MAX_ACTIVE_LISTINGS = 10;
const RH24_MARKET_TERMS_VERSION = '2026-08-24-v61';

function market_db(): PDO {
    static $db=null;
    if($db instanceof PDO) return $db;
    if(!is_file(RH24_MARKET_DB_CONFIG)) throw new RuntimeException('Datenbank ist noch nicht eingerichtet.');
    $cfg=require RH24_MARKET_DB_CONFIG;
    if(!is_array($cfg)) throw new RuntimeException('Datenbank-Konfiguration ist ungültig.');
    $dsn=sprintf('mysql:host=%s;dbname=%s;charset=%s',$cfg['host'],$cfg['database'],$cfg['charset']??'utf8mb4');
    $db=new PDO($dsn,(string)$cfg['user'],(string)$cfg['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    market_ensure_schema($db);
    return $db;
}

function market_ensure_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS market_users (
      id VARCHAR(40) NOT NULL PRIMARY KEY,
      email VARCHAR(190) NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      display_name VARCHAR(160) NOT NULL,
      phone VARCHAR(80) NULL,
      zip VARCHAR(20) NULL,
      city VARCHAR(120) NULL,
      lat DECIMAL(10,6) NULL,
      lon DECIMAL(10,6) NULL,
      email_verified_at DATETIME NULL,
      status VARCHAR(30) NOT NULL DEFAULT 'active',
      membership_status VARCHAR(30) NOT NULL DEFAULT 'pending',
      membership_started_at DATETIME NULL,
      membership_expires_at DATETIME NULL,
      membership_order_no VARCHAR(60) NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      UNIQUE KEY uq_market_email(email),
      KEY idx_market_membership(membership_status,membership_expires_at),
      KEY idx_market_location(zip,city)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS market_verification_tokens (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      user_id VARCHAR(40) NOT NULL,
      token_hash CHAR(64) NOT NULL,
      purpose VARCHAR(30) NOT NULL DEFAULT 'verify',
      expires_at DATETIME NOT NULL,
      used_at DATETIME NULL,
      created_at DATETIME NOT NULL,
      UNIQUE KEY uq_market_token(token_hash),
      KEY idx_market_token_user(user_id,purpose,expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS market_listings (
      id VARCHAR(40) NOT NULL PRIMARY KEY,
      user_id VARCHAR(40) NOT NULL,
      kind VARCHAR(20) NOT NULL DEFAULT 'sell',
      title VARCHAR(180) NOT NULL,
      description TEXT NOT NULL,
      category VARCHAR(80) NOT NULL DEFAULT 'Sonstiges',
      condition_label VARCHAR(60) NULL,
      price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      negotiable TINYINT(1) NOT NULL DEFAULT 0,
      shipping VARCHAR(20) NOT NULL DEFAULT 'pickup',
      zip VARCHAR(20) NOT NULL,
      city VARCHAR(120) NOT NULL,
      lat DECIMAL(10,6) NULL,
      lon DECIMAL(10,6) NULL,
      images_json LONGTEXT NULL,
      status VARCHAR(30) NOT NULL DEFAULT 'pending',
      views INT UNSIGNED NOT NULL DEFAULT 0,
      expires_at DATETIME NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      KEY idx_market_listing_status(status,created_at),
      KEY idx_market_listing_user(user_id,status),
      KEY idx_market_listing_kind(kind,category),
      KEY idx_market_listing_region(zip,city)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS market_messages (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      listing_id VARCHAR(40) NOT NULL,
      from_user_id VARCHAR(40) NOT NULL,
      to_user_id VARCHAR(40) NOT NULL,
      body TEXT NOT NULL,
      read_at DATETIME NULL,
      created_at DATETIME NOT NULL,
      KEY idx_market_msg_to(to_user_id,read_at,created_at),
      KEY idx_market_msg_listing(listing_id,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS market_reports (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      listing_id VARCHAR(40) NOT NULL,
      reporter_user_id VARCHAR(40) NULL,
      reason VARCHAR(100) NOT NULL,
      details TEXT NULL,
      status VARCHAR(30) NOT NULL DEFAULT 'open',
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      KEY idx_market_report_status(status,created_at),
      KEY idx_market_report_listing(listing_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // V61: dokumentierte Zustimmung zu den eigenständigen Marktplatz-AGB
    foreach([
      "ALTER TABLE market_users ADD COLUMN terms_version VARCHAR(40) NULL AFTER membership_order_no",
      "ALTER TABLE market_users ADD COLUMN terms_accepted_at DATETIME NULL AFTER terms_version",
      "ALTER TABLE market_reports ADD COLUMN reporter_name VARCHAR(160) NULL AFTER reporter_user_id",
      "ALTER TABLE market_reports ADD COLUMN reporter_email VARCHAR(190) NULL AFTER reporter_name",
      "ALTER TABLE market_reports ADD COLUMN content_url VARCHAR(500) NULL AFTER reporter_email"
    ] as $sql){ try{$db->exec($sql);}catch(Throwable){} }

    // Einmalige V61-Neupruefung: alte/off-topic Anzeigen nicht ungeprueft online lassen.
    try {
        $done=$db->query("SELECT setting_value FROM settings WHERE setting_key='marketplace_v61_recheck_done' LIMIT 1")->fetchColumn();
        if((string)$done!=='1'){
            $allowed=market_categories();$ph=implode(',',array_fill(0,count($allowed),'?'));
            $st=$db->prepare("UPDATE market_listings SET status='rejected',updated_at=NOW() WHERE status IN ('pending','published','paused') AND category NOT IN ($ph)");$st->execute($allowed);
            $db->exec("UPDATE market_listings SET status='pending',updated_at=NOW() WHERE status='published'");
            $db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('marketplace_v61_recheck_done','1',NOW()) ON DUPLICATE KEY UPDATE setting_value='1',updated_at=NOW()")->execute();
        }
    } catch(Throwable) {}

    try {
        $db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('schema_version','61',NOW()) ON DUPLICATE KEY UPDATE setting_value=IF(CAST(setting_value AS UNSIGNED)<61,'61',setting_value),updated_at=NOW()")->execute();
        $db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('db_schema_version','61',NOW()) ON DUPLICATE KEY UPDATE setting_value=IF(CAST(setting_value AS UNSIGNED)<61,'61',setting_value),updated_at=NOW()")->execute();
    } catch(Throwable) {}
}

function market_json(array $data,int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function market_id(string $prefix): string { return $prefix.strtoupper(bin2hex(random_bytes(6))); }
function market_strlen(string $s): int { return function_exists('mb_strlen') ? mb_strlen($s,'UTF-8') : strlen($s); }
function market_csrf(): string {
    if(empty($_SESSION['market_csrf'])) $_SESSION['market_csrf']=bin2hex(random_bytes(24));
    return (string)$_SESSION['market_csrf'];
}
function market_verify_csrf(?string $token): void {
    if(!$token || !hash_equals(market_csrf(),$token)) market_json(['ok'=>false,'error'=>'Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden.'],403);
}
function market_current_user_id(): string { return (string)($_SESSION['market_user_id']??''); }
function market_user_row(): ?array {
    $id=market_current_user_id(); if($id==='') return null;
    $q=market_db()->prepare('SELECT * FROM market_users WHERE id=? LIMIT 1');$q->execute([$id]);$u=$q->fetch();
    if(!$u){unset($_SESSION['market_user_id']);return null;}
    if(($u['membership_status']??'')==='active' && !empty($u['membership_expires_at']) && strtotime((string)$u['membership_expires_at']) < time()){
        market_db()->prepare("UPDATE market_users SET membership_status='expired',updated_at=NOW() WHERE id=?")->execute([$id]);$u['membership_status']='expired';
    }
    return $u;
}
function market_user_public(?array $u): ?array {
    if(!$u)return null;
    $count=0;try{$q=market_db()->prepare("SELECT COUNT(*) FROM market_listings WHERE user_id=? AND status IN ('pending','published','paused')");$q->execute([$u['id']]);$count=(int)$q->fetchColumn();}catch(Throwable){}
    return [
      'id'=>$u['id'],'email'=>$u['email'],'display_name'=>$u['display_name'],'phone'=>$u['phone']??'','zip'=>$u['zip']??'','city'=>$u['city']??'',
      'verified'=>!empty($u['email_verified_at']),'status'=>$u['status'],'membership_status'=>$u['membership_status'],
      'membership_started_at'=>$u['membership_started_at'],'membership_expires_at'=>$u['membership_expires_at'],'membership_order_no'=>$u['membership_order_no']??'',
      'terms_version'=>$u['terms_version']??'','terms_current'=>(($u['terms_version']??'')===RH24_MARKET_TERMS_VERSION),
      'active_listing_count'=>$count,'listing_limit'=>RH24_MARKET_MAX_ACTIVE_LISTINGS,'membership_active'=>market_membership_active($u)
    ];
}
function market_membership_active(array $u): bool {
    if(($u['status']??'')!=='active' || empty($u['email_verified_at']) || ($u['membership_status']??'')!=='active') return false;
    if(empty($u['membership_expires_at'])) return false;
    return strtotime((string)$u['membership_expires_at']) >= time();
}
function market_require_user(): array { $u=market_user_row();if(!$u)market_json(['ok'=>false,'error'=>'Bitte zuerst anmelden.'],401);if(($u['status']??'')!=='active')market_json(['ok'=>false,'error'=>'Dieses Marktplatzkonto ist gesperrt.'],403);return $u; }
function market_require_membership(): array { $u=market_require_user();if(empty($u['email_verified_at']))market_json(['ok'=>false,'error'=>'Bitte zuerst Ihre E-Mail-Adresse bestätigen.'],403);if(!market_membership_active($u))market_json(['ok'=>false,'error'=>'Zum Handeln benötigen Sie den aktiven Jahreszugang für 19,99 € inkl. MwSt.'],402);return $u; }
function market_mail(string $to,string $subject,string $body): bool {
    if(!filter_var($to,FILTER_VALIDATE_EMAIL))return false;
    $from='service@raeucherhaken24.com';
    $headers="From: Räucherhaken24 <$from>\r\nReply-To: $from\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
    return @mail($to,'=?UTF-8?B?'.base64_encode($subject).'?=',$body,$headers);
}
function market_customer_upsert(PDO $db,array $u): string {
    $email=strtolower(trim((string)$u['email']));
    $q=$db->prepare('SELECT id FROM customers WHERE email=?');$q->execute([$email]);$id=(string)($q->fetchColumn()?:'');
    if($id==='')$id=market_id('C-');
    $db->prepare('INSERT INTO customers(id,name,email,phone,company,street,zip,city,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),phone=VALUES(phone),zip=VALUES(zip),city=VALUES(city),updated_at=NOW()')
       ->execute([$id,$u['display_name'],$email,$u['phone']??'','','',$u['zip']??'',$u['city']??'','Marktplatz-Mitglied']);
    return $id;
}
function market_create_membership_order(PDO $db,array $u): string {
    if(($u['membership_status']??'')==='active' && !empty($u['membership_expires_at']) && strtotime((string)$u['membership_expires_at'])>=time()) return (string)($u['membership_order_no']??'');
    if(($u['membership_status']??'')==='pending' && !empty($u['membership_order_no'])){
        try{$q=$db->prepare('SELECT payment_status FROM orders WHERE order_no=? LIMIT 1');$q->execute([(string)$u['membership_order_no']]);$ps=(string)($q->fetchColumn()?:'');if($ps==='pending')return (string)$u['membership_order_no'];}catch(Throwable){}
    }
    $orderNo='RH24-M-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
    $gross=RH24_MARKET_FEE_GROSS;$net=round($gross/(1+RH24_MARKET_VAT_RATE/100),2);$vat=round($gross-$net,2);
    $custId=market_customer_upsert($db,$u);
    $customer=['name'=>$u['display_name'],'email'=>$u['email'],'phone'=>$u['phone']??'','street'=>'','zip'=>$u['zip']??'','city'=>$u['city']??'','company'=>''];
    $items=[['id'=>'marketplace-year','article_no'=>'MARKT-1J','name'=>'An- & Verkaufen · Jahreszugang','qty'=>1,'unit_price'=>$gross,'line_total'=>$gross,'meta'=>['duration'=>'1 Jahr','listing_limit'=>RH24_MARKET_MAX_ACTIVE_LISTINGS]]];
    $totals=['subtotal'=>$gross,'shipping'=>0,'net'=>$net,'vat'=>$vat,'vat_rate'=>RH24_MARKET_VAT_RATE,'gross'=>$gross,'product_weight_g'=>0,'shipping_weight_g'=>0];
    $history=[['at'=>date('c'),'type'=>'created','value'=>'Marktplatz-Jahreszugang gebucht'],['at'=>date('c'),'type'=>'payment','value'=>'pending']];
    $db->prepare('INSERT INTO orders(order_no,source,status,status_label,payment_status,payment_method,carrier,tracking,internal_note,customer_id,customer_json,items_json,totals_json,customer_note,history_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
       ->execute([$orderNo,'marketplace','payment_pending','Zahlung offen','pending','Auftragsbestätigung / Zahlungsanweisung','','','Marktplatz Jahreszugang 19,99 € inkl. MwSt.',$custId,json_encode($customer,JSON_UNESCAPED_UNICODE),json_encode($items,JSON_UNESCAPED_UNICODE),json_encode($totals,JSON_UNESCAPED_UNICODE),'',json_encode($history,JSON_UNESCAPED_UNICODE),date('Y-m-d H:i:s'),date('Y-m-d H:i:s')]);
    $db->prepare("UPDATE market_users SET membership_status='pending',membership_order_no=?,updated_at=NOW() WHERE id=?")->execute([$orderNo,$u['id']]);
    market_mail((string)$u['email'],'Ihr Jahreszugang An- & Verkaufen',"Vielen Dank für Ihre Registrierung bei Räucherhaken24 An- & Verkaufen.\n\nJahreszugang: 19,99 € inkl. MwSt.\nLaufzeit: 1 Jahr ab Freischaltung\nMaximal 10 aktive Anzeigen gleichzeitig\nAuftragsnummer: $orderNo\n\nNach bestätigtem Zahlungseingang wird Ihr Zugang automatisch für 365 Tage freigeschaltet.\n\nRäucherhaken24");
    market_mail('service@raeucherhaken24.com','Neuer Marktplatz-Jahreszugang '.$orderNo,"Neuer Marktplatz-Jahreszugang\n\nKunde: {$u['display_name']}\nE-Mail: {$u['email']}\nBetrag: 19,99 € inkl. MwSt.\nAuftragsnummer: $orderNo\n\nNach Zahlung im Orgaboard auf Bezahlt stellen; die Mitgliedschaft wird automatisch aktiviert.");
    return $orderNo;
}
function market_distance_km(float $lat1,float $lon1,float $lat2,float $lon2): float {
    $r=6371.0;$dLat=deg2rad($lat2-$lat1);$dLon=deg2rad($lon2-$lon1);$a=sin($dLat/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;return $r*2*atan2(sqrt($a),sqrt(1-$a));
}
function market_images(array $row): array { $d=json_decode((string)($row['images_json']??'[]'),true);return is_array($d)?array_values(array_filter($d,'is_string')):[]; }
function market_categories(): array {
    return [
      'Räucheröfen & Räucherschränke',
      'Smoker',
      'Grills',
      'Räucherhaken & Halter',
      'Räucherzubehör',
      'Grill- & BBQ-Zubehör',
      'Thermometer & Messtechnik',
      'Räucherholz, Chunks & Räuchermehl',
      'Brennstoff- & Feuerzubehör',
      'Ersatzteile & Werkzeuge'
    ];
}
function market_terms_current(array $u): bool { return (($u['terms_version']??'')===RH24_MARKET_TERMS_VERSION); }
function market_require_current_terms(): array {
    $u=market_require_user();
    if(!market_terms_current($u)) market_json(['ok'=>false,'error'=>'Bitte zuerst die aktuellen Marktplatz-AGB und Nutzungsregeln bestätigen.','code'=>'terms_required'],428);
    return $u;
}
function market_contains_disallowed_food(string $text): bool {
    $patterns=[
      '/\b(?:lebensmittel|nahrungsmittel|zum\s+verzehr|essbar|verfallsdatum|mhd|haltbar\s+bis)\b/iu',
      '/\b(?:räucherlachs|rauchlachs|grillfleisch|bratwurst|wurstpaket|fleischpaket|fischpaket|räucherlauge|pökelsalz|marinade)\b/iu',
      '/\bgeräuchert(?:e|er|es|en)?\s+(?:fisch|fleisch|wurst|käse)\b/iu',
      '/\bgewürz(?:e|mischung|mischungen)?\b/iu'
    ];
    foreach($patterns as $rx){ if(preg_match($rx,$text)) return true; }
    return false;
}
