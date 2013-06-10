<?php if (!defined('ENTRANCE')) exit;

/*
* Utilities
*/

function my_file_get($file) {
	$rt = file_get_contents($file);
	if ($rt===FALSE) exit("fail to open the file \"{$file}\".");
	return $rt;
}

function my_file_put($file,$text,$flag = 0) {
	$rt = file_put_contents($file,$text,$flag);
	if ($rt===FALSE) exit("fail to save the file \"{$file}\".");
	return $rt;
}

function my_match($patt,$sub,$info,&$mat = NULL) {
	$rt = preg_match($patt,$sub,$mat);
	if ($rt===FALSE) exit("an error occurred while {$info}.");
	return $rt;
}

function my_match_all($patt,$sub,$info,&$mat = NULL) {
	$rt = preg_match_all($patt,$sub,$mat);
	if ($rt===FALSE) exit("an error occurred while {$info}.");
	return $rt;
}

function my_replace($patt,$rep,$sub,$info) {
	$rt = preg_replace($patt,$rep,$sub);
	if ($rt===NULL) exit("an error occurred while {$info}.");
	return $rt;
}

/*
* Class definitions
*/

class Model {
	public $subject;
	public $config = array();
	
	function __construct($subject) {
		$this->subject = $subject;
	}
	
	function get_ready() {
		foreach ($this->config as $config) $config->get_ready();
	}
	
	function load() {
		foreach ($this->config as $config) $config->load();
	}
	
	function update() {
		foreach ($this->config as $config) $config->update();
	}
}

class Config {
	const PROJ_PATH = '../';
	const TPLT_PATH = 'template/';
	
	public $subject;
	public $target;
	public $source;
	public $field = array();
	
	function __construct($subject,$target,$source) {
		$this->subject = $subject;
		$this->target  = $target;
		$this->source  = $source;
	}
	
	function reset() {
		copy($this->source,$this->target);
	}
	
	function is_ready() {
		return (file_exists($this->target)===TRUE);
	}
	
	function get_ready() {
		if (!$this->is_ready()) $this->reset();
	}
	
	function load() {
		$text = my_file_get($this->target);
		my_match_all($this->patt_tag(),$text,"loading the file \"{$this->target}\"",$match);
		foreach ($match[2] as $i => $key) {
			if (array_key_exists($key,$this->field)) {
				$this->field[$key]->value = $match[3][$i];
			}
		}
	}
	
	function validate($data) {
		foreach ($this->field as $fld => $field) {
			if (!array_key_exists($fld,$data)) exit("lack of the \"{$fld}\" variable.");
			if (!$field->validate($data[$fld])) exit("invalid value for the \"{$fld}\" variable.");
		}
	}
	
	function save($data) {
		$this->validate($data);
		
		$text = my_file_get($this->target);
		foreach ($this->field as $fld => $field) {
			$text = my_replace($this->patt_tag($fld),"$1'{$data[$fld]}'",$text,"filling the fields of the file \"{$this->target}\"");
		}
		my_file_put($this->target,$text);
	}
	
	function outdated() {
		return filemtime($this->target)<filemtime($this->source); // The source is newer than the target.
	}
	
	function update() {
		$this->get_ready();
		if ($this->outdated()) {
			$this->load();
			$this->reset();
			foreach ($this->field as $fld => $field) $data[$fld] = $field->value;
			if (isset($data)) $this->save($data);
		}
	}
	
	protected function patt_tag($tag = '\w+') {
		return "%(/\*{{({$tag})}-->}\*/ )'([^']*)'%";
	}
	
	protected function patt_hta($tag) {
		return "%^(# )?({$tag} )(.*)$%m";
	}
}

class ConfigAR extends Config {
	private $append = array();
	private $htpswd = '.htpasswd';
	
	function __construct($subject,$target,$source,$local,$auth) {
		parent::__construct($subject,$target,$source);
		$this->append['local'] = $local;
		$this->append['auth']  = $auth;
	}
	
	function validate($data) {
		if (!array_key_exists('method',$data)) exit('lack of the "method" variable.');
		if ($data['method']!='local') {
			parent::validate($data);
			if ($data['passwd']!=$data['retype']) exit('passwords are not match.');
		}
	}
	
	function save($data) {
		$this->validate($data);
		$method = $data['method'];
		
		$this->reset();
		$text = my_file_get($this->append[$method]);
		if ($method=='auth') {
			$text = $this->set_auth($text,$data['user'],$data['passwd']);
		} else {
			@unlink($this->htpswd); // `@' because `no such file' is fine.
		}
		my_file_put($this->target,$text,FILE_APPEND);
	}
	
