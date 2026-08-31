<?php
declare(strict_types=1);
require __DIR__.'/marketplace-bootstrap.php';
try {
    $db=market_db();
} catch(Throwable $e) {
    market_json(['ok'=>false,'error'=>'Marktplatz vorübergehend nicht verfügbar. Bitte später erneut versuchen.'],503);
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
$action=(string)($_REQUEST['action']??'session');

if($method==='GET' && $action==='session'){
    market_json(['ok'=>true,'csrf'=>market_csrf(),'user'=>market_user_public(market_user_row()),'fee'=>RH24_MARKET_FEE_GROSS,'listing_limit'=>RH24_MARKET_MAX_ACTIVE_LISTINGS,'categories'=>market_categories(),'terms_version'=>RH24_MARKET_TERMS_VERSION]);
}

if($method==='GET' && $action==='listings'){
    $q=trim((string)($_GET['q']??''));$kind=trim((string)($_GET['kind']??''));$cat=trim((string)($_GET['category']??''));$region=trim((string)($_GET['region']??''));$sort=trim((string)($_GET['sort']??'newest'));
    $viewerLat=is_numeric($_GET['lat']??null)?(float)$_GET['lat']:null;$viewerLon=is_numeric($_GET['lon']??null)?(float)$_GET['lon']:null;$radius=max(0,min(500,(float)($_GET['radius']??0)));
    $where=["l.status='published'","(l.expires_at IS NULL OR l.expires_at>=NOW())","u.status='active'"];$vals=[];
    if($q!==''){$where[]='(l.title LIKE ? OR l.description LIKE ? OR l.category LIKE ? OR l.city LIKE ?)';$like='%'.$q.'%';array_push($vals,$like,$like,$like,$like);}
    if(in_array($kind,['sell','wanted','gift'],true)){$where[]='l.kind=?';$vals[]=$kind;}
    if($cat!=='' && in_array($cat,market_categories(),true)){$where[]='l.category=?';$vals[]=$cat;}
    if($region!==''){$where[]='(l.zip LIKE ? OR l.city LIKE ?)';$like='%'.$region.'%';array_push($vals,$like,$like);}
    $sql='SELECT l.*,u.display_name AS seller_name FROM market_listings l JOIN market_users u ON u.id=l.user_id WHERE '.implode(' AND ',$where).' ORDER BY l.created_at DESC LIMIT 250';
    $st=$db->prepare($sql);$st->execute($vals);$rows=[];
    foreach($st->fetchAll() as $r){
        if(!in_array((string)($r['category']??''),market_categories(),true)) continue;
        $distance=null;if($viewerLat!==null&&$viewerLon!==null&&$r['lat']!==null&&$r['lon']!==null)$distance=round(market_distance_km($viewerLat,$viewerLon,(float)$r['lat'],(float)$r['lon']),1);
        if($radius>0 && ($distance===null || $distance>$radius))continue;
        $rows[]=['id'=>$r['id'],'kind'=>$r['kind'],'title'=>$r['title'],'description'=>$r['description'],'category'=>$r['category'],'condition'=>$r['condition_label'],'price'=>(float)$r['price'],'negotiable'=>(bool)$r['negotiable'],'shipping'=>$r['shipping'],'zip'=>$r['zip'],'city'=>$r['city'],'images'=>market_images($r),'seller_name'=>$r['seller_name'],'distance_km'=>$distance,'views'=>(int)$r['views'],'created_at'=>$r['created_at']];
    }
    if($sort==='price_asc')usort($rows,fn($a,$b)=>$a['price']<=>$b['price']);
    elseif($sort==='price_desc')usort($rows,fn($a,$b)=>$b['price']<=>$a['price']);
    elseif($sort==='distance'&&$viewerLat!==null)usort($rows,fn($a,$b)=>($a['distance_km']??999999)<=>($b['distance_km']??999999));
    elseif($sort==='popular')usort($rows,fn($a,$b)=>$b['views']<=>$a['views']);
    market_json(['ok'=>true,'listings'=>$rows,'count'=>count($rows)]);
}

if($method==='GET' && $action==='my'){
    $u=market_require_user();
    $q=$db->prepare('SELECT * FROM market_listings WHERE user_id=? AND status<>\'deleted\' ORDER BY created_at DESC');$q->execute([$u['id']]);$list=[];foreach($q->fetchAll() as $r){$list[]=['id'=>$r['id'],'kind'=>$r['kind'],'title'=>$r['title'],'description'=>$r['description'],'category'=>$r['category'],'condition'=>$r['condition_label'],'price'=>(float)$r['price'],'negotiable'=>(bool)$r['negotiable'],'shipping'=>$r['shipping'],'zip'=>$r['zip'],'city'=>$r['city'],'images'=>market_images($r),'status'=>$r['status'],'views'=>(int)$r['views'],'created_at'=>$r['created_at'],'updated_at'=>$r['updated_at']];}
    $m=$db->prepare("SELECT m.*,l.title AS listing_title,fu.display_name AS from_name,tu.display_name AS to_name FROM market_messages m JOIN market_listings l ON l.id=m.listing_id JOIN market_users fu ON fu.id=m.from_user_id JOIN market_users tu ON tu.id=m.to_user_id WHERE m.from_user_id=? OR m.to_user_id=? ORDER BY m.created_at DESC LIMIT 100");$m->execute([$u['id'],$u['id']]);
    market_json(['ok'=>true,'user'=>market_user_public($u),'listings'=>$list,'messages'=>$m->fetchAll()]);
}

if($method==='GET' && $action==='view'){
    $id=trim((string)($_GET['id']??''));if($id!=='')$db->prepare("UPDATE market_listings SET views=views+1 WHERE id=? AND status='published'")->execute([$id]);market_json(['ok'=>true]);
}

if($method!=='POST')market_json(['ok'=>false,'error'=>'Methode nicht erlaubt.'],405);
market_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??($_POST['csrf']??null));

