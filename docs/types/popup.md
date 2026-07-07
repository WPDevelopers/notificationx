# Announcement / Popup Notification Type (`popup`)

> Shows a centered (or positioned) modal popup on the frontend — a promo banner, a
> lead-capture form, or an embedded video — used for announcements, offers, and
> email/name/message collection. Admin-facing label is **"Announcement"**; the type id
> and most internal identifiers use `popup`.

## At a glance

| | |
|---|---|
| **Type ID** | `popup` |
| **Class** | [`includes/Types/Popup.php`](../../includes/Types/Popup.php) |
| **Trait** | none — no `includes/Types/Traits/Popup.php` exists; `Popup` only uses the generic `GetInstance` trait |
| **Priority** | `16` (comment in source: "After NotificationBar (15) but before Reviews (20)") |
| **Default source** | `popup_notification` |
| **Default theme** | `popup_theme-one` — vestigial. The only compatible extension's full theme ids are prefixed `popup_notification_theme-*` (extension id, not type id), and `PopupNotification` does not set its own `$default_theme` (confirmed), so `Extension::__source_trigger()`'s fallback (`$type->default_theme`, [Extension.php:218-224](../../includes/Extensions/Extension.php#L218-L224)) resolves to `popup_theme-one` and emits `@themes:popup_theme-one` — a pre-fill value that matches no registered theme id. The real theme is only set once the user picks a card in the Themes tab; nothing in this repo relies on this bogus fallback matching. |
| **Module gate (`$module`)** | `modules_popup` (set on the `PopupNotification` extension, not on the `Popup` type class itself — `Popup::$module` is `[]`) |
| **Compatible extensions** | [`PopupNotification`](../../includes/Extensions/PopupNotification/PopupNotification.php) (`$id = 'popup_notification'`, `$types = 'popup'`) — the only extension found declaring `$types = 'popup'` |

## What it does

`Popup` (title/dashboard title = "Announcement") is a minimal `Types` subclass — it
declares no fields, themes, or templates of its own beyond the constructor/`init()`
boilerplate ([`includes/Types/Popup.php`](../../includes/Types/Popup.php) lines 37-60).
All of the real behaviour — themes, content/design/customize fields, form submission —
lives in its single data-source extension, `PopupNotification`.

`PopupNotification` registers **six themes** (`theme-one`, `theme-two`, `theme-three`,
`theme-four`, `theme-five`, `theme-six`, `theme-seven` — note `theme-five` is defined
before `theme-three` in source; there is no numeric gap otherwise) covering:
- Promotional/link-out popups with a title, content, and button linking to a URL
  (`theme-one`, `theme-two`, `theme-three`).
- An embedded-video theme (`theme-two`'s default content is a YouTube `<iframe>`).
- A repeater/"content items" theme (`theme-three`) — highlight text + title + subtitle
  per item.
- Lead-capture/form themes with optional name/email/message fields
  (`theme-four`, `theme-five`, `theme-six`, `theme-seven`) — name/email fields are
  gated `is_pro`.

Any Announcement can also be converted into a **centered exit-intent popup** via the
`convert_to_exit_intent` toggle in `customize_fields()` (`exit_intent_settings` section)
— this reuses the *Popup* type/theme UI but changes trigger behavior on the frontend
(shown on mouse-leave instead of on a timer), and is a different mechanism from the
dedicated `exit_intent` Type — see [Related docs](#related-docs).

## Data flow

1. **Fields** — `PopupNotification::init_fields()` hooks `nx_design_tab_fields`,
   `nx_content_fields`, `nx_customize_fields`, `nx_display_fields` (priority 99/999/999/999)
   to inject theme-specific Content, Design, and Customize tab fields, all gated with
   `Rules::is('source', $this->id, ...)` or `Rules::is('themes', 'popup_notification_theme-X')`.
2. **Save/Store** — no `save_post`/`get_data` override was found on `PopupNotification`
   in this file (no live data source to poll); the notification's own settings (title,
   content, button, position, colors, etc.) are the entirety of what's shown — there is
   no per-visit "entry" feed the way sales/reviews/comments have. Visitor form
   submissions (see below) are a separate concern.
3. **Form submission** — the popup's own lead-capture forms (theme-four .. theme-seven)
   POST to `notificationx/v1/popup-submit`
   ([`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php)), which delegates
   to `PopupNotification::handle_popup_submission()`
   ([`includes/Extensions/PopupNotification/PopupNotification.php`](../../includes/Extensions/PopupNotification/PopupNotification.php)
   lines 846-904). It stores `name`/`email`/`message`/`ip`/`theme`/`timestamp` as an
   entry via `Extension::update_notification()`, keyed `{source}_{nx_id}` — `source` is
   taken from the post's own `source` field (not hardcoded to `popup_notification`) "so
   exit-intent submissions aren't mis-tagged" (per the source comment), since the same
   `/popup-submit` endpoint also serves `exit_intent_custom` submissions.
   `Core/Rest/Popup.php::form_sources()` lists `['popup_notification', 'exit_intent_custom']`
   as the sources whose entries share the Feedback Entries admin screen
   (list/search/delete/bulk-delete/export endpoints, same file).
4. **Routing (`FrontEnd.php`)** — `popup_notification` is special-cased in
   `get_notifications_ids()`: `elseif($settings['source'] == 'popup_notification') { $popup_notifications[] = ...; }`
   ([`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) line 575-576),
   landing in the dedicated **`popup`** bucket (not `active`). In
   `get_notifications_data()`, the `popup` bucket is populated with the `{ post, content: "" }`
   shape (lines 433-448) — the "config-only" path (`normalizePressBar`, not `normalize`,
   on the React side per [`docs/new-notification-type.md`](../development/adding-a-notification-type.md)).
5. **Frontend render** — the React runtime
   ([`nxdev/notificationx/frontend/core/useNotificationX.ts`](../../nxdev/notificationx/frontend/core/useNotificationX.ts))
   holds `popupNotices` state populated from `response.popup`, checks a
   `notificationx_popup_{nx_id}` `sessionStorage` key to skip already-closed popups, waits
   `settings.delay_before` seconds (plus a fixed extra ~1s initial delay in the effect),
   then dispatches. [`NotificationContainer.tsx`](../../nxdev/notificationx/frontend/core/NotificationContainer.tsx)
   routes `notice.config.type == 'popup'` to
   [`Popup.tsx`](../../nxdev/notificationx/frontend/core/Popup.tsx), which renders the
   theme-specific markup, handles the exit-intent variant (`convert_to_exit_intent`,
   its own `nx_exit_intent_{nx_id}` cookie), form validation/submission (POSTs to
   `popup-submit`), and close/session-storage bookkeeping.

## Fields & settings schema

`PopupNotification::init_fields()` / theme code (all in
[`PopupNotification.php`](../../includes/Extensions/PopupNotification/PopupNotification.php)):

- **`display_fields()`** — hides the generic `image-section` field for this source.
- **`design_fields()`** — adds four flat "Advance Design" sections, each gated to
  `Rules::is('source', 'popup_notification', false) AND Rules::is('advance_edit', true)`:
  `popup_design` (bg/overlay color, width, border radius, padding, close button color/size),
  `popup_typography` (title/subtitle/content color+size+weight, some theme-scoped via
  `Rules::is('themes', ...)`), `popup_button_design` (button colors, border, font,
  radius, padding, width incl. `custom` width), `popup_email_design` (input field
  styling, only shown for `theme-five`/`theme-six`/`theme-seven`), and
  `popup_repeater_design` (only for `theme-three`'s content items).
- **`content_fields()`** — a `popup_content` section: `popup_title`, `popup_subtitle`
  (theme-seven only), `popup_icon` (icon-picker, theme-seven only), `popup_content`
  (textarea, themes one/two/seven), `popup_show_name_field` / `popup_show_email_field`
  (toggles, `is_pro: true`, themes four–seven), `popup_name_placeholder` /
  `popup_email_placeholder`, `popup_show_message_field` / `popup_message_placeholder`,
  `popup_button_text`, `popup_button_url` (themes one–three), `popup_button_icon`
  (icon-picker, theme-three/theme-seven), `open_in_new_tab`, and
  `popup_content_repeater` (theme-three only, with default 3-item content).
- **`customize_fields()`** — narrows `timing`, `behaviour`, `sound_section`,
  `queue_management` to this source; adds a `center` position option; adds the
  `exit_intent_settings` section (`convert_to_exit_intent` checkbox,
  `exit_intent_cookie_duration` number field, 1–365 days, default 7); hides
  `display_for`/`delay_between`/`position` when converted to exit intent
  (`$hide_when_exit_intent` rule); adds a `popup_settings` section
  (`show_close_button`, `close_on_button_click`, `close_button_position` select).

## Themes / templates

`PopupNotification::init_extension()` defines `$this->themes` (admin theme picker) —
full theme id = `popup_notification_theme-<key>` (extension id prefix, per
`Extension::get_themes()` / `array_add_prefix()`):

| Theme key | Column | Notes |
|---|---|---|
| `theme-one` (type default per `Popup::$default_theme`, but see caveat above) | 5 | promotional, link-out button, "Get Started" default copy |
| `theme-two` | 5 | embeds a YouTube `<iframe>` by default as `popup_content` |
| `theme-four` | 5 | lead-capture form theme |
| `theme-five` | 5 | `is_pro: true`; lead-capture with email placeholder |
| `theme-seven` | 5 | `is_pro: true`; icon + subtitle + email capture |
| `theme-six` | 5 | `is_pro: true`; email-only capture |
| `theme-three` | 5 | "All Offers" — repeater/content-items layout |

`Popup` (the Type class itself) declares no `$themes`/`$templates`/`$res_themes` — it
inherits the empty defaults from `Types` (`includes/Types/Types.php`). There is no
PHP-rendered template string (`$templates`) for this type; the popup's markup is
entirely client-side (`Popup.tsx`), unlike PHP-template-driven types such as Comments.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/Popup.php`](../../includes/Types/Popup.php) |
| Trait | none |
| Extension (data source) | [`includes/Extensions/PopupNotification/PopupNotification.php`](../../includes/Extensions/PopupNotification/PopupNotification.php) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'popup' => 'NotificationX\Types\Popup'`), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'popup_notification' => 'NotificationX\Extensions\Popup\PopupNotification'`) |
| REST | [`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php) — `/notificationx/v1/popup-submit` (form submission), `/feedback-entries*` (Feedback Entries admin screen: list/delete/bulk-delete/export, shared with `exit_intent_custom`) |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (`get_notifications_ids()` line ~575, `get_notifications_data()` lines 433-448 — dedicated `popup` bucket) |
| Frontend runtime | [`nxdev/notificationx/frontend/core/Popup.tsx`](../../nxdev/notificationx/frontend/core/Popup.tsx) (component), [`nxdev/notificationx/frontend/core/helper/PopupHeader.jsx`](../../nxdev/notificationx/frontend/core/helper/PopupHeader.jsx) (header sub-component), [`nxdev/notificationx/frontend/core/useNotificationX.ts`](../../nxdev/notificationx/frontend/core/useNotificationX.ts) (`popupNotices` state/dispatch), [`nxdev/notificationx/frontend/core/utils.ts`](../../nxdev/notificationx/frontend/core/utils.ts) (`normalizePressBar` for `popup`), [`nxdev/notificationx/frontend/core/NotificationContainer.tsx`](../../nxdev/notificationx/frontend/core/NotificationContainer.tsx) (routes `type == 'popup'`) |
| Styles | [`nxdev/notificationx/frontend/scss/_themes/_popup.scss`](../../nxdev/notificationx/frontend/scss/_themes/_popup.scss) |

## Dependencies

None — core WordPress only. `PopupNotification` has no third-party data-source
dependency; all content is authored directly in the admin builder (title, content,
button text/url, form field toggles).

## Testing notes & gotchas

- **`default_theme` mismatch** — `Popup::$default_theme = 'popup_theme-one'` does not
  match any theme id actually produced by `PopupNotification::get_themes()`
  (`popup_notification_theme-*`). Verify whether anything relies on the type-level
  fallback in `Extension::__source_trigger()` before "fixing" this — see the caveat in
  **At a glance** above.
- **Shared `/popup-submit` endpoint** — both `popup_notification` and
  `exit_intent_custom` submit through the same REST route and the same
  `handle_popup_submission()` method (on `PopupNotification`); `source` is read off the
  saved post rather than hardcoded, specifically to keep exit-intent submissions from
  being mis-tagged as `popup_notification` in the Feedback Entries screen (see comment
  in [`PopupNotification.php`](../../includes/Extensions/PopupNotification/PopupNotification.php)
  lines 886-889). Test both sources' submissions land under the correct `source` in
  `wp_nx_entries`.
- **`convert_to_exit_intent`** changes trigger timing (mouse-leave vs. delay) and uses a
  separate cookie (`nx_exit_intent_{nx_id}`, written client-side in `Popup.tsx`) from the
  plain popup's `sessionStorage` key (`notificationx_popup_{nx_id}`) — these two
  suppression mechanisms don't share state; verify both independently when testing
  dismissal persistence.
- **Pro gating** — `popup_show_name_field`/`popup_show_email_field` are marked
  `is_pro: true` in the field schema, and `Popup.tsx` only renders the name/email inputs
  when `is_pro` is true client-side; `theme-five`/`theme-six`/`theme-seven` are
  `is_pro: true` themes. Verify free-plugin behavior degrades sensibly (message field
  and button remain functional without name/email capture).
- This type **is** exercised by the PHPUnit suite: [`tests/test-types-factory.php`](../../tests/test-types-factory.php#L111-L115) uses `popup` as its representative fixture for `register_types()`/`get_all()`, and [`tests/test-rest.php`](../../tests/test-rest.php#L116) asserts the `/notificationx/v1/popup-submit` route (this type's form-submission endpoint) is registered. There is no broader behavioural coverage beyond that.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Exit Intent Popup](../features/exit-intent/00-overview.md) — the dedicated `exit_intent` Type/theme
  system; related but distinct from this type's `convert_to_exit_intent` toggle
- [Notification Bar Reference](../features/notification-bar/reference.md) (sibling
  `normalizePressBar`-shaped type)
