# Test Plan: builder preview access fix

**Audience:** QA tester running the NotificationX and NotificationX Pro release candidates that carry the preview access fix.
**Release under test:** the next free and Pro releases built from branch `fix/preview-access-gate` (both cut from `master`). Rename this file to `free-<version>-pro-<version>-preview-access.md` once the version numbers are fixed.

> Reported privately through Patchstack and not yet public. Don't paste the requests below into public issues, PRs or chat until the release is out.

## What changed

The builder's **Preview** button POSTs the notification's settings (`nx-preview`) to a front-end page in an iframe, and that page renders them. Before this fix, the front end accepted that POST from anyone. A logged-out visitor could send their own settings and have the site run any shortcode, or render a draft, private or password-protected post as a notification bar.

| Plugin | Change | What you can see |
|---|---|---|
| Free | A preview request needs the `nx-preview-nonce` nonce and the `edit_notificationx` capability | Preview works as before for admins; anyone else gets the normal page |
| Free | The builder sends the nonce (`notificationxTabs.preview_nonce`) | Nothing visible |
| Free | The NotificationX Inline block in a block template no longer leaves preview mode on for the whole page | Links and forms on such pages work for visitors |
| Free | A Gutenberg bar renders only from a published bar post without a password (editors still see their own drafts); an Elementor bar only from an `nx_bar` post | Nothing visible for normal bars |
| Pro | Build with AI (Exit Intent) preset previews send the nonce | Preset previews still show the popup |

**Release both together.** New free with an older Pro: the Exit Intent Build with AI preset cards show the page without the popup. Older free with new Pro works.

Automated coverage: free `tests/test-security-preview-gate.php` (PHPUnit), `tests/e2e/builder-preview.js` and `tests/e2e/compare-frontend-bundles.js` (browser).

## Setup

1. Test site with the free and Pro release candidates. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
2. Users: one **administrator**, one **editor**, one **subscriber**.
3. Notifications, note each ID:
   - **G:** GDPR notification. Enabled.
   - **S:** Sales notification (any source with sample data). Enabled.
   - **B:** Notification Bar built with Gutenberg. Enabled, shown on all pages.
   - **E:** Notification Bar built with Elementor (needs Elementor active). Enabled.
4. Keep the DevTools **Console** open on every check. After each part, check `wp-content/debug.log`: **any PHP fatal error, warning or notice from `notificationx` is a failure.**

The requests below use `curl`. Replace `https://site.test` with the test site URL.

## Part 1: preview still works

**T1. Builder preview.** As administrator, open **G** and **S** in the builder and click **Preview**. Switch desktop / tablet / phone.
Expected: the notification shows in the preview each time, as before. Clicking links inside the preview does nothing (as before).
Fails if: the preview shows the page without the notification.

**T2. Builder preview as a non-admin role.** In the NotificationX role settings, let the editor role create and edit notifications. Log in as the editor and repeat T1.
Expected: same as T1.

**T3. Build with AI (Pro, Exit Intent).** Open an Exit Intent notification, open **Build with AI**, generate presets and open the history.
Expected: every preset card shows the popup.
Fails if: a card shows the page without the popup.

**T4. Builder tab left open.** Optional. Leave the builder open for more than 24 hours, then click Preview.
Expected: the nonce has expired, so the preview may show the page without the notification. Reload the builder and the preview works again. Only report a failure if Preview fails right after a reload.

## Part 2: preview is refused for everyone else

Build a payload once:

```
PAYLOAD=$(printf '%s' '{"source":"press_bar","type":"notification_bar","themes":"press_bar_theme-one","global_queue":false,"press_content":"QA_PREVIEW_MARKER"}' | base64)
```

**T5. Logged out.**
```
curl -sk -X POST --data-urlencode "nx-preview=$PAYLOAD" https://site.test/ | grep -c QA_PREVIEW_MARKER
```
Expected: `0`.

**T6. Logged out with a made-up nonce.** Add `--data nx-preview-nonce=abcdef1234` to T5.
Expected: `0`.

**T7. Subscriber.** Log in as the subscriber in the browser, copy the `wordpress_logged_in_*` cookie, and repeat T5 with `-b 'wordpress_logged_in_...=...'`.
Expected: `0`.

**T8. Administrator without the nonce.** Same as T7 with the administrator's cookie.
Expected: `0`. (The nonce is what stops a third-party site from making an admin's browser send a preview.)

**T9. Automated.** On a local copy of the site, from the free plugin folder:
```
npm run admin
node tests/e2e/builder-preview.js G S        # use the real IDs
```
Expected: every line starts with ✔ and the exit code is 0.

## Part 3: bars

**T10. Gutenberg bar.** Open the front end logged out and logged in.
Expected: bar **B** shows with its content, as before.

**T11. Draft Gutenberg bar.** Find the bar post ID: `wp post list --post_type=nx_bar_eb --fields=ID,post_title`. Set it to draft: `wp post update <ID> --post_status=draft`. Open the front end logged out.
Expected: bar B shows without its block content. Logged in as administrator the content still shows. Afterwards run `wp post update <ID> --post_status=publish`.
Note for release notes: before this fix, a draft bar post still showed its content to visitors.

**T12. Elementor bar.** Open the front end with bar **E** enabled.
Expected: bar E shows, as before.

**T13. Notification Bar builder preview.** In the builder for **B** and **E**, check the in-builder bar preview (the bar type has no Preview button).
Expected: as before.

## Part 4: Inline block in a block template

Needs a block theme (Twenty Twenty-Five) and Pro with an Inline notification.

**T14.** In **Appearance → Editor**, add the NotificationX Inline block to the single post template and save. Open a post on the front end logged out.
Expected: links, the search form and the comment form on the page work, and the page has no `<script>` that cancels every click (before the fix, preview mode stayed on for the rest of such a page, which adds that script). The block shows what it showed before.

## Part 5: other

**T15. Query Monitor.** With Query Monitor active, open a builder Preview as administrator.
Expected: the preview has no Query Monitor panel. On normal front-end pages Query Monitor shows as usual.

**T16. Logs.** `debug.log` has no new entries from `notificationx` after all parts.
