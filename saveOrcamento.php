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

// valores default
$cliente = 0;
$valor_desconto = 0;
$valor_total = 0;
$transportadora = 0;
$valor_frete = 0;
$bloqueado_sep = 0;
$tipo_frete = 0;
$endereco_entrega = 0;

$wscallback = $_GET['wscallback'];
$orcamento = $_GET['orcamento'];

//$orcamento = "<orcamentos><orcamento><codigo>424</codigo><produtos><produto><prdno>1</prdno><grade></grade><quantidade>1</quantidade></produto></produtos></orcamento></orcamentos>";
//$wscallback = "";

// @var $adodb ADODB_mysql
$adodb = EacConnect::getInstance();

// testa se a conexao foi bem sucedida
if (!$adodb->IsConnected()) {
  echo $adodb->ErrorMsg();
  exit(0);
}

$data = date("Ymd");
$data_full = date("d/m/Y - H:i") . "hs";

$status = 1; //1 - Orcamento

// converte o xml em array
$orcamento = XML2Array::createArray($orcamento);

// obtem o numero de orcamento caso seja para atualizar
$update = $orcamento['orcamentos']['orcamento']['codigo'];

// url de ws
$client = new nusoap_client($url_saciWSPedido);
//$client->soap_defencoding = 'UTF-8';
//$client->http_encoding='UTF-8';
//$client->defencoding='UTF-8';
$client->useHTTPPersistentConnection();

// serial do cliente
$serail_number_cliente = '00000999'; //config();

$codigos = "";

// obtem todos os produto que vieram no xml
if (is_array($orcamento['orcamentos']['orcamento']['produtos']['produto'][0]))
  $codigos = $orcamento['orcamentos']['orcamento']['produtos']['produto'];
else
  $codigos = $orcamento['orcamentos']['orcamento']['produtos'];

$produtos = "";

// concatena cada produto ao xml de produtos
foreach ($codigos as $produto) {
  $prdno = $produto['prdno'];
  $grade = $produto['grade'];
  $preco = 0;
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

// verifica se ira atualizar algum orcamento e busca todos os seus dados
if ($update > 0) {
  $dados = sprintf("<dados><codigo_pedido>%s</codigo_pedido></dados>", $update);

  // monta os parametros a serem enviados
  $params = array(
      'crypt' => $serail_number_cliente,
      'dados' => $dados
  );

  // realiza a chamada de um metodo do ws passando os paramentros
  $result = $client->call('listar', $params);
  $result = explode('resultado', $client->getDebug());
  $res = htmlspecialchars_decode("<resultado" . $result[1] . "resultado>");

  $res = XML2Array::createArray($res);

  $produtos_existentes = "";
  if ($res['resultado']['sucesso']) {
    // obtem os dados do orcamento a ser atualizado
    $data = $res['resultado']['dados']['pedido']['data_pedido'];
    $cliente = $res['resultado']['dados']['pedido']['codigo_cliente'];
    $empno = $res['resultado']['dados']['pedido']['codigo_funcionario'];
    $storeno = $res['resultado']['dados']['pedido']['codigo_loja'];
    $pdvno = $res['resultado']['dados']['pedido']['codigo_pdv'];
    $valor_desconto = $res['resultado']['dados']['pedido']['valor_desconto'];
    $valor_total += $res['resultado']['dados']['pedido']['valor_total'];
    $transportadora = $res['resultado']['dados']['pedido']['codigo_transportadora'];
    $status = $res['resultado']['dados']['pedido']['situacao'];
    $valor_frete = $res['resultado']['dados']['pedido']['valor_frete'];
    $bloqueado_sep = $res['resultado']['dados']['pedido']['bloqueado_separacao'];
    $tipo_frete = $res['resultado']['dados']['pedido']['tipo_frete'];
    $endereco_entrega = $res['resultado']['dados']['pedido']['codigo_endereco_entrega'];

    // obtem todos os produtos ja existentes no orcamento
    $produtos_existentes = $res['resultado']['dados']['pedido']['lista_produtos']['produto'];
  }

  // concatena no xml os produtos ja existentes no orcamento
  foreach ($produtos_existentes as $produto) {
    $prdno = $produto['codigo_produto'];

    if(!($prdno > 0))
      continue;

    $grade = $produto['grade_produto'];
    $preco = 0;
    $qtde = $produto['quantidade'];

    $produtos .= sprintf("<produto>
          <prdno>%s</prdno>
          <grade>%s</grade>
          <qtty>%s</qtty>
          <preco_unitario>%s</preco_unitario>
          <codigo_endereco_entrega>0</codigo_endereco_entrega>
          <loja_retira>0</loja_retira>
        </produto>", $prdno, $grade, $qtde, $preco);
  }
}

$dados = sprintf("<dados>
      <codigo_loja>%s</codigo_loja>
      <codigo_pedido>%s</codigo_pedido>
      <codigo_pedido_web>0</codigo_pedido_web>
      <data_pedido>%s</data_pedido>
      <codigo_pdv>%s</codigo_pdv>
      <codigo_funcionario>%s</codigo_funcionario>
      <codigo_cliente>%s</codigo_cliente>
      <valor_desconto>%s</valor_desconto>
      <valor_total>%s</valor_total>
      <situacao>%s</situacao>
      <codigo_endereco_entrega>%s</codigo_endereco_entrega>
      <codigo_transportadora>%s</codigo_transportadora>
      <bloqueado_separacao>%s</bloqueado_separacao>
      <valor_frete>%s</valor_frete>
      <tipo_frete>%s</tipo_frete>
      %s
      </dados>", $storeno, ($update > 0 ? $update : 0), $data, $pdvno, $empno, $cliente,
      $valor_desconto, $valor_total, $status, $endereco_entrega, $transportadora,
      $bloqueado_sep, $valor_frete, $tipo_frete, $produtos);

// monta os parametros a serem enviados
$params = array(
    'crypt' => $serail_number_cliente,
    'dados' => $dados
);

// realiza a chamada de um metodo do ws passando os paramentros
$result = $client->call('atualizaPedidoPorCodigoInterno', $params);
$result = explode('resultado', $client->getDebug());
$res = htmlspecialchars_decode("<resultado" . $result[1] . "resultado>");

$res = XML2Array::createArray($res);

$xml = "<wsresult>";
if ($res['resultado']['sucesso']) {
  $ordno = $res['resultado']['dados']['pedido']['codigo_pedido'];

  $xml .= "<wsstatus>1</wsstatus>";
  $xml .= sprintf("<orcamento>%s<codigo>%s</codigo><data>%s</data></orcamento>",
          ($update > 0 ? '<update>1</update>' : ''), $ordno, $data_full);
} else {
  $xml .= "<wsstatus>0</wsstatus>";
}
$xml .= "</wsresult>";

header('Access-Control-Allow-Origin: "*"');
echo sprintf('%s("%s");', $wscallback, $xml);
exit;
?>
