/*global snappic_for_woocommerce */
jQuery( function ( $ ) {

	$( '.update-permalink' ).on( 'click', function(e) {
		e.preventDefault();

		var $slides = $(this).closest('.slides');
		var $_this = $(this);

		$_this.addClass('loading').prop('disabled', true);

		$.ajax
		    ({ 
		        url: snappic_for_woocommerce.ajaxurl,
		        type: 'post',
		        data: {
					'action': 'snappic_update_permalinks',
					'nonce': snappic_for_woocommerce.nonce
				},
		        success: function(response)
		        {
		        	$_this.removeClass('loading').prop('disabled', true);
		        	if( response == 1 ) {
		            	$slides.addClass( 'pick_plan' );
		        	} else {
		        		$('#change_url_structure .permalink_error').slideDown();
		        	}
		        }
		    });


	});

});
