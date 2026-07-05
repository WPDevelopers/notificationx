# GDPR Extension (`modules_gdpr`)

> Powers NotificationX's Cookie Notice / Consent banner. Unlike most Extensions,
> it does not pull data from a third-party plugin or API — it is a self-contained
> extension whose "data" is just the admin-configured banner copy (title,
> message, button labels, cookie list, preference center) stored on the
> notification post itself.

## At a glance

| | |
|---|---|
| **Integration** | `GDPR` (Cookie Notice) |
| **Directory** | [`includes/Extensions/GDPR/`](../../includes/Extensions/GDPR/) |
| **Module key(s) (`$module`)** | `modules_gdpr` |
| **Feeds Types** | `gdpr` (see [`includes/Types/GDPR.php`](../../includes/Types/GDPR.php)) |
| **Extension classes** | `GDPR_Notification.php` → `(type: gdpr, id: gdpr_notification)` |
| **Depends on** | None (no third-party plugin/service required or detected). The class doc-comment says "Wistia Extension" — appears to be copy-pasted boilerplate; disregard, it is unrelated to the actual GDPR behaviour. |

## What it does

From the user's perspective, enabling the `modules_gdpr` module and creating a
notification of type `gdpr` shows a cookie-consent banner (accept/reject/customize
buttons, optional cookie-policy link, and an optional preference-center panel
listing cookie categories). There is no external event source to listen to —
the banner content is authored directly in the NotificationX builder
(`Types\GDPR::add_content_fields()` / `__add_content_fields()`), and
`GDPR_Notification` (the Extension half of the pair) only contributes the
**design-related** builder fields (colors, font sizes for title/description/
buttons) plus visibility gating for shared sections like `appearance`,
`queue_management`, `timing`, `behaviour`, and `utm_options`/`content`.

Because there is nothing to fetch, `GDPR_Notification` defines **no `get_data()`
method** — `_TODO: verify_` whether any other hook (e.g. `save_post`) moves the
authored fields into an entry; no such method exists on this class either, so
rendering appears to read directly from the notification post's fields rather
than the `Entries`/analytics table used by data-pulling extensions like
WooCommerce or EDD.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source (`get_data()`) |
|---|---|---|---|---|
| [`GDPR_Notification.php`](../../includes/Extensions/GDPR/GDPR_Notification.php) | `gdpr` | `gdpr_notification` | `modules_gdpr` | No `get_data()` defined — no external data source. Provides `design_fields()`, `content_fields()`, `customize_fields()` (builder field/section visibility) and `doc()` (help-tab copy) only. |

`init_extension()` just sets display labels: `$this->title = 'GDPR'` and
`$this->module_title = 'Cookie Notice'` (the latter is also what shows as the
module's label in Settings, via `Modules::update()` in the base `Extension`
class).

## Data flow

There is no source-event → `get_data()` → entries pipeline here (`_TODO: verify_`
the exact render path — likely: builder saves the post's meta/content fields →
`Types\GDPR` + `FrontEnd` render the banner directly from those fields on page
load, with no `Entries`/`Database` row created per "notification"). This differs
from event-driven extensions (Sales, Reviews, etc.) where `get_data()` writes
rows that are then polled/streamed to the frontend.

## Fields & settings

- **Content fields** (on the Type, [`includes/Types/GDPR.php`](../../includes/Types/GDPR.php)): `gdpr_title`, `gdpr_message`, `gdpr_accept_btn`, `gdpr_reject_btn`, `gdpr_customize_btn`, `gdpr_cookies_policy_toggle` + link text/URL, `gdpr_custom_logo` (pro), plus the `preference_center` section (`preference_title`, `preference_overview`, `preference_google*`, `preference_btn`, `preference_more_btn`, `preference_less_btn`) and `cookies_list` section (`cookie_list_show_banner`, `cookie_list_active_label`, `cookie_list_no_cookies_label`).
- **Design fields** (on the Extension, `GDPR_Notification::design_fields()`): `gdpr_design` section (background/footer/title/description colors & font sizes, close-button color/size), `gdpr_accept_btn`, `gdpr_reject_btn`, `gdpr_customize_btn` sections (each with background/border/text color + font size). Several are gated by `Rules::is('themes', ...)` to specific GDPR theme slugs (e.g. `gdpr_theme-banner-light-one`, `gdpr_theme-dark-two`, …).
- **Customize fields**: `GDPR_Notification::customize_fields()` restricts the shared `appearance`, `queue_management`, `timing`, `behaviour`, `sound_section` fields to `Rules::is('source', 'gdpr_notification', true, ...)`. The Type itself (`Types\GDPR::customize_fields()`) additionally adds a `visibility` section (`cookie_visibility_show_on`, `cookie_visibility_display_for`, `cookie_visibility_delay_before`).
- The class imports `NotificationX\Extensions\GlobalFields` but does not call it anywhere in this file — `_TODO: verify_` whether it's dead code left from a template.

## Dependency & detection

- **No third-party plugin/service dependency.** `GDPR_Notification` leaves the base `Extension` properties `$class`, `$function`, and `$constant` unset, so `Extension::is_active()` never fails a `class_exists()`/`function_exists()`/`defined()` check for this extension — it is considered active whenever the `modules_gdpr` module is enabled and a `gdpr`-type notification exists (`PostType::get_active_items()`).
- The `doc()` method recommends installing the **WP Consent API** plugin (`https://wordpress.org/plugins/wp-consent-api/`) in its help copy, but this is editorial guidance only — the code never checks for it (`_TODO: verify_` if any other part of the plugin does).

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/GDPR/GDPR_Notification.php`](../../includes/Extensions/GDPR/GDPR_Notification.php) |
| Paired Type | [`includes/Types/GDPR.php`](../../includes/Types/GDPR.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'gdpr_notification' => 'NotificationX\Extensions\GDPR\GDPR_Notification'`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (imported, unused in this class) |

## Testing notes & gotchas

- Because this extension has no `get_data()`/entries pipeline, don't expect rows in the analytics/entries table for GDPR notifications — verify content instead via the builder-saved post fields and the rendered frontend banner.
- The `gdpr` Type ships many theme slugs (`gdpr_theme-light-one` … `gdpr_theme-banner-dark-two`); several design fields (close-button, reject-button) are conditionally shown only for specific theme slugs — check `Rules::is('themes', ...)` conditions when adding/renaming a theme.
- The stray `@package`/class doc-comment ("Wistia Extension") at the top of `GDPR_Notification.php` is misleading copy-paste; don't use it as a signal of the class's actual purpose.
- No existing tests under `tests/` were found referencing GDPR (`_TODO: verify_` with a repo-wide search if this changes).

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- Related Type docs under [../types/](../types/) (`_TODO: verify_` — no `types/gdpr.md` found at doc-writing time)

---
