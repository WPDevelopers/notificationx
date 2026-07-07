# Envato Extension (`modules_envato`)

> Connects NotificationX to [Envato Market](https://envato.com/) (ThemeForest / CodeCanyon
> author accounts) to surface item **sales** as `conversions` (Sales) type notifications.
> In the free plugin this integration is a **UI-only stub / Pro teaser** — the class that
> actually calls the Envato API lives in `notificationx-pro`.

## At a glance

| | |
|---|---|
| **Integration** | Envato Market |
| **Directory** | [`includes/Extensions/Envato/`](../../includes/Extensions/Envato/) |
| **Module key(s) (`$module`)** | `modules_envato` |
| **Feeds Types** | `conversions` (Sales — [`includes/Types/Conversions.php`](../../includes/Types/Conversions.php), registered as `'conversions' => 'NotificationX\Types\Conversions'` in [`TypesFactory.php`](../../includes/Types/TypesFactory.php)). `Conversions::$module` explicitly lists `modules_envato` alongside `modules_edd`, `modules_custom_notification`, `modules_zapier`, `modules_bitintegration`, `modules_freemius` |
| **Extension classes** | `Envato.php` → registers `$id = 'envato'` against `$types = 'conversions'` |
| **Depends on** | An Envato account/API token — see [Dependency & detection](#dependency--detection). In the free plugin there is no real dependency check (`is_active()` gates purely on module + being selected as an active source); the actual Envato API token requirement lives in the Pro override |

## What it does

The directory contains exactly one class, `Envato` (`includes/Extensions/Envato/Envato.php`),
extending the base [`Extension`](../../includes/Extensions/Extension.php). It is marked
`$is_pro = true`, so in the free plugin its role is to:

- Register the `envato` **source** in the builder's source picker (via
  `Extension::__nx_sources()`), showing the Envato icon/label with a **pro-upgrade popup**
  (`$popup['denyButtonText']` / `confirmButtonText` link to `notificationx.com/#pricing`, plus
  an embedded YouTube walkthrough).
- Register the `modules_envato` module (label "Envato", priority 17) so it appears in
  Settings under the Conversions/Sales group.
- Provide `doc()` copy (shown via `nx_instructions`) telling the user to sign in to
  an Envato account, with links to `account.envato.com`, the NotificationX docs page, a
  video tutorial, and the integrations page.
- `get_data( $args = [] )` — **does not fetch any real data**; it returns the literal
  string `'Hello From Custom Notification'` (a placeholder left over from a copy/paste of
  another extension). The only reference to an extension's `get_data()` is the static
  wrapper `ExtensionFactory::getExtension()` (`return $extension->get_data($args);`),
  but `getExtension()` is itself never called anywhere in the free or Pro plugin
  (the only `getExtension` matches in the codebase are an unrelated method in the
  bundled phpseclib X509 library). So `Envato::get_data()` is confirmed dead/unused
  code in the free plugin.

The class does **not** override `init_fields()`, `init_settings_fields()`, or
`admin_actions()`/`public_actions()` beyond the base no-ops, and does not reference
`GlobalFields` — it adds no Envato-specific builder fields itself.

### Pro implementation (context, not part of this doc's scope)

