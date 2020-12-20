(function ($) {
	"use strict";

	/**
	 * NotificationX Admin JS
	 */

	$.notificationx = $.notificationx || {};

	$(document).ready(function () {
		$.notificationx.init();
		$.notificationx.installPlugin();
		$("body").on(
			"click",
			".nx-metatab-menu li, .nx-builder-tab-menu li, .nx-meta-next, .nx-quick-builder-btn",
			function (e) {
				e.preventDefault();
				$.notificationx.tabChanger(this);
			}
		);

		$("body").on("click", ".nx-meta-modal-next", function (e) {
			e.preventDefault();
			$(".nx-press-bar-modal-wrapper.active").removeClass("active");
			$.notificationx.tabChanger(this);
		});

		$("body").on(
			"change",
			".nx-single-theme-wrapper > input:checked",
			function (e) {
				e.preventDefault();
				$.notificationx.templateForTheme();
			}
		);
		$("body").on("click", ".nx-email-test", function (e) {
			e.preventDefault();
			$.notificationx.testReport();
		});

		$("body").on("change", "#nx_meta_evergreen_timer", function () {
			$(".nx-time_randomize_between").hide();
			$(".nx-time_rotation").hide();

			if (!$("#nx_meta_enable_countdown").is(":checked")) {
				$(".nx-time_randomize").hide();
				$(".nx-time_reset").hide();
				return;
			}

			if ($("#nx_meta_time_randomize").is(":checked") && this.checked) {
				$(".nx-time_rotation").hide();
				$(".nx-time_randomize_between").show();
			} else {
				if (this.checked) {
					$(".nx-time_rotation").show();
					$(".nx-time_randomize_between").hide();
				}
			}
			if (!this.checked) {
				$(".nx-countdown_start_date").show();
				$(".nx-countdown_end_date").show();
				$(".nx-countdown_expired_text").show();
			} else {
				$(".nx-countdown_start_date").hide();
				$(".nx-countdown_end_date").hide();
				$(".nx-countdown_expired_text").hide();
			}
		});
		$("body").on("change", "#nx_meta_time_randomize", function () {
			$(".nx-time_randomize_between").hide();
			$(".nx-time_rotation").hide();

			if (
				!$("#nx_meta_enable_countdown").is(":checked") ||
				!$("#nx_meta_evergreen_timer").is(":checked")
			) {
				$(".nx-time_randomize").hide();
				$(".nx-time_reset").hide();
				return;
			}

			if (this.checked) {
				$(".nx-time_rotation").hide();
				$(".nx-time_randomize_between").show();
			} else {
				$(".nx-time_rotation").show();
				$(".nx-time_randomize_between").hide();
			}
		});
		$("body").on("change", "#nx_meta_enable_countdown", function () {
			$("#nx_meta_evergreen_timer").trigger("change");
			$("#nx_meta_time_randomize").trigger("change");
		});
		$("#nx_meta_evergreen_timer").trigger("change");
		$("#nx_meta_time_randomize").trigger("change");
	});

	$.notificationx.installPlugin = function () {
		$(".nx-on-click-install").each(function (e) {
			$(this).on("click", function (e) {
				e.preventDefault();
				var self = $(this);
				self.addClass("install-now updating-message");
				self.text("Installing...");

				var nonce = self.data("nonce"),
					slug = self.data("slug"),
					plugin_file = self.data("plugin_file");

				$.ajax({
					url: ajaxurl,
					type: "POST",
					data: {
						action: "wpdeveloper_upsale_core_install_notificationx",
						_wpnonce: nonce,
						slug: slug,
						file: plugin_file,
					},
					success: function (response) {
						self.text("Installed");
						if (!self.hasClass("nx-bar_with_elementor_install")) {
							setTimeout(function () {
								self.parents(".nx-field").hide();
							}, 2000);
						} else {
							setTimeout(function () {
								$(".nx-bar-install-elementor").remove();
								$(".nx-bar_with_elementor").removeClass(
									"hidden"
								);
							}, 2000);
						}
					},
					error: function (error) {
						console.log(error);
						self.removeClass("install-now updating-message");
						alert(error);
					},
					complete: function () {
						self.attr("disabled", "disabled");
						self.removeClass("install-now updating-message");
					},
				});
			});
		});
	};

	$(window).load(function () {
		$(".nx-preloader").fadeOut({
			complete: function () {
				$(".nx-metatab-inner-wrapper").fadeIn();
			},
		});

		var qVars = $.notificationx.get_query_vars("page");
		if (qVars != undefined) {
			if (qVars.indexOf("nx-settings") >= 0) {
				var cSettingsTab = qVars.split("#");
				$(
					'.nx-settings-menu li[data-tab="' + cSettingsTab[1] + '"]'
				).trigger("click");
			}
		}

		$("body").on("change", ".nx_meta_display_type", function () {
			var type = $(this).val();
			$.notificationx.get_instructions_enabled(type, false);
			switch (type) {
				case "conversions":
					$(".nx-themes .nx_meta_theme:checked").trigger("change");
					$.notificationx.trigger(".nx_meta_conversion_from");
					$("#nx_meta_advance_edit").trigger("change");
					break;
				case "comments":
					$(
						".nx-comment_themes .nx_meta_comment_theme:checked"
					).trigger("change");
					$.notificationx.trigger(".nx_meta_comments_source");
					$("#nx_meta_comment_advance_edit").trigger("change");
					break;
				case "reviews":
					$(".nx-wporg_themes .nx_meta_wporg_theme:checked").trigger(
						"change"
					);
					$.notificationx.trigger(".nx_meta_reviews_source");
					$("#nx_meta_wporg_advance_edit").trigger("change");
					break;
				case "download_stats":
					$(
						".nx-wpstats_themes .nx_meta_wpstats_theme:checked"
					).trigger("change");
					$.notificationx.trigger(".nx_meta_stats_source");
					$("#nx_meta_wpstats_advance_edit").trigger("change");
					break;
				case "elearning":
					$(
						".nx-elearning_themes .nx_meta_elearning_theme:checked"
					).trigger("change");
					$.notificationx.trigger(".nx_meta_elearning_source");
					$("#nx_meta_elearning_advance_edit").trigger("change");
					break;
				case "donation":
					$(
						".nx-donation_themes .nx_meta_donation_theme:checked"
					).trigger("change");
					$.notificationx.trigger(".nx_meta_donation_source");
					$("#nx_meta_donation_advance_edit").trigger("change");
					break;
				case "form":
					$(".nx-form_themes .nx_meta_form_theme:checked").trigger(
						"change"
					);
					$.notificationx.trigger(".nx_meta_form_source");
					$("#nx_meta_form_advance_edit").trigger("change");
					break;
			}
			$.notificationx.templateForTheme();
		});

		$("body").on(
			"change",
			"#nx_meta_wp_stats_template_new #nx_meta_wp_stats_template_new_third_param",
			function () {
				var value = $(this).val();
				if (value == "tag_custom_stats") {
					return "";
				}

				$(
					"#nx_meta_wp_stats_template_new #nx_meta_wp_stats_template_new_fourth_param"
				)
					.val(value + "_text")
					.trigger("change");
			}
		);

		$("body").on("change", ".nx_meta_conversion_from", function (e) {
			var conv_source = $(this).val();
			$.notificationx.get_instructions_enabled(false, conv_source);
			$(".nx-themes .nx_meta_theme:checked").trigger("change");
			$("#nx_meta_combine_multiorder:checked").trigger("change");
			switch (conv_source) {
				case "woocommerce" || "edd":
					$("#nx_meta_woo_template_adv").trigger("change");
					break;
			}
		});

		$("body").on("change", "#nx_meta_disable_reporting", function (e) {
			if (!$(this).is(":checked")) {
				$("#nx_meta_reporting_frequency").trigger("change");
			}
		});

		$("body").on("change", ".nx_meta_elearning_source", function () {
			var conv_source = $(this).val();
			$.notificationx.get_instructions_enabled(false, conv_source);
			switch (conv_source) {
				case "learndash":
					$("#nx_meta_ld_product_control").trigger("change");
					break;
				case "tutor":
					$("#nx_meta_tutor_product_control").trigger("change");
					break;
			}
		});

		$("body").on("change", ".nx_meta_donation_source", function () {
			var conv_source = $(this).val();
			$.notificationx.get_instructions_enabled(false, conv_source);
			switch (conv_source) {
				case "give":
					$("#nx_meta_give_forms_control").trigger("change");
					break;
			}
		});

		$("body").on("change", ".nx_meta_form_source", function () {
			var conv_source = $(this).val();
			$.notificationx.get_instructions_enabled(false, conv_source);
		});

		$("body").on("change", ".nx_meta_comments_source", function () {
			var comment_source = $(this).val();
			$.notificationx.get_instructions_enabled(false, comment_source);
			$(".nx-comment_themes .nx_meta_comment_theme:checked").trigger(
				"change"
			);
		});

		$("body").on("change", ".nx_meta_reviews_source", function () {
			var source = $(this).val();
			$.notificationx.get_instructions_enabled(false, source);
			$(".nx-wporg_themes .nx_meta_wporg_theme:checked").trigger(
				"change"
			);
			$("#nx_meta_wp_reviews_template_adv").trigger("change");
			switch (source) {
				case "wp_reviews":
					$("#nx_meta_wp_reviews_template_adv").trigger("change");
					var thirdParam = $(
						"#nx_meta_wp_reviews_template_new_third_param"
					);
					thirdParam[0][0].innerText = "Plugin Name";
					thirdParam.select2();
					break;
				case "woo_reviews":
					var thirdParam = $(
						"#nx_meta_wp_reviews_template_new_third_param"
					);
					thirdParam[0][0].innerText = "Product Name";
					thirdParam.select2();
					break;
			}
		});

		$("body").on("change", ".nx_meta_stats_source", function () {
			var source = $(this).val();
			$.notificationx.get_instructions_enabled(false, source);
			$(".nx-wpstats_themes .nx_meta_wpstats_theme:checked").trigger(
				"change"
			);
			$("#nx_meta_wp_stats_template_adv").trigger("change");
			switch (source) {
				case "wp_stats":
					$("#nx_meta_wp_stats_template_adv").trigger("change");
					break;
			}
		});

		$("body").on(
			"change",
			".nx-builder-content-wrapper .nx_meta_display_type",
			function (e) {
				var type = e.currentTarget.value,
					title = notificationx.title_of_types[type],
					options = {
						year: "numeric",
						month: "short",
						day: "numeric",
					},
					date = new Date().toLocaleDateString("en-US", options);

				// if (type === 'conversions') {
				// 	$('body').on('change', '.nx_meta_conversion_from', function (e) {
				// 		var title = notificationx.title_of_types[e.currentTarget.value];
				// 		$('.finalize_notificationx_name').text("NotificationX - " + title + ' - ' + date);
				// 	});
				// 	$('.nx_meta_conversion_from').trigger('change');
				// } else {
				// }
				$(".finalize_notificationx_name").text(
					"NotificationX - " + title + " - " + date
				);
			}
		);

		$(".nx_meta_display_type:checked").trigger("change");
	});

	$.notificationx.get_instructions_enabled = function (type, forSource) {
		var hasInstructions = false;
		if (!forSource && type != false) {
			Array.from(
				document.querySelectorAll("#nx-instructions .nxins-type")
			).forEach(function (item) {
				item.style.display = "none";
				if (item.classList.contains(type)) {
					item.style.display = "block";
					hasInstructions = true;
				}
			});
			// Array.from(
			// 	document.querySelectorAll(
			// 		"#nx-instructions .nxins-type .nxins-type-source"
			// 	)
			// ).forEach(function (item) {
			// 	item.style.display = "none";
			// });
		} else {
			if (hasInstructions) {
				hasInstructions = false;
			}
			Array.from(
				document.querySelectorAll(
					"#nx-instructions .nxins-type .nxins-type-source"
				)
			).forEach(function (item) {
				item.style.display = "none";
				if (item.classList.contains(forSource)) {
					item.style.display = "block";
					hasInstructions = true;
				}
			});
		}
		if (!hasInstructions) {
			$("#nx-instructions").hide();
		} else {
			$("#nx-instructions").show();
		}
	};

	$.notificationx.init = function () {
		$.notificationx.enabledDisabled();
		$.notificationx.toggleFields();
		$.notificationx.bindEvents();
		$.notificationx.initializeFields();
		$.notificationx.create_nx_bar();
	};
	$.notificationx.create_nx_bar = function () {
		$("body").on("click", ".nx-bar_with_elementor", function (e) {
			e.preventDefault();
			$(this).addClass("active");
			$(".nx-press-bar-modal-wrapper").addClass("active");
			$(".nx-metatab-menu > ul > li[data-tab='content_tab']").hide();
			$("#nx-design_tab .nx-meta-next").data("tab", "display_tab");
			$("#nx-design_tab .nx-meta-next").data("tabid", "4");
		});
		$(".nx-modal-close").on("click", function (e) {
			e.preventDefault();

			if (!e.currentTarget.classList.contains("nx-template-imported")) {
				$(".nx-metatab-menu > ul > li[data-tab='content_tab']").show();
				$("#nx-design_tab .nx-meta-next").data("tab", "content_tab");
				$("#nx-design_tab .nx-meta-next").data("tabid", "3");
				$(".nx-bar_with_elementor").removeClass("active");
			}

			$(".nx-press-bar-modal-wrapper").removeClass("active");
		});

		$(".nx-bar_with_elementor-import").on("click", function (e) {
			e.preventDefault();

			$(".nx-press-bar-modal-preload").addClass("active");
			$(
				".nx-press-bar-modal-preload.active .nx-modal-loading-text"
			).addClass("active");
			var self = $(this),
				theme = self.data("theme"),
				nonce = self.data("nonce"),
				post_data = $("#post").serializeArray(),
				bar_id = self.data("the_post");

			$.ajax({
				type: "post",
				url: window.ajaxurl,
				data: {
					action: "nx_create_bar",
					nonce: nonce,
					bar_id: bar_id,
					theme: theme,
					post_data: post_data,
				},
				success: function (res) {
					$(".nx-modal-close").addClass("nx-template-imported");
					$(
						".nx-press-bar-modal-preload.active .nx-modal-loading-text.active"
					).removeClass("active");
					$(
						".nx-press-bar-modal-preload.active .nx-modal-success-text"
					).addClass("active");
				},
			}).fail(function (err) {
				console.log(err);
			});
		});

		$(".nx-bar_with_elementor-remove").on("click", function (e) {
			e.preventDefault();

			var self = $(this),
				nonce = self.data("nonce"),
				bar_id = self.data("bar_id"),
				post_id = self.data("post_id");

			$.ajax({
				type: "post",
				url: window.ajaxurl,
				data: {
					action: "nx_create_bar_remove",
					nonce: nonce,
					bar_id: bar_id,
					post_id: post_id,
				},
				success: function (res) {
					window.location.reload();
				},
			});
		});
	};
	// @since 1.2.1
	$.notificationx.trigger = function (selector) {
		var source = $(selector + ":checked").val();
		if (source == undefined) {
			$(selector + ":first").trigger("click");
		} else {
			if ($(selector + ":checked").is(":disabled")) {
				$(".nx-radio-pro").trigger("click");
			} else {
				$(selector + ":checked").trigger("change");
			}
		}
	};

	$.notificationx.templateForTheme = function () {
		var source,
			templateID,
			themeID,
			type = $(".nx_meta_display_type:checked").val();

		if (type === "press_bar") {
			return;
		}

		source = $(
			".nx_meta_" + notificationx.source_types[type] + ":checked"
		).val();
		if (notificationx.theme_sources.hasOwnProperty(source)) {
			if (typeof notificationx.theme_sources[source] === "object") {
				themeID = $(
					".nx_meta_" +
						notificationx.theme_sources[source][type] +
						":checked"
				).val();
			} else {
				themeID = $(
					".nx_meta_" +
						notificationx.theme_sources[source] +
						":checked"
				).val();
			}
		}

		var temp_template_name = "";

		if (notificationx.template_keys.hasOwnProperty(source)) {
			if (typeof notificationx.template_keys[source] === "object") {
				templateID = $(
					"#nx_meta_" + notificationx.template_keys[source][type]
				);
				temp_template_name = notificationx.template_keys[source][type];
			} else {
				templateID = $(
					"#nx_meta_" + notificationx.template_keys[source]
				);
				temp_template_name = notificationx.template_keys[source];
			}
		}
		if (templateID.length <= 0) {
			return;
		}

		if (themeID.indexOf("comments-") >= 0) {
			temp_template_name = "comments_template_new";
		}
		if (themeID.indexOf("subs-") >= 0) {
			temp_template_name = "mailchimp_template_new";
		}
		if (themeID.indexOf("reviews-") >= 0) {
			temp_template_name = "wp_reviews_template_new";
		}
		if (themeID.indexOf("stats-") >= 0) {
			temp_template_name = "wp_stats_template_new";
		}

		var templateAdv = "";
		if (temp_template_name != undefined) {
			var templateAdv = temp_template_name.replace("_new", "_adv");
			var advTemplate = temp_template_name.replace("_new", "");
			templateAdv = $("#nx_meta_" + templateAdv);
			advTemplate = $("#nx_meta_" + advTemplate);
		}

		var templateDivID = templateID.attr("id");
		if (
			themeID === "maps_theme" ||
			themeID === "comments-maps_theme" ||
			themeID === "subs-maps_theme" ||
			themeID === "conv-theme-six"
		) {
			advTemplate.hide();
			templateID = $("#nx_meta_maps_theme_template_new");
			templateAdv = "maps_theme_template_adv";
			templateAdv = $("#nx_meta_" + templateAdv);
		} else {
			advTemplate.show();
		}

		if (temp_template_name != undefined) {
			if (templateAdv[0] != undefined) {
				if (templateAdv[0].checked === true) {
					templateAdv.trigger("change");
				}
			}
		}

		if (
			Object.keys(notificationx.template_settings).indexOf(
				templateDivID
			) >= 0 &&
			Object.keys(notificationx.template_settings[templateDivID]).indexOf(
				themeID
			) >= 0
		) {
			var themeOBJ =
				notificationx.template_settings[templateDivID][themeID];
			templateID.find("input, select").each(function (i, item) {
				var subKey = $(item).data("subkey");
				if (Object.keys(themeOBJ).indexOf(subKey) >= 0) {
					if (item.type === "text" && item.nodeName === "INPUT") {
						$(item).val(themeOBJ[subKey]);
					} else {
						$(item).val(themeOBJ[subKey]).trigger("change");
					}
				}
			});
		}
	};

	$.notificationx.bindEvents = function () {
		$("#nx_meta_show_on").trigger("change");

		//Advance Checkbox with SweetAlear
		$("body").on(
			"click",
			".nx-adv-checkbox-wrap label, #nx_sound_checkbox, .nx-stats-tease, .nx-cmo-conf",
			function (e) {
				if (typeof $(this)[0].dataset.swal == "undefined") {
					return;
				}
				if (typeof $(this)[0].dataset.swal != "undefined") {
					e.preventDefault();
				}
				var premium_content = document.createElement("p");
				var premium_anchor = document.createElement("a");

				premium_anchor.setAttribute(
					"href",
					"https://notificationx.com"
				);
				premium_anchor.innerText = "Premium";
				premium_anchor.style.color = "red";
				premium_content.innerHTML =
					"You need to upgrade to the <strong>" +
					premium_anchor.outerHTML +
					" </strong> Version to use this feature";

				swal({
					title: "Opps...",
					content: premium_content,
					icon: "warning",
					buttons: [false, "Close"],
					dangerMode: true,
				});
			}
		);

		/**
		 * Group Field Events
		 */
		$("body").delegate(
			".nx-group-field .nx-group-field-title",
			"click",
			function (e) {
				e.preventDefault();
				if ($(e.currentTarget).hasClass("nx-group-field-title")) {
					$.notificationx.groupToggle(this);
				}
			}
		);
		$("body").delegate(
			".nx-group-field .nx-group-clone",
			"click",
			function () {
				$.notificationx.cloneGroup(this);
			}
		);
		$("body").on("click", ".nx-group-field .nx-group-remove", function () {
			$.notificationx.removeGroup(this);
		});

		/**
		 * Media Field
		 */
		$("body").delegate(
			".nx-media-field-wrapper .nx-media-upload-button",
			"click",
			function (e) {
				e.preventDefault();
				$.notificationx.initMediaField(this);
			}
		);
		$("body").delegate(
			".nx-media-field-wrapper .nx-media-remove-button",
			"click",
			function (e) {
				e.preventDefault();
				$.notificationx.removeMedia(this);
			}
		);

		/**
		 * Settings Tab
		 */
		$("body").delegate(".nx-settings-menu li", "click", function (e) {
			$.notificationx.settingsTab(this);
		});

		var saveButton = $(".nx-settings-button");

		$("body").on(
			"click",
			".nx-pro-checkbox > label, .nx-radio-pro",
			function (e) {
				e.preventDefault();
				var premium_content = document.createElement("p");
				var premium_anchor = document.createElement("a");

				premium_anchor.setAttribute(
					"href",
					"https://wpdeveloper.net/in/notificationx-pro"
				);
				premium_anchor.innerText = "Premium";
				premium_anchor.style.color = "red";
				var pro_label = $(this).find(".nx-pro-label");
				if (pro_label.hasClass("has-to-update")) {
					premium_anchor.innerText =
						"Latest Pro v" +
						pro_label
							.text()
							.toString()
							.replace(/[ >=<]/g, "");
				}
				premium_content.innerHTML =
					"You need to upgrade to the <strong>" +
					premium_anchor.outerHTML +
					" </strong> Version to use this module.";

				swal({
					title: "Opps...",
					content: premium_content,
					icon: "warning",
					buttons: [false, "Close"],
					dangerMode: true,
				});
				return;
			}
		);

		$(
			".nx-checkbox-area .nx-checkbox input:enabled, .nx-settings-field"
		).on("click", function (e) {
			saveButton
				.addClass("nx-save-now")
				.removeAttr("disabled")
				.css("cursor", "pointer");
		});

		$("body").delegate(".nx-settings-button", "click", function (e) {
			e.preventDefault();
			var form = $(this).parents("#nx-settings-form");
			$.notificationx.submitSettings(this, form);
		});

		$("body").delegate(".nx-opt-alert", "click", function (e) {
			$.notificationx.fieldAlert(this);
		});

		/**
		 * Reset Section Settings
		 */
		$("body").delegate(".nx-section-reset", "click", function (e) {
			e.preventDefault();
			$.notificationx.resetSection(this);
		});
	};
	/**
	 * This function is responsible for
	 * enabling and disabling the notificationXs
	 */
	$.notificationx.enabledDisabled = function () {
		$(".nx-admin-status label").on("click", function (e) {
			e.stopPropagation();
			var $this = $(this),
				postID = $this.data("post"),
				nonce = $this.data("nonce"),
				siblings = $this.siblings("input"),
				$swal = $this.data("swal"),
				isActive = siblings.is(":checked");

			if ($swal) {
				var premium_content = document.createElement("p");
				var premium_anchor = document.createElement("a");

				premium_anchor.setAttribute(
					"href",
					"https://wpdeveloper.net/in/notificationx-pro"
				);
				premium_anchor.innerText = "Premium";
				premium_anchor.style.color = "red";
				premium_content.innerHTML =
					"You need to upgrade to the <strong>" +
					premium_anchor.outerHTML +
					" </strong> Version to use multiple notification for same type.";

				swal({
					title: "Opps...",
					content: premium_content,
					icon: "warning",
					buttons: [false, "Close"],
					dangerMode: true,
				});
				return;
			}

			if (isActive) {
				$this
					.siblings(".nx-admin-status-title.nxast-enable")
					.removeClass("active");
				$this
					.siblings(".nx-admin-status-title.nxast-disable")
					.addClass("active");
			} else {
				$this
					.siblings(".nx-admin-status-title.nxast-disable")
					.removeClass("active");
				$this
					.siblings(".nx-admin-status-title.nxast-enable")
					.addClass("active");
			}

			$.ajax({
				type: "post",
				url: window.ajaxurl,
				data: {
					action: "notifications_toggle_status",
					post_id: postID,
					nonce: nonce,
					status: isActive ? "inactive" : "active",
					url: window.location.href,
				},
				success: function (res) {
					if (res !== "success") {
						window.location.href = window.location.href;
					}
				},
			});
		});
	};

	$.notificationx.initializeFields = function () {
		if ($(".nx-meta-field, .nx-settings-field").length > 0) {
			$(".nx-meta-field, .nx-settings-field").map(function (
				iterator,
				item
			) {
				var node = item.nodeName;
				if (node === "SELECT") {
					var selectArgs = {};
					var form_id = $("#nx_meta_" + $(item).data("nxajax")).val();

					var ajaxArgs = {
						ajax: {
							url: ajaxurl,
							method: "GET",
							dataType: "json",
							cache: true,
							data: function (params) {
								return {
									action: $(item).data("ajax_action"),
									form_id: $(
										"#nx_meta_" + $(item).data("nxajax")
									).val(),
								};
							},
							processResults: function (data) {
								return { results: data };
							},
						},
					};
					if (
						$(item).data("nxajax") &&
						$(item).data("ajax_action").length > 0
					) {
						selectArgs = $.extend(selectArgs, ajaxArgs);
					}

					$(item).select2(selectArgs);

					if (form_id != undefined) {
						var tag_default_value = $(item).data("value");
						if (
							Object.keys(selectArgs).length > 0 &&
							$(item).data("ajax_action").length > 0
						) {
							$.ajax({
								type: "GET",
								url: ajaxurl,
								data: {
									action: $(item).data("ajax_action"),
									form_id: form_id,
								},
							}).then(function (data) {
								var tData = JSON.parse(data);
								if (typeof tData !== "object") {
									return;
								}
								var sData = tData.filter(function (m) {
									return m.id === tag_default_value;
								});
								if (sData.length === 0) {
									sData = tData;
								}
								if (tag_default_value.length === 0) {
									tag_default_value = sData[0].id;
								}
								var option = new Option(
									sData[0].text,
									tag_default_value,
									true,
									true
								);
								$(item).append(option).trigger("change");
								$(item).trigger({
									type: "select2:select",
									params: {
										data: data,
									},
								});
							});
						}
					}
				}
			});
		}
		// NotificationX_Admin.initDatepicker();
		if ($(".nx-countdown-datepicker").length > 0) {
			$("body .nx-control")
				.find(".nx-countdown-datepicker")
				.each(function (i, item) {
					var onlyPicker = $(item).find("input").data("only");
					if (onlyPicker === "timepicker") {
						$(item).find("input").flatpickr({
							enableTime: true,
							noCalendar: true,
							dateFormat: "h:i K",
						});
					} else {
						$(item).find("input").flatpickr({
							enableTime: true,
							dateFormat: "D, M d, Y h:i K",
						});
					}
				});
		}

		$(
			".notificationx-metabox-wrapper .nx-meta-field:not(.nx_meta_conversion_from)"
		).trigger("change");
		$(".nx-settings .nx-settings-field").trigger("change");

		// NotificationX_Admin.initColorField();
		if ($(".nx-colorpicker-field").length > 0) {
			if ("undefined" !== typeof $.fn.wpColorPicker) {
				$(".nx-colorpicker-field").each(function () {
					var color = $(this).val();
					$(this)
						.wpColorPicker({
							change: function (event, ui) {
								var element = event.target;
								var color = ui.color.toString();
								$(element)
									.parents(".wp-picker-container")
									.find("input.nx-colorpicker-field")
									.val(color)
									.trigger("change");
							},
						})
						.parents(".wp-picker-container")
						.find(".wp-color-result")
						.css("background-color", "#" + color);
				});
			}
		}
		$.notificationx.groupField();

		$.notificationx.template();
		$(".nx-meta-template-editable").trigger("blur");
	};

	$.notificationx.groupField = function () {
		if ($(".nx-group-field-wrapper").length < 0) {
			return;
		}

		$(".nx-group-field-wrapper")
			.find("div.nx-group-field:last-of-type")
			.addClass("open");

		var fields = $(".nx-group-field-wrapper");

		fields.each(function () {
			var $this = $(this),
				groups = $this.find(".nx-group-field"),
				firstGroup = $this.find(".nx-group-field:first"),
				lastGroup = $this.find(".nx-group-field:last");

			groups.each(function () {
				var groupContent = $(this)
					.find(".nx-group-field-title:not(.open)")
					.next();
				if (groupContent.is(":visible")) {
					groupContent.addClass("open");
				}
			});

			$this.find(".nx-group-field-add").on("click", function (e) {
				e.preventDefault();

				var fieldId = $this.attr("id"),
					dataId = $this.data("name"),
					wrapper = $this.find(".nx-group-fields-wrapper"),
					groups = $this.find(".nx-group-field"),
					firstGroup = $this.find(".nx-group-field:first"),
					lastGroup = $this.find(".nx-group-field:last"),
					clone = $($this.find(".nx-group-template").html()),
					groupId = parseInt(lastGroup.data("id")),
					nextGroupId = 1,
					title = clone.data("group-title");

				if (!isNaN(groupId)) {
					nextGroupId = groupId + 1;
				}

				groups.each(function () {
					$(this).removeClass("open");
				});

				// Reset all data of clone object.
				clone.attr("data-id", nextGroupId);
				clone.addClass("open");
				// clone.find('.nx-group-field-title > span').html(title + ' ' + nextGroupId);
				clone
					.find("tr.nx-field[id*=" + fieldId + "]")
					.each(function () {
						var fieldName = dataId;
						var fieldNameSuffix = $(this)
							.attr("id")
							.split("[1]")[1];
						var nextFieldId =
							fieldName +
							"[" +
							nextGroupId +
							"]" +
							fieldNameSuffix;
						var label = $(this).find("th label");

						$(this)
							.find('[name*="' + fieldName + '[1]"]')
							.each(function () {
								var inputName = $(this)
									.attr("name")
									.split("[1]");
								var inputNamePrefix = inputName[0];
								var inputNameSuffix = inputName[1];
								var newInputName =
									inputNamePrefix +
									"[" +
									nextGroupId +
									"]" +
									inputNameSuffix;
								$(this)
									.attr("id", newInputName)
									.attr("name", newInputName);
								label.attr("for", newInputName);
							});

						$(this).attr("id", nextFieldId);
					});

				clone.insertBefore($(this));
				$.notificationx.resetFieldIds(
					$(this)
						.parents(".nx-group-fields-wrapper")
						.find(".nx-group-field")
				);
				if ($(".nx-countdown-datepicker").length > 0) {
					$("body .nx-group-field")
						.find(".nx-countdown-datepicker")
						.each(function (i, item) {
							var input = $(item).find("input"),
								inputVal = input.val();
							input.flatpickr({
								enableTime: true,
								defaultDate: inputVal,
								dateFormat: "D, M d, Y h:i K",
							});
						});
				}
			});
		});
	};

	/**
	 * This function will change tab
	 * with menu click & Next Previous Button Click
	 */
	$.notificationx.tabChanger = function (buttonName) {
		var button = $(buttonName),
			tabID = button.data("tabid"),
			tabKey = button.data("tab"),
			tab,
			dir;

		if (tabKey != "") {
			tab = $("#nx-" + tabKey);
			$("#nx_builder_current_tab").val(tabKey);
		}
		if (button.hasClass("nx-quick-builder-btn")) {
			if (button.hasClass("btn-next")) {
				dir = "right";
			} else {
				dir = "left";
			}
		}

		if (buttonName.nodeName !== "BUTTON") {
			button
				.parent()
				.find("li")
				.each(function (i) {
					if (i < tabID) {
						$(this).addClass("nx-complete");
					} else {
						$(this).removeClass("nx-complete");
					}
				});

			button.addClass("active").siblings().removeClass("active");
			tab.addClass("active").siblings().removeClass("active");
			return;
		}
		if (tab === undefined) {
			console.log("add popup");
			$("#publish").trigger("click");
			return;
		}

		var contentMenu = $(".nx-builder-tab-menu").find(
				'li[data-tab="content_tab"]'
			),
			cDisplay = "none";
		if (contentMenu.length > 0) {
			if (contentMenu[0] != undefined) {
				cDisplay = contentMenu[0].style.display;
				var lMenu = $(
					'.nx-builder-tab-menu li[data-tabid="' + tabID + '"]'
				);
				cDisplay = lMenu[0].style.display;
			}
			if (cDisplay == "none" && dir != undefined) {
				if (dir == "left") {
					tabID = tabID - 1;
				} else {
					tabID = tabID + 1;
				}
			}
		}

		$('.nx-metatab-menu li[data-tabid="' + tabID + '"]').trigger("click");
		$('.nx-builder-tab-menu li[data-tabid="' + tabID + '"]').trigger(
			"click"
		);
	};

	$.notificationx.toggleFields = function () {
		$("body").delegate(
			".nx-meta-field, .nx-settings-field",
			"change",
			function (e) {
				if (this.type == "radio") {
					if (this.checked) {
						$.notificationx.checkDependencies(this);
					}
					return;
				}
				$.notificationx.checkDependencies(this);
			}
		);
		$("body").delegate(
			".nx-meta-field, .nx-settings-field",
			"click",
			function (e) {
				if (this.dataset.hasOwnProperty("swal") && this.dataset.swal) {
					$.notificationx.fieldAlert(this);
					e.preventDefault();
					return;
				}
			}
		);
	};

	$.notificationx.toggle = function (array, func, prefix, suffix, id) {
		var i = 0;
		suffix = "undefined" == typeof suffix ? "" : suffix;

		if (typeof array !== "undefined") {
			for (; i < array.length; i++) {
				var selector = prefix + array[i] + suffix;
				if (notificationx.template.indexOf(id) >= 0) {
					selector = "#nx_meta_" + id + "_" + array[i] + suffix;
				}

				var mainSelector = $(selector);
				if (mainSelector[0] != undefined) {
					var selectorType = mainSelector[0].nodeName;
					if (selectorType === "SELECT") {
						mainSelector.next()[func]();
					}
				}
				$(selector)[func]();
			}
		}
	};

	$.notificationx.checkDependencies = function (variable) {
		if (notificationx.toggleFields === null) {
			return;
		}

		var current = $(variable),
			container = current.parents(".nx-field:first"),
			id = container.data("id"),
			value = current.val();

		if ("checkbox" === current.attr("type")) {
			if (!current.is(":checked")) {
				value = 0;
			} else {
				value = 1;
			}
		}

		if (current.hasClass("nx-theme-selected")) {
			var currentTheme = current
				.parents(".nx-theme-control-wrapper")
				.data("name");
			value = $("#" + currentTheme).val();
		}

		var mainid = id;

		if (notificationx.template.indexOf(id) >= 0) {
			id = current.data("subkey");
		}

		if (notificationx.toggleFields.hasOwnProperty(id)) {
			var canShow = notificationx.toggleFields[id].hasOwnProperty(value);
			var canHide = true;
			if (notificationx.hideFields[id]) {
				var canHide = notificationx.hideFields[id].hasOwnProperty(
					value
				);
			}

			if (notificationx.toggleFields.hasOwnProperty(id) && canHide) {
				$.each(notificationx.toggleFields[id], function (key, array) {
					$.notificationx.toggle(
						array.fields,
						"hide",
						"#nx-meta-",
						"",
						mainid
					);
					$.notificationx.toggle(
						array.sections,
						"hide",
						"#nx-meta-section-",
						"",
						mainid
					);
				});
			}

			if (canShow) {
				$.notificationx.toggle(
					notificationx.toggleFields[id][value].fields,
					"show",
					"#nx-meta-",
					"",
					mainid
				);
				$.notificationx.toggle(
					notificationx.toggleFields[id][value].sections,
					"show",
					"#nx-meta-section-",
					"",
					mainid
				);
			}
		}

		if (notificationx.hideFields.hasOwnProperty(id)) {
			var hideFields = notificationx.hideFields[id];

			if (hideFields.hasOwnProperty(value)) {
				$.notificationx.toggle(
					hideFields[value].fields,
					"hide",
					"#nx-meta-",
					"",
					mainid
				);
				$.notificationx.toggle(
					hideFields[value].sections,
					"hide",
					"#nx-meta-section-",
					"",
					mainid
				);
			}
		}
	};

	$.notificationx.selectTheme = function (image) {
		var imgParent = $(image),
			img = imgParent.find("img"),
			value = img.data("theme"),
			wrapper = $(imgParent.parents(".nx-theme-control-wrapper")),
			inputID = wrapper.data("name");

		imgParent
			.addClass("nx-theme-selected")
			.siblings()
			.removeClass("nx-theme-selected");
		$("#" + inputID).val(value);
		imgParent.trigger("change");
	};

	$.notificationx.groupToggle = function (group) {
		var input = $(group),
			wrapper = input.parents(".nx-group-field");

		if (wrapper.hasClass("open")) {
			wrapper.removeClass("open");
		} else {
			wrapper.addClass("open").siblings().removeClass("open");
		}
	};

	$.notificationx.removeGroup = function (button) {
		var groupId = $(button).parents(".nx-group-field").attr("data-id"),
			group = $(button).parents(
				'.nx-group-field[data-id="' + groupId + '"]'
			),
			parent = group.parent();

		group.fadeOut({
			duration: 300,
			complete: function () {
				$(this).remove();
			},
		});

		$.notificationx.resetFieldIds(parent.find(".nx-group-field"));
	};

	$.notificationx.cloneGroup = function (button) {
		var groupId = $(button).parents(".nx-group-field").attr("data-id"),
			group = $(button).parents(
				'.nx-group-field[data-id="' + groupId + '"]'
			),
			clone = $(group.clone()),
			lastGroup = $(button)
				.parents(".nx-group-fields-wrapper")
				.find(".nx-group-field:last"),
			parent = group.parent(),
			nextGroupID = $(lastGroup).data("id") + 1;

		group.removeClass("open");

		clone.attr("data-id", nextGroupID);
		clone.insertAfter(group);
		$.notificationx.resetFieldIds(parent.find(".nx-group-field"));
		if ($(".nx-countdown-datepicker").length > 0) {
			$("body .nx-group-field")
				.find(".nx-countdown-datepicker")
				.each(function (i, item) {
					var input = $(item).find("input"),
						inputVal = input.val();
					input.flatpickr({
						enableTime: true,
						defaultDate: inputVal,
						dateFormat: "D, M d, Y h:i K",
					});
				});
		}
	};

	$.notificationx.resetFieldIds = function (groups) {
		if (groups.length <= 0) {
			return;
		}
		var groupID = 0;

		groups.map(function (iterator, item) {
			var item = $(item),
				fieldName = item.data("field-name"),
				groupInfo = item.find(".nx-group-field-info").data("info"),
				subFields = groupInfo.group_sub_fields;

			item.attr("data-id", groupID);

			var table_row = item.find("tr.nx-field");

			table_row.each(function (i, child) {
				var child = $($(child)[0]),
					childInput = child.find(
						'[name*="nx_meta_' + fieldName + '"]'
					),
					key = childInput.attr("data-key"),
					subKey = subFields[i].original_name,
					dataID = fieldName + "[" + groupID + "][" + subKey + "]",
					idName = "nx-meta-" + dataID,
					inputName = "nx_meta_" + dataID;

				child.attr("data-id", dataID);
				child.attr("id", idName);

				if (key != undefined && childInput.length === 1) {
					childInput.attr("id", inputName);
					childInput.attr("name", inputName);
					childInput.attr("data-key", dataID);
				} else {
					if (childInput.length > 1) {
						childInput.each(function (i, subInput) {
							if (subInput.type === "text") {
								var subInputName = inputName + "[url]";
							}
							if (subInput.type === "hidden") {
								var subInputName = inputName + "[id]";
							}

							subInput = $(subInput);
							subInput.attr("id", subInputName);
							subInput.attr("name", subInputName);
							subInput.attr("data-key", dataID);
						});
					}
				}
			});

			groupID++;
		});
	};

	$.notificationx.initMediaField = function (button) {
		var button = $(button),
			wrapper = button.parents(".nx-media-field-wrapper"),
			removeButton = wrapper.find(".nx-media-remove-button"),
			imgContainer = wrapper.find(".nx-thumb-container"),
			idField = wrapper.find(".nx-media-id"),
			urlField = wrapper.find(".nx-media-url");

		// Create a new media frame
		var frame = wp.media({
			title: "Upload Photo",
			button: {
				text: "Use this photo",
			},
			multiple: false, // Set to true to allow multiple files to be selected
		});

		// When an image is selected in the media frame...
		frame.on("select", function () {
			// Get media attachment details from the frame state
			var attachment = frame.state().get("selection").first().toJSON();
			/**
			 * Set image to the image container
			 */
			imgContainer
				.addClass("nx-has-thumb")
				.append(
					'<img src="' +
						attachment.url +
						'" alt="NotificationX" style="max-width:100%;"/>'
				);
			idField.val(attachment.id); // set image id
			urlField.val(attachment.url); // set image url
			// Hide the upload button
			button.addClass("hidden");
			// Show the remove button
			removeButton.removeClass("hidden");
		});
		// Finally, open the modal on click
		frame.open();
	};

	$.notificationx.removeMedia = function (button) {
		var button = $(button),
			wrapper = button.parents(".nx-media-field-wrapper"),
			uploadButton = wrapper.find(".nx-media-upload-button"),
			imgContainer = wrapper.find(".nx-has-thumb"),
			idField = wrapper.find(".nx-media-id"),
			urlField = wrapper.find(".nx-media-url");

		imgContainer.removeClass("nx-has-thumb").find("img").remove();

		urlField.val(""); // URL field has to be empty
		idField.val(""); // ID field has to empty as well

		button.addClass("hidden"); // Hide the remove button first
		uploadButton.removeClass("hidden"); // Show the uplaod button
	};

	$.notificationx.fieldAlert = function (button) {
		var premium_content = document.createElement("p");
		var premium_anchor = document.createElement("a");

		premium_anchor.setAttribute(
			"href",
			"https://wpdeveloper.net/in/notificationx-pro"
		);
		premium_anchor.innerText = "Premium";
		premium_anchor.style.color = "red";
		premium_content.innerHTML =
			"You need to upgrade to the <strong>" +
			premium_anchor.outerHTML +
			" </strong> Version to use this feature";

		swal({
			title: "Opps...",
			content: premium_content,
			icon: "warning",
			buttons: [false, "Close"],
			dangerMode: true,
		});
	};

	$.notificationx.resetSection = function (button) {
		var button = $(button),
			parent = button.parents(".nx-meta-section"),
			fields = parent.find(".nx-meta-field"),
			updateFields = [];

		window.fieldsss = fields;
		fields.map(function (iterator, item) {
			var item = $(item),
				default_value = item.data("default");

			item.val(default_value);

			if (item.hasClass("wp-color-picker")) {
				item.parents(".wp-picker-container")
					.find(".wp-color-result")
					.removeAttr("style");
			}
			if (item[0].id == "nx_meta_border") {
				item.trigger("click");
			} else {
				item.trigger("change");
			}
		});
	};

	$.notificationx.settingsTab = function (button) {
		var button = $(button),
			tabToGo = button.data("tab");

		button.addClass("active").siblings().removeClass("active");
		$("#nx-" + tabToGo)
			.addClass("active")
			.siblings()
			.removeClass("active");
	};

	$.notificationx.submitSettings = function (button, form) {
		var button = $(button),
			submitKey = button.data("key"),
			nonce = button.data("nonce"),
			formData = $(form).serializeArray();

		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action: "nx_general_settings",
				key: submitKey,
				nonce: nonce,
				form_data: formData,
			},
			beforeSend: function () {
				button.html(
					'<svg id="nx-spinner" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><circle cx="24" cy="4" r="4" fill="#fff"/><circle cx="12.19" cy="7.86" r="3.7" fill="#fffbf2"/><circle cx="5.02" cy="17.68" r="3.4" fill="#fef7e4"/><circle cx="5.02" cy="30.32" r="3.1" fill="#fef3d7"/><circle cx="12.19" cy="40.14" r="2.8" fill="#feefc9"/><circle cx="24" cy="44" r="2.5" fill="#feebbc"/><circle cx="35.81" cy="40.14" r="2.2" fill="#fde7af"/><circle cx="42.98" cy="30.32" r="1.9" fill="#fde3a1"/><circle cx="42.98" cy="17.68" r="1.6" fill="#fddf94"/><circle cx="35.81" cy="7.86" r="1.3" fill="#fcdb86"/></svg><span>Saving...</span>'
				);
			},
			success: function (res) {
				button.html("Save Settings");
				res = res.trim();
				if (res === "success") {
					swal({
						title: "Settings Saved!",
						text: "Click OK to continue",
						icon: "success",
						buttons: [false, "Ok"],
						timer: 2000,
					});
					$(".nx-save-now").removeClass("nx-save-now");
				} else {
					swal({
						title: "Settings Not Saved!",
						text: "Click OK to continue",
						icon: "error",
						buttons: [false, "Ok"],
					});
				}
			},
		});
	};

	$.notificationx.template = function (e) {
		$(".nx-meta-template-editable").prop("disabled", true);

		$(".nx-meta-template-editable").on("blur", function () {
			var editable = $(this),
				template = editable[0].innerText,
				splitedTemplate = template.trim().split("\n"),
				res,
				newItemLine = [],
				final;
			var nextSiblingsChild = editable[0].nextElementSibling.children;

			if (splitedTemplate != null) {
				splitedTemplate.forEach(function (item, i) {
					if (item != "") {
						var pattern = /\{\{[^\s]*\}\}/g;
						var templateVar = item.match(pattern);

						$(nextSiblingsChild[i]).val(item); // set value in hidden field!

						if (templateVar != null) {
							templateVar.forEach(function (
								childParam,
								iterator
							) {
								if (iterator > 0) {
									res = res.replace(
										childParam,
										'<span style="color:red">' +
											childParam +
											"</span>"
									);
								} else {
									res = item.replace(
										childParam,
										'<span style="color:red">' +
											childParam +
											"</span>"
									);
								}
							});
							newItemLine.push(res);
						} else {
							newItemLine.push(item);
						}
					}
				});
			}
			final = newItemLine.join("<br>");
			editable.html(final);
		});
	};

	$.notificationx.get_query_vars = function (name) {
		var vars = {};
		window.location.href.replace(
			/[?&]+([^=&]+)=([^&]*)/gi,
			function (m, key, value) {
				vars[key] = value;
			}
		);
		if (name != "") {
			return vars[name];
		}
		return vars;
	};

	$.notificationx.testReport = function () {
		$.ajax({
			type: "post",
			url: window.ajaxurl,
			data: {
				action: "nx_email_report_test",
				email: $("#nx_meta_reporting_email").val(),
			},
			success: function (res) {
				if (res.success) {
					swal({
						title: "",
						text: "Successfully Sent a Test Report in Your Email.",
						icon: "success",
						buttons: [false, "Ok"],
						timer: 2000,
					});
				} else {
					swal({
						title: "",
						text: "Something went wrong regarding sending email.",
						icon: "error",
						buttons: [false, "Ok"],
						timer: 2000,
					});
				}
			},
		});
	};
})(jQuery);
