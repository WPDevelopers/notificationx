# WordPress Extension (`modules_wordpress`)

> Surfaces three kinds of WordPress-native activity as notifications: new blog
> comments (via WP core comments), WordPress.org plugin/theme reviews, and
> WordPress.org plugin/theme download stats. No third-party plugin is required —
> all data comes from WordPress core itself (local comments table) or the public
> wordpress.org API (`plugins_api()` / `themes_api()`).

## At a glance

| | |
|---|---|
| **Integration** | `WordPress` |
| **Directory** | [`includes/Extensions/WordPress/`](../../includes/Extensions/WordPress/) |
| **Module key(s) (`$module`)** | `modules_wordpress` (all three classes share this one module) |
| **Feeds Types** | `comments`, `reviews`, `download_stats` |
| **Extension classes** | `WPComments.php` (type `comments`, id `wp_comments`), `WPOrgReview.php` (type `reviews`, id `wp_reviews`), `WPOrgStats.php` (type `download_stats`, id `wp_stats`) |
| **Depends on** | WordPress core only: `comment_post`/`transition_comment_status` hooks + `get_comments()` for `WPComments`; the wordpress.org plugin/theme API via core's `plugins_api()` / `themes_api()` for `WPOrgReview` / `WPOrgStats`. No `class_exists`/`function_exists` gate on a third-party plugin — see [Dependency & detection](#dependency--detection). |

## What it does

From the user's perspective, enabling the "WordPress" module unlocks three independent
notification sources, each tied to a different Type:

- **WP Comments** (`comments` type): shows real, approved comments left on the site's
  own posts. Driven by WordPress's native comment lifecycle hooks — no external
  service, no settings beyond picking this as the source.
- **WP.Org Reviews** (`reviews` type): the user enters a plugin slug
  (`wp_reviews_slug`) and NotificationX scrapes/parses that plugin's reviews section
  from the `plugins_api('plugin_information', …)` response (WP core's own client for
  the wordpress.org API) via `WPOrg_Helper`. Refreshed on a cron interval.
- **WP.Org Stats** (`download_stats` type): the user enters a plugin or theme slug
  (`wp_stats_product_type` + `wp_stats_slug`) and NotificationX pulls
  download/active-install stats for that plugin/theme from `plugins_api()` /
  `themes_api()` plus `https://api.wordpress.org/stats/{plugin|themes}/1.0/downloads.php?slug=...&historical_summary=1`.
  Also refreshed on a cron interval.

All three classes share the `WordPress` trait ([`Wordpress.php`](../../includes/Extensions/WordPress/Wordpress.php))
which only supplies the `doc()` method (the "Instructions" help text shown in the
admin builder) — it has no data-fetching logic.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source (`get_data()` / equivalent) |
|---|---|---|---|---|
| [`WPComments.php`](../../includes/Extensions/WordPress/WPComments.php) | `comments` | `wp_comments` | `modules_wordpress` | No `get_data()`; hooks `comment_post`, `trash_comment`, `deleted_comment`, `transition_comment_status`. `get_comments()` calls core `get_comments()` (status `approve`, date-filtered) and `add()` normalizes each `WP_Comment` (author name/email/IP, post title/link, timestamp, gravatar-ready fields) into an entry. |
| [`WPOrgReview.php`](../../includes/Extensions/WordPress/WPOrgReview.php) | `reviews` | `wp_reviews` | `modules_wordpress` | `update_data()` → `get_plugins_data()` → `WPOrg_Helper::get_plugin_reviews($slug)` (calls `plugins_api()` for `reviews`/`icons` sections) → `WPOrg_Helper::extract_reviews_from_html()` (DOM-parses the reviews HTML fragment into `username`, `avatar`, `content`, `title`, `timestamp`, `rating`, filtered by `nx_wp_reviews_rating_condition`, default min rating 3). Only `wp_reviews_product_type = 'plugin'` is implemented; theme reviews (`get_theme_reviews`) is a stub returning `[]`. Runs on cron `nx_wp_review_interval`. |
| [`WPOrgStats.php`](../../includes/Extensions/WordPress/WPOrgStats.php) | `download_stats` | `wp_stats` | `modules_wordpress` | `update_data()` → `get_plugins_data()` → for `wp_stats_product_type = 'plugin'`: `WPOrg_Helper::get_plugin_stats($slug)` (via `plugins_api()`) merged with `https://api.wordpress.org/stats/plugin/1.0/downloads.php?...&historical_summary=1`; for `'theme'`: `WPOrg_Helper::get_theme_stats($slug)` (via `themes_api()`) merged with the themes-stats historical-summary endpoint. Produces `today`/`yesterday`/`last_week`/`all_time`/`active_installs` plus plugin/theme name, icons/screenshot. Runs on cron `nx_wp_stats_interval`. |

`WPOrg_Helper.php` is not itself an Extension — it is a plain helper class
(DOM/XPath scraping utilities + the `plugins_api()`/`themes_api()` calls) used by
both `WPOrgReview` and `WPOrgStats`.

## Data flow

- **WPComments**: `comment_post` (new approved comment) or
  `transition_comment_status` (unapproved→approved) → `post_comment()` builds one
  entry via `add()` → `Extension::update_notification()` inserts/updates a row in
  the entries table (`Entries`/`Database`). `transition_comment_status` to
  `unapproved` calls `delete_comment()` → `Extension::delete_notification()`. First
  time a notification is created, `get_notification_ready()` backfills using
  `get_comments()` over a `display_from`/`display_last` window (see `Helper::generate_time_string`).
