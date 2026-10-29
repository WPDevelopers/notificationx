# QA guide: admin on WordPress < 6.2 (React 17) with Pro active

Covers [#196](https://github.com/WPDevelopers/notificationx/issues/196) in free and [notificationx-pro#56](https://github.com/WPDevelopers/notificationx-pro/issues/56) in Pro. Test free and Pro from these branches together:

- free `fix/56-prowidget-react17`
- Pro `fix/56-react-toastify-old-wp`

## What changed

- **Free.** The "Upgrade To PRO" sidebar widget (`ProWidget`) returned `undefined` when Pro was active. React 17, the core React of WordPress before 6.2, throws on that (minified React error #152), so the notification builder went blank. It now returns `null`.
- **Free.** The Build With AI panel that Pro renders (`nx_build_ai_render`) now sits inside an error boundary. If that panel fails to render, it shows "This section could not be loaded…" and the rest of the builder keeps working.
- **Pro.** `react-toastify` went from v10, which calls React 18's `useSyncExternalStore`, back to v9.1.3. Before this, Settings > License and Build With AI crashed with `o.useSyncExternalStore is not a function`.

Nothing should look different, apart from small toast styling: corners 4px instead of 6px, a slightly different shadow, and on Pro toasts the progress bar at full opacity.

## Setup

You need WordPress builds with different core React versions. Check the version with `window.React.version` in the browser console on any NotificationX admin page.

| Site | WordPress | Core React | PHP |
| --- | --- | --- | --- |
| A | 5.3.x | 16.9 | 7.4 |
| B | 6.1.x | 17.0 | 7.4 – 8.1 |
| C | latest | 18.3 | 8.x |

On old WordPress, turn off core auto-updates before you start: add `define( 'AUTOMATIC_UPDATER_DISABLED', true );` to `wp-config.php`. Otherwise an old WordPress can update itself to the latest version during the test.

Activate free and Pro on every site. Keep the browser console open and treat any red error as a failure.

## Cases

Run T1–T7 on sites A, B and C.

**T1. Builder, new notification.** NotificationX > Add New. Pick Notification Bar and click Next through Design, Content, Display and Customize.
Expected: every step renders, and the sidebar does not show the "Upgrade To PRO" box. Fails if the page goes blank or the console shows React error #152.

**T2. Builder, edit.** Publish the notification from T1, then open it from All NotificationX.
Expected: the edit screen renders, as in T1.

**T3. Build With AI toast.** Leave Settings > API Integrations > OpenAI key empty. In a Notification Bar, go to Design > Build With AI, type a prompt and click the send button.
Expected: a red toast at the bottom right says the OpenAI token is not configured. It closes on click and closes by itself after about 5 seconds, and the builder stays usable. Repeat for Exit Intent Popup > Build With AI.

**T4. License tab.** Settings > License.
Expected: the license form renders. Fails if the page goes blank with `useSyncExternalStore is not a function`.

**T5. Free toast.** Settings > General > Save Settings.
Expected: a "Changes Saved Successfully." toast appears and closes.

**T6. Other admin pages.** Dashboard, All NotificationX, Analytics, Quick Builder, and every Settings tab.
Expected: each renders, with no console errors.

**T7. Free without Pro.** Deactivate Pro and repeat T1.
Expected: the builder works, and the sidebar shows the "Upgrade To PRO" box.

**T8. Error boundary (site C only).** Put this mu-plugin in `wp-content/mu-plugins/nx-qa-broken-ai.php`:

```php
<?php
// Replaces Pro's Build With AI panel with a component that throws on render.
add_action( 'admin_enqueue_scripts', function () {
    wp_add_inline_script( 'wp-hooks', "wp.hooks.addFilter( 'nx_build_ai_render', 'nx-qa/broken', function () {
        return wp.element.createElement( function Broken() { throw new Error( 'QA: broken Build With AI' ); } );
    }, 99 );", 'after' );
} );
```

Open a Notification Bar > Design > Build With AI.
Expected: the panel shows "This section could not be loaded…", and the steps, preview and sidebar still work. **Delete the mu-plugin afterwards.**

## Automated coverage

`npm run test:js` runs `tests/js/admin/react17-compat.test.js` on React 17. It checks that `ProWidget` renders nothing without throwing when Pro is active, and that the error boundary keeps a render error inside its panel.
