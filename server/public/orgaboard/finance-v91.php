<?php
declare(strict_types=1);

/* Räucherhaken24 Orgaboard V91 · Finanz- & Buchhaltungsmodul */

function rh24_ensure_v91_schema(PDO $db): void {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS finance_suppliers (
          id VARCHAR(50) NOT NULL PRIMARY KEY,
          supplier_no VARCHAR(40) NOT NULL,
          name VARCHAR(220) NOT NULL,
          contact_name VARCHAR(180) NOT NULL DEFAULT '',
          email VARCHAR(190) NOT NULL DEFAULT '',
          phone VARCHAR(80) NOT NULL DEFAULT '',
          street VARCHAR(190) NOT NULL DEFAULT '',
          zip VARCHAR(20) NOT NULL DEFAULT '',
          city VARCHAR(120) NOT NULL DEFAULT '',
          country VARCHAR(80) NOT NULL DEFAULT 'Deutschland',
          iban VARCHAR(64) NOT NULL DEFAULT '',
          bic VARCHAR(32) NOT NULL DEFAULT '',
          vat_id VARCHAR(60) NOT NULL DEFAULT '',
          tax_no VARCHAR(80) NOT NULL DEFAULT '',
          payment_terms_days SMALLINT UNSIGNED NOT NULL DEFAULT 14,
          notes TEXT NULL,
          active TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_finance_supplier_no (supplier_no),
          KEY idx_finance_supplier_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_cost_centers (
          id VARCHAR(50) NOT NULL PRIMARY KEY,
          code VARCHAR(30) NOT NULL,
          name VARCHAR(180) NOT NULL,
          description VARCHAR(500) NOT NULL DEFAULT '',
          active TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_finance_cost_center_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_accounts (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          framework VARCHAR(10) NOT NULL DEFAULT 'SKR03',
          code VARCHAR(20) NOT NULL,
          name VARCHAR(220) NOT NULL,
          account_type VARCHAR(40) NOT NULL DEFAULT 'expense',
          tax_rate DECIMAL(6,2) NOT NULL DEFAULT 0.00,
          active TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_finance_account_framework_code (framework,code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_bank_accounts (
          id VARCHAR(50) NOT NULL PRIMARY KEY,
          name VARCHAR(180) NOT NULL,
          bank_name VARCHAR(180) NOT NULL DEFAULT '',
          iban VARCHAR(64) NOT NULL DEFAULT '',
          bic VARCHAR(32) NOT NULL DEFAULT '',
          account_type VARCHAR(30) NOT NULL DEFAULT 'bank',
          opening_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          active TINYINT(1) NOT NULL DEFAULT 1,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_transactions (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          bank_account_id VARCHAR(50) NULL,
          booking_date DATE NOT NULL,
          value_date DATE NULL,
          amount DECIMAL(14,2) NOT NULL,
          currency CHAR(3) NOT NULL DEFAULT 'EUR',
          transaction_type VARCHAR(30) NOT NULL DEFAULT 'bank',
          counterparty VARCHAR(220) NOT NULL DEFAULT '',
          iban VARCHAR(64) NOT NULL DEFAULT '',
          purpose VARCHAR(700) NOT NULL DEFAULT '',
          reference VARCHAR(180) NOT NULL DEFAULT '',
          external_ref VARCHAR(100) NULL,
          status VARCHAR(30) NOT NULL DEFAULT 'unassigned',
          order_no VARCHAR(80) NULL,
          document_id VARCHAR(80) NULL,
          supplier_id VARCHAR(50) NULL,
          category VARCHAR(120) NOT NULL DEFAULT '',
          ledger_account VARCHAR(30) NOT NULL DEFAULT '',
          tax_rate DECIMAL(6,2) NOT NULL DEFAULT 0.00,
          net_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          cost_center_id VARCHAR(50) NULL,
          project VARCHAR(160) NOT NULL DEFAULT '',
          notes VARCHAR(700) NOT NULL DEFAULT '',
          source VARCHAR(30) NOT NULL DEFAULT 'manual',
          created_by VARCHAR(60) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_finance_transaction_external (external_ref),
          KEY idx_finance_transaction_date (booking_date),
          KEY idx_finance_transaction_status (status),
          KEY idx_finance_transaction_order (order_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_expenses (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          voucher_no VARCHAR(50) NOT NULL,
          supplier_id VARCHAR(50) NULL,
          invoice_no VARCHAR(100) NOT NULL DEFAULT '',
          invoice_date DATE NOT NULL,
          due_date DATE NULL,
          gross_amount DECIMAL(14,2) NOT NULL,
          net_amount DECIMAL(14,2) NOT NULL,
          tax_amount DECIMAL(14,2) NOT NULL,
          tax_rate DECIMAL(6,2) NOT NULL DEFAULT 19.00,
          account_code VARCHAR(30) NOT NULL DEFAULT '',
          category VARCHAR(120) NOT NULL DEFAULT '',
          cost_center_id VARCHAR(50) NULL,
          project VARCHAR(160) NOT NULL DEFAULT '',
          payment_status VARCHAR(30) NOT NULL DEFAULT 'open',
          paid_at DATE NULL,
          payment_method VARCHAR(60) NOT NULL DEFAULT '',
          receipt_path VARCHAR(255) NOT NULL DEFAULT '',
          receipt_name VARCHAR(255) NOT NULL DEFAULT '',
          receipt_sha256 CHAR(64) NULL,
          notes TEXT NULL,
          record_status VARCHAR(30) NOT NULL DEFAULT 'active',
          cancelled_at DATETIME NULL,
          cancelled_by VARCHAR(60) NULL,
          cancel_reason VARCHAR(500) NOT NULL DEFAULT '',
          created_by VARCHAR(60) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_finance_expense_voucher (voucher_no),
          KEY idx_finance_expense_due (due_date,payment_status),
          KEY idx_finance_expense_supplier (supplier_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_cash_entries (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          entry_no VARCHAR(50) NOT NULL,
          entry_date DATE NOT NULL,
          entry_type VARCHAR(30) NOT NULL,
          amount DECIMAL(14,2) NOT NULL,
          description VARCHAR(500) NOT NULL,
          receipt_no VARCHAR(100) NOT NULL DEFAULT '',
          account_code VARCHAR(30) NOT NULL DEFAULT '',
          tax_rate DECIMAL(6,2) NOT NULL DEFAULT 0.00,
          tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          cost_center_id VARCHAR(50) NULL,
          created_by VARCHAR(60) NULL,
          locked_at DATETIME NULL,
          created_at DATETIME NOT NULL,
          UNIQUE KEY uq_finance_cash_entry_no (entry_no),
          KEY idx_finance_cash_date (entry_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_assets (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          asset_no VARCHAR(50) NOT NULL,
          name VARCHAR(220) NOT NULL,
          category VARCHAR(120) NOT NULL DEFAULT '',
          acquisition_date DATE NOT NULL,
          purchase_price DECIMAL(14,2) NOT NULL,
          useful_life_months SMALLINT UNSIGNED NOT NULL DEFAULT 36,
          depreciation_method VARCHAR(30) NOT NULL DEFAULT 'linear',
          residual_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          account_code VARCHAR(30) NOT NULL DEFAULT '',
          cost_center_id VARCHAR(50) NULL,
          disposed_at DATE NULL,
          disposal_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
          notes TEXT NULL,
          active TINYINT(1) NOT NULL DEFAULT 1,
          created_by VARCHAR(60) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          UNIQUE KEY uq_finance_asset_no (asset_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_recurring (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          title VARCHAR(220) NOT NULL,
          direction VARCHAR(20) NOT NULL DEFAULT 'expense',
          amount DECIMAL(14,2) NOT NULL,
          tax_rate DECIMAL(6,2) NOT NULL DEFAULT 19.00,
          interval_type VARCHAR(30) NOT NULL DEFAULT 'monthly',
          next_due_date DATE NOT NULL,
          supplier_id VARCHAR(50) NULL,
          category VARCHAR(120) NOT NULL DEFAULT '',
          account_code VARCHAR(30) NOT NULL DEFAULT '',
          cost_center_id VARCHAR(50) NULL,
          notes VARCHAR(500) NOT NULL DEFAULT '',
          active TINYINT(1) NOT NULL DEFAULT 1,
          created_by VARCHAR(60) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          KEY idx_finance_recurring_due (next_due_date,active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_budgets (
          id VARCHAR(60) NOT NULL PRIMARY KEY,
          period CHAR(7) NOT NULL,
          cost_center_id VARCHAR(50) NULL,
          category VARCHAR(120) NOT NULL DEFAULT '',
          amount DECIMAL(14,2) NOT NULL,
          notes VARCHAR(500) NOT NULL DEFAULT '',
          created_by VARCHAR(60) NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          KEY idx_finance_budget_period (period)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_dunning (
          order_no VARCHAR(80) NOT NULL PRIMARY KEY,
          dunning_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
          last_dunned_at DATETIME NULL,
          next_due_date DATE NULL,
          fees DECIMAL(12,2) NOT NULL DEFAULT 0.00,
          interest_rate DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
          blocked TINYINT(1) NOT NULL DEFAULT 0,
          notes VARCHAR(500) NOT NULL DEFAULT '',
          updated_by VARCHAR(60) NULL,
          updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE TABLE IF NOT EXISTS finance_period_closings (
          period CHAR(7) NOT NULL PRIMARY KEY,
          status VARCHAR(20) NOT NULL DEFAULT 'closed',
          note VARCHAR(500) NOT NULL DEFAULT '',
          closed_by VARCHAR(60) NULL,
          closed_at DATETIME NULL,
          reopened_by VARCHAR(60) NULL,
          reopened_at DATETIME NULL,
          updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $defaults=[
          'finance_framework'=>'SKR03','finance_accounting_method'=>'EÜR','finance_vat_mode'=>'Soll','finance_fiscal_year_start'=>'01-01',
          'finance_default_payment_days'=>'14','finance_cash_opening_balance'=>'0','finance_datev_consultant_no'=>'','finance_datev_client_no'=>'',
          'finance_datev_account_length'=>'4','finance_datev_dictation_code'=>'BH','finance_alert_liquidity_min'=>'3000'
        ];
        foreach($defaults as $k=>$v){try{if((string)rh24_setting_get($k,'')==='')rh24_setting_set($k,$v);}catch(Throwable $e){}}

        $cc=$db->prepare("INSERT IGNORE INTO finance_cost_centers(id,code,name,description,active,created_at,updated_at) VALUES(?,?,?,?,1,NOW(),NOW())");
        foreach([
          ['CC-100','100','Räucherhaken24','Shop, Produktion und Verkauf'],
          ['CC-200','200','Lager & Logistik','Lager, Verpackung und Versand'],
          ['CC-300','300','Marketing','Werbung, Kampagnen und Vertriebsmittel'],
          ['CC-400','400','Verwaltung','Büro, Software und allgemeine Verwaltung'],
          ['CC-500','500','Vertrieb','Kundenberater, Händler und Außendienst']
        ] as $x)$cc->execute($x);

        $bank=$db->prepare("INSERT IGNORE INTO finance_bank_accounts(id,name,bank_name,iban,bic,account_type,opening_balance,active,created_at,updated_at) VALUES(?,?,?,?,?,'bank',0,1,NOW(),NOW())");
        $bank->execute(['BANK-MAIN','Geschäftskonto','','','']);

        $acc=$db->prepare("INSERT IGNORE INTO finance_accounts(id,framework,code,name,account_type,tax_rate,active,created_at,updated_at) VALUES(?,?,?,?,?,?,1,NOW(),NOW())");
        $accounts=[
          ['A03-1000','SKR03','1000','Kasse','asset',0],['A03-1200','SKR03','1200','Bank','asset',0],['A03-1400','SKR03','1400','Forderungen aus Lieferungen und Leistungen','asset',0],
          ['A03-1600','SKR03','1600','Verbindlichkeiten aus Lieferungen und Leistungen','liability',0],['A03-1576','SKR03','1576','Abziehbare Vorsteuer 19 %','tax',19],['A03-1776','SKR03','1776','Umsatzsteuer 19 %','tax',19],
          ['A03-8400','SKR03','8400','Erlöse 19 % Umsatzsteuer','revenue',19],['A03-3400','SKR03','3400','Wareneingang / Material','expense',19],['A03-4210','SKR03','4210','Miete','expense',19],
          ['A03-4600','SKR03','4600','Werbekosten','expense',19],['A03-4900','SKR03','4900','Sonstige betriebliche Aufwendungen','expense',19],['A03-4970','SKR03','4970','Nebenkosten des Geldverkehrs','expense',0],
          ['A04-1600','SKR04','1600','Kasse','asset',0],['A04-1800','SKR04','1800','Bank','asset',0],['A04-1200','SKR04','1200','Forderungen aus Lieferungen und Leistungen','asset',0],
          ['A04-3300','SKR04','3300','Verbindlichkeiten aus Lieferungen und Leistungen','liability',0],['A04-1406','SKR04','1406','Abziehbare Vorsteuer 19 %','tax',19],['A04-3806','SKR04','3806','Umsatzsteuer 19 %','tax',19],
          ['A04-4400','SKR04','4400','Erlöse 19 % Umsatzsteuer','revenue',19],['A04-5400','SKR04','5400','Wareneingang / Material','expense',19],['A04-6310','SKR04','6310','Miete','expense',19],
          ['A04-6600','SKR04','6600','Werbekosten','expense',19],['A04-6800','SKR04','6800','Sonstige betriebliche Aufwendungen','expense',19],['A04-6855','SKR04','6855','Nebenkosten des Geldverkehrs','expense',0]
        ];
        foreach($accounts as $x)$acc->execute($x);

        rh24_setting_set('schema_version','91');rh24_setting_set('db_schema_version','91');
        try{rh24_audit('schema_upgrade','system','v91',['features'=>['finance_cockpit','receipts','banking_import','reconciliation','cashbook','open_items','dunning','suppliers','assets','cost_centers','budgets','recurring','liquidity','tax_preview','datev_export','period_closing']],'system');}catch(Throwable $e){}
    } catch(Throwable $e) { error_log('RH24 Finance V91 migration failed: '.$e->getMessage()); throw new RuntimeException('Finanzmodul V91 konnte nicht initialisiert werden. Bitte Server-Log und Datenbankrechte prüfen.',0,$e); }
}

function rh24_finance_required_tables(): array {
    return [
      'finance_suppliers','finance_cost_centers','finance_accounts','finance_bank_accounts','finance_transactions','finance_expenses',
      'finance_cash_entries','finance_assets','finance_recurring','finance_budgets','finance_dunning','finance_period_closings'
    ];
}
function rh24_finance_schema_health(PDO $db): array {
    $missing=[];$available=[];
    foreach(rh24_finance_required_tables() as $table){
        try{
            $q=$db->query("SHOW TABLES LIKE ".$db->quote($table));
            if($q && $q->fetchColumn())$available[]=$table;else$missing[]=$table;
        }catch(Throwable $e){$missing[]=$table;}
    }
    $schema=0;
    try{$q=$db->prepare("SELECT setting_value FROM settings WHERE setting_key='schema_version'");$q->execute();$schema=(int)($q->fetchColumn()?:0);}catch(Throwable $e){}
    return ['ok'=>count($missing)===0,'schema_version'=>$schema,'available'=>$available,'missing'=>$missing];
}
function rh24_finance_schema_ready(PDO $db): bool { return (bool)(rh24_finance_schema_health($db)['ok']??false); }
function rh24_finance_ensure_ready(?PDO $db=null): array {
    $db=$db??rh24_db();$health=rh24_finance_schema_health($db);
    if(($health['ok']??false) && (int)($health['schema_version']??0)>=91)return $health;
    try{rh24_ensure_v91_schema($db);}catch(Throwable $e){
        error_log('RH24 Finance V91.1 self repair failed: '.$e->getMessage());
        $after=rh24_finance_schema_health($db);$missing=implode(', ',(array)($after['missing']??[]));
        throw new RuntimeException('Finanz-Datenbankstruktur konnte nicht automatisch repariert werden'.($missing!==''?'. Fehlend: '.$missing:'').'. Ursache: '.$e->getMessage(),0,$e);
    }
    $health=rh24_finance_schema_health($db);
    if(!($health['ok']??false))throw new RuntimeException('Finanz-Datenbankstruktur ist unvollständig. Fehlend: '.implode(', ',(array)$health['missing']));
    return $health;
}

function rh24_finance_period(string $date): string { $ts=strtotime($date); return $ts?date('Y-m',$ts):date('Y-m'); }
function rh24_finance_assert_period_open(string $date): void {
    $period=rh24_finance_period($date);$q=rh24_db()->prepare("SELECT status FROM finance_period_closings WHERE period=?");$q->execute([$period]);
    if((string)$q->fetchColumn()==='closed')throw new RuntimeException('Periode '.$period.' ist abgeschlossen. Erst protokolliert wieder öffnen.');
}
function rh24_finance_next_no(string $key,string $prefix): string {
    $n=max(1,(int)rh24_setting_get($key,'1'));rh24_setting_set($key,(string)($n+1));return $prefix.'-'.date('Y').'-'.str_pad((string)$n,6,'0',STR_PAD_LEFT);
}
function rh24_finance_date(?string $v): string { return $v?date('Y-m-d',strtotime($v)):''; }
function rh24_finance_float(mixed $v): float { return round((float)$v,2); }
function rh24_finance_config(): array {
    return [
      'framework'=>(string)rh24_setting_get('finance_framework','SKR03'),
      'accounting_method'=>(string)rh24_setting_get('finance_accounting_method','EÜR'),
      'vat_mode'=>(string)rh24_setting_get('finance_vat_mode','Soll'),
      'fiscal_year_start'=>(string)rh24_setting_get('finance_fiscal_year_start','01-01'),
      'default_payment_days'=>(int)rh24_setting_get('finance_default_payment_days','14'),
      'cash_opening_balance'=>(float)rh24_setting_get('finance_cash_opening_balance','0'),
      'datev_consultant_no'=>(string)rh24_setting_get('finance_datev_consultant_no',''),
      'datev_client_no'=>(string)rh24_setting_get('finance_datev_client_no',''),
      'datev_account_length'=>(int)rh24_setting_get('finance_datev_account_length','4'),
      'datev_dictation_code'=>(string)rh24_setting_get('finance_datev_dictation_code','BH'),
      'liquidity_min'=>(float)rh24_setting_get('finance_alert_liquidity_min','3000')
    ];
}
function rh24_finance_suppliers(): array {
    $rows=rh24_db()->query("SELECT * FROM finance_suppliers ORDER BY active DESC,name")->fetchAll();
    foreach($rows as &$r){$r['payment_terms_days']=(int)$r['payment_terms_days'];$r['active']=(bool)$r['active'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_finance_cost_centers(): array {
    $rows=rh24_db()->query("SELECT * FROM finance_cost_centers ORDER BY code")->fetchAll();foreach($rows as &$r){$r['active']=(bool)$r['active'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_finance_accounts(): array {
    $rows=rh24_db()->query("SELECT * FROM finance_accounts ORDER BY framework,code")->fetchAll();foreach($rows as &$r){$r['tax_rate']=(float)$r['tax_rate'];$r['active']=(bool)$r['active'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_finance_bank_accounts(): array {
    $db=rh24_db();$rows=$db->query("SELECT * FROM finance_bank_accounts ORDER BY active DESC,name")->fetchAll();
    $sum=$db->prepare("SELECT COALESCE(SUM(amount),0) FROM finance_transactions WHERE bank_account_id=?");
    foreach($rows as &$r){$sum->execute([$r['id']]);$r['opening_balance']=(float)$r['opening_balance'];$r['booked_movement']=(float)$sum->fetchColumn();$r['balance']=round($r['opening_balance']+$r['booked_movement'],2);$r['active']=(bool)$r['active'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_finance_transactions(int $limit=1200): array {
    $limit=max(1,min(5000,$limit));$rows=rh24_db()->query("SELECT t.*,b.name bank_account_name,s.name supplier_name,c.code cost_center_code,c.name cost_center_name FROM finance_transactions t LEFT JOIN finance_bank_accounts b ON b.id=t.bank_account_id LEFT JOIN finance_suppliers s ON s.id=t.supplier_id LEFT JOIN finance_cost_centers c ON c.id=t.cost_center_id ORDER BY t.booking_date DESC,t.created_at DESC LIMIT ".$limit)->fetchAll();
    foreach($rows as &$r){foreach(['amount','tax_rate','net_amount','tax_amount'] as $f)$r[$f]=(float)$r[$f];$r['booking_date']=rh24_finance_date($r['booking_date']);$r['value_date']=rh24_finance_date($r['value_date']??null);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_finance_expenses(): array {
    $rows=rh24_db()->query("SELECT e.*,s.name supplier_name,c.code cost_center_code,c.name cost_center_name FROM finance_expenses e LEFT JOIN finance_suppliers s ON s.id=e.supplier_id LEFT JOIN finance_cost_centers c ON c.id=e.cost_center_id ORDER BY e.invoice_date DESC,e.created_at DESC")->fetchAll();
    foreach($rows as &$r){foreach(['gross_amount','net_amount','tax_amount','tax_rate'] as $f)$r[$f]=(float)$r[$f];$r['invoice_date']=rh24_finance_date($r['invoice_date']);$r['due_date']=rh24_finance_date($r['due_date']??null);$r['paid_at']=rh24_finance_date($r['paid_at']??null);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);$r['receipt_available']=trim((string)$r['receipt_path'])!=='';unset($r['receipt_path']);}unset($r);return $rows;
}
function rh24_finance_cash_entries(): array {
    $rows=rh24_db()->query("SELECT c.*,cc.code cost_center_code,cc.name cost_center_name,u.display_name created_by_name FROM finance_cash_entries c LEFT JOIN finance_cost_centers cc ON cc.id=c.cost_center_id LEFT JOIN users u ON u.id=c.created_by ORDER BY c.entry_date DESC,c.created_at DESC")->fetchAll();
    foreach($rows as &$r){foreach(['amount','tax_rate','tax_amount'] as $f)$r[$f]=(float)$r[$f];$r['entry_date']=rh24_finance_date($r['entry_date']);$r['locked_at']=rh24_iso($r['locked_at']??null);$r['created_at']=rh24_iso($r['created_at']);}unset($r);return $rows;
}
function rh24_finance_assets(): array {
    $rows=rh24_db()->query("SELECT a.*,c.code cost_center_code,c.name cost_center_name FROM finance_assets a LEFT JOIN finance_cost_centers c ON c.id=a.cost_center_id ORDER BY a.acquisition_date DESC,a.name")->fetchAll();$today=new DateTimeImmutable('today');
    foreach($rows as &$r){foreach(['purchase_price','residual_value','disposal_value'] as $f)$r[$f]=(float)$r[$f];$r['useful_life_months']=(int)$r['useful_life_months'];$r['active']=(bool)$r['active'];$start=new DateTimeImmutable((string)$r['acquisition_date']);$end=!empty($r['disposed_at'])?new DateTimeImmutable((string)$r['disposed_at']):$today;$months=max(0,((int)$end->format('Y')-(int)$start->format('Y'))*12+((int)$end->format('n')-(int)$start->format('n')));$months=min($months,$r['useful_life_months']);$base=max(0,$r['purchase_price']-$r['residual_value']);$monthly=$r['useful_life_months']>0?$base/$r['useful_life_months']:0;$acc=round(min($base,$monthly*$months),2);$r['monthly_depreciation']=round($monthly,2);$r['accumulated_depreciation']=$acc;$r['book_value']=round(max($r['residual_value'],$r['purchase_price']-$acc),2);$r['acquisition_date']=rh24_finance_date($r['acquisition_date']);$r['disposed_at']=rh24_finance_date($r['disposed_at']??null);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_finance_recurring(): array {
    $rows=rh24_db()->query("SELECT r.*,s.name supplier_name,c.code cost_center_code,c.name cost_center_name FROM finance_recurring r LEFT JOIN finance_suppliers s ON s.id=r.supplier_id LEFT JOIN finance_cost_centers c ON c.id=r.cost_center_id ORDER BY r.active DESC,r.next_due_date,r.title")->fetchAll();foreach($rows as &$r){$r['amount']=(float)$r['amount'];$r['tax_rate']=(float)$r['tax_rate'];$r['active']=(bool)$r['active'];$r['next_due_date']=rh24_finance_date($r['next_due_date']);$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;
}
function rh24_finance_budgets(): array {$rows=rh24_db()->query("SELECT b.*,c.code cost_center_code,c.name cost_center_name FROM finance_budgets b LEFT JOIN finance_cost_centers c ON c.id=b.cost_center_id ORDER BY b.period DESC,c.code,b.category")->fetchAll();foreach($rows as &$r){$r['amount']=(float)$r['amount'];$r['created_at']=rh24_iso($r['created_at']);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;}
function rh24_finance_closings(): array {$rows=rh24_db()->query("SELECT * FROM finance_period_closings ORDER BY period DESC")->fetchAll();foreach($rows as &$r){$r['closed_at']=rh24_iso($r['closed_at']??null);$r['reopened_at']=rh24_iso($r['reopened_at']??null);$r['updated_at']=rh24_iso($r['updated_at']);}unset($r);return $rows;}

function rh24_finance_sales_rows(): array {
    $rows=rh24_db()->query("SELECT order_no,status,payment_status,payment_method,customer_json,totals_json,created_at,updated_at FROM orders WHERE status<>'cancelled' ORDER BY created_at DESC")->fetchAll();$out=[];
    foreach($rows as $r){$c=rh24_json_decode($r['customer_json']??'{}',[]);$t=rh24_json_decode($r['totals_json']??'{}',[]);$gross=(float)($t['gross']??$t['total']??0);$net=(float)($t['net']??($gross/1.19));$tax=(float)($t['tax']??max(0,$gross-$net));$terms=max(0,min(365,(int)($c['payment_terms_days']??rh24_setting_get('finance_default_payment_days','14'))));$out[]=['order_no'=>$r['order_no'],'status'=>$r['status'],'payment_status'=>$r['payment_status'],'payment_method'=>$r['payment_method'],'customer_name'=>(string)($c['company']??$c['name']??''),'customer_email'=>(string)($c['email']??''),'payment_terms_days'=>$terms,'gross'=>round($gross,2),'net'=>round($net,2),'tax'=>round($tax,2),'created_at'=>rh24_iso($r['created_at']),'updated_at'=>rh24_iso($r['updated_at'])];}
    return $out;
}
function rh24_finance_open_receivables(array $sales): array {
    $db=rh24_db();$dun=[];foreach($db->query("SELECT * FROM finance_dunning")->fetchAll() as $d)$dun[(string)$d['order_no']]=$d;
    $doc=[];try{foreach($db->query("SELECT id,document_no,order_no,status,issued_at FROM documents WHERE document_type='invoice'")->fetchAll() as $d)$doc[(string)$d['order_no']]=$d;}catch(Throwable $e){}
    $defaultDays=(int)rh24_setting_get('finance_default_payment_days','14');$out=[];$today=strtotime(date('Y-m-d'));
    foreach($sales as $s){if(($s['payment_status']??'')==='paid')continue;$created=strtotime((string)$s['created_at']);$issued=!empty($doc[$s['order_no']]['issued_at'])?strtotime((string)$doc[$s['order_no']]['issued_at']):$created;$days=max(0,min(365,(int)($s['payment_terms_days']??$defaultDays)));$due=date('Y-m-d',$issued+86400*$days);$d=$dun[$s['order_no']]??[];if(!empty($d['next_due_date']))$due=(string)$d['next_due_date'];$over=floor(($today-strtotime($due))/86400);$out[]=$s+['document_id'=>$doc[$s['order_no']]['id']??'','document_no'=>$doc[$s['order_no']]['document_no']??'','invoice_status'=>$doc[$s['order_no']]['status']??'','due_date'=>$due,'days_overdue'=>max(0,(int)$over),'is_overdue'=>$over>0,'dunning_level'=>(int)($d['dunning_level']??0),'dunning_fees'=>(float)($d['fees']??0),'interest_rate'=>(float)($d['interest_rate']??0),'dunning_blocked'=>(bool)($d['blocked']??false),'dunning_notes'=>(string)($d['notes']??'')];}
    usort($out,fn($a,$b)=>strcmp($a['due_date'],$b['due_date']));return $out;
}
function rh24_finance_sale_payment_date(array $sale,array $transactions): string {
    foreach($transactions as $tx){if(($tx['order_no']??'')===($sale['order_no']??'')&&($tx['status']??'')==='matched'&&(float)($tx['amount']??0)>0)return (string)($tx['booking_date']??'');}
    return substr((string)($sale['updated_at']??$sale['created_at']??''),0,10);
}
function rh24_finance_summary(array $sales,array $expenses,array $cash,array $banks,array $open,array $transactions=[]): array {
    $month=date('Y-m');$year=date('Y');$cfg=rh24_finance_config();$method=(string)($cfg['accounting_method']??'EÜR');$vatMode=(string)($cfg['vat_mode']??'Soll');$revenueM=$revenueY=$taxOutM=0.0;
    foreach($sales as $s){if(($s['status']??'')==='cancelled')continue;$paid=($s['payment_status']??'')==='paid';$bookDate=$method==='EÜR'?($paid?rh24_finance_sale_payment_date($s,$transactions):''):substr((string)$s['created_at'],0,10);if($bookDate!==''){$p=substr($bookDate,0,7);$y=substr($bookDate,0,4);if($p===$month)$revenueM+=(float)$s['gross'];if($y===$year)$revenueY+=(float)$s['gross'];}$taxDate=$vatMode==='Ist'?($paid?rh24_finance_sale_payment_date($s,$transactions):''):substr((string)$s['created_at'],0,10);if($taxDate!==''&&substr($taxDate,0,7)===$month)$taxOutM+=(float)$s['tax'];}
    $expenseM=$expenseY=$taxInM=0.0;$openPay=0.0;$overPay=0;foreach($expenses as $e){if(($e['record_status']??'active')!=='active')continue;$bookDate=$method==='EÜR'?((($e['payment_status']??'')==='paid'&&!empty($e['paid_at']))?(string)$e['paid_at']:''):(string)$e['invoice_date'];if($bookDate!==''){$p=substr($bookDate,0,7);$y=substr($bookDate,0,4);if($p===$month)$expenseM+=(float)$e['gross_amount'];if($y===$year)$expenseY+=(float)$e['gross_amount'];}if(substr((string)$e['invoice_date'],0,7)===$month)$taxInM+=(float)$e['tax_amount'];if(($e['payment_status']??'')!=='paid'){$openPay+=(float)$e['gross_amount'];if(!empty($e['due_date'])&&strtotime((string)$e['due_date'])<strtotime(date('Y-m-d')))$overPay++;}}
    $cashBalance=(float)($cfg['cash_opening_balance']??0);foreach($cash as $c)$cashBalance+=in_array($c['entry_type'],['income','deposit'],true)?(float)$c['amount']:-(float)$c['amount'];$bankBalance=array_sum(array_map(fn($x)=>(float)$x['balance'],$banks));$openRec=array_sum(array_map(fn($x)=>(float)$x['gross']+(float)$x['dunning_fees'],$open));$overRec=count(array_filter($open,fn($x)=>$x['is_overdue']));
    return ['period'=>$month,'accounting_method'=>$method,'vat_mode'=>$vatMode,'revenue_month'=>round($revenueM,2),'revenue_year'=>round($revenueY,2),'expense_month'=>round($expenseM,2),'expense_year'=>round($expenseY,2),'profit_month'=>round($revenueM-$expenseM,2),'open_receivables'=>round($openRec,2),'overdue_receivables'=>$overRec,'open_payables'=>round($openPay,2),'overdue_payables'=>$overPay,'cash_balance'=>round($cashBalance,2),'bank_balance'=>round($bankBalance,2),'liquidity'=>round($cashBalance+$bankBalance,2),'vat_output_month'=>round($taxOutM,2),'vat_input_month'=>round($taxInM,2),'vat_due_month'=>round($taxOutM-$taxInM,2)];
}
function rh24_finance_recurring_occurrences(array $r,int $start,int $end): array {
    if(empty($r['active'])||empty($r['next_due_date']))return [];$ts=strtotime((string)$r['next_due_date']);if(!$ts)return [];$step=match((string)($r['interval_type']??'monthly')){'quarterly'=>'+3 months','yearly'=>'+1 year',default=>'+1 month'};$out=[];$guard=0;
    if($ts<$start){$out[]=$start;while($ts<$start&&$guard++<240){$next=strtotime($step,$ts);if(!$next||$next<=$ts)break;$ts=$next;}}
    while($ts<=$end&&$guard++<260){if($ts>=$start)$out[]=$ts;$next=strtotime($step,$ts);if(!$next||$next<=$ts)break;$ts=$next;}
    return array_values(array_unique($out));
}
function rh24_finance_liquidity(array $summary,array $open,array $expenses,array $recurring): array {
    $start=strtotime(date('Y-m-d'));$horizon=$start+90*86400;$balance=(float)$summary['liquidity'];$points=[];$occ=[];foreach($recurring as $r){foreach(rh24_finance_recurring_occurrences($r,$start,$horizon) as $ts)$occ[]=['ts'=>$ts,'direction'=>$r['direction'],'amount'=>(float)$r['amount']];}
    for($w=0;$w<=12;$w++){$from=$start+$w*7*86400;$to=min($horizon,$from+6*86400);$in=0.0;$out=0.0;foreach($open as $r){$d=strtotime((string)$r['due_date']);if($d>=$from&&$d<=$to)$in+=(float)$r['gross']+(float)($r['dunning_fees']??0);}foreach($expenses as $e){if(($e['payment_status']??'')==='paid'||($e['record_status']??'')!=='active'||empty($e['due_date']))continue;$d=strtotime((string)$e['due_date']);if($d>=$from&&$d<=$to)$out+=(float)$e['gross_amount'];}foreach($occ as $o){if($o['ts']<$from||$o['ts']>$to)continue;if($o['direction']==='income')$in+=$o['amount'];else$out+=$o['amount'];}$balance+=$in-$out;$points[]=['week'=>$w,'label'=>date('d.m',$from).'–'.date('d.m',$to),'in'=>round($in,2),'out'=>round($out,2),'balance'=>round($balance,2)];}
    return $points;
}
function rh24_finance_data(): array {
    rh24_finance_ensure_ready(rh24_db());
    $sales=rh24_finance_sales_rows();$expenses=rh24_finance_expenses();$cash=rh24_finance_cash_entries();$banks=rh24_finance_bank_accounts();$transactions=rh24_finance_transactions();$open=rh24_finance_open_receivables($sales);$recurring=rh24_finance_recurring();$summary=rh24_finance_summary($sales,$expenses,$cash,$banks,$open,$transactions);
    return ['summary'=>$summary,'config'=>rh24_finance_config(),'sales'=>$sales,'open_receivables'=>$open,'expenses'=>$expenses,'suppliers'=>rh24_finance_suppliers(),'transactions'=>$transactions,'cash_entries'=>$cash,'bank_accounts'=>$banks,'cost_centers'=>rh24_finance_cost_centers(),'accounts'=>rh24_finance_accounts(),'assets'=>rh24_finance_assets(),'recurring'=>$recurring,'budgets'=>rh24_finance_budgets(),'closings'=>rh24_finance_closings(),'liquidity'=>rh24_finance_liquidity($summary,$open,$expenses,$recurring)];
}

function rh24_finance_dunning_record(string $orderNo): array {
    $order=rh24_order_by_no($orderNo);if(!$order)throw new RuntimeException('Bestellung nicht gefunden.');
    $open=null;foreach(rh24_finance_open_receivables(rh24_finance_sales_rows()) as $r)if((string)$r['order_no']===$orderNo){$open=$r;break;}
    if(!$open)throw new RuntimeException('Für diese Bestellung besteht keine offene Forderung.');
    $q=rh24_db()->prepare('SELECT * FROM finance_dunning WHERE order_no=?');$q->execute([$orderNo]);$d=$q->fetch()?:[];
    return ['order'=>$order,'open'=>$open,'dunning'=>$d,'profile'=>rh24_invoice_profile()];
}
function rh24_finance_dunning_title(int $level): string {return match($level){1=>'Zahlungserinnerung',2=>'1. Mahnung',3=>'Letzte Mahnung',default=>'Zahlungserinnerung'};}
function rh24_finance_dunning_pdf(string $orderNo): string {
    $r=rh24_finance_dunning_record($orderNo);$o=$r['order'];$x=$r['open'];$d=$r['dunning'];$p=$r['profile'];$c=$o['customer']??[];$level=max(1,(int)($d['dunning_level']??1));$title=rh24_finance_dunning_title($level);$fees=(float)($d['fees']??0);$total=(float)$x['gross']+$fees;$pdf=new Rh24MiniPdf();$brown=[.36,.23,.12];$orange=[.82,.43,.14];$muted=[.39,.37,.35];
    $pdf->fillRect(0,0,595.28,12,$brown);$pdf->text(48,45,19,'RAEUCHERHAKEN',true,$brown);$pdf->text(246,45,25,'24',true,$orange);$pdf->text(48,62,8,trim(($p['company_name']??'').' · '.($p['street']??'').' · '.($p['zip']??'').' '.($p['city']??'')),false,$muted);$pdf->text(385,45,16,strtoupper($title),true,$brown);$pdf->line(48,76,547,76,1.1,$brown);
    $y=110;$pdf->text(48,$y,8,'EMPFÄNGER',true,$orange);$y+=18;$name=trim((string)($c['company']??''))?:trim((string)($c['name']??'Kunde'));$pdf->text(48,$y,11,$name,true,$brown);$y+=15;if(!empty($c['street'])){$pdf->text(48,$y,9,(string)$c['street']);$y+=13;}$pdf->text(48,$y,9,trim((string)($c['zip']??'').' '.(string)($c['city']??'')));
    $pdf->fillRect(342,98,205,102,[.97,.95,.92]);$my=116;foreach([['Bestellung',$orderNo],['Rechnung',(string)($x['document_no']?:'–')],['Fällig',date('d.m.Y',strtotime((string)$x['due_date']))],['Mahnstufe',(string)$level]] as [$k,$v]){$pdf->text(355,$my,7.5,$k,true,$muted);$pdf->text(442,$my,8.3,$v,false,$brown);$my+=20;}
    $y=242;$pdf->text(48,$y,13,$title,true,$brown);$y+=28;$intro=$level<=1?'bei der Durchsicht unseres Zahlungseingangs ist uns aufgefallen, dass die unten aufgeführte Rechnung noch offen ist. Falls Sie den Betrag zwischenzeitlich überwiesen haben, betrachten Sie dieses Schreiben bitte als gegenstandslos.':($level===2?'trotz Fälligkeit konnten wir für die unten aufgeführte Forderung bislang keinen vollständigen Zahlungseingang feststellen. Bitte gleichen Sie den offenen Betrag innerhalb der gesetzten Frist aus.':'die unten aufgeführte Forderung ist weiterhin offen. Wir bitten Sie letztmalig, den Gesamtbetrag innerhalb der gesetzten Frist auszugleichen.');$y=$pdf->multiText(48,$y,495,9,'Guten Tag, '.$intro,false,1.45,$muted)+12;
    $pdf->fillRect(48,$y,499,26,$brown);$pdf->text(58,$y+17,8,'Beleg',true,[1,1,1]);$pdf->text(210,$y+17,8,'Fälligkeit',true,[1,1,1]);$pdf->text(330,$y+17,8,'Hauptforderung',true,[1,1,1]);$pdf->text(455,$y+17,8,'Gesamt',true,[1,1,1]);$y+=42;$pdf->text(58,$y,9,(string)($x['document_no']?:$orderNo),true,$brown);$pdf->text(210,$y,9,date('d.m.Y',strtotime((string)$x['due_date'])));$pdf->text(330,$y,9,rh24_pdf_money((float)$x['gross']));$pdf->text(455,$y,9,rh24_pdf_money($total),true,$orange);$y+=25;if($fees>0){$pdf->text(330,$y,8,'inkl. Mahngebühren:',false,$muted);$pdf->text(455,$y,8,rh24_pdf_money($fees),true,$brown);$y+=18;}
    $due=(string)($d['next_due_date']??$x['due_date']);$y+=16;$pdf->text(48,$y,9,'Bitte zahlen Sie bis spätestens '.date('d.m.Y',strtotime($due)).'.',true,$brown);$y+=22;if(trim((string)($p['iban']??''))!==''){$pdf->text(48,$y,8,'ZAHLUNGSINFORMATION',true,$orange);$y+=15;$pdf->text(48,$y,9,'IBAN: '.$p['iban'].(trim((string)($p['bic']??''))!==''?' · BIC: '.$p['bic']:''),true,$brown);$y+=14;$pdf->text(48,$y,8.5,'Verwendungszweck: '.(string)($x['document_no']?:$orderNo),false,$muted);}
    if(trim((string)($d['notes']??''))!==''){$y+=26;$pdf->text(48,$y,8,'HINWEIS',true,$orange);$y+=14;$pdf->multiText(48,$y,495,8,(string)$d['notes'],false,1.4,$muted);}
    $pdf->line(48,792,547,792,.5,[.82,.79,.75]);$pdf->text(48,808,7.5,trim(($p['company_name']??'').' · '.($p['owner']??'')),false,$muted);$pdf->text(48,820,7.5,trim(($p['email']??'').' · '.($p['phone']??'').' · '.($p['website']??'')),false,$muted);$tax=trim((string)($p['vat_id']??''))!==''?'USt-IdNr.: '.$p['vat_id']:'Steuernr.: '.($p['tax_no']??'');$pdf->text(330,808,7.5,$tax,false,$muted);return $pdf->output($title.' '.$orderNo);
}
