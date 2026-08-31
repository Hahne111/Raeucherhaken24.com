<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
rh24_require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function pai_out(array $data,int $status=200): never {
    http_response_code($status);
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function pai_api_error(int $status,string $body,string $curlError=''): string {
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
    if($status===401) return 'Der gespeicherte OpenAI API-Schlüssel ist ungültig oder wurde widerrufen. Bitte unter „KI verbinden / testen“ einen neuen Schlüssel speichern.';
    if($status===403) return 'Der API-Schlüssel hat keinen Zugriff auf das gewählte Modell oder API-Projekt.';
    if($status===404) return 'Das gewählte OpenAI-Modell ist für dieses API-Projekt nicht verfügbar.';
    if($status===429){
        $suffix=$code!==''?' ('.$code.')':'';
        return 'OpenAI hat die Anfrage begrenzt. Bitte API-Abrechnung/Guthaben und Nutzungslimits des OpenAI-Projekts prüfen'.$suffix.'.';
    }
    if($status===0 && $msg!=='') return 'Der Webserver konnte OpenAI nicht erreichen: '.mb_substr($msg,0,260);
    if($msg!=='') return 'OpenAI API · HTTP '.$status.': '.mb_substr($msg,0,260);
    return 'OpenAI API · HTTP '.$status;
}
if($_SERVER['REQUEST_METHOD']!=='POST') pai_out(['ok'=>false,'error'=>'Nur POST erlaubt.'],405);

$raw=file_get_contents('php://input')?:'{}';
$data=json_decode($raw,true);
if(!is_array($data)) pai_out(['ok'=>false,'error'=>'Ungültige Anfrage.'],400);
rh24_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??($data['csrf']??null));
if(!rh24_is_admin()&&!rh24_can('edit_products')){
    pai_out(['ok'=>false,'error'=>'Keine Berechtigung für die KI-Produktoptimierung.'],403);
}
if(!function_exists('curl_init')){
    pai_out(['ok'=>false,'error'=>'PHP-cURL fehlt auf dem Server. Ohne cURL kann das Orgaboard OpenAI nicht erreichen.'],503);
}

$mode=(string)($data['mode']??'all');
if(!in_array($mode,['short','description','seo','all'],true)) $mode='all';
$p=is_array($data['product']??null)?$data['product']:[];
$name=trim((string)($p['name']??''));
if($name==='') pai_out(['ok'=>false,'error'=>'Bitte zuerst einen Produktnamen eintragen.'],422);

$len=function(string $v,int $max): string {
    $v=trim($v);
    return mb_substr($v,0,$max);
};
$features=[];
foreach((array)($p['features']??[]) as $f){
    $f=$len((string)$f,180);
    if($f!==''&&!in_array($f,$features,true)) $features[]=$f;
    if(count($features)>=10) break;
}
$product=[
    'name'=>$len($name,180),
    'category'=>$len((string)($p['category']??''),120),
    'type'=>$len((string)($p['type']??'product'),40),
    'unit'=>$len((string)($p['unit']??''),80),
    'price'=>max(0,(float)($p['price']??0)),
    'article_no'=>$len((string)($p['article_no']??''),60),
    'sku'=>$len((string)($p['sku']??''),80),
    'short_description'=>$len((string)($p['short_description']??''),320),
    'description'=>$len((string)($p['description']??''),5000),
    'features'=>$features,
    'seo_title'=>$len((string)($p['seo_title']??''),180),
    'seo_description'=>$len((string)($p['seo_description']??''),320),
    'seo_keywords'=>$len((string)($p['seo_keywords']??''),500),
    'product_weight_g'=>max(0,(int)($p['product_weight_g']??0)),
    'shipping_weight_g'=>max(0,(int)($p['shipping_weight_g']??0)),
    'audience'=>in_array((string)($p['audience']??'all'),['all','beginner','advanced','b2b'],true)?(string)$p['audience']:'all',
    'tone'=>in_array((string)($p['tone']??'professional'),['professional','technical','simple'],true)?(string)$p['tone']:'professional'
];

$key=rh24_openai_api_key();
if($key===''){
    pai_out(['ok'=>false,'error'=>'KI ist noch nicht eingerichtet. Als Administrator auf „KI verbinden / testen“ klicken und einen neuen OpenAI API-Schlüssel speichern.'],503);
}
$model=rh24_openai_model('product');

