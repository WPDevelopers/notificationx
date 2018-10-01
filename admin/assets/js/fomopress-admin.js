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
			FomoPressAdmin.bindEvents();
			FomoPressAdmin.initFields();
		},

		initFields: function(){
			$('.fomopress-metabox-wrapper .fomopress-meta-field').trigger('change');
			FomoPressAdmin.initColorField();
			FomoPressAdmin.initGroupField();
		},

		bindEvents: function(){
			$('body').delegate( '.fomopress-metabox-wrapper .fomopress-meta-field', 'change', function() {
				FomoPressAdmin.fieldChange( this );
            } );
			$('body').delegate( '.fomopress-group-field .fomopress-group-field-title', 'click', function() {
				FomoPressAdmin.groupToggle( this );
			} );
			$('body').delegate( '.fomopress-group-field .fomopress-group-remove', 'click', function() {
                FomoPressAdmin.removeGroup(this);
			} );
			$('body').delegate( '.fomopress-group-field .fomopress-group-clone', 'click', function() {
                FomoPressAdmin.cloneGroup(this);
            } );
		},

		fieldChange: function( input ){
			var field   = $(input),
                id  = field.attr('id'),
                toggle  = field.data('toggle'),
                hide    = field.data('hide'),
                val     = field.val(),
				i       = 0;
			
			if ( 'checkbox' === field.attr('type') && ! field.is(':checked') ) {
				val = '0';
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
                $( '.fomopress-colorpicker' ).each(function() {
                    var color = $(this).val();
                    $(this).wpColorPicker({
                        change: function(event, ui) {
                            var element = event.target;
                            var color = ui.color.toString();
                            $(element).parents('.wp-picker-container').find('input.fomopress-colorpicker').val(color).trigger('change');
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
					var groupContent = $(this).find('.fomopress-group-field:not(.open)').next();
					if ( groupContent.is(':visible') ) {
						groupContent.slideToggle(0);
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
						$(this).find('.fomopress-group-field-title').next().slideUp(0);
					});

					// Reset all data of clone object.
					clone.attr('data-id', nextGroupId);
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
		groupToggle : function( input ){
			var input = $(input);
            input.next().slideToggle({
                duration: 0,
                complete: function() {
                    if ( $(this).is(':visible') ) {
                        input.addClass('open');
                    } else {
                        input.removeClass('open');
                    }
                }
            });
		},
		removeGroup : function( button ){
			var groupId = $(button).parents('.fomopress-group-field').data('id'),
                group   = $(button).parents('.fomopress-group-field[data-id="'+groupId+'"]'),
				parent  = group.parent();

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
				clone   = group.clone(),
				lastGroup   = $( button ).parents('.fomopress-group-fields-wrapper').find('.fomopress-group-field:last'),
				parent  = group.parent(),
				nextGroupID = $( lastGroup ).data('id') + 1;

			clone.attr('data-id', nextGroupID);
			clone.insertAfter(group);
			FomoPressAdmin.resetFieldIds( parent.find('.fomopress-group-field') );
			group.find('.fomopress-group-field-title').trigger('click');
		},

		resetFieldIds : function( groups ){
			var groupID = 1;
				// fieldName = $( groups ).parents('.fomopress-group-field-wrapper').data('name');

			// 	console.log( fieldName );

			// return;
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
		var $tabMenu = $('.fomopress-meta-tab-menu');
		$tabMenu.on( 'click', 'li', function(){
			var $tab = $(this).data( 'tab' );
			$('#fomopress_current_tab').val( $tab );
			$(this).addClass( 'active' ).siblings().removeClass('active');
			$('#fomopress-' + $tab).addClass( 'active' ).siblings().removeClass('active');
		});

		FomoPressAdmin.init();
	});
	
	$( window ).load(function(){
		$('body').on('change', '#fomopress_display_type', function(){
			var type = $(this).val();
			if( type == 'conversions' ) {
				$('#fomopress_conversion_from').trigger('change');
			}
		});
		$('#fomopress_display_type').trigger('change');
	});

})( jQuery );
