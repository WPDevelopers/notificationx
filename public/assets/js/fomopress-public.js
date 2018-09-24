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

			FomoPressPlugin.initPressBar();

			if ( 'undefined' === typeof fomopress ) {
                return;
			}

			if ( fomopress.conversions.length > 0 ) {
                FomoPressPlugin.processNotifications( fomopress.conversions );
			}
			
			if ( fomopress.comments.length > 0 ) {
                FomoPressPlugin.processNotifications( fomopress.comments );
			}
			
			FomoPressPlugin.bindEvents();

		},

		bindEvents : function(){
			$('body').delegate( '.fomopress-press-bar .fomopress-close', 'click', function() {
                FomoPressPlugin.pressBarActive = 0;
                FomoPressPlugin.hidePressBar();
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
					var data = JSON.parse( response );
						html = node.html(data.content);
					FomoPressPlugin.render_notifications(data.config, html);
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

                    countdown['days']       = countdown_time.split(',')[0];
                    countdown['hours']      = countdown_time.split(',')[1];
                    countdown['minutes']    = countdown_time.split(',')[2];
                    countdown['seconds']    = countdown_time.split(',')[3];

                    // Get current date and time.
                    var date    = new Date(),
                        year    = date.getYear() + 1900,
                        month   = date.getMonth() + 1,
                        days    = ( parseInt( date.getDate() ) + parseInt( countdown['days'] ) ),
                        hours   = ( parseInt( date.getHours() ) + parseInt( countdown['hours'] ) ),
                        minutes = ( parseInt( date.getMinutes() ) + parseInt( countdown['minutes'] ) ),
                        seconds = ( parseInt( date.getSeconds() ) + parseInt( countdown['seconds'] ) ),
                        new_date = new Date( year, parseInt( month, 10 ) - 1, days, hours, minutes, seconds ),
                        countdown_cookie = '';

                    // Conver countdown time to miliseconds and add it to current date.
                    date.setTime(date.getTime() +  ( parseInt( countdown['days'] ) * 24 * 60 * 60 * 1000)
                                                +  ( parseInt( countdown['hours'] )  * 60 * 60 * 1000)
                                                +  ( parseInt( countdown['minutes'] ) * 60 * 1000)
                                                +  ( parseInt( countdown['seconds'] ) * 1000) );

                    // Remove countdown value from cookie if countdown value has changed in wp-admin.
                    if( $.cookie('ibx_fomo_countdown_old') !== countdown_time ){
                        $.cookie( 'ibx_fomo_countdown_old', countdown_time, { expires: date } );
                        $.removeCookie('ibx_fomo_countdown');
                    }
                    // Get countdown value from cookie if exist.
                    if ( $.cookie('ibx_fomo_countdown') ){
                        countdown_cookie = $.cookie( 'ibx_fomo_countdown' );
                    }
                    else {
                        // Set countdown value in cookie if doesn't exist.
                        $.cookie( 'ibx_fomo_countdown', new_date.getTime(), { expires: date } );
                        $.cookie( 'ibx_fomo_countdown_old', countdown_time, { expires: date } );
                        countdown_cookie = $.cookie( 'ibx_fomo_countdown' );
                    }

                    // Start countdown.
                    var countdown_interval = setInterval(function() {
                        var now         = new Date().getTime(),
                            difference  = countdown_cookie - now;

                        // Calculate time from difference.
                        var days        = Math.floor( difference / ( 1000 * 60 * 60 * 24 ) ),
                            hours       = Math.floor( ( difference % ( 1000 * 60 * 60 * 24 ) ) / ( 1000 * 60 * 60 ) ),
                            minutes     = Math.floor( ( difference % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) ),
                            seconds     = Math.floor( ( difference % ( 1000 * 60 )) / 1000 );

                        // Output the result in an element with id="ibx-fomo-countdown-time"
                        press_bar.find('.ibx-fomo-days').html(days);
                        press_bar.find('.ibx-fomo-hours').html(hours);
                        press_bar.find('.ibx-fomo-minutes').html(minutes);
                        press_bar.find('.ibx-fomo-seconds').html(seconds);
                        // If the count down is over, write some text
                        if ( difference < 0 ) {
                            clearInterval( countdown_interval );
                            // press_bar.find('#ibx-fomo-countdown-time').addClass('ibx-fomo-expired');
                        }
                    }, 1000);
                }

                FomoPressPlugin.showPressBar( press_bar, id );

                if ( ( '' !== duration || undefined !== duration ) && parseInt( auto_hide ) ) {
                    setTimeout(function() {
                        FomoPressPlugin.hidePressBar( press_bar, id );
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
					press_bar.animate( { 'top' : admin_bar_height + 'px' }, 300 );
                }

                FomoPressPlugin.pressBarActive = 1;
            }, initial_delay * 1000);	
		},

        hidePressBar: function( press_bar, id ) {
			if ( '' === press_bar ) {
                press_bar = $('.fomopress-press-bar ' + '.fomopress-bar-' + id);
			}
			
            var press_bar_height = press_bar.find('.fomopress-bar-inner').outerHeight(),
                admin_bar_height = ( $('#wpadminbar').length > 0 ) ? $('#wpadminbar').outerHeight() : 0;

            $('html').removeClass('fomopress-bar-active');
			$('html').css( 'padding-top', '0px' );
			press_bar.css( 'display', 'none' );
			
			// if ( press_bar.hasClass('fomopress-position-top') ) {
			// }

            FomoPressPlugin.pressBarActive = 0;
		},

		render_notifications : function( config, html ){

			var count       = 0,
				elements    = html.find('.fomopress-notification-' + config.id),
				delayCalc   = (config.initial_delay + config.hide_after + config.delay_between) / 1000,
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

						}, config.hide_after);

					}, delayEach + config.hide_after);

				}, config.hide_after);

			}, config.initial_delay);

		},
		showNotification: function( element, config, count ){

			if ( 'undefined' === typeof element || 0 === element.length ) {
				return;
			}

			// if ( 'undefined' !== typeof $.cookie( 'ibx_wpfomo_notification_hidden' ) ) {
			// 	return;
			// }

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