	function outdated() {
		return filemtime($this->target)<max(
			filemtime($this->source),
			filemtime($this->append['local']),
			filemtime($this->append['auth'])
		);
	}
	
	private function set_auth($text,$user,$passwd) {
		my_file_put($this->htpswd,$this->gen_sha($user,$passwd));
		return my_replace($this->patt_hta('AuthUserFile'),'$2'.realpath($this->htpswd),$text,'setting the path to ".htpasswd"');
	}
	
	private function gen_sha($user,$passwd) {
		return $user.':{SHA}'.base64_encode(sha1($passwd,TRUE));
	}
}

class ConfigRB extends Config {
	function load() {
		$text = my_file_get($this->target);
		my_match($this->patt_hta('RewriteBase'),$text,"loading the file \"{$this->target}\"",$match);
		$this->field['enable']->value = ($match[1]!='# '?'true':'false');
	}
	
	function save($data) {
		$this->validate($data);
		
		$text = my_file_get($this->target);
		$rep = ($data['enable']=='true'?'$2'.ConfigRB::base_url():'# $2/my-proj/');
		$text = my_replace($this->patt_hta('RewriteBase'),$rep,$text,'setting the base url');
		my_file_put($this->target,$text);
	}
	
	static function base_url() {
		return my_replace('%^(/.*)ci-proj-admin/\w+\.php$%','$1',$_SERVER['SCRIPT_NAME'],'getting the base url');
	}
}

class Field {
	const VALID_WORD = '^[\w-]*$';
	const VALID_WDNE = '^[\w-]+$'; // non-empty
	const VALID_HOST = '^([A-z][\w-]*\.)*[A-z][\w-]*$|^(\d{1,3}\.){3}\d{1,3}$';
	
	public $subject;
	public $type;
	public $valid;
	public $value = NULL;
	
	function __construct($subject,$type,$valid = Field::VALID_WORD) {
		$this->subject = $subject;
		$this->type    = $type;
		$this->valid   = $valid;
	}
	
	function validate($value) {
		if (is_string($this->valid)) {
			return (1==my_match("%{$this->valid}%",$value,"validating the \"{$this->subject}\" field"));
		}
		if (is_array($this->valid)) {
			return array_key_exists($value,$this->valid);
		}
		exit("invalid value of the \"valid\" variable of the \"{$this->subject}\" field.");
	}
}

/*
* Create the instance.
*/

$model = new Model('ci-proj-admin');

$model->config = array(
	'ar' => new ConfigAR('Access Restriction',
		'.htaccess',
		Config::TPLT_PATH.'admin/.htaccess',
		Config::TPLT_PATH.'admin/local/.htaccess',
		Config::TPLT_PATH.'admin/auth/.htaccess'
	),
	'rb' => new ConfigRB('Rewrite Base',
		Config::PROJ_PATH.'.htaccess',
		Config::TPLT_PATH.'root/.htaccess'
	),
	'ev' => new Config('Environment',
		Config::PROJ_PATH.'index.php',
		Config::TPLT_PATH.'root/index.php'
	),
	'db' => new Config('Database',
		Config::PROJ_PATH.'private/application/config/database.php',
		Config::TPLT_PATH.'config/database.php'
	)
);

$model->config['ar']->field = array(
	'method' => new Field('Method','radio',array(
		'local' => 'Local machine only',
		'auth'  => 'Basic authentication'
	)),
	'user'   => new Field('User','text',Field::VALID_WDNE),
	'passwd' => new Field('Password','password',Field::VALID_WDNE),
	'retype' => new Field('Retype password','password',Field::VALID_WDNE)
);

$model->config['rb']->field = array(
	'enable' => new Field('Base URL','radio',array(
		'false' => 'is relative to the document root.',
		'true'  => 'set as <code>'.ConfigRB::base_url().'</code>.'
	))
);

$model->config['ev']->field = array(
	'environment' => new Field('Environment','radio',array(
		'development' => 'Development',
		'testing'     => 'Testing',
		'production'  => 'Production'
	))
);

$model->config['db']->field = array(
	'hostname' => new Field('Host','text',Field::VALID_HOST),
	'username' => new Field('User','text'),
	'password' => new Field('Password','password'),
	'database' => new Field('Database','text'),
	'dbdriver' => new Field('Driver','select',array(
		'mysql'   => 'MySQL',
		'mysqli'  => 'MySQLi',
		'postgre' => 'PostgreSQL',
		'odbc'    => 'ODBC',
		'mssql'   => 'Microsoft SQL',
		'sqlite'  => 'SQLite',
		'oci8'    => 'OCI8'
	))
);

