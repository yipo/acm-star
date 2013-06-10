<?php if (!defined('ENTRANCE')) exit; ?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0">
	<title><?=$model->subject?></title>
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css">
	<link rel="stylesheet" href="view.css">
</head>
<body>
	<header class="navbar navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container">
				<a class="brand" href="."><?=$model->subject?></a>
				<a class="btn pull-right" href=".."><i class="icon-home"></i></a>
			</div>
		</div>
	</header>
	<div class="container">
		<div class="row">
			<div class="span4">
				<nav class="span4" data-spy="affix">
					<ul class="nav nav-tabs nav-stacked">
<?php foreach ($model->config as $cfg => $config): ?>
						<li><a href="#sec-<?=$cfg?>"><?=$config->subject?></a></li>
<?php endforeach; ?>
					</ul>
				</nav>
			</div>
			<div class="span8">
<?php foreach ($model->config as $cfg => $config): ?>
				<section id="sec-<?=$cfg?>">
					<div class="page-header"><h4><?=$config->subject?></h4></div>
					<form class="config form-horizontal" action="save.php?cfg=<?=$cfg?>" method="post">
<?php ;   foreach ($config->field as $fld => $field): $label = "fld-{$cfg}-{$fld}"; ?>
						<div class="control-group">
							<label class="control-label" for="<?=$label?>"><?=$field->subject?></label>
							<div class="controls">
<?php ;   ;   switch ($field->type): default: ?>
								<input id="<?=$label?>" type="<?=$field->type?>" name="<?=$fld?>"<?=($field->type=='password'?NULL:" value=\"{$field->value}\"")?> pattern="<?=$field->valid?>">
<?php ;   ;   break; case 'radio': ?>
<?php ;   ;   ;   foreach ($field->valid as $opt => $option): $label = "opt-{$cfg}-{$fld}-{$opt}"; ?>
								<label class="radio" for="<?=$label?>">
									<input id="<?=$label?>" class="<?="opt-{$cfg}-{$fld}"?>" type="radio" name="<?=$fld?>" value="<?=$opt?>"<?=($opt==$field->value?' checked':NULL)?>>
									<span><?=$option?></span>
								</label>
<?php ;   ;   ;   endforeach; ?>
<?php ;   ;   break; case 'select': ?>
								<select id="<?=$label?>" name="<?=$fld?>">
<?php ;   ;   ;   foreach ($field->valid as $opt => $option): ?>
									<option value="<?=$opt?>"<?=($opt==$field->value?' selected':NULL)?>><?=$option?></option>
<?php ;   ;   ;   endforeach; ?>
								</select>
<?php ;   ;   endswitch; ?>
							</div>
						</div>
<?php ;   endforeach; ?>
						<div class="form-actions"><input class="btn btn-primary" type="submit" value="Save"></div>
					</form>
				</section>
<?php endforeach; ?>
			</div>
		</div>
	</div>
	<footer>
		<div class="container">
			<span class="pull-right"><b>ci-proj-basis</b> v0.1</span>
			<span>Report an issue or star us on <a href="https://github.com/YiPo/ci-proj-basis" target="_blank">GitHub</a>.</span>
		</div>
	</footer>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
	<script src="view.js"></script>
</body>
</html>