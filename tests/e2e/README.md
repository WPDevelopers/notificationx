# Frontend bundle E2E comparison

[compare-frontend-bundles.js](compare-frontend-bundles.js) renders NotificationX scenarios in a real local WordPress site with two versions of `assets/public/js/frontend.js` and reports every difference. Use it before releasing any change to the frontend runtime or its CSS.

Background: [docs/features/frontend-performance/](../../docs/features/frontend-performance/).

## Requirements

- A local site with NotificationX active (default `https://nx.test`).
- WP-CLI on `PATH`.
- Google Chrome. `puppeteer-core` comes from `@wordpress/scripts`, so there's nothing extra to install.

The runner POSTs each scenario to NotificationX preview mode (`nx-preview`), which renders from the request. Preview mode only accepts the payload from a user who can edit notifications, with a valid `nx-preview-nonce`, so the runner logs in first: [auth.php](auth.php) (run through WP-CLI as `NX_E2E_USER`) creates a login session and a nonce for it, and ends the session when the run finishes. That session token is the only thing written to the database. If a run crashes, the token expires after an hour.

## Run

```bash
npm run frontend                                    # build the new bundle into assets/
node tests/e2e/compare-frontend-bundles.js          # all scenarios
node tests/e2e/compare-frontend-bundles.js popup    # one scenario
```

The baseline defaults to the committed `HEAD:assets/public/js/frontend.js`.

| Variable | Default |
| --- | --- |
| `NX_E2E_SITE_URL` | `https://nx.test` |
| `NX_E2E_WP_PATH` | WordPress root three levels above the plugin |
| `NX_E2E_USER` | `admin` (any user with `edit_notificationx`) |
| `NX_E2E_OLD_BUNDLE` | `git show HEAD:assets/public/js/frontend.js` |
| `NX_E2E_CHROME` | `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome` |

## What it compares

For each scenario in [scenarios.php](scenarios.php), once with each bundle:

- The NotificationX DOM, normalised for random IDs.
- A 1280×800 screenshot, pixel by pixel. Animations are frozen.
- Consent cookies (`nx_*`) and `delete-cookies` requests after clicking a GDPR button (`gdpr-modal`, `gdpr-accept`, `gdpr-reject`).
- Requests to Google's `platform.js`.
- When the notification first appeared.
- Console errors. The new bundle may not add any; pre-existing ones are only reported.

Off-site requests (analytics, fonts, CDNs) are logged but aborted, so timing and rendering are deterministic.

The exit code is 1 if any scenario differs. When a difference is intended (for example a CSS fix), review the PNGs and HTML dumps in `tests/e2e/out/` (git-ignored) by hand.

## Adding a scenario

Add an entry to `$nx_e2e_scenarios` in [scenarios.php](scenarios.php): source, type, theme, and whatever fields the theme needs to show real content. Settings go through `normalize_post()` and the `nx_get_post` filters, like a saved notification. To click something after the notification appears, add the scenario name and a selector to `ACTIONS` in the runner.

## Builder preview and access check

[builder-preview.js](builder-preview.js) opens the builder for existing notifications, clicks **Preview** and checks that:

- the form posted into the preview iframe carries `nx-preview-nonce`,
- the iframe renders a notification in preview mode (screenshot in `out/builder-preview-<id>.png`),
- replaying the same payload is refused without a login, with a nonce but no login, and with a login but no nonce.

```bash
npm run admin                                        # the builder bundle sends the nonce
node tests/e2e/builder-preview.js 179 163            # notification IDs on your site
```

Pick notifications whose type shows the Preview button (not Notification Bar, which previews inside the builder). It uses the same environment variables as the bundle comparison.

## Release smoke test

[release-smoke.js](release-smoke.js) covers what a release needs beyond the two runners above:

- The WPScan proofs of concept as a visitor who is not logged in: a private post and a shortcode through `nx-preview`, and `send-rating`. A request with a login and a valid nonce is the control.
- Every NotificationX admin page mounts without a script error.
- Every source in [sources.php](sources.php), free and Pro: create through the REST API, read back, open in the builder, preview, delete.
- Saved Notification Bar and Popup notifications show for a visitor who is not logged in.

