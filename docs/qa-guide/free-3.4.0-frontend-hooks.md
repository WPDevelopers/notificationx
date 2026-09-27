# Test Plan: Free 3.4.0 (frontend hooks for the code separation)

**Audience:** QA tester running the NotificationX (free) 3.4.0 release candidate.
**Release under test:** NotificationX 3.4.0 with NotificationX Pro 3.2.3.

## What changed

This release adds extension points that a later Pro release will use to take over Pro-only rendering from the free plugin. **Nothing is removed and no behavior should change.** Every notification must look and act exactly as it does on free 3.3.2. **Any visible difference is a bug.**

| Area | Change | What you can see |
|---|---|---|
| Frontend script | `notificationx-public` now loads the WordPress `wp-hooks` script first | One extra script (`wp-hooks`) on pages that show a notification |
| Discount Alert, Cart Peek, CTA buttons, mobile layout | The built-in code now runs through JavaScript filters. With no filter registered, it renders as before | Nothing. Verify with the regression cases |
| Admin | New warning notice when NotificationX Pro is older than 3.2.3. Users cannot dismiss it | Only on sites with an old Pro |
| Cross Domain Notice | `crossSite.js` is rebuilt with the same code | Nothing. It must still work on non-WordPress sites |

Developer reference: [../api/frontend-js-hooks.md](../api/frontend-js-hooks.md).

### Files changed (for reference when reporting)

- `nxdev/notificationx/frontend/core/hooks.ts`, `core/runtime.ts` (new), `index.tsx`
- `nxdev/notificationx/frontend/themes/GetTemplate.ts`, `Theme.tsx`, `helpers/Content.tsx`, `helpers/Image.js`
- `nxdev/notificationx/frontend/core/Analytics.tsx`, `core/NotificationContainer.tsx`
- `includes/FrontEnd/FrontEnd.php`: script dependencies
- `includes/Admin/Admin.php`: Pro version notice
- Built files: `assets/public/js/frontend.js`, `assets/public/js/crossSite.js`, `assets/admin/js/admin.js`

## Setup

1. Install free NotificationX **3.4.0** (release candidate) and Pro **3.2.3**.
2. Activate WooCommerce with at least one product and one completed order.
3. In **NotificationX → Settings → Modules**, confirm that WooCommerce, Discount Alert (announcements), Notification Bar and Flashing Tab are on.
4. Remove `NX_DEBUG` from `wp-config.php`, or make sure it is not defined. When `NX_DEBUG` is on, the plugin loads files from `nxbuild/` instead of `assets/`, and a stale local build causes false failures.
5. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
6. Keep the browser DevTools **Console** and **Network** tabs open for every frontend check. **Any red Console error that mentions NotificationX, `wp.hooks` or React is a failure,** even when the notification looks fine.
7. **Comparison site (recommended):** keep a second site on free **3.3.2** with the same notifications. When you are not sure whether something looks different, compare it with that site.

---

## Part A: Script loading

**T1.** Open a frontend page that shows a notification. In DevTools → Elements, search for `wp-hooks-js`.
- **Expected:** `<script id="wp-hooks-js">` comes **before** `<script id="notificationx-public-js">`.
- **Fail if:** `wp-hooks-js` is missing, or it comes after `notificationx-public-js`.

**T2.** In the Console, run the three lines below one at a time:

```js
typeof wp.hooks.applyFilters
window.nxFrontendRuntime.version
Object.isFrozen(window.nxFrontendRuntime)
```

- **Expected:** `"function"`, `1` and `true`.

**T3.** Open a page that shows **no** notification.
- **Expected:** neither `notificationx-public-js` nor `nxFrontendRuntime` is present. The plugin still loads nothing on pages without notifications.

## Part B: Discount Alert (the main regression area)

Discount Alert is a Pro type whose rendering code is still in free. This release sends that code through the new hooks, so check it carefully.

