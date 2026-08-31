<?php
declare(strict_types=1);
require __DIR__.'/marketplace-bootstrap.php';
$token=trim((string)($_GET['t']??''));$ok=false;$serviceError=false;$msg='Der Bestätigungslink ist ungültig oder abgelaufen.';
try {
    $db=market_db();
    if($token!==''){
        $hash=hash('sha256',$token);
        $q=$db->prepare("SELECT * FROM market_verification_tokens WHERE token_hash=? AND purpose='verify' AND used_at IS NULL AND expires_at>=NOW() LIMIT 1");
        $q->execute([$hash]);$r=$q->fetch();
        if($r){
            $db->beginTransaction();
            try {
                $db->prepare('UPDATE market_verification_tokens SET used_at=NOW() WHERE id=?')->execute([$r['id']]);
                $db->prepare('UPDATE market_users SET email_verified_at=COALESCE(email_verified_at,NOW()),updated_at=NOW() WHERE id=?')->execute([$r['user_id']]);
                $db->commit();
                $ok=true;$msg='Ihre E-Mail-Adresse ist bestätigt. Nach Zahlungseingang wird der Jahreszugang für 365 Tage freigeschaltet.';
            } catch(Throwable $e) {
                if($db->inTransaction()) $db->rollBack();
                throw $e;
            }
        }
    }
} catch(Throwable $e) {
    $serviceError=true;
    http_response_code(503);
    $msg='Die Bestätigung kann im Moment technisch nicht verarbeitet werden. Bitte versuchen Sie es später erneut.';
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>E-Mail bestätigen | Räucherhaken24</title><link rel="stylesheet" href="/src/styles/style-v13.css"><link rel="stylesheet" href="/src/styles/marketplace-v60.css"><script vite-ignore src="/src/scripts/core/boot.js"></script><link rel="stylesheet" href="/src/styles/site-base-v104.css?v=104.0"><link rel="stylesheet" href="/src/styles/light-base-v104.css?v=104.0"><link rel="stylesheet" href="/src/styles/premium-v104.css?v=104.0"><link rel="stylesheet" href="/src/styles/ui-icons-v1076.css?v=107.6"></head><body><main class="marketStandalone"><section class="marketAuthCard"><div class="marketMark">AN- & VERKAUFEN</div><h1><?= $ok?'E-Mail bestätigt':($serviceError?'Dienst vorübergehend nicht verfügbar':'Bestätigung nicht möglich') ?></h1><p><?= htmlspecialchars($msg) ?></p><a class="marketPrimary" href="ankauf-verkauf.php">Zum Marktplatz</a></section></main><style data-rh701-hd-credit-style>.rh701HdBand{box-sizing:border-box;width:100%;border-top:1px solid rgba(255,255,255,.10);background:#10171b;padding:16px 12px;color:#eef5f7}.rh701HdInner{max-width:1180px;margin:0 auto;text-align:center}.rh701HdLink{display:inline-flex;align-items:center;gap:10px;min-height:48px;padding:7px 12px;border:1px solid rgba(118,231,239,.24);border-radius:14px;color:#eef5f7!important;text-decoration:none!important;background:rgba(255,255,255,.025);font-family:Arial,Helvetica,sans-serif}.rh701HdPowered{font-size:9px;letter-spacing:.17em;font-weight:800;color:#93a3aa}.rh701HdMark{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #76e6ee;border-radius:10px;font-weight:900;color:#fff}.rh701HdMark i{font-style:normal;color:#76e6ee}.rh701HdName{font-size:12px;font-weight:900;letter-spacing:.16em;white-space:nowrap}.rh701HdName i{font-style:normal;color:#76e6ee;margin-left:7px}@media(max-width:520px){.rh701HdPowered{display:none}.rh701HdName{font-size:10px;letter-spacing:.10em}}</style><div class="rh701HdBand" data-rh701-hd-credit><div class="rh701HdInner"><a class="rh701HdLink" href="powered-by-click.php?go=1&amp;page=marktplatz-verifizieren.php" target="_blank" rel="noopener noreferrer" aria-label="Powered by HD Hahne Digital – Webseite öffnen"><span class="rh701HdPowered">POWERED BY</span><span class="rh701HdMark" aria-hidden="true">H<i>D</i></span><span class="rh701HdName">HAHNE <i>DIGITAL</i></span><span aria-hidden="true">↗</span></a></div></div><script vite-ignore src="/src/scripts/site-v104.js?v=104.0"></script><script vite-ignore src="/src/scripts/runtime-v104.js?v=104.0"></script><script vite-ignore src="/src/scripts/ui-icons-v1076.js?v=107.6"></script></body></html>
