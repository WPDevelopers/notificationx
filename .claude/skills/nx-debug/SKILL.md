---
name: nx-debug
description: Use when a NotificationX notification isn't showing, isn't generating entries, or the popup isn't rendering on the frontend. Triggers on phrases like "popup not showing", "notification not displaying", "no entries", "notification disappeared", "popup is blank", "source not in dropdown", "module not appearing". Walks the user through the systematic debug decision tree before suggesting fixes.
---

# Debugging "my notification isn't showing"

Before suggesting fixes, **walk the decision tree in order**. Most "broken" notifications fail at one of the first three checks, and skipping ahead wastes time.

## The decision tree (do not skip steps)

### 1. Is the notification enabled?

```sql
SELECT nx_id, title, type, source, enabled
FROM {prefix}_nx_posts
WHERE nx_id = <id>;
```

`enabled` must be `1`. If not — that's the bug. Toggle it on.

### 2. Is the module enabled?

```php
\NotificationX\Core\Modules::get_instance()->is_enabled( 'modules_<name>' )
```

A disabled module = the Extension never registers. Symptom: source missing from dropdown, or notification exists but produces nothing.

Setting key path: `settings.modules['modules_<name>']` (e.g. `modules_woocommerce`). Default is **enabled** when the key is absent.

### 3. Is the Extension registered? Is the Type valid?

```php
$ext  = \NotificationX\Extensions\ExtensionFactory::get_instance()->get_extension( '<source_id>' );
$type = \NotificationX\Types\TypesFactory::get_instance()->get_type( '<type_id>' );
var_dump( $ext, $type );
```

- `$ext === null` → class not in `ExtensionFactory::$extension_classes`, or `$id` mismatch, or module disabled.
- `$type === false` → Extension's `$types` property points to a non-existent Type ID.

### 4. Are there entries?

```sql
SELECT COUNT(*) FROM {prefix}_nx_entries WHERE nx_id = <id>;
SELECT * FROM {prefix}_nx_entries WHERE nx_id = <id> ORDER BY created_at DESC LIMIT 5;
```

Zero rows = nothing to display.
- For cron-driven: `wp cron event run --due-now` then recheck.
- For event-driven (e.g. WooCommerce orders): verify the source plugin actually fired a matching event since the notification was created.
- Force regenerate: `POST /wp-json/notificationx/v1/entries/regenerate/<nx_id>`.

### 5. Is the frontend script loaded?

In browser DevTools console on a page where the popup should show:
```js
window.notificationXArr
```

- `undefined` → enqueue failed. Check display restrictions, caching plugins, the `Network` tab for the bundle URL (should be under `nxbuild/`).
- Empty array → no notifications configured for this page.
- Array with the notification but no popup visible → step 6.

### 6. Frontend rendering errors

Open browser console. Common errors:
- `Cannot read property 'X' of undefined` → PHP `get_data()` shape doesn't match what the frontend Type renderer expects. **Dual-runtime desync** — see `docs/architecture.md § dual frontend runtimes`.
- React hydration mismatch → Pro theme is desynced from free Type.
- Network 4xx on the analytics endpoint → not blocking, but indicates a separate issue.

### 7. Cookies / frequency throttling

Popups suppress themselves after displaying. Test fresh:
```js
localStorage.clear();
document.cookie.split(';').forEach(c => document.cookie = c.trim().split('=')[0] + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT');
location.reload();
```

Or use a private/incognito window.

## Before recommending a fix

State **which step failed**. "The notification is enabled, the module is on, the Extension registers, but `nx_entries` is empty" is actionable. "It doesn't work" is not.

## Useful one-liners

```sh
# Inspect a notification
wp post get <nx_id> --post_type=notificationx --format=json | jq

# Wipe a notification's entries to force a refetch
wp db query "DELETE FROM wp_nx_entries WHERE nx_id = <id>"

# Tail debug log while reproducing
tail -f wp-content/debug.log
```

## Reference

Full debug guide with more depth: `docs/debugging.md`.
