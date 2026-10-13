# Deactivation feedback form

The "Sorry to see you go" dialog opens when an admin clicks **Deactivate** for NotificationX on **Plugins → Installed Plugins**. It asks for a reason, stores it, and sends it with the deactivation event to WP Insights.

All of it lives in [includes/Admin/PluginInsights.php](../../includes/Admin/PluginInsights.php). There is no separate CSS or JS file: the class prints both inline on `plugins.php` only.

## How it fits together

| Piece | Method | Hook |
| --- | --- | --- |
| Wrapper markup around the Deactivate link | `deactivate_action_links()` | `plugin_action_links_notificationx/notificationx.php` |
| CSS | `deactivate_reasons_form_style()` | `admin_print_styles-plugins.php` |
| Form markup + jQuery | `deactivate_reasons_form_script()` | `admin_print_footer_scripts-plugins.php` |
| Reason text and options | `deactivation_reasons()` | filter `wpins_form_text_notificationx` |
| AJAX save of the chosen reason | `deactivate_reasons_form_submit()` | `wp_ajax_deactivation_form_notificationx` |
| Send the reason on deactivation | `deactivate_this_plugin()` | `register_deactivation_hook` |

The form shows only while data collection is allowed (`is_tracking_allowed()`) and the instance was built with `goodbye_form => true`.

`deactivate_action_links()` puts this markup in front of the Deactivate link, inside the plugin row:

```html
<div class="wpinsights-goodbye-form-wrapper-notificationx">
  <div class="wpinsights-goodbye-form-bg"></div>          <!-- overlay -->
  <span class="wpinsights-goodbye-form" id="wpinsights-goodbye-form"></span>  <!-- dialog, filled by JS -->
</div>
<a id="wpinsights-goodbye-link-notificationx" href="…deactivate URL…">Deactivate</a>
```

Clicking the link adds `wpinsights-form-active-notificationx` to `<body>`. That class turns the wrapper into a full-screen `position: fixed` layer, and the jQuery fills the dialog. Clicking the overlay removes the class again. **Submit and Deactivate** posts the reason, then follows the deactivation URL. **Just Deactivate** follows it at once.

## Stacking rules

The wrapper sits deep inside the plugins table, but it must cover the whole admin screen, including the admin menu and the admin bar:

| Element | z-index (WordPress core) |
| --- | --- |
| `#adminmenuwrap` | 9990 |
| `#wpadminbar` | 99999 |
| Open `.wpinsights-goodbye-form-wrapper-notificationx` | **100000** |

The wrapper is `position: fixed`, so it is its own stacking context. Inside it the overlay has `z-index: 0` and the dialog `z-index: 1`. Do not move the z-index from the wrapper back onto the overlay: a z-index on a child of a stacking context cannot lift it above elements outside that context.

## Shared ids with other WPDeveloper plugins

Essential Blocks, EmbedPress and other WPDeveloper plugins print the same form with the same `#wpinsights-goodbye-form` id and `.wpinsights-goodbye-form-bg` class. When two of them are active, `$("#wpinsights-goodbye-form")` can return the other plugin's dialog. The script therefore finds the NotificationX wrapper once (`$wrapper`) and runs every other lookup through `$wrapper.find()` / `$form.find()`. Keep new lookups scoped the same way. Every CSS selector starts with the `-notificationx` wrapper or body class for the same reason.

## Fix history

**3.4.x (2026-10):** the dialog looked broken on the Plugins screen.

- The overlay had `z-index: 9` and the wrapper had none. The admin menu and admin bar stayed bright and clickable above the overlay. Fixed by the stacking rules above.
- **Just Deactivate** printed class `wpsp-put-deactivate-btn`, but the CSS styled `wpinsights-deactivate-btn`. The link showed as a plain admin link. The markup now uses `wpinsights-deactivate-btn`.
- The submit handler hid `#wpinsights-goodbye-form-notificationx .wpinsights-goodbye-form-body`. No element has that id, so the form never switched to the "Submitting form" spinner. Lookups now go through `$form.find()`.
- The overlay click handler was bound inside the Deactivate click handler, so each open added one more handler. It is now bound once.
- The redirect ran only on AJAX success. A failed request left the user on the dialog. The redirect now runs in `.always()`.
- The page behind the open dialog no longer scrolls, and the dialog has `max-height` and `overflow-y: auto` so it fits short and narrow screens.

## Tests

- PHPUnit: [tests/test-deactivation-feedback-form.php](../../tests/test-deactivation-feedback-form.php) (`--group deactivation-form`). It checks the z-index order, that every footer link class has a CSS rule, that the submit handler targets elements in the markup, the wrapper scoping, the single overlay binding, the `.always()` redirect, the action-link markup, and escaping of reason labels.
- Gating of the form on data collection: [tests/test-activation-insights.php](../../tests/test-activation-insights.php).
- Manual: [../qa-guide/deactivation-feedback-form.md](../qa-guide/deactivation-feedback-form.md).
