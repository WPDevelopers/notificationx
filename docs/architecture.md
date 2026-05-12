# Architecture

How NotificationX is wired internally. Read [../CLAUDE.md](../CLAUDE.md) first for the one-paragraph mental model.

## Bootstrap chain

[notificationx.php](../notificationx.php) defines path/version constants, loads `vendor/autoload.php`, then calls `\NotificationX\NotificationX::get_instance()` ([includes/NotificationX.php:70](../includes/NotificationX.php)).

The constructor instantiates subsystems in this order ([NotificationX.php:52–88](../includes/NotificationX.php)):

| # | Subsystem | Trait | Notes |
|---|---|---|---|
| 1 | `Settings` | `GetInstance` | `key=notificationx`, `store=options`, `auto_commit=true`. Wraps `lib-settings` package. |
| 2 | `WPDRoleManagement` | (none — direct `new`) | The lone exception to the singleton rule. |
| 3 | `Upgrader` | `GetInstance` | Version transitions; reads `NOTIFICATIONX_VERSION`. |
| 4 | `Admin` | `GetInstance` | Loaded only if `is_admin()`. |
| 5 | `FrontEnd` | `GetInstance` | Popup/bar runtime + asset enqueue. |
| 6 | `REST` | `GetInstance` | Hooked on `rest_api_init`. Namespace `notificationx/v1`. |
| 7 | `Cron` | `GetInstance` | Custom WP-Cron intervals + tick dispatch. |
| 8 | `QuickBuild` | `GetInstance` | QuickBuilder form engine bridge. |
| 9 | `ShortcodeInline` | `GetInstance` | `[notificationx]` shortcode. |
| 10 | `Blocks` | `GetInstance` | Gutenberg blocks bridge. |
| 11 | `CoreInstaller` | `GetInstance` | Activation/install steps. |
| 12 | `WPML`, `VisualPortfolio` | `GetInstance` | Third-party shims. |

Extension initialization happens later, on `plugins_loaded` ([NotificationX.php:71](../includes/NotificationX.php)), via `ExtensionFactory::get_instance()`. This delay matters: third-party plugins (WC, EDD, FluentForm) must finish loading before our Extensions try to detect them.

## The Type ↔ Extension model

The core abstraction.

```
         ┌──────────────────┐         ┌────────────────────┐
         │   TypesFactory   │         │ ExtensionFactory   │
         │  (lazy register) │         │ (eager register,   │
         │                  │         │  module-gated)     │
         └────────┬─────────┘         └─────────┬──────────┘
                  │                              │
        registers │                              │ registers
                  ▼                              ▼
         ┌──────────────────┐         ┌────────────────────┐
         │     Type         │◄────────│     Extension      │
         │ (display kind)   │  $types │ (data source)      │
         │  Conversions     │         │  WooCommerceSales  │
         │  Reviews         │         │  EDDSales          │
         │  NotificationBar │         │  CF7Subscriptions  │
         │  Popup           │         │  Zapier            │
         │  ExitIntent      │         │  BitIntegrations   │
         │  ...             │         │  ...               │
         └──────────────────┘         └────────────────────┘
              │                                  │
              │                                  │
              ▼                                  ▼
        defines templates,             provides get_data(),
        field schema,                  module gating,
        display logic                  third-party hooks
```

- **Types** ([includes/Types/](../includes/Types/)) — *what kind of notification* it is. Registered lazily in [TypesFactory](../includes/Types/TypesFactory.php): only instantiated when an Extension requests them via `register_types($id)`. Hardcoded class map at [TypesFactory.php:23–42](../includes/Types/TypesFactory.php); filtered via `nx_types_classes` ([line 51](../includes/Types/TypesFactory.php)).
- **Extensions** ([includes/Extensions/](../includes/Extensions/)) — *data sources*. Hardcoded class map in [ExtensionFactory::$extension_classes](../includes/Extensions/ExtensionFactory.php) (lines 31–87, ~50 entries); filtered via `nx_extension_classes` ([line 98](../includes/Extensions/ExtensionFactory.php)). On init, the factory loops the map, checks `Modules::is_enabled($obj->module)` ([Modules.php:66](../includes/Core/Modules.php)), and adds enabled ones to `$extensions[$id]` and `$types[$type_id][$id]`.

A notification is a `(Type, Extension)` pair. The post stores `type` + `source` columns; the runtime joins them to produce a display.

See [recipes/add-extension.md](recipes/add-extension.md) and [recipes/add-type.md](recipes/add-type.md) for how to add either.

## Module gating

Every Extension declares `$module = 'modules_<name>'`. Before registration, `ExtensionFactory` calls `Modules::is_enabled($module)`:

```php
// includes/Core/Modules.php:66
public function is_enabled($module) {
    $enabled = (array) Settings::get_instance()->get('settings.modules');
    if ( isset($enabled[$module]) && $enabled[$module] ) return true;
    if ( ! isset($enabled[$module]) )                    return true;  // default-on
    return false;
}
```