- **WPOrgReview / WPOrgStats**: on `saved_post` (notification saved in the builder)
  and on the recurring WP-Cron event (`nx_cron_update_data_{id}`, scheduled via
  `Extension::add_cron_job()` using `$cron_schedule`), `update_data($nx_id)` fetches
  fresh data from wordpress.org, deletes old entries for that `nx_id`
  (`delete_notification`), and calls `update_notifications()`/`update_notification()`
  to (re)insert entries. Cron interval is configurable in Settings
  (`settings.reviews_cache_duration`, `settings.download_stats_cache_duration`,
  default 3 minutes each — see [`includes/Admin/Cron.php`](../../includes/Admin/Cron.php) `cron_schedule()`).
- Entries then flow through the standard FrontEnd → REST → React pipeline shared by
  all extensions (`nx_frontend_get_entries`, `nx_filtered_entry_{id}` /
  `conversion_data`, `nx_fallback_data_{id}`, `nx_notification_image_{id}` filters
  each class hooks into for display-time shaping — comment trimming, rating/name
  fallbacks, review/plugin image selection).

## Fields & settings

- `WPComments`: adds no builder Content fields beyond the shared ones; relies on
  `fallback_data()` for placeholder text (`name`, `post_comment`, etc.) and
  `conversion_data()` to trim/quote the comment body per theme.
- `WPOrgReview` adds via `content_fields()` (hooked on `nx_content_fields`):
  `wp_reviews_product_type` (select, only `plugin` option currently),
  `wp_reviews_slug` (text), `wp_reviews_product_name_length` (responsive-number,
  default desktop 30 / mobile 20).
- `WPOrgStats` adds via `content_fields()`: `wp_stats_product_type` (select:
  `plugin` or `theme`), `wp_stats_slug` (text), `wp_stats_product_name_length`
  (responsive-number, default desktop 30 / mobile 20).
- All three field sets use `GlobalFields::get_instance()->normalize_fields()` to
  build their `select` options; no other `GlobalFields` field is reused directly.

## Dependency & detection

- None of the three classes set `$class`, `$function`, or `$constant` on
  `Extension`, so `Extension::is_active()` / `class_exists()` do **not** gate on a
  third-party plugin being installed — these sources are always available once the
  `modules_wordpress` module is enabled.
- `WPComments` depends only on WordPress core's native comments system (no
  detection needed).
- `WPOrgReview` / `WPOrgStats` depend on WordPress core's own
  `plugins_api()` (from `wp-admin/includes/plugin-install.php`, conditionally
  `require_once` if not already loaded) and `themes_api()` (from
  `wp-admin/includes/themes.php` / `theme.php`) to reach the wordpress.org API, plus
  a direct `wp_remote`-style call (`Extension::remote_get()` → `Helper::remote_get()`)
  to `api.wordpress.org/stats/...`. If the remote call fails or the slug is invalid,
  `get_plugins_data()`/`get_theme_stats()` return incomplete/empty arrays (e.g.
  `is_wp_error($this->theme_information)` short-circuits to `$new_data = []`) rather
  than throwing — no explicit "module hidden" behavior was found; this is
  `_TODO: verify_` for user-facing error messaging.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/WordPress/WPComments.php`](../../includes/Extensions/WordPress/WPComments.php), [`WPOrgReview.php`](../../includes/Extensions/WordPress/WPOrgReview.php), [`WPOrgStats.php`](../../includes/Extensions/WordPress/WPOrgStats.php) |
| Shared trait (doc/help text) | [`includes/Extensions/WordPress/Wordpress.php`](../../includes/Extensions/WordPress/Wordpress.php) |
| Scraping/API helper | [`includes/Extensions/WordPress/WPOrg_Helper.php`](../../includes/Extensions/WordPress/WPOrg_Helper.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`wp_comments`, `wp_reviews`, `wp_stats` map entries) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Cron schedules | [`includes/Admin/Cron.php`](../../includes/Admin/Cron.php) (`nx_wp_review_interval`, `nx_wp_stats_interval`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |

## Testing notes & gotchas

- `WPOrgReview::extract_reviews_from_html()` and the stats helpers parse HTML/XML
  from the wordpress.org API response with `DOMDocument`/`DOMXPath` — a markup
  change on wordpress.org's side (review markup classes, `h4` title tag, rating
  `data-rating` attribute) can silently break parsing. Verify after any suspicion of
  upstream markup changes.
- Theme reviews are unimplemented (`get_theme_reviews()` returns `[]`); only
  `wp_reviews_product_type = 'plugin'` actually produces data in `WPOrgReview`.
- Cron intervals default to 3 minutes but are user-configurable
  (`settings.reviews_cache_duration`, `settings.download_stats_cache_duration`) —
  confirm the schedule fires (`nx_cron_update_data_wp_reviews` /
  `nx_cron_update_data_wp_stats`) rather than assuming defaults after a settings
  change.
- `WPComments` relies on comment-status transitions; test both the "new approved
  comment" path (`comment_post`) and the "manually approved from Pending" path
  (`transition_comment_status`) since they use different entry points
  (`post_comment()` vs re-adding via `post_comment($comment_ID, 1)`).
- No dedicated tests for this integration were found under `tests/`;
  `_TODO: verify_` if coverage exists elsewhere.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
