jQuery(function($){
	$radio = $('#hcllist input[type="radio"]');
	
	$radio.each(function(){
		$(this).change(function(){
			if($(this).val() == 0){
				$(this).parent().parent().parent().removeClass('done error').addClass('pending');
			}else if($(this).val() == 1){
				$(this).parent().parent().parent().removeClass('pending error').addClass('done');
			}else if($(this).val() == 2){
				$(this).parent().parent().parent().removeClass('pending done').addClass('error');
			}

			$.post(hcllistobj.ajaxurl, {
				action: 'hcl_set_status',
				id: $(this).attr('name'),
				status: $(this).val()
			}, function(data){
				
			})
		})
	})

	check($radio);
});
function check(items){
	jQuery.getJSON(hcllistobj.ajaxurl, {
			action: 'hcl_check_status',
			listid: hcllistobj.postid
		},function(data){
			items.each(function(key){
								
			});
		})
		
	}