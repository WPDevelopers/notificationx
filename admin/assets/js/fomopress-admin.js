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
		},

		bindEvents: function(){
			$('body').delegate( '.fomopress-metabox-wrapper .fomopress-meta-field', 'change', function() {
				FomoPressAdmin.fieldChange( this );
            } );
		},

		fieldChange: function( input ){
			var field   = $(input),
                id      = field.attr('id'),
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
					FomoPressAdmin.fieldToggle(toggle[i].fields, 'hide', '#fomopress-');
					FomoPressAdmin.fieldToggle(toggle[i].sections, 'hide', '#fomopress-meta-section-');
				}

				if(typeof toggle[val] !== 'undefined') {
					FomoPressAdmin.fieldToggle(toggle[val].fields, 'show', '#fomopress-');
					FomoPressAdmin.fieldToggle(toggle[val].sections, 'show', '#fomopress-meta-section-');
				}
			}

			// HIDE sections or fields.
    		if ( typeof hide !== 'undefined' ) {

                if ( typeof hide !== 'object' ) {
    			    hide = JSON.parse(hide);
				}

    			if(typeof hide[val] !== 'undefined') {
    				FomoPressAdmin.fieldToggle(hide[val].fields, 'hide', '#fomopress-');
    				FomoPressAdmin.fieldToggle(hide[val].sections, 'hide', '#fomopress-meta-section-');
    			}
    		}
		},

		fieldToggle: function( array, func, prefix, suffix ){
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

		}

	};

	/**
	 * FomoPress Admin Fired
	 * when the document is ready.
	 */
	$(document).ready(function() {
		FomoPressAdmin.init();
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
    });

})( jQuery );
