<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
rh24_require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data,int $status=200): never { http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function body(): array { $d=json_decode(file_get_contents('php://input')?:'{}',true); return is_array($d)?$d:[]; }
function rh24_remote_json(string $url): array {
  $raw='';$status=0;
  if(function_exists('curl_init')){
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>8,CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Raeucherhaken24-Orgaboard/74 (+https://www.raeucherhaken24.de)']]);
    $res=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
    if($res===false) throw new RuntimeException('Adressdienst nicht erreichbar'.($err?': '.$err:''));
    $raw=(string)$res;
  }else{
    $ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>8,'ignore_errors'=>true,'header'=>"Accept: application/json\r\nUser-Agent: Raeucherhaken24-Orgaboard/74\r\n"]]);
    $res=@file_get_contents($url,false,$ctx);if($res===false)throw new RuntimeException('Adressdienst nicht erreichbar');$raw=(string)$res;
    if(isset($http_response_header[0])&&preg_match('/\s(\d{3})\s/',$http_response_header[0],$m))$status=(int)$m[1];
  }
  if($status>=400)throw new RuntimeException('Adressdienst antwortet mit HTTP '.$status);
  $decoded=json_decode($raw,true);if(!is_array($decoded))throw new RuntimeException('Ungültige Antwort des Adressdienstes');return $decoded;
}
function rh24_openplz(string $endpoint,array $params): array {return rh24_remote_json('https://openplzapi.org'.$endpoint.'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986));}

function rh24_overpass_post(string $query): array {
  // V83.3: bewusst kurze Requests. STRATO darf nicht 60-90 Sekunden auf eine
  // überlastete öffentliche Overpass-Instanz warten, weil sonst der Webserver
  // selbst mit HTTP 504 abbricht und kein JSON mehr an das Orgaboard liefert.
  $urls=['https://overpass-api.de/api/interpreter','https://overpass.kumi.systems/api/interpreter','https://overpass.private.coffee/api/interpreter'];
  $body=http_build_query(['data'=>$query],'','&',PHP_QUERY_RFC3986);$errors=[];
  foreach($urls as $url){$raw='';$status=0;
    try{
      if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>14,CURLOPT_ENCODING=>'',CURLOPT_HTTPHEADER=>['Accept: application/json','Content-Type: application/x-www-form-urlencoded','User-Agent: Raeucherhaken24-Orgaboard/90 (+https://www.raeucherhaken24.com)']]);
        $res=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
        if($res===false)throw new RuntimeException($err?:'keine Antwort');$raw=(string)$res;
      }else{
        $ctx=stream_context_create(['http'=>['method'=>'POST','timeout'=>14,'ignore_errors'=>true,'header'=>"Accept: application/json\r\nContent-Type: application/x-www-form-urlencoded\r\nUser-Agent: Raeucherhaken24-Orgaboard/90 (+https://www.raeucherhaken24.com)\r\n",'content'=>$body]]);
        $res=@file_get_contents($url,false,$ctx);if($res===false)throw new RuntimeException('keine Antwort');$raw=(string)$res;
        if(isset($http_response_header[0])&&preg_match('/\s(\d{3})\s/',$http_response_header[0],$m))$status=(int)$m[1];
      }
      if($status>=400)throw new RuntimeException('HTTP '.$status);
      $decoded=json_decode($raw,true);if(!is_array($decoded)||!isset($decoded['elements']))throw new RuntimeException('ungültige JSON-Antwort');return $decoded;
    }catch(Throwable $e){$errors[]=parse_url($url,PHP_URL_HOST).': '.$e->getMessage();}
  }
  throw new RuntimeException('Teilabfrage der Branchen-Recherche fehlgeschlagen ('.implode(' · ',$errors).'). Das Orgaboard kann diesen Teil automatisch erneut versuchen; bereits importierte Treffer bleiben erhalten.');
}
function rh24_osm_category(array $t): string {
  $shop=mb_strtolower((string)($t['shop']??''));$industrial=mb_strtolower((string)($t['industrial']??''));$amenity=mb_strtolower((string)($t['amenity']??''));$craft=mb_strtolower((string)($t['craft']??''));$name=mb_strtolower((string)($t['name']??''));$cuisine=mb_strtolower((string)($t['cuisine']??''));
  if($amenity==='restaurant')return 'restaurant';
  if($shop==='fishing'||($shop==='sports'&&mb_strtolower((string)($t['sport']??''))==='fishing'))return 'fishing_shop';
  if(($t['club']??'')==='sport'&&($t['sport']??'')==='fishing')return 'fishing_club';
  if(preg_match('/(angel|angler|fischerei|fischer).{0,25}(verein|club)/u',$name))return 'fishing_club';
  if(preg_match('/(fleischerei|metzgerei|schlachter|fleischer|metzger).{0,25}(bedarf|technik|ausstattung)|(?:bedarf|technik|ausstattung).{0,25}(fleischerei|metzgerei|schlachter|fleischer|metzger)/u',$name))return 'butcher_supply';
  if($shop==='butcher'||$craft==='butcher')return 'butcher';
  if($industrial==='slaughterhouse')return 'slaughterhouse';
  if(in_array($shop,['seafood','fishmonger'],true))return 'seafood';
  if($industrial==='seafood_processing')return 'fishery';
  if(in_array($shop,['doityourself','hardware','garden_centre','trade','building_materials'],true))return 'hardware_store';
  if(preg_match('/(gewürz|gewuerz|spice|seasoning|kräuter|kraeuter)/u',$name)&&preg_match('/(manufaktur|mühle|muehle|werk|fabrik|produktion|hersteller)/u',$name))return 'spice_producer';
  if($shop==='spices'||$shop==='herbalist')return 'spices';
  if(preg_match('/(gewürz|gewuerz|spice|seasoning|kräuter|kraeuter|marinad|rub\b)/u',$name))return 'spices';
  if(in_array($shop,['outdoor','hunting'],true))return 'outdoor_hunting';
  if($shop==='wholesale')return 'wholesale';
  if($amenity==='marketplace')return 'market';
  if($shop==='deli')return 'deli';
  if(in_array($shop,['supermarket','convenience','food','greengrocer','farm','general'],true))return 'grocery';
  if(preg_match('/(räucher|raeucher|smok|bbq|barbecue|grill)/u',$name.' '.$cuisine))return 'smoke_bbq';return 'other';
}
function rh24_overpass_query_for_state(string $stateName,string $pack): string {
  $state=str_replace(['\\','"'],['\\\\','\\"'],$stateName);
  // V83.3: keine ungebremsten name-RegEx-Abfragen mehr über sämtliche OSM-Objekte.
  // Jede Suche ist über vorhandene Branchen-Tags eingegrenzt und wird als kleines Paket ausgeführt.
  $fishing='nwr(area.a)["shop"="fishing"];nwr(area.a)["shop"="sports"]["sport"="fishing"];nwr(area.a)["club"="sport"]["sport"="fishing"];nwr(area.a)["office"="association"]["name"~"(Angel|Angler|Fischerei|Fischer)",i];';
  $meat='nwr(area.a)["shop"="butcher"];nwr(area.a)["craft"="butcher"];nwr(area.a)["industrial"="slaughterhouse"];nwr(area.a)["shop"="wholesale"]["name"~"(Fleischer|Fleischerei|Metzger|Metzgerei|Schlachter).{0,30}(Bedarf|Technik|Ausstattung)|(?:Bedarf|Technik|Ausstattung).{0,30}(Fleischer|Fleischerei|Metzger|Metzgerei|Schlachter)",i];';
  $hardware='nwr(area.a)["shop"~"^(doityourself|hardware|garden_centre|trade|building_materials)$"];';
  $spices='nwr(area.a)["shop"~"^(spices|herbalist)$"];nwr(area.a)["shop"]["name"~"(Gewürz|Gewuerz|Gewürzmühle|Gewuerzmuehle|Gewürzmanufaktur|Gewuerzmanufaktur|Gewürzwerk|Gewuerzwerk|Kräuter|Kraeuter|Spice|Seasoning|Marinad|Rub)",i];nwr(area.a)["craft"]["name"~"(Gewürz|Gewuerz|Kräuter|Kraeuter|Spice|Seasoning)",i];nwr(area.a)["industrial"]["name"~"(Gewürz|Gewuerz|Spice|Seasoning)",i];';
  $fishery='nwr(area.a)["shop"~"^(seafood|fishmonger)$"];nwr(area.a)["industrial"="seafood_processing"];';
  $smoke='nwr(area.a)["shop"]["name"~"(Räucher|Raeucher|Smoke|Smoker|BBQ|Barbecue|Grill)",i];nwr(area.a)["craft"]["name"~"(Räucher|Raeucher|Smoke|Smoker|BBQ|Barbecue|Grill)",i];';
  $outdoorTrade='nwr(area.a)["shop"~"^(outdoor|hunting)$"];nwr(area.a)["shop"="wholesale"]["name"~"(Gastro|Fleisch|Fleischer|Metzger|Gewürz|Gewuerz|Grill|BBQ|Fisch|Angel|Jagd|Outdoor)",i];';
  $food='nwr(area.a)["shop"~"^(supermarket|convenience|food|deli|greengrocer|farm|general)$"];nwr(area.a)["amenity"="marketplace"];';
  $restaurants='nwr(area.a)["amenity"="restaurant"];';
  $relevantRestaurants='nwr(area.a)["amenity"="restaurant"]["cuisine"~"(fish|seafood|barbecue|bbq|grill|german|regional)",i];nwr(area.a)["amenity"="restaurant"]["name"~"(Fisch|Smoke|Smok|BBQ|Barbecue|Grill|Räucher|Raeucher)",i];';
  $core=$fishing.$meat.$hardware.$spices.$fishery.$smoke.$outdoorTrade;
  $packs=['fishing'=>$fishing,'meat'=>$meat,'hardware'=>$hardware,'spices'=>$spices,'fishery'=>$fishery,'smoke_bbq'=>$smoke,'outdoor_trade'=>$outdoorTrade,'food'=>$food,'relevant_restaurants'=>$relevantRestaurants,'restaurants'=>$restaurants,'core'=>$core,'all'=>$core.$food.$relevantRestaurants];
  $q=$packs[$pack]??$core;
  return '[out:json][timeout:12][maxsize:67108864];area["boundary"="administrative"]["admin_level"="4"]["name"="'.$state.'"]->.a;('.$q.');out center tags qt;';
}


function rh24_territory_full_fill_packs(): array {
  return ['fishing','meat','hardware','spices','fishery','smoke_bbq','outdoor_trade','food','relevant_restaurants'];
}
function rh24_territory_pack_label_php(string $pack): string {
  return [
    'fishing'=>'Angelgeschäfte & Angelvereine','meat'=>'Fleischereien / Schlachtereien & Bedarf','hardware'=>'Baumärkte & Gartencenter',
    'spices'=>'Gewürze / Kräuter / Manufakturen','fishery'=>'Fischhandel & Fischverarbeitung','smoke_bbq'=>'Räuchern / Smoker / Grill / BBQ',
    'outdoor_trade'=>'Outdoor / Jagd / relevanter Großhandel','food'=>'Lebensmittelgeschäfte & Märkte','relevant_restaurants'=>'Branchennahe Gastronomie'
  ][$pack]??$pack;
}
function rh24_territory_discover_run(string $code,string $pack): array {
  $db=rh24_db();
  $code=str_pad((string)(int)$code,2,'0',STR_PAD_LEFT);
  if(!in_array($pack,array_merge(rh24_territory_full_fill_packs(),['core','restaurants','all']),true))$pack='core';
  $tq=$db->prepare('SELECT t.state_name,t.territory_book_no,t.owner_sales_rep_id,s.name owner_name FROM sales_territories t LEFT JOIN sales_reps s ON s.id=t.owner_sales_rep_id WHERE t.state_code=?');
  $tq->execute([$code]);$territory=$tq->fetch();if(!$territory)throw new RuntimeException('Festgebiet nicht gefunden');
  $stateName=(string)$territory['state_name'];$ownerId=trim((string)($territory['owner_sales_rep_id']??''));
  $remote=rh24_overpass_post(rh24_overpass_query_for_state($stateName,$pack));$elements=is_array($remote['elements']??null)?$remote['elements']:[];
  $up=$db->prepare("INSERT INTO territory_directory(id,state_code,category,company,brand,operator_name,contact_person,phone,mobile,email,website,opening_hours,street,zip,city,latitude,longitude,status,assigned_sales_rep_id,source,source_ref,source_url,source_checked_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'candidate',?,'openstreetmap',?,?,NOW(),NOW(),NOW()) ON DUPLICATE KEY UPDATE state_code=VALUES(state_code),category=VALUES(category),company=VALUES(company),brand=IF(COALESCE(territory_directory.brand,'')='',VALUES(brand),territory_directory.brand),operator_name=IF(COALESCE(territory_directory.operator_name,'')='',VALUES(operator_name),territory_directory.operator_name),phone=IF(COALESCE(territory_directory.phone,'')='',VALUES(phone),territory_directory.phone),mobile=IF(COALESCE(territory_directory.mobile,'')='',VALUES(mobile),territory_directory.mobile),email=IF(COALESCE(territory_directory.email,'')='',VALUES(email),territory_directory.email),website=IF(COALESCE(territory_directory.website,'')='',VALUES(website),territory_directory.website),opening_hours=IF(COALESCE(territory_directory.opening_hours,'')='',VALUES(opening_hours),territory_directory.opening_hours),street=IF(COALESCE(territory_directory.street,'')='',VALUES(street),territory_directory.street),zip=IF(COALESCE(territory_directory.zip,'')='',VALUES(zip),territory_directory.zip),city=IF(COALESCE(territory_directory.city,'')='',VALUES(city),territory_directory.city),latitude=COALESCE(territory_directory.latitude,VALUES(latitude)),longitude=COALESCE(territory_directory.longitude,VALUES(longitude)),assigned_sales_rep_id=CASE WHEN COALESCE(territory_directory.assigned_sales_rep_id,'')='' THEN VALUES(assigned_sales_rep_id) ELSE territory_directory.assigned_sales_rep_id END,source_checked_at=NOW(),updated_at=NOW()");
  $changed=0;$seen=0;
  foreach($elements as $el){
    if(!is_array($el))continue;$tags=is_array($el['tags']??null)?$el['tags']:[];$name=trim((string)($tags['name']??($tags['brand']??($tags['operator']??''))));if($name==='')continue;
    $seen++;$type=(string)($el['type']??'node');$eid=(string)($el['id']??'');if($eid==='')continue;$ref=$type.':'.$eid;
    $lat=$el['lat']??($el['center']['lat']??null);$lon=$el['lon']??($el['center']['lon']??null);$street=trim((string)($tags['addr:street']??''));$hn=trim((string)($tags['addr:housenumber']??''));if($hn!=='')$street=trim($street.' '.$hn);
    $phone=trim((string)($tags['contact:phone']??($tags['phone']??'')));$mobile=trim((string)($tags['contact:mobile']??($tags['mobile']??'')));$email=strtolower(trim((string)($tags['contact:email']??($tags['email']??''))));$website=trim((string)($tags['contact:website']??($tags['website']??'')));$sourceUrl='https://www.openstreetmap.org/'.$type.'/'.$eid;
    try{$up->execute([rh24_random_id('GB-'),$code,rh24_osm_category($tags),$name,trim((string)($tags['brand']??'')),trim((string)($tags['operator']??'')),trim((string)($tags['contact:person']??'')),$phone,$mobile,$email,$website,trim((string)($tags['opening_hours']??'')),$street,trim((string)($tags['addr:postcode']??'')),trim((string)($tags['addr:city']??($tags['addr:place']??''))),$lat,$lon,$ownerId!==''?$ownerId:null,$ref,$sourceUrl]);$changed+=$up->rowCount()>0?1:0;}catch(PDOException $e){if((string)$e->getCode()!=='23000')throw $e;}
  }
  if($ownerId!==''){$aq=$db->prepare("UPDATE territory_directory SET assigned_sales_rep_id=?,updated_at=NOW() WHERE state_code=? AND status<>'archived' AND (assigned_sales_rep_id IS NULL OR assigned_sales_rep_id='')");$aq->execute([$ownerId,$code]);}
  rh24_audit('territory_directory_discover','sales_territory',$code,['pack'=>$pack,'source'=>'OpenStreetMap','elements'=>$seen,'territory_book_no'=>$territory['territory_book_no'],'assigned_owner'=>$ownerId]);
  return ['source'=>'OpenStreetMap / Overpass','found'=>$seen,'changed'=>$changed,'territory_book_no'=>$territory['territory_book_no'],'state_name'=>$stateName,'owner_name'=>$territory['owner_name']??'','summary'=>rh24_territory_directory_summary($code)];
}
function rh24_territory_fill_job_get(): array {
  $raw=rh24_setting_get('territory_national_fill_v90','');$job=is_string($raw)?rh24_json_decode($raw,[]):(is_array($raw)?$raw:[]);return is_array($job)?$job:[];
}
function rh24_territory_fill_job_save(array $job): array {rh24_setting_set('territory_national_fill_v90',$job);return $job;}
function rh24_territory_fill_job_public(array $job): array {
  if(!$job)return ['status'=>'idle','total_steps'=>0,'completed_steps'=>0,'failed_steps'=>0,'percent'=>0,'states'=>[]];
  $total=max(1,(int)($job['total_steps']??0));$done=(int)($job['completed_steps']??0)+(int)($job['failed_steps']??0);
  return [
    'id'=>(string)($job['id']??''),'status'=>(string)($job['status']??'idle'),'started_at'=>$job['started_at']??null,'updated_at'=>$job['updated_at']??null,
    'completed_at'=>$job['completed_at']??null,'total_steps'=>(int)($job['total_steps']??0),'completed_steps'=>(int)($job['completed_steps']??0),'failed_steps'=>(int)($job['failed_steps']??0),
    'current_index'=>(int)($job['current_index']??0),'percent'=>(int)min(100,round($done/$total*100)),'last_message'=>(string)($job['last_message']??''),'last_error'=>(string)($job['last_error']??''),
    'states'=>array_values($job['states']??[]),'packs'=>array_values($job['packs']??rh24_territory_full_fill_packs())
  ];
}
function rh24_territory_fill_job_start(): array {
  $db=rh24_db();$states=$db->query("SELECT state_code,state_name,territory_book_no FROM sales_territories ORDER BY CAST(state_code AS UNSIGNED)")->fetchAll();
  if(count($states)!==16)throw new RuntimeException('Es wurden '.count($states).' statt 16 Festgebiete gefunden. Bitte Diagnose prüfen.');
  $packs=rh24_territory_full_fill_packs();$steps=[];$stateStatus=[];
  foreach($states as $s){$code=(string)$s['state_code'];$stateStatus[$code]=['state_code'=>$code,'state_name'=>(string)$s['state_name'],'territory_book_no'=>(string)$s['territory_book_no'],'completed'=>0,'failed'=>0,'found'=>0,'status'=>'queued'];foreach($packs as $p)$steps[]=['state_code'=>$code,'state_name'=>(string)$s['state_name'],'territory_book_no'=>(string)$s['territory_book_no'],'pack'=>$p,'attempts'=>0,'status'=>'queued'];}
  $job=['id'=>'NF-'.date('Ymd-His'),'status'=>'running','started_at'=>date('c'),'updated_at'=>date('c'),'completed_at'=>null,'packs'=>$packs,'steps'=>$steps,'states'=>$stateStatus,'current_index'=>0,'total_steps'=>count($steps),'completed_steps'=>0,'failed_steps'=>0,'last_message'=>'Vollfüllung gestartet · 16 Gebietsbücher','last_error'=>''];
  return rh24_territory_fill_job_save($job);
}

if($_SERVER['REQUEST_METHOD']==='GET'){
  try{
    $db=rh24_db();
    $catalog=[]; foreach(rh24_catalog() as $id=>$p)$catalog[]=['id'=>$id]+$p;
    $settings=rh24_is_admin()?rh24_config():[]; unset($settings['admin_password_hash'],$settings['google_routes_credentials']);
    $orderRows=(rh24_can('view_orders')||rh24_can('view_production'))?rh24_orders():[];
    if(rh24_user_role()==='production'){foreach($orderRows as &$o){unset($o['totals'],$o['internal_note'],$o['customer_note'],$o['sales_rep_id'],$o['sales_rep_name'],$o['commission_sales_rep_id'],$o['commission_sales_rep_name'],$o['commission_attribution'],$o['commission_attribution_label'],$o['commission_note'],$o['commission_assigned_at'],$o['consultation_id'],$o['history']);}unset($o);}
    $payload=['ok'=>true,'server_time'=>date('c'),'csrf'=>rh24_csrf(),'current_user'=>rh24_current_user(),'permissions'=>rh24_permissions(),
      'orders'=>$orderRows,
      'prototypes'=>rh24_can('view_prototypes')?rh24_prototypes():[],
      'customers'=>rh24_can('view_customers')?rh24_customers():[],
      'sales_reps'=>rh24_is_admin()?rh24_sales_reps():[],
      'sales_territories'=>rh24_is_admin()?rh24_sales_territories():[],
      'territory_book_scope'=>rh24_can('view_territory_book')?rh24_territory_book_scope():[],
      'territory_directory_summary'=>rh24_can('view_territory_book')?rh24_territory_directory_summary():[],
      'territory_book'=>(!rh24_is_admin()&&rh24_can('view_territory_book'))?rh24_territory_directory_rows():[],
      'territory_directory_categories'=>rh24_territory_directory_categories(),
      'territory_directory_national_summary'=>rh24_can('view_territory_book')?rh24_territory_directory_national_summary():[],
      'territory_national_fill'=>rh24_is_admin()?rh24_territory_fill_job_public(rh24_territory_fill_job_get()):[],
      'production_users'=>(rh24_is_admin()||rh24_can('view_production'))?rh24_production_users():[],
      'production_activity'=>(rh24_is_admin()||rh24_can('view_production'))?rh24_production_activity(350):[],
      'inventory'=>rh24_can('view_inventory')?rh24_inventory():[],
      'warehouse'=>rh24_can('view_inventory')?rh24_warehouse_data():[],
      'catalog'=>(rh24_can('view_products')||rh24_can('view_sales'))?$catalog:[],
      'documents'=>rh24_can('view_documents')?rh24_documents():[],
      'messages'=>rh24_can('view_messages')?rh24_messages_for_current_user():[],
      'message_users'=>rh24_can('view_messages')?$db->query("SELECT id,display_name,role,sales_rep_id FROM users WHERE status='active' ORDER BY role='admin' DESC,display_name")->fetchAll():[],
      'my_sales_stats'=>(rh24_can('view_own_stats')||rh24_can('view_earnings_calculator')||rh24_can('view_star_stats'))?rh24_my_sales_stats():[],
      'leaderboard'=>[],
      'my_star_year'=>[],
      'sales_calendar'=>rh24_can('view_sales_calendar')?rh24_sales_calendar_data():[],
      'star_thresholds'=>rh24_star_thresholds(),
      'order_statuses'=>rh24_order_statuses(),'prototype_statuses'=>rh24_prototype_statuses(),'settings'=>$settings,'shipping_integrations'=>rh24_shipping_integrations(false),'shipping_labels'=>rh24_can('view_shipping')?rh24_shipping_labels():[],'payment_integrations'=>rh24_is_admin()?rh24_payment_integrations(false):[],'payment_provider_catalog'=>rh24_is_admin()?rh24_payment_provider_catalog():[]];
    if(rh24_is_admin()){
      $payload['dealers']=rh24_dealers();$payload['consultations']=rh24_consultations();$payload['reviews']=rh24_reviews();$payload['content']=rh24_content();$payload['ai']=rh24_ai();
      $payload['users']=rh24_users();$payload['activity']=rh24_activity();$payload['cost_profiles']=rh24_cost_profiles();$payload['permission_catalog']=rh24_permission_catalog();$payload['mail_log']=rh24_mail_log_rows();
      $payload['invoice_profile']=rh24_invoice_profile();$payload['invoice_readiness']=rh24_invoice_profile_readiness();
      $payload['commission_tiers']=rh24_commission_tiers();$payload['newsletter_summary']=rh24_newsletter_summary();$payload['newsletter_campaigns']=rh24_newsletter_campaigns();$payload['marketplace']=rh24_marketplace_admin_data();$payload['hd_hahne_click_stats']=rh24_hd_hahne_click_stats();
    }
    out($payload);
  }catch(Throwable $e){out(['ok'=>false,'error'=>'Datenbankfehler: '.$e->getMessage()],500);}
}
if($_SERVER['REQUEST_METHOD']!=='POST')out(['ok'=>false,'error'=>'Methode nicht erlaubt'],405);
$data=body(); rh24_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??($data['csrf']??null)); $action=(string)($data['action']??''); $db=rh24_db();
/* V2026.2: Zentrale Rechteprüfung vor der Ausführung. Ergänzt die
   vorhandenen Prüfungen in den einzelnen Handlern und schliesst die
   Aktionen, die bisher nur eine Anmeldung vorausgesetzt haben. */
require_once __DIR__.'/api-guard.php';
rh24_api_guard($action);

