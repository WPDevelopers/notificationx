# 0004 — Extension registration is gated by a settings toggle, default-on

**Date:** 2024-01-01 *(approximate — original decision predates this ADR)*
**Status:** Accepted

## Context

NotificationX ships with ~50 Extensions (data sources) — WooCommerce, EDD, FluentForm, Mailchimp, Zapier, BitIntegrations, and many more. A typical user uses 2–5. Loading all 50 unconditionally would:

- Bloat the admin source-picker UI with sources for plugins the user doesn't have.
- Run prerequisite checks (`class_exists`, `function_exists`) for every Extension on every page load.
- Burden the WP cron with schedules from Extensions the user has no intention of using.

We needed a way to let users disable Extensions they don't need, and to do so cheaply (the check must run on every page).

## Decision

Every Extension declares a `$module` property — a settings key like `modules_woocommerce`. Before instantiating an Extension, [`ExtensionFactory`](../../includes/Extensions/ExtensionFactory.php) calls [`Modules::is_enabled($module)`](../../includes/Core/Modules.php) which reads `Settings::get('settings.modules')[$module]`. A disabled module is **never instantiated at all** — its hooks don't register, its cron doesn't schedule, its REST endpoints don't mount.

**Crucially, missing keys default to enabled** ([Modules.php:71](../../includes/Core/Modules.php)):
```php
elseif ( ! isset( $enabled_types[ $module_key ] ) ) {
    return true;
}
```

So new Extensions auto-enable on first run.

## Consequences

- ✅ Disabled Extensions have zero runtime cost — they're not loaded.
- ✅ The Modules tab in settings is a single source of truth: users see one toggle per integration.
- ✅ Adding a new Extension doesn't require existing users to opt in — it just appears.
- ❌ **A disabled module is invisible.** "Why isn't my notification working?" → first thing to check is whether the module is on. Documented in [../debugging.md § the decision tree](../debugging.md#the-my-notification-isnt-showing-decision-tree).
- ❌ Default-on means privacy/performance-conscious users might be running Extensions they don't realize. If this becomes a concern, the default can be flipped per-module by explicitly seeding `false` in settings defaults.
- ❌ The check runs on every Extension on every request — fast, but not free. Acceptable because it's a single array lookup.
- ❌ A module name typo (e.g. `$module = 'modules_woocomerce'`) is *not* caught — it's just a string the Settings array won't have, so the Extension defaults-on regardless. The mismatch only matters if the user disables the *correct* key in settings UI and expects it to take effect.

**Don't:**
- Bypass `Modules::is_enabled()` to "always load" an Extension. The architecture relies on this check being the single gate.
- Set `$module` to empty string — multiple Extensions would share the same gate.
- Read `settings.modules` directly from `Settings::get()` in Extension code — go through `Modules::is_enabled()` for consistency.

**Considered but rejected:**
- Loading all Extensions and using runtime feature flags. Loses the "zero cost when disabled" property.
- Default-off for new modules. Would require existing users to opt in on every update, which is bad UX for the common case.
