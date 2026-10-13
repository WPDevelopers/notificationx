# Test Plan: deactivation feedback form layout fix

**Audience:** QA tester checking the next NotificationX (free) release candidate.
**Release under test:** the next free release that carries the change to `includes/Admin/PluginInsights.php`. Rename this file to `free-<version>-deactivation-feedback-form.md` once the version number is fixed.

## What changed

The "Sorry to see you go" dialog on **Plugins → Installed Plugins** had layout and behaviour bugs. Background: [../features/deactivation-feedback-form.md](../features/deactivation-feedback-form.md).

| Before | After |
| --- | --- |
| Admin menu and admin bar stayed bright and clickable above the dark overlay | Overlay covers the whole screen, including the admin menu and admin bar |
| **Just Deactivate** looked like a plain blue admin link | **Just Deactivate** is a grey, underlined text link |
| **Submit and Deactivate** did not show the "Submitting form" spinner | The reasons and buttons fade out and the spinner shows until the redirect |
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
Expected: **Submit and Deactivate** is a pink button on the left. **Just Deactivate** is a grey, underlined link on the right. Hovering darkens each one.

**T6. Submit with a reason.** Open the dialog, select **I found a better plugin**, click **Submit and Deactivate**.
Expected: the reasons and buttons fade out and "Submitting form" with a spinner shows. Then the page reloads with NotificationX deactivated.
Fails if: the dialog stays unchanged until the reload, or the plugin is still active.

**T7. Submit without a reason.** Reactivate NotificationX. Open the dialog and click **Submit and Deactivate** without selecting a reason.
Expected: same as T6.

**T8. Just Deactivate.** Reactivate NotificationX. Open the dialog and click **Just Deactivate**.
Expected: the page reloads with NotificationX deactivated. No spinner shows.

**T9. Failed request still deactivates.** Reactivate NotificationX. In DevTools **Network**, block the request URL pattern `*admin-ajax.php*`. Open the dialog and click **Submit and Deactivate**.
Expected: the spinner shows, then the page reloads with NotificationX deactivated. Remove the block afterwards.

**T10. Other WPDeveloper plugin.** Click **Deactivate** under the other WPDeveloper plugin from Setup step 2.
Expected: that plugin's own dialog opens. The NotificationX dialog does not open. Close it, then repeat T1: only the NotificationX dialog opens.

**T11. Narrow screen.** Set the browser width to 375 px (DevTools device toolbar) and repeat T1.
Expected: the dialog has a small gap on each side and fits in the screen. If the screen is short, the dialog content scrolls inside the dialog.

**T12. Another admin colour scheme.** In **Users → Profile**, choose a different admin colour scheme and repeat T1 and T5.
Expected: same result as T1 and T5.
