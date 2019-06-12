(function( $ ) {
	'use strict';

	/**
	 * NotificationX Admin JS
	 */

	$.notificationx = $.notificationx || {};

	$(document).ready(function(){
		$.notificationx.init();
		$('body').on('click', '.nx-metatab-menu li, .nx-builder-tab-menu li, .nx-meta-next, .nx-quick-builder-btn', 
		function(e){
			e.preventDefault();
			$.notificationx.tabChanger( this );
		});
		$('body').on('click', '.nx-single-theme-wrapper', 
		function(e){
			e.preventDefault();
			$.notificationx.templateForTheme();
		});
	});

	$( window ).load(function(){
		$('body').on('change', '#nx_meta_display_type', function(){
			var type = $(this).val();
			switch( type ) {
				case 'conversions' : 
					$('#nx_meta_conversion_from').trigger('change');
					break;
				case 'comments' : 
					$('#nx_meta_comments_source').trigger('change');
					break;
				case 'reviews' : 
					$('#nx_meta_reviews_source').trigger('change');
					break;
				case 'download_stats' : 
					$('#nx_meta_stats_source').trigger('change');
					break;
			}

			$.notificationx.templateForTheme();
		});

		$('body').on('change', '#nx_meta_conversion_from', function(){
			var conv_source = $(this).val();
			switch( conv_source ) {
				case 'woocommerce' : 
					$('#nx_meta_woo_template_adv').trigger('change');
					break;
				case 'edd' : 
					$('#nx_meta_edd_template_adv').trigger('change');
					break;
			}
		});

		$('body').on('change', '#nx_meta_reviews_source', function(){
			var source = $(this).val();
			switch( source ) {
				case 'wp_reviews' : 
					$('#nx_meta_wp_reviews_template_adv').trigger('change');
					$('#nx_meta_wporg_advance_edit').trigger('change');
					break;
			}
		});

		$('body').on('change', '#nx_meta_stats_source', function(){
			var source = $(this).val();
			switch( source ) {
				case 'wp_stats' : 
					$('#nx_meta_wp_stats_template_adv').trigger('change');
					$('#nx_meta_wpstats_advance_edit').trigger('change');
					break;
			}
		});

		$('body').on('change', '.nx-builder-content-wrapper #nx_meta_display_type.nx-select', function( e ){
			var type = $(this).val(),
				title = e.currentTarget.selectedOptions[0].innerText,
				options = { year: 'numeric', month: 'short', day: 'numeric' },
				date = ( new Date() ).toLocaleDateString('en-US', options);
			if( type === 'conversions' ) {
				$('body').on('change', '#nx_meta_conversion_from.nx-select', function( e ){
					var type = $(this).val(),
						title = e.currentTarget.selectedOptions[0].innerText;
					$('.finalize_notificationx_name').text("NotificationX - " + title + ' - ' + date);
				});
				$('#nx_meta_conversion_from.nx-select').trigger('change');
			} else {
				$('.finalize_notificationx_name').text("NotificationX - " + title + ' - ' + date);
			}
		});

		$('#nx_meta_display_type').trigger('change');
	});

	$.notificationx.init = function(){
		$.notificationx.enabledDisabled();
		$.notificationx.toggleFields();
		$.notificationx.bindEvents();
		$.notificationx.initializeFields();
	};

	$.notificationx.templateForTheme = function(){
		var source, templateID, themeID,
			type = $('#nx_meta_display_type').val();
		switch( type ) {
			case 'download_stats' : 
				themeID = $('#nx_meta_wpstats_theme').val();
				source = $('#nx_meta_stats_source').val();
				break;
			case 'reviews' : 
				themeID = $('#nx_meta_wporg_theme').val();
				source = $('#nx_meta_reviews_source').val();
				break;
			case 'comments' : 
				themeID = $('#nx_meta_comment_theme').val();
				source = $('#nx_meta_comments_source').val();
				break;
			case 'conversions' : 
				themeID = $('#nx_meta_theme').val();
				source = $('#nx_meta_conversion_from').val();
				break;
		}

		if( source == 'woocommerce' ) {
			templateID = $('#nx_meta_woo_template_new');
		} else {
			templateID = $('#nx_meta_'+ source +'_template_new');
		}

		if( source == 'wp_comments' ) {
			templateID = $('#nx_meta_comments_template_new');
		}

		var obj = {
			'nx_meta_wp_stats_template_new' : {
				'theme-one' : {
					'first_param' : 'tag_custom',
				},
				'theme-two' : {
					'third_param' : 'tag_last_week',
				}
			}
		};

		var templateDivID = templateID.attr('id');
		if( Object.keys( notificationx.template_settings ).indexOf( templateDivID ) >= 0 && Object.keys( notificationx.template_settings[templateDivID] ).indexOf( themeID ) >= 0 ) {
			var themeOBJ = notificationx.template_settings[templateDivID][themeID];
			templateID.find('input, select').each(function( i, item ){
				var subKey = $( item ).data('subkey');
				if( Object.keys( themeOBJ ).indexOf( subKey ) >= 0 ) {
					$( item ).val( themeOBJ[ subKey ] ).trigger('change');
				}
			});
		}
	};

	$.notificationx.bindEvents = function(){
		$('#nx_meta_show_on').trigger('change');

		$('body').on('click', '.nx-single-theme-wrapper', function(){
			$.notificationx.selectTheme( this )
		});

		/**
		 * Group Field Events
		 */
		$('body').delegate( '.nx-group-field .nx-group-field-title', 'click', function(e) {
			e.preventDefault();
			if( $( e.srcElement ).hasClass( 'nx-group-field-title' ) ) {
				$.notificationx.groupToggle( this );
			}
		});
		$('body').delegate( '.nx-group-field .nx-group-clone', 'click', function() {
			$.notificationx.cloneGroup(this);
		} );
		$('body').on( 'click', '.nx-group-field .nx-group-remove', function() {
			$.notificationx.removeGroup(this);
		});

		/**
		 * Media Field
		 */
		$('body').delegate( '.nx-media-field-wrapper .nx-media-upload-button', 'click', function(e) {
			e.preventDefault();
			$.notificationx.initMediaField( this );
		});
		$('body').delegate( '.nx-media-field-wrapper .nx-media-remove-button', 'click', function(e) {
			e.preventDefault();
			$.notificationx.removeMedia(this);
		});

		/**
		 * Settings Tab
		 */
		$('body').delegate( '.nx-settings-menu li', 'click', function( e ) {
			$.notificationx.settingsTab( this );
		} );
		$('body').delegate( '.nx-submit-general', 'click', function( e ) {
			e.preventDefault();
			var form = $( this ).parent('#nx-settings-general-form');
			$.notificationx.submitSettings( this, form );
		} );

		$('body').delegate( '.nx-opt-alert', 'click', function( e ) {
			$.notificationx.fieldAlert( this );
		} );

		/**
		 * Reset Section Settings
		 */
		$('body').delegate( '.nx-section-reset', 'click', function( e ) {
			e.preventDefault();
			$.notificationx.resetSection( this );
		} );
	};
	/**
	 * This function is responsible for 
	 * enabling and disabling the notificationXs
	 */
	$.notificationx.enabledDisabled = function(){
		$('.wp-list-table .column-notification_status img').off('click').on('click', function(e) {
            e.stopPropagation();
            var $this       = $(this),
                isActive    = $this.attr('src').indexOf('active1.png') >= 0,
                postID      = $this.data('post'),
                nonce       = $this.data('nonce');

            if ( isActive ) {
                $this.attr('src', $this.attr('src').replace('active1.png', 'active0.png'));
                $this.attr('title', 'Inactive').attr('alt', 'Inactive');
            } else {
                $this.attr('src', $this.attr('src').replace('active0.png', 'active1.png'));
                $this.attr('title', 'Active').attr('alt', 'Active');
            }

            $.ajax({
                type: 'post',
                url: window.ajaxurl,
                data: {
                    action: 'notifications_toggle_status',
                    post_id: postID,
                    nonce: nonce,
                    status: isActive ? 'inactive' : 'active'
                },
                success: function(res) {
                    if ( res !== 'success' ) {
                        alert( res );
                        isActive = $this.attr('src').indexOf('active1.png') >= 0;
                        if ( isActive ) {
                            $this.attr('src', $this.attr('src').replace('active1.png', 'active0.png'));
                            $this.attr('title', 'Inactive').attr('alt', 'Inactive');
                        } else {
                            $this.attr('src', $this.attr('src').replace('active0.png', 'active1.png'));
                            $this.attr('title', 'Active').attr('alt', 'Active');
                        }
                    }
                }
            });
        });
	};

	$.notificationx.initializeFields = function(){
		// NotificationX_Admin.initSelect2();
		if( $('.nx-meta-field').length > 0 ) {
			$('.nx-meta-field').map(function( iterator, item ){
				var node = item.nodeName;
				if( node === 'SELECT' ) {
					$(item).select2();
				}
			});
		}
		// NotificationX_Admin.initDatepicker();
		if( $('.nx-countdown-datepicker').length > 0 ) {
			$('.nx-countdown-datepicker').each(function(){
				$(this).find('input').datepicker({
					changeMonth: true,
					changeYear: true,
					dateFormat : 'DD, d MM, yy'
				});
			});
		}
        
		$('.notificationx-metabox-wrapper .nx-meta-field:not(#nx_meta_conversion_from)').trigger('change');
		
		// NotificationX_Admin.initColorField();
		if( $( '.nx-colorpicker-field' ).length > 0 ){
			if ( 'undefined' !== typeof $.fn.wpColorPicker ) {
				$( '.nx-colorpicker-field' ).each(function() {
					var color = $(this).val();
					$(this).wpColorPicker({
						change: function(event, ui) {
							var element = event.target;
							var color = ui.color.toString();
							$(element).parents('.wp-picker-container').find('input.nx-colorpicker-field').val(color).trigger('change');
						}
					}).parents('.wp-picker-container').find('.wp-color-result').css('background-color', '#' + color);
				});
			}
		}
		$.notificationx.groupField();
		
		$.notificationx.template();
		$('.nx-meta-template-editable').trigger('blur');
	};

	$.notificationx.groupField = function(){

        if( $('.nx-group-field-wrapper').length < 0 ) {
            return;
        }

        var fields = $('.nx-group-field-wrapper');

        fields.each(function(){

            var $this  = $( this ),
                groups = $this.find('.nx-group-field'),
                firstGroup   = $this.find('.nx-group-field:first'),
                lastGroup   = $this.find('.nx-group-field:last');

            groups.each(function() {
                var groupContent = $(this).find('.nx-group-field-title:not(.open)').next();
                if ( groupContent.is(':visible') ) {
                    groupContent.addClass('open');
                }
            });

            $this.find('.nx-group-field-add').on('click', function( e ){
                e.preventDefault();

                var fieldId     = $this.attr('id'),
                    dataId      = $this.data( 'name' ),
                    wrapper     = $this.find( '.nx-group-fields-wrapper' ),
                    groups      = $this.find('.nx-group-field'),
                    firstGroup  = $this.find('.nx-group-field:first'),
                    lastGroup   = $this.find('.nx-group-field:last'),
                    clone       = $( $this.find('.nx-group-template').html() ),
                    groupId     = parseInt( lastGroup.data('id') ),
                    nextGroupId = 1,
					title       = clone.data('group-title');
					
				if( ! isNaN( groupId ) ) {
					nextGroupId = groupId + 1;
				}
			
                groups.each(function() {
                    $(this).removeClass('open');
                });

                // Reset all data of clone object.
                clone.attr('data-id', nextGroupId);
                clone.addClass('open');
                // clone.find('.nx-group-field-title > span').html(title + ' ' + nextGroupId);
                clone.find('tr.nx-field[id*='+fieldId+']').each(function() {
                    var fieldName       = dataId;
                    var fieldNameSuffix = $(this).attr('id').split('[1]')[1];
                    var nextFieldId     = fieldName + '[' + nextGroupId + ']' + fieldNameSuffix;
                    var label           = $(this).find('th label');

                    $(this).find('[name*="'+fieldName+'[1]"]').each(function() {
                        var inputName       = $(this).attr('name').split('[1]');
                        var inputNamePrefix = inputName[0];
                        var inputNameSuffix = inputName[1];
                        var newInputName    = inputNamePrefix + '[' + nextGroupId + ']' + inputNameSuffix;
                        $(this).attr('id', newInputName).attr('name', newInputName);
                        label.attr('for', newInputName);
                    });

                    $(this).attr('id', nextFieldId);
                });

				clone.insertBefore( $( this ) );
				
				$.notificationx.resetFieldIds( $('.nx-group-field') );
            });

        });

	};

	/**
	 * This function will change tab 
	 * with menu click & Next Previous Button Click
	 */
	$.notificationx.tabChanger = function( buttonName ){
		var button = $( buttonName ),
			tabID = button.data('tabid'),
			tabKey = button.data('tab'), tab;

		if( tabKey != '' ) {
			tab = $( '#nx-' + tabKey );
			$('#nx_builder_current_tab').val( tabKey );
		}
	
		if( buttonName.nodeName !== 'BUTTON' ) {
			button.parent().find('li').each(function( i ){
				if( i < tabID ) {
					$( this ).addClass('nx-complete');
				} else {
					$( this ).removeClass('nx-complete');
				}
			});

			button.addClass( 'active' ).siblings().removeClass('active');
			tab.addClass( 'active' ).siblings().removeClass('active');
			return;
		}
		if( tab === undefined ){
			$('#publish').trigger('click');
			return;
		}
		$('.nx-metatab-menu li[data-tabid="'+ tabID +'"]').trigger('click');
		$('.nx-builder-tab-menu li[data-tabid="'+ tabID +'"]').trigger('click');
	};

	$.notificationx.toggleFields = function(){
		$( "body" ).delegate( '.nx-meta-field', 'change', function( e ) {
                $.notificationx.checkDependencies( this );
            }
		);
	};

	$.notificationx.toggle = function( array, func, prefix, suffix, id) {
		var i = 0;
		suffix = 'undefined' == typeof suffix ? '' : suffix;

		if(typeof array !== 'undefined') {
			for( ; i < array.length; i++) {
				var selector = prefix + array[i] + suffix;
				if( notificationx.template.indexOf( id ) >= 0 ) {
					selector = "#nx_meta_" + id + "_" + array[i] + suffix;
				}

				$(selector)[func]();
			}
		}
	};

	$.notificationx.checkDependencies = function( variable ){
		if ( notificationx.toggleFields === null ) {
			return;
		}

		var current = $( variable ),
			container = current.parents( '.nx-field:first' ),
			id = container.data( 'id' ),
			value = current.val();
		
		if ( 'checkbox' === current.attr('type') ) {
			if( ! current.is(':checked') ) {
				value = 0;
			} else {
				value = 1;
			}
		}

		if ( current.hasClass('nx-theme-selected') ) {
			var currentTheme = current.parents('.nx-theme-control-wrapper').data('name');
			value = $( '#' + currentTheme ).val();
		}

		var mainid = id;
		
		if( notificationx.template.indexOf( id ) >= 0 ) {
			id = current.data('subkey');
		}

		if ( notificationx.toggleFields.hasOwnProperty( id ) ) {
			var canShow = notificationx.toggleFields[id].hasOwnProperty( value );
			var canHide = true;
			if( notificationx.hideFields[id] ) {
				var canHide = notificationx.hideFields[id].hasOwnProperty( value );
			}
			
			if( notificationx.toggleFields.hasOwnProperty( id ) && canHide ) {
				$.each(notificationx.toggleFields[id], function( key, array ){
					$.notificationx.toggle(array.fields, 'hide', '#nx-meta-', '', mainid);
					$.notificationx.toggle(array.sections, 'hide', '#nx-meta-section-', '', mainid);
				})
			}
	
			if( canShow ) {
				$.notificationx.toggle(notificationx.toggleFields[id][value].fields, 'show', '#nx-meta-', '', mainid);
				$.notificationx.toggle(notificationx.toggleFields[id][value].sections, 'show', '#nx-meta-section-', '', mainid);
			}
		}

		if( notificationx.hideFields.hasOwnProperty( id ) ) {
			var hideFields = notificationx.hideFields[id];

			if( hideFields.hasOwnProperty( value ) ) {
				$.notificationx.toggle(hideFields[ value ].fields, 'hide', '#nx-meta-', '', mainid);
				$.notificationx.toggle(hideFields[ value ].sections, 'hide', '#nx-meta-section-', '', mainid);
			}
		}

	};

	$.notificationx.selectTheme = function( image ){
		var imgParent = $( image ),
			img = imgParent.find('img'),
			value = img.data('theme'),
			wrapper = $( imgParent.parents('.nx-theme-control-wrapper') ),
			inputID = wrapper.data('name');

		imgParent.addClass('nx-theme-selected').siblings().removeClass('nx-theme-selected');
		$('#' + inputID).val( value );
		imgParent.trigger('change');
	};

	$.notificationx.groupToggle = function( group ){
		var input = $( group ),
			wrapper = input.parents('.nx-group-field');

		if( wrapper.hasClass('open') ) {
			wrapper.removeClass( 'open' );
		} else {
			wrapper.addClass('open').siblings().removeClass('open');
		}
	};

	$.notificationx.removeGroup = function( button ){
		var groupId = $(button).parents('.nx-group-field').attr('data-id'),
			group   = $(button).parents('.nx-group-field[data-id="'+groupId+'"]'),
			parent  = group.parent();

		group.fadeOut({
			duration: 300,
			complete: function() {
				$(this).remove();
			}
		});

		$.notificationx.resetFieldIds( parent.find('.nx-group-field') );
	};

	$.notificationx.cloneGroup = function( button ){
		var groupId = $(button).parents('.nx-group-field').attr('data-id'),
			group   = $(button).parents('.nx-group-field[data-id="'+groupId+'"]'),
			clone   = $( group.clone() ),
			lastGroup   = $( button ).parents('.nx-group-fields-wrapper').find('.nx-group-field:last'),
			parent  = group.parent(),
			nextGroupID = $( lastGroup ).data('id') + 1;
			
		group.removeClass('open');

		clone.attr('data-id', nextGroupID);
		clone.insertAfter( group );
		$.notificationx.resetFieldIds( parent.find('.nx-group-field') );
	};

	$.notificationx.resetFieldIds = function( groups ){
		if( groups.length <= 0 ) {
			return;
		}
		var groupID = 0;

		groups.map(function( iterator, item ){

			var item = $(item),
				fieldName = item.data('field-name'),
				groupInfo = item.find( '.nx-group-field-info' ).data('info'),
				subFields = groupInfo.group_sub_fields;

			item.attr('data-id', groupID);

			var table_row = item.find('tr.nx-field');
			
			table_row.each(function( i, child ){

				var child = $( $( child )[0] ),
					childInput = child.find( '[name*="nx_meta_'+fieldName+'"]' ),
					key = childInput.attr('data-key'),
					subKey = subFields[i].original_name,
					dataID = fieldName + "["+ groupID + "][" + subKey + "]",
					idName = 'nx-meta-' + dataID,
					inputName = 'nx_meta_' + dataID;

				child.attr( 'data-id', dataID );
				child.attr( 'id', idName );

				childInput.attr('id', inputName);
				childInput.attr('name', inputName);
				childInput.attr('data-key', dataID);

				// if( childInput.length > 1 ) {
				// 	childInput.each(function(i, subInput){
				// 	});
				// }
			});

			groupID++;
		});
	}

	$.notificationx.initMediaField = function( button ){
		var button = $( button ),
			wrapper = button.parents('.nx-media-field-wrapper'),
			removeButton = wrapper.find('.nx-media-remove-button'),
			imgContainer = wrapper.find('.nx-thumb-container'),
			idField = wrapper.find('.nx-media-id'),
			urlField = wrapper.find('.nx-media-url');

		// Create a new media frame
		var frame = wp.media({
			title: 'Upload Photo',
			button: {
				text: 'Use this photo'
			},
			multiple: false  // Set to true to allow multiple files to be selected
		});

		// When an image is selected in the media frame...
		frame.on( 'select', function() {
			// Get media attachment details from the frame state
			var attachment = frame.state().get('selection').first().toJSON();
			/**
			 * Set image to the image container
			 */
			imgContainer.addClass('nx-has-thumb').append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );
			idField.val( attachment.id ); // set image id
			urlField.val( attachment.url ); // set image url
			// Hide the upload button
			button.addClass( 'hidden' );
			// Show the remove button
			removeButton.removeClass( 'hidden' );
		});
		// Finally, open the modal on click
		frame.open();
	};

	$.notificationx.removeMedia = function( button ){
		var button = $( button ),
			wrapper = button.parents('.nx-media-field-wrapper'),
			uploadButton = wrapper.find('.nx-media-upload-button'),
			imgContainer = wrapper.find('.nx-has-thumb'),
			idField = wrapper.find('.nx-media-id'),
			urlField = wrapper.find('.nx-media-url');

		imgContainer.removeClass('nx-has-thumb').find('img').remove();

		urlField.val(''); // URL field has to be empty
		idField.val(''); // ID field has to empty as well

		button.addClass('hidden'); // Hide the remove button first
		uploadButton.removeClass('hidden'); // Show the uplaod button
	};

	$.notificationx.fieldAlert = function( button ){
		var premium_content = document.createElement("p");
		var premium_anchor = document.createElement("a");
			
		premium_anchor.setAttribute( 'href', 'https://wpdeveloper.net/in/notificationx-pro' );
		premium_anchor.innerText = 'Premium';
		premium_anchor.style.color = 'red';
		premium_content.innerHTML = 'You need to upgrade to the <strong>'+ premium_anchor.outerHTML +' </strong> Version to use this feature';
		
		swal({
			title     : "Opps...",
			content   :  premium_content,
			icon      : "warning",
			buttons   : [false, "Close"],
			dangerMode: true,
		});
	};

	$.notificationx.resetSection = function( button ){
		var button = $( button ),
			parent = button.parents('.nx-meta-section'),
			fields = parent.find('.nx-meta-field'), updateFields = [];
		
		window.fieldsss = fields;
		fields.map(function(iterator, item){ 
			var item = $( item ),
				default_value = item.data( 'default' );

			item.val( default_value );

			if( item.hasClass('wp-color-picker') ) {
				item.parents('.wp-picker-container').find('.wp-color-result').removeAttr('style')
			}
			if( item[0].id == 'nx_meta_border' ){
				item.trigger('click');
			} else {
				item.trigger('change');
			}
		});
	};

	$.notificationx.settingsTab = function( button ){
		var button = $(button),
			tabToGo = button.data('tab');

		button.addClass('active').siblings().removeClass('active');
		$('#nx-'+tabToGo).addClass('active').siblings().removeClass('active');
	};

	$.notificationx.submitSettings = function( button, form ){
		var button = $(button),
			submitKey = button.data('key'),
			nonce = button.data('nonce'),
			formData = $( form ).serializeArray();

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'nx_general_settings',
				key: submitKey,
				nonce: nonce,
				form_data: formData
			},
			success: function(res) {
				if ( res === 'success' ) {
					swal({
						title     : "Settings Saved!",
						text      : "Click OK to continue",
						icon      : "success",
						buttons   : [false, "Ok"],
					});
				} else {
					swal({
						title     : "Settings Not Saved!",
						text      : "Click OK to continue",
						icon      : "success",
						buttons   : [false, "Ok"],
					});
				}
			}
		});
	};

	$.notificationx.template = function( e ){
		$('.nx-meta-template-editable').prop('disabled',true);

		$('.nx-meta-template-editable').on('blur', function(){
			var editable = $(this),
				template = editable[0].innerText,
				splitedTemplate = template.trim().split("\n"),
				res, newItemLine = [], final;
			var nextSiblingsChild = editable[0].nextElementSibling.children;
			
			if( splitedTemplate != null ) {
				splitedTemplate.forEach(function( item, i ){
					if( item != '' ) {
						var pattern = /\{\{[^\s]*\}\}/g;
						var templateVar = item.match( pattern );

						$(nextSiblingsChild[i]).val( item ); // set value in hidden field!

						if( templateVar != null ) {
							templateVar.forEach(function( childParam, iterator ){
								if( iterator > 0 ) {
									res = res.replace( childParam, '<span style="color:red">' + childParam + '</span>' );
								} else {
									res = item.replace( childParam, '<span style="color:red">' + childParam + '</span>' );
								}
							});
							newItemLine.push( res );
						} else {
							newItemLine.push( item );
						}
					}
				});
			}
			final = newItemLine.join( '<br>' );
			editable.html( final );
		});
	};


})( jQuery );