$actionPermission=[
  'order_update'=>'edit_orders','production_update'=>'edit_production','prototype_update'=>'edit_prototypes','inventory_update'=>'edit_inventory','warehouse_save'=>'edit_inventory','warehouse_stock_book'=>'edit_inventory','sales_rep_save'=>'manage_sales_reps','territory_assign'=>'manage_sales_reps',
  'consultation_save'=>'save_consultations','manual_order_create'=>'create_orders','customer_save'=>'edit_customers','customer_note'=>'edit_customers',
  'product_save'=>'edit_products','product_publish_repair'=>'edit_products','product_publish_probe'=>'edit_products','product_quick_update'=>'edit_products','product_delete'=>'edit_products','theme_save'=>'manage_settings','payment_integration_save'=>'manage_settings','payment_integration_test'=>'manage_settings','dealer_save'=>'edit_dealers','review_update'=>'edit_reviews','content_save'=>'edit_content','settings_save'=>'manage_settings',
  'password_change'=>'change_own_password','document_get'=>'view_documents','document_save'=>'edit_documents','document_status'=>'edit_documents',
  'cost_profile_save'=>'use_calculator','cost_price_apply'=>'use_calculator','message_send'=>'send_messages','message_read'=>'view_messages',
  'user_save'=>'manage_users','user_permissions_save'=>'manage_users','welcome_resend'=>'manage_users','address_localities'=>'view_customers','address_streets'=>'view_customers','shipping_integration_save'=>'manage_settings','shipping_connection_test'=>'manage_settings','shipping_label_save'=>'edit_orders','shipping_label_get'=>'view_shipping','leaderboard_get'=>'view_leaderboard','dealer_calendar_get'=>'view_dealer_visits','dealer_visit_complete'=>'manage_dealer_visits','dealer_visit_reschedule'=>'manage_dealer_visits','customer_verify'=>'edit_customers','appointment_get'=>'view_appointments','appointment_customer_search'=>'view_appointments','appointment_save'=>'edit_appointments','appointment_status'=>'edit_appointments','appointment_delete'=>'edit_appointments','appointment_move'=>'edit_appointments','appointment_duplicate'=>'edit_appointments','appointment_task_toggle'=>'edit_appointments','appointment_template_save'=>'edit_appointments','appointment_template_delete'=>'edit_appointments','appointment_email_invite'=>'edit_appointments','appointment_reminders_run'=>'manage_settings','triplog_get'=>'view_triplog','trip_save'=>'edit_triplog','trip_finalize'=>'edit_triplog','trip_correct'=>'edit_triplog','trip_delete'=>'edit_triplog','trip_vehicle_save'=>'edit_triplog','vehicle_key_lookup'=>'view_triplog','vehicle_lookup_config_save'=>'manage_settings','vehicle_lookup_test'=>'manage_settings','trip_route_optimize'=>'view_triplog','territory_directory_list'=>'view_territory_book','territory_directory_search'=>'view_territory_book','territory_directory_history'=>'view_territory_book','territory_directory_contact'=>'contact_territory_book','newsletter_test'=>'manage_newsletter','newsletter_send'=>'manage_newsletter'
];
if(!rh24_is_admin()){
  $perm=$actionPermission[$action]??'';
  if($perm===''||!rh24_can($perm)) out(['ok'=>false,'error'=>'Für diese Aktion fehlt die erforderliche Berechtigung.'],403);
}


