<?php

require_once WService_DIR . '/adodb5/adodb.inc.php';
require_once WService_DIR . '/adodb5/adodb-active-record.inc.php';
require_once WService_DIR . '/lib/function.inc.php';

/**
 * Description of ADODB_Basic
 *
 * @author gladyston
 */
// colcoar abstracts
class BasicADO extends ADODB_Active_Record {

  protected $my_dbase;
  protected $my_table;
  protected $whereXML;
  protected $list_erros;

  function __construct($dbname, $table, $pkeyarr=false, $db=false) {

    $this->list_erros = array();

    //remove o nome do banco caso esteja apendado
    $pos = strpos($dbname, ".");
    $dbname = ($pos === false) ? $dbname : substr($dbname, 0, $pos);

    $this->whereXML = "1";
    $this->my_dbase = $dbname;
    $this->my_table = $table;
    $table = sprintf("%s.%s", $dbname, $table);

    parent::__construct($table, $pkeyarr = false, $db = false);
  }

  function set2($row) {
    $tableInfo = & $this->TableInfo();
    foreach ($tableInfo->flds as $name => $dados) {
      if (isset($row[$name])) {
        $this->$name = $row[$name];
      }
    }
  }

  function apendError($mensagem) {
    $this->list_erros[] = $mensagem;
  }

  function hasError() {
    return count($this->list_erros);
  }

  function validate($parm = false) {

    $db = & $this->DB();
    global $ADODB_ASSOC_CASE;

    foreach ($db->MetaColumns($this->_table) as $fld) {
      switch ($ADODB_ASSOC_CASE) {
        case 0:
          $name = strtolower($fld->name);
          break;
        case 1:
          $name = strtoupper($fld->name);
          break;
        default:
          $name = ($fld->name);
          break;
      }

      //valida campo obrigatorio
      if (!$fld->auto_increment && $fld->not_null && is_null($this->$name)) {
        if ($fld->has_default) {
          $this->$name = $fld->default_value;
        } elseif ($fld->type == "char" || $fld->type == "varchar") {
          $this->$name = '';
        } elseif ($fld->type == "smallint" || $fld->type == "int" || $fld->type == "bigint") {
          $this->$name = 0;
        } else {
          $this->Error(sprintf("O campo '%s' não aceita valores nulos.", $name), __FILE__);
          return false;
        }
      }

      //ajusta campos do tipo string (remove acentos e trata minusculos)
      if ($fld->not_null && ($fld->type == "char" || $fld->type == "varchar")) {
        $this->$name = strtoupper(normalizeChars($this->$name));
      }
    }

    return true;
  }

  /**
   * Execute SQL
   *
   * @param sql		SQL statement to execute, or possibly an array holding prepared statement ($sql[0] will hold sql text)
   * @param [inputarr]	holds the input data to bind to. Null elements will be set to null.
   * @return 		RecordSet or false
   */
  function Execute($sql, $inputarr=false) {

    $db = & $this->DB();
    $resultado = $db->Execute($sql, $inputarr);

    if (!$resultado) {
      $this->Error($db->ErrorMsg(), __FUNCTION__);
    }

    return $resultado;
  }

  /**
   * Return first element of first row of sql statement. Recordset is disposed
   * for you.
   *
   * @param sql			SQL statement
   * @param [inputarr]		input bind array
   */
  function GetOne($sql, $inputarr=false) {

    $db = & $this->DB();
    $inputarr = ($inputarr == false) ? $inputarr : (array) $inputarr;
    $resultado = $db->GetOne($sql, $inputarr);

    if ($resultado === false) {
      $this->Error($db->ErrorMsg(), __FUNCTION__);
    }

    return $resultado;
  }

  /**
   *
   * @return <type>
   */
  public function Insert() {
    if (!$this->validate()) {
      return false;
    }
    return parent::Insert();
  }

  /**
   *
   * @return <type>
   */
  public function Replace() {
    if (!$this->validate()) {
      return false;
    }
    return parent::Replace();
  }

  /**
   *
   * @return <type>
   */
  public function Update() {
    if (!$this->validate()) {
      return false;
    }
    return parent::Update();
  }

  /**
   *
   * @param <type> $db
   * @param <type> $table
   * @return <type>
   */
  function GenWhere(&$db, &$table) {
    $keys = $table->keys;
    $parr = array();

    //==============================================================
    //CORRECAO DE BUG (by Gladyston/EAC - 02/Agosto/2010)
    //quando se alterava o valor de um dos capos da primary key ele,
    //o resgistro não era atualizado visto que o WHERE usava o valor
    //corrente e não o valor original (antes de ser alterado)
    //==============================================================
    if (isset($this->_original)) {
      $i = 0;
      foreach ($table->flds as $k => $f) {
        $value = $this->_original[$i++];
        if (in_array($k, $keys)) {
          $parr[] = $k . ' = ' . $this->doquote($db, $value, $db->MetaType($f->type));
        }
      }
    }
    //==============================================================
    //codigo original antes da alteração acima (Gladyston)
    //==============================================================
    else {
      foreach ($keys as $k) {
        $f = $table->flds[$k];
        if ($f) {
          $parr[] = $k . ' = ' . $this->doquote($db, $this->$k, $db->MetaType($f->type));
        }
      }
    }
    return implode(' and ', $parr);
  }

