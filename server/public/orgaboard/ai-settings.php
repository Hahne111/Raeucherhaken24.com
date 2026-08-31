<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
rh24_require_login();
rh24_require_admin();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function ais_out(array $data,int $status=200): never {
    http_response_code($status);
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function ais_source(): string {
    if(trim((string)(getenv('OPENAI_API_KEY')?:''))!=='') return 'environment';
    if(trim((string)(rh24_ai_config()['api_key']??''))!=='') return 'private_file';
    return 'none';
}
function ais_private_writable(): bool {
    $dir=dirname(RH24_AI_CONFIG_FILE);
    if(is_dir($dir)) return is_writable($dir);
    $parent=dirname($dir);
    return is_dir($parent) && is_writable($parent);
}
function ais_status(): array {
    $key=rh24_openai_api_key();
    $source=ais_source();
    $suffix=$key!==''?'••••'.substr($key,-4):'';
    return [
        'configured'=>$key!=='',
        'source'=>$source,
        'source_label'=>$source==='environment'
            ?'Server-Umgebungsvariable'
            :($source==='private_file'?'Geschützte Orgaboard-Konfiguration':'Nicht eingerichtet'),
        'model'=>rh24_openai_model('product'),
        'key_hint'=>$suffix,
        'curl_available'=>function_exists('curl_init'),
        'config_writable'=>ais_private_writable(),
    ];
}
function ais_write(array $cfg): void {
    $dir=dirname(RH24_AI_CONFIG_FILE);
    if(!is_dir($dir)&&!@mkdir($dir,0750,true)&&!is_dir($dir)){
        throw new RuntimeException('Privater Konfigurationsordner konnte nicht erstellt werden.');
    }
    if(!is_writable($dir)){
        throw new RuntimeException('Der Ordner /orgaboard/private ist nicht schreibbar. Bitte beim Hoster Schreibrechte für PHP freigeben.');
    }
    $deny=$dir.'/.htaccess';
    if(!is_file($deny)){
        @file_put_contents($deny,"<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
    $php="<?php\n// Automatisch durch das Räucherhaken24 Orgaboard erzeugt. Nicht veröffentlichen.\nreturn ".var_export($cfg,true).";\n";
    $tmp=RH24_AI_CONFIG_FILE.'.tmp';
    if(@file_put_contents($tmp,$php,LOCK_EX)===false){
        throw new RuntimeException('KI-Konfiguration konnte nicht geschrieben werden. Schreibrechte für /orgaboard/private prüfen.');
    }
    @chmod($tmp,0600);
    if(!@rename($tmp,RH24_AI_CONFIG_FILE)){
        @unlink($tmp);
        throw new RuntimeException('KI-Konfiguration konnte nicht aktiviert werden.');
    }
    @chmod(RH24_AI_CONFIG_FILE,0600);
}
function ais_api_error(int $status,string $body,string $curlError=''): string {
    $msg=trim($curlError);
    $code='';
    if($body!==''){
        $d=json_decode($body,true);
        if(is_array($d)){
            $apiMsg=trim((string)($d['error']['message']??''));
            $code=trim((string)($d['error']['code']??''));
            if($apiMsg!=='') $msg=$apiMsg;
        }
    }
    if($status===401) return 'API-Schlüssel wurde von OpenAI abgelehnt. Bitte einen neuen Schlüssel speichern.';
    if($status===403) return 'Der API-Schlüssel hat keinen Zugriff auf das gewählte Modell bzw. Projekt.';
    if($status===404) return 'Das gewählte Modell ist für dieses API-Projekt nicht verfügbar.';
    if($status===429){
        $suffix=$code!==''?' ('.$code.')':'';
        return 'OpenAI hat die Anfrage begrenzt. Bitte API-Abrechnung/Guthaben und Nutzungslimits des OpenAI-Projekts prüfen'.$suffix.'.';
    }
    if($status===400 && $msg!=='') return 'OpenAI hat die Anfrage abgelehnt: '.mb_substr($msg,0,260);
    if($status===0 && $msg!=='') return 'Server konnte OpenAI nicht erreichen: '.mb_substr($msg,0,260);
    if($msg!=='') return 'OpenAI API · HTTP '.$status.': '.mb_substr($msg,0,260);
    return 'OpenAI API · HTTP '.$status;
}
function ais_test(string $key,string $model): array {
    if(!function_exists('curl_init')){
        return ['ok'=>false,'message'=>'PHP-cURL ist auf dem Server nicht verfügbar.'];
    }
    // V106.5: echter Minimaltest gegen dieselbe Responses API wie die Produktoptimierung.
    // So werden Schlüssel, Modellzugriff und API-Abrechnung geprüft – nicht nur die Existenz eines Modells.
    $payload=[
        'model'=>$model,
        'instructions'=>'Du bist ein technischer Verbindungstest. Antworte ausschließlich mit OK.',
        'input'=>'Verbindungstest',
        'max_output_tokens'=>24,
        'reasoning'=>['effort'=>'none'],
        'text'=>['verbosity'=>'low'],
        'store'=>false,
    ];
    $ch=curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>[
            'Content-Type: application/json',
            'Authorization: Bearer '.$key
        ],
        CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT=>12,
        CURLOPT_TIMEOUT=>35
    ]);
    $res=curl_exec($ch);
    $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $err=curl_error($ch);
    curl_close($ch);
    if($res!==false&&$status>=200&&$status<300){
        return ['ok'=>true,'message'=>'OpenAI Responses API funktioniert mit Modell '.$model.'.'];
    }
    return ['ok'=>false,'message'=>ais_api_error($status,is_string($res)?$res:'',$err)];
}

if($_SERVER['REQUEST_METHOD']==='GET'){
    ais_out(['ok'=>true,'status'=>ais_status()]);
}
if($_SERVER['REQUEST_METHOD']!=='POST'){
    ais_out(['ok'=>false,'error'=>'Nur GET oder POST erlaubt.'],405);
}
$raw=file_get_contents('php://input')?:'{}';
$data=json_decode($raw,true);
if(!is_array($data)) ais_out(['ok'=>false,'error'=>'Ungültige Anfrage.'],400);
rh24_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??($data['csrf']??null));