if($action==='register'){
    $email=strtolower(trim((string)($_POST['email']??'')));$name=trim((string)($_POST['name']??''));$pass=(string)($_POST['password']??'');$zip=trim((string)($_POST['zip']??''));$city=trim((string)($_POST['city']??''));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))market_json(['ok'=>false,'error'=>'Bitte eine gültige E-Mail-Adresse eingeben.'],422);
    if(market_strlen($name)<2)market_json(['ok'=>false,'error'=>'Bitte Ihren Namen eingeben.'],422);
    if(strlen($pass)<10)market_json(['ok'=>false,'error'=>'Das Passwort muss mindestens 10 Zeichen haben.'],422);
    if(empty($_POST['terms'])||empty($_POST['rules'])||empty($_POST['platform_role'])||empty($_POST['age']))market_json(['ok'=>false,'error'=>'Bitte Marktplatz-AGB, Nutzungsregeln, Plattformrolle, Preis und Volljährigkeit bestätigen.'],422);
    $q=$db->prepare('SELECT id FROM market_users WHERE email=?');$q->execute([$email]);if($q->fetchColumn())market_json(['ok'=>false,'error'=>'Für diese E-Mail-Adresse besteht bereits ein Konto.'],409);
    $id=market_id('MU-');$db->prepare("INSERT INTO market_users(id,email,password_hash,display_name,zip,city,status,membership_status,terms_version,terms_accepted_at,created_at,updated_at) VALUES(?,?,?,?,?,?,'active','pending',?,NOW(),NOW(),NOW())")->execute([$id,$email,password_hash($pass,PASSWORD_DEFAULT),$name,$zip,$city,RH24_MARKET_TERMS_VERSION]);
    $token=bin2hex(random_bytes(32));$db->prepare("INSERT INTO market_verification_tokens(user_id,token_hash,purpose,expires_at,created_at) VALUES(?,?, 'verify',DATE_ADD(NOW(),INTERVAL 24 HOUR),NOW())")->execute([$id,hash('sha256',$token)]);
    $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=$_SERVER['HTTP_HOST']??'raeucherhaken24.com';$url=$scheme.'://'.$host.'/marktplatz-verifizieren.php?t='.urlencode($token);
    market_mail($email,'E-Mail für An- & Verkaufen bestätigen',"Hallo $name,\n\nbitte bestätigen Sie Ihre E-Mail-Adresse für Räucherhaken24 An- & Verkaufen:\n$url\n\nDer Link ist 24 Stunden gültig.\n\nJahreszugang: 19,99 € inkl. MwSt. · Laufzeit 1 Jahr · maximal 10 aktive Anzeigen.\n\nRäucherhaken24");
    $u=['id'=>$id,'email'=>$email,'display_name'=>$name,'phone'=>'','zip'=>$zip,'city'=>$city,'membership_status'=>'pending'];$order=market_create_membership_order($db,$u);
    $_SESSION['market_user_id']=$id;session_regenerate_id(true);
    market_json(['ok'=>true,'message'=>'Konto angelegt. Bitte E-Mail bestätigen. Der Jahreszugang wurde gebucht.','order_no'=>$order,'user'=>market_user_public(market_user_row())]);
}

