<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
if(!rh24_db_configured()){header('Location: setup.php');exit;}
$error='';$success='';$token=(string)($_GET['token']??$_POST['token']??'');$mode=$token!==''?'reset':'request';
$csrf=$_SESSION['rh24_pw_csrf']??'';if($csrf===''){$csrf=bin2hex(random_bytes(24));$_SESSION['rh24_pw_csrf']=$csrf;}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $sent=(string)($_POST['csrf']??'');
  if(!$sent||!hash_equals($csrf,$sent)){$error='Die Sitzung ist abgelaufen. Bitte laden Sie die Seite neu.';}
  elseif($mode==='request'){
    $identifier=strtolower(trim((string)($_POST['identifier']??'')));
    if($identifier===''){$error='Bitte Benutzername oder E-Mail-Adresse eingeben.';}
    else{
      try{
        $q=rh24_db()->prepare("SELECT id FROM users WHERE status='active' AND (LOWER(username)=? OR LOWER(email)=?) LIMIT 1");$q->execute([$identifier,$identifier]);$uid=(string)($q->fetchColumn()?:'');
        if($uid!==''){
          $recent=rh24_db()->prepare("SELECT created_at FROM password_reset_tokens WHERE user_id=? AND purpose='reset' ORDER BY id DESC LIMIT 1");$recent->execute([$uid]);$last=$recent->fetchColumn();
          if(!$last||strtotime((string)$last)<time()-120){rh24_send_reset_email($uid);rh24_audit('password_reset_requested','user',$uid,[],'system');}
        }
        $success='Wenn ein aktiver Zugang gefunden wurde, wurde eine E-Mail mit einem sicheren Link zum Festlegen eines neuen Passworts versendet.';
      }catch(Throwable){$success='Wenn ein aktiver Zugang gefunden wurde, wurde eine E-Mail mit einem sicheren Link zum Festlegen eines neuen Passworts versendet.';}
    }
  }else{
    $pw=(string)($_POST['password']??'');$repeat=(string)($_POST['password_repeat']??'');
    if(strlen($pw)<12)$error='Das neue Passwort muss mindestens 12 Zeichen lang sein.';
    elseif($pw!==$repeat)$error='Die beiden Passwörter stimmen nicht überein.';
    else{
      try{
        $hash=hash('sha256',$token);$q=rh24_db()->prepare("SELECT t.id,t.user_id,t.purpose FROM password_reset_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.used_at IS NULL AND t.expires_at>NOW() AND u.status='active' LIMIT 1");$q->execute([$hash]);$row=$q->fetch();
        if(!$row)$error='Dieser Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.';
        else{
          $db=rh24_db();$db->beginTransaction();$db->prepare('UPDATE users SET password_hash=?,must_change_password=0,updated_at=NOW() WHERE id=?')->execute([password_hash($pw,PASSWORD_DEFAULT),$row['user_id']]);$db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$row['user_id']]);$db->commit();rh24_audit('password_reset_completed','user',(string)$row['user_id'],['purpose'=>$row['purpose']],'system');header('Location: index.php?password=changed');exit;
        }
      }catch(Throwable $e){if(isset($db)&&$db instanceof PDO&&$db->inTransaction())$db->rollBack();$error='Das Passwort konnte nicht gespeichert werden. Bitte versuchen Sie es erneut.';}
    }
  }
}
$validToken=false;$displayName='';
if($mode==='reset'&&$error===''){
  try{$q=rh24_db()->prepare("SELECT u.display_name FROM password_reset_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=? AND t.used_at IS NULL AND t.expires_at>NOW() AND u.status='active' LIMIT 1");$q->execute([hash('sha256',$token)]);$displayName=(string)($q->fetchColumn()?:'');$validToken=$displayName!=='';}catch(Throwable){}
  if(!$validToken&&!$error)$error='Dieser Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen Link an.';
}
?>
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Passwort | Räucherhaken24 Orgaboard</title><link rel="stylesheet" href="assets/admin.css?v=40"></head><body class="ob-auth"><main class="authShell"><section class="authCard compact"><div class="authBrand"><span>RÄUCHERHAKEN</span><strong>24</strong></div><div class="authKicker">ORGABOARD · SICHERER ZUGANG</div>
<?php if($mode==='request'): ?><h1>Passwort vergessen?</h1><p>Geben Sie Ihren Benutzernamen oder Ihre hinterlegte E-Mail-Adresse ein. Sie erhalten einen zeitlich begrenzten Link, über den Sie unkompliziert ein neues Passwort festlegen können.</p><?php else: ?><h1>Neues Passwort festlegen</h1><p><?= $displayName!==''?'Guten Tag '.htmlspecialchars($displayName).'. ':'' ?>Legen Sie jetzt Ihr neues persönliches Orgaboard-Passwort fest.</p><?php endif; ?>
<?php if($error): ?><div class="authError"><?=htmlspecialchars($error)?></div><?php endif; ?><?php if($success): ?><div class="authSuccess"><?=htmlspecialchars($success)?></div><?php endif; ?>
<?php if($mode==='request'&&!$success): ?><form method="post" class="authForm"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><label>Benutzername oder E-Mail<input name="identifier" required autocomplete="username"></label><button type="submit">Reset-Link per E-Mail senden</button></form><?php elseif($mode==='reset'&&$validToken): ?><form method="post" class="authForm"><input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>"><input type="hidden" name="token" value="<?=htmlspecialchars($token)?>"><label>Neues Passwort<input type="password" name="password" minlength="12" required autocomplete="new-password"></label><label>Passwort wiederholen<input type="password" name="password_repeat" minlength="12" required autocomplete="new-password"></label><button type="submit">Passwort speichern</button></form><?php endif; ?>
<a href="index.php" class="backShop">← Zur Anmeldung</a></section></main></body></html>
