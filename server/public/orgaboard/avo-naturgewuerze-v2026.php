<?php
declare(strict_types=1);

/*
 * Räucherhaken24 · AVO Naturgewürze · V2026.6
 *
 * Ziel: ausschließlich die acht bereits vorhandenen BIO-Naturgewürze
 * 13008–13015 mit den vom Betreiber beauftragten Verkaufsdaten und den
 * offiziellen AVO-Produktbildern verbinden.
 *
 * Wichtig: Der Lagerbestand wird je Artikel genau EINMAL auf 100 gesetzt.
 * Danach darf der normale Warenfluss den Bestand verändern; ein späterer
 * Shop-Aufruf setzt ihn nicht wieder zurück.
 */

function rh24_avo_naturgewuerze_v2026_map(): array {
    return [
        'natur-13008' => ['avo'=>'795800','price'=>2.99,'slug'=>'bio-ingwer-gemahlen'],
        'natur-13009' => ['avo'=>'795500','price'=>2.99,'slug'=>'bio-knoblauchpulver'],
        'natur-13010' => ['avo'=>'796300','price'=>2.99,'slug'=>'bio-koriander-gemahlen'],
        'natur-13011' => ['avo'=>'795900','price'=>2.99,'slug'=>'bio-kuemmel-gemahlen'],
        'natur-13012' => ['avo'=>'795400','price'=>2.99,'slug'=>'bio-majoran-gerebelt'],
        'natur-13013' => ['avo'=>'795200','price'=>4.49,'slug'=>'bio-muskatnuss-gemahlen'],
        'natur-13014' => ['avo'=>'795700','price'=>2.99,'slug'=>'bio-paprika-rot-gemahlen'],
        'natur-13015' => ['avo'=>'795100','price'=>3.49,'slug'=>'bio-pfeffer-schwarz-gemahlen'],
    ];
}

function rh24_apply_avo_naturgewuerze_v2026(PDO $db): void {
    static $ran = false;
    if ($ran) return;
    $ran = true;

    $hasPublishedAt = false;
    try {
        $q = $db->query("SHOW COLUMNS FROM products LIKE 'published_at'");
        $hasPublishedAt = (bool)$q->fetch();
    } catch (Throwable $e) {}

    $select = $db->prepare("SELECT id,name,category FROM products WHERE id=? LIMIT 1");
    $update = $db->prepare("UPDATE products
        SET base_price=?, unit='30 g', product_weight_g=30,
            image_path=?, status='active', shop_visible=1, updated_at=NOW()
        WHERE id=? AND category='Naturgewürze'");
    $inventory = $db->prepare("INSERT INTO inventory(id,name,stock,minimum,unit,updated_at)
        VALUES(?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE name=VALUES(name),stock=VALUES(stock),minimum=VALUES(minimum),unit=VALUES(unit),updated_at=NOW()");
    $publish = $hasPublishedAt
        ? $db->prepare("UPDATE products SET published_at=COALESCE(published_at,NOW()) WHERE id=? AND category='Naturgewürze'")
        : null;
    $mark = $db->prepare("INSERT INTO settings(setting_key,setting_value,updated_at) VALUES(?,?,NOW())
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()");
    $checkMark = $db->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");

    foreach (rh24_avo_naturgewuerze_v2026_map() as $id => $cfg) {
        $settingKey = 'avo_naturgewuerz_v2026_6_' . $id;
        try {
            $checkMark->execute([$settingKey]);
            if ((string)($checkMark->fetchColumn() ?: '') === '1') continue;

            $select->execute([$id]);
            $row = $select->fetch();
            if (!$row || (string)$row['category'] !== 'Naturgewürze') continue;

            $db->beginTransaction();
            $imagePath = '/avo-product-image.php?article=' . rawurlencode((string)$cfg['avo']);
            $update->execute([(float)$cfg['price'], $imagePath, $id]);
            $inventory->execute([$id, (string)$row['name'], 100, 10, '30 g']);
            if ($publish) $publish->execute([$id]);
            $mark->execute([$settingKey, '1']);
            $db->commit();

            try {
                rh24_audit('avo_naturgewuerz_sync','product',$id,[
                    'avo_article'=>(string)$cfg['avo'],
                    'unit'=>'30 g',
                    'price'=>(float)$cfg['price'],
                    'stock'=>100,
                    'minimum'=>10,
                    'image'=>$imagePath,
                ],'system');
            } catch (Throwable $e) {}
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('RH24 AVO Naturgewürz Sync '.$id.': '.$e->getMessage());
        }
    }
}