if($action==='login'){
    $email=strtolower(trim((string)($_POST['email']??'')));$pass=(string)($_POST['password']??'');$q=$db->prepare('SELECT * FROM market_users WHERE email=? LIMIT 1');$q->execute([$email]);$u=$q->fetch();
    if(!$u||!password_verify($pass,(string)$u['password_hash']))market_json(['ok'=>false,'error'=>'E-Mail-Adresse oder Passwort ist falsch.'],401);
    if(($u['status']??'')!=='active')market_json(['ok'=>false,'error'=>'Dieses Konto ist gesperrt.'],403);
    session_regenerate_id(true);$_SESSION['market_user_id']=$u['id'];market_json(['ok'=>true,'user'=>market_user_public(market_user_row())]);
}
if($action==='logout'){$_SESSION=[];session_regenerate_id(true);market_json(['ok'=>true,'csrf'=>market_csrf()]);}

if($action==='terms_accept'){
    $u=market_require_user();
    if(empty($_POST['terms'])||empty($_POST['rules'])||empty($_POST['platform_role']))market_json(['ok'=>false,'error'=>'Bitte Marktplatz-AGB, Nutzungsregeln und Plattformrolle bestätigen.'],422);
    $db->prepare('UPDATE market_users SET terms_version=?,terms_accepted_at=NOW(),updated_at=NOW() WHERE id=?')->execute([RH24_MARKET_TERMS_VERSION,$u['id']]);
    market_json(['ok'=>true,'message'=>'Marktplatz-Bedingungen bestätigt.','user'=>market_user_public(market_user_row())]);
}

if($action==='resend_verify'){
    $u=market_require_user();if(!empty($u['email_verified_at']))market_json(['ok'=>true,'message'=>'E-Mail ist bereits bestätigt.']);
    $token=bin2hex(random_bytes(32));$db->prepare("INSERT INTO market_verification_tokens(user_id,token_hash,purpose,expires_at,created_at) VALUES(?,?,'verify',DATE_ADD(NOW(),INTERVAL 24 HOUR),NOW())")->execute([$u['id'],hash('sha256',$token)]);
    $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';$host=$_SERVER['HTTP_HOST']??'raeucherhaken24.com';$url=$scheme.'://'.$host.'/marktplatz-verifizieren.php?t='.urlencode($token);market_mail($u['email'],'E-Mail für An- & Verkaufen bestätigen',"Bitte bestätigen Sie Ihre E-Mail-Adresse:\n$url\n\nDer Link ist 24 Stunden gültig.");market_json(['ok'=>true,'message'=>'Bestätigungs-E-Mail wurde erneut gesendet.']);
}

if($action==='membership_order'){$u=market_require_current_terms();$order=market_create_membership_order($db,$u);market_json(['ok'=>true,'order_no'=>$order,'user'=>market_user_public(market_user_row())]);}

