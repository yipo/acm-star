<?php define('ENTRANCE',__FILE__);

require_once('model.inc.php');

$model->get_ready();

if (empty($_SERVER['HTTPS'])) {
	if (!isset($_GET['fresh'])) {
		exit(header('Refresh: 0; url=?fresh'));
	} else {
		exit('something went wrong! X-(');
	}
}

$model->load();

require_once('view.inc.php');