try{
  if($action==='appointment_reminders_run'){
    if(!rh24_is_admin())out(['ok'=>false,'error'=>'Nur Administratoren dürfen den E-Mail-Erinnerungslauf manuell starten.'],403);$rep=trim((string)($data['sales_rep_id']??''));$result=rh24_appointment_send_due_reminders($rep!==''?$rep:null);out(['ok'=>true,'reminder_run'=>$result,'appointments'=>rh24_appointment_data((string)($data['period']??date('Y-m')),$rep)]);
  }
  if($action==='appointment_get'){
    if(!rh24_can('view_appointments'))out(['ok'=>false,'error'=>'Keine Berechtigung für den Terminplaner.'],403);$period=trim((string)($data['period']??date('Y-m')));$rep=trim((string)($data['sales_rep_id']??''));out(['ok'=>true,'appointments'=>rh24_appointment_data($period,$rep)]);
  }
  if($action==='appointment_customer_search'){
    if(!rh24_can('view_appointments'))out(['ok'=>false,'error'=>'Keine Berechtigung für die Kundensuche.'],403);$scope=rh24_appointment_rep_scope((string)($data['sales_rep_id']??''));$repId=(string)($scope['id']??'');if($repId==='')out(['ok'=>true,'customers'=>[]]);rh24_appointment_assert_owned_rep($repId);$term=trim((string)($data['term']??''));out(['ok'=>true,'customers'=>rh24_appointment_customer_search($repId,$term,30)]);
  }
  if($action==='appointment_save'){
    if(!rh24_can('edit_appointments'))out(['ok'=>false,'error'=>'Keine Berechtigung zum Bearbeiten von Terminen.'],403);$scope=rh24_appointment_rep_scope((string)($data['sales_rep_id']??''));$repId=(string)($scope['id']??'');if($repId==='')out(['ok'=>false,'error'=>'Kein Kundenberater ausgewählt.'],422);rh24_appointment_assert_owned_rep($repId);
    $id=trim((string)($data['id']??''));$existing=null;if($id!==''){$q=$db->prepare('SELECT * FROM advisor_appointments WHERE id=?');$q->execute([$id]);$existing=$q->fetch();if(!$existing)out(['ok'=>false,'error'=>'Termin nicht gefunden.'],404);if((string)$existing['sales_rep_id']!==$repId)out(['ok'=>false,'error'=>'Termin gehört zu einem anderen Kundenberater.'],403);}else$id=rh24_random_id('APT-');
    $customerId=trim((string)($data['customer_id']??''));$customer=null;if($customerId!==''){$q=$db->prepare('SELECT id,name,email,phone,company,street,zip,city,sales_rep_id FROM customers WHERE id=? AND sales_rep_id=?');$q->execute([$customerId,$repId]);$customer=$q->fetch();if(!$customer)out(['ok'=>false,'error'=>'Dieser Kunde gehört nicht zum Kundenstamm des ausgewählten Beraters.'],403);}
    $title=trim((string)($data['title']??''));if($title===''&&$customer)$title='Termin · '.(string)$customer['name'];if($title==='')out(['ok'=>false,'error'=>'Terminbezeichnung ist erforderlich.'],422);
    $types=rh24_appointment_type_labels();$type=(string)($data['appointment_type']??'consultation');if(!isset($types[$type]))$type='other';$statuses=rh24_appointment_status_labels();$status=(string)($data['status']??($existing['status']??'scheduled'));if(!isset($statuses[$status]))$status='scheduled';$locations=rh24_appointment_location_labels();$location=(string)($data['location_mode']??'visit');if(!isset($locations[$location]))$location='visit';
    $startsRaw=trim((string)($data['starts_at']??''));$endsRaw=trim((string)($data['ends_at']??''));$starts=str_replace('T',' ',$startsRaw);$ends=str_replace('T',' ',$endsRaw);if(strlen($starts)===16)$starts.=':00';if(strlen($ends)===16)$ends.=':00';$st=strtotime($starts);$et=strtotime($ends);if(!$st||!$et||$et<=$st)out(['ok'=>false,'error'=>'Start- und Endzeit des Termins sind ungültig.'],422);if($et-$st>24*3600)out(['ok'=>false,'error'=>'Ein einzelner Termin darf maximal 24 Stunden dauern.'],422);
    $allDay=!empty($data['all_day'])?1:0;$address=trim((string)($data['address']??''));if($address===''&&$customer)$address=trim(implode(', ',array_filter([(string)$customer['street'],trim((string)$customer['zip'].' '.(string)$customer['city'])])));if($location==='visit'&&$address==='')out(['ok'=>false,'error'=>'Für einen Vor-Ort-Termin ist eine Adresse erforderlich.'],422);
    $meetingUrl=trim((string)($data['meeting_url']??''));if($meetingUrl!==''&&!preg_match('~^https?://~i',$meetingUrl))out(['ok'=>false,'error'=>'Der Online-Meeting-Link muss mit http:// oder https:// beginnen.'],422);
    $contact=trim((string)($data['contact_name']??''));if($contact===''&&$customer)$contact=(string)$customer['name'];$phone=trim((string)($data['phone']??''));if($phone===''&&$customer)$phone=(string)$customer['phone'];$email=strtolower(trim((string)($data['email']??'')));if($email===''&&$customer)$email=strtolower(trim((string)$customer['email']));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'E-Mail-Adresse ist ungültig.'],422);
    $priority=in_array((string)($data['priority']??'normal'),['low','normal','high','urgent'],true)?(string)$data['priority']:'normal';$bufBefore=max(0,min(240,(int)($data['buffer_before_minutes']??0)));$bufAfter=max(0,min(240,(int)($data['buffer_after_minutes']??0)));$rem=max(0,min(10080,(int)($data['reminder_minutes']??60)));$rem2=max(0,min(20160,(int)($data['reminder_2_minutes']??0)));$channels=rh24_appointment_reminder_channel_labels();$remChannel=(string)($data['reminder_channel']??'inapp');if(!isset($channels[$remChannel]))$remChannel='inapp';
    $recurrences=rh24_appointment_recurrence_labels();$recurrence=(string)($data['recurrence_rule']??($existing['recurrence_rule']??'none'));if(!isset($recurrences[$recurrence]))$recurrence='none';$recurrenceUntil=trim((string)($data['recurrence_until']??($existing['recurrence_until']??'')));if($recurrence!=='none'&&$recurrenceUntil==='')$recurrenceUntil=date('Y-m-d',strtotime('+3 months',$st));if($recurrenceUntil!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$recurrenceUntil))out(['ok'=>false,'error'=>'Serienende ist ungültig.'],422);$seriesId=(!$existing&&$recurrence!=='none')?$id:(($existing['series_id']??'')?:null);
    $color=in_array((string)($data['color']??'brown'),['brown','orange','green','blue','purple','red','gray'],true)?(string)$data['color']:'brown';$tags=$data['tags']??[];if(is_string($tags))$tags=preg_split('/[,;]+/', $tags)?:[];if(!is_array($tags))$tags=[];$tags=array_values(array_slice(array_filter(array_map(fn($x)=>trim((string)$x),$tags)),0,12));
    $notes=trim((string)($data['notes']??''));$outcome=trim((string)($data['outcome']??''));$nextAction=trim((string)($data['next_action']??''));$follow=trim((string)($data['follow_up_at']??''));if($follow!==''){$follow=str_replace('T',' ',$follow);if(strlen($follow)===16)$follow.=':00';if(!strtotime($follow))out(['ok'=>false,'error'=>'Wiedervorlage ist ungültig.'],422);}else$follow=null;$conflict=rh24_appointment_conflict($repId,date('Y-m-d H:i:s',$st),date('Y-m-d H:i:s',$et),$id,$bufBefore,$bufAfter);
    $source=$customerId!==''?'customer':'manual';$startSql=date('Y-m-d H:i:s',$st);$endSql=date('Y-m-d H:i:s',$et);$resetReminders=$existing&&((string)($existing['starts_at']??'')!==$startSql||(string)($existing['email']??'')!==$email||(int)($existing['reminder_minutes']??0)!==$rem||(int)($existing['reminder_2_minutes']??0)!==$rem2||(string)($existing['reminder_channel']??'inapp')!==$remChannel);
    if($existing){$db->prepare("UPDATE advisor_appointments SET customer_id=?,title=?,appointment_type=?,status=?,starts_at=?,ends_at=?,all_day=?,location_mode=?,meeting_url=?,address=?,contact_name=?,phone=?,email=?,priority=?,buffer_before_minutes=?,buffer_after_minutes=?,reminder_minutes=?,reminder_2_minutes=?,reminder_channel=?,notes=?,outcome=?,next_action=?,follow_up_at=?,source=?,series_id=?,recurrence_rule=?,recurrence_until=?,color=?,tags_json=?,reminder_1_sent_at=?,reminder_2_sent_at=?,last_reminded_at=?,completed_at=CASE WHEN ?='completed' THEN COALESCE(completed_at,NOW()) ELSE completed_at END,confirmed_at=CASE WHEN ?='confirmed' THEN COALESCE(confirmed_at,NOW()) ELSE confirmed_at END,updated_at=NOW() WHERE id=?")->execute([$customerId===''?null:$customerId,$title,$type,$status,$startSql,$endSql,$allDay,$location,$meetingUrl,$address,$contact,$phone,$email,$priority,$bufBefore,$bufAfter,$rem,$rem2,$remChannel,$notes,$outcome,$nextAction,$follow,$source,$seriesId,$recurrence,$recurrenceUntil?:null,$color,rh24_json_encode($tags),$resetReminders?null:($existing['reminder_1_sent_at']??null),$resetReminders?null:($existing['reminder_2_sent_at']??null),$resetReminders?null:($existing['last_reminded_at']??null),$status,$status,$id]);}
    else{$db->prepare("INSERT INTO advisor_appointments(id,sales_rep_id,customer_id,title,appointment_type,status,starts_at,ends_at,all_day,location_mode,meeting_url,address,contact_name,phone,email,priority,buffer_before_minutes,buffer_after_minutes,reminder_minutes,reminder_2_minutes,reminder_channel,notes,outcome,next_action,follow_up_at,source,series_id,recurrence_rule,recurrence_until,color,tags_json,completed_at,confirmed_at,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CASE WHEN ?='completed' THEN NOW() ELSE NULL END,CASE WHEN ?='confirmed' THEN NOW() ELSE NULL END,?,NOW(),NOW())")->execute([$id,$repId,$customerId===''?null:$customerId,$title,$type,$status,date('Y-m-d H:i:s',$st),date('Y-m-d H:i:s',$et),$allDay,$location,$meetingUrl,$address,$contact,$phone,$email,$priority,$bufBefore,$bufAfter,$rem,$rem2,$remChannel,$notes,$outcome,$nextAction,$follow,$source,$seriesId,$recurrence,$recurrenceUntil?:null,$color,rh24_json_encode($tags),$status,$status,rh24_user_id()]);}
    $attendees=is_array($data['attendees']??null)?$data['attendees']:[];$tasks=is_array($data['tasks']??null)?$data['tasks']:[];rh24_appointment_save_related($id,$attendees,$tasks);$seriesCount=(!$existing&&$recurrence!=='none')?rh24_appointment_expand_series($id):0;
    rh24_audit('appointment_save','appointment',$id,['sales_rep_id'=>$repId,'customer_id'=>$customerId,'starts_at'=>date('Y-m-d H:i:s',$st),'status'=>$status,'series_count'=>$seriesCount]);$period=date('Y-m',$st);out(['ok'=>true,'appointments'=>rh24_appointment_data($period,$repId),'series_count'=>$seriesCount,'warning'=>$conflict?('Zeitüberschneidung inkl. Puffer mit „'.(string)$conflict['title'].'“ um '.substr((string)$conflict['starts_at'],11,5).' Uhr.'):null]);
  }
  if($action==='appointment_status'){
    if(!rh24_can('edit_appointments'))out(['ok'=>false,'error'=>'Keine Berechtigung zum Ändern des Terminstatus.'],403);$id=trim((string)($data['id']??''));$status=(string)($data['status']??'');$labels=rh24_appointment_status_labels();if(!isset($labels[$status]))out(['ok'=>false,'error'=>'Ungültiger Terminstatus.'],422);$q=$db->prepare('SELECT * FROM advisor_appointments WHERE id=?');$q->execute([$id]);$r=$q->fetch();if(!$r)out(['ok'=>false,'error'=>'Termin nicht gefunden.'],404);rh24_appointment_assert_owned_rep((string)$r['sales_rep_id']);$reason=trim((string)($data['reason']??''));$db->prepare("UPDATE advisor_appointments SET status=?,completed_at=CASE WHEN ?='completed' THEN COALESCE(completed_at,NOW()) ELSE completed_at END,confirmed_at=CASE WHEN ?='confirmed' THEN COALESCE(confirmed_at,NOW()) ELSE confirmed_at END,cancellation_reason=CASE WHEN ?='cancelled' THEN ? ELSE cancellation_reason END,updated_at=NOW() WHERE id=?")->execute([$status,$status,$status,$status,$reason,$id]);rh24_audit('appointment_status','appointment',$id,['status'=>$status,'reason'=>$reason]);out(['ok'=>true,'appointments'=>rh24_appointment_data(substr((string)$r['starts_at'],0,7),(string)$r['sales_rep_id'])]);
  }
  if($action==='appointment_delete'){
    if(!rh24_can('edit_appointments'))out(['ok'=>false,'error'=>'Keine Berechtigung zum Löschen von Terminen.'],403);$id=trim((string)($data['id']??''));$q=$db->prepare('SELECT * FROM advisor_appointments WHERE id=?');$q->execute([$id]);$r=$q->fetch();if(!$r)out(['ok'=>false,'error'=>'Termin nicht gefunden.'],404);rh24_appointment_assert_owned_rep((string)$r['sales_rep_id']);if(in_array((string)$r['status'],['completed'],true))out(['ok'=>false,'error'=>'Erledigte Termine werden aus Nachweisgründen nicht gelöscht. Bitte im Termin belassen.'],409);$db->prepare('DELETE FROM advisor_appointment_attendees WHERE appointment_id=?')->execute([$id]);$db->prepare('DELETE FROM advisor_appointment_tasks WHERE appointment_id=?')->execute([$id]);$db->prepare('DELETE FROM advisor_appointments WHERE id=?')->execute([$id]);rh24_audit('appointment_delete','appointment',$id,[]);out(['ok'=>true,'appointments'=>rh24_appointment_data(substr((string)$r['starts_at'],0,7),(string)$r['sales_rep_id'])]);
  }
  if($action==='appointment_task_toggle'){
    $id=trim((string)($data['id']??''));$q=$db->prepare("SELECT t.*,a.sales_rep_id,a.starts_at FROM advisor_appointment_tasks t JOIN advisor_appointments a ON a.id=t.appointment_id WHERE t.id=?");$q->execute([$id]);$t=$q->fetch();if(!$t)out(['ok'=>false,'error'=>'Aufgabe nicht gefunden.'],404);rh24_appointment_assert_owned_rep((string)$t['sales_rep_id']);$done=!empty($data['is_done'])?1:0;$db->prepare('UPDATE advisor_appointment_tasks SET is_done=?,updated_at=NOW() WHERE id=?')->execute([$done,$id]);rh24_audit('appointment_task_toggle','appointment',(string)$t['appointment_id'],['task_id'=>$id,'done'=>$done]);out(['ok'=>true,'appointments'=>rh24_appointment_data(substr((string)$t['starts_at'],0,7),(string)$t['sales_rep_id'])]);
  }
  if($action==='appointment_move'){
    $id=trim((string)($data['id']??''));$target=trim((string)($data['date']??''));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$target))out(['ok'=>false,'error'=>'Zieldatum ungültig.'],422);$q=$db->prepare('SELECT * FROM advisor_appointments WHERE id=?');$q->execute([$id]);$a=$q->fetch();if(!$a)out(['ok'=>false,'error'=>'Termin nicht gefunden.'],404);rh24_appointment_assert_owned_rep((string)$a['sales_rep_id']);$startTs=strtotime((string)$a['starts_at']);$endTs=strtotime((string)$a['ends_at']);$start=date('Y-m-d H:i:s',strtotime($target.' '.date('H:i:s',$startTs)));$end=date('Y-m-d H:i:s',strtotime($start)+max(60,$endTs-$startTs));$conf=rh24_appointment_conflict((string)$a['sales_rep_id'],$start,$end,$id,(int)($a['buffer_before_minutes']??0),(int)($a['buffer_after_minutes']??0));$db->prepare('UPDATE advisor_appointments SET starts_at=?,ends_at=?,reminder_1_sent_at=NULL,reminder_2_sent_at=NULL,last_reminded_at=NULL,updated_at=NOW() WHERE id=?')->execute([$start,$end,$id]);rh24_audit('appointment_move','appointment',$id,['to'=>$start]);out(['ok'=>true,'appointments'=>rh24_appointment_data(substr($start,0,7),(string)$a['sales_rep_id']),'warning'=>$conf?('Überschneidung mit „'.$conf['title'].'“.'):null]);
  }
  if($action==='appointment_duplicate'){
    $id=trim((string)($data['id']??''));$q=$db->prepare('SELECT * FROM advisor_appointments WHERE id=?');$q->execute([$id]);$a=$q->fetch();if(!$a)out(['ok'=>false,'error'=>'Termin nicht gefunden.'],404);rh24_appointment_assert_owned_rep((string)$a['sales_rep_id']);$nid=rh24_random_id('APT-');$start=date('Y-m-d H:i:s',strtotime((string)$a['starts_at'].' +7 days'));$end=date('Y-m-d H:i:s',strtotime((string)$a['ends_at'].' +7 days'));$db->prepare("INSERT INTO advisor_appointments(id,sales_rep_id,customer_id,title,appointment_type,status,starts_at,ends_at,all_day,location_mode,meeting_url,address,contact_name,phone,email,priority,buffer_before_minutes,buffer_after_minutes,reminder_minutes,reminder_2_minutes,reminder_channel,notes,outcome,next_action,follow_up_at,source,series_id,recurrence_rule,recurrence_until,color,tags_json,created_by,created_at,updated_at) VALUES(?,?,?,?,?,'scheduled',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'','',NULL,?,NULL,'none',NULL,?,?,?,NOW(),NOW())")->execute([$nid,$a['sales_rep_id'],$a['customer_id'],'Kopie · '.$a['title'],$a['appointment_type'],$start,$end,(int)($a['all_day']??0),$a['location_mode'],$a['meeting_url']??'',$a['address'],$a['contact_name'],$a['phone'],$a['email'],$a['priority'],(int)($a['buffer_before_minutes']??0),(int)($a['buffer_after_minutes']??0),(int)$a['reminder_minutes'],(int)($a['reminder_2_minutes']??0),$a['reminder_channel']??'inapp',$a['notes'],$a['source'],$a['color']??'brown',$a['tags_json']??'[]',rh24_user_id()]);$rel=rh24_appointment_related([$id]);rh24_appointment_save_related($nid,$rel['attendees'][$id]??[],$rel['tasks'][$id]??[]);rh24_audit('appointment_duplicate','appointment',$nid,['source_id'=>$id]);out(['ok'=>true,'appointments'=>rh24_appointment_data(substr($start,0,7),(string)$a['sales_rep_id']),'id'=>$nid]);
  }
  if($action==='appointment_template_save'){
    $rep=rh24_appointment_rep_scope((string)($data['sales_rep_id']??''));$repId=(string)($rep['id']??'');rh24_appointment_assert_owned_rep($repId);$name=trim((string)($data['name']??''));if($name==='')out(['ok'=>false,'error'=>'Vorlagenname fehlt.'],422);$id=rh24_random_id('ATP-');$type=(string)($data['appointment_type']??'consultation');$loc=(string)($data['location_mode']??'visit');$priority=(string)($data['priority']??'normal');$db->prepare("INSERT INTO advisor_appointment_templates(id,sales_rep_id,name,appointment_type,duration_minutes,location_mode,priority,reminder_minutes,reminder_2_minutes,buffer_before_minutes,buffer_after_minutes,notes,active,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,1,?,NOW(),NOW())")->execute([$id,$repId,$name,$type,max(15,min(720,(int)($data['duration_minutes']??60))),$loc,$priority,max(0,(int)($data['reminder_minutes']??60)),max(0,(int)($data['reminder_2_minutes']??0)),max(0,(int)($data['buffer_before_minutes']??0)),max(0,(int)($data['buffer_after_minutes']??0)),trim((string)($data['notes']??'')),rh24_user_id()]);rh24_audit('appointment_template_save','appointment_template',$id,['name'=>$name]);out(['ok'=>true,'appointments'=>rh24_appointment_data((string)($data['period']??date('Y-m')),$repId)]);
  }
  if($action==='appointment_template_delete'){
    $id=trim((string)($data['id']??''));$q=$db->prepare('SELECT * FROM advisor_appointment_templates WHERE id=?');$q->execute([$id]);$t=$q->fetch();if(!$t)out(['ok'=>false,'error'=>'Vorlage nicht gefunden.'],404);if(!rh24_is_admin()&&(string)$t['sales_rep_id']!==rh24_user_sales_rep_id())out(['ok'=>false,'error'=>'Keine Berechtigung für diese Vorlage.'],403);$db->prepare('UPDATE advisor_appointment_templates SET active=0,updated_at=NOW() WHERE id=?')->execute([$id]);out(['ok'=>true,'appointments'=>rh24_appointment_data((string)($data['period']??date('Y-m')),(string)$t['sales_rep_id'])]);
  }
  if($action==='appointment_email_invite'){
    $id=trim((string)($data['id']??''));$q=$db->prepare('SELECT * FROM advisor_appointments WHERE id=?');$q->execute([$id]);$a=$q->fetch();if(!$a)out(['ok'=>false,'error'=>'Termin nicht gefunden.'],404);rh24_appointment_assert_owned_rep((string)$a['sales_rep_id']);$to=strtolower(trim((string)($data['email']??$a['email']??'')));if(!filter_var($to,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'Für die Einladung ist eine gültige E-Mail-Adresse erforderlich.'],422);$when=date('d.m.Y H:i',strtotime((string)$a['starts_at']));$html='<p>Guten Tag '.htmlspecialchars((string)($a['contact_name']?:'')).',</p><p>anbei erhalten Sie die Kalendereinladung für <b>'.htmlspecialchars((string)$a['title']).'</b>.</p><p><b>Termin:</b> '.$when.' Uhr</p>'.(!empty($a['address'])?'<p><b>Ort:</b> '.htmlspecialchars((string)$a['address']).'</p>':'').(!empty($a['meeting_url'])?'<p><b>Online-Meeting:</b> '.htmlspecialchars((string)$a['meeting_url']).'</p>':'').'<p>Räucherhaken24</p>';$ok=rh24_send_mail_attachments($to,'Termineinladung · '.$a['title'],$html,[['name'=>'Termin-'.$id.'.ics','data'=>rh24_appointment_ics_content($a),'mime'=>'text/calendar; charset=utf-8']],'appointment_invite');if($ok)$db->prepare("UPDATE advisor_appointments SET confirmation_source='email_invite',updated_at=NOW() WHERE id=?")->execute([$id]);rh24_audit('appointment_email_invite','appointment',$id,['recipient'=>$to,'status'=>$ok?'sent':'failed']);out(['ok'=>$ok,'error'=>$ok?null:'E-Mail konnte vom Server nicht versendet werden.']);
  }
  if($action==='triplog_get'){
    if(!rh24_can('view_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung für das Fahrtenbuch.'],403);$period=trim((string)($data['period']??date('Y-m')));$rep=trim((string)($data['sales_rep_id']??''));out(['ok'=>true,'triplog'=>rh24_triplog_data($period,$rep)]);
  }
  if($action==='trip_maps_config_save'){
    rh24_require_admin();$key=trim((string)($data['api_key']??''));$old=rh24_google_routes_secret();if($key!=='')$old['api_key']=$key;if(empty($old['api_key']))out(['ok'=>false,'error'=>'Bitte einen Google Maps Platform API-Key eingeben.'],422);rh24_setting_set('google_routes_credentials',rh24_encrypt_secret($old));rh24_audit('trip_maps_config_save','settings','google_routes',['configured'=>true]);out(['ok'=>true,'maps'=>rh24_google_routes_config()]);
  }
  if($action==='vehicle_key_lookup'){
    if(!rh24_can('view_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung für Fahrzeugdaten.'],403);try{$lookup=rh24_vehicle_lookup((string)($data['hsn']??''),(string)($data['tsn']??''),!empty($data['force_live']));out(['ok'=>true,'lookup'=>$lookup]);}catch(InvalidArgumentException $e){out(['ok'=>false,'error'=>$e->getMessage()],422);}catch(Throwable $e){out(['ok'=>false,'error'=>$e->getMessage(),'config'=>rh24_vehicle_lookup_config()],502);}
  }
  if($action==='vehicle_lookup_config_save'){
    rh24_require_admin();try{$cfg=rh24_vehicle_lookup_config_save((string)($data['api_key']??''),(string)($data['api_secret']??''));out(['ok'=>true,'config'=>$cfg]);}catch(Throwable $e){out(['ok'=>false,'error'=>$e->getMessage()],422);}
  }
  if($action==='vehicle_lookup_test'){
    rh24_require_admin();try{$result=rh24_vehicle_lookup_test((string)($data['hsn']??'0603'),(string)($data['tsn']??'COB'));out(['ok'=>true,'test'=>$result,'config'=>$result['config']]);}catch(InvalidArgumentException $e){out(['ok'=>false,'error'=>$e->getMessage()],422);}catch(Throwable $e){out(['ok'=>false,'error'=>$e->getMessage(),'config'=>rh24_vehicle_lookup_config()],502);}
  }
  if($action==='trip_vehicle_save'){
    if(!rh24_can('edit_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung zum Bearbeiten des Fahrtenbuchs.'],403);$scope=rh24_triplog_rep_scope((string)($data['sales_rep_id']??''));$repId=(string)($scope['id']??'');if($repId==='')out(['ok'=>false,'error'=>'Kein Kundenberater ausgewählt.'],422);rh24_triplog_assert_owned_rep($repId);$id=trim((string)($data['id']??''));if($id!==''){$cq=$db->prepare('SELECT sales_rep_id FROM trip_vehicles WHERE id=?');$cq->execute([$id]);$owner=(string)($cq->fetchColumn()?:'');if($owner!==''&&$owner!==$repId)out(['ok'=>false,'error'=>'Fahrzeug gehört zu einem anderen Kundenberater.'],403);}else$id=rh24_random_id('CAR-');
    $type=rh24_vehicle_type_value($data['vehicle_type']??'car');$manufacturer=trim((string)($data['manufacturer']??''));$model=trim((string)($data['model']??''));$label=trim((string)($data['label']??''));if($label==='')$label=trim($manufacturer.' '.$model);if($label==='')out(['ok'=>false,'error'=>'Bitte eine Fahrzeugbezeichnung oder Hersteller/Modell angeben.'],422);$status=in_array((string)($data['status']??'active'),['active','inactive'],true)?(string)$data['status']:'active';$odo=max(0,(float)($data['odometer_start']??0));$hsn=rh24_vehicle_clean_hsn((string)($data['hsn']??''));$tsn=rh24_vehicle_clean_tsn((string)($data['tsn']??''));if($hsn!==''&&!preg_match('/^\d{4}$/',$hsn))out(['ok'=>false,'error'=>'HSN muss 4-stellig sein.'],422);if($tsn!==''&&!preg_match('/^[A-Z0-9]{3}$/',$tsn))out(['ok'=>false,'error'=>'TSN muss 3-stellig sein.'],422);$vin=strtoupper(trim((string)($data['vin']??'')));if($vin!==''&&!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/',$vin))out(['ok'=>false,'error'=>'FIN/VIN muss 17 Zeichen haben und darf I, O und Q nicht enthalten.'],422);
    $makeModel=trim((string)($data['make_model']??''));if($makeModel==='')$makeModel=trim($manufacturer.' '.$model.' '.trim((string)($data['variant_name']??'')));$vals=[$id,$repId,$type,$label,trim((string)($data['license_plate']??'')),$makeModel,$manufacturer,$model,trim((string)($data['variant_name']??'')),trim((string)($data['engine_name']??'')),$hsn,$tsn,$vin,rh24_vehicle_date_or_null($data['first_registration']??''),trim((string)($data['fuel_type']??'')),($data['power_kw']??'')===''?null:max(0,(float)$data['power_kw']),($data['displacement_cc']??'')===''?null:max(0,(int)$data['displacement_cc']),trim((string)($data['transmission']??'')),trim((string)($data['color']??'')),trim((string)($data['owner_name']??'')),trim((string)($data['insurance_company']??'')),trim((string)($data['insurance_policy_no']??'')),rh24_vehicle_date_or_null($data['hu_due']??''),rh24_vehicle_date_or_null($data['au_due']??''),rh24_vehicle_date_or_null($data['service_due']??''),trim((string)($data['cost_center']??'')),rh24_vehicle_usage_value($data['usage_type']??'mixed'),trim((string)($data['notes']??'')),trim((string)($data['lookup_source']??'')),trim((string)($data['base_address']??'')),$odo,$status,rh24_user_id()];
    $db->prepare("INSERT INTO trip_vehicles(id,sales_rep_id,vehicle_type,label,license_plate,make_model,manufacturer,model,variant_name,engine_name,hsn,tsn,vin,first_registration,fuel_type,power_kw,displacement_cc,transmission,color,owner_name,insurance_company,insurance_policy_no,hu_due,au_due,service_due,cost_center,usage_type,notes,lookup_source,base_address,odometer_start,status,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE vehicle_type=VALUES(vehicle_type),label=VALUES(label),license_plate=VALUES(license_plate),make_model=VALUES(make_model),manufacturer=VALUES(manufacturer),model=VALUES(model),variant_name=VALUES(variant_name),engine_name=VALUES(engine_name),hsn=VALUES(hsn),tsn=VALUES(tsn),vin=VALUES(vin),first_registration=VALUES(first_registration),fuel_type=VALUES(fuel_type),power_kw=VALUES(power_kw),displacement_cc=VALUES(displacement_cc),transmission=VALUES(transmission),color=VALUES(color),owner_name=VALUES(owner_name),insurance_company=VALUES(insurance_company),insurance_policy_no=VALUES(insurance_policy_no),hu_due=VALUES(hu_due),au_due=VALUES(au_due),service_due=VALUES(service_due),cost_center=VALUES(cost_center),usage_type=VALUES(usage_type),notes=VALUES(notes),lookup_source=VALUES(lookup_source),base_address=VALUES(base_address),odometer_start=VALUES(odometer_start),status=VALUES(status),updated_at=NOW()")
      ->execute($vals);rh24_audit('trip_vehicle_save','trip_vehicle',$id,['sales_rep_id'=>$repId,'status'=>$status,'vehicle_type'=>$type,'hsn'=>$hsn,'tsn'=>$tsn,'lookup_source'=>$vals[28]]);out(['ok'=>true,'triplog'=>rh24_triplog_data((string)($data['period']??date('Y-m')),$repId)]);
  }
  if($action==='trip_save'){
    if(!rh24_can('edit_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung zum Bearbeiten des Fahrtenbuchs.'],403);$scope=rh24_triplog_rep_scope((string)($data['sales_rep_id']??''));$repId=(string)($scope['id']??'');if($repId==='')out(['ok'=>false,'error'=>'Kein Kundenberater ausgewählt.'],422);rh24_triplog_assert_owned_rep($repId);$id=trim((string)($data['id']??''));$existing=null;if($id!==''){$q=$db->prepare('SELECT * FROM trip_log WHERE id=?');$q->execute([$id]);$existing=$q->fetch();if(!$existing)out(['ok'=>false,'error'=>'Fahrt nicht gefunden.'],404);if((string)$existing['sales_rep_id']!==$repId)out(['ok'=>false,'error'=>'Fahrt gehört zu einem anderen Kundenberater.'],403);if((string)$existing['status']==='finalized')out(['ok'=>false,'error'=>'Abgeschlossene Fahrten können nur über „Korrektur“ geändert werden.'],409);}else$id=rh24_random_id('TRIP-');
    $vehicleId=trim((string)($data['vehicle_id']??''));$vq=$db->prepare('SELECT id FROM trip_vehicles WHERE id=? AND sales_rep_id=?');$vq->execute([$vehicleId,$repId]);if(!$vq->fetchColumn())out(['ok'=>false,'error'=>'Bitte ein gültiges Fahrzeug auswählen.'],422);$date=trim((string)($data['trip_date']??''));if(!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$date))out(['ok'=>false,'error'=>'Fahrtdatum ist ungültig.'],422);$startAddress=trim((string)($data['start_address']??''));$dest=trim((string)($data['destination_address']??''));if($startAddress===''||$dest==='')out(['ok'=>false,'error'=>'Start- und Zieladresse sind erforderlich.'],422);$type=in_array((string)($data['trip_type']??'business'),['business','private','commute'],true)?(string)$data['trip_type']:'business';$purpose=trim((string)($data['purpose']??''));$partner=trim((string)($data['business_partner']??''));if($type==='business'&&($purpose===''||$partner===''))out(['ok'=>false,'error'=>'Bei geschäftlichen Fahrten sind Reisezweck und Geschäftspartner erforderlich.'],422);$startO=(float)($data['start_odometer']??0);$endO=(float)($data['end_odometer']??0);if($startO<0||$endO<$startO)out(['ok'=>false,'error'=>'Kilometerstände sind nicht plausibel.'],422);$last=rh24_triplog_last_odometer($vehicleId,$existing?(string)$existing['id']:null);if(!$existing&&$last>0&&$startO+0.1<$last)out(['ok'=>false,'error'=>'Startkilometerstand liegt unter dem letzten erfassten Kilometerstand ('.number_format($last,1,',','.').' km).'],409);$distance=round($endO-$startO,1);
    $vals=[$repId,$vehicleId,$date,($data['start_time']??'')?:null,($data['end_time']??'')?:null,$type,$startAddress,$dest,$purpose,$partner,$startO,$endO,$distance,trim((string)($data['detour_reason']??'')),trim((string)($data['appointment_type']??'')),trim((string)($data['appointment_ref']??'')),rh24_user_id()];
    if($existing){$db->prepare("UPDATE trip_log SET vehicle_id=?,trip_date=?,start_time=?,end_time=?,trip_type=?,start_address=?,destination_address=?,purpose=?,business_partner=?,start_odometer=?,end_odometer=?,distance_km=?,detour_reason=?,appointment_type=?,appointment_ref=?,updated_at=NOW() WHERE id=?")->execute([$vehicleId,$date,$vals[3],$vals[4],$type,$startAddress,$dest,$purpose,$partner,$startO,$endO,$distance,$vals[13],$vals[14],$vals[15],$id]);}
    else{$db->prepare("INSERT INTO trip_log(id,sales_rep_id,vehicle_id,trip_date,start_time,end_time,trip_type,start_address,destination_address,purpose,business_partner,start_odometer,end_odometer,distance_km,detour_reason,appointment_type,appointment_ref,status,created_by,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'draft',?,NOW(),NOW())")->execute([$id,$repId,$vehicleId,$date,$vals[3],$vals[4],$type,$startAddress,$dest,$purpose,$partner,$startO,$endO,$distance,$vals[13],$vals[14],$vals[15],rh24_user_id()]);}
    rh24_audit('trip_save','trip',$id,['sales_rep_id'=>$repId,'date'=>$date,'distance_km'=>$distance,'status'=>'draft']);out(['ok'=>true,'triplog'=>rh24_triplog_data(substr($date,0,7),$repId)]);
  }
  if($action==='trip_finalize'){
    if(!rh24_can('edit_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung zum Abschließen des Fahrtenbuchs.'],403);$id=trim((string)($data['id']??''));$q=$db->prepare('SELECT * FROM trip_log WHERE id=?');$q->execute([$id]);$r=$q->fetch();if(!$r)out(['ok'=>false,'error'=>'Fahrt nicht gefunden.'],404);rh24_triplog_assert_owned_rep((string)$r['sales_rep_id']);if((string)$r['status']==='finalized')out(['ok'=>true,'triplog'=>rh24_triplog_data(substr((string)$r['trip_date'],0,7),(string)$r['sales_rep_id'])]);if((string)$r['trip_type']==='business'&&(trim((string)$r['purpose'])===''||trim((string)$r['business_partner'])===''))out(['ok'=>false,'error'=>'Reisezweck und Geschäftspartner müssen vor Abschluss vollständig sein.'],422);$hash=rh24_triplog_row_hash(array_merge($r,['status'=>'finalized','finalized_at'=>date('Y-m-d H:i:s')]));$db->prepare("UPDATE trip_log SET status='finalized',finalized_at=NOW(),row_hash=?,updated_at=NOW() WHERE id=?")->execute([$hash,$id]);rh24_audit('trip_finalize','trip',$id,['hash'=>$hash]);out(['ok'=>true,'triplog'=>rh24_triplog_data(substr((string)$r['trip_date'],0,7),(string)$r['sales_rep_id'])]);
  }
  if($action==='trip_correct'){
    if(!rh24_can('edit_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung zur Fahrtenbuchkorrektur.'],403);$id=trim((string)($data['id']??''));$reason=trim((string)($data['reason']??''));if(mb_strlen($reason)<5)out(['ok'=>false,'error'=>'Für eine nachträgliche Korrektur ist ein nachvollziehbarer Korrekturgrund erforderlich.'],422);$q=$db->prepare('SELECT * FROM trip_log WHERE id=?');$q->execute([$id]);$before=$q->fetch();if(!$before)out(['ok'=>false,'error'=>'Fahrt nicht gefunden.'],404);rh24_triplog_assert_owned_rep((string)$before['sales_rep_id']);if((string)$before['status']!=='finalized')out(['ok'=>false,'error'=>'Nur abgeschlossene Fahrten benötigen eine protokollierte Korrektur.'],409);$allowed=['start_time','end_time','trip_type','start_address','destination_address','purpose','business_partner','start_odometer','end_odometer','detour_reason'];$after=$before;foreach($allowed as $f)if(array_key_exists($f,$data))$after[$f]=is_string($data[$f])?trim((string)$data[$f]):$data[$f];$after['start_odometer']=(float)$after['start_odometer'];$after['end_odometer']=(float)$after['end_odometer'];if($after['end_odometer']<$after['start_odometer'])out(['ok'=>false,'error'=>'Kilometerstände sind nicht plausibel.'],422);$after['distance_km']=round($after['end_odometer']-$after['start_odometer'],1);if($after['trip_type']==='business'&&(trim((string)$after['purpose'])===''||trim((string)$after['business_partner'])===''))out(['ok'=>false,'error'=>'Geschäftliche Fahrten benötigen Reisezweck und Geschäftspartner.'],422);$after['row_hash']=rh24_triplog_row_hash($after);$vq=$db->prepare('SELECT COALESCE(MAX(version_no),0)+1 FROM trip_log_revisions WHERE trip_id=?');$vq->execute([$id]);$version=(int)$vq->fetchColumn();$db->beginTransaction();try{$db->prepare("INSERT INTO trip_log_revisions(trip_id,version_no,change_reason,before_json,after_json,changed_by,changed_at) VALUES(?,?,?,?,?,?,NOW())")->execute([$id,$version,$reason,rh24_json_encode($before),rh24_json_encode($after),rh24_user_id()]);$db->prepare("UPDATE trip_log SET start_time=?,end_time=?,trip_type=?,start_address=?,destination_address=?,purpose=?,business_partner=?,start_odometer=?,end_odometer=?,distance_km=?,detour_reason=?,row_hash=?,updated_at=NOW() WHERE id=?")->execute([$after['start_time']?:null,$after['end_time']?:null,$after['trip_type'],$after['start_address'],$after['destination_address'],$after['purpose'],$after['business_partner'],$after['start_odometer'],$after['end_odometer'],$after['distance_km'],$after['detour_reason'],$after['row_hash'],$id]);$db->commit();}catch(Throwable $e){$db->rollBack();throw $e;}rh24_audit('trip_correct','trip',$id,['version'=>$version,'reason'=>$reason]);out(['ok'=>true,'triplog'=>rh24_triplog_data(substr((string)$before['trip_date'],0,7),(string)$before['sales_rep_id'])]);
  }
  if($action==='trip_delete'){
    if(!rh24_can('edit_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung.'],403);$id=trim((string)($data['id']??''));$q=$db->prepare('SELECT * FROM trip_log WHERE id=?');$q->execute([$id]);$r=$q->fetch();if(!$r)out(['ok'=>false,'error'=>'Fahrt nicht gefunden.'],404);rh24_triplog_assert_owned_rep((string)$r['sales_rep_id']);if((string)$r['status']!=='draft')out(['ok'=>false,'error'=>'Abgeschlossene Fahrten dürfen nicht gelöscht werden.'],409);$db->prepare('DELETE FROM trip_log WHERE id=?')->execute([$id]);rh24_audit('trip_delete','trip',$id,['status'=>'draft']);out(['ok'=>true,'triplog'=>rh24_triplog_data(substr((string)$r['trip_date'],0,7),(string)$r['sales_rep_id'])]);
  }
  if($action==='trip_route_optimize'){
    if(!rh24_can('view_triplog'))out(['ok'=>false,'error'=>'Keine Berechtigung für die Routenplanung.'],403);$origin=trim((string)($data['origin']??''));$destination=trim((string)($data['destination']??''));$stops=is_array($data['stops']??null)?array_values(array_filter($data['stops'],fn($x)=>is_array($x)&&trim((string)($x['address']??''))!=='')):[];if($origin===''||$destination===''||!$stops)out(['ok'=>false,'error'=>'Start, Ziel und mindestens ein Termin sind erforderlich.'],422);if(count($stops)>20)out(['ok'=>false,'error'=>'Maximal 20 Zwischenstopps pro Planung.'],422);$opt=(bool)($data['optimize']??false);$route=rh24_google_routes_compute($origin,$destination,$stops,$opt);$ordered=[];foreach($route['order'] as $i)if(isset($stops[$i]))$ordered[]=$stops[$i];$route['stops']=$ordered;$route['google_maps_url']='https://www.google.com/maps/dir/?api=1&origin='.rawurlencode($origin).'&destination='.rawurlencode($destination).'&travelmode=driving&waypoints='.rawurlencode(implode('|',array_map(fn($s)=>(string)$s['address'],$ordered)));out(['ok'=>true,'route'=>$route]);
  }
  if($action==='market_listing_moderate'){
    rh24_require_admin();$id=trim((string)($data['id']??''));$status=(string)($data['status']??'published');if(!in_array($status,['published','rejected','paused'],true))out(['ok'=>false,'error'=>'Ungültiger Marktplatzstatus'],422);
    $q=$db->prepare("SELECT l.*,u.email seller_email,u.display_name seller_name FROM market_listings l LEFT JOIN market_users u ON u.id=l.user_id WHERE l.id=?");$q->execute([$id]);$l=$q->fetch();if(!$l)out(['ok'=>false,'error'=>'Anzeige nicht gefunden'],404);
    if($status==='published'){
      if(!in_array((string)($l['category']??''),rh24_marketplace_allowed_categories(),true))out(['ok'=>false,'error'=>'Freigabe gesperrt: Kategorie ist auf An- & Verkaufen nicht zugelassen.'],422);
      if(rh24_marketplace_food_violation((string)($l['title']??'').' '.(string)($l['description']??'')))out(['ok'=>false,'error'=>'Freigabe gesperrt: Die Anzeige enthält Hinweise auf Lebensmittel/verzehrbare Ware.'],422);
    }
    $db->prepare('UPDATE market_listings SET status=?,updated_at=NOW() WHERE id=?')->execute([$status,$id]);$label=$status==='published'?'freigegeben':($status==='rejected'?'abgelehnt':'pausiert');
    if(!empty($l['seller_email']))rh24_send_system_mail((string)$l['seller_email'],'Ihre Marktplatz-Anzeige wurde '.$label,'<p>Hallo '.htmlspecialchars((string)$l['seller_name']).',</p><p>Ihre Anzeige <b>'.htmlspecialchars((string)$l['title']).'</b> wurde '.$label.'.</p><p>Räucherhaken24 An- &amp; Verkaufen</p>','marketplace');
    rh24_audit('market_listing_moderate','market_listing',$id,['status'=>$status]);out(['ok'=>true,'marketplace'=>rh24_marketplace_admin_data()]);
  }
  if($action==='market_membership_activate'){
    rh24_require_admin();$id=trim((string)($data['user_id']??''));$q=$db->prepare('SELECT * FROM market_users WHERE id=?');$q->execute([$id]);$u=$q->fetch();if(!$u)out(['ok'=>false,'error'=>'Marktplatz-Mitglied nicht gefunden'],404);
    $db->prepare("UPDATE market_users SET membership_status='active',membership_started_at=NOW(),membership_expires_at=DATE_ADD(NOW(),INTERVAL 1 YEAR),updated_at=NOW() WHERE id=?")->execute([$id]);
    if(!empty($u['email']))rh24_send_system_mail((string)$u['email'],'Ihr Jahreszugang ist freigeschaltet','<p>Hallo '.htmlspecialchars((string)$u['display_name']).',</p><p>Ihr Jahreszugang für Räucherhaken24 An- &amp; Verkaufen ist jetzt für 1 Jahr freigeschaltet.</p><p>Sie können gleichzeitig bis zu 10 Anzeigen veröffentlichen.</p>','marketplace');
    rh24_audit('market_membership_activate','market_user',$id,[]);out(['ok'=>true,'marketplace'=>rh24_marketplace_admin_data()]);
  }
  if($action==='market_user_status'){
    rh24_require_admin();$id=trim((string)($data['user_id']??''));$status=(string)($data['status']??'active');if(!in_array($status,['active','banned'],true))out(['ok'=>false,'error'=>'Ungültiger Kontostatus'],422);$db->prepare('UPDATE market_users SET status=?,updated_at=NOW() WHERE id=?')->execute([$status,$id]);if($status==='banned')$db->prepare("UPDATE market_listings SET status='paused',updated_at=NOW() WHERE user_id=? AND status='published'")->execute([$id]);rh24_audit('market_user_status','market_user',$id,['status'=>$status]);out(['ok'=>true,'marketplace'=>rh24_marketplace_admin_data()]);
  }
  if($action==='market_report_close'){
    rh24_require_admin();$id=(int)($data['report_id']??0);$db->prepare("UPDATE market_reports SET status='closed',updated_at=NOW() WHERE id=?")->execute([$id]);rh24_audit('market_report_close','market_report',(string)$id,[]);out(['ok'=>true,'marketplace'=>rh24_marketplace_admin_data()]);
  }
  if($action==='theme_save'){
    rh24_require_admin();$theme=trim((string)($data['theme']??'standard'));$allowed=['standard','weihnachten','nikolaus','ostern','advent','black_week','black_friday','silvester','neujahr'];if(!in_array($theme,$allowed,true))out(['ok'=>false,'error'=>'Unbekanntes Shop-Design'],422);rh24_setting_set('active_theme',$theme);try{$tf=dirname(__DIR__).'/data/active-theme.json';@file_put_contents($tf,json_encode(['theme'=>$theme,'updated_at'=>date('c')],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);}catch(Throwable $e){}rh24_audit('theme_change','settings','active_theme',['theme'=>$theme]);out(['ok'=>true,'theme'=>$theme,'persisted'=>true]);
  }
  if($action==='leaderboard_get'){
    if(!rh24_can('view_leaderboard')) out(['ok'=>false,'error'=>'Keine Berechtigung für die Rangliste.'],403);
    $payload=['ok'=>true,'leaderboard'=>rh24_leaderboard(),'my_star_year'=>rh24_can('view_star_stats')?rh24_star_year_stats(rh24_user_sales_rep_id()):[]];
    out($payload);
  }


  if($action==='dealer_calendar_get'){
    if(!rh24_can('view_dealer_visits')) out(['ok'=>false,'error'=>'Keine Berechtigung für Händlerbesuche.'],403);
    out(['ok'=>true,'dealer_calendar'=>rh24_dealer_route_data()]);
  }
  if($action==='dealer_visit_complete'){
    if(!rh24_can('manage_dealer_visits')) out(['ok'=>false,'error'=>'Keine Berechtigung, Händlerbesuche abzuschließen.'],403);
    $id=trim((string)($data['dealer_id']??''));$note=trim((string)($data['notes']??''));$outcome=trim((string)($data['outcome']??'Besuch durchgeführt'));
    $q=$db->prepare('SELECT * FROM dealers WHERE id=?');$q->execute([$id]);$dealer=$q->fetch();if(!$dealer)out(['ok'=>false,'error'=>'Händler nicht gefunden'],404);
    if(!rh24_is_admin() && (string)($dealer['sales_rep_id']??'')!==rh24_user_sales_rep_id())out(['ok'=>false,'error'=>'Dieser Händler ist dir nicht zugeordnet.'],403);
    $completed=trim((string)($data['completed_at']??''));$ts=$completed!==''?strtotime($completed):time();if($ts===false)$ts=time();$done=date('Y-m-d H:i:s',$ts);$next=date('Y-m-d H:i:s',strtotime('+14 days',$ts));$visitId=rh24_random_id('DV-');
    $db->prepare("INSERT INTO dealer_visits(id,dealer_id,sales_rep_id,planned_at,completed_at,status,outcome,notes,next_visit_at,created_by,created_at,updated_at) VALUES(?,?,?,?,?,'completed',?,?,?,?,NOW(),NOW())")
       ->execute([$visitId,$id,($dealer['sales_rep_id']??null),($dealer['next_visit_at']??null),$done,$outcome,$note,$next,rh24_user_id()]);
    $db->prepare('UPDATE dealers SET last_visit_at=?,next_visit_at=?,last_visit_note=?,visit_interval_days=14,updated_at=NOW() WHERE id=?')->execute([$done,$next,$note,$id]);
    rh24_audit('dealer_visit_completed','dealer',$id,['visit_id'=>$visitId,'next_visit_at'=>$next,'outcome'=>$outcome]);
    out(['ok'=>true,'dealer_calendar'=>rh24_dealer_route_data()]);
  }
  if($action==='dealer_visit_reschedule'){
    if(!rh24_can('manage_dealer_visits')) out(['ok'=>false,'error'=>'Keine Berechtigung, Händlertermine zu verschieben.'],403);
    $id=trim((string)($data['dealer_id']??''));$date=trim((string)($data['next_visit_at']??''));$ts=strtotime($date);if($id===''||$ts===false)out(['ok'=>false,'error'=>'Bitte einen gültigen Termin angeben.'],422);
    $q=$db->prepare('SELECT sales_rep_id FROM dealers WHERE id=?');$q->execute([$id]);$rep=(string)($q->fetchColumn()?:'');if($rep===''&& !$q->rowCount())out(['ok'=>false,'error'=>'Händler nicht gefunden'],404);
    if(!rh24_is_admin() && $rep!==rh24_user_sales_rep_id())out(['ok'=>false,'error'=>'Dieser Händler ist dir nicht zugeordnet.'],403);
    $next=date('Y-m-d H:i:s',$ts);$db->prepare('UPDATE dealers SET next_visit_at=?,updated_at=NOW() WHERE id=?')->execute([$next,$id]);rh24_audit('dealer_visit_rescheduled','dealer',$id,['next_visit_at'=>$next]);
    out(['ok'=>true,'dealer_calendar'=>rh24_dealer_route_data()]);
  }

  if($action==='payment_integration_save'){
    rh24_require_admin();$provider=trim((string)($data['provider']??''));$catalog=rh24_payment_provider_catalog();if(!isset($catalog[$provider]))out(['ok'=>false,'error'=>'Zahlungsanbieter ungültig'],422);$env=(string)($data['environment']??'sandbox');if(!in_array($env,['sandbox','production'],true))$env='sandbox';$enabled=!empty($data['enabled']);$checkout=!empty($data['checkout_enabled']);
    $old=rh24_payment_integration_secret($provider);$incoming=is_array($data['credentials']??null)?$data['credentials']:[];$allowed=array_map(fn($x)=>(string)$x[0],$catalog[$provider]['fields']);foreach($allowed as $k){if(array_key_exists($k,$incoming)){$v=trim((string)$incoming[$k]);if($v!=='')$old[$k]=$v;}}
    $enc=rh24_encrypt_secret($old);$test=[];foreach($catalog[$provider]['required'] as $k)if(trim((string)($old[$k]??''))==='')$test[]=$k;$status=$test?'incomplete':'configured';$message=$test?'Gespeichert, aber noch unvollständig: '.implode(', ',$test):'Zugangsdaten gespeichert. Bitte Konfiguration prüfen und danach Sandbox-End-to-End testen.';
    $db->prepare("INSERT INTO payment_integrations(provider,environment,enabled,checkout_enabled,credentials_enc,status,last_test_at,last_message,updated_at) VALUES(?,?,?,?,?,?,NULL,?,NOW()) ON DUPLICATE KEY UPDATE environment=VALUES(environment),enabled=VALUES(enabled),checkout_enabled=VALUES(checkout_enabled),credentials_enc=VALUES(credentials_enc),status=VALUES(status),last_message=VALUES(last_message),updated_at=NOW()")->execute([$provider,$env,$enabled?1:0,$checkout?1:0,$enc,$status,$message]);rh24_audit('payment_integration_save','payment',$provider,['environment'=>$env,'enabled'=>$enabled,'checkout_enabled'=>$checkout,'fields'=>array_keys(array_filter($old,fn($v)=>trim((string)$v)!==''))]);out(['ok'=>true,'integrations'=>rh24_payment_integrations(false)]);
  }
  if($action==='payment_integration_test'){
    rh24_require_admin();$provider=trim((string)($data['provider']??''));$res=rh24_payment_config_test($provider);$db->prepare('UPDATE payment_integrations SET status=?,last_test_at=NOW(),last_message=?,updated_at=NOW() WHERE provider=?')->execute([$res['ok']?'ready':'incomplete',(string)$res['message'],$provider]);rh24_audit('payment_integration_test','payment',$provider,['ok'=>$res['ok'],'message'=>$res['message']]);out(['ok'=>(bool)$res['ok'],'message'=>$res['message'],'error'=>$res['ok']?'':$res['message'],'integrations'=>rh24_payment_integrations(false)],$res['ok']?200:422);
  }

  if($action==='shipping_integration_save'){
    rh24_require_admin();$carrier=strtoupper(trim((string)($data['carrier']??'')));if(!in_array($carrier,['DHL','DPD'],true))out(['ok'=>false,'error'=>'Versanddienstleister ungültig'],422);
    $env=(string)($data['environment']??'sandbox');if(!in_array($env,['sandbox','production'],true))$env='sandbox';
    $old=rh24_shipping_integration_secret($carrier);$incoming=is_array($data['credentials']??null)?$data['credentials']:[];$allowed=$carrier==='DHL'?['client_id','client_secret','username','password','ekp','billing_number']:['partner_name','partner_token','cloud_user_id','user_token','customer_number'];
    foreach($allowed as $k){if(array_key_exists($k,$incoming)){ $v=trim((string)$incoming[$k]); if($v!=='')$old[$k]=$v; }}
    $enc=rh24_encrypt_secret($old);$db->prepare("INSERT INTO shipping_integrations(carrier,environment,credentials_enc,status,last_test_at,last_message,updated_at) VALUES(?,?,?,'configured',NULL,'Zugangsdaten gespeichert – Verbindung noch nicht geprüft.',NOW()) ON DUPLICATE KEY UPDATE environment=VALUES(environment),credentials_enc=VALUES(credentials_enc),status='configured',last_message='Zugangsdaten gespeichert – Verbindung noch nicht geprüft.',updated_at=NOW()")->execute([$carrier,$env,$enc]);
    rh24_audit('shipping_integration_save','shipping',$carrier,['environment'=>$env,'fields'=>array_keys(array_filter($old,fn($v)=>$v!==''))]);out(['ok'=>true,'integrations'=>rh24_shipping_integrations(false)]);
  }
  if($action==='shipping_connection_test'){
    rh24_require_admin();$carrier=strtoupper(trim((string)($data['carrier']??'')));if(!in_array($carrier,['DHL','DPD'],true))out(['ok'=>false,'error'=>'Versanddienstleister ungültig'],422);$res=rh24_shipping_test($carrier);$db->prepare('UPDATE shipping_integrations SET status=?,last_test_at=NOW(),last_message=?,updated_at=NOW() WHERE carrier=?')->execute([$res['ok']?'connected':'error',(string)$res['message'],$carrier]);rh24_audit('shipping_connection_test','shipping',$carrier,['ok'=>$res['ok'],'message'=>$res['message']]);out(['ok'=>(bool)$res['ok'],'message'=>(string)$res['message'],'error'=>$res['ok']?'':(string)$res['message'],'integrations'=>rh24_shipping_integrations(false)],$res['ok']?200:422);
  }
  if($action==='shipping_label_save'){
    $orderNo=trim((string)($data['order_no']??''));$carrier=strtoupper(trim((string)($data['carrier']??'')));$tracking=trim((string)($data['tracking_no']??''));if($orderNo===''||!in_array($carrier,['DHL','DPD'],true))out(['ok'=>false,'error'=>'Bestellung und Versanddienstleister sind erforderlich'],422);$q=$db->prepare('SELECT order_no FROM orders WHERE order_no=?');$q->execute([$orderNo]);if(!$q->fetchColumn())out(['ok'=>false,'error'=>'Bestellung nicht gefunden'],404);
    $mime=trim((string)($data['label_mime']??''));$label=trim((string)($data['label_data']??''));if($label!==''){if(!in_array($mime,['application/pdf','image/png','image/jpeg'],true))out(['ok'=>false,'error'=>'Label bitte als PDF, PNG oder JPG hinterlegen'],422);if(strlen($label)>7000000)out(['ok'=>false,'error'=>'Versandlabel ist zu groß'],422);if(str_contains($label,','))$label=substr($label,strpos($label,',')+1);if(base64_decode($label,true)===false)out(['ok'=>false,'error'=>'Versandlabel konnte nicht gelesen werden'],422);}
    $existing=$db->prepare('SELECT label_data,label_mime FROM shipping_labels WHERE order_no=?');$existing->execute([$orderNo]);$ex=$existing->fetch();if($label===''){ $label=(string)($ex['label_data']??'');$mime=(string)($ex['label_mime']??''); }
    $payload=['customer'=>$data['customer']??null,'shipping_weight_g'=>(int)($data['shipping_weight_g']??0)];$db->prepare("INSERT INTO shipping_labels(order_no,carrier,tracking_no,label_mime,label_data,status,payload_json,created_by,created_at,updated_at) VALUES(?,?,?,?,?,'ready',?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE carrier=VALUES(carrier),tracking_no=VALUES(tracking_no),label_mime=VALUES(label_mime),label_data=VALUES(label_data),status='ready',payload_json=VALUES(payload_json),updated_at=NOW()")
      ->execute([$orderNo,$carrier,$tracking,$mime,$label,rh24_json_encode($payload),rh24_user_id()?:null]);$db->prepare('UPDATE orders SET carrier=?,tracking=?,updated_at=NOW() WHERE order_no=?')->execute([$carrier,$tracking,$orderNo]);rh24_audit('shipping_label_save','order',$orderNo,['carrier'=>$carrier,'tracking'=>$tracking,'has_label'=>$label!=='']);out(['ok'=>true,'shipping_labels'=>rh24_shipping_labels()]);
  }
  if($action==='shipping_label_get'){
    $orderNo=trim((string)($data['order_no']??''));$q=$db->prepare('SELECT order_no,carrier,tracking_no,label_mime,label_data,status,payload_json,updated_at FROM shipping_labels WHERE order_no=?');$q->execute([$orderNo]);$r=$q->fetch();if(!$r)out(['ok'=>false,'error'=>'Für diese Bestellung ist noch kein Versandlabel hinterlegt'],404);out(['ok'=>true,'label'=>['order_no'=>$r['order_no'],'carrier'=>$r['carrier'],'tracking_no'=>$r['tracking_no'],'label_mime'=>$r['label_mime'],'label_data'=>$r['label_data'],'status'=>$r['status'],'payload'=>rh24_json_decode($r['payload_json'],[]),'updated_at'=>rh24_iso($r['updated_at'])]]);
  }


  if($action==='production_update'){
    $id=trim((string)($data['id']??''));
    $st=$db->prepare('SELECT * FROM orders WHERE order_no=? FOR UPDATE');$db->beginTransaction();$st->execute([$id]);$r=$st->fetch();
    if(!$r){$db->rollBack();out(['ok'=>false,'error'=>'Bestellung nicht gefunden'],404);}
    $priority=(string)($data['priority']??($r['production_priority']??'normal'));if(!in_array($priority,['low','normal','high','urgent'],true))$priority='normal';
    $station=trim((string)($data['station']??($r['production_station']??'')));$station=substr($station,0,60);
    $step=(string)($data['step']??($r['production_step']??'planung'));$steps=['planung','material','cut','form','point','solder','clean','quality','pack','ready'];if(!in_array($step,$steps,true))$step='planung';
    $progress=max(0,min(100,(int)($data['progress']??($r['production_progress']??0))));
    $note=trim((string)($data['note']??($r['production_note']??'')));
    $dueRaw=trim((string)($data['due_at']??''));$due=null;if($dueRaw!==''){ $ts=strtotime($dueRaw); if($ts!==false)$due=date('Y-m-d H:i:s',$ts); }
    $actor=rh24_current_user();$actorId=(string)($actor['id']??'');$actorName=(string)($actor['display_name']??'Benutzer');$actorRole=(string)($actor['role']??'');
    $existingAssigneeId=(string)($r['production_assignee_user_id']??'');
    $assigneeId=trim((string)($data['assignee_user_id']??$existingAssigneeId));
    if($actorRole==='production'){
      if($existingAssigneeId==='')$assigneeId=$actorId;
      elseif($assigneeId===''||$assigneeId===$existingAssigneeId)$assigneeId=$existingAssigneeId;
      else{$db->rollBack();out(['ok'=>false,'error'=>'Die verantwortliche Zuordnung kann nur durch die Administration geändert werden.'],403);}
    }
    $assigneeName='';
    if($assigneeId!==''){
      $uq=$db->prepare("SELECT display_name FROM users WHERE id=? AND role='production' AND status='active'");$uq->execute([$assigneeId]);$assigneeName=(string)($uq->fetchColumn()?:'');
      if($assigneeName===''){$db->rollBack();out(['ok'=>false,'error'=>'Produktionsmitarbeiter nicht gefunden oder nicht aktiv.'],422);}
    }
    if($step!=='planung' && $assigneeId===''){$db->rollBack();out(['ok'=>false,'error'=>'Bitte zuerst einen aktiven Produktionsmitarbeiter zuweisen.'],422);}
    $status=(string)$r['status'];$statusLabel=(string)$r['status_label'];
    $map=['material'=>'production','cut'=>'production','form'=>'production','point'=>'production','solder'=>'production','clean'=>'production','quality'=>'quality','pack'=>'packing','ready'=>'ready'];
    if($step!=='planung' && (string)$r['payment_status']!=='paid' && in_array($status,['new','payment_pending'],true)){$db->rollBack();out(['ok'=>false,'error'=>'Produktion kann erst nach bestätigtem Zahlungseingang gestartet werden.'],422);}
    if(!in_array($status,['shipped','complete','cancelled'],true) && isset($map[$step])){$status=$map[$step];$statusLabel=rh24_order_statuses()[$status]??$statusLabel;}
    $started=$r['production_started_at']??null;$finished=$r['production_finished_at']??null;
    if($step!=='planung' && !$started)$started=date('Y-m-d H:i:s');
    if($step==='ready'||$progress>=100){$progress=100;$finished=$finished?:date('Y-m-d H:i:s');}elseif($step!=='ready'){$finished=null;}
    $oldStep=(string)($r['production_step']??'planung');$oldProgress=(int)($r['production_progress']??0);$oldAssignee=$existingAssigneeId;
    $isExecution=($step!==$oldStep)||($progress!==$oldProgress)||($step!=='planung'&&!$started);
    $lastWorkerId=(string)($r['production_last_worker_id']??'');$lastWorkerName=(string)($r['production_last_worker_name']??'');$lastWorkAt=$r['production_last_work_at']??null;
    if($actorRole==='production' && (($step!==$oldStep)||($progress!==$oldProgress))){$lastWorkerId=$actorId;$lastWorkerName=$actorName;$lastWorkAt=date('Y-m-d H:i:s');}
    $history=rh24_json_decode($r['history_json'],[]);$history[]=['at'=>date('c'),'type'=>'production','step'=>$step,'progress'=>$progress,'priority'=>$priority,'station'=>$station,'assignee_user_id'=>$assigneeId,'by'=>$actorId,'by_name'=>$actorName,'by_role'=>$actorRole];
    $up=$db->prepare('UPDATE orders SET status=?,status_label=?,production_priority=?,production_due_at=?,production_station=?,production_step=?,production_progress=?,production_assignee=?,production_assignee_user_id=?,production_last_worker_id=?,production_last_worker_name=?,production_last_work_at=?,production_note=?,production_started_at=?,production_finished_at=?,history_json=?,updated_at=NOW() WHERE order_no=?');
    $up->execute([$status,$statusLabel,$priority,$due,$station,$step,$progress,$assigneeName,$assigneeId===''?null:$assigneeId,$lastWorkerId===''?null:$lastWorkerId,$lastWorkerName,$lastWorkAt,$note,$started,$finished,rh24_json_encode($history),$id]);
    $eventType=($oldAssignee!==$assigneeId && $step===$oldStep && $progress===$oldProgress)?'assignment':(($step!==$oldStep||$progress!==$oldProgress)?'step':'update');
    if($eventType!=='update' || $actorRole==='production'){
      $log=$db->prepare('INSERT INTO production_activity(order_no,event_type,production_step,progress,station,assignee_user_id,worker_user_id,worker_name,worker_role,note,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW())');
      $log->execute([$id,$eventType,$step,$progress,$station,$assigneeId===''?null:$assigneeId,$actorId===''?null:$actorId,$actorName,$actorRole,substr($note,0,500)]);
    }
    $db->commit();rh24_audit('production_update','order',$id,['step'=>$step,'progress'=>$progress,'priority'=>$priority,'station'=>$station,'assignee_user_id'=>$assigneeId,'actor_role'=>$actorRole]);
    $o=array_values(array_filter(rh24_orders(),fn($x)=>$x['order_no']===$id))[0]??null;out(['ok'=>true,'order'=>$o]);
  }

  if($action==='order_update'){
    $id=(string)($data['id']??''); $st=$db->prepare('SELECT * FROM orders WHERE order_no=? FOR UPDATE'); $db->beginTransaction(); $st->execute([$id]); $r=$st->fetch(); if(!$r){$db->rollBack();out(['ok'=>false,'error'=>'Bestellung nicht gefunden'],404);} $history=rh24_json_decode($r['history_json'],[]); $status=$r['status'];$statusLabel=$r['status_label'];$payment=$r['payment_status'];$tracking=$r['tracking'];$carrier=$r['carrier'];$note=$r['internal_note']??'';
    if(isset($data['status'])){$statuses=rh24_order_statuses();$s=(string)$data['status'];if(!isset($statuses[$s])){$db->rollBack();out(['ok'=>false,'error'=>'Ungültiger Status'],422);} $status=$s;$statusLabel=$statuses[$s];if($s==='paid')$payment='paid';$history[]=['at'=>date('c'),'type'=>'status','value'=>$s];}
    if(isset($data['payment_status'])){$ps=(string)$data['payment_status'];if(!in_array($ps,['pending','paid','refunded','cancelled'],true)){$db->rollBack();out(['ok'=>false,'error'=>'Ungültiger Zahlungsstatus'],422);} $payment=$ps;if($ps==='paid'&&in_array($status,['new','payment_pending'],true)){$status='paid';$statusLabel='Bezahlt';}$history[]=['at'=>date('c'),'type'=>'payment','value'=>$ps];}
    if(array_key_exists('tracking',$data))$tracking=trim((string)$data['tracking']);if(array_key_exists('carrier',$data))$carrier=trim((string)$data['carrier']);if(array_key_exists('internal_note',$data))$note=trim((string)$data['internal_note']);
    $salesChannel=rh24_sales_channel_normalize((string)($r['sales_channel']??''),(string)($r['source']??''));$commissionRep=(string)($r['commission_sales_rep_id']??$r['sales_rep_id']??'');$commissionAttr=(string)($r['commission_attribution']??'');$commissionNote=(string)($r['commission_note']??'');$commissionAt=$r['commission_assigned_at']??null;$salesChannelChanged=false;
    if(rh24_is_admin()&&array_key_exists('sales_channel',$data)){$newChannel=rh24_sales_channel_normalize((string)$data['sales_channel'],(string)($r['source']??''));$salesChannelChanged=$newChannel!==$salesChannel;$salesChannel=$newChannel;$history[]=['at'=>date('c'),'type'=>'sales_channel','value'=>$salesChannel];}
    if(rh24_is_admin()&&array_key_exists('commission_sales_rep_id',$data)){$candidate=trim((string)$data['commission_sales_rep_id']);if($candidate!==''){$cq=$db->prepare("SELECT name FROM sales_reps WHERE id=? AND status='active'");$cq->execute([$candidate]);$cn=(string)($cq->fetchColumn()?:'');if($cn===''){$db->rollBack();out(['ok'=>false,'error'=>'Ungültiger Provisions-Empfänger'],422);}$commissionRep=$candidate;$commissionAttr='admin_override';$commissionNote=rh24_sales_channel_label($salesChannel).' · Provisionszuordnung manuell geprüft: '.$cn;$commissionAt=date('Y-m-d H:i:s');$history[]=['at'=>date('c'),'type'=>'commission_override','sales_rep_id'=>$candidate,'value'=>$commissionNote];}}
    elseif($salesChannelChanged&&$commissionRep!==''){$cn=rh24_rep_name_by_id($db,$commissionRep);$label=rh24_sales_channel_label($salesChannel);if($commissionAttr==='returning_customer')$commissionNote=$label.' gekauft · Provisionsgutschrift an bestehenden Kundenberater '.$cn;elseif($commissionAttr==='direct_advisor')$commissionNote=$label.' · Kundenberater '.$cn.' · volle Provisionsgutschrift';elseif($commissionAttr==='house_rotation')$commissionNote=$label.' · ohne bestehenden Beraterkontakt · automatische Provisionsgutschrift an '.$cn;elseif($commissionAttr==='admin_override')$commissionNote=$label.' · Provisionszuordnung manuell geprüft: '.$cn;$history[]=['at'=>date('c'),'type'=>'sales_attribution_note','value'=>$commissionNote];}
    $up=$db->prepare('UPDATE orders SET status=?,status_label=?,payment_status=?,tracking=?,carrier=?,internal_note=?,sales_channel=?,commission_sales_rep_id=?,commission_attribution=?,commission_note=?,commission_assigned_at=?,history_json=?,updated_at=NOW() WHERE order_no=?');$up->execute([$status,$statusLabel,$payment,$tracking,$carrier,$note,$salesChannel,$commissionRep===''?null:$commissionRep,$commissionAttr,$commissionNote,$commissionAt,rh24_json_encode($history),$id]);
    if((string)($r['source']??'')==='marketplace'){
      if($payment==='paid'){
        $mu=$db->prepare('SELECT * FROM market_users WHERE membership_order_no=? LIMIT 1');$mu->execute([$id]);$marketUser=$mu->fetch();
        $db->prepare("UPDATE market_users SET membership_status='active',membership_started_at=NOW(),membership_expires_at=DATE_ADD(NOW(),INTERVAL 1 YEAR),updated_at=NOW() WHERE membership_order_no=?")->execute([$id]);
        if($marketUser&&!empty($marketUser['email']))rh24_send_system_mail((string)$marketUser['email'],'Ihr Jahreszugang ist freigeschaltet','<p>Hallo '.htmlspecialchars((string)$marketUser['display_name']).',</p><p>Ihr Jahreszugang für Räucherhaken24 An- &amp; Verkaufen ist ab sofort für 1 Jahr aktiv.</p><p>Sie können gleichzeitig bis zu 10 Anzeigen anbieten.</p>','marketplace');
        $status='complete';$statusLabel='Abgeschlossen';$db->prepare("UPDATE orders SET status='complete',status_label='Abgeschlossen',updated_at=NOW() WHERE order_no=?")->execute([$id]);
      } elseif(in_array($payment,['refunded','cancelled'],true)){
        $db->prepare("UPDATE market_users SET membership_status='cancelled',updated_at=NOW() WHERE membership_order_no=?")->execute([$id]);
        $status='cancelled';$statusLabel='Storniert';$db->prepare("UPDATE orders SET status='cancelled',status_label='Storniert',updated_at=NOW() WHERE order_no=?")->execute([$id]);
      }
    }
    if($payment==='paid'){$ps=$db->prepare("UPDATE prototypes SET payment_status='paid',status=IF(status IN ('new','payment_pending'),'paid',status),status_label=IF(status IN ('new','payment_pending'),'Bezahlt',status_label),updated_at=NOW() WHERE order_no=?");$ps->execute([$id]);}
    $db->commit();rh24_audit('order_update','order',$id,['status'=>$status,'payment_status'=>$payment,'sales_channel'=>$salesChannel,'commission_sales_rep_id'=>$commissionRep,'commission_attribution'=>$commissionAttr]);$o=array_values(array_filter(rh24_orders(),fn($x)=>$x['order_no']===$id))[0]??null;out(['ok'=>true,'order'=>$o]);
  }

  if($action==='prototype_update'){
    $id=(string)($data['id']??'');$st=$db->prepare('SELECT * FROM prototypes WHERE reference=?');$st->execute([$id]);$r=$st->fetch();if(!$r)out(['ok'=>false,'error'=>'Prototyp nicht gefunden'],404);$history=rh24_json_decode($r['history_json'],[]);$status=$r['status'];$label=$r['status_label'];$payment=$r['payment_status'];$note=$r['internal_note']??'';$tracking=$r['tracking']??'';
    if(isset($data['status'])){$statuses=rh24_prototype_statuses();$s=(string)$data['status'];if(!isset($statuses[$s]))out(['ok'=>false,'error'=>'Ungültiger Status'],422);$status=$s;$label=$statuses[$s];if($s==='paid')$payment='paid';$history[]=['at'=>date('c'),'type'=>'status','value'=>$s];}
    if(array_key_exists('internal_note',$data))$note=trim((string)$data['internal_note']);if(array_key_exists('tracking',$data))$tracking=trim((string)$data['tracking']);$db->prepare('UPDATE prototypes SET status=?,status_label=?,payment_status=?,internal_note=?,tracking=?,history_json=?,updated_at=NOW() WHERE reference=?')->execute([$status,$label,$payment,$note,$tracking,rh24_json_encode($history),$id]);rh24_audit('prototype_update','prototype',$id,['status'=>$status]);$p=array_values(array_filter(rh24_prototypes(),fn($x)=>$x['reference']===$id))[0]??null;out(['ok'=>true,'prototype'=>$p]);
  }

  if($action==='inventory_update'){
    $id=(string)($data['id']??'');$st=$db->prepare('SELECT id FROM inventory WHERE id=?');$st->execute([$id]);if(!$st->fetchColumn())out(['ok'=>false,'error'=>'Lagerartikel nicht gefunden'],404);$sets=[];$vals=[];if(isset($data['stock'])){$sets[]='stock=?';$vals[]=max(0,(int)$data['stock']);}if(isset($data['minimum'])){$sets[]='minimum=?';$vals[]=max(0,(int)$data['minimum']);}if($sets){$vals[]=$id;$db->prepare('UPDATE inventory SET '.implode(',',$sets).',updated_at=NOW() WHERE id=?')->execute($vals);}rh24_audit('inventory_update','inventory',$id,$data);$row=array_values(array_filter(rh24_inventory(),fn($x)=>$x['id']===$id))[0]??null;out(['ok'=>true,'inventory'=>$row]);
  }

  if($action==='warehouse_save'){
    $warehouse=is_array($data['warehouse']??null)?$data['warehouse']:[];
    $saved=rh24_warehouse_save($warehouse);
    rh24_audit('warehouse_save','settings','warehouse_v84',['sections'=>array_keys($warehouse)]);
    out(['ok'=>true,'warehouse'=>$saved]);
  }

  if($action==='warehouse_stock_book'){
    $target=(string)($data['target']??'inventory');
    $id=trim((string)($data['id']??''));
    $delta=(int)($data['delta']??0);
    if($id===''||$delta===0) out(['ok'=>false,'error'=>'Artikel und Menge sind erforderlich.'],422);
    $kind=trim((string)($data['kind']??($delta>0?'receipt':'issue')));
    $reason=trim((string)($data['reason']??''));
    $locationId=trim((string)($data['location_id']??''));
    $note=trim((string)($data['note']??''));
    $warehouse=rh24_warehouse_data();
    $inventoryRow=null;$packagingRow=null;$itemName='';$before=0;$after=0;
    if($target==='inventory'){
      $st=$db->prepare('SELECT id,name,stock FROM inventory WHERE id=? FOR UPDATE');
      $db->beginTransaction();$st->execute([$id]);$row=$st->fetch();if(!$row){$db->rollBack();out(['ok'=>false,'error'=>'Lagerartikel nicht gefunden'],404);} $before=(int)$row['stock'];$after=max(0,$before+$delta);$db->prepare('UPDATE inventory SET stock=?,updated_at=NOW() WHERE id=?')->execute([$after,$id]);$db->commit();$itemName=(string)$row['name'];$inventoryRow=array_values(array_filter(rh24_inventory(),fn($x)=>$x['id']===$id))[0]??null;
    } elseif($target==='packaging'){
      $found=false;foreach($warehouse['packaging'] as &$row){if((string)($row['id']??'')===$id){$found=true;$before=(int)($row['stock']??0);$after=max(0,$before+$delta);$row['stock']=$after;$row['updated_at']=date('c');$itemName=(string)($row['name']??$id);$packagingRow=$row;break;}}unset($row);if(!$found)out(['ok'=>false,'error'=>'Verpackungsartikel nicht gefunden'],404);
    } else out(['ok'=>false,'error'=>'Ungültiges Ziel für Lagerbewegung.'],422);
    array_unshift($warehouse['movements'],[
      'id'=>rh24_random_id('WM-'),'target'=>$target,'item_id'=>$id,'item_name'=>$itemName,'delta'=>$delta,'before'=>$before,'after'=>$after,
      'kind'=>$kind,'reason'=>$reason,'location_id'=>$locationId,'note'=>$note,'created_at'=>date('c'),'user_id'=>rh24_user_id(),'user_name'=>(string)(rh24_current_user()['display_name']??'Orgaboard')
    ]);
    $warehouse=rh24_warehouse_save($warehouse);
    rh24_audit('warehouse_stock_book',$target,$id,['delta'=>$delta,'kind'=>$kind,'reason'=>$reason,'location_id'=>$locationId,'before'=>$before,'after'=>$after]);
    out(['ok'=>true,'warehouse'=>$warehouse,'inventory'=>$inventoryRow,'packaging'=>$packagingRow]);
  }



if($action==='territory_directory_search'){
  if(!rh24_can('view_territory_book'))out(['ok'=>false,'error'=>'Kein Zugriff auf Gebietsbücher'],403);
  $filters=['state_code'=>(string)($data['state_code']??''),'status'=>(string)($data['status']??'all'),'category'=>(string)($data['category']??'all'),'contact'=>(string)($data['contact']??'all'),'q'=>(string)($data['q']??''),'limit'=>(int)($data['limit']??150),'offset'=>(int)($data['offset']??0)];
  $result=rh24_territory_directory_search($filters);$result['ok']=true;$result['national_summary']=rh24_territory_directory_national_summary();$scopeCode=(string)($result['scope']['state_code']??'');$result['summary']=$scopeCode!==''&&$scopeCode!=='all'?rh24_territory_directory_summary($scopeCode):$result['national_summary'];$result['categories']=rh24_territory_directory_categories();out($result);
}
if($action==='territory_directory_list'){
  if(!rh24_can('view_territory_book'))out(['ok'=>false,'error'=>'Kein Zugriff auf Gebietsbücher'],403);$requested=trim((string)($data['state_code']??''));$scope=rh24_territory_book_scope($requested);if(empty($scope['state_code']))out(['ok'=>false,'error'=>rh24_is_admin()?'Bitte ein Festgebiet auswählen.':'Ihrem Kundenberaterkonto ist noch kein Gebietsbuch zugeordnet.'],422);out(['ok'=>true,'scope'=>$scope,'rows'=>rh24_territory_directory_rows((string)$scope['state_code'],rh24_is_admin()),'summary'=>rh24_territory_directory_summary((string)$scope['state_code']),'categories'=>rh24_territory_directory_categories()]);
}
if($action==='territory_directory_history'){
  if(!rh24_can('view_territory_book'))out(['ok'=>false,'error'=>'Kein Zugriff auf Gebietsbücher'],403);$id=trim((string)($data['id']??''));$row=rh24_territory_directory_row($id);if(!$row)out(['ok'=>false,'error'=>'Adresse nicht gefunden'],404);$scope=rh24_territory_book_scope((string)$row['state_code']);if(!rh24_is_admin() && (string)$scope['state_code']!==(string)$row['state_code'])out(['ok'=>false,'error'=>'Diese Adresse gehört nicht zu Ihrem Gebietsbuch.'],403);out(['ok'=>true,'history'=>rh24_territory_contact_history($id)]);
}
if($action==='territory_directory_contact'){
  if(!rh24_can('contact_territory_book'))out(['ok'=>false,'error'=>'Keine Berechtigung zum Dokumentieren von Gebietsbuchkontakten'],403);$id=trim((string)($data['id']??''));$row=rh24_territory_directory_row($id);if(!$row)out(['ok'=>false,'error'=>'Adresse nicht gefunden'],404);$scope=rh24_territory_book_scope((string)$row['state_code']);if(!rh24_is_admin() && (string)$scope['state_code']!==(string)$row['state_code'])out(['ok'=>false,'error'=>'Diese Adresse gehört nicht zu Ihrem Gebietsbuch.'],403);
  $methods=rh24_territory_contact_method_labels();$results=rh24_territory_contact_result_labels();$method=(string)($data['method']??'phone');$result=(string)($data['result']??'reached');if(!isset($methods[$method]))$method='other';if(!isset($results[$result]))$result='other';$at=trim((string)($data['contact_at']??''));$ts=$at!==''?strtotime($at):time();if($ts===false)$ts=time();$contactAt=date('Y-m-d H:i:s',$ts);$follow=trim((string)($data['next_follow_up_at']??''));$followDb=null;if($follow!==''){$ft=strtotime($follow);if($ft!==false)$followDb=date('Y-m-d H:i:s',$ft);} $notes=trim((string)($data['notes']??''));$repId=rh24_user_sales_rep_id();if(rh24_is_admin())$repId=trim((string)($data['sales_rep_id']??($row['assigned_sales_rep_id']??'')));
  $db->beginTransaction();try{$db->prepare("INSERT INTO territory_contact_logs(directory_id,state_code,sales_rep_id,user_id,contact_at,method,result,notes,next_follow_up_at,created_at) VALUES(?,?,?,?,?,?,?,?,?,NOW())")->execute([$id,$row['state_code'],$repId!==''?$repId:null,rh24_user_id(),$contactAt,$method,$result,$notes,$followDb]);$db->prepare("UPDATE territory_directory SET last_contacted_at=?,last_contact_method=?,last_contact_result=?,next_follow_up_at=?,updated_at=NOW() WHERE id=?")->execute([$contactAt,$method,$result,$followDb,$id]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}rh24_audit('territory_contact','territory_directory',$id,['method'=>$method,'result'=>$result,'next_follow_up_at'=>$followDb]);out(['ok'=>true,'rows'=>rh24_territory_directory_rows((string)$row['state_code'],rh24_is_admin()),'summary'=>rh24_territory_directory_summary((string)$row['state_code']),'history'=>rh24_territory_contact_history($id)]);
}
if($action==='territory_directory_save'){
  rh24_require_admin();$id=trim((string)($data['id']??''));if($id==='')$id=rh24_random_id('GB-');$code=str_pad((string)(int)($data['state_code']??0),2,'0',STR_PAD_LEFT);$sq=$db->prepare('SELECT state_code FROM sales_territories WHERE state_code=?');$sq->execute([$code]);if(!$sq->fetchColumn())out(['ok'=>false,'error'=>'Ungültiges Festgebiet'],422);$cats=rh24_territory_directory_categories();$cat=(string)($data['category']??'other');if(!isset($cats[$cat]))$cat='other';$statuses=rh24_territory_directory_status_labels();$status=(string)($data['status']??'active');if(!isset($statuses[$status]))$status='candidate';$company=trim((string)($data['company']??''));if($company==='')out(['ok'=>false,'error'=>'Firma / Verein ist erforderlich'],422);$email=strtolower(trim((string)($data['email']??'')));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'E-Mail-Adresse ist ungültig'],422);$rep=trim((string)($data['assigned_sales_rep_id']??''));if($rep!==''){$rq=$db->prepare("SELECT id,state_code FROM sales_reps WHERE id=? AND status='active'");$rq->execute([$rep]);$rr=$rq->fetch();if(!$rr||((string)($rr['state_code']??'')!==''&&(string)$rr['state_code']!==$code))out(['ok'=>false,'error'=>'Der zugewiesene Kundenberater gehört nicht zu diesem Gebiet.'],409);} $verified=$status==='active'?date('Y-m-d H:i:s'):null;
  $existing=rh24_territory_directory_row($id);if($existing){$db->prepare("UPDATE territory_directory SET state_code=?,category=?,company=?,contact_person=?,phone=?,mobile=?,email=?,website=?,street=?,zip=?,city=?,status=?,assigned_sales_rep_id=?,verified_at=CASE WHEN ?='active' THEN COALESCE(verified_at,NOW()) ELSE verified_at END,verified_by=CASE WHEN ?='active' THEN ? ELSE verified_by END,notes=?,updated_at=NOW() WHERE id=?")->execute([$code,$cat,$company,trim((string)($data['contact_person']??'')),trim((string)($data['phone']??'')),trim((string)($data['mobile']??'')),$email,trim((string)($data['website']??'')),trim((string)($data['street']??'')),trim((string)($data['zip']??'')),trim((string)($data['city']??'')),$status,$rep!==''?$rep:null,$status,$status,rh24_user_id(),trim((string)($data['notes']??'')),$id]);}
  else{$db->prepare("INSERT INTO territory_directory(id,state_code,category,company,contact_person,phone,mobile,email,website,street,zip,city,status,assigned_sales_rep_id,source,source_checked_at,verified_at,verified_by,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,'manual',NOW(),?,?,?,NOW(),NOW())")->execute([$id,$code,$cat,$company,trim((string)($data['contact_person']??'')),trim((string)($data['phone']??'')),trim((string)($data['mobile']??'')),$email,trim((string)($data['website']??'')),trim((string)($data['street']??'')),trim((string)($data['zip']??'')),trim((string)($data['city']??'')),$status,$rep!==''?$rep:null,$verified,$verified?rh24_user_id():null,trim((string)($data['notes']??''))]);}
  rh24_audit('territory_directory_save','territory_directory',$id,['state_code'=>$code,'category'=>$cat,'status'=>$status]);out(['ok'=>true,'rows'=>rh24_territory_directory_rows($code,true),'summary'=>rh24_territory_directory_summary($code)]);
}
if($action==='territory_directory_bulk_import'){
  rh24_require_admin();$code=str_pad((string)(int)($data['state_code']??0),2,'0',STR_PAD_LEFT);$rows=is_array($data['rows']??null)?$data['rows']:[];if(!$rows||count($rows)>500)out(['ok'=>false,'error'=>'CSV-Import muss zwischen 1 und 500 Zeilen pro Vorgang enthalten.'],422);$cats=rh24_territory_directory_categories();$oq=$db->prepare('SELECT owner_sales_rep_id,territory_book_no,state_name FROM sales_territories WHERE state_code=?');$oq->execute([$code]);$territory=$oq->fetch();if(!$territory)out(['ok'=>false,'error'=>'Ungültiges Festgebiet'],422);$ownerId=trim((string)($territory['owner_sales_rep_id']??''));$ins=$db->prepare("INSERT INTO territory_directory(id,state_code,category,company,contact_person,phone,mobile,email,website,street,zip,city,status,assigned_sales_rep_id,source,source_ref,source_checked_at,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,'candidate',?,'csv',?,NOW(),?,NOW(),NOW())");$n=0;foreach($rows as $i=>$r){if(!is_array($r))continue;$company=trim((string)($r['company']??''));if($company==='')continue;$cat=(string)($r['category']??'other');if(!isset($cats[$cat]))$cat='other';$ref='csv:'.hash('sha256',$code.'|'.mb_strtolower($company).'|'.mb_strtolower((string)($r['street']??'')).'|'.(string)($r['zip']??''));try{$ins->execute([rh24_random_id('GB-'),$code,$cat,$company,trim((string)($r['contact_person']??'')),trim((string)($r['phone']??'')),trim((string)($r['mobile']??'')),strtolower(trim((string)($r['email']??''))),trim((string)($r['website']??'')),trim((string)($r['street']??'')),trim((string)($r['zip']??'')),trim((string)($r['city']??'')),$ownerId!==''?$ownerId:null,$ref,trim((string)($r['notes']??''))]);$n++;}catch(PDOException $e){if((string)$e->getCode()!=='23000')throw $e;}}rh24_audit('territory_directory_csv','sales_territory',$code,['imported'=>$n,'territory_book_no'=>$territory['territory_book_no'],'assigned_owner'=>$ownerId]);out(['ok'=>true,'imported'=>$n,'territory_book_no'=>$territory['territory_book_no'],'state_name'=>$territory['state_name'],'rows'=>rh24_territory_directory_rows($code,true),'summary'=>rh24_territory_directory_summary($code)]);
}

