<?php
// Räucherhaken24 V104 cleanup: removes only storefront runtime files superseded by V104.
$token = 'pNX2oO2pVG0glC_e0opS5IA9RGk';
if (!isset($_GET['token']) || !hash_equals($token, (string)$_GET['token'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("403 – ungültiger Cleanup-Token\n");
}
header('Content-Type: text/plain; charset=utf-8');
$files = [
    'site-v82.js',
    'site-v82.css',
    'light-pro-v102.css',
    'runtime-v1022.js',
    'cleanup-v103.php',
    'CLEANUP-TOKEN-V103.txt'
];
$base = __DIR__;
$deleted = 0; $missing = 0; $failed = 0;
foreach ($files as $rel) {
    $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (is_file($path)) {
        if (@unlink($path)) { echo "GELÖSCHT: $rel\n"; $deleted++; }
        else { echo "FEHLER: $rel\n"; $failed++; }
    } else { echo "NICHT VORHANDEN: $rel\n"; $missing++; }
}
echo "\nV104 Cleanup fertig. Gelöscht: $deleted · nicht vorhanden: $missing · Fehler: $failed\n";
if ($failed===0 && @unlink(__FILE__)) echo "Cleanup-Datei hat sich selbst gelöscht.\n";
else echo "HINWEIS: cleanup-v104.php nach erfolgreicher Prüfung manuell löschen.\n";
?>