$action=(string)($data['action']??'status');

if($action==='save'){
    $existing=rh24_ai_config();
    $key=trim((string)($data['api_key']??''));
    if($key==='') $key=trim((string)($existing['api_key']??''));
    if($key===''||!str_starts_with($key,'sk-')||strlen($key)<30||preg_match('/\s/',$key)){
        ais_out(['ok'=>false,'error'=>'Bitte einen gültigen neuen OpenAI API-Schlüssel eintragen.'],422);
    }
    $model=trim((string)($data['model']??''));
    if($model==='') $model='gpt-5.6-luna';
    if(!preg_match('/^[A-Za-z0-9._-]{2,80}$/',$model)){
        ais_out(['ok'=>false,'error'=>'Ungültiger Modellname.'],422);
    }
    try{
        ais_write([
            'api_key'=>$key,
            'product_model'=>$model,
            'smoky_model'=>$model,
            'updated_at'=>gmdate('c')
        ]);
    }catch(Throwable $e){
        ais_out(['ok'=>false,'error'=>$e->getMessage()],500);
    }
    try{
        rh24_audit('openai_config_save','system','openai',[
            'model'=>$model,'source'=>'private_file','user'=>rh24_user_id()
        ]);
    }catch(Throwable $e){}
    ais_out([
        'ok'=>true,
        'status'=>ais_status(),
        'message'=>'API-Schlüssel sicher gespeichert. Jetzt bitte „Verbindung testen“ ausführen.'
    ]);
}

if($action==='test'){
    $key=trim((string)($data['api_key']??''));
    if($key==='') $key=rh24_openai_api_key();
    $model=trim((string)($data['model']??''));
    if($model==='') $model=rh24_openai_model('product');
    if($key==='') ais_out(['ok'=>false,'error'=>'Noch kein OpenAI API-Schlüssel gespeichert.'],422);
    $test=ais_test($key,$model);
    if(!$test['ok']) ais_out(['ok'=>false,'error'=>'Verbindung fehlgeschlagen: '.$test['message'],'status'=>ais_status()],502);
    ais_out(['ok'=>true,'status'=>ais_status(),'message'=>$test['message']]);
}

if($action==='delete'){
    if(ais_source()==='environment'){
        ais_out(['ok'=>false,'error'=>'Der aktive Schlüssel kommt aus der Server-Umgebungsvariable und kann hier nicht gelöscht werden.'],409);
    }
    if(is_file(RH24_AI_CONFIG_FILE)&&!@unlink(RH24_AI_CONFIG_FILE)){
        ais_out(['ok'=>false,'error'=>'Gespeicherte KI-Konfiguration konnte nicht gelöscht werden.'],500);
    }
    try{rh24_audit('openai_config_delete','system','openai',['user'=>rh24_user_id()]);}catch(Throwable $e){}
    ais_out(['ok'=>true,'status'=>ais_status(),'message'=>'Gespeicherte KI-Konfiguration entfernt.']);
}
ais_out(['ok'=>false,'error'=>'Unbekannte Aktion.'],400);
