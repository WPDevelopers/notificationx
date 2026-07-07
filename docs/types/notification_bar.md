# Notification Bar Notification Type (`notification_bar`)

> Shows a full-width bar (top or bottom of the page) with a message, optional
> countdown, and CTA button — e.g. an announcement, promo, or "offer ends in..."
> bar. Internally this Type is called **NotificationBar**; its sole data-source
> extension is **PressBar** (`press_bar`), and the admin UI labels the feature
> "Notification Bar" / "Press Bar".

## At a glance

| | |
|---|---|
| **Type ID** | `notification_bar` |
| **Class** | [`includes/Types/NotificationBar.php`](../../includes/Types/NotificationBar.php) |
| **Trait** | none — `NotificationBar` has no matching file in `includes/Types/Traits/` |
| **Priority** | `15` (`$priority`) |
| **Default source** | `press_bar` (`$default_source`) |
| **Default theme** | Not set on the Type class itself (`$themes = []`, inherited `$default_theme = ''`). The **PressBar extension** declares `$default_theme = 'press_bar_theme-two'` ([PressBar.php:43](../../includes/Extensions/PressBar/PressBar.php#L43)) and its own theme catalogue (see below) — the Type class carries no themes of its own. |
| **Link type** | `-1` (`$link_type`) — a sentinel `Extension::get_link_type()` ([`includes/Extensions/Extension.php:196-207`](../../includes/Extensions/Extension.php#L196-L207)) rewrites to `''`, which suppresses the `@link_type:` source-trigger ([Extension.php:227-231](../../includes/Extensions/Extension.php#L227-L231)) and the Link Type field dependency — i.e. no Link Type field is offered for this type, unlike the string link-type ids used by other Types (e.g. `conversions`'s `product_page`). |
| **Module gate** | `NotificationBar::$module` is declared as `[]` (empty) on the Type class — it does not appear to gate anything itself, consistent with the pattern documented in [`docs/types/conversions.md`](conversions.md). The extension that actually loads for this type, **PressBar**, is gated by `$module = 'modules_bar'` ([PressBar.php:41](../../includes/Extensions/PressBar/PressBar.php#L41)), checked via `Modules::get_instance()->is_enabled($obj->module)` in [`ExtensionFactory::register_extensions()`](../../includes/Extensions/ExtensionFactory.php). If `modules_bar` is off, PressBar never registers and `notification_bar` has no data source. |
| **Compatible extensions** | Exactly one: **PressBar** (`$id = 'press_bar'`, `$types = 'notification_bar'`) — see [`includes/Extensions/PressBar/PressBar.php`](../../includes/Extensions/PressBar/PressBar.php). Verified via `grep -rl "'notification_bar'" includes/Extensions` (only hits: `GlobalFields.php` and `PressBar.php`). |

There is already a deep reference doc for this feature's builder (Elementor/Gutenberg) mechanics: **[`docs/notification-bar-reference.md`](../features/notification-bar/reference.md)**. This page is deliberately a thinner overview per the Types-doc template; read the reference doc for anything about the Elementor/Gutenberg import flow, theme registries, or the porting checklist.

## What it does

A `notification_bar` campaign is always rendered by the `press_bar` source (`$default_source = 'press_bar'`) — there is no other extension registered under this Type. Unlike most other Types, PressBar's content is **not React-rendered on the frontend**: `FrontEnd::get_bar_content()` calls `PressBar::print_bar_notice($settings)` ([PressBar.php:2070](../../includes/Extensions/PressBar/PressBar.php#L2070-L2088)) which returns raw HTML chosen from one of three mutually-exclusive paths:

1. **Elementor-built** — if `elementor_id` is set and published, returns `Elementor\Plugin::$instance->frontend->get_builder_content_for_display($elementor_id, false)`.
2. **Gutenberg-built** — else if `gutenberg_id` is set, returns `do_blocks($post->post_content)` for the linked `nx_bar_eb` post.
3. **Built-in theme (custom editor)** — else, returns `do_shortcode($settings->press_content)`, the plain rich-text content typed into the "Static Text" content field (or the currently active slide, if "Slide Multiple Text" is chosen — see `content_fields()`).

`NotificationBar::show_on_exclude()` ([NotificationBar.php:55](../../includes/Types/NotificationBar.php#L55-L217)) is the Type's other main job: it's hooked to the generic `nx_show_on_exclude` filter and decides whether a given bar should be suppressed for the current request, based on (in order): bar-reappearance cookie state (`bar_reappearance`), daily/weekly/custom `schedule_type` windows, `country_targeting`, and `targeting_user_roles`.

## Data flow

Trace one bar end-to-end:

1. `FrontEnd::get_notifications_ids()` ([FrontEnd.php:487](../../includes/FrontEnd/FrontEnd.php#L487-L613)) buckets every enabled `nx_bar` post by `source`; any post with `source == 'press_bar'` goes into the `pressbar` bucket ([FrontEnd.php:555](../../includes/FrontEnd/FrontEnd.php#L555-L572)), separate from the generic `active`/`global` buckets used by React-rendered Types.
2. `FrontEnd::get_notifications_data()` ([FrontEnd.php:243](../../includes/FrontEnd/FrontEnd.php#L243-L467)) processes the `pressbar` bucket in its own branch ([FrontEnd.php:385-415](../../includes/FrontEnd/FrontEnd.php#L385-L415)): it normalizes `position` (`bottom_left` → `top`), resolves the Elementor post id, filters the `button_url`, then calls `get_bar_content()` and stores `result['pressbar'][$nx_id] = ['post' => ..., 'content' => ...]`.
3. `apply_filters('nx_show_on_exclude', false, $settings)` (wired to `NotificationBar::show_on_exclude`) runs earlier, inside `get_notifications_ids()`, and can drop the bar before it ever reaches step 2.
4. The resulting `pressbar` array is localized to the frontend (`window.notificationXArr`) alongside the other buckets; the bar's own HTML (`content`) is pre-rendered server-side rather than assembled by the React runtime — the JS side is only responsible for positioning/sticky/countdown/close behaviour, not content templating. On the React side, [`useNotificationX.ts`](../../nxdev/notificationx/frontend/core/useNotificationX.ts) reads `response.pressbar` into `pressbarNotices` state (normalized via `normalizePressBar`, [`utils.ts`](../../nxdev/notificationx/frontend/core/utils.ts)), and [`NotificationContainer.tsx`](../../nxdev/notificationx/frontend/core/NotificationContainer.tsx) routes `config.type == 'notification_bar'` to the [`Pressbar.tsx`](../../nxdev/notificationx/frontend/core/Pressbar.tsx) component.

## Fields & settings schema

`NotificationBar` itself declares no fields (`init_fields()` is not overridden — it's inherited as a no-op from `Types`). All builder fields are added by the **PressBar extension**'s `init_fields()` ([PressBar.php:332](../../includes/Extensions/PressBar/PressBar.php#L332-L342)), gated by `Rules::is('source', 'press_bar', ...)`. Notable groups:

- **Content** (`content_fields()`, [PressBar.php:1682](../../includes/Extensions/PressBar/PressBar.php#L1682)) — `bar_content_type` (`static` vs. pro-only `sliding`), `press_content` (rich text), `sliding_content` (repeater of slide texts, pro), `sliding_interval`, plus `button_text` / `button_url` / `button_icon`.
- **Design** (`design_tab_fields()` / `design_tab_fields_for_button()`, [PressBar.php:527](../../includes/Extensions/PressBar/PressBar.php#L527-L1120)) — `bar_bg_color`, `bar_bg_image`, `bar_text_color`, `bar_counter_bg`/`bar_counter_text_color`, `bar_close_color`/`bar_close_button_size`/`bar_close_position` (+ its top/left/right offsets), `nx_bar_border_radius_*`, and the entire "Import Design" panel (Elementor/Gutenberg build-edit-remove buttons and hidden state fields — see the reference doc §3-4).
- **Customize** (`customize_fields()`, [PressBar.php:1174](../../includes/Extensions/PressBar/PressBar.php#L1174-L1371)) — `sticky_bar`, `pressbar_body` (overlap vs. push content), `auto_hide`/`hide_after`, `appear_condition` (`after_few_seconds` vs. `on_scroll`) + `scroll_offset`, `initial_delay`, `country_targeting`, `targeting_user_roles`, and a pro `schedule` section (`schedule_type`: `daily`/`weekly`/`custom` + their time-range fields) — these are exactly the settings keys `show_on_exclude()` reads.
- **Display** (`display_fields()`, [PressBar.php:1125](../../includes/Extensions/PressBar/PressBar.php#L1125-L1155)) — `bar_reappearance` (`dont_show_welcomebar` / `show_welcomebar_next_visit` / `show_welcomebar_every_page`) and `bar_cache_duration_for_dont_show`.
- **REST helper** — `NotificationBar::restResponse($params)` ([NotificationBar.php:219](../../includes/Types/NotificationBar.php#L219-L224)) powers the `country_targeting` field's ajax options lookup (`Helper::nx_get_all_country()`), registered against `/notificationx/v1/get-data`.

## Themes / templates

The Type class has no `$themes` of its own (`$themes = []`, never populated). Themes live entirely on the **PressBar extension**:

- `PressBar::$themes` — 7 built-in "Custom" themes (`theme-one` … `theme-seven`), each a set of style `defaults` (background color/gradient, button colors, border radius, countdown colors) applied on top of the shared bar editor — not distinct templates/components.
- `PressBar::$bar_themes` — 5 Elementor seed themes (`theme-one`…`theme-five`) used by the "Build With Elementor" modal, importing from `includes/Extensions/PressBar/jsons/*.json`.
- `PressBar::$block_themes` — 7 Gutenberg block-pattern seed themes (`theme-one`…`theme-seven`), importing from `includes/Extensions/PressBar/jsons-gb/*.json`; `theme-five`/`six`/`seven` are gated behind an Essential Blocks dependency check (`load_plugin_dependencies()`).

Full mechanics (state machine for which renderer wins, import/remove flows, dependency gating) are documented in **[`docs/notification-bar-reference.md`](../features/notification-bar/reference.md)**.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [includes/Types/NotificationBar.php](../../includes/Types/NotificationBar.php) |
| Trait | none |
| Extension | [includes/Extensions/PressBar/PressBar.php](../../includes/Extensions/PressBar/PressBar.php) |
| Elementor importer | [includes/Extensions/PressBar/importer.php](../../includes/Extensions/PressBar/importer.php) |
| Seed content | [includes/Extensions/PressBar/jsons/](../../includes/Extensions/PressBar/jsons/) (Elementor), [includes/Extensions/PressBar/jsons-gb/](../../includes/Extensions/PressBar/jsons-gb/) (Gutenberg) |
| PHP frontend | [includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) — `get_notifications_ids()`, `get_notifications_data()`, `get_bar_content()` |
| Frontend runtime | [`nxdev/notificationx/frontend/core/Pressbar.tsx`](../../nxdev/notificationx/frontend/core/Pressbar.tsx) (component), [`useNotificationX.ts`](../../nxdev/notificationx/frontend/core/useNotificationX.ts) (`pressbarNotices` state from `response.pressbar`), [`NotificationContainer.tsx`](../../nxdev/notificationx/frontend/core/NotificationContainer.tsx) (routes `type == 'notification_bar'`), [`utils.ts`](../../nxdev/notificationx/frontend/core/utils.ts) (`normalizePressBar`) |

## Dependencies

None required for the built-in ("Custom") theme path — core WordPress only. The builder paths are optional, feature-gated dependencies:
- **Elementor** (`elementor/elementor.php`) — required for the "Build With Elementor" path; PressBar shows an Install/Activate button when absent.
- **Essential Blocks** — required for Gutenberg seed themes `theme-five`/`theme-six`/`theme-seven` only (see `load_plugin_dependencies()`); themes one–four use only core blocks.
- **WP Rocket** (optional) — if active, `rocket_rucss_safelist()` adds Elementor-built bars' CSS to the Remove Unused CSS safelist.

## Testing notes & gotchas

- This Type does not use the React notification-content pipeline that most other Types rely on — the bar's HTML is server-rendered via `print_bar_notice()`, so changes to bar content/behaviour usually only need PHP-side changes (unlike the "PHP + React desync" risk called out for other Types). The countdown/sticky/close/positioning chrome is still JS — owned by [`nxdev/notificationx/frontend/core/Pressbar.tsx`](../../nxdev/notificationx/frontend/core/Pressbar.tsx).
- `show_on_exclude()` reads settings keys (`bar_reappearance`, `schedule_type` + its sub-fields, `country_targeting`, `targeting_user_roles`) that are declared by the PressBar extension, not this Type — if you rename a field there, update `NotificationBar.php` in lockstep.
- Elementor/Gutenberg-backed bars suppress most of the built-in Content/Design/Customize fields via `Rules::isOfType('elementor_id'|'gutenberg_id', 'number', true, ...)` — see the reference doc §3 for the exact suppression sites; don't assume `press_content` etc. are always populated.
- `NotificationBar::$link_type = '-1'` and `NotificationBar::$module = []`/`$themes = []` are declared but, per the pattern already documented for `conversions`, `Types::$module`/`$themes` are not read by the factory — confirmed: `grep -n "module" includes/Types/TypesFactory.php` returns nothing, so `Types::$module` gates nothing at Type-registration time (the PressBar extension's own `$module = 'modules_bar'` is the real gate). Treat these Type-class properties as informational.
- No dedicated test files for this Type exist under `tests/` — confirmed: the suite (`test-types-factory.php`, `test-rest.php`, etc.) references no `notification_bar`/`press_bar` fixtures.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Notification Bar — Reference](../features/notification-bar/reference.md) — the authoritative deep-dive for Elementor/Gutenberg builder mechanics on this type
- [docs/types/_TEMPLATE.md](_TEMPLATE.md) — template this doc was generated from
