# Google Extension (`modules_google_reviews` / `modules_google_analytics` / `modules_google_youtube`)

> Bundles three unrelated Google integrations under one directory: **Google Reviews**
> (place ratings/reviews → `reviews` Type), **Google Analytics** (site traffic →
> `page_analytics` Type), and **YouTube** (channel/video stats → `video` Type). All three
> are `$is_pro = true` — in this free plugin they register as sources/modules and provide
> UI (themes, templates, docs, pro-upgrade popups) but the actual third-party API calls
> live in the sibling `notificationx-pro` plugin.

## At a glance

| | |
|---|---|
| **Integration** | Google (Reviews, Analytics, YouTube — three separate integrations, one directory) |
| **Directory** | [`includes/Extensions/Google/`](../../includes/Extensions/Google/) |
| **Module key(s) (`$module`)** | `modules_google_reviews` (GoogleReviews), `modules_google_analytics` (Google_Analytics), `modules_google_youtube` (YouTube) |
| **Feeds Types** | `reviews` ([`Types\Reviews`](../../includes/Types/Reviews.php)) ← GoogleReviews; `page_analytics` ([`Types\PageAnalytics`](../../includes/Types/PageAnalytics.php)) ← Google_Analytics; `video` ([`Types\Video`](../../includes/Types/Video.php)) ← YouTube |
| **Extension classes** | `GoogleReviews.php` → `$id = 'google_reviews'`, `$types = 'reviews'`; `Google_Analytics.php` → `$id = 'google'`, `$types = 'page_analytics'`; `YouTube.php` → `$id = 'youtube'`, `$types = 'video'` |
| **Depends on** | Google Places API (Reviews), Google Analytics (site property), YouTube Data API v3 (YouTube) — see [Dependency & detection](#dependency--detection). None of the three classes set `$class`/`$function`/`$constant`, so the free plugin does no presence check at all; real API-key/auth gating happens in `notificationx-pro` |

## What it does

Three independent `Extension` subclasses live in this one directory (an exception to the
usual one-integration-per-directory layout — the shared factor is just "Google"):

- **`GoogleReviews`** (`$id = 'google_reviews'`) pairs with the `reviews` Type. It defines
  six themes (`total-rated`, `reviewed`, `review-comment`, `review-comment-2`,
  `review-comment-3`, `maps_theme` — the last marked `is_pro`), a single `_template_new`
  template group, a `preview_entry()` override that swaps in a generic Google-Places
  business icon for the `greview_icon` image option, and a `doc()` pointing at the
  plugin's API-integrations settings tab and Google-Reviews docs.
- **`Google_Analytics`** (`$id = 'google'`, note the class is namespaced
  `NotificationX\Extensions\Google_Analytics`, not `...\Google`) pairs with
  `page_analytics`. It sets `$option_key = 'nx_pa_settings'` (where its settings are
  expected to be stored) and defines no themes of its own in `init_extension()` — themes
  for this Type live on `Types\PageAnalytics` instead (see `get_themes()` fallback in
  [`Extension.php`](../../includes/Extensions/Extension.php)).
- **`YouTube`** (`$id = 'youtube'`) pairs with `video`. It defines two "channel" themes and
  four "video" themes plus matching `res_themes` (responsive variants), a
  `$cron_schedule = 'nx_youtube_interval'`, `$api_base = 'https://youtube.googleapis.com/youtube/v3/'`,
  and a `fallback_data()` override that maps saved meta (`_yt_views`, `_yt_subscribers`,
  `_yt_videos`, `_yt_likes`, `_yt_comments`, `_yt_favorites`) through
  `Helper::nice_number()` for display.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source (`get_data()`) |
|---|---|---|---|---|
| [`GoogleReviews.php`](../../includes/Extensions/Google/GoogleReviews.php) | `reviews` | `google_reviews` | `modules_google_reviews` | No `get_data()` in this class. [`REST.php`](../../includes/Core/REST.php) routes `type=reviews&source=google_reviews` to `GoogleReviews::get_instance()->restResponse(...)`, which is the base `Extension::restResponse()` (just `error_log()`s the params) — no real Places-API fetch exists in the free plugin |
| [`Google_Analytics.php`](../../includes/Extensions/Google/Google_Analytics.php) | `page_analytics` | `google` | `modules_google_analytics` | `get_data()` is defined but is a stub: `return 'Hello From Google Analytics';` — no real GA call |
| [`YouTube.php`](../../includes/Extensions/Google/YouTube.php) | `video` | `youtube` | `modules_google_youtube` | No `get_data()` in this class. Data is expected to arrive as saved meta (`_yt_views`, `_yt_subscribers`, `_yt_videos`, `_yt_likes`, `_yt_comments`, `_yt_favorites`) that `fallback_data()` formats; nothing in this directory populates that meta from the YouTube API |

## Data flow

None of the three classes implement a working fetch-and-store path in the free plugin:

- `GoogleReviews` has no cron hook, no `save_post`/`saved_post` override, and its only
  data-adjacent method is the inherited `restResponse()` stub reached via
  `REST.php`'s `type=reviews&source=google_reviews` branch.
- `Google_Analytics`'s `get_data()` returns a hardcoded string and is never invoked in
  the free plugin: `ExtensionFactory::getExtension()` (the only method that would call
  `get_data()` for this source) has **no callers anywhere** in the codebase (confirmed by
  grep), and `REST.php`'s `page_analytics`/`google` branch falls through to the default
  case, which calls `restResponse()`, not `get_data()`. So nothing reaches this stub.
- `YouTube` sets `$cron_schedule = 'nx_youtube_interval'`, which the base
  `Extension::add_cron_job()` hooks onto `nx_saved_post_{$this->id}` — so a cron event is
  scheduled when a YouTube notification post is saved — but no handler for
  `nx_youtube_interval` exists in this free-plugin directory; `fallback_data()` only
  formats meta that must already be populated by something else.

**Verified in source (context only, not part of this repo):** the sibling
`notificationx-pro` plugin ships `NotificationXPro\Extensions\Google\GoogleReviews`,
`NotificationXPro\Extensions\Google_Analytics\Google_Analytics`, and
`NotificationXPro\Extensions\Google\YouTube`, each `extends`-ing the corresponding free
class above and adding the real API calls (Google Places, Google Analytics/GA4 via
`inc/class-nxpro-ga-updater.php` + `inc/class-nxpro-ga4.php`, and the YouTube Data API),
plus their own cron intervals (e.g. `$cron_schedule = 'nx_google_review_cache_duration'`,
`'nx_ga_cache_duration'` overriding the free defaults). Pro's own
`ExtensionFactory::extension_classes()` filter callback (meant to swap these into
`nx_extension_classes`) is present but its body just returns the array unchanged and the
`add_filter` call registering it is commented out — mirroring the same pattern documented
for [Envato](envato.md). The mechanism that actually makes the Pro subclasses active is the
free→pro swap in [`GetInstance::get_instance()`](../../includes/GetInstance.php) (confirmed
in source: it rewrites the `NotificationX\` prefix to `NotificationXPro\` and instantiates
that subclass when it exists and `is_subclass_of` the free class) — the same mechanism
documented for [Zapier](zapier.md), independent of the commented-out factory filter.

## Fields & settings

None of the three classes add extension-specific fields via `init_fields()` /
`init_settings_fields()` (both are left as no-ops, inherited from `Extension`). The only
Google/YouTube-aware entry in [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php)
is `"youtube"` in the shared **show-notification-image** field's source-dependency list
(line ~1589), so the generic image-source options apply to YouTube notifications too.
`GoogleReviews.doc()` and `YouTube.doc()` both point at
`admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_..._settings_section')`
for API-key configuration, but the actual settings-field definitions for those sections
are not in this directory or in `GlobalFields.php` — they are defined in
`notificationx-pro` (its `includes/Admin/Settings.php` and the Pro Google extension
classes, confirmed by grep) and/or the React admin app (`nxdev/`).

## Dependency & detection

- **Required service:** Google Places API key (Reviews), a connected Google Analytics
  property (Analytics), a YouTube Data API v3 key (YouTube) — all referenced only in
  `doc()` copy and admin-settings deep links, not enforced in code here.
- **Detection:** none of the three classes set `$class`, `$function`, or `$constant` on
  the base `Extension`, so `Extension::is_active()` (see
  [`Extension.php`](../../includes/Extensions/Extension.php)) only checks whether the
  source has been selected as an active notification item — there is no third-party
  presence/credential check in the free plugin for any of the three.
- **When absent (no API key/connection, Pro inactive):** the source still appears in the
  builder's source picker (behind the pro-upgrade `$popup` when Pro isn't licensed, e.g.
  "Upgrade to PRO" linking to `notificationx.com/#pricing`), but no real data is ever
  fetched — `GoogleReviews`/`YouTube` simply have nothing wired to fetch it, and
  `Google_Analytics::get_data()` returns its placeholder string.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/Google/GoogleReviews.php`](../../includes/Extensions/Google/GoogleReviews.php), [`includes/Extensions/Google/Google_Analytics.php`](../../includes/Extensions/Google/Google_Analytics.php), [`includes/Extensions/Google/YouTube.php`](../../includes/Extensions/Google/YouTube.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Types fed | [`includes/Types/Reviews.php`](../../includes/Types/Reviews.php), [`includes/Types/PageAnalytics.php`](../../includes/Types/PageAnalytics.php), [`includes/Types/Video.php`](../../includes/Types/Video.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'google' => 'NotificationX\Extensions\Google_Analytics\Google_Analytics'`, `'google_reviews' => 'NotificationX\Extensions\Google\GoogleReviews'`, `'youtube' => 'NotificationX\Extensions\Google\YouTube'`) |
