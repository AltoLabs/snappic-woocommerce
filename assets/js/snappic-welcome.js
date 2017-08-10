/*global snappic_for_woocommerce */
jQuery( function ( $ ) {

	$( '.update-permalink' ).on( 'click', function(e) {
		e.preventDefault();

		var $slides = $(this).closest('.slides');
		var $_this = $(this);

		$_this.addClass('loading');

		$.ajax
		    ({ 
		        url: snappic_for_woocommerce.permalink_url,
		        type: 'post',
		        beforeSend: function ( xhr ) {
        			xhr.setRequestHeader( 'X-WP-Nonce', snappic_for_woocommerce.nonce );
    			},
		        success: function(result)
		        {
		        	$_this.removeClass('loading');
		            $slides.addClass( 'pick_plan' );
		        }
		    });


	});

});