$audience=[
    'all'=>'alle Kunden, von Einsteigern bis erfahrenen Nutzern',
    'beginner'=>'Einsteiger; besonders verständlich und ohne unnötige Fachsprache',
    'advanced'=>'erfahrene Räucherer; präzise und fachlich knapp',
    'b2b'=>'Händler und gewerbliche Kunden; sachlich, professionell und nutzenorientiert'
][$product['audience']];
$tone=[
    'professional'=>'professionell, glaubwürdig und verkaufsstark',
    'technical'=>'technisch präzise und nüchtern',
    'simple'=>'einfach, klar und sehr verständlich'
][$product['tone']];
$modeText=[
    'short'=>'Erstelle ausschließlich eine neue, aussagekräftige Kurzbeschreibung. Sie soll den Artikel in 1–2 starken Sätzen sofort verständlich machen und den wichtigsten Kundennutzen klar nennen. Ausführliche Beschreibung, Vorteile und alle SEO-Felder unverändert zurückgeben.',
    'description'=>'Erstelle ausschließlich die ausführliche Produktbeschreibung neu. Kurzbeschreibung, Vorteile und alle SEO-Felder unverändert zurückgeben. Der Text muss leicht verständlich, sprachlich sauber und frei von Rechtschreib-, Grammatik- und Zeichensetzungsfehlern sein.',
    'seo'=>'Optimiere nur SEO-Titel, Meta-Beschreibung und Fokusbegriffe. Produktbeschreibung unverändert zurückgeben.',
    'all'=>'Erstelle eine aussagekräftige Kurzbeschreibung, eine verständliche ausführliche Produktbeschreibung, klare Vorteile und passende SEO-Daten gemeinsam.'
][$mode];

$instructions=<<<TXT
Du bist der interne Produktredakteur und SEO-Assistent von Räucherhaken24. Du arbeitest auf Deutsch und ausschließlich mit den Produktfakten, die dir übergeben werden.

{$modeText}
Zielgruppe: {$audience}.
Stil: {$tone}.

Harte Regeln:
- Erfinde KEINE Eigenschaften, Materialien, Temperaturen, Traglasten, Zertifikate, Spülmaschineneignung, Lieferzeiten, Zutaten, Dosierungen, Garantien oder Herkunftsangaben.
- Wenn eine Information nicht in den Produktdaten steht, lasse sie weg.
- Keine Konkurrenznamen, keine fremden Marken, keine unbelegbaren Superlative.
- Keine HTML-Tags, URLs oder Emojis.
- Schreibe fehlerfreies, natürliches Standarddeutsch. Prüfe vor der Ausgabe Rechtschreibung, Grammatik, Satzbau und Zeichensetzung.
- Vermeide Floskeln, künstliche Werbesprache, Wiederholungen, Bandwurmsätze und SEO-Spam.
- Kurzbeschreibung: 1–2 prägnante Sätze, ideal 80–170 Zeichen, maximal 220 Zeichen. Beginne direkt mit Produkt bzw. Hauptnutzen. Konkret, aussagekräftig und verkaufsstark, aber nicht marktschreierisch. Der Kunde soll in wenigen Sekunden verstehen, was das Produkt ist, wofür es gedacht ist und welchen konkreten Nutzen es bietet.
- Ausführliche Beschreibung: 3–5 kurze, gut lesbare Absätze, ideal 500–1.000 Zeichen. Erkläre zuerst verständlich, was das Produkt ist und wofür es eingesetzt wird. Danach Anwendung/Nutzen und vorhandene Material- oder Ausstattungsmerkmale. Fachbegriffe nur verwenden, wenn sie aus den Produktdaten stammen, und dann so formulieren, dass auch Einsteiger den Text verstehen.
- Die ausführliche Beschreibung soll wie von einem professionellen deutschen Produktredakteur geschrieben wirken: klar, menschlich, sachlich überzeugend und ohne Schreibfehler.
- Vorteile: 3–7 kurze, konkrete Punkte, nur aus belegten Produktfakten.
- SEO-Titel: ungefähr 45–60 Zeichen.
- Meta-Beschreibung: ungefähr 130–155 Zeichen.
- Fokusbegriffe: 3–7 natürliche Suchbegriffe oder Suchphrasen.
TXT;

$input="MODUS: {$mode}\nPRODUKTDATEN (verbindliche Fakten):\n".json_encode(
    $product,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
);

$schema=[
    'type'=>'object',
    'additionalProperties'=>false,
    'properties'=>[
        'short_description'=>['type'=>'string'],
        'description'=>['type'=>'string'],
        'features'=>['type'=>'array','items'=>['type'=>'string'],'maxItems'=>7],
        'seo_title'=>['type'=>'string'],
        'seo_description'=>['type'=>'string'],
        'seo_keywords'=>['type'=>'array','items'=>['type'=>'string'],'maxItems'=>7],
        'note'=>['type'=>'string'],
    ],
    'required'=>[
        'short_description','description','features',
        'seo_title','seo_description','seo_keywords','note'
    ],
];