**Key consequence:** a missing settings key = enabled. New modules auto-enable on first run until the user explicitly disables them. Settings UI for these toggles is in **NotificationX → Settings → Modules**, driven by each Extension's `$module_title` and `$module_priority`.

Setting key path: `settings.modules['modules_woocommerce']`, `settings.modules['modules_fluentform']`, etc. — pattern is always `modules_<slug>`.

## Storage

Three custom tables defined in [includes/Core/Database.php](../includes/Core/Database.php):

| Table | Purpose | Key columns |
|---|---|---|
| `{$prefix}nx_posts` | Notification records (what the user creates in the builder) | `nx_id` PK, `title`, `type`, `source`, `theme`, `enabled`, `data` (LONGTEXT) |
| `{$prefix}nx_entries` | Source-fetched data rows (one entry = one popup) | `entry_id` PK, `nx_id` FK, `source`, `entry_key`, `data` (LONGTEXT), `created_at` |
| `{$prefix}nx_stats` | Per-day analytics | `stat_id` PK, `nx_id` FK, `views`, `clicks`, `created_at` |

Plus a custom post type slug `notificationx` ([PostType.php:33](../includes/Core/PostType.php)) — note: the *post type* exists alongside the `nx_posts` table; both coexist for historical/migration reasons.

Schema/version transitions live in [includes/Core/Migration.php](../includes/Core/Migration.php) and [includes/Core/Upgrader.php](../includes/Core/Upgrader.php), driven by `NOTIFICATIONX_VERSION`.

## REST API

Namespace `notificationx/v1` ([includes/Core/REST.php:43](../includes/Core/REST.php)). Six controller classes mounted ([REST.php:50–55](../includes/Core/REST.php)): `Posts`, `Integration`, `Entries`, `Analytics`, `BulkAction`, `Popup`. Full route table: [rest-api.md](rest-api.md).

## Dual frontend runtimes (the silent-desync trap)

Three webpack builds, three runtime contexts:

| Build | Config | Entry | Where it runs |
|---|---|---|---|
| Admin SPA | [webpack.config.js](../webpack.config.js) | [nxdev/index.tsx](../nxdev/index.tsx) | wp-admin notification builder |
| Frontend popup runtime | [webpack.frontend.config.js](../webpack.frontend.config.js) | `nxdev/notificationx/frontend/{index,crossSite,flashing-tab}.tsx` | Public-facing site |
| Gutenberg blocks | [webpack.blocks.config.js](../webpack.blocks.config.js) | [blocks/notificationx/index.jsx](../blocks/notificationx/index.jsx) | Block editor + inline blocks |

**The trap:** Display logic lives in *both* PHP Type classes and the frontend runtime. Changes to template tag handling, field schema, or popup structure must update both. The build system won't catch divergence — the popup will silently render wrong (missing fields, stale data).

When changing display:
1. Update the PHP Type class in `includes/Types/<Name>.php`.
2. Update the matching frontend renderer in `nxdev/notificationx/frontend/`.
3. Update the QuickBuilder field schema if field names changed (see [recipes/add-settings-field.md](recipes/add-settings-field.md)).

See [decisions/0003-dual-frontend-builds.md](decisions/0003-dual-frontend-builds.md) for why this split exists.

## Settings

Delegated to the `wpdeveloper/lib-settings` package:

```php
// includes/NotificationX.php:54
Settings::get_instance([
    'key'         => 'notificationx',
    'auto_commit' => true,
    'store'       => 'options',
]);
```

Stored as a single WP option `notificationx`. `auto_commit=true` means writes flush immediately; no need to call save.

Read: `Settings::get_instance()->get('settings.modules.modules_woocommerce')`.
Write: `Settings::get_instance()->set('settings.modules.modules_woocommerce', true)`.

## Third-party shims

| Shim | File | Purpose |
|---|---|---|
| WPML | [includes/ThirdParty/WPML.php](../includes/ThirdParty/WPML.php) | Register translatable strings ([wpml-config.xml](../wpml-config.xml)). |
| VisualPortfolio | [includes/ThirdParty/](../includes/ThirdParty/) | Compatibility fixes. |
| Freemius | [includes/Extensions/Freemius/](../includes/Extensions/Freemius/) | Extension only — not used for licensing. |

Details: [integrations/third-party.md](integrations/third-party.md).

## Build outputs and asset paths

- `nxbuild/` — webpack output. Referenced via `NOTIFICATIONX_DEV_ASSETS` constant. **Generated; do not edit.**
- `assets/` — committed static assets (icons, images, hand-authored CSS).
- `languages/notificationx.pot` — regenerated via `npm run pot` (excludes `nxbuild/`).

When registering script/style handles, point at `NOTIFICATIONX_DEV_ASSETS` for built JS/CSS, `NOTIFICATIONX_ASSETS` for static files.

## Extension contract surface

The free plugin exposes ~20 filters/actions to Pro and to third-party integrators. Catalog: [integrations/pro-hooks.md](integrations/pro-hooks.md).
