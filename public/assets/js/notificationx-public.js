(function ($) {
	'use strict';

	/**
	 * NotificationX Admin JS
	 */

	$.notificationx = $.notificationx || {};

	$.notificationx.active_pressbar = 0;

	$(document).ready(function () {
		$.notificationx.init();
	});

	$.notificationx.init = function () {
		$.notificationx.windowWidth = $(window).outerWidth();
		$.notificationx.pressbar();
		$.notificationx.conversions();
		$.notificationx.events();
	};

	$.notificationx.pressbar = function () {
		var bars = $('.nx-bar');
		if (bars.length > 0) {
			bars.each(function (i, bar) {
				var id = bar.dataset.press_id,
					duration = bar.dataset.hide_after,
					auto_hide = bar.dataset.auto_hide,
					close_forever = bar.dataset.close_forever,
					start_date = new Date(bar.dataset.start_date),
					end_date = new Date(bar.dataset.end_date),
					start_timestamp = start_date.getTime(),
					end_timestamp = end_date.getTime(),
					current_date = new Date(),
					current_timestamp = current_date.getTime(),
					barHeight = $(bar).outerHeight(),
					initialDelay = bar.dataset.initial_delay * 1000,
					position = bar.dataset.position;

				/* add padding in body after initial delay */
				setTimeout(function () {
					$('body').addClass('has-nx-bar').css('padding-' + position, barHeight);
				}, initialDelay);
				/* remove padding in body after if auto hide is enable */
				if(parseInt(auto_hide)) {
					setTimeout(function () {
						$('body').css('padding-' + position, 0).removeClass('has-nx-bar');
					}, parseInt(duration) * 1000);
				}

				if (current_timestamp > start_timestamp && current_timestamp < end_timestamp) {
					var bar_interval = setInterval(function () {
						var current_timestamp = Date.now(),
							difference = end_timestamp - current_timestamp,
							days = Math.floor(difference / (1000 * 60 * 60 * 24)),
							hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
							minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60)),
							seconds = Math.floor((difference % (1000 * 60)) / 1000);

						bar.querySelector('.nx-days').innerHTML = days;
						bar.querySelector('.nx-hours').innerHTML = hours;
						bar.querySelector('.nx-minutes').innerHTML = minutes;
						bar.querySelector('.nx-seconds').innerHTML = seconds;
						if (difference < 0) {
							clearInterval(bar_interval);
							bar.querySelector('.nx-countdown').classList.add('nx-expired');
							var endText = bar.querySelector('.nx-countdown-text');
							if( endText != null ) {
								endText.classList.add('nx-expired');
							}
						}
					}, 1000);
				} else {
					var countdown = bar.querySelector('.nx-countdown');
					if( countdown != null ) {
						countdown.classList.add('nx-expired');
					}
					var endText = bar.querySelector('.nx-countdown-text');
					if( endText != null ) {
						endText.classList.add('nx-expired');
					}
				}

				$.notificationx.showBar(bar, id);

				if (('' !== duration || undefined !== duration) && parseInt(auto_hide)) {
					setTimeout(function () {
						$.notificationx.hideBar('nx-bar-' + id, close_forever);
					}, parseInt(duration) * 1000);
				}
			});
		}
	};

	$.notificationx.conversions = function () {
		if ('undefined' === typeof notificationx) {
			return;
		}

		window.localStorage.removeItem('nx_notifications');

		if (notificationx.conversions.length > 0) {

			if (notificationx.conversions.length > 1) {
				notificationx.conversions.map(function (id) {
					$.notificationx.process(id);
				});
			} else {
				$.notificationx.process(notificationx.conversions[0]);
			}

		}

		if (notificationx.reviews.length > 0) {

			if (notificationx.reviews.length > 1) {
				notificationx.reviews.map(function (id) {
					$.notificationx.process(id);
				});
			} else {
				$.notificationx.process(notificationx.reviews[0]);
			}

		}

		if (notificationx.stats.length > 0) {

			if (notificationx.stats.length > 1) {
				notificationx.stats.map(function (id) {
					$.notificationx.process(id);
				});
			} else {
				$.notificationx.process(notificationx.stats[0]);
			}

		}

		if (notificationx.comments.length > 0) {
			$.notificationx.process(notificationx.comments[0]);
		}

		if (notificationx.pro_ext.length > 0) {
			notificationx.pro_ext.map(function (item, i) {
				$.notificationx.process(item);
			});
		}
	};

	$.notificationx.events = function () {
		var barClose = $('.nx-bar .nx-close');
		if (barClose !== null) {
			barClose.on('click', function (event) {
				var position = $('#' + event.currentTarget.offsetParent.id).data('position');
				$.notificationx.active_pressbar = 0;
				$.notificationx.hideBar(event.currentTarget.offsetParent.id);
				$('body').css('padding-' + position, 0 ).removeClass('has-nx-bar');
			});
		}
	};

	$.notificationx.showBar = function (bar, bar_id) {
		if (Cookies.get('notificationx_nx-bar-' + bar_id)) {
			return false;
		}

		var delay = parseInt(bar.dataset.initial_delay),
			barHeight = $(bar).children('.nx-bar-inner'),
			xAdminBar = document.querySelector('#wpadminbar'),
			xAdminBarHeight = xAdminBar != null ? xAdminBar.offsetHeight : 0;

		if (delay === '' || isNaN(delay)) {
			delay = 0;
		}

		setTimeout(function () {
			var html = $('html');
			html.addClass('nx-bar-active');
			if ($(bar).hasClass('nx-position-top')) {
				$(html).animate([{
						'padding-top': 0,
					},
					{
						'padding-top': barHeight + 'px'
					},
				], {
					duration: 300
				});
				html.css('padding-top', barHeight + 'px');
				$(bar).animate([{
						top: 0 + 'px'
					},
					{
						top: xAdminBarHeight + 'px'
					},
				], {
					duration: 300
				});
				$(bar).css('top', xAdminBarHeight + 'px');
			}
			$(bar).addClass('nx-bar-visible');
			var body = $('body');
				body.trigger('nx_frontend_bar_show', [bar, bar_id]);
			$.notificationx.active_pressbar = 1;
		}, delay * 1000);
	};

	$.notificationx.hideBar = function (id) {
		var bar = $('.nx-bar#' + id),
			html = $('html'),
			close_forever = bar[0].dataset.close_forever;

		if (close_forever) {
			var date = new Date(),
				expired_timestamp = date.getTime() + (2 * 30 * 24 * 60 * 60 * 1000),
				expired_date = new Date(expired_timestamp);
			Cookies.set('notificationx_' + id, true, {
				expires: expired_date,
				path: '/'
			});
		}

		html.removeClass('nx-bar-active');
		html.css('padding-top', 0);

		bar.animate({
			height: 0 + 'px'
		}, 300);
		bar.removeClass('nx-bar-visible');
		$.notificationx.active_pressbar = 0;
	};

	$.notificationx.render = function (configuration, html) {
		var notificationHTML = document.createElement('div');
		notificationHTML.classList.add('notificationx-conversions');
		notificationHTML.insertAdjacentHTML('beforeend', html);

		var count = 0,
			notifications = notificationHTML.querySelectorAll('.notificationx-' + configuration.id),
			delayBetween = configuration.delay_between,
			last = $.notificationx.last(configuration.id, false);

		if (last >= 0) {
			count = last + 1;
		}

		if (configuration.loop === 0 && notifications.length === 1) {
			count = 0;
		}

		$('body').trigger('nx_before_render', [configuration, html]);

		setTimeout(function () {
			$.notificationx.show(notifications[count], configuration, count);

			setTimeout(function () {
				$.notificationx.hide(notifications[count]);
				count++;
				var nextNotification = setInterval(function () {
					$.notificationx.show(notifications[count], configuration, count);
					setTimeout(function () {
						$.notificationx.hide(notifications[count]);
						if (count >= notifications.length - 1) {
							count = 0;
							if (configuration.loop == 0) {
								clearInterval(nextNotification);
							}
						} else {
							count++;
						}
					}, configuration.display_for);
				}, delayBetween + configuration.display_for);
			}, configuration.display_for);
		}, configuration.delay_before);
	};

	$.notificationx.process = function (ids) {
		fetch(notificationx.ajaxurl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: new Headers({
					'Content-Type': 'application/x-www-form-urlencoded'
				}),
				body: 'action=nx_get_conversions&nonce=' + notificationx.nonce + '&ids=' + ids,
			})
			.then(function (response) {
				return response.json();
			})
			.then(function (response) {
				$.notificationx.render(response.config, response.content);
			})
			.catch(function (err) {
				console.log('AJAX error, Something went wrong! Please, Contact support team.')
			});
	};

	$.notificationx.show = function (notification, configuration, count) {
		if ('undefined' === typeof notification || 0 === notification.length) {
			return;
		}
		/* Check if notification is closed by user */
		if (Cookies.get('nx-close-for-session')) {
			return;
		}

		var image = $( notification ).find('img');
			image = image[0], isGIF = -1;
		if( image != undefined ) {
			var imgSrc = image.src,
				isGIF = image.src.indexOf('.gif');
		}

		var body = $('body'), 
			isMobile = notification.classList.value.indexOf('nx-mobile-notification') != -1,
			bottomCss = isMobile ? '10px' : '30px';
		body.append(notification);

		if( isGIF > 0 ) {
			image.src = imgSrc;
		}
		
		if( $.notificationx.windowWidth > 480 && isMobile ) {
			bottomCss = '20px';
		}
		if( $.notificationx.windowWidth > 786 ) {
			bottomCss = '30px';
		}

		$(notification).animate({
			'bottom': bottomCss,
			'opacity': 1
		}, 500);

		body.trigger('nx_frontend_jquery', [configuration, notification]);

		$.notificationx.save(configuration.id, count);

		var nxClose = $(notification).find('.notificationx-close');
		if (nxClose != null) {
			nxClose.on('click', function (event) {
				var close = $(this);
				var parent = $(close[0]).parents('.nx-notification');
				$.notificationx.hide(parent);
				/* Set cookie for stop showing notification for current session */
				Cookies.set("nx-close-for-session", 1);
			});
		}
	};

	$.notificationx.hide = function (notification) {
		if (notification === undefined) {
			return;
		}
		if (Cookies.get('nx-close-for-session')) {
			return;
		}
		$(notification).animate({
			'bottom': '0px',
			'opacity': 0
		}, 500);

		setTimeout(function () {
			$.notificationx.remove($(notification));
		}, 300);
	};

	$.notificationx.remove = function (notification) {
		notification.remove();
	};

	$.notificationx.save = function (id, rank) {
		if (window.localStorage) {
			var lastOne = $.notificationx.last(id, true);
			if ('object' === typeof lastOne) {
				lastOne[id] = rank;
			} else {
				lastOne = new Object;
				lastOne[id] = rank;
			}
			window.localStorage.setItem('nx_notifications', JSON.stringify(lastOne));
		} else {
			console.log('Browser does not support localStorage!');
		}
	};

	$.notificationx.last = function (id, elem) {
		var last = -1;
		if (window.localStorage) {
			var notifications = window.localStorage.getItem('nx_notifications');
			if (null !== notifications) {
				notifications = JSON.parse(notifications);
				if (undefined !== notifications[id]) {
					if (elem) {
						return notifications;
					}
					last = notifications[id];
				}
			}
		} else {
			console.log('Browser does not support localStorage!');
		}
		return last;
	};
})(jQuery);