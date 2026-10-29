# Test Plan: Zapier / IFTTT legacy API key grace window

**Audience:** QA tester running the next NotificationX free release candidate (built from branch `84902-security-anyone-can-add`), with NotificationX Pro 3.2.5 for the Zapier and IFTTT settings screens. Below, "the fixed version" means that release candidate. Rename this file to `free-<version>-integration-legacy-key.md` once the version number is fixed.

## What changed

`POST /wp-json/notificationx/v1/notification/{id}` is the webhook Zapier and IFTTT call to add an entry. Since 3.2.11 it expects the random key shown in **NotificationX → Settings → API Integration**. The old key, `md5(home_url())`, was still accepted for 14 days after an update whenever the site had a Zapier or IFTTT notification, including on sites that were set up with the random key. Anyone can compute the old key from the site address, so for those 14 days anyone could add fake sales to any notification.

| Change | What you can see |
|---|---|
| The 14-day window opens only on the update that first creates the random key | A site that already has the key and later adds a Zapier notification rejects the old key after every update |
| A window left open by 3.2.11 – 3.3.5 is closed by the update to the fixed version | The old key stops working right after the update; a site that still used it sees the red admin notice |
| A webhook for a notification id that does not exist no longer logs a PHP warning | `debug.log` stays clean |

Automated coverage: `tests/test-security-integration-key.php` (seeding rules) and `tests/test-security-integration-key-rest.php` (real REST requests on both routes).

## Setup

1. Test site with free 3.3.5 and Pro 3.2.5 installed, `WP_DEBUG` and `WP_DEBUG_LOG` on. Keep the fixed version's zip ready to upload.
2. Turn on the **Zapier** module in **NotificationX → Settings → Modules**.
3. Create a **Sales → Zapier** notification, enabled, theme one. Note its id as `Z`.
4. Put the shortcode `[notificationx_inline id=Z]` on a public page, **Page A**, so stored entries are easy to see.
5. In a terminal, compute the old key and keep it as `OLD`:
   ```
   wp eval 'echo md5( home_url( "", "http" ) ), PHP_EOL;'
   ```
6. Copy the random key from **NotificationX → Settings → API Integration → Zapier Settings → API Key** and keep it as `NEW`.

The requests below use `curl`. Replace `https://site.test` and `Z`. Send them **logged out** (no cookies). After each part, check `wp-content/debug.log`: **any PHP warning or notice from `notificationx` is a failure.**

```
POST() { curl -s -X POST "https://site.test/wp-json/notificationx/v1/notification/Z" \
  -H 'Content-Type: application/json' \
  -d "{\"api_key\":\"$1\",\"name\":\"QA\",\"title\":\"QA product $2\"}"; echo; }
```

## Part 1: the reported attack, before and after the update

**T1. Reproduce on 3.3.5.** Still on 3.3.5, make the plugin run its update seeding again, which is what opened the window on every update:
```
wp option delete nx_integration_key_seeded_version
```
Load any admin page once. Then `POST OLD t1`.
Expected on 3.3.5: `{"data":{...},"error":false}` and "QA product t1" shows on Page A. This confirms the test setup; delete the entry afterwards from **NotificationX → Analytics / entries** or with `wp db query "DELETE FROM wp_nx_entries WHERE data LIKE '%QA product%'"`.

**T2. Update to the fixed version closes the window.** Upload the fixed version's zip over 3.3.5 and load any admin page. Then `POST OLD t2`.
Expected: `{"data":"","error":"Error: Invalid API key."}`, nothing on Page A, and
```
wp option get nx_integration_api_key_grace_started_at
```
prints `Error: Could not get 'nx_integration_api_key_grace_started_at' option.`
Fails if: the entry is stored, or the option still exists.

**T3. The new key still works.** `POST NEW t3`.
Expected: `error:false`, "QA product t3" shows on Page A within a minute.

**T4. The un-versioned route behaves the same.** Repeat T2 and T3 with the URL `https://site.test/wp-json/notificationx/notification/Z`.
Expected: same results as T2 and T3.

**T5. Later update does not reopen the window.** Simulate the next update:
```
wp option update nx_integration_key_seeded_version 0.0.1
```
load any admin page, then `POST OLD t5`.
Expected: rejected as in T2.

## Part 2: a real legacy site keeps working for 14 days

**T6. Legacy site upgrade.** Simulate a site that comes from before the random key existed:
```
wp option delete nx_integration_api_key nx_integration_key_seeded_version nx_integration_api_key_grace_started_at nx_integration_api_key_grace_first_seed nx_integration_legacy_key_last_used_at
```
Load any admin page. Then `POST OLD t6`.
Expected: `error:false` (window open for this site), and `wp option get nx_integration_api_key_grace_first_seed` prints `1`.

**T7. Admin notice.** Log in as administrator and open the Dashboard.
Expected: a yellow notice "A Zapier (or other webhook) integration is still calling NotificationX with the legacy API key. Rotate it to the new key before <date> …" with a **Get the new key** link to the settings page. The date is 14 days from today.

**T8. The window survives the next update.** Run `wp option update nx_integration_key_seeded_version 0.0.1`, load any admin page, `POST OLD t8`.
Expected: still accepted.

**T9. The window expires.**
```
wp option update nx_integration_api_key_grace_started_at $(( $(date +%s) - 15*86400 ))
```
`POST OLD t9`.
Expected: rejected. The Dashboard notice turns red: "Those calls are now being rejected — update the integration with the new key to restore it."

## Part 3: no regressions

**T10. Fresh install never opens the window.** On a fresh site (or after `wp option delete` of the five options above and with no Zapier / IFTTT notification), load any admin page, create the Zapier notification, `POST OLD t10`.
Expected: rejected; `nx_integration_api_key_grace_started_at` does not exist.

**T11. Unknown notification id.** `POST NEW` against `/notification/999999`.
Expected: `error:false`, nothing stored, and **no** `Trying to access array offset on value of type null` line in `debug.log`.

**T12. Settings screens.** Open **NotificationX → Settings → API Integration**. The Zapier and IFTTT API key fields show the random key (32 letters and digits), not a 32-character hex string.

**T13. Zapier end to end (if a Zapier account is available).** Create a Zap with the NotificationX action using `NEW` and fire it.
Expected: the entry appears on Page A.

## Cleanup

Delete the QA entries, Page A and the Zapier notification. Remove the inline shortcode page.
