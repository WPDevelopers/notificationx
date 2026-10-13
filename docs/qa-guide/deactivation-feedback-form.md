# Test Plan: deactivation feedback form layout fix

**Audience:** QA tester checking the next NotificationX (free) release candidate.
**Release under test:** the next free release that carries the change to `includes/Admin/PluginInsights.php`. Rename this file to `free-<version>-deactivation-feedback-form.md` once the version number is fixed.

## What changed

The "Sorry to see you go" dialog on **Plugins → Installed Plugins** had layout and behaviour bugs. Background: [../features/deactivation-feedback-form.md](../features/deactivation-feedback-form.md).

| Before | After |
| --- | --- |
| Admin menu and admin bar stayed bright and clickable above the dark overlay | Overlay covers the whole screen, including the admin menu and admin bar |
| Pink **Submit and Deactivate** button, **Just Deactivate** looked like a plain blue admin link | Same layout as the Better Payment form: rounded button in the NotificationX brand colour (`#6A4BFF`), purple radio buttons, light grey underlined **Just Deactivate** |
| The dialog also opened on sites with NotificationX Pro active | No dialog while Pro is active |
| A "Submitting form" spinner was in the markup but never showed | No loading state: the dialog stays as it is until the page reloads. Clicking **Submit and Deactivate** twice sends one request |
| A failed submit request left the dialog open | The plugin deactivates even if the request fails |
| The page behind the dialog scrolled | The page behind the dialog does not scroll |

Automated coverage: `tests/test-deactivation-feedback-form.php`.

## Setup

1. Test site with the free release candidate active. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
2. Activate at least one other WPDeveloper plugin that has the same dialog, for example Essential Blocks or EmbedPress.
3. Keep the DevTools **Console** open. Any JavaScript error is a failure. Check `wp-content/debug.log` after each part: any PHP warning or notice from `notificationx` is a failure.

## Cases

**T1. Overlay covers the whole screen.** Desktop browser, at least 1280 px wide. Go to **Plugins** and click **Deactivate** under NotificationX.
Expected: the dialog is centred. The page, the left admin menu and the top admin bar are all dimmed. Hovering the admin menu or admin bar does not highlight anything.
Fails if: the admin menu or admin bar is not dimmed, or reacts to hover or click.

**T2. Close by clicking outside.** From T1, click the dark area outside the dialog.
Expected: the dialog closes. The page scrolls again. NotificationX is still active.

**T3. Reopen.** From T2, click **Deactivate** under NotificationX again.
Expected: the dialog opens once, with no reason selected. Clicking outside closes it as in T2.

**T4. Page behind does not scroll.** Open the dialog. Scroll with the mouse wheel or trackpad.
Expected: the plugins list behind the dialog does not move.

**T5. Footer links.** Open the dialog.
Expected: **Submit and Deactivate** is a button in the NotificationX brand colour (`#6A4BFF`, same as the **Add New** button) with rounded corners on the left. **Just Deactivate** is a light grey, underlined link on the right. Selecting a reason fills its radio button with a purple dot. Open Better Payment's dialog (if installed) and compare: both look the same except the submit button colour.

**T6. Submit with a reason.** Open the dialog, select **I found a better plugin**, click **Submit and Deactivate**.
Expected: the dialog stays as it is, with no spinner or "Submitting form" text. Then the page reloads with NotificationX deactivated.
Fails if: a spinner shows, the dialog content disappears before the reload, or the plugin is still active.

**T7. Double click.** Reactivate NotificationX. In DevTools **Network**, filter by `admin-ajax`. Open the dialog and double-click **Submit and Deactivate**.
Expected: one `admin-ajax.php` request with `action=deactivation_form_notificationx`, then the page reloads with NotificationX deactivated.

**T8. Submit without a reason.** Reactivate NotificationX. Open the dialog and click **Submit and Deactivate** without selecting a reason.
Expected: same as T6.

**T9. Just Deactivate.** Reactivate NotificationX. Open the dialog and click **Just Deactivate**.
Expected: the page reloads with NotificationX deactivated.

**T10. Failed request still deactivates.** Reactivate NotificationX. In DevTools **Network**, block the request URL pattern `*admin-ajax.php*`. Open the dialog and click **Submit and Deactivate**.
Expected: the page reloads with NotificationX deactivated. Remove the block afterwards.

**T11. Other WPDeveloper plugin.** Click **Deactivate** under the other WPDeveloper plugin from Setup step 2.
Expected: that plugin's own dialog opens. The NotificationX dialog does not open. Close it, then repeat T1: only the NotificationX dialog opens.

**T12. Narrow screen.** Set the browser width to 375 px (DevTools device toolbar) and repeat T1.
Expected: the dialog has a small gap on each side and fits in the screen. If the screen is short, the dialog content scrolls inside the dialog.

**T13. Another admin colour scheme.** In **Users → Profile**, choose a different admin colour scheme and repeat T1 and T5.
Expected: same result as T1 and T5.

**T14. No form with NotificationX Pro.** Activate NotificationX Pro. Go to **Plugins** and click **Deactivate** under NotificationX.
Expected: no dialog opens and the Deactivate link works like any other plugin's. Reactivate NotificationX afterwards.
Fails if: the "Sorry to see you go" dialog opens.
