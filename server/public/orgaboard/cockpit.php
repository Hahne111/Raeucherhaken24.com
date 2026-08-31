<?php
/* =====================================================================
   ORGABOARD · UNTERNEHMENS-COCKPIT — DATENMOTOR   (cockpit.php)
   ---------------------------------------------------------------------
   Liefert Kennzahlen, Aufmerksamkeitspunkte, Lagerprognosen, Auswertungen
   und den Systemzustand.

   Grundsätze:
   · Es werden ausschliesslich tatsächlich vorhandene Daten ausgewertet.
     Wo nichts vorliegt, wird das offen gemeldet – es werden keine Werte
     geschätzt und keine Beispieldaten erzeugt.
   · Nur lesende Zugriffe. Diese Datei verändert keinen Datensatz.
   · Alle Zeitgrenzen werden in PHP berechnet und als Parameter gebunden.
     Dadurch bleibt das SQL schlicht, portabel und gut prüfbar.
   · Jede Auswertung achtet auf die Rechte des angemeldeten Kontos.
   ===================================================================== */
declare(strict_types=1);

/* ------------------------------------------------------------ Hilfen */

function rh24_cp_json(?string $raw): array {
    if ($raw === null || $raw === '') return [];
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

/** Bruttobetrag einer Bestellung aus totals_json. */
function rh24_cp_gross(array $totals): float {
    if (isset($totals['gross'])) return round((float)$totals['gross'], 2);
    $sub = (float)($totals['subtotal'] ?? 0);
    $shp = (float)($totals['shipping'] ?? 0);
    return round($sub + $shp, 2);
}

/** Tabelle vorhanden? Ältere Installationen haben nicht jede Tabelle.
    Das Ergebnis wird je Verbindung gemerkt, nicht global – sonst würde
    eine zweite Verbindung die Antwort der ersten übernehmen. */
function rh24_cp_table_exists(PDO $db, string $table): bool {
    static $cache = [];
    $k = spl_object_id($db) . '|' . $table;
    if (isset($cache[$k])) return $cache[$k];
    try {
        $db->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
        return $cache[$k] = true;
    } catch (Throwable $e) {
        return $cache[$k] = false;
    }
}

/** Spalte vorhanden? */
function rh24_cp_column_exists(PDO $db, string $table, string $column): bool {
    static $cache = [];
    $k = spl_object_id($db) . '|' . $table . '.' . $column;
    if (isset($cache[$k])) return $cache[$k];
    try {
        $db->query('SELECT ' . $column . ' FROM ' . $table . ' LIMIT 1');
        return $cache[$k] = true;
    } catch (Throwable $e) {
        return $cache[$k] = false;
    }
}

/** Zeitgrenzen für Auswertungen – bewusst in PHP berechnet. */
function rh24_cp_ranges(): array {
    $now       = time();
    $todayFrom = strtotime('today');
    $dayNames  = ['So','Mo','Di','Mi','Do','Fr','Sa'];
    $dayFull   = ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
    return [
        'now'             => date('Y-m-d H:i:s', $now),
        'today_from'      => date('Y-m-d H:i:s', $todayFrom),
        'today_to'        => date('Y-m-d H:i:s', $todayFrom + 86400),
        'yesterday_from'  => date('Y-m-d H:i:s', $todayFrom - 86400),
        'yesterday_to'    => date('Y-m-d H:i:s', $todayFrom),
        'same_weekday_from' => date('Y-m-d H:i:s', $todayFrom - 7 * 86400),
        'same_weekday_to'   => date('Y-m-d H:i:s', $todayFrom - 6 * 86400),
        'week_from'       => date('Y-m-d H:i:s', $todayFrom - 6 * 86400),
        'days7_from'      => date('Y-m-d H:i:s', $todayFrom - 6 * 86400),
        'days7_prev_from' => date('Y-m-d H:i:s', $todayFrom - 13 * 86400),
        'days30_from'     => date('Y-m-d H:i:s', $todayFrom - 29 * 86400),
        'days30_prev_from'=> date('Y-m-d H:i:s', $todayFrom - 59 * 86400),
        'days90_from'     => date('Y-m-d H:i:s', $todayFrom - 89 * 86400),
        'year_from'       => date('Y-01-01 00:00:00', $now),
        'prev_year_from'  => date('Y-01-01 00:00:00', strtotime('-1 year', $now)),
        'prev_year_to'    => date('Y-01-01 00:00:00', $now),
        'weekday_label'   => $dayNames[(int)date('w', $now)],
        'weekday_full'    => $dayFull[(int)date('w', $now)],
        'today_label'     => date('d.m.Y', $now),
    ];
}

/**
 * Lädt die Bestellungen eines Zeitraums in schlanker Form.
 * Es werden nur die Felder geholt, die für Auswertungen gebraucht werden.
 */
function rh24_cp_orders_between(PDO $db, string $from, ?string $to = null): array {
    $sql = 'SELECT order_no,status,payment_status,sales_channel,source,carrier,tracking,'
         . 'customer_id,totals_json,items_json,created_at,updated_at'
         . ' FROM orders WHERE created_at>=?';
    $args = [$from];
    if ($to !== null) { $sql .= ' AND created_at<?'; $args[] = $to; }
    $sql .= ' ORDER BY created_at DESC';
    $st = $db->prepare($sql);
    $st->execute($args);
    $rows = [];
    foreach ($st->fetchAll() as $r) {
        $totals = rh24_cp_json($r['totals_json'] ?? '');
        $rows[] = [
            'order_no'       => (string)$r['order_no'],
            'status'         => (string)$r['status'],
            'payment_status' => (string)$r['payment_status'],
            'sales_channel'  => (string)($r['sales_channel'] ?? ''),
            'carrier'        => (string)($r['carrier'] ?? ''),
            'tracking'       => (string)($r['tracking'] ?? ''),
            'customer_id'    => (string)($r['customer_id'] ?? ''),
            'gross'          => rh24_cp_gross($totals),
            'items'          => rh24_cp_json($r['items_json'] ?? ''),
            'created_at'     => (string)$r['created_at'],
            'updated_at'     => (string)($r['updated_at'] ?? ''),
        ];
    }
    return $rows;
}

/** Zählt nur Bestellungen, die tatsächlich Umsatz bedeuten. */
function rh24_cp_is_revenue(array $o): bool {
    return $o['status'] !== 'cancelled';
}

function rh24_cp_sum(array $orders): array {
    $revenue = 0.0; $count = 0;
    foreach ($orders as $o) {
        if (!rh24_cp_is_revenue($o)) continue;
        $revenue += $o['gross'];
        $count++;
    }
    return [
        'revenue' => round($revenue, 2),
        'count'   => $count,
        'basket'  => $count > 0 ? round($revenue / $count, 2) : 0.0,
    ];
}

/** Veränderung in Prozent; null wenn kein sinnvoller Vergleich möglich. */
function rh24_cp_delta(float $current, float $previous): ?float {
    if ($previous <= 0.0) return null;
    return round((($current - $previous) / $previous) * 100, 1);
}

/* =====================================================================
   1 · KENNZAHLEN
   ===================================================================== */
function rh24_cockpit_kpis(PDO $db, array $r): array {
    $today      = rh24_cp_orders_between($db, $r['today_from']);
    $yesterday  = rh24_cp_orders_between($db, $r['yesterday_from'], $r['yesterday_to']);
    $sameWeekday= rh24_cp_orders_between($db, $r['same_weekday_from'], $r['same_weekday_to']);
    $d7         = rh24_cp_orders_between($db, $r['days7_from']);
    $d7prev     = rh24_cp_orders_between($db, $r['days7_prev_from'], $r['days7_from']);
    $d30        = rh24_cp_orders_between($db, $r['days30_from']);
    $d30prev    = rh24_cp_orders_between($db, $r['days30_prev_from'], $r['days30_from']);
    $year       = rh24_cp_orders_between($db, $r['year_from']);

    $sToday = rh24_cp_sum($today);
    $sYest  = rh24_cp_sum($yesterday);
    $sWeekd = rh24_cp_sum($sameWeekday);
    $s7     = rh24_cp_sum($d7);
    $s7p    = rh24_cp_sum($d7prev);
    $s30    = rh24_cp_sum($d30);
    $s30p   = rh24_cp_sum($d30prev);
    $sYear  = rh24_cp_sum($year);

    // Umsatzverlauf der letzten 14 Tage für die kleinen Diagramme
    $spark = [];
    $byDay = [];
    foreach (rh24_cp_orders_between($db, date('Y-m-d H:i:s', strtotime('today') - 13 * 86400)) as $o) {
        if (!rh24_cp_is_revenue($o)) continue;
        $d = substr($o['created_at'], 0, 10);
        $byDay[$d] = ($byDay[$d] ?? 0) + $o['gross'];
    }
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime('today') - $i * 86400);
        $spark[] = ['date' => $d, 'value' => round((float)($byDay[$d] ?? 0), 2)];
    }

    // Offene Bestellungen und offene Forderungen über den Gesamtbestand
    $openStates = ['new','payment_pending','paid','production','quality','packing','ready'];
    $ph = implode(',', array_fill(0, count($openStates), '?'));
    $st = $db->prepare('SELECT COUNT(*) FROM orders WHERE status IN (' . $ph . ')');
    $st->execute($openStates);
    $openOrders = (int)$st->fetchColumn();

    $st = $db->prepare("SELECT totals_json FROM orders WHERE payment_status='pending' AND status<>'cancelled'");
    $st->execute();
    $receivables = 0.0; $receivableCount = 0;
    foreach ($st->fetchAll() as $row) {
        $receivables += rh24_cp_gross(rh24_cp_json($row['totals_json'] ?? ''));
        $receivableCount++;
    }

    // Lagerwert aus tatsächlichen Beständen und hinterlegten Preisen
    $stockValue = null; $stockItems = 0; $stockUnpriced = 0;
    if (rh24_cp_table_exists($db, 'inventory')) {
        $sql = 'SELECT i.stock, p.base_price FROM inventory i LEFT JOIN products p ON p.id=i.id';
        $val = 0.0;
        foreach ($db->query($sql)->fetchAll() as $row) {
            $stockItems++;
            if ($row['base_price'] === null) { $stockUnpriced++; continue; }
            $val += ((int)$row['stock']) * ((float)$row['base_price']);
        }
        $stockValue = round($val, 2);
    }

    // Produktion und Versand
    $prodStates = ['production','quality'];
    $st = $db->prepare('SELECT COUNT(*) FROM orders WHERE status IN (?,?)');
    $st->execute($prodStates);
    $inProduction = (int)$st->fetchColumn();

    $st = $db->prepare("SELECT COUNT(*) FROM orders WHERE status IN ('packing','ready')");
    $st->execute();
    $toShip = (int)$st->fetchColumn();

    $st = $db->prepare("SELECT COUNT(*) FROM orders WHERE status='shipped'");
    $st->execute();
    $shipped = (int)$st->fetchColumn();

    // Neue Kunden
    $st = $db->prepare('SELECT COUNT(*) FROM customers WHERE created_at>=?');
    $st->execute([$r['today_from']]);
    $newCustomersToday = (int)$st->fetchColumn();
    $st->execute([$r['days30_from']]);
    $newCustomers30 = (int)$st->fetchColumn();

    return [
        'revenue_today' => [
            'value' => $sToday['revenue'],
            'compare' => [
                'yesterday'    => ['value' => $sYest['revenue'],  'delta' => rh24_cp_delta($sToday['revenue'], $sYest['revenue'])],
                'same_weekday' => ['value' => $sWeekd['revenue'], 'delta' => rh24_cp_delta($sToday['revenue'], $sWeekd['revenue']), 'label' => $r['weekday_label'] . ' der Vorwoche'],
            ],
            'spark' => $spark,
        ],
        'orders_today'     => ['value' => $sToday['count'], 'compare' => ['yesterday' => ['value' => $sYest['count'], 'delta' => rh24_cp_delta((float)$sToday['count'], (float)$sYest['count'])]]],
        'basket_today'     => ['value' => $sToday['basket'], 'compare' => ['days30' => ['value' => $s30['basket'], 'delta' => rh24_cp_delta($sToday['basket'], $s30['basket'])]]],
        'revenue_7'        => ['value' => $s7['revenue'],  'compare' => ['previous' => ['value' => $s7p['revenue'],  'delta' => rh24_cp_delta($s7['revenue'], $s7p['revenue'])]]],
        'revenue_30'       => ['value' => $s30['revenue'], 'compare' => ['previous' => ['value' => $s30p['revenue'], 'delta' => rh24_cp_delta($s30['revenue'], $s30p['revenue'])]]],
        'revenue_year'     => ['value' => $sYear['revenue'], 'count' => $sYear['count']],
        'open_orders'      => ['value' => $openOrders],
        'receivables'      => ['value' => round($receivables, 2), 'count' => $receivableCount],
        'stock_value'      => ['value' => $stockValue, 'items' => $stockItems, 'unpriced' => $stockUnpriced],
        'in_production'    => ['value' => $inProduction],
        'to_ship'          => ['value' => $toShip],
        'shipped'          => ['value' => $shipped],
        'new_customers'    => ['value' => $newCustomersToday, 'days30' => $newCustomers30],
        'basket_30'        => ['value' => $s30['basket'], 'compare' => ['previous' => ['value' => $s30p['basket'], 'delta' => rh24_cp_delta($s30['basket'], $s30p['basket'])]]],
    ];
}

