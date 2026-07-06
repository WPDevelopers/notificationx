# Download Stats Notification Type (`download_stats`)

> Shows a notification advertising how many times a plugin/theme has been downloaded
> (today, last 7 days, or all-time) or how many sites are actively using it — pulling
> the numbers from the WordPress.org stats API (or, on Pro, Freemius) to build social
> proof for a plugin/theme product page.

## At a glance

| | |
|---|---|
| **Type ID** | `download_stats` |
| **Class** | [`includes/Types/DownloadStats.php`](../../includes/Types/DownloadStats.php) |
| **Trait** | none — no `Traits\DownloadStats` file exists; the class only pulls in the generic `GetInstance` trait |
| **Priority** | `30` |
| **Default source** | `wp_stats` |
| **Default theme** | `download_stats_today-download` |
| **Module gate (`$module`)** | `modules_wordpress`, `modules_freemius` — declared on the Type class as `public $module = ['modules_wordpress', 'modules_freemius']`; each compatible Extension additionally declares its own single module key (see below) |
| **Compatible extensions** | [`includes/Extensions/WordPress/WPOrgStats.php`](../../includes/Extensions/WordPress/WPOrgStats.php) (`$id = 'wp_stats'`, `$module = 'modules_wordpress'`) and [`includes/Extensions/Freemius/FreemiusStats.php`](../../includes/Extensions/Freemius/FreemiusStats.php) (`$id = 'freemius_stats'`, `$module = 'modules_freemius'`, `$is_pro = true`) — both declare `$types = 'download_stats'` |

## What it does

A `download_stats` notification renders a small popup ("N people are actively using
X" / "X has been downloaded N times today/this week/ever") that links out to the
product's page (`link_type = 'stats_page'`, added to the Link Type field via
`link_types()`). The numbers come from the WordPress.org stats API for `wp_stats`
(free) — plugin or theme slug configured per-notification — or from Freemius for
`freemius_stats` (Pro only, gated by `$is_pro = true` on `FreemiusStats`).

There is no dedicated `Traits/DownloadStats.php` file; all type-level config (themes,
templates, content-field additions) lives directly in
[`DownloadStats.php`](../../includes/Types/DownloadStats.php).

## Data flow

