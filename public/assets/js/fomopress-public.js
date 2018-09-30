(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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

	var FomoPressPlugin = {


		pressBarActive: 0,

		init : function(){

			// FomoPressPlugin.initPressBar();			
			FomoPressPlugin.initNotifications();			
			FomoPressPlugin.bindEvents();

		},

		initNotifications : function(){
			if ( 'undefined' === typeof fomopress ) {
                return;
			}

			if ( fomopress.conversions.length > 0 ) {
                FomoPressPlugin.processNotifications( fomopress.conversions );
			}
			
			if ( fomopress.comments.length > 0 ) {
                FomoPressPlugin.processNotifications( fomopress.comments );
			}
		},

		bindEvents : function(){
			$('body').delegate( '.fomopress-press-bar .fomopress-close', 'click', function( e ) {
				FomoPressPlugin.pressBarActive = 0;
				FomoPressPlugin.hidePressBar( e.target.offsetParent.id );
            } );
		},

		processNotifications : function( ids ){
			var node = $('<div class="fomopress-conversions"></div>');
			var html = '';

			$.ajax({
				type: 'post',
                url: fomopress.ajaxurl,
                cache: false,
                data: {
                    action: 'fomopress_get_conversions',
					nonce: fomopress.nonce,
					ids: ids
				},
				success : function( response ){
					if( response ) {
						var data = JSON.parse( response );
							html = node.html(data.content);
						FomoPressPlugin.render_notifications(data.config, html);
					}
				}
			});
		},

        initPressBar: function() {
            var elements = $('.fomopress-press-bar');

            if ( elements.length === 0 ) {
                return;
			}
			
			
            elements.each(function() {
				var press_bar      = $(this),
				    id             = press_bar.data('press_id'),
				    duration       = press_bar.data('hide_after'),
				    auto_hide      = press_bar.data('auto_hide'),
				    countdown_time = press_bar.find('.fomopress-countdown').data('press_time'),
				    countdown      = [];

                if ( 'undefined' !== typeof countdown_time ) {

                   
                }

                FomoPressPlugin.showPressBar( press_bar, id );

                if ( ( '' !== duration || undefined !== duration ) && parseInt( auto_hide ) ) {
                    setTimeout(function() {
                        FomoPressPlugin.hidePressBar( 'fomopress-bar-' + id );
                    }, parseInt(duration) * 1000);
                }
            });
        },

		showPressBar : function( press_bar, id ){
			if ( '' === press_bar ) {
                press_bar = $('.fomopress-press-bar ' + '.fomopress-bar-' + id);
            }
            var initial_delay    = parseInt( press_bar.data('initial_delay') ),
                press_bar_height = press_bar.find('.fomopress-bar-inner').outerHeight(),
                admin_bar_height = ( $('#wpadminbar').length > 0 ) ? $('#wpadminbar').outerHeight() : 0;

            if ( '' === initial_delay || isNaN( initial_delay ) ) {
                initial_delay = 0;
			}

            setTimeout(function() {
                $('html').addClass('fomopress-bar-active');
                if ( press_bar.hasClass('fomopress-position-top') ) {
					$('html').animate({ 'padding-top': press_bar_height + 'px' }, 300);
					press_bar.animate({ 'top' : admin_bar_height + 'px' }, 300);
				}

				press_bar.addClass('fomopress-press-bar-visible');

                FomoPressPlugin.pressBarActive = 1;
            }, initial_delay * 1000);	
		},

        hidePressBar: function( id ) {
			
			var press_bar        = $('.fomopress-press-bar#' + id ),
			    press_bar_height = press_bar.find('.fomopress-bar-inner').outerHeight() ,
			    admin_bar_height = ( $('#wpadminbar').length > 0 ) ? $('#wpadminbar').outerHeight() : 0;

            $('html').removeClass('fomopress-bar-active');
			$('html').css( 'padding-top', '0px' );
			
			if ( press_bar.hasClass('fomopress-position-top') ) {
				press_bar.animate( { 'top' : 0 }, 300 );
			}

			// press_bar.animate( { 'visibility' : 'hidden' }, 300 );
			press_bar.removeClass('fomopress-press-bar-visible');

            FomoPressPlugin.pressBarActive = 0;
		},

		render_notifications : function( config, html ){

			var count       = 0,
				elements    = html.find('.fomopress-notification-' + config.id),
				delayEach   = config.delay_between,
				last        = FomoPressPlugin.lastNotification(config.id, false);

			if ( last >= 0 ) {
				count = last + 1;
			}

			
			if ( config.loop === 0 && elements.length === 1 ) {
				count = 0;
			}

			setTimeout(function() {

				// Show the first notification.
				FomoPressPlugin.showNotification( $(elements[count]), config, count );

				setTimeout(function() {

					// Hide the first notification when display duration is expired.
					FomoPressPlugin.hideNotification( $(elements[count]) );
					
					// Increase the sequence.
					count++;

					// Now lets render next notifications.
					var next = setInterval(function() {
						// Show next notification
						FomoPressPlugin.showNotification( $(elements[count]), config, count );

						setTimeout(function() {
							// Again hide this notification once display duration is expired.
							FomoPressPlugin.hideNotification( $(elements[count]) );

							// reset the count, so that it can either start from begining or stop.
							if ( count >= elements.length - 1 ) {
								count = 0;
								// If notifications are not in loop, clear the interval.
								if ( config.loop === 0 ) {
									clearInterval(next);
								}
							} else {
								count++;
							}

						}, config.display_for);

					}, delayEach + config.display_for);

				}, config.display_for);

			}, config.delay_before);

		},
		showNotification: function( element, config, count ){

			if ( 'undefined' === typeof element || 0 === element.length ) {
				return;
			}

			$('body').append( element );
			element.animate({ 'bottom': '20px', 'opacity': '1' }, 500);

			FomoPressPlugin.saveNotification( config.id, count );
		},
		hideNotification: function( element ){
			element.animate({ 'bottom': '-250px', 'opacity': '0' }, 1000, function() {
                FomoPressPlugin.removeNotification( element );
            });
		},
		removeNotification: function( element ){
			if ( element.length > 0 ) {
                element.remove();
            }
		},
		saveNotification: function(id, sequence){
			if ( window.localStorage ) {
                var lastConversion = FomoPressPlugin.lastNotification(id, true);
                if ( 'object' === typeof lastConversion ) {
                    lastConversion[id] = sequence;
                } else {
                    lastConversion = new Object;
                    lastConversion[id] = sequence;
				}
                window.localStorage.setItem('fomopress_notifications', JSON.stringify(lastConversion));
            } else {
                console.log('Browser does not support localStorage!');
            }
		},
		lastNotification: function(id, obj){
			var last = -1;
            if ( window.localStorage ) {
				var notificationSequenece = window.localStorage.getItem('fomopress_notifications');
                if ( null !== notificationSequenece ) {
					notificationSequenece = JSON.parse(notificationSequenece);
                    if ( undefined !== notificationSequenece[id] ) {
                        if ( obj ) {
                            return notificationSequenece;
                        }
                        last = notificationSequenece[id];
                    }
                }
            } else {
                console.log('Browser does not support localStorage!');
            }
            return last;
		}
	};


	jQuery( window ).load(function(){

		FomoPressPlugin.init();

	});



})( jQuery );
