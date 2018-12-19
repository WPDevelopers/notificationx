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
			FomoPressPlugin.initNotifications();			
			FomoPressPlugin.bindEvents();

		},

		initNotifications : function(){
			if ( 'undefined' === typeof fomopress ) {
                return;
			}

			window.localStorage.removeItem('fomopress_notifications');

			if ( fomopress.conversions.length > 0 ) {
				FomoPressPlugin.processNotifications( fomopress.conversions[0] );
			}
			
			if ( fomopress.comments.length > 0 ) {
				FomoPressPlugin.processNotifications( fomopress.comments[0] );
			}

			if( fomopress.pro_ext.length > 0 ) {
				fomopress.pro_ext.map(function( item, i ){
					FomoPressPlugin.processNotifications( item[0] );
				});
			}
		},

		bindEvents : function(){
			$('body').delegate( '.fomopress-press-bar .fomopress-close', 'click', function( e ) {
				FomoPressPlugin.pressBarActive = 0;
				FomoPressPlugin.hidePressBar( e.target.offsetParent.id );
            } );
			$('body').delegate( '.fomopress-notification-close', 'click', function( e ) {
				FomoPressPlugin.hideNotification( $( e.target.offsetParent ) );
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
					countdown_time = press_bar.find('.fomopress-countdown').data('countdown');
					
				if ( 'undefined' !== typeof countdown_time ) {
					// Get current date and time.
                    var date    = new Date(),
                        year    = date.getYear() + 1900,
                        month   = date.getMonth() + 1,
                        days    = ( parseInt( date.getDate() ) + parseInt( countdown_time.days ) ),
                        hours   = ( parseInt( date.getHours() ) + parseInt( countdown_time.hours ) ),
                        minutes = ( parseInt( date.getMinutes() ) + parseInt( countdown_time.minutes ) ),
                        seconds = ( parseInt( date.getSeconds() ) + parseInt( countdown_time.seconds ) ),
                        new_date = new Date( year, parseInt( month, 10 ) - 1, days, hours, minutes, seconds ),
						countdown_cookie = '',
						countdown_string = countdown_time.days + ', ' + countdown_time.hours + ', ' + countdown_time.minutes + ', ' + countdown_time.seconds;

                    // Conver countdown time to miliseconds and add it to current date.
                    date.setTime(date.getTime() +  ( parseInt( countdown_time.days ) * 24 * 60 * 60 * 1000)
                                                +  ( parseInt( countdown_time.hours )  * 60 * 60 * 1000)
                                                +  ( parseInt( countdown_time.minutes ) * 60 * 1000)
												+  ( parseInt( countdown_time.seconds ) * 1000) );

					// Remove countdown value from cookie if countdown value has changed in wp-admin.
                    if( Cookies.get( 'fomopress_bar_countdown_old' ) !== countdown_string ){
						document.cookie = 'fomopress_bar_countdown_old' + "=" + countdown_string + ";" + date + ";path=/";
						Cookies.clear('fomopress_bar_countdown');
                    }
                    // Get countdown value from cookie if exist.
                    if ( Cookies.get( 'fomopress_bar_countdown' ) ){
                        countdown_cookie = Cookies.get( 'fomopress_bar_countdown' );
                    } else {
                        // Set countdown value in cookie if doesn't exist.
						document.cookie = 'fomopress_bar_countdown' + "=" + new_date.getTime() + ";" + date + ";path=/";
						document.cookie = 'fomopress_bar_countdown_old' + "=" + countdown_string + ";" + date + ";path=/";
                        countdown_cookie = Cookies.get( 'fomopress_bar_countdown' );
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
                        press_bar.find('.fomopress-days').html(days);
                        press_bar.find('.fomopress-hours').html(hours);
                        press_bar.find('.fomopress-minutes').html(minutes);
                        press_bar.find('.fomopress-seconds').html(seconds);
                        // If the count down is over, write some text
                        if ( difference < 0 ) {
                            clearInterval( countdown_interval );
                            press_bar.find('.fomopress-countdown').addClass('fomopress-expired');
                        }
                    }, 1000);
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
			element.animate({ 'bottom': '30px', 'opacity': '1' }, 500);

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
