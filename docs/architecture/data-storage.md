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

#### Field types in `data`

`data` holds whatever JSON the caller sent. The admin builder always sends the right shapes, but MCP `create-notification` / `update-notification` and the REST save pass caller JSON straight through, and many list fields declare `'default' => ''`. Front-end code then passes these values to `explode()`, `array_diff()`, `array_map()`, `shuffle()` and similar, which throw a `TypeError` on PHP 8. Because `FrontEnd::get_notifications_ids()` loops over every enabled notification on every page, one bad row returned a 500 for the whole site.

[`NotificationX::normalize_field_types()`](../../includes/NotificationX.php) coerces each field to the shape its consumers expect. It runs in two places:

- **On save:** in `PostType::save_post()`, before the row is stored and before the `nx_get_post` filters that `save_post()` fires.
- **On read:** in `normalize_post()`, which every `PostType::get_posts()` call with `select = '*'` uses. This also repairs rows stored before the fix.

| Field | Coerced to | Rule |
| --- | --- | --- |
| `'multiple' => true` (`select`, `select-async`, `better-select`) | array | `''`/`null`/`false` becomes `[]`; a scalar becomes `[ scalar ]` |
| `text`, `textarea`, `date`, `timepicker`, `advanced-codeviewer` | string | A list of scalars is joined with `,`; any other array becomes `''` |
| `repeater`, `advanced-repeater` | list of array rows | Non-array rows are dropped; a non-array value becomes `[]` |
| `notification-template` | array | A non-array value becomes `[]` |
| `custom_contents` | list of array rows | As for repeaters |
| `custom_ids`, `taxonomy_ids` | string | As for text fields |

The field types come from the cached builder config (`nx_builder_fields` transient, cleared on settings save and on upgrade). The last four keys are in `NotificationX::GUARDED_FIELDS` and are coerced even when a module that declares them was off when the cache was built.

Tests: [tests/test-field-type-normalization.php](../../tests/test-field-type-normalization.php), [tests/test-inline-location.php](../../tests/test-inline-location.php).

#### The field list cache

`normalize_post()` runs for every notification that is read, on the front end too (each `/notice` request and each page that lists active notifications). It needs the name, type, default and `multiple` flag of every builder field: `NotificationX::get_field_names()`.

That list comes from the builder fields, which are large: about 840 KB serialized. They are cached in two transients, written together so they expire together:

| Transient | Holds | Size | Read by |
| --- | --- | --- | --- |
| `nx_builder_fields` | The whole builder config (`GlobalFields::tabs()`) | about 840 KB | The admin builder, through `NotificationX::get_tab()` |
| `nx_builder_field_names` | The field list derived from it | about 90 KB | `get_field_names()`, so every notification read |
| `nx_builder_fields_pro` | The state the two above were built for (Pro version, Elementor, Essential Blocks) | a few bytes | Both, to drop a stale cache |

`get_field_names()` loads the list once per request and keeps it in memory, per site ID so a request that switches sites on a network never mixes lists. The front end never reads `nx_builder_fields`.

This matters when a store cannot keep the large value, for example an object cache with a 1 MB item limit. `set_transient()` then fails silently and every read finds nothing. Before the split, each notification read rebuilt the whole builder config: in a local test with 3 notifications the `/notice` handler took 579 ms instead of 10 ms. Now the small list is kept, and when nothing can be kept at all the config is rebuilt once per request, not once per notification.

Rules when you touch this:

- Clear the cache with `delete_transient( 'nx_builder_fields' )`, as before. `NotificationX::forget_field_names()` is hooked to `delete_transient_nx_builder_fields` and `set_transient_nx_builder_fields`, so the field list follows. Never write `nx_builder_field_names` yourself.
- Some field defaults hold the time the config was built (`expire_time`, the countdown dates: "in 7 days"). A notification with no stored value for them gets the default, so those values change whenever the config is rebuilt. A response whose `expire_time` moves on every request is a sign that the cache is not being kept.
- `NX_DEBUG` rebuilds the config on every call and skips both the in-memory copy and the field list transient.

Tests: [tests/test-builder-field-names-cache.php](../../tests/test-builder-field-names-cache.php), [tests/test-builder-fields-cache.php](../../tests/test-builder-fields-cache.php), and [tests/e2e/builder-field-names-cache.js](../../tests/e2e/builder-field-names-cache.js) against a real site. Manual checks: [docs/qa-guide/builder-field-list-cache.md](../qa-guide/builder-field-list-cache.md).

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

Views are counted on the server, inside the front-end `notice` request: `Analytics::insert_views()` runs on `nx_filtered_data` (and on `nx_filtered_data_press_bar` for bars) and updates one row per notification shown. Clicks come from the browser through the `/analytics` route. Both go through `Analytics::insert_analytics()`, which writes nothing when **Enable Analytics** (`settings.enable_analytics`) is off. In that state the Analytics page, the dashboard widget, the report email and the click route are off too, so nothing reads the rows. Before this, views were still written with analytics off: one update per notification on every `notice` request.

Record analytics only through `insert_analytics()`, so the setting, `analytics_from` and the bot filter keep applying. Tests: [tests/test-analytics-disabled.php](../../tests/test-analytics-disabled.php), [tests/e2e/analytics-disabled.js](../../tests/e2e/analytics-disabled.js).

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
