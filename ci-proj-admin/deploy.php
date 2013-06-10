<?php define('ENTRANCE',__FILE__);

require_once('model.inc.php');

/*
* Show the outputs of commands as plain text on client's browser.
*/
header('Content-Type: text/plain');

/*
* [Windows] Try to find installed git.
*/
if (PHP_OS=='WINNT') {
	if (shell_exec('git --version')=='') {
		putenv('PATH='.
			'C:\Program Files\Git\bin;'.
			'C:\Program Files (x86)\Git\bin'
		);
	}
}

/*
* These commands to run:
*/
$command = array(
	'git pull',
	'git submodule update --init --recursive'
);

chdir('..'); // Run commands from the toplevel of the working tree.
foreach ($command as $cmd) {
	set_time_limit(10*60); // It may takes time.
	echo "$ {$cmd}\n";
	echo shell_exec($cmd.' 2>&1'); // Show both stdout and stderr by `2>&1'.
}

/*
* Update the config files if there is anything new.
*/
chdir(dirname(ENTRANCE));
$model->update();

