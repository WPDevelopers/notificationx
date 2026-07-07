# Data & Storage

Where NotificationX keeps its data. As of the current schema (`Database::$version = 2.1`) notifications are **not** stored as a WordPress custom post type — they live in three custom tables created by [../../includes/Core/Database.php](../../includes/Core/Database.php). A legacy `notificationx` CPT existed in older versions and is migrated into these tables on upgrade (see [Migrations & upgrades](#migrations--upgrades)).

## Custom tables (`nx_posts`, `nx_entries`, `nx_stats`)

`Database::Create_DB()` builds three tables via `dbDelta`, each prefixed with the site's `$wpdb->prefix`:

### `{prefix}nx_posts` — the notifications

One row per notification. Read/written through `PostType` ([../../includes/Core/PostType.php](../../includes/Core/PostType.php)); the full builder config is JSON/serialized into the `data` column, while a few hot columns are denormalized for querying.

| Column | Type | Notes |
| --- | --- | --- |
| `nx_id` | bigint unsigned, PK, auto_increment | Primary key used everywhere as the notification ID. |
| `title` | text | Notification title. |
| `type` | varchar(55) | The Type `$id` (e.g. `conversions`, `notification_bar`). Indexed. |
| `source` | varchar(55) | The Extension `$id` / `source` (e.g. `woocommerce`, `press_bar`). Indexed. |
| `theme` | varchar(55) | Selected theme slug. Indexed. |
| `is_inline` | varchar(255) | Inline-shortcode marker. |
| `global_queue` | boolean (default false) | Whether it joins the global notification queue. |
| `enabled` | boolean (default false) | Active/inactive. |
| `data` | longtext | Serialized full builder settings. |
| `created_at` / `updated_at` | timestamp | |

The column-format map (used on insert/update) is `PostType::$format`.

### `{prefix}nx_entries` — per-item data

The individual items a notification cycles through — each sale, review, comment, form submission, or popup/exit-intent form entry.

| Column | Type | Notes |
| --- | --- | --- |
| `entry_id` | bigint unsigned, PK, auto_increment | |
| `nx_id` | bigint unsigned, nullable | Owning notification. Indexed. |
| `source` | varchar(55) | Extension source. Indexed. |
| `entry_key` | varchar(255) | Dedup key (order id, username, timestamp, …). |
| `data` | longtext | Serialized entry payload. |
| `created_at` / `updated_at` | timestamp | |

Popup and exit-intent form submissions (`source IN ('popup_notification','exit_intent_custom')`) are stored here too and surfaced in the admin Feedback Entries screen — see `Core\Rest\Popup` and [../api/rest-endpoints.md](../api/rest-endpoints.md).

### `{prefix}nx_stats` — analytics

Daily view/click counters per notification.

| Column | Type | Notes |
| --- | --- | --- |
| `stat_id` | bigint unsigned, PK, auto_increment | |
| `nx_id` | bigint unsigned, nullable | Owning notification. Indexed. |
| `views` | varchar(55) (default 0) | View count for the day. |
| `clicks` | varchar(55) (default 0) | Click count for the day. |
| `created_at` | date | The day the row aggregates. |

Counters are bumped by `Database::update_analytics()` / `Core\Analytics`; the analytics REST endpoints read and reset them.

`Database` also exposes generic helpers (`insert_post`, `update_post`, `get_posts`, `delete_posts`, `get_where_query`, `serialize_data`/`unserialize_data`) that operate on these tables and are used throughout the plugin. Query building also uses the `wpdeveloper/query-builder` package (`Database::query()`).

## Auxiliary post types (Elementor / Gutenberg bodies)

Some Types let you design the notification body in Elementor or the block editor. Those designs are stored as small, non-public CPTs — they are *not* the notification records themselves:

- `nx_bar` and `nx_bar_eb` (Gutenberg) — registered by `Extensions\PressBar\PressBar::register_post_type()` for the Notification Bar.
- `nx_exit_intent` — registered by `Extensions\ExitIntent\ExitIntentNotification::register_post_type()` for the Exit Intent popup.

Each notification row references its design post via an `elementor_id` (or similar) stored in the `nx_posts` `data` column.

## Settings storage

Plugin settings are delegated to the `wpdeveloper/lib-settings` package, initialized in the root singleton with `key=notificationx`, `store=options`, `auto_commit=true` ([../../includes/NotificationX.php](../../includes/NotificationX.php)). Settings therefore live in the standard `wp_options` table under the `notificationx` option, accessed via `Settings::get_instance()->get('settings.…')` / `->set(…)`.

Module activation is settings-driven: each Type/Extension declares a `$module` key, and a module toggled off in settings is never registered. A handful of bookkeeping options are also written directly to `wp_options` — `nx_db_version`, `nx_free_version`, `nx_admin_notice_close`, `notificationx_milestone_level`, `nx_initial_popup_dismissed`, and the legacy `notificationx_settings` / `notificationx_data` (pre-migration).

## Migrations & upgrades

Two classes handle schema and data transitions, both driven by [../../includes/Core/Upgrader.php](../../includes/Core/Upgrader.php) on every load (see [plugin-lifecycle.md](plugin-lifecycle.md)):

- **Schema** — when `nx_db_version` ≠ `Database::$version`, `Create_DB()` re-runs (`dbDelta` is additive/idempotent) and the option is updated.
- **Legacy data** — [../../includes/Core/Migration.php](../../includes/Core/Migration.php) runs once for sites coming from `< 2.0.0`. It reads the old `post_type = 'notificationx'` rows and their `_nx_meta_*` post meta, maps each legacy Type (`conversions`, `comments`, `reviews`, `download_stats`, `press_bar`, `form`, `email_subscription`, `page_analytics`, `custom`, …) into the new `nx_posts`/`nx_entries`/`nx_stats` shape, and re-schedules crons for pull-based sources. Old options (`notificationx_settings`, `notificationx_data`) are migrated into the new settings store; the destructive delete of the old CPT rows is currently left commented out.
- **Point fixes** — e.g. `Upgrader::migrate_for_donation()` rewrites a donation template tag for sites `<= 2.8.0`.

## Source
- [../../includes/Core/PostType.php](../../includes/Core/PostType.php)
- [../../includes/Core/Database.php](../../includes/Core/Database.php)
- [../../includes/Core/Migration.php](../../includes/Core/Migration.php)
- [../../includes/Core/Upgrader.php](../../includes/Core/Upgrader.php)
- [../../includes/Admin/Entries.php](../../includes/Admin/Entries.php)
