# Exit Intent Extension (`modules_exit_intent`)

> Powers NotificationX's own Exit Intent Popup — a built-in modal (7 React-rendered
> themes, plus an optional Elementor-designed "Custom" mode) shown when a visitor's
> cursor moves toward the browser chrome, meant to recover abandoning users with a
> feedback form, coupon, or offer. Unlike most extensions it pulls no data from a
> third-party plugin/service — its only optional dependency is Elementor, used purely
> as an alternative popup-content builder.

## At a glance

| | |
|---|---|
| **Integration** | Exit Intent |
| **Directory** | [`includes/Extensions/ExitIntent/`](../../includes/Extensions/ExitIntent/) |
| **Module key(s) (`$module`)** | `modules_exit_intent` |
| **Feeds Types** | `exit_intent` (see [`includes/Types/ExitIntent.php`](../../includes/Types/ExitIntent.php)) |
| **Extension classes** | `ExitIntentNotification.php` → `(exit_intent, exit_intent_custom)` |
| **Depends on** | Elementor (optional, only for the "Custom"/"Build With Elementor" popup-design mode) — detected via `class_exists('\Elementor\Plugin')` / `Helper::is_plugin_installed('elementor/elementor.php')`. Absent → falls back to the 7 built-in themes, no error surfaced |

## What it does

From the user's perspective: enabling the `modules_exit_intent` module unlocks a new
Exit Intent notification type in the builder. The admin either picks one of 7
built-in themes (Feedback Form, Flash Sale, Coupon Offer, Video Popup, Live Flash
Sale w/ timer, Product w/ Countdown, Email Lead Capture — each with its own content
and design fields) or, if Elementor is installed/active, clicks "Build With
Elementor" to seed a fully custom popup design from one of the same 7 layouts
(imported from JSON templates under `jsons/`) into a dedicated `nx_exit_intent`
Elementor document. There is no external event source: the "data" driving the popup
is the admin-configured content/settings themselves, plus (for theme-one and
theme-seven) end-user form submissions posted back through the shared
`popup-submit` REST route.

The real trigger is client-side: [`useNotificationX.ts`](../../nxdev/notificationx/frontend/core/useNotificationX.ts)
listens for `mouseleave` near the top of the viewport and dispatches the popup once
per `nx_id` per page load, honoring a dismissal cookie/sessionStorage flag
(`exit_intent_cookie_days`).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`ExitIntentNotification.php`](../../includes/Extensions/ExitIntent/ExitIntentNotification.php) | `exit_intent` | `exit_intent_custom` | No `get_data()` method is defined — neither overridden here nor declared on the abstract `Extension` base. This extension is not a data-pulling integration: `init_extension()` registers the 7 built-in `$this->themes` (labels/defaults/preview images) plus `$this->elementor_themes` (seed-theme registry for the Elementor modal); `init_fields()` wires the admin-builder field filters (content/design/customize/display) that configure the popup's static content instead. |

