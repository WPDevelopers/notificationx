# Test Plan — Free 3.4.0 (code separation)

**Audience:** QA tester running the NotificationX 3.4.0 release candidate.
**Release under test:** NotificationX (free) 3.4.0, against NotificationX Pro 3.3.0 and against Pro 3.2.2.

## What changed

This release adds extension points and a compatibility warning. **No feature was added, changed or removed.** Every notification must look and behave exactly as it does in 3.3.1 — any visible difference on the front end is a bug.

| Change | Why it matters for testing |
|---|---|
| The front-end runtime now exposes eight WordPress JS filters (listed below) so add-on plugins can supply their own theme markup and behaviour | New code runs on every notification render |
| `@wordpress/hooks` is no longer bundled into the front-end JS; it now loads from WordPress's shared `wp-hooks` script | The front-end bundle was rebuilt and its script dependencies changed — this is the highest-risk part of the release |
| The "update NotificationX Pro" admin notice now requires Pro **3.3.0**, is error-level, and can no longer be dismissed | New admin-facing behaviour |

### The eight extension points

Each one wraps a decision the runtime already made. With no add-on listening, every one of them must produce exactly the result it produced in 3.3.1.

| Filter | Wraps |
|---|---|
| `nx_frontend_template` | The theme's rows of text, chosen ahead of all built-in themes |
| `nx_theme_before_content` | Markup between the image and the content block (Discount Alert theme 13's button) |
| `nx_theme_after_content` | Markup after the content block (Discount Alert theme 15's button) |
| `nx_content_append` | Markup at the end of the content block (Discount Alert theme 14's button) |
| `nx_frontend_time_is_countdown` | Whether the time reads "5 days remaining" instead of "5 days ago" |
| `nx_frontend_no_entry_link_types` | Which link types render no link to the entry |
| `nx_frontend_link_button` | The link button's label, URL and subscribe behaviour |
| `nx_frontend_no_mobile_design_sources` | Which sources opt out of the compact mobile layout |

### Files changed (for reference when reporting)

- `notificationx.php` — new `NOTIFICATIONX_REQUIRED_PRO_VERSION` constant; rewritten `notificationx_free_compatibility_notice()`
- `includes/FrontEnd/FrontEnd.php` — `notificationx-public` script now declares a `wp-hooks` dependency
- `nxdev/notificationx/frontend/themes/GetTemplate.ts` — `nx_frontend_template` added ahead of the built-in theme cases
- `nxdev/notificationx/frontend/themes/Theme.tsx` — `nx_theme_before_content` / `nx_theme_after_content` slots; `nx_frontend_time_is_countdown`
- `nxdev/notificationx/frontend/themes/helpers/Content.tsx` — `nx_content_append` slot
- `nxdev/notificationx/frontend/core/Analytics.tsx` — `nx_frontend_no_entry_link_types`, `nx_frontend_link_button`
- `nxdev/notificationx/frontend/core/NotificationContainer.tsx` — `nx_frontend_no_mobile_design_sources`
- `webpack.frontend.config.js` — `@wordpress/hooks` added to `externals`
- `assets/public/js/frontend.js`, `crossSite.js`, `4468.js`, `5223.js` — rebuilt bundles

## Setup

1. Install NotificationX **3.4.0** (release candidate).
2. Have both Pro versions available: **3.3.0** (current) and **3.2.2** (previous). Parts D and E switch between them.
3. Activate WooCommerce and EDD with at least one purchasable product each.
4. Enable `WP_DEBUG` and `WP_DEBUG_LOG` so `wp-content/debug.log` captures warnings.
5. Keep the browser DevTools **Console** and **Network** tabs open throughout. Several checks depend on script load order and console output, not only on what is visible on the page.

---

## Part A — Front-end regression

This is the most important section. The front-end bundle was rebuilt and its dependency handling changed, so every notification type needs a look.

1. Create and publish one notification for each of: **WooCommerce Sales, EDD Sales, Comments, Reviews, Download Stats, Notification Bar, Popup, Exit Intent, Contact Form, GDPR**.
2. View each one on the front end.
   - **Expected:** identical rendering to 3.3.1 — same rows of text, same images, same layout, same close button.
   - **Fail if:** a row is missing, rows are out of order, text is unescaped or doubled, or the notification does not appear at all.
3. For a WooCommerce Sales notification, step through **every theme** in the theme grid and confirm each renders correctly on the front end.
4. Repeat step 3 for the **responsive/mobile** themes at mobile width.
5. Check the **Notification Bar** specifically — it uses a separate render path from the popup themes.
6. Confirm **Exit Intent** still triggers on exit and renders its selected design.
7. Check the browser **Console** on every page where a notification renders.
   - **Fail if:** any JavaScript error appears, in particular `wp is not defined`, `Cannot read properties of undefined (reading 'hooks')`, or `applyFilters is not a function`.

## Part B — Script loading

8. On a page with a notification, open DevTools → Network and filter for `.js`.
   - **Expected:** `wp-hooks` (WordPress core's `hooks.min.js`) is requested, and it appears **before** `notificationx-public` / `frontend.js` in the document.
   - **Fail if:** `wp-hooks` is absent, or loads after the NotificationX bundle.
9. In the Console, type `wp.hooks` and press Enter.
   - **Expected:** an object with `addFilter`, `applyFilters`, `removeFilter` and similar methods.
10. View the page source and confirm the `notificationx-public` script tag is present and not 404ing.

## Part C — Extension points

These prove the new filters are reachable from outside the plugin. Drop the snippet below into a must-use plugin (`wp-content/mu-plugins/nx-qa-hooks.php`), test, then **remove it** before continuing to Part D.

```php
<?php
/**
 * Plugin Name: NX QA — extension point probe
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_add_inline_script(
		'notificationx-public',
		"wp.hooks.addFilter('nx_frontend_template','nx-qa',function(template,themeName){"
		. "console.log('NX QA saw theme:',themeName);"
		. "return ['<span>NX QA ROW ONE</span>','<span>NX QA ROW TWO</span>'];"
		. "});",
		'before'
	);
}, 20 );
```

11. With the snippet active, view a page with any notification.
    - **Expected:** the notification renders the two rows **NX QA ROW ONE** and **NX QA ROW TWO** instead of its normal text.
    - **Expected:** the Console logs `NX QA saw theme: <theme name>` for each notification rendered.
    - **Fail if:** the notification renders its normal content — that means the filter never reached the bundle, and the release is not shippable.
12. Change the snippet's callback to `return template;` (leave the `console.log` in place) and reload.
    - **Expected:** the Console still logs the theme name, and the notification renders its **normal** content again.
    - This proves an add-on that declines a theme falls through to the built-in markup.
13. Delete `wp-content/mu-plugins/nx-qa-hooks.php` and reload.
    - **Expected:** everything back to normal, no console output.

## Part D — Discount Alert and the remaining filters

The three Discount Alert designs that add a button are now rendered through slots. Each must still render its button exactly once.

14. With Pro 3.3.0 active, create a **Discount Alert** notification using **theme 13**.
    - **Expected:** exactly one button renders, with its icon, in the same position as in 3.3.1.
    - **Fail if:** two buttons render, or none.
15. Repeat step 14 with **theme 15** (button after the content block) and **theme 14** (button at the end of the content block).
    - **Expected:** exactly one button each, in the same position as in 3.3.1.
16. Check a Discount Alert theme that has **no** button (for example theme 1) and confirm no stray button appears.
17. On any Discount Alert notification, check the time text.
    - **Expected:** it counts **down** — "5 days remaining" — not "5 days ago".
    - This exercises `nx_frontend_time_is_countdown`. On every other notification type the time must still read "… ago".
18. On a Discount Alert notification with a link button configured, click it.
    - **Expected:** the button shows the Discount Alert link text and behaves as in 3.3.1.
    - This exercises `nx_frontend_no_entry_link_types` and `nx_frontend_link_button`. Also confirm a **YouTube channel** notification's subscribe button is unchanged, since it shares the same code path.
19. View a Discount Alert, a Custom Notification, an Inline notification and a GDPR notification on a **phone-width** screen.
    - **Expected:** each keeps its full desktop-style layout rather than switching to the compact mobile card — exactly as in 3.3.1.
    - This exercises `nx_frontend_no_mobile_design_sources`. Then check a WooCommerce Sales notification at the same width and confirm it **does** still use the compact mobile layout.

## Part E — Compatibility notice

20. Activate Pro **3.2.2** (older than the required 3.3.0) and open any wp-admin page.
    - **Expected:** a red (error) notice reading "Your NotificationX Pro is older than 3.3.0…".
    - **Expected:** the notice has **no dismiss (×) button** and survives a page reload.
21. Confirm the notice's "wp-admin → Plugins" link goes to the Plugins screen.
22. Update Pro to **3.3.0** and reload wp-admin.
    - **Expected:** the notice is gone.
23. Deactivate Pro entirely and reload wp-admin.
    - **Expected:** the notice is gone (it only targets an installed but outdated Pro).
24. With Pro 3.2.2 still installed, confirm that Cart Peek, Inline notifications and Flashing Tab **still work** on the front end.
    - This matters: the notice warns about a *future* release. Nothing should actually be broken yet. If any of these three has stopped working, the notice is telling the truth too early and that is a bug.

## Part F — Free alone

25. Deactivate NotificationX Pro, leaving free 3.4.0 active.
    - **Expected:** the site loads with no PHP error and no JavaScript error.
    - **Expected:** free notification types still render — WooCommerce Sales, Comments, Reviews, Notification Bar, Popup, Exit Intent, GDPR.
    - **Expected:** Pro types appear as locked upsell cards in **Add New**, not as broken entries.

## Part G — With Pro 3.3.0

26. With free 3.4.0 and Pro 3.3.0 both active, re-run the Pro 3.3.0 test plan's Parts A–D (`notificationx-pro/docs/tests-guide/pro-3.3.0-code-separation.md`) — Flashing Tab, Cart Peek, Inline notifications and the inline shortcode.
    - **Expected:** all pass exactly as they did against free 3.3.1.

## Part H — Error log

27. After working through the sections above, open `wp-content/debug.log`.
    - **Fail if:** any new PHP warning, notice or fatal mentioning `NotificationX` appears.

---

## How to report a failure

Include the step number, the notification type and theme, the browser, the full Console error text, the DevTools Network entry for `frontend.js` and `wp-hooks` (full URLs and their order), and any `debug.log` lines.