| REST routing (Reviews) | [`includes/Core/REST.php`](../../includes/Core/REST.php) (`case 'google_reviews':` under `type=reviews`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Pro implementation (out of this repo's scope) | `notificationx-pro/includes/Extensions/Google/GoogleReviews.php`, `Google_Analytics.php`, `Youtube.php` (sibling plugin) |

## Testing notes & gotchas

- `Google_Analytics::get_data()` returning a hardcoded string is a placeholder — do not
  treat it as reflecting real GA data; the real fetch is in Pro's GA4 updater
  (`inc/class-nxpro-ga4.php`, `inc/class-nxpro-ga-updater.php`).
- `GoogleReviews` and `YouTube` have no `get_data()` at all in the free plugin — any
  "reviews came in" / "video stats updated" behavior you see on a live site is coming from
  `notificationx-pro`, not this directory.
- The class file is `Google_Analytics.php` but its namespace is
  `NotificationX\Extensions\Google_Analytics` (distinct from the `NotificationX\Extensions\Google`
  namespace used by `GoogleReviews.php` and `YouTube.php`, despite living in the same
  `Google/` directory) — easy to mis-`use` when adding new code.
- `YouTube::$cron_schedule = 'nx_youtube_interval'` schedules a cron event on save
  (via `Extension::add_cron_job()`), but no handler for that hook exists in the free
  plugin — confirm in Pro before assuming any polling happens without it active.
- Per this repo's `CLAUDE.md`, Pro-only logic lives in the sibling `notificationx-pro`
  plugin — the Pro details above are included only as necessary context for what the free
  classes are stubs/UI-registrations for.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Envato Extension](envato.md) — same free-stub / Pro-implements-the-real-logic pattern
