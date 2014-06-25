/**
 * Sfif Admin JS
 * @since 1.0
 * @athor Lucy Tomás
 */

jQuery(document).ready(function($) {

	
	$('input[type=submit]').on('click', function(e){
		
		sendRequest(0,100);
		
		$('#activity').html('');
		
		if($('#activity').css('display') == 'none') {
			$('#activity').css('display', 'block');
		}
		
	});
	
	/**
	 * sendRequest
	 * @since 1.0
	 */
	
	function sendRequest (start, limit) {
		
		var data = {};
		data['post_type']     = $('form[name=sfif] select#post_type').attr('value');
		data['overwrite'] 	  = $('form[name=sfif] input#overwrite').attr('checked');
		data['token'] 	  	  = $('form[name=sfif] input#token').attr('value');
		data['action_update'] = $('form[name=sfif] input#action_update').attr('value');
		data['start'] 	  	  = start;
		data['limit'] 	  	  = limit;
		data['action']    	  = 'sfif_request';
		
		$.post($('form[name=sfif]').attr("action"), data, function(response){
   			
   			responseRequest( response );
   			
   		});
		
	}

	/**
	 * responseRequest
	 * @since 1.0
	 */
	
	function responseRequest ( response ) {
		
		response = $.parseJSON(response);
		
		if( response.continue_request == true ) {
			
			$.each( response.result,function( index, value ) {
				
				var html = '<p class="' + value.success + '">';
					if( value.success == 'not_updated') {
						html = html + '<span>No Change</span><br>';
					} 
					html = html + '<span>ID</span>: ' + index;
					html = html + '<br><span>Title</span>: ' + value.title;
					html = html + '<br><span>Image</span>: ' + value.image;
					
					
					
					html = html + '</p>';
					
				$('#activity').append(html);	
				
			});
			
			sendRequest(response.next_start, response.next_limit);
				
		} else { 
			
			$('#activity').append('<p>END</p>');	
		
		}
	}

});