$payload=[
    'model'=>$model,
    'instructions'=>$instructions,
    'input'=>$input,
    'max_output_tokens'=>1400,
    'reasoning'=>['effort'=>'none'],
    'text'=>[
        'verbosity'=>'low',
        'format'=>[
            'type'=>'json_schema',
            'name'=>'rh24_product_optimization',
            'description'=>'Optimierte Produkt- und SEO-Daten für Räucherhaken24',
            'strict'=>true,
            'schema'=>$schema,
        ],
    ],
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
    CURLOPT_TIMEOUT=>60
]);
$res=curl_exec($ch);
$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
$err=curl_error($ch);
curl_close($ch);

if($res===false||$status<200||$status>=300){
    $detail=pai_api_error($status,is_string($res)?$res:'',$err);
    pai_out(['ok'=>false,'error'=>'KI konnte keine Optimierung erzeugen: '.$detail],502);
}
$d=json_decode((string)$res,true);
if(!is_array($d)){
    pai_out(['ok'=>false,'error'=>'OpenAI lieferte keine verwertbare JSON-Serverantwort.'],502);
}
if(($d['status']??'')==='failed'){
    $apiError=trim((string)($d['error']['message']??''));
    pai_out(['ok'=>false,'error'=>'OpenAI konnte die Anfrage nicht abschließen'.($apiError!==''?': '.mb_substr($apiError,0,260):'.')],502);
}

$text='';
if(isset($d['output_text'])&&is_string($d['output_text'])){
    $text=trim($d['output_text']);
} else {
    foreach(($d['output']??[]) as $item){
        if(($item['type']??'')!=='message') continue;
        foreach(($item['content']??[]) as $c){
            if(($c['type']??'')==='output_text'){
                $text.=($text?"\n":'').($c['text']??'');
            }
        }
    }
}
$text=trim($text);
$result=json_decode($text,true);
if(!is_array($result)){
    pai_out([
        'ok'=>false,
        'error'=>'Die KI-Antwort konnte trotz erfolgreicher Verbindung nicht als Produktdaten verarbeitet werden. Bitte „KI verbinden / testen“ ausführen und erneut versuchen.'
    ],502);
}

$clean=[
    'short_description'=>$len((string)($result['short_description']??$product['short_description']),220),
    'description'=>$len((string)($result['description']??$product['description']),3500),
    'features'=>[],
    'seo_title'=>$len((string)($result['seo_title']??$product['seo_title']),80),
    'seo_description'=>$len((string)($result['seo_description']??$product['seo_description']),180),
    'seo_keywords'=>[],
];
foreach((array)($result['features']??$features) as $f){
    $f=$len((string)$f,180);
    if($f!==''&&!in_array($f,$clean['features'],true)) $clean['features'][]=$f;
    if(count($clean['features'])>=7) break;
}
foreach((array)($result['seo_keywords']??[]) as $k){
    $k=$len((string)$k,90);
    if($k!==''&&!in_array($k,$clean['seo_keywords'],true)) $clean['seo_keywords'][]=$k;
    if(count($clean['seo_keywords'])>=7) break;
}
if($mode==='description'){
    $clean['short_description']=$product['short_description'];
    $clean['features']=$features;
    $clean['seo_title']=$product['seo_title'];
    $clean['seo_description']=$product['seo_description'];
    $clean['seo_keywords']=array_values(array_filter(array_map(
        'trim',preg_split('/[,;]+/',$product['seo_keywords'])?:[]
    )));
}
if($mode==='seo'){
    $clean['short_description']=$product['short_description'];
    $clean['description']=$product['description'];
    $clean['features']=$features;
}
if($mode==='short'){
    $clean['description']=$product['description'];
    $clean['features']=$features;
    $clean['seo_title']=$product['seo_title'];
    $clean['seo_description']=$product['seo_description'];
    $clean['seo_keywords']=array_values(array_filter(array_map(
        'trim',preg_split('/[,;]+/',$product['seo_keywords'])?:[]
    )));
}
$note=$len((string)($result['note']??'KI-Vorschlag erzeugt. Bitte vor dem Speichern fachlich prüfen.'),220);
try{
    rh24_audit('product_ai_optimize','product',$product['article_no']?:$product['name'],[
        'mode'=>$mode,'model'=>$model,'user'=>(string)($_SESSION['rh24_user_id']??'')
    ]);
}catch(Throwable $e){}
pai_out(['ok'=>true,'result'=>$clean,'note'=>$note,'mode'=>$mode]);
