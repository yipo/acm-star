
function bootstrapAlert(msg,prompt) {
	return $('<div>').addClass('alert alert-error').append(
		$('<button>').addClass('close').attr('type','button').attr('data-dismiss','alert').html('&times;'),
		$('<strong>').text(prompt),
		$('<span>').text(' '+msg)
	);
}

$('form.config').submit(function() {
	$(this).find(':submit').prop('disabled',true);
	var that=this;
	/*
	* I know the way of `$.ajax({context:this,...})'.
	* But it messes up my code. :(
	*/
	$.post(
		$(this).attr('action'),
		$(this).serializeArray(),
		function(error) {
			$(that).find(':submit').prop('disabled',false);
			if (error!='') {
				$(that).before(bootstrapAlert(error,'Error:'));
			} else {
				location.reload(true);
			}
		}
	);
	return false;
});

if ($('.opt-ar-method:checked').val()===undefined) {
	$('#sec-ar form.config').before(
		bootstrapAlert(
			'restrict the accessing to this admin panel from others for safety.','Warning:'
		).hide().delay(1000).fadeIn()
	);
}

function switchElementByRadio(element,radioClass,radioId) {
	var elmt=$(element).hide();
	$(radioClass).change(function() {
		if ($(radioId).prop('checked')) {
			elmt.fadeIn();
		} else {
			elmt.hide();
		}
	});
}

switchElementByRadio(
	'#sec-ar form div.control-group:not(:first-child)',
	'.opt-ar-method',
	'#opt-ar-method-auth'
);

