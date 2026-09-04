<?php
declare(strict_types=1);

/*
 * Liefert ausschließlich die vom Betreiber freigegebenen offiziellen
 * AVO-Produktbilder der acht BIO-Naturgewürze aus. Keine freie URL-
 * Weiterleitung: Artikelnummer und Zielseite sind fest whitelisted.
 */

$products = [
    '795800' => ['slug'=>'bio-ingwer-gemahlen',       'direct'=>'https://www.avo.de/avo-mv/public/sortiment/picture/795800_bio-ingwer-gemahlen%28862%29.jpg'],
    '795500' => ['slug'=>'bio-knoblauchpulver',       'direct'=>'https://www.avo.de/avo-mv/public/sortiment/picture/795500_bio-knoblauchpulver%28841%29.jpg'],
    '796300' => ['slug'=>'bio-koriander-gemahlen',    'direct'=>''],
    '795900' => ['slug'=>'bio-kuemmel-gemahlen',      'direct'=>'https://www.avo.de/avo-mv/public/sortiment/picture/795900_bio-kuemmel-gemahlen%281278%29.jpg'],
    '795400' => ['slug'=>'bio-majoran-gerebelt',      'direct'=>''],
    '795200' => ['slug'=>'bio-muskatnuss-gemahlen',   'direct'=>''],
    '795700' => ['slug'=>'bio-paprika-rot-gemahlen',  'direct'=>''],
    '795100' => ['slug'=>'bio-pfeffer-schwarz-gemahlen','direct'=>''],
];

$article = trim((string)($_GET['article'] ?? ''));
if (!isset($products[$article])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bild nicht gefunden';
    exit;
}

$cacheDir = __DIR__ . '/uploads/products/avo';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

function rh24_avo_image_mime(string $bytes): string {
    try {
        $fi = new finfo(FILEINFO_MIME_TYPE);
        return strtolower((string)$fi->buffer($bytes));
    } catch (Throwable $e) { return ''; }
}
function rh24_avo_ext(string $mime): string {
    return [
        'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',
        'image/avif'=>'avif','image/gif'=>'gif'
    ][$mime] ?? '';
}
function rh24_avo_get(string $url, ?string &$contentType=null): string|false {
    $contentType = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_MAXREDIRS=>4,
            CURLOPT_CONNECTTIMEOUT=>8,
            CURLOPT_TIMEOUT=>18,
            CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; Raeucherhaken24/1.0; +https://www.raeucherhaken24.com)',
            CURLOPT_HTTPHEADER=>[
                'Accept: text/html,image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Accept-Language: de-DE,de;q=0.9,en;q=0.6',
            ],
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $contentType = (string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '');
        curl_close($ch);
        if ($body !== false && $status >= 200 && $status < 400) return (string)$body;
    }
    if ((bool)ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http'=>[
            'timeout'=>18,
            'follow_location'=>1,
            'max_redirects'=>4,
            'header'=>"User-Agent: Mozilla/5.0 (compatible; Raeucherhaken24/1.0)\r\nAccept-Language: de-DE,de;q=0.9\r\n"
        ]]);
        $body = @file_get_contents($url, false, $ctx);
        if (is_string($body)) return $body;
    }
    return false;
}
function rh24_avo_serve(string $file): never {
    $bytes = @file_get_contents($file);
    if (!is_string($bytes) || $bytes === '') {
        http_response_code(404); exit;
    }
    $mime = rh24_avo_image_mime($bytes);
    if (rh24_avo_ext($mime) === '') {
        http_response_code(415); exit;
    }
    header('Content-Type: '.$mime);
    header('Content-Length: '.strlen($bytes));
    header('Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
}

foreach (['jpg','jpeg','png','webp','avif','gif'] as $ext) {
    $cached = $cacheDir.'/'.$article.'.'.$ext;
    if (is_file($cached) && filesize($cached) > 100) rh24_avo_serve($cached);
}

$cfg = $products[$article];
$imageUrl = trim((string)$cfg['direct']);

if ($imageUrl === '') {
    $detailUrl = 'https://www.avo.de/sortiment/produkte/details/' . rawurlencode((string)$cfg['slug']);
    $htmlType = null;
    $html = rh24_avo_get($detailUrl, $htmlType);
    if (!is_string($html) || $html === '') {
        http_response_code(502);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'AVO-Produktbild ist vorübergehend nicht erreichbar.';
        exit;
    }

    $articlePattern = preg_quote($article, '~');
    $pattern = '~(?:https?:)?//www\.avo\.de/avo-mv/public/sortiment/picture/'.$articlePattern.'_[^"\'<>\s]+|/avo-mv/public/sortiment/picture/'.$articlePattern.'_[^"\'<>\s]+~i';
    if (!preg_match($pattern, $html, $m)) {
        http_response_code(502);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Offizielles AVO-Produktbild konnte nicht aufgelöst werden.';
        exit;
    }
    $imageUrl = html_entity_decode((string)$m[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (str_starts_with($imageUrl, '//')) $imageUrl = 'https:' . $imageUrl;
    elseif (str_starts_with($imageUrl, '/')) $imageUrl = 'https://www.avo.de' . $imageUrl;
}

if (!preg_match('~^https://www\.avo\.de/avo-mv/public/sortiment/picture/'.$article.'_~i', $imageUrl)) {
    http_response_code(403);
    exit;
}

$imageType = null;
$bytes = rh24_avo_get($imageUrl, $imageType);
if (!is_string($bytes) || strlen($bytes) < 100 || strlen($bytes) > 12 * 1024 * 1024) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'AVO-Produktbild konnte nicht geladen werden.';
    exit;
}

$mime = rh24_avo_image_mime($bytes);
$ext = rh24_avo_ext($mime);
if ($ext === '') {
    http_response_code(415);
    exit;
}

$target = $cacheDir.'/'.$article.'.'.$ext;
if (is_dir($cacheDir) && is_writable($cacheDir)) {
    $tmp = $target.'.tmp-'.bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, $bytes, LOCK_EX) !== false) {
        @rename($tmp, $target);
        @chmod($target, 0644);
    }
    if (is_file($tmp)) @unlink($tmp);
}

header('Content-Type: '.$mime);
header('Content-Length: '.strlen($bytes));
header('Cache-Control: public, max-age=86400, stale-while-revalidate=604800');
header('X-Content-Type-Options: nosniff');
echo $bytes;
