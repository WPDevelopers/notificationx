# Test Plan: Free 3.3.5 + Pro 3.2.5 (WordPress.org compliance, issue #184)

**Audience:** QA tester running the NotificationX 3.3.5 and NotificationX Pro 3.2.5 release candidates.
**Release under test:** free 3.3.5 with Pro 3.2.5. Some parts also use free without Pro, and Pro 3.2.4.

## What changed

| Area | Change | What you can see |
|---|---|---|
| Usage tracking | Nothing is sent to WPDeveloper until the admin opts in. Before, data was sent from activation, whatever the admin chose | New install: no request to `send.wpinsight.com` until "Allow" or "Get My Recommendations" |
| Setup Wizard, Welcome step | New **Skip** link next to "Get My Recommendations". The notice says what is shared, with a **What do we collect?** toggle | "Skip" and "I'll do this Manually" send nothing |
| Deactivation feedback form | Shown only on sites that opted in | Not opted in: **Deactivate** deactivates straight away |
| Exit Intent themes two, five, six, seven | The code moved to Pro | With Pro 3.2.5: **nothing changes**. Without Pro, or with Pro 3.2.4: these Exit Intent notifications do not show on the frontend |
| YouTube subscribe button | Moved to Pro | With Pro 3.2.5: nothing changes |
| "Powered by NotificationX" | Hidden by default. Shown only after unticking **Settings → Advanced → Disable Powered By** | New install: no "Powered by" link |
| Fonts and icons | Open Sans, Font Awesome 4.7 (frontend), DM Sans, Inter (admin) are bundled | No request to `fonts.googleapis.com`, `fonts.gstatic.com` or `cdnjs.cloudflare.com` on WordPress pages |
| Exit Intent default images | Bundled in the plugin | No request to `notificationx.com/wp-content/uploads` on the frontend, also for Exit Intent notifications created before 3.3.5 |
| Country Targeting | Uses `https://api.country.is/` instead of `http://ip-api.com/` | Request goes over HTTPS |

Developer reference: [../api/frontend-js-hooks.md](../api/frontend-js-hooks.md) ("Free 3.3.5 (issue #184)").

## Setup

1. **Test site:** free 3.3.5 + Pro 3.2.5. **Comparison site:** free 3.3.4 + Pro 3.2.4 with the same notifications.
2. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`. Make sure `NX_DEBUG` is not defined.
3. Keep the DevTools **Console** and **Network** tabs open on every frontend check. **Any red Console error that mentions NotificationX or React is a failure.** Check `wp-content/debug.log` after each part: **any PHP fatal error, warning or notice from `notificationx` is a failure.**
4. To see outgoing server requests, install a request logger such as the "Log HTTP Requests" plugin.

---

## Part A: Usage tracking consent (free only, no Pro)

| # | Steps | Expected |
|---|---|---|
| A1 | Fresh site. Install and activate free 3.3.5 | The Setup Wizard opens. No request to `send.wpinsight.com` in the request log. `wp cron event list` has no `put_do_weekly_action` added by this activation |
| A2 | On the Welcome step click **What do we collect?** | The list of collected data opens below the notice. Clicking again closes it |
| A3 | Click **Skip** | The wizard goes to the next step. No request to `send.wpinsight.com`. Option `wpins_allow_tracking` has no `notificationx` key |
| A4 | Restart the wizard (`admin.php?page=nx-setup-wizard`), click **I'll do this Manually** | Dashboard opens. No request to `send.wpinsight.com` |
| A5 | Restart the wizard, click **Get My Recommendations** | One request to `send.wpinsight.com`. `wpins_allow_tracking` contains `notificationx`. The payload has no `file_location` and no `inactive_plugins` list (only `inactive_plugins_count`) |
| A6 | On a site that has not opted in, go to **Plugins** and click **Deactivate** under NotificationX | The plugin deactivates without the "Sorry to see you go" form. No request to `send.wpinsight.com` |
| A7 | On a site that opted in (A5), click **Deactivate** | The feedback form opens, as before |
| A8 | Site that ran 3.3.4 and never opted in: update to 3.3.5, wait one day or run `wp cron event run --all` | No request to `send.wpinsight.com` |

## Part B: Exit Intent themes (Pro code)

| # | Steps | Expected |
|---|---|---|
| B1 | Free 3.3.5 + Pro 3.2.5. Create Exit Intent notifications with themes two, five, six and seven. Trigger each one on the frontend | Each looks and acts exactly as on the comparison site: countdown ticks (five, six), CTA and "No thanks" close the popup, theme seven stores the email (check **NotificationX → Entries**) |
| B2 | Same, with themes one, three and four | Same as the comparison site |
| B3 | Deactivate Pro | Exit Intent with themes two, five, six, seven do not show. Themes one, three, four still show. No Console error |
| B4 | Replace Pro 3.2.5 with Pro 3.2.4 | The "update NotificationX Pro" notice shows. Themes two, five, six, seven do not show on the frontend. No fatal error |
| B5 | Pro 3.2.5. Exit Intent linked to an Elementor design, whatever theme is selected | The Elementor design shows, as before |
| B6 | Cross Domain Notice site with an Exit Intent using theme seven | Shows and stores the email, as before |

## Part C: YouTube subscribe button (Pro)

| # | Steps | Expected |
|---|---|---|
| C1 | Pro 3.2.5. YouTube channel notification with **Subscribe button type: Youtube (Default)** | The red YouTube Subscribe button shows, as before. `apis.google.com/js/platform.js` is requested |
| C2 | Same notification with a custom button | A normal link button shows. `platform.js` is not requested |
| C3 | Any page with only free notifications | `platform.js` is never requested |

## Part D: "Powered by"

| # | Steps | Expected |
|---|---|---|
| D1 | Fresh install. Create a sales notification, view it on the frontend | No "Powered by NotificationX" link. In **Settings → Advanced**, **Disable Powered By** is ticked |
| D2 | Untick **Disable Powered By**, save | The link shows on notifications, popups and the GDPR banner |
| D3 | Site updated from 3.3.4 where the settings were saved with the box unticked | The link still shows (the saved choice is kept) |

## Part E: No third-party font, icon or image requests

| # | Steps | Expected |
|---|---|---|
| E1 | Frontend page with a sales notification (theme with icons, for example "Theme Eight"), a notification bar and a GDPR banner. Filter the Network tab by `googleapis`, `gstatic`, `cdnjs` | No match. Open Sans and the Font Awesome icons still render (compare with the comparison site) |
| E2 | Admin: NotificationX dashboard, builder, Setup Wizard. Same filter | No match. DM Sans and Inter render |
| E3 | Exit Intent theme three created on 3.3.4 (saved with the old default image), on 3.3.5. Filter Network by `notificationx.com` | No match. The image shows, loaded from `.../notificationx/assets/public/image/exit-intent/` |
| E4 | Cross Domain Notice site (Pro 3.2.5) | Fonts and icons render. They load from Google Fonts and cdnjs there (on purpose: other origins cannot load the site's font files) |

## Part F: Country Targeting

| # | Steps | Expected |
|---|---|---|
| F1 | Notification with Country Targeting set to one country. Visit from a non-local IP without Cloudflare | One server request to `https://api.country.is/<ip>`. The notification shows only for that country |
| F2 | Visit again within 12 hours | No new request (cached) |
