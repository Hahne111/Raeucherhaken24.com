<?php
/* =====================================================================
   ORGABOARD · ANMELDE-BREMSE   (login-throttle.php)
   ---------------------------------------------------------------------
   Bisher liess sich das Anmeldeformular beliebig oft abschicken. Damit
   ist ein automatisiertes Durchprobieren von Passwörtern möglich.

   Diese Bremse zählt fehlgeschlagene Versuche je Benutzername und je
   IP-Adresse und sperrt danach für eine kurze Zeit. Sie arbeitet mit
   einer einfachen Datei im geschützten Ordner private/ und braucht
   weder Datenbank noch zusätzliche Servertechnik – sie funktioniert
   deshalb auch dann, wenn die Datenbank gerade nicht erreichbar ist.
   ===================================================================== */
declare(strict_types=1);

const RH24_LOGIN_THROTTLE_FILE    = __DIR__ . '/private/login-throttle.json';
const RH24_LOGIN_THROTTLE_WINDOW  = 900;  // 15 Minuten Beobachtungszeitraum
const RH24_LOGIN_THROTTLE_MAX     = 8;    // erlaubte Fehlversuche
const RH24_LOGIN_THROTTLE_LOCK    = 600;  // 10 Minuten Sperre

function rh24_login_client_ip(): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip !== '' ? $ip : 'unbekannt';
}

function rh24_login_throttle_keys(string $username): array {
    $user = strtolower(trim($username));
    return [
        'ip:'   . hash('sha256', rh24_login_client_ip()),
        'user:' . hash('sha256', $user . '|' . rh24_login_client_ip()),
    ];
}

function rh24_login_throttle_read(): array {
    if (!is_file(RH24_LOGIN_THROTTLE_FILE)) return [];
    $raw = @file_get_contents(RH24_LOGIN_THROTTLE_FILE);
    if (!is_string($raw) || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function rh24_login_throttle_write(array $data): void {
    $dir = dirname(RH24_LOGIN_THROTTLE_FILE);
    if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
    if (!is_dir($dir)) return;
    // Alte Einträge entfernen, damit die Datei nicht wächst.
    $now = time();
    foreach ($data as $k => $entry) {
        $last = (int)($entry['last'] ?? 0);
        if ($now - $last > RH24_LOGIN_THROTTLE_WINDOW * 4) unset($data[$k]);
    }
    @file_put_contents(
        RH24_LOGIN_THROTTLE_FILE,
        json_encode($data, JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

/**
 * Gibt die verbleibende Sperrzeit in Sekunden zurück (0 = nicht gesperrt).
 */
function rh24_login_throttle_locked(string $username): int {
    $data = rh24_login_throttle_read();
    $now  = time();
    $wait = 0;
    foreach (rh24_login_throttle_keys($username) as $key) {
        $e = $data[$key] ?? null;
        if (!is_array($e)) continue;
        $until = (int)($e['until'] ?? 0);
        if ($until > $now) $wait = max($wait, $until - $now);
    }
    return $wait;
}

function rh24_login_throttle_fail(string $username): void {
    $data = rh24_login_throttle_read();
    $now  = time();
    foreach (rh24_login_throttle_keys($username) as $key) {
        $e = $data[$key] ?? ['count' => 0, 'last' => $now, 'until' => 0];
        if ($now - (int)($e['last'] ?? 0) > RH24_LOGIN_THROTTLE_WINDOW) {
            $e['count'] = 0;
        }
        $e['count'] = (int)($e['count'] ?? 0) + 1;
        $e['last']  = $now;
        if ($e['count'] >= RH24_LOGIN_THROTTLE_MAX) {
            $e['until'] = $now + RH24_LOGIN_THROTTLE_LOCK;
            $e['count'] = 0;
        }
        $data[$key] = $e;
    }
    rh24_login_throttle_write($data);
}

function rh24_login_throttle_reset(string $username): void {
    $data = rh24_login_throttle_read();
    foreach (rh24_login_throttle_keys($username) as $key) {
        unset($data[$key]);
    }
    rh24_login_throttle_write($data);
}

function rh24_login_throttle_message(int $seconds): string {
    $minutes = (int)ceil($seconds / 60);
    return 'Zu viele fehlgeschlagene Anmeldeversuche. Bitte in etwa '
        . $minutes . ' ' . ($minutes === 1 ? 'Minute' : 'Minuten') . ' erneut versuchen.';
}
