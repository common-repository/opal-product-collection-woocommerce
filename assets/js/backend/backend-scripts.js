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

	function opcw_clear_new_item($selector) {
		$selector.val('');
	}

	function opcw_init_select2_settings($selector = false) {
		var $selector = !$selector ? $('.opcw_init_select2') : $selector;
		if (!$selector.length) return false;
		
		$selector.each(function() {
			var optionSelect2;
			if ($(this).hasClass('setting_page')) {
				optionSelect2 = {};
			}
			else {
				var term = 'product';
				if ($(this).hasClass('opcw_rule_value')) {
					term = $(this).closest('.opcw_rules_box').find('.opcw_rule_item').val();
				}
				optionSelect2 = {
					ajax: {
						url: opcw_script.ajaxurl,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								term: term,
								q: params.term, // search query
								ajax_nonce_parameter: opcw_script.security_nonce,
								action: 'opcw_load_rule_apply_ajax'
							};
						},
						processResults: function( data ) {
							var options = [];
							if ( data ) {
								$.each( data, function( index, text ) {
									options.push( { id: text[0], text: text[1]  } );
								});
							}
							return {
								results: options
							};
						},
						cache: true
					},
					multiple: true,
					minimumInputLength: 1,
					placeholder: 'Typing to select',
				};
			}

			// Init select2
			$(this).select2(optionSelect2);
		})
	}

	function opcw_image_uploader(event, btn) {
		var $wrap = btn.closest('.opcw_image_uploader');
	
		media_uploader = wp.media({
			frame: 'post', state: 'insert', multiple: false,
		});

		media_uploader.on('insert', function() {
			var json = media_uploader.state().get('selection').first().toJSON();
			var image_id = json.id;
			var image_url = (typeof json.sizes.medium != 'undefined') ? json.sizes.medium.url : json.url;
			var image_html = '<img src="' + image_url + '"/>';

			$wrap.find('.opcw_image_val').val(image_id);
			$wrap.find('.opcw_selected_image').show();
			$wrap.find('.opcw_selected_image_img').html(image_html);
		});

		media_uploader.open();
	}

	function opcw_action_image() {
		$(document).on('click touch', '#opcw_logo_select', function(event) {
            opcw_image_uploader(event, $(this));
		});

		$(document).on('click touch', '.opcw_remove_image', function(e) {
			var $wrap = $(this).closest('.opcw_image_uploader');
		
			$wrap.find('.opcw_image_val').val('');
			$wrap.find('.opcw_selected_image_img').html('');
			$wrap.find('.opcw_selected_image').hide();
		});
	}

	function opcw_toggle_input_select(element, target, condition = false) {
		element.val('');
		element.empty();
		if (element.attr('data-select2-id') || element.hasClass('select2-hidden-accessible')) {
			element.select2('destroy');
			element.removeAttr('data-select2-id multiple tabindex aria-hidden');
		}
		var retEl = element;
		if (element.is("input") && target == 'select') {
			var inputAttributes = element.prop("attributes");
			var replaceElement = $("<select>");
			$.each(inputAttributes, function() {
				if (this.name.toLowerCase() !== 'type') {
					replaceElement.attr(this.name, this.value);
				}
			});
			retEl = element.replaceWithPush(replaceElement);
			
		} 
		else if (element.is("select") && target == 'input') {
			var selectAttributes = element.prop("attributes");
			var replaceElement = $("<input>");
			$.each(selectAttributes, function() {
				replaceElement.attr(this.name, this.value.replace("[]", ""));
				replaceElement.attr('type', 'text');
			});
			element.replaceWithPush(replaceElement);
		}

		// retEl.attr('name', idEl.replace("[]", ""));
		if (target == 'select') {
			var idEl = retEl.attr('id');
			idEl = idEl.replace("[]", "");
			if ($.inArray(condition, ['stock_status', 'product_type', 'attribute']) !== -1) {
				retEl.attr('name', idEl);
				retEl.attr('id', idEl);
				retEl.removeAttr('multiple');
				$.each(opcw_script.global_data[condition], function(index, val) {
					retEl.append('<option value="'+index+'">'+val+'</option>');
				});
			}
			else {
				retEl.attr('name', idEl+'[]');
				retEl.attr('id', idEl+'[]');
				retEl.select2({
					ajax: {
						url: opcw_script.ajaxurl,
						dataType: 'json',
						delay: 250,
						data: function (params) {
							return {
								term: condition,
								q: params.term, // search query
								ajax_nonce_parameter: opcw_script.security_nonce,
								action: 'opcw_load_rule_apply_ajax'
							};
						},
						processResults: function( data ) {
							var options = [];
							if ( data ) {
								$.each( data, function( index, text ) {
									options.push( { id: text[0], text: text[1]  } );
								});
							}
							return {
								results: options
							};
						},
						cache: true
					},
					multiple: true,
					minimumInputLength: 1,
					placeholder: 'Typing to select',
				});
			}
		}
	}

	function opcw_trigger_condition($selector = false) {
		var $selector = !$selector ? $('.opcw_rule_item') : $selector;
		if (!$selector.length) return false;

		$('.opcw_rule_item').on('change', function(e) {
			e.preventDefault();
			var field = $(this).val(),
				itemParent = $(this).parents('.opcw_rules_box'),
				relationBox = itemParent.find('.opcw_rule_relation'),
				valueBox = itemParent.find('.opcw_rule_value');

				relationBox.find('option').show();
				switch (field) {
					case 'stock_status':
						relationBox.val('is');
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is', 'is_not']) === -1) $(this).hide();
						});
						opcw_toggle_input_select(valueBox, 'select', field);
						break;
					case 'product_title':
						relationBox.val('contains');
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['contains', 'not_contains', 'starts_with', 'ends_with']) === -1) $(this).hide();
						});
						opcw_toggle_input_select(valueBox, 'input');
						break;
					case 'product_type':
						relationBox.val('is');
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is', 'is_not']) === -1) $(this).hide();
						});
						opcw_toggle_input_select(valueBox, 'select', field);
						break
					case 'product_category':
						relationBox.val('is_in');
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is_in', 'is_not_in']) === -1) $(this).hide();
						});
						opcw_toggle_input_select(valueBox, 'select', field);
						break
					case 'product_tag':
						relationBox.val('is_in');
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is_in', 'is_not_in']) === -1) $(this).hide();
						});
						opcw_toggle_input_select(valueBox, 'select', field);
						break
					case 'price':
						relationBox.val('is');
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is', 'is_not', 'is_greater', 'is_lessthan', 'is_greater_or_equal', 'is_lessthan_or_equal']) === -1) $(this).hide();
						});
						opcw_toggle_input_select(valueBox, 'input');
						break
					case 'attribute':
						relationBox.val('have');
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['have', 'not_have']) === -1) $(this).hide();
						});
						opcw_toggle_input_select(valueBox, 'select', field);
						break
					default:
						relationBox.val('');
						opcw_toggle_input_select(valueBox, 'input');
						// if (onAdded) {
						// }
						break;
				} 
			
		})
	}
	
	function opcw_init_condition() {
		if (!$('.opcw_rule_item').length) {
			return;
		}

		$('.opcw_rule_item').each(function(i) {
			var field = $(this).val(),
				itemParent = $(this).parents('.opcw_rules_box'),
				relationBox = itemParent.find('.opcw_rule_relation');

				switch (field) {
					case 'stock_status':
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is', 'is_not']) === -1) $(this).hide();
						});
						break;
					case 'product_title':
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['contains', 'not_contains', 'starts_with', 'ends_with']) === -1) $(this).hide();
						});
						break;
					case 'product_type':
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is', 'is_not']) === -1) $(this).hide();
						});
						break
					case 'product_category':
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is_in', 'is_not_in']) === -1) $(this).hide();
						});
						break
					case 'product_tag':
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is_in', 'is_not_in']) === -1) $(this).hide();
						});
						break
					case 'price':
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['is', 'is_not', 'is_greater', 'is_lessthan', 'is_greater_or_equal', 'is_lessthan_or_equal']) === -1) $(this).hide();
						});
						break
					case 'attribute':
						relationBox.find('option').each(function() {
							var valOption = $(this).attr('value');
							if ($.inArray(valOption, ['have', 'not_have']) === -1) $(this).hide();
						});
						break
					default:
						break;
				} 
			
		})
	}

	function opcw_init_repeater_rules() {
		if (!$('.opcw_wrapper_rules').length) return false;
		if (!$('.opcw_rules_box').length) return false;

		$('.opcw_wrapper_rules').repeater({
			btnAddClass: 'rpt_btn_add',
			btnRemoveClass: 'rpt_btn_remove',
			groupClass: 'opcw_rules_box',
			minItems: 1,
			maxItems: 0,
			startingIndex: 0,
			showMinItemsOnLoad: true,
			reindexOnDelete: true,
			repeatMode: 'insertAfterLast',
			animation: 'fade',
			animationSpeed: 400,
			animationEasing: 'swing',
			clearValues: true,
			afterAdd: function($item) {
				if (!$item.hasClass('added')) {
					// Clear new field
					opcw_clear_new_item($item.find('.opcw_rule_item'));

					// Reinit toggle condition
					opcw_trigger_condition($item.find('.opcw_rule_item'));

					// Trigger change
					$item.find('.opcw_setting_field').change();
				}
				
				//afterAdded
				$item.addClass('added');

				$('.opcw_wrapper_rules').attr('start-index', $('.opcw_rules_box').length - 1);
			},
			afterDelete: function() {
				// opcw_trigger_condition();
				$('.opcw_wrapper_rules .opcw_init_select2').each(function() {
					var id = $(this).attr('id');
					$(this).attr('id', id+'[]');
					$(this).attr('name', id+'[]');
				})
				$('.opcw_wrapper_rules').attr('start-index', $('.opcw_rules_box').length - 1);
			}
		});
	}

	function opcw_add_local_item_html() {
		const item_scan_default = localStorage.getItem('opcw_item_scan_default');
		if (!item_scan_default || item_scan_default == 'undefined') {
			localStorage.setItem('opcw_item_scan_default', $('#opcw_default_process').html());
		}
	}

	function opcw_active_process_box(data_scan) {
		if (!$('#opcw_process_box').hasClass('active')) {
			if (data_scan.length) {
				var list_box = $('#opcw_list_process');
				// list_box.empty();
				const item_scan_default = localStorage.getItem('opcw_item_scan_default');
				$.each(data_scan, function(i, v) {
					if (!list_box.find('#term-scan-'+v.term_id).length) {
						var cur = $(item_scan_default);
						cur.attr('id', 'term-scan-'+v.term_id);
						cur.attr('data-id', v.term_id);
						cur.find('.opcw_term_scan').text(v.tax_name + ': ' + v.term_name);
						
						list_box.append(cur);
	
						opcw_on_stop_scanning(v.term_id);
					}
				})
			}
			$('#opcw_process_box').addClass('active').show();
		}
	}
	
	function opcw_deactive_process_box() {
		if ($('#opcw_close_process').length) {
			$('#opcw_close_process').on('click touch', function(e) {
				$('#opcw_process_box').removeClass('active').fadeOut().promise().done(function() {
					$('#opcw_list_process .opcw_item_process:not(.is-scanning)').remove();
				});
			});
		}
	}

	function opcw_ajax_rescan_collection(collection, paged = 1, scan = 'all') {
		if (paged == 1) {
			scan = $('[name="option_scan"]:checked').val();
		}
		$.ajax({ 
			url: opcw_script.ajaxurl,
			type: "post", 
			dataType: 'json', 
			cache: false,
			data: {
				action: 'opcw_rescan_collection',
				paged: paged,
				collection: collection,
				scan: scan,
				ajax_nonce_parameter: opcw_script.security_nonce,
			}, 
			beforeSend: function(){
				
			},
			success: function(response) { 
				$(document).trigger('opcw_scan_response', [response, collection])

				if(!response.data.is_finished) {
					var next_paged = response.data.next_paged;
					opcw_ajax_rescan_collection(collection, next_paged, scan);
				}
				else {
					$.toast({
						heading: 'Success',
						text: response.data.message,
						showHideTransition: 'slide',
						icon: 'success',
						position: 'top-right',
						hideAfter: 6000
					});
				}
			}, 
			error: function() { 
				alert("An error occured, please try again.");          
			} 
		}); 
	}

	function opcw_stop_scanning_collection(collection) {
		if (confirm('Are you sure you want to stop scanning process?')) {
	        $.ajax({
	            url: opcw_script.ajaxurl,
	            type: "post", 
	            dataType: 'json', 
	            cache: false,
	            data: {
	            	action: 'opcw_stop_scanning_collection',
	            	collection: collection,
	            	ajax_nonce_parameter: opcw_script.security_nonce,
	            },
	            beforeSend: function(){
            		$('#opcw_stop_process').addClass('stopping').text('Stopping');
	            }, 
	            success: function (response) {
            		$('#opcw_stop_process').removeAttr('cur-collection');
					$.toast({
						heading: 'Success',
						text: response.data.message,
						showHideTransition: 'slide',
						icon: 'success',
						position: 'top-right',
						hideAfter: 6000
					})
	            },
	            complete: function(e) {
	            	$('#opcw_stop_process').removeClass('stopping').text('Stopped');
	            },
	            error: function() { 
	            	alert("An error occured, please try again.");          
	            } 
	        });
	    }
	}

	function opcw_scan_action_edit_page() {
		var actionsEdit = $('.opcw_show_module_scan #edittag .edit-tag-actions');
		if (actionsEdit.length) {
			$('<a id="rescan-collection" class="button" style="margin: 0 5px 0 10px" href="javascript:void(0)">Rescan</a>').insertAfter(actionsEdit.find('input[type="submit"]'));
			$('#rescan-collection').on('click touch', function(e) {
				var term_id = $('input[name="tag_ID"]').val(),
					term_name = $('#name').val(),
					tax_name = $('#opcw_term_name').val(),
					tax = $('input[name="taxonomy"]').val(),
					data_scan = [
					{
						term_id: term_id,
						term_name: term_name,
						tax_name: tax_name,
						tax: tax,
					}
				];
				opcw_active_process_box(data_scan);
				return false;
			});
		}
	}
	
	function opcw_scan_action_list_page() {
		if ($('.opcw_scan_action').length) {
			$('.opcw_scan_action').on('click touch', function(e) {
				var term_id = $(this).data('term'),
					term_name = $(this).data('name'),
					tax_name = $(this).data('tax-name'),
					tax = $('input[name="taxonomy"]').val(),
					data_scan = [
					{
						term_id: term_id,
						term_name: term_name,
						tax_name: tax_name,
						tax: tax,
					}
				];

				opcw_active_process_box(data_scan);
				return false;
			});
		}
	}
	
	function opcw_on_start_scanning() {
		if ($('#opcw_start_process').length) {
			var startBtn = $('#opcw_start_process');
			startBtn.on('click touch', function(e) {
				var item_scan = $('#opcw_list_process .opcw_item_process');
				if (item_scan.length) {
					item_scan.each(function() {
						if (!$(this).hasClass('is-scanning')) {
							if ($(this).hasClass('is-finished')) {
								$(this).removeClass('is-finished');
							}
							$(this).find('.opcw_data_process').text('0%');
							$(this).find('.opcw_process_active').css('width', '0%');
							$(this).find('.opcw_products_match').text(0);
							$(this).addClass('is-scanning');
							var term_id = $(this).data('id');
							opcw_ajax_rescan_collection(term_id);
						}
					})
				}
			});
		}
	}
	
	function opcw_on_stop_scanning(id) {
		var item = $('#term-scan-'+id);
		if (item.find('.opcw_stop_process').length) {
			var stopBtn = item.find('.opcw_stop_process');
			stopBtn.on('click touch', function(e) {
				if (!item.hasClass('is-scanning')) {
					return false;
				}
				if (!$(stopBtn).hasClass('stopping')) {
					opcw_stop_scanning_collection(id);
				}
			});
		}
	}

	function opcw_action_after_add_term() {
		if ($('body').hasClass('edit-tags-php') &&
		$('body').hasClass('opcw_show_module_scan')) {
		$(document).ajaxSuccess(function(event, xhr, settings) {
			// check ajax action of request that succeeded
			if (typeof settings != 'undefined' && settings.data &&
				~settings.data.indexOf('action=add-tag')) {

				if ($('.opcw_selected_image img').length) {
					$('.opcw_remove_image').click();
				}
				if ($('.opcw_rules_box.added').length) {
					$('.opcw_rules_box.added').remove();
					opcw_clear_new_item($('.opcw_rules_box').find('.opcw_rule_item'));
					$('.opcw_rules_box').find('.opcw_rule_item').change();
				}
				if ($('.opcw_init_select2').length) {
					$('.opcw_init_select2').empty();
					$('.opcw_init_select2').select2('destroy');
					opcw_init_select2_settings();
				}

				if ($('#the-list > tr:not(.added) .opcw_scan_action').length) {
					$('#the-list > tr:not(.added) .opcw_scan_action').on('click touch', function(e) {
						var term_id = $(this).data('term'),
							term_name = $(this).data('name'),
							tax_name = $(this).data('tax-name'),
							tax = $('input[name="taxonomy"]').val(),
							data_scan = [
							{
								term_id: term_id,
								term_name: term_name,
								tax_name: tax_name,
								tax: tax,
							}
						];
						opcw_active_process_box(data_scan);
					});
				}
			}
		});
	}
	}

	function opcw_bulk_scan() {
		$('#doaction').on('click', function(e) {
			e.preventDefault();
			var action = $(this).prev('select[id^="bulk-action-selector-"]').val();
			if (action == 'opcw_scan') {
				var data_scan = [];
				// Get the selected term IDs
				$('#the-list .check-column input[type="checkbox"]:checked').each(function() {
					var term_id = $(this).val(),
						term_name = $(this).closest('tr').find('.opcw_scan_action').data('name'),
						tax_name = $(this).closest('tr').find('.opcw_scan_action').data('tax-name'),
						tax = $('input[name="taxonomy"]').val(),
						item_scan = {
							term_id: term_id,
							term_name: term_name,
							tax_name: tax_name,
							tax: tax,
						};

					data_scan.push(item_scan);
				});

				opcw_active_process_box(data_scan);
    			return false
			}
			$(this).closest('form').submit();
		});
	}

	function opcw_add_import_export_action() {
		if (typeof opcw_script.export_link == 'undefined') {
			return false;
		}
		
		var title = $('#addtag').prev('h2');

		if (title.length) {
			let importBtn = '<a id="open-import-collection" class="button-primary" href="javascript:void(0)">Import</a>';
			let exportBtn = '<a class="button" style="margin-left:10px" href="'+opcw_script.export_link+'">Export All</a>';
			title.append('<span>'+importBtn+exportBtn+'</span>');
			title.addClass('opcw_tag_title');
		}

		$('#open-import-collection').on('click touch', function(e) {
			e.preventDefault();
			$('#opcw_import_collection').show();
		});
		$('#close_import_collection').on('click touch', function(e) {
			e.preventDefault();
			$('#opcw_import_collection').fadeOut();
		});
	}

	$(document).on('opcw_scan_response', function(e, response, collection) {
		var term_scan = $('#term-scan-'+collection),
			matchEl = term_scan.find('.opcw_products_match');

		term_scan.find('.opcw_data_process').text(response.data.percentage+'%');
		term_scan.find('.opcw_process_active').css('width', response.data.percentage+'%');

		var productsMatch = parseInt(matchEl.text()),
			productsMatch = productsMatch + parseInt(response.data.match_count);
		matchEl.text(productsMatch);

		var tagRow = $('#tag-'+collection);
		if (tagRow.length && response.data.term_count) {
			var tagLink = tagRow.find('.column-posts[data-colname="Count"] a');
			tagLink.text(response.data.term_count);	
		}

		if(response.data.is_finished) {
			term_scan.removeClass('is-scanning').addClass('is-finished');
		}
	});

    $(document).ready(function($) {
		// Export/Import Event
		opcw_add_import_export_action();

		// Handler Collections
		opcw_init_condition();
		opcw_trigger_condition();
		opcw_init_repeater_rules();
		opcw_init_select2_settings();

		opcw_action_image();
		// End

		// Edit collection
		opcw_add_local_item_html();
		opcw_scan_action_edit_page();
		opcw_scan_action_list_page();
		
		// Bulk Scan
		opcw_bulk_scan();

		// Action start scanning
		opcw_on_start_scanning();

		// Trigger deactive process
		opcw_deactive_process_box();

		// End

		if ($('.opcw_show_module_scan #the-list > tr').length) {
			$('.opcw_show_module_scan #the-list > tr').addClass('added');
		}
    });
	
	opcw_action_after_add_term();
});