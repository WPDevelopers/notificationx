# QA: Frontend Performance

Run before releasing any phase of [../features/frontend-performance/](../features/frontend-performance/). Phase B1's automated results are in [02-b1-changes.md](../features/frontend-performance/02-b1-changes.md#verification-2026-09-22). This list covers what they can't.

Test with Free only, then with Free + Pro. Use a real browser with DevTools open, and a fresh private window for every GDPR check.

## Automated first

- [ ] `npm run test:js` passes.
- [ ] `npm run frontend && npm run check:bundle` passes.
- [ ] PHPUnit: no failures beyond the known baseline (`Test_Activation_Insights::test_activation_schedules_tracking_without_consent`).
- [ ] `node tests/e2e/compare-frontend-bundles.js` passes, or every difference is intended and reviewed.

## Notifications

- [ ] Sales/Conversions (WooCommerce): at least theme-one, theme-seven and theme-eight on desktop and mobile. Text, image, time ago, close button.
- [ ] Reviews (wordpress.org) with an animated-GIF plugin icon: the image shows.
- [ ] Comments, Download stats, Email subscription: one theme each.
- [ ] Rotation: with several entries, the second and third notifications appear on schedule.
- [ ] A notification with a link: clicking it opens the link, and the click is counted in Analytics.

## YouTube (Pro)

- [ ] Channel notification with the "default" subscribe button: the Google subscribe widget renders, and `apis.google.com/js/platform.js` is requested.
- [ ] Let it rotate to a second entry: the widget renders again.
- [ ] Channel notification with a custom button: no `platform.js` request.
- [ ] Any non-YouTube page: no `platform.js` request (DevTools → Network, filter `platform`).

## Notification bar

- [ ] Top and bottom position, sticky, close button.
- [ ] `press_bar_theme-four` and `-five`: the background image shows.
- [ ] Countdown bar.
- [ ] A bar using an Elementor template and one using a Gutenberg template.
- [ ] The page content moves down by the bar height and back when the bar closes.

## GDPR

- [ ] First visit: the banner appears after the configured delay (default 5 s).
- [ ] Delay set to **0**: the banner appears right after the page loads. **New in B1.**
- [ ] Delay left empty: 5 s.
- [ ] Accept All: the banner hides, and the `nx_cookie_manager` cookie has every category set to `true`. Reload: no banner.
- [ ] Reject All with "cookie removal" on: a `delete-cookies` request is sent and the banner hides.
- [ ] Customize: the preferences modal looks right (overlay, width, tabs, accordion, buttons) on desktop and mobile. Save My Preferences stores the chosen categories.
- [ ] Scripts added to cookie categories load only after consent for that category.
- [ ] With the WP Consent API plugin active: consent is passed through (`wp_set_consent`).
- [ ] Custom CSS on the GDPR notice still applies.

## Popup and exit intent

- [ ] Popup with an icon and a button icon: both icons show.
- [ ] Closing a popup keeps it closed for the session.
- [ ] Exit intent fires on mouse leave.

## Other contexts

- [ ] Builder preview for each type above renders the same as the live site.
- [ ] Cross-domain: a second site with the cross-domain snippet shows the notice, and loads the new `frontend.js` from the main site.
- [ ] Flashing tab still works.
- [ ] A non-English site locale (for example `de_DE`): "time ago" text is translated.
- [ ] Admin: the notification list toggle, bulk enable/disable, the builder, the cookie-scanner modal, and CSV import open and work. (The admin bundle re-exports the moved helpers.)

## Optimizer behaviour

- [ ] With a caching plugin's Delay JS on and no GDPR notice: notifications appear after the first interaction, with no console errors.
- [ ] Enabling or disabling a notification from the list fires `nx_status_updated`, for example with `add_action( 'nx_status_updated', fn( ...$a ) => error_log( print_r( $a, true ) ), 10, 3 );`.
