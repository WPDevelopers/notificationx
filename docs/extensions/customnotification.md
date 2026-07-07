# CustomNotification Extension (`modules_custom_notification`)

> Lets a site owner author notifications manually — typing in name, message, and image
> per entry — instead of pulling data from a third-party plugin. It is the "source"
> used for the dedicated **Custom Notification** type (`custom`) and can also be
> selected as a manual data source for the **Sales/Conversions** type (`conversions`).

## At a glance

| | |
|---|---|
| **Integration** | `Custom Notification` |
| **Directory** | [`includes/Extensions/CustomNotification/`](../../includes/Extensions/CustomNotification/) |
| **Module key(s) (`$module`)** | `modules_custom_notification` (both classes) |
| **Feeds Types** | `custom` (Custom Notification type), `conversions` (Sales/Conversions type) |
| **Extension classes** | `CustomNotification.php` → `(custom, custom_notification)`; `CustomNotificationConversions.php` → `(conversions, custom_notification_conversions)` |
| **Depends on** | None — no third-party plugin/service. Both classes are Pro-gated (`$is_pro = true`) and have no dependency check (see [Dependency & detection](#dependency--detection)) |

## What it does

There is no external plugin to install or connect. The user picks **Custom
Notification** as the "Source" in the notification builder (either under the
dedicated `custom` notification Type, or as a manual source option under the
`conversions`/Sales Type), then fills in one or more entries by hand — a name,
message/content fields, and optionally an image — via a `custom_contents` repeater
field on the builder's Content tab. Those hand-entered rows are stored directly on
the notification's settings (`custom_contents`) rather than being fetched into the
shared entries table, and are rendered as-is on the frontend/preview. Because there
is no live event source, there is nothing to poll or sync — the "data" is whatever
the admin typed.

Both extension classes are marked `$is_pro = true`, so this integration is a Pro-only
feature; free installs see it locked behind the upgrade prompt (`popup` /
`nx_pro_alert_popup`).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source (`get_data()`) |
|---|---|---|---|---|
| [`CustomNotification.php`](../../includes/Extensions/CustomNotification/CustomNotification.php) | `custom` | `custom_notification` | `modules_custom_notification` | `get_data()` returns the literal string `'Hello From Custom Notification'` — not used for real content. Also implements `supported_themes()`, mapping the `custom` type's builder to theme sets from `conversions`, `conversions_count`, `maps_theme`, `comments`, `reviews`, `download_stats`, and `email_subscription` types (via `get_themes_for_type()` / `ExtensionFactory::get_instance()->get_themes_for_type()`). |
| [`CustomNotificationConversions.php`](../../includes/Extensions/CustomNotification/CustomNotificationConversions.php) | `conversions` | `custom_notification_conversions` | `modules_custom_notification` | `get_data()` also returns the literal string `'Hello From Custom Notification'`. `init_extension()` additionally sets a `$this->popup` (Pro upgrade / "more info" popup with an embedded YouTube tutorial) shown when the source is picked while not Pro. |

Both classes implement `doc()`, returning the same instructional HTML (links to
`https://notificationx.com/docs/custom-notification/` and a tutorial video), which is
surfaced through the base `Extension::nx_instructions()` filter.

Neither class defines `save_post()`, `preview_entry()`, `preview_settings()`, or
`fallback_data()`, so none of the optional hooks `Extension::init()` /
`public_actions()` wire up for those — the real "content" pipeline for this
integration is the `custom_contents` field (see below), not the `save`/`get_data`
entry pipeline other extensions use.

## Data flow

Unlike data-pulling integrations (WooCommerce, EDD, etc.), there is no
source-event → `get_data()` → `Entries` table pipeline here. Instead:

1. The builder's Content tab shows a `custom_contents` repeater field (config
   supplied by `$configs['tabs']['content_tab']['fields']['content']['fields']['custom_contents']`
   in [`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php)) whenever
   `source` is `custom_notification` or `custom_notification_conversions`.
2. The admin's typed rows are saved as-is under the `custom_contents` setting key on
   the notification post. The field definition itself is **not** in this free-plugin
   repo (it is referenced by key only in `GlobalFields.php` / `QuickBuild.php` /
   `Migration.php` / `FrontEnd.php`); it lives in `notificationx-pro`'s
   `CustomNotification::init_fields()` (`custom_contents` repeater with keys including
   `first_name`, `last_name`, `email`, `title`, `image`, `link`, `sales_count`,
   `username`, `post_title`, `post_comment`) — consistent with `$is_pro = true` on
   both extension classes.
3. On the frontend and in the builder preview,
   [`includes/FrontEnd/Preview.php`](../../includes/FrontEnd/Preview.php) reads
   `$settings['custom_contents'][0]` directly (merging `first_name`/`last_name` into
   a computed `name` via `Helper::name()`) to build the preview payload — no REST
   round-trip to an entries table for this source.
4. [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) treats
   `custom_contents` as an internal-only prop, stripping it from the payload sent to
   the frontend/React runtime (`filtered_post()` ignore-list) once the display
   values have been derived from it.
5. [`includes/Core/Migration.php`](../../includes/Core/Migration.php) contains
   legacy-upgrade paths (`case 'custom_notification':`, `case 'custom':`) that copy
   the old `_nx_meta_custom_contents` post meta into the new `custom_contents`
   settings key and normalize `name`/`first_name`/`last_name`.

## Fields & settings

- `custom_contents` — the repeater of manually-authored entries; gates the whole
  Content-tab section to `source` in `['custom_notification', 'custom_notification_conversions']`
  (`QuickBuild.php`). Field schema is defined in `notificationx-pro`'s
  `CustomNotification::init_fields()` (see [Data flow](#data-flow)), not in this repo.
- `show_notification_image` (in [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php))
  includes `custom_notification` / `custom_notification_conversions` in its `source`
  rules, with `featured_image` and `gravatar` as selectable image options and a
  default trigger of `show_notification_image: featured_image` for both sources
  (set via `nx_source_trigger` in `GlobalFields.php`).
- Settings → General has a **Custom Notification Import Limit** field
  (`custom_notification_import_limit`, default `100`) in
  [`includes/Admin/Settings.php`](../../includes/Admin/Settings.php), surfaced to the
  builder as `cus_imp_limit` (`GlobalFields.php`, `Settings.php`). This suggests a
  CSV/bulk-import feature for custom entries; only the setting definition exists —
  no server-side import handler was found in either the free plugin or
  `notificationx-pro` (the limit is surfaced to the React admin app as `cus_imp_limit`
  and applied there).
- No dedicated GlobalFields section keyed to `custom_notification*` beyond the
  `source`-rule inclusions above; this integration does not add its own builder tab,
  it reuses the standard Content tab's `custom_contents` field.

## Dependency & detection

- **No third-party plugin or service dependency.** Both `CustomNotification` and
  `CustomNotificationConversions` leave `$class`, `$function`, and `$constant`
  (inherited from `Extension`) unset/empty. `Extension::is_active()` only returns
  `false` for those checks when they are non-empty, so for this integration the
  checks are always skipped — the extension is "active" purely based on whether its
  `$module` (`modules_custom_notification`) is enabled and the notification itself
  is enabled (`PostType::get_active_items()`).
- Being Pro-gated is enforced separately via `$is_pro = true` (locks the source
  behind `nx_pro_alert_popup` / `is_pro_sources` in the UI when NotificationX Pro is
  not active), not via a class/function/constant existence check.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/CustomNotification/CustomNotification.php`](../../includes/Extensions/CustomNotification/CustomNotification.php), [`includes/Extensions/CustomNotification/CustomNotificationConversions.php`](../../includes/Extensions/CustomNotification/CustomNotificationConversions.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`custom_notification`, `custom_notification_conversions` keys) |
| Paired Type | [`includes/Types/CustomNotification.php`](../../includes/Types/CustomNotification.php) (`$id = 'custom'`, `$default_source = 'custom_notification'`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Content-tab wiring for `custom_contents` | [`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php) |
| Preview rendering | [`includes/FrontEnd/Preview.php`](../../includes/FrontEnd/Preview.php) |
| Frontend payload filtering | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) |
| Legacy migration | [`includes/Core/Migration.php`](../../includes/Core/Migration.php) |
| Import-limit setting | [`includes/Admin/Settings.php`](../../includes/Admin/Settings.php) |

## Testing notes & gotchas

- `get_data()` on both classes is a stub returning a fixed string — do not expect it
  to return real entry data; the actual displayed content comes from the
  `custom_contents` setting, read directly by `Preview.php` (and, per
  `FrontEnd.php`'s ignore-list, expected to be consumed/derived before the payload
  reaches the frontend React runtime — verify this derivation if content isn't
  showing correctly, since it wasn't fully traced in this pass).
- Because there's no entries-table pipeline, `Extension::save()` /
  `update_notification()` / `Entries` are not exercised by this integration in the
  way they are for data-pulling sources — don't expect rows in the analytics/entries
  table for custom-authored notifications.
- The `custom_contents` field definition/schema lives in `notificationx-pro`'s
  `CustomNotification::init_fields()` (verified in the sibling checkout), not in this
  repo; changes to its shape must be kept in sync with `Preview.php`'s expected keys
  (`first_name`, `last_name`, `name`, plus the message/image keys Pro defines).
- `CustomNotification::supported_themes()` pulls in themes from several other Types
  (`conversions`, `comments`, `reviews`, `download_stats`, `email_subscription`) — a
  theme added to one of those types may unexpectedly become selectable/unselectable
  for the `custom` type via this indirection; check `get_themes_for_type()` if theme
  options look wrong.
- No dedicated tests for this integration exist under `tests/`; the
  `custom_notification` / `custom_notification_conversions` sources are exercised
  generically by [`tests/test-extension-factory.php`](../../tests/test-extension-factory.php).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs under [../types/](../types/) (Custom Notification / Sales-Conversions type docs, if present)