**T4.** Create a Discount Alert notification for each selectable theme: **Theme 1, Theme 2, Theme 12, Theme 14, Theme 15**. For each one, set a discount, an expiry time and a link. Where the design has a button, also set the button text. Publish them, then check each one on the frontend (enable only one at a time if they overlap).

| Theme | What must render |
|---|---|
| Theme 1, Theme 2 | The discount badge graphic (an SVG with the discount value and "OFF") in the image area, with the correct colors |
| Theme 12 | Its image and the three text rows |
| Theme 14 | The text rows, and the CTA button **inside** the content area, after the text |
| Theme 15 | The text rows, and the CTA button **after** the content area, before the close button |

- **Expected:** each theme looks exactly like it does on free 3.3.2.
- **Fail if:** the badge or button is missing, or **appears twice**, or its colors or text differ.

**T5.** On the Theme 14 or Theme 15 notification, click the CTA button.
- **Expected:** the link opens (in a new tab if "open in new tab" is on). **NotificationX → Analytics** shows the click for that notification.

**T6.** On the same notification, click the card itself, **not** the button.
- **Expected:** the card click does **not** navigate. Only the button navigates. This is the same behavior as 3.3.2.

**T7.** Check the time row of a Discount Alert whose expiry is in the future.
- **Expected:** the text reads like **"5 days remaining"** (a countdown), **not** "5 days ago".

**T8.** Turn on **Advanced Design** for a Discount Alert and change the discount text color, discount background, button background, button text color and button font size.
- **Expected:** every change shows on the frontend.

**T9.** Open the site at mobile width (DevTools device toolbar, below 575 px).
- **Expected:** Discount Alert keeps the **desktop** layout, as in 3.3.2. It does not switch to the compact mobile card.

**T10.** If a Discount Alert was saved with **Theme 13** on an older version (the theme is hidden in the picker now), open the page that shows it.
- **Expected:** it still renders the button with the play icon before the content.
- Skip this case if no Theme 13 notification exists.

**T11.** In the builder, open each Discount Alert from T4 and look at the live preview. Open **Advanced Template** and check the default template text.
- **Expected:** the preview matches the frontend. The Advanced Template default is filled in and is not empty.

## Part C: Cart Peek, CTA buttons and mobile layout

**T12.** Create a **Cart Peek** notification. Check the first two designs in the theme grid (`conv-theme-fourteen` and `conv-theme-sixteen`), and one other design. Add a product to the cart in another browser, then view the notification.
- **Expected:** the first two designs show **2 rows**. Other designs show **3 rows** (shopper count, product, time). No "recently purchased" text appears.

**T13.** Create one notification for each link type below and check its CTA button text and link:
- **WooCommerce Sales** with a product link.
- **YouTube** with a video link, and **YouTube** with a channel link (subscribe button).
- **Notification Bar** with a button.
- **Expected:** the button text and link work as in 3.3.2. For a YouTube channel with the default subscribe button, the Google subscribe widget renders.

**T14.** At mobile width (below 575 px), check a WooCommerce Sales notification, a Custom Notification, an Inline notification and the GDPR banner.
- **Expected:** Sales uses the compact mobile card (when Pro is active). Custom, Inline and GDPR keep their desktop layout. This matches 3.3.2.

## Part D: Regression sweep

**T15.** Create and publish one notification of each type, and check that it renders with no Console error: WooCommerce Sales, Comments, Reviews, Download Stats, Notification Bar, Popup, Email Subscription, Exit Intent, Page Analytics, Custom Notification, Inline (shortcode on a page), Flashing Tab, GDPR.

**T16.** Check two notifications that use a split design (for example Sales **Theme Five** and Reviews **review-comment**) with Advanced Design on.
- **Expected:** image background color and badge look as in 3.3.2.

**T17.** In the builder, open **Add New** and create a Sales notification from scratch. Switch between several themes and watch the preview.
- **Expected:** the preview updates. There is no white screen and no Console error.

