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

	var NotificationXPublic = {


		pressBarActive: 0,

		init : function(){

			NotificationXPublic.initPressBar();			
			NotificationXPublic.initNotifications();			
			NotificationXPublic.bindEvents();

		},

		initNotifications : function(){
			if ( 'undefined' === typeof notificationx ) {
                return;
			}

			window.localStorage.removeItem('nx_notifications');

			if ( notificationx.conversions.length > 0 ) {
				NotificationXPublic.processNotifications( notificationx.conversions[0] );
			}
			
			if ( notificationx.comments.length > 0 ) {
				NotificationXPublic.processNotifications( notificationx.comments[0] );
			}

			if( notificationx.pro_ext.length > 0 ) {
				notificationx.pro_ext.map(function( item, i ){
					NotificationXPublic.processNotifications( item[0] );
				});
			}
		},

		bindEvents : function(){
			$('body').delegate( '.nx-bar .nx-close', 'click', function( e ) {
				NotificationXPublic.pressBarActive = 0;
				NotificationXPublic.hidePressBar( e.target.offsetParent.id );
            } );
			$('body').delegate( '.notificationx-close', 'click', function( e ) {
				NotificationXPublic.hideNotification( $( e.target.offsetParent ) );
            } );
		},

		processNotifications : function( ids ){
			var node = $('<div class="notificationx-conversions"></div>');
			var html = '';

			$.ajax({
				type: 'post',
                url: notificationx.ajaxurl,
                cache: false,
                data: {
                    action: 'nx_get_conversions',
					nonce: notificationx.nonce,
					ids: ids
				},
				success : function( response ){
					if( response ) {
						var data = JSON.parse( response );
							html = node.html(data.content);
						NotificationXPublic.render_notifications(data.config, html);
					}
				}
			});
		},

        initPressBar: function() {
            var elements = $('.nx-bar');

            if ( elements.length === 0 ) {
                return;
			}
			
			
            elements.each(function() {
				var press_bar      = $(this),
				    id             = press_bar.data('press_id'),
				    duration       = press_bar.data('hide_after'),
				    auto_hide      = press_bar.data('auto_hide'),
					countdown_time = press_bar.find('.nx-countdown').data('countdown');
					
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
                    if( Cookies.get( 'nx_bar_countdown_old' ) !== countdown_string ){
						document.cookie = 'nx_bar_countdown_old' + "=" + countdown_string + ";" + date + ";path=/";
						Cookies.clear('nx_bar_countdown');
                    }
                    // Get countdown value from cookie if exist.
                    if ( Cookies.get( 'nx_bar_countdown' ) ){
                        countdown_cookie = Cookies.get( 'nx_bar_countdown' );
                    } else {
                        // Set countdown value in cookie if doesn't exist.
						document.cookie = 'nx_bar_countdown' + "=" + new_date.getTime() + ";" + date + ";path=/";
						document.cookie = 'nx_bar_countdown_old' + "=" + countdown_string + ";" + date + ";path=/";
                        countdown_cookie = Cookies.get( 'nx_bar_countdown' );
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
                        // Output the result in an element
                        press_bar.find('.nx-days').html(days);
                        press_bar.find('.nx-hours').html(hours);
                        press_bar.find('.nx-minutes').html(minutes);
                        press_bar.find('.nx-seconds').html(seconds);
                        // If the count down is over, write some text
                        if ( difference < 0 ) {
                            clearInterval( countdown_interval );
                            press_bar.find('.nx-countdown').addClass('nx-expired');
                        }
                    }, 1000);
                }

                NotificationXPublic.showPressBar( press_bar, id );

                if ( ( '' !== duration || undefined !== duration ) && parseInt( auto_hide ) ) {
                    setTimeout(function() {
                        NotificationXPublic.hidePressBar( 'nx-bar-' + id );
                    }, parseInt(duration) * 1000);
                }
            });
        },

		showPressBar : function( press_bar, id ){
			if ( '' === press_bar ) {
                press_bar = $('.nx-bar ' + '.nx-bar-' + id);
            }
            var initial_delay    = parseInt( press_bar.data('initial_delay') ),
                press_bar_height = press_bar.find('.nx-bar-inner').outerHeight(),
                admin_bar_height = ( $('#wpadminbar').length > 0 ) ? $('#wpadminbar').outerHeight() : 0;

            if ( '' === initial_delay || isNaN( initial_delay ) ) {
                initial_delay = 0;
			}

            setTimeout(function() {
                $('html').addClass('nx-bar-active');
                if ( press_bar.hasClass('nx-position-top') ) {
					$('html').animate({ 'padding-top': press_bar_height + 'px' }, 300);
					press_bar.animate({ 'top' : admin_bar_height + 'px' }, 300);
				}

				press_bar.addClass('nx-bar-visible');

                NotificationXPublic.pressBarActive = 1;
            }, initial_delay * 1000);	
		},

        hidePressBar: function( id ) {
			
			var press_bar        = $('.nx-bar#' + id ),
			    press_bar_height = press_bar.find('.nx-bar-inner').outerHeight() ,
			    admin_bar_height = ( $('#wpadminbar').length > 0 ) ? $('#wpadminbar').outerHeight() : 0;

            $('html').removeClass('nx-bar-active');
			$('html').css( 'padding-top', '0px' );
			
			if ( press_bar.hasClass('nx-position-top') ) {
				press_bar.animate( { 'top' : 0 }, 300 );
			}

			// press_bar.animate( { 'visibility' : 'hidden' }, 300 );
			press_bar.removeClass('nx-bar-visible');

            NotificationXPublic.pressBarActive = 0;
		},

		render_notifications : function( config, html ){

			var count       = 0,
				elements    = html.find('.notificationx-' + config.id),
				delayEach   = config.delay_between,
				last        = NotificationXPublic.lastNotification(config.id, false);

			if ( last >= 0 ) {
				count = last + 1;
			}

			
			if ( config.loop === 0 && elements.length === 1 ) {
				count = 0;
			}

			setTimeout(function() {

				// Show the first notification.
				NotificationXPublic.showNotification( $(elements[count]), config, count );

				setTimeout(function() {

					// Hide the first notification when display duration is expired.
					NotificationXPublic.hideNotification( $(elements[count]) );
					
					// Increase the sequence.
					count++;

					// Now lets render next notifications.
					var next = setInterval(function() {
						// Show next notification
						NotificationXPublic.showNotification( $(elements[count]), config, count );

						setTimeout(function() {
							// Again hide this notification once display duration is expired.
							NotificationXPublic.hideNotification( $(elements[count]) );

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

			NotificationXPublic.saveNotification( config.id, count );
		},
		hideNotification: function( element ){
			element.animate({ 'bottom': '-250px', 'opacity': '0' }, 1000, function() {
                NotificationXPublic.removeNotification( element );
            });
		},
		removeNotification: function( element ){
			if ( element.length > 0 ) {
                element.remove();
            }
		},
		saveNotification: function(id, sequence){
			if ( window.localStorage ) {
                var lastConversion = NotificationXPublic.lastNotification(id, true);
                if ( 'object' === typeof lastConversion ) {
                    lastConversion[id] = sequence;
                } else {
                    lastConversion = new Object;
                    lastConversion[id] = sequence;
				}
                window.localStorage.setItem('nx_notifications', JSON.stringify(lastConversion));
            } else {
                console.log('Browser does not support localStorage!');
            }
		},
		lastNotification: function(id, obj){
			var last = -1;
            if ( window.localStorage ) {
				var notificationSequenece = window.localStorage.getItem('nx_notifications');
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

		NotificationXPublic.init();

	});



})( jQuery );
