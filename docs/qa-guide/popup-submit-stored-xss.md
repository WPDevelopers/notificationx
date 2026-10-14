# Test Plan: popup-submit stored XSS fix

**Audience:** QA tester running the NotificationX and NotificationX Pro release candidates that carry the security fix.
**Release under test:** the next free and Pro releases built from branch `fix/popup-submit-stored-xss`. Rename this file to `free-<version>-pro-<version>-popup-submit-xss.md` once the version numbers are fixed.

## What changed

Wordfence reported "NotificationX <= 3.3.3 - Unauthenticated Stored Cross-Site Scripting via 'name' Parameter via popup-submit REST Route". Anyone could post a form entry into any notification, including a WordPress.org Reviews notification. The inline shortcode and the NotificationX block then printed the entry's `name` as live HTML for every visitor.

| Plugin | Change | What you can see |
|---|---|---|
| Free | `POST /wp-json/notificationx/v1/popup-submit` accepts entries only for a Popup or Exit Intent notification, enabled or disabled | Any other notification ID gets `404 Notification not found` and nothing is stored |
| Free | WordPress.org Reviews names have tags removed after decoding | Plugin names with `&` still show `&`, not `&amp;` |
| Free | A submission without a title no longer logs `Undefined array key "title"` | The entry title is "Popup Submission" |
| Pro | `[notificationx_inline]` and the NotificationX block escape every entry value | Markup inside an entry shows as text, not as a live element |

Automated coverage: free `tests/test-security-popup-submit.php`, Pro `tests/test-security-inline-escaping.php`.

## Setup

1. Test site with the free and Pro release candidates. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
2. In **NotificationX → Settings → Modules**, turn on WordPress, Popup and Exit Intent.
3. Create these notifications and note each ID (shown in the notification list):
   - **R:** Reviews → WordPress.org, any plugin slug (for example `notificationx`). Enabled.
   - **P:** Popup, theme four (has the name, email and message fields). Enabled.
   - **E:** Exit Intent, theme one. Enabled.
   - **D:** a second Popup, theme four. **Disabled.**
4. Create a public page **Page A** with the NotificationX block set to **R**, and a page **Page B** with the shortcode `[notificationx_inline id=R]` (replace `R` with the ID).
5. Keep the DevTools **Console** open on every frontend check. Check `wp-content/debug.log` after each part: **any PHP fatal error, warning or notice from `notificationx` is a failure.**

The attack requests below use `curl`. Replace `https://site.test` with the test site URL and `R` with the ID. Send them **logged out** (no cookies).

## Part 1: attack is blocked

**T1. Submission to a Reviews notification is refused.**
```
curl -s -X POST https://site.test/wp-json/notificationx/v1/popup-submit \
  -d nx_id=R -d 'name=&lt;img src=x onerror=alert(1)&gt;'
```
Expected: HTTP 404 with `{"success":false,"message":"Notification not found"}`.
Then check: `wp db query "SELECT COUNT(*) FROM wp_nx_entries WHERE nx_id=R AND entry_key LIKE 'wp_reviews_%' AND data LIKE '%onerror%'"` returns `0`.
Fails if: the response is 200, or a row is stored.

**T2. Pages stay clean.** Open Page A and Page B logged out and logged in as administrator.
Expected: no alert, no broken image, no Console error.

**T3. Disabled popup is accepted.** Repeat T1 with `nx_id=D`.
Expected: HTTP 200 and one entry stored for `D`, shown only in **NotificationX → Feedback Entries** (the markup shows as text there). Delete that entry afterwards.

**T4. Unknown ID is refused.** Repeat T1 with `nx_id=999999`.
Expected: 404.

## Part 2: escaping (Pro)

This part puts a malicious row straight into the database, the way an old injection would have left it.

**T5. Stored markup renders as text.**
```
wp eval 'global $wpdb; $wpdb->insert( $wpdb->prefix."nx_entries", [ "nx_id" => R, "source" => "wp_reviews", "entry_key" => "qa_xss", "data" => maybe_serialize( [ "name" => "&lt;img src=x onerror=alert(1)&gt;", "timestamp" => time() + 600 ] ), "created_at" => current_time("mysql"), "updated_at" => current_time("mysql") ] );'
```
Open Page A and Page B.
Expected: the notification shows the plain text `<img src=x onerror=alert(1)>` (or nothing for that field). No alert, no image element in DevTools **Elements**.
Fails if: an alert opens or an `<img>` element appears inside `notificationx-shortcode-inline-R`.

Clean up afterwards: `wp db query "DELETE FROM wp_nx_entries WHERE entry_key='qa_xss'"`.

**T6. Real reviews still look right.** Wait for the review sync (about 3 minutes) or save **R** again.
Expected: Page A and Page B show the real plugin name and rating stars. A plugin or reviewer name with `&` or an apostrophe shows that character, not `&amp;` or `&#039;`.

**T7. Links still work.** In **R**, set **Link Type** to a value other than None and turn on **Open in new tab**. Open Page B.
Expected: the plugin name is a working link and opens in a new tab.

## Part 3: no regressions

**T8. Popup submission.** On a page that shows **P**, submit name, email and message.
Expected: success message. The entry appears in **NotificationX → Feedback Entries** with the right name, email and message.

**T9. Exit Intent submission.** Trigger **E** (move the mouse out of the window) and submit the email.
Expected: success message. The entry appears in Feedback Entries.

**T10. Popup themes five to seven.** Repeat T8 with Popup themes five, six and seven.
Expected: all save.

**T11. Elementor form widget.** With Elementor active, add the **NotificationX Form** widget bound to **P** and submit it.
Expected: the entry appears in Feedback Entries.

**T12. CSV export.** In Feedback Entries, export the entries.
Expected: same columns and values as before the update.

**T13. Elementor form bound to a disabled popup.** Add the **NotificationX Form** widget bound to **D** to a page. Visit it logged out and submit Name, Email and Message.
Expected: "Thanks! Your submission has been received." and the entry appears in Feedback Entries. Repeat with a disabled Exit Intent campaign: it saves too.
Fails if: the visitor sees "Notification not found".

## Part 4: version matrix

Run T1, T2 and T8 on each combination:

| Free | Pro | Expected |
|---|---|---|
| new release | new release | All pass |
| new release | none | All pass. The shortcode renders nothing without Pro |
| new release | 3.2.4 | T1 refused. T5 shows nothing for the name, because free removes the tags from Reviews names. Pro 3.2.4 itself does not escape, so other fields stay unprotected until Pro is updated |
| 3.3.3 | new release | T1 **accepted** (old free has no gate), T5 escaped by Pro. Note this for the release notes: users must update free |

## Report

List the case ids with pass or fail. For a fail, add the page, the browser, the Console error and any `debug.log` lines.
