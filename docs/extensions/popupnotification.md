# Popup Notification Extension (`modules_popup`)

> Powers NotificationX's "Announcement" popup — a builder-authored modal (7 themes:
> promo/offer, video, form-capture, email-capture, etc.) with no third-party data
> source. It shares its REST submission pipeline (`/popup-submit`) and Feedback
> Entries listing with the Exit Intent extension.

## At a glance

| | |
|---|---|
| **Integration** | Popup Notification (aka "Announcement") |
| **Directory** | [`includes/Extensions/PopupNotification/`](../../includes/Extensions/PopupNotification/) |
| **Module key(s) (`$module`)** | `modules_popup` |
| **Feeds Types** | `popup` (see [`includes/Types/Popup.php`](../../includes/Types/Popup.php)) |
| **Extension classes** | `PopupNotification.php` → `(popup, popup_notification)` |
| **Depends on** | None. No `class_exists`/`function_exists`/option-based dependency detection appears anywhere in the class — this is a self-contained, builder-authored content type, not an integration with a third-party plugin/service |

## What it does

From the user's perspective: enabling the `modules_popup` module unlocks the
"Announcement" notification type in the builder. The admin picks one of the
registered `$this->themes` (`theme-one`, `theme-two`, `theme-three`, `theme-four`
free; `theme-five`, `theme-six`, `theme-seven` marked `is_pro`) and configures its
title/content/button copy, optional name/email/message form fields (for the
form-capture themes), and design/typography/button/email-input styling — all via
admin-builder fields registered in `init_fields()`. There is no external event
source: the "data" driving the popup is the admin-configured content/settings
themselves, plus (for the form-capture themes) end-user form submissions posted
back through the shared `popup-submit` REST route.

The extension also exposes an "Exit Intent Popup" cross-over toggle
(`convert_to_exit_intent` in `customize_fields()`) that lets an Announcement be
shown as a centered exit-intent popup instead of on its normal schedule.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`PopupNotification.php`](../../includes/Extensions/PopupNotification/PopupNotification.php) | `popup` | `popup_notification` | No `get_data()` method is defined — neither overridden here nor declared on the abstract `Extension` base. `init_extension()` registers the 7 built-in `$this->themes` (labels/defaults/preview images); `init_fields()` wires the admin-builder field filters (`design_fields`, `content_fields`, `customize_fields`, `display_fields`) that configure the popup's static content/design instead of fetching anything. |

## Data flow

Popup Notification has no third-party source event; content is authored directly in
the NotificationX builder. Trace:

```
Admin configures theme/content/design fields in the builder
       ↓
includes/FrontEnd/FrontEnd.php: get_notifications_data() serializes enabled `popup`
       posts into the REST response under the `popup` key (post settings only,
       content is always "" — see lines ~434-445)
       ↓
REST JSON response → response.popup
       ↓
React frontend picks up the `popup` bucket (nxdev/notificationx/frontend/) and
       renders the matching theme
```

For the form-capture themes, submitting the popup's form POSTs to the shared
`/notificationx/v1/popup-submit` route
([`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php) →
`handle_popup_submission()`), which delegates to
`PopupNotification::handle_popup_submission()`. That method builds an entry
(`title`, `timestamp`, optional `email`/`message`/`name`/`theme`, IP) and calls
`Extension::update_notification()` to persist it, tagging the entry with the post's
own `source` (so exit-intent conversions of an Announcement aren't mis-tagged).
`Popup::form_sources()` treats `popup_notification` as one of its two sources
(alongside `exit_intent_custom`) for the Feedback Entries screen (list/search/
delete/bulk-delete/export, all in `includes/Core/Rest/Popup.php`).

## Fields & settings

Registered via `init_fields()` (all in
[`PopupNotification.php`](../../includes/Extensions/PopupNotification/PopupNotification.php)):

- **`content_fields()`** — adds a `popup_content` section: `popup_title`,
  `popup_subtitle` (theme-seven only), `popup_icon`, `popup_content`, form-field
  toggles (`popup_show_name_field`, `popup_show_email_field` — both `is_pro`,
  `popup_show_message_field`) with matching placeholder-text fields, `popup_button_text`,
  `popup_button_url` (themes 1–3), `popup_button_icon` (theme-three/seven), and a
  `popup_content_repeater` (theme-three only) of highlight/title/subtitle items.
- **`design_fields()`** — adds `popup_design` (background/overlay color, width,
  border radius, padding, close-button color/size), `popup_typography` (title/
  subtitle/content color & size, font weight via
  `GlobalFields::get_instance()->normalize_fields()`), `popup_button_design`,
  `popup_email_design` (email-collection themes five/six/seven), and
  `popup_repeater_design` (theme-three content items).
- **`customize_fields()`** — hides the generic `behaviour`/`sound_section`/
  `queue_management`/`timing` fields for this source and adds a `center` position
  option, an `exit_intent_settings` section (`convert_to_exit_intent`,
  `exit_intent_cookie_duration`), and a `popup_settings` section
  (`show_close_button`, `close_on_button_click`, `close_button_position`).
- **`display_fields()`** — hides the generic image-selection section for this
  source.

See [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php) for the shared
field registry (`normalize_fields()` used throughout for select options).

## Dependency & detection

- No third-party plugin/service dependency. `PopupNotification.php` contains no
  `class_exists`/`function_exists`/option-based gating anywhere — unlike
  [Exit Intent](exitintent.md) (which optionally detects Elementor), this extension
  has nothing to detect.
- Module gating is the standard `modules_popup` settings key, checked generically by
  `ExtensionFactory::register_extensions()` via `Modules::get_instance()->is_enabled($obj->module)`.
  `Modules::is_enabled()` treats an unset key as enabled by default (returns `true`
  unless the settings option explicitly stores `modules_popup => false`).

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/PopupNotification/PopupNotification.php`](../../includes/Extensions/PopupNotification/PopupNotification.php) |
| Type registration | [`includes/Types/Popup.php`](../../includes/Types/Popup.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`popup_notification` key) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| REST — submission, Feedback Entries CRUD/export | [`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php) (`/popup-submit`, `/feedback-entries*`) |
| Data serialization | `includes/FrontEnd/FrontEnd.php` (`get_notifications_data()`, `popup` key) |

## Testing notes & gotchas

- This is a self-contained popup, not a third-party data feed — after changing this
  integration, verify the *builder field wiring* (content/design/customize tab
  visibility per theme), not an external API response shape.
- `popup_notification` shares the `popup-submit` REST pipeline and Feedback Entries
  listing with `exit_intent_custom` (`Popup::form_sources()`) — a regression there
  can affect both sources. `handle_popup_submission()` deliberately re-derives
  `source` from the target post rather than hard-coding `$this->id`, so exit-intent
  conversions of an Announcement are filed under the correct source.
- The `convert_to_exit_intent` toggle changes how the same post is displayed
  (exit-intent trigger vs. normal schedule) without changing its `source` — verify
  both paths after editing `customize_fields()`.
- _TODO: verify_ — whether/where `theme-five`/`theme-six`/`theme-seven` (`is_pro`
  themes) are actually gated in the free plugin at render time, versus just marked
  `is_pro` in the theme registry.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Exit Intent extension](exitintent.md) — shares the `popup-submit` REST pipeline and Feedback Entries listing
