# Exit Intent Popup Notification Type (`exit_intent`)

> Detects when a visitor's cursor moves toward the browser chrome (about to leave the
> page) and shows a targeted modal popup — either one of the built-in themes or a
> custom Elementor design — to try to recover the abandoning visitor.

## At a glance

| | |
|---|---|
| **Type ID** | `exit_intent` |
| **Class** | [`includes/Types/ExitIntent.php`](../../includes/Types/ExitIntent.php) |
| **Trait** | none — no `includes/Types/Traits/ExitIntent.php` exists |
| **Priority** | `17` |
| **Default source** | `exit_intent_custom` (`$default_source`) |
| **Default theme** | `exit_intent_theme-one` (`$default_theme`) |
| **Link type** | `none` (`$link_type`) |
| **Module gate (`$module`)** | `['modules_exit_intent']` declared on the Type object. As documented for the sibling `conversions` type ([docs/types/conversions.md](conversions.md)), `Types::$module` is **not** itself read by `TypesFactory` to gate loading — the real per-extension gate is the extension's own `$module` string checked by `ExtensionFactory`. For this type the only compatible extension, `ExitIntentNotification`, also declares `$module = 'modules_exit_intent'`, so in practice the same key gates both. Confirmed `TypesFactory` never reads `Types::$module` — `grep -n "module" includes/Types/TypesFactory.php` returns nothing. |
| **Compatible extensions** | `ExitIntentNotification` (`$types = 'exit_intent'`) — see table below. |

## What it does

