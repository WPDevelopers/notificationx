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

	var NotificationX_Admin = {

		init: function(){
			NotificationX_Admin.notificationStatus();
			NotificationX_Admin.bindEvents();
			NotificationX_Admin.initFields();
		},

		initFields: function(){
			NotificationX_Admin.initSelect2();
			$('.notificationx-metabox-wrapper .nx-meta-field:not(#nx_meta_conversion_from)').trigger('change');
			NotificationX_Admin.initColorField();
			NotificationX_Admin.initGroupField();
		},

		bindEvents: function(){
			$('body').delegate( '.nx-settings-menu li', 'click', function( e ) {
				NotificationX_Admin.settingsTab( this );
            } );
			$('body').delegate( '.nx-submit-general', 'click', function( e ) {
				e.preventDefault();
				NotificationX_Admin.submitSettings( this );
            } );
			$('body').delegate( '.nx-opt-alert', 'click', function( e ) {
				NotificationX_Admin.fieldAlert( this );
            } );
			$('body').delegate( '.nx-section-reset', 'click', function( e ) {
				e.preventDefault();
				NotificationX_Admin.resetSection( this );
            } );
			$('body').delegate( '.nx-meta-field', 'change', function( ) {
				NotificationX_Admin.fieldChange( this );
            } );
			$('body').delegate( '.nx-meta-next, .nx-quick-builder-btn', 'click', function(e) {
				e.preventDefault();
				NotificationX_Admin.tabChanger( this );
            } );
			$('body').delegate( '.nx-single-theme-wrapper', 'click', function(e) {
				e.preventDefault();
				NotificationX_Admin.selectImage( this );
            } );
			$('body').delegate( '.nx-group-field .nx-group-field-title', 'click', function(e) {
				e.preventDefault();
				if( $( e.srcElement ).hasClass( 'nx-group-field-title' ) ) {
					NotificationX_Admin.groupToggle( this );
				}
			} );
			$('body').delegate( '.nx-group-field .nx-group-remove', 'click', function() {
				NotificationX_Admin.removeGroup(this);
			} );
			$('body').delegate( '.nx-group-field .nx-group-clone', 'click', function() {
                NotificationX_Admin.cloneGroup(this);
            } );
			$('body').delegate( '.nx-media-field-wrapper .nx-media-upload-button', 'click', function(e) {
				e.preventDefault();
                NotificationX_Admin.initMediaField(this);
            } );
			$('body').delegate( '.nx-media-field-wrapper .nx-media-remove-button', 'click', function(e) {
				e.preventDefault();
                NotificationX_Admin.removeMedia(this);
			} );
			$('body').delegate( '.nx-optin-button', 'click', function(e) {
				e.preventDefault();
                NotificationX_Admin.optinAllowOrNot(this);
			} );
		},

		initSelect2 : function(){
			$('.nx-meta-field').map(function( iterator, item ){
				var node = item.nodeName;

				if( node === 'SELECT' ) {
					$(item).select2();
				}
			});
		},

		tabChanger : function( button ){
			var button = $( button ),
				totalTab = button.parents('.nx-metatab-wrapper').data('totaltab'),
				tabID = button.data('tabid'),
				tab = $( '#nx-' + button.data('tab') );

			if( totalTab + 1 == tabID ){
				$('#publish').trigger('click');
				return;
			}

			$('.nx-metatab-menu li[data-tabid="'+ tabID +'"]').trigger('click');
			$('.nx-builder-tab-menu li[data-tabid="'+ tabID +'"]').trigger('click');
		},

		selectImage : function( image ){
			var imgParent = $( image ),
				img = imgParent.find('img'),
				value = img.data('theme'),
				wrapper = $( imgParent.parents('.nx-theme-control-wrapper') ),
				inputID = wrapper.data('name');

			imgParent.addClass('nx-theme-selected').siblings().removeClass('nx-theme-selected');
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
			// var element = $( element );

			var premium_content = document.createElement("p");
			var premium_anchor = document.createElement("a");
				
			premium_anchor.setAttribute( 'href', 'https://wpdeveloper.net/notificationx-pro' );
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
		},

		resetSection: function( button ){
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

			if ( field.hasClass('nx-theme-selected') ) {
				id = field.parents('.nx-theme-control-wrapper').data('name');
				val = $( '#' + id ).val();
			}

			// TOGGLE sections or fields.
			if ( typeof toggle !== 'undefined' ) {

				if ( typeof toggle !== 'object' ) {
					toggle = JSON.parse(toggle);
				}
				for(i in toggle) {
					NotificationX_Admin.fieldToggle(toggle[i].fields, 'hide', '#nx-meta-', '', id);
					NotificationX_Admin.fieldToggle(toggle[i].sections, 'hide', '#nx-meta-section-', '', id);
				}

				if(typeof toggle[val] !== 'undefined') {
					NotificationX_Admin.fieldToggle(toggle[val].fields, 'show', '#nx-meta-', '', id);
					NotificationX_Admin.fieldToggle(toggle[val].sections, 'show', '#nx-meta-section-', '', id);
				}

			}

			// HIDE sections or fields.
    		if ( typeof hide !== 'undefined' ) {

                if ( typeof hide !== 'object' ) {
    			    hide = JSON.parse(hide);
				}
				
    			if(typeof hide[val] !== 'undefined') {
    				NotificationX_Admin.fieldToggle(hide[val].fields, 'hide', '#nx-meta-', '', id);
    				NotificationX_Admin.fieldToggle(hide[val].sections, 'hide', '#nx-meta-section-', '', id);
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
		},
		initGroupField : function(){

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
					    nextGroupId = groupId + 1,
					    title       = clone.data('group-title');

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
				});

			});

		},
		setDate : function( item ){
			var date    = new Date();
			item.find('.nx-group-field-timestamp').val( date.getTime() );
		},
		groupToggle : function( input ){
			var input = $(input),
				wrapper = input.parents('.nx-group-field');

			if( wrapper.hasClass('open') ) {
				wrapper.removeClass( 'open' );
			} else {
				wrapper.addClass('open').siblings().removeClass('open');
			}
		},
		removeGroup : function( button ){
			var groupId = $(button).parents('.nx-group-field').data('id'),
                group   = $(button).parents('.nx-group-field[data-id="'+groupId+'"]');

            group.fadeOut({
                duration: 300,
                complete: function() {
                    $(this).remove();
                }
            });
		},
		cloneGroup : function( button ){
			var groupId = $(button).parents('.nx-group-field').data('id'),
				group   = $(button).parents('.nx-group-field[data-id="'+groupId+'"]'),
				clone   = $( group.clone() ),
				lastGroup   = $( button ).parents('.nx-group-fields-wrapper').find('.nx-group-field:last'),
				parent  = group.parent(),
				nextGroupID = $( lastGroup ).data('id') + 1;

			clone.attr('data-id', nextGroupID);
			clone.insertAfter(group);
			NotificationX_Admin.resetFieldIds( parent.find('.nx-group-field') );
		},
		resetFieldIds : function( groups ){
			var groupID = 1;
				
			groups.each(function() {
				var group       = $(this),
					fieldName   = group.data('field-name'),
					fieldId     = 'nx-' + fieldName,
					groupInfo   = group.find('.nx-group-field-info').data('info'),
					subFields   = groupInfo.group_sub_fields;

				group.data('id', groupID);

				subFields.forEach(function( item ){
					var table_row = group.find('tr.nx-field[id="nx-' + item.field_name + '"]');

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

					group.find('tr.nx-field[id="nx-' + item.field_name + '"]').attr('id', fieldId + '[' + groupID + '][' + item.original_name + ']');
				});
				groupID++;
			});
		},
		initMediaField : function( button ){

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
		},
		removeMedia : function( button ) {
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
		},
		shownPreview : function( type ) {
			if ( type === 'press_bar' ) {
				$('#nx-notification-preview').hide();
			} else {
				$('#nx-notification-preview').removeClass('nx-notification-preview-comments').removeClass('nx-notification-preview-conversions');
				$('#nx-notification-preview').show().addClass('nx-notification-preview-' + type);
			}
		},
		updatePreview: function( fields ){

			fields.map(function(item, i){
				var event = item.event || 'change';

				$( item.id ).each(function(){

					$( this ).on( event, function(){
						var val = $( this ).val(),
							suffix = '',
							selector = '.notificationx-inner';

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
							if( ! $( this ).is(":checked") ) {
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

						/**
						 * For theme changed
						 */
						if( item.field == 'nx_theme' ) {
							var themeField = $( this ).find( 'input' ),
								theme_name = themeField.val(),
								prev_theme = themeField.data('prev_theme'),
								prev_className = 'nx-notification-' + prev_theme,
								class_name = 'nx-notification-' + theme_name;

							$( selector ).removeClass( prev_className );
							$( selector ).addClass( class_name );
							themeField.data( 'prev_theme',  theme_name );
						}
	
						if( typeof item.property != 'undefined' ) {
							$( selector ).css( item.property, val + suffix );
						}
						
						if( 'image_shape' == item.field || 'comment_image_shape' == item.field ) {
							$( selector ).removeClass( 'nx-img-circle nx-img-rounded nx-img-square' );
						}
						if( 'image_position' == item.field || 'comment_image_position' == item.field ) {
							$( selector ).removeClass( 'nx-img-left nx-img-right' );
						}
	
						if( ( item.field == 'image_shape' || 'image_position' == item.field ) || ( item.field == 'comment_image_shape' || 'comment_image_position' == item.field ) ) {
							$( selector ).addClass( 'nx-img-' + val ); 
							/**
							 * This lines of code use for layouting the notification preview
							 */
							if( 'image_position' == item.field || 'comment_image_position' == item.field ) {
								if( val == 'left' ) {
									$( '.notificationx-inner' ).removeClass( 'nx-flex-reverse' );
								} else {
									$( '.notificationx-inner' ).addClass( 'nx-flex-reverse' );
								}
							}
						}
					})

				});
			});
		},
		optinAllowOrNot : function( button ){
			var button = $( button ),
			    args   = button.data('args'),
			    parent = button.parents('.nx-opt-in'),
			    inputs = parent.find('.nx-single-opt input'),
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
					action: 'nx_optin_check',
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
				form = button.parent('#nx-settings-general-form'),
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
		},
		createTitle : function( selector ){
			$('body').on('change', selector, function( e ){
				var type = $(this).val(),
					title = e.currentTarget.selectedOptions[0].innerText,
					options = { year: 'numeric', month: 'short', day: 'numeric' },
					date = ( new Date() ).toLocaleDateString('en-US', options);
				return [ type, title, date ];
			});
		}
	};

	/**
	 * NotificationX Admin Fired
	 * when the document is ready.
	 */
	$(document).ready(function() {
		/**
		 * Current Tab Manage
		 */
		var $tabMenu = $('.nx-metatab-menu, .nx-builder-tab-menu');
		$tabMenu.on( 'click', 'li', function(){
			var $tab = $(this).data( 'tab' ),
				tabID = $(this).data('tabid') - 1;
			$('#nx_builder_current_tab').val( $tab );

			$tabMenu.find('li').each(function(i){
				if( i < tabID ) {
					$(this).addClass('nx-complete');
				} else {
					$(this).removeClass('nx-complete');
				}
			});
			$(this).addClass( 'active' ).siblings().removeClass('active');
			$('#nx-' + $tab).addClass( 'active' ).siblings().removeClass('active');
		});

		NotificationX_Admin.init();
	});
	
	$( window ).load(function(){
		$('body').on('change', '#nx_meta_display_type', function(){
			var type = $(this).val();
			NotificationX_Admin.shownPreview( type );
			if( type == 'conversions' ) {
				$('#nx_meta_conversion_from').trigger('change');
			}
		});

		$('body').on('change', '#nx_meta_display_type.nx-select', function( e ){
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

		var fields = [
			{
				id: [ "#nx_meta_bg_color", "#nx_meta_comment_bg_color" ],
				field: "bg_color",
				property : "background-color",
			},
			{
				id: [ "#nx_meta_text_color", "#nx_meta_comment_text_color" ],
				field: "text_color",
				property : "color",
			},
			{
				id: [ "#nx_meta_border", "#nx_meta_comment_border" ],
				field: "border",
				event : "click",
				selector : ".notificationx-inner",
				hide : [
					{ 'key': '#nx_meta_border_size', 'property' : 'border-width' }, 
					{ 'key': '#nx_meta_border_style', 'property' : 'border-style' }, 
					{ 'key': '#nx_meta_border_color', 'property' : 'border-color' },
					{ 'key': '#nx_meta_comment_border_size', 'property' : 'border-width' }, 
					{ 'key': '#nx_meta_comment_border_style', 'property' : 'border-style' }, 
					{ 'key': '#nx_meta_comment_border_color', 'property' : 'border-color' }, 
				],
			},
			{
				id: [ "#nx_meta_border_size", "#nx_meta_comment_border_size" ],
				field: "border_size",
				event : "keyup",
				property : "border-width",
				selector : ".notificationx-inner",
				unit : "px",
			},
			{
				id: [ "#nx_meta_border_style", "#nx_meta_comment_border_style" ],
				field: "border_style",
				property : "border-style",
				selector : ".notificationx-inner",
			},
			{
				id: [ "#nx_meta_border_color", "#nx_meta_comment_border_color" ],
				field: "border_color",
				property : "border-color",
				selector : ".notificationx-inner",
			},
			{
				id: [ "#nx_meta_image_shape", "#nx_meta_comment_image_shape" ],
				field: "image_shape",
				selector: ".nx-preview-image > img",
			},
			{
				id: [ "#nx_meta_image_position", "#nx_meta_comment_image_position" ],
				field: "image_position",
				selector: ".notificationx-inner",
			},
			{
				id: [ "#nx_meta_first_font_size", "#nx_meta_comment_first_font_size" ],
				field: "first_font_size",
				selector: ".nx-first-row",
				property : "font-size",
				event : "keyup",
				unit : "px",
			},
			{
				id: [ "#nx_meta_second_font_size", "#nx_meta_comment_second_font_size" ],
				field: "second_font_size",
				selector: ".nx-second-row",
				property : "font-size",
				event : "keyup",
				unit : "px",
			},
			{
				id: [ "#nx_meta_third_font_size", "#nx_meta_comment_third_font_size" ],
				field: "third_font_size",
				selector: ".nx-third-row",
				property : "font-size",
				event : "keyup",
				unit : "px",
			},
			{
				id: [ "#nx-meta-theme", "#nx-meta-comment_theme" ],
				field: "nx_theme",
				event : "change",
				selector: ".notificationx-inner",
			},
		];

		NotificationX_Admin.updatePreview( fields );


		/**
		 * TODO: Advance Edit Preview 
		 * have to done with a better way, 
		 * FIXME: Multiple id not working, precedence problem maybe. 
		 */
		var defaultsAdvancedDesign = [
			{
				id: [ '#nx_meta_image_shape', '#nx_meta_bg_color', '#nx_meta_text_color' ],
				event : "change",
				type: 'conversions'
			},
			{
				id: [ '#nx_meta_comment_image_shape', '#nx_meta_comment_bg_color', '#nx_meta_comment_text_color' ],
				event : "change",
				type: 'comments'
			},
			{
				id: [ '#nx_meta_comment_border_color', '#nx_meta_comment_border_style' ],
				event : "change",
				type: 'comments',
				dependency: '#nx_meta_comment_border'
			},
			{
				id: [ '#nx_meta_comment_border_size' ],
				event : "keyup",
				type: 'comments',
				dependency: '#nx_meta_comment_border'
			}
		];

		$('.nx-meta-adv_checkbox').each(function(){
			var buttonAdv = $(this);
			
			buttonAdv.on('click', 'label', function( e ){
				var checked = false, i, j;
				var listItems = defaultsAdvancedDesign.length;
				if( $(buttonAdv).find('input').is(":checked") ) {
					$('.notificationx-inner').removeAttr('style');
					return;
				}

				for( i = 0; i < listItems; i++ ) {
					let itemD = defaultsAdvancedDesign[ i ];
					let itemLen = itemD.id.length;
					for( j = 0; j < itemLen; j++ ) {
						var cItem = $( itemD.id[j] );
						var type = $( '#nx_meta_display_type' ).val();
						if( type === itemD.type ) { 
							if( typeof itemD.dependency != 'undefined' ) {
								checked = $( itemD.dependency + ':checked' ).length > 0 ? true : false;
								if( checked ) {
									$( cItem ).trigger( itemD.event );
									return;
								} else {
									continue;
								}
							}
							$( cItem ).trigger( itemD.event );
						}
					}
				}
			});
		});

	});
})( jQuery );