if($action==='listing_save'){
    $u=market_require_membership();
    if(!market_terms_current($u))market_json(['ok'=>false,'error'=>'Bitte zuerst die aktuellen Marktplatz-AGB bestätigen.','code'=>'terms_required'],428);
    if(empty($_POST['no_food'])||empty($_POST['platform_only']))market_json(['ok'=>false,'error'=>'Bitte bestätigen, dass kein Lebensmittel angeboten wird und Räucherhaken24 nur die Plattform bereitstellt.'],422);$id=trim((string)($_POST['id']??''));$isNew=$id==='';
    if(!$isNew){$q=$db->prepare('SELECT * FROM market_listings WHERE id=? AND user_id=?');$q->execute([$id,$u['id']]);$old=$q->fetch();if(!$old)market_json(['ok'=>false,'error'=>'Anzeige nicht gefunden.'],404);}else{$old=null;}
    if($isNew){$q=$db->prepare("SELECT COUNT(*) FROM market_listings WHERE user_id=? AND status IN ('pending','published','paused')");$q->execute([$u['id']]);if((int)$q->fetchColumn()>=RH24_MARKET_MAX_ACTIVE_LISTINGS)market_json(['ok'=>false,'error'=>'Maximal 10 aktive Anzeigen gleichzeitig. Bitte zuerst eine Anzeige beenden oder löschen.'],422);$id=market_id('ML-');}
    $kind=(string)($_POST['kind']??'sell');if(!in_array($kind,['sell','wanted','gift'],true))$kind='sell';$title=trim((string)($_POST['title']??''));$desc=trim((string)($_POST['description']??''));$cat=trim((string)($_POST['category']??''));if(!in_array($cat,market_categories(),true))market_json(['ok'=>false,'error'=>'Auf diesem Marktplatz sind ausschließlich Räucher-, Smoker- und Grilltechnik sowie passendes Zubehör zugelassen.'],422);$condition=trim((string)($_POST['condition']??''));$shipping=(string)($_POST['shipping']??'pickup');if(!in_array($shipping,['pickup','shipping','both'],true))$shipping='pickup';$zip=trim((string)($_POST['zip']??$u['zip']??''));$city=trim((string)($_POST['city']??$u['city']??''));
    if(market_strlen($title)<5||market_strlen($desc)<20)market_json(['ok'=>false,'error'=>'Bitte einen aussagekräftigen Titel und mindestens 20 Zeichen Beschreibung eingeben.'],422);if($zip===''||$city==='')market_json(['ok'=>false,'error'=>'PLZ und Ort sind für die Regionalsuche erforderlich.'],422);if(market_contains_disallowed_food($title.' '.$desc))market_json(['ok'=>false,'error'=>'Lebensmittel und verzehrbare Produkte sind auf An- & Verkaufen nicht zulässig. Bitte nur Räucher-, Smoker- oder Grilltechnik und Zubehör anbieten.'],422);
    $price=$kind==='gift'?0:max(0,(float)str_replace(',','.',(string)($_POST['price']??0)));$neg=!empty($_POST['negotiable'])?1:0;$lat=is_numeric($_POST['lat']??null)?round((float)$_POST['lat'],3):($old['lat']??null);$lon=is_numeric($_POST['lon']??null)?round((float)$_POST['lon'],3):($old['lon']??null);
    $images=$old?market_images($old):[];$dir=__DIR__.'/marketplace-uploads';if(!is_dir($dir))@mkdir($dir,0755,true);if(!is_file($dir.'/.htaccess'))@file_put_contents($dir.'/.htaccess',"Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|py|sh)$\">\nRequire all denied\n</FilesMatch>\n");
    if(isset($_FILES['images'])&&is_array($_FILES['images']['name']??null)){
      $f=$_FILES['images'];$n=count($f['name']);for($i=0;$i<$n && count($images)<5;$i++){if(($f['error'][$i]??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)continue;if(($f['size'][$i]??0)>5*1024*1024)continue;$tmp=$f['tmp_name'][$i];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??'';if($ext==='')continue;$name='mkt-'.bin2hex(random_bytes(10)).'.'.$ext;if(move_uploaded_file($tmp,$dir.'/'.$name))$images[]='marketplace-uploads/'.$name;}
    }
    $status='pending';
    if($isNew)$db->prepare("INSERT INTO market_listings(id,user_id,kind,title,description,category,condition_label,price,negotiable,shipping,zip,city,lat,lon,images_json,status,expires_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',DATE_ADD(NOW(),INTERVAL 180 DAY),NOW(),NOW())")
      ->execute([$id,$u['id'],$kind,$title,$desc,$cat,$condition,$price,$neg,$shipping,$zip,$city,$lat,$lon,json_encode($images,JSON_UNESCAPED_SLASHES)]);
    else $db->prepare("UPDATE market_listings SET kind=?,title=?,description=?,category=?,condition_label=?,price=?,negotiable=?,shipping=?,zip=?,city=?,lat=?,lon=?,images_json=?,status='pending',updated_at=NOW() WHERE id=? AND user_id=?")
      ->execute([$kind,$title,$desc,$cat,$condition,$price,$neg,$shipping,$zip,$city,$lat,$lon,json_encode($images,JSON_UNESCAPED_SLASHES),$id,$u['id']]);
    market_mail('service@raeucherhaken24.com','Marktplatz-Anzeige zur Prüfung: '.$title,"Neue/aktualisierte Marktplatz-Anzeige wartet auf Freigabe.\n\n$title\n$zip $city\nID: $id\n\nBitte im Orgaboard unter Marktplatz prüfen.");market_json(['ok'=>true,'id'=>$id,'status'=>$status,'message'=>'Anzeige gespeichert und zur Prüfung eingereicht.']);
}

if($action==='listing_status'){
    $u=market_require_membership();$id=trim((string)($_POST['id']??''));$status=(string)($_POST['status']??'paused');if(!in_array($status,['paused','sold','deleted'],true))market_json(['ok'=>false,'error'=>'Status nicht erlaubt.'],422);$q=$db->prepare('UPDATE market_listings SET status=?,updated_at=NOW() WHERE id=? AND user_id=?');$q->execute([$status,$id,$u['id']]);market_json(['ok'=>true]);
}

if($action==='message_send'){
    $u=market_require_membership();if(!market_terms_current($u))market_json(['ok'=>false,'error'=>'Bitte zuerst die aktuellen Marktplatz-AGB bestätigen.','code'=>'terms_required'],428);$id=trim((string)($_POST['listing_id']??''));$body=trim((string)($_POST['body']??''));if(market_strlen($body)<3)market_json(['ok'=>false,'error'=>'Bitte eine Nachricht eingeben.'],422);$q=$db->prepare("SELECT l.*,u.email seller_email,u.display_name seller_name FROM market_listings l JOIN market_users u ON u.id=l.user_id WHERE l.id=? AND l.status='published'");$q->execute([$id]);$l=$q->fetch();if(!$l)market_json(['ok'=>false,'error'=>'Anzeige nicht gefunden.'],404);if($l['user_id']===$u['id'])market_json(['ok'=>false,'error'=>'Sie können sich nicht selbst schreiben.'],422);
    $db->prepare('INSERT INTO market_messages(listing_id,from_user_id,to_user_id,body,created_at) VALUES(?,?,?,?,NOW())')->execute([$id,$u['id'],$l['user_id'],$body]);market_mail($l['seller_email'],'Neue Nachricht zu „'.$l['title'].'“',"Hallo {$l['seller_name']},\n\nSie haben eine neue Nachricht zu Ihrer Anzeige „{$l['title']}“ erhalten.\n\nBitte melden Sie sich bei Räucherhaken24 An- & Verkaufen an, um die Nachricht zu lesen und zu antworten.\n\nRäucherhaken24");market_json(['ok'=>true,'message'=>'Nachricht wurde übermittelt.']);
}

if($action==='message_reply'){
    $u=market_require_membership();if(!market_terms_current($u))market_json(['ok'=>false,'error'=>'Bitte zuerst die aktuellen Marktplatz-AGB bestätigen.','code'=>'terms_required'],428);$id=(int)($_POST['message_id']??0);$body=trim((string)($_POST['body']??''));if(market_strlen($body)<3)market_json(['ok'=>false,'error'=>'Bitte eine Nachricht eingeben.'],422);$q=$db->prepare('SELECT * FROM market_messages WHERE id=? AND (from_user_id=? OR to_user_id=?)');$q->execute([$id,$u['id'],$u['id']]);$m=$q->fetch();if(!$m)market_json(['ok'=>false,'error'=>'Nachricht nicht gefunden.'],404);$to=$m['from_user_id']===$u['id']?$m['to_user_id']:$m['from_user_id'];$db->prepare('INSERT INTO market_messages(listing_id,from_user_id,to_user_id,body,created_at) VALUES(?,?,?,?,NOW())')->execute([$m['listing_id'],$u['id'],$to,$body]);market_json(['ok'=>true]);
}

if($action==='report'){
    $u=market_user_row();$id=trim((string)($_POST['listing_id']??''));$reason=trim((string)($_POST['reason']??'Unzulässiger Inhalt'));$details=trim((string)($_POST['details']??''));
    $reporterName=trim((string)($_POST['reporter_name']??($u['display_name']??'')));$reporterEmail=trim((string)($_POST['reporter_email']??($u['email']??'')));$contentUrl=trim((string)($_POST['content_url']??''));
    if($id==='')market_json(['ok'=>false,'error'=>'Anzeige fehlt.'],422);if(market_strlen($reason)<5)market_json(['ok'=>false,'error'=>'Bitte den Meldegrund genauer angeben.'],422);
    if($reporterEmail!==''&&!filter_var($reporterEmail,FILTER_VALIDATE_EMAIL))market_json(['ok'=>false,'error'=>'Bitte eine gültige E-Mail-Adresse für die Rückmeldung eingeben.'],422);
    $db->prepare("INSERT INTO market_reports(listing_id,reporter_user_id,reporter_name,reporter_email,content_url,reason,details,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'open',NOW(),NOW())")->execute([$id,$u['id']??null,$reporterName,$reporterEmail,$contentUrl,$reason,$details]);
    market_json(['ok'=>true,'message'=>'Danke. Die Meldung wurde aufgenommen und wird geprüft.']);
}

market_json(['ok'=>false,'error'=>'Unbekannte Aktion.'],404);
