(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var FomoPressAdmin = {

		init: function(){
			FomoPressAdmin.notificationStatus();
			FomoPressAdmin.bindEvents();
			FomoPressAdmin.initFields();
		},

		initFields: function(){
			$('.fomopress-metabox-wrapper .fomopress-meta-field').trigger('change');
			FomoPressAdmin.initColorField();
			FomoPressAdmin.initGroupField();
		},

		bindEvents: function(){
			$('body').delegate( '.fomopress-settings-menu li', 'click', function( e ) {
				FomoPressAdmin.settingsTab( this );
            } );
			$('body').delegate( '.fomopress-settings-button', 'click', function( e ) {
				e.preventDefault();
				FomoPressAdmin.submitSettings( this );
            } );
			$('body').delegate( '.fomopress-opt-alert', 'click', function( e ) {
				FomoPressAdmin.fieldAlert( this );
            } );
			$('body').delegate( '.fomopress-section-reset', 'click', function( e ) {
				e.preventDefault();
				FomoPressAdmin.resetSection( this );
            } );
			$('body').delegate( '.fomopress-meta-field', 'change', function( ) {
				FomoPressAdmin.fieldChange( this );
            } );
			$('body').delegate( '.fomopress-meta-next, .fomopress-quick-builder-btn', 'click', function(e) {
				e.preventDefault();
				FomoPressAdmin.tabChanger( this );
            } );
			$('body').delegate( '.fomopress-single-theme-wrapper', 'click', function(e) {
				e.preventDefault();
				FomoPressAdmin.selectImage( this );
            } );
			$('body').delegate( '.fomopress-group-field .fomopress-group-field-title', 'click', function(e) {
				e.preventDefault();
				if( $( e.srcElement ).hasClass( 'fomopress-group-field-title' ) ) {
					FomoPressAdmin.groupToggle( this );
				}
			} );
			$('body').delegate( '.fomopress-group-field .fomopress-group-remove', 'click', function() {
				FomoPressAdmin.removeGroup(this);
			} );
			$('body').delegate( '.fomopress-group-field .fomopress-group-clone', 'click', function() {
                FomoPressAdmin.cloneGroup(this);
            } );
			$('body').delegate( '.fomopress-media-field-wrapper .fomopress-media-upload-button', 'click', function(e) {
				e.preventDefault();
                FomoPressAdmin.initMediaField(this);
            } );
			$('body').delegate( '.fomopress-media-field-wrapper .fomopress-media-remove-button', 'click', function(e) {
				e.preventDefault();
                FomoPressAdmin.removeMedia(this);
			} );
			$('body').delegate( '.fomopress-optin-button', 'click', function(e) {
				e.preventDefault();
                FomoPressAdmin.optinAllowOrNot(this);
			} );
		},

		tabChanger : function( button ){
			var button = $( button ),
				totalTab = button.parents('.fomopress-tab-content-wrapper').data('totaltab'),
				tabID = button.data('tabid'),
				tab = $( '#fomopress-' + button.data('tab') );

			if( totalTab + 1 == tabID ){
				$('#publish').trigger('click');
				return;
			}

			$('.fomopress-meta-tab-menu li[data-tabid="'+ tabID +'"]').trigger('click');
			$('.fomopress-builder-menu li[data-tabid="'+ tabID +'"]').trigger('click');
		},

		selectImage : function( image ){
			var imgParent = $( image ),
				img = imgParent.find('img'),
				value = img.data('theme'),
				wrapper = $( imgParent.parents('.fomopress-theme-field-wrapper') ),
				inputID = wrapper.data('name');

			imgParent.addClass('fomopress-theme-selected').siblings().removeClass('fomopress-theme-selected');
			$('#' + inputID).val( value );
			imgParent.trigger('change');
		},

		notificationStatus : function(){
			$('.wp-list-table .column-notification_status img').off('click').on('click', function(e) {
				e.stopPropagation();
				var $this       = $(this),
					isActive    = $(this).attr('src').indexOf('active1.png') >= 0,
					postID      = $(this).data('post'),
					nonce       = $(this).data('nonce');
	
				if ( isActive ) {
					$this.attr('src', $this.attr('src').replace('active1.png', 'active0.png'));
					$this.attr('title', 'Inactive').attr('alt', 'Inactive');
				} else {
					$this.attr('src', $this.attr('src').replace('active0.png', 'active1.png'));
					$this.attr('title', 'Active').attr('alt', 'Active');
				}
	
				$.ajax({
					type: 'post',
					url: ajaxurl,
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
		},

		fieldAlert: function( element ){
			var element = $( element );
			swal({
				title     : "Hi, there.",
				text      : "You have to upgrade in PRO",
				icon      : "warning",
				buttons   : [false, "Close"],
				dangerMode: true,
			});
		},

		resetSection: function( button ){
			var button = $( button ),
				parent = button.parents('.fomopress-metabox-section'),
				fields = parent.find('.fomopress-meta-field'), updateFields = [];
			
			window.fieldsss = fields;
			fields.map(function(iterator, item){ 
				var item = $( item ),
					default_value = item.data( 'default' );

				item.val( default_value );

				if( item.hasClass('wp-color-picker') ) {
					item.parents('.wp-picker-container').find('.wp-color-result').removeAttr('style')
				}
				if( item[0].id == 'fomopress_border' ){
					item.trigger('click');
				} else {
					item.trigger('change');
				}
			});
		},

		fieldChange: function( input ){
			var field   = $(input),
                id  = field.attr('id'),
                toggle  = field.data('toggle'),
                hide    = field.data('hide'),
                val     = field.val(),
				i       = 0;

			if ( 'checkbox' === field.attr('type') ) {
				if( ! field.is(':checked') ) {
					val = 0;
				} else {
					val = 1;
				}
			} 

			if ( field.hasClass('fomopress-theme-selected') ) {
				id = field.parents('.fomopress-theme-field-wrapper').data('name');
				val = $( '#' + id ).val();
			}

			// TOGGLE sections or fields.
			if ( typeof toggle !== 'undefined' ) {

				if ( typeof toggle !== 'object' ) {
					toggle = JSON.parse(toggle);
				}
				for(i in toggle) {
					FomoPressAdmin.fieldToggle(toggle[i].fields, 'hide', '#fomopress-', '', id);
					FomoPressAdmin.fieldToggle(toggle[i].sections, 'hide', '#fomopress-meta-section-', '', id);
				}

				if(typeof toggle[val] !== 'undefined') {
					FomoPressAdmin.fieldToggle(toggle[val].fields, 'show', '#fomopress-', '', id);
					FomoPressAdmin.fieldToggle(toggle[val].sections, 'show', '#fomopress-meta-section-', '', id);
				}

			}

			// HIDE sections or fields.
    		if ( typeof hide !== 'undefined' ) {

                if ( typeof hide !== 'object' ) {
    			    hide = JSON.parse(hide);
				}
				
    			if(typeof hide[val] !== 'undefined') {
    				FomoPressAdmin.fieldToggle(hide[val].fields, 'hide', '#fomopress-', '', id);
    				FomoPressAdmin.fieldToggle(hide[val].sections, 'hide', '#fomopress-meta-section-', '', id);
    			}
			}
		},

		fieldToggle: function( array, func, prefix, suffix, id = '' ){
			var i = 0;
			
			suffix = 'undefined' == typeof suffix ? '' : suffix;
    		if(typeof array !== 'undefined') {
    			for( ; i < array.length; i++) {
    				$(prefix + array[i] + suffix)[func]();
    			}
    		}
		},

		initColorField: function(){
			if ( 'undefined' !== typeof $.fn.wpColorPicker ) {
                // Add Color Picker to all inputs that have 'mbt-color-picker' class.
                $( '.fomopress-colorpicker-field' ).each(function() {
                    var color = $(this).val();
                    $(this).wpColorPicker({
                        change: function(event, ui) {
                            var element = event.target;
                            var color = ui.color.toString();
							$(element).parents('.wp-picker-container').find('input.fomopress-colorpicker-field').val(color).trigger('change');
                        }
                    }).parents('.wp-picker-container').find('.wp-color-result').css('background-color', '#' + color);
                });
            }
		},
		initGroupField : function(){

			if( $('.fomopress-group-field-wrapper').length < 0 ) {
				return;
			}

			var fields = $('.fomopress-group-field-wrapper');

			fields.each(function(){

				var $this  = $( this ),
					groups = $this.find('.fomopress-group-field'),
					firstGroup   = $this.find('.fomopress-group-field:first'),
					lastGroup   = $this.find('.fomopress-group-field:last');

				groups.each(function() {
					var groupContent = $(this).find('.fomopress-group-field-title:not(.open)').next();
					if ( groupContent.is(':visible') ) {
						groupContent.addClass('open');
					}
				});

				$this.find('.fomopress-group-field-add').on('click', function( e ){
					e.preventDefault();

					var fieldId     = $this.attr('id'),
					    dataId      = $this.data( 'name' ),
					    wrapper     = $this.find( '.fomopress-group-fields-wrapper' ),
					    groups      = $this.find('.fomopress-group-field'),
					    firstGroup  = $this.find('.fomopress-group-field:first'),
					    lastGroup   = $this.find('.fomopress-group-field:last'),
					    clone       = $( $this.find('.fomopress-group-template').html() ),
					    groupId     = parseInt( lastGroup.data('id') ),
					    nextGroupId = groupId + 1,
					    title       = clone.data('group-title');

					groups.each(function() {
						$(this).removeClass('open');
					});

					// Reset all data of clone object.
					clone.attr('data-id', nextGroupId);
					clone.addClass('open');
					// clone.find('.fomopress-group-field-title > span').html(title + ' ' + nextGroupId);
					clone.find('tr.fomopress-field[id*='+fieldId+']').each(function() {
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
				});

			});

		},
		setDate : function( item ){
			var date    = new Date();
			item.find('.fomopress-group-field-timestamp').val( date.getTime() );
		},
		groupToggle : function( input ){
			var input = $(input),
				wrapper = input.parents('.fomopress-group-field');

			if( wrapper.hasClass('open') ) {
				wrapper.removeClass( 'open' );
			} else {
				wrapper.addClass('open').siblings().removeClass('open');
			}
		},
		removeGroup : function( button ){
			var groupId = $(button).parents('.fomopress-group-field').data('id'),
                group   = $(button).parents('.fomopress-group-field[data-id="'+groupId+'"]');

            group.fadeOut({
                duration: 300,
                complete: function() {
                    $(this).remove();
                }
            });
		},
		cloneGroup : function( button ){
			var groupId = $(button).parents('.fomopress-group-field').data('id'),
				group   = $(button).parents('.fomopress-group-field[data-id="'+groupId+'"]'),
				clone   = $( group.clone() ),
				lastGroup   = $( button ).parents('.fomopress-group-fields-wrapper').find('.fomopress-group-field:last'),
				parent  = group.parent(),
				nextGroupID = $( lastGroup ).data('id') + 1;

			clone.attr('data-id', nextGroupID);
			clone.insertAfter(group);
			// clone.find('.fomopress-group-field-title').trigger('click');
			// clone.addClass('open'); //.siblings().removeClass('open');
			FomoPressAdmin.resetFieldIds( parent.find('.fomopress-group-field') );
		},
		resetFieldIds : function( groups ){
			var groupID = 1;
				
			groups.each(function() {
				var group       = $(this),
					fieldName   = group.data('field-name'),
					fieldId     = 'fomopress-' + fieldName,
					groupInfo   = group.find('.fomopress-group-field-info').data('info'),
					subFields   = groupInfo.group_sub_fields;

				group.data('id', groupID);

				subFields.forEach(function( item ){
					var table_row = group.find('tr.fomopress-field[id="fomopress-' + item.field_name + '"]');

					table_row.find('[name*="'+item.field_name+'"]').each(function(){
						var name = $(this).attr('name'),
							prefix  = name.split(item.field_name)[0],
							suffix  = '';

						if ( undefined === prefix ) {
							prefix = '';
						}
						
						name = name.replace( name, prefix + fieldName + '[' + groupID + '][' + item.original_name + ']' + suffix );
						$(this).attr('name', name).attr('id', name);
					});

					group.find('tr.fomopress-field[id="fomopress-' + item.field_name + '"]').attr('id', fieldId + '[' + groupID + '][' + item.original_name + ']');
				});
				// Update group title.
				// group.find('.fomopress-group-field-title > span').html(title + ' ' + groupId);

				groupID++;
			});
		},
		initMediaField : function( button ){

			var button = $( button ),
				wrapper = button.parents('.fomopress-media-field-wrapper'),
				removeButton = wrapper.find('.fomopress-media-remove-button'),
				imgContainer = wrapper.find('.fomopress-thumb-container'),
				idField = wrapper.find('.fomopress-media-id'),
				urlField = wrapper.find('.fomopress-media-url');

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
                imgContainer.addClass('fomopress-has-thumb').append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );
                idField.val( attachment.id ); // set image id
                urlField.val( attachment.url ); // set image url

                // Hide the upload button
                button.addClass( 'hidden' );

                // Show the remove button
                removeButton.removeClass( 'hidden' );
            });

            // Finally, open the modal on click
            frame.open();
		},
		removeMedia : function( button ) {
			var button = $( button ),
				wrapper = button.parents('.fomopress-media-field-wrapper'),
				uploadButton = wrapper.find('.fomopress-media-upload-button'),
				imgContainer = wrapper.find('.fomopress-has-thumb'),
				idField = wrapper.find('.fomopress-media-id'),
				urlField = wrapper.find('.fomopress-media-url');

			imgContainer.removeClass('fomopress-has-thumb').find('img').remove();

			urlField.val(''); // URL field has to be empty
			idField.val(''); // ID field has to empty as well

			button.addClass('hidden'); // Hide the remove button first
			uploadButton.removeClass('hidden'); // Show the uplaod button
		},
		shownPreview : function( type ) {
			if ( type === 'press_bar' ) {
				$('#fomopress-notification-preview').hide();
			} else {
				$('#fomopress-notification-preview').removeClass('fomopress-notification-preview-comments').removeClass('fomopress-notification-preview-conversions');
				$('#fomopress-notification-preview').show().addClass('fomopress-notification-preview-' + type);
			}
		},
		updatePreview: function( fields ){

			fields.map(function(item, i){
				var event = item.event || 'change';
				$( item.id ).on( event, function(){
					var val = $( item.id ).val(),
						suffix = '',
						selector = '.fomopress-preview-inner';

					if( typeof item.selector != 'undefined' ) {
						selector = item.selector;
					}

					if( typeof item.unit != 'undefined' ) {
						suffix = item.unit;
					}
					/**
					 * This lines of code use for removing & adding the border css 
					 * on CLICK to want border.
					 */
					if( event == 'click' && item.field == 'border' ) {
						window.itemshide = item.hide;
						if( ! $( item.id ).is(":checked") ) {
							item.hide.forEach(function(item){
								if( item.property == 'border-width' ) {
									$( selector ).css( item.property, '0px' );
								} else {
									$( selector ).css( item.property, '' );
								}
							});
						} else {
							item.hide.forEach(function(item){
								var oval = $(item.key).val();
								$( selector ).css( item.property, oval );
							});
						}
					}

					if( typeof item.property != 'undefined' ) {
						$( selector ).css( item.property, val + suffix );
					}
					
					if( 'image_shape' == item.field ) {
						$( selector ).removeClass( 'fp-img-circle fp-img-rounded fp-img-square' );
					}
					if( 'image_position' == item.field ) {
						$( selector ).removeClass( 'fp-img-left fp-img-right' );
					}

					if( item.field == 'image_shape' || 'image_position' == item.field ) {
						$( selector ).addClass( 'fp-img-' + val ); 
						/**
						 * This lines of code use for layouting the notification preview
						 */
						if( 'image_position' == item.field ) {
							if( val == 'left' ) {
								$( '.fomopress-preview-inner' ).css( 'flex-direction', 'row' );
							} else {
								$( '.fomopress-preview-inner' ).css( 'flex-direction', 'row-reverse' );
							}
						}
					}
				})
			});
		},
		optinAllowOrNot : function( button ){
			var button = $( button ),
			    args   = button.data('args'),
			    parent = button.parents('.fomopress-opt-in'),
			    inputs = parent.find('.fomopress-single-opt input'),
			    values = {};

			inputs.each(function(){
				var input = $(this)[0],
					id = input.id;
				if( $( input ).is(':checked') ) {
					values[ id ] = true;
				} else {
					values[ id ] = false;
				}
			});

			values  = Object.assign(values, args);

			$.ajax({
				type: 'post',
				url: ajaxurl,
				data: {
					action: 'fomopress_optin_check',
					fields : values
				},
				success: function(res) {
					if( res == 'true' || res == 'false' ) {
						parent.slideToggle( '500' );
					}
				}
			});
			
		},
		settingsTab : function( button ){
			var button = $(button),
				tabToGo = button.data('tab');

			button.addClass('active').siblings().removeClass('active');
			$('#fs-'+tabToGo).addClass('active').siblings().removeClass('active');
		},
		submitSettings : function( button ){
			var button = $(button),
				submitKey = button.data('key'),
				nonce = button.data('nonce'),
				form = button.parent('#fomopress-settings-'+ submitKey +'-form'),
				formData = $( form ).serializeArray();
		

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'fomopress_' + submitKey + '_settings_ac',
					key: submitKey,
					nonce: nonce,
					form_data: JSON.stringify(formData)
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
		}
	};

	/**
	 * FomoPress Admin Fired
	 * when the document is ready.
	 */
	$(document).ready(function() {
		/**
		 * Current Tab Manage
		 */
		var $tabMenu = $('.fomopress-meta-tab-menu, .fomopress-builder-menu');
		$tabMenu.on( 'click', 'li', function(){
			var $tab = $(this).data( 'tab' ),
				tabID = $(this).data('tabid') - 1;
			$('#fomopress_current_tab').val( $tab );
			$tabMenu.find('li').each(function(i){
				if( i < tabID ) {
					$(this).addClass('fp-complete');
				} else {
					$(this).removeClass('fp-complete');
				}
			});
			$(this).addClass( 'active' ).siblings().removeClass('active');
			$('#fomopress-' + $tab).addClass( 'active' ).siblings().removeClass('active');
		});

		FomoPressAdmin.init();
	});
	
	$( window ).load(function(){
		$('body').on('change', '#fomopress_display_type', function(){
			var type = $(this).val();
			FomoPressAdmin.shownPreview( type );
			if( type == 'conversions' ) {
				$('#fomopress_conversion_from').trigger('change');
			}
		});
		$('#fomopress_display_type').trigger('change');

		var fields = [
			{
				id: "#fomopress_bg_color",
				field: "bg_color",
				property : "background-color",
			},
			{
				id: "#fomopress_text_color",
				field: "text_color",
				property : "color",
			},
			{
				id: "#fomopress_border",
				field: "border",
				event : "click",
				selector : ".fomopress-preview-inner",
				hide : [
					{ 'key': '#fomopress_border_size', 'property' : 'border-width' }, 
					{ 'key': '#fomopress_border_style', 'property' : 'border-style' }, 
					{ 'key': '#fomopress_border_color', 'property' : 'border-color' }, 
				],
			},
			{
				id: "#fomopress_border_size",
				field: "border_size",
				event : "keyup",
				property : "border-width",
				selector : ".fomopress-preview-inner",
				unit : "px",
			},
			{
				id: "#fomopress_border_style",
				field: "border_style",
				property : "border-style",
				selector : ".fomopress-preview-inner",
			},
			{
				id: "#fomopress_border_color",
				field: "border_color",
				property : "border-color",
				selector : ".fomopress-preview-inner",
			},
			{
				id: "#fomopress_image_shape",
				field: "image_shape",
				selector: ".fomopress-preview-image",
			},
			{
				id: "#fomopress_image_position",
				field: "image_position",
				selector: ".fomopress-preview-image",
			},
			{
				id: "#fomopress_first_font_size",
				field: "first_font_size",
				selector: ".fomopress-preview-first-row",
				property : "font-size",
				event : "keyup",
				unit : "px",
			},
			{
				id: "#fomopress_second_font_size",
				field: "second_font_size",
				selector: ".fomopress-preview-second-row",
				property : "font-size",
				event : "keyup",
				unit : "px",
			},
			{
				id: "#fomopress_third_font_size",
				field: "third_font_size",
				selector: ".fomopress-preview-third-row",
				property : "font-size",
				event : "keyup",
				unit : "px",
			},
		];

		FomoPressAdmin.updatePreview( fields );
	});

})( jQuery );
