# Cookie Notice Notification Type (`gdpr`)

> Renders a GDPR/CCPA-style cookie consent banner (or popup) on the frontend, with
> Accept/Reject/Customize actions and a "Preference Center" that lists cookies by
> category (Necessary, Functional, Analytics, Performance, Advertisement,
> Uncategorized) — used to obtain and record visitor cookie consent.

## At a glance

| | |
|---|---|
| **Type ID** | `gdpr` |
| **Class** | [`includes/Types/GDPR.php`](../../includes/Types/GDPR.php) |
| **Trait** | none — no `includes/Types/Traits/GDPR.php` exists |
| **Priority** | `10` (`$priority`) |
| **Default source** | `gdpr_notification` (`$default_source`) |
| **Default theme** | _not set_ — `$default_theme` is inherited from `Types` and left at `''` (`_TODO: verify_` how the admin UI picks an initial theme without this) |
| **Module gate (`$module`)** | `['modules_gdpr']` declared on the Type object. As documented for the sibling `conversions`/`exit_intent` types, `Types::$module` is **not** itself read by the factory that loads Types — the real per-extension gate is `Extension::$module`, checked via `Modules::get_instance()->is_enabled($obj->module)` in [`ExtensionFactory.php:103`](../../includes/Extensions/ExtensionFactory.php#L103). For this type, `GDPR_Notification::$module = 'modules_gdpr'` ([includes/Extensions/GDPR/GDPR_Notification.php:31](../../includes/Extensions/GDPR/GDPR_Notification.php#L31)), so the same key gates both in practice. |
| **Compatible extensions** | `GDPR_Notification` (`$types = 'gdpr'`, `$id = 'gdpr_notification'`) — the active data source/UI provider. `CCPA_Notification` (`$types = 'gdpr'`, `$id = 'ccpa_notification'`) also targets this type but sets `$show_on_module = false` / `$show_on_type = false` and registers no fields — `_TODO: verify_` its current purpose/activation path. |

## What it does

`GDPR::init()` ([includes/Types/GDPR.php:47](../../includes/Types/GDPR.php#L47)) sets the admin/dashboard title to "Cookie Notice" and registers 14 Type-level themes in `$this->themes`, split by naming convention into three families:

- `theme-light-one` … `theme-light-four` and `theme-dark-one` … `theme-dark-four` — the "popup/box" style cookie notice (`image_shape` varies: `circle`, `square`, `rounded`).
- `theme-banner-light-one`, `theme-banner-light-two`, `theme-banner-dark-one`, `theme-banner-dark-two` — the "banner" style cookie notice (full-width, `image_shape: circle`).

Every theme entry carries a `template` block (comment fields `first_param`…`fourth_param` reused from the generic comment-template shape — labelled e.g. `tag_name` / "commented on" / `tag_post_title`/`tag_post_comment` / `tag_time`) and a `rules` entry gating it to `Rules::is('gdpr_theme', false)` (light themes) or `Rules::is('gdpr_theme', true)` (dark themes) — i.e. visibility in the admin theme picker is filtered by the "Select Theme" Light/Dark toggle (`gdpr_theme` field, registered globally — see below), not per-type UI.

`_TODO: verify_` — the `template` values here (`tag_post_title`, `tag_post_comment`, "commented on") look copy-pasted from a comments-style type and appear unused for the actual cookie-banner content, which is driven instead by the dedicated `gdpr_title`/`gdpr_message`/etc. fields below.

## Data flow

1. Settings for a `gdpr` notification are stored on the `nx_bar` custom post as usual, with `source = 'gdpr_notification'`.
2. `FrontEnd::get_notifications_ids()` buckets posts whose `source == 'gdpr_notification'` into a dedicated `$gdpr_notification` array, separate from `active`/`global`/`pressbar`/`popup`/`exit_intent` ([includes/FrontEnd/FrontEnd.php:573-574](../../includes/FrontEnd/FrontEnd.php#L573-L574)).
3. `FrontEnd::get_notifications_data()` reads the `gdpr` param bucket ([includes/FrontEnd/FrontEnd.php:417-431](../../includes/FrontEnd/FrontEnd.php#L417-L431)): for each notification it applies `apply_filters('nx_filtered_post', $settings, $params)` and places the **raw settings** under `$result['gdpr'][$nx_id]['post']`, with `content` hardcoded to an empty string (`""`) — unlike bar/popup types, there is no server-rendered HTML `content` string for this type; the React runtime renders the banner entirely from the `post` settings object.
4. `FrontEnd::generate_custom_css()` special-cases `source == 'gdpr_notification'` the same way as `press_bar` when combining per-notification custom CSS ([includes/FrontEnd/FrontEnd.php:196-198](../../includes/FrontEnd/FrontEnd.php#L196-L198)).
5. `Preview.php` also special-cases `source === 'gdpr_notification'` when building preview args ([includes/FrontEnd/Preview.php:93-94](../../includes/FrontEnd/Preview.php#L93-L94)).
6. React runtime consumes `result.gdpr[...]` — `_TODO: verify_` the exact component name/file under `nxdev/notificationx/frontend/` that renders the cookie banner (not located in this pass).

## Fields & settings schema

Unlike most types, the bulk of this type's fields are **global** (registered in `GlobalFields.php`, gated with `Rules::is('type', 'gdpr')`), not on the Type or Extension class:

- **Theme picker** — the standard `themes` radio-card field plus a type-specific "Select Theme" Light/Dark `better-toggle` named `gdpr_theme` ([includes/Extensions/GlobalFields.php:288-297](../../includes/Extensions/GlobalFields.php#L288-L297)) that determines which half of the 14 Type-level themes (light vs dark) is offered.
- **Position** — `gdpr_position` (Bottom Left / Bottom Right / Center, for the popup/box themes) and `gdpr_banner_position` (Bottom / Top, for the banner themes), each gated by `Rules::includes('themes', [...])` to the matching theme family ([includes/Extensions/GlobalFields.php:404-433](../../includes/Extensions/GlobalFields.php#L404-L433)).
- **Cookies Content section** (`gdpr_content`, priority 95) — built via the `nx_content_gdpr` / `nx_content_fields_gdpr` filters, which `GDPR::add_content_fields()` and `GDPR::__add_content_fields()` hook into (see below).
- **Manager tab** (`Rules::is('type', 'gdpr')`, [includes/Extensions/GlobalFields.php:1023-1027](../../includes/Extensions/GlobalFields.php#L1023-L1027)) — a `gdpr`-only tab containing:
  - `general_settngs`: `gdpr_force_reload` (reload the page after consent so analytics scripts load), `gdpr_cookie_removal` (strip non-essential cookies on reject), `gdpr_consent_expiry` (days, default `30`).
  - `scan_cookies`: a `cookie-scanner` field type wired to `Scanner::get_instance()`'s REST endpoints (`/notificationx/v1/scan`, `/notificationx/v1/scan/status`, see [includes/Admin/Scanner/Scanner.php](../../includes/Admin/Scanner/Scanner.php)), which calls an external scanning API and can persist scanned cookies/stats as entries with `source = 'gdpr_notification'`.
  - `cookies_list_section`: a `better-repeater` per cookie category tab — **Necessary**, **Functional**, **Analytics**, **Performance**, **Advertisement**, **Uncategorized** — each using `Helper::gdpr_common_fields()` (`enabled`, `discovered`, `cookies_id`, `domain`, `duration`, `description`, `is_add_script`, `load_inside`, `script_url_pattern`) and `Helper::gdpr_cookie_list_visible_fields()` for the summary columns. Each tab also has an editable title/description via `Helper::tab_info_title()` / `tab_info_desc()`.

`GDPR::init_fields()` ([includes/Types/GDPR.php:229-234](../../includes/Types/GDPR.php#L229-L234)) hooks:

- `nx_content_gdpr` → `add_content_fields()`: adds the Content-tab fields `gdpr_title`, `gdpr_message`, `gdpr_accept_btn`, `gdpr_reject_btn`, `gdpr_customize_btn`, `gdpr_cookies_policy_toggle` (+ link text/URL fields), `gdpr_custom_logo` (media, `is_pro: true`, rules limited to specific theme slugs).
- `nx_content_fields` → `__add_content_fields()`: adds two more sections — **Preference Center** (`preference_title`, `preference_overview`, `preference_google` toggle + its message/link fields, `preference_btn`/`preference_more_btn`/`preference_less_btn`) and **Cookies List** (`cookie_list_show_banner` toggle, `cookie_list_active_label`, `cookie_list_no_cookies_label`) — both gated `Rules::is('type', 'gdpr')`.
- `nx_customize_fields` → `customize_fields()`: adds a "Visibility" section (`cookie_visibility_show_on` select — currently only `default`/"Show Everywhere"; `cookie_visibility_display_for` select — currently only `default`/"Everyone"; `cookie_visibility_delay_before` text, default `5` seconds). `_TODO: verify_` whether more `Show On`/`Display For` options exist in the pro plugin.

`GDPR_Notification::design_fields()` (the Extension) adds the Advanced Design sub-sections: `gdpr_design` (background/footer background/title/description colors + font sizes, close button color/size gated to specific themes), `gdpr_accept_btn`, `gdpr_reject_btn` (gated to specific theme slugs), `gdpr_customize_btn` — all under `Rules::is('advance_edit', true)`.

## Themes / templates

- **Type class** (`GDPR.php`): registers all 14 themes listed above directly in `$themes` (no separate Extension-level theme registry for this type, unlike `exit_intent`). Preview images are hosted at `notificationx.com/wp-content/uploads/2025/01/gdpr-*.png`.
- Theme visibility in the picker is filtered by the `gdpr_theme` Light/Dark toggle (`Rules::is('gdpr_theme', false|true)`), and by whether the current theme is a "popup/box" vs "banner" family (`gdpr_position` vs `gdpr_banner_position` rules use `Rules::includes('themes', [...])`).
- `gdpr_custom_logo` (custom logo upload, pro) is only shown for a subset of themes: `theme-banner-light-one`, `theme-light-one`, `theme-light-two`, `theme-banner-dark-one`, `theme-dark-one`, `theme-dark-two` ([includes/Types/GDPR.php:481-488](../../includes/Types/GDPR.php#L481-L488)).

## Key files

| Layer | File(s) |
|---|---|
| Type class | [includes/Types/GDPR.php](../../includes/Types/GDPR.php) |
| Trait | none |
| Base class | [includes/Types/Types.php](../../includes/Types/Types.php) |
| Extension (design fields, docs) | [includes/Extensions/GDPR/GDPR_Notification.php](../../includes/Extensions/GDPR/GDPR_Notification.php) |
| Related extension (unclear activation) | [includes/Extensions/CCPA/CCPA_Notification.php](../../includes/Extensions/CCPA/CCPA_Notification.php) |
| Global fields (theme/position/manager tab/cookie repeaters) | [includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php) (search `gdpr`) |
| Cookie-list field helpers | [includes/Core/Helper.php](../../includes/Core/Helper.php) (`gdpr_common_fields()`, `gdpr_cookie_list_visible_fields()`, `default_cookie_list()`, `tab_info_title()`, `tab_info_desc()`, `delete_server_cookies()`) |
| Cookie scanner REST endpoints | [includes/Admin/Scanner/Scanner.php](../../includes/Admin/Scanner/Scanner.php) |
| PHP frontend routing | [includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) (`get_notifications_ids()`, `get_notifications_data()`, `generate_custom_css()`) |
| Preview | [includes/FrontEnd/Preview.php](../../includes/FrontEnd/Preview.php) |
| Frontend runtime (React/TS) | `_TODO: verify_` — not located in this pass under `nxdev/notificationx/frontend/` |

## Dependencies

None required for core functionality — core WordPress only. The **Cookie Scanner** feature calls an external WPDeveloper-hosted API (`https://api.notificationx.com/cookie-scanner/v1`, or `https://notificationx-api.test/cookie-scanner/v1` when `NX_DEBUG` is defined) to scan a URL for cookies; this is an outbound network dependency, not a WP plugin dependency.

## Testing notes & gotchas

- `$result['gdpr'][$nx_id]['content']` is always `""` — this type has no server-rendered HTML content string; don't expect `get_bar_content()`-style output like `notification_bar`/`popup` types have. Verify frontend rendering purely from the `post` settings payload.
- The 14 Type-level `themes` entries' `template` fields (`tag_post_title`/`tag_post_comment`/"commented on") look like leftover boilerplate copied from a comments-style type and don't obviously map to any rendered cookie-banner copy, which instead comes from `gdpr_title`/`gdpr_message`/etc. `_TODO: verify_` whether `template` is read at all for this type's frontend rendering.
- `CCPA_Notification` also declares `$types = 'gdpr'` but sets `show_on_module = false` and `show_on_type = false` and defines no fields — `_TODO: verify_` its current role (dormant/WIP vs. silently active).
- Cookie category tabs (Necessary/Functional/Analytics/Performance/Advertisement/Uncategorized) each use an independent `better-repeater` — check `Helper::default_cookie_list()` for the seeded default rows (at minimum a `wordpress_logged_in` entry under Necessary) when testing a fresh notification.
- `gdpr_cookie_removal` / `Helper::delete_server_cookies()` hardcodes a domain-cookie allow/deny heuristic (skips `moove_gdpr_popup`, WooCommerce/`wordpress` cookies; treats `language`/`currency` and Google Analytics/Facebook Pixel cookie name patterns specially) — verify this logic if changing cookie-removal behaviour.
- `_TODO: verify_` whether any PHPUnit tests under `tests/` cover this type — none were found by name in this pass.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [docs/types/exit_intent.md](exit_intent.md) — sibling type doc with the same `Types::$module` gating caveat
- [docs/types/_TEMPLATE.md](_TEMPLATE.md) — template this doc was generated from
