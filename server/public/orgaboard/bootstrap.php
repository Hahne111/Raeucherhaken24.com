<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('RH24_ORGABOARD');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

const RH24_DB_CONFIG_FILE = __DIR__ . '/private/db-config.php';
const RH24_AI_CONFIG_FILE = __DIR__ . '/private/openai-config.php';

function rh24_ai_config(): array {
    if (!is_file(RH24_AI_CONFIG_FILE)) return [];
    $cfg = require RH24_AI_CONFIG_FILE;
    return is_array($cfg) ? $cfg : [];
}
function rh24_openai_api_key(): string {
    $env = trim((string)(getenv('OPENAI_API_KEY') ?: ''));
    if ($env !== '') return $env;
    $cfg = rh24_ai_config();
    return trim((string)($cfg['api_key'] ?? ''));
}
function rh24_openai_model(string $purpose='product'): string {
    $envProduct = trim((string)(getenv('OPENAI_PRODUCT_AI_MODEL') ?: ''));
    if ($purpose === 'product' && $envProduct !== '') return $envProduct;
    $envSmoky = trim((string)(getenv('OPENAI_SMOKY_MODEL') ?: ''));
    if ($purpose === 'smoky' && $envSmoky !== '') return $envSmoky;
    $cfg = rh24_ai_config();
    $specific = trim((string)($cfg[$purpose.'_model'] ?? ''));
    if ($specific !== '') return $specific;
    $generic = trim((string)($cfg['model'] ?? ''));
    return $generic !== '' ? $generic : 'gpt-5.6-luna';
}


function rh24_db_configured(): bool { return is_file(RH24_DB_CONFIG_FILE); }
function rh24_db_config(): array {
    if (!rh24_db_configured()) return [];
    $cfg = require RH24_DB_CONFIG_FILE;
    return is_array($cfg) ? $cfg : [];
}
function rh24_db(): PDO {
    static $db = null;
    if ($db instanceof PDO) return $db;
    $cfg = rh24_db_config();
    if (!$cfg) throw new RuntimeException('Datenbank ist noch nicht eingerichtet.');
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['database'], $cfg['charset'] ?? 'utf8mb4');
    $db = new PDO($dsn, (string)$cfg['user'], (string)$cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $schemaVersion=0;
    try { $schemaVersion=(int)($db->query("SELECT setting_value FROM settings WHERE setting_key='schema_version'")->fetchColumn() ?: 0); } catch(Throwable) {}
    if($schemaVersion < 37) rh24_ensure_v34_schema($db);
    if($schemaVersion < 38) rh24_ensure_v38_schema($db);
    if($schemaVersion < 40) rh24_ensure_v40_schema($db);
    if($schemaVersion < 41) rh24_ensure_v41_schema($db);
    if($schemaVersion < 42) rh24_ensure_v42_schema($db);
    if($schemaVersion < 43) rh24_ensure_v43_schema($db);
    if($schemaVersion < 44) rh24_ensure_v44_schema($db);
    if($schemaVersion < 45) rh24_ensure_v45_schema($db);
    if($schemaVersion < 46) rh24_ensure_v46_schema($db);
    if($schemaVersion < 47) rh24_ensure_v47_schema($db);
    if($schemaVersion < 49) rh24_ensure_v48_schema($db);
    if($schemaVersion < 50) rh24_ensure_v50_schema($db);
    if($schemaVersion < 51) rh24_ensure_v51_schema($db);
    if($schemaVersion < 57) rh24_ensure_v57_schema($db);
    if($schemaVersion < 58) rh24_ensure_v58_schema($db);
    if($schemaVersion < 59) rh24_ensure_v59_schema($db);
    if($schemaVersion < 60) rh24_ensure_v60_schema($db);
    if($schemaVersion < 68) rh24_ensure_v68_schema($db);
    if($schemaVersion < 69) rh24_ensure_v69_schema($db);
    if($schemaVersion < 71) rh24_ensure_v71_schema($db);
    if($schemaVersion < 72) rh24_ensure_v72_schema($db);
    if($schemaVersion < 73) rh24_ensure_v73_schema($db);
    if($schemaVersion < 74) rh24_ensure_v74_schema($db);
    if($schemaVersion < 75) rh24_ensure_v75_schema($db);
    if($schemaVersion < 76) rh24_ensure_v76_schema($db);
    if($schemaVersion < 77) rh24_ensure_v77_schema($db);
    if($schemaVersion < 80) rh24_ensure_v80_schema($db);
    if($schemaVersion < 86) rh24_ensure_v86_schema($db);
    if($schemaVersion < 89) rh24_ensure_v89_schema($db);
    if($schemaVersion < 91){try{rh24_ensure_v91_schema($db);}catch(Throwable $e){error_log('RH24 V91 migration deferred to finance self-repair: '.$e->getMessage());}}
    $appointmentSchema=0;
    try{$st=$db->prepare("SELECT setting_value FROM settings WHERE setting_key='appointment_schema_version'");$st->execute();$appointmentSchema=(int)($st->fetchColumn()?:0);}catch(Throwable $e){}
    if($appointmentSchema < 92 || !rh24_v92_appointments_ready($db)){try{rh24_ensure_v92_appointments_schema($db);}catch(Throwable $e){error_log('RH24 V92 appointment migration failed: '.$e->getMessage());}}
    if(function_exists('rh24_pos_schema_health')){try{$posHealth=rh24_pos_schema_health($db);if(!$posHealth['ready'] || (int)$posHealth['version']<94)rh24_ensure_v94_pos_schema($db);}catch(Throwable $e){error_log('RH24 V94 POS migration deferred to POS self-repair: '.$e->getMessage());}}
    if(function_exists('rh24_vehicle_schema_health')){try{$vh=rh24_vehicle_schema_health($db);if(!$vh['ready'] || (int)$vh['version']<98)rh24_ensure_v98_vehicle_schema($db);}catch(Throwable $e){error_log('RH24 V98 vehicle migration deferred: '.$e->getMessage());}}
    if(function_exists('rh24_trip_receipt_schema_health')){try{$rh=rh24_trip_receipt_schema_health($db);if(!$rh['ready'] || (int)$rh['version']<99)rh24_ensure_v99_trip_receipt_schema($db);}catch(Throwable $e){error_log('RH24 V99 trip receipt migration deferred: '.$e->getMessage());}}
    // V106.2: interne SEO-Schlüsselbegriffe für den KI-Produktoptimierer. Idempotent für bestehende Installationen.
    try{$q=$db->query("SHOW COLUMNS FROM products LIKE 'seo_keywords'");if(!$q->fetchColumn())$db->exec("ALTER TABLE products ADD COLUMN seo_keywords VARCHAR(500) NOT NULL DEFAULT '' AFTER seo_description");}catch(Throwable $e){}
    // V106.9: formatierbarer Vorteile-/Merkmale-Text. Bestehende features_json-Daten bleiben als kompatible Kurzliste erhalten.
    try{$q=$db->query("SHOW COLUMNS FROM products LIKE 'features_rich'");if(!$q->fetchColumn())$db->exec("ALTER TABLE products ADD COLUMN features_rich LONGTEXT NULL AFTER features_json");}catch(Throwable $e){}
    // Rich-Text benötigt mehr Speicher als die frühere 320-Zeichen-VARCHAR-Spalte. Nur einmal migrieren.
    try{$q=$db->query("SHOW COLUMNS FROM products LIKE 'short_description'");$col=$q->fetch();if($col&&stripos((string)($col['Type']??''),'text')===false)$db->exec("ALTER TABLE products MODIFY COLUMN short_description TEXT NOT NULL");}catch(Throwable $e){}
    return $db;
}
function rh24_json_decode(?string $raw, mixed $default=[]): mixed {
    if ($raw === null || $raw === '') return $default;
    $d = json_decode($raw, true);
    return $d === null && json_last_error() !== JSON_ERROR_NONE ? $default : $d;
}
function rh24_json_encode(mixed $value): string {
    $s = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($s === false) throw new RuntimeException('JSON-Daten konnten nicht gespeichert werden.');
    return $s;
}
function rh24_setting_get(string $key, mixed $default=null): mixed {
    if (!rh24_db_configured()) return $default;
    try {
        $st = rh24_db()->prepare('SELECT setting_value FROM settings WHERE setting_key=?');
        $st->execute([$key]);
        $v = $st->fetchColumn();
        return $v === false ? $default : $v;
    } catch (Throwable) { return $default; }
}
function rh24_setting_set(string $key, mixed $value): void {
    $st = rh24_db()->prepare('INSERT INTO settings(setting_key,setting_value,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()');
    $st->execute([$key, is_scalar($value) ? (string)$value : rh24_json_encode($value)]);
}

function rh24_warehouse_defaults(): array {
    return [
        'locations' => [
            ['id'=>'LOC-INBOUND','name'=>'Wareneingang','type'=>'inbound','description'=>'Anlieferung, Prüfung und erste Erfassung','capacity_skus'=>0,'zone_code'=>'WE'],
            ['id'=>'LOC-MAIN','name'=>'Hauptlager','type'=>'storage','description'=>'Regallager für laufende Bestände','capacity_skus'=>0,'zone_code'=>'HL'],
            ['id'=>'LOC-PICK','name'=>'Kommissionierung','type'=>'picking','description'=>'Bereitstellung für Fertigung und Versand','capacity_skus'=>0,'zone_code'=>'KO'],
            ['id'=>'LOC-SHIP','name'=>'Versandzone','type'=>'shipping','description'=>'Verpacken, Etikettieren und Versand','capacity_skus'=>0,'zone_code'=>'VZ'],
            ['id'=>'LOC-RET','name'=>'Retouren / QS','type'=>'quality','description'=>'Prüfung, Nacharbeit und Rückläufer','capacity_skus'=>0,'zone_code'=>'QS'],
        ],
        'suppliers' => [],
        'packaging' => [
            ['id'=>'PACK-BOX-S','name'=>'Karton klein','stock'=>0,'minimum'=>20,'unit'=>'Stück','location_id'=>'LOC-SHIP','supplier_id'=>'','notes'=>''],
            ['id'=>'PACK-FILL','name'=>'Füllmaterial','stock'=>0,'minimum'=>10,'unit'=>'Beutel','location_id'=>'LOC-SHIP','supplier_id'=>'','notes'=>''],
            ['id'=>'PACK-LABEL','name'=>'Versandetiketten','stock'=>0,'minimum'=>50,'unit'=>'Stück','location_id'=>'LOC-SHIP','supplier_id'=>'','notes'=>''],
        ],
        'purchase_orders' => [],
        'movements' => [],
        'tasks' => [],
        'stock_locations' => [],
    ];
}
function rh24_warehouse_data(): array {
    $defaults = rh24_warehouse_defaults();
    $raw = rh24_setting_get('warehouse_v84', '');
    $parsed = is_string($raw) ? rh24_json_decode($raw, []) : (is_array($raw) ? $raw : []);
    if (!is_array($parsed)) $parsed = [];
    foreach ($defaults as $key => $value) {
        if (array_key_exists($key, $parsed) && is_array($parsed[$key])) $defaults[$key] = array_values($parsed[$key]);
    }
    if (!$defaults['locations']) $defaults['locations'] = rh24_warehouse_defaults()['locations'];
    return $defaults;
}
function rh24_warehouse_save(array $warehouse): array {
    $current = rh24_warehouse_data();
    foreach (['locations','suppliers','packaging','purchase_orders','movements','tasks','stock_locations'] as $key) {
        if (isset($warehouse[$key]) && is_array($warehouse[$key])) $current[$key] = array_values($warehouse[$key]);
    }
    if (count($current['movements']) > 800) $current['movements'] = array_slice($current['movements'], 0, 800);
    if (count($current['tasks']) > 250) $current['tasks'] = array_slice($current['tasks'], 0, 250);
    rh24_setting_set('warehouse_v84', $current);
    return $current;
}

function rh24_config(): array {
    $defaults=['shop_name'=>'Räucherhaken24','shipping_threshold'=>'39','shipping_cost'=>'7','vat_rate'=>'19','system_email'=>'service@raeucherhaken24.com','commission_period'=>'monthly','commission_statement_day'=>'27','commission_payout_day'=>'1','star_thresholds'=>'[15000,20000,30000,40000,50000,75000]','newsletter_sender_name'=>'Räucherhaken24','newsletter_reply_to'=>'service@raeucherhaken24.com','shipping_default_carrier'=>'DPD','active_theme'=>'standard','google_routes_credentials'=>'','invoice_company_name'=>'Räucherhaken24','invoice_owner'=>'Björn Hahne','invoice_street'=>'Schiffbrücke 5','invoice_zip'=>'24340','invoice_city'=>'Eckernförde','invoice_country'=>'Deutschland','invoice_phone'=>'0176 / 20204188','invoice_email'=>'service@raeucherhaken24.com','invoice_website'=>'www.raeucherhaken24.de','invoice_tax_no'=>'','invoice_vat_id'=>'','invoice_iban'=>'','invoice_bic'=>'','invoice_bank_name'=>'','invoice_payment_days'=>'7','invoice_footer'=>'Vielen Dank für Ihren Einkauf bei Räucherhaken24.','invoice_auto_email'=>'1'];
    if (!rh24_db_configured()) return $defaults;
    try {
        $rows=rh24_db()->query('SELECT setting_key,setting_value FROM settings')->fetchAll();
        foreach($rows as $r) $defaults[(string)$r['setting_key']]=$r['setting_value'];
    } catch(Throwable) {}
    return $defaults;
}
function rh24_is_configured(): bool {
    if (!rh24_db_configured()) return false;
    try {
        $db=rh24_db();
        $count=(int)($db->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn() ?: 0);
        return $count>0 || (string)rh24_setting_get('admin_password_hash','') !== '';
    } catch(Throwable) { return (string)rh24_setting_get('admin_password_hash','') !== ''; }
}
function rh24_current_user(): array {
    return is_array($_SESSION['rh24_user']??null) ? $_SESSION['rh24_user'] : [];
}
function rh24_is_logged_in(): bool { return !empty($_SESSION['rh24_user']['id']); }
function rh24_is_admin(): bool { return rh24_is_logged_in() && (string)($_SESSION['rh24_user']['role']??'')==='admin'; }
function rh24_user_role(): string { return (string)(rh24_current_user()['role']??'guest'); }
function rh24_user_id(): string { return (string)(rh24_current_user()['id']??''); }
function rh24_user_sales_rep_id(): string { return (string)(rh24_current_user()['sales_rep_id']??''); }
function rh24_permission_catalog(): array {
    return [
      'view_dashboard'=>['group'=>'Allgemein','label'=>'Dashboard ansehen'],
      'view_customers'=>['group'=>'Kunden','label'=>'Kundendaten ansehen'],
      'edit_customers'=>['group'=>'Kunden','label'=>'Kunden anlegen und bearbeiten'],
      'view_sales'=>['group'=>'Vertrieb','label'=>'Produktberatung ansehen'],
      'save_consultations'=>['group'=>'Vertrieb','label'=>'Produktberatungen speichern'],
      'create_orders'=>['group'=>'Vertrieb','label'=>'Bestellungen für Kunden erfassen'],
      'view_own_stats'=>['group'=>'Vertrieb','label'=>'Eigene Verkaufs- und Provisionsstatistik ansehen'],
      'view_earnings_calculator'=>['group'=>'Vertrieb','label'=>'Persönlichen Verdienst- und Provisionsrechner verwenden'],
      'view_leaderboard'=>['group'=>'Vertrieb','label'=>'Mitarbeiter-Rangliste nach Provision ansehen'],
      'view_star_stats'=>['group'=>'Vertrieb','label'=>'Eigene Sternstatistik ansehen'],
      'view_sales_calendar'=>['group'=>'Vertrieb','label'=>'Vertriebskalender und Auszahlungstermine ansehen'],
      'view_dealer_visits'=>['group'=>'Vertrieb','label'=>'Händler-Besuchsplan und Kaufhistorie ansehen'],
      'manage_dealer_visits'=>['group'=>'Vertrieb','label'=>'Händlerbesuche abschließen und Folgetermine planen'],
      'view_territory_book'=>['group'=>'Vertrieb','label'=>'Eigenes Gebietsbuch und Branchenadressen ansehen'],
      'contact_territory_book'=>['group'=>'Vertrieb','label'=>'Kontakte im eigenen Gebietsbuch dokumentieren und Wiedervorlagen planen'],
      'view_triplog'=>['group'=>'Vertrieb','label'=>'Fahrtenbuch, Termine und Routenplanung ansehen'],
      'edit_triplog'=>['group'=>'Vertrieb','label'=>'Eigenes Fahrtenbuch führen, Fahrzeuge pflegen und Fahrten abschließen'],
      'view_appointments'=>['group'=>'Vertrieb','label'=>'Persönlichen Terminplaner und Kalender ansehen'],
      'edit_appointments'=>['group'=>'Vertrieb','label'=>'Eigene Kundentermine anlegen, ändern und abschließen'],
      'view_orders'=>['group'=>'Aufträge','label'=>'Bestellungen ansehen'],
      'edit_orders'=>['group'=>'Aufträge','label'=>'Bestellstatus und Zahlung bearbeiten'],
      'view_production'=>['group'=>'Produktion','label'=>'Produktion ansehen'],
      'edit_production'=>['group'=>'Produktion','label'=>'Produktionsschritte, Fortschritt und Mitarbeiterzuordnung bearbeiten'],
      'edit_prototypes'=>['group'=>'Aufträge','label'=>'Prototypen bearbeiten'],
      'view_prototypes'=>['group'=>'Aufträge','label'=>'Prototypen ansehen'],
      'view_products'=>['group'=>'Produkte','label'=>'Produkte und Preise ansehen'],
      'edit_products'=>['group'=>'Produkte','label'=>'Produkte und Preise bearbeiten'],
      'view_inventory'=>['group'=>'Produkte','label'=>'Lagerbestand ansehen'],
      'edit_inventory'=>['group'=>'Produkte','label'=>'Lagerbestand bearbeiten'],
      'view_shipping'=>['group'=>'Dokumente & Versand','label'=>'Versand ansehen'],
      'view_documents'=>['group'=>'Dokumente & Versand','label'=>'Rechnungen und Lieferscheine ansehen'],
      'edit_documents'=>['group'=>'Dokumente & Versand','label'=>'Rechnungen und Lieferscheine bearbeiten'],
      'view_messages'=>['group'=>'Kommunikation','label'=>'Nachrichten ansehen'],
      'send_messages'=>['group'=>'Kommunikation','label'=>'Nachrichten senden'],
      'manage_newsletter'=>['group'=>'Kommunikation','label'=>'Newsletter verwalten und versenden'],
      'change_own_password'=>['group'=>'Konto','label'=>'Eigenes Passwort ändern'],
      'view_finance'=>['group'=>'Finanzen','label'=>'Finanzen & Buchhaltung ansehen'],
      'edit_finance'=>['group'=>'Finanzen','label'=>'Buchungen, Belege und Finanzstammdaten bearbeiten'],
      'view_pos'=>['group'=>'Kasse & POS','label'=>'Kassensystem ansehen'],
      'operate_pos'=>['group'=>'Kasse & POS','label'=>'Kassenverkäufe erfassen und kassieren'],
      'pos_refund'=>['group'=>'Kasse & POS','label'=>'Retouren und Erstattungen durchführen'],
      'pos_cash_manage'=>['group'=>'Kasse & POS','label'=>'Bareinlagen und Barentnahmen buchen'],
      'pos_close_shift'=>['group'=>'Kasse & POS','label'=>'Kassenschichten und Z-Abschlüsse durchführen'],
      'pos_reports'=>['group'=>'Kasse & POS','label'=>'Kassenberichte und Exporte ansehen'],
      'pos_manage'=>['group'=>'Kasse & POS','label'=>'Kassenplätze und Hardware konfigurieren'],
      'pos_tse_manage'=>['group'=>'Kasse & POS','label'=>'TSE-/Fiskaleinstellungen verwalten'],
      'view_labels'=>['group'=>'Etikettenstudio','label'=>'Etikettenstudio, Vorlagen und Druckaufträge ansehen'],
      'edit_labels'=>['group'=>'Etikettenstudio','label'=>'Etikettenvorlagen und Layouts bearbeiten'],
      'print_labels'=>['group'=>'Etikettenstudio','label'=>'Etiketten erstellen, drucken und nachdrucken'],
      'manage_labels'=>['group'=>'Etikettenstudio','label'=>'Etikettendrucker, Schnittstellen und Einstellungen verwalten'],
      'manage_marketplace'=>['group'=>'Administration','label'=>'An- & Verkaufen verwalten']
    ];
}
function rh24_default_permissions_for_role(string $role): array {
    if($role==='admin') return ['*'];
    if($role==='field_sales') return ['view_dashboard','view_customers','edit_customers','view_sales','view_products','view_messages','send_messages','view_own_stats','view_earnings_calculator','view_leaderboard','view_star_stats','view_sales_calendar','view_dealer_visits','manage_dealer_visits','view_territory_book','contact_territory_book','view_triplog','edit_triplog','view_appointments','edit_appointments','change_own_password'];
    // V2026.2: 'edit_inventory' ergänzt. Die Produktion konnte Bestände
    // bisher ohnehin buchen, weil der Endpunkt keine Rechte geprüft hat.
    // Mit der neuen serverseitigen Prüfung bleibt dieser Arbeitsablauf so
    // erhalten – Kasse und Aussendienst haben das Recht bewusst nicht.
    if($role==='production') return ['view_dashboard','view_production','edit_production','view_inventory','edit_inventory','view_labels','print_labels','view_messages','send_messages','change_own_password'];
    if($role==='cashier') return ['view_dashboard','view_pos','operate_pos','pos_cash_manage','pos_close_shift','view_products','view_inventory','view_labels','print_labels','view_messages','send_messages','change_own_password'];
    return [];
}
function rh24_permissions(): array {
    if(rh24_is_admin()) return ['*'];
    $u=rh24_current_user();$custom=null;
    if(!empty($u['id'])){
      try{$q=rh24_db()->prepare('SELECT permissions_json FROM users WHERE id=?');$q->execute([(string)$u['id']]);$raw=$q->fetchColumn();if($raw!==false&&$raw!==null&&$raw!=='')$custom=rh24_json_decode((string)$raw,[]);}catch(Throwable){}
    }
    if($custom===null)$custom=$u['permissions']??null;
    if(is_array($custom)){
        $allowed=array_keys(rh24_permission_catalog());
        return array_values(array_intersect($custom,$allowed));
    }
    return rh24_default_permissions_for_role(rh24_user_role());
}
function rh24_can(string $permission): bool {
    $p=rh24_permissions();
    return in_array('*',$p,true)||in_array($permission,$p,true);
}
function rh24_require_login(): void {
    if (!rh24_is_logged_in()) {
        http_response_code(401); header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'error'=>'Nicht angemeldet'],JSON_UNESCAPED_UNICODE); exit;
    }
}
function rh24_require_admin(): void {
    rh24_require_login();
    if (!rh24_is_admin()) {
        http_response_code(403); header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'error'=>'Diese Aktion ist nur für Administratoren erlaubt.'],JSON_UNESCAPED_UNICODE); exit;
    }
}
function rh24_require_permission(string $permission): void {
    rh24_require_login();
    if(!rh24_can($permission)){
        http_response_code(403); header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'error'=>'Für diese Aktion fehlen die erforderlichen Rechte.'],JSON_UNESCAPED_UNICODE); exit;
    }
}
function rh24_login_user(string $username,string $password): ?array {
    $username=strtolower(trim($username));
    if($username===''||$password==='') return null;
    try {
        $st=rh24_db()->prepare("SELECT * FROM users WHERE username=? AND status='active' LIMIT 1");
        $st->execute([$username]); $u=$st->fetch();
        if(!$u || !password_verify($password,(string)$u['password_hash'])) return null;
        $user=['id'=>(string)$u['id'],'username'=>(string)$u['username'],'display_name'=>(string)$u['display_name'],'role'=>(string)$u['role'],'sales_rep_id'=>(string)($u['sales_rep_id']??''),'permissions'=>rh24_json_decode($u['permissions_json']??'',null),'must_change_password'=>(bool)$u['must_change_password']];
        rh24_db()->prepare('UPDATE users SET last_login=NOW(),updated_at=NOW() WHERE id=?')->execute([$u['id']]);
        return $user;
    } catch(Throwable) { return null; }
}
function rh24_csrf(): string {
    if (empty($_SESSION['rh24_csrf'])) $_SESSION['rh24_csrf']=bin2hex(random_bytes(24));
    return $_SESSION['rh24_csrf'];
}
function rh24_verify_csrf(?string $token): void {
    $known=$_SESSION['rh24_csrf']??'';
    if(!$token||!$known||!hash_equals($known,$token)){
        http_response_code(403); header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'error'=>'Ungültige Sitzung. Seite neu laden.'],JSON_UNESCAPED_UNICODE); exit;
    }
}
function rh24_now(): string { return date('Y-m-d H:i:s'); }
function rh24_iso(?string $dbDate): string { return $dbDate ? date('c', strtotime($dbDate)) : ''; }
function rh24_random_id(string $prefix): string { return $prefix . strtoupper(bin2hex(random_bytes(4))); }
function rh24_audit(string $action,string $type='',string $id='',array $detail=[],string $actor=''): void {
    if($actor===''){
        $u=rh24_current_user();
        $actor=(string)($u['display_name']??$u['username']??'system');
    }
    try { $st=rh24_db()->prepare('INSERT INTO activity_log(actor,action_name,entity_type,entity_id,detail_json,created_at) VALUES(?,?,?,?,?,NOW())'); $st->execute([$actor,$action,$type,$id,rh24_json_encode($detail)]); } catch(Throwable) {}
}

function rh24_order_statuses(): array { return ['new'=>'Neu','payment_pending'=>'Zahlung offen','paid'=>'Bezahlt','production'=>'In Fertigung','quality'=>'Qualitätskontrolle','packing'=>'Verpacken','ready'=>'Versandbereit','shipped'=>'Versendet','complete'=>'Abgeschlossen','cancelled'=>'Storniert']; }
function rh24_prototype_statuses(): array { return ['new'=>'Anfrage','payment_pending'=>'Zahlung offen','paid'=>'Bezahlt','review'=>'Technische Prüfung','construction'=>'Konstruktion','approval'=>'Freigabe','production'=>'Fertigung','quality'=>'Qualitätskontrolle','shipped'=>'Versendet','complete'=>'Abgeschlossen','cancelled'=>'Storniert']; }

function rh24_next_article_no(PDO $db): string {
    $next=(int)rh24_setting_get('next_article_no','20001');
    if($next < 20001) $next=20001;
    $check=$db->prepare('SELECT 1 FROM products WHERE article_no=? LIMIT 1');
    do { $nr=(string)$next++; $check->execute([$nr]); } while($check->fetchColumn());
    rh24_setting_set('next_article_no',(string)$next);
    return $nr;
}

function rh24_default_catalog(): array {
    return [
        'prototype-project'=>['sku'=>'PROTO-149','article_no'=>'90001','name'=>'Prototypenentwicklung Räucherhaken','category'=>'Sonderanfertigung','base'=>149.00,'unit'=>'Projekt','type'=>'prototype','stock'=>999,'minimum'=>0,'status'=>'active','description'=>'Individuelle Prototypenentwicklung und Projektstart.','image'=>'assets/smoky-hilfe-button.png','shop_visible'=>false],
        'std'=>['sku'=>'RH-STD','article_no'=>'10001','name'=>'Räucherhaken Standard – 10er-Set','category'=>'Räucherhaken','base'=>12.90,'unit'=>'10er-Set','type'=>'hook','stock'=>320,'minimum'=>50,'status'=>'active','description'=>'Standard-Räucherhaken mit Varianten.','image'=>'assets/standard.png','shop_visible'=>true],
        'aal'=>['sku'=>'RH-AAL','article_no'=>'10002','name'=>'Räucherhaken Standard Aal – 10er-Set','category'=>'Räucherhaken','base'=>12.90,'unit'=>'10er-Set','type'=>'hook','stock'=>180,'minimum'=>50,'status'=>'active','description'=>'Räucherhaken mit kleinem Hakenbogen für Aal, Hornhecht und schlanke Fische.','image'=>'assets/standard-aal-weiss.png','shop_visible'=>true],
        'ultra'=>['sku'=>'RH-ULT','article_no'=>'10003','name'=>'Räucherhaken Ultra – 10er-Set','category'=>'Räucherhaken','base'=>19.90,'unit'=>'10er-Set','type'=>'hook','stock'=>120,'minimum'=>50,'status'=>'active','description'=>'Extra stabil für große und schwere Fische.','image'=>'assets/ultra-original-korrekt.png','shop_visible'=>true],
        'kralle'=>['sku'=>'RH-KRA','article_no'=>'10004','name'=>'Räucherhaken Kralle – 10er-Set','category'=>'Räucherhaken','base'=>18.90,'unit'=>'10er-Set','type'=>'hook','stock'=>80,'minimum'=>50,'status'=>'active','description'=>'Mehrpunkt-Halt für große und schwere Fische.','image'=>'assets/kralle.png','shop_visible'=>true],
        'filet'=>['sku'=>'RH-FIL','article_no'=>'10005','name'=>'Räucherhaken Filet – 10er-Set','category'=>'Räucherhaken','base'=>15.90,'unit'=>'10er-Set','type'=>'hook','stock'=>110,'minimum'=>50,'status'=>'active','description'=>'Für Filets und flache Räucherstücke.','image'=>'assets/filet.png','shop_visible'=>true],
        'doppel'=>['sku'=>'RH-DOP','article_no'=>'10006','name'=>'Räucherhaken Doppeldorn – 10er-Set','category'=>'Räucherhaken','base'=>15.90,'unit'=>'10er-Set','type'=>'hook','stock'=>90,'minimum'=>50,'status'=>'active','description'=>'Doppeldorn-Ausführung für mehr Stabilität.','image'=>'assets/doppeldorn.png','shop_visible'=>true],
        'fleisch'=>['sku'=>'FH-S5','article_no'=>'10007','name'=>'Fleischerhaken S-Form 5 mm','category'=>'Fleischerhaken','base'=>7.90,'unit'=>'Stück','type'=>'product','stock'=>150,'minimum'=>50,'status'=>'active','description'=>'Massiver Fleischerhaken in S-Form für Schinken und schwere Fleischstücke.','image'=>'assets/fleischer.jpeg','shop_visible'=>true],
        'mehl-buche'=>['sku'=>'RM-BUC','article_no'=>'11001','name'=>'Räuchermehl Buche – 500 g','category'=>'Räuchermehl','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>70,'minimum'=>50,'status'=>'active','description'=>'Klassisches Räuchermehl Buche – ausgewogener Allrounder.','image'=>'assets/raeuchermehl-buche-produkt.jpg','shop_visible'=>true],
        'mehl-erle'=>['sku'=>'RM-ERL','article_no'=>'11002','name'=>'Räuchermehl Erle – 500 g','category'=>'Räuchermehl','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>55,'minimum'=>50,'status'=>'active','description'=>'Mildes Räuchermehl Erle – besonders passend zu Fisch.','image'=>'assets/raeuchermehl-erle-produkt.jpg','shop_visible'=>true],
        'mehl-birke'=>['sku'=>'RM-BIR','article_no'=>'11003','name'=>'Räuchermehl Birke – 500 g','category'=>'Räuchermehl','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Mildes Räuchermehl aus Birke.','image'=>'assets/raeuchermehl-birke-produkt.jpg','shop_visible'=>true],
        'mehl-eiche'=>['sku'=>'RM-EIC','article_no'=>'11004','name'=>'Räuchermehl Eiche – 500 g','category'=>'Räuchermehl','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Kräftiges Räuchermehl aus Eiche.','image'=>'assets/raeuchermehl-eiche-produkt.jpg','shop_visible'=>true],
        'mehl-kirsche'=>['sku'=>'RM-KIR','article_no'=>'11005','name'=>'Räuchermehl Kirsche – 500 g','category'=>'Räuchermehl','base'=>6.95,'unit'=>'500 g','type'=>'product','stock'=>35,'minimum'=>50,'status'=>'active','description'=>'Mild-fruchtiges Räuchermehl aus Kirschholz.','image'=>'assets/raeuchermehl-kirsche-produkt.jpg','shop_visible'=>true],
        'lauge-forelle-0'=>['sku'=>'RL-FOR-STD','article_no'=>'12001','name'=>'Räucherlauge Forelle – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>80,'minimum'=>50,'status'=>'active','description'=>'Salz-Gewürz-Mischung mit Dill und Wacholder für Forellen.','image'=>'assets/lauge-standard.png','shop_visible'=>true],
        'lauge-forelle-1'=>['sku'=>'RL-FOR-CLA','article_no'=>'12002','name'=>'Räucherlauge Forelle Classic – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Klassische fein-würzige Forellen-Räucherlauge mit Wacholderaroma.','image'=>'assets/lauge-delikat.png','shop_visible'=>true],
        'lauge-forelle-2'=>['sku'=>'RL-FOR-CHI','article_no'=>'12003','name'=>'Räucherlauge Forelle Chili – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>45,'minimum'=>40,'status'=>'active','description'=>'Forellen-Räucherlauge mit fein-feuriger Chili-Note.','image'=>'assets/lauge-chili.png','shop_visible'=>true],
        'lauge-forelle-3'=>['sku'=>'RL-FOR-RED','article_no'=>'12004','name'=>'Räucherlauge Forelle RED – 500 g','category'=>'Räucherlaugen Forelle','base'=>6.95,'unit'=>'500 g','type'=>'product','stock'=>45,'minimum'=>40,'status'=>'active','description'=>'Gewürzmischung mit ganzen Wacholderbeeren für Forellen und Lachsforellen.','image'=>'assets/lauge-red.png','shop_visible'=>true],
        'lauge-forelle-4'=>['sku'=>'RL-FOR-KRA','article_no'=>'12005','name'=>'Räucherlauge Forelle Kräuter – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Milde Forellen-Räucherlauge mit feiner Kräuternote.','image'=>'assets/lauge-kraeuter.png','shop_visible'=>true],
        'lauge-forelle-5'=>['sku'=>'RL-FOR-KNO','article_no'=>'12006','name'=>'Räucherlauge Forelle Knoblauch – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Herzhafte Forellen-Räucherlauge mit feiner Knoblauchnote.','image'=>'assets/lauge-knoblauch.png','shop_visible'=>true],
        'lauge-forelle-6'=>['sku'=>'RL-FOR-ZPF','article_no'=>'12007','name'=>'Räucherlauge Forelle Zitronenpfeffer – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Forellen-Räucherlauge mit Zitronennote und mildem Pfeffer.','image'=>'assets/lauge-zitronenpfeffer.png','shop_visible'=>true],
        'lauge-forelle-7'=>['sku'=>'RL-FOR-DEL','article_no'=>'12008','name'=>'Räucherlauge Forelle Delikat – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Fein abgestimmte Forellen-Räucherlauge mit leicht feurigem Geschmack.','image'=>'assets/lauge-gartenkraeuter.png','shop_visible'=>true],
        'lauge-forelle-8'=>['sku'=>'RL-FOR-ELP','article_no'=>'12009','name'=>'Räucherlauge Forelle EL PASO – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Kräftig-würzige Forellen-Räucherlauge mit leicht pikant-rauchiger Note.','image'=>'assets/lauge-elpaso.png','shop_visible'=>true],
        'lauge-forelle-9'=>['sku'=>'RL-FOR-KAN','article_no'=>'12010','name'=>'Räucherlauge Forelle Kansas – 500 g','category'=>'Räucherlaugen Forelle','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Abgestimmte Räucherlauge für Lachsforellen und Forellen.','image'=>'assets/lauge-kansas.png','shop_visible'=>true],
        'lauge-aal-0'=>['sku'=>'RL-AAL-STD','article_no'=>'12101','name'=>'Räucherlauge Aal – 500 g','category'=>'Räucherlaugen Aal','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>65,'minimum'=>50,'status'=>'active','description'=>'Kräftige klassische Salz-Gewürz-Mischung für Räucheraal.','image'=>'assets/lauge-aal_standard.png','shop_visible'=>true],
        'lauge-aal-1'=>['sku'=>'RL-AAL-PFE','article_no'=>'12102','name'=>'Räucherlauge Aal Pfeffer – 500 g','category'=>'Räucherlaugen Aal','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Aal-Räucherlauge mit fein-würziger Pfeffernote.','image'=>'assets/lauge-aal_pfeffer.png','shop_visible'=>true],
        'lauge-aal-2'=>['sku'=>'RL-AAL-DEL','article_no'=>'12103','name'=>'Räucherlauge Aal Delikat – 500 g','category'=>'Räucherlaugen Aal','base'=>4.95,'unit'=>'500 g','type'=>'product','stock'=>50,'minimum'=>40,'status'=>'active','description'=>'Milde, fein ausgewogene Aal-Räucherlauge.','image'=>'assets/lauge-aal_delikat.png','shop_visible'=>true],
    ];
}
function rh24_ensure_v34_schema(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS products (
          id VARCHAR(80) NOT NULL PRIMARY KEY,
          sku VARCHAR(100) NOT NULL DEFAULT '',
          article_no VARCHAR(40) NULL,
          name VARCHAR(220) NOT NULL,
          category VARCHAR(120) NOT NULL DEFAULT 'Sonstiges',
          product_type VARCHAR(40) NOT NULL DEFAULT 'product',
          base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          unit VARCHAR(80) NOT NULL DEFAULT 'Stück',
          product_weight_g INT UNSIGNED NOT NULL DEFAULT 0,
          shipping_weight_g INT UNSIGNED NOT NULL DEFAULT 0,
          status VARCHAR(30) NOT NULL DEFAULT 'active',
          description TEXT NULL,
          image_path VARCHAR(255) NOT NULL DEFAULT '',
          shop_visible TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          KEY idx_products_name (name),
          KEY idx_products_category (category),
          KEY idx_products_status (status),
          KEY idx_products_sku (sku),
          UNIQUE KEY uq_products_article_no (article_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try { $db->exec("ALTER TABLE products ADD COLUMN article_no VARCHAR(40) NULL AFTER sku"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NOT NULL DEFAULT '' AFTER description"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE products ADD COLUMN shop_visible TINYINT(1) NOT NULL DEFAULT 1 AFTER image_path"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE products ADD COLUMN product_weight_g INT UNSIGNED NOT NULL DEFAULT 0 AFTER unit"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE products ADD COLUMN shipping_weight_g INT UNSIGNED NOT NULL DEFAULT 0 AFTER product_weight_g"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE products ADD UNIQUE KEY uq_products_article_no (article_no)"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE customers MODIFY email VARCHAR(190) NULL"); } catch(Throwable) {}

        $version=(int)($db->query("SELECT setting_value FROM settings WHERE setting_key='schema_version'")->fetchColumn() ?: 0);
        if($version < 35){
            $articleMap=[
              'std'=>'10001','aal'=>'10002','ultra'=>'10003','kralle'=>'10004','filet'=>'10005','doppel'=>'10006','fleisch'=>'10007',
              'mehlBuche'=>'11001','mehlErle'=>'11002','mehlBirke'=>'11003','mehlEiche'=>'11004','mehlKirsche'=>'11005',
              'lauForelle'=>'12001','lauForelleChili'=>'12002','lauForelleRed'=>'12003','lauAal'=>'12004','prototype-project'=>'90001'
            ];
            $imageMap=[
              'std'=>'assets/standard.png','aal'=>'assets/standard-aal-weiss.png','ultra'=>'assets/ultra-original-korrekt.png','kralle'=>'assets/kralle.png','filet'=>'assets/filet.png','doppel'=>'assets/doppeldorn.png','fleisch'=>'assets/fleischer.jpeg',
              'mehlBuche'=>'assets/raeuchermehl-buche-produkt.jpg','mehlErle'=>'assets/raeuchermehl-erle-produkt.jpg','mehlBirke'=>'assets/raeuchermehl-birke-produkt.jpg','mehlEiche'=>'assets/raeuchermehl-eiche-produkt.jpg','mehlKirsche'=>'assets/raeuchermehl-kirsche-produkt.jpg',
              'lauForelle'=>'assets/lauge-standard.png','lauForelleChili'=>'assets/lauge-chili.png','lauForelleRed'=>'assets/lauge-red.png','lauAal'=>'assets/lauge-aal_standard.png','prototype-project'=>'assets/smoky-hilfe-button.png'
            ];
            $extra=[
              ['mehlBirke','RM-BIR','Räuchermehl Birke – 500 g','Räuchermehl','product',4.95,'500 g','active','Mildes Räuchermehl aus Birke.'],
              ['mehlEiche','RM-EIC','Räuchermehl Eiche – 500 g','Räuchermehl','product',5.95,'500 g','active','Kräftiges Räuchermehl aus Eiche.']
            ];
            $ins=$db->prepare('INSERT IGNORE INTO products(id,sku,article_no,name,category,product_type,base_price,unit,status,description,image_path,shop_visible,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
            foreach($extra as $r){$ins->execute([$r[0],$r[1],$articleMap[$r[0]],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7],$r[8],$imageMap[$r[0]]??'',1]);}
            $i=$db->prepare('INSERT IGNORE INTO inventory(id,name,stock,minimum,unit,updated_at) VALUES(?,?,?,?,?,NOW())');
            $i->execute(['mehlBirke','Räuchermehl Birke – 500 g',50,40,'500 g']);
            $i->execute(['mehlEiche','Räuchermehl Eiche – 500 g',50,40,'500 g']);
            $up=$db->prepare("UPDATE products SET article_no=COALESCE(NULLIF(article_no,''),?), image_path=CASE WHEN image_path='' THEN ? ELSE image_path END, shop_visible=? WHERE id=?");
            foreach($articleMap as $id=>$nr){$up->execute([$nr,$imageMap[$id]??'', $id==='prototype-project'?0:1, $id]);}
            $missing=$db->query("SELECT id FROM products WHERE article_no IS NULL OR article_no='' ORDER BY created_at,id")->fetchAll();
            $setNr=$db->prepare('UPDATE products SET article_no=? WHERE id=?');
            foreach($missing as $m){$setNr->execute([rh24_next_article_no($db),(string)$m['id']]);}
            try { $db->exec("ALTER TABLE products ADD UNIQUE KEY uq_products_article_no (article_no)"); } catch(Throwable) {}
            rh24_setting_set('schema_version','35');
        }

        // V36: Telefonverkauf, Produktberatung und Außendienst
        $db->exec("CREATE TABLE IF NOT EXISTS sales_reps (
          id VARCHAR(40) NOT NULL PRIMARY KEY,
          employee_no VARCHAR(40) NOT NULL,
          name VARCHAR(180) NOT NULL,
          email VARCHAR(190) NULL,
          phone VARCHAR(80) NOT NULL DEFAULT '',
          territory VARCHAR(180) NOT NULL DEFAULT '',
          commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
          status VARCHAR(30) NOT NULL DEFAULT 'active',
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_sales_reps_employee_no (employee_no),
          KEY idx_sales_reps_name (name),
          KEY idx_sales_reps_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS consultations (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          customer_id VARCHAR(40) NULL,
          sales_rep_id VARCHAR(40) NULL,
          channel VARCHAR(40) NOT NULL DEFAULT 'phone',
          needs_json LONGTEXT NOT NULL,
          recommendation_json LONGTEXT NOT NULL,
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          KEY idx_consult_customer (customer_id),
          KEY idx_consult_rep (sales_rep_id),
          KEY idx_consult_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try { $db->exec("ALTER TABLE customers ADD COLUMN sales_rep_id VARCHAR(40) NULL AFTER notes"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE orders ADD COLUMN sales_rep_id VARCHAR(40) NULL AFTER customer_id"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE orders ADD COLUMN consultation_id VARCHAR(60) NULL AFTER sales_rep_id"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE orders ADD KEY idx_orders_sales_rep (sales_rep_id)"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE customers ADD KEY idx_customers_sales_rep (sales_rep_id)"); } catch(Throwable) {}
        if((int)rh24_setting_get('schema_version','0') < 36) rh24_setting_set('schema_version','36');


        // V37: vollständiger Artikelstamm mit festen Artikelnummern für alle vorhandenen Shopprodukte
        if((int)rh24_setting_get('schema_version','0') < 37){
            $aliases=['mehlBuche'=>'mehl-buche','mehlErle'=>'mehl-erle','mehlBirke'=>'mehl-birke','mehlEiche'=>'mehl-eiche','mehlKirsche'=>'mehl-kirsche','lauForelle'=>'lauge-forelle-0','lauForelleChili'=>'lauge-forelle-2','lauForelleRed'=>'lauge-forelle-3','lauAal'=>'lauge-aal-0'];
            foreach($aliases as $old=>$new){
                $q=$db->prepare('SELECT id FROM products WHERE id=?');$q->execute([$old]);$hasOld=(bool)$q->fetchColumn();
                $q->execute([$new]);$hasNew=(bool)$q->fetchColumn();
                if($hasOld && !$hasNew){
                    $iq=$db->prepare('SELECT id FROM inventory WHERE id=?');$iq->execute([$new]);
                    if($iq->fetchColumn()){$db->prepare('DELETE FROM inventory WHERE id=?')->execute([$old]);}
                    else{$db->prepare('UPDATE inventory SET id=? WHERE id=?')->execute([$new,$old]);}
                    $db->prepare('UPDATE products SET id=? WHERE id=?')->execute([$new,$old]);
                } elseif($hasOld && $hasNew){
                    $db->prepare('DELETE FROM inventory WHERE id=?')->execute([$old]);
                    $db->prepare('DELETE FROM products WHERE id=?')->execute([$old]);
                }
            }
            $predefined=['prototype-project','std','aal','ultra','kralle','filet','doppel','fleisch','mehl-buche','mehl-erle','mehl-birke','mehl-eiche','mehl-kirsche','lauge-forelle-0','lauge-forelle-1','lauge-forelle-2','lauge-forelle-3','lauge-forelle-4','lauge-forelle-5','lauge-forelle-6','lauge-forelle-7','lauge-forelle-8','lauge-forelle-9','lauge-aal-0','lauge-aal-1','lauge-aal-2'];
            $marks=implode(',',array_fill(0,count($predefined),'?'));
            $db->prepare("UPDATE products SET article_no=NULL WHERE id IN ($marks)")->execute($predefined);
            $ins=$db->prepare("INSERT INTO products(id,sku,article_no,name,category,product_type,base_price,unit,status,description,image_path,shop_visible,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE sku=VALUES(sku),article_no=VALUES(article_no),name=VALUES(name),category=VALUES(category),product_type=VALUES(product_type),base_price=VALUES(base_price),unit=VALUES(unit),status=VALUES(status),description=VALUES(description),image_path=CASE WHEN products.image_path='' THEN VALUES(image_path) ELSE products.image_path END,shop_visible=VALUES(shop_visible),updated_at=NOW()");
            $inv=$db->prepare('INSERT INTO inventory(id,name,stock,minimum,unit,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),unit=VALUES(unit),updated_at=NOW()');
            foreach(rh24_default_catalog() as $id=>$p){
                $ins->execute([$id,$p['sku'],$p['article_no'],$p['name'],$p['category'],$p['type'],$p['base'],$p['unit'],$p['status'],$p['description'],$p['image'],$p['shop_visible']?1:0]);
                if($p['type']!=='prototype')$inv->execute([$id,$p['name'],$p['stock'],$p['minimum'],$p['unit']]);
            }
            $missing=$db->query("SELECT id FROM products WHERE article_no IS NULL OR article_no='' ORDER BY created_at,id")->fetchAll();
            $setNr=$db->prepare('UPDATE products SET article_no=? WHERE id=?');
            foreach($missing as $m)$setNr->execute([rh24_next_article_no($db),(string)$m['id']]);
            rh24_setting_set('next_article_no',(string)max(20001,(int)rh24_setting_get('next_article_no','20001')));
            rh24_setting_set('schema_version','37');
        }
    } catch(Throwable $e) {
        // Das Orgaboard bleibt bei einem fehlgeschlagenen Komfort-Upgrade grundsätzlich erreichbar.
    }
}

function rh24_ensure_v38_schema(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
          id VARCHAR(40) NOT NULL PRIMARY KEY,
          username VARCHAR(80) NOT NULL,
          display_name VARCHAR(180) NOT NULL,
          email VARCHAR(190) NULL,
          role VARCHAR(40) NOT NULL DEFAULT 'field_sales',
          sales_rep_id VARCHAR(40) NULL,
          password_hash VARCHAR(255) NOT NULL,
          status VARCHAR(30) NOT NULL DEFAULT 'active',
          must_change_password TINYINT(1) NOT NULL DEFAULT 0,
          last_login DATETIME NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_users_username (username),
          KEY idx_users_role (role),
          KEY idx_users_sales_rep (sales_rep_id),
          KEY idx_users_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS documents (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          document_type VARCHAR(30) NOT NULL,
          document_no VARCHAR(60) NOT NULL,
          order_no VARCHAR(60) NOT NULL,
          status VARCHAR(30) NOT NULL DEFAULT 'draft',
          version_no INT NOT NULL DEFAULT 1,
          payload_json LONGTEXT NOT NULL,
          note TEXT NULL,
          issued_at DATETIME NULL,
          created_by VARCHAR(40) NULL,
          updated_by VARCHAR(40) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_documents_no (document_no),
          UNIQUE KEY uq_documents_order_type (order_no,document_type),
          KEY idx_documents_order (order_no),
          KEY idx_documents_type_status (document_type,status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS document_versions (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          document_id VARCHAR(60) NOT NULL,
          version_no INT NOT NULL,
          payload_json LONGTEXT NOT NULL,
          change_note VARCHAR(255) NOT NULL DEFAULT '',
          edited_by VARCHAR(40) NULL,
          created_at DATETIME NOT NULL,
          KEY idx_doc_versions_doc (document_id,version_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS messages (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          thread_id VARCHAR(60) NOT NULL,
          sender_user_id VARCHAR(40) NOT NULL,
          recipient_user_id VARCHAR(40) NOT NULL,
          subject VARCHAR(220) NOT NULL DEFAULT '',
          body TEXT NOT NULL,
          read_at DATETIME NULL,
          created_at DATETIME NOT NULL,
          KEY idx_messages_recipient (recipient_user_id,read_at,created_at),
          KEY idx_messages_thread (thread_id,created_at),
          KEY idx_messages_sender (sender_user_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS product_cost_profiles (
          product_id VARCHAR(80) NOT NULL PRIMARY KEY,
          material_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          labor_minutes DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          labor_hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 32.00,
          packaging_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          other_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          overhead_percent DECIMAL(6,2) NOT NULL DEFAULT 12.00,
          selling_fee_percent DECIMAL(6,2) NOT NULL DEFAULT 2.50,
          target_margin_percent DECIMAL(6,2) NOT NULL DEFAULT 45.00,
          vat_percent DECIMAL(6,2) NOT NULL DEFAULT 19.00,
          calculated_gross DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          updated_by VARCHAR(40) NULL,
          updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $version=(int)($db->query("SELECT setting_value FROM settings WHERE setting_key='schema_version'")->fetchColumn() ?: 0);
        if($version < 38){
            $legacyHash=(string)($db->query("SELECT setting_value FROM settings WHERE setting_key='admin_password_hash'")->fetchColumn() ?: '');
            if($legacyHash!==''){
                $ins=$db->prepare("INSERT IGNORE INTO users(id,username,display_name,email,role,sales_rep_id,password_hash,status,must_change_password,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW())");
                $ins->execute(['USR-BJOERN','bjoern.hahne','Björn Hahne',null,'admin',null,$legacyHash,'active',1]);
                $ins->execute(['USR-JESSICA','jessica.hahne','Jessica Hahne',null,'admin',null,$legacyHash,'active',1]);
            }
            rh24_setting_set('schema_version','38');
            rh24_setting_set('db_schema_version','38');
            if((string)rh24_setting_get('next_invoice_no','')==='') rh24_setting_set('next_invoice_no','1');
            if((string)rh24_setting_get('next_delivery_no','')==='') rh24_setting_set('next_delivery_no','1');
            rh24_audit('schema_upgrade','system','v38',['features'=>['roles','documents','calculator','messages']],'system');
        }
    } catch(Throwable $e) {
        // Komfort-Upgrade darf den Login nicht vollständig blockieren.
    }
}

function rh24_ensure_v40_schema(PDO $db): void {
    try {
        try { $db->exec("ALTER TABLE users ADD COLUMN permissions_json LONGTEXT NULL AFTER sales_rep_id"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE users ADD COLUMN welcome_sent_at DATETIME NULL AFTER must_change_password"); } catch(Throwable) {}
        $db->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          user_id VARCHAR(40) NOT NULL,
          purpose VARCHAR(30) NOT NULL DEFAULT 'reset',
          token_hash CHAR(64) NOT NULL,
          expires_at DATETIME NOT NULL,
          used_at DATETIME NULL,
          created_at DATETIME NOT NULL,
          UNIQUE KEY uq_reset_token_hash (token_hash),
          KEY idx_reset_user (user_id,used_at,expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS mail_log (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          user_id VARCHAR(40) NULL,
          recipient VARCHAR(190) NOT NULL,
          mail_type VARCHAR(40) NOT NULL,
          subject VARCHAR(220) NOT NULL,
          status VARCHAR(30) NOT NULL,
          created_at DATETIME NOT NULL,
          KEY idx_mail_user (user_id,created_at),
          KEY idx_mail_status (status,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if((int)rh24_setting_get('schema_version','0') < 40){
            rh24_setting_set('schema_version','40');
            if((string)rh24_setting_get('system_email','')==='') rh24_setting_set('system_email','service@raeucherhaken24.com');
            if((string)rh24_setting_get('commission_period','')==='') rh24_setting_set('commission_period','monthly');
            rh24_audit('schema_upgrade','system','v40',['features'=>['welcome_email','password_reset','permissions_center','commission_stats']],'system');
        }
    } catch(Throwable) {}
}


function rh24_ensure_v41_schema(PDO $db): void {
    try {
        $version=(int)rh24_setting_get('schema_version','0');
        if($version < 41){
            $q=$db->query("SELECT id,permissions_json FROM users WHERE role='field_sales'");
            $up=$db->prepare('UPDATE users SET permissions_json=?,updated_at=NOW() WHERE id=?');
            foreach($q->fetchAll() as $u){
                $perms=rh24_json_decode((string)($u['permissions_json']??''),null);
                if(!is_array($perms)) $perms=rh24_default_permissions_for_role('field_sales');
                if(!in_array('view_earnings_calculator',$perms,true)) $perms[]='view_earnings_calculator';
                $up->execute([rh24_json_encode(array_values(array_unique($perms))),(string)$u['id']]);
            }
            rh24_setting_set('schema_version','41');
            rh24_setting_set('db_schema_version','41');
            rh24_audit('schema_upgrade','system','v41',['features'=>['earnings_calculator','sales_goal_planner']],'system');
        }
    } catch(Throwable) {}
}


function rh24_ensure_v42_schema(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS sales_star_monthly (
          sales_rep_id VARCHAR(40) NOT NULL,
          period CHAR(7) NOT NULL,
          net_revenue DECIMAL(12,2) NOT NULL DEFAULT 0,
          commission DECIMAL(12,2) NOT NULL DEFAULT 0,
          stars TINYINT UNSIGNED NOT NULL DEFAULT 0,
          updated_at DATETIME NOT NULL,
          PRIMARY KEY (sales_rep_id,period),
          KEY idx_star_period (period,stars)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $version=(int)rh24_setting_get('schema_version','0');
        if($version < 42){
            if((string)rh24_setting_get('commission_statement_day','')==='') rh24_setting_set('commission_statement_day','27');
            if((string)rh24_setting_get('commission_payout_day','')==='') rh24_setting_set('commission_payout_day','1');
            if((string)rh24_setting_get('star_thresholds','')==='') rh24_setting_set('star_thresholds',[15000,20000,30000,40000,50000,75000]);
            $q=$db->query("SELECT id,permissions_json FROM users WHERE role='field_sales'");
            $up=$db->prepare('UPDATE users SET permissions_json=?,updated_at=NOW() WHERE id=?');
            foreach($q->fetchAll() as $u){
                $perms=rh24_json_decode((string)($u['permissions_json']??''),null);
                if(!is_array($perms)) $perms=rh24_default_permissions_for_role('field_sales');
                foreach(['view_leaderboard','view_star_stats','view_sales_calendar'] as $perm) if(!in_array($perm,$perms,true)) $perms[]=$perm;
                $up->execute([rh24_json_encode(array_values(array_unique($perms))),(string)$u['id']]);
            }
            rh24_setting_set('schema_version','42');
            rh24_setting_set('db_schema_version','42');
            rh24_audit('schema_upgrade','system','v42',['features'=>['leaderboard','monthly_stars','annual_star_stats','sales_calendar','confetti_rewards']],'system');
        }
    } catch(Throwable) {}
}

function rh24_ensure_v43_schema(PDO $db): void {
    try {
        foreach([
          "ALTER TABLE customers ADD COLUMN newsletter_status VARCHAR(20) NOT NULL DEFAULT 'none' AFTER sales_rep_id",
          "ALTER TABLE customers ADD COLUMN newsletter_consent_at DATETIME NULL AFTER newsletter_status",
          "ALTER TABLE customers ADD COLUMN newsletter_confirmed_at DATETIME NULL AFTER newsletter_consent_at",
          "ALTER TABLE customers ADD COLUMN newsletter_unsubscribed_at DATETIME NULL AFTER newsletter_confirmed_at",
          "ALTER TABLE customers ADD COLUMN newsletter_source VARCHAR(80) NULL AFTER newsletter_unsubscribed_at",
          "ALTER TABLE customers ADD COLUMN contact_verified_at DATETIME NULL AFTER newsletter_source",
          "ALTER TABLE customers ADD COLUMN contact_verified_by VARCHAR(180) NULL AFTER contact_verified_at",
          "ALTER TABLE customers ADD COLUMN contact_verification_note VARCHAR(255) NULL AFTER contact_verified_by"
        ] as $sql){ try{$db->exec($sql);}catch(Throwable){} }
        try{$db->exec("ALTER TABLE customers ADD KEY idx_customers_newsletter (newsletter_status,email)");}catch(Throwable){}
        $db->exec("CREATE TABLE IF NOT EXISTS newsletter_campaigns (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          subject VARCHAR(220) NOT NULL,
          body LONGTEXT NOT NULL,
          recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
          sent_count INT UNSIGNED NOT NULL DEFAULT 0,
          failed_count INT UNSIGNED NOT NULL DEFAULT 0,
          status VARCHAR(30) NOT NULL DEFAULT 'draft',
          created_by VARCHAR(80) NULL,
          created_at DATETIME NOT NULL,
          sent_at DATETIME NULL,
          KEY idx_newsletter_created (created_at),
          KEY idx_newsletter_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if((string)rh24_setting_get('newsletter_signing_secret','')==='') rh24_setting_set('newsletter_signing_secret',bin2hex(random_bytes(32)));
        if((string)rh24_setting_get('newsletter_sender_name','')==='') rh24_setting_set('newsletter_sender_name','Räucherhaken24');
        if((string)rh24_setting_get('newsletter_reply_to','')==='') rh24_setting_set('newsletter_reply_to',(string)rh24_setting_get('system_email','service@raeucherhaken24.com'));
        $version=(int)rh24_setting_get('schema_version','0');
        if($version < 43){
            rh24_setting_set('schema_version','43');rh24_setting_set('db_schema_version','43');
            rh24_audit('schema_upgrade','system','v43',['features'=>['progressive_commission','employee_performance_chart','customer_maps','contact_verification','double_opt_in_newsletter']],'system');
        }
    } catch(Throwable) {}
}

function rh24_ensure_v44_schema(PDO $db): void {
    try {
        foreach([
          "ALTER TABLE dealers ADD COLUMN customer_id VARCHAR(40) NULL AFTER id",
          "ALTER TABLE dealers ADD COLUMN street VARCHAR(190) NOT NULL DEFAULT '' AFTER phone",
          "ALTER TABLE dealers ADD COLUMN zip VARCHAR(20) NOT NULL DEFAULT '' AFTER street",
          "ALTER TABLE dealers ADD COLUMN city VARCHAR(120) NOT NULL DEFAULT '' AFTER zip",
          "ALTER TABLE dealers ADD COLUMN sales_rep_id VARCHAR(40) NULL AFTER city",
          "ALTER TABLE dealers ADD COLUMN visit_interval_days INT NOT NULL DEFAULT 14 AFTER discount",
          "ALTER TABLE dealers ADD COLUMN last_visit_at DATETIME NULL AFTER visit_interval_days",
          "ALTER TABLE dealers ADD COLUMN next_visit_at DATETIME NULL AFTER last_visit_at",
          "ALTER TABLE dealers ADD COLUMN last_visit_note TEXT NULL AFTER next_visit_at"
        ] as $sql){ try{$db->exec($sql);}catch(Throwable){} }
        foreach([
          "ALTER TABLE dealers ADD KEY idx_dealers_sales_rep (sales_rep_id)",
          "ALTER TABLE dealers ADD KEY idx_dealers_next_visit (next_visit_at)",
          "ALTER TABLE dealers ADD KEY idx_dealers_customer (customer_id)"
        ] as $sql){ try{$db->exec($sql);}catch(Throwable){} }
        $db->exec("CREATE TABLE IF NOT EXISTS dealer_visits (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          dealer_id VARCHAR(40) NOT NULL,
          sales_rep_id VARCHAR(40) NULL,
          planned_at DATETIME NULL,
          completed_at DATETIME NULL,
          status VARCHAR(30) NOT NULL DEFAULT 'planned',
          outcome VARCHAR(80) NOT NULL DEFAULT '',
          notes TEXT NULL,
          next_visit_at DATETIME NULL,
          created_by VARCHAR(80) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          KEY idx_dealer_visits_dealer (dealer_id,completed_at),
          KEY idx_dealer_visits_rep (sales_rep_id,planned_at),
          KEY idx_dealer_visits_status (status,planned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("UPDATE dealers SET visit_interval_days=14 WHERE visit_interval_days IS NULL OR visit_interval_days<>14");
        $db->exec("UPDATE dealers SET next_visit_at=DATE_ADD(COALESCE(last_visit_at,created_at), INTERVAL 14 DAY) WHERE next_visit_at IS NULL");
        // Bestehende Außendienstkonten erhalten ausschließlich die neuen Händler-Tourenrechte zusätzlich.
        try{
            $uq=$db->query("SELECT id,permissions_json FROM users WHERE role='field_sales'");$up=$db->prepare('UPDATE users SET permissions_json=?,updated_at=NOW() WHERE id=?');
            foreach($uq->fetchAll() as $u){$perms=rh24_json_decode($u['permissions_json']??'',null);if(!is_array($perms))$perms=rh24_default_permissions_for_role('field_sales');foreach(['view_dealer_visits','manage_dealer_visits'] as $perm)if(!in_array($perm,$perms,true))$perms[]=$perm;$up->execute([rh24_json_encode(array_values(array_unique($perms))),(string)$u['id']]);}
        }catch(Throwable){}
        $version=(int)rh24_setting_get('schema_version','0');
        if($version < 44){
            rh24_setting_set('schema_version','44');rh24_setting_set('db_schema_version','44');
            rh24_audit('schema_upgrade','system','v44',['features'=>['dealer_14_day_route_calendar','dealer_purchase_history','repeat_order_prompts']],'system');
        }
    } catch(Throwable) {}
}

function rh24_ensure_v45_schema(PDO $db): void {
    try {
        $version=(int)rh24_setting_get('schema_version','0');
        if($version < 45){
            $rows=[
          ['natur-13001','NG-13001','13001','"WAIDMANN" Kräuter Zwiebel Mischung','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13002','NG-13002','13002','Apfelgranulat 3–5 mm geschnitten, geschwefelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13003','NG-13003','13003','Bärlauch geschnitten, 2 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13004','NG-13004','13004','Basilikum gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13005','NG-13005','13005','BIO Chilipulver','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13006','NG-13006','13006','BIO Curry','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13007','NG-13007','13007','BIO Gelbsenfmehl','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13008','NG-13008','13008','BIO Ingwer, gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13009','NG-13009','13009','BIO Knoblauchpulver','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13010','NG-13010','13010','BIO Koriander, gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13011','NG-13011','13011','BIO Kümmel, gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13012','NG-13012','13012','BIO Majoran, gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13013','NG-13013','13013','BIO Muskatnuss, gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13014','NG-13014','13014','BIO Paprika, rot, gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13015','NG-13015','13015','BIO Pfeffer schwarz, gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13016','NG-13016','13016','BIO Pfeffer weiß, gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13017','NG-13017','13017','BIO Röstzwiebel','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13018','NG-13018','13018','BIO Zwiebelpulver','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13019','NG-13019','13019','Bohnenkraut gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13020','NG-13020','13020','Bratapfel – Fruchtmix','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13021','NG-13021','13021','Cardamom in der Schale gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13022','NG-13022','13022','Chilis gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13023','NG-13023','13023','Cranberry – Apfel – Mix','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13024','NG-13024','13024','CUMIN gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13025','NG-13025','13025','Curcuma fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13026','NG-13026','13026','Curry','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13027','NG-13027','13027','Dillspitzen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13028','NG-13028','13028','Edamer Käsegranulat 6 mm gefriergetrocknet','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13029','NG-13029','13029','Estragonblätter gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13030','NG-13030','13030','Feine Kräutermischung für Rohwurst / für Brühwurst','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13031','NG-13031','13031','Fermentierter Pfeffer schwarz ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13032','NG-13032','13032','Gelbsenfkörner','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13033','NG-13033','13033','Gelbsenfmehl teilentölt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13034','NG-13034','13034','GV – Chillies geschrotet ohne Kerne','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13035','NG-13035','13035','GV – Curry Madras','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13036','NG-13036','13036','GV – Knoblauchgranulat chinesisch','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13037','NG-13037','13037','GV – Paprika edelsüß „C“ spanisch','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13038','NG-13038','13038','GV – Pfeffer weiß, feinst vermahlen „K“','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13039','NG-13039','13039','Ingwer gemahlen keimreduziert','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13040','NG-13040','13040','Italienischer Hartkäse fein gerieben','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13041','NG-13041','13041','Karottengranulat 3 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13042','NG-13042','13042','Karottenwürfel getrocknet, 10 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13043','NG-13043','13043','Kerbel gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13044','NG-13044','13044','Knoblauch granuliert 81604201','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13045','NG-13045','13045','Knoblauchgranulat 2–3 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13046','NG-13046','13046','Knoblauchpaste','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13047','NG-13047','13047','Knoblauchpulver','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13048','NG-13048','13048','Knoblauchpulver californisch 812892','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13049','NG-13049','13049','Koriander ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13050','NG-13050','13050','Koriander gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13051','NG-13051','13051','Kräuter der Provence','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13052','NG-13052','13052','Kümmel ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13053','NG-13053','13053','Kümmel gebrochen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13054','NG-13054','13054','Kümmel gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13055','NG-13055','13055','Liebstocklaub gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13056','NG-13056','13056','Liebstockwurzel fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13057','NG-13057','13057','Lorbeerlaub (ganz) gereinigt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13058','NG-13058','13058','Majoran extra gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13059','NG-13059','13059','Majoran extra gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13060','NG-13060','13060','Majoran, ägyptisch gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13061','NG-13061','13061','Majoran, thüringisch gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13062','NG-13062','13062','Minze geschnitten, 2–3 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13063','NG-13063','13063','Muskatblüte (Macis) – hell gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13064','NG-13064','13064','Muskatblüte (Macis) gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13065','NG-13065','13065','Muskatnuss gemahlen 810847','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13066','NG-13066','13066','Nelken fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13067','NG-13067','13067','Nelken ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13068','NG-13068','13068','Oliven geschwärzt granuliert','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13069','NG-13069','13069','Oregano gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13070','NG-13070','13070','Paprika delikatess','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13071','NG-13071','13071','Paprika edelsüß','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13072','NG-13072','13072','Paprika flakes rot, 9 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13073','NG-13073','13073','Paprika scharf','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13074','NG-13074','13074','Paprikaflocken grün, 9 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13075','NG-13075','13075','Paprikaflocken grün, fein','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13076','NG-13076','13076','Paprikaflocken rot/grün granuliert, 1–3 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13077','NG-13077','13077','Peppadew Streifen ATG 1,5 kg','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13078','NG-13078','13078','Peppadew™ Whole sweet piquante peppers','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13079','NG-13079','13079','Petersilie gerebelt (2 mm)','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13080','NG-13080','13080','Pfeffer grün, (100 g) in Lake','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13081','NG-13081','13081','Pfeffer weiß gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13082','NG-13082','13082','Pfeffer, grün luftgetr., geschroten – ganzes Korn','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13083','NG-13083','13083','Pfeffer, grün luftgetrocknet','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13084','NG-13084','13084','Pfeffer, grün, in Lake','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13085','NG-13085','13085','Pfeffer, schwarz – weiß (70/30) mittelf., gebrochen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13086','NG-13086','13086','Pfeffer, schwarz fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13087','NG-13087','13087','Pfeffer, schwarz ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13088','NG-13088','13088','Pfeffer, schwarz ganz doppelt gereinigt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13089','NG-13089','13089','Pfeffer, schwarz grob gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13090','NG-13090','13090','Pfeffer, schwarz mittelfein geschroten','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13091','NG-13091','13091','Pfeffer, SW / WS (70/30), mittelfein, Spezial','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13092','NG-13092','13092','Pfeffer, weiß fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13093','NG-13093','13093','Pfeffer, weiß ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13094','NG-13094','13094','Pfeffer, weiß ganz, Handverlesen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13095','NG-13095','13095','Pfeffer, weiß grob gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13096','NG-13096','13096','Pfeffer, weiß mittelfein geschroten','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13097','NG-13097','13097','Pfeffer, weiß, geschroten','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13098','NG-13098','13098','Piment fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13099','NG-13099','13099','Piment ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13100','NG-13100','13100','Piment grob gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1]
            ];
            $ins=$db->prepare("INSERT INTO products(id,sku,article_no,name,category,product_type,base_price,unit,status,description,image_path,shop_visible,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE sku=VALUES(sku),article_no=VALUES(article_no),name=VALUES(name),category=VALUES(category),product_type=VALUES(product_type),status=VALUES(status),description=CASE WHEN products.description='' THEN VALUES(description) ELSE products.description END,shop_visible=VALUES(shop_visible),updated_at=NOW()");
            foreach($rows as $r){$ins->execute($r);}
            rh24_setting_set('schema_version','45');rh24_setting_set('db_schema_version','45');
            rh24_audit('schema_upgrade','system','v45',['features'=>['naturgewuerze_catalog','100_spice_articles','article_numbers_13001_13100']],'system');
        }
    } catch(Throwable) {}
}


function rh24_ensure_v46_schema(PDO $db): void {
    try {
        $version=(int)rh24_setting_get('schema_version','0');
        if($version < 46){
            $rows=[
          ['natur-13101','NG-13101','13101','Pistazien enthäutet','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13102','NG-13102','13102','Porreeflocken geschnitten, 6 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13103','NG-13103','13103','Rosenpaprika','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13104','NG-13104','13104','Rosmarin fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13105','NG-13105','13105','Rosmarin geschnitten','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13106','NG-13106','13106','Salatgemüse-Mix geschnitten','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13107','NG-13107','13107','Schnittlauch Klasse 1A, 4–5 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13108','NG-13108','13108','Schwarzer Trüffel (Tuber indicum)','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13109','NG-13109','13109','Sellerieblätter fein gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13110','NG-13110','13110','Sellerieknollenpulver','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13111','NG-13111','13111','Suppengrün 1a Suppengewürz grob','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13112','NG-13112','13112','Thymian fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13113','NG-13113','13113','Thymian gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Lieferanten-Referenz aus Vorlage: 81602801. Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13114','NG-13114','13114','Tomatenflocken geschnitten, 10 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13115','NG-13115','13115','Top-Pfeffer weiß','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13116','NG-13116','13116','Vanillinzucker','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13117','NG-13117','13117','Wacholderbeeren fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13118','NG-13118','13118','Wacholderbeeren ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13119','NG-13119','13119','Wacholderbeeren gequetscht, gebrochen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13120','NG-13120','13120','Wacholderbeeren Industrie gebrochen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13121','NG-13121','13121','Walnusskerne gebrochen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13122','NG-13122','13122','Zimt fein gemahlen','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13123','NG-13123','13123','Zimtstangen ganz','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13124','NG-13124','13124','Zwiebelgranulat 0,3–0,8 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13125','NG-13125','13125','Zwiebelgrieß 1–2 mm','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13126','NG-13126','13126','Zwiebellauch gerebelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13127','NG-13127','13127','Zwiebeln fein gehackt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13128','NG-13128','13128','Zwiebeln gekibbelt','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13129','NG-13129','13129','Zwiebeln gekibbelt (4–5 mm)','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13130','NG-13130','13130','Zwiebeln geröstet SG','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13131','NG-13131','13131','Zwiebeln getoastet','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13132','NG-13132','13132','Zwiebeln in Scheiben getrocknet','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13133','NG-13133','13133','Zwiebeln in Scheiben getrocknet I A','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13134','NG-13134','13134','Zwiebelpulver','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1],
          ['natur-13135','NG-13135','13135','Zwiebelpulver californisch, fein','Naturgewürze','product',0.00,'Gebinde folgt','active','Sortimentsartikel Naturgewürze. Produktbild, Gebindegröße und Verkaufspreis werden im Orgaboard ergänzt.','',1]
            ];
            $ins=$db->prepare("INSERT INTO products(id,sku,article_no,name,category,product_type,base_price,unit,status,description,image_path,shop_visible,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE sku=VALUES(sku),article_no=VALUES(article_no),name=VALUES(name),category=VALUES(category),product_type=VALUES(product_type),status=VALUES(status),description=CASE WHEN products.description='' THEN VALUES(description) ELSE products.description END,shop_visible=VALUES(shop_visible),updated_at=NOW()");
            foreach($rows as $r){$ins->execute($r);}
            rh24_setting_set('schema_version','46');rh24_setting_set('db_schema_version','46');
            rh24_audit('schema_upgrade','system','v46',['features'=>['naturgewuerze_extension','35_additional_spice_articles','article_numbers_13101_13135','total_nature_spices_135']],'system');
        }
    } catch(Throwable) {}
}


function rh24_ensure_v47_schema(PDO $db): void {
    try {
        $db->beginTransaction();
        $descriptions=[
          '13001'=>'Kräftige, grobe Kräuter-Zwiebel-Mischung mit deutlicher Röstzwiebelnote, abgerundet durch Schnittlauch und Petersilie. Besonders passend für herzhafte Fleisch-, Wurst- und Schmalzspezialitäten.',
          '13002'=>'Getrocknetes Apfelgranulat mit fruchtig-säuerlicher Note und sichtbarer Stückstruktur. Geeignet für Füllungen, Pasteten, Wurst- und Feinkostrezepturen sowie süß-herzhafte Anwendungen.',
          '13003'=>'Aromatisches Kraut mit frischer, knoblauchartiger Kräuternote. Vielseitig für Fleisch, Fisch, Kräuterbutter, Saucen, Füllungen und Marinaden.',
          '13004'=>'Aromatisches Kraut mit aromatisch-würziger, leicht süßlicher Basilikumnote. Vielseitig für Tomatengerichte, Fleisch, Fisch, Saucen und mediterrane Küche.',
          '13005'=>'Chili mit klarer, pikanter Schärfe und warmer Paprikanote. Für Marinaden, Fleisch, Wurst, Saucen, Grillgerichte und scharfe Gewürzmischungen.',
          '13006'=>'Aromatische Currymischung mit warm-würzigem, vielschichtigem Profil. Für Geflügel, Fleisch, Saucen, Marinaden, Reisgerichte und kreative Feinkost.',
          '13007'=>'Gelbsenf als Mehl mit typischer würziger, leicht scharfer Senfnote. Für Wurst, Marinaden, Saucen, Dressings und Einlegeprodukte.',
          '13008'=>'Ingwer mit warm-würziger, leicht zitroniger Schärfe. Geeignet für Fleisch, Geflügel, Marinaden, Currys, Saucen und asiatisch inspirierte Gerichte.',
          '13009'=>'Kräftige, typische Knoblauchnote, fein und gut verteilbar. Vielseitig für Fleisch, Wurst, Marinaden, Saucen, Grillgerichte und mediterrane Rezepturen.',
          '13010'=>'Koriander mit warm-würziger, leicht zitrusartiger Note. Geeignet für Wurst, Fleisch, Currys, Marinaden, Brot und internationale Gewürzmischungen.',
          '13011'=>'Kümmel mit kräftig-würziger, charakteristischer Aromatik. Klassisch für Wurst, Schweinefleisch, Kohlgerichte, Brot und herzhafte Saucen.',
          '13012'=>'Aromatisches Kraut mit warm-würziger, leicht süßlicher Majorannote. Vielseitig für Wurst, Kartoffelgerichte, Fleisch, Suppen und Eintöpfe.',
          '13013'=>'Gemahlene Muskatnuss mit warmer, intensiv-würziger Aromatik. Sparsam dosiert ideal für Wurst, Kartoffeln, Gemüse, helle Saucen und Füllungen.',
          '13014'=>'Paprikagewürz mit fruchtig-milder, warmer Aromatik und intensiver Farbe. Für Fleisch, Wurst, Marinaden, Saucen und Grillgerichte.',
          '13015'=>'Schwarzer Pfeffer, in der angegebenen Körnung, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13016'=>'Weißer Pfeffer, in der angegebenen Körnung, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13017'=>'Getrocknete Zwiebel mit herzhaft-röstiger Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13018'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13019'=>'Aromatisches Kraut mit kräftiger, pfeffrig-herber Kräuternote. Vielseitig für Bohnen, Wurst, Fleisch, Eintöpfe und herzhafte Saucen.',
          '13020'=>'Fruchtige Mischung mit warmer, winterlicher Aromatik für süß-herzhafte Anwendungen. Geeignet für Füllungen, Pasteten, Saucen und kreative saisonale Rezepturen.',
          '13021'=>'Gemahlener Cardamom mit warmer, leicht zitronig-eukalyptischer Aromatik. Für Wurst, Currys, Backwaren, orientalische Gerichte und feine Gewürzmischungen.',
          '13022'=>'Chili mit klarer, pikanter Schärfe und warmer Paprikanote. Für Marinaden, Fleisch, Wurst, Saucen, Grillgerichte und scharfe Gewürzmischungen.',
          '13023'=>'Fruchtige Kombination aus Cranberry und Apfel mit süß-säuerlichem Profil. Passt zu Wild, Geflügel, Pasteten, Saucen und modernen herzhaften Rezepturen.',
          '13024'=>'Gemahlener Cumin mit kräftiger, warm-erdiger und leicht herber Note. Ideal für Grillfleisch, Wurst, Hackgerichte, Currys und orientalische Gewürzmischungen.',
          '13025'=>'Fein gemahlene Curcuma mit erdig-würziger Note und intensiver gelber Farbe. Für Currys, Marinaden, Reisgerichte, Saucen und Gewürzmischungen.',
          '13026'=>'Aromatische Currymischung mit warm-würzigem, vielschichtigem Profil. Für Geflügel, Fleisch, Saucen, Marinaden, Reisgerichte und kreative Feinkost.',
          '13027'=>'Aromatisches Kraut mit frischer, fein-würziger Dillnote. Vielseitig für Fisch, Gurke, Saucen, Dressings und Marinaden.',
          '13028'=>'Gefriergetrocknetes Käsegranulat mit kräftiger Edamer-Note und sichtbarer Stückstruktur. Für Wurst, Füllungen, Snacks und herzhafte Spezialitäten mit Käsecharakter.',
          '13029'=>'Aromatisches Kraut mit fein-herber, leicht anisartiger Estragonnote. Vielseitig für Fisch, Geflügel, Saucen, Dressings und helle Marinaden.',
          '13030'=>'Feine Kräuter- und Gemüsemischung mit grüner, grober Struktur. Geeignet zur dekorativen Ummantelung von Rohwurst oder als aromatischer Zusatz für Brühwurst.',
          '13031'=>'Ganze schwarze Pfefferkörner mit intensivem, komplexem Aroma durch Fermentation. Für hochwertige Fleischgerichte, Saucen, Marinaden und Feinkost.',
          '13032'=>'Gelbsenf als Körner mit typischer würziger, leicht scharfer Senfnote. Für Wurst, Marinaden, Saucen, Dressings und Einlegeprodukte.',
          '13033'=>'Gelbsenf als Mehl mit typischer würziger, leicht scharfer Senfnote. Für Wurst, Marinaden, Saucen, Dressings und Einlegeprodukte.',
          '13034'=>'Chili mit klarer, pikanter Schärfe und warmer Paprikanote. Für Marinaden, Fleisch, Wurst, Saucen, Grillgerichte und scharfe Gewürzmischungen.',
          '13035'=>'Aromatische Currymischung mit warm-würzigem, vielschichtigem Profil. Für Geflügel, Fleisch, Saucen, Marinaden, Reisgerichte und kreative Feinkost.',
          '13036'=>'Kräftige, typische Knoblauchnote, granuliert mit sichtbarer Struktur. Vielseitig für Fleisch, Wurst, Marinaden, Saucen, Grillgerichte und mediterrane Rezepturen.',
          '13037'=>'Paprikagewürz mit fruchtig-milder, warmer Aromatik und intensiver Farbe. Für Fleisch, Wurst, Marinaden, Saucen und Grillgerichte.',
          '13038'=>'Weißer Pfeffer, fein gemahlen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13039'=>'Ingwer mit warm-würziger, leicht zitroniger Schärfe. Geeignet für Fleisch, Geflügel, Marinaden, Currys, Saucen und asiatisch inspirierte Gerichte.',
          '13040'=>'Fein geriebener Hartkäse mit würzig-herzhafter Note. Geeignet für Füllungen, Saucen, Fleischgerichte, Pasta- und Feinkostanwendungen.',
          '13041'=>'Getrocknete Karotte mit milder, leicht süßlicher Gemüsenote und sichtbarer Struktur. Für Suppen, Füllungen, Wurst, Saucen und dekorative Einlagen.',
          '13042'=>'Getrocknete Karotte mit milder, leicht süßlicher Gemüsenote und sichtbarer Struktur. Für Suppen, Füllungen, Wurst, Saucen und dekorative Einlagen.',
          '13043'=>'Aromatisches Kraut mit feiner, leicht süßlich-anisartiger Kräuternote. Vielseitig für Suppen, Saucen, Fisch, Geflügel und Kräutermischungen.',
          '13044'=>'Kräftige, typische Knoblauchnote, granuliert mit sichtbarer Struktur. Vielseitig für Fleisch, Wurst, Marinaden, Saucen, Grillgerichte und mediterrane Rezepturen.',
          '13045'=>'Kräftige, typische Knoblauchnote, granuliert mit sichtbarer Struktur. Vielseitig für Fleisch, Wurst, Marinaden, Saucen, Grillgerichte und mediterrane Rezepturen.',
          '13046'=>'Kräftige, typische Knoblauchnote, als gebrauchsfertige Paste. Vielseitig für Fleisch, Wurst, Marinaden, Saucen, Grillgerichte und mediterrane Rezepturen.',
          '13047'=>'Kräftige, typische Knoblauchnote, fein und gut verteilbar. Vielseitig für Fleisch, Wurst, Marinaden, Saucen, Grillgerichte und mediterrane Rezepturen.',
          '13048'=>'Kräftige, typische Knoblauchnote, fein und gut verteilbar. Vielseitig für Fleisch, Wurst, Marinaden, Saucen, Grillgerichte und mediterrane Rezepturen.',
          '13049'=>'Koriander mit warm-würziger, leicht zitrusartiger Note. Geeignet für Wurst, Fleisch, Currys, Marinaden, Brot und internationale Gewürzmischungen.',
          '13050'=>'Koriander mit warm-würziger, leicht zitrusartiger Note. Geeignet für Wurst, Fleisch, Currys, Marinaden, Brot und internationale Gewürzmischungen.',
          '13051'=>'Mediterrane Kräutermischung mit Rosmarin und Basilikum im Vordergrund, harmonisch ergänzt durch Majoran und Thymian. Ideal für Grillgerichte, Marinaden, Gemüse und mediterrane Fleischgerichte.',
          '13052'=>'Kümmel mit kräftig-würziger, charakteristischer Aromatik. Klassisch für Wurst, Schweinefleisch, Kohlgerichte, Brot und herzhafte Saucen.',
          '13053'=>'Kümmel mit kräftig-würziger, charakteristischer Aromatik. Klassisch für Wurst, Schweinefleisch, Kohlgerichte, Brot und herzhafte Saucen.',
          '13054'=>'Kümmel mit kräftig-würziger, charakteristischer Aromatik. Klassisch für Wurst, Schweinefleisch, Kohlgerichte, Brot und herzhafte Saucen.',
          '13055'=>'Aromatisches Kraut mit kräftiger, sellerieähnlicher Würznote. Vielseitig für Suppen, Brühen, Eintöpfe, Wurst und herzhafte Saucen.',
          '13056'=>'Aromatisches Kraut mit kräftiger, sellerieähnlicher Würznote. Vielseitig für Suppen, Brühen, Eintöpfe, Wurst und herzhafte Saucen.',
          '13057'=>'Aromatisches Kraut mit kräftiger, aromatisch-herber Lorbeernote. Vielseitig für Schmorgerichte, Fonds, Suppen, Beizen und Marinaden.',
          '13058'=>'Aromatisches Kraut mit warm-würziger, leicht süßlicher Majorannote. Vielseitig für Wurst, Kartoffelgerichte, Fleisch, Suppen und Eintöpfe.',
          '13059'=>'Aromatisches Kraut mit warm-würziger, leicht süßlicher Majorannote. Vielseitig für Wurst, Kartoffelgerichte, Fleisch, Suppen und Eintöpfe.',
          '13060'=>'Aromatisches Kraut mit warm-würziger, leicht süßlicher Majorannote. Vielseitig für Wurst, Kartoffelgerichte, Fleisch, Suppen und Eintöpfe.',
          '13061'=>'Aromatisches Kraut mit warm-würziger, leicht süßlicher Majorannote. Vielseitig für Wurst, Kartoffelgerichte, Fleisch, Suppen und Eintöpfe.',
          '13062'=>'Aromatisches Kraut mit frischer, kühl-aromatischer Minznote. Vielseitig für Lamm, Saucen, Dressings, orientalische Gerichte und kreative Mischungen.',
          '13063'=>'Gemahlene Muskatblüte (Macis) mit feiner, warmer und leicht süßlicher Würze. Für Brühwurst, helle Saucen, Kartoffelgerichte, Gemüse und feine Fleischwaren.',
          '13064'=>'Gemahlene Muskatblüte (Macis) mit feiner, warmer und leicht süßlicher Würze. Für Brühwurst, helle Saucen, Kartoffelgerichte, Gemüse und feine Fleischwaren.',
          '13065'=>'Gemahlene Muskatnuss mit warmer, intensiv-würziger Aromatik. Sparsam dosiert ideal für Wurst, Kartoffeln, Gemüse, helle Saucen und Füllungen.',
          '13066'=>'Nelken mit intensiv warmer, süßlich-würziger Aromatik. Sparsam dosiert für Wild, Schmorgerichte, Beizen, Saucen, Wurst und winterliche Rezepturen.',
          '13067'=>'Nelken mit intensiv warmer, süßlich-würziger Aromatik. Sparsam dosiert für Wild, Schmorgerichte, Beizen, Saucen, Wurst und winterliche Rezepturen.',
          '13068'=>'Granulierte, geschwärzte Oliven mit mediterran-herzhafter Note. Gut geeignet für Wurst, Füllungen, Brot, Salate und mediterrane Fleischspezialitäten.',
          '13069'=>'Aromatisches Kraut mit kräftiger, mediterran-herber Oreganonote. Vielseitig für Tomatengerichte, Pizza, Fleisch, Grillgerichte, Saucen und Marinaden.',
          '13070'=>'Paprikagewürz mit fruchtig-milder, warmer Aromatik und intensiver Farbe. Für Fleisch, Wurst, Marinaden, Saucen und Grillgerichte.',
          '13071'=>'Paprikagewürz mit fruchtig-milder, warmer Aromatik und intensiver Farbe. Für Fleisch, Wurst, Marinaden, Saucen und Grillgerichte.',
          '13072'=>'Paprikagewürz mit fruchtig-milder, warmer Aromatik und intensiver Farbe. Für Fleisch, Wurst, Marinaden, Saucen und Grillgerichte.',
          '13073'=>'Paprikagewürz mit fruchtig-würziger Aromatik und deutlicher Schärfe und intensiver Farbe. Für Fleisch, Wurst, Marinaden, Saucen und Grillgerichte.',
          '13074'=>'Paprikaflocken mit fruchtig und sichtbar strukturierter Paprikanote. Für Marinaden, Wurst, Grillgerichte, Füllungen und dekorative Gewürzmischungen.',
          '13075'=>'Paprikaflocken mit fruchtig und sichtbar strukturierter Paprikanote. Für Marinaden, Wurst, Grillgerichte, Füllungen und dekorative Gewürzmischungen.',
          '13076'=>'Paprikaflocken mit fruchtig und sichtbar strukturierter Paprikanote. Für Marinaden, Wurst, Grillgerichte, Füllungen und dekorative Gewürzmischungen.',
          '13077'=>'Süß-pikante Paprikastreifen mit fruchtigem Geschmack und angenehmer Schärfe. Ideal für Salate, Fleischgerichte, Füllungen, Antipasti und Feinkost.',
          '13078'=>'Ganze süß-pikante Peppadew-Paprika mit fruchtiger Schärfe. Für Antipasti, Füllungen, Grillgerichte, Salate und dekorative Feinkostanwendungen.',
          '13079'=>'Aromatisches Kraut mit frischer, grüner Petersiliennote. Vielseitig für Fleisch, Fisch, Suppen, Saucen, Füllungen und Kräutermischungen.',
          '13080'=>'Grüner Pfeffer, in der angegebenen Körnung, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13081'=>'Weißer Pfeffer, in der angegebenen Körnung, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13082'=>'Grüner Pfeffer, geschrotet bzw. gebrochen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13083'=>'Grüner Pfeffer, in der angegebenen Körnung, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13084'=>'Grüner Pfeffer, in der angegebenen Körnung, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13085'=>'Schwarzer Pfeffer, geschrotet bzw. gebrochen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13086'=>'Schwarzer Pfeffer, fein gemahlen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13087'=>'Schwarzer Pfeffer, ganz, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13088'=>'Schwarzer Pfeffer, ganz, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13089'=>'Schwarzer Pfeffer, grob gemahlen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13090'=>'Schwarzer Pfeffer, geschrotet bzw. gebrochen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13091'=>'Schwarzer Pfeffer, in der angegebenen Körnung, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13092'=>'Weißer Pfeffer, fein gemahlen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13093'=>'Weißer Pfeffer, ganz, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13094'=>'Weißer Pfeffer, ganz, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13095'=>'Weißer Pfeffer, grob gemahlen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13096'=>'Weißer Pfeffer, geschrotet bzw. gebrochen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13097'=>'Weißer Pfeffer, geschrotet bzw. gebrochen, mit klarer aromatischer Schärfe. Vielseitig für Fleisch, Fisch, Wurst, Saucen, Marinaden und Gewürzmischungen.',
          '13098'=>'Piment mit warm-würziger Aromatik, die an Pfeffer, Nelke und Muskat erinnert. Für Wurst, Wild, Schmorgerichte, Marinaden, Saucen und Beizen.',
          '13099'=>'Piment mit warm-würziger Aromatik, die an Pfeffer, Nelke und Muskat erinnert. Für Wurst, Wild, Schmorgerichte, Marinaden, Saucen und Beizen.',
          '13100'=>'Piment mit warm-würziger Aromatik, die an Pfeffer, Nelke und Muskat erinnert. Für Wurst, Wild, Schmorgerichte, Marinaden, Saucen und Beizen.',
          '13101'=>'Enthäutete Pistazien für dekorative und geschmackliche Akzente. Ideal für Wurst- und Pastetenspezialitäten, Käse, Backwaren sowie hochwertige Füllungen und Toppings.',
          '13102'=>'Getrockneter Lauch mit milder, würziger Zwiebelnote. Für Suppen, Saucen, Wurst, Füllungen und herzhafte Gewürzmischungen.',
          '13103'=>'Rosenpaprika mit fruchtig-würziger Paprikanote, kräftiger roter Farbe und deutlicher Schärfe. Für Fleisch, Wurst, Marinaden, Saucen und Grillgerichte.',
          '13104'=>'Aromatisches Kraut mit harzig-würziger, mediterraner Rosmarinnote. Vielseitig für Lamm, Schwein, Geflügel, Kartoffeln, Grillgerichte und Marinaden.',
          '13105'=>'Aromatisches Kraut mit harzig-würziger, mediterraner Rosmarinnote. Vielseitig für Lamm, Schwein, Geflügel, Kartoffeln, Grillgerichte und Marinaden.',
          '13106'=>'Bunte geschnittene Gemüsemischung für sichtbare Einlagen und eine appetitliche Optik. Geeignet für Salate, Füllungen, Feinkost und herzhafte Mischungen.',
          '13107'=>'Aromatisches Kraut mit milder, frischer Zwiebel-Kräuternote. Vielseitig für Dressings, Saucen, Frischkäse, Kartoffeln und dekorative Mischungen.',
          '13108'=>'Schwarzer Trüffel mit charakteristisch erdig-würziger Aromatik für hochwertige Spezialitäten. Sparsam dosiert geeignet für Pasteten, Saucen, Fleischgerichte und Feinkost.',
          '13109'=>'Fein gerebelte Sellerieblätter mit kräftig-würziger, typischer Sellerienote. Für Suppen, Brühen, Wurst, Saucen und herzhafte Mischungen.',
          '13110'=>'Feines Sellerieknollenpulver mit kräftig-würziger Gemüsenote. Geeignet für Suppen, Brühen, Wurst, Saucen und herzhafte Gewürzmischungen.',
          '13111'=>'Grobe Gemüsemischung für kräftige, klassische Suppen- und Brühenaromen. Auch für Schmorgerichte, Saucen und herzhafte Füllungen geeignet.',
          '13112'=>'Aromatisches Kraut mit kräftiger, warm-herber Thymiannote. Vielseitig für Fleisch, Wild, Saucen, Marinaden, Gemüse und mediterrane Gerichte.',
          '13113'=>'Aromatisches Kraut mit kräftiger, warm-herber Thymiannote. Vielseitig für Fleisch, Wild, Saucen, Marinaden, Gemüse und mediterrane Gerichte.',
          '13114'=>'Getrocknete Tomatenflocken mit fruchtig-herzhafter Tomatennote und sichtbarer Struktur. Geeignet für Marinaden, Füllungen, Saucen, Wurst und mediterrane Mischungen.',
          '13115'=>'Ausgewählter weißer Pfeffer mit klarer, warmer Schärfe. Für helle Wurstwaren, Saucen, Fisch, Geflügel und feine Fleischgerichte.',
          '13116'=>'Fein dosierbare süße Vanillenote für Desserts, Backwaren, Fruchtzubereitungen und süß-herzhafte Spezialitäten.',
          '13117'=>'Wacholderbeeren mit harzig-würziger, leicht bittersüßer Waldnote. Klassisch für Wild, Schinken, Beizen, Sauerkraut, Saucen und Räucherspezialitäten.',
          '13118'=>'Wacholderbeeren mit harzig-würziger, leicht bittersüßer Waldnote. Klassisch für Wild, Schinken, Beizen, Sauerkraut, Saucen und Räucherspezialitäten.',
          '13119'=>'Wacholderbeeren mit harzig-würziger, leicht bittersüßer Waldnote. Klassisch für Wild, Schinken, Beizen, Sauerkraut, Saucen und Räucherspezialitäten.',
          '13120'=>'Wacholderbeeren mit harzig-würziger, leicht bittersüßer Waldnote. Klassisch für Wild, Schinken, Beizen, Sauerkraut, Saucen und Räucherspezialitäten.',
          '13121'=>'Gebrochene Walnusskerne mit kräftig-nussigem Aroma. Ideal für Pasteten, Käse, Füllungen, Backwaren und hochwertige Feinkost.',
          '13122'=>'Zimt mit warmer, süßlich-würziger Aromatik. Für Desserts und Backwaren ebenso geeignet wie für Wild, Schmorgerichte, Chutneys und orientalische Rezepturen.',
          '13123'=>'Zimt mit warmer, süßlich-würziger Aromatik. Für Desserts und Backwaren ebenso geeignet wie für Wild, Schmorgerichte, Chutneys und orientalische Rezepturen.',
          '13124'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13125'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13126'=>'Getrockneter Lauch mit milder, würziger Zwiebelnote. Für Suppen, Saucen, Wurst, Füllungen und herzhafte Gewürzmischungen.',
          '13127'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13128'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13129'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13130'=>'Getrocknete Zwiebel mit herzhaft-röstiger Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13131'=>'Getrocknete Zwiebel mit herzhaft-röstiger Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13132'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13133'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13134'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
          '13135'=>'Getrocknete Zwiebel mit würzig-süßlicher Aromatik und guter Dosierbarkeit. Ideal für Wurst, Fleisch, Saucen, Suppen, Marinaden und herzhafte Mischungen.',
        ];
        $q=$db->prepare("UPDATE products SET description=?,updated_at=NOW() WHERE article_no=? AND category='Naturgewürze' AND (description='' OR description LIKE 'Sortimentsartikel Naturgewürze.%')");
        foreach($descriptions as $articleNo=>$description){$q->execute([$description,$articleNo]);}
        $db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('schema_version','47',NOW()) ON DUPLICATE KEY UPDATE setting_value='47',updated_at=NOW()")->execute();
        $db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('db_schema_version','47',NOW()) ON DUPLICATE KEY UPDATE setting_value='47',updated_at=NOW()")->execute();
        $db->commit();
        try{rh24_audit('schema_upgrade','system','v47',['features'=>['nature_spices_shop_category','135_editorial_descriptions','avo_reference_research','prominent_shop_navigation']],'system');}catch(Throwable){}
    } catch(Throwable $e) { if($db->inTransaction())$db->rollBack(); }
}

function rh24_ensure_v48_schema(PDO $db): void {
    try{
        $version=(int)rh24_setting_get('schema_version','0');
        if($version<48){rh24_setting_set('schema_version','48');rh24_setting_set('db_schema_version','48');try{rh24_audit('schema_upgrade','system','v48',['features'=>['nature_spices_sidebar_everywhere','inline_product_price','inline_shop_visibility']],'system');}catch(Throwable){}}
        $version=(int)rh24_setting_get('schema_version','0');
        if($version<49){
            try { $db->exec("ALTER TABLE products ADD COLUMN product_weight_g INT UNSIGNED NOT NULL DEFAULT 0 AFTER unit"); } catch(Throwable) {}
            try { $db->exec("ALTER TABLE products ADD COLUMN shipping_weight_g INT UNSIGNED NOT NULL DEFAULT 0 AFTER product_weight_g"); } catch(Throwable) {}
            // Bei eindeutigen Gebindeangaben (z. B. 500 g) kann das reine Produktgewicht sicher vorbelegt werden.
            try { $db->exec("UPDATE products SET product_weight_g=CAST(SUBSTRING_INDEX(unit,' ',1) AS UNSIGNED) WHERE product_weight_g=0 AND unit REGEXP '^[0-9]+[[:space:]]*g$'"); } catch(Throwable) {}
            rh24_setting_set('schema_version','49');rh24_setting_set('db_schema_version','49');
            try{rh24_audit('schema_upgrade','system','v49',['features'=>['product_weight','shipping_weight','shop_weight_display']],'system');}catch(Throwable){}
        }
    }catch(Throwable){}
}


function rh24_ensure_v50_schema(PDO $db): void {
    try{
        $version=(int)rh24_setting_get('schema_version','0');
        if($version>=50) return;
        try { $db->exec("ALTER TABLE products ADD COLUMN barcode VARCHAR(80) NOT NULL DEFAULT '' AFTER article_no"); } catch(Throwable) {}
        try { $db->exec("ALTER TABLE products ADD KEY idx_products_barcode (barcode)"); } catch(Throwable) {}
        $db->exec("CREATE TABLE IF NOT EXISTS shipping_integrations (
          carrier VARCHAR(20) NOT NULL PRIMARY KEY,
          environment VARCHAR(20) NOT NULL DEFAULT 'sandbox',
          credentials_enc LONGTEXT NULL,
          status VARCHAR(30) NOT NULL DEFAULT 'not_configured',
          last_test_at DATETIME NULL,
          last_message VARCHAR(500) NOT NULL DEFAULT '',
          updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS shipping_labels (
          order_no VARCHAR(80) NOT NULL PRIMARY KEY,
          carrier VARCHAR(20) NOT NULL DEFAULT '',
          tracking_no VARCHAR(120) NOT NULL DEFAULT '',
          label_mime VARCHAR(80) NOT NULL DEFAULT '',
          label_data LONGTEXT NULL,
          status VARCHAR(30) NOT NULL DEFAULT 'draft',
          payload_json LONGTEXT NULL,
          created_by VARCHAR(80) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          KEY idx_shipping_labels_carrier (carrier),
          KEY idx_shipping_labels_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        rh24_setting_set('schema_version','50');rh24_setting_set('db_schema_version','50');
        try{rh24_audit('schema_upgrade','system','v50',['features'=>['barcode_search','shop_search','carrier_badges','dhl_dpd_integration_settings','connection_test','shipping_label_preview']],'system');}catch(Throwable){}
    }catch(Throwable){}
}

function rh24_ensure_v51_schema(PDO $db): void {
    try{
        $version=(int)rh24_setting_get('schema_version','0');
        if($version>=51) return;
        foreach([
          "ALTER TABLE products ADD COLUMN is_popular TINYINT(1) NOT NULL DEFAULT 0 AFTER shop_visible",
          "ALTER TABLE products ADD COLUMN is_offer TINYINT(1) NOT NULL DEFAULT 0 AFTER is_popular",
          "ALTER TABLE products ADD COLUMN old_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER base_price",
          "ALTER TABLE products ADD COLUMN sale_price DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER old_price",
          "ALTER TABLE products ADD COLUMN sale_start_at DATETIME NULL AFTER sale_price",
          "ALTER TABLE products ADD COLUMN sale_end_at DATETIME NULL AFTER sale_start_at",
          "ALTER TABLE products ADD COLUMN price_basis VARCHAR(20) NOT NULL DEFAULT 'auto' AFTER shipping_weight_g",
          "ALTER TABLE products ADD COLUMN pack_quantity INT UNSIGNED NOT NULL DEFAULT 1 AFTER price_basis"
        ] as $sql){try{$db->exec($sql);}catch(Throwable){}}
        try{$db->exec("UPDATE products SET pack_quantity=10 WHERE pack_quantity=1 AND (unit LIKE '10er%' OR unit LIKE '10-%' OR unit LIKE '10 %')");}catch(Throwable){}
        if((string)rh24_setting_get('active_theme','')==='') rh24_setting_set('active_theme','standard');
        rh24_setting_set('schema_version','51');rh24_setting_set('db_schema_version','51');
        try{rh24_audit('schema_upgrade','system','v51',['features'=>['seasonal_shop_themes','popular_badge','offer_badge','scheduled_sale_prices','strike_prices','unit_price_weight_piece']],'system');}catch(Throwable){}
    }catch(Throwable){}
}
function rh24_sale_is_active(array $p, ?int $now=null): bool {
    if(empty($p['is_offer'])) return false;
    $sale=(float)($p['sale_price']??0); if($sale<=0) return false;
    $now=$now??time();
    $start=trim((string)($p['sale_start_at']??''));$end=trim((string)($p['sale_end_at']??''));
    if($start!=='' && ($ts=strtotime($start))!==false && $now<$ts) return false;
    if($end!=='' && ($ts=strtotime($end))!==false && $now>$ts) return false;
    return true;
}
function rh24_effective_base(array $p): float {
    return rh24_sale_is_active($p)?(float)($p['sale_price']??0):(float)($p['base']??$p['base_price']??0);
}
function rh24_unit_price_meta(array $p, ?float $effective=null): array {
    $effective=$effective??rh24_effective_base($p);$mode=(string)($p['price_basis']??'auto');$w=(int)($p['product_weight_g']??0);$qty=max(1,(int)($p['pack_quantity']??1));$unit=(string)($p['unit']??'');
    if($effective<=0)return ['mode'=>'none','label'=>''];
    $weightMode=$mode==='weight'||($mode==='auto'&&$w>0&&(preg_match('/g|kg/i',$unit)||str_contains(mb_strtolower((string)($p['category']??'')),'gewürz')||str_contains(mb_strtolower((string)($p['category']??'')),'lauge')||str_contains(mb_strtolower((string)($p['category']??'')),'mehl')));
    if($weightMode&&$w>0){$per100=round($effective/$w*100,2);return ['mode'=>'weight','package_weight_g'=>$w,'per_100g'=>$per100,'label'=>number_format($w,0,',','.').' g '.number_format($effective,2,',','.').' € · 100 g '.number_format($per100,2,',','.').' €'];}
    if($mode==='piece'||($mode==='auto'&&$qty>=1)){ $per=round($effective/$qty,2);return ['mode'=>'piece','pack_quantity'=>$qty,'per_piece'=>$per,'label'=>$qty.' Stück '.number_format($effective,2,',','.').' €'.($qty>1?' · 1 Stück '.number_format($per,2,',','.').' €':'')];}
    return ['mode'=>'none','label'=>''];
}

function rh24_shipping_key(): string {
    $cfg=rh24_db_config();
    return hash('sha256','RH24-V50-SHIPPING|'.($cfg['host']??'').'|'.($cfg['database']??'').'|'.($cfg['user']??'').'|'.($cfg['password']??''),true);
}
function rh24_encrypt_secret(array $value): string {
    if(!$value) return '';
    if(!function_exists('openssl_encrypt')) return 'plain:'.base64_encode(rh24_json_encode($value));
    $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt(rh24_json_encode($value),'aes-256-gcm',rh24_shipping_key(),OPENSSL_RAW_DATA,$iv,$tag);
    if($cipher===false) throw new RuntimeException('Versand-Zugangsdaten konnten nicht verschlüsselt werden.');
    return 'gcm:'.base64_encode($iv.$tag.$cipher);
}
function rh24_decrypt_secret(?string $raw): array {
    $raw=(string)$raw;if($raw==='')return [];
    if(str_starts_with($raw,'plain:')){ $d=base64_decode(substr($raw,6),true);return $d===false?[]:rh24_json_decode($d,[]); }
    if(!str_starts_with($raw,'gcm:')||!function_exists('openssl_decrypt'))return [];
    $bin=base64_decode(substr($raw,4),true);if($bin===false||strlen($bin)<29)return [];$iv=substr($bin,0,12);$tag=substr($bin,12,16);$cipher=substr($bin,28);
    $plain=openssl_decrypt($cipher,'aes-256-gcm',rh24_shipping_key(),OPENSSL_RAW_DATA,$iv,$tag);return $plain===false?[]:rh24_json_decode($plain,[]);
}
function rh24_shipping_integrations(bool $withSecrets=false): array {
    $rows=[];foreach(['DHL','DPD'] as $carrier)$rows[$carrier]=['carrier'=>$carrier,'environment'=>'sandbox','configured'=>false,'status'=>'not_configured','last_test_at'=>null,'last_message'=>'','credentials'=>[]];
    try{
      foreach(rh24_db()->query('SELECT * FROM shipping_integrations')->fetchAll() as $r){$c=strtoupper((string)$r['carrier']);if(!isset($rows[$c]))continue;$cred=rh24_decrypt_secret($r['credentials_enc']??'');$rows[$c]=['carrier'=>$c,'environment'=>(string)$r['environment'],'configured'=>!empty($cred),'status'=>(string)$r['status'],'last_test_at'=>rh24_iso($r['last_test_at']??null),'last_message'=>(string)($r['last_message']??''),'credentials'=>$withSecrets?$cred:array_map(fn($v)=>$v!==''&&$v!==null,true,$cred)];}
    }catch(Throwable){}
    return array_values($rows);
}
function rh24_shipping_integration_secret(string $carrier): array {
    $q=rh24_db()->prepare('SELECT credentials_enc FROM shipping_integrations WHERE carrier=?');$q->execute([strtoupper($carrier)]);return rh24_decrypt_secret((string)($q->fetchColumn()?:''));
}
function rh24_http_request(string $url,array $opts=[]): array {
    $method=$opts['method']??'GET';$headers=$opts['headers']??[];$body=$opts['body']??null;$timeout=(int)($opts['timeout']??12);
    if(function_exists('curl_init')){
      $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>$timeout,CURLOPT_TIMEOUT=>$timeout,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers]);if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);$resp=curl_exec($ch);$err=curl_error($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);return ['ok'=>$resp!==false,'status'=>$code,'body'=>$resp===false?'':(string)$resp,'error'=>$err];
    }
    $ctx=stream_context_create(['http'=>['method'=>$method,'header'=>implode("
",$headers),'content'=>$body??'','timeout'=>$timeout,'ignore_errors'=>true]]);$resp=@file_get_contents($url,false,$ctx);$status=0;foreach($http_response_header??[] as $h)if(preg_match('/^HTTP\/\S+\s+(\d+)/',$h,$m))$status=(int)$m[1];return ['ok'=>$resp!==false,'status'=>$status,'body'=>$resp===false?'':(string)$resp,'error'=>$resp===false?'HTTP-Verbindung fehlgeschlagen':''];
}
function rh24_shipping_test(string $carrier): array {
    $carrier=strtoupper($carrier);$q=rh24_db()->prepare('SELECT environment,credentials_enc FROM shipping_integrations WHERE carrier=?');$q->execute([$carrier]);$row=$q->fetch();if(!$row)return ['ok'=>false,'message'=>'Noch keine Zugangsdaten gespeichert.'];$env=(string)$row['environment'];$cred=rh24_decrypt_secret((string)$row['credentials_enc']);
    if($carrier==='DHL'){
      foreach(['client_id','client_secret','username','password'] as $k)if(trim((string)($cred[$k]??''))==='')return ['ok'=>false,'message'=>'DHL: API Key, API Secret, GKP-Benutzer und GKP-Passwort sind erforderlich.'];
      $base=$env==='production'?'https://api-eu.dhl.com/parcel/de/account/auth/ropc/v1/token':'https://api-sandbox.dhl.com/parcel/de/account/auth/ropc/v1/token';
      $body=http_build_query(['grant_type'=>'password','username'=>$cred['username'],'password'=>$cred['password'],'client_id'=>$cred['client_id'],'client_secret'=>$cred['client_secret']]);
      $r=rh24_http_request($base,['method'=>'POST','headers'=>['Content-Type: application/x-www-form-urlencoded','Accept: application/json'],'body'=>$body]);$json=rh24_json_decode($r['body'],[]);
      if($r['status']>=200&&$r['status']<300&&!empty($json['access_token']))return ['ok'=>true,'message'=>'DHL-Verbindung steht. OAuth-Zugang wurde erfolgreich bestätigt.'];
      return ['ok'=>false,'message'=>'DHL-Verbindung nicht bestätigt (HTTP '.($r['status']?:'0').'). '.substr((string)($json['error_description']??$json['error']??$r['error']??'Zugangsdaten prüfen.'),0,220)];
    }
    if($carrier==='DPD'){
      foreach(['partner_name','partner_token','cloud_user_id','user_token'] as $k)if(trim((string)($cred[$k]??''))==='')return ['ok'=>false,'message'=>'DPD: Partner-Name, Partner-Token, Cloud User ID und User-Token sind erforderlich.'];
      $url=$env==='production'?'https://cloud.dpd.com/services/v1/DPDCloudService.asmx':'https://cloud-stage.dpd.com/services/v1/DPDCloudService.asmx';
      $xml='<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><getZipCodeRules xmlns="https://cloud.dpd.com/"><getZipCodeRulesRequest><Version>100</Version><Language>de_DE</Language><PartnerCredentials><Name>'.htmlspecialchars((string)$cred['partner_name'],ENT_XML1).'</Name><Token>'.htmlspecialchars((string)$cred['partner_token'],ENT_XML1).'</Token></PartnerCredentials><UserCredentials><cloudUserID>'.htmlspecialchars((string)$cred['cloud_user_id'],ENT_XML1).'</cloudUserID><Token>'.htmlspecialchars((string)$cred['user_token'],ENT_XML1).'</Token></UserCredentials></getZipCodeRulesRequest></getZipCodeRules></soap:Body></soap:Envelope>';
      $r=rh24_http_request($url,['method'=>'POST','headers'=>['Content-Type: text/xml; charset=utf-8','SOAPAction: "https://cloud.dpd.com/getZipCodeRules"'],'body'=>$xml]);
      $bad=preg_match('/<ErrorMsg(?:Short|Long)>\s*([^<]+)/i',$r['body'],$m);if($r['status']>=200&&$r['status']<300&&!$bad&&str_contains($r['body'],'getZipCodeRulesResponse'))return ['ok'=>true,'message'=>'DPD-Verbindung steht. Webservice-Zugang wurde erfolgreich bestätigt.'];
      return ['ok'=>false,'message'=>'DPD-Verbindung nicht bestätigt (HTTP '.($r['status']?:'0').'). '.substr($bad?trim($m[1]):($r['error']?:'Zugangsdaten bzw. DPD-Freischaltung prüfen.'),0,220)];
    }
    return ['ok'=>false,'message'=>'Unbekannter Versanddienstleister.'];
}
function rh24_shipping_labels(): array {
    try{$rows=rh24_db()->query('SELECT order_no,carrier,tracking_no,label_mime,status,created_at,updated_at,CASE WHEN label_data IS NULL OR label_data="" THEN 0 ELSE 1 END has_label FROM shipping_labels ORDER BY updated_at DESC')->fetchAll();foreach($rows as &$r){$r['has_label']=(bool)$r['has_label'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;}catch(Throwable){return [];}
}

function rh24_b64url_encode(string $raw): string { return rtrim(strtr(base64_encode($raw),'+/','-_'),'='); }
function rh24_b64url_decode(string $raw): string|false { $pad=strlen($raw)%4;if($pad)$raw.=str_repeat('=',4-$pad);return base64_decode(strtr($raw,'-_','+/'),true); }
function rh24_newsletter_token(string $customerId,string $purpose='confirm',int $ttl=172800): string {
    $payload=rh24_json_encode(['id'=>$customerId,'purpose'=>$purpose,'exp'=>time()+$ttl]);
    $body=rh24_b64url_encode($payload);$secret=(string)rh24_setting_get('newsletter_signing_secret','');
    if($secret===''){ $secret=bin2hex(random_bytes(32));rh24_setting_set('newsletter_signing_secret',$secret); }
    return $body.'.'.rh24_b64url_encode(hash_hmac('sha256',$body,$secret,true));
}
function rh24_newsletter_verify_token(string $token,string $purpose): ?string {
    $parts=explode('.',$token);if(count($parts)!==2)return null;[$body,$sig]=$parts;$secret=(string)rh24_setting_get('newsletter_signing_secret','');if($secret==='')return null;
    $calc=rh24_b64url_encode(hash_hmac('sha256',$body,$secret,true));if(!hash_equals($calc,$sig))return null;$raw=rh24_b64url_decode($body);if($raw===false)return null;$d=rh24_json_decode($raw,[]);
    if(!is_array($d)||($d['purpose']??'')!==$purpose||(int)($d['exp']??0)<time())return null;$id=trim((string)($d['id']??''));return $id!==''?$id:null;
}
function rh24_public_base_url(): string {
    $https=!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off';$scheme=$https?'https':'http';$host=(string)($_SERVER['HTTP_HOST']??'www.raeucherhaken24.com');return $scheme.'://'.$host.'/';
}
function rh24_send_newsletter_confirmation(string $customerId): bool {
    $q=rh24_db()->prepare('SELECT id,name,email FROM customers WHERE id=?');$q->execute([$customerId]);$c=$q->fetch();if(!$c||!filter_var((string)$c['email'],FILTER_VALIDATE_EMAIL))return false;
    $token=rh24_newsletter_token($customerId,'confirm',172800);$link=rh24_public_base_url().'newsletter.php?action=confirm&token='.rawurlencode($token);$name=htmlspecialchars((string)$c['name'],ENT_QUOTES,'UTF-8');
    $subject='Räucherhaken24 Newsletter – Anmeldung bestätigen';
    $html='<!doctype html><html><body style="font-family:Arial,sans-serif;color:#2b211a;background:#f6f2ed;padding:24px"><div style="max-width:640px;margin:auto;background:#fff;border:1px solid #e6ddd4;border-radius:16px;padding:34px"><div style="font-size:22px;font-weight:800">RÄUCHERHAKEN<span style="color:#b85d1b">24</span></div><h1>Newsletter-Anmeldung bestätigen</h1><p>Guten Tag '.$name.',</p><p>bitte bestätigen Sie, dass Sie Informationen, Neuheiten und Angebote von Räucherhaken24 per E-Mail erhalten möchten.</p><p style="margin:26px 0"><a href="'.$link.'" style="display:inline-block;background:#5a341c;color:#fff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700">Newsletter-Anmeldung bestätigen</a></p><p style="font-size:13px;color:#6f675f">Ohne Bestätigung wird kein Newsletter versendet. Der Link ist 48 Stunden gültig.</p></div></body></html>';
    return rh24_send_system_mail((string)$c['email'],$subject,$html,'newsletter_optin',null);
}
function rh24_newsletter_unsubscribe_url(string $customerId): string { return rh24_public_base_url().'newsletter.php?action=unsubscribe&token='.rawurlencode(rh24_newsletter_token($customerId,'unsubscribe',315360000)); }
function rh24_newsletter_campaigns(int $limit=50): array { $limit=max(1,min(100,$limit));$rows=rh24_db()->query('SELECT * FROM newsletter_campaigns ORDER BY created_at DESC LIMIT '.$limit)->fetchAll();foreach($rows as &$r){$r['recipient_count']=(int)$r['recipient_count'];$r['sent_count']=(int)$r['sent_count'];$r['failed_count']=(int)$r['failed_count'];$r['created_at']=rh24_iso($r['created_at']);$r['sent_at']=rh24_iso($r['sent_at']);}unset($r);return $rows; }
function rh24_newsletter_summary(): array { $db=rh24_db();$total=(int)($db->query("SELECT COUNT(*) FROM customers WHERE newsletter_status='confirmed' AND email IS NOT NULL AND email<>''")->fetchColumn()?:0);$pending=(int)($db->query("SELECT COUNT(*) FROM customers WHERE newsletter_status='pending'")->fetchColumn()?:0);$unsub=(int)($db->query("SELECT COUNT(*) FROM customers WHERE newsletter_status='unsubscribed'")->fetchColumn()?:0);return ['confirmed'=>$total,'pending'=>$pending,'unsubscribed'=>$unsub]; }
function rh24_send_newsletter_to_customer(array $c,string $subject,string $body): bool {
    $email=(string)($c['email']??'');if(!filter_var($email,FILTER_VALIDATE_EMAIL))return false;$name=htmlspecialchars((string)($c['name']??''),ENT_QUOTES,'UTF-8');$safeBody=nl2br(htmlspecialchars($body,ENT_QUOTES,'UTF-8'));$unsub=rh24_newsletter_unsubscribe_url((string)$c['id']);
    $html='<!doctype html><html><body style="font-family:Arial,sans-serif;color:#2b211a;background:#f6f2ed;padding:24px"><div style="max-width:680px;margin:auto;background:#fff;border:1px solid #e6ddd4;border-radius:16px;padding:34px"><div style="font-size:22px;font-weight:800">RÄUCHERHAKEN<span style="color:#b85d1b">24</span></div><p>Guten Tag '.$name.',</p><div style="font-size:16px;line-height:1.65">'.$safeBody.'</div><hr style="border:0;border-top:1px solid #e8dfd6;margin:30px 0"><p style="font-size:12px;color:#777">Sie erhalten diese E-Mail, weil Sie den Räucherhaken24-Newsletter bestätigt haben. <a href="'.$unsub.'">Newsletter abbestellen</a>.</p></div></body></html>';
    return rh24_send_system_mail($email,$subject,$html,'newsletter',null);
}

function rh24_star_thresholds(): array {
    $raw=rh24_setting_get('star_thresholds','[15000,20000,30000,40000,50000,75000]');
    $vals=is_array($raw)?$raw:rh24_json_decode((string)$raw,[]);
    if(!is_array($vals)||count($vals)!==6) $vals=[15000,20000,30000,40000,50000,75000];
    $vals=array_map(fn($v)=>(float)$v,$vals);
    sort($vals,SORT_NUMERIC);
    for($i=0;$i<6;$i++) if($vals[$i]<=0||($i>0&&$vals[$i]<=$vals[$i-1])) return [15000.0,20000.0,30000.0,40000.0,50000.0,75000.0];
    return $vals;
}
function rh24_star_count(float $netRevenue): int {
    $n=0;foreach(rh24_star_thresholds() as $t) if($netRevenue+0.0001 >= $t) $n++;return min(6,$n);
}
function rh24_next_star_threshold(float $netRevenue): ?float {
    foreach(rh24_star_thresholds() as $t) if($netRevenue<$t) return $t;return null;
}
function rh24_star_snapshot(string $repId,string $period,float $net,float $commission,int $stars): void {
    if($repId==='') return;
    try { rh24_db()->prepare("INSERT INTO sales_star_monthly(sales_rep_id,period,net_revenue,commission,stars,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE net_revenue=VALUES(net_revenue),commission=VALUES(commission),stars=VALUES(stars),updated_at=NOW()")
      ->execute([$repId,$period,round($net,2),round($commission,2),max(0,min(6,$stars))]); } catch(Throwable) {}
}
function rh24_star_year_stats(string $repId,?int $year=null): array {
    $year=$year?: (int)date('Y');$months=[];$total=0;$now=date('Y-m');
    for($m=1;$m<=12;$m++){
        $ym=sprintf('%04d-%02d',$year,$m);
        if($ym<=$now){$st=rh24_sales_rep_stats($repId,$ym);$stars=(int)($st['stars']??0);$net=(float)($st['net_revenue']??0);$commission=(float)($st['commission']??0);}else{$stars=0;$net=0.0;$commission=0.0;}
        $months[]=['period'=>$ym,'stars'=>$stars,'net_revenue'=>$net,'commission'=>$commission];$total+=$stars;
    }
    return ['year'=>$year,'total_stars'=>$total,'months'=>$months];
}
function rh24_leaderboard(): array {
    $rows=rh24_db()->query("SELECT id,employee_no,name,status FROM sales_reps WHERE status='active' ORDER BY name")->fetchAll();$out=[];
    foreach($rows as $r){$st=rh24_sales_rep_stats((string)$r['id']);$yr=rh24_star_year_stats((string)$r['id']);$out[]=['sales_rep_id'=>(string)$r['id'],'employee_no'=>(string)$r['employee_no'],'name'=>(string)$r['name'],'net_revenue'=>(float)$st['net_revenue'],'commission'=>(float)$st['commission'],'commission_rate'=>(float)$st['commission_rate'],'stars'=>(int)$st['stars'],'year_stars'=>(int)$yr['total_stars']];}
    usort($out,function($a,$b){$c=$b['commission']<=>$a['commission'];if($c!==0)return $c;$c=$b['net_revenue']<=>$a['net_revenue'];if($c!==0)return $c;return strcmp($a['name'],$b['name']);});
    foreach($out as $i=>&$r)$r['rank']=$i+1;unset($r);return $out;
}
function rh24_valid_calendar_day(int $year,int $month,int $day): int { $days=(int)(new DateTimeImmutable(sprintf('%04d-%02d-01',$year,$month)))->format('t'); return max(1,min($day,$days)); }
function rh24_sales_calendar_data(): array {
    $statementDay=max(1,min(28,(int)rh24_setting_get('commission_statement_day','27')));$payoutDay=max(1,min(28,(int)rh24_setting_get('commission_payout_day','1')));
    $base=new DateTimeImmutable('first day of this month 00:00:00');$events=[];
    for($offset=-1;$offset<=4;$offset++){
      $m=$base->modify(($offset>=0?'+':'').$offset.' month');$y=(int)$m->format('Y');$mo=(int)$m->format('n');
      $sd=rh24_valid_calendar_day($y,$mo,$statementDay);$events[]=['date'=>sprintf('%04d-%02d-%02d',$y,$mo,$sd),'type'=>'statement','title'=>'Provisionsabrechnung','description'=>'Abrechnung des provisionsfähigen Nettoumsatzes für '.$m->format('m/Y').'.'];
      $next=$m->modify('+1 month');$py=(int)$next->format('Y');$pm=(int)$next->format('n');$pd=rh24_valid_calendar_day($py,$pm,$payoutDay);$events[]=['date'=>sprintf('%04d-%02d-%02d',$py,$pm,$pd),'type'=>'payout','title'=>'Geplante Auszahlung','description'=>'Planmäßige Auszahlung der Provision aus '.$m->format('m/Y').' – vorbehaltlich Banklaufzeit, Wochenende und Feiertagen.'];
    }
    usort($events,fn($a,$b)=>strcmp($a['date'],$b['date']));
    return ['statement_day'=>$statementDay,'payout_day'=>$payoutDay,'events'=>$events,'note'=>'Die Provisionsabrechnung erfolgt planmäßig am 27. des Monats. Die Auszahlung ist grundsätzlich zum 1. des Folgemonats vorgesehen; bei Wochenenden, Feiertagen oder Banklaufzeiten kann sich die Gutschrift verschieben.'];
}

function rh24_orgaboard_url(): string {
    $https=!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off';
    $scheme=$https?'https':'http';
    $host=(string)($_SERVER['HTTP_HOST']??'www.raeucherhaken24.com');
    $path=rtrim(str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/orgaboard/index.php'))),'/');
    if($path===''||$path==='.')$path='/orgaboard';
    return $scheme.'://'.$host.$path.'/';
}
function rh24_mail_log(?string $userId,string $recipient,string $type,string $subject,string $status): void {
    try { rh24_db()->prepare('INSERT INTO mail_log(user_id,recipient,mail_type,subject,status,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$userId?:null,$recipient,$type,$subject,$status]); } catch(Throwable) {}
}
function rh24_send_system_mail(string $to,string $subject,string $html,string $type='system',?string $userId=null): bool {
    if(!filter_var($to,FILTER_VALIDATE_EMAIL)) return false;
    $from=(string)rh24_setting_get('system_email','service@raeucherhaken24.com');
    if(!filter_var($from,FILTER_VALIDATE_EMAIL))$from='service@raeucherhaken24.com';
    $senderName=preg_replace('/[\r\n]+/',' ',trim((string)rh24_setting_get('newsletter_sender_name','Räucherhaken24'))) ?: 'Räucherhaken24';
    $reply=$from;if(str_starts_with($type,'newsletter')){$candidate=(string)rh24_setting_get('newsletter_reply_to',$from);if(filter_var($candidate,FILTER_VALIDATE_EMAIL))$reply=$candidate;}
    $headers=[
      'MIME-Version: 1.0',
      'Content-Type: text/html; charset=UTF-8',
      'From: '.$senderName.' <'.$from.'>',
      'Reply-To: '.$reply,
      'X-Mailer: Räucherhaken24 Orgaboard'
    ];
    $encodedSubject=function_exists('mb_encode_mimeheader')?mb_encode_mimeheader($subject,'UTF-8'):$subject;
    $ok=@mail($to,$encodedSubject,$html,implode("\r\n",$headers));
    rh24_mail_log($userId,$to,$type,$subject,$ok?'sent':'failed');
    return $ok;
}
function rh24_create_reset_token(string $userId,string $purpose='reset',int $ttlSeconds=3600): string {
    $raw=bin2hex(random_bytes(32));$hash=hash('sha256',$raw);$expires=date('Y-m-d H:i:s',time()+$ttlSeconds);
    $db=rh24_db();
    $db->prepare("UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND purpose=? AND used_at IS NULL")->execute([$userId,$purpose]);
    $db->prepare('INSERT INTO password_reset_tokens(user_id,purpose,token_hash,expires_at,used_at,created_at) VALUES(?,?,?,?,NULL,NOW())')->execute([$userId,$purpose,$hash,$expires]);
    return $raw;
}
function rh24_send_welcome_email(string $userId): bool {
    $db=rh24_db();$q=$db->prepare('SELECT * FROM users WHERE id=?');$q->execute([$userId]);$u=$q->fetch();if(!$u)return false;
    $email=(string)($u['email']??'');if(!filter_var($email,FILTER_VALIDATE_EMAIL))return false;
    $token=rh24_create_reset_token($userId,'activation',172800);$base=rh24_orgaboard_url();$activation=$base.'passwort.php?token='.rawurlencode($token);
    $subject='Herzlich willkommen bei Räucherhaken24 – Ihr Orgaboard-Zugang';
    $name=htmlspecialchars((string)$u['display_name'],ENT_QUOTES,'UTF-8');$username=htmlspecialchars((string)$u['username'],ENT_QUOTES,'UTF-8');
    $html='<!doctype html><html><body style="font-family:Arial,sans-serif;color:#2b211a;background:#f6f2ed;padding:24px"><div style="max-width:680px;margin:auto;background:#fff;border:1px solid #e6ddd4;border-radius:16px;padding:34px"><div style="font-size:24px;font-weight:800;letter-spacing:.04em">RÄUCHERHAKEN<span style="color:#b85d1b">24</span></div><h1 style="font-size:28px;margin:28px 0 12px">Herzlich willkommen, '.$name.'.</h1><p style="font-size:16px;line-height:1.65">Wir begrüßen Sie herzlich bei Räucherhaken24 und freuen uns sehr, dass Sie sich für die Zusammenarbeit mit uns entschieden haben und unser Außendienstteam verstärken.</p><p style="font-size:16px;line-height:1.65">Damit Sie ohne Umwege starten können, haben wir Ihren persönlichen Zugang zum Orgaboard vorbereitet.</p><div style="background:#f8f4ef;border-radius:12px;padding:20px;margin:22px 0"><b>Ihre Zugangsdaten</b><p>Benutzername: <strong>'.$username.'</strong><br>Login / Handy-App: <a href="'.$base.'">'.$base.'</a></p></div><p style="font-size:16px;line-height:1.65">Ihr Passwort wird aus Sicherheitsgründen nicht per E-Mail verschickt. Legen Sie es einmalig über den folgenden persönlichen Link fest. Der Link ist 48 Stunden gültig:</p><p style="margin:26px 0"><a href="'.$activation.'" style="display:inline-block;background:#5a341c;color:#fff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700">Passwort festlegen & starten</a></p><h3>Orgaboard als Handy-App</h3><p style="line-height:1.6">Öffnen Sie den Orgaboard-Link auf Ihrem Smartphone. Auf Android können Sie die App über Chrome installieren; auf dem iPhone über Safari → Teilen → „Zum Home-Bildschirm“.</p><p style="font-size:14px;color:#6f675f;margin-top:30px">Falls Sie später Ihr Passwort vergessen, können Sie auf der Anmeldeseite jederzeit unkompliziert einen neuen Einrichtungslink per E-Mail anfordern.</p><p style="margin-top:28px">Wir wünschen Ihnen einen erfolgreichen Start.<br><strong>Ihr Team von Räucherhaken24</strong></p></div></body></html>';
    $ok=rh24_send_system_mail($email,$subject,$html,'welcome',$userId);
    if($ok)$db->prepare('UPDATE users SET welcome_sent_at=NOW(),updated_at=NOW() WHERE id=?')->execute([$userId]);
    return $ok;
}
function rh24_send_reset_email(string $userId): bool {
    $db=rh24_db();$q=$db->prepare('SELECT * FROM users WHERE id=? AND status=\'active\'');$q->execute([$userId]);$u=$q->fetch();if(!$u)return false;
    $email=(string)($u['email']??'');if(!filter_var($email,FILTER_VALIDATE_EMAIL))return false;
    $token=rh24_create_reset_token($userId,'reset',3600);$base=rh24_orgaboard_url();$link=$base.'passwort.php?token='.rawurlencode($token);
    $subject='Räucherhaken24 Orgaboard – Passwort neu festlegen';
    $name=htmlspecialchars((string)$u['display_name'],ENT_QUOTES,'UTF-8');
    $html='<!doctype html><html><body style="font-family:Arial,sans-serif;color:#2b211a;background:#f6f2ed;padding:24px"><div style="max-width:640px;margin:auto;background:#fff;border:1px solid #e6ddd4;border-radius:16px;padding:34px"><div style="font-size:22px;font-weight:800">RÄUCHERHAKEN<span style="color:#b85d1b">24</span></div><h1>Passwort neu festlegen</h1><p>Guten Tag '.$name.',</p><p>für Ihren Orgaboard-Zugang wurde eine Passwortänderung angefordert. Über den folgenden Link können Sie unkompliziert ein neues Passwort festlegen. Der Link ist 60 Minuten gültig.</p><p style="margin:26px 0"><a href="'.$link.'" style="display:inline-block;background:#5a341c;color:#fff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700">Neues Passwort festlegen</a></p><p style="font-size:13px;color:#6f675f">Falls Sie diese Änderung nicht angefordert haben, ignorieren Sie diese E-Mail. Ihr bisheriges Passwort bleibt dann unverändert.</p></div></body></html>';
    return rh24_send_system_mail($email,$subject,$html,'password_reset',$userId);
}
function rh24_username_from_name(string $name): string {
    $s=trim(function_exists('mb_strtolower')?mb_strtolower($name,'UTF-8'):strtolower($name));$map=['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss'];$s=strtr($s,$map);
    if(function_exists('iconv')){$t=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);if($t!==false)$s=$t;}
    $s=preg_replace('/[^a-z0-9]+/','.',strtolower($s))??'';$s=trim($s,'.');return $s!==''?$s:'mitarbeiter';
}
function rh24_unique_username(string $name): string {
    $db=rh24_db();$base=substr(rh24_username_from_name($name),0,65);$candidate=$base;$i=1;$q=$db->prepare('SELECT 1 FROM users WHERE username=? LIMIT 1');
    while(true){$q->execute([$candidate]);if(!$q->fetchColumn())return $candidate;$i++;$candidate=$base.'.'.$i;}
}
function rh24_commission_tiers(): array { return [
    ['from'=>0.0,'to'=>10000.0,'rate'=>5.0],
    ['from'=>10000.0,'to'=>20000.0,'rate'=>7.5],
    ['from'=>20000.0,'to'=>30000.0,'rate'=>10.0],
    ['from'=>30000.0,'to'=>50000.0,'rate'=>12.5],
    ['from'=>50000.0,'to'=>75000.0,'rate'=>15.0],
    ['from'=>75000.0,'to'=>null,'rate'=>20.0],
]; }
function rh24_commission_calculation(float $netRevenue): array {
    $net=max(0.0,$netRevenue);$commission=0.0;$segments=[];$marginal=5.0;
    foreach(rh24_commission_tiers() as $tier){$from=(float)$tier['from'];$to=$tier['to']===null?null:(float)$tier['to'];if($net<=$from)break;$portion=($to===null?max(0,$net-$from):max(0,min($net,$to)-$from));if($portion<=0)continue;$value=$portion*((float)$tier['rate']/100);$commission+=$value;$marginal=(float)$tier['rate'];$segments[]=['from'=>$from,'to'=>$to,'rate'=>(float)$tier['rate'],'revenue'=>round($portion,2),'commission'=>round($value,2)];}
    $marginal=$net>=75000?20.0:($net>=50000?15.0:($net>=30000?12.5:($net>=20000?10.0:($net>=10000?7.5:5.0))));$next=null;foreach([10000.0,20000.0,30000.0,50000.0,75000.0] as $t)if($net<$t){$next=$t;break;}
    return ['commission'=>round($commission,2),'marginal_rate'=>$marginal,'effective_rate'=>$net>0?round($commission/$net*100,2):0.0,'next_threshold'=>$next,'gap_to_next'=>$next===null?0.0:round(max(0,$next-$net),2),'segments'=>$segments];
}
function rh24_commission_rate(float $netRevenue): float { return (float)rh24_commission_calculation($netRevenue)['marginal_rate']; }
function rh24_commission_amount(float $netRevenue): float { return (float)rh24_commission_calculation($netRevenue)['commission']; }
function rh24_commission_next_threshold(float $netRevenue): ?float { return rh24_commission_calculation($netRevenue)['next_threshold']; }
function rh24_sales_rep_stats(string $repId,?string $yearMonth=null): array {
    if($repId==='')return ['period'=>'','order_count'=>0,'item_qty'=>0,'net_revenue'=>0.0,'gross_goods'=>0.0,'commission_rate'=>5.0,'effective_rate'=>0.0,'commission'=>0.0,'next_threshold'=>10000.0,'gap_to_next'=>10000.0,'commission_segments'=>[],'daily'=>[],'breakdown'=>[],'credited_orders'=>[]];
    if(!$yearMonth||!preg_match('/^\d{4}-\d{2}$/',$yearMonth))$yearMonth=date('Y-m');
    $start=$yearMonth.'-01 00:00:00';$next=date('Y-m-d H:i:s',strtotime($start.' +1 month'));
    $q=rh24_db()->prepare("SELECT order_no,source,sales_channel,sales_rep_id,commission_sales_rep_id,commission_attribution,commission_note,customer_json,items_json,totals_json,created_at FROM orders WHERE COALESCE(NULLIF(commission_sales_rep_id,''),sales_rep_id)=? AND payment_status='paid' AND status<>'cancelled' AND created_at>=? AND created_at<? ORDER BY created_at ASC,order_no ASC");
    $q->execute([$repId,$start,$next]);$rows=$q->fetchAll();
    $net=0.0;$grossGoods=0.0;$qty=0;$break=[];$credited=[];$days=(int)date('t',strtotime($start));$daily=array_fill(1,$days,0.0);$runningNet=0.0;
    foreach($rows as $r){$tot=rh24_json_decode($r['totals_json'],[]);$vat=(float)($tot['vat_rate']??rh24_setting_get('vat_rate','19'));$goodsGross=(float)($tot['subtotal']??0);$orderNet=$goodsGross/(1+$vat/100);$before=rh24_commission_amount($runningNet);$runningNet+=$orderNet;$after=rh24_commission_amount($runningNet);$orderCredit=round($after-$before,2);$grossGoods+=$goodsGross;$net+=$orderNet;$day=(int)date('j',strtotime((string)$r['created_at']));if(isset($daily[$day]))$daily[$day]+=$orderNet;$cust=rh24_json_decode($r['customer_json'],[]);$channel=rh24_sales_channel_normalize((string)($r['sales_channel']??''),(string)($r['source']??''));$credited[]=['order_no'=>(string)$r['order_no'],'created_at'=>rh24_iso($r['created_at']),'customer_name'=>(string)($cust['name']??'Kunde'),'sales_channel'=>$channel,'sales_channel_label'=>rh24_sales_channel_label($channel),'commission_attribution'=>(string)($r['commission_attribution']??''),'commission_attribution_label'=>rh24_commission_attribution_label((string)($r['commission_attribution']??'')),'commission_note'=>(string)($r['commission_note']??''),'net'=>round($orderNet,2),'gross_goods'=>round($goodsGross,2),'commission_credit'=>$orderCredit];
      foreach(rh24_json_decode($r['items_json'],[]) as $it){$qv=max(0,(int)($it['qty']??0));$qty+=$qv;$key=(string)($it['article_no']??$it['id']??'Artikel');if(!isset($break[$key]))$break[$key]=['article_no'=>(string)($it['article_no']??''),'name'=>(string)($it['name']??$it['id']??'Artikel'),'qty'=>0,'gross'=>0.0,'net'=>0.0];$lineGross=(float)($it['line_total']??((float)($it['unit_price']??0)*$qv));$break[$key]['qty']+=$qv;$break[$key]['gross']+=$lineGross;$break[$key]['net']+=$lineGross/(1+$vat/100);}
    }
    $net=round($net,2);$grossGoods=round($grossGoods,2);$calc=rh24_commission_calculation($net);$rate=(float)$calc['marginal_rate'];$baseCommission=(float)$calc['commission'];$teamBonus=rh24_team_bonus_for_period($repId,$start,$next,$rows);$commission=round($baseCommission+(float)$teamBonus['amount'],2);$effective=$net>0?round($baseCommission/$net*100,2):0.0;$nextT=$calc['next_threshold'];$stars=rh24_star_count($net);$nextStar=rh24_next_star_threshold($net);$dailyRows=[];foreach($daily as $d=>$v)$dailyRows[]=['day'=>$d,'date'=>sprintf('%s-%02d',$yearMonth,$d),'net'=>round($v,2)];
    rh24_star_snapshot($repId,$yearMonth,$net,$commission,$stars);$breakdown=array_values($break);usort($breakdown,fn($a,$b)=>$b['gross']<=>$a['gross']);$credited=array_reverse($credited);
    return ['period'=>$yearMonth,'order_count'=>count($rows),'item_qty'=>$qty,'net_revenue'=>$net,'gross_goods'=>$grossGoods,'commission_rate'=>$rate,'effective_rate'=>$effective,'base_commission'=>$baseCommission,'team_leader_bonus_rate'=>(float)$teamBonus['rate'],'team_leader_bonus_net'=>(float)$teamBonus['net'],'team_leader_bonus'=>(float)$teamBonus['amount'],'team_leader_bonus_team_size'=>(int)($teamBonus['team_size']??0),'team_leader_bonus_members'=>$teamBonus['members']??[],'team_leader_bonus_orders'=>$teamBonus['orders']??[],'commission'=>$commission,'next_threshold'=>$nextT,'gap_to_next'=>(float)$calc['gap_to_next'],'commission_segments'=>$calc['segments'],'daily'=>$dailyRows,'stars'=>$stars,'next_star_threshold'=>$nextStar,'gap_to_next_star'=>$nextStar===null?0.0:round(max(0,$nextStar-$net),2),'breakdown'=>$breakdown,'credited_orders'=>$credited];
}
function rh24_my_sales_stats(): array { return rh24_sales_rep_stats(rh24_user_sales_rep_id()); }
function rh24_mail_log_rows(int $limit=100): array { $limit=max(1,min(250,$limit));$rows=rh24_db()->query('SELECT * FROM mail_log ORDER BY id DESC LIMIT '.$limit)->fetchAll();foreach($rows as &$r)$r['created_at']=rh24_iso($r['created_at']);unset($r);return $rows; }

function rh24_users(): array {
    $rows=rh24_db()->query("SELECT id,username,display_name,email,role,sales_rep_id,permissions_json,status,must_change_password,welcome_sent_at,last_login,created_at,updated_at FROM users ORDER BY role='admin' DESC,display_name")->fetchAll();
    foreach($rows as &$r){$r['permissions']=rh24_json_decode($r['permissions_json']??'',null);unset($r['permissions_json']);$r['must_change_password']=(bool)$r['must_change_password'];$r['welcome_sent_at']=rh24_iso($r['welcome_sent_at']??null);$r['last_login']=rh24_iso($r['last_login']??null);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}
    unset($r); return $rows;
}
function rh24_activity(int $limit=250): array {
    $limit=max(1,min(500,$limit));
    $rows=rh24_db()->query("SELECT * FROM activity_log ORDER BY id DESC LIMIT ".$limit)->fetchAll();
    foreach($rows as &$r){$r['detail']=rh24_json_decode($r['detail_json']??'',[]);unset($r['detail_json']);$r['created_at']=rh24_iso($r['created_at']);}
    unset($r); return $rows;
}
function rh24_cost_profiles(): array {
    $rows=rh24_db()->query("SELECT * FROM product_cost_profiles ORDER BY product_id")->fetchAll();
    foreach($rows as &$r){
        foreach(['material_cost','labor_minutes','labor_hourly_rate','packaging_cost','other_cost','overhead_percent','selling_fee_percent','target_margin_percent','vat_percent','calculated_gross'] as $f)$r[$f]=(float)$r[$f];
        $r['updated_at']=rh24_iso($r['updated_at']);
    }
    unset($r); return $rows;
}
function rh24_messages_for_current_user(): array {
    $uid=rh24_user_id(); if($uid==='') return [];
    $st=rh24_db()->prepare("SELECT m.*,su.display_name sender_name,ru.display_name recipient_name
      FROM messages m LEFT JOIN users su ON su.id=m.sender_user_id LEFT JOIN users ru ON ru.id=m.recipient_user_id
      WHERE m.sender_user_id=? OR m.recipient_user_id=? ORDER BY m.created_at DESC LIMIT 500");
    $st->execute([$uid,$uid]); $rows=$st->fetchAll();
    foreach($rows as &$r){$r['created_at']=rh24_iso($r['created_at']);$r['read_at']=rh24_iso($r['read_at']??null);}
    unset($r); return $rows;
}
function rh24_documents(): array {
    $rows=rh24_db()->query("SELECT * FROM documents ORDER BY created_at DESC")->fetchAll();
    foreach($rows as &$r){$r['payload']=rh24_json_decode($r['payload_json'],[]);unset($r['payload_json']);$r['version_no']=(int)$r['version_no'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);$r['issued_at']=rh24_iso($r['issued_at']??null);}
    unset($r); return $rows;
}
function rh24_next_document_no(string $type): string {
    $key=$type==='delivery_note'?'next_delivery_no':'next_invoice_no';
    $prefix=$type==='delivery_note'?'LS':'RE';
    $next=max(1,(int)rh24_setting_get($key,'1'));
    rh24_setting_set($key,(string)($next+1));
    return $prefix.'-'.date('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT);
}
function rh24_order_by_no(string $orderNo): ?array {
    foreach(rh24_orders() as $o) if((string)$o['order_no']===$orderNo) return $o;
    return null;
}
function rh24_document_payload_from_order(array $o,string $type): array {
    return [
      'document_type'=>$type,
      'title'=>$type==='delivery_note'?'Lieferschein':'Rechnung',
      'order_no'=>(string)($o['order_no']??$o['id']??''),
      'customer'=>$o['customer']??[],
      'items'=>$o['items']??[],
      'totals'=>$o['totals']??[],
      'payment_status'=>$o['payment_status']??'pending',
      'payment_method'=>$o['payment_method']??'',
      'carrier'=>$o['carrier']??'',
      'tracking'=>$o['tracking']??'',
      'document_note'=>'',
      'order_created_at'=>$o['created_at']??''
    ];
}
function rh24_get_or_create_document(string $orderNo,string $type): array {
    if(!in_array($type,['invoice','delivery_note'],true)) throw new InvalidArgumentException('Ungültiger Dokumenttyp.');
    $db=rh24_db();
    $st=$db->prepare("SELECT * FROM documents WHERE order_no=? AND document_type=? LIMIT 1");$st->execute([$orderNo,$type]);$r=$st->fetch();
    if(!$r){
        $o=rh24_order_by_no($orderNo); if(!$o) throw new RuntimeException('Bestellung nicht gefunden.');
        $id='DOC-'.strtoupper(bin2hex(random_bytes(6))); $no=rh24_next_document_no($type); $payload=rh24_document_payload_from_order($o,$type);
        $uid=rh24_user_id()?:null;
        $db->prepare("INSERT INTO documents(id,document_type,document_no,order_no,status,version_no,payload_json,note,issued_at,created_by,updated_by,created_at,updated_at) VALUES(?,?,?,?, 'draft',1,?,'',NULL,?,?,NOW(),NOW())")
           ->execute([$id,$type,$no,$orderNo,rh24_json_encode($payload),$uid,$uid]);
        $db->prepare("INSERT INTO document_versions(document_id,version_no,payload_json,change_note,edited_by,created_at) VALUES(?,?,?,?,?,NOW())")
           ->execute([$id,1,rh24_json_encode($payload),'Dokument aus Bestellung erzeugt',$uid]);
        $st->execute([$orderNo,$type]);$r=$st->fetch();
        rh24_audit('document_created','document',$id,['document_no'=>$no,'order_no'=>$orderNo,'type'=>$type]);
    }
    $r['payload']=rh24_json_decode($r['payload_json'],[]);unset($r['payload_json']);$r['version_no']=(int)$r['version_no'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);$r['issued_at']=rh24_iso($r['issued_at']??null);
    return $r;
}
function rh24_ensure_v57_schema(PDO $db): void {
    try {
        $changes=[
          "ALTER TABLE products ADD COLUMN content_quantity DECIMAL(12,3) NOT NULL DEFAULT 1.000 AFTER unit",
          "ALTER TABLE products ADD COLUMN content_unit VARCHAR(24) NOT NULL DEFAULT 'Stück' AFTER content_quantity",
          "ALTER TABLE products ADD COLUMN package_type VARCHAR(40) NOT NULL DEFAULT 'Einzelartikel' AFTER pack_quantity",
          "ALTER TABLE products ADD COLUMN package_length_cm DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER package_type",
          "ALTER TABLE products ADD COLUMN package_width_cm DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER package_length_cm",
          "ALTER TABLE products ADD COLUMN package_height_cm DECIMAL(8,2) NOT NULL DEFAULT 0.00 AFTER package_width_cm"
        ];
        foreach($changes as $sql){try{$db->exec($sql);}catch(Throwable){}}
        // Bestehende Daten sinnvoll vorbelegen, ohne manuelle Angaben zu überschreiben.
        try{$db->exec("UPDATE products SET content_quantity=CAST(SUBSTRING_INDEX(unit,' ',1) AS DECIMAL(12,3)),content_unit='g' WHERE content_quantity=1.000 AND unit REGEXP '^[0-9]+([.,][0-9]+)?[[:space:]]*g$'");}catch(Throwable){}
        try{$db->exec("UPDATE products SET content_quantity=pack_quantity,content_unit='Stück' WHERE pack_quantity>1 AND content_quantity=1.000");}catch(Throwable){}
        rh24_setting_set('schema_version','57');rh24_setting_set('db_schema_version','57');
        try{rh24_audit('schema_upgrade','system','v57',['features'=>['production_landscape_table','product_categories','content_quantity_unit','package_type_dimensions']],'system');}catch(Throwable){}
    } catch(Throwable) {}
}


function rh24_ensure_v58_schema(PDO $db): void {
    try {
        $changes=[
          "ALTER TABLE orders ADD COLUMN production_priority VARCHAR(16) NOT NULL DEFAULT 'normal' AFTER internal_note",
          "ALTER TABLE orders ADD COLUMN production_due_at DATETIME NULL AFTER production_priority",
          "ALTER TABLE orders ADD COLUMN production_station VARCHAR(60) NOT NULL DEFAULT '' AFTER production_due_at",
          "ALTER TABLE orders ADD COLUMN production_step VARCHAR(40) NOT NULL DEFAULT 'planung' AFTER production_station",
          "ALTER TABLE orders ADD COLUMN production_progress TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER production_step",
          "ALTER TABLE orders ADD COLUMN production_assignee VARCHAR(100) NOT NULL DEFAULT '' AFTER production_progress",
          "ALTER TABLE orders ADD COLUMN production_note TEXT NULL AFTER production_assignee",
          "ALTER TABLE orders ADD COLUMN production_started_at DATETIME NULL AFTER production_note",
          "ALTER TABLE orders ADD COLUMN production_finished_at DATETIME NULL AFTER production_started_at"
        ];
        foreach($changes as $sql){try{$db->exec($sql);}catch(Throwable){}}
        try{$db->exec("UPDATE orders SET production_progress=100,production_step='ready',production_finished_at=COALESCE(production_finished_at,updated_at) WHERE status IN ('ready','shipped','complete') AND production_progress<100");}catch(Throwable){}
        try{$db->exec("UPDATE orders SET production_progress=85,production_step='pack' WHERE status='packing' AND production_progress<85");}catch(Throwable){}
        try{$db->exec("UPDATE orders SET production_progress=70,production_step='quality' WHERE status='quality' AND production_progress<70");}catch(Throwable){}
        try{$db->exec("UPDATE orders SET production_progress=25,production_step='material' WHERE status='production' AND production_progress=0");}catch(Throwable){}
        rh24_setting_set('schema_version','58');rh24_setting_set('db_schema_version','58');
        try{rh24_audit('schema_upgrade','system','v58',['features'=>['production_control_center','priority_due_station_step_progress','production_ticket']],'system');}catch(Throwable){}
    } catch(Throwable) {}
}


function rh24_ensure_v59_schema(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS content (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          title VARCHAR(240) NOT NULL,
          type VARCHAR(60) NOT NULL DEFAULT 'Rezept',
          status VARCHAR(30) NOT NULL DEFAULT 'draft',
          body LONGTEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          KEY idx_content_status (status),
          KEY idx_content_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $seedFile=__DIR__.'/content-seed-v59.json';
        if(is_file($seedFile)){
            $rows=json_decode((string)file_get_contents($seedFile),true);
            if(is_array($rows)){
                $ins=$db->prepare("INSERT IGNORE INTO content(id,title,type,status,body,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())");
                foreach($rows as $r){
                    if(!is_array($r))continue;
                    $id=trim((string)($r['id']??''));$title=trim((string)($r['title']??''));
                    if($id===''||$title==='')continue;
                    $ins->execute([$id,$title,(string)($r['type']??'Rezept'),(string)($r['status']??'published'),rh24_json_encode($r['body']??[])]);
                }
            }
        }
        rh24_setting_set('schema_version','59');rh24_setting_set('db_schema_version','59');
        try{rh24_audit('schema_upgrade','system','v59',['features'=>['all_recipes_in_orgaboard','print_pdf_content','product_category_tab','thermometer_shop_category']],'system');}catch(Throwable){}
    } catch(Throwable) {}
}


function rh24_ensure_v60_schema(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS market_users (
          id VARCHAR(40) NOT NULL PRIMARY KEY,email VARCHAR(190) NOT NULL,password_hash VARCHAR(255) NOT NULL,display_name VARCHAR(160) NOT NULL,phone VARCHAR(80) NULL,zip VARCHAR(20) NULL,city VARCHAR(120) NULL,lat DECIMAL(10,6) NULL,lon DECIMAL(10,6) NULL,email_verified_at DATETIME NULL,status VARCHAR(30) NOT NULL DEFAULT 'active',membership_status VARCHAR(30) NOT NULL DEFAULT 'pending',membership_started_at DATETIME NULL,membership_expires_at DATETIME NULL,membership_order_no VARCHAR(60) NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_market_email(email),KEY idx_market_membership(membership_status,membership_expires_at),KEY idx_market_location(zip,city)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS market_verification_tokens (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,user_id VARCHAR(40) NOT NULL,token_hash CHAR(64) NOT NULL,purpose VARCHAR(30) NOT NULL DEFAULT 'verify',expires_at DATETIME NOT NULL,used_at DATETIME NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_market_token(token_hash),KEY idx_market_token_user(user_id,purpose,expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS market_listings (id VARCHAR(40) NOT NULL PRIMARY KEY,user_id VARCHAR(40) NOT NULL,kind VARCHAR(20) NOT NULL DEFAULT 'sell',title VARCHAR(180) NOT NULL,description TEXT NOT NULL,category VARCHAR(80) NOT NULL DEFAULT 'Sonstiges',condition_label VARCHAR(60) NULL,price DECIMAL(10,2) NOT NULL DEFAULT 0.00,negotiable TINYINT(1) NOT NULL DEFAULT 0,shipping VARCHAR(20) NOT NULL DEFAULT 'pickup',zip VARCHAR(20) NOT NULL,city VARCHAR(120) NOT NULL,lat DECIMAL(10,6) NULL,lon DECIMAL(10,6) NULL,images_json LONGTEXT NULL,status VARCHAR(30) NOT NULL DEFAULT 'pending',views INT UNSIGNED NOT NULL DEFAULT 0,expires_at DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,KEY idx_market_listing_status(status,created_at),KEY idx_market_listing_user(user_id,status),KEY idx_market_listing_kind(kind,category),KEY idx_market_listing_region(zip,city)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS market_messages (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,listing_id VARCHAR(40) NOT NULL,from_user_id VARCHAR(40) NOT NULL,to_user_id VARCHAR(40) NOT NULL,body TEXT NOT NULL,read_at DATETIME NULL,created_at DATETIME NOT NULL,KEY idx_market_msg_to(to_user_id,read_at,created_at),KEY idx_market_msg_listing(listing_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $db->exec("CREATE TABLE IF NOT EXISTS market_reports (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,listing_id VARCHAR(40) NOT NULL,reporter_user_id VARCHAR(40) NULL,reason VARCHAR(100) NOT NULL,details TEXT NULL,status VARCHAR(30) NOT NULL DEFAULT 'open',created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,KEY idx_market_report_status(status,created_at),KEY idx_market_report_listing(listing_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        rh24_setting_set('schema_version','60');rh24_setting_set('db_schema_version','60');
        try{rh24_audit('schema_upgrade','system','v60',['features'=>['marketplace','annual_membership','regional_distance','listing_moderation']],'system');}catch(Throwable){}
    } catch(Throwable) {}
}
function rh24_ensure_v68_schema(PDO $db): void {
    try {
        $changes=[
          "ALTER TABLE orders ADD COLUMN production_assignee_user_id VARCHAR(40) NULL AFTER production_assignee",
          "ALTER TABLE orders ADD COLUMN production_last_worker_id VARCHAR(40) NULL AFTER production_assignee_user_id",
          "ALTER TABLE orders ADD COLUMN production_last_worker_name VARCHAR(180) NOT NULL DEFAULT '' AFTER production_last_worker_id",
          "ALTER TABLE orders ADD COLUMN production_last_work_at DATETIME NULL AFTER production_last_worker_name"
        ];
        foreach($changes as $sql){try{$db->exec($sql);}catch(Throwable){}}
        $db->exec("CREATE TABLE IF NOT EXISTS production_activity (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          order_no VARCHAR(60) NOT NULL,
          event_type VARCHAR(30) NOT NULL DEFAULT 'step',
          production_step VARCHAR(40) NOT NULL DEFAULT 'planung',
          progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
          station VARCHAR(60) NOT NULL DEFAULT '',
          assignee_user_id VARCHAR(40) NULL,
          worker_user_id VARCHAR(40) NULL,
          worker_name VARCHAR(180) NOT NULL DEFAULT '',
          worker_role VARCHAR(40) NOT NULL DEFAULT '',
          note VARCHAR(500) NULL,
          created_at DATETIME NOT NULL,
          KEY idx_prod_activity_order (order_no,created_at),
          KEY idx_prod_activity_worker (worker_user_id,created_at),
          KEY idx_prod_activity_step (production_step,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try{$db->exec("UPDATE orders o JOIN users u ON u.role='production' AND u.display_name=o.production_assignee SET o.production_assignee_user_id=u.id WHERE (o.production_assignee_user_id IS NULL OR o.production_assignee_user_id='') AND o.production_assignee<>''");}catch(Throwable){}
        rh24_setting_set('schema_version','68');rh24_setting_set('db_schema_version','68');
        try{rh24_audit('schema_upgrade','system','v68',['features'=>['production_role','production_staff','execution_trace','worker_assignment']],'system');}catch(Throwable){}
    } catch(Throwable) {}
}

function rh24_ensure_v69_schema(PDO $db): void {
    try {
        $changes=[
          "ALTER TABLE orders ADD COLUMN sales_channel VARCHAR(40) NOT NULL DEFAULT 'other' AFTER source",
          "ALTER TABLE orders ADD COLUMN commission_sales_rep_id VARCHAR(40) NULL AFTER sales_rep_id",
          "ALTER TABLE orders ADD COLUMN commission_attribution VARCHAR(40) NOT NULL DEFAULT '' AFTER commission_sales_rep_id",
          "ALTER TABLE orders ADD COLUMN commission_note VARCHAR(255) NOT NULL DEFAULT '' AFTER commission_attribution",
          "ALTER TABLE orders ADD COLUMN commission_assigned_at DATETIME NULL AFTER commission_note",
          "ALTER TABLE customers ADD COLUMN advisor_assigned_at DATETIME NULL AFTER sales_rep_id",
          "ALTER TABLE customers ADD COLUMN advisor_assignment_source VARCHAR(40) NOT NULL DEFAULT '' AFTER advisor_assigned_at"
        ];
        foreach($changes as $sql){try{$db->exec($sql);}catch(Throwable){}}
        foreach([
          "ALTER TABLE orders ADD KEY idx_orders_sales_channel (sales_channel,created_at)",
          "ALTER TABLE orders ADD KEY idx_orders_commission_rep (commission_sales_rep_id,created_at)"
        ] as $sql){try{$db->exec($sql);}catch(Throwable){}}
        try{$db->exec("UPDATE orders SET sales_channel=CASE
          WHEN source IN ('shop','checkout') THEN 'online'
          WHEN source IN ('field-sales','field','advisor') THEN 'advisor'
          WHEN source IN ('orgaboard-phone','phone','telephone') THEN 'telephone'
          WHEN source IN ('email','orgaboard-email') THEN 'email'
          WHEN source IN ('pickup','walk-in','vor-ort') THEN 'walk_in'
          WHEN source='marketplace' THEN 'marketplace'
          ELSE COALESCE(NULLIF(sales_channel,''),'other') END
          WHERE sales_channel IS NULL OR sales_channel='' OR sales_channel='other'");}catch(Throwable){}
        try{$db->exec("UPDATE customers SET advisor_assigned_at=COALESCE(advisor_assigned_at,updated_at,created_at),advisor_assignment_source=CASE WHEN advisor_assignment_source='' THEN 'bestand_vor_v69' ELSE advisor_assignment_source END WHERE sales_rep_id IS NOT NULL AND sales_rep_id<>''");}catch(Throwable){}
        try{$db->exec("UPDATE orders SET commission_sales_rep_id=sales_rep_id,
          commission_attribution=CASE WHEN sales_channel='online' THEN 'returning_customer' ELSE 'legacy_assigned' END,
          commission_note=CASE WHEN sales_channel='online' THEN 'Online gekauft · Provisionsgutschrift an bestehenden Kundenberater' ELSE 'Bestehende Beraterzuordnung übernommen' END,
          commission_assigned_at=COALESCE(commission_assigned_at,created_at)
          WHERE (commission_sales_rep_id IS NULL OR commission_sales_rep_id='') AND sales_rep_id IS NOT NULL AND sales_rep_id<>''");}catch(Throwable){}
        try{$db->exec("UPDATE orders o JOIN customers c ON c.id=o.customer_id
          SET o.sales_rep_id=c.sales_rep_id,o.commission_sales_rep_id=c.sales_rep_id,o.commission_attribution='returning_customer',
              o.commission_note='Online gekauft · Provisionsgutschrift an bestehenden Kundenberater',o.commission_assigned_at=COALESCE(o.commission_assigned_at,o.created_at)
          WHERE o.sales_channel='online' AND (o.commission_sales_rep_id IS NULL OR o.commission_sales_rep_id='')
            AND c.sales_rep_id IS NOT NULL AND c.sales_rep_id<>''");}catch(Throwable){}
        try{$db->exec("UPDATE orders SET commission_attribution='legacy_unassigned',
          commission_note='Altauftrag ohne eindeutig nachweisbaren Beraterkontakt · keine automatische Rückverteilung'
          WHERE (commission_sales_rep_id IS NULL OR commission_sales_rep_id='') AND commission_attribution=''");}catch(Throwable){}
        rh24_house_sales_reps($db);
        $st=$db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('online_commission_rotation_next','bjoern',NOW()) ON DUPLICATE KEY UPDATE setting_value=setting_value");
        $st->execute();
        rh24_setting_set('schema_version','69');rh24_setting_set('db_schema_version','69');
        try{rh24_audit('schema_upgrade','system','v69',['features'=>['sales_origin','advisor_customer_binding','full_commission_credit','house_rotation_bjoern_jessica','commission_ledger']],'system');}catch(Throwable){}
    } catch(Throwable) {}
}

function rh24_sales_channel_labels(): array {
    return ['online'=>'Online-Shop','advisor'=>'Kundenberater','telephone'=>'Telefonleitung','email'=>'E-Mail','walk_in'=>'Vor Ort / Abholung','marketplace'=>'An- & Verkaufen','other'=>'Sonstiges'];
}
function rh24_sales_channel_normalize(string $channel,string $source=''): string {
    $v=strtolower(trim($channel));
    $aliases=['shop'=>'online','checkout'=>'online','online-shop'=>'online','web'=>'online','field'=>'advisor','field-sales'=>'advisor','aussendienst'=>'advisor','außendienst'=>'advisor','berater'=>'advisor','kundenberater'=>'advisor','phone'=>'telephone','telefon'=>'telephone','orgaboard-phone'=>'telephone','mail'=>'email','orgaboard-email'=>'email','pickup'=>'walk_in','walk-in'=>'walk_in','vor_ort'=>'walk_in','vor-ort'=>'walk_in','marketplace'=>'marketplace'];
    $v=$aliases[$v]??$v;$src=strtolower(trim($source));$srcMapped=$aliases[$src]??($src==='marketplace'?'marketplace':'other');
    if(!isset(rh24_sales_channel_labels()[$v])||($v==='other'&&$srcMapped!=='other'))$v=$srcMapped;
    return isset(rh24_sales_channel_labels()[$v])?$v:'other';
}
function rh24_sales_channel_label(string $channel,string $source=''): string {$n=rh24_sales_channel_normalize($channel,$source);return rh24_sales_channel_labels()[$n]??'Sonstiges';}
function rh24_commission_attribution_labels(): array {
    return ['returning_customer'=>'Wiederkehrender Beraterkunde','direct_advisor'=>'Direkter Kundenberater-Verkauf','house_rotation'=>'Hausverteilung ohne Beraterkontakt','admin_override'=>'Manuell geprüft / korrigiert','legacy_assigned'=>'Bestehende Beraterzuordnung','legacy_unassigned'=>'Altauftrag ohne eindeutige Zuordnung'];
}
function rh24_commission_attribution_label(string $key): string {return rh24_commission_attribution_labels()[$key]??($key!==''?$key:'Noch nicht zugeordnet');}

/* V71 · Festgebiete, Weißgebiete, Gebietsbücher und Teamleiter */
function rh24_ensure_v71_schema(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS sales_territories (
          state_code VARCHAR(5) NOT NULL PRIMARY KEY,
          state_name VARCHAR(80) NOT NULL,
          territory_book_no VARCHAR(20) NOT NULL,
          owner_sales_rep_id VARCHAR(40) NULL,
          status VARCHAR(20) NOT NULL DEFAULT 'white',
          notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_sales_territory_book (territory_book_no),
          KEY idx_sales_territory_owner (owner_sales_rep_id),
          KEY idx_sales_territory_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        try{$db->exec("ALTER TABLE sales_reps ADD COLUMN state_code VARCHAR(5) NULL AFTER territory");}catch(Throwable){}
        try{$db->exec("ALTER TABLE sales_reps ADD COLUMN parent_sales_rep_id VARCHAR(40) NULL AFTER state_code");}catch(Throwable){}
        try{$db->exec("ALTER TABLE sales_reps ADD COLUMN team_leader_since DATETIME NULL AFTER parent_sales_rep_id");}catch(Throwable){}
        try{$db->exec("ALTER TABLE sales_reps ADD KEY idx_sales_reps_state (state_code)");}catch(Throwable){}
        try{$db->exec("ALTER TABLE sales_reps ADD KEY idx_sales_reps_parent (parent_sales_rep_id)");}catch(Throwable){}
        $states=[
          ['01','Schleswig-Holstein'],['02','Hamburg'],['03','Niedersachsen'],['04','Bremen'],['05','Nordrhein-Westfalen'],['06','Hessen'],['07','Rheinland-Pfalz'],['08','Baden-Württemberg'],['09','Bayern'],['10','Saarland'],['11','Berlin'],['12','Brandenburg'],['13','Mecklenburg-Vorpommern'],['14','Sachsen'],['15','Sachsen-Anhalt'],['16','Thüringen']
        ];
        $ins=$db->prepare("INSERT INTO sales_territories(state_code,state_name,territory_book_no,owner_sales_rep_id,status,notes,created_at,updated_at) VALUES(?,?,?,NULL,'white','',NOW(),NOW()) ON DUPLICATE KEY UPDATE state_name=VALUES(state_name),territory_book_no=VALUES(territory_book_no),updated_at=NOW()");
        foreach($states as [$code,$name])$ins->execute([$code,$name,'FG-'.$code]);
        rh24_setting_set('team_leader_bonus_rate','3');
        rh24_setting_set('schema_version','71');rh24_setting_set('db_schema_version','71');
        try{rh24_audit('schema_upgrade','system','v71',['features'=>['16_bundesland_festgebiete','weissgebiete','gebietsbuchnummer','team_hierarchy','teamleiter_bonus_3pct']],'system');}catch(Throwable){}
    } catch(Throwable $e) { /* Upgrade bleibt idempotent und wird beim nächsten Aufruf erneut versucht. */ }
}

/* V72 · Vertriebsrollen, Führungshierarchie und farbcodierte Festgebiete */
function rh24_ensure_v72_schema(PDO $db): void {
    try {
        try{$db->exec("ALTER TABLE sales_reps ADD COLUMN sales_role VARCHAR(40) NOT NULL DEFAULT 'advisor_active' AFTER team_leader_since");}catch(Throwable){}
        try{$db->exec("ALTER TABLE sales_reps ADD COLUMN role_since DATETIME NULL AFTER sales_role");}catch(Throwable){}
        try{$db->exec("ALTER TABLE sales_reps ADD KEY idx_sales_reps_role (sales_role,status)");}catch(Throwable){}
        // Bestehende Datensätze sinnvoll migrieren. Hauskonten bleiben aus der Personalhierarchie heraus.
        try{$db->exec("UPDATE sales_reps SET sales_role='house' WHERE employee_no LIKE 'ZENTRALE-%'");}catch(Throwable){}
        try{$db->exec("UPDATE sales_reps SET sales_role='team_leader',role_since=COALESCE(role_since,team_leader_since,updated_at,created_at) WHERE team_leader_since IS NOT NULL AND sales_role NOT IN ('regional_manager','district_manager','house')");}catch(Throwable){}
        try{$db->exec("UPDATE sales_reps SET sales_role='advisor_active',role_since=COALESCE(role_since,created_at) WHERE (sales_role IS NULL OR sales_role='' OR sales_role='field_sales') AND employee_no NOT LIKE 'ZENTRALE-%'");}catch(Throwable){}
        rh24_setting_set('schema_version','72');rh24_setting_set('db_schema_version','72');
        try{rh24_audit('schema_upgrade','system','v72',['features'=>['sales_roles','regional_manager','district_manager','team_leader','advisor_active','advisor_be','territory_assignment_tabs','state_colors']],'system');}catch(Throwable){}
    } catch(Throwable $e) { /* Idempotentes Upgrade – beim nächsten Aufruf erneut versuchen. */ }
}
function rh24_sales_role_labels(): array {
    return [
      'regional_manager'=>'Regionalmanager',
      'district_manager'=>'Bezirksmanager',
      'team_leader'=>'Teamleiter',
      'advisor_active'=>'Kundenberater Aktiv',
      'advisor_be'=>'Kundenberater BE',
      'house'=>'Zentrale / Hauskonto'
    ];
}
function rh24_sales_role_label(string $role): string {return rh24_sales_role_labels()[$role]??'Kundenberater Aktiv';}
function rh24_sales_role_public(string $role): bool {return in_array($role,['regional_manager','district_manager','team_leader','advisor_active','advisor_be'],true);}
function rh24_sales_role_can_own_territory(string $role): bool {return in_array($role,['regional_manager','district_manager','team_leader','advisor_active'],true);}
function rh24_sales_role_parent_allowed(string $role,string $parentRole): bool {
    if($role==='regional_manager')return false;
    if($role==='district_manager')return $parentRole==='regional_manager';
    if($role==='team_leader')return in_array($parentRole,['regional_manager','district_manager'],true);
    if(in_array($role,['advisor_active','advisor_be'],true))return in_array($parentRole,['regional_manager','district_manager','team_leader','advisor_active','advisor_be'],true);
    return false;
}
function rh24_sales_rep_parent_cycle(PDO $db,string $repId,string $candidateParentId): bool {
    if($repId===''||$candidateParentId==='')return false;
    $seen=[];$cur=$candidateParentId;
    for($i=0;$i<32 && $cur!=='';$i++){
        if($cur===$repId)return true;
        if(isset($seen[$cur]))return true;$seen[$cur]=true;
        $q=$db->prepare("SELECT parent_sales_rep_id FROM sales_reps WHERE id=? LIMIT 1");$q->execute([$cur]);$cur=(string)($q->fetchColumn()?:'');
    }
    return false;
}

/* V73 · Professionelle Gebietsbücher, Branchenadressen und Kontakt-CRM */
function rh24_territory_directory_categories(): array {
    return [
      'grocery'=>'Lebensmittelgeschäft / Supermarkt',
      'deli'=>'Feinkost / Delikatessen',
      'fishing_shop'=>'Angelgeschäft / Angelbedarf',
      'fishing_club'=>'Angelverein / Fischereiverein',
      'butcher'=>'Fleischerei / Metzgerei / Schlachterei',
      'butcher_supply'=>'Fleischer- / Schlachtereibedarf',
      'restaurant'=>'Restaurant / Gastronomie',
      'seafood'=>'Fischgeschäft / Fischhandel',
      'fishery'=>'Fischerei / Fischer / Fischverarbeitung',
      'slaughterhouse'=>'Schlachthof / Fleischverarbeitung',
      'smoke_bbq'=>'Räuchern / Grill / BBQ',
      'hardware_store'=>'Baumarkt / Gartencenter / DIY',
      'spices'=>'Gewürzfachhandel / Gewürzladen / Kräuter',
      'spice_producer'=>'Gewürzhersteller / Gewürzmanufaktur / Gewürzmühle',
      'outdoor_hunting'=>'Outdoor / Jagd / Angel-nahe Branche',
      'wholesale'=>'Großhandel / Gastrobedarf',
      'market'=>'Markt / Wochenmarkt',
      'other'=>'Sonstiger Branchenkontakt'
    ];
}
function rh24_territory_directory_category_label(string $key): string {return rh24_territory_directory_categories()[$key]??'Sonstiger Branchenkontakt';}
function rh24_territory_directory_status_labels(): array {return ['candidate'=>'Recherche · zu prüfen','active'=>'Aktiv / freigegeben','paused'=>'Pausiert','archived'=>'Archiviert'];}
function rh24_territory_contact_method_labels(): array {return ['phone'=>'Telefon','email'=>'E-Mail','visit'=>'Vor-Ort-Besuch','whatsapp'=>'WhatsApp','letter'=>'Brief/Post','other'=>'Sonstiges'];}
function rh24_territory_contact_result_labels(): array {return ['reached'=>'Erreicht','not_reached'=>'Nicht erreicht','appointment'=>'Termin vereinbart','interested'=>'Interesse','info_sent'=>'Information gesendet','callback'=>'Rückruf / Wiedervorlage','no_interest'=>'Kein Interesse','other'=>'Sonstiges'];}
function rh24_ensure_v73_schema(PDO $db): void {
    try {
      $db->exec("CREATE TABLE IF NOT EXISTS territory_directory (
        id VARCHAR(40) NOT NULL PRIMARY KEY,
        state_code VARCHAR(5) NOT NULL,
        category VARCHAR(40) NOT NULL,
        company VARCHAR(190) NOT NULL,
        contact_person VARCHAR(160) NULL,
        phone VARCHAR(100) NULL,
        email VARCHAR(190) NULL,
        website VARCHAR(255) NULL,
        street VARCHAR(190) NULL,
        zip VARCHAR(12) NULL,
        city VARCHAR(140) NULL,
        latitude DECIMAL(10,7) NULL,
        longitude DECIMAL(10,7) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'candidate',
        assigned_sales_rep_id VARCHAR(40) NULL,
        source VARCHAR(40) NOT NULL DEFAULT 'manual',
        source_ref VARCHAR(120) NULL,
        source_url VARCHAR(255) NULL,
        source_checked_at DATETIME NULL,
        verified_at DATETIME NULL,
        verified_by VARCHAR(80) NULL,
        notes TEXT NULL,
        last_contacted_at DATETIME NULL,
        last_contact_method VARCHAR(30) NULL,
        last_contact_result VARCHAR(50) NULL,
        next_follow_up_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uq_territory_source_ref (source,source_ref),
        KEY idx_territory_dir_state_status (state_code,status),
        KEY idx_territory_dir_category (state_code,category),
        KEY idx_territory_dir_rep (assigned_sales_rep_id),
        KEY idx_territory_dir_followup (next_follow_up_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      $db->exec("CREATE TABLE IF NOT EXISTS territory_contact_logs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        directory_id VARCHAR(40) NOT NULL,
        state_code VARCHAR(5) NOT NULL,
        sales_rep_id VARCHAR(40) NULL,
        user_id VARCHAR(40) NULL,
        contact_at DATETIME NOT NULL,
        method VARCHAR(30) NOT NULL,
        result VARCHAR(50) NOT NULL,
        notes TEXT NULL,
        next_follow_up_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        KEY idx_territory_log_directory (directory_id,contact_at),
        KEY idx_territory_log_rep (sales_rep_id,contact_at),
        KEY idx_territory_log_followup (next_follow_up_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      // Bestehende Außendienstkonten bekommen die beiden neuen Gebietsbuchrechte automatisch.
      try{
        $q=$db->query("SELECT id,permissions_json FROM users WHERE role='field_sales'");
        $up=$db->prepare("UPDATE users SET permissions_json=?,updated_at=NOW() WHERE id=?");
        foreach($q->fetchAll() as $u){$perms=rh24_json_decode((string)($u['permissions_json']??''),[]);if(!is_array($perms))$perms=rh24_default_permissions_for_role('field_sales');foreach(['view_territory_book','contact_territory_book'] as $perm)if(!in_array($perm,$perms,true))$perms[]=$perm;$up->execute([rh24_json_encode(array_values(array_unique($perms))),$u['id']]);}
      }catch(Throwable){}
      rh24_setting_set('schema_version','73');rh24_setting_set('db_schema_version','73');
      try{rh24_audit('schema_upgrade','system','v73',['features'=>['territory_directory','branch_contacts','advisor_territory_book','contact_calendar','print_pdf_view','osm_research_import']],'system');}catch(Throwable){}
    } catch(Throwable $e) { /* Idempotentes Upgrade. */ }
}


/* V74 · Deutschlandweites Branchenverzeichnis, serverseitige Suche und Datenqualität */
function rh24_ensure_v74_schema(PDO $db): void {
    try {
      $cols=[];foreach($db->query("SHOW COLUMNS FROM territory_directory")->fetchAll() as $c)$cols[]=(string)$c['Field'];
      $add=[
        'brand'=>"ALTER TABLE territory_directory ADD COLUMN brand VARCHAR(160) NULL AFTER company",
        'operator_name'=>"ALTER TABLE territory_directory ADD COLUMN operator_name VARCHAR(160) NULL AFTER brand",
        'opening_hours'=>"ALTER TABLE territory_directory ADD COLUMN opening_hours VARCHAR(255) NULL AFTER website",
        'mobile'=>"ALTER TABLE territory_directory ADD COLUMN mobile VARCHAR(100) NULL AFTER phone"
      ];
      foreach($add as $col=>$sql){if(!in_array($col,$cols,true)){try{$db->exec($sql);}catch(Throwable $e){}}}
      try{$db->exec("CREATE INDEX idx_territory_dir_zip ON territory_directory(zip)");}catch(Throwable $e){}
      try{$db->exec("CREATE INDEX idx_territory_dir_city ON territory_directory(city)");}catch(Throwable $e){}
      rh24_setting_set('schema_version','74');rh24_setting_set('db_schema_version','74');
      try{rh24_audit('schema_upgrade','system','v74',['features'=>['national_directory','server_search','restaurant_category','contact_quality_filters','nationwide_sync']],'system');}catch(Throwable $e){}
    } catch(Throwable $e) { /* Idempotentes Upgrade. */ }
}
function rh24_territory_directory_national_summary(): array {
    $db=rh24_db();
    $where=rh24_is_admin()?"status<>'archived'":"state_code=? AND status<>'archived'";
    $params=[];
    if(!rh24_is_admin()){$scope=rh24_territory_book_scope();$code=(string)($scope['state_code']??'');if($code==='')return ['total'=>0,'active'=>0,'candidate'=>0,'with_phone'=>0,'with_email'=>0,'complete'=>0,'states'=>[]];$params[]=$code;}
    $q=$db->prepare("SELECT COUNT(*) total,SUM(status='active') active,SUM(status='candidate') candidate,SUM(COALESCE(NULLIF(phone,''),mobile,'')<>'') with_phone,SUM(COALESCE(email,'')<>'') with_email,SUM(COALESCE(NULLIF(phone,''),mobile,'')<>'' AND COALESCE(email,'')<>'' AND COALESCE(street,'')<>'' AND COALESCE(zip,'')<>'' AND COALESCE(city,'')<>'') complete FROM territory_directory WHERE $where");$q->execute($params);$r=$q->fetch()?:[];
    $states=[];
    if(rh24_is_admin()){
      $sq=$db->query("SELECT t.state_code,t.state_name,t.territory_book_no,t.owner_sales_rep_id,s.name owner_name,COUNT(d.id) total,COALESCE(SUM(d.status='active'),0) active,COALESCE(SUM(d.status='candidate'),0) candidate,COALESCE(SUM(d.next_follow_up_at IS NOT NULL),0) followups,COALESCE(SUM(COALESCE(NULLIF(d.phone,''),d.mobile,'')<>''),0) with_phone,COALESCE(SUM(COALESCE(d.email,'')<>''),0) with_email,COALESCE(SUM(COALESCE(NULLIF(d.phone,''),d.mobile,'')<>'' AND COALESCE(d.email,'')<>'' AND COALESCE(d.street,'')<>'' AND COALESCE(d.zip,'')<>'' AND COALESCE(d.city,'')<>''),0) complete,COUNT(DISTINCT d.category) category_count FROM sales_territories t LEFT JOIN territory_directory d ON d.state_code=t.state_code AND d.status<>'archived' LEFT JOIN sales_reps s ON s.id=t.owner_sales_rep_id GROUP BY t.state_code,t.state_name,t.territory_book_no,t.owner_sales_rep_id,s.name ORDER BY t.state_code");
      $states=$sq->fetchAll();
      $catsByState=[];$cq=$db->query("SELECT state_code,category,COUNT(*) n FROM territory_directory WHERE status<>'archived' GROUP BY state_code,category ORDER BY state_code,n DESC");
      foreach($cq->fetchAll() as $c){$sc=(string)$c['state_code'];$catsByState[$sc][(string)$c['category']]=(int)$c['n'];}
      foreach($states as &$x){foreach(['total','active','candidate','followups','with_phone','with_email','complete','category_count'] as $k)$x[$k]=(int)($x[$k]??0);$x['categories']=$catsByState[(string)$x['state_code']]??[];}unset($x);
    }
    return ['total'=>(int)($r['total']??0),'active'=>(int)($r['active']??0),'candidate'=>(int)($r['candidate']??0),'with_phone'=>(int)($r['with_phone']??0),'with_email'=>(int)($r['with_email']??0),'complete'=>(int)($r['complete']??0),'states'=>$states];
}

function rh24_territory_directory_search(array $filters=[]): array {
    $db=rh24_db();$admin=rh24_is_admin();$where=["d.status<>'archived'"];$params=[];
    $stateCode=trim((string)($filters['state_code']??''));
    if(!$admin){$scope=rh24_territory_book_scope();$stateCode=(string)($scope['state_code']??'');if($stateCode==='')return ['rows'=>[],'total'=>0,'limit'=>100,'offset'=>0,'scope'=>$scope];}
    if($stateCode!==''&&$stateCode!=='all'){$stateCode=str_pad((string)(int)$stateCode,2,'0',STR_PAD_LEFT);$where[]='d.state_code=?';$params[]=$stateCode;}
    $status=trim((string)($filters['status']??'all'));if($status!==''&&$status!=='all'){if($status==='followup')$where[]='d.next_follow_up_at IS NOT NULL';else{$where[]='d.status=?';$params[]=$status;}}
    $cat=trim((string)($filters['category']??'all'));if($cat!==''&&$cat!=='all'){$where[]='d.category=?';$params[]=$cat;}
    $contact=trim((string)($filters['contact']??'all'));if($contact==='phone')$where[]="COALESCE(NULLIF(d.phone,''),d.mobile,'')<>''";elseif($contact==='email')$where[]="COALESCE(d.email,'')<>''";elseif($contact==='complete')$where[]="COALESCE(NULLIF(d.phone,''),d.mobile,'')<>'' AND COALESCE(d.email,'')<>'' AND COALESCE(d.street,'')<>'' AND COALESCE(d.zip,'')<>'' AND COALESCE(d.city,'')<>''";elseif($contact==='missing')$where[]="COALESCE(NULLIF(d.phone,''),d.mobile,'')='' OR COALESCE(d.email,'')='' OR COALESCE(d.street,'')='' OR COALESCE(d.zip,'')='' OR COALESCE(d.city,'')=''";
    $q=trim((string)($filters['q']??''));if($q!==''){$like='%'.$q.'%';$where[]='(d.company LIKE ? OR d.brand LIKE ? OR d.operator_name LIKE ? OR d.contact_person LIKE ? OR d.phone LIKE ? OR d.mobile LIKE ? OR d.email LIKE ? OR d.website LIKE ? OR d.street LIKE ? OR d.zip LIKE ? OR d.city LIKE ? OR t.state_name LIKE ? OR t.territory_book_no LIKE ?)';for($i=0;$i<13;$i++)$params[]=$like;}
    $limit=max(25,min(500,(int)($filters['limit']??150)));$offset=max(0,(int)($filters['offset']??0));$whereSql=implode(' AND ',$where);
    $cq=$db->prepare("SELECT COUNT(*) FROM territory_directory d LEFT JOIN sales_territories t ON t.state_code=d.state_code WHERE $whereSql");$cq->execute($params);$total=(int)$cq->fetchColumn();
    $sql="SELECT d.*,t.state_name,t.territory_book_no,s.name assigned_sales_rep_name,(SELECT COUNT(*) FROM territory_contact_logs l WHERE l.directory_id=d.id) contact_count FROM territory_directory d LEFT JOIN sales_territories t ON t.state_code=d.state_code LEFT JOIN sales_reps s ON s.id=d.assigned_sales_rep_id WHERE $whereSql ORDER BY FIELD(d.status,'active','candidate','paused','archived'),t.state_name,d.category,d.city,d.company LIMIT $limit OFFSET $offset";
    $rq=$db->prepare($sql);$rq->execute($params);$rows=$rq->fetchAll();
    foreach($rows as &$r){$r['category_label']=rh24_territory_directory_category_label((string)$r['category']);$r['status_label']=rh24_territory_directory_status_labels()[$r['status']]??$r['status'];$r['contact_count']=(int)$r['contact_count'];foreach(['source_checked_at','verified_at','last_contacted_at','next_follow_up_at','created_at','updated_at'] as $f)$r[$f]=rh24_iso($r[$f]??null);$r['latitude']=$r['latitude']!==null?(float)$r['latitude']:null;$r['longitude']=$r['longitude']!==null?(float)$r['longitude']:null;$r['contact_quality']=((($r['phone']??'')!==''||($r['mobile']??'')!=='')?1:0)+(($r['email']??'')!==''?1:0)+(($r['street']??'')!==''&&($r['zip']??'')!==''&&($r['city']??'')!==''?1:0);}unset($r);
    return ['rows'=>$rows,'total'=>$total,'limit'=>$limit,'offset'=>$offset,'scope'=>$admin?['is_admin'=>true,'state_code'=>$stateCode]:rh24_territory_book_scope()];
}

function rh24_territory_book_scope(?string $requestedState=null): array {
    $db=rh24_db();
    if(rh24_is_admin()){
      $code=trim((string)$requestedState);if($code==='')return ['is_admin'=>true,'state_code'=>'','state_name'=>'','territory_book_no'=>'','sales_rep_id'=>'','sales_rep_name'=>''];
      $code=str_pad((string)(int)$code,2,'0',STR_PAD_LEFT);$q=$db->prepare("SELECT state_code,state_name,territory_book_no,owner_sales_rep_id FROM sales_territories WHERE state_code=?");$q->execute([$code]);$t=$q->fetch();if(!$t)return ['is_admin'=>true,'state_code'=>'','state_name'=>'','territory_book_no'=>'','sales_rep_id'=>'','sales_rep_name'=>''];return ['is_admin'=>true,'state_code'=>$code,'state_name'=>(string)$t['state_name'],'territory_book_no'=>(string)$t['territory_book_no'],'sales_rep_id'=>(string)($t['owner_sales_rep_id']??''),'sales_rep_name'=>rh24_rep_name_by_id($db,(string)($t['owner_sales_rep_id']??''))];
    }
    $repId=rh24_user_sales_rep_id();if($repId==='')return ['is_admin'=>false,'state_code'=>'','state_name'=>'','territory_book_no'=>'','sales_rep_id'=>'','sales_rep_name'=>''];
    $q=$db->prepare("SELECT r.id,r.name,r.state_code,t.state_name,t.territory_book_no FROM sales_reps r LEFT JOIN sales_territories t ON t.state_code=r.state_code WHERE r.id=? AND r.status='active' LIMIT 1");$q->execute([$repId]);$r=$q->fetch();if(!$r||empty($r['state_code']))return ['is_admin'=>false,'state_code'=>'','state_name'=>'','territory_book_no'=>'','sales_rep_id'=>$repId,'sales_rep_name'=>(string)($r['name']??'')];
    return ['is_admin'=>false,'state_code'=>(string)$r['state_code'],'state_name'=>(string)($r['state_name']??''),'territory_book_no'=>(string)($r['territory_book_no']??''),'sales_rep_id'=>$repId,'sales_rep_name'=>(string)($r['name']??'')];
}
function rh24_territory_directory_rows(?string $requestedState=null,bool $includeArchived=false): array {
    $db=rh24_db();$scope=rh24_territory_book_scope($requestedState);$code=(string)($scope['state_code']??'');if($code==='')return [];
    if(!rh24_is_admin() && !rh24_can('view_territory_book'))return [];
    $sql="SELECT d.*,s.name assigned_sales_rep_name,(SELECT COUNT(*) FROM territory_contact_logs l WHERE l.directory_id=d.id) contact_count FROM territory_directory d LEFT JOIN sales_reps s ON s.id=d.assigned_sales_rep_id WHERE d.state_code=?".($includeArchived?'':" AND d.status<>'archived'")." ORDER BY FIELD(d.status,'active','candidate','paused','archived'),d.category,d.city,d.company";
    $q=$db->prepare($sql);$q->execute([$code]);$rows=$q->fetchAll();
    foreach($rows as &$r){$r['category_label']=rh24_territory_directory_category_label((string)$r['category']);$r['status_label']=rh24_territory_directory_status_labels()[$r['status']]??$r['status'];$r['contact_count']=(int)$r['contact_count'];foreach(['source_checked_at','verified_at','last_contacted_at','next_follow_up_at','created_at','updated_at'] as $f)$r[$f]=rh24_iso($r[$f]??null);$r['latitude']=$r['latitude']!==null?(float)$r['latitude']:null;$r['longitude']=$r['longitude']!==null?(float)$r['longitude']:null;}unset($r);return $rows;
}
function rh24_territory_directory_summary(?string $requestedState=null): array {
    $db=rh24_db();$scope=rh24_territory_book_scope($requestedState);$code=(string)($scope['state_code']??'');if($code==='')return ['scope'=>$scope,'total'=>0,'active'=>0,'candidate'=>0,'followups'=>0,'contacted'=>0,'categories'=>[]];
    $q=$db->prepare("SELECT status,category,COUNT(*) n,SUM(last_contacted_at IS NOT NULL) contacted,SUM(next_follow_up_at IS NOT NULL AND next_follow_up_at>=NOW()) followups FROM territory_directory WHERE state_code=? AND status<>'archived' GROUP BY status,category");$q->execute([$code]);$rows=$q->fetchAll();$out=['scope'=>$scope,'total'=>0,'active'=>0,'candidate'=>0,'followups'=>0,'contacted'=>0,'categories'=>[]];
    foreach($rows as $r){$n=(int)$r['n'];$out['total']+=$n;if($r['status']==='active')$out['active']+=$n;if($r['status']==='candidate')$out['candidate']+=$n;$out['followups']+=(int)$r['followups'];$out['contacted']+=(int)$r['contacted'];$k=(string)$r['category'];$out['categories'][$k]=($out['categories'][$k]??0)+$n;}
    $cq=$db->prepare("SELECT SUM(COALESCE(NULLIF(phone,''),mobile,'')<>'') with_phone,SUM(COALESCE(email,'')<>'') with_email,SUM(COALESCE(NULLIF(phone,''),mobile,'')<>'' AND COALESCE(email,'')<>'' AND COALESCE(street,'')<>'' AND COALESCE(zip,'')<>'' AND COALESCE(city,'')<>'') complete FROM territory_directory WHERE state_code=? AND status<>'archived'");$cq->execute([$code]);$cr=$cq->fetch()?:[];$out['with_phone']=(int)($cr['with_phone']??0);$out['with_email']=(int)($cr['with_email']??0);$out['complete']=(int)($cr['complete']??0);return $out;
}
function rh24_territory_contact_history(string $directoryId): array {
    $db=rh24_db();$q=$db->prepare("SELECT l.*,s.name sales_rep_name,u.display_name user_name FROM territory_contact_logs l LEFT JOIN sales_reps s ON s.id=l.sales_rep_id LEFT JOIN users u ON u.id=l.user_id WHERE l.directory_id=? ORDER BY l.contact_at DESC,l.id DESC LIMIT 100");$q->execute([$directoryId]);$rows=$q->fetchAll();foreach($rows as &$r){$r['method_label']=rh24_territory_contact_method_labels()[$r['method']]??$r['method'];$r['result_label']=rh24_territory_contact_result_labels()[$r['result']]??$r['result'];foreach(['contact_at','next_follow_up_at','created_at'] as $f)$r[$f]=rh24_iso($r[$f]??null);}unset($r);return $rows;
}
function rh24_territory_directory_row(string $id): ?array {$db=rh24_db();$q=$db->prepare("SELECT * FROM territory_directory WHERE id=? LIMIT 1");$q->execute([$id]);$r=$q->fetch();return $r?:null;}
function rh24_sales_territories(): array {
    $db=rh24_db();
    $rows=$db->query("SELECT t.*,s.name owner_name,s.employee_no owner_employee_no,s.status owner_status,
      (SELECT COUNT(*) FROM sales_reps m WHERE m.state_code=t.state_code AND m.status='active') AS member_count,
      (SELECT COUNT(*) FROM customers c WHERE c.sales_rep_id IN (SELECT r.id FROM sales_reps r WHERE r.state_code=t.state_code)) AS customer_count,
      (SELECT COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(o.totals_json,'$.gross')) AS DECIMAL(12,2))),0) FROM orders o WHERE COALESCE(NULLIF(o.commission_sales_rep_id,''),o.sales_rep_id) IN (SELECT r2.id FROM sales_reps r2 WHERE r2.state_code=t.state_code) AND o.status<>'cancelled') AS revenue
      FROM sales_territories t LEFT JOIN sales_reps s ON s.id=t.owner_sales_rep_id ORDER BY CAST(t.state_code AS UNSIGNED)")->fetchAll();
    foreach($rows as &$r){$r['member_count']=(int)$r['member_count'];$r['customer_count']=(int)$r['customer_count'];$r['revenue']=(float)$r['revenue'];$r['status']=$r['owner_sales_rep_id']?'fixed':'white';$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}

function rh24_rep_team_info(string $repId): array {
    if($repId==='')return ['is_team_leader'=>false,'team_size'=>0,'team_leader_since'=>null,'bonus_rate'=>0.0];
    $db=rh24_db();$q=$db->prepare("SELECT team_leader_since,sales_role FROM sales_reps WHERE id=?");$q->execute([$repId]);$row=$q->fetch()?:[];$since=$row['team_leader_since']??null;$role=(string)($row['sales_role']??'advisor_active');
    $q=$db->prepare("SELECT COUNT(*) FROM sales_reps WHERE parent_sales_rep_id=? AND status='active'");$q->execute([$repId]);$n=(int)($q->fetchColumn()?:0);
    $isLeader=$role==='team_leader' && (bool)$since;
    $rate=$isLeader?max(0.0,(float)rh24_setting_get('team_leader_bonus_rate','3')):0.0;
    return ['is_team_leader'=>$isLeader,'team_size'=>$n,'team_leader_since'=>rh24_iso($since?:null),'bonus_rate'=>$rate];
}
function rh24_mark_team_leader_if_needed(PDO $db,string $parentId): void {
    if($parentId==='')return;
    $q=$db->prepare("SELECT COUNT(*) FROM sales_reps WHERE parent_sales_rep_id=? AND status='active'");$q->execute([$parentId]);$n=(int)$q->fetchColumn();if($n<=0)return;
    $q=$db->prepare("SELECT sales_role FROM sales_reps WHERE id=? LIMIT 1");$q->execute([$parentId]);$role=(string)($q->fetchColumn()?:'advisor_active');
    // Nur ein Kundenberater wird durch das erste eigene Team automatisch Teamleiter.
    if(in_array($role,['advisor_active','advisor_be','team_leader'],true)){
        $db->prepare("UPDATE sales_reps SET role_since=CASE WHEN sales_role<>'team_leader' THEN NOW() ELSE COALESCE(role_since,NOW()) END,sales_role='team_leader',team_leader_since=COALESCE(team_leader_since,NOW()),updated_at=NOW() WHERE id=?")->execute([$parentId]);
    }
}

function rh24_team_bonus_for_period(string $repId,string $start,string $end,array $rows=[]): array {
    $info=rh24_rep_team_info($repId);
    $rate=(float)$info['bonus_rate'];
    $since=$info['team_leader_since']?strtotime((string)$info['team_leader_since']):0;
    if($rate<=0||!$since){
        return ['rate'=>0.0,'net'=>0.0,'amount'=>0.0,'since'=>null,'team_size'=>0,'members'=>[],'orders'=>[]];
    }

    $db=rh24_db();
    $mq=$db->prepare("SELECT id,employee_no,name,status FROM sales_reps WHERE parent_sales_rep_id=? ORDER BY status='active' DESC,name ASC");
    $mq->execute([$repId]);
    $members=$mq->fetchAll()?:[];
    if(!$members){
        return ['rate'=>$rate,'net'=>0.0,'amount'=>0.0,'since'=>$info['team_leader_since'],'team_size'=>0,'members'=>[],'orders'=>[]];
    }

    $memberMap=[];
    foreach($members as $m){
        $memberMap[(string)$m['id']]=[
            'id'=>(string)$m['id'],
            'employee_no'=>(string)($m['employee_no']??''),
            'name'=>(string)($m['name']??'Verkäufer'),
            'status'=>(string)($m['status']??'active'),
            'net'=>0.0,
            'bonus'=>0.0,
            'order_count'=>0,
        ];
    }

    $memberIds=array_keys($memberMap);
    $effectiveStart=max(strtotime($start),$since);
    $effectiveStartSql=date('Y-m-d H:i:s',$effectiveStart);
    $ph=implode(',',array_fill(0,count($memberIds),'?'));
    $sql="SELECT order_no,COALESCE(NULLIF(commission_sales_rep_id,''),sales_rep_id) credited_rep_id,customer_json,totals_json,created_at
          FROM orders
          WHERE COALESCE(NULLIF(commission_sales_rep_id,''),sales_rep_id) IN ($ph)
            AND payment_status='paid'
            AND status<>'cancelled'
            AND created_at>=?
            AND created_at<?
          ORDER BY created_at ASC,order_no ASC";
    $oq=$db->prepare($sql);
    $oq->execute(array_merge($memberIds,[$effectiveStartSql,$end]));
    $orders=$oq->fetchAll()?:[];

    $eligible=0.0;$orderRows=[];
    foreach($orders as $r){
        $memberId=(string)($r['credited_rep_id']??'');
        if(!isset($memberMap[$memberId]))continue;
        $tot=rh24_json_decode($r['totals_json'],[]);
        $vat=(float)($tot['vat_rate']??rh24_setting_get('vat_rate','19'));
        $net=(float)($tot['subtotal']??0)/(1+$vat/100);
        $net=round(max(0.0,$net),2);
        if($net<=0)continue;
        $bonus=round($net*$rate/100,2);
        $eligible+=$net;
        $memberMap[$memberId]['net']+= $net;
        $memberMap[$memberId]['bonus']+= $bonus;
        $memberMap[$memberId]['order_count']++;
        $cust=rh24_json_decode($r['customer_json'],[]);
        $orderRows[]=[
            'order_no'=>(string)($r['order_no']??''),
            'created_at'=>rh24_iso($r['created_at']??null),
            'sales_rep_id'=>$memberId,
            'sales_rep_name'=>(string)$memberMap[$memberId]['name'],
            'customer_name'=>(string)($cust['name']??'Kunde'),
            'net'=>$net,
            'bonus'=>$bonus,
        ];
    }

    $memberRows=array_values($memberMap);
    foreach($memberRows as &$m){
        $m['net']=round((float)$m['net'],2);
        $m['bonus']=round((float)$m['bonus'],2);
        $m['order_count']=(int)$m['order_count'];
    }
    unset($m);
    usort($memberRows,fn($a,$b)=>(float)$b['net']<=>(float)$a['net']);

    $eligible=round($eligible,2);
    return [
        'rate'=>$rate,
        'net'=>$eligible,
        'amount'=>round($eligible*$rate/100,2),
        'since'=>$info['team_leader_since'],
        'team_size'=>count($memberRows),
        'members'=>$memberRows,
        'orders'=>array_reverse($orderRows),
    ];
}

function rh24_rep_name_by_id(PDO $db,string $repId): string {if($repId==='')return '';$q=$db->prepare("SELECT name FROM sales_reps WHERE id=? LIMIT 1");$q->execute([$repId]);return (string)($q->fetchColumn()?:'');}
function rh24_house_sales_reps(PDO $db): array {
    $spec=['bjoern'=>['user_id'=>'USR-BJOERN','id'=>'AD-HOUSE-BJOERN','employee_no'=>'ZENTRALE-01','name'=>'Björn Hahne'],'jessica'=>['user_id'=>'USR-JESSICA','id'=>'AD-HOUSE-JESSICA','employee_no'=>'ZENTRALE-02','name'=>'Jessica Hahne']];
    $out=[];
    foreach($spec as $key=>$x){
        $uq=$db->prepare("SELECT sales_rep_id FROM users WHERE id=? LIMIT 1");$uq->execute([$x['user_id']]);$userRep=(string)($uq->fetchColumn()?:'');$id='';
        if($userRep!==''){$cq=$db->prepare("SELECT id FROM sales_reps WHERE id=? LIMIT 1");$cq->execute([$userRep]);if($cq->fetchColumn())$id=$userRep;}
        if($id===''){$q=$db->prepare("SELECT id FROM sales_reps WHERE name=? ORDER BY status='active' DESC,created_at LIMIT 1");$q->execute([$x['name']]);$id=(string)($q->fetchColumn()?:'');}
        if($id===''){
            $id=$x['id'];
            $byNo=$db->prepare("SELECT id FROM sales_reps WHERE employee_no=? LIMIT 1");$byNo->execute([$x['employee_no']]);$existingByNo=(string)($byNo->fetchColumn()?:'');if($existingByNo!=='')$id=$existingByNo;
            else $db->prepare("INSERT INTO sales_reps(id,employee_no,name,email,phone,territory,commission_rate,status,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),status='active',updated_at=NOW()")->execute([$id,$x['employee_no'],$x['name'],null,'','Zentrale / Online',0,'active','Hauskonto für automatische Provisionsgutschriften ohne vorherigen Kundenberaterkontakt']);
        }
        $out[$key]=$id;
        try{$db->prepare("UPDATE users SET sales_rep_id=?,updated_at=NOW() WHERE id=? AND (sales_rep_id IS NULL OR sales_rep_id='' OR sales_rep_id=?)")->execute([$id,$x['user_id'],$id]);}catch(Throwable){}
    }
    return $out;
}
function rh24_next_house_commission_rep(PDO $db): array {
    $house=rh24_house_sales_reps($db);
    $q=$db->prepare("SELECT setting_value FROM settings WHERE setting_key='online_commission_rotation_next' FOR UPDATE");$q->execute();$next=strtolower((string)($q->fetchColumn()?:'bjoern'));
    if(!isset($house[$next]))$next='bjoern';
    $chosen=$house[$next];$following=$next==='bjoern'?'jessica':'bjoern';
    $db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('online_commission_rotation_next',?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()")->execute([$following]);
    return ['sales_rep_id'=>$chosen,'name'=>rh24_rep_name_by_id($db,$chosen),'rotation_key'=>$next,'next_rotation'=>$following];
}
function rh24_resolve_sales_attribution(PDO $db,string $customerId,string $channel,string $explicitRepId=''): array {
    $channel=rh24_sales_channel_normalize($channel);$customerRep='';
    if($customerId!==''){
        $q=$db->prepare("SELECT sales_rep_id FROM customers WHERE id=? FOR UPDATE");$q->execute([$customerId]);$customerRep=(string)($q->fetchColumn()?:'');
        if($customerRep!==''){$name=rh24_rep_name_by_id($db,$customerRep);return ['sales_channel'=>$channel,'sales_rep_id'=>$customerRep,'commission_sales_rep_id'=>$customerRep,'commission_sales_rep_name'=>$name,'commission_attribution'=>'returning_customer','commission_note'=>rh24_sales_channel_label($channel).' gekauft · Provisionsgutschrift an bestehenden Kundenberater '.$name,'customer_relationship'=>true];}
    }
    $explicitRepId=trim($explicitRepId);
    if($explicitRepId!==''){
        $q=$db->prepare("SELECT id,name,employee_no FROM sales_reps WHERE id=? AND status='active' LIMIT 1");$q->execute([$explicitRepId]);$rep=$q->fetch();
        if($rep && !str_starts_with((string)($rep['employee_no']??''),'ZENTRALE-')){
            if($customerId!=='')$db->prepare("UPDATE customers SET sales_rep_id=?,advisor_assigned_at=NOW(),advisor_assignment_source=?,updated_at=NOW() WHERE id=? AND (sales_rep_id IS NULL OR sales_rep_id='')")->execute([$explicitRepId,$channel==='advisor'?'kundenberater_kontakt':'direkter_kontakt',$customerId]);
            $name=(string)$rep['name'];return ['sales_channel'=>$channel,'sales_rep_id'=>$explicitRepId,'commission_sales_rep_id'=>$explicitRepId,'commission_sales_rep_name'=>$name,'commission_attribution'=>'direct_advisor','commission_note'=>rh24_sales_channel_label($channel).' · Kundenberater '.$name.' · volle Provisionsgutschrift','customer_relationship'=>true];
        }
    }
    $rot=rh24_next_house_commission_rep($db);
    return ['sales_channel'=>$channel,'sales_rep_id'=>'','commission_sales_rep_id'=>(string)$rot['sales_rep_id'],'commission_sales_rep_name'=>(string)$rot['name'],'commission_attribution'=>'house_rotation','commission_note'=>rh24_sales_channel_label($channel).' · ohne bestehenden Beraterkontakt · automatische Provisionsgutschrift an '.(string)$rot['name'],'customer_relationship'=>false];
}

function rh24_production_users(): array {
    $rows=rh24_db()->query("SELECT id,username,display_name,email,status,last_login,created_at,updated_at FROM users WHERE role='production' ORDER BY status='active' DESC,display_name")->fetchAll();
    foreach($rows as &$r){$r['last_login']=rh24_iso($r['last_login']??null);$r['created_at']=rh24_iso($r['created_at']??null);$r['updated_at']=rh24_iso($r['updated_at']??null);}unset($r);return $rows;
}
function rh24_production_activity(int $limit=300): array {
    $limit=max(1,min(1000,$limit));
    $rows=rh24_db()->query("SELECT id,order_no,event_type,production_step,progress,station,assignee_user_id,worker_user_id,worker_name,worker_role,note,created_at FROM production_activity ORDER BY created_at DESC LIMIT ".$limit)->fetchAll();
    foreach($rows as &$r){$r['id']=(int)$r['id'];$r['progress']=(int)$r['progress'];$r['created_at']=rh24_iso($r['created_at']);}unset($r);return $rows;
}

function rh24_marketplace_allowed_categories(): array {
    return ['Räucheröfen & Räucherschränke','Smoker','Grills','Räucherhaken & Halter','Räucherzubehör','Grill- & BBQ-Zubehör','Thermometer & Messtechnik','Räucherholz, Chunks & Räuchermehl','Brennstoff- & Feuerzubehör','Ersatzteile & Werkzeuge'];
}
function rh24_marketplace_food_violation(string $text): bool {
    foreach([
      '/\b(?:lebensmittel|nahrungsmittel|zum\s+verzehr|essbar|verfallsdatum|mhd|haltbar\s+bis)\b/iu',
      '/\b(?:räucherlachs|rauchlachs|grillfleisch|bratwurst|wurstpaket|fleischpaket|fischpaket|räucherlauge|pökelsalz|marinade)\b/iu',
      '/\bgeräuchert(?:e|er|es|en)?\s+(?:fisch|fleisch|wurst|käse)\b/iu',
      '/\bgewürz(?:e|mischung|mischungen)?\b/iu'
    ] as $rx){ if(preg_match($rx,$text)) return true; }
    return false;
}

function rh24_marketplace_admin_data(): array {
    $db=rh24_db();
    try{$db->exec("UPDATE market_users SET membership_status='expired',updated_at=NOW() WHERE membership_status='active' AND membership_expires_at IS NOT NULL AND membership_expires_at<NOW()");}catch(Throwable){}
    $users=$db->query("SELECT id,email,display_name,phone,zip,city,email_verified_at,status,membership_status,membership_started_at,membership_expires_at,membership_order_no,terms_version,terms_accepted_at,created_at,updated_at FROM market_users ORDER BY created_at DESC LIMIT 500")->fetchAll();
    $listings=$db->query("SELECT l.id,l.user_id,l.kind,l.title,l.description,l.category,l.condition_label,l.price,l.shipping,l.zip,l.city,l.status,l.views,l.created_at,l.updated_at,u.display_name seller_name,u.email seller_email FROM market_listings l LEFT JOIN market_users u ON u.id=l.user_id WHERE l.status<>'deleted' ORDER BY FIELD(l.status,'pending','published','paused','sold','rejected'),l.updated_at DESC LIMIT 500")->fetchAll();
    $reports=$db->query("SELECT r.*,l.title listing_title FROM market_reports r LEFT JOIN market_listings l ON l.id=r.listing_id ORDER BY FIELD(r.status,'open','closed'),r.created_at DESC LIMIT 200")->fetchAll();
    $stats=['users'=>count($users),'active_members'=>0,'pending_members'=>0,'pending_listings'=>0,'published_listings'=>0,'open_reports'=>0];
    foreach($users as $u){if(($u['membership_status']??'')==='active')$stats['active_members']++;if(($u['membership_status']??'')==='pending')$stats['pending_members']++;}
    foreach($listings as $l){if(($l['status']??'')==='pending')$stats['pending_listings']++;if(($l['status']??'')==='published')$stats['published_listings']++;}
    foreach($reports as $r){if(($r['status']??'')==='open')$stats['open_reports']++;}
    return ['stats'=>$stats,'users'=>$users,'listings'=>$listings,'reports'=>$reports,'fee_gross'=>19.99,'listing_limit'=>10];
}

function rh24_publication_registry_ensure(?PDO $db=null): void {
    $db=$db?:rh24_db();
    $db->exec("CREATE TABLE IF NOT EXISTS product_publications (product_id VARCHAR(80) NOT NULL PRIMARY KEY,published_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,KEY idx_product_publications_updated (updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function rh24_product_publication_set(PDO $db,string $id,bool $published): void {
    rh24_publication_registry_ensure($db);
    if($published){
        $db->prepare("INSERT INTO product_publications(product_id,published_at,updated_at) VALUES(?,NOW(),NOW()) ON DUPLICATE KEY UPDATE updated_at=NOW()")->execute([$id]);
    }else{
        $db->prepare('DELETE FROM product_publications WHERE product_id=?')->execute([$id]);
    }
}

function rh24_catalog(): array {
    if(!rh24_db_configured()) return rh24_default_catalog();
    try {
        $rows=rh24_db()->query("SELECT p.*,i.stock,i.minimum FROM products p LEFT JOIN inventory i ON i.id=p.id ORDER BY p.category,p.name")->fetchAll();
        if(!$rows) return rh24_default_catalog();
        $out=[];
        foreach($rows as $r){
            $item=[
              'sku'=>(string)($r['sku']??''),'article_no'=>(string)($r['article_no']??''),'barcode'=>(string)($r['barcode']??''),'name'=>(string)$r['name'],'category'=>(string)($r['category']??'Sonstiges'),'system_product'=>array_key_exists((string)$r['id'],rh24_default_catalog()),
              'base'=>(float)$r['base_price'],'unit'=>(string)$r['unit'],'type'=>(string)$r['product_type'],
              'product_weight_g'=>(int)($r['product_weight_g']??0),'shipping_weight_g'=>(int)($r['shipping_weight_g']??0),
              'stock'=>(int)($r['stock']??0),'minimum'=>(int)($r['minimum']??0),'status'=>(string)($r['status']??'active'),
              'description'=>(string)($r['description']??''),'image'=>(string)($r['image_path']??''),'shop_visible'=>(bool)($r['shop_visible']??1),
              'is_popular'=>(bool)($r['is_popular']??0),'is_offer'=>(bool)($r['is_offer']??0),'is_new'=>(bool)($r['is_new']??0),'new_until'=>(string)($r['new_until']??''),'published_at'=>(string)($r['published_at']??''),'old_price'=>(float)($r['old_price']??0),'sale_price'=>(float)($r['sale_price']??0),
              'sale_start_at'=>(string)($r['sale_start_at']??''),'sale_end_at'=>(string)($r['sale_end_at']??''),'price_basis'=>(string)($r['price_basis']??'auto'),'pack_quantity'=>max(1,(int)($r['pack_quantity']??1)),
              'short_description'=>(string)($r['short_description']??''),'features'=>rh24_json_decode((string)($r['features_json']??''),[]),'features_rich'=>(string)($r['features_rich']??''),'seo_title'=>(string)($r['seo_title']??''),'seo_description'=>(string)($r['seo_description']??''),'seo_keywords'=>(string)($r['seo_keywords']??''),
              'cross_sell_enabled'=>(bool)($r['cross_sell_enabled']??0),'cross_sell_title'=>(string)($r['cross_sell_title']??'Passt perfekt dazu'),'cross_sell_max'=>max(1,min(8,(int)($r['cross_sell_max']??4))),'cross_sell_auto'=>(bool)($r['cross_sell_auto']??0),'cross_sell_reciprocal'=>(bool)($r['cross_sell_reciprocal']??0),'cross_sell'=>rh24_json_decode((string)($r['cross_sell_json']??''),[]),
              'content_quantity'=>(float)($r['content_quantity']??1),'content_unit'=>(string)($r['content_unit']??'Stück'),'package_type'=>(string)($r['package_type']??'Einzelartikel'),'package_length_cm'=>(float)($r['package_length_cm']??0),'package_width_cm'=>(float)($r['package_width_cm']??0),'package_height_cm'=>(float)($r['package_height_cm']??0)
            ];
            $item['sale_active']=rh24_sale_is_active($item);$item['new_active']=rh24_product_is_new_active($item);$item['effective_price']=rh24_effective_base($item);$item['unit_price']=rh24_unit_price_meta($item,$item['effective_price']);$out[(string)$r['id']]=$item;
        }
        return $out;
    } catch(Throwable) { return rh24_default_catalog(); }
}
function rh24_resolve_price(string $id,array $meta=[]): float {
    $catalog=rh24_catalog(); if(!isset($catalog[$id])) throw new InvalidArgumentException('Unbekannter Artikel: '.$id);
    if(($catalog[$id]['status']??'active')!=='active') throw new InvalidArgumentException('Artikel ist derzeit nicht aktiv: '.$id);
    $effective=rh24_effective_base($catalog[$id]);
    if(($catalog[$id]['type']??'')!=='hook') return round($effective,2);
    $length=(string)($meta['length']??'');
    $lengthPrices=['std'=>['12 cm'=>12.90,'14 cm'=>14.80,'18 cm'=>15.80,'20 cm'=>16.80,'24 cm'=>18.80],'aal'=>['12 cm'=>12.90,'14 cm'=>14.80,'18 cm'=>15.80,'20 cm'=>16.80,'24 cm'=>18.80],'kralle'=>['18 cm'=>18.90,'20 cm'=>19.90,'24 cm'=>21.90],'filet'=>['12 cm'=>15.90,'14 cm'=>17.80,'18 cm'=>18.80,'20 cm'=>19.80,'24 cm'=>21.80],'doppel'=>['12 cm'=>15.90,'14 cm'=>17.80,'18 cm'=>18.80,'20 cm'=>19.80,'24 cm'=>21.80],'ultra'=>['20 cm'=>19.90,'22 cm'=>23.90,'24 cm'=>24.90]];
    $normalBase=(float)$catalog[$id]['base'];
    $base=$lengthPrices[$id][$length]??$normalBase;
    if(rh24_sale_is_active($catalog[$id])) $base=max(0,$base-$normalBase+$effective);
    $base+=['VA'=>0.0,'V2A'=>3.99,'V4A'=>7.99][(string)($meta['material']??'VA')]??0.0;
    $base+=(string)($meta['tip']??'standard')==='extra'?2.00:0.0;
    return round($base,2);
}

function rh24_normalize_phone(string $value): string {
    $digits=preg_replace('/\D+/','',$value)??'';if(str_starts_with($digits,'0049'))$digits='0'.substr($digits,4);elseif(str_starts_with($digits,'49')&&strlen($digits)>10)$digits='0'.substr($digits,2);return $digits;
}
function rh24_customer_upsert(array $customer,string $orderNo=''): string {
    $db=rh24_db();$email=strtolower(trim((string)($customer['email']??'')));if($email==='')return '';
    $st=$db->prepare('SELECT id FROM customers WHERE LOWER(email)=? LIMIT 1');$st->execute([$email]);$id=(string)($st->fetchColumn()?:'');
    // Wiederkehrende Kunden zusätzlich über eine eindeutig passende Telefonnummer erkennen.
    if($id===''){
        $phoneNorm=rh24_normalize_phone((string)($customer['phone']??''));
        if(strlen($phoneNorm)>=7){
            $candidates=$db->query("SELECT id,phone FROM customers WHERE phone<>'' ORDER BY updated_at DESC LIMIT 5000")->fetchAll();$matches=[];
            foreach($candidates as $row)if(rh24_normalize_phone((string)($row['phone']??''))===$phoneNorm)$matches[]=(string)$row['id'];
            $matches=array_values(array_unique($matches));if(count($matches)===1)$id=$matches[0];
        }
    }
    if($id==='')$id=rh24_random_id('C-');
    $sql='INSERT INTO customers(id,name,email,phone,company,street,zip,city,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),email=VALUES(email),phone=VALUES(phone),company=VALUES(company),street=VALUES(street),zip=VALUES(zip),city=VALUES(city),updated_at=NOW()';
    $db->prepare($sql)->execute([$id,trim((string)($customer['name']??'')),$email,trim((string)($customer['phone']??'')),trim((string)($customer['company']??'')),trim((string)($customer['street']??'')),trim((string)($customer['zip']??'')),trim((string)($customer['city']??'')),'']);
    return $id;
}
function rh24_inventory_seed(): void {
    if(!rh24_db_configured()) return;
    $st=rh24_db()->prepare('INSERT IGNORE INTO inventory(id,name,stock,minimum,unit,updated_at) VALUES(?,?,?,?,?,NOW())');
    foreach(rh24_catalog() as $id=>$p){ if($id==='prototype-project') continue; $st->execute([$id,$p['name'],(int)($p['stock']??0),50,$p['unit']]); }
}
function rh24_orders(): array {
    $db=rh24_db();$rows=$db->query("SELECT o.*,sr.name sales_rep_name,cr.name commission_sales_rep_name FROM orders o LEFT JOIN sales_reps sr ON sr.id=o.sales_rep_id LEFT JOIN sales_reps cr ON cr.id=COALESCE(NULLIF(o.commission_sales_rep_id,''),o.sales_rep_id) ORDER BY o.created_at DESC")->fetchAll(); $out=[];
    foreach($rows as $r){$channel=rh24_sales_channel_normalize((string)($r['sales_channel']??''),(string)($r['source']??''));$out[]=['id'=>$r['order_no'],'order_no'=>$r['order_no'],'source'=>$r['source'],'sales_channel'=>$channel,'sales_channel_label'=>rh24_sales_channel_label($channel),'sales_rep_id'=>$r['sales_rep_id']??'','sales_rep_name'=>$r['sales_rep_name']??'','commission_sales_rep_id'=>$r['commission_sales_rep_id']??($r['sales_rep_id']??''),'commission_sales_rep_name'=>$r['commission_sales_rep_name']??'','commission_attribution'=>$r['commission_attribution']??'','commission_attribution_label'=>rh24_commission_attribution_label((string)($r['commission_attribution']??'')),'commission_note'=>$r['commission_note']??'','commission_assigned_at'=>rh24_iso($r['commission_assigned_at']??null),'consultation_id'=>$r['consultation_id']??'','status'=>$r['status'],'status_label'=>$r['status_label'],'payment_status'=>$r['payment_status'],'payment_method'=>$r['payment_method'],'carrier'=>$r['carrier'],'tracking'=>$r['tracking'],'internal_note'=>$r['internal_note']??'','production_priority'=>$r['production_priority']??'normal','production_due_at'=>rh24_iso($r['production_due_at']??null),'production_station'=>$r['production_station']??'','production_step'=>$r['production_step']??'planung','production_progress'=>(int)($r['production_progress']??0),'production_assignee'=>$r['production_assignee']??'','production_assignee_user_id'=>$r['production_assignee_user_id']??'','production_last_worker_id'=>$r['production_last_worker_id']??'','production_last_worker_name'=>$r['production_last_worker_name']??'','production_last_work_at'=>rh24_iso($r['production_last_work_at']??null),'production_note'=>$r['production_note']??'','production_started_at'=>rh24_iso($r['production_started_at']??null),'production_finished_at'=>rh24_iso($r['production_finished_at']??null),'customer'=>rh24_json_decode($r['customer_json'],[]),'items'=>rh24_json_decode($r['items_json'],[]),'totals'=>rh24_json_decode($r['totals_json'],[]),'customer_note'=>$r['customer_note']??'','history'=>rh24_json_decode($r['history_json'],[]),'created_at'=>rh24_iso($r['created_at']),'updated_at'=>rh24_iso($r['updated_at'])];}
    return $out;
}
function rh24_prototypes(): array {
    $rows=rh24_db()->query('SELECT * FROM prototypes ORDER BY created_at DESC')->fetchAll(); $out=[];
    foreach($rows as $r){$out[]=['id'=>$r['reference'],'reference'=>$r['reference'],'order_no'=>$r['order_no']??'','source'=>$r['source'],'project_name'=>$r['project_name'],'summary'=>$r['summary']??'','customer'=>rh24_json_decode($r['customer_json'],[]),'fields'=>rh24_json_decode($r['fields_json'],[]),'files'=>rh24_json_decode($r['files_json'],[]),'status'=>$r['status'],'status_label'=>$r['status_label'],'payment_status'=>$r['payment_status'],'internal_note'=>$r['internal_note']??'','tracking'=>$r['tracking']??'','history'=>rh24_json_decode($r['history_json'],[]),'created_at'=>rh24_iso($r['created_at']),'updated_at'=>rh24_iso($r['updated_at'])];}
    return $out;
}
function rh24_customers(): array {
    $db=rh24_db();$rows=$db->query("SELECT c.*,s.name sales_rep_name FROM customers c LEFT JOIN sales_reps s ON s.id=c.sales_rep_id ORDER BY c.updated_at DESC")->fetchAll(); $out=[];$ost=$db->prepare('SELECT order_no FROM orders WHERE customer_id=? ORDER BY created_at DESC');
    foreach($rows as $r){
      $ost->execute([$r['id']]);$orders=array_map(fn($x)=>$x['order_no'],$ost->fetchAll());
      $out[]=['id'=>$r['id'],'name'=>$r['name'],'customer_type'=>$r['customer_type']??'private','status'=>$r['status']??'active','salutation'=>$r['salutation']??'','email'=>$r['email'],'phone'=>$r['phone'],'mobile'=>$r['mobile']??'','website'=>$r['website']??'','company'=>$r['company'],'street'=>$r['street'],'zip'=>$r['zip'],'city'=>$r['city'],'country'=>$r['country']??'Deutschland','vat_id'=>$r['vat_id']??'','tax_no'=>$r['tax_no']??'','payment_method'=>$r['payment_method']??'','payment_terms_days'=>(int)($r['payment_terms_days']??7),'discount_percent'=>(float)($r['discount_percent']??0),'preferred_contact'=>$r['preferred_contact']??'email','source'=>$r['source']??'Orgaboard','tags'=>rh24_json_decode($r['tags_json']??'[]',[]),'billing'=>rh24_json_decode($r['billing_json']??'{}',[]),'shipping'=>rh24_json_decode($r['shipping_json']??'{}',[]),'payment_note'=>$r['payment_note']??'','consent_note'=>$r['consent_note']??'','notes'=>$r['notes']??'','sales_rep_id'=>$r['sales_rep_id']??'','sales_rep_name'=>$r['sales_rep_name']??'','advisor_assigned_at'=>rh24_iso($r['advisor_assigned_at']??null),'advisor_assignment_source'=>$r['advisor_assignment_source']??'','newsletter_status'=>$r['newsletter_status']??'none','newsletter_consent_at'=>rh24_iso($r['newsletter_consent_at']??null),'newsletter_confirmed_at'=>rh24_iso($r['newsletter_confirmed_at']??null),'newsletter_unsubscribed_at'=>rh24_iso($r['newsletter_unsubscribed_at']??null),'contact_verified_at'=>rh24_iso($r['contact_verified_at']??null),'contact_verified_by'=>$r['contact_verified_by']??'','contact_verification_note'=>$r['contact_verification_note']??'','orders'=>$orders,'created_at'=>rh24_iso($r['created_at']),'updated_at'=>rh24_iso($r['updated_at'])];
    }return $out;
}
function rh24_dealers(): array {
    $rows=rh24_db()->query('SELECT * FROM dealers ORDER BY company')->fetchAll();
    foreach($rows as &$r){
        $r['discount']=(float)$r['discount'];$r['visit_interval_days']=(int)($r['visit_interval_days']??14);
        foreach(['last_visit_at','next_visit_at','created_at','updated_at'] as $f)$r[$f]=rh24_iso($r[$f]??null);
    }unset($r);return $rows;
}
function rh24_dealer_order_history(array $dealer): array {
    $db=rh24_db();$cid=trim((string)($dealer['customer_id']??''));$email=strtolower(trim((string)($dealer['email']??'')));$company=trim((string)($dealer['company']??''));
    $parts=[];$vals=[];
    if($cid!==''){$parts[]='customer_id=?';$vals[]=$cid;}
    if($email!==''){$parts[]="LOWER(JSON_UNQUOTE(JSON_EXTRACT(customer_json,'$.email')))=?";$vals[]=$email;}
    if($company!==''){$parts[]="JSON_UNQUOTE(JSON_EXTRACT(customer_json,'$.company'))=?";$vals[]=$company;}
    if(!$parts)return ['orders'=>[],'products'=>[],'last_purchase_at'=>null,'total_net'=>0.0,'total_gross'=>0.0];
    $q=$db->prepare("SELECT order_no,items_json,totals_json,created_at FROM orders WHERE payment_status='paid' AND status<>'cancelled' AND (".implode(' OR ',$parts).") ORDER BY created_at DESC LIMIT 100");
    $q->execute($vals);$rows=$q->fetchAll();$orders=[];$products=[];$netTotal=0.0;$grossTotal=0.0;$last=null;
    foreach($rows as $r){
        $items=rh24_json_decode($r['items_json']??'[]',[]);$tot=rh24_json_decode($r['totals_json']??'{}',[]);$net=(float)($tot['net']??0);$gross=(float)($tot['gross']??0);$netTotal+=$net;$grossTotal+=$gross;
        $at=rh24_iso($r['created_at']);if($last===null)$last=$at;
        $cleanItems=[];
        foreach($items as $it){
            if(!is_array($it))continue;$pid=(string)($it['id']??'');$name=(string)($it['name']??$pid);$article=(string)($it['article_no']??'');$qty=max(0,(int)($it['qty']??0));$unit=(float)($it['unit_price']??0);$line=(float)($it['line_total']??($unit*$qty));
            $cleanItems[]=['id'=>$pid,'article_no'=>$article,'name'=>$name,'qty'=>$qty,'unit_price'=>$unit,'line_total'=>$line];
            $key=$pid!==''?$pid:($article!==''?$article:$name);
            if(!isset($products[$key]))$products[$key]=['id'=>$pid,'article_no'=>$article,'name'=>$name,'qty'=>0,'order_count'=>0,'last_qty'=>0,'last_order_at'=>$at,'gross'=>0.0];
            $products[$key]['qty']+=$qty;$products[$key]['order_count']++;$products[$key]['gross']+=$line;
            if($products[$key]['last_order_at']===$at)$products[$key]['last_qty']=$qty;
        }
        $orders[]=['order_no'=>(string)$r['order_no'],'created_at'=>$at,'net'=>$net,'gross'=>$gross,'items'=>$cleanItems];
    }
    $prod=array_values($products);usort($prod,fn($a,$b)=>($b['order_count']<=>$a['order_count'])?:($b['qty']<=>$a['qty']));
    foreach($prod as &$pr){$days=$pr['last_order_at']?max(0,(int)floor((time()-strtotime((string)$pr['last_order_at']))/86400)):9999;$pr['days_since_last']=$days;$pr['repeat_prompt']=$days>=10||$pr['order_count']>=2;}unset($pr);
    return ['orders'=>array_slice($orders,0,20),'products'=>array_slice($prod,0,12),'last_purchase_at'=>$last,'total_net'=>round($netTotal,2),'total_gross'=>round($grossTotal,2)];
}
function rh24_dealer_route_data(): array {
    $db=rh24_db();$isAdmin=rh24_is_admin();$repId=rh24_user_sales_rep_id();
    if($isAdmin){$rows=$db->query("SELECT * FROM dealers WHERE status='active' ORDER BY COALESCE(next_visit_at,'2099-12-31'),company")->fetchAll();}
    else{$q=$db->prepare("SELECT * FROM dealers WHERE status='active' AND sales_rep_id=? ORDER BY COALESCE(next_visit_at,'2099-12-31'),company");$q->execute([$repId]);$rows=$q->fetchAll();}
    $visQ=$db->prepare("SELECT id,sales_rep_id,planned_at,completed_at,status,outcome,notes,next_visit_at,created_by,created_at FROM dealer_visits WHERE dealer_id=? ORDER BY COALESCE(completed_at,planned_at) DESC LIMIT 12");
    $out=[];
    foreach($rows as $r){
        $visQ->execute([(string)$r['id']]);$vis=$visQ->fetchAll();foreach($vis as &$v){foreach(['planned_at','completed_at','next_visit_at','created_at'] as $f)$v[$f]=rh24_iso($v[$f]??null);}unset($v);
        $history=rh24_dealer_order_history($r);
        $out[]=['id'=>(string)$r['id'],'customer_id'=>(string)($r['customer_id']??''),'company'=>(string)$r['company'],'contact'=>(string)($r['contact']??''),'email'=>(string)($r['email']??''),'phone'=>(string)($r['phone']??''),'street'=>(string)($r['street']??''),'zip'=>(string)($r['zip']??''),'city'=>(string)($r['city']??''),'sales_rep_id'=>(string)($r['sales_rep_id']??''),'tier'=>(string)($r['tier']??''),'discount'=>(float)($r['discount']??0),'visit_interval_days'=>(int)($r['visit_interval_days']??14),'last_visit_at'=>rh24_iso($r['last_visit_at']??null),'next_visit_at'=>rh24_iso($r['next_visit_at']??null),'last_visit_note'=>(string)($r['last_visit_note']??''),'purchase_history'=>$history,'visits'=>$vis];
    }
    return ['dealers'=>$out,'interval_days'=>14,'generated_at'=>date('c')];
}
function rh24_sales_reps(): array {
    $db=rh24_db();
    $rows=$db->query("SELECT s.*,
      (SELECT COUNT(*) FROM customers c WHERE c.sales_rep_id=s.id) AS customer_count,
      (SELECT COUNT(*) FROM orders o WHERE COALESCE(NULLIF(o.commission_sales_rep_id,''),o.sales_rep_id)=s.id AND o.status<>'cancelled') AS order_count,
      (SELECT COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(o.totals_json,'$.gross')) AS DECIMAL(12,2))),0) FROM orders o WHERE COALESCE(NULLIF(o.commission_sales_rep_id,''),o.sales_rep_id)=s.id AND o.status<>'cancelled') AS revenue,
      (SELECT u.id FROM users u WHERE u.sales_rep_id=s.id LIMIT 1) AS user_id,
      (SELECT u.username FROM users u WHERE u.sales_rep_id=s.id LIMIT 1) AS username
      FROM sales_reps s ORDER BY s.status='active' DESC,s.name")->fetchAll();
    foreach($rows as &$r){$r['commission_rate']=(float)$r['commission_rate'];$r['customer_count']=(int)$r['customer_count'];$r['order_count']=(int)$r['order_count'];$r['revenue']=(float)$r['revenue'];$r['sales_role']=(string)($r['sales_role']??'advisor_active');$r['sales_role_label']=rh24_sales_role_label($r['sales_role']);$r['team']=rh24_rep_team_info((string)$r['id']);$r['parent_name']='';$r['parent_role']='';$r['parent_role_label']='';if(!empty($r['parent_sales_rep_id'])){$pq=$db->prepare('SELECT name,sales_role FROM sales_reps WHERE id=?');$pq->execute([$r['parent_sales_rep_id']]);$pr=$pq->fetch()?:[];$r['parent_name']=(string)($pr['name']??'');$r['parent_role']=(string)($pr['sales_role']??'');$r['parent_role_label']=$r['parent_role']!==''?rh24_sales_role_label($r['parent_role']):'';}$r['stats_current']=rh24_sales_rep_stats((string)$r['id']);$r['role_since']=rh24_iso($r['role_since']??null);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_consultations(): array {
    $rows=rh24_db()->query('SELECT * FROM consultations ORDER BY created_at DESC LIMIT 250')->fetchAll();
    foreach($rows as &$r){$r['needs']=rh24_json_decode($r['needs_json'],[]);$r['recommendations']=rh24_json_decode($r['recommendation_json'],[]);unset($r['needs_json'],$r['recommendation_json']);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_inventory(): array { $rows=rh24_db()->query('SELECT * FROM inventory ORDER BY name')->fetchAll(); foreach($rows as &$r){$r['stock']=(int)$r['stock'];$r['minimum']=(int)$r['minimum'];$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows; }
function rh24_reviews(): array { $rows=rh24_db()->query('SELECT * FROM reviews ORDER BY created_at DESC')->fetchAll(); foreach($rows as &$r){$r['rating']=(int)$r['rating'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows; }
function rh24_content(): array { $rows=rh24_db()->query("SELECT * FROM content ORDER BY FIELD(type,'Rezept','Anleitung','Ratgeber'),title")->fetchAll(); foreach($rows as &$r){$r['body_data']=rh24_json_decode((string)($r['body']??''),['text'=>(string)($r['body']??'')]);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows; }
function rh24_ai(): array { $rows=rh24_db()->query('SELECT label,stat_count AS count FROM ai_stats ORDER BY stat_count DESC,label')->fetchAll(); foreach($rows as &$r)$r['count']=(int)$r['count'];unset($r);return ['questions'=>$rows]; }

if (rh24_db_configured()) { try { rh24_inventory_seed(); } catch(Throwable) {} }


/* V70.1 · isoliertes HD-Hahne-Klicktracking. Kein Eingriff in den normalen DB-Start. */
function rh24_hd_click_table_ready(PDO $db): bool {
    static $ready=null;
    if($ready!==null) return $ready;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS external_link_clicks (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
          click_key VARCHAR(72) NOT NULL,
          target VARCHAR(60) NOT NULL,
          page VARCHAR(255) NOT NULL DEFAULT '',
          created_at DATETIME NOT NULL,
          UNIQUE KEY uq_external_click_key (click_key),
          KEY idx_external_click_target_date (target,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $ready=true;
    } catch(Throwable $e) { $ready=false; }
    return $ready;
}
function rh24_hd_hahne_click_stats(): array {
    $empty=['total'=>0,'today'=>0,'last_30_days'=>0,'last_click_at'=>null,'last_page'=>'','available'=>false];
    try {
        $db=rh24_db();
        if(!rh24_hd_click_table_ready($db)) return $empty;
        $total=(int)$db->query("SELECT COUNT(*) FROM external_link_clicks WHERE target='hd_hahne'")->fetchColumn();
        $today=(int)$db->query("SELECT COUNT(*) FROM external_link_clicks WHERE target='hd_hahne' AND created_at>=CURDATE()")->fetchColumn();
        $last30=(int)$db->query("SELECT COUNT(*) FROM external_link_clicks WHERE target='hd_hahne' AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
        $last=$db->query("SELECT page,created_at FROM external_link_clicks WHERE target='hd_hahne' ORDER BY id DESC LIMIT 1")->fetch();
        return ['total'=>$total,'today'=>$today,'last_30_days'=>$last30,'last_click_at'=>rh24_iso($last['created_at']??null),'last_page'=>(string)($last['page']??''),'available'=>true];
    } catch(Throwable $e) { return $empty; }
}


/* V75 · Fahrtenbuch, Termine, Google-Maps-Routenplanung und Finanzamt-/ELSTER-Export */
function rh24_ensure_v75_schema(PDO $db): void {
    try {
      $db->exec("CREATE TABLE IF NOT EXISTS trip_vehicles (
        id VARCHAR(40) PRIMARY KEY,
        sales_rep_id VARCHAR(40) NOT NULL,
        label VARCHAR(140) NOT NULL,
        license_plate VARCHAR(40) NULL,
        make_model VARCHAR(180) NULL,
        base_address VARCHAR(255) NULL,
        odometer_start DECIMAL(12,1) NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_by VARCHAR(40) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        KEY idx_trip_vehicle_rep (sales_rep_id,status)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      $db->exec("CREATE TABLE IF NOT EXISTS trip_log (
        id VARCHAR(40) PRIMARY KEY,
        sales_rep_id VARCHAR(40) NOT NULL,
        vehicle_id VARCHAR(40) NOT NULL,
        trip_date DATE NOT NULL,
        start_time TIME NULL,
        end_time TIME NULL,
        trip_type VARCHAR(24) NOT NULL DEFAULT 'business',
        start_address VARCHAR(255) NOT NULL,
        destination_address VARCHAR(255) NOT NULL,
        purpose VARCHAR(255) NULL,
        business_partner VARCHAR(255) NULL,
        start_odometer DECIMAL(12,1) NOT NULL,
        end_odometer DECIMAL(12,1) NOT NULL,
        distance_km DECIMAL(12,1) NOT NULL DEFAULT 0,
        detour_reason VARCHAR(255) NULL,
        appointment_type VARCHAR(40) NULL,
        appointment_ref VARCHAR(80) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'draft',
        finalized_at DATETIME NULL,
        row_hash CHAR(64) NULL,
        created_by VARCHAR(40) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        KEY idx_trip_log_rep_date (sales_rep_id,trip_date),
        KEY idx_trip_log_vehicle_date (vehicle_id,trip_date),
        KEY idx_trip_log_status (status,trip_date)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      $db->exec("CREATE TABLE IF NOT EXISTS trip_log_revisions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        trip_id VARCHAR(40) NOT NULL,
        version_no INT NOT NULL DEFAULT 1,
        change_reason VARCHAR(255) NOT NULL,
        before_json LONGTEXT NOT NULL,
        after_json LONGTEXT NOT NULL,
        changed_by VARCHAR(40) NULL,
        changed_at DATETIME NOT NULL,
        KEY idx_trip_revision_trip (trip_id,version_no),
        KEY idx_trip_revision_at (changed_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      try{
        $q=$db->query("SELECT id,permissions_json FROM users WHERE role='field_sales'");
        $up=$db->prepare("UPDATE users SET permissions_json=?,updated_at=NOW() WHERE id=?");
        foreach($q->fetchAll() as $u){
          $perms=rh24_json_decode((string)($u['permissions_json']??''),rh24_default_permissions_for_role('field_sales'));
          if(!is_array($perms))$perms=[];
          foreach(['view_triplog','edit_triplog'] as $perm)if(!in_array($perm,$perms,true))$perms[]=$perm;
          $up->execute([rh24_json_encode(array_values(array_unique($perms))),(string)$u['id']]);
        }
      }catch(Throwable $e){}
      rh24_setting_set('schema_version','75');rh24_setting_set('db_schema_version','75');
      try{rh24_audit('schema_upgrade','system','v75',['features'=>['triplog','vehicles','appointments','google_routes','print_csv_elster','revision_history']],'system');}catch(Throwable $e){}
    } catch(Throwable $e) { /* idempotentes Upgrade */ }
}
function rh24_triplog_rep_scope(?string $requestedRep=''): array {
    $db=rh24_db();
    if(rh24_is_admin()){
      $id=trim((string)$requestedRep);
      if($id===''){$id=(string)($db->query("SELECT id FROM sales_reps WHERE status='active' AND employee_no NOT LIKE 'ZENTRALE-%' ORDER BY name LIMIT 1")->fetchColumn()?:'');}
      if($id==='')return ['id'=>'','name'=>'','employee_no'=>''];
      $q=$db->prepare("SELECT id,name,employee_no,state_code FROM sales_reps WHERE id=? LIMIT 1");$q->execute([$id]);$r=$q->fetch();
      return $r?:['id'=>'','name'=>'','employee_no'=>''];
    }
    $id=rh24_user_sales_rep_id();if($id==='')return ['id'=>'','name'=>'','employee_no'=>''];
    $q=$db->prepare("SELECT id,name,employee_no,state_code FROM sales_reps WHERE id=? AND status='active' LIMIT 1");$q->execute([$id]);$r=$q->fetch();
    return $r?:['id'=>$id,'name'=>(string)(rh24_current_user()['display_name']??''),'employee_no'=>''];
}
function rh24_triplog_period_bounds(string $period): array {
    if(!preg_match('/^\\d{4}-\\d{2}$/',$period))$period=date('Y-m');
    $start=$period.'-01';$end=(new DateTimeImmutable($start))->modify('first day of next month')->format('Y-m-d');
    return [$period,$start,$end];
}
function rh24_triplog_row_hash(array $r): string {
    $keys=['id','sales_rep_id','vehicle_id','trip_date','start_time','end_time','trip_type','start_address','destination_address','purpose','business_partner','start_odometer','end_odometer','distance_km','detour_reason','appointment_type','appointment_ref','status','finalized_at'];$x=[];
    foreach($keys as $k){$v=$r[$k]??null;if($k==='finalized_at'&&$v){$ts=strtotime((string)$v);$v=$ts?date('Y-m-d H:i:s',$ts):(string)$v;}$x[$k]=$v;}return hash('sha256',rh24_json_encode($x));
}
function rh24_triplog_rows(string $repId,string $period): array {
    [$period,$start,$end]=rh24_triplog_period_bounds($period);if($repId==='')return [];$db=rh24_db();
    $q=$db->prepare("SELECT l.*,v.label vehicle_label,v.license_plate,v.make_model FROM trip_log l LEFT JOIN trip_vehicles v ON v.id=l.vehicle_id WHERE l.sales_rep_id=? AND l.trip_date>=? AND l.trip_date<? ORDER BY l.trip_date DESC,COALESCE(l.start_time,'23:59:59') DESC,l.created_at DESC");$q->execute([$repId,$start,$end]);$rows=$q->fetchAll();
    foreach($rows as &$r){foreach(['start_odometer','end_odometer','distance_km'] as $f)$r[$f]=(float)$r[$f];foreach(['finalized_at','created_at','updated_at'] as $f)$r[$f]=rh24_iso($r[$f]??null);$r['hash_ok']=$r['status']!=='finalized'||empty($r['row_hash'])||hash_equals((string)$r['row_hash'],rh24_triplog_row_hash($r));}unset($r);return $rows;
}
function rh24_triplog_vehicles(string $repId): array {
    if($repId==='')return [];$q=rh24_db()->prepare("SELECT * FROM trip_vehicles WHERE sales_rep_id=? ORDER BY status='active' DESC,label");$q->execute([$repId]);$rows=$q->fetchAll();foreach($rows as &$r){$r['odometer_start']=(float)$r['odometer_start'];if(array_key_exists('power_kw',$r)&&$r['power_kw']!==null)$r['power_kw']=(float)$r['power_kw'];if(array_key_exists('displacement_cc',$r)&&$r['displacement_cc']!==null)$r['displacement_cc']=(int)$r['displacement_cc'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_triplog_appointments(string $repId,int $days=45): array {
    if($repId==='')return [];$db=rh24_db();$out=[];$until=date('Y-m-d H:i:s',time()+max(7,min(120,$days))*86400);
    $q=$db->prepare("SELECT id,company,contact,street,zip,city,next_visit_at FROM dealers WHERE status='active' AND sales_rep_id=? AND next_visit_at IS NOT NULL AND next_visit_at<=? ORDER BY next_visit_at");$q->execute([$repId,$until]);
    foreach($q->fetchAll() as $r){$addr=trim(implode(', ',array_filter([(string)$r['street'],trim((string)$r['zip'].' '.(string)$r['city'])])));if($addr==='')continue;$out[]=['type'=>'dealer','ref'=>(string)$r['id'],'title'=>(string)$r['company'],'partner'=>(string)($r['contact']??$r['company']),'address'=>$addr,'at'=>rh24_iso($r['next_visit_at']),'source'=>'Händlertermin'];}
    $q=$db->prepare("SELECT d.id,d.company,d.contact_person,d.street,d.zip,d.city,d.next_follow_up_at FROM territory_directory d WHERE d.status='active' AND d.next_follow_up_at IS NOT NULL AND d.next_follow_up_at<=? AND (d.assigned_sales_rep_id=? OR EXISTS(SELECT 1 FROM territory_contact_logs l WHERE l.directory_id=d.id AND l.sales_rep_id=?)) ORDER BY d.next_follow_up_at");$q->execute([$until,$repId,$repId]);
    foreach($q->fetchAll() as $r){$addr=trim(implode(', ',array_filter([(string)$r['street'],trim((string)$r['zip'].' '.(string)$r['city'])])));if($addr==='')continue;$out[]=['type'=>'territory','ref'=>(string)$r['id'],'title'=>(string)$r['company'],'partner'=>(string)($r['contact_person']??$r['company']),'address'=>$addr,'at'=>rh24_iso($r['next_follow_up_at']),'source'=>'Gebietsbuch-Wiedervorlage'];}
    try{$q=$db->prepare("SELECT id,title,contact_name,address,starts_at FROM advisor_appointments WHERE sales_rep_id=? AND status IN ('scheduled','confirmed') AND location_mode='visit' AND address<>'' AND starts_at<=? AND starts_at>=NOW() ORDER BY starts_at");$q->execute([$repId,$until]);foreach($q->fetchAll() as $r){$out[]=['type'=>'appointment','ref'=>(string)$r['id'],'title'=>(string)$r['title'],'partner'=>(string)($r['contact_name']??$r['title']),'address'=>(string)$r['address'],'at'=>rh24_iso($r['starts_at']),'source'=>'Terminplaner'];}}catch(Throwable $e){}
    usort($out,fn($a,$b)=>strcmp((string)$a['at'],(string)$b['at']));return $out;
}
function rh24_triplog_revisions(string $repId,string $period): array {
    [$period,$start,$end]=rh24_triplog_period_bounds($period);if($repId==='')return [];$db=rh24_db();$q=$db->prepare("SELECT r.*,u.display_name changed_by_name FROM trip_log_revisions r JOIN trip_log l ON l.id=r.trip_id LEFT JOIN users u ON u.id=r.changed_by WHERE l.sales_rep_id=? AND l.trip_date>=? AND l.trip_date<? ORDER BY r.changed_at DESC LIMIT 300");$q->execute([$repId,$start,$end]);$rows=$q->fetchAll();foreach($rows as &$r){$r['changed_at']=rh24_iso($r['changed_at']);unset($r['before_json'],$r['after_json']);}unset($r);return $rows;
}
function rh24_google_routes_secret(): array {$raw=(string)rh24_setting_get('google_routes_credentials','');return $raw!==''?rh24_decrypt_secret($raw):[];}
function rh24_google_routes_config(): array {$s=rh24_google_routes_secret();return ['configured'=>!empty($s['api_key'])];}
function rh24_triplog_data(string $period='',?string $requestedRep=''): array {
    $rep=rh24_triplog_rep_scope($requestedRep);$repId=(string)($rep['id']??'');[$period]=$period?rh24_triplog_period_bounds($period):rh24_triplog_period_bounds(date('Y-m'));
    $rows=rh24_triplog_rows($repId,$period);$vehicles=rh24_triplog_vehicles($repId);$appointments=rh24_triplog_appointments($repId,60);$business=0.0;$private=0.0;$commute=0.0;$finalized=0;$badHashes=0;
    foreach($rows as $r){$km=(float)$r['distance_km'];if($r['trip_type']==='business')$business+=$km;elseif($r['trip_type']==='commute')$commute+=$km;else$private+=$km;if($r['status']==='finalized')$finalized++;if(isset($r['hash_ok'])&&!$r['hash_ok'])$badHashes++;}
    $reps=[];if(rh24_is_admin()){foreach(rh24_sales_reps() as $x){if(str_starts_with((string)($x['employee_no']??''),'ZENTRALE-'))continue;$reps[]=['id'=>(string)$x['id'],'name'=>(string)$x['name'],'employee_no'=>(string)$x['employee_no'],'status'=>(string)$x['status']];}}
    $receipts=function_exists('rh24_trip_receipt_rows')?rh24_trip_receipt_rows($repId,$period):[];$receiptStats=function_exists('rh24_trip_receipt_stats')?rh24_trip_receipt_stats($receipts):['count'=>0,'gross'=>0,'finance_synced'=>0,'finance_errors'=>0];
    return ['period'=>$period,'rep'=>$rep,'reps'=>$reps,'rows'=>$rows,'vehicles'=>$vehicles,'appointments'=>$appointments,'receipts'=>$receipts,'receipt_stats'=>$receiptStats,'receipt_types'=>function_exists('rh24_trip_receipt_types')?rh24_trip_receipt_types():[],'revisions'=>rh24_triplog_revisions($repId,$period),'maps'=>rh24_google_routes_config(),'vehicle_lookup'=>function_exists('rh24_vehicle_lookup_config')?rh24_vehicle_lookup_config():['configured'=>false,'provider'=>'api4cars'],'stats'=>['trips'=>count($rows),'business_km'=>round($business,1),'private_km'=>round($private,1),'commute_km'=>round($commute,1),'finalized'=>$finalized,'drafts'=>count($rows)-$finalized,'hash_warnings'=>$badHashes]];
}
function rh24_triplog_assert_owned_rep(string $repId): void {if(!rh24_is_admin()&&$repId!==rh24_user_sales_rep_id())throw new RuntimeException('Zugriff auf fremdes Fahrtenbuch ist nicht erlaubt.');}
function rh24_triplog_last_odometer(string $vehicleId,?string $excludeId=null): float {
    $db=rh24_db();$sql="SELECT end_odometer FROM trip_log WHERE vehicle_id=?".($excludeId?" AND id<>?":"")." ORDER BY trip_date DESC,COALESCE(end_time,'23:59:59') DESC,created_at DESC LIMIT 1";$q=$db->prepare($sql);$params=[$vehicleId];if($excludeId)$params[]=$excludeId;$q->execute($params);$v=$q->fetchColumn();if($v!==false)return (float)$v;$q=$db->prepare("SELECT odometer_start FROM trip_vehicles WHERE id=?");$q->execute([$vehicleId]);return (float)($q->fetchColumn()?:0);
}
function rh24_google_routes_compute(string $origin,string $destination,array $stops,bool $optimize): array {
    $secret=rh24_google_routes_secret();$key=trim((string)($secret['api_key']??''));if($key==='')throw new RuntimeException('Google Routes API ist noch nicht eingerichtet. Die Route kann weiterhin direkt in Google Maps geöffnet werden.');
    $payload=['origin'=>['address'=>$origin],'destination'=>['address'=>$destination],'travelMode'=>'DRIVE','languageCode'=>'de-DE','units'=>'METRIC','optimizeWaypointOrder'=>$optimize,'intermediates'=>array_map(fn($s)=>['address'=>(string)$s['address']],$stops)];$body=rh24_json_encode($payload);$raw='';$status=0;
    if(function_exists('curl_init')){$ch=curl_init('https://routes.googleapis.com/directions/v2:computeRoutes');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-Goog-Api-Key: '.$key,'X-Goog-FieldMask: routes.distanceMeters,routes.duration,routes.optimizedIntermediateWaypointIndex']]);$res=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);if($res===false)throw new RuntimeException('Google-Routenberechnung nicht erreichbar'.($err?': '.$err:''));$raw=(string)$res;}
    else{$ctx=stream_context_create(['http'=>['method'=>'POST','timeout'=>20,'ignore_errors'=>true,'header'=>"Content-Type: application/json
X-Goog-Api-Key: $key
X-Goog-FieldMask: routes.distanceMeters,routes.duration,routes.optimizedIntermediateWaypointIndex
",'content'=>$body]]);$res=@file_get_contents('https://routes.googleapis.com/directions/v2:computeRoutes',false,$ctx);if($res===false)throw new RuntimeException('Google-Routenberechnung nicht erreichbar');$raw=(string)$res;if(isset($http_response_header[0])&&preg_match('/\s(\d{3})\s/',$http_response_header[0],$m))$status=(int)$m[1];}
    $d=json_decode($raw,true);if($status>=400)throw new RuntimeException('Google Routes API: '.(string)($d['error']['message']??('HTTP '.$status)));$route=$d['routes'][0]??null;if(!$route)throw new RuntimeException('Google hat keine Route geliefert.');$order=$route['optimizedIntermediateWaypointIndex']??array_keys($stops);$seconds=0;if(isset($route['duration'])&&preg_match('/([0-9.]+)s/',(string)$route['duration'],$m))$seconds=(int)round((float)$m[1]);return ['distance_km'=>round(((float)($route['distanceMeters']??0))/1000,1),'duration_minutes'=>(int)round($seconds/60),'order'=>array_map('intval',$order)];
}


/* V76 · Professioneller Terminplaner für Kundenberater */
function rh24_ensure_v76_schema(PDO $db): void {
    try {
      $db->exec("CREATE TABLE IF NOT EXISTS advisor_appointments (
        id VARCHAR(40) PRIMARY KEY,
        sales_rep_id VARCHAR(40) NOT NULL,
        customer_id VARCHAR(40) NULL,
        title VARCHAR(180) NOT NULL,
        appointment_type VARCHAR(40) NOT NULL DEFAULT 'consultation',
        status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
        starts_at DATETIME NOT NULL,
        ends_at DATETIME NOT NULL,
        location_mode VARCHAR(24) NOT NULL DEFAULT 'visit',
        address VARCHAR(255) NULL,
        contact_name VARCHAR(180) NULL,
        phone VARCHAR(80) NULL,
        email VARCHAR(190) NULL,
        priority VARCHAR(20) NOT NULL DEFAULT 'normal',
        reminder_minutes INT NOT NULL DEFAULT 60,
        notes TEXT NULL,
        source VARCHAR(30) NOT NULL DEFAULT 'manual',
        completed_at DATETIME NULL,
        cancellation_reason VARCHAR(255) NULL,
        created_by VARCHAR(40) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        KEY idx_adv_appt_rep_start (sales_rep_id,starts_at),
        KEY idx_adv_appt_customer (customer_id,starts_at),
        KEY idx_adv_appt_status (status,starts_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      try{
        $q=$db->query("SELECT id,permissions_json FROM users WHERE role='field_sales'");
        $up=$db->prepare("UPDATE users SET permissions_json=?,updated_at=NOW() WHERE id=?");
        foreach($q->fetchAll() as $u){
          $perms=rh24_json_decode((string)($u['permissions_json']??''),rh24_default_permissions_for_role('field_sales'));
          if(!is_array($perms))$perms=[];
          foreach(['view_appointments','edit_appointments'] as $perm)if(!in_array($perm,$perms,true))$perms[]=$perm;
          $up->execute([rh24_json_encode(array_values(array_unique($perms))),(string)$u['id']]);
        }
      }catch(Throwable $e){}
      rh24_setting_set('schema_version','76');rh24_setting_set('db_schema_version','76');
      try{rh24_audit('schema_upgrade','system','v76',['features'=>['advisor_appointments','customer_search','month_week_agenda','ics','print','triplog_integration']],'system');}catch(Throwable $e){}
    } catch(Throwable $e) { /* idempotentes Upgrade */ }
}
function rh24_appointment_type_labels(): array {return ['consultation'=>'Beratung','service'=>'Service / Betreuung','follow_up'=>'Nachfassen / Wiedervorlage','order'=>'Bestellung / Verkauf','presentation'=>'Vorführung','delivery'=>'Lieferung / Übergabe','phone'=>'Telefontermin','video'=>'Video-Termin','other'=>'Sonstiger Termin'];}
function rh24_appointment_status_labels(): array {return ['scheduled'=>'Geplant','confirmed'=>'Bestätigt','completed'=>'Erledigt','cancelled'=>'Abgesagt','no_show'=>'Nicht erschienen'];}
function rh24_appointment_location_labels(): array {return ['visit'=>'Vor Ort','phone'=>'Telefon','video'=>'Video','office'=>'Büro / Zentrale'];}
function rh24_appointment_rep_scope(?string $requestedRep=''): array {
    return rh24_triplog_rep_scope($requestedRep);
}
function rh24_appointment_bounds(string $period): array {
    if(!preg_match('/^\d{4}-\d{2}$/',$period))$period=date('Y-m');
    $first=new DateTimeImmutable($period.'-01 00:00:00');
    $from=$first->modify('-7 days')->format('Y-m-d H:i:s');
    $to=$first->modify('first day of next month +7 days')->format('Y-m-d H:i:s');
    return [$period,$from,$to];
}
function rh24_appointment_rows(string $repId,string $period): array {
    if($repId==='')return [];$db=rh24_db();[$period,$from,$to]=rh24_appointment_bounds($period);
    $q=$db->prepare("SELECT a.*,c.name customer_name,c.company customer_company,c.street customer_street,c.zip customer_zip,c.city customer_city FROM advisor_appointments a LEFT JOIN customers c ON c.id=a.customer_id WHERE a.sales_rep_id=? AND a.starts_at>=? AND a.starts_at<? ORDER BY a.starts_at,a.ends_at,a.created_at");
    $q->execute([$repId,$from,$to]);$rows=$q->fetchAll();$ids=array_map(fn($r)=>(string)$r['id'],$rows);$related=rh24_appointment_related($ids);
    $types=rh24_appointment_type_labels();$statuses=rh24_appointment_status_labels();$locations=rh24_appointment_location_labels();
    foreach($rows as &$r){foreach(['starts_at','ends_at','completed_at','follow_up_at','confirmed_at','last_reminded_at','reminder_1_sent_at','reminder_2_sent_at','created_at','updated_at'] as $f)$r[$f]=rh24_iso($r[$f]??null);foreach(['reminder_minutes','reminder_2_minutes','buffer_before_minutes','buffer_after_minutes'] as $f)$r[$f]=(int)($r[$f]??0);$r['all_day']=(bool)($r['all_day']??0);$r['tags']=rh24_json_decode((string)($r['tags_json']??''),[]);unset($r['tags_json']);$r['attendees']=$related['attendees'][(string)$r['id']]??[];$r['tasks']=$related['tasks'][(string)$r['id']]??[];$r['type_label']=$types[$r['appointment_type']]??'Termin';$r['status_label']=$statuses[$r['status']]??(string)$r['status'];$r['location_label']=$locations[$r['location_mode']]??(string)$r['location_mode'];}unset($r);return $rows;
}
function rh24_appointment_customer_search(string $repId,string $term,int $limit=30): array {
    if($repId==='')return [];$db=rh24_db();$term=trim($term);$limit=max(5,min(50,$limit));
    $sql="SELECT id,name,email,phone,company,street,zip,city,sales_rep_id FROM customers WHERE sales_rep_id=?";$params=[$repId];
    if($term!==''){$like='%'.$term.'%';$sql.=" AND (name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ? OR street LIKE ? OR zip LIKE ? OR city LIKE ?)";for($i=0;$i<7;$i++)$params[]=$like;}
    $sql.=" ORDER BY name LIMIT ".$limit;$q=$db->prepare($sql);$q->execute($params);return $q->fetchAll();
}
function rh24_appointment_data(string $period='',?string $requestedRep=''): array {
    $rep=rh24_appointment_rep_scope($requestedRep);$repId=(string)($rep['id']??'');if($period==='')$period=date('Y-m');$rows=rh24_appointment_rows($repId,$period);$today=date('Y-m-d');$weekEnd=date('Y-m-d',strtotime('+7 days'));$now=time();
    $stats=['total'=>count($rows),'today'=>0,'next7'=>0,'confirmed'=>0,'open'=>0,'completed'=>0,'no_show'=>0,'followups_due'=>0,'workload_minutes'=>0];$reminders=[];
    foreach($rows as $r){$d=substr((string)$r['starts_at'],0,10);$start=strtotime((string)$r['starts_at']);$end=strtotime((string)$r['ends_at']);if($d===$today)$stats['today']++;if($d>=$today&&$d<=$weekEnd&&!in_array($r['status'],['cancelled','completed'],true))$stats['next7']++;if($r['status']==='confirmed')$stats['confirmed']++;if(in_array($r['status'],['scheduled','confirmed'],true)){$stats['open']++;$stats['workload_minutes']+=max(0,(int)round(($end-$start)/60));}if($r['status']==='completed')$stats['completed']++;if($r['status']==='no_show')$stats['no_show']++;if(!empty($r['follow_up_at'])&&strtotime((string)$r['follow_up_at'])<=$now&&!in_array($r['status'],['cancelled'],true))$stats['followups_due']++;
      if(in_array($r['status'],['scheduled','confirmed'],true)&&$start>$now){foreach([(int)$r['reminder_minutes'],(int)$r['reminder_2_minutes']] as $rm){if($rm>0&&$start-$rm*60<=$now){$reminders[]=['id'=>$r['id'],'title'=>$r['title'],'starts_at'=>$r['starts_at'],'contact'=>$r['contact_name']?:$r['customer_name'],'minutes'=>$rm];break;}}}}
    $reps=[];if(rh24_is_admin()){foreach(rh24_sales_reps() as $x){if(str_starts_with((string)($x['employee_no']??''),'ZENTRALE-'))continue;$reps[]=['id'=>(string)$x['id'],'name'=>(string)$x['name'],'employee_no'=>(string)$x['employee_no'],'status'=>(string)$x['status']];}}
    return ['period'=>$period,'rep'=>$rep,'reps'=>$reps,'rows'=>$rows,'stats'=>$stats,'types'=>rh24_appointment_type_labels(),'statuses'=>rh24_appointment_status_labels(),'locations'=>rh24_appointment_location_labels(),'recurrences'=>rh24_appointment_recurrence_labels(),'reminder_channels'=>rh24_appointment_reminder_channel_labels(),'templates'=>rh24_appointment_templates($repId),'reminders'=>array_slice($reminders,0,8),'workday'=>['start'=>(string)rh24_setting_get('appointment_workday_start','08:00'),'end'=>(string)rh24_setting_get('appointment_workday_end','18:00')]];
}
function rh24_appointment_assert_owned_rep(string $repId): void {if(!rh24_is_admin()&&$repId!==rh24_user_sales_rep_id())throw new RuntimeException('Zugriff auf fremde Termine ist nicht erlaubt.');}
function rh24_appointment_conflict(string $repId,string $starts,string $ends,string $excludeId='',int $bufferBefore=0,int $bufferAfter=0): ?array {
    $db=rh24_db();$newStart=date('Y-m-d H:i:s',strtotime($starts)-max(0,$bufferBefore)*60);$newEnd=date('Y-m-d H:i:s',strtotime($ends)+max(0,$bufferAfter)*60);try{$sql="SELECT id,title,starts_at,ends_at FROM advisor_appointments WHERE sales_rep_id=? AND status IN ('scheduled','confirmed') AND DATE_SUB(starts_at, INTERVAL buffer_before_minutes MINUTE)<? AND DATE_ADD(ends_at, INTERVAL buffer_after_minutes MINUTE)>?";$params=[$newEnd,$newStart];if($excludeId!==''){$sql.=' AND id<>?';$params[]=$excludeId;}$sql.=' ORDER BY starts_at LIMIT 1';$q=$db->prepare($sql);$q->execute($params);$r=$q->fetch();return $r?:null;}catch(Throwable $e){$sql="SELECT id,title,starts_at,ends_at FROM advisor_appointments WHERE sales_rep_id=? AND status IN ('scheduled','confirmed') AND starts_at<? AND ends_at>?";$params=[$newEnd,$newStart];if($excludeId!==''){$sql.=' AND id<>?';$params[]=$excludeId;}$sql.=' ORDER BY starts_at LIMIT 1';$q=$db->prepare($sql);$q->execute($params);$r=$q->fetch();return $r?:null;}
}


/* V92 · Terminmanagement Pro */
function rh24_ensure_v92_appointments_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS advisor_appointments (
      id VARCHAR(40) PRIMARY KEY, sales_rep_id VARCHAR(40) NOT NULL, customer_id VARCHAR(40) NULL, title VARCHAR(180) NOT NULL,
      appointment_type VARCHAR(40) NOT NULL DEFAULT 'consultation', status VARCHAR(30) NOT NULL DEFAULT 'scheduled',
      starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, location_mode VARCHAR(24) NOT NULL DEFAULT 'visit', address VARCHAR(255) NULL,
      contact_name VARCHAR(180) NULL, phone VARCHAR(80) NULL, email VARCHAR(190) NULL, priority VARCHAR(20) NOT NULL DEFAULT 'normal',
      reminder_minutes INT NOT NULL DEFAULT 60, notes TEXT NULL, source VARCHAR(30) NOT NULL DEFAULT 'manual', completed_at DATETIME NULL,
      cancellation_reason VARCHAR(255) NULL, created_by VARCHAR(40) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
      KEY idx_adv_appt_rep_start (sales_rep_id,starts_at), KEY idx_adv_appt_customer (customer_id,starts_at), KEY idx_adv_appt_status (status,starts_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $changes=[
      "ALTER TABLE advisor_appointments ADD COLUMN all_day TINYINT(1) NOT NULL DEFAULT 0 AFTER ends_at",
      "ALTER TABLE advisor_appointments ADD COLUMN meeting_url VARCHAR(500) NOT NULL DEFAULT '' AFTER location_mode",
      "ALTER TABLE advisor_appointments ADD COLUMN buffer_before_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER priority",
      "ALTER TABLE advisor_appointments ADD COLUMN buffer_after_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER buffer_before_minutes",
      "ALTER TABLE advisor_appointments ADD COLUMN reminder_2_minutes INT NOT NULL DEFAULT 0 AFTER reminder_minutes",
      "ALTER TABLE advisor_appointments ADD COLUMN reminder_channel VARCHAR(20) NOT NULL DEFAULT 'inapp' AFTER reminder_2_minutes",
      "ALTER TABLE advisor_appointments ADD COLUMN series_id VARCHAR(40) NULL AFTER source",
      "ALTER TABLE advisor_appointments ADD COLUMN recurrence_rule VARCHAR(30) NOT NULL DEFAULT 'none' AFTER series_id",
      "ALTER TABLE advisor_appointments ADD COLUMN recurrence_until DATE NULL AFTER recurrence_rule",
      "ALTER TABLE advisor_appointments ADD COLUMN color VARCHAR(20) NOT NULL DEFAULT 'brown' AFTER recurrence_until",
      "ALTER TABLE advisor_appointments ADD COLUMN tags_json TEXT NULL AFTER color",
      "ALTER TABLE advisor_appointments ADD COLUMN outcome TEXT NULL AFTER notes",
      "ALTER TABLE advisor_appointments ADD COLUMN next_action VARCHAR(255) NOT NULL DEFAULT '' AFTER outcome",
      "ALTER TABLE advisor_appointments ADD COLUMN follow_up_at DATETIME NULL AFTER next_action",
      "ALTER TABLE advisor_appointments ADD COLUMN confirmed_at DATETIME NULL AFTER cancellation_reason",
      "ALTER TABLE advisor_appointments ADD COLUMN confirmation_source VARCHAR(30) NOT NULL DEFAULT '' AFTER confirmed_at",
      "ALTER TABLE advisor_appointments ADD COLUMN last_reminded_at DATETIME NULL AFTER confirmation_source",
      "ALTER TABLE advisor_appointments ADD COLUMN reminder_1_sent_at DATETIME NULL AFTER last_reminded_at",
      "ALTER TABLE advisor_appointments ADD COLUMN reminder_2_sent_at DATETIME NULL AFTER reminder_1_sent_at",
      "ALTER TABLE advisor_appointments ADD KEY idx_adv_appt_series (series_id,starts_at)",
      "ALTER TABLE advisor_appointments ADD KEY idx_adv_appt_followup (follow_up_at,status)"
    ];
    foreach($changes as $sql){try{$db->exec($sql);}catch(Throwable $e){}}
    $db->exec("CREATE TABLE IF NOT EXISTS advisor_appointment_attendees (
      id VARCHAR(40) PRIMARY KEY, appointment_id VARCHAR(40) NOT NULL, user_id VARCHAR(40) NULL,
      name VARCHAR(180) NOT NULL DEFAULT '', email VARCHAR(190) NOT NULL DEFAULT '', role VARCHAR(30) NOT NULL DEFAULT 'required',
      attendance_status VARCHAR(30) NOT NULL DEFAULT 'needs_action', created_at DATETIME NOT NULL,
      KEY idx_appt_attendee_appointment (appointment_id), KEY idx_appt_attendee_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS advisor_appointment_tasks (
      id VARCHAR(40) PRIMARY KEY, appointment_id VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL,
      is_done TINYINT(1) NOT NULL DEFAULT 0, due_at DATETIME NULL, assigned_user_id VARCHAR(40) NULL,
      created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
      KEY idx_appt_task_appointment (appointment_id,is_done), KEY idx_appt_task_due (due_at,is_done)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS advisor_appointment_templates (
      id VARCHAR(40) PRIMARY KEY, sales_rep_id VARCHAR(40) NULL, name VARCHAR(160) NOT NULL,
      appointment_type VARCHAR(40) NOT NULL DEFAULT 'consultation', duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
      location_mode VARCHAR(24) NOT NULL DEFAULT 'visit', priority VARCHAR(20) NOT NULL DEFAULT 'normal',
      reminder_minutes INT NOT NULL DEFAULT 60, reminder_2_minutes INT NOT NULL DEFAULT 0,
      buffer_before_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0, buffer_after_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      notes TEXT NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_by VARCHAR(40) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
      KEY idx_appt_template_rep (sales_rep_id,active,name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    try{$db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('appointment_workday_start','08:00',NOW()) ON DUPLICATE KEY UPDATE setting_value=setting_value")->execute();}catch(Throwable $e){}
    try{$db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('appointment_workday_end','18:00',NOW()) ON DUPLICATE KEY UPDATE setting_value=setting_value")->execute();}catch(Throwable $e){}
    try{$token=bin2hex(random_bytes(24));$db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('appointment_cron_token',?,NOW()) ON DUPLICATE KEY UPDATE setting_value=setting_value")->execute([$token]);}catch(Throwable $e){}
    try{$db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES('appointment_schema_version','92',NOW()) ON DUPLICATE KEY UPDATE setting_value='92',updated_at=NOW()")->execute();}catch(Throwable $e){}
    try{rh24_audit('schema_upgrade','system','v92-appointments',['features'=>['day_view','filters','recurrence','dual_reminders','buffers','attendees','tasks','templates','followups','meeting_links','drag_drop','email_invite']],'system');}catch(Throwable $e){}
}
function rh24_v92_appointments_ready(PDO $db): bool {
    try{
      $cols=$db->query("SHOW COLUMNS FROM advisor_appointments")->fetchAll(PDO::FETCH_COLUMN);
      $need=['all_day','meeting_url','buffer_before_minutes','buffer_after_minutes','reminder_2_minutes','reminder_channel','series_id','recurrence_rule','recurrence_until','color','tags_json','outcome','next_action','follow_up_at','confirmed_at','confirmation_source','last_reminded_at','reminder_1_sent_at','reminder_2_sent_at'];
      if(array_diff($need,$cols))return false;
      foreach(['advisor_appointment_attendees','advisor_appointment_tasks','advisor_appointment_templates'] as $table)$db->query('SELECT 1 FROM `'.$table.'` LIMIT 1');
      return true;
    }catch(Throwable $e){return false;}
}
function rh24_appointment_recurrence_labels(): array {return ['none'=>'Keine Serie','daily'=>'Täglich','weekly'=>'Wöchentlich','biweekly'=>'Alle 2 Wochen','monthly'=>'Monatlich'];}
function rh24_appointment_reminder_channel_labels(): array {return ['inapp'=>'Orgaboard','email'=>'E-Mail','both'=>'Orgaboard + E-Mail'];}
function rh24_appointment_recurrence_dates(string $start,string $rule,string $until,int $max=60): array {
    if($rule==='none'||$until==='')return [];$base=new DateTimeImmutable($start);$limit=new DateTimeImmutable($until.' 23:59:59');$out=[];$cur=$base;
    for($i=0;$i<$max;$i++){
      if($rule==='daily')$cur=$cur->modify('+1 day');elseif($rule==='weekly')$cur=$cur->modify('+1 week');elseif($rule==='biweekly')$cur=$cur->modify('+2 weeks');elseif($rule==='monthly')$cur=$cur->modify('+1 month');else break;
      if($cur>$limit)break;$out[]=$cur->format('Y-m-d H:i:s');
    }return $out;
}
function rh24_appointment_templates(string $repId): array {
    try{$db=rh24_db();$q=$db->prepare("SELECT * FROM advisor_appointment_templates WHERE active=1 AND (sales_rep_id IS NULL OR sales_rep_id=?) ORDER BY name");$q->execute([$repId]);$rows=$q->fetchAll();foreach($rows as &$r){foreach(['duration_minutes','reminder_minutes','reminder_2_minutes','buffer_before_minutes','buffer_after_minutes'] as $f)$r[$f]=(int)$r[$f];}unset($r);return $rows;}catch(Throwable $e){return [];}
}
function rh24_appointment_related(array $ids): array {
    $out=['attendees'=>[],'tasks'=>[]];if(!$ids)return $out;try{$db=rh24_db();$ph=implode(',',array_fill(0,count($ids),'?'));$q=$db->prepare("SELECT * FROM advisor_appointment_attendees WHERE appointment_id IN ($ph) ORDER BY created_at,id");$q->execute($ids);foreach($q->fetchAll() as $r){$out['attendees'][(string)$r['appointment_id']][]=$r;}$q=$db->prepare("SELECT * FROM advisor_appointment_tasks WHERE appointment_id IN ($ph) ORDER BY is_done,due_at,created_at");$q->execute($ids);foreach($q->fetchAll() as $r){$r['is_done']=(bool)$r['is_done'];$r['due_at']=rh24_iso($r['due_at']??null);$out['tasks'][(string)$r['appointment_id']][]=$r;}}catch(Throwable $e){}return $out;
}
function rh24_appointment_save_related(string $id,array $attendees,array $tasks): void {
    $db=rh24_db();$db->prepare('DELETE FROM advisor_appointment_attendees WHERE appointment_id=?')->execute([$id]);$db->prepare('DELETE FROM advisor_appointment_tasks WHERE appointment_id=?')->execute([$id]);
    $ia=$db->prepare("INSERT INTO advisor_appointment_attendees(id,appointment_id,user_id,name,email,role,attendance_status,created_at) VALUES(?,?,?,?,?,?,?,NOW())");
    foreach(array_slice($attendees,0,20) as $a){if(!is_array($a))continue;$name=trim((string)($a['name']??''));$email=strtolower(trim((string)($a['email']??'')));if($name===''&&$email==='')continue;if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))continue;$role=in_array((string)($a['role']??'required'),['required','optional','organizer'],true)?(string)$a['role']:'required';$ia->execute([rh24_random_id('ATA-'),$id,($a['user_id']??'')?:null,$name,$email,$role,(string)($a['attendance_status']??'needs_action')]);}
    $it=$db->prepare("INSERT INTO advisor_appointment_tasks(id,appointment_id,title,is_done,due_at,assigned_user_id,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())");
    foreach(array_slice($tasks,0,30) as $t){if(!is_array($t))continue;$title=trim((string)($t['title']??''));if($title==='')continue;$due=trim((string)($t['due_at']??''));$due=$due!==''?str_replace('T',' ',$due).(strlen($due)===16?':00':''):null;$it->execute([rh24_random_id('ATK-'),$id,$title,!empty($t['is_done'])?1:0,$due,($t['assigned_user_id']??'')?:null]);}
}
function rh24_appointment_expand_series(string $id): int {
    $db=rh24_db();$q=$db->prepare('SELECT * FROM advisor_appointments WHERE id=?');$q->execute([$id]);$a=$q->fetch();if(!$a)return 0;$rule=(string)($a['recurrence_rule']??'none');$until=(string)($a['recurrence_until']??'');$dates=rh24_appointment_recurrence_dates((string)$a['starts_at'],$rule,$until,60);if(!$dates)return 0;
    $duration=max(60,strtotime((string)$a['ends_at'])-strtotime((string)$a['starts_at']));$rel=rh24_appointment_related([$id]);$ins=$db->prepare("INSERT INTO advisor_appointments(id,sales_rep_id,customer_id,title,appointment_type,status,starts_at,ends_at,all_day,location_mode,meeting_url,address,contact_name,phone,email,priority,buffer_before_minutes,buffer_after_minutes,reminder_minutes,reminder_2_minutes,reminder_channel,notes,outcome,next_action,follow_up_at,source,series_id,recurrence_rule,recurrence_until,color,tags_json,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");$n=0;
    foreach($dates as $start){$nid=rh24_random_id('APT-');$end=date('Y-m-d H:i:s',strtotime($start)+$duration);$ins->execute([$nid,$a['sales_rep_id'],$a['customer_id'],$a['title'],$a['appointment_type'],'scheduled',$start,$end,(int)($a['all_day']??0),$a['location_mode'],$a['meeting_url']??'',$a['address'],$a['contact_name'],$a['phone'],$a['email'],$a['priority'],(int)($a['buffer_before_minutes']??0),(int)($a['buffer_after_minutes']??0),(int)$a['reminder_minutes'],(int)($a['reminder_2_minutes']??0),$a['reminder_channel']??'inapp',$a['notes'],'','',null,$a['source'],$id,$rule,$a['recurrence_until'],$a['color']??'brown',$a['tags_json']??'[]',$a['created_by']]);rh24_appointment_save_related($nid,$rel['attendees'][$id]??[],$rel['tasks'][$id]??[]);$n++;}
    return $n;
}
function rh24_appointment_ics_content(array $a): string {
    $esc=function(string $v): string{return str_replace(["\\",";",",","\r\n","\n","\r"],["\\\\","\\;","\\,","\\n","\\n","\\n"],$v);};$start=strtotime((string)$a['starts_at']);$end=strtotime((string)$a['ends_at']);$desc=trim((string)($a['notes']??''));if(!empty($a['phone']))$desc.=($desc?"\n":'').'Telefon: '.$a['phone'];if(!empty($a['email']))$desc.=($desc?"\n":'').'E-Mail: '.$a['email'];if(!empty($a['meeting_url']))$desc.=($desc?"\n":'').'Meeting: '.$a['meeting_url'];
    $ics="BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Raeucherhaken24//Orgaboard V92//DE\r\nCALSCALE:GREGORIAN\r\nMETHOD:REQUEST\r\nBEGIN:VEVENT\r\n";$ics.='UID:'.$esc((string)$a['id'].'@raeucherhaken24.com')."\r\n";$ics.='DTSTAMP:'.gmdate('Ymd\\THis\\Z')."\r\n";$ics.='DTSTART:'.date('Ymd\\THis',$start)."\r\n";$ics.='DTEND:'.date('Ymd\\THis',$end)."\r\n";$ics.='SUMMARY:'.$esc((string)$a['title'])."\r\n";if(!empty($a['address']))$ics.='LOCATION:'.$esc((string)$a['address'])."\r\n";if($desc!=='')$ics.='DESCRIPTION:'.$esc($desc)."\r\n";
    foreach([(int)($a['reminder_minutes']??0),(int)($a['reminder_2_minutes']??0)] as $rem)if($rem>0)$ics.="BEGIN:VALARM\r\nTRIGGER:-PT".$rem."M\r\nACTION:DISPLAY\r\nDESCRIPTION:Termin-Erinnerung\r\nEND:VALARM\r\n";$ics.="END:VEVENT\r\nEND:VCALENDAR\r\n";return $ics;
}


function rh24_appointment_send_due_reminders(?string $repId=null): array {
    $db=rh24_db();$now=time();$sent=0;$failed=0;$skipped=0;$details=[];
    $sql="SELECT a.*,c.name customer_name FROM advisor_appointments a LEFT JOIN customers c ON c.id=a.customer_id WHERE a.status IN ('scheduled','confirmed') AND a.starts_at>NOW() AND a.starts_at<=DATE_ADD(NOW(),INTERVAL 15 DAY) AND a.reminder_channel IN ('email','both') AND a.email<>''";$params=[];
    if($repId!==null&&$repId!==''){$sql.=' AND a.sales_rep_id=?';$params[]=$repId;}$sql.=' ORDER BY a.starts_at LIMIT 250';$q=$db->prepare($sql);$q->execute($params);
    foreach($q->fetchAll() as $a){
      $start=strtotime((string)$a['starts_at']);if(!$start)continue;$due=[];
      foreach([[1,(int)($a['reminder_minutes']??0),'reminder_1_sent_at'],[2,(int)($a['reminder_2_minutes']??0),'reminder_2_sent_at']] as [$n,$minutes,$field]){if($minutes>0&&empty($a[$field])&&$start-$minutes*60<=$now)$due[]=[$n,$minutes,$field];}
      if(!$due)continue;usort($due,fn($x,$y)=>$x[1]<=>$y[1]);$chosen=$due[0];
      $email=strtolower(trim((string)$a['email']));if(!filter_var($email,FILTER_VALIDATE_EMAIL)){$skipped++;continue;}
      $contact=trim((string)($a['contact_name']??''))?:trim((string)($a['customer_name']??''))?:'Kunde';$when=date('d.m.Y H:i',$start);$title=trim((string)($a['title']??'Termin'));
      $html='<p>Guten Tag '.htmlspecialchars($contact,ENT_QUOTES,'UTF-8').',</p><p>dies ist Ihre Erinnerung an den Termin <b>'.htmlspecialchars($title,ENT_QUOTES,'UTF-8').'</b>.</p><p><b>Termin:</b> '.htmlspecialchars($when,ENT_QUOTES,'UTF-8').' Uhr</p>';
      if(trim((string)($a['address']??''))!=='')$html.='<p><b>Ort:</b> '.htmlspecialchars((string)$a['address'],ENT_QUOTES,'UTF-8').'</p>';
      if(trim((string)($a['meeting_url']??''))!=='')$html.='<p><b>Online-Termin:</b> '.htmlspecialchars((string)$a['meeting_url'],ENT_QUOTES,'UTF-8').'</p>';
      $html.='<p>Räucherhaken24</p>';$ics=rh24_appointment_ics_content($a);$ok=rh24_send_mail_attachments($email,'Terminerinnerung · '.$title,$html,[['name'=>'Termin-'.$a['id'].'.ics','data'=>$ics,'mime'=>'text/calendar; charset=UTF-8']],'appointment_reminder');
      if($ok){$fields=array_column($due,2);$set=implode(',',array_map(fn($f)=>$f.'=NOW()',$fields));$db->exec("UPDATE advisor_appointments SET $set,last_reminded_at=NOW(),updated_at=NOW() WHERE id=".$db->quote((string)$a['id']));$sent++;$details[]=['id'=>(string)$a['id'],'email'=>$email,'title'=>$title,'minutes'=>(int)$chosen[1]];}else{$failed++;}
    }
    try{rh24_audit('appointment_reminders_run','appointments','batch',['sent'=>$sent,'failed'=>$failed,'skipped'=>$skipped],'system');}catch(Throwable $e){}
    return ['sent'=>$sent,'failed'=>$failed,'skipped'=>$skipped,'checked_at'=>date('c'),'details'=>$details];
}

/* V77 · Professionelles Rechnungstool, PDF-Dokumente und Shop-Automation */
function rh24_ensure_v77_schema(PDO $db): void {
    try {
      $changes=[
        "ALTER TABLE documents ADD COLUMN pdf_sha256 CHAR(64) NULL AFTER note",
        "ALTER TABLE documents ADD COLUMN pdf_generated_at DATETIME NULL AFTER pdf_sha256",
        "ALTER TABLE documents ADD COLUMN emailed_at DATETIME NULL AFTER pdf_generated_at",
        "ALTER TABLE documents ADD COLUMN email_status VARCHAR(30) NULL AFTER emailed_at",
        "ALTER TABLE documents ADD COLUMN locked_at DATETIME NULL AFTER issued_at",
        "ALTER TABLE documents ADD COLUMN cancelled_at DATETIME NULL AFTER locked_at",
        "ALTER TABLE documents ADD COLUMN cancel_reason VARCHAR(255) NULL AFTER cancelled_at"
      ];
      foreach($changes as $sql){try{$db->exec($sql);}catch(Throwable $e){}}
      $defaults=[
        'invoice_company_name'=>'Räucherhaken24','invoice_owner'=>'Björn Hahne','invoice_street'=>'Schiffbrücke 5','invoice_zip'=>'24340','invoice_city'=>'Eckernförde','invoice_country'=>'Deutschland',
        'invoice_phone'=>'0176 / 20204188','invoice_email'=>'service@raeucherhaken24.com','invoice_website'=>'www.raeucherhaken24.de','invoice_tax_no'=>'','invoice_vat_id'=>'',
        'invoice_iban'=>'','invoice_bic'=>'','invoice_bank_name'=>'','invoice_payment_days'=>'7','invoice_footer'=>'Vielen Dank für Ihren Einkauf bei Räucherhaken24.','invoice_auto_email'=>'1'
      ];
      foreach($defaults as $k=>$v){try{if((string)rh24_setting_get($k,'')==='')rh24_setting_set($k,$v);}catch(Throwable $e){}}
      rh24_setting_set('schema_version','77');rh24_setting_set('db_schema_version','77');
      try{rh24_audit('schema_upgrade','system','v77',['features'=>['invoice_tool','server_pdf','automatic_shop_invoice','delivery_note','email_attachments','customer_pdf_download','immutable_issued_documents','invoice_profile']],'system');}catch(Throwable $e){}
    } catch(Throwable $e) { /* idempotentes Upgrade */ }
}
function rh24_ensure_v80_schema(PDO $db): void {
    try{
      $version=(int)rh24_setting_get('schema_version','0');
      if($version>=80) return;
      foreach([
        "ALTER TABLE products ADD COLUMN short_description VARCHAR(320) NOT NULL DEFAULT '' AFTER description",
        "ALTER TABLE products ADD COLUMN features_json LONGTEXT NULL AFTER short_description",
        "ALTER TABLE products ADD COLUMN seo_title VARCHAR(180) NOT NULL DEFAULT '' AFTER features_json",
        "ALTER TABLE products ADD COLUMN seo_description VARCHAR(320) NOT NULL DEFAULT '' AFTER seo_title",
        "ALTER TABLE products ADD COLUMN is_new TINYINT(1) NOT NULL DEFAULT 0 AFTER is_offer",
        "ALTER TABLE products ADD COLUMN new_until DATETIME NULL AFTER is_new",
        "ALTER TABLE products ADD COLUMN published_at DATETIME NULL AFTER new_until"
      ] as $sql){try{$db->exec($sql);}catch(Throwable $e){}}
      rh24_setting_set('schema_version','80');rh24_setting_set('db_schema_version','80');
      try{rh24_audit('schema_upgrade','system','v80',['features'=>['product_builder','field_help','draft_publish_flow','shop_locked_template','new_in_range','product_preview','seo_fields','feature_list']],'system');}catch(Throwable $e){}
    }catch(Throwable $e){}
}
function rh24_ensure_v86_schema(PDO $db): void {
    try{
      $version=(int)rh24_setting_get('schema_version','0');
      if($version>=86) return;
      foreach([
        "ALTER TABLE products ADD COLUMN cross_sell_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER seo_description",
        "ALTER TABLE products ADD COLUMN cross_sell_title VARCHAR(160) NOT NULL DEFAULT 'Passt perfekt dazu' AFTER cross_sell_enabled",
        "ALTER TABLE products ADD COLUMN cross_sell_max TINYINT UNSIGNED NOT NULL DEFAULT 4 AFTER cross_sell_title",
        "ALTER TABLE products ADD COLUMN cross_sell_auto TINYINT(1) NOT NULL DEFAULT 0 AFTER cross_sell_max",
        "ALTER TABLE products ADD COLUMN cross_sell_reciprocal TINYINT(1) NOT NULL DEFAULT 0 AFTER cross_sell_auto",
        "ALTER TABLE products ADD COLUMN cross_sell_json LONGTEXT NULL AFTER cross_sell_reciprocal"
      ] as $sql){try{$db->exec($sql);}catch(Throwable $e){}}
      rh24_setting_set('schema_version','86');rh24_setting_set('db_schema_version','86');
      try{rh24_audit('schema_upgrade','system','v86',['features'=>['cross_selling','manual_relations','auto_fill','reciprocal_links','shop_cross_sell_cards']],'system');}catch(Throwable $e){}
    }catch(Throwable $e){}
}

function rh24_ensure_v89_schema(PDO $db): void {
    try{
      $version=(int)rh24_setting_get('schema_version','0');
      if($version>=89) return;
      foreach([
        "ALTER TABLE customers ADD COLUMN customer_type VARCHAR(20) NOT NULL DEFAULT 'private' AFTER name",
        "ALTER TABLE customers ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER customer_type",
        "ALTER TABLE customers ADD COLUMN salutation VARCHAR(30) NOT NULL DEFAULT '' AFTER status",
        "ALTER TABLE customers ADD COLUMN mobile VARCHAR(80) NOT NULL DEFAULT '' AFTER phone",
        "ALTER TABLE customers ADD COLUMN website VARCHAR(220) NOT NULL DEFAULT '' AFTER mobile",
        "ALTER TABLE customers ADD COLUMN country VARCHAR(80) NOT NULL DEFAULT 'Deutschland' AFTER city",
        "ALTER TABLE customers ADD COLUMN vat_id VARCHAR(60) NOT NULL DEFAULT '' AFTER country",
        "ALTER TABLE customers ADD COLUMN tax_no VARCHAR(80) NOT NULL DEFAULT '' AFTER vat_id",
        "ALTER TABLE customers ADD COLUMN payment_method VARCHAR(80) NOT NULL DEFAULT '' AFTER tax_no",
        "ALTER TABLE customers ADD COLUMN payment_terms_days SMALLINT UNSIGNED NOT NULL DEFAULT 7 AFTER payment_method",
        "ALTER TABLE customers ADD COLUMN discount_percent DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER payment_terms_days",
        "ALTER TABLE customers ADD COLUMN preferred_contact VARCHAR(30) NOT NULL DEFAULT 'email' AFTER discount_percent",
        "ALTER TABLE customers ADD COLUMN source VARCHAR(80) NOT NULL DEFAULT 'Orgaboard' AFTER preferred_contact",
        "ALTER TABLE customers ADD COLUMN tags_json LONGTEXT NULL AFTER source",
        "ALTER TABLE customers ADD COLUMN billing_json LONGTEXT NULL AFTER tags_json",
        "ALTER TABLE customers ADD COLUMN shipping_json LONGTEXT NULL AFTER billing_json",
        "ALTER TABLE customers ADD COLUMN payment_note VARCHAR(500) NOT NULL DEFAULT '' AFTER shipping_json",
        "ALTER TABLE customers ADD COLUMN consent_note VARCHAR(500) NOT NULL DEFAULT '' AFTER payment_note"
      ] as $sql){try{$db->exec($sql);}catch(Throwable $e){}}
      try{$db->exec("CREATE TABLE IF NOT EXISTS payment_integrations (
        provider VARCHAR(50) NOT NULL PRIMARY KEY,
        environment VARCHAR(20) NOT NULL DEFAULT 'sandbox',
        enabled TINYINT(1) NOT NULL DEFAULT 0,
        checkout_enabled TINYINT(1) NOT NULL DEFAULT 0,
        credentials_enc LONGTEXT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'not_configured',
        last_test_at DATETIME NULL,
        last_message VARCHAR(500) NOT NULL DEFAULT '',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");}catch(Throwable $e){}
      rh24_setting_set('schema_version','89');rh24_setting_set('db_schema_version','89');
      try{rh24_audit('schema_upgrade','system','v89',['features'=>['shop_color_orgaboard','crm_customer_master','billing_shipping_addresses','payment_preferences','payment_integration_center']],'system');}catch(Throwable $e){}
    }catch(Throwable $e){}
}
function rh24_payment_provider_catalog(): array {
    return [
      'paypal'=>['label'=>'PayPal Checkout','group'=>'Wallet & Karten','description'=>'PayPal, Pay Later sowie je nach Freischaltung Karten und Wallets.','required'=>['client_id','client_secret'],'fields'=>[['client_id','Client ID','text'],['client_secret','Client Secret','password'],['webhook_id','Webhook ID','text']], 'help'=>'PayPal Business-/Developer-App anlegen, zuerst Sandbox nutzen und nach erfolgreichem Test auf Produktion wechseln.'],
      'klarna'=>['label'=>'Klarna','group'=>'Rechnung & Raten','description'=>'Klarna Payments für Rechnung, Ratenkauf und weitere freigeschaltete Zahlungsarten.','required'=>['api_username','api_password'],'fields'=>[['api_username','API-Benutzername','text'],['api_password','API-Passwort','password'],['merchant_id','Merchant ID / Store ID','text']], 'help'=>'Klarna-Händlerkonto und API-Zugangsdaten benötigt. Für Deutschland die europäische API-Region verwenden.'],
      'amazon_pay'=>['label'=>'Amazon Pay','group'=>'Wallet','description'=>'Bezahlen mit den im Amazon-Konto hinterlegten Zahlungs- und Adressdaten.','required'=>['merchant_id','store_id','public_key_id','private_key'],'fields'=>[['merchant_id','Merchant ID','text'],['store_id','Store ID','text'],['public_key_id','Public Key ID','text'],['private_key','Private Key','password']], 'help'=>'Amazon-Pay-Händlerkonto, Store und Schlüssel im Seller/Integration-Bereich anlegen. Sandbox vor Livebetrieb vollständig testen.'],
      'sparkasse'=>['label'=>'Sparkasse E-Payment / S-Payment','group'=>'Bank & Karten','description'=>'Sparkassen E-Payment Plattform; je nach Vertrag inklusive Karten, Wero und weiterer Zahlarten.','required'=>['merchant_id','api_endpoint'],'fields'=>[['merchant_id','Händler-/Vertrags-ID','text'],['terminal_id','Terminal-/Portal-ID','text'],['api_endpoint','Gateway / API-Endpunkt','text'],['api_secret','API-Schlüssel / Secret','password']], 'help'=>'Die konkreten Zugangsdaten hängen vom gebuchten Sparkassen-/S-Payment-Paket ab. Daten aus Händlerportal oder Vertragsunterlagen übernehmen.'],
      'wero'=>['label'=>'Wero','group'=>'Europäische Wallet','description'=>'Wero als europäische Zahlart; bei Sparkassen typischerweise über die E-Payment Plattform.','required'=>['processor'],'fields'=>[['processor','Abwicklung über','text'],['merchant_reference','Händlerreferenz optional','text']], 'help'=>'Bei Nutzung der Sparkassen E-Payment Plattform Wero dort freischalten lassen und im Feld Prozessor den internen Schlüssel sparkasse eintragen.'],
      'stripe'=>['label'=>'Stripe','group'=>'Karten & Wallets','description'=>'Kredit-/Debitkarten sowie Apple Pay und Google Pay über einen zentralen Zahlungsprozessor.','required'=>['publishable_key','secret_key'],'fields'=>[['publishable_key','Publishable Key','text'],['secret_key','Secret Key','password'],['webhook_secret','Webhook Secret','password']], 'help'=>'Stripe-Konto anlegen, Testschlüssel verwenden, Webhook konfigurieren und Wallet-Domainfreigaben durchführen.'],
      'google_pay'=>['label'=>'Google Pay','group'=>'Wallet','description'=>'Google Pay als schnelle Wallet-Zahlung; Abwicklung über unterstützten Zahlungsprozessor.','required'=>['processor'],'fields'=>[['processor','Prozessor (z. B. PayPal/Stripe)','text'],['merchant_id','Google Merchant ID','text']], 'help'=>'Google Pay benötigt einen unterstützten Prozessor. Hinterlege hier den verwendeten Prozessor und – falls vorhanden – die Merchant ID.'],
      'apple_pay'=>['label'=>'Apple Pay','group'=>'Wallet','description'=>'Apple Pay für Safari/iPhone; Abwicklung über unterstützten Zahlungsprozessor.','required'=>['processor'],'fields'=>[['processor','Prozessor (z. B. PayPal/Stripe)','text'],['merchant_id','Apple Merchant ID','text'],['domain','Verifizierte Shop-Domain','text']], 'help'=>'Apple Pay benötigt Prozessorfreischaltung und in der Regel eine verifizierte Shop-Domain.'],
      'mollie'=>['label'=>'Mollie','group'=>'Payment Service Provider','description'=>'Alternative gebündelte Zahlungsplattform für mehrere Zahlarten.','required'=>['api_key'],'fields'=>[['api_key','API Key','password'],['profile_id','Profile ID','text']], 'help'=>'Optionaler PSP als Alternative oder Ergänzung. Erst Testmodus, anschließend Live-Key verwenden.'],
      'sepa_debit'=>['label'=>'SEPA-Lastschrift','group'=>'Bankeinzug','description'=>'SEPA-Lastschrift mit Mandat über einen unterstützten Zahlungsprozessor.','required'=>['processor'],'fields'=>[['processor','Prozessor (z. B. Stripe/Mollie)','text'],['creditor_id','Gläubiger-ID','text']], 'help'=>'Für Lastschrift ist ein rechtssicheres SEPA-Mandat nötig. Als Prozessor z. B. stripe oder mollie eintragen; keine Kontodaten unverschlüsselt im Shop speichern.'],
      'bank_transfer'=>['label'=>'Vorkasse / SEPA-Überweisung','group'=>'Manuell / Bank','description'=>'Klassische Überweisung auf das Geschäftskonto.','required'=>['account_holder','iban'],'fields'=>[['account_holder','Kontoinhaber','text'],['iban','IBAN','text'],['bic','BIC','text']], 'help'=>'Keine externe API erforderlich. Die Kontodaten erscheinen später in Zahlungsanweisungen und Rechnungen.'],
      'invoice'=>['label'=>'Kauf auf Rechnung','group'=>'Manuell / Rechnung','description'=>'Rechnung mit Zahlungsziel; sinnvoll für geprüfte Kunden und B2B.','required'=>['payment_days'],'fields'=>[['payment_days','Zahlungsziel in Tagen','text'],['minimum_order','Mindestbestellwert optional','text']], 'help'=>'Kein externer Zahlungsanbieter. Bonitäts-/Freigabeprozess sollte organisatorisch festgelegt werden.']
    ];
}
function rh24_payment_integrations(bool $withSecrets=false): array {
    $catalog=rh24_payment_provider_catalog();$rows=[];
    foreach($catalog as $id=>$meta)$rows[$id]=['provider'=>$id,'label'=>$meta['label'],'environment'=>'sandbox','enabled'=>false,'checkout_enabled'=>false,'configured'=>false,'status'=>'not_configured','last_test_at'=>null,'last_message'=>'Noch nicht eingerichtet','credentials'=>[]];
    try{
      foreach(rh24_db()->query('SELECT * FROM payment_integrations')->fetchAll() as $r){$id=(string)$r['provider'];if(!isset($rows[$id]))continue;$cred=rh24_decrypt_secret($r['credentials_enc']??'');$rows[$id]=array_merge($rows[$id],['environment'=>(string)$r['environment'],'enabled'=>(bool)$r['enabled'],'checkout_enabled'=>(bool)$r['checkout_enabled'],'configured'=>!empty($cred),'status'=>(string)$r['status'],'last_test_at'=>rh24_iso($r['last_test_at']??null),'last_message'=>(string)($r['last_message']??''),'credentials'=>$withSecrets?$cred:array_map(fn($v)=>trim((string)$v)!=='', $cred)]);}
    }catch(Throwable $e){}
    return array_values($rows);
}
function rh24_payment_integration_secret(string $provider): array {
    try{$q=rh24_db()->prepare('SELECT credentials_enc FROM payment_integrations WHERE provider=?');$q->execute([$provider]);return rh24_decrypt_secret((string)($q->fetchColumn()?:''));}catch(Throwable $e){return [];}
}
function rh24_payment_config_test(string $provider): array {
    $catalog=rh24_payment_provider_catalog();if(!isset($catalog[$provider]))return ['ok'=>false,'message'=>'Unbekannter Zahlungsanbieter.'];
    $cred=rh24_payment_integration_secret($provider);$missing=[];foreach($catalog[$provider]['required'] as $key)if(trim((string)($cred[$key]??''))==='')$missing[]=$key;
    if($missing)return ['ok'=>false,'message'=>'Noch unvollständig: '.implode(', ',$missing).'.'];
    if(in_array($provider,['google_pay','apple_pay','wero','sepa_debit'],true)){
      $processor=mb_strtolower(trim((string)($cred['processor']??'')));if($processor==='')return ['ok'=>false,'message'=>'Bitte einen Zahlungsprozessor angeben.'];
      $connected=false;foreach(rh24_payment_integrations(false) as $p)if(in_array(mb_strtolower($p['provider']),[$processor,str_replace(' ','_',$processor)],true)&&in_array($p['status'],['ready','connected'],true))$connected=true;
      if(!$connected)return ['ok'=>false,'message'=>'Wallet ist konfiguriert, aber der angegebene Prozessor ist noch nicht als bereit geprüft.'];
    }
    return ['ok'=>true,'message'=>'Konfiguration vollständig. Für echte Zahlungen anschließend Sandbox-Transaktion und Webhook-End-to-End-Test durchführen.'];
}

function rh24_product_public_url(string $id): string {
    $map=[
      'std'=>'raeucherhaken-standard.html','aal'=>'raeucherhaken-standard-aal.html','ultra'=>'raeucherhaken-ultra.html','kralle'=>'raeucherhaken-kralle.html','filet'=>'raeucherhaken-filet.html','doppel'=>'raeucherhaken-doppeldorn.html','fleisch'=>'fleischerhaken-s-form-5mm.html',
      'mehl-buche'=>'raeuchermehl-buche.html','mehl-erle'=>'raeuchermehl-erle.html','mehl-birke'=>'raeuchermehl-birke.html','mehl-eiche'=>'raeuchermehl-eiche.html','mehl-kirsche'=>'raeuchermehl-kirsche.html'
    ];
    if(isset($map[$id])) return $map[$id];
    if(str_starts_with($id,'lauge-forelle-')) return 'raeucherlauge-forelle.html';
    if(str_starts_with($id,'lauge-aal-')) return 'raeucherlauge-aal.html';
    return 'artikel.php?id='.rawurlencode($id);
}

function rh24_cross_sell_relation_labels(): array {
    return ['frequently_bought'=>'Häufig zusammen gekauft','accessory'=>'Passendes Zubehör','upsell'=>'Upgrade / Premium','alternative'=>'Alternative'];
}
function rh24_cross_sell_clean(array $items,string $productId=''): array {
    $labels=rh24_cross_sell_relation_labels();$out=[];$seen=[];
    foreach($items as $row){
      if(!is_array($row))continue;$id=trim((string)($row['id']??''));if($id===''||$id===$productId||isset($seen[$id]))continue;
      $relation=(string)($row['relation']??'accessory');if(!isset($labels[$relation]))$relation='accessory';
      $priority=max(1,min(99,(int)($row['priority']??50)));$note=trim((string)($row['note']??''));$note=function_exists('mb_substr')?mb_substr($note,0,140):substr($note,0,140);
      $out[]=['id'=>$id,'relation'=>$relation,'priority'=>$priority,'note'=>$note];$seen[$id]=true;if(count($out)>=24)break;
    }
    usort($out,fn($a,$b)=>($a['priority']<=>$b['priority'])?:strcmp($a['id'],$b['id']));return $out;
}

function rh24_product_is_new_active(array $p, ?int $now=null): bool {
    if(empty($p['is_new'])) return false;
    $until=trim((string)($p['new_until']??''));
    if($until==='') return true;
    $ts=strtotime($until); if($ts===false) return true;
    return ($now??time()) <= $ts;
}

function rh24_invoice_profile(): array {
    $c=rh24_config();
    return [
      'company_name'=>(string)($c['invoice_company_name']??'Räucherhaken24'),'owner'=>(string)($c['invoice_owner']??''),'street'=>(string)($c['invoice_street']??''),'zip'=>(string)($c['invoice_zip']??''),'city'=>(string)($c['invoice_city']??''),'country'=>(string)($c['invoice_country']??'Deutschland'),
      'phone'=>(string)($c['invoice_phone']??''),'email'=>(string)($c['invoice_email']??($c['system_email']??'')),'website'=>(string)($c['invoice_website']??''),'tax_no'=>(string)($c['invoice_tax_no']??''),'vat_id'=>(string)($c['invoice_vat_id']??''),
      'iban'=>(string)($c['invoice_iban']??''),'bic'=>(string)($c['invoice_bic']??''),'bank_name'=>(string)($c['invoice_bank_name']??''),'payment_days'=>max(1,min(60,(int)($c['invoice_payment_days']??7))),'footer'=>(string)($c['invoice_footer']??''),'auto_email'=>(string)($c['invoice_auto_email']??'1')==='1'
    ];
}
function rh24_invoice_profile_readiness(): array {
    $p=rh24_invoice_profile();$missing=[];
    foreach(['company_name'=>'Firmenname','street'=>'Straße','zip'=>'PLZ','city'=>'Ort','email'=>'E-Mail'] as $k=>$l)if(trim((string)$p[$k])==='')$missing[]=$l;
    if(trim((string)$p['tax_no'])===''&&trim((string)$p['vat_id'])==='')$missing[]='Steuernummer oder USt-IdNr.';
    if(trim((string)$p['iban'])==='')$missing[]='IBAN für Rechnungszahlung';
    return ['ready'=>count($missing)===0,'missing'=>$missing,'profile'=>$p];
}
function rh24_next_document_no_v77(string $type): string {
    $db=rh24_db();$prefix=$type==='delivery_note'?'LS':($type==='cancellation'?'ST':'RE');$key='next_'.$type.'_no';$started=!$db->inTransaction();
    try{
      if($started)$db->beginTransaction();
      $q=$db->prepare('SELECT setting_value FROM settings WHERE setting_key=? FOR UPDATE');$q->execute([$key]);$v=$q->fetchColumn();$next=max(1,(int)($v===false?1:$v));
      $db->prepare('INSERT INTO settings(setting_key,setting_value,updated_at) VALUES(?,?,NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()')->execute([$key,(string)($next+1)]);
      if($started)$db->commit();
      return $prefix.'-'.date('Y').'-'.str_pad((string)$next,6,'0',STR_PAD_LEFT);
    }catch(Throwable $e){if($started&&$db->inTransaction())$db->rollBack();throw $e;}
}
function rh24_document_row(string $id): ?array {
    $q=rh24_db()->prepare('SELECT * FROM documents WHERE id=? LIMIT 1');$q->execute([$id]);$r=$q->fetch();if(!$r)return null;
    $r['payload']=rh24_json_decode((string)($r['payload_json']??''),[]);unset($r['payload_json']);$r['version_no']=(int)$r['version_no'];
    foreach(['created_at','updated_at','issued_at','locked_at','cancelled_at','emailed_at','pdf_generated_at'] as $f)$r[$f]=rh24_iso($r[$f]??null);return $r;
}
function rh24_document_by_order_type(string $orderNo,string $type): ?array {$q=rh24_db()->prepare('SELECT id FROM documents WHERE order_no=? AND document_type=? LIMIT 1');$q->execute([$orderNo,$type]);$id=$q->fetchColumn();return $id?rh24_document_row((string)$id):null;}
function rh24_document_payload_v77(array $o,string $type): array {
    $p=rh24_document_payload_from_order($o,$type==='cancellation'?'invoice':$type);$profile=rh24_invoice_profile();$issued=date('Y-m-d');$days=(int)$profile['payment_days'];
    $p['seller']=$profile;$p['invoice_date']=$issued;$p['due_date']=date('Y-m-d',strtotime('+'.$days.' days'));$p['delivery_date']='';$p['currency']='EUR';$p['tax_rate']=(float)($o['totals']['vat_rate']??rh24_setting_get('vat_rate','19'));
    if($type==='cancellation'){
      $p['title']='Stornorechnung';$p['document_type']='cancellation';$p['items']=array_map(function($x){$x['unit_price']=-abs((float)($x['unit_price']??0));$x['line_total']=-abs((float)($x['line_total']??((float)($x['unit_price']??0)*(int)($x['qty']??1))));return $x;},$p['items']??[]);
      foreach(['subtotal','shipping','net','vat','gross'] as $k)if(isset($p['totals'][$k]))$p['totals'][$k]=-abs((float)$p['totals'][$k]);
    }
    return $p;
}
function rh24_get_or_create_document_v77(string $orderNo,string $type): array {
    if(!in_array($type,['invoice','delivery_note','cancellation'],true))throw new InvalidArgumentException('Ungültiger Dokumenttyp.');
    $existing=rh24_document_by_order_type($orderNo,$type);if($existing)return $existing;$o=rh24_order_by_no($orderNo);if(!$o)throw new RuntimeException('Bestellung nicht gefunden.');
    $db=rh24_db();$id='DOC-'.strtoupper(bin2hex(random_bytes(6)));$no=rh24_next_document_no_v77($type);$payload=rh24_document_payload_v77($o,$type);$uid=rh24_user_id()?:null;
    $db->prepare("INSERT INTO documents(id,document_type,document_no,order_no,status,version_no,payload_json,note,created_by,updated_by,created_at,updated_at) VALUES(?,?,?,?, 'draft',1,?,'',?,?,NOW(),NOW())")->execute([$id,$type,$no,$orderNo,rh24_json_encode($payload),$uid,$uid]);
    $db->prepare('INSERT INTO document_versions(document_id,version_no,payload_json,change_note,edited_by,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$id,1,rh24_json_encode($payload),'Dokument aus Bestellung erzeugt',$uid]);
    try{rh24_audit('document_created','document',$id,['document_no'=>$no,'order_no'=>$orderNo,'type'=>$type]);}catch(Throwable $e){}
    return rh24_document_row($id)??[];
}
function rh24_pdf_enc(string $s): string {$s=str_replace(["\r","\n","\t"],[' ',' ',' '],$s);if(function_exists('iconv')){$x=@iconv('UTF-8','Windows-1252//TRANSLIT',$s);if($x!==false)$s=$x;}return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);}
class Rh24MiniPdf {
    public array $pages=[[]]; public int $page=0; public float $W=595.28,$H=841.89;
    public function newPage(): void {$this->pages[]=[];$this->page=count($this->pages)-1;}
    public function text(float $x,float $y,float $size,string $text,bool $bold=false,array $rgb=[0.12,0.11,0.10]): void {$font=$bold?'F2':'F1';$yy=$this->H-$y;$e=rh24_pdf_enc($text);$this->pages[$this->page][]=sprintf('%.3f %.3f %.3f rg BT /%s %.2f Tf %.2f %.2f Td (%s) Tj ET',$rgb[0],$rgb[1],$rgb[2],$font,$size,$x,$yy,$e);}
    public function line(float $x1,float $y1,float $x2,float $y2,float $w=0.7,array $rgb=[0.78,0.74,0.69]): void {$this->pages[$this->page][]=sprintf('%.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S',$rgb[0],$rgb[1],$rgb[2],$w,$x1,$this->H-$y1,$x2,$this->H-$y2);}
    public function fillRect(float $x,float $y,float $w,float $h,array $rgb): void {$this->pages[$this->page][]=sprintf('%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f',$rgb[0],$rgb[1],$rgb[2],$x,$this->H-$y-$h,$w,$h);}
    public function wrap(string $text,float $width,float $size): array {$max=max(8,(int)floor($width/max(3.2,$size*.52)));$words=preg_split('/\s+/u',trim($text))?:[];$lines=[];$line='';foreach($words as $w){$try=trim($line.' '.$w);if((function_exists('mb_strlen')?mb_strlen($try):strlen($try))>$max&&$line!==''){$lines[]=$line;$line=$w;}else$line=$try;}if($line!=='')$lines[]=$line;return $lines?:[''];}
    public function multiText(float $x,float $y,float $width,float $size,string $text,bool $bold=false,float $leading=1.35,array $rgb=[0.12,0.11,0.10]): float {$lines=$this->wrap($text,$width,$size);foreach($lines as $line){$this->text($x,$y,$size,$line,$bold,$rgb);$y+=$size*$leading;}return $y;}
    public function output(string $title='Dokument'): string {
      $objs=[];$kids=[];$font1=3;$font2=4;$n=count($this->pages);for($i=0;$i<$n;$i++)$kids[]=(5+$i*2).' 0 R';
      $objs[1]='<< /Type /Catalog /Pages 2 0 R >>';$objs[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.$n.' >>';$objs[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';$objs[4]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
      foreach($this->pages as $i=>$cmds){$po=5+$i*2;$co=6+$i*2;$stream=implode("\n",$cmds);$objs[$po]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$co.' 0 R >>';$objs[$co]='<< /Length '.strlen($stream).' >>'."\nstream\n".$stream."\nendstream";}
      ksort($objs);$pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offset=[0];foreach($objs as $id=>$body){$offset[$id]=strlen($pdf);$pdf.=$id." 0 obj\n".$body."\nendobj\n";}$xref=strlen($pdf);$max=max(array_keys($objs));$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf('%010d 00000 n ',(int)($offset[$i]??0))."\n";$pdf.='trailer'."\n<< /Size ".($max+1)." /Root 1 0 R /Info << /Title (".rh24_pdf_enc($title).") /Producer (Raeucherhaken24 Orgaboard V77) >> >>\nstartxref\n".$xref."\n%%EOF";return $pdf;
    }
}
function rh24_pdf_money(float $v): string {return number_format($v,2,',','.').' EUR';}
function rh24_document_pdf_content(array $doc): string {
    $payload=$doc['payload']??[];$profile=$payload['seller']??rh24_invoice_profile();$c=$payload['customer']??[];$items=$payload['items']??[];$tot=$payload['totals']??[];$type=(string)($doc['document_type']??'invoice');$invoice=$type!=='delivery_note';$title=$type==='delivery_note'?'LIEFERSCHEIN':($type==='cancellation'?'STORNORECHNUNG':'RECHNUNG');
    $pdf=new Rh24MiniPdf();$brown=[0.36,0.23,0.12];$orange=[0.82,0.43,0.14];$muted=[0.39,0.37,0.35];
    $header=function()use($pdf,$profile,$title,$doc,$brown,$orange,$muted){$pdf->fillRect(0,0,595.28,12,$brown);$pdf->text(48,45,19,'RAEUCHERHAKEN',true,$brown);$pdf->text(246,45,25,'24',true,$orange);$pdf->text(48,62,8,trim(($profile['company_name']??'').' · '.($profile['street']??'').' · '.($profile['zip']??'').' '.($profile['city']??'')),false,$muted);$pdf->text(390,44,17,$title,true,$brown);$pdf->text(390,62,9,(string)($doc['document_no']??''),true,$muted);$pdf->line(48,76,547,76,1.1,$brown);};
    $footer=function()use($pdf,$profile,$muted){$tax=trim((string)($profile['vat_id']??''))!==''?'USt-IdNr.: '.$profile['vat_id']:'Steuernr.: '.($profile['tax_no']??'');$left=trim(($profile['company_name']??'').' · '.($profile['owner']??''));$pdf->line(48,792,547,792,.5,[.82,.79,.75]);$pdf->text(48,808,7.5,$left,false,$muted);$pdf->text(48,820,7.5,trim(($profile['email']??'').' · '.($profile['phone']??'').' · '.($profile['website']??'')),false,$muted);$pdf->text(330,808,7.5,$tax,false,$muted);if(trim((string)($profile['iban']??''))!=='')$pdf->text(330,820,7.5,'IBAN: '.$profile['iban'].(trim((string)($profile['bic']??''))!==''?' · BIC: '.$profile['bic']:''),false,$muted);};
    $header();$y=105;$pdf->text(48,$y,8,'EMPFÄNGER',true,$orange);$y+=17;$pdf->text(48,$y,11,(string)($c['name']??''),true,$brown);$y+=15;if(trim((string)($c['company']??''))!==''){$pdf->text(48,$y,9,(string)$c['company']);$y+=13;}$pdf->text(48,$y,9,(string)($c['street']??''));$y+=13;$pdf->text(48,$y,9,trim((string)($c['zip']??'').' '.(string)($c['city']??'')));
    $my=105;$pdf->fillRect(340,96,207,$invoice?124:100,[.97,.95,.92]);$meta=[['Dokument-Nr.',(string)($doc['document_no']??'')],['Bestellung',(string)($doc['order_no']??'')],['Rechnungsdatum',date('d.m.Y',strtotime((string)($doc['issued_at']??$doc['created_at']??'now')))],['Zahlung',(string)($payload['payment_method']??'')]];if($invoice){$delivery=trim((string)($payload['delivery_date']??''));$meta[]=['Lieferdatum',$delivery!==''?date('d.m.Y',strtotime($delivery)):'gemäß Lieferschein'];$meta[]=['Fällig',!empty($payload['due_date'])?date('d.m.Y',strtotime((string)$payload['due_date'])):''];}foreach($meta as [$k,$v]){$pdf->text(354,$my,7.4,$k,true,$muted);$pdf->text(445,$my,8.2,$v,false,$brown);$my+=17;}
    $y=max($y+36,$invoice?252:225);$pdf->fillRect(48,$y,499,26,$brown);$cols=$invoice?[[52,'Pos.',24],[78,'Art.-Nr.',58],[139,'Beschreibung',222],[365,'Menge',47],[416,'Einzel',62],[482,'Gesamt',61]]:[[52,'Pos.',28],[83,'Art.-Nr.',74],[161,'Beschreibung',300],[466,'Menge',78]];foreach($cols as [$x,$h])$pdf->text($x,$y+17,8,$h,true,[1,1,1]);$y+=36;$pos=1;
    foreach($items as $it){$desc=trim((string)($it['name']??$it['id']??'Artikel'));$metaIt=$it['meta']??[];if(is_array($metaIt)&&$metaIt)$desc.=' · '.implode(' · ',array_values(array_filter(array_map('strval',$metaIt))));$lines=$pdf->wrap($desc,$invoice?215:290,8.2);$rowH=max(24,12+count($lines)*10);if($y+$rowH>760){$footer();$pdf->newPage();$header();$y=105;$pdf->fillRect(48,$y,499,26,$brown);foreach($cols as [$x,$h])$pdf->text($x,$y+17,8,$h,true,[1,1,1]);$y+=36;}$pdf->text(52,$y+11,8,(string)$pos);$pdf->text(78,$y+11,8,(string)($it['article_no']??'–'));$dy=$y+11;foreach($lines as $line){$pdf->text($invoice?139:161,$dy,8.2,$line);$dy+=10;}$qty=(float)($it['qty']??1);$pdf->text($invoice?373:480,$y+11,8,rtrim(rtrim(number_format($qty,2,'.',''),'0'),'.'));if($invoice){$u=(float)($it['unit_price']??0);$lt=(float)($it['line_total']??($u*$qty));$pdf->text(421,$y+11,8,rh24_pdf_money($u));$pdf->text(486,$y+11,8,rh24_pdf_money($lt),true,$brown);}$pdf->line(48,$y+$rowH,547,$y+$rowH,.45,[.88,.85,.82]);$y+=$rowH;$pos++;}
    if($invoice){if($y>620){$footer();$pdf->newPage();$header();$y=120;}$y+=16;$x=340;$pdf->text($x,$y,8,'Warenwert brutto',false,$muted);$pdf->text(470,$y,9,rh24_pdf_money((float)($tot['subtotal']??0)),true,$brown);$y+=18;$pdf->text($x,$y,8,'Versand & Verpackung',false,$muted);$pdf->text(470,$y,9,rh24_pdf_money((float)($tot['shipping']??0)),true,$brown);$y+=18;$pdf->text($x,$y,8,'Netto',false,$muted);$pdf->text(470,$y,9,rh24_pdf_money((float)($tot['net']??0)),true,$brown);$y+=18;$rate=(float)($payload['tax_rate']??$tot['vat_rate']??19);$pdf->text($x,$y,8,'Umsatzsteuer '.rtrim(rtrim(number_format($rate,2,'.',''),'0'),'.').' %',false,$muted);$pdf->text(470,$y,9,rh24_pdf_money((float)($tot['vat']??0)),true,$brown);$y+=18;$pdf->line($x,$y,547,$y,1,$brown);$y+=17;$pdf->text($x,$y,11,'Gesamtbetrag',true,$brown);$pdf->text(455,$y,12,rh24_pdf_money((float)($tot['gross']??0)),true,$orange);$y+=30;if(trim((string)($profile['iban']??''))!==''){$pdf->text(48,$y,8,'ZAHLUNGSINFORMATION',true,$orange);$y+=14;$pdf->text(48,$y,8.5,'Bitte überweisen Sie den Rechnungsbetrag unter Angabe der Rechnungsnummer.');$y+=13;$pdf->text(48,$y,8.5,'IBAN: '.$profile['iban'].(trim((string)($profile['bic']??''))!==''?' · BIC: '.$profile['bic']:''),true,$brown);}}
    else{$y+=18;$pdf->text(48,$y,8,'VERSAND / LIEFERUNG',true,$orange);$y+=14;$pdf->text(48,$y,8.5,'Versanddienstleister: '.(string)($payload['carrier']??'').(trim((string)($payload['tracking']??''))!==''?' · Tracking: '.$payload['tracking']:''));}
    $note=trim((string)($payload['document_note']??''));if($note!==''){$y+=24;$pdf->text(48,$y,8,'HINWEIS',true,$orange);$y+=13;$pdf->multiText(48,$y,490,8,$note,false,1.35,$muted);}if($invoice&&trim((string)($profile['footer']??''))!==''){$y=min(750,$y+22);$pdf->multiText(48,$y,490,8,(string)$profile['footer'],false,1.3,$muted);}$footer();return $pdf->output($title.' '.(string)($doc['document_no']??''));
}
function rh24_document_pdf_filename(array $doc): string {$t=$doc['document_type']==='delivery_note'?'Lieferschein':($doc['document_type']==='cancellation'?'Stornorechnung':'Rechnung');return preg_replace('/[^A-Za-z0-9._-]+/','-',iconv('UTF-8','ASCII//TRANSLIT',$t.'-'.$doc['document_no'])?:($t.'-'.$doc['document_no'])).'.pdf';}
function rh24_issue_document_v77(string $id): array {
    $r=rh24_document_row($id);if(!$r)throw new RuntimeException('Dokument nicht gefunden.');if($r['status']==='cancelled')throw new RuntimeException('Storniertes Dokument kann nicht ausgegeben werden.');
    if($r['status']!=='issued'){$db=rh24_db();$db->prepare("UPDATE documents SET status='issued',issued_at=COALESCE(issued_at,NOW()),locked_at=COALESCE(locked_at,NOW()),updated_at=NOW() WHERE id=?")->execute([$id]);$r=rh24_document_row($id)??$r;}
    $pdf=rh24_document_pdf_content($r);$hash=hash('sha256',$pdf);rh24_db()->prepare('UPDATE documents SET pdf_sha256=?,pdf_generated_at=NOW() WHERE id=?')->execute([$hash,$id]);$r['pdf_sha256']=$hash;return $r;
}
function rh24_send_mail_attachments(string $to,string $subject,string $html,array $attachments,string $type='documents'): bool {
    if(!filter_var($to,FILTER_VALIDATE_EMAIL))return false;$from=(string)rh24_setting_get('system_email','service@raeucherhaken24.com');if(!filter_var($from,FILTER_VALIDATE_EMAIL))$from='service@raeucherhaken24.com';$boundary='=_RH24_'.bin2hex(random_bytes(12));$sender=preg_replace('/[\r\n]+/',' ',trim((string)rh24_setting_get('newsletter_sender_name','Räucherhaken24')))?:'Räucherhaken24';
    $headers=['MIME-Version: 1.0','From: '.$sender.' <'.$from.'>','Reply-To: '.$from,'Content-Type: multipart/mixed; boundary="'.$boundary.'"','X-Mailer: Räucherhaken24 Orgaboard V77'];$body='--'.$boundary."\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n".$html."\r\n";
    foreach($attachments as $a){$name=preg_replace('/[\r\n"]+/','',(string)($a['name']??'Dokument.pdf'));$data=(string)($a['data']??'');$mime=(string)($a['mime']??'application/pdf');$body.='--'.$boundary."\r\nContent-Type: ".$mime.'; name="'.$name."\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"".$name."\"\r\n\r\n".chunk_split(base64_encode($data))."\r\n";}$body.='--'.$boundary."--\r\n";$encoded=function_exists('mb_encode_mimeheader')?mb_encode_mimeheader($subject,'UTF-8'):$subject;$ok=@mail($to,$encoded,$body,implode("\r\n",$headers));rh24_mail_log(null,$to,$type,$subject,$ok?'sent':'failed');return $ok;
}
function rh24_order_document_bundle(string $orderNo,bool $issue=true): array {
    $readiness=rh24_invoice_profile_readiness();$docs=[];foreach(['invoice','delivery_note'] as $type){$d=rh24_get_or_create_document_v77($orderNo,$type);if($issue&&$readiness['ready'])$d=rh24_issue_document_v77((string)$d['id']);$docs[$type]=$d;}return ['documents'=>$docs,'readiness'=>$readiness];
}
function rh24_email_order_documents(string $orderNo,string $recipient=''): array {
    $bundle=rh24_order_document_bundle($orderNo,true);if(!$bundle['readiness']['ready'])return ['ok'=>false,'reason'=>'profile_incomplete','missing'=>$bundle['readiness']['missing'],'documents'=>$bundle['documents']];$o=rh24_order_by_no($orderNo);if(!$o)throw new RuntimeException('Bestellung nicht gefunden.');$email=$recipient!==''?$recipient:(string)($o['customer']['email']??'');if(!filter_var($email,FILTER_VALIDATE_EMAIL))return ['ok'=>false,'reason'=>'email_invalid','documents'=>$bundle['documents']];$atts=[];foreach($bundle['documents'] as $d){$atts[]=['name'=>rh24_document_pdf_filename($d),'data'=>rh24_document_pdf_content($d),'mime'=>'application/pdf'];}$name=htmlspecialchars((string)($o['customer']['name']??'Kunde'),ENT_QUOTES,'UTF-8');$html='<p>Guten Tag '.$name.',</p><p>vielen Dank für Ihre Bestellung bei <b>Räucherhaken24</b>.</p><p>Im Anhang erhalten Sie Ihre <b>Rechnung</b> und den <b>Lieferschein</b> als PDF.</p><p><b>Bestellnummer:</b> '.htmlspecialchars($orderNo,ENT_QUOTES,'UTF-8').'<br><b>Gesamtbetrag:</b> '.number_format((float)($o['totals']['gross']??0),2,',','.').' €</p><p>Die Dokumente können zusätzlich im Kundenbereich über den Bestellfortschritt erneut abgerufen werden.</p><p>Räucherhaken24</p>';$ok=rh24_send_mail_attachments($email,'Rechnung & Lieferschein zu Ihrer Bestellung '.$orderNo,$html,$atts,'order_documents');$status=$ok?'sent':'failed';$db=rh24_db();foreach($bundle['documents'] as $d)$db->prepare('UPDATE documents SET emailed_at=CASE WHEN ?="sent" THEN NOW() ELSE emailed_at END,email_status=?,updated_at=NOW() WHERE id=?')->execute([$status,$status,$d['id']]);try{rh24_audit('order_documents_email','order',$orderNo,['recipient'=>$email,'status'=>$status]);}catch(Throwable $e){}return ['ok'=>$ok,'reason'=>$ok?'sent':'mail_failed','documents'=>$bundle['documents']];
}
function rh24_cancel_invoice_v77(string $invoiceId,string $reason,bool $emailCustomer=true): array {
    $reason=trim($reason);if($reason==='')throw new InvalidArgumentException('Stornogrund ist erforderlich.');$invoice=rh24_document_row($invoiceId);if(!$invoice||$invoice['document_type']!=='invoice')throw new RuntimeException('Rechnung nicht gefunden.');if($invoice['status']==='cancelled')throw new RuntimeException('Rechnung ist bereits storniert.');$orderNo=(string)$invoice['order_no'];$cancel=rh24_document_by_order_type($orderNo,'cancellation');if(!$cancel){$cancel=rh24_get_or_create_document_v77($orderNo,'cancellation');$payload=$cancel['payload']??[];$payload['document_note']='Storno zu Rechnung '.$invoice['document_no'].'. Grund: '.$reason;$db=rh24_db();$db->prepare('UPDATE documents SET payload_json=?,updated_at=NOW() WHERE id=?')->execute([rh24_json_encode($payload),$cancel['id']]);$cancel=rh24_document_row((string)$cancel['id']);}$cancel=rh24_issue_document_v77((string)$cancel['id']);$db=rh24_db();$db->prepare("UPDATE documents SET status='cancelled',cancelled_at=NOW(),cancel_reason=?,updated_at=NOW() WHERE id=?")->execute([$reason,$invoiceId]);if($emailCustomer){$o=rh24_order_by_no($orderNo);$email=(string)($o['customer']['email']??'');if(filter_var($email,FILTER_VALIDATE_EMAIL)){$html='<p>Guten Tag,</p><p>die Rechnung <b>'.htmlspecialchars((string)$invoice['document_no'],ENT_QUOTES,'UTF-8').'</b> wurde storniert.</p><p>Im Anhang erhalten Sie den Stornobeleg.</p><p>Räucherhaken24</p>';$ok=rh24_send_mail_attachments($email,'Stornorechnung '.$cancel['document_no'],$html,[['name'=>rh24_document_pdf_filename($cancel),'data'=>rh24_document_pdf_content($cancel),'mime'=>'application/pdf']],'invoice_cancellation');$db->prepare('UPDATE documents SET emailed_at=CASE WHEN ?=1 THEN NOW() ELSE emailed_at END,email_status=?,updated_at=NOW() WHERE id=?')->execute([$ok?1:0,$ok?'sent':'failed',$cancel['id']]);}}try{rh24_audit('invoice_cancelled','document',$invoiceId,['reason'=>$reason,'cancellation_document'=>$cancel['document_no']]);}catch(Throwable $e){}return ['invoice'=>rh24_document_row($invoiceId),'cancellation'=>$cancel];
}
function rh24_document_versions_v77(string $id): array {$q=rh24_db()->prepare('SELECT v.*,u.display_name edited_by_name FROM document_versions v LEFT JOIN users u ON u.id=v.edited_by WHERE v.document_id=? ORDER BY v.version_no DESC,v.created_at DESC');$q->execute([$id]);$rows=$q->fetchAll();foreach($rows as &$r){$r['version_no']=(int)$r['version_no'];$r['created_at']=rh24_iso($r['created_at']);unset($r['payload_json']);}unset($r);return $rows;}


require_once __DIR__ . '/finance-v91.php';
require_once __DIR__ . '/pos-v94.php';
require_once __DIR__ . '/labels-v96.php';
require_once __DIR__ . '/vehicle-v98.php';
require_once __DIR__ . '/trip-receipts-v99.php';