/* =====================================================================
   2 · HEUTE BRAUCHT DEINE AUFMERKSAMKEIT
   Jeder Punkt entsteht aus einer nachvollziehbaren Regel auf echten Daten.
   ===================================================================== */
function rh24_cockpit_attention(PDO $db, array $r): array {
    $items = [];
    $now   = time();

    $add = function (array $item) use (&$items) { $items[] = $item; };

    /* --- Bezahlt, aber noch nicht in Bearbeitung ------------------- */
    if (rh24_can('view_orders')) {
        $limit = date('Y-m-d H:i:s', $now - 12 * 3600);
        $st = $db->prepare("SELECT order_no,created_at,updated_at,totals_json,customer_json FROM orders
                            WHERE payment_status='paid' AND status IN ('new','payment_pending','paid')
                              AND updated_at<? ORDER BY updated_at ASC LIMIT 25");
        $st->execute([$limit]);
        foreach ($st->fetchAll() as $row) {
            $hours = max(1, (int)floor(($now - strtotime((string)$row['updated_at'])) / 3600));
            $cust  = rh24_cp_json($row['customer_json'] ?? '');
            $add([
                'level'   => 'critical',
                'group'   => 'Bestellungen',
                'title'   => 'Bezahlt, aber noch nicht bearbeitet',
                'detail'  => 'Bestellung ' . $row['order_no'] . ($cust ? ' · ' . (string)($cust['name'] ?? '') : '')
                             . ' – seit ' . $hours . ' Stunden unverändert.',
                'amount'  => rh24_cp_gross(rh24_cp_json($row['totals_json'] ?? '')),
                'age_hours' => $hours,
                'ref'     => ['type' => 'order', 'id' => (string)$row['order_no']],
                'actions' => [
                    ['label' => 'Bestellung öffnen', 'view' => 'orders', 'focus' => (string)$row['order_no']],
                ],
            ]);
        }
    }

    /* --- Versandbereit, aber liegen geblieben ----------------------- */
    if (rh24_can('view_shipping') || rh24_can('view_orders')) {
        $limit = date('Y-m-d H:i:s', $now - 48 * 3600);
        $st = $db->prepare("SELECT order_no,updated_at,carrier,tracking FROM orders
                            WHERE status='ready' AND updated_at<? ORDER BY updated_at ASC LIMIT 25");
        $st->execute([$limit]);
        foreach ($st->fetchAll() as $row) {
            $days = max(1, (int)floor(($now - strtotime((string)$row['updated_at'])) / 86400));
            $add([
                'level'  => 'critical',
                'group'  => 'Versand',
                'title'  => 'Versandbereit, aber nicht übergeben',
                'detail' => 'Bestellung ' . $row['order_no'] . ' steht seit ' . $days . ' Tagen auf versandbereit'
                            . ($row['tracking'] ? ' (Tracking ' . $row['tracking'] . ')' : ' – noch ohne Tracking') . '.',
                'age_hours' => $days * 24,
                'ref'    => ['type' => 'order', 'id' => (string)$row['order_no']],
                'actions'=> [['label' => 'Versand öffnen', 'view' => 'shipping', 'focus' => (string)$row['order_no']]],
            ]);
        }

        /* Als versendet gebucht, aber ohne Sendungsnummer. Der Kunde
           bekommt dann keine Sendungsverfolgung und fragt erfahrungs-
           gemäss nach. Abholungen und Auslieferungen durch den
           Kundenberater sind davon ausgenommen – dort gibt es keine. */
        $st = $db->prepare("SELECT order_no,updated_at,carrier FROM orders
                            WHERE status='shipped' AND tracking=''
                              AND carrier NOT IN ('Abholung','Kundenberater','Selbstabholung')
                            ORDER BY updated_at DESC LIMIT 25");
        $st->execute();
        foreach ($st->fetchAll() as $row) {
            $hours = max(1, (int)floor(($now - strtotime((string)$row['updated_at'])) / 3600));
            $add([
                'level'  => 'warning',
                'group'  => 'Versand',
                'title'  => 'Versendet ohne Sendungsnummer',
                'detail' => 'Bestellung ' . $row['order_no'] . ' ist als versendet gebucht ('
                            . ((string)$row['carrier'] ?: 'ohne Versanddienst') . '), es ist aber keine Sendungsnummer hinterlegt.',
                'age_hours' => $hours,
                'ref'    => ['type' => 'order', 'id' => (string)$row['order_no']],
                'actions'=> [['label' => 'Versand öffnen', 'view' => 'shipping', 'focus' => (string)$row['order_no']]],
            ]);
        }
    }

    /* --- Überfällige Zahlungen -------------------------------------- */
    if (rh24_can('view_orders') || rh24_can('view_finance')) {
        $terms = max(1, (int)rh24_setting_get('invoice_payment_days', '7'));
        $limit = date('Y-m-d H:i:s', $now - ($terms + 1) * 86400);
        $st = $db->prepare("SELECT order_no,created_at,totals_json,customer_json FROM orders
                            WHERE payment_status='pending' AND status<>'cancelled'
                              AND created_at<? ORDER BY created_at ASC LIMIT 25");
        $st->execute([$limit]);
        foreach ($st->fetchAll() as $row) {
            $days = max(1, (int)floor(($now - strtotime((string)$row['created_at'])) / 86400));
            $cust = rh24_cp_json($row['customer_json'] ?? '');
            $add([
                'level'  => 'warning',
                'group'  => 'Finanzen',
                'title'  => 'Zahlung überfällig',
                'detail' => 'Bestellung ' . $row['order_no'] . ($cust ? ' · ' . (string)($cust['name'] ?? '') : '')
                            . ' – seit ' . $days . ' Tagen offen (Zahlungsziel ' . $terms . ' Tage).',
                'amount' => rh24_cp_gross(rh24_cp_json($row['totals_json'] ?? '')),
                'age_hours' => $days * 24,
                'ref'    => ['type' => 'order', 'id' => (string)$row['order_no']],
                'actions'=> [['label' => 'Bestellung öffnen', 'view' => 'orders', 'focus' => (string)$row['order_no']]],
            ]);
        }
    }

    /* --- Lagerbestand am oder unter dem Mindestbestand -------------- */
    if (rh24_can('view_inventory') && rh24_cp_table_exists($db, 'inventory')) {
        $st = $db->prepare('SELECT id,name,stock,minimum,unit FROM inventory
                            WHERE minimum>0 AND stock<=minimum ORDER BY (stock-minimum) ASC LIMIT 30');
        $st->execute();
        foreach ($st->fetchAll() as $row) {
            $stock = (int)$row['stock'];
            $min   = (int)$row['minimum'];
            $add([
                'level'  => $stock <= 0 ? 'critical' : 'warning',
                'group'  => 'Lager',
                'title'  => $stock <= 0 ? 'Artikel ausverkauft' : 'Bestand unter Mindestbestand',
                'detail' => (string)$row['name'] . ' – Bestand ' . $stock . ' ' . (string)($row['unit'] ?: 'Stück')
                            . ', Mindestbestand ' . $min . '.',
                'ref'    => ['type' => 'inventory', 'id' => (string)$row['id']],
                'actions'=> [['label' => 'Lager öffnen', 'view' => 'inventory', 'focus' => (string)$row['id']]],
            ]);
        }
    }

    /* --- Prototypen ohne Fortschritt -------------------------------- */
    if (rh24_can('view_prototypes') && rh24_cp_table_exists($db, 'prototypes')) {
        $limit = date('Y-m-d H:i:s', $now - 7 * 86400);
        $st = $db->prepare("SELECT reference,project_name,updated_at FROM prototypes
                            WHERE status IN ('new','payment_pending','review') AND updated_at<?
                            ORDER BY updated_at ASC LIMIT 15");
        $st->execute([$limit]);
        foreach ($st->fetchAll() as $row) {
            $days = max(1, (int)floor(($now - strtotime((string)$row['updated_at'])) / 86400));
            $add([
                'level'  => 'info',
                'group'  => 'Prototypen',
                'title'  => 'Prototypanfrage wartet',
                'detail' => (string)$row['project_name'] . ' (' . $row['reference'] . ') – seit ' . $days . ' Tagen ohne Änderung.',
                'age_hours' => $days * 24,
                'ref'    => ['type' => 'prototype', 'id' => (string)$row['reference']],
                'actions'=> [['label' => 'Prototypen öffnen', 'view' => 'prototypes', 'focus' => (string)$row['reference']]],
            ]);
        }
    }

    /* --- Ungelesene interne Nachrichten ----------------------------- */
    if (rh24_can('view_messages') && rh24_cp_table_exists($db, 'messages')) {
        $st = $db->prepare('SELECT COUNT(*) FROM messages WHERE recipient_user_id=? AND read_at IS NULL');
        $st->execute([rh24_user_id()]);
        $unread = (int)$st->fetchColumn();
        if ($unread > 0) {
            $add([
                'level'  => 'info',
                'group'  => 'Nachrichten',
                'title'  => $unread === 1 ? 'Eine ungelesene Nachricht' : $unread . ' ungelesene Nachrichten',
                'detail' => 'Interne Nachrichten warten auf Antwort.',
                'actions'=> [['label' => 'Nachrichten öffnen', 'view' => 'messages']],
            ]);
        }
    }

    /* --- Neue Bewertungen ------------------------------------------- */
    if (rh24_is_admin() && rh24_cp_table_exists($db, 'reviews')) {
        $st = $db->query("SELECT COUNT(*) FROM reviews WHERE status='new'");
        $n = (int)$st->fetchColumn();
        if ($n > 0) {
            $add([
                'level'  => 'info',
                'group'  => 'Bewertungen',
                'title'  => $n === 1 ? 'Eine neue Bewertung' : $n . ' neue Bewertungen',
                'detail' => 'Kundenfeedback wartet auf Freigabe oder Antwort.',
                'actions'=> [['label' => 'Bewertungen öffnen', 'view' => 'reviews']],
            ]);
        }
    }

    /* --- Neue Anfragen aus An- & Verkaufen -------------------------- */
    if (rh24_is_admin() && rh24_cp_table_exists($db, 'market_listings')) {
        $n = (int)$db->query("SELECT COUNT(*) FROM market_listings WHERE status='pending'")->fetchColumn();
        if ($n > 0) {
            $add([
                'level'  => 'info',
                'group'  => 'An- & Verkaufen',
                'title'  => $n === 1 ? 'Eine Anzeige wartet auf Freigabe' : $n . ' Anzeigen warten auf Freigabe',
                'detail' => 'Neue Einträge im Bereich An- & Verkaufen.',
                'actions'=> [['label' => 'Marktplatz öffnen', 'view' => 'marketplace']],
            ]);
        }
    }

    /* --- Fällige Termine heute -------------------------------------- */
    if (rh24_can('view_appointments') && rh24_cp_table_exists($db, 'advisor_appointments')) {
        $st = $db->prepare("SELECT COUNT(*) FROM advisor_appointments
                            WHERE starts_at>=? AND starts_at<? AND status NOT IN ('cancelled','done')");
        try {
            $st->execute([$r['today_from'], $r['today_to']]);
            $n = (int)$st->fetchColumn();
            if ($n > 0) {
                $add([
                    'level'  => 'info',
                    'group'  => 'Termine',
                    'title'  => $n === 1 ? 'Ein Termin heute' : $n . ' Termine heute',
                    'detail' => 'Im Terminplaner stehen heute Kundentermine an.',
                    'actions'=> [['label' => 'Termine öffnen', 'view' => 'appointments']],
                ]);
            }
        } catch (Throwable $e) { /* ältere Terminstruktur – Punkt entfällt */ }
    }

    /* Reihenfolge: kritisch zuerst, danach nach Alter */
    $rank = ['critical' => 0, 'warning' => 1, 'info' => 2];
    usort($items, function ($a, $b) use ($rank) {
        $ra = $rank[$a['level']] ?? 3;
        $rb = $rank[$b['level']] ?? 3;
        if ($ra !== $rb) return $ra <=> $rb;
        return (int)($b['age_hours'] ?? 0) <=> (int)($a['age_hours'] ?? 0);
    });

    return $items;
}

/* =====================================================================
   3 · LAGER 2.0 MIT REICHWEITENPROGNOSE
   Die Prognose entsteht aus dem tatsächlichen Verbrauch der letzten
   90 Tage. Gibt es keinen Verbrauch, wird das offen gesagt – es wird
   nichts geschätzt.
   ===================================================================== */
function rh24_cockpit_inventory(PDO $db, array $r): array {
    if (!rh24_cp_table_exists($db, 'inventory')) {
        return ['available' => false, 'rows' => [], 'note' => 'Es ist noch kein Lager angelegt.'];
    }

    // Verbrauch je Artikel aus den Bestellpositionen der letzten 90 Tage
    $sold = [];
    $days = 90;
    foreach (rh24_cp_orders_between($db, $r['days90_from']) as $o) {
        if (!rh24_cp_is_revenue($o)) continue;
        foreach ($o['items'] as $line) {
            if (!is_array($line)) continue;
            $id = (string)($line['id'] ?? '');
            if ($id === '') continue;
            $sold[$id] = ($sold[$id] ?? 0) + max(0, (int)($line['qty'] ?? 0));
        }
    }

    $rows = [];
    $sql = 'SELECT i.id,i.name,i.stock,i.minimum,i.unit,i.updated_at,p.base_price,p.category,p.status
            FROM inventory i LEFT JOIN products p ON p.id=i.id ORDER BY i.name';
    foreach ($db->query($sql)->fetchAll() as $row) {
        $id      = (string)$row['id'];
        $stock   = (int)$row['stock'];
        $minimum = (int)$row['minimum'];
        $sold90  = (int)($sold[$id] ?? 0);
        $perDay  = $sold90 > 0 ? $sold90 / $days : 0.0;

        $reach = null;              // Reichweite in Tagen
        $reachNote = 'In den letzten 90 Tagen wurde dieser Artikel nicht verkauft – daher ist keine Reichweite berechenbar.';
        if ($perDay > 0) {
            $reach = (int)floor($stock / $perDay);
            $reachNote = 'Berechnet aus ' . $sold90 . ' verkauften Einheiten in 90 Tagen.';
        }

        $level = 'ok';
        if ($stock <= 0)                         $level = 'critical';
        elseif ($minimum > 0 && $stock <= $minimum) $level = 'critical';
        elseif ($minimum > 0 && $stock <= $minimum * 1.5) $level = 'warning';
        elseif ($reach !== null && $reach <= 14)  $level = 'warning';

        // Nachbestellvorschlag: auffüllen bis Mindestbestand plus 30 Tage Verbrauch
        $suggestion = null;
        if ($minimum > 0 && $stock <= $minimum) {
            $target = $minimum + (int)ceil($perDay * 30);
            $suggestion = max(1, $target - $stock);
        } elseif ($reach !== null && $reach <= 14 && $perDay > 0) {
            $suggestion = max(1, (int)ceil($perDay * 30));
        }

        $rows[] = [
            'id'          => $id,
            'name'        => (string)$row['name'],
            'category'    => (string)($row['category'] ?? ''),
            'stock'       => $stock,
            'minimum'     => $minimum,
            'unit'        => (string)($row['unit'] ?: 'Stück'),
            'price'       => $row['base_price'] !== null ? round((float)$row['base_price'], 2) : null,
            'value'       => $row['base_price'] !== null ? round($stock * (float)$row['base_price'], 2) : null,
            'sold_90'     => $sold90,
            'per_day'     => round($perDay, 3),
            'reach_days'  => $reach,
            'reach_note'  => $reachNote,
            'level'       => $level,
            'suggestion'  => $suggestion,
            'updated_at'  => (string)($row['updated_at'] ?? ''),
        ];
    }

    usort($rows, function ($a, $b) {
        $rank = ['critical' => 0, 'warning' => 1, 'ok' => 2];
        $ra = $rank[$a['level']]; $rb = $rank[$b['level']];
        if ($ra !== $rb) return $ra <=> $rb;
        $rda = $a['reach_days'] ?? PHP_INT_MAX;
        $rdb = $b['reach_days'] ?? PHP_INT_MAX;
        return $rda <=> $rdb;
    });

    $critical = 0; $warning = 0; $value = 0.0; $unpriced = 0;
    foreach ($rows as $x) {
        if ($x['level'] === 'critical') $critical++;
        elseif ($x['level'] === 'warning') $warning++;
        if ($x['value'] === null) $unpriced++; else $value += $x['value'];
    }

    return [
        'available' => true,
        'rows'      => $rows,
        'summary'   => [
            'items'    => count($rows),
            'critical' => $critical,
            'warning'  => $warning,
            'value'    => round($value, 2),
            'unpriced' => $unpriced,
        ],
    ];
}

/* =====================================================================
   4 · PRODUKTAUSWERTUNG · TOPSELLER, LADENHÜTER, KOMBINATIONEN
   ===================================================================== */
function rh24_cockpit_products(PDO $db, array $r, int $days = 30): array {
    $from = $days >= 365 ? $r['year_from'] : date('Y-m-d H:i:s', strtotime('today') - ($days - 1) * 86400);
    $orders = rh24_cp_orders_between($db, $from);

    $agg = [];      // Artikel → Menge, Umsatz, Bestellungen
    $pairs = [];    // Artikelpaare für die Kombinationsanalyse
    $ordersCounted = 0;

    foreach ($orders as $o) {
        if (!rh24_cp_is_revenue($o)) continue;
        $ordersCounted++;
        $idsInOrder = [];
        foreach ($o['items'] as $line) {
            if (!is_array($line)) continue;
            $id = (string)($line['id'] ?? '');
            if ($id === '') continue;
            $qty = max(0, (int)($line['qty'] ?? 0));
            /* Bestellungen sind über viele Jahre entstanden. Alle heutigen
               Quellen (Shop, Kasse, Orgaboard) schreiben unit_price und
               line_total; ältere Datensätze können abweichen. Deshalb wird
               der Reihe nach geprüft und offen vermerkt, wenn sich kein
               Betrag ermitteln lässt – lieber keine Zahl als eine falsche. */
            $sum = null;
            foreach (['line_total', 'total', 'sum'] as $k) {
                if (isset($line[$k]) && is_numeric($line[$k])) { $sum = (float)$line[$k]; break; }
            }
            if ($sum === null) {
                foreach (['unit_price', 'price', 'einzelpreis'] as $k) {
                    if (isset($line[$k]) && is_numeric($line[$k])) { $sum = $qty * (float)$line[$k]; break; }
                }
            }
            if (!isset($agg[$id])) {
                $agg[$id] = ['id' => $id, 'name' => (string)($line['name'] ?? $id),
                             'qty' => 0, 'revenue' => 0.0, 'orders' => 0, 'revenue_known' => true];
            }
            $agg[$id]['qty']     += $qty;
            if ($sum === null) $agg[$id]['revenue_known'] = false;
            else               $agg[$id]['revenue'] += $sum;
            $agg[$id]['orders']  += 1;
            $idsInOrder[$id] = true;
        }
        $ids = array_keys($idsInOrder);
        sort($ids);
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $key = $ids[$i] . '|' . $ids[$j];
                $pairs[$key] = ($pairs[$key] ?? 0) + 1;
            }
        }
    }

    foreach ($agg as &$a) {
        $a['revenue'] = round($a['revenue'], 2);
        /* Liess sich für keine einzige Position ein Betrag ermitteln,
           wird das gemeldet statt 0,00 € zu behaupten. */
        if (!$a['revenue_known'] && $a['revenue'] <= 0.0) $a['revenue'] = null;
    }
    unset($a);

    $top = array_values($agg);
    usort($top, fn($x, $y) => (float)($y['revenue'] ?? -1) <=> (float)($x['revenue'] ?? -1));

    $byQty = array_values($agg);
    usort($byQty, fn($x, $y) => $y['qty'] <=> $x['qty']);

    // Ladenhüter: im Sortiment aktiv, aber im Zeitraum ohne Verkauf
    $slow = [];
    try {
        $st = $db->query("SELECT id,name,category,base_price FROM products
                          WHERE status='active' AND COALESCE(product_type,'product')<>'prototype' ORDER BY name");
        foreach ($st->fetchAll() as $p) {
            $id = (string)$p['id'];
            if (isset($agg[$id])) continue;
            $slow[] = ['id' => $id, 'name' => (string)$p['name'], 'category' => (string)($p['category'] ?? ''), 'price' => round((float)($p['base_price'] ?? 0), 2)];
        }
    } catch (Throwable $e) { /* Produkttabelle nicht verfügbar */ }

    // Häufige Kombinationen – nur echte Paare mit mindestens zwei Vorkommen
    arsort($pairs);
    $combos = [];
    foreach (array_slice($pairs, 0, 12, true) as $key => $count) {
        if ($count < 2) continue;
        [$a, $b] = explode('|', $key);
        $combos[] = [
            'a' => ['id' => $a, 'name' => $agg[$a]['name'] ?? $a],
            'b' => ['id' => $b, 'name' => $agg[$b]['name'] ?? $b],
            'count' => $count,
        ];
    }

    return [
        'days'        => $days,
        'orders'      => $ordersCounted,
        'top_revenue' => array_slice($top, 0, 10),
        'top_qty'     => array_slice($byQty, 0, 10),
        'slow'        => array_slice($slow, 0, 20),
        'slow_total'  => count($slow),
        'combos'      => $combos,
        'note'        => $ordersCounted === 0
            ? 'Im gewählten Zeitraum liegen keine Bestellungen vor.'
            : '',
    ];
}

/* =====================================================================
   5 · FINANZÜBERSICHT
   ===================================================================== */
function rh24_cockpit_finance(PDO $db, array $r): array {
    $out = ['available' => true];

    // Umsatzverlauf der letzten 30 Tage
    $byDay = [];
    foreach (rh24_cp_orders_between($db, $r['days30_from']) as $o) {
        if (!rh24_cp_is_revenue($o)) continue;
        $d = substr($o['created_at'], 0, 10);
        $byDay[$d] = ($byDay[$d] ?? 0) + $o['gross'];
    }
    $series = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime('today') - $i * 86400);
        $series[] = ['date' => $d, 'value' => round((float)($byDay[$d] ?? 0), 2)];
    }
    $out['revenue_series'] = $series;

    /* V2026.3 · Umsatz nach Vertriebskanal (letzte 30 Tage)
       Rein additiv: es kommt ein neuer Schlüssel hinzu, es wird nichts
       verändert oder entfernt. Die Werte stammen ausschliesslich aus
       tatsächlichen Bestellungen. Gibt es keine, bleibt die Liste leer –
       die Oberfläche zeigt dann einen Leerzustand statt erfundener
       Zahlen. */
    $channelSums   = [];
    $channelCounts = [];
    $channelTotal  = 0.0;
    foreach (rh24_cp_orders_between($db, $r['days30_from']) as $o) {
        if (!rh24_cp_is_revenue($o)) continue;
        $key = trim((string)($o['sales_channel'] ?? ''));
        if ($key === '') $key = 'unbekannt';
        $channelSums[$key]   = ($channelSums[$key] ?? 0) + $o['gross'];
        $channelCounts[$key] = ($channelCounts[$key] ?? 0) + 1;
        $channelTotal       += $o['gross'];
    }
    arsort($channelSums);
    $channelLabels = [
        'shop'        => 'Online-Shop',
        'online'      => 'Online-Shop',
        'web'         => 'Online-Shop',
        'pos'         => 'Kasse / POS',
        'kasse'       => 'Kasse / POS',
        'phone'       => 'Telefonisch',
        'telefon'     => 'Telefonisch',
        'field'       => 'Außendienst',
        'aussendienst'=> 'Außendienst',
        'dealer'      => 'Händler',
        'marketplace' => 'Marktplatz',
        'unbekannt'   => 'Ohne Kanalangabe',
    ];
    $channels = [];
    foreach ($channelSums as $key => $sum) {
        $channels[] = [
            'key'   => (string)$key,
            'label' => $channelLabels[strtolower((string)$key)] ?? (string)$key,
            'value' => round((float)$sum, 2),
            'count' => (int)($channelCounts[$key] ?? 0),
            'share' => $channelTotal > 0 ? round($sum / $channelTotal * 100, 1) : 0.0,
        ];
    }
    $out['channels']       = array_slice($channels, 0, 6);
    $out['channels_total'] = round($channelTotal, 2);

    // Zahlungsstatus des Gesamtbestands
    $counts = ['paid' => 0, 'pending' => 0, 'refunded' => 0, 'cancelled' => 0];
    $sums   = ['paid' => 0.0, 'pending' => 0.0];
    $st = $db->query('SELECT payment_status,status,totals_json FROM orders');
    foreach ($st->fetchAll() as $row) {
        $ps = (string)$row['payment_status'];
        if (!isset($counts[$ps])) $counts[$ps] = 0;
        $counts[$ps]++;
        if ($ps === 'paid' || $ps === 'pending') {
            $sums[$ps] += rh24_cp_gross(rh24_cp_json($row['totals_json'] ?? ''));
        }
    }
    $out['payment_counts'] = $counts;
    $out['payment_sums']   = ['paid' => round($sums['paid'], 2), 'pending' => round($sums['pending'], 2)];

    // Zahlungsarten
    $methods = [];
    $st = $db->query("SELECT payment_method,COUNT(*) c FROM orders WHERE status<>'cancelled' GROUP BY payment_method ORDER BY c DESC");
    foreach ($st->fetchAll() as $row) {
        $label = trim((string)$row['payment_method']);
        if ($label === '') $label = 'ohne Angabe';
        $methods[] = ['label' => $label, 'count' => (int)$row['c']];
    }
    $out['payment_methods'] = array_slice($methods, 0, 8);

    // Ausgaben aus der Buchhaltung, sofern vorhanden und erlaubt
    $out['expenses'] = null;
    if (rh24_can('view_finance') && rh24_cp_table_exists($db, 'finance_expenses')) {
        try {
            $st = $db->prepare('SELECT status,gross_amount,due_date FROM finance_expenses WHERE created_at>=?');
            $st->execute([$r['days30_from']]);
            $total = 0.0; $open = 0.0; $overdue = 0.0; $n = 0;
            $today = date('Y-m-d');
            foreach ($st->fetchAll() as $row) {
                $amount = (float)($row['gross_amount'] ?? 0);
                $total += $amount; $n++;
                $status = (string)($row['status'] ?? '');
                if ($status !== 'paid' && $status !== 'cancelled') {
                    $open += $amount;
                    $due = (string)($row['due_date'] ?? '');
                    if ($due !== '' && $due < $today) $overdue += $amount;
                }
            }
            $out['expenses'] = ['days30' => round($total, 2), 'open' => round($open, 2), 'overdue' => round($overdue, 2), 'count' => $n];
        } catch (Throwable $e) {
            $out['expenses'] = null;
        }
    }

    return $out;
}

/* =====================================================================
   6 · SYSTEMZUSTAND
   Es wird geprüft, was sich zuverlässig prüfen lässt. Nicht Prüfbares
   wird als „nicht prüfbar“ ausgewiesen und nicht behauptet.
   ===================================================================== */
function rh24_cockpit_health(PDO $db): array {
    $checks = [];
    $add = function (string $key, string $label, string $state, string $detail) use (&$checks) {
        $checks[] = ['key' => $key, 'label' => $label, 'state' => $state, 'detail' => $detail];
    };

    // Datenbank
    try {
        $db->query('SELECT 1');
        $add('db', 'Datenbank', 'ok', 'Verbindung steht.');
    } catch (Throwable $e) {
        $add('db', 'Datenbank', 'critical', 'Verbindung nicht möglich.');
    }

    // PHP
    $phpOk = version_compare(PHP_VERSION, '8.1', '>=');
    $add('php', 'PHP-Version', $phpOk ? 'ok' : 'warning', 'Aktiv: PHP ' . PHP_VERSION . ($phpOk ? '' : ' – eine neuere Version wird empfohlen.'));

    // Schreibrechte auf den Ordnern, die das System braucht
    $dirs = [
        'uploads/products'                 => dirname(__DIR__) . '/uploads/products',
        'orgaboard/private'                => __DIR__ . '/private',
        'orgaboard/finance-uploads'        => __DIR__ . '/finance-uploads',
        'orgaboard/trip-receipt-uploads'   => __DIR__ . '/trip-receipt-uploads',
    ];
    foreach ($dirs as $label => $path) {
        if (!is_dir($path)) {
            $add('dir:' . $label, 'Ordner ' . $label, 'warning', 'Ordner ist noch nicht vorhanden. Er wird beim ersten Upload angelegt.');
        } elseif (!is_writable($path)) {
            $add('dir:' . $label, 'Ordner ' . $label, 'critical', 'Kein Schreibrecht. Uploads schlagen fehl.');
        } else {
            $add('dir:' . $label, 'Ordner ' . $label, 'ok', 'Beschreibbar.');
        }
    }

    // Produktbilder, die im Datensatz stehen, aber als Datei fehlen
    try {
        $missing = [];
        $st = $db->query("SELECT id,name,image_path FROM products WHERE image_path<>'' LIMIT 800");
        foreach ($st->fetchAll() as $row) {
            $rel = (string)$row['image_path'];
            if ($rel === '' || str_starts_with($rel, 'http')) continue;
            $abs = dirname(__DIR__) . '/' . ltrim($rel, '/');
            if (!is_file($abs)) $missing[] = (string)$row['name'];
        }
        $add('images', 'Produktbilder', $missing ? 'warning' : 'ok',
            $missing ? count($missing) . ' hinterlegte Bilddatei(en) fehlen: ' . implode(', ', array_slice($missing, 0, 4)) . (count($missing) > 4 ? ' …' : '')
                     : 'Alle hinterlegten Produktbilder sind vorhanden.');
    } catch (Throwable $e) {
        $add('images', 'Produktbilder', 'unknown', 'Nicht prüfbar.');
    }

    // Rechnungsprofil
    if (function_exists('rh24_invoice_profile_readiness')) {
        try {
            $ready = rh24_invoice_profile_readiness();
            $add('invoice', 'Rechnungsprofil', !empty($ready['ready']) ? 'ok' : 'warning',
                !empty($ready['ready']) ? 'Vollständig hinterlegt.' : 'Es fehlen noch Angaben: ' . implode(', ', (array)($ready['missing'] ?? [])));
        } catch (Throwable $e) { /* optional */ }
    }

    // E-Mail-Versand der letzten 7 Tage
    if (rh24_cp_table_exists($db, 'mail_log')) {
        try {
            $st = $db->prepare("SELECT status,COUNT(*) c FROM mail_log WHERE created_at>=? GROUP BY status");
            $st->execute([date('Y-m-d H:i:s', strtotime('today') - 7 * 86400)]);
            $sent = 0; $failed = 0;
            foreach ($st->fetchAll() as $row) {
                if ((string)$row['status'] === 'sent') $sent = (int)$row['c'];
                else $failed += (int)$row['c'];
            }
            $add('mail', 'E-Mail-Versand (7 Tage)', $failed > 0 ? 'warning' : 'ok',
                $failed > 0 ? $failed . ' Versand(e) fehlgeschlagen, ' . $sent . ' erfolgreich.' : $sent . ' E-Mails erfolgreich versendet.');
        } catch (Throwable $e) {
            $add('mail', 'E-Mail-Versand', 'unknown', 'Nicht prüfbar.');
        }
    }

    // Zahlungs- und Versandschnittstellen
    foreach ([['payment_integrations', 'Zahlungsschnittstellen'], ['shipping_integrations', 'Versandschnittstellen']] as [$table, $label]) {
        if (!rh24_cp_table_exists($db, $table)) continue;
        try {
            $rows = $db->query('SELECT status FROM ' . $table)->fetchAll();
            $bad = 0; $good = 0;
            foreach ($rows as $row) {
                $s = (string)$row['status'];
                if ($s === 'connected' || $s === 'ready') $good++;
                elseif ($s === 'error') $bad++;
            }
            if (!$rows) continue;
            $add($table, $label, $bad > 0 ? 'warning' : 'ok',
                $bad > 0 ? $bad . ' Schnittstelle(n) melden einen Fehler.' : $good . ' Schnittstelle(n) verbunden.');
        } catch (Throwable $e) { /* optional */ }
    }

    // Freier Speicherplatz, sofern der Server ihn meldet
    $free = @disk_free_space(__DIR__);
    $total = @disk_total_space(__DIR__);
    if ($free !== false && $total !== false && $total > 0) {
        $pct = $free / $total;
        $add('disk', 'Speicherplatz', $pct < 0.08 ? 'critical' : ($pct < 0.15 ? 'warning' : 'ok'),
            round($free / 1073741824, 1) . ' GB von ' . round($total / 1073741824, 1) . ' GB frei.');
    } else {
        $add('disk', 'Speicherplatz', 'unknown', 'Der Server meldet keine Speicherinformationen.');
    }

    $state = 'ok';
    foreach ($checks as $c) {
        if ($c['state'] === 'critical') { $state = 'critical'; break; }
        if ($c['state'] === 'warning') $state = 'warning';
    }
    return ['state' => $state, 'checks' => $checks];
}

/* =====================================================================
   7 · MORGENÜBERBLICK UND ERKENNTNISSE
   Jede Aussage ist aus den obigen Zahlen abgeleitet und benennt den Weg.
   ===================================================================== */
function rh24_cockpit_brief(array $kpi, array $attention, array $inventory): array {
    $brief = [];

    if (($kpi['orders_today']['value'] ?? 0) > 0) {
        $brief[] = [
            'icon' => 'orders',
            'text' => $kpi['orders_today']['value'] . ' ' . ($kpi['orders_today']['value'] === 1 ? 'neue Bestellung' : 'neue Bestellungen') . ' heute',
            'view' => 'orders',
        ];
    }
    if (($kpi['to_ship']['value'] ?? 0) > 0) {
        $brief[] = ['icon' => 'shipping', 'text' => $kpi['to_ship']['value'] . ' ' . ($kpi['to_ship']['value'] === 1 ? 'Sendung wartet' : 'Sendungen warten') . ' auf Versand', 'view' => 'shipping'];
    }
    if (($kpi['in_production']['value'] ?? 0) > 0) {
        $brief[] = ['icon' => 'production', 'text' => $kpi['in_production']['value'] . ' ' . ($kpi['in_production']['value'] === 1 ? 'Auftrag' : 'Aufträge') . ' in Fertigung', 'view' => 'production'];
    }
    $overdue = 0;
    foreach ($attention as $a) if (($a['group'] ?? '') === 'Finanzen') $overdue++;
    if ($overdue > 0) {
        $brief[] = ['icon' => 'finance', 'text' => $overdue . ' ' . ($overdue === 1 ? 'Zahlung ist überfällig' : 'Zahlungen sind überfällig'), 'view' => 'orders'];
    }
    $crit = (int)($inventory['summary']['critical'] ?? 0);
    if ($crit > 0) {
        $brief[] = ['icon' => 'inventory', 'text' => $crit . ' ' . ($crit === 1 ? 'Artikel liegt' : 'Artikel liegen') . ' unter dem Mindestbestand', 'view' => 'inventory'];
    }
    if (($kpi['new_customers']['value'] ?? 0) > 0) {
        $brief[] = ['icon' => 'customers', 'text' => $kpi['new_customers']['value'] . ' ' . ($kpi['new_customers']['value'] === 1 ? 'neuer Kunde' : 'neue Kunden') . ' heute', 'view' => 'customers'];
    }

    if (!$brief) {
        $brief[] = ['icon' => 'ok', 'text' => 'Heute liegt nichts Dringendes an.', 'view' => ''];
    }
    return array_slice($brief, 0, 7);
}

/**
 * Erkenntnisse statt reiner Zahlen. Jede Aussage nennt die Grundlage,
 * damit sie nachvollziehbar bleibt.
 */
function rh24_cockpit_insights(array $kpi, array $inventory, array $products): array {
    $out = [];

    $rev30 = $kpi['revenue_30'] ?? null;
    if ($rev30 && $rev30['compare']['previous']['delta'] !== null) {
        $d = (float)$rev30['compare']['previous']['delta'];
        $basketDelta = $kpi['basket_30']['compare']['previous']['delta'] ?? null;
        $text = 'Umsatz der letzten 30 Tage ' . ($d >= 0 ? '+' : '') . rtrim(rtrim(number_format($d, 1, ',', '.'), '0'), ',') . ' % gegenüber den 30 Tagen davor.';
        if ($basketDelta !== null) {
            $bd = (float)$basketDelta;
            $text .= ' Der durchschnittliche Warenkorb liegt dabei '
                   . ($bd >= 0 ? '+' : '') . rtrim(rtrim(number_format($bd, 1, ',', '.'), '0'), ',') . ' %.';
        }
        $out[] = ['level' => $d >= 0 ? 'positive' : 'attention', 'text' => $text, 'basis' => 'Grundlage: Bruttosummen aller nicht stornierten Bestellungen beider Zeiträume.'];
    }

    // Artikel mit gutem Verkauf, aber knappem Bestand
    $topIds = [];
    foreach (($products['top_qty'] ?? []) as $p) $topIds[$p['id']] = $p;
    foreach (($inventory['rows'] ?? []) as $row) {
        if (!isset($topIds[$row['id']])) continue;
        if ($row['reach_days'] === null || $row['reach_days'] > 21) continue;
        /* Bei leerem Lager wäre „reicht noch 0 Tage“ eine merkwürdige
           Auskunft – dann ist der Artikel schlicht nicht lieferbar. */
        $text = (int)$row['stock'] <= 0
            ? $row['name'] . ' verkauft sich gut, ist aber ausverkauft. Jeder weitere Verkauf muss warten.'
            : $row['name'] . ' verkauft sich gut, der Bestand reicht bei aktuellem Verbrauch noch etwa '
              . $row['reach_days'] . ' ' . ((int)$row['reach_days'] === 1 ? 'Tag' : 'Tage') . '.';
        $out[] = [
            'level' => (int)$row['stock'] <= 0 ? 'critical' : 'attention',
            'text'  => $text,
            'basis' => 'Grundlage: ' . $row['sold_90'] . ' verkaufte Einheiten in 90 Tagen, aktueller Bestand ' . $row['stock'] . '.',
            'view'  => 'inventory',
        ];
        if (count($out) >= 5) break;
    }

    // Ladenhüter
    $slowTotal = (int)($products['slow_total'] ?? 0);
    if ($slowTotal > 0 && (int)($products['orders'] ?? 0) > 0) {
        $out[] = [
            'level' => 'neutral',
            'text'  => $slowTotal . ' aktive ' . ($slowTotal === 1 ? 'Artikel wurde' : 'Artikel wurden') . ' in den letzten ' . (int)$products['days'] . ' Tagen kein einziges Mal verkauft.',
            'basis' => 'Grundlage: Vergleich des aktiven Sortiments mit allen Bestellpositionen des Zeitraums.',
            'view'  => 'products',
        ];
    }

    // Häufige Kombination
    if (!empty($products['combos'])) {
        $c = $products['combos'][0];
        $out[] = [
            'level' => 'neutral',
            'text'  => $c['a']['name'] . ' und ' . $c['b']['name'] . ' wurden ' . $c['count'] . '-mal gemeinsam bestellt.',
            'basis' => 'Grundlage: gemeinsame Positionen innerhalb derselben Bestellung im gewählten Zeitraum.',
            'view'  => 'products',
        ];
    }

    return array_slice($out, 0, 6);
}

/* =====================================================================
   8 · GESAMTPAKET
   ===================================================================== */
function rh24_cockpit_payload(int $productDays = 30): array {
    $db = rh24_db();
    $r  = rh24_cp_ranges();

    $kpi       = rh24_can('view_orders') || rh24_is_admin() ? rh24_cockpit_kpis($db, $r) : [];
    $attention = rh24_cockpit_attention($db, $r);
    $inventory = rh24_can('view_inventory') ? rh24_cockpit_inventory($db, $r) : ['available' => false, 'rows' => []];
    $products  = rh24_can('view_products') ? rh24_cockpit_products($db, $r, $productDays) : [];
    $finance   = rh24_can('view_finance') || rh24_is_admin() ? rh24_cockpit_finance($db, $r) : null;
    $health    = rh24_is_admin() ? rh24_cockpit_health($db) : null;

    return [
        'generated_at' => date('c'),
        'ranges'       => [
            'today'        => $r['today_label'],
            'weekday'      => $r['weekday_label'],
            'weekday_full' => $r['weekday_full'],
        ],
        'kpi'          => $kpi,
        'attention'    => $attention,
        'inventory'    => $inventory,
        'products'     => $products,
        'finance'      => $finance,
        'health'       => $health,
        'brief'        => $kpi ? rh24_cockpit_brief($kpi, $attention, $inventory) : [],
        'insights'     => $kpi ? rh24_cockpit_insights($kpi, $inventory, $products) : [],
    ];
}
