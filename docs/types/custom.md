# Custom Notification Type (`custom`)

> A Pro-only, "author it yourself" notification: instead of pulling live entries from
> a third-party plugin, the admin hand-types one or more entries and picks a theme
> borrowed from another notification type (Sales/Conversions, Comments, Reviews,
> Download Stats, Email Subscription, or the shared "maps" theme) to display them in.

## At a glance

| | |
|---|---|
| **Type ID** | `custom` |
| **Class** | [`includes/Types/CustomNotification.php`](../../includes/Types/CustomNotification.php) |
| **Trait** | none — no `includes/Types/Traits/CustomNotification.php` exists; the class only uses the generic `GetInstance` trait |
| **Priority** | `55` (admin ordering value; near the bottom of the sidebar relative to other types — e.g. `Video` = 60, `EmailSubscription` = 65, `PageAnalytics` = 70) |
| **Default source** | `custom_notification` |
| **Default theme** | none set — `$default_theme` is commented out in source (`// @todo default theme for custom`); inherits the empty string default from `Types` |
| **`$themes`** | literal string `'all'` (every other type in this repo sets `$themes` to an array or `[]`) — how the admin UI interprets this string value was **not found** in this repo (no matching `=== 'all'` check on the type-level `$themes` prop in PHP or the `nxdev/` React source); `_TODO: verify_` (likely handled in the `notificationx-pro` sibling plugin, since this whole type is Pro-gated) |
| **`is_pro`** | `true` — the entire type is Pro-only |
| **Module gate (`$module`)** | `modules_custom_notification` |
| **Compatible extensions** | [`CustomNotification`](../../includes/Extensions/CustomNotification/CustomNotification.php) (`$id = 'custom_notification'`, `$types = 'custom'`) — the only extension found declaring `$types = 'custom'`. A sibling class in the same file/directory, [`CustomNotificationConversions`](../../includes/Extensions/CustomNotification/CustomNotificationConversions.php) (`$id = 'custom_notification_conversions'`), shares the same `modules_custom_notification` module gate but declares `$types = 'conversions'` — it plugs the same "hand-type an entry" mechanism into the Sales/Conversions type instead, not into `custom`. |

## What it does

`CustomNotification` (the Type class) is a thin shell: its `__construct()`/`init()`
only set `$this->id = 'custom'` and the title "Custom Notification"
([`includes/Types/CustomNotification.php`](../../includes/Types/CustomNotification.php)
lines 34-47) — no fields, templates, or theme arrays are declared on the Type class
itself. All of the real behaviour lives in the paired extension,
`CustomNotification` ($id `custom_notification`), which is documented in depth in
**[docs/extensions/customnotification.md](../extensions/customnotification.md)** —
this doc links to it rather than duplicating it.

In short: the admin picks **Custom Notification** as the source, hand-fills one or
more entries via a `custom_contents` repeater field (name/message/image — exact
schema not found in this repo, likely defined in `notificationx-pro`), and chooses a
theme. The theme picker is not limited to a `custom`-specific theme set — the
extension's `supported_themes()` method
([`CustomNotification.php`](../../includes/Extensions/CustomNotification/CustomNotification.php)
lines 60-70) assembles theme lists from several *other* types via
`ExtensionFactory::get_themes_for_type()`: `conversions`, the `conversions_count`
subset, a shared `maps_theme` set, `comments`, `reviews`, `download_stats`
("stats"), and `email_subscription` ("subs"). In other words, a Custom Notification
reuses another type's visual theme to display manually-authored content.

`supported_themes()` itself is only called from one place in this repo —
[`includes/Core/Migration.php`](../../includes/Core/Migration.php) `case 'custom':`
(around line 1001) — the legacy V1→V2 settings migrator that maps old
`_nx_meta_custom_theme` values (e.g. `reviews-review_saying`, `stats-today-download`,
`maps_theme`) onto the new `themes` setting key (e.g. `conversions_<theme>`) and picks
the matching legacy notification-template meta to carry forward. `_TODO: verify_`
whether the live (non-migration) admin builder calls the same
`supported_themes()`/`get_themes_for_type()` logic to populate the theme picker for
new Custom Notifications, or whether that live wiring lives entirely in
`notificationx-pro` (the free-plugin `CustomNotification` extension defines no
`init_fields()` override and no `content_fields()`/`design_fields()` methods, unlike
extensions such as `ExitIntentNotification`).

`get_data()` on the extension is a stub returning the literal string
`'Hello From Custom Notification'` — it is not used for real content; see the linked
extension doc for how content actually flows (`custom_contents` → `Preview.php`).

## Data flow

