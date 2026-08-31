<?php
/* =====================================================================
   ORGABOARD · SMOKY BUSINESS ASSISTANT   (assistant.php)
   ---------------------------------------------------------------------
   Der interne Assistent beantwortet Fragen zum laufenden Geschäft.

   Wichtige Festlegungen:
   · Er liest ausschliesslich. Er ändert, löscht und verschickt nichts.
     Jede Änderung bleibt eine bewusste Handlung des Benutzers.
   · Er antwortet nur mit Zahlen, die er im selben Moment aus der
     Datenbank berechnet hat. Es wird nichts geschätzt.
   · Er zeigt nur, was das angemeldete Konto auch sehen darf.
   · Es wird kein externer Dienst aufgerufen und nichts übertragen.
   ===================================================================== */
declare(strict_types=1);

require_once __DIR__ . '/cockpit.php';

function rh24_as_norm(string $s): string {
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    $s = strtr($s, ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss']);
    $s = preg_replace('/[^a-z0-9]+/u', ' ', $s) ?? '';
    return trim(preg_replace('/\s+/', ' ', $s) ?? '');
}
function rh24_as_has(string $hay, array $needles): bool {
    foreach ($needles as $n) if ($n !== '' && str_contains($hay, $n)) return true;
    return false;
}
/**
 * Zeichenlänge. mbstring ist auf den meisten Hostings vorhanden, aber
 * nicht garantiert – ohne die Erweiterung würde der Aufruf die Seite
 * abbrechen. Die Rückfallebene zählt Bytes; für die reine
 * Mindestlängenprüfung genügt das.
 */
function rh24_as_len(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function rh24_as_money(float $v): string {
    return number_format($v, 2, ',', '.') . ' €';
}

/**
 * Baut eine Antwort aus Textblöcken, Tabellenzeilen und Aktionskarten.
 * Jede Aktion verweist auf einen Bereich, den es im Orgaboard gibt.
 */
function rh24_as_reply(string $title, array $blocks = [], array $actions = [], array $rows = []): array {
    return ['title' => $title, 'blocks' => $blocks, 'actions' => $actions, 'rows' => $rows];
}

/**
 * Deutscher Name eines Bereichs. Die internen Bezeichner sind englisch
 * ("orders", "shipping"); in der Oberfläche hat das nichts zu suchen.
 */
function rh24_as_view_label(string $view): string {
    $map = [
        'dashboard'   => 'Chef-Dashboard',
        'orders'      => 'Bestellungen',
        'customers'   => 'Kunden',
        'inventory'   => 'Lager',
        'products'    => 'Produkte',
        'production'  => 'Produktion',
        'prototypes'  => 'Prototypen',
        'shipping'    => 'Versand',
        'documents'   => 'Rechnungen',
        'finance'     => 'Finanzen',
        'messages'    => 'Nachrichten',
        'reviews'     => 'Bewertungen',
        'appointments'=> 'Termine',
        'marketplace' => 'An- & Verkaufen',
        'dealers'     => 'Händler',
        'settings'    => 'Einstellungen',
        'administration' => 'Administration',
    ];
    return $map[$view] ?? 'Bereich';
}

/**
 * Antwort auf alle Lagerfragen. Ausgelagert, weil dieselbe Auskunft von
 * mehreren Frageformen erreicht wird ("was ist knapp", "was muss
 * nachbestellt werden", "welche Artikel sind kritisch").
 * Es wird nichts bestellt – die Vorschläge sind Vorschläge.
 */
function rh24_as_inventory_answer(PDO $db, array $r): array {
    if (!rh24_can('view_inventory')) {
        return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für Lagerdaten fehlt diesem Konto die Berechtigung.']]);
    }
    $inv = rh24_cockpit_inventory($db, $r);
    if (empty($inv['available'])) {
        return rh24_as_reply('Kein Lager angelegt', [['t' => 'p', 'v' => 'Es sind noch keine Lagerartikel hinterlegt.']]);
    }
    $need = array_values(array_filter($inv['rows'], fn($x) => $x['suggestion'] !== null));
    if (!$need) {
        return rh24_as_reply('Bestand ist in Ordnung', [['t' => 'p', 'v' => 'Kein Artikel liegt unter dem Mindestbestand oder unter zwei Wochen Reichweite.']]);
    }
    $rows = [];
    foreach (array_slice($need, 0, 12) as $x) {
        if ((int)$x['stock'] <= 0)            $reach = 'ausverkauft';
        elseif ($x['reach_days'] !== null)    $reach = 'Reichweite ca. ' . $x['reach_days'] . ' ' . ((int)$x['reach_days'] === 1 ? 'Tag' : 'Tage');
        else                                  $reach = 'Reichweite nicht berechenbar';
        $rows[] = [
            'a' => $x['name'],
            'b' => 'Bestand ' . $x['stock'] . ' ' . $x['unit'],
            'c' => $reach . ' · Vorschlag: ' . $x['suggestion'] . ' ' . $x['unit'] . ' nachbestellen',
            'action' => ['label' => 'Lager öffnen', 'view' => 'inventory', 'focus' => $x['id']],
        ];
    }
    return rh24_as_reply(
        count($need) . ' ' . (count($need) === 1 ? 'Artikel braucht' : 'Artikel brauchen') . ' Nachschub',
        [['t' => 'note', 'v' => 'Die Vorschläge beruhen auf dem tatsächlichen Verbrauch der letzten 90 Tage. Bestellt wird nichts automatisch.']],
        [['label' => 'Lager öffnen', 'view' => 'inventory']],
        $rows
    );
}

function rh24_assistant_answer(string $question): array {
    $db = rh24_db();
    $r  = rh24_cp_ranges();
    $q  = rh24_as_norm($question);

    /* ---------------------------------------------- Bestellnummer?
       Es wird jede Zeichenfolge geprüft, die eine Bestellnummer sein
       könnte – der Vorrat an Nummernkreisen ist über die Jahre
       gewachsen (RH24-WEB-…, RH24-P-…, ältere kurze Nummern). Ob es
       wirklich eine ist, entscheidet die Datenbank, nicht ein Muster.
       Ohne Treffer läuft die Frage weiter durch die anderen Zweige,
       damit sie nicht fälschlich als Bestellsuche endet. */
    $orderCandidate = '';
    if (preg_match_all('/\b([A-Za-z]{0,6}-?[A-Za-z0-9]*\d[A-Za-z0-9-]{2,38})\b/', $question, $mm)) {
        foreach ($mm[1] as $cand) {
            $candidate = strtoupper(trim($cand, '-'));
            if ($candidate === '' || !preg_match('/\d/', $candidate)) continue;
            if (preg_match('/^\d{1,3}$/', $candidate)) continue;   // blosse Mengenangabe
            $orderCandidate = $candidate;
            if (rh24_can('view_orders')) {
                $st = $db->prepare('SELECT order_no,status,status_label,payment_status,carrier,tracking,customer_json,totals_json,created_at,updated_at
                                    FROM orders WHERE order_no=? OR order_no LIKE ? LIMIT 1');
                $st->execute([$candidate, '%' . $candidate]);
                $o = $st->fetch();
                if ($o) {
                    $cust = rh24_cp_json($o['customer_json'] ?? '');
                    $tot  = rh24_cp_json($o['totals_json'] ?? '');
                    return rh24_as_reply(
                        'Bestellung ' . $o['order_no'],
                        [
                            ['t' => 'facts', 'v' => [
                                ['label' => 'Status',   'value' => (string)$o['status_label']],
                                ['label' => 'Zahlung',  'value' => ['pending'=>'offen','paid'=>'bezahlt','refunded'=>'erstattet','cancelled'=>'storniert'][(string)$o['payment_status']] ?? (string)$o['payment_status']],
                                ['label' => 'Betrag',   'value' => rh24_as_money(rh24_cp_gross($tot))],
                                ['label' => 'Kunde',    'value' => (string)($cust['name'] ?? '–')],
                                ['label' => 'Versand',  'value' => trim((string)$o['carrier'] . ' ' . (string)$o['tracking']) ?: '–'],
                                ['label' => 'Eingang',  'value' => date('d.m.Y H:i', strtotime((string)$o['created_at']))],
                            ]],
                        ],
                        [['label' => 'Bestellung öffnen', 'view' => 'orders', 'focus' => (string)$o['order_no']]]
                    );
                }
                /* Vielleicht ein Vorgang statt einer Bestellung. */
                if (rh24_can('view_prototypes')) {
                    $st = $db->prepare('SELECT reference,project_name,status_label,updated_at FROM prototypes
                                        WHERE reference=? OR reference LIKE ? OR order_no=? LIMIT 1');
                    $st->execute([$candidate, '%' . $candidate, $candidate]);
                    $p = $st->fetch();
                    if ($p) {
                        return rh24_as_reply(
                            'Vorgang ' . $p['reference'],
                            [['t' => 'facts', 'v' => [
                                ['label' => 'Projekt', 'value' => (string)$p['project_name']],
                                ['label' => 'Status',  'value' => (string)$p['status_label']],
                                ['label' => 'Zuletzt', 'value' => date('d.m.Y H:i', strtotime((string)$p['updated_at']))],
                            ]]],
                            [['label' => 'Vorgang öffnen', 'view' => 'prototypes', 'focus' => (string)$p['reference']]]
                        );
                    }
                }
            }
        }
    }

    /* ------------------------------------------------ Nachbestellung
       Steht bewusst vor den allgemeinen Zweigen: „Was muss nachbestellt
       werden?“ ist eine Lagerfrage, keine Tagesübersicht. */
    if (rh24_as_has($q, ['nachbestell', 'nachschub', 'knapp', 'mindestbestand', 'lager', 'bestand', 'ausverkauft', 'reichweite', 'artikel'])
        && !rh24_as_has($q, ['bestellung', 'bestellungen', 'bestellnummer'])) {
        return rh24_as_inventory_answer($db, $r);
    }

    /* ------------------------------------------ Was ist heute zu tun */
    if (rh24_as_has($q, ['was muss heute', 'was ist heute', 'heute erledig', 'zu tun', 'aufgaben heute',
                         'steht an', 'steht heute an', 'liegt an', 'liegt heute an', 'todo',
                         'ueberblick', 'tagesbrief', 'briefing', 'guten morgen', 'wie sieht es aus'])) {
        $payload   = rh24_cockpit_payload(30);
        $brief     = $payload['brief'];
        $attention = $payload['attention'];
        $lines = [];
        foreach ($brief as $b) $lines[] = $b['text'];
        $crit = array_values(array_filter($attention, fn($a) => $a['level'] === 'critical'));
        $blocks = [['t' => 'ul', 'v' => $lines]];
        if ($crit) {
            $blocks[] = ['t' => 'h', 'v' => 'Dringend'];
            $blocks[] = ['t' => 'ul', 'v' => array_map(fn($a) => $a['title'] . ': ' . $a['detail'], array_slice($crit, 0, 5))];
        }
        $actions = [];
        foreach (array_slice($brief, 0, 3) as $b) {
            if (!empty($b['view'])) {
                $actions[] = ['label' => rh24_as_view_label((string)$b['view']) . ' öffnen', 'view' => $b['view']];
            }
        }
        return rh24_as_reply('Heute im Überblick', $blocks, $actions);
    }

    /* -------------------------------------------- Kritische Vorgänge */
    if (rh24_as_has($q, ['kritisch', 'dringend', 'probleme', 'haengt', 'stecken'])) {
        $items = rh24_cockpit_attention($db, $r);
        $crit  = array_values(array_filter($items, fn($a) => $a['level'] === 'critical'));
        if (!$crit) {
            return rh24_as_reply('Nichts Kritisches', [['t' => 'p', 'v' => 'Es gibt derzeit keinen Vorgang, der als kritisch eingestuft ist.']]);
        }
        $rows = [];
        foreach (array_slice($crit, 0, 12) as $a) {
            $rows[] = ['a' => $a['group'], 'b' => $a['title'], 'c' => $a['detail'],
                       'action' => $a['actions'][0] ?? null];
        }
        return rh24_as_reply(count($crit) . ' kritische ' . (count($crit) === 1 ? 'Meldung' : 'Meldungen'), [], [], $rows);
    }

    /* ------------------------------------------- Überfällige Zahlungen */
    if (rh24_as_has($q, ['ueberfaellig', 'rechnung', 'offene forderung', 'nicht bezahlt', 'mahn', 'zahlt nicht'])) {
        if (!rh24_can('view_orders') && !rh24_can('view_finance')) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für Zahlungsdaten fehlt diesem Konto die Berechtigung.']]);
        }
        $terms = max(1, (int)rh24_setting_get('invoice_payment_days', '7'));
        $limit = date('Y-m-d H:i:s', time() - ($terms + 1) * 86400);
        $st = $db->prepare("SELECT order_no,created_at,totals_json,customer_json FROM orders
                            WHERE payment_status='pending' AND status<>'cancelled' AND created_at<?
                            ORDER BY created_at ASC LIMIT 20");
        $st->execute([$limit]);
        $rowsRaw = $st->fetchAll();
        if (!$rowsRaw) {
            return rh24_as_reply('Keine überfälligen Zahlungen', [['t' => 'p', 'v' => 'Alle offenen Beträge liegen noch innerhalb des Zahlungsziels von ' . $terms . ' Tagen.']]);
        }
        $sum = 0.0; $rows = [];
        foreach ($rowsRaw as $row) {
            $g = rh24_cp_gross(rh24_cp_json($row['totals_json'] ?? ''));
            $sum += $g;
            $days = max(1, (int)floor((time() - strtotime((string)$row['created_at'])) / 86400));
            $cust = rh24_cp_json($row['customer_json'] ?? '');
            $rows[] = [
                'a' => (string)$row['order_no'],
                'b' => (string)($cust['name'] ?? '–'),
                'c' => rh24_as_money($g) . ' · seit ' . $days . ' Tagen offen',
                'action' => ['label' => 'Öffnen', 'view' => 'orders', 'focus' => (string)$row['order_no']],
            ];
        }
        return rh24_as_reply(
            count($rows) . ' ' . (count($rows) === 1 ? 'überfällige Zahlung' : 'überfällige Zahlungen'),
            [['t' => 'p', 'v' => 'Offener Betrag insgesamt: ' . rh24_as_money($sum) . '. Zahlungsziel laut Einstellungen: ' . $terms . ' Tage.']],
            [['label' => 'Bestellungen öffnen', 'view' => 'orders']],
            $rows
        );
    }

    /* ------------------------------------------------ Bestseller */
    if (rh24_as_has($q, ['verkauft sich', 'bestseller', 'topseller', 'beste produkte', 'meistverkauft', 'top produkt', 'laeuft am besten'])) {
        if (!rh24_can('view_products')) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für Produktdaten fehlt diesem Konto die Berechtigung.']]);
        }
        $days = rh24_as_has($q, ['woche', '7 tage']) ? 7 : (rh24_as_has($q, ['jahr']) ? 365 : (rh24_as_has($q, ['quartal', '90']) ? 90 : 30));
        $p = rh24_cockpit_products($db, $r, $days);
        if (($p['orders'] ?? 0) === 0) {
            return rh24_as_reply('Keine Verkäufe im Zeitraum', [['t' => 'p', 'v' => 'In den letzten ' . $days . ' Tagen wurde keine Bestellung erfasst.']]);
        }
        $rows = [];
        foreach (array_slice($p['top_revenue'], 0, 10) as $i => $x) {
            $rows[] = ['a' => ($i + 1) . '.', 'b' => $x['name'], 'c' => $x['qty'] . ' Stück · ' . rh24_as_money((float)$x['revenue'])];
        }
        return rh24_as_reply(
            'Meistverkauft in ' . $days . ' Tagen',
            [['t' => 'p', 'v' => 'Grundlage: ' . $p['orders'] . ' Bestellungen im Zeitraum.']],
            [['label' => 'Produkt-Analyse öffnen', 'view' => 'product_analysis']],
            $rows
        );
    }

    /* -------------------------------------------------- Ladenhüter */
    if (rh24_as_has($q, ['ladenhueter', 'verkauft sich nicht', 'verkauft sich schlecht', 'langsamdreher',
                         'liegt wie blei', 'dreht nicht'])) {
        if (!rh24_can('view_products')) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für Produktdaten fehlt diesem Konto die Berechtigung.']]);
        }
        $p = rh24_cockpit_products($db, $r, 90);
        if (empty($p['slow'])) {
            return rh24_as_reply('Keine Ladenhüter', [['t' => 'p', 'v' => 'In den letzten 90 Tagen wurde jeder aktive Artikel mindestens einmal verkauft.']]);
        }
        $rows = [];
        foreach (array_slice($p['slow'], 0, 12) as $x) {
            $rows[] = ['a' => $x['name'], 'b' => $x['category'] ?: '–', 'c' => rh24_as_money((float)$x['price'])];
        }
        return rh24_as_reply(
            (int)$p['slow_total'] . ' ' . ((int)$p['slow_total'] === 1 ? 'Artikel ohne Verkauf' : 'Artikel ohne Verkauf') . ' in 90 Tagen',
            [], [['label' => 'Produktzentrale öffnen', 'view' => 'products']], $rows
        );
    }

    /* --------------------------------------------------- Umsatz */
    if (rh24_as_has($q, ['umsatz', 'einnahmen', 'erloes', 'verkauft fuer', 'wie viel umsatz', 'wieviel umsatz'])
        && !rh24_as_has($q, ['versand', 'versend', 'verschick', 'produktion', 'lager'])) {
        if (!rh24_can('view_orders') && !rh24_is_admin()) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für Umsatzdaten fehlt diesem Konto die Berechtigung.']]);
        }
        $kpi = rh24_cockpit_kpis($db, $r);
        $facts = [
            ['label' => 'Heute',            'value' => rh24_as_money((float)$kpi['revenue_today']['value'])],
            ['label' => '7 Tage',           'value' => rh24_as_money((float)$kpi['revenue_7']['value'])],
            ['label' => '30 Tage',          'value' => rh24_as_money((float)$kpi['revenue_30']['value'])],
            ['label' => 'Laufendes Jahr',   'value' => rh24_as_money((float)$kpi['revenue_year']['value'])],
            ['label' => 'Ø Warenkorb 30 T.','value' => rh24_as_money((float)$kpi['basket_30']['value'])],
        ];
        $d = $kpi['revenue_30']['compare']['previous']['delta'] ?? null;
        $blocks = [['t' => 'facts', 'v' => $facts]];
        if ($d !== null) {
            $blocks[] = ['t' => 'note', 'v' => 'Die letzten 30 Tage liegen ' . ($d >= 0 ? '+' : '') . number_format((float)$d, 1, ',', '.') . ' % gegenüber den 30 Tagen davor.'];
        }
        return rh24_as_reply('Umsatzübersicht', $blocks, [['label' => 'Finanzen öffnen', 'view' => 'finance']]);
    }

    /* --------------------------------------- Kunden warten auf Antwort */
    if (rh24_as_has($q, ['warten auf antwort', 'unbeantwortet', 'nachricht', 'anfrage', 'kunden warten'])) {
        $blocks = []; $rows = [];
        if (rh24_can('view_messages') && rh24_cp_table_exists($db, 'messages')) {
            $st = $db->prepare('SELECT COUNT(*) FROM messages WHERE recipient_user_id=? AND read_at IS NULL');
            $st->execute([rh24_user_id()]);
            $n = (int)$st->fetchColumn();
            $blocks[] = ['t' => 'p', 'v' => $n === 0 ? 'Es liegen keine ungelesenen internen Nachrichten vor.'
                                                     : ($n === 1 ? 'Eine ungelesene interne Nachricht.' : $n . ' ungelesene interne Nachrichten.')];
        }
        if (rh24_is_admin() && rh24_cp_table_exists($db, 'reviews')) {
            $n = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE status='new'")->fetchColumn();
            if ($n > 0) $blocks[] = ['t' => 'p', 'v' => $n . ' Kundenbewertung(en) warten auf eine Rückmeldung.'];
        }
        if (rh24_can('view_prototypes') && rh24_cp_table_exists($db, 'prototypes')) {
            $st = $db->query("SELECT reference,project_name,updated_at FROM prototypes WHERE status IN ('new','payment_pending','review') ORDER BY updated_at ASC LIMIT 8");
            foreach ($st->fetchAll() as $row) {
                $rows[] = ['a' => (string)$row['reference'], 'b' => (string)$row['project_name'],
                           'c' => 'Zuletzt geändert ' . date('d.m.Y', strtotime((string)$row['updated_at'])),
                           'action' => ['label' => 'Öffnen', 'view' => 'prototypes', 'focus' => (string)$row['reference']]];
            }
        }
        if (!$blocks && !$rows) $blocks[] = ['t' => 'p', 'v' => 'Es liegt nichts Unbeantwortetes vor.'];
        return rh24_as_reply('Offene Rückmeldungen', $blocks, [['label' => 'Nachrichten öffnen', 'view' => 'messages']], $rows);
    }

    /* --------------------------------------------------- Produktion */
    if (rh24_as_has($q, ['produktion', 'fertigung', 'werkstatt'])) {
        if (!rh24_can('view_production')) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für die Produktion fehlt diesem Konto die Berechtigung.']]);
        }
        $st = $db->query("SELECT order_no,status,status_label,updated_at FROM orders
                          WHERE status IN ('production','quality','packing') ORDER BY updated_at ASC LIMIT 15");
        $rowsRaw = $st->fetchAll();
        if (!$rowsRaw) return rh24_as_reply('Produktion frei', [['t' => 'p', 'v' => 'Zurzeit ist kein Auftrag in Fertigung, Prüfung oder Verpackung.']]);
        $rows = [];
        foreach ($rowsRaw as $row) {
            $rows[] = ['a' => (string)$row['order_no'], 'b' => (string)$row['status_label'],
                       'c' => 'Zuletzt geändert ' . date('d.m.Y H:i', strtotime((string)$row['updated_at'])),
                       'action' => ['label' => 'Öffnen', 'view' => 'production', 'focus' => (string)$row['order_no']]];
        }
        return rh24_as_reply(count($rows) . ' ' . (count($rows) === 1 ? 'Auftrag' : 'Aufträge') . ' in Arbeit', [], [['label' => 'Produktion öffnen', 'view' => 'production']], $rows);
    }

    /* ------------------------------------------------------ Versand */
    if (rh24_as_has($q, ['versand', 'versend', 'paket', 'sendung', 'verschick', 'tracking', 'zustell', 'abholung'])) {
        if (!rh24_can('view_shipping') && !rh24_can('view_orders')) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für Versanddaten fehlt diesem Konto die Berechtigung.']]);
        }
        $st = $db->query("SELECT order_no,status,status_label,carrier,tracking,updated_at FROM orders
                          WHERE status IN ('ready','packing') ORDER BY updated_at ASC LIMIT 15");
        $rowsRaw = $st->fetchAll();
        if (!$rowsRaw) return rh24_as_reply('Nichts zu versenden', [['t' => 'p', 'v' => 'Zurzeit wartet keine Bestellung auf den Versand.']]);
        $rows = [];
        foreach ($rowsRaw as $row) {
            $days = (int)floor((time() - strtotime((string)$row['updated_at'])) / 86400);
            $rows[] = ['a' => (string)$row['order_no'], 'b' => (string)$row['status_label'],
                       'c' => trim((string)$row['carrier'] . ' ' . (string)$row['tracking']) . ($days >= 2 ? ' · seit ' . $days . ' Tagen' : ''),
                       'action' => ['label' => 'Öffnen', 'view' => 'shipping', 'focus' => (string)$row['order_no']]];
        }
        return rh24_as_reply(count($rows) . ' ' . (count($rows) === 1 ? 'Sendung wartet' : 'Sendungen warten'), [], [['label' => 'Versand öffnen', 'view' => 'shipping']], $rows);
    }

    /* ------------------------------------------------ Systemzustand */
    if (rh24_as_has($q, ['systemzustand', 'system', 'technische fehler', 'gesundheit', 'health', 'laeuft alles'])) {
        if (!rh24_is_admin()) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Der Systemzustand ist Administratoren vorbehalten.']]);
        }
        $h = rh24_cockpit_health($db);
        $rows = [];
        foreach ($h['checks'] as $c) {
            $rows[] = ['a' => $c['label'], 'b' => ['ok'=>'in Ordnung','warning'=>'Hinweis','critical'=>'kritisch','unknown'=>'nicht prüfbar'][$c['state']] ?? $c['state'], 'c' => $c['detail']];
        }
        return rh24_as_reply('Systemzustand', [], [['label' => 'Einstellungen öffnen', 'view' => 'settings']], $rows);
    }

    /* ------------------------------------------------ Kundensuche */
    if (rh24_as_has($q, ['kunde', 'kundin', 'kontakt'])) {
        if (!rh24_can('view_customers')) {
            return rh24_as_reply('Kein Zugriff', [['t' => 'p', 'v' => 'Für Kundendaten fehlt diesem Konto die Berechtigung.']]);
        }
        $needle = trim(preg_replace('/\b(kunde|kundin|kontakt|zeige|mir|suche|finde|von|der|die|das)\b/iu', ' ', $question) ?? '');
        if (rh24_as_len($needle) >= 2) {
            $st = $db->prepare('SELECT id,name,company,email,city FROM customers
                                WHERE name LIKE ? OR company LIKE ? OR email LIKE ? ORDER BY updated_at DESC LIMIT 8');
            $like = '%' . $needle . '%';
            $st->execute([$like, $like, $like]);
            $rows = [];
            foreach ($st->fetchAll() as $row) {
                $rows[] = ['a' => (string)$row['name'], 'b' => (string)($row['company'] ?: $row['city'] ?: '–'),
                           'c' => (string)($row['email'] ?: '–'),
                           'action' => ['label' => 'Kundenakte', 'view' => 'customers', 'focus' => (string)$row['id']]];
            }
            if ($rows) return rh24_as_reply('Gefundene Kunden', [], [], $rows);
        }
        return rh24_as_reply('Kein Treffer', [['t' => 'p', 'v' => 'Zu dieser Suche wurde kein Kunde gefunden. Bitte Name, Firma oder E-Mail angeben.']]);
    }

    /* --------------------------------------------------- Fallback */
    if ($orderCandidate !== '' && rh24_can('view_orders')) {
        /* Es sah nach einer Nummer aus, es gab aber weder Bestellung
           noch Vorgang dazu. Das gehört gesagt, statt es zu verschweigen. */
        return rh24_as_reply(
            'Nichts gefunden',
            [
                ['t' => 'p', 'v' => 'Zu „' . $orderCandidate . '“ gibt es weder eine Bestellung noch einen Vorgang. Möglich sind ein Zahlendreher oder eine Nummer aus einem anderen System.'],
                ['t' => 'note', 'v' => 'Über die Suche oben lässt sich auch nach Kundenname, Firma oder Produkt suchen.'],
            ],
            [['label' => 'Bestellungen öffnen', 'view' => 'orders']]
        );
    }

    return rh24_as_reply(
        'Womit kann ich helfen?',
        [
            ['t' => 'p', 'v' => 'Ich beantworte Fragen zum laufenden Betrieb. Ich lese die Daten – ändern tust du selbst.'],
            ['t' => 'ul', 'v' => [
                'Was muss heute erledigt werden?',
                'Welche Bestellungen sind kritisch?',
                'Welche Produkte müssen bald nachbestellt werden?',
                'Welche Zahlungen sind überfällig?',
                'Was verkauft sich diese Woche am besten?',
                'Wie ist der Umsatz?',
                'Zeige mir Bestellung RH24-…',
            ]],
        ]
    );
}

/* =====================================================================
   GLOBALE SUCHE FÜR DIE BEFEHLSPALETTE
   Liefert nur, was das angemeldete Konto sehen darf.
   ===================================================================== */
function rh24_cockpit_search(string $needle): array {
    $db = rh24_db();
    $needle = trim($needle);
    if (rh24_as_len($needle) < 2) return [];
    $like = '%' . $needle . '%';
    $out  = [];

    if (rh24_can('view_orders')) {
        $st = $db->prepare('SELECT order_no,status_label,customer_json FROM orders
                            WHERE order_no LIKE ? OR customer_json LIKE ? ORDER BY created_at DESC LIMIT 6');
        $st->execute([$like, $like]);
        foreach ($st->fetchAll() as $row) {
            $c = rh24_cp_json($row['customer_json'] ?? '');
            $out[] = ['type' => 'Bestellung', 'title' => (string)$row['order_no'],
                      'subtitle' => trim((string)($c['name'] ?? '') . ' · ' . (string)$row['status_label'], ' ·'),
                      'view' => 'orders', 'focus' => (string)$row['order_no']];
        }
    }
    if (rh24_can('view_customers')) {
        $st = $db->prepare('SELECT id,name,company,email,city FROM customers
                            WHERE name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?
                            ORDER BY updated_at DESC LIMIT 6');
        $st->execute([$like, $like, $like, $like, $like]);
        foreach ($st->fetchAll() as $row) {
            $out[] = ['type' => 'Kunde', 'title' => (string)$row['name'],
                      'subtitle' => trim((string)($row['company'] ?: '') . ' ' . (string)($row['city'] ?: '')) ?: (string)($row['email'] ?: ''),
                      'view' => 'customers', 'focus' => (string)$row['id']];
        }
    }
    if (rh24_can('view_products')) {
        $st = $db->prepare('SELECT id,name,article_no,sku,category FROM products
                            WHERE name LIKE ? OR article_no LIKE ? OR sku LIKE ? ORDER BY name LIMIT 6');
        $st->execute([$like, $like, $like]);
        foreach ($st->fetchAll() as $row) {
            $out[] = ['type' => 'Produkt', 'title' => (string)$row['name'],
                      'subtitle' => trim('Art.-Nr. ' . (string)($row['article_no'] ?: '–') . ' · ' . (string)($row['category'] ?: '')),
                      'view' => 'products', 'focus' => (string)$row['id']];
        }
    }
    if (rh24_can('view_inventory') && rh24_cp_table_exists($db, 'inventory')) {
        $st = $db->prepare('SELECT id,name,stock,unit FROM inventory WHERE name LIKE ? OR id LIKE ? ORDER BY name LIMIT 5');
        $st->execute([$like, $like]);
        foreach ($st->fetchAll() as $row) {
            $out[] = ['type' => 'Lager', 'title' => (string)$row['name'],
                      'subtitle' => 'Bestand ' . (int)$row['stock'] . ' ' . (string)($row['unit'] ?: 'Stück'),
                      'view' => 'inventory', 'focus' => (string)$row['id']];
        }
    }
    if (rh24_is_admin() && rh24_cp_table_exists($db, 'dealers')) {
        $st = $db->prepare('SELECT id,company,contact,email FROM dealers WHERE company LIKE ? OR contact LIKE ? OR email LIKE ? ORDER BY company LIMIT 4');
        $st->execute([$like, $like, $like]);
        foreach ($st->fetchAll() as $row) {
            $out[] = ['type' => 'Händler', 'title' => (string)$row['company'],
                      'subtitle' => (string)($row['contact'] ?: $row['email'] ?: ''),
                      'view' => 'dealers', 'focus' => (string)$row['id']];
        }
    }
    return $out;
}