`notificationx-pro` ships its own `NotificationXPro\Extensions\Envato\Envato` class
(`notificationx-pro/includes/Extensions/Envato/Envato.php`) that `extends` the free
`Envato` class above and adds the real behaviour: an API-integrations settings section
(API token + cache duration fields, `settings.envato_token`), a `get_sales()` method that
calls `https://api.envato.com/v3/market/author/sales` with a Bearer token, a
`nx_envato_interval` cron hook (`update_data()`), and `notification_image()`/
`nx_frontend_get_entries()` overrides. **Verified in source:** the Pro `ExtensionFactory`
(`notificationx-pro/includes/Extensions/ExtensionFactory.php`) has an
`add_filter('nx_extension_classes', ...)` call that would swap the registered `envato`
class for this Pro one, but that line is currently **commented out** and its
`extension_classes()` filter callback body is empty — so that particular mechanism is
inactive. The actual override happens elsewhere: the shared `GetInstance` trait
([`includes/GetInstance.php`](../../includes/GetInstance.php)) implements a namespace
swap in `get_instance()` — it `str_replace`s `NotificationX\` → `NotificationXPro\`
and, if that subclass exists and is a subclass of the free class, instantiates the Pro
class instead. Since `ExtensionFactory` registers extensions via
`$extension::get_instance()`, calling `NotificationX\Extensions\Envato\Envato::get_instance()`
transparently returns the Pro `NotificationXPro\Extensions\Envato\Envato` when
`notificationx-pro` is active. The commented-out `nx_extension_classes` filter is a
separate, currently-unused path — not the live wiring.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`Envato.php`](../../includes/Extensions/Envato/Envato.php) | `conversions` | `envato` | Placeholder only — returns the hardcoded string `'Hello From Custom Notification'`; no API call. Real Envato Market API fetching (`get_sales()` against `api.envato.com`) exists only in the Pro class described above, outside this directory |

## Data flow

Because `get_data()` is a non-functional placeholder and nothing in this directory hooks
`save_post`/`saved_post`/a cron callback, the free-plugin `Envato` class does not itself
produce any notification entries. `includes/Core/Migration.php` does contain historic
migration logic for an `envato` source (case `'envato'` in its post-migration switch):
it reads legacy meta `_nx_meta_envato_content`, schedules a `nx_envato_interval` cron via
`Cron::get_instance()->set_cron()` when the post is enabled, and calls
`Envato::get_instance()->update_notifications($sales)` to bulk-insert previously stored
sale entries into the entries table — but this is one-time migration of old data, not an
ongoing fetch path. The ongoing fetch path (`get_sales()` → cron → `update_data()` →
`Extension::update_notifications()` → `Entries::insert_entries()`) is implemented in
the Pro class only.

## Fields & settings

No Envato-specific builder fields are added by this class. The only Envato-aware entries
in [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php) are dependency-rule
list memberships (e.g. `"envato"` included alongside `edd`, `grvf`, `give`, etc. so the
shared **featured image** / notification-image toggle applies to it too) — there is no
Envato-specific field registry here. Envato's own settings fields (API token, cache
duration, Connect button) are defined in the Pro class's `api_integration_settings()`,
not in this directory.

## Dependency & detection

- **Required service:** an Envato account with an API access token
  (`https://build.envato.com`), used only by the Pro class.
- **Detection:** the free `Envato` class sets none of `$class`, `$function`, or
  `$constant` on the base `Extension`, so `is_active()` (see
  [`Extension.php`](../../includes/Extensions/Extension.php)) only checks whether the
  `envato` source has been selected as an active item — there is no third-party
  presence check in the free plugin. The Pro class instead gates functionally on
  `Settings::get('settings.envato_token')` being non-empty (`source_error_message()`
  shows an admin error linking to the API-integrations settings tab when it's missing;
  `get_sales()` returns `[]` when the token is empty).
- **When absent (no token, Pro inactive/disabled):** the source still appears in the
  builder (behind the pro-upgrade popup when Pro is not licensed), but no real sales
  data is ever fetched or displayed.

## Key files

| Purpose | File |
|---|---|
| Extension class (free/stub) | [`includes/Extensions/Envato/Envato.php`](../../includes/Extensions/Envato/Envato.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type it feeds | [`includes/Types/Conversions.php`](../../includes/Types/Conversions.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'envato' => 'NotificationX\Extensions\Envato\Envato'`) |
| Legacy migration handling | [`includes/Core/Migration.php`](../../includes/Core/Migration.php) (`case 'envato':`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Pro implementation (out of this repo's scope) | `notificationx-pro/includes/Extensions/Envato/Envato.php` (sibling plugin) |

## Testing notes & gotchas

- `get_data()` returning a hardcoded string is very likely dead/placeholder code — do not
  assume it reflects real Envato sales data; confirm with a maintainer before relying on
  it or "fixing" it without checking whether Pro's `get_sales()`/`update_data()` is the
  actual intended path.
- The Pro override is **not** wired through the Pro `ExtensionFactory`'s
  `nx_extension_classes` filter (that call is commented out and empty). The live
  mechanism is the `GetInstance` trait's namespace swap in `get_instance()` (see
  [Pro implementation](#pro-implementation-context-not-part-of-this-docs-scope)) —
  don't assume Envato is broken under Pro just because the factory filter is disabled;
  the real Pro class is instantiated via `get_instance()`.
- `Migration.php`'s `envato` case only runs during the legacy post-migration flow (reading
  `_nx_meta_envato_content`); it is not a substitute for the ongoing cron-based fetch.
- Per this repo's `CLAUDE.md`, Pro-only logic lives in the sibling `notificationx-pro`
  plugin — the Pro class details above are documented here only as necessary context for
  understanding what the free `Envato` stub is a placeholder for.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
