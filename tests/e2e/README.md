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

## WooCommerce orders after a save or Regenerate

[woo-orders-rebuild.js](woo-orders-rebuild.js) checks which orders a WooCommerce sales notification stores when it is saved or regenerated, and which reach a visitor (issue #218). The start of the "Display From The Last" window used to be 12 hours off when it fell between 00:00 and 00:59 UTC or between 13:00 and 23:59 UTC. The runner picks the "hours" part of the window from the current time, so it tests both ranges at any time of day.

- [woo-orders-rebuild.php](woo-orders-rebuild.php) (through WP-CLI) creates one product and 16 Processing orders of known ages, without sending email.
- The runner saves a WooCommerce Sales notification through `POST /nx`, as the builder does, then for each window compares the stored orders with the expected ones. An order 6 hours inside the window must be stored and one 6 hours outside must not.
- It also checks Display The Last (5 and 20), a save back to the first settings, `GET /regenerate/{id}`, the entries the public `notice` route returns to a visitor, the popup on the home page, and the older Sales Notification type with the WooCommerce source.

```bash
node tests/e2e/woo-orders-rebuild.js
NX_E2E_PHP_MEMORY=2G node tests/e2e/woo-orders-rebuild.js   # when WP-CLI runs out of memory
```

It needs WooCommerce active and no Processing or Completed order from the last 31 days, because other orders would count towards Display The Last; it stops with a message otherwise. It deletes the product, the orders, both notifications and the login session when the run ends. Manual cases: [docs/qa-guide/woo-sales-rebuild-window.md](../../docs/qa-guide/woo-sales-rebuild-window.md).

## Build With AI preset preview (Pro)

`tests/e2e/preset-preview.js` in NotificationX Pro opens the Exit Intent Build With AI history with a mocked preset and checks the preview POST carries the nonce and renders. It reuses `auth.php` and `puppeteer-core` from this plugin.
