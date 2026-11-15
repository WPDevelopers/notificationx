# Test Plan: credentials in the builder payload

**Audience:** QA tester running the NotificationX and NotificationX Pro release candidates that carry the builder credentials fix.
**Release under test:** the next free and Pro releases built from branch `fix/builder-secret-redaction` (both cut from `dev`). Rename this file to `free-<version>-pro-<version>-builder-settings.md` once the version numbers are fixed.

> Security fix, not public yet. Don't paste the steps below into public issues, PRs or chat until the release is out.

## What changed

Any role an admin adds under **NotificationX → Settings → Advanced Settings → Role Management** ("Who Can View Notification?", "Who Can Create Notification?") loads `GET /wp-json/notificationx/v1/builder`, which also carries the settings screen. Before this fix that response gave those users:

- the YouTube OAuth access and refresh token (`yt_settings.token_info`),
- the Freemius Developer Secret Key (`freemius_dev_sk`),
- the integration API key of Zapier, IFTTT and the Brevo webhook (the default value of those settings fields). With this key anyone, logged out, can add fake entries to any Zapier or IFTTT notification,
- every other stored setting nobody had marked as secret (for example keys left behind by older versions, and the reporting email).

| Plugin | Change | What you can see |
|---|---|---|
| Free | Users without "Who Can Edit Settings?" get no settings form and only the few settings values the builder needs (Build with AI model options) | Nothing; they could never open the settings screen |
| Free | `yt_settings`, `freemius_dev_sk` and `brevo_webhook_url` are treated as secrets: left out of settings exports, kept on import and on save | Settings export file is smaller |
| Free | The MCP "Get settings" ability leaves out the same secrets | Nothing |
| Pro | Registers those three keys as secrets itself (for older free versions) | Nothing |
| Pro | The Zapier, IFTTT and Brevo webhook fields carry the key only for users who can edit settings | Nothing for admins |
| Pro | The Brevo webhook URL is no longer saved into settings; it is rebuilt from the key on every page load | Nothing |
| Pro | The IFTTT setup panel in the builder points to the settings screen instead of printing the key | Panel shows a link instead of the key |

Either plugin can be released first: new Pro with older free also closes the leak, and new free with older Pro also closes it. Release both.

Automated coverage: free `tests/test-security-builder-settings-payload.php` and `tests/test-security-settings-boundary.php`, Pro `tests/test-security-builder-secrets.php` (PHPUnit), free `tests/e2e/builder-settings-redaction.js` (browser).

## Setup

1. Test site with the free and Pro release candidates, **Pro licence active** (role management is a Pro feature). Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
2. Users: one **administrator**, one **author**, one **editor**, one **subscriber**.
3. As administrator, in **Settings → Advanced Settings → Role Management**: add **Author** to "Who Can View Notification?" and **Editor** to "Who Can Create Notification?". Save.
4. In **Settings → General → Modules**, turn on Zapier, Brevo, Freemius and YouTube. Save.
5. Credentials. YouTube no longer has a connect button, so store a token the way older versions did, plus a Freemius secret key:
   ```
   wp eval '$s = \NotificationX\Admin\Settings::get_instance();
   $s->set("settings.yt_settings", ["token_info" => ["access_token" => "QA-YT-ACCESS", "refresh_token" => "QA-YT-REFRESH"], "auth_code" => "QA-YT-CODE", "yt_app_type" => "nx_app"]);
   $s->set("settings.freemius_dev_sk", "QA-FREEMIUS-SK");'
   ```
   Remove them after the test: `wp eval '$s = \NotificationX\Admin\Settings::get_instance(); $all = $s->get("settings"); unset($all["yt_settings"], $all["freemius_dev_sk"]); $s->set("settings", $all);'`
6. Note the integration API key: `wp option get nx_integration_api_key`. Below it is called `KEY`.
7. Create a **Zapier** notification (any type), enable it, and note its ID (`Z`).
8. Keep the DevTools **Console** open on every check. After each part, check `wp-content/debug.log`: **any PHP fatal error, warning or notice from `notificationx` is a failure.**

To read the builder response as a user: log in as that user, open **NotificationX → All NotificationX**, then in the DevTools Console run

```js
copy( JSON.stringify( window.notificationxTabs ) )
```

and paste into a text editor. Search the text for: `KEY`, `QA-YT-ACCESS`, `QA-YT-REFRESH`, `QA-YT-CODE`, `QA-FREEMIUS-SK`, and the site's admin email.

## Part 1: users who cannot edit settings

**T1. Author (View role), page data.** As the author, read `window.notificationxTabs` as above.
Expected: none of the searched values appear. `settings.tabs` is `[]`.
Fails if: any of them appears.

**T2. Author, REST.** Still as the author, in the Console:
```js
fetch( '/wp-json/notificationx/v1/builder', { headers: { 'X-WP-Nonce': notificationxTabs.rest.nonce } } ).then( r => r.text() ).then( t => console.log( [ 'KEY', 'QA-YT-ACCESS', 'QA-YT-REFRESH', 'QA-YT-CODE', 'QA-FREEMIUS-SK' ].filter( s => t.includes( s ) ) ) )
```
(replace `KEY` with the real key). Expected: `[]`.

