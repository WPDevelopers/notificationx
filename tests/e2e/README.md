# Frontend bundle E2E comparison

[compare-frontend-bundles.js](compare-frontend-bundles.js) renders NotificationX scenarios in a real local WordPress site with two versions of `assets/public/js/frontend.js` and reports every difference. Use it before releasing any change to the frontend runtime or its CSS.

Background: [docs/features/frontend-performance/](../../docs/features/frontend-performance/).

## Requirements

- A local site with NotificationX active (default `https://nx.test`).
- WP-CLI on `PATH`.
- Google Chrome. `puppeteer-core` comes from `@wordpress/scripts`, so there's nothing extra to install.

Nothing is written to the database. The runner POSTs each scenario to NotificationX preview mode (`nx-preview`), which renders from the request.

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
