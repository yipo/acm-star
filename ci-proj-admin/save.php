<?php define('ENTRANCE',__FILE__);

require_once('model.inc.php');

if (!isset($_GET['cfg'])) exit('lack of the "cfg" variable.');
$cfg = $_GET['cfg'];

if (!array_key_exists($cfg,$model->config)) exit('invalid value for the "cfg" variable.');
$config = $model->config[$cfg];

$config->save($_POST);