  /**
   * getSaciRlock
   *
   * @param string chave a  ser locada (-1 ignora esta validacao)
   * @return boolean se conseguiu ou nao fazer o lock
   * @access public
   */
  public function getSaciRlock($rlockkey=false) {

    set_time_limit(10 * 5 * 5);

    // padrao adotado para o GET_LOCK
    $labelkey = sprintf("rlock.%s.%s", $this->my_dbase, $this->my_table);

    //query
    for ($i = 0; $i < 10; $i++) {

      if (!$this->GetOne("SELECT GET_LOCK(?, 5)", (array) $labelkey)) {
        $msg = sprintf("Não foi possivel obter um lock para tabela '%s'", $this->_table);
        sleep(2);
        continue;
      }

      if ($rlockkey) {

        $conf = getConfig();
        $dbname = $conf['DATABASE']['database'];

        $object = new BasicADO($dbname, "rlock");
        if ($object->Load("dbname = ? AND tablename = ? AND lockstr = ?", array($this->my_dbase, $this->my_table, $rlockkey))) {
          $msg = sprintf("Registro travado pelo funcionario '%s'", $object->username);
          sleep(2);
          continue;
        }
      }

      //GET_LOCK esta travado e table rlock esta fazia
      return true;
    }

    $this->Error($msg, __FUNCTION__);
    return(false);
  }

  /**
   * releaseSaciRlock
   *
   * @return boolean se conseguiu ou nao fazer o lock
   * @access public
   */
  public function releaseSaciRlock() {

    $labelKey = sprintf("rlock.%s.%s", $this->my_dbase, $this->my_table);
    return $this->GetOne("SELECT RELEASE_LOCK(?)", (array) $labelKey);
  }

  /**
   *
   * @return <type>
   */
  public function __toString() {

    $result = "";
    $db = & $this->DB();
    global $ADODB_ASSOC_CASE;

    foreach ($db->MetaColumns($this->_table) as $fld) {
      switch ($ADODB_ASSOC_CASE) {
        case 0:
          $name = strtolower($fld->name);
          break;
        case 1:
          $name = strtoupper($fld->name);
          break;
        default:
          $name = ($fld->name);
          break;
      }
      $result .= sprintf("[%s = %s]", $name, $this->$name);
    }
    return $result;
  }

  /**
   *  Retorna as chaves primarias de um registro concatenadas
   * @return <string>
   */
  public function GetPrimaryKeysValues() {
    $db = & $this->DB();
    $pk = $this->GetPrimaryKeys($db, $this->_table);

    $result = "";
    foreach ($pk as $field) {
      $result .= sprintf("[%8s]", $this->$field);
    }
    return $result;
  }

  /**
   * gera um numero do hash apartir da funcao crc32
   *
   * @return type
   */
  public function getCRC32() {
    return sprintf("%d", crc32($this->getHash()));
  }

  /**
   *  Retorna o hash de um registro
   *
   * @return <string>
   */
  public function getHash() {

    // gera a chave para busca
    $chave_concat = "";
    foreach ($this->getXMLFields() as $field => $fieldValue) {
      $chave_concat .= "{$this->$field}|";
    }

    // gera o sha1 com base nos campos encontrados
    return sha1($chave_concat);
  }

  /**
   *  retorna se um determinado registro foi alterado
   *  comparando seu Hash atual com o Hash da  tabela
   *  WSHASH. Caso o registro nao se encontre na tabe
   *  la, entao retorna que foi modificado
   *
   * @return <boolean>
   */
  public function isModificado() {

    $db = & $this->DB();
    $key = $db->GetOne("SELECT hash FROM wshash WHERE
                          banco  = ?  AND
                          tabela = ? AND
                          chave  = ?",
                      array($this->my_dbase, $this->_table,
                            $this->GetPrimaryKeysValues()));

    return $this->getHash() != $key;
  }

  /**
   *  Insere um registro na tabela de Hash
   * @return <type>
   */
  public function saveHash() {

    $db = & $this->DB();

    // necessario para casos em que o objeto a ser feito o foi criado em outra
    // base de dados, porem o hash precisa estar na base padrao (caso do prdstk)
    $dbname = $this->my_dbase;
    $object = new Wshash($dbname);

    if (!$object->Load("banco = ? AND tabela = ? AND chave = ?",
            array($this->my_dbase, $this->_table, $this->GetPrimaryKeysValues()))) {

      $object->banco  = $this->my_dbase;
      $object->tabela = $this->_table;
      $object->chave  = $this->GetPrimaryKeysValues();
    }

    $object->hash = $this->getHash();
    $object->alteracao = date("Y-m-d H:i:s");

    if (!$object->Save()) {
      $this->Error($object->ErrorMsg(), __FUNCTION__);
      return false;
    }
    return true;
  }

//  abstract function getXMLFields();
  protected function getXMLFields() {
    return array();
  }

//  abstract function getXMLElementRoot();
  protected function getXMLElementRoot() {
    return "";
  }

