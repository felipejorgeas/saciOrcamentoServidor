<?php

define('WService_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once WService_DIR . 'lib/define.inc.php';
require_once WService_DIR . 'lib/connect.inc.php';
require_once WService_DIR . 'classes/XML2Array.class.php';
require_once WService_DIR . 'nusoap/nusoap.php';

/* obtendo algumas configuracoes do sistema */
$conf = getConfig();
$ws = sprintf("%s/ambientews.php", $conf['SISTEMA']['saciWS']);

$wscallback = $_GET['wscallback'];

// @var $adodb ADODB_mysql
$adodb = EacConnect::getInstance();

// testa se a conexao foi bem sucedida
if (!$adodb->IsConnected()) {
  echo $adodb->ErrorMsg();
  exit(0);
}

// url de ws
$client = new nusoap_client($ws);
$client->useHTTPPersistentConnection();

// serial do cliente
$serail_number_cliente = '00000999'; //config();

// monta os parametros a serem enviados
$params = array(
    'crypt' => $serail_number_cliente,
    'dados' => "<dados></dados>"
);

// realiza a chamada de um metodo do ws passando os paramentros
$result = $client->call('listar', $params);

$res = XML2Array::createArray($result);

//echo '<pre>';
//print_r($res);
//exit;

$xml = "<wsresult>";

$xml .= "<wsstatus>1</wsstatus>";
$xml .= "<ambientes>";

// ambiente padrao
$xml .= "<ambiente><codigo>0</codigo><nome>Sem ambiente</nome></ambiente>";

if ($res['resultado']['sucesso'] && isset($res['resultado']['dados']['ambiente'])) {
  if(isset($res['resultado']['dados']['ambiente'][0]))
    $ambientes = $res['resultado']['dados']['ambiente'];
  else
    $ambientes[] = $res['resultado']['dados']['ambiente'];

  foreach($ambientes as $ambiente){
    $xml .= sprintf("<ambiente><codigo>%s</codigo><nome>%s</nome></ambiente>",
            $ambiente['codigo_ambiente'], $ambiente['nome_do_ambiente']);
  }
}

$xml .= "</ambientes>";
$xml .= "</wsresult>";

header('Access-Control-Allow-Origin: "*"');
echo sprintf("%s('%s')", $wscallback, $xml);
exit;
?>