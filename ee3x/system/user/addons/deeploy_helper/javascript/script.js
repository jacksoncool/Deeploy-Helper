$(document).ready(function(){

	$('form input').change(function(){
		$(this).css('color', 'red');
	});

	$('.promotion').change(function(){
		$(this).css('color', 'red');
	});
	
	// We place our event on the button click to override EE default events
	$('#quick_replace').on('click', function(e){
		// We need to be sure to get the right form
		// Unfortunately, we can't add an id to it, so using a trick here
		$('input[name="site_url"]').parents('form').find('input[type="text"]').each(function(){
			var old = $(this).val();
			$(this).val($(this).val().replace($('#find').val(), $('#replace').val()));
			if ($(this).val() != old)
			{
				$(this).change();
			}
		});
		return false;
		e.preventDefault();
	});
});
