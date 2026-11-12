# Test Plan: no views written with analytics off

**Audience:** QA tester running the NotificationX (free) release candidate that carries this change.
**Release under test:** the next free release built from branch `fix/214-builder-field-names-cache` (cut from `dev`). NotificationX Pro is not changed.

## What changed

NotificationX counts a view each time a notification is sent to a visitor. The count is written while the page asks for its notifications (`/wp-json/notificationx/v1/notice`).

With **Settings → General → Analytics → Enable Analytics** switched off, the Analytics page, the dashboard widget, the report email and click counting are all off. Views were still written: one database update per notification on every page view, for numbers nobody could see.

| Before | After |
|---|---|
| Analytics off: views are still written, clicks are not | Analytics off: nothing is written |
| Analytics on: views and clicks are written | Unchanged |

One visible difference: a site that turns analytics off and later on again will no longer find views for the time it was off. Before, that period showed views with zero clicks.

Automated coverage: `tests/test-analytics-disabled.php` (PHPUnit), `tests/e2e/analytics-disabled.js` (real requests against a site). The change is tracked in issue #214.

Background, measurements and what is still open for issue #214: [../features/notice-request-performance.md](../features/notice-request-performance.md).

## Setup

1. Test site with the free release candidate. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`, and empty `wp-content/debug.log`.
2. Two enabled notifications that show on the home page: a Notification Bar and one other type with entries (for example a Sales Notification or Comments). Note their IDs (`B` and `N`).
3. **Settings → General → Analytics**: **Enable Analytics** on, **Analytics From** = Everyone, **Exclude Bot Analytics** off.
4. WP-CLI access. This prints today's views for a notification; replace `ID`:
   ```
   wp eval 'echo (int) \NotificationX\Core\Analytics::get_instance()->get_count( ID, "views" ), "\n";'
   ```
   Below this is called the **view count**. If a notification has rows for several days, compare the totals on the Analytics page instead.

## Cases

### Analytics on (unchanged)

**T1. Views are counted.** Note the view count of `B` and `N`. Open the home page logged out, wait until both notifications have shown, reload once.
Expected: each view count went up by 2.

**T2. Clicks are counted.** Click the notification `N` (or the button of bar `B`). Open **NotificationX → Analytics**.
Expected: the click shows for that notification.

### Analytics off

Switch **Enable Analytics** off and save.

**T3. Notifications still show.** Open the home page logged out.
Expected: both notifications show exactly as in T1.
Fails when: a notification is missing or looks different, or `debug.log` has a new entry.

**T4. No views are written.** Note the view counts. Reload the home page five times, logged out.
Expected: the view counts did not change.
Fails when: any view count went up.

**T5. No clicks are written.** Click a notification. Run the view count command with `"clicks"` in place of `"views"`.
Expected: the click count did not change. (This was already the case before the change.)

**T6. The analytics screens are off.** In wp-admin there is no **Analytics** submenu and no NotificationX dashboard widget.
Expected: as before the change.

### Analytics on again

Switch **Enable Analytics** on and save.

**T7. Counting resumes.** Reload the home page twice, logged out.
Expected: each view count went up by 2 from the value noted in T4. The Analytics page shows the earlier numbers plus the new ones; the views from T4's reloads are not there.

### Other settings still apply

**T8. Analytics From.** Set **Analytics From** to **Guests Only**. Reload the home page while logged in as an administrator, then once logged out.
Expected: only the logged-out view is counted. Set it back to Everyone.

**T9. Inline notifications (Pro).** If the site has a WooCommerce inline notification on a product page, open that page with analytics on and off.
Expected: the inline text shows both times, and `debug.log` has no new entry.

## Automated checks

Run these on the development site, not on the QA site:

```
vendor/bin/phpunit --filter 'Test_Analytics_Disabled'
node tests/e2e/analytics-disabled.js
```

Expected: PHPUnit reports no failures; the e2e script prints `All checks passed.` It creates one temporary Notification Bar and deletes it, and puts **Enable Analytics** back to its original value.

## Reporting

Report results as a list of case ids (`T1 pass, T4 fail: B went from 12 to 17`).
