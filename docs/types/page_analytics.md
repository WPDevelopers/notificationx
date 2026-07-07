# Page Analytics Notification Type (`page_analytics`)

> Shows a "toast" popup with a live/aggregated Google Analytics page-view count (e.g.
> "1,234 people visited this page in last 30 Days") to build social proof from real
> site-traffic numbers. Entirely a Pro-gated type — `$is_pro = true` on the Type
> itself, and its only data-source extension is also Pro.

## At a glance

| | |
|---|---|
| **Type ID** | `page_analytics` |
| **Class** | [`includes/Types/PageAnalytics.php`](../../includes/Types/PageAnalytics.php) |
| **Trait** | none — no `includes/Types/Traits/PageAnalytics.php` exists; the class only uses the generic `GetInstance` trait |
| **Priority** | `70` |
| **`is_pro`** | `true` (the whole Type, not just individual themes) |
| **Default source** | `google` (`$default_source`) — matches `Google_Analytics::$id` |
| **Default theme** | Not seeded — `$default_theme` / `$default_res_theme` are inherited from `Types` (`''`) and never set in `PageAnalytics::init()`, and the `Google_Analytics` extension does not set them either. Since `Extension::__source_trigger()` ([includes/Extensions/Extension.php:217-224](../../includes/Extensions/Extension.php#L217-L224)) only emits a `@themes:<id>` pre-fill when `default_theme` is non-empty, this source seeds no initial theme server-side. |
| **Module gate (`$module`)** | Declares `['modules_google_analytics']` on the Type object, but — same as documented for `conversions` in [conversions.md](conversions.md) — `TypeFactory::register_types()` ([includes/Types/TypesFactory.php](../../includes/Types/TypesFactory.php)) never reads `Types::$module`. What actually gates loading is the `Google_Analytics` extension's own `$module = 'modules_google_analytics'`, checked via `Modules::get_instance()->is_enabled($obj->module)` in `ExtensionFactory::register_extensions()` ([includes/Extensions/ExtensionFactory.php](../../includes/Extensions/ExtensionFactory.php)). |
| **Compatible extensions** | Only one: [`Google_Analytics`](../../includes/Extensions/Google/Google_Analytics.php) (`$id = 'google'`, `$types = 'page_analytics'`) |

## What it does

`PageAnalytics::init()` ([PageAnalytics.php:43](../../includes/Types/PageAnalytics.php#L43)) sets the admin title to "Page Analytics" and a Pro-upsell `popup` (video + "Upgrade to PRO" CTA — consistent with `is_pro = true`), then registers four themes (`pa-theme-one` … `pa-theme-four`) and four responsive themes (`res-pa-theme-one` … `res-pa-theme-four`, all `is_pro => true`).

Each theme's `template` config picks a `first_param` (which GA metric to show: total site views, realtime site views, or "current page view"), a static `second_param` phrase, a `third_param` (site title vs. a custom label), and time-window params (`ga_fourth_param`, `ga_fifth_param`, `sixth_param` = day/month/year). `pa-theme-four` is the odd one out: it uses `tag_current_page_view` and ships `defaults.link_button = true` with a "Grab Now" CTA — the other three default to `link_button => false, link_type => 'none'`.

`preview_entry()` ([PageAnalytics.php:190](../../includes/Types/PageAnalytics.php#L190)) is a trivial override that stubs the admin-builder preview entry with `"title" => "WPDeveloper"` (hooked automatically via `Types::__construct()`'s `add_filter("nx_preview_entry_{$this->id}", ...)`).

## Data flow

1. `Google_Analytics::get_data()` ([includes/Extensions/Google/Google_Analytics.php:60](../../includes/Extensions/Google/Google_Analytics.php#L60)) is the extension's data-fetch entry point. **In this (free) plugin it is a stub** — it literally `return 'Hello From Google Analytics';`. The real GA API integration (OAuth, reporting API calls, caching) is not present in this repo. **Verified (Pro):** `NotificationXPro\Extensions\Google_Analytics\Google_Analytics` (in [`notificationx-pro/includes/Extensions/Google/Google_Analytics.php`](../../../notificationx-pro/includes/Extensions/Google/Google_Analytics.php)) `extends` the free class but does **not** override `get_data()` — the stub is never used. The real pipeline is instead: (a) OAuth/connection is handled on the settings page via a `Google_Client` (fields `ga_connect`/`ga_own_app`/`ga_profile`/`ga_cache_duration`, connect/disconnect/save wired to the `notificationx/v1/api-connect` REST route); connection state persists under the `nx_pa_settings` option (`$option_key`). (b) On save (`get_notification_ready()`/`update_data()`) and on a cron — the extension sets `$cron_schedule = 'nx_ga_cache_duration'`, and `nx_cron_update_data_{$id}` → `update_data()` → `insert_data()` → `new Google_Analytics_Updater(...)` — it pulls view counts from the GA4 Reporting API (`Google_Analytics_V4::get_view()`, in `includes/Extensions/Google/inc/`) and writes them into the Entries DB table. (c) `fallback_data()` maps the fetched numbers onto the template tags (`siteview`, `realtime_siteview`, `current_page_view`, `ga_title`, plus `day`/`month`/`year` for the time window).
2. `Google_Analytics::$option_key = 'nx_pa_settings'` is where GA connection/account settings are expected to be persisted (as a WP option), per the property's docblock.
3. Legacy migration code in [includes/Core/Migration.php:941-1000](../../includes/Core/Migration.php#L941) (old meta-box → new settings-array upgrade path) shows the historically expected shape: `source`, `themes`, per-theme color/border/image fields, `notification-template.{first,second,third,custom_third,ga_fourth,ga_fifth,sixth}_param`, and a cron key `nx_ga_cache_duration` set via `Cron::get_instance()->set_cron($nx_id, 'nx_ga_cache_duration')` when the notification is enabled — implying GA data is fetched/cached on a schedule rather than live per-request. This is migration code, not the current data pipeline; treat it as corroborating evidence only.
4. `includes/FrontEnd/FrontEnd.php` has no `page_analytics`-specific branch found in this pass (only generic `analytics_*` settings keys related to NotificationX's *own* usage-analytics opt-in, unrelated to this Type) — entries for this type flow through the same generic entry/render pipeline as other types. **Verified:** there is no `page_analytics`-specific route — it is served by the **generic frontend notifications route `POST notificationx/v1/notice`** (registered in [`includes/Core/REST.php`](../../includes/Core/REST.php) ~line 238; callback `REST::notice()` → `FrontEnd::get_notifications_data($params)`). The React runtime ([`nxdev/notificationx/frontend/core/useNotificationX.ts`](../../nxdev/notificationx/frontend/core/useNotificationX.ts) ~line 192-206) POSTs to `notice/` with `extra: { ...extras, url: location.pathname, page_title: document.title }`. The Pro GA extension keys off exactly those values: `frontend_filter_ga_entries()` (hooked on `nx_frontend_get_entries`) reads `$params['extra']['url']`/`['page_title']` to serve/refresh per-URL cached entries (cache window = `ga_cache_duration` minutes, re-fetching via `Google_Analytics_Updater` when stale), and `frontend_filter_ga_query()` (hooked on `nx_get_entries_query_part_{$id}`) appends `AND entry_key = '<url>'` for the `page_analytics_pa-theme-four` (current-page-view) theme. (The admin-only `notificationx/v1/api-connect` route is for GA connect/disconnect/save, not for serving notifications.)
5. `includes/Features/ShortcodeInline.php:127` explicitly excludes `page_analytics` from the generic "wrap title in `<a href>`" linking behavior applied to other types — consistent with its themes generally defaulting to `link_type => 'none'`.

## Fields & settings schema

`PageAnalytics` does not override `init_fields()` — it inherits the no-op from `Types::init_fields()`. No type-specific field arrays are declared in this class; fields would come from `GlobalFields` (generic notification-template / link-options / theme fields) plus whatever `Google_Analytics::init_extension()` adds. `Google_Analytics::init_extension()` ([Google_Analytics.php:48](../../includes/Extensions/Google/Google_Analytics.php#L48)) only sets `$this->title` / `$this->module_title` — it registers no extension-specific fields in this pass.

One cross-type field this type participates in: `GlobalFields.php:1010` includes `page_analytics` in the `Rules::includes('type', [...])` list that shows the generic **`link_button`** checkbox field (Link Options tab) for this type.

## Themes / templates

`$this->themes`:

| Theme ID | `first_param` | Notes |
|---|---|---|
| `pa-theme-one` | `tag_siteview` | Default image shown; no image_shape override |
| `pa-theme-two` | `tag_siteview` | `image_shape => 'rounded'` |
| `pa-theme-three` | `tag_realtime_siteview` | `image_shape => 'circle'` |
| `pa-theme-four` | `tag_current_page_view` | `image_shape => 'circle'`; only theme with `link_button => true` + "Grab Now" CTA |

`$this->res_themes`: `res-pa-theme-one` … `res-pa-theme-four`, all `is_pro => true` (no `_template` tagging visible on these entries, unlike some other types).

`$this->templates` maps two content templates to theme groups via `_themes`:
- **`pa_template_new`** — covers `pa-theme-one`/`two`/`three`; offers `first_param` options `tag_siteview` / `tag_realtime_siteview`, `third_param` option `ga_title` (Site Title), `sixth_param` options day/month/year.
- **`pa_template_current_page_view`** — covers `pa-theme-four`; offers `first_param` option `tag_current_page_view`, `third_param` option `tag_ga_page_title` (Page Title, note the different tag name vs. `ga_title` used in `pa_template_new`), `sixth_param` day/month/year.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [includes/Types/PageAnalytics.php](../../includes/Types/PageAnalytics.php) |
| Trait | none |
| Base class | [includes/Types/Types.php](../../includes/Types/Types.php) |
| Extension | [includes/Extensions/Google/Google_Analytics.php](../../includes/Extensions/Google/Google_Analytics.php) |
| Factory registration | [includes/Types/TypesFactory.php:36](../../includes/Types/TypesFactory.php#L36), [includes/Extensions/ExtensionFactory.php:49](../../includes/Extensions/ExtensionFactory.php#L49) |
| Legacy migration shape | [includes/Core/Migration.php:941](../../includes/Core/Migration.php#L941) |
| PHP frontend | [includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) (no type-specific branch found; generic pipeline) |

## Dependencies

Google Analytics (via the `google` / `Google_Analytics` extension) is the **only** data source registered for this type in the free plugin. Both the Type (`is_pro = true`) and every responsive theme (`is_pro => true`) and the extension itself (`Google_Analytics::$is_pro = true`) are Pro-gated — this type is non-functional/upsell-only without NotificationX Pro. The actual GA API/OAuth integration is not in this repo (`get_data()` is a stub); confirm real behavior in `notificationx-pro`.

## Testing notes & gotchas

- `Google_Analytics::get_data()` is a stub (`'Hello From Google Analytics'`) in the free plugin — do not expect real analytics numbers when testing against this repo alone; the Pro plugin supplies the actual implementation.
- Like `conversions`, the `Types::$module` array on this class (`['modules_google_analytics']`) is **not** what gates the type — verify module-enable behavior via the extension's own `$module` key and `Modules::is_enabled()`, not by editing this property.
- `default_theme` / `default_res_theme` are never set on this Type (both `''`, inherited from `Types`) and the `Google_Analytics` extension doesn't set them either, so `Extension::__source_trigger()` emits no `@themes:` pre-fill trigger — no theme is seeded server-side for a brand-new Page Analytics notification (any first-theme fallback would be a client-side QuickBuilder concern, not driven from PHP).
- Note the third-param tag name mismatch between the two templates: `pa_template_new` uses `ga_title`, `pa_template_current_page_view` uses `tag_ga_page_title` — a naming inconsistency worth being aware of if extending either template.
- `ShortcodeInline.php:127` explicitly special-cases (excludes) `page_analytics` from its generic title-linking logic — if adding link behavior to this type, that exclusion may need revisiting.
- No dedicated deep-dive doc exists for this type's theme/design system (unlike Exit Intent or Sales Notification) — if adding a new `pa-theme-*`, follow the general pattern in [../development/adding-a-notification-type.md](../development/adding-a-notification-type.md) and cross-check the sibling free `Conversions` design-doc ([../features/sales-notification/add-new-design.md](../features/sales-notification/add-new-design.md)) for the PHP-registry + `GetTemplate.ts` + SCSS checklist shape, adapting to this type's own template ids.
- No tests under `tests/` reference `page_analytics` or `PageAnalytics` — confirmed: `grep -rli "page_analytics\|PageAnalytics" tests/` returns no hits (the factory tests use `popup` as their representative fixture).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [conversions.md](conversions.md) — sibling type doc with the same `Types::$module`-is-not-authoritative caveat, explained in more depth
- [docs/types/_TEMPLATE.md](_TEMPLATE.md) — template this doc was generated from
