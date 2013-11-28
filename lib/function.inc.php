<?php

/**
 * retorna o arquivo de configuração
 *
 * @param <type> $modulo
 * @return array()
 */
function getConfig($modulo = false) {
  $conf = parse_ini_file(WService_DIR . "/config/config.php", true);
  return (is_null($modulo) || !isset($conf[$modulo])) ? $conf : $conf[$modulo];
}

/**
 * retorna o codigo do produto formatado para o padrao do SACI:
 * - se inteiro, alinhado a direita
 * - se string, alinhado a esquerda
 *
 * @param <type> $prdno
 * @return char(16)
 */
function format_prdno($prdno) {
  $prdno = mysql_real_escape_string(trim($prdno));
  $sizeOfPrdNo = 16;

  if (preg_match("/^\d+$/", $prdno))
    $prdno = str_pad($prdno, $sizeOfPrdNo, ' ', STR_PAD_LEFT);
  else
    $prdno = str_pad($prdno, $sizeOfPrdNo, ' ', STR_PAD_RIGHT);

  return($prdno);
}

/**
 * retorna o codigo do cpf_cgc formatado para o padrao Brasileiro:
 * - se tamanho igual 11 posições é um cpf
 * - se tamanho igual 14 posições é um cgc
 *
 * @param <type> $cpfCgc
 * @return char(14 or 15)
 */
function format_CpfCgc($cpfCgc) {

  //$cpfCgc = ereg_replace("[^0-9]", "", $cpfCgc);
  $cpfCgc = preg_replace("#[^0-9]#", "", $cpfCgc);

  //Example: 38.743.738/0001-11
  if (strlen($cpfCgc) == 14) {
    $p1 = substr($cpfCgc, 0, 2);
    $p2 = substr($cpfCgc, 2, 3);
    $p3 = substr($cpfCgc, 5, 3);
    $p4 = substr($cpfCgc, 8, 4);
    $p5 = substr($cpfCgc, 12, 2);
    $cpfCgc = sprintf("%s.%s.%s/%s-%s", $p1, $p2, $p3, $p4, $p5);
  }
  //Example: 562.341.556-34
  elseif (strlen($cpfCgc) == 11) {
    $p1 = substr($cpfCgc, 0, 3);
    $p2 = substr($cpfCgc, 3, 3);
    $p3 = substr($cpfCgc, 6, 3);
    $p4 = substr($cpfCgc, 9, 2);
    $cpfCgc = sprintf("%s.%s.%s-%s", $p1, $p2, $p3, $p4);
  }

  return $cpfCgc;
}

/**
 *
 * @param type $value
 */
function normalizeChars($value) {

  $normalizeChars = array(
      'Š' => 'S', 'š' => 's', 'Ð' => 'D', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
      'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I',
      'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
      'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'S', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
      'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i',
      'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u',
      'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f', '|' => '', '#' => '');

  return strtr($value, $normalizeChars);
}

/* * ************************
 *          setMask        *
 * **************************
  Retorna a mascara para o bit em questao. */

function setMask($bitno) {
  return (1 << $bitno);
}

/**
 *
 * @param type $bits
 * @param type $bitno
 * @return type
 */
function bitOk($bits, $bitno) {
  /* mascara binaria para o bit */
  $mask = setMask($bitno);

  /* verifica se o bit esta ativo ou nao */
  return ((($bits & $mask) == $mask) ? 1 : 0);
}

/* * ************************
 *          setBit         *
 * **************************
  Altera o valor do bit (bitno) presente no
  argumento bits para 0 (set == 0) ou para
  1 (set == 1). */

function setBit($bits, $bitno, $set) {
  /* mascara binaria para o bit */
  $mask = setMask($bitno);

  /* altera o valor do bit para 0 */
  if (!$set)
    return ((($bits | $mask) - $mask));

  /* altera o valor do bit para 1 */
  else
    return ($bits | $mask);
}

/**
 *
 * @param type $time
 * @return type
 */
function timeToSecond($time = "") {
  if (empty($time))
    $time = date("H:i:s");

  /* quebra as partes da hora */
  $time = explode(":", $time);

  $sec = intval(trim($time[0])) * 3600;
  $sec += intval(trim($time[1])) * 60;
  $sec += intval(trim($time[2]));

  return $sec;
}

/**
 * Formata o barcode de acordo com o cliente sendo utilizado
 *
 * @param String $barcode
 * @return FormatedString
 */
function formatBarCode($barcode) {
  $barcode = trim($barcode);
  $sizeOfBarCode = 16;

  if (preg_match("/^\d+$/", $barcode))
    $barcode = str_pad($barcode, $sizeOfBarCode, ' ', STR_PAD_LEFT);
  else
    $barcode = str_pad($barcode, $sizeOfBarCode, ' ', STR_PAD_RIGHT);

  return($barcode);
}

function getPrdBarCode2Codigo($barcode, $db, $conf) {

  $conf = $conf['DATABASE'];
  $db->Connect($conf['hostname'], $conf['username'], $conf['password'], $conf['database']);

  $where = sprintf("barcode='%s'", formatBarCode($barcode));
  $where22 = sprintf("barcode48='%s'", formatBarCode(substr($barcode, 0, 22)));

  //alterado do sql_prdbar
  $SqlQuery = sprintf(" (SELECT prdbar.prdno,
                                prdbar.grade,
                                prd.name,
                                prd.mfno,
                                prd.mfno_ref,
                                prd.taxno,
                                prd.mult,
                                prd.class,
                                prd.grade_l,
                                prd.discount,
                                prd.clno,
                                prd.weight,
                                prd.weight_g
                          FROM prdbar
                            LEFT JOIN
                               prd ON (prd.no = prdbar.prdno)
                          WHERE prdbar.%s
                        )
                       UNION
                        (SELECT prdbar.prdno,
                                prdbar.grade,
                                prd.name,
                                prd.mfno,
                                prd.mfno_ref,
                                prd.taxno,
                                prd.mult,
                                prd.class,
                                prd.grade_l,
                                prd.discount,
                                prd.clno,
                                prd.weight,
                                prd.weight_g
                          FROM prdbar
                            LEFT JOIN
                               prd ON (prd.no = prdbar.prdno)
                          WHERE prdbar.bits&1=1
                            AND prdbar.%s
                        )
                       LIMIT 1",
                  $where, $where22);
  $result = $db->GetRow($SqlQuery);

  //echo $db->ErrorMsg();

  if ($result && is_array($result) && !empty($result)) {
    return ($result);
  }

  //tirado de 'sql_prdFromBarcode'
  $SqlQuery = sprintf("SELECT no as prdno,
                              prd.name,
                              prd.mfno,
                              prd.mfno_ref,
                              prd.taxno,
                              prd.mult,
                              prd.class,
                              prd.grade_l,
                              prd.discount,
                              prd.clno,
                              prd.weight,
                              prd.weight_g
                       FROM prd
                       WHERE %s", $where);
  $result = $db->GetRow($SqlQuery);

  if ($result && is_array($result) && !empty($result)) {
    //grade nao pode ser vazia
    $result['grade'] = '';
    return ($result);
  }

  return false;
}
?>
