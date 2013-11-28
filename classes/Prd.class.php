<?php
require_once WService_DIR . "/classes/BasicADO.class.php";

/**
 * Description of Prd
 *
 * @author felipe
 */
class Prd extends BasicADO {

  var $conf;

  /**
   * Construtor da classe
   *
   * @param string $dbname
   */
  function __construct($dbname) {
    parent::__construct($dbname, "prd");
    $this->conf = getConfig();
  }

  public function getPrd($barcode){
    $db = & $this->DB();
    /* buscar o código e a grade do produto usando o codigo de barras */
    $prd = getPrdBarCode2Codigo($barcode, $db, $this->conf);
    return $prd;
  }

}

?>
