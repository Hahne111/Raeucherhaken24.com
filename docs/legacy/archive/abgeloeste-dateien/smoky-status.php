<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$key=(string)getenv('OPENAI_API_KEY');
echo json_encode([
  'ok'=>true,
  'version'=>'56',
  'live_research'=>($key!==''),
  'message'=>($key!==''?'Smoky Live-Recherche ist serverseitig aktiviert.':'Smoky läuft mit lokalen Kurzantworten. Für Live-Webrecherche muss OPENAI_API_KEY am Server gesetzt sein.')
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
