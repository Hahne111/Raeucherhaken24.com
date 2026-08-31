<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
rh24_require_permission('edit_products');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function done(array $d,int $s=200): never {http_response_code($s);echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function rh24_iso_image_mime(string $file): string {
    $h=@file_get_contents($file,false,null,0,64);if(!is_string($h)||strlen($h)<16||substr($h,4,4)!=='ftyp')return '';
    $brands=[];for($i=8;$i+4<=strlen($h);$i+=4)$brands[]=strtolower(substr($h,$i,4));
    foreach($brands as $b){if(in_array($b,['avif','avis'],true))return 'image/avif';}
    foreach($brands as $b){if(in_array($b,['heic','heix','hevc','hevx','heim','heis','mif1','msf1'],true))return 'image/heic';}
    return '';
}
function rh24_imagick_to_webp(string $src,string $dst): bool {
    if(!class_exists('Imagick'))return false;
    try{
        $im=new Imagick();$im->readImage($src);if($im->getNumberImages()>1)$im->setIteratorIndex(0);
        if(method_exists($im,'autoOrientImage'))@$im->autoOrientImage();
        $w=$im->getImageWidth();$h=$im->getImageHeight();$max=2000;if(max($w,$h)>$max){$im->thumbnailImage($max,$max,true,true);}
        @$im->stripImage();$im->setImageFormat('webp');$im->setImageCompressionQuality(86);@$im->setOption('webp:method','6');
        $ok=$im->writeImage($dst);$im->clear();$im->destroy();return $ok&&is_file($dst)&&filesize($dst)>0;
    }catch(Throwable $e){return false;}
}
function rh24_gd_to_webp(string $src,string $dst,string $mime): bool {
    if(!extension_loaded('gd')||!function_exists('imagewebp'))return false;
    try{
        $im=null;
        if($mime==='image/jpeg'&&function_exists('imagecreatefromjpeg'))$im=@imagecreatefromjpeg($src);
        elseif($mime==='image/png'&&function_exists('imagecreatefrompng'))$im=@imagecreatefrompng($src);
        elseif($mime==='image/webp'&&function_exists('imagecreatefromwebp'))$im=@imagecreatefromwebp($src);
        elseif($mime==='image/avif'&&function_exists('imagecreatefromavif'))$im=@imagecreatefromavif($src);
        if(!$im)return false;$w=imagesx($im);$h=imagesy($im);if($w<1||$h<1){imagedestroy($im);return false;}
        $max=2000;$scale=min(1,$max/max($w,$h));$nw=max(1,(int)round($w*$scale));$nh=max(1,(int)round($h*$scale));
        $out=imagecreatetruecolor($nw,$nh);imagealphablending($out,false);imagesavealpha($out,true);$transparent=imagecolorallocatealpha($out,0,0,0,127);imagefill($out,0,0,$transparent);
        imagecopyresampled($out,$im,0,0,0,0,$nw,$nh,$w,$h);$ok=imagewebp($out,$dst,86);imagedestroy($out);imagedestroy($im);return $ok&&is_file($dst)&&filesize($dst)>0;
    }catch(Throwable $e){return false;}
}
if($_SERVER['REQUEST_METHOD']!=='POST')done(['ok'=>false,'error'=>'Methode nicht erlaubt'],405);
rh24_verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN']??($_POST['csrf']??null));
$id=trim((string)($_POST['id']??''));if(!preg_match('/^[A-Za-z0-9_-]{2,80}$/',$id))done(['ok'=>false,'error'=>'Ungültige Artikel-ID'],422);
if(empty($_FILES['image']))done(['ok'=>false,'error'=>'Kein Bild ausgewählt'],422);
$f=$_FILES['image'];$uploadError=(int)($f['error']??UPLOAD_ERR_OK);if($uploadError!==UPLOAD_ERR_OK){$messages=[UPLOAD_ERR_INI_SIZE=>'Datei überschreitet das Server-Uploadlimit',UPLOAD_ERR_FORM_SIZE=>'Datei ist größer als im Formular erlaubt',UPLOAD_ERR_PARTIAL=>'Datei wurde nur teilweise übertragen',UPLOAD_ERR_NO_FILE=>'Kein Bild ausgewählt',UPLOAD_ERR_NO_TMP_DIR=>'Temporärer Uploadordner fehlt auf dem Server',UPLOAD_ERR_CANT_WRITE=>'Server konnte die Upload-Datei nicht schreiben',UPLOAD_ERR_EXTENSION=>'Eine Server-Erweiterung hat den Upload gestoppt'];done(['ok'=>false,'error'=>$messages[$uploadError]??('Uploadfehler (Code '.$uploadError.')')],422);}
if(empty($f['tmp_name'])||!is_uploaded_file($f['tmp_name']))done(['ok'=>false,'error'=>'Upload-Datei konnte nicht geprüft werden'],422);
if(($f['size']??0)>20*1024*1024)done(['ok'=>false,'error'=>'Bild darf maximal 20 MB groß sein'],422);
$fi=new finfo(FILEINFO_MIME_TYPE);$mime=strtolower((string)$fi->file($f['tmp_name']));$iso=rh24_iso_image_mime($f['tmp_name']);if($iso!=='')$mime=$iso;
$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/avif'=>'avif','image/heic'=>'heic','image/heif'=>'heif','image/heic-sequence'=>'heic','image/heif-sequence'=>'heif'];
if(!isset($allowed[$mime]))done(['ok'=>false,'error'=>'Erlaubt sind JPG/JPEG, PNG, WebP, AVIF, HEIC und HEIF'],422);
if(in_array($mime,['image/jpeg','image/png','image/webp'],true)&&@getimagesize($f['tmp_name'])===false)done(['ok'=>false,'error'=>'Datei ist kein gültiges Bild'],422);
$db=rh24_db();$q=$db->prepare('SELECT id,image_path FROM products WHERE id=?');$q->execute([$id]);$row=$q->fetch();if(!$row)done(['ok'=>false,'error'=>'Produkt nicht gefunden'],404);
$dir=dirname(__DIR__).'/uploads/products';if(!is_dir($dir)&&!mkdir($dir,0755,true))done(['ok'=>false,'error'=>'Uploadordner konnte nicht erstellt werden'],500);
$token=$id.'-'.date('YmdHis').'-'.bin2hex(random_bytes(3));$webp=$dir.'/'.$token.'.webp';$optimized=false;$engine='original';
if(rh24_imagick_to_webp($f['tmp_name'],$webp)){$optimized=true;$engine='imagick';}
elseif(!str_contains($mime,'heic')&&!str_contains($mime,'heif')&&rh24_gd_to_webp($f['tmp_name'],$webp,$mime)){$optimized=true;$engine='gd';}
if($optimized){$name=basename($webp);$target=$webp;$storedMime='image/webp';}
else{
    if(str_contains($mime,'heic')||str_contains($mime,'heif'))done(['ok'=>false,'error'=>'HEIC/HEIF wurde erkannt, konnte auf diesem Server aber nicht in WebP umgewandelt werden. Der Browser hat keine Vorab-Konvertierung geliefert und auf dem Server fehlt ein HEIC-fähiger Imagick/libheif-Konverter. Bitte das Foto als JPG/PNG/WebP/AVIF exportieren oder Imagick mit HEIC-Unterstützung bei STRATO aktivieren.'],422);
    $ext=$allowed[$mime];$name=$token.'.'.$ext;$target=$dir.'/'.$name;if(!move_uploaded_file($f['tmp_name'],$target))done(['ok'=>false,'error'=>'Bild konnte nicht gespeichert werden'],500);$storedMime=$mime;
}
$path='uploads/products/'.$name;
$old=trim((string)($row['image_path']??''));$db->prepare('UPDATE products SET image_path=?,updated_at=NOW() WHERE id=?')->execute([$path,$id]);
if($old!==''&&$old!==$path&&str_starts_with($old,'uploads/products/')){try{$cq=$db->prepare('SELECT COUNT(*) FROM products WHERE image_path=?');$cq->execute([$old]);if((int)$cq->fetchColumn()===0){$oldFile=dirname(__DIR__).'/'.ltrim($old,'/');if(is_file($oldFile))@unlink($oldFile);}}catch(Throwable $e){}}
$clientOptimized=((string)($_POST['client_optimized']??'0'))==='1';rh24_audit('product_image_upload','product',$id,['image'=>$path,'source_mime'=>$mime,'stored_mime'=>$storedMime,'optimized'=>$optimized||$clientOptimized,'engine'=>$clientOptimized?'browser-webp':$engine,'source_name'=>(string)($_POST['original_name']??$f['name']??'')]);
done(['ok'=>true,'image'=>$path,'mime'=>$storedMime,'optimized'=>$optimized||$clientOptimized,'engine'=>$clientOptimized?'browser-webp':$engine,'max_dimension'=>2000]);
