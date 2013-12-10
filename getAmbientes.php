<?php

$wscallback = $_GET['wscallback'];

$res = true;

$xml = "<wsresult>";

if ($res){
  $xml .= "<wsstatus>1</wsstatus>";
  $xml .= "<ambientes>";
  $xml .= "<ambiente><codigo>0</codigo><nome>Sem ambiente</nome></ambiente>";
  $xml .= "<ambiente><codigo>1</codigo><nome>Sala de estar</nome></ambiente>";
  $xml .= "<ambiente><codigo>2</codigo><nome>Sala de jantar</nome></ambiente>";
  $xml .= "<ambiente><codigo>3</codigo><nome>Cozinha</nome></ambiente>";
  $xml .= "<ambiente><codigo>4</codigo><nome>Quarto</nome></ambiente>";
  $xml .= "<ambiente><codigo>5</codigo><nome>Banheiro</nome></ambiente>";
//  $xml .= "<ambiente><codigo>6</codigo><nome>Área de Serviço</nome></ambiente>";
//  $xml .= "<ambiente><codigo>7</codigo><nome>Sacada</nome></ambiente>";
  $xml .= "</ambientes>";
}else
  $xml .= "<wsstatus>0</wsstatus>";

$xml .= "</wsresult>";

header('Access-Control-Allow-Origin: "*"');
echo sprintf("%s('%s')", $wscallback, $xml);
exit;
?>