This type has no dedicated routing branch in `FrontEnd.php`: its source
(`custom_notification`) does not match any of the special-cased `elseif` branches in
`get_notifications_ids()` (`press_bar`, `gdpr_notification`, `popup_notification`,
`exit_intent_custom`) — see
[`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) lines
553-592. It therefore falls into the generic **`active`** bucket like standard
data-pulling types (Sales, Reviews, Comments), which on the React side is normalized
with `normalize()` (the `{ post, entries: [] }` shape), not `normalizePressBar()`
(`nxdev/notificationx/frontend/core/utils.ts`). There is no `type == 'custom'` branch
found in `NotificationContainer.tsx` or elsewhere under
`nxdev/notificationx/frontend/core/` — `_TODO: verify_` exactly how a Custom
Notification's `custom_contents` entries end up populating the `entries` array
consumed by the generic `<Notification>` renderer, since `custom_contents` is
explicitly stripped from the outgoing payload by `FrontEnd::filtered_post()`'s
ignore-list before it reaches the frontend (see the extension doc's Data flow /
Testing notes sections for what *is* traced: `Preview.php` reading
`custom_contents[0]` directly for the builder preview).

For the full traced pipeline (Content-tab wiring via `QuickBuild.php`, preview
rendering, legacy migration, Pro-only field schema caveat), see
**[docs/extensions/customnotification.md § Data flow](../extensions/customnotification.md#data-flow)**.

## Fields & settings schema

No type-specific fields are declared on `CustomNotification` (the Type class) itself.
The extension-level fields (`custom_contents` repeater, `show_notification_image`
source rules, the `custom_notification_import_limit` setting) are documented in
[docs/extensions/customnotification.md § Fields & settings](../extensions/customnotification.md#fields--settings).

One `custom`-specific rule was found in the shared field registry: the "For Mobile"
responsive themes tab section is shown for `type` in
`['notification_bar', 'flashing_tab', 'inline', 'sales_inline', 'offer_announcement', 'custom']`
([`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php)
line 328).

## Themes / templates

`CustomNotification::$themes = 'all'` and `$templates` / `$mobile_templates` are left
at their inherited empty-array defaults from `Types`
([`includes/Types/Types.php`](../../includes/Types/Types.php)). There is no
type-owned theme list — the actual selectable themes come from the extension's
`supported_themes()` indirection described above (borrowed from `conversions`,
`comments`, `reviews`, `download_stats`, `email_subscription`, plus a shared
`maps_theme` set). No `custom`-prefixed theme ids (e.g. `custom_theme-one`) were
found anywhere in this repo.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/CustomNotification.php`](../../includes/Types/CustomNotification.php) |
| Trait | none |
| Extension (data source) | [`includes/Extensions/CustomNotification/CustomNotification.php`](../../includes/Extensions/CustomNotification/CustomNotification.php) |
| Sibling extension (different type) | [`includes/Extensions/CustomNotification/CustomNotificationConversions.php`](../../includes/Extensions/CustomNotification/CustomNotificationConversions.php) (`$types = 'conversions'`) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'custom' => 'NotificationX\Types\CustomNotification'`), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'custom_notification' => 'NotificationX\Extensions\CustomNotification\CustomNotification'`) |
| Legacy migration | [`includes/Core/Migration.php`](../../includes/Core/Migration.php) `case 'custom':` (~line 1001) |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (falls into the generic `active` bucket — no dedicated branch) |
| Frontend runtime | generic `active`/`normalize()` path in `nxdev/notificationx/frontend/core/utils.ts` — no dedicated component found |

## Dependencies

None — core WordPress only. Like its extension, `custom` has no third-party
plugin/service dependency; it is gated purely by `is_pro` and the
`modules_custom_notification` module toggle (see
[docs/extensions/customnotification.md § Dependency & detection](../extensions/customnotification.md#dependency--detection)).

## Testing notes & gotchas

- **This type is entirely Pro-gated** (`$is_pro = true` on both the Type and its
  Extension) and the free-plugin `CustomNotification` extension defines no
  `init_fields()`/content-design-customize field hooks — the live builder UI for
  this type most likely lives in the sibling `notificationx-pro` plugin.
  `_TODO: verify_` in that repo if you need the exact field schema.
- **`$default_theme` is unset** (commented out in source with a `@todo`) — verify
  what a brand-new Custom Notification defaults to before any theme is explicitly
  chosen.
- **Theme borrowing is transitive**: because `supported_themes()` pulls live theme
  lists from `conversions`/`comments`/`reviews`/`download_stats`/`email_subscription`
  extensions (excluding any extension with `$exclude_custom_themes = true`, e.g.
  `WooCommerceSalesReviews`, `WOOReviews`, `ReviewX`), adding/removing a theme on one
  of those types can silently change what's selectable for `custom` — see the
  extension doc's gotcha on this same indirection.
- **No dedicated `FrontEnd.php` routing** — a `custom_notification`-sourced post is
  treated as a standard `active` notification; if you add type-specific display
  logic elsewhere in this repo, remember there is no `custom`-specific branch to
  hook into today.
- No dedicated tests for this type were found under `tests/`. `_TODO: verify_` if any
  exist elsewhere in the suite.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [CustomNotification Extension](../extensions/customnotification.md) — the deep-dive
  for the actual data/field/preview pipeline behind this type
- [Announcement / Popup Notification Type](popup.md) — another Pro-leaning,
  config-only type for tonal comparison
