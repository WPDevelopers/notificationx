# CCPA Extension (`ccpa_notification`)

> Registers a second Extension (`ccpa_notification`) on the same `gdpr` Type that
> the built-in [Cookie Notice / GDPR extension](gdpr.md) uses. Unlike `GDPR_Notification`,
> it is hidden from the module and type-selector UI and implements no fields, themes,
> or data logic beyond a title — as far as the source shows, it is a registered but
> effectively inert stub, not a working CCPA-specific data source. `_TODO: verify_
> with the plugin authors whether this is planned/in-progress or dead code.

## At a glance

| | |
|---|---|
| **Integration** | `CCPA` |
| **Directory** | [`includes/Extensions/CCPA/`](../../includes/Extensions/CCPA/) |
| **Module key(s) (`$module`)** | none set (`$module` left at the `Extension` base default `''`); `$show_on_module = false` so it never calls `Modules::add()` — it registers no module of its own |
| **Feeds Types** | `gdpr` (shared with `gdpr_notification`, see [includes/Types/GDPR.php](../../includes/Types/GDPR.php)) |
| **Extension classes** | `CCPA_Notification.php` → `(types: gdpr, id: ccpa_notification)` |
| **Depends on** | No third-party plugin/service detected in this class — `$class`, `$function`, `$constant` (the `Extension::is_active()` gates) are all left empty, so `is_active()` always returns `true` for it |

## What it does

`CCPA_Notification` (`includes/Extensions/CCPA/CCPA_Notification.php`) extends
[`Extension`](../../includes/Extensions/Extension.php) and pairs with the `gdpr`
Type — the same Type the built-in "Cookie Notice" feature (`GDPR_Notification`,
`$id = 'gdpr_notification'`) uses. Its `init_extension()` only sets
`$this->title = __('CCPA', 'notificationx')`. It:

- Sets `$show_on_module = false` and `$show_on_type = false`, so `register_module()`
  never adds a settings-page module for it and `TypeFactory::register_types()` is
  never called from this class (the `gdpr` Type is registered instead via
  `GDPR_Notification`, which does set `$module = 'modules_gdpr'` and defaults
  `show_on_module`/`show_on_type` to `true`).
- Declares no `get_data()`, `doc()`, `save_post()`, `fallback_data()`,
  `notification_image()`, content/design/customize field filters, or theme
  overrides — everything `GDPR_Notification` adds for the actual Cookie Notice
  builder UI is absent here.
- Is registered in `ExtensionFactory` (so it does instantiate on `init` and its
  constructor runs), but `ccpa_notification` does not appear anywhere else in
  `includes/` (not in `FrontEnd.php`, `Preview.php`, `Scanner.php`, or any Type's
  `default_source`) — i.e. nothing in the data/render pipeline currently keys off
  this extension's `$id`.

Practically: enabling/using "CCPA" today has no observable effect distinct from
the existing GDPR/Cookie Notice extension. `_TODO: verify_ with product/roadmap
whether CCPA-specific fields (e.g. "Do Not Sell My Info" link, US state-specific
banner copy) are planned for this class, or whether it should be removed.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`CCPA_Notification.php`](../../includes/Extensions/CCPA/CCPA_Notification.php) | `gdpr` | `ccpa_notification` | None — no `get_data()` method is defined on this class or inherited from `Extension` (the base class has no `get_data()` either); `init_extension()` only sets `$this->title`. |

## Data flow

Because this class defines no data-fetching method, no field filters, and is not
referenced by `FrontEnd.php`/`Preview.php`/`Scanner.php`, there is no traceable
source-event → `get_data()` → entries → FrontEnd → REST → React flow for
`ccpa_notification` distinct from the general Extension lifecycle described in
[Extension.php](../../includes/Extensions/Extension.php) (`__construct()` →
`register_module()`/`TypeFactory::register_types()` → `initialize()` →
`init_extension()` on WP `init`). Compare with the working flow for the sibling
`gdpr_notification` extension, whose fields feed `includes/FrontEnd/FrontEnd.php`
(`source == 'gdpr_notification'` branches) and `includes/Admin/Scanner/Scanner.php`.

## Fields & settings

None. `CCPA_Notification` does not hook `nx_content_fields`, `nx_customize_fields`,
`nx_design_tab_fields`, or any `GlobalFields` registry entries. All Cookie-Notice
builder fields (title, message, accept/reject/customize buttons, preference
center, cookies list, design colors) are added by `GDPR_Notification`
(`includes/Extensions/GDPR/GDPR_Notification.php`) and the `GDPR` Type itself
(`includes/Types/GDPR.php`), not by this class.

## Dependency & detection

- No third-party plugin/service dependency was found for this class.
- `Extension::is_active()` gates on `$this->class` / `$this->function` /
  `$this->constant` (via `class_exists()` / `function_exists()` / `defined()`);
  `CCPA_Notification` leaves all three unset, so this check is always satisfied
  and the module-enabled check (`Modules::is_enabled()`) also defaults to `true`
  because no module key (`''`) is ever registered against the settings option.
- Net effect: there is nothing to detect an "absent" state for — the extension
  is always considered active; it simply doesn't do anything beyond registering
  a title. `_TODO: verify_ if a real CCPA compliance check (e.g. US visitor
  detection, a companion consent-management plugin) was intended here.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/CCPA/CCPA_Notification.php`](../../includes/Extensions/CCPA/CCPA_Notification.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'ccpa_notification' => 'NotificationX\Extensions\CCPA\CCPA_Notification'`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared Type (`gdpr`) | [`includes/Types/GDPR.php`](../../includes/Types/GDPR.php) |
| Sibling extension (working Cookie Notice UI) | [`includes/Extensions/GDPR/GDPR_Notification.php`](../../includes/Extensions/GDPR/GDPR_Notification.php) |

## Testing notes & gotchas

- Before assuming any CCPA-specific behavior exists at runtime, grep for
  `ccpa_notification` across `includes/` and `nxdev/` — as of this writing it
  only appears in `ExtensionFactory.php` and this class itself.
- If you are asked to build out real CCPA functionality, use
  `GDPR_Notification.php` as the template (field filters, `doc()`, design
  fields) rather than assuming `CCPA_Notification.php` already has parity.
- No tests reference CCPA or `ccpa_notification`; `tests/test-extension-factory.php`
  does not name it (confirmed: `CCPA`/`ccpa_notification` appears only in
  `ExtensionFactory.php` and `CCPA_Notification.php` across `includes/` and `nxdev/`,
  and nowhere in `notificationx-pro`).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type doc: [GDPR / Cookie Notice](../types/gdpr.md) (the `gdpr` Type this
  extension pairs with; see also [`includes/Types/GDPR.php`](../../includes/Types/GDPR.php))
