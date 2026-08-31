<?php
declare(strict_types=1);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require __DIR__ . '/bootstrap.php';

$error = '';
$success = (($_GET['password'] ?? '') === 'changed') ? 'Passwort erfolgreich gespeichert. Sie können sich jetzt anmelden.' : '';
$action = $_POST['action'] ?? '';

if (!rh24_db_configured() || !rh24_is_configured()) {
    header('Location: setup.php');
    exit;
}

if ($action === 'login') {
    $username = (string)($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    // V2026.2: Anmeldebremse gegen automatisiertes Durchprobieren.
    require_once __DIR__ . '/login-throttle.php';
    $lockedFor = rh24_login_throttle_locked($username);
    if ($lockedFor > 0) {
        $error = rh24_login_throttle_message($lockedFor);
        $user  = null;
    } else {
        $user = rh24_login_user($username,$password);
    }
    if ($user) {
        rh24_login_throttle_reset($username);
        session_regenerate_id(true);
        $_SESSION['rh24_user'] = $user;
        rh24_csrf();
        rh24_audit('user_login','session',session_id(),['role'=>$user['role']],(string)$user['display_name']);
        header('Location: index.php');
        exit;
    }
    if ($error === '') {
        rh24_login_throttle_fail($username);
        rh24_audit('user_login_failed','session',substr(hash('sha256',strtolower(trim($username))),0,16),[],'System');
        $error = 'Anmeldung fehlgeschlagen. Benutzername oder Passwort ist falsch.';
    }
}

if ($action === 'logout') {
    $u=rh24_current_user();
    rh24_audit('user_logout','session',session_id(),[],(string)($u['display_name']??'Benutzer'));
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

$configured = rh24_is_configured();
$loggedIn = rh24_is_logged_in();
$currentUser = $loggedIn ? rh24_current_user() : [];
$isAdmin = $loggedIn && rh24_is_admin();
$csrf = $loggedIn ? rh24_csrf() : '';
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#f6f3ef">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="RH24 OrgaBoard">
<link rel="manifest" href="manifest.webmanifest">
<link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
<meta name="robots" content="noindex,nofollow">
<title>Orgaboard | Räucherhaken24</title>
<link rel="stylesheet" href="assets/admin.css?v=106.9">
<link rel="stylesheet" href="assets/earnings-v97.css?v=97.0">
<link rel="stylesheet" href="assets/vehicle-v98.css?v=99.1">
<link rel="stylesheet" href="assets/trip-receipts-v99.css?v=99.0">
<link rel="stylesheet" href="assets/appointments-v92.css?v=92.0">
<link rel="stylesheet" href="assets/finance-v91.css?v=91.1">
<link rel="stylesheet" href="assets/pos-v95.css?v=95.0">
<link rel="stylesheet" href="assets/labels-v966.css?v=105.4">
<link rel="stylesheet" href="assets/product-builder-v83.css?v=87">
<link rel="stylesheet" href="assets/product-ai-v1062.css?v=106.9">
<link rel="stylesheet" href="assets/light-pro-v102.css?v=103.0">
<link rel="stylesheet" href="assets/labels-v967.css?v=105.4">
<link rel="stylesheet" href="assets/labels-v1053.css?v=105.4">
<!-- V2026.2: Premium-Designschicht. Bewusst als letztes Stylesheet,
     damit sie sich über die gewachsene Gestaltung legt. -->
<link rel="stylesheet" href="assets/orgaboard-premium-2026.css?v=2026.2">
<!-- V2026.3: Gemeinsames Designsystem von Shop und OrgaBoard.
     Enthält ausschliesslich Variablen und legt damit die Marken-
     sprache fest. Die Atelier-Schicht danach setzt sie um und ist
     bewusst das allerletzte Stylesheet. -->
<link rel="stylesheet" href="../rh-design-system.css?v=2026.3">
<link rel="stylesheet" href="assets/orgaboard-atelier-2026.css?v=2026.4">
<script>document.documentElement.classList.add('ob-atelier')</script>
</head>
<body class="<?= $loggedIn ? 'ob-app' : 'ob-auth' ?>">
<?php if (!$configured): ?>
<main class="authShell">
  <section class="authCard">
    <div class="authBrand"><span>RÄUCHERHAKEN</span><strong>24</strong></div>
    <div class="authKicker">ORGABOARD · ERSTEINRICHTUNG</div>
    <h1>Verwaltung sicher einrichten</h1>
    <p>Lege beim ersten Aufruf dein persönliches Admin-Passwort fest. Es wird ausschließlich als sicherer Passwort-Hash gespeichert.</p>
    <?php if ($error): ?><div class="authError"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="authForm" autocomplete="off">
      <input type="hidden" name="action" value="setup">
      <label>Admin-Passwort<input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
      <label>Passwort wiederholen<input type="password" name="password_repeat" minlength="10" required autocomplete="new-password"></label>
      <button type="submit">Orgaboard einrichten</button>
    </form>
    <small>Empfehlung: mindestens 14 Zeichen, einzigartig und nicht identisch mit dem Shop-Testkennwort.</small>
  </section>
</main>
<?php elseif (!$loggedIn): ?>
<main class="authShell">
  <section class="authCard compact">
    <div class="authBrand"><span>RÄUCHERHAKEN</span><strong>24</strong></div>
    <div class="authKicker">ORGABOARD · V102.1</div>
    <h1>Anmeldung</h1>
    <p>Geschützter Verwaltungsbereich mit rollenbasierter Rechtevergabe für Administration, Produktion und Kundenberatung.</p>
    <?php if ($error): ?><div class="authError"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="authSuccess"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <form method="post" class="authForm">
      <input type="hidden" name="action" value="login">
      <label>Benutzername<input type="text" name="username" required autocomplete="username" autofocus></label>
      <label>Passwort<input type="password" name="password" required autocomplete="current-password"></label>
      <button type="submit">Anmelden</button>
    </form>
    <a href="passwort.php" class="forgotLink">Passwort vergessen? Schnell per E-Mail neu festlegen</a>
    <p class="authHint">Administratoren: <b>bjoern.hahne</b> und <b>jessica.hahne</b>. Beide verwenden beim ersten Login das bisherige Orgaboard-Passwort und sollten anschließend ein eigenes Passwort setzen.</p>
    <a href="../index.html" class="backShop">← Zur Website</a>
  </section>
</main>
<?php else: ?>
<div class="appFrame" data-csrf="<?= htmlspecialchars($csrf) ?>">
  <aside class="sideNav" id="sideNav">
    <div class="sideBrand">
      <div class="sideLogo"><span>RÄUCHERHAKEN</span><strong>24</strong></div>
      <small>ORGABOARD</small>
    </div>
    <nav>
      <div class="navSection navSectionStart">
        <div class="navSectionLabel"><span>ÜBERSICHT</span></div>
        <button class="navItem active" data-view="dashboard"><span>⌂</span><b>Chef-Dashboard</b></button>
      </div>

      <div class="navSection">
        <div class="navSectionLabel"><span>KUNDEN & CRM</span></div>
        <button class="navItem" data-view="customers"><span>♙</span><b>Kunden & CRM</b></button>
        <button class="navItem" data-view="appointments" data-perm="view_appointments"><span>◫</span><b>Termine</b></button>
        <button class="navItem" data-view="messages" data-perm="view_messages"><span>✉</span><b>Nachrichten</b><em id="navMessageBadge">0</em></button>
        <button class="navItem" data-view="sales"><span>☎</span><b>Produktberatung</b></button>
        <button class="navItem" data-view="territorybook" data-perm="view_territory_book"><span>⌖</span><b><?= $isAdmin ? 'Gebietsbücher' : 'Mein Gebietsbuch' ?></b></button>
      </div>

      <div class="navSection">
        <div class="navSectionLabel"><span>VERKAUF & ZAHLUNG</span></div>
        <button class="navItem" data-view="orders"><span>▣</span><b>Bestellungen</b><em id="navOrderBadge">0</em></button>
        <button class="navItem posNavItem" data-view="pos" data-perm="view_pos"><span>▰</span><b>Kasse & POS</b><em>V95</em></button>
<?php if ($isAdmin): ?>
        <button class="navItem paymentNavItem" data-view="payments"><span>€</span><b>Zahlungsarten & Schnittstellen</b><em>NEU</em></button>
<?php endif; ?>
        <button class="navItem" data-view="documents"><span>▧</span><b>Rechnungen & Lieferscheine</b></button>
        <button class="navItem" data-view="shipping"><span>➜</span><b>Versand & Tracking</b></button>
      </div>

<?php if ($isAdmin || rh24_can('view_finance')): ?>
      <div class="navSection financeNavSection">
        <div class="navSectionLabel"><span>FINANZEN & BUCHHALTUNG</span></div>
        <button class="navItem financeNavItem" data-view="finance" data-perm="view_finance"><span>€</span><b>Finanz-Cockpit</b><em>V91.1</em></button>
      </div>
<?php endif; ?>

      <div class="navSection">
        <div class="navSectionLabel"><span>PRODUKTION & LAGER</span></div>
        <button class="navItem" data-view="production"><span>⇢</span><b>Produktion</b></button>
        <button class="navItem" data-view="prototypes"><span>◇</span><b>Prototypen</b><em id="navProtoBadge">0</em></button>
        <button class="navItem" data-view="inventory"><span>▤</span><b>Lagerraum & Logistik</b><em id="navStockBadge">0</em></button>
<?php if ($isAdmin): ?>
        <button class="navItem" data-view="production_staff"><span>♟</span><b>Produktionsteam</b></button>
<?php endif; ?>
      </div>

      <div class="navSection">
        <div class="navSectionLabel"><span>PRODUKTE & SORTIMENT</span></div>
        <button class="navItem" data-view="products"><span>▦</span><b>Produktzentrale</b></button>
        <button class="navItem labelNavItem" data-view="labels" data-perm="view_labels"><span>▤</span><b>Etikettenstudio</b><em>V105.4</em></button>
        <button class="navItem navItemBuilder" data-view="productbuilder" data-perm="view_products"><span>＋</span><b>Produkt-Baukasten</b></button>
        <button class="navItem" data-view="nature_spices" data-perm="view_products"><span>✿</span><b>Naturgewürze</b></button>
<?php if ($isAdmin): ?>
        <button class="navItem" data-view="product_analysis"><span>↗</span><b>Produkt-Analyse</b></button>
        <button class="navItem" data-view="calculator"><span>∑</span><b>Produkt-Kalkulator</b></button>
<?php endif; ?>
      </div>

      <div class="navSection">
        <div class="navSectionLabel"><span>VERTRIEB & GEBIETE</span></div>
        <button class="navItem" data-view="salescalendar" data-perm="view_sales_calendar"><span>▦</span><b>Vertriebskalender</b></button>
        <button class="navItem" data-view="triplog" data-perm="view_triplog"><span>⌁</span><b>Fahrtenbuch & Belege</b><em>V99.1.1</em></button>
<?php if (!$isAdmin): ?>
        <button class="navItem" data-view="mystats" data-perm="view_own_stats"><span>↗</span><b>Meine Provision</b></button>
<?php endif; ?>
        <button class="navItem" data-view="earnings" data-perm="view_earnings_calculator"><span>€</span><b><?= $isAdmin ? 'Verdienstrechner' : 'Mein Verdienst' ?></b></button>
        <button class="navItem" data-view="leaderboard" data-perm="view_leaderboard"><span>★</span><b>Rangliste & Sterne</b></button>
<?php if ($isAdmin): ?>
        <button class="navItem" data-view="salesreps"><span>⇄</span><b>Kundenberater</b></button>
        <button class="navItem" data-view="territories"><span>⌖</span><b>Festgebiete Deutschland</b></button>
        <button class="navItem" data-view="dealers"><span>♜</span><b>Händler</b></button>
<?php endif; ?>
      </div>

<?php if ($isAdmin): ?>
      <div class="navSection navSectionAdmin">
        <div class="navSectionLabel"><span>SHOP & MARKETING</span></div>
        <button class="navItem" data-view="marketplace"><span>♢</span><b>An- & Verkaufen</b><em id="navMarketBadge">0</em></button>
        <button class="navItem" data-view="newsletter"><span>✉</span><b>Newsletter</b></button>
        <button class="navItem" data-view="reviews"><span>★</span><b>Bewertungen</b></button>
        <button class="navItem" data-view="content"><span>✎</span><b>Rezepte & Inhalte</b></button>
        <button class="navItem" data-view="ai"><span>✦</span><b>Smoky / KI</b></button>
        <button class="navItem" data-view="shopdesign"><span>◐</span><b>Shop-Design</b></button>
      </div>

      <div class="navSection navSectionAdmin">
        <div class="navSectionLabel"><span>SYSTEM</span></div>
        <button class="navItem" data-view="administration"><span>◆</span><b>Administration</b></button>
        <button class="navItem" data-view="rights"><span>☷</span><b>Zugriffsrechte</b></button>
        <button class="navItem" data-view="settings"><span>⚙</span><b>Einstellungen</b></button>
        <button class="navItem" data-view="calculator_help"><span>?</span><b>Hilfecenter</b></button>
      </div>
<?php endif; ?>

      <div class="navSection">
        <div class="navSectionLabel"><span>MEIN KONTO</span></div>
        <button class="navItem" data-view="profile"><span>●</span><b>Mein Zugang</b></button>
        <button class="navItem" data-view="mobileapp"><span>▣</span><b>Handy-App</b></button>
      </div>
    </nav>
    <div class="sideFoot">
      <a href="../index.html" target="_blank" rel="noopener">Website öffnen ↗</a>
      <form method="post"><input type="hidden" name="action" value="logout"><button type="submit">Abmelden</button></form>
    </div>
  </aside>

  <main class="mainArea">
    <header class="topBar">
      <button class="mobileMenu" id="mobileMenu">☰</button>
      <div>
        <small id="viewKicker">ZENTRALE SHOPVERWALTUNG</small>
        <h1 id="viewTitle">Dashboard</h1>
      </div>
      <div class="topActions"><span class="serverBuildBadge" title="Aktive Orgaboard-Version">V102.1</span>
        <label class="globalSearch"><span>⌕</span><input id="globalSearch" type="search" placeholder="Bestellung, Kunde, E-Mail …"></label>
        <button class="iconBtn" id="refreshBtn" title="Aktualisieren">↻</button>
        <div class="adminChip"><i></i><span><?= htmlspecialchars((string)($currentUser['display_name']??'Benutzer')) ?></span><small><?= $isAdmin ? 'Administrator' : (($currentUser['role']??'')==='production' ? 'Produktion · Fertigungszugang' : (($currentUser['role']??'')==='cashier' ? 'Kasse · POS-Zugang' : 'Kundenberater · Vertriebszugang')) ?></small></div>
      </div>
    </header>
    <section class="coreQuickBar" aria-label="Schnellzugriff Arbeitsbereiche">
      <div class="coreQuickIntro"><small>ARBEITSBEREICH</small><b>Schnell wechseln</b></div>
      <div class="coreQuickActions">
        <button type="button" class="quickViewBtn" data-quick-view="customers" data-perm="view_customers"><span>♙</span><b>Kunden</b></button>
        <button type="button" class="quickViewBtn" data-quick-view="appointments" data-perm="view_appointments"><span>◫</span><b>Termine</b></button>
        <button type="button" class="quickViewBtn" data-quick-view="territorybook" data-perm="view_territory_book"><span>⌖</span><b>Gebietsbuch</b></button>
        <button type="button" class="quickViewBtn" data-quick-view="sales" data-perm="view_sales"><span>☎</span><b>Beratung</b></button>
        <button type="button" class="quickViewBtn" data-quick-view="triplog" data-perm="view_triplog"><span>⌁</span><b>Fahrtenbuch</b></button>
        <button type="button" class="quickViewBtn" data-quick-view="inventory" data-perm="view_inventory"><span>▤</span><b>Lager</b></button>
        <button type="button" class="quickViewBtn" data-quick-view="pos" data-perm="view_pos"><span>▰</span><b>Kasse</b></button>
        <button type="button" class="quickViewBtn" data-quick-view="finance" data-perm="view_finance"><span>€</span><b>Finanzen</b></button>
      </div>
    </section>

    <section class="workspace" id="workspace">
      <div class="loadingState"><div class="spinner"></div><p>Orgaboard wird geladen …</p></div>
    </section>
  </main>
</div>
  <nav class="mobileBottomNav" id="mobileBottomNav" aria-label="Mobile Navigation">
    <button data-mobile-view="dashboard"><span>⌂</span><b>Start</b></button>
    <button data-mobile-view="customers"><span>♙</span><b>Kunden</b></button>
    <button data-mobile-view="appointments"><span>◫</span><b>Termine</b></button>
    <button data-mobile-view="territorybook"><span>⌖</span><b>Gebiet</b></button>
    <button data-mobile-more="1"><span>☰</span><b>Mehr</b></button>
  </nav>

<div class="toast" id="toast"></div>
<div class="modalBackdrop" id="modalBackdrop">
  <section class="modalCard" id="modalCard">
    <button class="modalClose" id="modalClose">×</button>
    <div id="modalContent"></div>
  </section>
</div>
<script>
window.__rh24FrontendStarted=false;
window.__rh24ScriptLoaded=false;
window.__rh24BootStartedAt=Date.now();
window.addEventListener('error',function(e){
  var w=document.getElementById('workspace');
  if(w && w.querySelector('.loadingState')){
    w.innerHTML='<div class="panel panelPad"><h2>Oberflächenfehler erkannt</h2><p class="muted">'+String(e.message||'Unbekannter JavaScript-Fehler').replace(/[<&]/g,'')+'</p><p><b>Datei:</b> '+String(e.filename||'').split('/').pop()+' · Zeile '+String(e.lineno||'–')+'</p><button class="btn primary" onclick="location.reload()">Neu laden</button></div>';
  }
});
window.addEventListener('unhandledrejection',function(e){
  var w=document.getElementById('workspace');
  if(w && w.querySelector('.loadingState')){
    var m=(e.reason&&e.reason.message)?e.reason.message:String(e.reason||'Unbekannter Promise-Fehler');
    w.innerHTML='<div class="panel panelPad"><h2>Datenfehler erkannt</h2><p class="muted">'+m.replace(/[<&]/g,'')+'</p><button class="btn primary" onclick="location.reload()">Neu laden</button></div>';
  }
});
// Alte Orgaboard-Caches entfernen, ohne andere Website-Caches anzufassen.
if('caches' in window){caches.keys().then(function(keys){keys.filter(function(k){return k.indexOf('rh24-orgaboard-static-')===0 && k!=='rh24-orgaboard-static-v106-9-editor-pro'}).forEach(function(k){caches.delete(k)});}).catch(function(){});}
</script>
<script>window.RH24_ORGABOARD={csrf:<?= json_encode($csrf) ?>,user:<?= json_encode($currentUser,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,isAdmin:<?= $isAdmin ? 'true' : 'false' ?>};</script>
<script src="https://cdn.jsdelivr.net/npm/bwip-js@4.11.2/dist/bwip-js-min.js" defer></script>
<script src="admin-v9911.js?v=108.3" onerror="document.getElementById('workspace').innerHTML='<div class=&quot;panel panelPad&quot;><h2>Programmdatei konnte nicht geladen werden</h2><p>Bitte das aktuelle V107.0-Update direkt in den Serverordner /orgaboard hochladen.</p><p><a href=&quot;diagnose.php&quot;>Diagnose öffnen</a></p></div>'"></script>
<!-- V2026.2: Ergänzende Oberfläche (Cockpit, Befehlspalette, Assistent).
     Laedt nach dem Hauptprogramm und veraendert es nicht. -->
<script src="assets/orgaboard-premium-2026.js?v=2026.2" defer></script>
<!-- V2026.3: Gemeinsame Near-Realtime-Schicht. Im OrgaBoard MELDET sie
     nur Änderungen an geöffnete Shop-Tabs; sie fragt hier bewusst
     nichts ab. Danach die Atelier-Oberflächenschicht. -->
<script src="../rh-realtime-2026.js?v=2026.4" defer></script>
<script src="assets/orgaboard-atelier-2026.js?v=2026.4" defer></script>
<script>if('serviceWorker' in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('sw.js?v=107.0').catch(()=>{}));}</script>
<?php endif; ?>

<script>
window.setTimeout(function(){
  var w=document.getElementById('workspace');
  if(!w || !w.querySelector('.loadingState')) return;
  if(window.__rh24FrontendStarted===true){
    // Frontend ist gestartet; die API lädt noch. Nicht fälschlich blockieren.
    var p=w.querySelector('.loadingState p');
    if(p) p.textContent='Daten werden noch geladen … Bitte einen Moment warten.';
    return;
  }
  if(window.__rh24ScriptLoaded!==true){
    w.innerHTML='<div class="panel panelPad"><h2>Programmdatei wurde nicht gestartet</h2><p class="muted">Die JavaScript-Datei des Orgaboards wurde nicht vollständig geladen. Bitte V102.1 erneut hochladen und den Browser-Cache leeren.</p><p><a class="btn primary" href="diagnose.php">Systemdiagnose öffnen</a> <button class="btn" onclick="location.reload()">Neu laden</button></p></div>';
    return;
  }
  w.innerHTML='<div class="panel panelPad"><h2>Frontend-Initialisierung unterbrochen</h2><p class="muted">Die Programmdatei wurde geladen, die Oberfläche konnte ihre Initialisierung aber nicht abschließen. Die Systemdiagnose zeigt den nächsten Prüfpunkt.</p><p><a class="btn primary" href="diagnose.php">Systemdiagnose öffnen</a> <button class="btn" onclick="location.reload()">Neu laden</button></p></div>';
},15000);
</script>
</body>
</html>
