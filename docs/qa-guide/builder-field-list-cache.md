# Test Plan: field list cache on the frontend

**Audience:** QA tester running the NotificationX (free) release candidate that carries this change.
**Release under test:** the next free release built from branch `fix/214-builder-field-names-cache` (cut from `dev`). NotificationX Pro is not changed; test with the current Pro active and without Pro.

## What changed

Every page of a site with active notifications sends a `/wp-json/notificationx/v1/notice` request. For each notification it reads, the plugin needs the list of builder fields (name, type, default). That list was read from the cached builder config, a single value of about 840 KB.

Some sites cannot keep a value that large (an object cache with a 1 MB item limit, for example). On those sites the config was rebuilt for every notification on every request, which made the `/notice` request several times slower.

| Before | After |
|---|---|
| The field list is read from the 840 KB builder config, once per notification | The field list has its own cache entry (about 90 KB) and is read once per request |
| A site that cannot keep the large value rebuilds the config for every notification | That site keeps the small list and rebuilds nothing |
| A site that can keep no cache value at all rebuilds the config for every notification | That site rebuilds the config once per request |

Nothing should look different: same notifications, same content, same builder. The change is tracked in issue #214.

Automated coverage: `tests/test-builder-field-names-cache.php` and `tests/test-builder-fields-cache.php` (PHPUnit), `tests/e2e/builder-field-names-cache.js` (real requests against a site).

Background, measurements and what is still open for issue #214: [../features/notice-request-performance.md](../features/notice-request-performance.md).

## Setup

1. Test site with the free release candidate. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`, and empty `wp-content/debug.log`.
2. At least three enabled notifications of different types that show on the home page, for example a Notification Bar, a Sales Notification with entries and a Cookie Notice.
3. WP-CLI access to the site.
4. A way to time the request. This prints the time of one `/notice` request in seconds; replace the site address:
   ```
   curl -sk -o /dev/null -w '%{time_total}\n' -X POST -H 'Content-Type: application/json' \
     -d '{"all_active":true,"deviceType":"desktop"}' \
     'https://SITE/wp-json/notificationx/v1/notice/?frontend=true'
   ```
   Run it five times and take the middle value. Below this is called the **request time**.

## Cases

### Normal site

**T1. Notifications still show.**
Open the home page logged out. Each notification from setup step 2 shows with its usual content and position.
Fails when: a notification is missing, shows default text where it had its own, or `debug.log` has a new entry.

**T2. The two cache entries exist.**
Load the home page, then run:
```
wp transient get nx_builder_field_names --format=json | head -c 200
wp eval 'echo get_transient( "nx_builder_fields" ) ? "large: present\n" : "large: MISSING\n";'
```
Expected: the first prints the start of a JSON object, the second prints `large: present`.

**T3. Note the request time.** Write it down as `A`.

### Site that cannot keep the large value

Add this file as `wp-content/mu-plugins/nx-qa-drop-large.php`. It makes the site behave like one whose cache rejects the large value. **Delete it after T6.**

```php
<?php
// Temporary, for QA only: the large builder config is never returned from the cache.
add_filter( 'transient_nx_builder_fields', '__return_false' );
```

**T4. Notifications still show.** Repeat T1. Same result.

**T5. The request is not slower.** Measure the request time as `B`.
Expected: `B` is close to `A` (within normal variation).
Fails when: `B` is clearly larger than `A`. For reference, without this change the same test gives a request several times slower; on the development machine the handler went from 10 ms to 579 ms with 3 notifications.

**T6. The builder still works.** In wp-admin open **NotificationX → Add New**, pick a type and a source, go through all tabs, save. Open an existing notification and save it.
Expected: every tab shows its fields, saving works. The builder is slower to open with this file in place; that is expected and is the same as before the change.

Delete `wp-content/mu-plugins/nx-qa-drop-large.php` now.

### Cache clearing

**T7. Settings save.** In **NotificationX → Settings → General**, turn one module off and save. Then:
```
wp eval 'var_dump( get_transient( "nx_builder_field_names" ) );'
```
Expected: `bool(false)` right after the save. Load the home page: notifications show, and the command now prints an array. Turn the module back on and save.

**T8. Pro switched on or off.** With Pro active, load the home page. Deactivate Pro, load the home page, then open the builder for a Sales Notification.
Expected: notifications that need no Pro still show; the builder shows Pro options as locked. Activate Pro again: the options are available again, without clearing any cache by hand.

**T9. Elementor switched on or off.** If Elementor is installed: deactivate it, open **Add New → Notification Bar**. Activate it, open the same screen again.
Expected: the "Build with Elementor" option follows Elementor's state both times.

**T10. Update from the previous release.** On a site running the previous free release, load the home page so the old cache exists. Update to the release candidate. Load the home page logged out.
Expected: notifications show, `debug.log` has no new entry, and T2 passes.

### Saved values

**T11. Field types are kept.** Edit a notification and set a multi-select field (for example **Display → Locations**, two values) and a number field (for example **Display For**). Save, reload the builder.
Expected: both values are as saved. On the frontend the notification follows them.

### Persistent object cache (if the site has one)

**T12.** On a site with Redis or Memcached object cache, repeat T1, T2 and T7.
Expected: same results. In T2, `large: MISSING` on such a site is not a failure of this change; it means that cache cannot keep the large value, which is the case this change is for. Report it with the cache type.

### Debug mode

**T13.** Add `define( 'NX_DEBUG', true );` to `wp-config.php`. Repeat T1 and T6. Remove the line.
Expected: both pass. Requests are slower in this mode; that is expected.

## Automated checks

Run these on the development site, not on the QA site:

```
vendor/bin/phpunit --filter 'Builder_Field'
node tests/e2e/builder-field-names-cache.js
```

Expected: PHPUnit reports no failures; the e2e script prints `All checks passed.` It creates three temporary Notification Bars and deletes them.

## Reporting

Report results as a list of case ids (`T1 pass, T5 fail: A=0.21 B=0.94`). For T3, T5 and T12 include the measured times and the object cache type, if any.