`FrontEnd.php` has no `download_stats`-specific branch — it is **not** special-cased
like `press_bar`, `popup_notification`, or `exit_intent_custom`. It falls through to
the generic `active_notifications` bucket in `get_notifications_ids()`
(`includes/FrontEnd/FrontEnd.php`), and its entries are built with the
`{ post, entries: [...] }` shape (populated around
`$result['active'][$nx_id]['entries'][] = $entry;`), which the React side normalizes
with `normalize()` (not `normalizePressBar()`) per the convention documented in
[../development/adding-a-notification-type.md](../development/adding-a-notification-type.md#choosing-normalize-vs-normalizepressbar).

For the `wp_stats` source specifically:
- `WPOrgStats::update_data()` (hooked on save and on a cron, `nx_cron_update_data_wp_stats`,
  via `$cron_schedule = 'nx_wp_stats_interval'`) calls `get_plugins_data()`, which hits
  `https://api.wordpress.org/stats/plugin/1.0/downloads.php` (or the `themes` variant)
  and stores the merged result via `update_notification()`.
- `nx_filtered_entry_wp_stats` → `conversion_data()` formats the raw numbers (today /
  yesterday / last_week / all_time / active_installs) into display strings using
  `Helper::nice_number()`.
- `nx_frontend_get_entries` → `slice_product_name()` truncates `plugin_theme_name` per
  the `wp_stats_product_name_length` responsive setting before the entry reaches the
  frontend.

`FreemiusStats` (`includes/Extensions/Freemius/FreemiusStats.php`) only sets
`init_extension()` (title + an upsell `popup` shown to non-Pro users); its actual
data-fetching logic is `_TODO: verify_` (not present in the free-plugin file — likely
lives in `notificationx-pro`).

## Fields & settings schema

- `content_fields()` on the Type class (`DownloadStats::content_fields`, hooked at
  priority 20) re-applies `Rules::is('type', $this->id, ...)` to the `random_order`
  content field so it only shows for this type.
- `link_types()` adds a single option to the global Link Type field: `stats_page` →
  "Product Page", scoped to this type via `GlobalFields::normalize_fields(..., 'type',
  $this->id)`.
- `preview_entry()` overrides the builder preview image with a fixed NotificationX
  plugin icon (`https://ps.w.org/notificationx/assets/icon-256x256.gif`) regardless of
  the real product being configured.
- The `wp_stats` Extension (`WPOrgStats::content_fields()`) contributes the
  source-specific fields that actually matter to the end user:
  - `wp_stats_product_type` — select, `plugin` | `theme`, default `plugin`.
  - `wp_stats_slug` — text, the WordPress.org plugin/theme slug to fetch stats for.
  - `wp_stats_product_name_length` — `responsive-number` (desktop/mobile), truncates
    `plugin_theme_name` in the rendered entry.
  All three are gated with `Rules::is('source', 'wp_stats')` so they only appear when
  the WP.org source is selected.

## Themes / templates

`$themes` (standard, non-responsive):

| Theme key | Image shape | Template |
|---|---|---|
| `today-download` | square | `first_param=tag_plugin_theme_name`, `third_param=tag_today`, `fourth_param=tag_today_text` |
| `7day-download` | rounded | `third_param=tag_last_week`, `fourth_param=tag_last_week_text` |
| `actively_using` | rounded | `first_param=tag_active_installs`, `third_param=tag_plugin_theme_name` (no fourth param) |
| `total-download` | circle | `third_param=tag_all_time`, `fourth_param=tag_all_time_text` |

`$res_themes` (responsive, all `is_pro => true`):

| Theme key | Image shape | Underlying `_template` |
|---|---|---|
| `res-today-download` | square | `wp_stats_template_new` |
| `res-7day-download` | rounded | `wp_stats_template_new` |
| `res-actively_using` | rounded | `actively_using_template_new` |
| `res-total-download` | circle | `wp_stats_template_new` |

`$templates` used by the responsive themes:

- `wp_stats_template_new` — tokens `tag_plugin_theme_name` (first), `tag_today` /
  `tag_last_week` / `tag_all_time` / `tag_active_installs` (third), and matching
  `_text` variants (fourth); applies to `download_stats_today-download`,
  `download_stats_7day-download`, `download_stats_total-download`.
- `actively_using_template_new` — swaps first/third param roles for the
  "actively using" phrasing; applies to `download_stats_actively_using`.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/DownloadStats.php`](../../includes/Types/DownloadStats.php) |
| Trait | none |
| Extensions | [`includes/Extensions/WordPress/WPOrgStats.php`](../../includes/Extensions/WordPress/WPOrgStats.php), [`includes/Extensions/Freemius/FreemiusStats.php`](../../includes/Extensions/Freemius/FreemiusStats.php) |
| Frontend runtime | `_TODO: verify_` — no `download_stats`-specific React component was found under `nxdev/notificationx/frontend/`; it likely renders through the generic notification component used for `normalize()`-shaped types |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (generic `active` bucket routing; no type-specific branch) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'download_stats' => 'NotificationX\Types\DownloadStats'`), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'wp_stats' => ...WPOrgStats`, `'freemius_stats' => ...FreemiusStats`) |

## Dependencies

- **WP.Org Stats (`wp_stats`)** — none; core WordPress only. Fetches public data from
  `api.wordpress.org` for the configured plugin/theme slug.
- **Freemius (`freemius_stats`)** — Pro-only (`$is_pro = true`); actual data source
  integration is `_TODO: verify_` (not implemented in this free-plugin codebase).

`CustomNotification` (`includes/Extensions/CustomNotification/CustomNotification.php`)
also references `download_stats` — but only to look up its theme list
(`get_themes_for_type('download_stats')`) for the Custom Notification builder; it is
not a data-source Extension for this type.

## Testing notes & gotchas

- `wp_stats_slug` must be a real, published WordPress.org plugin/theme slug or the
  remote API calls in `WPOrgStats::get_plugins_data()` return empty stats.
- The cron key is `nx_wp_stats_interval` / action `nx_cron_update_data_wp_stats` —
  verify the cron is actually scheduled if numbers appear stale.
- Because this type has no dedicated `FrontEnd.php` routing branch, changes to the
  generic `active`/`entries` pipeline affect this type along with every other
  "standard" (non-popup, non-bar) notification type — test broadly, not in isolation.
- `preview_entry()` always swaps in the NotificationX plugin icon for the builder
  preview — don't mistake this for a bug when the real product's icon doesn't show in
  the admin preview.
- No PHP tests specific to this type were found under `tests/`. `_TODO: verify_`.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [`includes/Extensions/WordPress/WPOrgStats.php`](../../includes/Extensions/WordPress/WPOrgStats.php)
- [`includes/Extensions/Freemius/FreemiusStats.php`](../../includes/Extensions/Freemius/FreemiusStats.php)
