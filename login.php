<?php

$callback = $_GET['wscallback'];
$usuario = $_GET['usuario'];
$senha = $_GET['senha'];

if ($usuario == 'felipe' && $senha == 'a')
  $xml = "<wsresult><wsstatus>1</wsstatus><nome>Felipe Jorge</nome><id>10</id></wsresult>";
else
  $xml = "<wsresult><wsstatus>0</wsstatus></wsresult>";

header('Access-Control-Allow-Origin: "*"');
echo sprintf("%s('%s')", $callback, $xml);
exit;
?>