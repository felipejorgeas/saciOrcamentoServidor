<?php

define('WService_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once WService_DIR . 'lib/define.inc.php';
require_once WService_DIR . 'lib/connect.inc.php';
require_once WService_DIR . 'classes/XML2Array.class.php';
require_once WService_DIR . 'nusoap/nusoap.php';

/* obtendo algumas configuracoes do sistema */
$conf = getConfig();
$url_saciWSFuncionario = sprintf("%s/funcionariows.php", $conf['SISTEMA']['saciWS']);

$wscallback = $_GET['wscallback'];
$apelido = $_GET['usuario'];
$senha = $_GET['senha'];

// @var $adodb ADODB_mysql
$adodb = EacConnect::getInstance();

// testa se a conexao foi bem sucedida
if (!$adodb->IsConnected()) {
  echo $adodb->ErrorMsg();
  exit(0);
}

// url de ws
$client = new nusoap_client($url_saciWSFuncionario);
$client->useHTTPPersistentConnection();

// serial do cliente
$serail_number_cliente = '00000999'; //config();

$dados = sprintf("<dados><apelido>%s</apelido><senha>%s</senha></dados>", $apelido, $senha);

// monta os parametros a serem enviados
$params = array(
    'crypt' => $serail_number_cliente,
    'dados' => $dados
);

// realiza a chamada de um metodo do ws passando os paramentros
$result = $client->call('listar', $params);

$res = XML2Array::createArray($result);

//echo '<pre>';
//print_r($res['resultado']);

$xml = "<wsresult>";
if ($res['resultado']['sucesso'] && isset($res['resultado']['dados']['funcionario'])) {
  $funcionario = $res['resultado']['dados']['funcionario'];

  // verifica o nivel de permissao do usuario
  // 0 - usuario sem permissao para usar o app
  // 1 - usuario com permissao para usar o app
  // 2 - usuario com permissao para usar o app e alterar configuracoes
  switch($funcionario['cargo']){
    case EMPTYPE_VENDEDOR:
      $permissao = 1;
      break;
    case EMPTYPE_GERENTE:
      $permissao = 2;
      break;
    case EMPTYPE_DIRETOR:
      $permissao = 2;
      break;
    default:
      $permissao = 0;
  }

  if($permissao == 0){
    $xml .= "<wsstatus>0</wsstatus>";
  }else{
    $xml .= "<wsstatus>1</wsstatus>";
    $xml .= sprintf("<funcionario><nome>%s</nome><email>%s</email><cargo>%s</cargo><loja>%s</loja><permissao>%s</permissao></funcionario>",
            $funcionario['nome_funcionario'], $funcionario['email'], $funcionario['cargo'],
            $funcionario['codigo_loja'], $permissao);
  }
} else {
  $xml .= "<wsstatus>0</wsstatus>";
}
$xml .= "</wsresult>";

header('Access-Control-Allow-Origin: "*"');
echo sprintf("%s('%s')", $wscallback, $xml);
exit;
?>