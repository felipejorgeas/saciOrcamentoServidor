<?php

define('WService_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once WService_DIR . 'lib/connect.inc.php';
require_once WService_DIR . 'classes/Prd.class.php';
require_once WService_DIR . 'classes/XML2Array.class.php';
require_once WService_DIR . 'nusoap/nusoap.php';

/* obtendo algumas configuracoes do sistema */
$conf = getConfig();
$url_saciWSPedido = sprintf("%s/pedidows.php", $conf['SISTEMA']['saciWS']);
$storeno = $conf["MISC"]['loja'];
$pdvno = $conf["MISC"]['pdv'];
$empno = $conf["MISC"]['funcionario'];

$wscallback = $_GET['wscallback'];
$orcamento = $_GET['orcamento'];

//$orcamento = "<orcamentos><orcamento><produtos><produto><prdno>1</prdno><grade></grade><quantidade>1</quantidade></produto></produtos></orcamento></orcamentos>";
//$wscallback = "";

$conf = getConfig();

// @var $adodb ADODB_mysql
$adodb = EacConnect::getInstance();

// testa se a conexao foi bem sucedida
if (!$adodb->IsConnected()) {
  echo $adodb->ErrorMsg();
  exit(0);
}

$data = date("Ymd");
$data_full = date("d/m/Y - H:i") . "hs";

$status = 1; //1 - Orçamento

$orcamento = XML2Array::createArray($orcamento);

if (is_array($orcamento['orcamentos']['orcamento']['produtos']['produto'][0]))
  $codigos = $orcamento['orcamentos']['orcamento']['produtos']['produto'];
else
  $codigos = $orcamento['orcamentos']['orcamento']['produtos'];

$valor_total = 0;
$produtos = "";
foreach ($codigos as $produto) {

  $prdno = $produto['prdno'];
  $grade = $produto['grade'];
  $preco = 1000;
  $qtde = $produto['quantidade'] * 1000;
  $valor_total += ($preco * ($qtde / 1000));

  $produtos .= sprintf("<produto>
        <prdno>%s</prdno>
        <grade>%s</grade>
        <qtty>%s</qtty>
        <preco_unitario>%s</preco_unitario>
        <codigo_endereco_entrega>0</codigo_endereco_entrega>
        <loja_retira>0</loja_retira>
      </produto>", $prdno, $grade, $qtde, $preco);
}

$dados = sprintf("<dados>
      <codigo_loja>%s</codigo_loja>
      <codigo_pedido>0</codigo_pedido>
      <codigo_pedido_web>0</codigo_pedido_web>
      <data_pedido>%s</data_pedido>
      <codigo_pdv>%s</codigo_pdv>
      <codigo_funcionario>%s</codigo_funcionario>
      <codigo_cliente>0</codigo_cliente>
      <valor_desconto>0</valor_desconto>
      <valor_total>%s</valor_total>
      <situacao>%s</situacao>
      <codigo_endereco_entrega>0</codigo_endereco_entrega>
      <codigo_transportadora>0</codigo_transportadora>
      <bloqueado_separacao>0</bloqueado_separacao>
      <valor_frete>0</valor_frete>
      <tipo_frete>0</tipo_frete>
      %s
      </dados>", $storeno, $data, $pdvno, $empno, $valor_total, $status, $produtos);

// url de ws
$client = new nusoap_client("$url_saciWSPedido");
//$client->soap_defencoding = 'UTF-8';
//$client->http_encoding='UTF-8';
//$client->defencoding='UTF-8';

$client->useHTTPPersistentConnection();
//
//      // verifica se houve algum erro
//      $err = $client->getError();
//      if ($err) {
//        echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
//        echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
//        exit();
//      }
//
//      // serial do cliente
$serail_number_cliente = '00000999'; //config();
//
//      // monta os parametros a serem enviados
$params = array(
    'crypt' => $serail_number_cliente,
    'dados' => $dados
);
//
//      // realiza a chamada de um metodo do ws passando os paramentros
$result = $client->call('atualizaPedidoPorCodigoInterno', $params);
//
//      // verifica se houve algum erro
//      if ($client->fault) {
//      	echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
//      } else {
//      	$err = $client->getError();
//      	if ($err) {
//      		echo '<h2>Error</h2><pre>' . $err . '</pre>';
//      	} else {
////            if($xml_to_array){
////              // converte o retorno xml em um array
////              $result = xml2array($result);
////
////              // exibe o retorno
////               echo '<h2>Result</h2><pre>'; print_r($result); echo '</pre>';
////            }else{
////              // exibe o retorno
////      		echo '<h2>Result</h2><pre>' . htmlspecialchars($result, ENT_QUOTES) .'</pre>';
////            }
//      	}
//      }
//      /*
//      // exibe informacoes complementares
//      echo '<h2>Request</h2><pre>' . $client->request . '</pre>';
//      echo '<h2>Response</h2><pre>' . $client->response . '</pre>';
//      echo '<h2>Debug</h2><pre>' . $client->getDebug() . '</pre>';
//      */
//

$result = explode('resultado', $client->getDebug());
$res = htmlspecialchars_decode("<resultado" . $result[1] . "resultado>");

$res = XML2Array::createArray($res);

//echo $res['resultado']['dados']['pedido']['codigo_pedido'];
//print_r($res);

$xml = "<wsresult>";
if ($res['resultado']['sucesso']) {
  $ordno = $res['resultado']['dados']['pedido']['codigo_pedido'];

  $xml .= "<wsstatus>1</wsstatus>";
  $xml .= sprintf("<orcamento><codigo>%s</codigo><data>%s</data></orcamento>", $ordno, $data_full);
} else {
  $xml .= "<wsstatus>0</wsstatus>";
}
$xml .= "</wsresult>";

header('Access-Control-Allow-Origin: "*"');
echo sprintf('%s("%s");', $wscallback, $xml);
exit;
?>