[`includes/Extensions/ExitIntent/importer.php`](../../includes/Extensions/ExitIntent/importer.php) (`Importer` class, extends Elementor's `Source_Local`) is a helper used by `ExitIntentNotification::create_exit_intent_with_elementor()` — it is not itself registered in `ExtensionFactory` and has no `$types`/`$id`/`$module`. It reads a seed JSON from `jsons/{theme}.json` and creates a new `nx_exit_intent` Elementor document (`create_nx()`).

## Data flow

Exit Intent has no third-party source event; content is authored directly in the
NotificationX builder (or in Elementor for Custom mode). Trace:

```
Admin configures theme/content/design fields (built-in) or links an Elementor doc (Custom)
       ↓
includes/FrontEnd/FrontEnd.php: get_notifications_data() serializes enabled exit_intent
       posts into the REST response under the `exit_intent` key
       ↓
REST JSON response → response.exit_intent
       ↓
nxdev/notificationx/frontend/core/utils.ts: normalizePressBar() → array of { post, content }
       ↓
useNotificationX.ts: setExitIntentNotices(...); document `mouseleave` near viewport top →
       dispatchNotification({ config: exitItem.post }) (fires once per nx_id per page load)
       ↓
NotificationContainer.tsx: config.type === 'exit_intent' → <ExitIntentPopup>
       ↓
ExitIntentPopup.tsx: renders the matching theme branch; in Custom/Elementor mode
       (settings.mode === 'elementor') renders `settings.elementor_html` instead
```

For theme-one (feedback form) and theme-seven (email capture), submitting the popup's
form POSTs to the shared `/notificationx/v1/popup-submit` route
([`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php)), which treats
`exit_intent_custom` as one of its two `form_sources()` (alongside
`popup_notification`) and stores/lists the resulting entries together in the Feedback
Entries screen.

Server-side, `ExitIntentNotification::inject_elementor_html()` (hooked on
`nx_filtered_post`) checks whether the campaign's `source` is `exit_intent_custom`
and an `elementor_id` is linked + published; if so it renders the Elementor document
to HTML server-side and injects it (plus resolved Layout-panel settings) into the
campaign settings payload as `elementor_html` / `popup_layout` / `mode: 'elementor'`.
If Elementor is missing or the doc unpublished, `mode` falls back to `'built_in'`.

## Fields & settings

Distinctive fields registered via `init_fields()` (see
[docs/exit-intent-popup.md](../features/exit-intent/00-overview.md) for the full, per-theme field
tables — not duplicated here):

- **Themes tab** — rebuilt into Default (7 React themes, the global `themes`
  radio-card) / Custom (Elementor build/edit/remove buttons + modal) tabs via
  `builder_tabs_fields()`, overriding the global `for_desktop`/`for_mobile` tabs for
  this source only.
- **Content tab** — one section per theme (`exit_intent_theme_two_section`, …,
  `exit_intent_theme_seven_section`) via `content_fields()`; hidden entirely when an
  Elementor doc is linked (`suppress_when_elementor()`), which also hides the whole
  Content wizard step (`hide_content_tab_for_elementor()`).
- **Design tab** — per-theme private helpers (`theme_one_design_fields()` …
  `theme_seven_design_fields()`) merged flat into `advance_design_section` by
  `design_fields()`, gated by `Rules::is('themes', ...)` per theme.
- **Customize tab** — `exit_intent_popup_settings` section added by
  `customize_fields()`: `show_close_button`, `exit_intent_position`,
  `exit_intent_sensitivity`, `exit_intent_cookie_days`, `exit_intent_mobile_disable`.
  Standard `timing`/`behaviour`/`sound_section`/`queue_management`/`appearance`/
  `animation` fields are hidden for this source (a `center` position option is added
  instead). Uses `GlobalFields::get_instance()->normalize_fields()` for the select
  options.
- **Elementor "Layout" panel** (only inside the Elementor editor, only for
  `nx_exit_intent` documents) — `nx_popup_width`, `nx_popup_height`,
  `nx_popup_custom_height`, `nx_popup_horizontal`, `nx_popup_vertical`,
  `nx_popup_overlay`, `nx_popup_close_button`, `nx_popup_entrance_animation`,
  `nx_popup_exit_animation`, registered via `register_popup_layout_controls()`.

## Dependency & detection

- **Elementor** (`elementor/elementor.php`) — optional, required only for the
  "Custom" popup-design mode.
- Detection is `class_exists('\Elementor\Plugin')` (used throughout —
  `inject_elementor_html()`, `get_popup_layout_settings()`,
  `enqueue_elementor_assets()` via `\Elementor\Core\Files\CSS\Post`,
  `filter_edit_post_link()`, `enqueue_editor_live_preview()`) and
  `Helper::is_plugin_installed('elementor/elementor.php')` (used in
  `builder_tabs_fields()` to decide whether the modal's button reads "Install
  Elementor" or "Activate Elementor").
- When absent: the built-in 7 themes still work unaffected (no `class_exists` gate
  on them). The Elementor-specific paths simply no-op/fall back:
  `inject_elementor_html()` sets `mode = 'built_in'`; `get_popup_layout_settings()`
  returns hard-coded defaults; `enqueue_elementor_assets()` and
  `enqueue_editor_live_preview()` return early; `filter_edit_post_link()` returns the
  original WP edit link unchanged. No admin error/notice is shown for a missing
  Elementor — the UI just offers to install/activate it before "Build With
  Elementor" can be used.
- No `$class`/`$function`/`$constant` gating on the `Extension::is_active()` base
  mechanism is set for this extension (all three are unset/empty on
  `ExitIntentNotification`), so `is_active()` only checks whether an
  `exit_intent_custom` post exists among active items — Elementor's presence is
  checked ad hoc inside individual methods, not via the base class's dependency gate.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../includes/Extensions/ExitIntent/ExitIntentNotification.php) |
| Elementor seed importer | [`includes/Extensions/ExitIntent/importer.php`](../../includes/Extensions/ExitIntent/importer.php) |
| Elementor seed templates | [`includes/Extensions/ExitIntent/jsons/`](../../includes/Extensions/ExitIntent/jsons/) (`theme-one.json` … `theme-seven.json`) |
| Type registration | [`includes/Types/ExitIntent.php`](../../includes/Types/ExitIntent.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`exit_intent_custom` key) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| REST — Elementor import/remove, popup-submit | [`includes/Core/REST.php`](../../includes/Core/REST.php) (`/exit-intent/elementor/import`, `/exit-intent/elementor/remove`), [`includes/Core/Rest/Popup.php`](../../includes/Core/Rest/Popup.php) (`/popup-submit`) |
| Data serialization | `includes/FrontEnd/FrontEnd.php` (`get_notifications_data()`, `exit_intent` key) |
| React rendering | `nxdev/notificationx/frontend/core/ExitIntentPopup.tsx`, `NotificationContainer.tsx`, `useNotificationX.ts`, `utils.ts` |

## Testing notes & gotchas

- This is a self-contained popup, not a third-party data feed — after changing this
  integration, verify the *builder field wiring* (content/design/customize tab
  visibility per theme) and the *client-side trigger* (`mouseleave` + cookie/session
  dismissal), not an external API response shape.
- Changes to popup content/design fields must stay in sync between
  `ExitIntentNotification.php` and `ExitIntentPopup.tsx` — see the repo-wide
  "Frontend templating quirk" note in the root `CLAUDE.md`.
- When Elementor is linked, per-theme Content/Design fields are suppressed
  (`suppress_when_elementor()`) — confirm the Themes radio itself stays visible so
  users can switch back to a built-in theme.
- `exit_intent_custom` shares the `popup-submit` REST pipeline and Feedback Entries
  listing with `popup_notification` (`Popup::form_sources()`) — a regression there
  can affect both sources.
- See [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md) for the full per-theme
  field tables, and [docs/exit-intent-add-new-design.md](../features/exit-intent/add-new-design.md)
  for the process of adding a new theme.

## Related docs

- [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md) — full per-theme content/design field reference, trigger mechanism, data flow
- [docs/exit-intent-add-new-design.md](../features/exit-intent/add-new-design.md) — adding a new Exit Intent theme
- [Adding a New Notification Type](../development/adding-a-notification-type.md) — uses Exit Intent as its worked example
