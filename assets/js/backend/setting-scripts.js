jQuery( function( $ ) {

	$.fn.replaceWithPush = function(a) {
		var $a = $(a);
	
		this.replaceWith($a);
		return $a;
	};

	function opcw_show_el($el) {
		$el.removeClass('opcw_hidden');
	}
	
	function opcw_hide_el($el) {
		$el.addClass('opcw_hidden');
	}

	function opcw_save_settings() {
		if (!$('#opcw_submit_settings').length) return false;

		$('#opcw_submit_settings').on('click', function(e) {
			e.preventDefault();
			$(this).addClass('loading');

			var data = {};
			data['action'] = 'opcw_handle_settings_form';
			data['ajax_nonce_parameter'] = opcw_script.security_nonce;
			$('.opcw_g_set_tabcontents .opcw_setting_field').each(function() {
				if ($(this).attr('type') == 'checkbox' && !$(this).is(":checked")) {
					data[$(this).attr('name')] = 0;	
				}
                else if ($(this).attr('type') == 'radio') {
                    if ($(this).is(":checked")) {
                        data[$(this).attr('name')] = $(this).val();
                    }
                }
				else {
					data[$(this).attr('name')] = $(this).val();
				}
			});

			$.ajax({ 
				url: opcw_script.ajaxurl,
				type: "post", 
				dataType: 'json', 
				data: data, 
				success: function(data) { 
					$.toast({
						heading: 'Success',
						text: data.data.message,
						showHideTransition: 'slide',
						icon: 'success',
						position: 'top-right',
						hideAfter: 6000
					})
					
				}, 
				error: function() { 
					alert("An error occured, please try again.");          
				} 
			});   
		});
	}

	function opcw_init_select2_settings($selector = false) {
		var $selector = !$selector ? $('.opcw_init_select2') : $selector;
		if (!$selector.length) return false;
		
		$selector.each(function() {
			// Init select2
			$(this).select2();
		})
	}
    
    function opcw_on_trigger_setting_field() {
		$('.opcw_field_trigger').on('change', function() {
			var name = $(this).attr('name'),
				fieldCondition = $('.option_list[data-condition="'+name+'"]');
			if (fieldCondition.length) {
				fieldCondition.each(function() {
                    $(this).toggleClass('hidden_setting');
				})
			}
		});
	}

    $(document).ready(function($) {
        // Handler Settings
        opcw_init_select2_settings();
        opcw_on_trigger_setting_field();
		opcw_save_settings();
		
		$( '.opcw_wrap_settings .opcw_g_set_tabs li a' ).on( 'click', function(e) {
			// e.preventDefault();
			$( '.opcw_wrap_settings .opcw_g_set_tabs li a' ).removeClass('active');
			$(this).addClass('active');
			$('.opcw_tabcontent').hide();
			$($(this).attr('href')).show();
		})
		// End
    });
	
});