if($action==='territory_directory_discover'){
  rh24_require_admin();$code=str_pad((string)(int)($data['state_code']??0),2,'0',STR_PAD_LEFT);$pack=(string)($data['pack']??'core');
  try{$d=rh24_territory_discover_run($code,$pack);out(['ok'=>true]+$d+['rows'=>rh24_territory_directory_rows($code,true),'notice'=>'Alle Treffer wurden automatisch dem Gebietsbuch '.$d['territory_book_no'].' · '.$d['state_name'].' zugeordnet. Automatische Einträge bleiben bis zur Prüfung im Status Recherche.']);}
  catch(Throwable $e){out(['ok'=>false,'error'=>$e->getMessage()],502);}
}

if($action==='territory_national_fill_start'){
  rh24_require_admin();try{$job=rh24_territory_fill_job_start();rh24_audit('territory_national_fill_start','system',(string)$job['id'],['steps'=>$job['total_steps'],'states'=>16]);out(['ok'=>true,'job'=>rh24_territory_fill_job_public($job)]);}catch(Throwable $e){out(['ok'=>false,'error'=>$e->getMessage()],500);}
}
if($action==='territory_national_fill_pause'){
  rh24_require_admin();$job=rh24_territory_fill_job_get();if(!$job)out(['ok'=>true,'job'=>rh24_territory_fill_job_public([])]);$job['status']='paused';$job['updated_at']=date('c');$job['last_message']='Vollfüllung pausiert';rh24_territory_fill_job_save($job);out(['ok'=>true,'job'=>rh24_territory_fill_job_public($job)]);
}
if($action==='territory_national_fill_resume'){
  rh24_require_admin();$job=rh24_territory_fill_job_get();if(!$job||in_array((string)($job['status']??''),['complete','idle'],true))$job=rh24_territory_fill_job_start();else{$job['status']='running';$job['updated_at']=date('c');$job['last_message']='Vollfüllung fortgesetzt';rh24_territory_fill_job_save($job);}out(['ok'=>true,'job'=>rh24_territory_fill_job_public($job)]);
}
if($action==='territory_national_fill_step'){
  rh24_require_admin();$job=rh24_territory_fill_job_get();if(!$job)out(['ok'=>false,'error'=>'Keine Vollfüllung gestartet.'],409);if((string)($job['status']??'')==='paused')out(['ok'=>true,'job'=>rh24_territory_fill_job_public($job),'paused'=>true]);
  $idx=(int)($job['current_index']??0);$steps=is_array($job['steps']??null)?$job['steps']:[];
  if($idx>=count($steps)){$job['status']='complete';$job['completed_at']=date('c');$job['updated_at']=date('c');$job['last_message']='Alle 16 Gebietsbücher wurden durchlaufen.';rh24_territory_fill_job_save($job);out(['ok'=>true,'job'=>rh24_territory_fill_job_public($job),'complete'=>true,'national_summary'=>rh24_territory_directory_national_summary()]);}
  $step=$steps[$idx];$code=(string)$step['state_code'];$pack=(string)$step['pack'];$label=rh24_territory_pack_label_php($pack);$result=null;$error='';
  try{$result=rh24_territory_discover_run($code,$pack);$job['steps'][$idx]['status']='complete';$job['steps'][$idx]['found']=(int)($result['found']??0);$job['completed_steps']=(int)($job['completed_steps']??0)+1;$job['states'][$code]['completed']=(int)($job['states'][$code]['completed']??0)+1;$job['states'][$code]['found']=(int)($job['states'][$code]['found']??0)+(int)($result['found']??0);$job['states'][$code]['status']='running';$job['current_index']=$idx+1;$job['last_message']='✓ '.$step['state_name'].' · '.$label.' · '.number_format((int)($result['found']??0),0,',','.').' Treffer';$job['last_error']='';}
  catch(Throwable $e){$error=$e->getMessage();$attempts=(int)($job['steps'][$idx]['attempts']??0)+1;$job['steps'][$idx]['attempts']=$attempts;$job['last_error']=$error;$job['last_message']='↻ '.$step['state_name'].' · '.$label.' · Versuch '.$attempts.'/3';if($attempts>=3){$job['steps'][$idx]['status']='failed';$job['steps'][$idx]['error']=$error;$job['failed_steps']=(int)($job['failed_steps']??0)+1;$job['states'][$code]['failed']=(int)($job['states'][$code]['failed']??0)+1;$job['current_index']=$idx+1;}}
  $next=(int)$job['current_index'];$packsCount=count($job['packs']??[]);foreach($job['states'] as $sc=>&$ss){$processed=(int)($ss['completed']??0)+(int)($ss['failed']??0);if($processed>=$packsCount)$ss['status']=((int)($ss['failed']??0)>0?'partial':'complete');}unset($ss);
  if($next>=count($steps)){$job['status']='complete';$job['completed_at']=date('c');$job['last_message']='Alle 16 Gebietsbücher durchlaufen · '.(int)$job['completed_steps'].' Pakete erfolgreich · '.(int)$job['failed_steps'].' Pakete mit Fehler.';}
  $job['updated_at']=date('c');rh24_territory_fill_job_save($job);out(['ok'=>true,'job'=>rh24_territory_fill_job_public($job),'result'=>$result,'error'=>$error,'national_summary'=>rh24_territory_directory_national_summary()]);
}



  if($action==='territory_assign'){
    rh24_require_admin();$code=str_pad((string)(int)($data['state_code']??0),2,'0',STR_PAD_LEFT);$repId=trim((string)($data['sales_rep_id']??''));
    $tq=$db->prepare('SELECT * FROM sales_territories WHERE state_code=?');$tq->execute([$code]);$t=$tq->fetch();if(!$t)out(['ok'=>false,'error'=>'Bundesland/Festgebiet nicht gefunden'],404);
    if($repId===''){
      $mq=$db->prepare("SELECT COUNT(*) FROM sales_reps WHERE state_code=? AND status='active' AND id<>COALESCE(?, '')");$mq->execute([$code,(string)($t['owner_sales_rep_id']??'')]);if((int)$mq->fetchColumn()>0)out(['ok'=>false,'error'=>'Das Festgebiet hat noch aktive Teammitglieder. Diese zuerst umsetzen oder deaktivieren, bevor das Gebiet zum Weißgebiet wird.'],409);
      if(!empty($t['owner_sales_rep_id'])){$db->prepare("UPDATE sales_reps SET state_code=NULL,territory='',updated_at=NOW() WHERE id=?")->execute([$t['owner_sales_rep_id']]);$db->prepare("UPDATE territory_directory SET assigned_sales_rep_id=NULL,updated_at=NOW() WHERE state_code=? AND assigned_sales_rep_id=?")->execute([$code,$t['owner_sales_rep_id']]);}
      $db->prepare("UPDATE sales_territories SET owner_sales_rep_id=NULL,status='white',updated_at=NOW() WHERE state_code=?")->execute([$code]);
      rh24_audit('territory_release','sales_territory',$code,['territory_book_no'=>$t['territory_book_no']]);out(['ok'=>true,'territories'=>rh24_sales_territories(),'sales_reps'=>rh24_sales_reps()]);
    }
    $rq=$db->prepare("SELECT id,name,state_code,parent_sales_rep_id,sales_role FROM sales_reps WHERE id=? AND status='active'");$rq->execute([$repId]);$rep=$rq->fetch();if(!$rep)out(['ok'=>false,'error'=>'Aktiver Außendienstmitarbeiter nicht gefunden'],404);if(!rh24_sales_role_can_own_territory((string)($rep['sales_role']??'advisor_active')))out(['ok'=>false,'error'=>'Kundenberater BE und Hauskonten können kein Festgebiet als Hauptinhaber übernehmen.'],409);if((string)($rep['sales_role']??'advisor_active')==='advisor_active' && !empty($rep['parent_sales_rep_id']))out(['ok'=>false,'error'=>'Ein untergeordneter Kundenberater kann nicht gleichzeitig Festgebietsinhaber sein. Bitte zuerst zur Teamleiter-/Führungsrolle hochstufen oder die Unterordnung lösen.'],409);
    $other=$db->prepare("SELECT state_code,state_name FROM sales_territories WHERE owner_sales_rep_id=? AND state_code<>?");$other->execute([$repId,$code]);if($other->fetch())out(['ok'=>false,'error'=>'Dieser Mitarbeiter besitzt bereits ein anderes Festgebiet.'],409);
    if(!empty($t['owner_sales_rep_id']) && $t['owner_sales_rep_id']!==$repId)out(['ok'=>false,'error'=>'Dieses Bundesland ist bereits als Festgebiet vergeben. Erst freigeben oder den Gebietsinhaber wechseln.'],409);
    $db->beginTransaction();try{
      $db->prepare("UPDATE sales_territories SET owner_sales_rep_id=?,status='fixed',updated_at=NOW() WHERE state_code=?")->execute([$repId,$code]);
      $db->prepare("UPDATE sales_reps SET state_code=?,territory=?,updated_at=NOW() WHERE id=?")->execute([$code,$t['state_name'].' · Festgebiet '.$t['territory_book_no'],$repId]);
      $db->prepare("UPDATE territory_directory SET assigned_sales_rep_id=?,updated_at=NOW() WHERE state_code=? AND status<>'archived' AND (assigned_sales_rep_id IS NULL OR assigned_sales_rep_id='')")->execute([$repId,$code]);
      $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    rh24_audit('territory_assign','sales_territory',$code,['sales_rep_id'=>$repId,'territory_book_no'=>$t['territory_book_no'],'state_name'=>$t['state_name']]);out(['ok'=>true,'territories'=>rh24_sales_territories(),'sales_reps'=>rh24_sales_reps()]);
  }


if($action==='sales_rep_save_probe'){
    rh24_require_admin();
    $id=trim((string)($data['id']??''));
    $required=['state_code','parent_sales_rep_id','team_leader_since','sales_role','role_since'];
    $cols=[];foreach($db->query('SHOW COLUMNS FROM sales_reps')->fetchAll() as $c)$cols[]=(string)$c['Field'];
    $missing=array_values(array_diff($required,$cols));
    if($missing)out(['ok'=>false,'error'=>'Datenbankschema unvollständig. Fehlende Spalten: '.implode(', ',$missing).'. Bitte /orgaboard/diagnose.php öffnen.'],500);
    if($id!==''){$q=$db->prepare('SELECT id FROM sales_reps WHERE id=? LIMIT 1');$q->execute([$id]);if(!$q->fetchColumn())out(['ok'=>false,'error'=>'Mitarbeiterdatensatz wurde auf dem Server nicht gefunden. Bitte Ansicht neu laden.'],404);}
    out(['ok'=>true,'server_build'=>'74','schema_version'=>rh24_setting_get('db_schema_version',rh24_setting_get('schema_version','?')),'sales_rep_id'=>$id]);
  }

  if($action==='sales_rep_save'){
    rh24_require_admin();
    $requiredCols=['state_code','parent_sales_rep_id','team_leader_since','sales_role','role_since'];
    $columnSql=[
      'state_code'=>"ALTER TABLE sales_reps ADD COLUMN state_code VARCHAR(5) NULL AFTER territory",
      'parent_sales_rep_id'=>"ALTER TABLE sales_reps ADD COLUMN parent_sales_rep_id VARCHAR(40) NULL AFTER state_code",
      'team_leader_since'=>"ALTER TABLE sales_reps ADD COLUMN team_leader_since DATETIME NULL AFTER parent_sales_rep_id",
      'sales_role'=>"ALTER TABLE sales_reps ADD COLUMN sales_role VARCHAR(40) NOT NULL DEFAULT 'advisor_active' AFTER team_leader_since",
      'role_since'=>"ALTER TABLE sales_reps ADD COLUMN role_since DATETIME NULL AFTER sales_role"
    ];
    $cols=[];foreach($db->query('SHOW COLUMNS FROM sales_reps')->fetchAll() as $c)$cols[]=(string)$c['Field'];
    foreach($requiredCols as $col){if(!in_array($col,$cols,true)){try{$db->exec($columnSql[$col]);}catch(Throwable $e){}}}
    $cols=[];foreach($db->query('SHOW COLUMNS FROM sales_reps')->fetchAll() as $c)$cols[]=(string)$c['Field'];
    $missing=array_values(array_diff($requiredCols,$cols));if($missing)out(['ok'=>false,'error'=>'Speichern blockiert: Datenbankschema unvollständig ('.implode(', ',$missing).'). Diagnose öffnen.'],500);
    $id=trim((string)($data['id']??'')); $isNew=$id===''; if($isNew)$id=rh24_random_id('AD-');
    $name=trim((string)($data['name']??'')); if($name==='')out(['ok'=>false,'error'=>'Name des Kundenberaters ist erforderlich'],422);
    $employeeNo=trim((string)($data['employee_no']??''));
    if($employeeNo===''){ $mx=(int)($db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(employee_no,4) AS UNSIGNED)),0) FROM sales_reps WHERE employee_no LIKE 'AD-%'")->fetchColumn()?:0); $employeeNo='AD-'.str_pad((string)($mx+1),4,'0',STR_PAD_LEFT); }
    $email=strtolower(trim((string)($data['email']??''))); if($isNew&&$email==='')out(['ok'=>false,'error'=>'Für neue Kundenberater ist eine E-Mail-Adresse erforderlich, damit der Zugang automatisch angelegt werden kann.'],422); if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'E-Mail-Adresse ist ungültig'],422);
    $status=(string)($data['status']??'active'); if(!in_array($status,['active','inactive'],true))$status='active';
    $role=(string)($data['sales_role']??'advisor_active');$validRoles=['regional_manager','district_manager','team_leader','advisor_active','advisor_be'];if(!in_array($role,$validRoles,true))$role='advisor_active';
    $stateCode=trim((string)($data['state_code']??''));$parentId=trim((string)($data['parent_sales_rep_id']??''));
    if($stateCode!==''){$stateCode=str_pad((string)(int)$stateCode,2,'0',STR_PAD_LEFT);$sq=$db->prepare('SELECT state_name,territory_book_no,owner_sales_rep_id FROM sales_territories WHERE state_code=?');$sq->execute([$stateCode]);$territoryRow=$sq->fetch();if(!$territoryRow)out(['ok'=>false,'error'=>'Ungültiges Bundesland'],422);}else{$territoryRow=null;}
    if($role==='regional_manager')$parentId='';
    $parent=null;
    if($parentId!==''){
      $pq=$db->prepare("SELECT id,name,state_code,sales_role FROM sales_reps WHERE id=? AND status='active'");$pq->execute([$parentId]);$parent=$pq->fetch();if(!$parent)out(['ok'=>false,'error'=>'Übergeordnete Führungskraft wurde nicht gefunden'],422);
      if($parentId===$id)out(['ok'=>false,'error'=>'Ein Mitarbeiter kann nicht seine eigene Führungskraft sein.'],422);
      if(!$isNew && rh24_sales_rep_parent_cycle($db,$id,$parentId))out(['ok'=>false,'error'=>'Diese Zuordnung würde eine Kreisbeziehung in der Führungshierarchie erzeugen.'],409);
      $parentRole=(string)($parent['sales_role']??'advisor_active');
      if(!rh24_sales_role_parent_allowed($role,$parentRole)){
        // Sonderfall: ein aktiver Kundenberater stellt selbst einen Kundenberater ein. Er wird nach dem Speichern automatisch Teamleiter.
        $selfPromote=in_array($role,['advisor_active','advisor_be'],true)&&in_array($parentRole,['advisor_active','advisor_be'],true);
        if(!$selfPromote)out(['ok'=>false,'error'=>'Diese Führungshierarchie ist nicht zulässig: '.rh24_sales_role_label($parentRole).' → '.rh24_sales_role_label($role).'.'],409);
      }
      $sameTerritoryRequired=in_array($role,['advisor_active','advisor_be'],true)&&in_array($parentRole,['team_leader','advisor_active','advisor_be'],true);
      if($sameTerritoryRequired){if($stateCode===''&&!empty($parent['state_code']))$stateCode=(string)$parent['state_code'];if($stateCode!=='' && (string)($parent['state_code']??'')!=='' && (string)$parent['state_code']!==$stateCode)out(['ok'=>false,'error'=>'Teamleiter und Kundenberater müssen demselben Festgebiet/Bundesland zugeordnet sein.'],409);}
    }
    if($role==='advisor_be' && $parentId===''){
      // BE darf vorübergehend ohne Teamleiter erfasst werden, wird aber deutlich als noch nicht vollständig zugeordnet angezeigt.
    }
    $oldRole='';if(!$isNew){$oq=$db->prepare('SELECT sales_role FROM sales_reps WHERE id=?');$oq->execute([$id]);$oldRole=(string)($oq->fetchColumn()?:'advisor_active');$exists=$db->prepare('SELECT id FROM sales_reps WHERE id=? LIMIT 1');$exists->execute([$id]);if(!$exists->fetchColumn())out(['ok'=>false,'error'=>'Mitarbeiterdatensatz wurde nicht gefunden. Bitte Ansicht aktualisieren.'],404);}
    $dup=$db->prepare('SELECT id FROM sales_reps WHERE employee_no=? AND id<>? LIMIT 1');$dup->execute([$employeeNo,$id]);if($dup->fetchColumn())out(['ok'=>false,'error'=>'Diese Mitarbeiternummer ist bereits einem anderen Mitarbeiter zugeordnet.'],409);
    $territoryText=$territoryRow?((string)$territoryRow['state_name'].' · '.($parentId!==''?'Vertriebsorganisation':'Festgebiet '.(string)$territoryRow['territory_book_no'])):trim((string)($data['territory']??''));
    try{
      if($isNew){
        $db->prepare('INSERT INTO sales_reps(id,employee_no,name,email,phone,territory,commission_rate,status,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NOW(),NOW())')->execute([$id,$employeeNo,$name,$email===''?null:$email,trim((string)($data['phone']??'')),$territoryText,0,$status,trim((string)($data['notes']??''))]);
      }else{
        $db->prepare('UPDATE sales_reps SET employee_no=?,name=?,email=?,phone=?,territory=?,status=?,notes=?,updated_at=NOW() WHERE id=?')->execute([$employeeNo,$name,$email===''?null:$email,trim((string)($data['phone']??'')),$territoryText,$status,trim((string)($data['notes']??'')),$id]);
      }
    }catch(PDOException $e){if((string)$e->getCode()==='23000')out(['ok'=>false,'error'=>'Mitarbeiternummer oder E-Mail ist bereits vergeben.'],409);throw $e;}
    $db->prepare('UPDATE sales_reps SET state_code=?,parent_sales_rep_id=?,role_since=CASE WHEN ?<>? OR role_since IS NULL THEN NOW() ELSE role_since END,sales_role=?,territory=?,updated_at=NOW() WHERE id=?')
      ->execute([$stateCode!==''?$stateCode:null,$parentId!==''?$parentId:null,$oldRole,$role,$role,$territoryText,$id]);
    if($parentId!=='')rh24_mark_team_leader_if_needed($db,$parentId);
    // Wenn der Datensatz selbst bereits Teammitglieder hat, kann eine KB-Rolle nicht unterhalb Teamleiter bleiben.
    rh24_mark_team_leader_if_needed($db,$id);

    $uq=$db->prepare('SELECT * FROM users WHERE sales_rep_id=? LIMIT 1');$uq->execute([$id]);$user=$uq->fetch();$createdUser=false;
    if(!$user && $email!==''){
      $uid='USR-'.strtoupper(bin2hex(random_bytes(4)));$username=rh24_unique_username($name);$randomHash=password_hash(bin2hex(random_bytes(24)),PASSWORD_DEFAULT);$perms=rh24_default_permissions_for_role('field_sales');
      $db->prepare('INSERT INTO users(id,username,display_name,email,role,sales_rep_id,permissions_json,password_hash,status,must_change_password,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,1,NOW(),NOW())')
        ->execute([$uid,$username,$name,$email,'field_sales',$id,rh24_json_encode($perms),$randomHash,$status]);
      $uq=$db->prepare('SELECT * FROM users WHERE id=?');$uq->execute([$uid]);$user=$uq->fetch();$createdUser=true;
    }elseif($user){
      $db->prepare('UPDATE users SET display_name=?,email=?,status=?,updated_at=NOW() WHERE id=?')->execute([$name,$email===''?null:$email,$status,$user['id']]);
      $uq=$db->prepare('SELECT * FROM users WHERE id=?');$uq->execute([$user['id']]);$user=$uq->fetch();
    }
    $sendWelcome=!empty($data['send_welcome'])||$createdUser;$mailSent=false;
    if($sendWelcome && $user && $email!=='')$mailSent=rh24_send_welcome_email((string)$user['id']);
    rh24_audit('sales_rep_save','sales_rep',$id,['employee_no'=>$employeeNo,'status'=>$status,'sales_role'=>$role,'old_role'=>$oldRole,'state_code'=>$stateCode,'parent_sales_rep_id'=>$parentId,'user_created'=>$createdUser,'welcome_mail'=>$mailSent]);
    $row=array_values(array_filter(rh24_sales_reps(),fn($x)=>$x['id']===$id))[0]??null;
    out(['ok'=>true,'sales_rep'=>$row,'sales_reps'=>rh24_sales_reps(),'territories'=>rh24_sales_territories(),'user'=>$user?['id'=>(string)$user['id'],'username'=>(string)$user['username']]:null,'mail_sent'=>$mailSent,'mail_requested'=>$sendWelcome]);
  }


  if($action==='consultation_save'){
    $customerId=trim((string)($data['customer_id']??'')); if($customerId!==''){ $q=$db->prepare('SELECT id FROM customers WHERE id=?');$q->execute([$customerId]);if(!$q->fetchColumn())out(['ok'=>false,'error'=>'Kunde nicht gefunden'],404); }
    $repId=trim((string)($data['sales_rep_id']??'')); if($repId!==''){ $q=$db->prepare('SELECT id FROM sales_reps WHERE id=?');$q->execute([$repId]);if(!$q->fetchColumn())out(['ok'=>false,'error'=>'Außendienstmitarbeiter nicht gefunden'],404); }
    $needs=is_array($data['needs']??null)?$data['needs']:[]; $recRaw=is_array($data['recommendations']??null)?$data['recommendations']:[]; $catalog=rh24_catalog(); $recs=[];
    foreach($recRaw as $rid){$rid=(string)$rid;if(isset($catalog[$rid]))$recs[]=$rid;} $recs=array_values(array_unique($recs));
    $id='BER-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3))); $channel=(string)($data['channel']??'phone'); if(!in_array($channel,['phone','field','shop','other'],true))$channel='other';
    $db->prepare('INSERT INTO consultations(id,customer_id,sales_rep_id,channel,needs_json,recommendation_json,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,NOW(),NOW())')->execute([$id,$customerId===''?null:$customerId,$repId===''?null:$repId,$channel,rh24_json_encode($needs),rh24_json_encode($recs),trim((string)($data['notes']??''))]);
    if($customerId!==''&&$repId!=='')$db->prepare("UPDATE customers SET sales_rep_id=?,advisor_assigned_at=NOW(),advisor_assignment_source='beratergespraech',updated_at=NOW() WHERE id=? AND (sales_rep_id IS NULL OR sales_rep_id='')")->execute([$repId,$customerId]);
    rh24_audit('consultation_created','consultation',$id,['customer_id'=>$customerId,'sales_rep_id'=>$repId,'recommendations'=>$recs,'advisor_binding'=>'only_if_unassigned']);
    out(['ok'=>true,'consultation_id'=>$id]);
  }

  if($action==='manual_order_create'){
    $customerId=trim((string)($data['customer_id']??'')); if($customerId==='')out(['ok'=>false,'error'=>'Bitte zuerst einen Kunden auswählen'],422);
    $q=$db->prepare('SELECT * FROM customers WHERE id=?');$q->execute([$customerId]);$cr=$q->fetch();if(!$cr)out(['ok'=>false,'error'=>'Kunde nicht gefunden'],404);
    if(trim((string)$cr['name'])===''||trim((string)$cr['street'])===''||trim((string)$cr['zip'])===''||trim((string)$cr['city'])==='')out(['ok'=>false,'error'=>'Für eine Bestellung müssen Name und vollständige Lieferadresse in der Kundenakte hinterlegt sein.'],422);
    $items=is_array($data['items']??null)?$data['items']:[];if(!$items||count($items)>50)out(['ok'=>false,'error'=>'Bitte mindestens einen Artikel auswählen'],422);
    $catalog=rh24_catalog();$clean=[];$subtotal=0.0;$productWeightG=0;$shippingWeightG=0;
    foreach($items as $row){if(!is_array($row))continue;$pid=(string)($row['id']??'');$qty=max(1,min(99,(int)($row['qty']??1)));$meta=is_array($row['meta']??null)?$row['meta']:[];if(!isset($catalog[$pid]))out(['ok'=>false,'error'=>'Unbekannter Artikel: '.$pid],422);$price=rh24_resolve_price($pid,$meta);$line=round($price*$qty,2);$subtotal+=$line;$pw=max(0,(int)($catalog[$pid]['product_weight_g']??0));$sw=max(0,(int)($catalog[$pid]['shipping_weight_g']??0));$productWeightG+=$pw*$qty;$shippingWeightG+=$sw*$qty;$clean[]=['id'=>$pid,'article_no'=>$catalog[$pid]['article_no']??'','name'=>$catalog[$pid]['name'],'unit'=>$catalog[$pid]['unit'],'product_weight_g'=>$pw,'shipping_weight_g'=>$sw,'qty'=>$qty,'meta'=>$meta,'unit_price'=>$price,'line_total'=>$line];}
    if(!$clean)out(['ok'=>false,'error'=>'Bestellung enthält keine gültigen Positionen'],422);
    $shippingMode=(string)($data['shipping_mode']??'dpd');if(!in_array($shippingMode,['dpd','field_delivery','pickup'],true))$shippingMode='dpd';$cfg=rh24_config();$threshold=(float)($cfg['shipping_threshold']??39);$shipping=$shippingMode==='dpd'?($subtotal>=$threshold?0.0:(float)($cfg['shipping_cost']??7)):0.0;
    $gross=round($subtotal+$shipping,2);$vatRate=(float)($cfg['vat_rate']??19);$net=round($gross/(1+$vatRate/100),2);$vat=round($gross-$net,2);
    $repId=trim((string)($data['sales_rep_id']??''));if($repId!==''){$q=$db->prepare("SELECT id FROM sales_reps WHERE id=? AND status='active'");$q->execute([$repId]);if(!$q->fetchColumn())$repId='';}
    $consultId=trim((string)($data['consultation_id']??''));if($consultId!==''){$q=$db->prepare('SELECT id FROM consultations WHERE id=?');$q->execute([$consultId]);if(!$q->fetchColumn())$consultId='';}
    $paymentMethod=trim((string)($data['payment_method']??'Rechnung / Überweisung'));$paid=!empty($data['paid']);$payment=$paid?'paid':'pending';$status=$paid?'paid':'payment_pending';$statusLabel=$paid?'Bezahlt':'Zahlung offen';
    $channel=rh24_sales_channel_normalize((string)($data['channel']??'telephone'));
    if($channel==='advisor'&&$repId===''&&empty($cr['sales_rep_id']))out(['ok'=>false,'error'=>'Bei Herkunft „Kundenberater“ muss ein Kundenberater ausgewählt werden.'],422);
    $source=['online'=>'shop','advisor'=>'field-sales','telephone'=>'orgaboard-phone','email'=>'orgaboard-email','walk_in'=>'walk-in','marketplace'=>'marketplace','other'=>'orgaboard-other'][$channel]??'orgaboard-other';
    $prefix=['online'=>'WEB','advisor'=>'KB','telephone'=>'TEL','email'=>'MAIL','walk_in'=>'ORT','other'=>'ORG'][$channel]??'ORG';
    $orderNo='RH24-'.$prefix.'-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));$created=rh24_now();
    $cust=['name'=>(string)$cr['name'],'email'=>(string)($cr['email']??''),'phone'=>(string)$cr['phone'],'company'=>(string)$cr['company'],'street'=>(string)$cr['street'],'zip'=>(string)$cr['zip'],'city'=>(string)$cr['city']];
    $totals=['subtotal'=>round($subtotal,2),'shipping'=>$shipping,'net'=>$net,'vat'=>$vat,'vat_rate'=>$vatRate,'gross'=>$gross,'product_weight_g'=>$productWeightG,'shipping_weight_g'=>$shippingWeightG];
    try{
      $db->beginTransaction();
      $attr=rh24_resolve_sales_attribution($db,$customerId,$channel,$repId);
      $history=[['at'=>date('c'),'type'=>'created','value'=>rh24_sales_channel_label($channel).' · Bestellung im Orgaboard'],['at'=>date('c'),'type'=>'payment','value'=>$payment],['at'=>date('c'),'type'=>'sales_attribution','channel'=>$attr['sales_channel'],'commission_sales_rep_id'=>$attr['commission_sales_rep_id'],'value'=>$attr['commission_note']]];
      $st=$db->prepare('INSERT INTO orders(order_no,source,sales_channel,status,status_label,payment_status,payment_method,carrier,tracking,internal_note,customer_id,sales_rep_id,commission_sales_rep_id,commission_attribution,commission_note,commission_assigned_at,consultation_id,customer_json,items_json,totals_json,customer_note,history_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
      $st->execute([$orderNo,$source,$attr['sales_channel'],$status,$statusLabel,$payment,$paymentMethod,$shippingMode==='dpd'?'DPD':($shippingMode==='field_delivery'?'Kundenberater':'Abholung'),'','',$customerId,$attr['sales_rep_id']===''?null:$attr['sales_rep_id'],$attr['commission_sales_rep_id'],$attr['commission_attribution'],$attr['commission_note'],$created,$consultId===''?null:$consultId,rh24_json_encode($cust),rh24_json_encode($clean),rh24_json_encode($totals),trim((string)($data['note']??'')),rh24_json_encode($history),$created,$created]);
      $inv=$db->prepare('UPDATE inventory SET stock=GREATEST(0,stock-?),updated_at=NOW() WHERE id=?');foreach($clean as $line){if($line['id']!=='prototype-project')$inv->execute([(int)$line['qty'],$line['id']]);}
      foreach($clean as $line){if($line['id']!=='prototype-project')continue;$ref='RH24-P-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));$ph=[['at'=>date('c'),'type'=>'created','value'=>'Projekt im Orgaboard erfasst'],['at'=>date('c'),'type'=>'order','value'=>$orderNo]];$db->prepare('INSERT INTO prototypes(reference,order_no,source,project_name,summary,customer_id,customer_json,fields_json,files_json,status,status_label,payment_status,internal_note,tracking,history_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$ref,$orderNo,$source,'Prototypenentwicklung Räucherhaken',trim((string)($data['note']??'')),$customerId,rh24_json_encode($cust),rh24_json_encode(['channel'=>$channel,'sales_rep_id'=>$attr['sales_rep_id'],'commission_sales_rep_id'=>$attr['commission_sales_rep_id']]),'[]',$status,$statusLabel,$payment,'','',rh24_json_encode($ph),$created,$created]);break;}
      $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    rh24_audit('manual_order_created','order',$orderNo,['gross'=>$gross,'source'=>$source,'sales_channel'=>$attr['sales_channel'],'sales_rep_id'=>$attr['sales_rep_id'],'commission_sales_rep_id'=>$attr['commission_sales_rep_id'],'commission_attribution'=>$attr['commission_attribution']]);
    $o=array_values(array_filter(rh24_orders(),fn($x)=>$x['order_no']===$orderNo))[0]??null;out(['ok'=>true,'order'=>$o,'attribution'=>$attr]);
  }

  if($action==='address_localities'){
    if(!rh24_can('view_customers'))out(['ok'=>false,'error'=>'Keine Berechtigung für Kundendaten.'],403);
    $zip=preg_replace('/\D/','',(string)($data['zip']??''));if(strlen($zip)!==5)out(['ok'=>false,'error'=>'Bitte eine 5-stellige deutsche PLZ eingeben.'],422);
    $rows=rh24_openplz('/de/Localities',['postalCode'=>$zip,'page'=>1,'pageSize'=>50]);$places=[];$seen=[];
    foreach($rows as $r){if(!is_array($r))continue;$name=trim((string)($r['name']??''));$postal=trim((string)($r['postalCode']??$zip));if($name==='')continue;$key=$postal.'|'.mb_strtolower($name);if(isset($seen[$key]))continue;$seen[$key]=true;$places[]=['name'=>$name,'postalCode'=>$postal,'municipality'=>(string)($r['municipality']['name']??''),'district'=>(string)($r['district']['name']??''),'federalState'=>(string)($r['federalState']['name']??'')];}
    usort($places,fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));out(['ok'=>true,'places'=>$places,'source'=>'OpenPLZ']);
  }
  if($action==='address_streets'){
    if(!rh24_can('view_customers'))out(['ok'=>false,'error'=>'Keine Berechtigung für Kundendaten.'],403);
    $zip=preg_replace('/\D/','',(string)($data['zip']??''));$city=trim((string)($data['city']??''));$term=trim((string)($data['term']??''));$page=max(1,min(50,(int)($data['page']??1)));
    if(strlen($zip)!==5||$city==='')out(['ok'=>false,'error'=>'PLZ und Ort werden für die Straßensuche benötigt.'],422);
    $params=['postalCode'=>$zip,'locality'=>$city,'page'=>$page,'pageSize'=>50];if($term!=='')$params['name']='.*'.preg_quote(mb_substr($term,0,60),'/').'.*';
    $rows=rh24_openplz('/de/Streets',$params);$streets=[];$seen=[];
    foreach($rows as $r){if(!is_array($r))continue;$name=trim((string)($r['name']??''));if($name===''||isset($seen[mb_strtolower($name)]))continue;$seen[mb_strtolower($name)]=true;$streets[]=['name'=>$name,'postalCode'=>(string)($r['postalCode']??$zip),'locality'=>(string)($r['locality']??$city),'borough'=>(string)($r['borough']??'')];}
    usort($streets,fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));out(['ok'=>true,'streets'=>$streets,'page'=>$page,'source'=>'OpenPLZ']);
  }
  if($action==='customer_save'){
    $id=trim((string)($data['id']??''));$name=trim((string)($data['name']??''));$email=strtolower(trim((string)($data['email']??'')));
    if($name==='')out(['ok'=>false,'error'=>'Name ist erforderlich'],422);if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'E-Mail-Adresse ist ungültig'],422);
    $isNew=$id==='';if($isNew)$id=rh24_random_id('C-');$oldStatus='none';$oldRep='';if(!$isNew){$q=$db->prepare('SELECT newsletter_status,sales_rep_id FROM customers WHERE id=?');$q->execute([$id]);$old=$q->fetch()?:[];$oldStatus=(string)($old['newsletter_status']??'none');$oldRep=(string)($old['sales_rep_id']??'');}
    $wantsNewsletter=!empty($data['newsletter_opt_in']);if($wantsNewsletter&&$email==='')out(['ok'=>false,'error'=>'Für den Newsletter ist eine gültige E-Mail-Adresse erforderlich.'],422);$emailDb=$email===''?null:$email;
    $customerType=in_array((string)($data['customer_type']??'private'),['private','business','dealer'],true)?(string)$data['customer_type']:'private';$status=in_array((string)($data['status']??'active'),['active','lead','blocked','inactive'],true)?(string)$data['status']:'active';
    $preferred=in_array((string)($data['preferred_contact']??'email'),['email','phone','mobile','whatsapp','post'],true)?(string)$data['preferred_contact']:'email';$paymentDays=max(0,min(90,(int)($data['payment_terms_days']??7)));$discount=max(0,min(100,(float)($data['discount_percent']??0)));
    $tags=is_array($data['tags']??null)?array_values(array_unique(array_filter(array_map(fn($x)=>trim((string)$x),$data['tags'])))):[];$billing=is_array($data['billing']??null)?$data['billing']:[];$shipping=is_array($data['shipping']??null)?$data['shipping']:[];
    try{
      $repId=trim((string)($data['sales_rep_id']??''));if(!rh24_is_admin()){$ck=$db->prepare('SELECT sales_rep_id FROM customers WHERE id=?');$ck->execute([$id]);$existingRep=(string)($ck->fetchColumn()?:'');$repId=$existingRep!==''?$existingRep:rh24_user_sales_rep_id();}elseif($repId!==''){$ck=$db->prepare('SELECT id FROM sales_reps WHERE id=?');$ck->execute([$repId]);if(!$ck->fetchColumn())$repId='';}
      $sql='INSERT INTO customers(id,name,customer_type,status,salutation,email,phone,mobile,website,company,street,zip,city,country,vat_id,tax_no,payment_method,payment_terms_days,discount_percent,preferred_contact,source,tags_json,billing_json,shipping_json,payment_note,consent_note,notes,sales_rep_id,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),customer_type=VALUES(customer_type),status=VALUES(status),salutation=VALUES(salutation),email=VALUES(email),phone=VALUES(phone),mobile=VALUES(mobile),website=VALUES(website),company=VALUES(company),street=VALUES(street),zip=VALUES(zip),city=VALUES(city),country=VALUES(country),vat_id=VALUES(vat_id),tax_no=VALUES(tax_no),payment_method=VALUES(payment_method),payment_terms_days=VALUES(payment_terms_days),discount_percent=VALUES(discount_percent),preferred_contact=VALUES(preferred_contact),source=VALUES(source),tags_json=VALUES(tags_json),billing_json=VALUES(billing_json),shipping_json=VALUES(shipping_json),payment_note=VALUES(payment_note),consent_note=VALUES(consent_note),notes=VALUES(notes),sales_rep_id=VALUES(sales_rep_id),updated_at=NOW()';
      $db->prepare($sql)->execute([$id,$name,$customerType,$status,trim((string)($data['salutation']??'')),$emailDb,trim((string)($data['phone']??'')),trim((string)($data['mobile']??'')),trim((string)($data['website']??'')),trim((string)($data['company']??'')),trim((string)($data['street']??'')),trim((string)($data['zip']??'')),trim((string)($data['city']??'')),trim((string)($data['country']??'Deutschland')),trim((string)($data['vat_id']??'')),trim((string)($data['tax_no']??'')),trim((string)($data['payment_method']??'')),$paymentDays,$discount,$preferred,trim((string)($data['source']??'Orgaboard')),rh24_json_encode($tags),rh24_json_encode($billing),rh24_json_encode($shipping),substr(trim((string)($data['payment_note']??'')),0,500),substr(trim((string)($data['consent_note']??'')),0,500),trim((string)($data['notes']??'')),$repId===''?null:$repId]);
      if($repId!==$oldRep){if($repId!=='')$db->prepare("UPDATE customers SET advisor_assigned_at=NOW(),advisor_assignment_source=?,updated_at=NOW() WHERE id=?")->execute([rh24_is_admin()?'admin_zuordnung':'kundenberater_pflege',$id]);else $db->prepare("UPDATE customers SET advisor_assigned_at=NULL,advisor_assignment_source='',updated_at=NOW() WHERE id=?")->execute([$id]);}
    }catch(PDOException $e){if((string)$e->getCode()==='23000')out(['ok'=>false,'error'=>'Diese E-Mail-Adresse ist bereits einem anderen Kunden zugeordnet.'],409);throw $e;}
    $newsletterMail=false;if($wantsNewsletter){if($oldStatus!=='confirmed'){$db->prepare("UPDATE customers SET newsletter_status='pending',newsletter_consent_at=NOW(),newsletter_unsubscribed_at=NULL,newsletter_source='Orgaboard',updated_at=NOW() WHERE id=?")->execute([$id]);$newsletterMail=rh24_send_newsletter_confirmation($id);}}elseif(in_array($oldStatus,['pending','confirmed'],true)){$db->prepare("UPDATE customers SET newsletter_status='unsubscribed',newsletter_unsubscribed_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$id]);}
    rh24_audit('customer_save','customer',$id,['new'=>$isNew,'customer_type'=>$customerType,'status'=>$status,'newsletter_requested'=>$wantsNewsletter,'newsletter_mail'=>$newsletterMail,'sales_rep_id'=>$repId,'advisor_changed'=>$repId!==$oldRep]);$row=array_values(array_filter(rh24_customers(),fn($x)=>$x['id']===$id))[0]??null;out(['ok'=>true,'customer'=>$row,'newsletter_mail_sent'=>$newsletterMail]);
  }

  if($action==='customer_verify'){
    $id=trim((string)($data['id']??''));$note=trim((string)($data['note']??''));$q=$db->prepare('SELECT id FROM customers WHERE id=?');$q->execute([$id]);if(!$q->fetchColumn())out(['ok'=>false,'error'=>'Kunde nicht gefunden'],404);
    $by=(string)(rh24_current_user()['display_name']??rh24_user_id());$db->prepare('UPDATE customers SET contact_verified_at=NOW(),contact_verified_by=?,contact_verification_note=?,updated_at=NOW() WHERE id=?')->execute([$by,$note,$id]);rh24_audit('customer_verified','customer',$id,['note'=>$note]);
    $row=array_values(array_filter(rh24_customers(),fn($x)=>$x['id']===$id))[0]??null;out(['ok'=>true,'customer'=>$row]);
  }

  if($action==='newsletter_test'){
    rh24_require_admin();$to=strtolower(trim((string)($data['email']??'')));$subject=trim((string)($data['subject']??''));$bodyText=trim((string)($data['body']??''));
    if(!filter_var($to,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'Test-E-Mail-Adresse ist ungültig'],422);if($subject===''||$bodyText==='')out(['ok'=>false,'error'=>'Betreff und Nachricht sind erforderlich'],422);
    $html='<!doctype html><html><body style="font-family:Arial,sans-serif;color:#2b211a;padding:24px"><div style="max-width:680px;margin:auto"><h2>Räucherhaken24 · Newsletter-Test</h2><div style="line-height:1.65">'.nl2br(htmlspecialchars($bodyText,ENT_QUOTES,'UTF-8')).'</div></div></body></html>';
    $ok=rh24_send_system_mail($to,'[TEST] '.$subject,$html,'newsletter_test',rh24_user_id());rh24_audit('newsletter_test','newsletter','test',['to'=>$to,'sent'=>$ok]);out(['ok'=>true,'sent'=>$ok]);
  }

  if($action==='newsletter_send'){
    rh24_require_admin();$subject=trim((string)($data['subject']??''));$bodyText=trim((string)($data['body']??''));if($subject===''||$bodyText==='')out(['ok'=>false,'error'=>'Betreff und Nachricht sind erforderlich'],422);if((function_exists('mb_strlen')?mb_strlen($subject,'UTF-8'):strlen($subject))>220||(function_exists('mb_strlen')?mb_strlen($bodyText,'UTF-8'):strlen($bodyText))>12000)out(['ok'=>false,'error'=>'Newsletter ist zu lang'],422);
    $rows=$db->query("SELECT id,name,email FROM customers WHERE newsletter_status='confirmed' AND email IS NOT NULL AND email<>'' ORDER BY id LIMIT 150")->fetchAll();if(!$rows)out(['ok'=>false,'error'=>'Es gibt noch keine bestätigten Newsletter-Empfänger.'],422);
    $total=(int)($db->query("SELECT COUNT(*) FROM customers WHERE newsletter_status='confirmed' AND email IS NOT NULL AND email<>''")->fetchColumn()?:0);if($total>150)out(['ok'=>false,'error'=>'Aktuell sind mehr als 150 bestätigte Empfänger vorhanden. Bitte Newsletter-Versand vor einer Massenmail auf einen professionellen Mailingdienst umstellen.'],422);
    $cid='NL-'.date('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(2)));$db->prepare('INSERT INTO newsletter_campaigns(id,subject,body,recipient_count,sent_count,failed_count,status,created_by,created_at,sent_at) VALUES(?,?,?,?,0,0,?,?,NOW(),NULL)')->execute([$cid,$subject,$bodyText,count($rows),'sending',(string)(rh24_current_user()['display_name']??rh24_user_id())]);
    $sent=0;$failed=0;foreach($rows as $c){if(rh24_send_newsletter_to_customer($c,$subject,$bodyText))$sent++;else$failed++;}
    $db->prepare("UPDATE newsletter_campaigns SET sent_count=?,failed_count=?,status=?,sent_at=NOW() WHERE id=?")->execute([$sent,$failed,$failed===0?'sent':'partial',$cid]);rh24_audit('newsletter_send','newsletter',$cid,['recipients'=>count($rows),'sent'=>$sent,'failed'=>$failed]);out(['ok'=>true,'campaign_id'=>$cid,'sent'=>$sent,'failed'=>$failed]);
  }

  if($action==='product_delete'){
    rh24_require_admin();
    $id=trim((string)($data['id']??''));$confirmName=trim((string)($data['confirm_name']??''));$confirmed=!empty($data['confirm_delete']);
    if($id===''||!$confirmed)out(['ok'=>false,'error'=>'Löschbestätigung fehlt.'],422);
    $q=$db->prepare('SELECT id,name,article_no,image_path FROM products WHERE id=?');$q->execute([$id]);$product=$q->fetch();if(!$product)out(['ok'=>false,'error'=>'Produkt nicht gefunden.'],404);
    if(!hash_equals((string)$product['name'],$confirmName))out(['ok'=>false,'error'=>'Produktname stimmt nicht mit der Löschbestätigung überein.'],422);
    $systemCatalog=array_keys(rh24_default_catalog());if(in_array($id,$systemCatalog,true))out(['ok'=>false,'error'=>'Dieser fest eingebaute Systemartikel ist gegen endgültiges Löschen geschützt. Bitte stattdessen „Aus Shop nehmen“ verwenden.'],409);
    $historyCount=0;try{$hq=$db->prepare('SELECT COUNT(*) FROM orders WHERE items_json LIKE ?');$hq->execute(['%"id":"'.str_replace(['%','_'],['\\%','\\_'],$id).'"%']);$historyCount=(int)($hq->fetchColumn()?:0);}catch(Throwable){}
    $image=(string)($product['image_path']??'');$db->beginTransaction();try{
      $db->prepare('DELETE FROM inventory WHERE id=?')->execute([$id]);
      try{$db->prepare('DELETE FROM product_publications WHERE product_id=?')->execute([$id]);}catch(Throwable){}
      try{$db->prepare('DELETE FROM product_cost_profiles WHERE product_id=?')->execute([$id]);}catch(Throwable){}
      $db->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
      rh24_audit('product_delete','product',$id,['name'=>(string)$product['name'],'article_no'=>(string)($product['article_no']??''),'historical_orders'=>$historyCount]);
      $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    if(str_starts_with($image,'uploads/products/')){try{$cq=$db->prepare('SELECT COUNT(*) FROM products WHERE image_path=?');$cq->execute([$image]);if((int)$cq->fetchColumn()===0){$file=dirname(__DIR__).'/'.ltrim($image,'/');if(is_file($file))@unlink($file);}}catch(Throwable){}}
    out(['ok'=>true,'deleted_id'=>$id,'historical_orders'=>$historyCount]);
  }

  if($action==='product_publish_repair'){
    $id=trim((string)($data['id']??''));if($id==='')out(['ok'=>false,'error'=>'Artikel-ID fehlt'],422);
    $q=$db->prepare('SELECT id,name,article_no,product_type,status,shop_visible FROM products WHERE id=?');$q->execute([$id]);$row=$q->fetch();if(!$row)out(['ok'=>false,'error'=>'Artikel nicht gefunden'],404);
    if((string)($row['product_type']??'product')==='prototype')out(['ok'=>false,'error'=>'Prototypen können nicht im normalen Shop veröffentlicht werden.'],409);
    $db->prepare("UPDATE products SET status='active',shop_visible=1,published_at=COALESCE(published_at,NOW()),updated_at=NOW() WHERE id=?")->execute([$id]);
    $q=$db->prepare('SELECT id,name,article_no,product_type,status,shop_visible,published_at,updated_at FROM products WHERE id=?');$q->execute([$id]);$fixed=$q->fetch();
    rh24_audit('product_publish_repair','product',$id,['status'=>'active','shop_visible'=>1]);
    out(['ok'=>true,'product'=>$fixed]);
  }

  if($action==='product_publish_probe'){
    $id=trim((string)($data['id']??''));if($id==='')out(['ok'=>false,'error'=>'Artikel-ID fehlt'],422);
    $q=$db->prepare('SELECT id,name,article_no,product_type,status,shop_visible,published_at,updated_at FROM products WHERE id=?');$q->execute([$id]);$row=$q->fetch();
    if(!$row)out(['ok'=>true,'exists'=>false,'eligible'=>false,'product'=>null]);
    $eligible=((string)($row['product_type']??'product')!=='prototype' && !empty($row['published_at']));
    out(['ok'=>true,'exists'=>true,'eligible'=>$eligible,'product'=>$row]);
  }

  if($action==='product_quick_update'){
    $id=trim((string)($data['id']??''));if($id==='')out(['ok'=>false,'error'=>'Artikel-ID fehlt'],422);
    $q=$db->prepare('SELECT id,product_type FROM products WHERE id=?');$q->execute([$id]);$existing=$q->fetch();if(!$existing)out(['ok'=>false,'error'=>'Artikel nicht gefunden'],404);
    $sets=[];$args=[];$audit=[];
    if(array_key_exists('base',$data)){$base=max(0,(float)$data['base']);$sets[]='base_price=?';$args[]=$base;$audit['base']=$base;}
    if(array_key_exists('product_weight_g',$data)){$wg=max(0,(int)$data['product_weight_g']);$sets[]='product_weight_g=?';$args[]=$wg;$audit['product_weight_g']=$wg;}
    if(array_key_exists('shipping_weight_g',$data)){$swg=max(0,(int)$data['shipping_weight_g']);$sets[]='shipping_weight_g=?';$args[]=$swg;$audit['shipping_weight_g']=$swg;}
    if(array_key_exists('is_popular',$data)){$v=!empty($data['is_popular'])?1:0;$sets[]='is_popular=?';$args[]=$v;$audit['is_popular']=$v;}
    if(array_key_exists('is_offer',$data)){$v=!empty($data['is_offer'])?1:0;$sets[]='is_offer=?';$args[]=$v;$audit['is_offer']=$v;}
    if(array_key_exists('shop_visible',$data)){$visible=!empty($data['shop_visible'])&&((string)$existing['product_type']!=='prototype')?1:0;$sets[]='shop_visible=?';$args[]=$visible;$audit['shop_visible']=$visible;if($visible){$sets[]="status='active'";$sets[]='published_at=COALESCE(published_at,NOW())';$audit['status']='active';$audit['published']=1;}else{$sets[]='published_at=NULL';$audit['published']=0;}}
    if(!$sets)out(['ok'=>false,'error'=>'Keine Änderung übergeben'],422);
    $sets[]='updated_at=NOW()';$args[]=$id;$db->prepare('UPDATE products SET '.implode(',',$sets).' WHERE id=?')->execute($args);
    rh24_audit('product_quick_update','product',$id,$audit);
    $catalog=[];foreach(rh24_catalog() as $pid=>$p)$catalog[]=['id'=>$pid]+$p;$row=array_values(array_filter($catalog,fn($x)=>$x['id']===$id))[0]??null;
    out(['ok'=>true,'product'=>$row]);
  }

  if($action==='product_save'){
    $id=trim((string)($data['id']??''));
    $name=trim((string)($data['name']??''));
    if($name==='')out(['ok'=>false,'error'=>'Produktname ist erforderlich'],422);
    if($id==='')$id='p-'.strtolower(bin2hex(random_bytes(4)));
    if(!preg_match('/^[A-Za-z0-9_-]{2,80}$/',$id))out(['ok'=>false,'error'=>'Artikel-ID darf nur Buchstaben, Zahlen, - und _ enthalten'],422);
    $type=(string)($data['type']??'product'); if(!in_array($type,['product','hook','prototype'],true))$type='product';
    $status=(string)($data['status']??'active'); if(!in_array($status,['active','inactive'],true))$status='active';
    $base=max(0,(float)($data['base']??0));
    $unit=trim((string)($data['unit']??'Stück')) ?: 'Stück';
    $productWeightG=max(0,(int)($data['product_weight_g']??0));
    $shippingWeightG=max(0,(int)($data['shipping_weight_g']??0));
    $isPopular=!empty($data['is_popular'])?1:0;$isOffer=!empty($data['is_offer'])?1:0;
    $oldPrice=max(0,(float)($data['old_price']??0));$salePrice=max(0,(float)($data['sale_price']??0));if($isOffer&&$salePrice>0&&$oldPrice<=0&&$base>$salePrice)$oldPrice=$base;if($oldPrice>0&&$salePrice>0&&$salePrice>=$oldPrice)out(['ok'=>false,'error'=>'Der neue Aktionspreis muss unter dem Streichpreis liegen.'],422);
    $dateDb=function($v){$v=trim((string)$v);if($v==='')return null;$v=str_replace('T',' ',$v);if(strlen($v)===16)$v.=':00';$ts=strtotime($v);return $ts===false?null:date('Y-m-d H:i:s',$ts);};
    $saleStart=$dateDb($data['sale_start_at']??'');$saleEnd=$dateDb($data['sale_end_at']??'');if($saleStart&&$saleEnd&&strtotime($saleEnd)<=strtotime($saleStart))out(['ok'=>false,'error'=>'Aktionsende muss nach dem Aktionsstart liegen'],422);
    $priceBasis=(string)($data['price_basis']??'auto');if(!in_array($priceBasis,['auto','weight','piece'],true))$priceBasis='auto';$packQuantity=max(1,min(9999,(int)($data['pack_quantity']??1)));
    $contentQuantity=max(0,(float)($data['content_quantity']??1));
    $contentUnit=trim((string)($data['content_unit']??'Stück')) ?: 'Stück';if(!in_array($contentUnit,['Stück','g','kg','ml','l','Set','Paar','Paket'],true))$contentUnit='Stück';
    $packageType=trim((string)($data['package_type']??'Einzelartikel')) ?: 'Einzelartikel';if(!in_array($packageType,['Einzelartikel','Beutel','Dose','Flasche','Karton','Set','Paket','Rolle','Sonstiges'],true))$packageType='Sonstiges';
    $packageLength=max(0,(float)($data['package_length_cm']??0));$packageWidth=max(0,(float)($data['package_width_cm']??0));$packageHeight=max(0,(float)($data['package_height_cm']??0));
    $category=trim((string)($data['category']??'Sonstiges')) ?: 'Sonstiges';
    $sku=trim((string)($data['sku']??''));
    $articleNo=trim((string)($data['article_no']??''));
    if($articleNo===''){
      $q=$db->prepare('SELECT article_no FROM products WHERE id=?');$q->execute([$id]);$articleNo=(string)($q->fetchColumn()?:'');
      if($articleNo==='')$articleNo=rh24_next_article_no($db);
    }
    if(!preg_match('/^[A-Za-z0-9._-]{2,40}$/',$articleNo))out(['ok'=>false,'error'=>'Artikelnummer enthält ungültige Zeichen'],422);
    $dupe=$db->prepare('SELECT id FROM products WHERE article_no=? AND id<>?');$dupe->execute([$articleNo,$id]);if($dupe->fetchColumn())out(['ok'=>false,'error'=>'Diese Artikelnummer ist bereits vergeben'],409);
    $barcode=preg_replace('/[^A-Za-z0-9._-]/','',trim((string)($data['barcode']??'')));if(strlen($barcode)>80)out(['ok'=>false,'error'=>'Barcode/EAN ist zu lang'],422);if($barcode!==''){ $bq=$db->prepare('SELECT id FROM products WHERE barcode=? AND id<>?');$bq->execute([$barcode,$id]);if($bq->fetchColumn())out(['ok'=>false,'error'=>'Dieser Barcode/EAN ist bereits einem anderen Artikel zugeordnet'],409);}
    $description=trim((string)($data['description']??''));
    $publishMode=(string)($data['publish_mode']??'save');
    if($publishMode==='draft'){$status='inactive';$data['shop_visible']=false;}
    if($publishMode==='publish'&&$type!=='prototype'){$status='active';$data['shop_visible']=true;}
    $shopVisible=!empty($data['shop_visible'])&&$type!=='prototype'?1:0;
    $image=trim((string)($data['image']??''));
    if($image===''){$q=$db->prepare('SELECT image_path FROM products WHERE id=?');$q->execute([$id]);$image=(string)($q->fetchColumn()?:'');}
    $sql='INSERT INTO products(id,sku,article_no,barcode,name,category,product_type,base_price,old_price,sale_price,sale_start_at,sale_end_at,unit,content_quantity,content_unit,product_weight_g,shipping_weight_g,price_basis,pack_quantity,package_type,package_length_cm,package_width_cm,package_height_cm,status,description,image_path,shop_visible,is_popular,is_offer,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE sku=VALUES(sku),article_no=VALUES(article_no),barcode=VALUES(barcode),name=VALUES(name),category=VALUES(category),product_type=VALUES(product_type),base_price=VALUES(base_price),old_price=VALUES(old_price),sale_price=VALUES(sale_price),sale_start_at=VALUES(sale_start_at),sale_end_at=VALUES(sale_end_at),unit=VALUES(unit),content_quantity=VALUES(content_quantity),content_unit=VALUES(content_unit),product_weight_g=VALUES(product_weight_g),shipping_weight_g=VALUES(shipping_weight_g),price_basis=VALUES(price_basis),pack_quantity=VALUES(pack_quantity),package_type=VALUES(package_type),package_length_cm=VALUES(package_length_cm),package_width_cm=VALUES(package_width_cm),package_height_cm=VALUES(package_height_cm),status=VALUES(status),description=VALUES(description),image_path=VALUES(image_path),shop_visible=VALUES(shop_visible),is_popular=VALUES(is_popular),is_offer=VALUES(is_offer),updated_at=NOW()';
    $db->prepare($sql)->execute([$id,$sku,$articleNo,$barcode,$name,$category,$type,$base,$oldPrice,$salePrice,$saleStart,$saleEnd,$unit,$contentQuantity,$contentUnit,$productWeightG,$shippingWeightG,$priceBasis,$packQuantity,$packageType,$packageLength,$packageWidth,$packageHeight,$status,$description,$image,$shopVisible,$isPopular,$isOffer]);
    $shortDescription=mb_substr(trim((string)($data['short_description']??'')),0,3000);
    $featuresRaw=$data['features']??[];if(!is_array($featuresRaw))$featuresRaw=[];$features=[];foreach($featuresRaw as $f){$f=trim((string)$f);if($f!==''&&!in_array($f,$features,true))$features[]=mb_substr($f,0,180);if(count($features)>=10)break;}$featuresRich=mb_substr(trim((string)($data['features_rich']??'')),0,12000);
    $seoTitle=mb_substr(trim((string)($data['seo_title']??'')),0,180);$seoDescription=mb_substr(trim((string)($data['seo_description']??'')),0,320);$seoKeywords=mb_substr(trim((string)($data['seo_keywords']??'')),0,500);
    $crossSellEnabled=!empty($data['cross_sell_enabled'])?1:0;$crossSellTitle=mb_substr(trim((string)($data['cross_sell_title']??'Passt perfekt dazu')),0,160);if($crossSellTitle==='')$crossSellTitle='Passt perfekt dazu';$crossSellMax=max(1,min(8,(int)($data['cross_sell_max']??4)));$crossSellAuto=!empty($data['cross_sell_auto'])?1:0;$crossSellReciprocal=!empty($data['cross_sell_reciprocal'])?1:0;$crossSellRaw=is_array($data['cross_sell']??null)?$data['cross_sell']:[];$crossSell=rh24_cross_sell_clean($crossSellRaw,$id);
    $isNew=!empty($data['is_new'])?1:0;$newUntilRaw=trim((string)($data['new_until']??''));if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$newUntilRaw))$newUntilRaw.=' 23:59:59';$newUntil=$isNew?$dateDb($newUntilRaw):null;
    $publishNow=$publishMode==='publish';$publishedAt=$publishNow?date('Y-m-d H:i:s'):null;
    $db->prepare("UPDATE products SET short_description=?,features_json=?,features_rich=?,seo_title=?,seo_description=?,seo_keywords=?,cross_sell_enabled=?,cross_sell_title=?,cross_sell_max=?,cross_sell_auto=?,cross_sell_reciprocal=?,cross_sell_json=?,is_new=?,new_until=?,published_at=CASE WHEN ? IS NOT NULL AND published_at IS NULL THEN ? ELSE published_at END WHERE id=?")
       ->execute([$shortDescription,rh24_json_encode($features),$featuresRich,$seoTitle,$seoDescription,$seoKeywords,$crossSellEnabled,$crossSellTitle,$crossSellMax,$crossSellAuto,$crossSellReciprocal,rh24_json_encode($crossSell),$isNew,$newUntil,$publishedAt,$publishedAt,$id]);
    if($publishMode==='publish'&&$type!=='prototype'){
      $db->prepare("UPDATE products SET status='active',shop_visible=1,published_at=COALESCE(published_at,NOW()),updated_at=NOW() WHERE id=?")->execute([$id]);
      $pubCheck=$db->prepare('SELECT status,shop_visible,product_type FROM products WHERE id=?');$pubCheck->execute([$id]);$pubState=$pubCheck->fetch();
      if(!$pubState||($pubState['status']??'')!=='active'||(int)($pubState['shop_visible']??0)!==1||(string)($pubState['product_type']??'product')==='prototype') throw new RuntimeException('Veröffentlichungsstatus konnte nicht dauerhaft gespeichert werden.');
    }
    if($publishMode==='draft'||$type==='prototype'){$db->prepare("UPDATE products SET published_at=NULL WHERE id=?")->execute([$id]);}
    if($crossSellEnabled&&$crossSellReciprocal){
      foreach($crossSell as $rel){$targetId=(string)$rel['id'];$q=$db->prepare('SELECT cross_sell_json FROM products WHERE id=?');$q->execute([$targetId]);$raw=$q->fetchColumn();if($raw===false)continue;$target=rh24_cross_sell_clean(rh24_json_decode((string)$raw,[]),$targetId);$exists=false;foreach($target as $x){if((string)$x['id']===$id){$exists=true;break;}}if(!$exists){$target[]= ['id'=>$id,'relation'=>'accessory','priority'=>80,'note'=>'Passend dazu'];$target=rh24_cross_sell_clean($target,$targetId);$db->prepare("UPDATE products SET cross_sell_enabled=1,cross_sell_json=?,updated_at=NOW() WHERE id=?")->execute([rh24_json_encode($target),$targetId]);}}
    }
    if($type!=='prototype'){
      $stock=max(0,(int)($data['stock']??0));$minimum=max(0,(int)($data['minimum']??0));
      $db->prepare('INSERT INTO inventory(id,name,stock,minimum,unit,updated_at) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),stock=VALUES(stock),minimum=VALUES(minimum),unit=VALUES(unit),updated_at=NOW()')->execute([$id,$name,$stock,$minimum,$unit]);
    } else {$db->prepare('DELETE FROM inventory WHERE id=?')->execute([$id]);}
    rh24_audit('product_save','product',$id,['article_no'=>$articleNo,'barcode'=>$barcode,'status'=>$status,'shop_visible'=>$shopVisible,'publish_mode'=>$publishMode,'base'=>$base,'old_price'=>$oldPrice,'sale_price'=>$salePrice,'sale_start_at'=>$saleStart,'sale_end_at'=>$saleEnd,'is_popular'=>$isPopular,'is_offer'=>$isOffer,'is_new'=>$isNew,'new_until'=>$newUntil,'price_basis'=>$priceBasis,'pack_quantity'=>$packQuantity,'content_quantity'=>$contentQuantity,'content_unit'=>$contentUnit,'package_type'=>$packageType,'package_dimensions_cm'=>[$packageLength,$packageWidth,$packageHeight],'product_weight_g'=>$productWeightG,'shipping_weight_g'=>$shippingWeightG,'features_count'=>count($features),'cross_sell_enabled'=>$crossSellEnabled,'cross_sell_count'=>count($crossSell),'cross_sell_auto'=>$crossSellAuto,'cross_sell_reciprocal'=>$crossSellReciprocal]);
    $catalog=[];foreach(rh24_catalog() as $pid=>$p)$catalog[]=['id'=>$pid]+$p;
    $row=array_values(array_filter($catalog,fn($x)=>$x['id']===$id))[0]??null;
    out(['ok'=>true,'product'=>$row]);
  }

  if($action==='customer_note'){
    $id=(string)($data['id']??'');$st=$db->prepare('UPDATE customers SET notes=?,updated_at=NOW() WHERE id=?');$st->execute([trim((string)($data['notes']??'')),$id]);if(!$st->rowCount()){ $ck=$db->prepare('SELECT id FROM customers WHERE id=?');$ck->execute([$id]);if(!$ck->fetchColumn())out(['ok'=>false,'error'=>'Kunde nicht gefunden'],404);}rh24_audit('customer_note','customer',$id,[]);out(['ok'=>true]);
  }

  if($action==='dealer_save'){
    $id=trim((string)($data['id']??''));$tier=(string)($data['tier']??'Bronze');if(!in_array($tier,['Bronze','Silber','Gold'],true))$tier='Bronze';$discount=['Bronze'=>10,'Silber'=>15,'Gold'=>20][$tier];
    $company=trim((string)($data['company']??''));$email=strtolower(trim((string)($data['email']??'')));if($company===''||!filter_var($email,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'Firma und gültige E-Mail erforderlich'],422);if($id==='')$id=rh24_random_id('H-');
    $customerId=trim((string)($data['customer_id']??''));if($customerId!==''){$q=$db->prepare('SELECT id FROM customers WHERE id=?');$q->execute([$customerId]);if(!$q->fetchColumn())$customerId='';}
    if($customerId===''){$q=$db->prepare("SELECT id FROM customers WHERE LOWER(email)=? OR company=? ORDER BY updated_at DESC LIMIT 1");$q->execute([$email,$company]);$customerId=(string)($q->fetchColumn()?:'');}
    $repId=trim((string)($data['sales_rep_id']??''));if($repId!==''){$q=$db->prepare('SELECT id FROM sales_reps WHERE id=?');$q->execute([$repId]);if(!$q->fetchColumn())$repId='';}
    $lastVisit=trim((string)($data['last_visit_at']??''));$lastTs=$lastVisit!==''?strtotime($lastVisit):time();if($lastTs===false)$lastTs=time();$lastDb=date('Y-m-d H:i:s',$lastTs);$nextDb=date('Y-m-d H:i:s',strtotime('+14 days',$lastTs));
    $sql='INSERT INTO dealers(id,customer_id,company,contact,email,phone,street,zip,city,sales_rep_id,tier,discount,visit_interval_days,last_visit_at,next_visit_at,last_visit_note,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,14,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE customer_id=VALUES(customer_id),company=VALUES(company),contact=VALUES(contact),email=VALUES(email),phone=VALUES(phone),street=VALUES(street),zip=VALUES(zip),city=VALUES(city),sales_rep_id=VALUES(sales_rep_id),tier=VALUES(tier),discount=VALUES(discount),visit_interval_days=14,last_visit_at=VALUES(last_visit_at),next_visit_at=VALUES(next_visit_at),last_visit_note=VALUES(last_visit_note),status=VALUES(status),updated_at=NOW()';
    $db->prepare($sql)->execute([$id,$customerId===''?null:$customerId,$company,trim((string)($data['contact']??'')),$email,trim((string)($data['phone']??'')),trim((string)($data['street']??'')),trim((string)($data['zip']??'')),trim((string)($data['city']??'')),$repId===''?null:$repId,$tier,$discount,$lastDb,$nextDb,trim((string)($data['last_visit_note']??'')),(string)($data['status']??'active')]);
    rh24_audit('dealer_save','dealer',$id,['tier'=>$tier,'sales_rep_id'=>$repId,'next_visit_at'=>$nextDb]);$row=array_values(array_filter(rh24_dealers(),fn($x)=>$x['id']===$id))[0]??null;out(['ok'=>true,'dealer'=>$row]);
  }

  if($action==='review_update'){
    $id=(string)($data['id']??'');$sets=[];$vals=[];foreach(['status','reply'] as $f)if(array_key_exists($f,$data)){$sets[]=$f.'=?';$vals[]=trim((string)$data[$f]);}if($sets){$vals[]=$id;$st=$db->prepare('UPDATE reviews SET '.implode(',',$sets).',updated_at=NOW() WHERE id=?');$st->execute($vals);}else{$st=$db->prepare('SELECT id FROM reviews WHERE id=?');$st->execute([$id]);}if(!$st->rowCount()&&$action==='review_update'){$ck=$db->prepare('SELECT id FROM reviews WHERE id=?');$ck->execute([$id]);if(!$ck->fetchColumn())out(['ok'=>false,'error'=>'Bewertung nicht gefunden'],404);}rh24_audit('review_update','review',$id,[]);out(['ok'=>true]);
  }

  if($action==='content_save'){
    $id=trim((string)($data['id']??''));if($id==='')$id=rh24_random_id('CT-');$title=trim((string)($data['title']??''));if($title==='')out(['ok'=>false,'error'=>'Titel erforderlich'],422);$type=(string)($data['type']??'Rezept');$status=(string)($data['status']??'draft');$db->prepare('INSERT INTO content(id,title,type,status,body,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE title=VALUES(title),type=VALUES(type),status=VALUES(status),body=VALUES(body),updated_at=NOW()')->execute([$id,$title,$type,$status,(string)($data['body']??'')]);rh24_audit('content_save','content',$id,['status'=>$status]);out(['ok'=>true,'id'=>$id]);
  }

  if($action==='settings_save'){
    if(array_key_exists('system_email',$data)){ $em=strtolower(trim((string)$data['system_email'])); if(!filter_var($em,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'System-E-Mail-Adresse ist ungültig'],422); rh24_setting_set('system_email',$em); }
    foreach(['shop_name','shipping_threshold','shipping_cost','vat_rate','newsletter_sender_name'] as $f)if(array_key_exists($f,$data))rh24_setting_set($f,$data[$f]);
    if(array_key_exists('shipping_default_carrier',$data)){ $c=strtoupper(trim((string)$data['shipping_default_carrier'])); if(!in_array($c,['DHL','DPD'],true))out(['ok'=>false,'error'=>'Standard-Versender muss DHL oder DPD sein'],422); rh24_setting_set('shipping_default_carrier',$c); }
    if(array_key_exists('newsletter_reply_to',$data)){ $em=strtolower(trim((string)$data['newsletter_reply_to']));if(!filter_var($em,FILTER_VALIDATE_EMAIL))out(['ok'=>false,'error'=>'Newsletter-Antwortadresse ist ungültig'],422);rh24_setting_set('newsletter_reply_to',$em); }
    if(array_key_exists('commission_statement_day',$data)){ $d=(int)$data['commission_statement_day']; if($d<1||$d>28)out(['ok'=>false,'error'=>'Abrechnungstag muss zwischen 1 und 28 liegen.'],422); rh24_setting_set('commission_statement_day',(string)$d);}
    if(array_key_exists('commission_payout_day',$data)){ $d=(int)$data['commission_payout_day']; if($d<1||$d>28)out(['ok'=>false,'error'=>'Auszahlungstag muss zwischen 1 und 28 liegen.'],422); rh24_setting_set('commission_payout_day',(string)$d);}
    if(array_key_exists('star_thresholds',$data)){ $vals=$data['star_thresholds']; if(!is_array($vals)||count($vals)!==6)out(['ok'=>false,'error'=>'Es müssen genau 6 Sternschwellen angegeben werden.'],422);$vals=array_map(fn($v)=>(float)$v,$vals);sort($vals,SORT_NUMERIC);for($i=0;$i<6;$i++){if($vals[$i]<=0||($i>0&&$vals[$i]<=$vals[$i-1]))out(['ok'=>false,'error'=>'Sternschwellen müssen positiv und streng aufsteigend sein.'],422);}rh24_setting_set('star_thresholds',$vals);}
    rh24_audit('settings_save','settings','shop',['commission_statement_day'=>$data['commission_statement_day']??null,'commission_payout_day'=>$data['commission_payout_day']??null,'star_thresholds'=>$data['star_thresholds']??null]);out(['ok'=>true]);
  }

  if($action==='password_change'){
    $current=(string)($data['current']??'');$new=(string)($data['new']??'');
    if(strlen($new)<12)out(['ok'=>false,'error'=>'Neues Passwort muss mindestens 12 Zeichen lang sein'],422);
    $uid=rh24_user_id();$q=$db->prepare('SELECT password_hash FROM users WHERE id=?');$q->execute([$uid]);$hash=(string)($q->fetchColumn()?:'');
    if($hash===''||!password_verify($current,$hash))out(['ok'=>false,'error'=>'Aktuelles Passwort ist falsch'],422);
    $db->prepare('UPDATE users SET password_hash=?,must_change_password=0,updated_at=NOW() WHERE id=?')->execute([password_hash($new,PASSWORD_DEFAULT),$uid]);
    $_SESSION['rh24_user']['must_change_password']=false;rh24_audit('password_change','user',$uid,[]);out(['ok'=>true]);
  }

  if($action==='user_save'){
    rh24_require_admin();
    $id=trim((string)($data['id']??'')); if($id==='') $id='USR-'.strtoupper(bin2hex(random_bytes(4)));
    $username=strtolower(trim((string)($data['username']??''))); $display=trim((string)($data['display_name']??'')); $role=(string)($data['role']??'field_sales');
    if(!preg_match('/^[a-z0-9._-]{3,80}$/',$username)) out(['ok'=>false,'error'=>'Benutzername: mindestens 3 Zeichen, nur a-z, Zahlen, Punkt, _ oder -'],422);
    if($display==='') out(['ok'=>false,'error'=>'Anzeigename ist erforderlich'],422);
    if(!in_array($role,['admin','field_sales','production','cashier'],true)) $role='field_sales';
    $email=strtolower(trim((string)($data['email']??''))); if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)) out(['ok'=>false,'error'=>'E-Mail-Adresse ist ungültig'],422);
    $repId=trim((string)($data['sales_rep_id']??'')); if($role!=='field_sales') $repId='';
    if($repId!==''){ $q=$db->prepare('SELECT id FROM sales_reps WHERE id=?'); $q->execute([$repId]); if(!$q->fetchColumn()) out(['ok'=>false,'error'=>'Außendienst-Zuordnung nicht gefunden'],422); }
    $status=(string)($data['status']??'active'); if(!in_array($status,['active','inactive'],true)) $status='active';
    $permissions=is_array($data['permissions']??null)?array_values(array_intersect(array_map('strval',$data['permissions']),array_keys(rh24_permission_catalog()))):rh24_default_permissions_for_role($role);
    $permissionsJson=$role==='admin'?null:rh24_json_encode($permissions);
    $password=(string)($data['password']??''); if($password!==''&&strlen($password)<12) out(['ok'=>false,'error'=>'Temporäres Passwort muss mindestens 12 Zeichen lang sein'],422);

    // Die beiden gewünschten Hauptadministratoren können nicht versehentlich entzogen/deaktiviert werden.
    if(in_array($id,['USR-BJOERN','USR-JESSICA'],true) && ($role!=='admin'||$status!=='active')){
      out(['ok'=>false,'error'=>'Björn Hahne und Jessica Hahne sind geschützte Hauptadministratoren und müssen aktiv bleiben.'],422);
    }
    if($id===rh24_user_id() && $status!=='active') out(['ok'=>false,'error'=>'Das eigene Benutzerkonto kann nicht deaktiviert werden.'],422);

    $q=$db->prepare('SELECT * FROM users WHERE id=?'); $q->execute([$id]); $existing=$q->fetch();
    if(!$existing && $password==='') out(['ok'=>false,'error'=>'Für neue Benutzer ist ein temporäres Passwort erforderlich'],422);
    try{
      if($existing){
        if($password!==''){
          $db->prepare('UPDATE users SET username=?,display_name=?,email=?,role=?,sales_rep_id=?,permissions_json=?,password_hash=?,status=?,must_change_password=1,updated_at=NOW() WHERE id=?')
            ->execute([$username,$display,$email===''?null:$email,$role,$repId===''?null:$repId,$permissionsJson,password_hash($password,PASSWORD_DEFAULT),$status,$id]);
        }else{
          $db->prepare('UPDATE users SET username=?,display_name=?,email=?,role=?,sales_rep_id=?,permissions_json=?,status=?,updated_at=NOW() WHERE id=?')
            ->execute([$username,$display,$email===''?null:$email,$role,$repId===''?null:$repId,$permissionsJson,$status,$id]);
        }
      }else{
        $db->prepare('INSERT INTO users(id,username,display_name,email,role,sales_rep_id,permissions_json,password_hash,status,must_change_password,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,1,NOW(),NOW())')
          ->execute([$id,$username,$display,$email===''?null:$email,$role,$repId===''?null:$repId,$permissionsJson,password_hash($password,PASSWORD_DEFAULT),$status]);
      }
    }catch(PDOException $e){ if((string)$e->getCode()==='23000') out(['ok'=>false,'error'=>'Benutzername ist bereits vergeben'],409); throw $e; }
    rh24_audit('user_save','user',$id,['username'=>$username,'role'=>$role,'status'=>$status]); out(['ok'=>true]);
  }

  if($action==='user_permissions_save'){
    rh24_require_admin();$id=(string)($data['id']??'');$q=$db->prepare('SELECT role FROM users WHERE id=?');$q->execute([$id]);$role=(string)($q->fetchColumn()?:'');if($role==='')out(['ok'=>false,'error'=>'Benutzer nicht gefunden'],404);if($role==='admin')out(['ok'=>false,'error'=>'Administratoren besitzen immer Vollzugriff.'],422);
    $permissions=is_array($data['permissions']??null)?array_values(array_intersect(array_map('strval',$data['permissions']),array_keys(rh24_permission_catalog()))):[];
    $db->prepare('UPDATE users SET permissions_json=?,updated_at=NOW() WHERE id=?')->execute([rh24_json_encode($permissions),$id]);rh24_audit('permissions_update','user',$id,['permissions'=>$permissions]);out(['ok'=>true]);
  }

  if($action==='welcome_resend'){
    rh24_require_admin();$id=(string)($data['id']??'');$ok=rh24_send_welcome_email($id);rh24_audit('welcome_resend','user',$id,['sent'=>$ok]);if(!$ok)out(['ok'=>false,'error'=>'Begrüßungsmail konnte nicht versendet werden. Bitte E-Mail-Adresse und STRATO-Mailversand prüfen.'],422);out(['ok'=>true]);
  }

  if($action==='document_get'){
    $orderNo=trim((string)($data['order_no']??''));$type=(string)($data['type']??'invoice');
    if($orderNo==='')out(['ok'=>false,'error'=>'Bestellnummer fehlt'],422);$doc=rh24_get_or_create_document_v77($orderNo,$type);out(['ok'=>true,'document'=>$doc]);
  }

  if($action==='document_save'){
    rh24_require_admin();$id=(string)($data['id']??'');$q=$db->prepare('SELECT * FROM documents WHERE id=?');$q->execute([$id]);$doc=$q->fetch();if(!$doc)out(['ok'=>false,'error'=>'Dokument nicht gefunden'],404);
    if(in_array((string)$doc['status'],['issued','cancelled'],true)||!empty($doc['locked_at']))out(['ok'=>false,'error'=>'Ausgegebene oder stornierte Dokumente sind gesperrt. Bitte einen Storno-/Korrekturbeleg verwenden.'],409);
    $payload=is_array($data['payload']??null)?$data['payload']:[];if(!$payload)out(['ok'=>false,'error'=>'Dokumentdaten fehlen'],422);$note=trim((string)($data['change_note']??'Bearbeitet im Orgaboard'));
    $version=(int)$doc['version_no']+1;$uid=rh24_user_id();
    $db->beginTransaction();$db->prepare('UPDATE documents SET payload_json=?,version_no=?,note=?,updated_by=?,updated_at=NOW() WHERE id=?')->execute([rh24_json_encode($payload),$version,$note,$uid,$id]);
    $db->prepare('INSERT INTO document_versions(document_id,version_no,payload_json,change_note,edited_by,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$id,$version,rh24_json_encode($payload),$note,$uid]);$db->commit();
    rh24_audit('document_save','document',$id,['version'=>$version,'note'=>$note]);out(['ok'=>true,'document'=>rh24_document_row($id)]);
  }

  if($action==='document_status'){
    rh24_require_admin();$id=(string)($data['id']??'');$status=(string)($data['status']??'draft');if(!in_array($status,['draft','issued'],true))out(['ok'=>false,'error'=>'Stornierungen sind nur über den Stornobeleg möglich.'],422);
    $current=rh24_document_row($id);if(!$current)out(['ok'=>false,'error'=>'Dokument nicht gefunden'],404);
    if($status==='draft'&&$current['status']!=='draft')out(['ok'=>false,'error'=>'Ein ausgegebenes Dokument kann nicht in den Entwurfsstatus zurückgesetzt werden.'],409);
    if($status==='issued'){if(!rh24_invoice_profile_readiness()['ready'])out(['ok'=>false,'error'=>'Rechnungsprofil ist unvollständig: '.implode(', ',rh24_invoice_profile_readiness()['missing'])],422);$doc=rh24_issue_document_v77($id);out(['ok'=>true,'document'=>$doc]);}
    out(['ok'=>true,'document'=>$current]);
  }

  if($action==='document_bundle_generate'){
    rh24_require_admin();$orderNo=trim((string)($data['order_no']??''));if($orderNo==='')out(['ok'=>false,'error'=>'Bestellnummer fehlt'],422);$bundle=rh24_order_document_bundle($orderNo,!empty($data['issue']));out(['ok'=>true]+$bundle);
  }
  if($action==='document_email'){
    rh24_require_admin();$orderNo=trim((string)($data['order_no']??''));if($orderNo==='')out(['ok'=>false,'error'=>'Bestellnummer fehlt'],422);$r=rh24_email_order_documents($orderNo,trim((string)($data['email']??'')));if(!$r['ok'])out(['ok'=>false,'error'=>$r['reason']==='profile_incomplete'?'Rechnungsprofil unvollständig: '.implode(', ',$r['missing']??[]):'Dokumenten-E-Mail konnte nicht versendet werden.'],422);out(['ok'=>true,'message'=>'Rechnung und Lieferschein wurden per E-Mail versendet.']);
  }
  if($action==='document_history'){
    if(!rh24_can('view_documents'))out(['ok'=>false,'error'=>'Keine Berechtigung'],403);$id=(string)($data['id']??'');out(['ok'=>true,'versions'=>rh24_document_versions_v77($id)]);
  }
  if($action==='invoice_profile_save'){
    rh24_require_admin();$fields=['company_name','owner','street','zip','city','country','phone','email','website','tax_no','vat_id','iban','bic','bank_name','payment_days','footer'];foreach($fields as $f){$v=trim((string)($data[$f]??''));if($f==='payment_days')$v=(string)max(1,min(60,(int)$v));rh24_setting_set('invoice_'.$f,$v);}rh24_setting_set('invoice_auto_email',!empty($data['auto_email'])?'1':'0');$ready=rh24_invoice_profile_readiness();rh24_audit('invoice_profile_save','settings','invoice_profile',['ready'=>$ready['ready'],'missing'=>$ready['missing']]);out(['ok'=>true,'profile'=>rh24_invoice_profile(),'readiness'=>$ready]);
  }
  if($action==='invoice_cancel'){
    rh24_require_admin();$id=(string)($data['id']??'');$reason=trim((string)($data['reason']??''));try{$r=rh24_cancel_invoice_v77($id,$reason,!isset($data['email_customer'])||!empty($data['email_customer']));out(['ok'=>true]+$r);}catch(InvalidArgumentException $e){out(['ok'=>false,'error'=>$e->getMessage()],422);}
  }

  if($action==='cost_profile_save'){
    rh24_require_admin();$pid=(string)($data['product_id']??'');if($pid==='')out(['ok'=>false,'error'=>'Produkt fehlt'],422);
    $fields=['material_cost','labor_minutes','labor_hourly_rate','packaging_cost','other_cost','overhead_percent','selling_fee_percent','target_margin_percent','vat_percent'];$v=[];foreach($fields as $f)$v[$f]=max(0,(float)($data[$f]??0));
    if($v['target_margin_percent']+$v['selling_fee_percent']>=90)out(['ok'=>false,'error'=>'Zielmarge und Verkaufsgebühren sind zusammen zu hoch'],422);
    $direct=$v['material_cost']+($v['labor_minutes']/60*$v['labor_hourly_rate'])+$v['packaging_cost']+$v['other_cost'];$cost=$direct*(1+$v['overhead_percent']/100);$net=$cost/max(.05,1-($v['target_margin_percent']+$v['selling_fee_percent'])/100);$gross=$net*(1+$v['vat_percent']/100);$gross=ceil($gross*10)/10-0.05;if($gross<0)$gross=0;$gross=round($gross,2);
    $db->prepare("INSERT INTO product_cost_profiles(product_id,material_cost,labor_minutes,labor_hourly_rate,packaging_cost,other_cost,overhead_percent,selling_fee_percent,target_margin_percent,vat_percent,calculated_gross,updated_by,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE material_cost=VALUES(material_cost),labor_minutes=VALUES(labor_minutes),labor_hourly_rate=VALUES(labor_hourly_rate),packaging_cost=VALUES(packaging_cost),other_cost=VALUES(other_cost),overhead_percent=VALUES(overhead_percent),selling_fee_percent=VALUES(selling_fee_percent),target_margin_percent=VALUES(target_margin_percent),vat_percent=VALUES(vat_percent),calculated_gross=VALUES(calculated_gross),updated_by=VALUES(updated_by),updated_at=NOW()")
      ->execute([$pid,$v['material_cost'],$v['labor_minutes'],$v['labor_hourly_rate'],$v['packaging_cost'],$v['other_cost'],$v['overhead_percent'],$v['selling_fee_percent'],$v['target_margin_percent'],$v['vat_percent'],$gross,rh24_user_id()]);
    rh24_audit('cost_profile_save','product',$pid,['recommended_gross'=>$gross]);out(['ok'=>true,'recommended_gross'=>$gross,'total_cost'=>round($cost,2)]);
  }

  if($action==='cost_price_apply'){
    rh24_require_admin();$pid=(string)($data['product_id']??'');$price=max(0,(float)($data['price']??0));if($pid===''||$price<=0)out(['ok'=>false,'error'=>'Produkt und Preis erforderlich'],422);
    $db->prepare('UPDATE products SET base_price=?,updated_at=NOW() WHERE id=?')->execute([$price,$pid]);rh24_audit('price_from_calculator','product',$pid,['price'=>$price]);out(['ok'=>true]);
  }

  if($action==='message_send'){
    rh24_require_permission('send_messages');$recipient=(string)($data['recipient_user_id']??'');$subject=trim((string)($data['subject']??''));$text=trim((string)($data['body']??''));if($recipient===''||$text==='')out(['ok'=>false,'error'=>'Empfänger und Nachricht sind erforderlich'],422);
    $q=$db->prepare("SELECT role FROM users WHERE id=? AND status='active'");$q->execute([$recipient]);$recipientRole=(string)($q->fetchColumn()?:'');if($recipientRole==='')out(['ok'=>false,'error'=>'Empfänger nicht gefunden'],404);
    if(!rh24_is_admin()&&$recipientRole!=='admin')out(['ok'=>false,'error'=>'Außendienst kann Nachrichten nur an Administratoren senden'],403);
    $thread=trim((string)($data['thread_id']??''));if($thread==='')$thread='THR-'.strtoupper(bin2hex(random_bytes(5)));$id='MSG-'.strtoupper(bin2hex(random_bytes(6)));
    $db->prepare('INSERT INTO messages(id,thread_id,sender_user_id,recipient_user_id,subject,body,read_at,created_at) VALUES(?,?,?,?,?,?,NULL,NOW())')->execute([$id,$thread,rh24_user_id(),$recipient,$subject,$text]);rh24_audit('message_send','message',$id,['recipient'=>$recipient]);out(['ok'=>true,'id'=>$id,'thread_id'=>$thread]);
  }

  if($action==='message_read'){
    $id=(string)($data['id']??'');$db->prepare('UPDATE messages SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND recipient_user_id=?')->execute([$id,rh24_user_id()]);out(['ok'=>true]);
  }


  out(['ok'=>false,'error'=>'Unbekannte Aktion'],400);
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();out(['ok'=>false,'error'=>$e->getMessage()],500);}