**T18.** Check `wp-content/debug.log` after all Part A–D cases.
- **Fail if:** there is any new PHP warning, notice or fatal error that mentions `NotificationX`.

## Part E: Hooks actually run (optional, needs file access)

This part shows that the new hooks run on a real site. It needs a temporary mu-plugin. **Delete the file afterwards.**

**T19.** Create the file `wp-content/mu-plugins/nx-qa-hooks.php` with this content:

```php
<?php
// QA only: delete after testing free 3.4.0.
add_action( 'wp_enqueue_scripts', function () {
	wp_add_inline_script( 'wp-hooks', "
		wp.hooks.addFilter( 'nx_frontend_template', 'nx-qa', function ( rows, settings ) {
			var sales = [ 'woocommerce', 'woocommerce_sales' ];
			return settings && sales.indexOf( settings.source ) !== -1 ? [ 'QA HOOK OK' ] : rows;
		} );
		wp.hooks.addFilter( 'nx_frontend_template', 'nx-qa-throw', function () {
			throw new Error( 'QA broken filter' );
		}, 20 );
	" );
}, 20 );
```

Then open a page with a **WooCommerce Sales** notification.
- **Expected:** the Console shows the error `NotificationX: filter "nx_frontend_template" failed.` Because of that error, the Sales notification falls back to its **normal** text, and the site keeps working.

Delete the second `addFilter` block (the one that throws), then reload.
- **Expected:** the Sales notification text reads **"QA HOOK OK"**. Notifications from other sources are not affected.

**Delete `nx-qa-hooks.php` and reload.**
- **Expected:** the normal text is back.

## Part F: Cross Domain Notice

**T20.** In **NotificationX → Settings → Cross Domain Notice**, copy the script snippet. Paste it into a plain `.html` file on a **non-WordPress** site, or into a local file opened in the browser, and open that page.
- **Expected:** the notifications appear. The Console has no error about `wp`, `hooks` or `undefined`.
- **Fail if:** the page shows an error such as `Cannot read properties of undefined (reading 'hooks')`.

## Part G: Pro version notice

**T21.** With Pro **3.2.3** active, open the WordPress Dashboard and the NotificationX pages.
- **Expected:** **no** "Please update NotificationX Pro" notice.

**T22.** On a separate site, install Pro **3.2.2** or older together with free 3.4.0. Open the Dashboard, the Plugins page, and **NotificationX → All Notifications**.
- **Expected:** a yellow notice on every screen, **including the NotificationX screens**: "You are using NotificationX Pro 3.2.2. Please update NotificationX Pro to version 3.2.3 or later. …". It has a **Go to plugin updates** link.
- **Expected:** the notice has **no** dismiss (×) button, and it comes back after every reload.

**T23.** On the same site, log in as an **Editor**.
- **Expected:** no notice. Only users who can update plugins see it.

**T24.** Update Pro on that site to 3.2.3.
- **Expected:** the notice disappears. All notifications keep working with no re-saving.

**T25.** Deactivate Pro (free only).
- **Expected:** no notice, no PHP error. Discount Alert and Cart Peek show as locked upsell cards in Add New. Free types from T15 still render.

## Part H: Caching and optimization plugins

**T26.** If the test site has WP Rocket, LiteSpeed Cache or Autoptimize, turn on JS minify, combine and **delay JS execution**. Clear the cache, then repeat T1, T4 (one theme) and T15 (Sales + Notification Bar).
- **Expected:** the notifications render as without the optimizer.
- **Fail if:** the notifications stop showing, or the Console shows `wp is not defined` or `Invalid hook call`. Record the optimizer name and settings.

---

## How to report a failure

Include:
- the case number (for example **T4, Theme 15**)
- the notification type and theme
- browser and screen width
- whether `NX_DEBUG` was set
- the full Console error text
- for script problems, the Network entry for `frontend.js` or `wp-hooks` (full URL)
- any `debug.log` lines
- whether the same case works on the free 3.3.2 comparison site
