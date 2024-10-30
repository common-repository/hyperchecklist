jQuery(function($){
	var $field = $('.formfield').first().clone();
	$field.find('input').not('.idbox').val('');
	$field.find('.idbox').val('-1');
	$field.find('textarea').text('');
	
	$('#hcladder').click(function(){
		$field.clone().insertBefore($('.hcladdbefore'));
		return false;
	});

	$(document).on('click', '.remover',function(){
		$(this).parent().parent().parent().addClass('removal');
		$(this).hide();
		$(this).parent().parent().parent().find('.deleter').val('true');
		var html = '<span class="removalbox">' + hcllistjs.removeboxtext + '<br><a href="#" class="cancellink">' +hcllistjs.canceltext + '</a>';
		$(this).parent().append(html);
		return false;
	})

	$(document).on('click','.cancellink', function(){
		$(this).parent().parent().parent().parent().removeClass('removal');
		$(this).parent().parent().parent().parent().find('.deleter').val('false');
		$(this).parent().parent().find('.remover').show();
		$(this).parent().remove();

		return false;
	});


	$('#listchooser').change(function(){
		if($(this).val() == '0'){
			return false;
		}
		
		if ($('.newimport').length != 0) {
			var answer = confirm(hcllistjs.removetext);

			if (answer) {
				$('.newimport').remove();
			}else{
				$('.newimport').each(function(){
					$(this).removeClass('newimport');
				});
			};
		};
		
		var data = {
			action : 'hcl_get_elements',
			id : $(this).val()
		};

		$.get(hcllistjs.ajaxurl, data, function(msg) {
			$(msg).insertBefore($('.hcladdbefore'));
		});

	});

 
$("#hcllist tbody").sortable({
	items: "tr:not(.ui-state-disable)"
});
	
});