**T3. Author, app still works.** Open **All NotificationX** and **Dashboard**. Expected: both load as before, notifications are listed, no new red errors in the Console.

**T4. Editor (Create role).** As the editor, repeat T1 and T2, then create a notification, edit notification **Z**, and open **Quick Builder**.
Expected: no searched value appears; creating, editing, the builder preview and Quick Builder work as before.

**T5. Settings link from the app.** As the editor, on a Popup notification row click the **entries** link (it points to the settings screen).
Expected: the app goes back to **All NotificationX**, as before. No blank screen, no Console error.

**T6. IFTTT panel.** Only if IFTTT appears as a source in the builder (it is not registered in current free): as the editor, open its setup panel.
Expected: the API Key line links to the settings screen and does not show the key.

**T7. Build with AI (Pro).** As the editor, open **Build with AI** for a notification.
Expected: same as before the update. (The OpenAI access token was already hidden from this role, so the panel asks for it on the settings screen.)

## Part 2: administrator

**T8. Settings screen.** As administrator, open **Settings → API Integrations**.
Expected: Zapier shows the API Key (= `KEY`); Brevo, with "Real-time Updates (Webhook)" on, shows the Webhook URL ending in `api_key=KEY`; Freemius shows the Developer Secret Key `QA-FREEMIUS-SK`. All other integrations show their saved values as before.

**T9. Save keeps credentials.** Change any harmless setting (for example Cache Limit) and click **Save Settings**. Reload.
Expected: every integration still shows its key; `wp eval 'var_dump( \NotificationX\Admin\Settings::get_instance()->get("settings.yt_settings") );'` still shows the QA tokens; the Freemius secret key is unchanged.

**T10. Brevo webhook URL is not stored.** After T9: `wp eval 'var_dump( \NotificationX\Admin\Settings::get_instance()->get("settings.brevo_webhook_url") );'`
Expected: `bool(false)`. The settings screen still shows the URL (T8).

**T11. Editing a key still works.** Change the Freemius Developer Secret Key to `QA-FREEMIUS-SK2`, click **Refresh** in the Freemius section or save. Reload.
Expected: the new value is shown and stored. Put it back afterwards.

## Part 3: export and import

**T12. Export.** As administrator, **Settings → Miscellaneous → Export Settings**, export and open the JSON.
Expected: no `yt_settings`, no `freemius_dev_sk`, no `brevo_webhook_url`, and no other API key or token (as before). Ordinary settings (modules, cache limit) are there.

**T13. Re-import.** Import the file from T12.
Expected: import succeeds; afterwards T8 still shows every key and T9's `wp eval` still shows the QA tokens. A redacted import must not blank stored credentials.

## Part 4: integrations keep working

**T14. Zapier / webhook with the existing key.**
```
curl -sk -X POST "https://site.test/wp-json/notificationx/v1/notification/Z?api_key=KEY" --data "name=QA Tester&title=QA Product"
```
Expected: the response has `"error":false` and the entry appears in notification **Z** (front end). Delete the entry afterwards.
With a wrong key (`api_key=wrong`) expected: `{"data":"","error":"Error: Invalid API key."}` and no entry. (The status code is 200 in both cases, as before.)

**T15. Brevo webhook.** With "Real-time Updates (Webhook)" on:
```
curl -sk -o /dev/null -w "%{http_code}\n" -X POST "https://site.test/wp-json/notificationx/v1/brevo-webhook?api_key=KEY" -H "Content-Type: application/json" --data '{}'
```
Expected: `200` (the key is accepted; the empty body is ignored, nothing is fetched). With `api_key=wrong`: `401`. With the webhook toggle off: `404`, as before.

**T16. Real Zapier.** Optional, if a Zapier account is connected: trigger the Zap.
Expected: the entry arrives as before; nothing to change on the Zapier side.

## Part 5: who gets the builder at all

**T17. Subscriber and visitor.**
```
curl -sk -o /dev/null -w "%{http_code}\n" https://site.test/wp-json/notificationx/v1/builder
```
Expected: `401`. A subscriber gets `403`; that case is covered by PHPUnit (`test_subscriber_and_visitor_still_get_403_on_builder`). The subscriber also has no NotificationX menu, as before.

## Part 6: version matrix

**T18. New Pro, older free.** Replace free with the last released free, keep the new Pro. Repeat T1 and T4.
Expected: `KEY`, `QA-YT-*` and `QA-FREEMIUS-SK` do not appear. (Other settings values may still appear with an older free; that part is fixed in free.)

**T19. New free, older Pro.** New free, last released Pro. Repeat T1, T4, T8, T12.
Expected: T1/T4 as in Part 1; T8 and T12 as above. The IFTTT panel (T6) may still show the key with the older Pro.

## Part 7: automated

**T20.** On a local copy of the site, from the free plugin folder:
```
node tests/e2e/builder-settings-redaction.js
```
It creates two temporary users (`nx_e2e_viewer`, `nx_e2e_creator`) and deletes them when it finishes.
Expected: every line starts with ✔ and the exit code is 0. Then check `wp user list` shows neither temporary user.

**T21. Logs.** `debug.log` has no new entries from `notificationx` after all parts.