`ExitIntent::init()` ([includes/Types/ExitIntent.php:32](../../includes/Types/ExitIntent.php#L32)) sets the admin/dashboard title to "Exit Intent Popup" and registers exactly **one** Type-level theme, `theme-one`, with a simple 3-slot template (`tag_name` / static "is about to leave" text / `tag_title` / `tag_time`) used as a fallback/legacy content template (`exit_intent_template_default`).

The actual richly-designed popup experience — 7 built-in visual themes (`theme-one`, `theme-two`, `theme-three`, `theme-four`, `theme-five`, `theme-six`, `theme-seven`) plus an "Elementor custom design" mode — is registered by the **Extension** class, `ExitIntentNotification::init_extension()` ([includes/Extensions/ExitIntent/ExitIntentNotification.php:590](../../includes/Extensions/ExitIntent/ExitIntentNotification.php#L590)), not by the Type class. Each built-in theme has its own `defaults` array (title/subtitle/button text/etc.) and a `column` layout hint; several (`theme-two`, `theme-five`, `theme-six`, `theme-seven`) are `is_pro => true`.

On top of the built-in themes, this extension also supports building the popup visually in **Elementor**: it registers a private `nx_exit_intent` post type, a custom "Layout" panel (width/height/position/overlay/close button/animations) added to the Elementor document-settings panel, and injects the rendered Elementor HTML into the REST payload (`inject_elementor_html()`) with a `mode: 'elementor' | 'built_in'` flag so the React popup shell knows which rendering path to use.

## Data flow

Trace for an Exit Intent notification, built-in theme or Elementor:

1. `ExitIntentNotification::get_data()`/settings are stored on the `nx_bar` custom post as usual (`source = 'exit_intent_custom'`).
2. `FrontEnd::get_notifications_ids()` ([includes/FrontEnd/FrontEnd.php:577](../../includes/FrontEnd/FrontEnd.php#L577)) special-cases `source == 'exit_intent_custom'`: it buckets the `nx_id` into `$exit_intent_notifications` (separate from the generic `active`/`global` buckets), and — if an `elementor_id` is linked — force-enqueues Elementor's frontend runtime assets (`Elementor\Plugin::$instance->frontend->get_builder_content()`) so widget CSS/JS (e.g. a countdown widget) isn't lost.
3. `FrontEnd::get_notifications_data()` ([includes/FrontEnd/FrontEnd.php:451](../../includes/FrontEnd/FrontEnd.php#L451)) reads the `exit_intent` param bucket, loads each notification's settings, applies `apply_filters('nx_filtered_post', $settings, $params)` (this is where `ExitIntentNotification::inject_elementor_html()` hooks in to attach `mode`/`elementor_html`/`popup_layout`), and places the result under `$result['exit_intent'][$nx_id]['post']`.
4. React runtime: per [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md), `useNotificationX.ts` attaches a `document` `mouseleave` listener and fires the popup once per `nx_id` per page load (tracked in a `triggered` Set); `NotificationContainer.tsx` renders `<ExitIntentPopup>` for `config.type === 'exit_intent'`; closing sets a `sessionStorage`/cookie key so it doesn't re-fire.
5. `link_type = 'none'` on this Type — no notification link/click-through URL is attached the way `conversions` attaches a product-page link.

See [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md) for the full trigger-mechanism and per-theme React rendering details, and [docs/exit-intent-add-new-design.md](../features/exit-intent/add-new-design.md) for how to add a new built-in theme.

## Fields & settings schema

`ExitIntent::init_fields()` is not overridden (inherits the no-op `Types::init_fields()`); all builder fields for this type are registered by the **Extension** in `ExitIntentNotification::init_fields()` ([includes/Extensions/ExitIntent/ExitIntentNotification.php:776](../../includes/Extensions/ExitIntent/ExitIntentNotification.php#L776)) via `nx_content_fields`, `nx_design_tab_fields`, `nx_customize_fields`, `nx_display_fields`. Notable behaviour:

- The Themes tab is restructured into two custom sub-tabs — **Default** (`exit_intent_default_tab`, the 7-theme radio-card grid) and **Custom** (`exit_intent_custom_tab`, the "Build With Elementor" modal + Edit/Remove buttons) — replacing the global `for_desktop`/`for_mobile` tabs for this type only.
- Per-theme content sections (`exit_intent_*_section`) are auto-hidden once an `elementor_id` is linked (`suppress_when_elementor()`), and the whole Content wizard step is hidden in that case too (`hide_content_tab_for_elementor()`), since Elementor then owns all content.
- Distinctive settings keys: `elementor_id`, `elementor_edit_link`, `is_elementor`, `elementor_exit_theme`, plus each theme's own `exit_intent_<theme>_*` keys (see the extension source for the full per-theme list).
- `exit_intent_cookie_days` — per [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md), when > 0 a dismissal cookie is written in addition to the `sessionStorage` key.

The Type-level template registered in `ExitIntent.php` (`exit_intent_template_default`) uses `GlobalFields::get_instance()->common_name_fields()` for its `first_param`; that method ([`includes/Extensions/GlobalFields.php:2304-2313`](../../includes/Extensions/GlobalFields.php#L2304-L2313)) returns `tag_name` (Full Name), `tag_first_name` (First Name) and `tag_last_name` (Last Name), plus `tag_display_name` (Display Name) when called with `$display_name = true`.

## Themes / templates

- **Type class** (`ExitIntent.php`): registers only `theme-one` (fully-qualified `exit_intent_theme-one`), mapped to template `exit_intent_template_default`.
- **Extension class** (`ExitIntentNotification.php`): registers the 7 visual themes actually shown in the admin theme picker — `theme-one`, `theme-four`, `theme-three`, `theme-six` (pro), `theme-two` (pro), `theme-seven` (pro), `theme-five` (pro) — each with its own `defaults` and preview image under `images/extensions/themes/exit-intent/`. A separate `elementor_themes` registry (same 7 slugs) feeds the "Build With Elementor" seed-theme picker modal, backed by JSON files under `jsons/` (per an in-source comment referencing "Task 07").
- Per [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md): two preview image URLs are intentionally cross-wired (`theme-six`'s registry entry points at the `theme-seven` PNG and vice versa) to match the admin picker's visual order — not a bug to "fix" without also updating the picker layout.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [includes/Types/ExitIntent.php](../../includes/Types/ExitIntent.php) |
| Trait | none |
| Base class | [includes/Types/Types.php](../../includes/Types/Types.php) |
| Extension (themes, fields, Elementor bridge) | [includes/Extensions/ExitIntent/ExitIntentNotification.php](../../includes/Extensions/ExitIntent/ExitIntentNotification.php) |
| PHP frontend routing | [includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) (`get_notifications_ids()`, `get_notifications_data()`) |
| Frontend runtime (React/TS) | `nxdev/notificationx/frontend/core/ExitIntentPopup.tsx`, `useNotificationX.ts`, `NotificationContainer.tsx`, `utils.ts` — see [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md) for the full list |
| Styles | `nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss` |

## Dependencies

None required for the built-in themes (core WordPress only). Elementor (`elementor/elementor.php`) is required only for the "Build With Elementor" custom-design mode — `ExitIntentNotification` checks `class_exists('\Elementor\Plugin')` throughout and gracefully falls back to `mode = 'built_in'` when Elementor is absent or the linked post isn't published.

## Testing notes & gotchas

- This type has both a Type-level theme (`exit_intent_theme-one`, in `ExitIntent.php`) and an overlapping-but-different Extension-level `theme-one` (in `ExitIntentNotification.php`) — the Extension's richer theme registry is what actually powers the admin theme picker and React rendering; don't assume `ExitIntent.php`'s single `themes` entry is the full picture.
- Adding/editing a built-in theme normally requires changes in multiple places — see [docs/exit-intent-add-new-design.md](../features/exit-intent/add-new-design.md) for the full checklist.
- Elementor-linked campaigns hide the entire Content wizard tab and every per-theme content section (`suppress_when_elementor()`); a theme switch back to a built-in design (via the "Remove" button) resets `elementor_id`, `elementor_edit_link`, `elementor_exit_theme`, and `themes` fields together — verify all four are reset if touching that flow.
- Popup width/height for Elementor designs falls back to the design's own container width (`resolve_design_width()`) when no explicit Layout-panel Width is set — not a fixed 540px default; only top-level elements are inspected.
- `FrontEnd.php` treats `exit_intent_custom` as a hardcoded source string in `get_notifications_ids()` ([FrontEnd.php:577](../../includes/FrontEnd/FrontEnd.php#L577)) — a new/alternate Exit Intent extension with a different `$id` would not be routed into the `exit_intent` bucket without a matching code change there.
- No PHPUnit tests under `tests/` cover this type — confirmed: `grep -rli "exit_intent" tests/` returns no hits (the factory tests use `popup` as their representative fixture).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md) — uses Exit Intent as its reference implementation
- [docs/exit-intent-popup.md](../features/exit-intent/00-overview.md) — deep dive: trigger mechanism, per-theme React rendering, shared CTA helper
- [docs/exit-intent-add-new-design.md](../features/exit-intent/add-new-design.md) — checklist for adding a new built-in theme
- [docs/types/conversions.md](conversions.md) — sibling type doc with the same `Types::$module` gating caveat
- [docs/types/_TEMPLATE.md](_TEMPLATE.md) — template this doc was generated from
