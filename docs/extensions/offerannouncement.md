# OfferAnnouncement Extension (`modules_announcements`)

> Powers **Discount Alert** — a notification that shows a manually-authored offer
> (title, description, optional discount/expiry) rather than pulling data from a
> third-party plugin. It is the sole/default source for the dedicated **Discount
> Alert** notification Type (`offer_announcement`).

## At a glance

| | |
|---|---|
| **Integration** | `Discount Alert` (directory name `OfferAnnouncement`, extension id `announcements`) |
| **Directory** | [`includes/Extensions/OfferAnnouncement/`](../../includes/Extensions/OfferAnnouncement/) |
| **Module key(s) (`$module`)** | `modules_announcements` |
| **Feeds Types** | `offer_announcement` (Discount Alert type — [`includes/Types/OfferAnnouncement.php`](../../includes/Types/OfferAnnouncement.php), `$default_source = 'announcements'`) |
| **Extension classes** | `Announcements.php` → `(offer_announcement, announcements)` |
| **Depends on** | None — no third-party plugin/service. `$is_pro = true`; no `$class`/`$function`/`$constant` dependency check is set (see [Dependency & detection](#dependency--detection)) |

## What it does

There is no external plugin to install or connect. The user creates a notification
under the **Discount Alert** Type, and the builder's Content tab exposes manual
fields — `offer_title`, `offer_description`, `offer_discount`, `image`,
`expire_time`, and an `announcement_entries` repeater (field names seen in the Quick
Builder whitelist, [`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php)
lines ~231-236) — that the admin fills in by hand. `Announcements::get_data()` does
not fetch or return any of this content; it is a stub. There is no live event source
to poll, so this integration is purely "author it yourself" content, similar in
shape to `CustomNotification` (see [customnotification.md](customnotification.md)).

The extension is Pro-gated (`$is_pro = true`) and ships six frontend themes
(`theme-1`, `theme-2`, `theme-12`, `theme-14`, `theme-15`, plus a commented-out
`theme-13`) and five responsive/mobile themes (`res-theme-one` … `res-theme-five`),
each mapping template placeholder params (`first_param`, `third_param`,
`fourth_param`) to tags like `tag_offer_title`, `tag_offer_description`, `tag_time`.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source (`get_data()`) |
|---|---|---|---|---|
| [`Announcements.php`](../../includes/Extensions/OfferAnnouncement/Announcements.php) | `offer_announcement` | `announcements` | `modules_announcements` | `get_data()` returns the literal string `'Hello From Custom Notification'` (line 265) — a stub, not real data. `init_extension()` (not the constructor) sets `$this->title` / `$this->module_title` to "Discount Alert", and defines `$this->themes`, `$this->res_themes`, and `$this->templates` (see above). Also implements `doc()`, returning instructional HTML linking to `https://notificationx.com/docs/configure-discount-alert/` and a blog post, surfaced via the base `Extension::nx_instructions()` filter. |

`Announcements` imports `NotificationX\Extensions\GlobalFields` but does not
actually call any `GlobalFields` method in this class — the import is unused in the
current source. It also imports `NotificationX\Types\Conversions` (unused import;
`$types` is `'offer_announcement'`, not `conversions`).

Neither `save_post()`, `saved_post()`, `preview_entry()`, `preview_settings()`, nor
`fallback_data()` are defined on `Announcements`, so none of those optional
`Extension::init()` / `public_actions()` hooks wire up — this integration does not
use the `save()`/`update_notification()`/`Entries`-table pipeline the way
data-pulling extensions (WooCommerce, EDD, etc.) do.

## Data flow

Unlike data-pulling integrations, there is no source-event → `get_data()` →
`Entries` table pipeline here:

1. The builder's Content tab exposes the manual fields listed above
   (`offer_title`, `offer_description`, `offer_discount`, `image`, `expire_time`,
   `announcement_entries`) — these are in the Quick Builder's allowed-field
   whitelist (`QuickBuild.php`) but their actual field *definitions* (type, default,
   validation) were **not found** in this free-plugin repo — `_TODO: verify_`
   (consistent with `$is_pro = true`, they likely live in `notificationx-pro`).
2. The admin's entered values are saved on the notification post's settings.
   Exactly how they reach the frontend/preview render (i.e. whether `Preview.php` /
   `FrontEnd.php` read them directly the way `custom_contents` is read for
   `CustomNotification`) was **not traced** in this pass — `_TODO: verify_`.
3. Theme/template wiring (which placeholder shows `tag_offer_title` /
   `tag_offer_description` / `tag_time` for a given theme) is handled entirely by
   the base `Extension` class's `__nx_themes` / `__themes_trigger` /
   `__notification_template` filters, driven by the `$this->themes` /
   `$this->res_themes` / `$this->templates` arrays defined in `init_extension()`.

## Fields & settings

- `offer_title`, `offer_description`, `offer_discount`, `image`, `expire_time`,
  `announcement_entries` — field names referenced in the Quick Builder's field
  whitelist ([`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php));
  full field schema not defined in this repo — `_TODO: verify_`.
- `announcement_link_button_text` and `link` — set as theme `defaults` inside
  `Announcements::init_extension()` (not a `GlobalFields` entry); used as fallback
  values for the CTA button/link per theme.
- Two `discount_text_color` / `discount_background` colorpicker fields in
  [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (~lines 517-533)
  are scoped via `Rules::is('type', 'offer_announcement')` combined with
  `Rules::includes('themes', ['announcements_theme-1', 'announcements_theme-2'], false)`
  — i.e. they only show for the `offer_announcement` type on themes 1 and 2.
- No dedicated GlobalFields section/tab is added by this extension beyond the two
  colorpicker fields above; it otherwise reuses the standard Content tab.

## Dependency & detection

- **No third-party plugin or service dependency.** `Announcements` leaves `$class`,
  `$function`, and `$constant` (inherited from `Extension`) unset/empty.
  `Extension::is_active()` only returns `false` for those checks when they are
  non-empty, so for this integration the checks are always skipped — the extension
  is "active" purely based on whether its `$module` (`modules_announcements`) is
  enabled and the notification itself is enabled (`PostType::get_active_items()`).
- Being Pro-gated is enforced separately via `$is_pro = true` on both the extension
  (`Announcements`) and its paired Type
  ([`includes/Types/OfferAnnouncement.php`](../../includes/Types/OfferAnnouncement.php)),
  which locks the source/type behind an upgrade popup
  (`nx_pro_alert_popup` / `is_pro_sources`) when NotificationX Pro is not active —
  not via any class/function/constant existence check.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/OfferAnnouncement/Announcements.php`](../../includes/Extensions/OfferAnnouncement/Announcements.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`announcements` key) |
| Paired Type | [`includes/Types/OfferAnnouncement.php`](../../includes/Types/OfferAnnouncement.php) (`$id = 'offer_announcement'`, `$default_source = 'announcements'`, `$is_pro = true`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Quick Builder field whitelist | [`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php) |
| Base Extension behaviour | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |

## Testing notes & gotchas

- `get_data()` is a stub returning a fixed string copy-pasted from
  `CustomNotification` (`'Hello From Custom Notification'`) — do not expect it to
  return real Discount Alert content; treat it as dead/placeholder code until
  proven otherwise.
- Because no `save_post`/`fallback_data`/`Entries` hooks are wired, this integration
  does not populate the analytics/entries table the way data-pulling extensions do
  — don't expect rows there for Discount Alert notifications.
- The actual field schema for `offer_title`, `offer_description`, `offer_discount`,
  `image`, `expire_time`, and `announcement_entries` lives outside this free-plugin
  repo — `_TODO: verify_` (check `notificationx-pro` if available).
- Both unused imports (`GlobalFields`, `Types\Conversions`) are worth confirming are
  still unused before relying on them as documentation of intended reuse.
- No dedicated tests for this integration were found under `tests/` —
  `_TODO: verify_`.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [CustomNotification Extension](customnotification.md) — closest analog (manual,
  no third-party dependency, Pro-gated, stub `get_data()`)
- Related Type docs under [../types/](../types/) (Discount Alert / `offer_announcement`
  type doc, if present)
