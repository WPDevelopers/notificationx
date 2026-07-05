# Freemius Extension (`modules_freemius`)

> Connects NotificationX to [Freemius](https://freemius.com/) (a SaaS billing/licensing
> platform used by many WordPress plugin/theme sellers) to surface a seller's own
> **sales** (`conversions`), **reviews** (`reviews`), and **download/active-install
> stats** (`download_stats`) as notifications. In the free plugin this integration is a
> **UI-only stub / Pro teaser** for all three notification kinds — the code that actually
> calls the Freemius API lives in `notificationx-pro`.

## At a glance

| | |
|---|---|
| **Integration** | Freemius |
| **Directory** | [`includes/Extensions/Freemius/`](../../includes/Extensions/Freemius/) |
| **Module key(s) (`$module`)** | `modules_freemius` (all three classes) |
| **Feeds Types** | `conversions` (Sales — [`includes/Types/Conversions.php`](../../includes/Types/Conversions.php)), `reviews` ([`includes/Types/Reviews.php`](../../includes/Types/Reviews.php)), `download_stats` ([`includes/Types/DownloadStats.php`](../../includes/Types/DownloadStats.php)). All three Type classes explicitly list `modules_freemius` in their `$module` array alongside other sales/review/stats modules |
| **Extension classes** | `FreemiusConversions.php` → `$id = 'freemius_conversions'` / `$types = 'conversions'`; `FreemiusReviews.php` → `$id = 'freemius_reviews'` / `$types = 'reviews'`; `FreemiusStats.php` → `$id = 'freemius_stats'` / `$types = 'download_stats'` |
| **Depends on** | A Freemius developer account (Dev ID + Public/Secret keys). In the free plugin there is no real dependency check (`is_active()` gates purely on module + being selected as an active source); the actual Freemius API credential requirement lives in the Pro override — see [Dependency & detection](#dependency--detection) |

## What it does

The directory contains three near-identical classes — `FreemiusConversions`,
`FreemiusReviews`, `FreemiusStats` — each extending the base
[`Extension`](../../includes/Extensions/Extension.php) and sharing a common `Freemius`
**trait** ([`Freemius.php`](../../includes/Extensions/Freemius/Freemius.php)) that supplies
only a `doc()` method (the instructions/help text shown via `nx_instructions`, telling the
user to sign in to a Freemius account and linking to NotificationX docs/video). All three
are marked `$is_pro = true`, so in the free plugin their role is limited to:

- Registering their respective **source** (`freemius_conversions` / `freemius_reviews` /
  `freemius_stats`) in the builder's source picker (via `Extension::__nx_sources()`),
  showing the shared Freemius icon/label behind a **pro-upgrade popup**
  (`$popup['denyButtonText']` / `confirmButtonText` link to `notificationx.com/#pricing`).
  `FreemiusConversions` and `FreemiusStats` embed a YouTube walkthrough in their popup
  HTML; `FreemiusReviews`'s popup is text-only.
- Registering the shared `modules_freemius` module once (label "Freemius",
  `module_priority = 12`) so it appears in Settings.
- Setting `$title` / `$module_title` to `"Freemius"` in `init_extension()` — that is the
  **entirety** of each class's `init_extension()` override.

**None of the three classes define a `get_data()` method** (unlike most other
integrations in this codebase — see e.g. [`envato.md`](envato.md)). They add no fields,
no `save_post`/`saved_post` handlers, and no cron scheduling of their own in the free
plugin; `$cron_schedule` is left at the base `Extension` default (empty string).

### Pro implementation (context, not part of this doc's scope)

`notificationx-pro` ships parallel classes in the same directory name under its own
namespace — `NotificationXPro\Extensions\Freemius\{FreemiusConversions,FreemiusReviews,FreemiusStats}`
(`notificationx-pro/includes/Extensions/Freemius/*.php`) — each `extends` the
corresponding free class above and adds the real behaviour via a Pro-side `Freemius`
trait (`notificationx-pro/includes/Extensions/Freemius/Freemius.php`):

- `$cron_schedule = 'nx_freemius_interval'` is set, and `admin_actions()` hooks
  `nx_cron_update_data_{$this->id}` to an `update_data()` method.
- `init_settings_fields()` adds a `freemius_settings_section` (Dev ID, Public Key, Secret
  Key, cache duration, a "Connect" button hitting `/notificationx/v1/api-connect`) to the
  API Integrations settings tab.
- `init_fields()` adds Freemius-specific builder fields (`freemius_item_type`,
  `freemius_themes`, `freemius_plugins`, `freemius_bundles`, `freemius_addons`) via
  `content_fields()`.
- There is **no `get_data()`** in Pro either. Instead `update_data($nx_id, $settings)`
  dispatches by `$settings['type']` to `get_reviews()` (reviews), `get_stats()`
  (download_stats), or `get_conversions()` (conversions, defined on the Pro
  `FreemiusConversions` class, calling `subscriptions.json` / `users.json?filter=paid` /
  `pricing.json` via `$this->freemius()->Api(...)`), then rebuilds entries and calls
  `Extension::update_notifications()`.
- **Verified in source:** exactly the same pattern found in the Envato integration
  (see [`envato.md`](envato.md#pro-implementation-context-not-part-of-this-docs-scope))
  — the Pro `ExtensionFactory`'s `add_filter('nx_extension_classes', ...)` call that would
  swap the registered `freemius_conversions`/`freemius_reviews`/`freemius_stats` classes
  for these Pro ones is **commented out**
  (`notificationx-pro/includes/Extensions/ExtensionFactory.php`), and its
  `extension_classes()` callback body is empty. No other reference to the Pro Freemius
  classes was found anywhere in the Pro plugin outside their own directory. _TODO: verify_
  whether some other, unfound mechanism activates the Pro classes in production, or
  whether this is in-progress/incomplete wiring (same open question as Envato).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`FreemiusConversions.php`](../../includes/Extensions/Freemius/FreemiusConversions.php) | `conversions` | `freemius_conversions` | No `get_data()` in the free class — pure UI/module/source registration stub. Real fetching (`get_conversions()` → Freemius `subscriptions.json`/`users.json`/`pricing.json`) exists only in the Pro class, outside this directory |
| [`FreemiusReviews.php`](../../includes/Extensions/Freemius/FreemiusReviews.php) | `reviews` | `freemius_reviews` | No `get_data()` in the free class. Real fetching (`get_reviews()`, Pro-only) is outside this directory |
| [`FreemiusStats.php`](../../includes/Extensions/Freemius/FreemiusStats.php) | `download_stats` | `freemius_stats` | No `get_data()` in the free class. Real fetching (`get_stats()`, Pro-only) is outside this directory |

## Data flow

Because none of the three free classes implement `get_data()`, hook `save_post`/
`saved_post`, or schedule a cron job (`$cron_schedule` is empty), the free-plugin classes
do not themselves produce any notification entries on an ongoing basis.

[`includes/Core/Migration.php`](../../includes/Core/Migration.php) does contain historic,
one-time migration logic for a legacy `'freemius'` source, handled separately in the
migration switch for each of the three Types (`case 'freemius':` appears once per Type's
migration block). Each block:

1. Schedules the `nx_freemius_interval` cron via `Cron::get_instance()->set_cron($nx_id, 'nx_freemius_interval')` if the post is enabled.
2. Copies legacy meta (`_nx_meta_freemius_item_type`, `_nx_meta_freemius_themes`, `_nx_meta_freemius_plugins`) onto the migrated post and sets `$post['source']` to the matching new id (`freemius_conversions` / `freemius_reviews` / `freemius_stats`).
3. Reads previously stored data from `_nx_meta_freemius_content` and calls `{FreemiusConversions|FreemiusReviews|FreemiusStats}::get_instance()->update_notifications($sales)` to bulk-insert those legacy entries into the entries table.

This is one-time migration of old data, not an ongoing fetch path. The ongoing fetch path
(`update_data()` → `get_conversions()`/`get_reviews()`/`get_stats()` → cron
`nx_cron_update_data_{id}` → `Extension::update_notifications()` →
`Entries::insert_entries()`) is implemented entirely in the Pro classes described above.

## Fields & settings

No Freemius-specific builder fields are added by any of the three free classes — they
only implement `doc()` (via the shared `Freemius` trait) and `init_extension()`. The only
Freemius-aware entries in [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php)
are dependency-rule list memberships, e.g. all three source ids (`freemius_conversions`,
`freemius_reviews`, `freemius_stats`) are included in the shared
`show_notification_image` → `featured_image` default rule set, and in several other
`Rules::includes('source', [...])` lists alongside `woocommerce`, `edd`, `envato`, etc.
There is no Freemius-specific field registry in this directory. The real Freemius builder
fields (Item Type, Theme/Plugin/Bundle/Add-on pickers) and the API Integrations
settings section (Dev ID / Public Key / Secret Key / cache duration / Connect button) are
defined in the Pro trait's `content_fields()` and `api_integration_settings()`, not here.

## Dependency & detection

- **Required service:** a Freemius developer account with a Dev ID, Public Key, and
  Secret Key (`https://dashboard.freemius.com`), used only by the Pro classes.
- **Detection:** none of the three free classes set `$class`, `$function`, or `$constant`
  on the base `Extension`, so `is_active()` (see
  [`Extension.php`](../../includes/Extensions/Extension.php)) only checks whether the
  relevant source has been selected as an active item — there is no third-party presence
  check in the free plugin. The Pro trait instead gates functionally on
  `Settings::get('settings.freemius_dev_id')` / `freemius_dev_pk` / `freemius_dev_sk`
  being non-empty; `source_error_message()` (defined on the Pro `FreemiusConversions`
  class) shows an admin error linking to the API Integrations settings tab when any of
  those are missing.
- **When absent (no Dev ID/keys, Pro inactive/disabled):** the source still appears in
  the builder (behind the pro-upgrade popup when Pro is not licensed), but no real Freemius
  data is ever fetched or displayed.

## Key files

| Purpose | File |
|---|---|
| Extension classes (free/stub) | [`includes/Extensions/Freemius/FreemiusConversions.php`](../../includes/Extensions/Freemius/FreemiusConversions.php), [`FreemiusReviews.php`](../../includes/Extensions/Freemius/FreemiusReviews.php), [`FreemiusStats.php`](../../includes/Extensions/Freemius/FreemiusStats.php) |
| Shared `doc()` trait | [`includes/Extensions/Freemius/Freemius.php`](../../includes/Extensions/Freemius/Freemius.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Types it feeds | [`includes/Types/Conversions.php`](../../includes/Types/Conversions.php), [`includes/Types/Reviews.php`](../../includes/Types/Reviews.php), [`includes/Types/DownloadStats.php`](../../includes/Types/DownloadStats.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'freemius_conversions'`, `'freemius_reviews'`, `'freemius_stats'` entries) |
| Legacy migration handling | [`includes/Core/Migration.php`](../../includes/Core/Migration.php) (three separate `case 'freemius':` blocks, one per Type's migration switch) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Pro implementation (out of this repo's scope) | `notificationx-pro/includes/Extensions/Freemius/*.php` (sibling plugin) |

## Testing notes & gotchas

- All three classes are `$is_pro = true` UI stubs with no `get_data()` — do not assume any
  real Freemius sales/review/stats data flows through the free plugin. Confirm with a
  maintainer before relying on or "fixing" anything here; the real logic lives in Pro.
- The Pro `ExtensionFactory`'s `nx_extension_classes` filter for swapping in the real
  Freemius classes is present but commented out and empty — exactly the same
  not-obviously-wired pattern documented for Envato. If Freemius notifications are
  expected to show real data even with Pro active, check whether this wiring was
  intentionally disabled or is a regression, and how the Pro classes actually get
  instantiated in production (not found in this review).
- `Migration.php`'s three `freemius` cases only run during the legacy post-migration flow
  (reading `_nx_meta_freemius_content`); they are not a substitute for the ongoing
  cron-based fetch (`nx_freemius_interval` / `nx_cron_update_data_{id}`), which is
  Pro-only.
- Per this repo's `CLAUDE.md`, Pro-only logic lives in the sibling `notificationx-pro`
  plugin — the Pro class details above are documented here only as necessary context for
  understanding what the free `Freemius*` stubs are placeholders for.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [Envato Extension](envato.md) — the closest analog: same free-stub / Pro-real-logic
  split, same apparently-unwired `nx_extension_classes` filter pattern
