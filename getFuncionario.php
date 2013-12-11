<?php

define('WService_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once WService_DIR . 'lib/define.inc.php';
require_once WService_DIR . 'lib/connect.inc.php';
require_once WService_DIR . 'classes/XML2Array.class.php';
require_once WService_DIR . 'nusoap/nusoap.php';

/* obtendo algumas configuracoes do sistema */
$conf = getConfig();
$ws = sprintf("%s/funcionariows.php", $conf['SISTEMA']['saciWS']);

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
$client = new nusoap_client($ws);
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

$wsresult = "";
$wsstatus = 0;

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

  if($permissao > 0){
    $wsstatus = 1;
    $wsresult = "<funcionario>";
    $wsresult .= sprintf("<codigo>%s</codigo>", $funcionario['codigo_funcionario']);
    $wsresult .= sprintf("<nome>%s</nome>", $funcionario['nome_funcionario']);
    $wsresult .= sprintf("<email>%s</email>", $funcionario['email']);
    $wsresult .= sprintf("<loja>%s</loja>", $funcionario['codigo_loja']);
    $wsresult .= sprintf("<permissao>%s</permissao>", $permissao);
    $wsresult .= "</funcionario>";
  }
}

/* monta o xml de retorno */
$xml = sprintf("<wsresult><wsstatus>%d</wsstatus>%s</wsresult>", $wsstatus, $wsresult);

header('Access-Control-Allow-Origin: "*"');
echo sprintf("%s('%s')", $wscallback, $xml);
exit;
?>