<?php

define('WService_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once WService_DIR . 'lib/define.inc.php';
require_once WService_DIR . 'lib/connect.inc.php';
require_once WService_DIR . 'classes/Prd.class.php';

/* obtendo algumas configuracoes do sistema */
$conf = getConfig();
$url_imgs = $conf['SISTEMA']['urlImgs'];
$dir_imgs = $conf['SISTEMA']['dirImgs'];

/* cria a conexao com o banco de dados */
$adodb = EacConnect::getInstance();

/* testa se a conexao foi bem sucedida */
if (!$adodb->IsConnected()) {
  echo $adodb->ErrorMsg();
  exit(0);
}

/* variaveis recebidas na requisicao */
$wscallback = $_GET['wscallback'];
$barcode = $_GET['barcode'];
$tag_produtos = 0;

/* define o caminho completo do diteretorio de imagens do produto buscado */
$dir_full = sprintf("%s/%s", $dir_imgs, $barcode);

/* variaveis de controle para o criacao do xml de retorno */
$existe_produto = false;
$existe_imgs = false;

/* cria a tag contendo as informacoes sobre o produto */
$xml_produto = "<produto>";

/* busca o produto no SACI */
$obj = new Prd($conf['DATABASE']["database"]);
$produto = $obj->getPrd($barcode);

if($produto){
  $xml_produto .= sprintf("<nome>%s</nome>", $produto['name']);
  $xml_produto .= sprintf("<barcode>%s</barcode>", $barcode);
  $xml_produto .= sprintf("<codigo>%s</codigo>", trim($produto['prdno']));
  $xml_produto .= sprintf("<grade>%s</grade>", $produto['grade']);
  $existe_produto = true;
}

/* cria a tag contendo as imagens do produto */
$xml_imgs = "<imgs>";
/* verifica se o diretorio existe */
if (file_exists($dir_full)) {
  /* se o diretorio existir, percorre o diretorio buscando as imagens */
  $handle = opendir($dir_full);
  while (false !== ($file = readdir($handle))) {
    if (in_array($file, array(".", "..")))
      continue;
    $xml_imgs .= sprintf("<arquivo>%s/%s/%s</arquivo>", $url_imgs, $barcode, $file);
    $existe_imgs = true;
  }
}
/* fecha a tag contendo as imagens do produto */
$xml_imgs .= "</imgs>";

/* caso nao exista imagens nao retorna o produto */
if ($existe_imgs)
  $xml_produto .= $xml_imgs;

/* fecha a tag contendo as informacoes sobre o produto */
$xml_produto .= "</produto>";

/* monta o xml de retorno */
$xml = "<wsresult>";
if ($existe_produto) {
  $xml .= "<wsstatus>1</wsstatus>";
  $xml .= $xml_produto;
} else {
  $xml .= "<wsstatus>0</wsstatus>";
}
$xml .= "</wsresult>";

/* retorno */
header('Access-Control-Allow-Origin: "*"');
echo sprintf('%s("%s");', $wscallback, $xml);
exit;
?>
