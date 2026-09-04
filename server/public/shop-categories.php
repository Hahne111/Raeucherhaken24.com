<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

const RH24_DB_CONFIG_FILE = __DIR__ . '/orgaboard/private/db-config.php';

function rh24_shop_db(): PDO
{
    if (!is_file(RH24_DB_CONFIG_FILE)) {
        throw new RuntimeException('Datenbank ist nicht eingerichtet.');
    }
    $cfg = require RH24_DB_CONFIG_FILE;
    if (!is_array($cfg)) {
        throw new RuntimeException('Datenbankkonfiguration ist ungültig.');
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        (string)($cfg['host'] ?? ''),
        (string)($cfg['database'] ?? ''),
        (string)($cfg['charset'] ?? 'utf8mb4')
    );

    return new PDO($dsn, (string)($cfg['user'] ?? ''), (string)($cfg['password'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function rh24_ensure_shop_categories(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS shop_categories (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        slug VARCHAR(80) NOT NULL,
        name VARCHAR(120) NOT NULL,
        url VARCHAR(190) NOT NULL,
        sort_order INT NOT NULL DEFAULT 100,
        is_visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_shop_categories_slug (slug),
        KEY idx_shop_categories_order (is_visible, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // INSERT IGNORE ist absichtlich idempotent: spätere manuelle Sortier-/Sichtbarkeitsänderungen
    // in shop_categories werden bei weiteren API-Aufrufen nicht überschrieben.
    $seed = [
        ['raeucherhaken',   'Räucherhaken',   'raeucherhaken.html',  10, 1],
        ['raeucherlauge',   'Räucherlauge',   'raeucherlaugen.html', 20, 1],
        ['raeuchermehl',    'Räuchermehl',    'raeuchermehl.html',   30, 1],
        ['fleischerhaken',  'Fleischerhaken', 'fleischerhaken.html', 40, 1],
        ['fischgewuerze',   'Fischgewürze',   'fischgewuerze.html',  50, 1],
        ['naturgewuerze',   'Naturgewürze',   'naturgewuerze.html',  60, 1],
        ['wissen',          'Wissen',          'wissen.html',         70, 1],
        ['ueber-uns',       'Über uns',        'ueber-uns.html',      80, 1],
        // Ehemalige Menügruppen bleiben als deaktivierte Datensätze erhalten.
        // Die URLs zeigen bewusst auf vorhandene Seiten, damit eine spätere Aktivierung nicht zu 404 führt.
        ['raeucherholz',    'Räucherholz',     'raeuchermehl.html',   90, 0],
        ['sets',            'Sets',             'raeucherhaken.html', 100, 0],
        ['zubehoer',        'Zubehör',          'shop.html',           110, 0],
    ];

    $insert = $db->prepare(
        'INSERT IGNORE INTO shop_categories (slug,name,url,sort_order,is_visible) VALUES (?,?,?,?,?)'
    );
    foreach ($seed as $row) {
        $insert->execute($row);
    }
}

function rh24_shop_categories(PDO $db): array
{
    $sql = "SELECT id, slug, name, url, sort_order
            FROM shop_categories
            WHERE is_visible = 1
            ORDER BY sort_order ASC, id ASC";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $db = rh24_shop_db();
    try {
        $db->query('SELECT 1 FROM shop_categories LIMIT 1');
    } catch (PDOException $e) {
        if ((string)$e->getCode() !== '42S02') {
            throw $e;
        }
        // Einmalige, idempotente Migration beim ersten Aufruf nach dem Update.
        rh24_ensure_shop_categories($db);
    }
    echo json_encode([
        'ok' => true,
        'categories' => rh24_shop_categories($db),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('RH24 shop_categories API: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'categories' => [],
        'error' => 'Kategorien konnten nicht geladen werden.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
