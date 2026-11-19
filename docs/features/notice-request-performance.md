# Frontend `/notice` request performance (issue #214)

What issue #214 reported, what was measured, what branch `fix/214-builder-field-names-cache` fixes, and what is still open. Written 2026-11-12.

Short version: the branch fixes three causes of slowness that the investigation found. It does **not** implement the proposal in the issue (GET, caching, server-side proxy), and none of the issue's acceptance criteria is met yet. Whether the proposal is still needed depends on a measurement after this branch is released.

## The issue

Every frontend page with active notifications sends:

```
POST /wp-json/notificationx/v1/notice/?frontend=true
```

Issue [#214](https://github.com/WPDevelopers/notificationx/issues/214) reported two problems:

1. **No caching.** `FrontEnd::get_notifications_data()` runs the notification and entries queries on every call, and the route is POST only, so browsers and CDNs cannot cache it.
2. **Cross Domain Notice exposes the source site.** With the Pro cross-domain snippet each visitor's browser calls the source site's REST API directly. Visitors can see the source domain and the request payload, and every page view on every consuming site reaches the source site.

The reported impact was one uncached PHP + MySQL request on the source site per page view, with a time to first byte of about 0.85 s.

The issue proposed three changes:

1. Make the request a GET with `Cache-Control` headers.
2. Cache the response internally (transient or object cache) with a TTL per type, a "last good" copy, cache clearing and a rebuild lock.
3. In Pro, fetch Cross Domain notices on the consuming site's server and serve them from its own domain.

## What was measured

### Production

Requests to api.wpdeveloper.com from outside, alternating between the two kinds, time to first byte:

| Request | Median | Range |
| --- | --- | --- |
| Empty (no IDs, so NotificationX runs no queries) | 0.64 s | 0.50–0.91 s |
| Full (`all_active`, as the Cross Domain snippet sends it) | 1.61 s | 1.05–2.30 s |

- The request is slower than the issue said: about 1.6 s, not 0.85 s.
- The NotificationX handler costs about 1 s (60%). WordPress boot costs about 0.64 s (40%).
- The response is 106 KB uncompressed. Most of it is notification settings; the entries are small.
- The responses were the same from one request to the next, apart from one field: `expire_time` moved forward with each request and always equalled the request time plus 7 days. That field's default is computed when the builder fields are built, so the builder fields were being rebuilt on every request there. See [fix 1](#fix-1-the-field-list-no-longer-depends-on-the-large-builder-cache).

### Local

Copy of a development database, WooCommerce sales notifications, median of 40 requests, milliseconds:

| Data | Total | WP boot | Handler | Entries SQL | Per-entry loop |
| --- | --- | --- | --- | --- | --- |
| Empty request | 74 | 72 | 0 | 0 | 0 |
| 1 notification × 20 entries | 114 | 74 | 37 | 0.9 | 28 |
| 5 × 100 | 193 | 75 | 116 | 6.5 | 92 |
| 10 × 100 | 301 | 81 | 213 | 12.7 | 168 |

- The entries query that the issue blamed is cheap: 1 to 13 ms.
- For WooCommerce sources the time went into a product check that ran for every stored entry. See [fix 3](#fix-3-woocommerce-entries-load-each-product-once-per-request).
- The same handler and boot are far slower on production than the code alone explains, which pointed to something specific to that server. That turned out to be the builder field cache.

## What this branch fixes

Three commits, each with its own tests, end-to-end script, QA guide and docs. None of them changes what a visitor sees.

| Commit | Fix | Who benefits |
| --- | --- | --- |
| `f0826a3a` | The field list no longer depends on the large builder cache | Sites whose cache cannot keep a value of about 840 KB. This is the likely cause of the 1 s on production. |
| `0c6fd9bf` | No views are written while analytics is off | Sites with **Enable Analytics** off |
| `d3b54348` | WooCommerce entries load each product once per request | Sites with WooCommerce sources |

### Fix 1: the field list no longer depends on the large builder cache

**Cause.** `normalize_post()` runs for every notification that is read. It needs the builder's field list (name, type, default), which it took from the `nx_builder_fields` transient: the whole builder config, about 840 KB serialized. A store that cannot keep a value that large, for example an object cache with a 1 MB item limit, returns nothing, and the whole config was then rebuilt **for each notification on each request**.

**Change.** The field list has its own transient, `nx_builder_field_names` (about 90 KB), written together with the builder fields so both expire together. It is loaded once per request and kept in memory per site ID. The frontend no longer reads `nx_builder_fields`. The list is dropped whenever `nx_builder_fields` is deleted or replaced, so existing code that clears the cache needs no change.

**Effect**, `/notice` handler with 3 notifications, local:

| State of the cache | Before | After |
| --- | --- | --- |
| Working | 10 ms | 3 ms |
| The large value is not kept | 579 ms | 3 ms |
| Nothing is kept | 579 ms | 114 ms |

The response, the field list and the normalized notifications are identical to before.

**Limit.** A site where no cache value is kept at all still rebuilds the config once per request (114 ms above). That is a broken object cache and is outside the plugin.

Details: [../architecture/data-storage.md](../architecture/data-storage.md#the-field-list-cache). Code: `NotificationX::get_field_names()`, `get_tab()`, `forget_field_names()` in [includes/NotificationX.php](../../includes/NotificationX.php).

### Fix 2: no views are written while analytics is off

**Cause.** Views are counted on the server inside the `/notice` request. With **Enable Analytics** off, the Analytics page, the dashboard widget, the report email and the click route are all off, so nothing reads the stats. Views were written anyway: one row update per notification on every request.

**Change.** `Analytics::insert_analytics()` writes nothing when the setting is off.

**Behaviour change.** A site that turns analytics off and later on again no longer has views for the time it was off. Before, that period held views with no clicks.

Code: [includes/Core/Analytics.php](../../includes/Core/Analytics.php).

### Fix 3: WooCommerce entries load each product once per request

**Cause.** `WooCommerce::entry_display()` hides an entry whose product is gone or not published. It runs for every stored entry of a notification, before `display_last` cuts the list, and it called `wc_get_product()` each time. A notification stores up to 100 entries, usually of the same few products. With 5 notifications of 100 entries that was about 85 ms of a 117 ms handler.

**Change.** The answer is kept per product ID for the rest of the request, in a static shared by every WooCommerce source, and cleared when a post changes status or is deleted. `wc_get_product()` still decides, so which entries are hidden does not change.

**Effect.** On a real site a request with 40 entries went from 35 product loads to 3. In a prototype with the same logic the handler for 5 × 100 went from 117 ms to 56 ms with the same response.

**Not taken.** Checking with `get_post_status()` in place of `wc_get_product()` is faster still (31 ms), but it bypasses WooCommerce's own filters and product classes, so it was left out.

Code: [includes/Extensions/WooCommerce/WooCommerce.php](../../includes/Extensions/WooCommerce/WooCommerce.php). Details: [../extensions/woocommerce.md](../extensions/woocommerce.md).

## How much of the issue is fixed

| From the issue | Status after this branch |
| --- | --- |
| The request is slow on the source site | **Likely mostly fixed for the handler part (about 1 s of 1.6 s), not yet confirmed.** Fix 1 removes the per-notification rebuild that production's responses show. It must be measured on production after release. |
| WordPress boot (about 0.64 s per request) | **Not fixed.** Not a plugin problem; only a cache in front of PHP (GET + cache headers) or work on the server removes it. |
| No caching of the response | **Not done.** |
| Route is POST only | **Not done.** |
| Cross Domain exposes the source site and its payload | **Not done.** |
| Every page view reaches the source site | **Not done.** |

Acceptance criteria of the issue:

| # | Criterion | Status |
| --- | --- | --- |
| 1 | The frontend notice request is a GET with cache headers | Not done |
| 2 | Repeated identical requests within the TTL do not run the entries query | Not done |
| 3 | A new conversion shows within about 1 minute; edited or disabled notifications show on the next request | Still true as today (no cache yet). With HTTP caching the second half cannot hold; see below. |
| 4 | If a rebuild fails, the last valid data is served | Not done |
| 5 | Only one rebuild per key runs at a time under load | Not done |
| 6 | With remote-source mode the browser only calls its own domain, and the source URL and payload don't appear in DevTools | Not done. The second half cannot hold; see below. |

## What is left

In the order it should be decided:

1. **Release this branch and measure production again.** Run the two requests from [Verifying on production](#verifying-on-production). If the full request drops to about the time of the empty one, the handler is no longer the problem and item 2 is probably not needed.
2. **Internal cache (point 2 of the proposal).** Only if the handler is still slow after item 1. Cache the built entries per notification, and keep view counting and notification selection outside the cache so analytics and targeting behave as they do now. The entries query has no hook for bulk inserts or deletes (`Entries::insert_entries()`, `delete_entries()`), so cache clearing needs those added.
3. **GET + cache headers (point 1).** The only plugin change that removes the 0.64 s boot, and only on a cache hit. It needs the three problems under [What the proposal breaks as written](#what-the-proposal-breaks-as-written) solved first. A GET route without cache headers can ship earlier: it changes no behaviour and saves the preflight request that every cross-domain POST costs today.
4. **Trim the notification settings in the response.** They are most of the 106 KB. Each field has to be checked against the frontend runtime before it is removed.
5. **Server-side proxy for Cross Domain (point 3, Pro).** A separate issue. It needs its own decisions: a token, forwarding the visitor's country and device to the source, a path for views and clicks, and what happens when the two sites run different versions.

Needed from other people before items 2, 3 and 5:

- **From the api server:** the output of the commands in the [first comment on the issue](https://github.com/WPDevelopers/notificationx/issues/214#issuecomment-6817428445), whether a cache in front of the site can cache a GET route and key on `Origin`, and whether anything on the server restricts REST access by `Origin`.
- **From the issue's author:** how exact view counts must be once responses are cached, and new wording for criteria 3 and 6.

### What the proposal breaks as written

These apply to items 2 and 3 above and are the reason they were not built in this branch.

1. **View analytics.** Views are counted inside the `/notice` request (`Analytics::insert_views` on `nx_filtered_data`). A response served from a CDN or browser cache never reaches PHP, so those views are lost. A server-side cache of the whole response skips the filter too. View counting has to move to a separate request from the browser first, and that request still costs one PHP request per page view unless views are counted less often.
2. **The response is not the same for every visitor on every site.** In `all_active` mode the server picks the notifications, and that uses country targeting. With country targeting on, a shared cache serves the wrong notifications. The cache key also needs `shortcode`, `inline_shortcode`, `addon_scripts` and, for the Google Analytics source, the page URL and title.
3. **CORS behind a shared cache.** WordPress answers with the request's `Origin` in `Access-Control-Allow-Origin` plus `Vary: Origin`. A cache that does not key on `Origin` would serve a response stored for one consuming site to another, and the browser would block it.

Two acceptance criteria cannot be met as written:

- **Criterion 3, "edited or disabled notifications show on the next request".** With `Cache-Control: public, max-age=60`, browser and CDN copies cannot be purged by a version counter. The real bound is the TTL.
- **Criterion 6, "the source URL and payload don't appear in DevTools".** Even with a server-side proxy, the entries carry image URLs and product links on the source domain, and the browser loads them. What can be hidden is the API call and the request payload.

## Tests

| Fix | PHPUnit | End-to-end (real requests, no browser) | QA guide |
| --- | --- | --- | --- |
| 1 | [tests/test-builder-field-names-cache.php](../../tests/test-builder-field-names-cache.php) (12 tests, 2 for networks), [tests/test-builder-fields-cache.php](../../tests/test-builder-fields-cache.php) | [tests/e2e/builder-field-names-cache.js](../../tests/e2e/builder-field-names-cache.js) (13 checks) | [../qa-guide/builder-field-list-cache.md](../qa-guide/builder-field-list-cache.md) |
| 2 | [tests/test-analytics-disabled.php](../../tests/test-analytics-disabled.php) (8 tests) | [tests/e2e/analytics-disabled.js](../../tests/e2e/analytics-disabled.js) (8 checks) | [../qa-guide/analytics-off-no-views.md](../qa-guide/analytics-off-no-views.md) |
| 3 | [tests/test-woocommerce-entry-visibility.php](../../tests/test-woocommerce-entry-visibility.php) (15 tests) | [tests/e2e/woocommerce-entry-visibility.js](../../tests/e2e/woocommerce-entry-visibility.js) (9 checks) | [../qa-guide/woocommerce-entry-visibility.md](../qa-guide/woocommerce-entry-visibility.md) |

How to run the end-to-end scripts: [../../tests/e2e/README.md](../../tests/e2e/README.md).

Results on 2026-11-12:

- PHPUnit, free: 337 tests pass (one is for networks only and is skipped on a single site).
- PHPUnit, free, as a network (`WP_MULTISITE=1`): 5 failures, the same 5 that `dev` has (4 in `Test_Activation_Insights`, 1 in `Test_Security_Settings_Boundary`). None is new.
- PHPUnit, Pro: 68 tests pass. Pro is not changed.
- The three end-to-end scripts pass, and each fails against the code before its fix (per-entry product loads, views written with analytics off).
- [tests/e2e/release-smoke.js](../../tests/e2e/release-smoke.js): 57 sources, no failed check.

Not tested, and covered by the QA guides: a real Redis or Memcached object cache, and a running network.

`WooCommerce` is not loaded in the PHPUnit suite, so the fix 3 unit tests use a stand-in for `wc_get_product()`. The end-to-end script uses real products.

## Verifying on production

After the release is on the source site. Replace `SITE`.

**Time of the two requests.** Run each five times and take the middle value.

```sh
# Empty: WordPress boot only.
curl -s -o /dev/null -w '%{time_starttransfer}\n' -X POST -H 'Content-Type: application/json' \
  -d '{"deviceType":"desktop"}' 'https://SITE/wp-json/notificationx/v1/notice/?frontend=true'

# Full: what the Cross Domain snippet sends. Counts one view per notification when analytics is on.
curl -s -o /dev/null -w '%{time_starttransfer}\n' -X POST -H 'Content-Type: application/json' \
  -d '{"all_active":true,"deviceType":"desktop","addon_scripts":true}' 'https://SITE/wp-json/notificationx/v1/notice/?frontend=true'
```

Before the release these were 0.64 s and 1.61 s. The difference between them is the handler.

**Is the builder config still rebuilt on every request?** Send the full request twice, a few seconds apart, and compare `expire_time` in the two responses:

```sh
curl -s -X POST -H 'Content-Type: application/json' -d '{"all_active":true,"deviceType":"desktop"}' \
  'https://SITE/wp-json/notificationx/v1/notice/?frontend=true' | grep -o '"expire_time":"[^"]*"' | sort -u
```

Before the release the value moved with every request. After it, the value must stay the same from one request to the next (it changes about once a day, when the cache expires).