```bash
node tests/e2e/release-smoke.js                      # everything, about 6 minutes
node tests/e2e/release-smoke.js press_bar brevo      # only these sources
```

It writes one login session, one private post and one notification per source to the site's database, and removes them when the run ends. It never sends the authorised `send-rating` request, because that emails support; `tests/test-security-send-rating.php` covers that path with a mocked mailer.

## Settings in the builder payload

[builder-settings-redaction.js](builder-settings-redaction.js) checks that users who cannot edit settings get no settings form and no credentials in the admin app, and that the app still works for them:

- It creates two temporary users, `nx_e2e_viewer` (author with `read_notificationx`) and `nx_e2e_creator` (contributor with `read_notificationx` + `edit_notificationx`), and deletes them when the run ends.
- For each, and for `NX_E2E_USER`, it opens the NotificationX pages the user can reach and fails on any script error, then follows the client-side route to the settings screen (WordPress never checks that route) and expects the app to leave it.
- It searches `window.notificationxTabs` and `GET /builder` for the integration API key and the admin email. The administrator must still see the key.

```bash
node tests/e2e/builder-settings-redaction.js         # newest notification as the edit target
node tests/e2e/builder-settings-redaction.js 179     # this notification
```

Besides the two users, it writes one login session per user and removes them when the run ends. Console errors that also show on `dev` without the fix (React development warnings, an icon with `height="auto"`, the Dashboard's analytics request for a view-only user) are not counted; the list is in the script.

## Field list cache on the frontend

This script and the next two (views with analytics off, WooCommerce entries) belong to the frontend `/notice` performance work for issue #214. What the issue was, what is fixed and what is left: [docs/features/notice-request-performance.md](../../docs/features/notice-request-performance.md).

[builder-field-names-cache.js](builder-field-names-cache.js) checks that the frontend `/notice` request does not depend on the large cached builder fields (`nx_builder_fields`). It needs no browser. It sends real `/notice` requests in three states:

- no cache at all: the first request caches the builder fields and the field list,
- the large value removed behind the plugin's back, as a store that rejected it would leave things: responses are byte-identical and the large value is not rebuilt,
- both values removed: the response is the same apart from field defaults that hold the build time, and one request rebuilds both.

It also checks that the field list matches the cached builder fields and that the builder still gets its fields, and prints the time of each state.

```bash
node tests/e2e/builder-field-names-cache.js
```

It creates three temporary Notification Bars ("NX E2E field list 1" to 3) and deletes them when the run ends. Each `/notice` request counts one view per notification shown, as a page view does. Background: [docs/architecture/data-storage.md](../../docs/architecture/data-storage.md#the-field-list-cache).

## Views with analytics off

[analytics-disabled.js](analytics-disabled.js) checks that the frontend writes no views while **Enable Analytics** is off. It needs no browser. For a temporary Notification Bar it sends real `/notice` requests with analytics on (each request adds one view), off (the bar is still in the response, no view and no row is added, and the response is otherwise unchanged) and on again (counting resumes).

```bash
node tests/e2e/analytics-disabled.js
```

It creates one temporary Notification Bar ("NX E2E analytics") and deletes it with its views when the run ends. It changes the **Enable Analytics** setting during the run and puts the original value back.

## WooCommerce entries and product state

[woocommerce-entry-visibility.js](woocommerce-entry-visibility.js) checks that WooCommerce sales entries follow the state of their product and that each product is loaded once per request. It needs no browser, but it needs WooCommerce active with at least two published products; it prints `SKIP` otherwise. It creates a sales notification with 40 entries (30 for two published products, 5 for a draft product, 5 for a product that does not exist) and checks that:

- only entries of published products are in the `/notice` response,
- after the draft product is published its entries appear, and after it is trashed they are gone again,
- one request loads each product once (3 loads for 40 entries; before the change it was one load per entry).

```bash
node tests/e2e/woocommerce-entry-visibility.js
```

It creates one temporary notification ("NX E2E woo visibility") and one temporary product ("NX E2E hidden draft"), and deletes both when the run ends.

## Offer campaign

[offer-campaign.js](offer-campaign.js) checks the seasonal offer notice on the WordPress Dashboard and the pointer beside the NotificationX menu item ([docs/features/offer-campaign.md](../../docs/features/offer-campaign.md)):

- Notice: copy, link, new tab, both actions, styling keyed on its id, the emoji, the logo, Dashboard only, hidden on a phone.
- Pointer: copy, link, position beside the menu item, fixed colours under another admin colour scheme, which screens show it, staying beside the menu item while the page scrolls, hidden while the menu is off canvas.
- Dismiss: the click closes the pointer and sends the action with a nonce; a request with a wrong nonce is refused; a real dismissal is recorded for the user and gives the shared pointer slot back.

```bash
node tests/e2e/offer-campaign.js                              # NotificationX Pro inactive
NX_E2E_DEACTIVATE_PRO=1 node tests/e2e/offer-campaign.js      # deactivates Pro for the run, activates it again after
```

It needs today's date inside the campaign and exits with code 2 when the campaign cannot show (Pro active, outside the dates). It runs as `NX_E2E_USER`, who must be an administrator. Set `NX_E2E_PHP_MEMORY=1G` if WP-CLI runs out of memory on the site.

[offer-campaign.php](offer-campaign.php) saves and then clears what would hide the two surfaces (this user's dismissals of the notice and the pointer, the user's admin colour scheme, the notice library's `wpnotice_priority_time_expired` cooldown, the `_wpdeveloper_plugin_pointer_priority` option), and puts all of it back when the run ends, along with the one login session.

Only one WPDeveloper notice shows at a time. If another active plugin has a notice due on the Dashboard, the notice checks fail and the runner prints which notice showed instead ("shown instead: wpnotice-embedpress-optin"). Deactivate that plugin for the run. The pointer checks do not depend on it.

## Build With AI preset preview (Pro)

`tests/e2e/preset-preview.js` in NotificationX Pro opens the Exit Intent Build With AI history with a mocked preset and checks the preview POST carries the nonce and renders. It reuses `auth.php` and `puppeteer-core` from this plugin.

## Exit Intent end date

[exit-intent-end.js](exit-intent-end.js) checks "Stop Showing After End Date" (free issue #211) against QA guide [exit-intent-hide-after-end.md](../../docs/qa-guide/exit-intent-hide-after-end.md), cases T1–T8 and T11:

- Front end, as a visitor who is not logged in: an expired campaign does not open; a running one opens and closes when its end time arrives, without a dismissal entry; a page loaded before the end time does not open it after; a campaign with the toggle off keeps opening.
- Builder: the Customize tab toggle and end date field, the 7-day default on the Theme Five date, one value in Content and Customize, the saved value, and the preview of an expired campaign.
- T6 (Elementor design, seeded from `theme-five`) runs only when Elementor is active, and is reported as skipped otherwise.

```bash
npm run frontend
node tests/e2e/exit-intent-end.js                    # about 90 seconds
```

It creates four or five notifications (and one Elementor design for T6) through the REST API and removes them at the end. It waits for one campaign's end time, 40 seconds after the start.

Pro's `tests/e2e/exit-intent-countdown.js` covers the Theme Five / Six timer: the same end time in UTC+6 and UTC-5 (T9), and Theme Six closing at zero.

If WP-CLI dies with `Allowed memory size of 134217728 bytes exhausted` (seen on this site with Elementor, WooCommerce, EmbedPress and both NotificationX plugins active), skip a plugin the runner does not need for its WP-CLI calls only:

```bash
printf 'skip-plugins: woocommerce\n' > /tmp/wp-cli-e2e.yml
WP_CLI_CONFIG_PATH=/tmp/wp-cli-e2e.yml node tests/e2e/exit-intent-end.js
```
