<?php
declare(strict_types=1);

const RH24_DB_CONFIG = __DIR__ . '/private/db-config.php';

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function write_config(array $cfg): void {
    $dir = dirname(RH24_DB_CONFIG);
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Privates Konfigurationsverzeichnis konnte nicht erstellt werden.');
    }
    $php = "<?php\ndeclare(strict_types=1);\nreturn " . var_export($cfg, true) . ";\n";
    $tmp = RH24_DB_CONFIG . '.tmp';
    if (file_put_contents($tmp, $php, LOCK_EX) === false) throw new RuntimeException('Datenbank-Konfiguration konnte nicht geschrieben werden.');
    @chmod($tmp, 0640);
    if (!rename($tmp, RH24_DB_CONFIG)) { @unlink($tmp); throw new RuntimeException('Datenbank-Konfiguration konnte nicht aktiviert werden.'); }
}
function connect(array $cfg): PDO {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['database']);
    return new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
function schema(PDO $db): void {
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
  setting_value LONGTEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  name VARCHAR(180) NOT NULL DEFAULT '',
  customer_type VARCHAR(20) NOT NULL DEFAULT 'private',
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  salutation VARCHAR(30) NOT NULL DEFAULT '',
  email VARCHAR(190) NULL,
  phone VARCHAR(80) NOT NULL DEFAULT '',
  mobile VARCHAR(80) NOT NULL DEFAULT '',
  website VARCHAR(220) NOT NULL DEFAULT '',
  company VARCHAR(180) NOT NULL DEFAULT '',
  street VARCHAR(220) NOT NULL DEFAULT '',
  zip VARCHAR(30) NOT NULL DEFAULT '',
  city VARCHAR(120) NOT NULL DEFAULT '',
  country VARCHAR(80) NOT NULL DEFAULT 'Deutschland',
  vat_id VARCHAR(60) NOT NULL DEFAULT '',
  tax_no VARCHAR(80) NOT NULL DEFAULT '',
  payment_method VARCHAR(80) NOT NULL DEFAULT '',
  payment_terms_days SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  discount_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
  preferred_contact VARCHAR(30) NOT NULL DEFAULT 'email',
  source VARCHAR(80) NOT NULL DEFAULT 'Orgaboard',
  tags_json LONGTEXT NULL,
  billing_json LONGTEXT NULL,
  shipping_json LONGTEXT NULL,
  payment_note VARCHAR(500) NOT NULL DEFAULT '',
  consent_note VARCHAR(500) NOT NULL DEFAULT '',
  notes TEXT NULL,
  sales_rep_id VARCHAR(40) NULL,
  advisor_assigned_at DATETIME NULL,
  advisor_assignment_source VARCHAR(40) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_customers_email (email),
  KEY idx_customers_name (name),
  KEY idx_customers_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS payment_integrations (
  provider VARCHAR(50) NOT NULL PRIMARY KEY,
  environment VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  checkout_enabled TINYINT(1) NOT NULL DEFAULT 0,
  credentials_enc LONGTEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'not_configured',
  last_test_at DATETIME NULL,
  last_message VARCHAR(500) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
  order_no VARCHAR(60) NOT NULL PRIMARY KEY,
  source VARCHAR(40) NOT NULL DEFAULT 'shop',
  sales_channel VARCHAR(40) NOT NULL DEFAULT 'other',
  status VARCHAR(40) NOT NULL,
  status_label VARCHAR(100) NOT NULL,
  payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  payment_method VARCHAR(160) NOT NULL DEFAULT '',
  carrier VARCHAR(80) NOT NULL DEFAULT 'DPD',
  tracking VARCHAR(180) NOT NULL DEFAULT '',
  internal_note TEXT NULL,
  customer_id VARCHAR(40) NULL,
  sales_rep_id VARCHAR(40) NULL,
  commission_sales_rep_id VARCHAR(40) NULL,
  commission_attribution VARCHAR(40) NOT NULL DEFAULT '',
  commission_note VARCHAR(255) NOT NULL DEFAULT '',
  commission_assigned_at DATETIME NULL,
  consultation_id VARCHAR(60) NULL,
  customer_json LONGTEXT NOT NULL,
  items_json LONGTEXT NOT NULL,
  totals_json LONGTEXT NOT NULL,
  customer_note TEXT NULL,
  history_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_orders_status (status),
  KEY idx_orders_payment (payment_status),
  KEY idx_orders_created (created_at),
  KEY idx_orders_customer (customer_id),
  KEY idx_orders_sales_channel (sales_channel,created_at),
  KEY idx_orders_commission_rep (commission_sales_rep_id,created_at),
  CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prototypes (
  reference VARCHAR(60) NOT NULL PRIMARY KEY,
  order_no VARCHAR(60) NULL,
  source VARCHAR(40) NOT NULL DEFAULT 'prototype-form',
  project_name VARCHAR(220) NOT NULL,
  summary TEXT NULL,
  customer_id VARCHAR(40) NULL,
  sales_rep_id VARCHAR(40) NULL,
  consultation_id VARCHAR(60) NULL,
  customer_json LONGTEXT NOT NULL,
  fields_json LONGTEXT NOT NULL,
  files_json LONGTEXT NOT NULL,
  status VARCHAR(40) NOT NULL,
  status_label VARCHAR(100) NOT NULL,
  payment_status VARCHAR(40) NOT NULL DEFAULT 'pending',
  internal_note TEXT NULL,
  tracking VARCHAR(180) NOT NULL DEFAULT '',
  history_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_proto_status (status),
  KEY idx_proto_order (order_no),
  KEY idx_proto_customer (customer_id),
  CONSTRAINT fk_proto_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dealers (
  id VARCHAR(40) NOT NULL PRIMARY KEY,
  company VARCHAR(180) NOT NULL,
  contact VARCHAR(180) NOT NULL DEFAULT '',
  email VARCHAR(190) NULL,
  phone VARCHAR(80) NOT NULL DEFAULT '',
  tier VARCHAR(30) NOT NULL DEFAULT 'Bronze',
  discount DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_dealers_email (email),
  KEY idx_dealers_company (company)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS products (
  id VARCHAR(80) NOT NULL PRIMARY KEY,
  sku VARCHAR(100) NOT NULL DEFAULT '',
  article_no VARCHAR(40) NULL,
  name VARCHAR(220) NOT NULL,
  category VARCHAR(120) NOT NULL DEFAULT 'Sonstiges',
  product_type VARCHAR(40) NOT NULL DEFAULT 'product',
  base_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  unit VARCHAR(80) NOT NULL DEFAULT 'Stück',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory (
  id VARCHAR(80) NOT NULL PRIMARY KEY,
  name VARCHAR(220) NOT NULL,
  stock INT NOT NULL DEFAULT 0,
  minimum INT NOT NULL DEFAULT 0,
  unit VARCHAR(80) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id VARCHAR(60) NOT NULL PRIMARY KEY,
  product VARCHAR(220) NOT NULL DEFAULT '',
  rating TINYINT NOT NULL DEFAULT 5,
  name VARCHAR(120) NOT NULL DEFAULT '',
  title VARCHAR(180) NOT NULL DEFAULT '',
  text TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'new',
  reply TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_reviews_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content (
  id VARCHAR(60) NOT NULL PRIMARY KEY,
  title VARCHAR(240) NOT NULL,
  type VARCHAR(60) NOT NULL DEFAULT 'Rezept',
  status VARCHAR(30) NOT NULL DEFAULT 'draft',
  body LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_content_status (status),
  KEY idx_content_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_stats (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  stat_key VARCHAR(160) NOT NULL,
  label VARCHAR(220) NOT NULL,
  stat_count INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_ai_key (stat_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_reps (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consultations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  actor VARCHAR(80) NOT NULL DEFAULT 'system',
  action_name VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80) NOT NULL DEFAULT '',
  entity_id VARCHAR(100) NOT NULL DEFAULT '',
  detail_json LONGTEXT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_activity_created (created_at),
  KEY idx_activity_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    foreach (preg_split('/;\s*(?:\r?\n|$)/', trim($sql)) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') $db->exec($stmt);
    }
}
function schema_v38_extra(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS users (id VARCHAR(40) NOT NULL PRIMARY KEY,username VARCHAR(80) NOT NULL,display_name VARCHAR(180) NOT NULL,email VARCHAR(190) NULL,role VARCHAR(40) NOT NULL DEFAULT 'field_sales',sales_rep_id VARCHAR(40) NULL,password_hash VARCHAR(255) NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'active',must_change_password TINYINT(1) NOT NULL DEFAULT 0,last_login DATETIME NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_users_username(username),KEY idx_users_role(role),KEY idx_users_sales_rep(sales_rep_id),KEY idx_users_status(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS documents (id VARCHAR(60) NOT NULL PRIMARY KEY,document_type VARCHAR(30) NOT NULL,document_no VARCHAR(60) NOT NULL,order_no VARCHAR(60) NOT NULL,status VARCHAR(30) NOT NULL DEFAULT 'draft',version_no INT NOT NULL DEFAULT 1,payload_json LONGTEXT NOT NULL,note TEXT NULL,issued_at DATETIME NULL,created_by VARCHAR(40) NULL,updated_by VARCHAR(40) NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE KEY uq_documents_no(document_no),UNIQUE KEY uq_documents_order_type(order_no,document_type),KEY idx_documents_order(order_no)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS document_versions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,document_id VARCHAR(60) NOT NULL,version_no INT NOT NULL,payload_json LONGTEXT NOT NULL,change_note VARCHAR(255) NOT NULL DEFAULT '',edited_by VARCHAR(40) NULL,created_at DATETIME NOT NULL,KEY idx_doc_versions_doc(document_id,version_no)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS messages (id VARCHAR(60) NOT NULL PRIMARY KEY,thread_id VARCHAR(60) NOT NULL,sender_user_id VARCHAR(40) NOT NULL,recipient_user_id VARCHAR(40) NOT NULL,subject VARCHAR(220) NOT NULL DEFAULT '',body TEXT NOT NULL,read_at DATETIME NULL,created_at DATETIME NOT NULL,KEY idx_messages_recipient(recipient_user_id,read_at,created_at),KEY idx_messages_thread(thread_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS product_cost_profiles (product_id VARCHAR(80) NOT NULL PRIMARY KEY,material_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,labor_minutes DECIMAL(10,2) NOT NULL DEFAULT 0.00,labor_hourly_rate DECIMAL(12,2) NOT NULL DEFAULT 32.00,packaging_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,other_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,overhead_percent DECIMAL(6,2) NOT NULL DEFAULT 12.00,selling_fee_percent DECIMAL(6,2) NOT NULL DEFAULT 2.50,target_margin_percent DECIMAL(6,2) NOT NULL DEFAULT 45.00,vat_percent DECIMAL(6,2) NOT NULL DEFAULT 19.00,calculated_gross DECIMAL(12,2) NOT NULL DEFAULT 0.00,updated_by VARCHAR(40) NULL,updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function schema_v40_extra(PDO $db): void {
    try { $db->exec("ALTER TABLE users ADD COLUMN permissions_json LONGTEXT NULL AFTER sales_rep_id"); } catch(Throwable) {}
    try { $db->exec("ALTER TABLE users ADD COLUMN welcome_sent_at DATETIME NULL AFTER must_change_password"); } catch(Throwable) {}
    $db->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,user_id VARCHAR(40) NOT NULL,purpose VARCHAR(30) NOT NULL DEFAULT 'reset',token_hash CHAR(64) NOT NULL,expires_at DATETIME NOT NULL,used_at DATETIME NULL,created_at DATETIME NOT NULL,UNIQUE KEY uq_reset_token_hash(token_hash),KEY idx_reset_user(user_id,used_at,expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS mail_log (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,user_id VARCHAR(40) NULL,recipient VARCHAR(190) NOT NULL,mail_type VARCHAR(40) NOT NULL,subject VARCHAR(220) NOT NULL,status VARCHAR(30) NOT NULL,created_at DATETIME NOT NULL,KEY idx_mail_user(user_id,created_at),KEY idx_mail_status(status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function set_setting(PDO $db, string $key, mixed $value): void {
    $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()');
    $stmt->execute([$key, is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
}
function seed_inventory(PDO $db): void {
    $catalog = [
      ['std','Räucherhaken Standard – 10er-Set',320,50,'10er-Set'],
      ['aal','Räucherhaken Standard Aal – 10er-Set',180,50,'10er-Set'],
      ['ultra','Räucherhaken Ultra – 10er-Set',120,50,'10er-Set'],
      ['kralle','Räucherhaken Kralle – 10er-Set',80,50,'10er-Set'],
      ['filet','Räucherhaken Filet – 10er-Set',110,50,'10er-Set'],
      ['doppel','Räucherhaken Doppeldorn – 10er-Set',90,50,'10er-Set'],
      ['fleisch','Fleischerhaken S-Form 5 mm',150,50,'Stück'],
      ['mehl-buche','Räuchermehl Buche – 500 g',70,50,'500 g'],
      ['mehl-erle','Räuchermehl Erle – 500 g',55,50,'500 g'],
      ['mehl-birke','Räuchermehl Birke – 500 g',50,40,'500 g'],
      ['mehl-eiche','Räuchermehl Eiche – 500 g',50,40,'500 g'],
      ['mehl-kirsche','Räuchermehl Kirsche – 500 g',35,50,'500 g'],
      ['lauge-forelle-0','Räucherlauge Forelle – 500 g',80,50,'500 g'],
      ['lauge-forelle-1','Räucherlauge Forelle Classic – 500 g',50,40,'500 g'],
      ['lauge-forelle-2','Räucherlauge Forelle Chili – 500 g',45,40,'500 g'],
      ['lauge-forelle-3','Räucherlauge Forelle RED – 500 g',45,40,'500 g'],
      ['lauge-forelle-4','Räucherlauge Forelle Kräuter – 500 g',50,40,'500 g'],
      ['lauge-forelle-5','Räucherlauge Forelle Knoblauch – 500 g',50,40,'500 g'],
      ['lauge-forelle-6','Räucherlauge Forelle Zitronenpfeffer – 500 g',50,40,'500 g'],
      ['lauge-forelle-7','Räucherlauge Forelle Delikat – 500 g',50,40,'500 g'],
      ['lauge-forelle-8','Räucherlauge Forelle EL PASO – 500 g',50,40,'500 g'],
      ['lauge-forelle-9','Räucherlauge Forelle Kansas – 500 g',50,40,'500 g'],
      ['lauge-aal-0','Räucherlauge Aal – 500 g',65,50,'500 g'],
      ['lauge-aal-1','Räucherlauge Aal Pfeffer – 500 g',50,40,'500 g'],
      ['lauge-aal-2','Räucherlauge Aal Delikat – 500 g',50,40,'500 g']
    ];
    $stmt=$db->prepare('INSERT IGNORE INTO inventory (id,name,stock,minimum,unit,updated_at) VALUES (?,?,?,?,?,NOW())');
    foreach($catalog as $r) $stmt->execute($r);
}
function seed_products(PDO $db): void {
    $catalog = [
      ['prototype-project','PROTO-149','90001','Prototypenentwicklung Räucherhaken','Sonderanfertigung','prototype',149.00,'Projekt','active','Individuelle Prototypenentwicklung und Projektstart.','assets/smoky-hilfe-button.png',0],
      ['std','RH-STD','10001','Räucherhaken Standard – 10er-Set','Räucherhaken','hook',12.90,'10er-Set','active','Standard-Räucherhaken mit Varianten.','assets/standard.png',1],
      ['aal','RH-AAL','10002','Räucherhaken Standard Aal – 10er-Set','Räucherhaken','hook',12.90,'10er-Set','active','Räucherhaken mit kleinem Hakenbogen für Aal, Hornhecht und schlanke Fische.','assets/standard-aal-weiss.png',1],
      ['ultra','RH-ULT','10003','Räucherhaken Ultra – 10er-Set','Räucherhaken','hook',19.90,'10er-Set','active','Extra stabil für große und schwere Fische.','assets/ultra-original-korrekt.png',1],
      ['kralle','RH-KRA','10004','Räucherhaken Kralle – 10er-Set','Räucherhaken','hook',18.90,'10er-Set','active','Mehrpunkt-Halt für große und schwere Fische.','assets/kralle.png',1],
      ['filet','RH-FIL','10005','Räucherhaken Filet – 10er-Set','Räucherhaken','hook',15.90,'10er-Set','active','Für Filets und flache Räucherstücke.','assets/filet.png',1],
      ['doppel','RH-DOP','10006','Räucherhaken Doppeldorn – 10er-Set','Räucherhaken','hook',15.90,'10er-Set','active','Doppeldorn-Ausführung für mehr Stabilität.','assets/doppeldorn.png',1],
      ['fleisch','FH-S5','10007','Fleischerhaken S-Form 5 mm','Fleischerhaken','product',7.90,'Stück','active','Massiver Fleischerhaken in S-Form für Schinken und schwere Fleischstücke.','assets/fleischer.jpeg',1],
      ['mehl-buche','RM-BUC','11001','Räuchermehl Buche – 500 g','Räuchermehl','product',4.95,'500 g','active','Klassisches Räuchermehl Buche – ausgewogener Allrounder.','assets/raeuchermehl-buche-produkt.jpg',1],
      ['mehl-erle','RM-ERL','11002','Räuchermehl Erle – 500 g','Räuchermehl','product',4.95,'500 g','active','Mildes Räuchermehl Erle – besonders passend zu Fisch.','assets/raeuchermehl-erle-produkt.jpg',1],
      ['mehl-birke','RM-BIR','11003','Räuchermehl Birke – 500 g','Räuchermehl','product',4.95,'500 g','active','Mildes Räuchermehl aus Birke.','assets/raeuchermehl-birke-produkt.jpg',1],
      ['mehl-eiche','RM-EIC','11004','Räuchermehl Eiche – 500 g','Räuchermehl','product',4.95,'500 g','active','Kräftiges Räuchermehl aus Eiche.','assets/raeuchermehl-eiche-produkt.jpg',1],
      ['mehl-kirsche','RM-KIR','11005','Räuchermehl Kirsche – 500 g','Räuchermehl','product',6.95,'500 g','active','Mild-fruchtiges Räuchermehl aus Kirschholz.','assets/raeuchermehl-kirsche-produkt.jpg',1],
      ['lauge-forelle-0','RL-FOR-STD','12001','Räucherlauge Forelle – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Salz-Gewürz-Mischung mit Dill und Wacholder für Forellen.','assets/lauge-standard.png',1],
      ['lauge-forelle-1','RL-FOR-CLA','12002','Räucherlauge Forelle Classic – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Klassische fein-würzige Forellen-Räucherlauge mit Wacholderaroma.','assets/lauge-delikat.png',1],
      ['lauge-forelle-2','RL-FOR-CHI','12003','Räucherlauge Forelle Chili – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Forellen-Räucherlauge mit fein-feuriger Chili-Note.','assets/lauge-chili.png',1],
      ['lauge-forelle-3','RL-FOR-RED','12004','Räucherlauge Forelle RED – 500 g','Räucherlaugen Forelle','product',6.95,'500 g','active','Gewürzmischung mit ganzen Wacholderbeeren für Forellen und Lachsforellen.','assets/lauge-red.png',1],
      ['lauge-forelle-4','RL-FOR-KRA','12005','Räucherlauge Forelle Kräuter – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Milde Forellen-Räucherlauge mit feiner Kräuternote.','assets/lauge-kraeuter.png',1],
      ['lauge-forelle-5','RL-FOR-KNO','12006','Räucherlauge Forelle Knoblauch – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Herzhafte Forellen-Räucherlauge mit feiner Knoblauchnote.','assets/lauge-knoblauch.png',1],
      ['lauge-forelle-6','RL-FOR-ZPF','12007','Räucherlauge Forelle Zitronenpfeffer – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Forellen-Räucherlauge mit Zitronennote und mildem Pfeffer.','assets/lauge-zitronenpfeffer.png',1],
      ['lauge-forelle-7','RL-FOR-DEL','12008','Räucherlauge Forelle Delikat – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Fein abgestimmte Forellen-Räucherlauge mit leicht feurigem Geschmack.','assets/lauge-gartenkraeuter.png',1],
      ['lauge-forelle-8','RL-FOR-ELP','12009','Räucherlauge Forelle EL PASO – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Kräftig-würzige Forellen-Räucherlauge mit leicht pikant-rauchiger Note.','assets/lauge-elpaso.png',1],
      ['lauge-forelle-9','RL-FOR-KAN','12010','Räucherlauge Forelle Kansas – 500 g','Räucherlaugen Forelle','product',4.95,'500 g','active','Abgestimmte Räucherlauge für Lachsforellen und Forellen.','assets/lauge-kansas.png',1],
      ['lauge-aal-0','RL-AAL-STD','12101','Räucherlauge Aal – 500 g','Räucherlaugen Aal','product',4.95,'500 g','active','Kräftige klassische Salz-Gewürz-Mischung für Räucheraal.','assets/lauge-aal_standard.png',1],
      ['lauge-aal-1','RL-AAL-PFE','12102','Räucherlauge Aal Pfeffer – 500 g','Räucherlaugen Aal','product',4.95,'500 g','active','Aal-Räucherlauge mit fein-würziger Pfeffernote.','assets/lauge-aal_pfeffer.png',1],
      ['lauge-aal-2','RL-AAL-DEL','12103','Räucherlauge Aal Delikat – 500 g','Räucherlaugen Aal','product',4.95,'500 g','active','Milde, fein ausgewogene Aal-Räucherlauge.','assets/lauge-aal_delikat.png',1]
    ];
    $stmt=$db->prepare('INSERT IGNORE INTO products (id,sku,article_no,name,category,product_type,base_price,unit,status,description,image_path,shop_visible,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    foreach($catalog as $r) $stmt->execute($r);
}


$error='';
$installed=is_file(RH24_DB_CONFIG);
if ($_SERVER['REQUEST_METHOD']==='POST' && !$installed) {
    $cfg=[
      'host'=>trim((string)($_POST['host']??'')),
      'database'=>trim((string)($_POST['database']??'')),
      'user'=>trim((string)($_POST['user']??'')),
      'password'=>(string)($_POST['db_password']??''),
      'charset'=>'utf8mb4',
    ];
    $admin=(string)($_POST['admin_password']??'');
    $repeat=(string)($_POST['admin_password_repeat']??'');
    try {
      if($cfg['host']===''||$cfg['database']===''||$cfg['user']===''||$cfg['password']==='') throw new RuntimeException('Bitte alle Datenbankfelder ausfüllen.');
      if(strlen($admin)<12) throw new RuntimeException('Das Admin-Passwort muss mindestens 12 Zeichen lang sein.');
      if($admin!==$repeat) throw new RuntimeException('Die Admin-Passwörter stimmen nicht überein.');
      $db=connect($cfg);
      schema($db);
      schema_v38_extra($db);
      schema_v40_extra($db);
      $adminHash=password_hash($admin,PASSWORD_DEFAULT);
      set_setting($db,'admin_password_hash',$adminHash);
      $u=$db->prepare("INSERT IGNORE INTO users(id,username,display_name,email,role,sales_rep_id,password_hash,status,must_change_password,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW())");
      $u->execute(['USR-BJOERN','bjoern.hahne','Björn Hahne',null,'admin',null,$adminHash,'active',1]);
      $u->execute(['USR-JESSICA','jessica.hahne','Jessica Hahne',null,'admin',null,$adminHash,'active',1]);
      set_setting($db,'created_at',date('c'));
      set_setting($db,'shop_name','Räucherhaken24');
      set_setting($db,'shipping_threshold','39');
      set_setting($db,'shipping_cost','7');
      set_setting($db,'vat_rate','19');
      set_setting($db,'system_email','service@raeucherhaken24.com');
      set_setting($db,'commission_period','monthly');
      set_setting($db,'db_schema_version','40');
      set_setting($db,'schema_version','40');
      seed_inventory($db);
      seed_products($db);
      $log=$db->prepare('INSERT INTO activity_log(actor,action_name,entity_type,entity_id,detail_json,created_at) VALUES(?,?,?,?,?,NOW())');
      $log->execute(['setup','database_install','system','v40',json_encode(['database'=>$cfg['database'],'host'=>$cfg['host']],JSON_UNESCAPED_UNICODE)]);
      write_config($cfg);
      header('Location: index.php?setup=ok'); exit;
    } catch(Throwable $e) { $error=$e->getMessage(); }
}
?>
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Orgaboard Datenbank-Setup | Räucherhaken24</title><link rel="stylesheet" href="assets/admin.css?v=42"><style>.setupGrid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.setupGrid .full{grid-column:1/-1}.authCard{max-width:760px}.hint{background:#f5efe7;border:1px solid #e0d2c1;padding:14px 16px;border-radius:12px;margin:16px 0;font-size:13px;line-height:1.5}.ok{background:#eaf6ee;border-color:#b9dfc4}.secret{font-size:12px;color:#6c6259}@media(max-width:650px){.setupGrid{grid-template-columns:1fr}.setupGrid .full{grid-column:auto}}</style></head><body class="ob-auth"><main class="authShell"><section class="authCard"><div class="authBrand"><span>RÄUCHERHAKEN</span><strong>24</strong></div><div class="authKicker">ORGABOARD · MARIADB V42</div><h1>Datenbank einmalig verbinden</h1>
<?php if($installed): ?><div class="hint ok"><b>Die Datenbank-Konfiguration ist bereits vorhanden.</b><br>Die Setup-Seite ist gesperrt. Öffne jetzt das Orgaboard.</div><a class="backShop" href="index.php">→ Orgaboard öffnen</a>
<?php else: ?><p>Die STRATO-Daten sind bereits vorbereitet. Du trägst nur dein Datenbank-Passwort und ein eigenes Orgaboard-Admin-Passwort ein. Das Datenbank-Passwort wird nicht an ChatGPT gesendet.</p><div class="hint"><b>STRATO:</b> MariaDB 11.8 · Datenbank <b>dbs16044993</b> · Benutzer <b>dbu4769168</b></div><?php if($error): ?><div class="authError"><?=h($error)?></div><?php endif; ?><form method="post" class="authForm" autocomplete="off"><div class="setupGrid"><label class="full">Datenbankserver<input name="host" required value="<?=h($_POST['host']??'database-5021257243.webspace-host.com')?>"></label><label>Datenbank<input name="database" required value="<?=h($_POST['database']??'dbs16044993')?>"></label><label>Benutzer<input name="user" required value="<?=h($_POST['user']??'dbu4769168')?>"></label><label class="full">STRATO-Datenbankpasswort<input type="password" name="db_password" required autocomplete="new-password"></label><label>Orgaboard-Admin-Passwort<input type="password" name="admin_password" minlength="12" required autocomplete="new-password"></label><label>Admin-Passwort wiederholen<input type="password" name="admin_password_repeat" minlength="12" required autocomplete="new-password"></label></div><button type="submit">Datenbank verbinden & Orgaboard einrichten</button></form><p class="secret">Das Setup erstellt die Tabellen automatisch. Du musst phpMyAdmin dafür nicht manuell bedienen.</p><?php endif; ?></section></main></body></html>
