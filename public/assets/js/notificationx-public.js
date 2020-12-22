(function ($) {
	"use strict";

	/**
	 * NotificationX Admin JS
	 */

	$.notificationx = $.notificationx || {};

	$.notificationx.active_pressbar = 0;
	$.notificationx.countdownInterval = null;

	$(document).ready(function () {
		$.notificationx.init();
	});

	window.addEventListener("load", function () {
		$.notificationx.pressbar();
	});

	$.notificationx.init = function () {
		$.notificationx.windowWidth = $(window).outerWidth();
		$.notificationx.conversions();
		$.notificationx.analytics();
		$.notificationx.events();
	};

	$.notificationx.Ajaxlytics = function (data) {
		if (data == {} || data == undefined) {
			return;
		}
		if (typeof NotificationX != "undefined") {
			if (data.nonce == undefined) {
				return;
			}
			jQuery.ajax({
				type: "POST",
				url: NotificationX.ajaxurl,
				data: {
					action: "notificationx_pro_analytics",
					nonce: data.nonce,
					id: data.id,
					clicked: data.clicked || false,
					nonce_key: data.nonce_key || false,
				},
				success: function (response) {
					// Response Code
				},
			});
		}
	};

	$.notificationx.analytics = function () {
		var data = {};
		// Bar Analytics nx_frontend_bar_show
		$("body").on("nx_frontend_bar_show", function (event, bar, bar_id) {
			var nonce = $(bar).data("nonce"),
				analytics = $(bar).data("analytics") == true;
			data.nonce = nonce;
			data.id = bar_id;
			data.nonce_key = "_notificationx_bar_nonce";
			if (nonce != undefined && analytics) {
				$.notificationx.Ajaxlytics(data);
				$(bar)
					.find("a.nx-bar-button")
					.on("click", function (e) {
						e.preventDefault();
						data.clicked = true;
						$.notificationx.Ajaxlytics(data);
						data.clicked = false;
						if (
							e.currentTarget.attributes.hasOwnProperty("target")
						) {
							window.open($(this).attr("href"));
						} else {
							window.location.href = $(this).attr("href");
						}
					});
			}
		});

		$("body").on("nx_before_render", function (event, configuration, html) {
			if (configuration.id && configuration.analytics) {
				var nonce = $(html).find(".notificationx-analytics").val();
				data.nonce = nonce;
				data.id = configuration.id;
				data.nonce_key = "_notificationx_pro_analytics_nonce";
				$.notificationx.Ajaxlytics(data);
			}
		});
		$("body").on(
			"nx_frontend_jquery",
			function (event, configuration, notification) {
				if (configuration.id && configuration.analytics) {
					$(notification).on("click", function (e) {
						var nonce = $(this)
							.find(".notificationx-analytics")
							.val();
						data.nonce = nonce;
						data.id = configuration.id;
						data.nonce_key = "_notificationx_pro_analytics_nonce";
						data.clicked = true;
						$.notificationx.Ajaxlytics(data);
						data.clicked = false;
					});
				}
			}
		);
	};

	$.notificationx.countdown = function (args) {
		if (!args.end_date) {
			return;
		}
		var currentTime = new Date();
		var expiredTime = args.end_date.getTime();
		var time = 0;
		time = parseInt((expiredTime - currentTime) / 1000);
		if (time <= 0) {
			if ($.notificationx.countdownInterval) {
				clearInterval($.notificationx.countdownInterval);
			}
			time = 0;
			if (!args.evergreen) {
				args.bar
					.querySelector(".nx-countdown")
					.classList.add("nx-expired");
				var endText = args.bar.querySelector(".nx-countdown-text");
				if (endText != null) {
					endText.classList.add("nx-expired");
				}
			} else {
				$.notificationx.hideBar("nx-bar-" + args.id, true);
			}
		}

		var days,
			hours,
			minutes,
			seconds = 0;

		seconds = time % 60;
		time = (time - seconds) / 60;
		minutes = time % 60;
		time = (time - minutes) / 60;
		hours = time % 24;
		days = (time - hours) / 24;

		days = (days < 10 ? "0" : "") + days;
		hours = (hours < 10 ? "0" : "") + hours;
		minutes = (minutes < 10 ? "0" : "") + minutes;
		seconds = (seconds < 10 ? "0" : "") + seconds;

		args.days.innerHTML = days;
		args.hours.innerHTML = hours;
		args.minutes.innerHTML = minutes;
		args.seconds.innerHTML = seconds;
	};
	$.notificationx.countdownWrapper = function (args) {
		$.notificationx.countdownInterval = setInterval(function () {
			$.notificationx.countdown(args);
		}, 1000);
		$.notificationx.countdown(args);
	};

	$.notificationx.pressbar = function () {
		var bars = $(".nx-bar");
		if (bars.length > 0) {
			bars.each(function (i, bar) {
				var id = bar.dataset.press_id,
					duration = bar.dataset.hide_after,
					auto_hide = bar.dataset.auto_hide,
					close_forever = bar.dataset.close_forever,
					start_date = bar.dataset.start_date
						? new Date(bar.dataset.start_date)
						: false,
					end_date = bar.dataset.end_date
						? new Date(bar.dataset.end_date)
						: false,
					barHeight = bar.querySelector(".nx-bar-inner").offsetHeight,
					initialDelay = bar.dataset.initial_delay * 1000,
					position = bar.dataset.position,
					body_push = bar.dataset.body_push,
					evergreen = Boolean(bar.dataset.evergreen);

				if (bar.classList.contains("nx-bar-shortcode")) {
					return false;
				}

				if (Cookies.get("notificationx_nx-bar-" + id)) {
					return false;
				}

				if (body_push == "pushed" || body_push == undefined) {
					/* add padding in body after initial delay */
					var initTimeout = setTimeout(function () {
						if ($(bar).hasClass("nx-bar-out")) {
							clearTimeout(initTimeout);
							return;
						}

						$("body")
							.addClass("has-nx-bar")
							.css("padding-" + position, barHeight);
						clearTimeout(initTimeout);
					}, initialDelay);
					/* remove padding in body after if auto hide is enable */
					if (parseInt(auto_hide)) {
						var timeoutAutoHide = setTimeout(function () {
							$("body")
								.css("padding-" + position, 0)
								.removeClass("has-nx-bar");
							clearTimeout(timeoutAutoHide);
						}, parseInt(duration) * 1000);
					}
				}

				var cdWargs = {
					id: id,
					bar: bar,
					end_date: end_date,
					evergreen: evergreen,
					start_date: start_date,
					days: bar.querySelector(".nx-days"),
					hours: bar.querySelector(".nx-hours"),
					minutes: bar.querySelector(".nx-minutes"),
					seconds: bar.querySelector(".nx-seconds"),
				};
				$.notificationx.showBar(bar, id);
				if (!evergreen) {
					$.notificationx.countdownWrapper(cdWargs);
				}

				if (
					("" !== duration || undefined !== duration) &&
					parseInt(auto_hide)
				) {
					var durationTimeout = setTimeout(function () {
						$.notificationx.hideBar("nx-bar-" + id, close_forever);
						clearTimeout(durationTimeout);
					}, parseInt(duration) * 1000);
				}
			});
		}
	};

	$.notificationx.conversions = function () {
		if ("undefined" === typeof notificationx) {
			return;
		}

		window.localStorage.removeItem("nx_notifications");

		if (notificationx.notificatons.length > 0) {
			notificationx.notificatons.map(function (id) {
				$.notificationx.process(id);
			});
		}
		if (notificationx.global_notifications.length > 0) {
			$.notificationx.process(notificationx.global_notifications);
		}

		// if (notificationx.conversions.length > 0) {
		// 	notificationx.conversions.map(function (id) {
		// 		$.notificationx.process(id);
		// 	});
		// }

		// if (notificationx.reviews.length > 0) {
		// 	if (notificationx.reviews.length > 1) {
		// 		notificationx.reviews.map(function (id) {
		// 			$.notificationx.process(id);
		// 		});
		// 	} else {
		// 		$.notificationx.process(notificationx.reviews[0]);
		// 	}
		// }

		// if (notificationx.form.length > 0) {
		// 	if (notificationx.form.length > 1) {
		// 		notificationx.form.map(function (id) {
		// 			$.notificationx.process(id);
		// 		});
		// 	} else {
		// 		$.notificationx.process(notificationx.form[0]);
		// 	}
		// }

		// if (notificationx.stats.length > 0) {
		// 	if (notificationx.stats.length > 1) {
		// 		notificationx.stats.map(function (id) {
		// 			$.notificationx.process(id);
		// 		});
		// 	} else {
		// 		$.notificationx.process(notificationx.stats[0]);
		// 	}
		// }

		// if (notificationx.comments.length > 0) {
		// 	$.notificationx.process(notificationx.comments[0]);
		// }

		// if (notificationx.pro_ext.length > 0) {
		// 	notificationx.pro_ext.map(function (item, i) {
		// 		$.notificationx.process(item);
		// 	});
		// }
	};

	$.notificationx.events = function () {
		var barClose = $(".nx-bar .nx-close");
		if (barClose !== null) {
			barClose.on("click", function (event) {
				$.notificationx.active_pressbar = 0;
				$.notificationx.hideBar(event.currentTarget.offsetParent.id);
			});
		}
	};

	$.notificationx.showBar = function (bar, bar_id) {
		if (Cookies.get("notificationx_nx-bar-" + bar_id)) {
			return false;
		}

		var delay = parseInt(bar.dataset.initial_delay),
			barHeight = $(bar).children(".nx-bar-inner"),
			xAdminBar = document.querySelector("#wpadminbar"),
			xAdminBarHeight = xAdminBar != null ? xAdminBar.offsetHeight : 0;

		if (delay === "" || isNaN(delay)) {
			delay = 0;
		}

		var showBarTimeout = setTimeout(function () {
			if ($(bar).hasClass("nx-bar-out")) {
				clearTimeout(showBarTimeout);
				return;
			}

			var html = $("html");
			html.addClass("nx-bar-active");
			if ($(bar).hasClass("nx-position-top")) {
				$(html).animate(
					[
						{
							"padding-top": 0,
						},
						{
							"padding-top": barHeight + "px",
						},
					],
					{
						duration: 300,
					}
				);
				html.css("padding-top", barHeight + "px");
				// $(bar).animate(
				// 	[
				// 		{
				// 			top: 0 + "px",
				// 		},
				// 		{
				// 			top: xAdminBarHeight + "px",
				// 		},
				// 	],
				// 	{
				// 		duration: 300,
				// 	}
				// );
				$(bar).css("top", xAdminBarHeight + "px");
			}
			$(bar).addClass("nx-bar-visible");
			var body = $("body");
			body.trigger("nx_frontend_bar_show", [bar, bar_id]);
			$.notificationx.active_pressbar = 1;
			clearTimeout(showBarTimeout);
		}, delay * 1000);
	};

	$.notificationx.hideBar = function (id) {
		var bar = $(".nx-bar#" + id),
			html = $("html"),
			close_forever = bar[0].dataset.close_forever,
			position = $("#" + id).data("position");

		if (close_forever) {
			var date = new Date(),
				expired_timestamp =
					date.getTime() + 2 * 30 * 24 * 60 * 60 * 1000,
				expired_date = new Date(expired_timestamp);
			Cookies.set("notificationx_" + id, true, {
				expires: expired_date,
				path: "/",
			});
		}

		html.removeClass("nx-bar-active");
		html.css("padding-top", 0);
		bar.addClass("nx-bar-out")
			.parents("body")
			.css("padding-" + position, 0)
			.removeClass("has-nx-bar");
		bar.removeClass("nx-bar-visible");
		$.notificationx.active_pressbar = 0;
	};

	$.notificationx.render = function (configuration, html) {
		// if (configuration.id == undefined) {
		// 	return;
		// }
		var notificationHTML = document.createElement("div");
		notificationHTML.classList.add("notificationx-conversions");
		notificationHTML.insertAdjacentHTML("beforeend", html);

		var id =
			configuration.id != null
				? ".notificationx-" + configuration.id
				: ".nx-notification";

		var count = 0,
			notifications = notificationHTML.querySelectorAll(id),
			delayBetween = configuration.delay_between,
			last = $.notificationx.last(configuration.id, false);

		if (last >= 0) {
			count = last + 1;
		}

		if (configuration.loop === 0 && notifications.length === 1) {
			count = 0;
		}

		$("body").trigger("nx_before_render", [configuration, html]);

		setTimeout(function () {
			$.notificationx.show(notifications[count], configuration, count);

			setTimeout(function () {
				$.notificationx.hide(notifications[count], configuration.id);
				count++;
				var nextNotification = setInterval(function () {
					$.notificationx.show(
						notifications[count],
						configuration,
						count
					);
					setTimeout(function () {
						$.notificationx.hide(
							notifications[count],
							configuration.id
						);
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
		var idx = ids,
			global = false;
		if (typeof idx !== "number") {
			idx = idx.toString();
			global = true;
		}

		jQuery
			.post({
				url: notificationx.ajaxurl,
				method: "POST",
				credentials: "same-origin",
				data: {
					action: "nx_get_conversions",
					nonce: notificationx.nonce,
					ids: idx,
					global: global,
				},
				success: function (response) {
					var res = JSON.parse(response);
					$.notificationx.render(res.config, res.content);
				},
			})
			.fail(function (err) {
				console.error(err);
				console.error(
					"AJAX error, Something went wrong! Please, Contact support team."
				);
			});
	};

	$.notificationx.show = function (notification, configuration, count) {
		if ("undefined" === typeof notification || 0 === notification.length) {
			return;
		}
		/* Check if notification is closed by user */
		var nxCookies = Cookies.get("nx-close-for-session");
		if (nxCookies != undefined) {
			nxCookies = JSON.parse(nxCookies);
			if (
				nxCookies.hasOwnProperty(configuration.id) &&
				nxCookies[configuration.id] == true
			) {
				return;
			}
		} else {
			nxCookies = {};
		}

		var image = $(notification).find("img");
		(image = image[0]), (isGIF = -1);
		if (image != undefined) {
			var imgSrc = image.src,
				isGIF = image.src.indexOf(".gif");
		}

		var body = $("body"),
			isMobile =
				notification.classList.value.indexOf(
					"nx-mobile-notification"
				) != -1,
			bottomCss = isMobile ? "10px" : "30px";
		body.append(notification);

		if (isGIF > 0) {
			image.src = imgSrc;
		}

		if ($.notificationx.windowWidth > 480 && isMobile) {
			bottomCss = "20px";
		}
		if ($.notificationx.windowWidth > 786) {
			bottomCss = "30px";
		}

		$(notification).animate(
			{
				bottom: bottomCss,
				opacity: 1,
			},
			500
		);

		body.trigger("nx_frontend_jquery", [configuration, notification]);

		$.notificationx.save(configuration.id, count);

		var nxClose = $(notification).find(".notificationx-close");
		if (nxClose != null) {
			nxClose.on("click", function (event) {
				var close = $(this);
				var parent = $(close[0]).parents(".nx-notification");
				$.notificationx.hide(parent, configuration.id);
				nxCookies[configuration.id] = true;
				/* Set cookie for stop showing notification for current session */
				Cookies.set("nx-close-for-session", JSON.stringify(nxCookies));
			});
		}
	};

	$.notificationx.hide = function (notification, nx_id) {
		if (notification === undefined) {
			return;
		}

		var nxCookies = Cookies.get("nx-close-for-session");

		if (
			nxCookies != undefined &&
			nx_id != undefined &&
			nxCookies.hasOwnProperty(nx_id) &&
			nxCookies[nx_id] == true
		) {
			return;
		}
		$(notification).animate(
			{
				bottom: "0px",
				opacity: 0,
			},
			500
		);

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
			if ("object" === typeof lastOne) {
				lastOne[id] = rank;
			} else {
				lastOne = new Object();
				lastOne[id] = rank;
			}
			window.localStorage.setItem(
				"nx_notifications",
				JSON.stringify(lastOne)
			);
		} else {
			console.log("Browser does not support localStorage!");
		}
	};

	$.notificationx.last = function (id, elem) {
		var last = -1;
		if (window.localStorage) {
			var notifications = window.localStorage.getItem("nx_notifications");
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
			console.log("Browser does not support localStorage!");
		}
		return last;
	};
})(jQuery);