  public function findByXML($xml_srt, $extras=false) {

    $db = & $this->DB();

    // caso esse metodo seja chamado mais de uma vez ele reinicia a variavel whereXML
    $whereXML = $this->whereXML;

    // realiza o parser do xml recebido
    if (($simpleXML = simplexml_load_string($xml_srt)) == false) {
      $error = libxml_get_last_error();
      $error_msg = sprintf("%s [Linha %d, Coluna %d]", $error->message, $error->line, $error->column);
      $this->Error($error_msg, __FUNCTION__);
      return false;
    }

    $table = $this->TableInfo();

    foreach ($this->getXMLFields() as $key => $fld) {

      if (isset($simpleXML->$fld) && $simpleXML->$fld != '') {
        // C: Character fields
        // X: Clob (character large objects), or large text
        // D: Date field
        // T: Timestamp field
        // L: Logical field (boolean or bit-field)
        // N: Numeric field. Includes decimal, numeric, floating point, and real.
        // I: Integer field.
        // R: Counter or Autoincrement field. Must be numeric.
        // B: Blob, or binary large objects.
        $type = $db->MetaType($table->flds[$key]);

        // busca os atributos da tag XML
        $attr = $simpleXML->$fld->attributes();
        if (isset($attr['operador']) && !empty($attr['operador'])) {
          //TODO: implementar os operadores =, >, >=, <, <=, BETWEEN, LIKE (Gladyston, 30/01/2012)
        } else {
          if (get_class($this) == "Prd" && $key == "no") {
            $whereXML .= sprintf(" AND %s = '%s'", $key, format_prdno($simpleXML->$fld));
          } else if ($type == 'C') {
            $whereXML .= sprintf(" AND %s LIKE '%s'", $key, $simpleXML->$fld);
          } else {
            $whereXML .= sprintf(" AND %s = '%s'", $key, $simpleXML->$fld);
          }
        }
      }
    }

    return $this->Find($whereXML . ((!$extras) ? "" : $extras));
  }

  /**
   *  Retorna o objeto DOMDocumento contendo as
   *  informações do xml
   *
   * @return DOMDocument
   */
  public function toXML() {

    $dom = new DOMDocument("1.0", "UTF-8");
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;

    $elem = $dom->createElement("erro");
    $dom->appendChild($elem);
    $root = $dom->createElement($this->getXMLElementRoot());

    foreach ($this->getXMLFields() as $field => $tag) {
      if (is_array($this->$field)) {
        $this->Error("Campo '$this->my_table.$field' nao preenchido", __FUNCTION__);
        return false;
      }
      $elem = $dom->createElement($tag, htmlentities($this->$field, ENT_QUOTES));
      $root->appendChild($elem);
    }
    $dom->appendChild($root);
    return $dom;
  }

  /**
   *
   * @return <type>
   */
  function getDatabaseRetaguardaName() {

    /* @var $conf array */
    $conf = getConfig();
    return $conf['DATABASE']['database'];
  }

  /**
   *
   * @return <type>
   */
  function getDatabasePDVName() {

    /* @var $conf array */
    $conf = getConfig();
    return $conf['DATABASE']['database_pdv'];
  }

  /**
   * Insere um registro de log na tabela wsLog
   *
   * @global type $_SERVER
   * @param type $servico
   * @param type $metodo
   * @param type $cryptno
   * @param type $dados
   * @param type $tempoExec
   * @param type $tipoRequisicao
   * @return boolean
   */
  static function doLog($servico = "", $metodo = "", $cryptno = "", $dados = "",
                        $tempoExec = "", $tipoRequisicao = "", $referencia = 0) {

    global $_SERVER;

    /* @var $conf array */
    $conf = getConfig("DATABASE");

    $dbname = $conf['database'];

    $object = new Wslog($dbname);

    $object->servico        = $servico;
    $object->metodo         = $metodo;
    $object->data           = date("Y-m-d h:i:s");
    $object->tempoexec      = $tempoExec;
    $object->ip_acesso      = ip2long($_SERVER['REMOTE_ADDR']);
    $object->cryptno        = $cryptno;
    $object->dados          = $dados;
    $object->tiporequisicao = $tipoRequisicao;
    $object->wslognoref     = $referencia;
    $object->versao         = MyDEFINES::WS_VERSAO;

    if($object->Save())
      return $object->wslogno;

    return false;
  }
}

?>
