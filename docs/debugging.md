# Debugging

Practical debugging for NotificationX. Skips generic WP debugging — assumes you already know `WP_DEBUG`.

## Turn on the right flags

In `wp-config.php`:

```php
define( 'WP_DEBUG',         true );
define( 'WP_DEBUG_LOG',     true );  // writes to wp-content/debug.log
define( 'WP_DEBUG_DISPLAY', false ); // don't render errors in the page (breaks the popup)
define( 'SCRIPT_DEBUG',     true );  // load unminified JS/CSS
```

`SCRIPT_DEBUG` matters: with it off, WP loads the minified bundles from `nxbuild/`, which makes JS stack traces useless. With it on, dev bundles are served.

Log location: `wp-content/debug.log`. Tail it during testing:
```sh
tail -f wp-content/debug.log
```

## The "my notification isn't showing" decision tree

Walk these in order. Stop at the first failure.

### 1. Is the notification enabled?

```sql
SELECT nx_id, title, type, source, enabled
FROM wp_nx_posts
WHERE nx_id = <id>;
```

`enabled` must be `1`. If not, toggle in the admin or via REST `POST /notificationx/v1/nx/enable`.

### 2. Is the module enabled?

```php
\NotificationX\Core\Modules::get_instance()->is_enabled( 'modules_<name>' )
```

Returns `false` only if the setting is explicitly off. Check **NotificationX → Settings → Modules**.

### 3. Is the Extension registered?

```php
$ext = \NotificationX\Extensions\ExtensionFactory::get_instance()->get_extension( 'woocommerce_sales' );
var_dump( $ext );  // should be the Extension object, not null
```

If `null`: either `$id` doesn't match what you think, the class isn't in `ExtensionFactory::$extension_classes`, or the module is disabled.

### 4. Is the Type registered?

```php
$type = \NotificationX\Types\TypesFactory::get_instance()->get_type( 'conversions' );
var_dump( $type );
```

If `false`: typo in the Extension's `$types` property, or the Type class is missing from `TypesFactory::$types`.

### 5. Are there entries?

```sql
SELECT entry_id, source, entry_key, created_at
FROM wp_nx_entries
WHERE nx_id = <id>
ORDER BY created_at DESC
LIMIT 10;
```

Zero rows = nothing to display. Check whether:
- The source plugin has actually generated data (e.g. real WC orders exist).
- The cron-driven fetch has run (`wp cron event run --due-now` or wait).
- The event-driven hook fired (add a `error_log()` in your Extension's save hook).

### 6. Force a regenerate

```http
POST /wp-json/notificationx/v1/entries/regenerate/<nx_id>
X-WP-Nonce: <nonce>
```

Or via the admin: hover the notification row → **Regenerate**.

### 7. Is the frontend script loaded?

In the browser console on a page where the notification should show:
```js
window.notificationXArr  // should be an array with your notification's config
```

If `undefined`: enqueue failed. Check that:
- The page isn't excluded by the **Display → Restrictions** rules.
- A caching/optimization plugin isn't stripping the script.
- The bundle URL (Network tab) resolves — should be from `nxbuild/`.

### 8. Frontend rendering errors

Open browser DevTools → Console. Look for errors from the NotificationX bundle. Common ones:
- `Cannot read property 'X' of undefined` → data shape from PHP doesn't match what the frontend Type renderer expects. See [architecture.md § dual frontend runtimes](architecture.md#dual-frontend-runtimes-the-silent-desync-trap).
- React hydration mismatch → a Pro theme override is desynced from the free Type.

## Inspecting the database

The three custom tables:

```sql
-- Notification records
SELECT * FROM wp_nx_posts;

-- Entries (one per popup)
SELECT * FROM wp_nx_entries WHERE source = 'woocommerce_sales' ORDER BY created_at DESC LIMIT 20;

-- Daily analytics
SELECT * FROM wp_nx_stats WHERE nx_id = <id>;
```

The `data` column on each table is LONGTEXT JSON — use `JSON_EXTRACT` or pull into a JSON viewer.

## Forcing a popup to show for testing

The frontend has frequency/timing rules that suppress popups during testing. Bypass:

1. Open a private/incognito browser window (resets cookies).
2. In the notification's **Display** tab, set Initial Delay → 0, Display Duration → high, Loop → on.
3. Or, in DevTools: `localStorage.clear(); document.cookie.split(';').forEach(c => document.cookie = c.trim().split('=')[0] + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT')` then reload.

## Cron-driven Extensions

If your Extension uses `$cron_schedule`:

```sh
wp cron event list | grep nx
wp cron event run <hook_name>
```

To verify your schedule is registered:
```sh
wp cron schedule list
```

`Cron.php` registers custom intervals via the `nx_cron_schedules` filter.

## Useful WP-CLI commands

```sh
# List all NX notifications
wp post list --post_type=notificationx

# Wipe all entries (DEV ONLY)
wp db query "TRUNCATE TABLE wp_nx_entries"

# Inspect the settings option
wp option get notificationx --format=json | jq

# Reset settings to defaults (DEV ONLY)
wp option delete notificationx
```

## Pro plugin issues

Symptoms that point to Pro:
- "It worked yesterday, then we updated Pro" → check `notificationx-pro` debug log, deactivate Pro and retest.
- A theme/design renders blank → Pro's theme is desynced; see [integrations/pro-hooks.md](integrations/pro-hooks.md).
- A source disappears from the dropdown → Pro filtered `nx_extension_classes` wrong.

## When to escalate

If you've walked the decision tree and the notification still doesn't show, gather:
1. WP version, PHP version, NotificationX version, Pro version.
2. List of active plugins and theme.
3. `wp_nx_posts` row JSON for the failing notification.
4. `wp_nx_entries` count for that `nx_id`.
5. Browser console errors.
6. `wp-content/debug.log` excerpt around the time of the failed display.

That's enough for a maintainer (or future-you) to diagnose without re-running the whole tree.
