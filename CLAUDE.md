# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

NotificationX is a WordPress plugin (FOMO / social proof / sales popup / notification bar) by WPDeveloper. The plugin slug is `notificationx`, text domain `notificationx`, main entry `notificationx.php`. The free plugin lives here; the paid features live in a sibling plugin directory `notificationx-pro` and hook in via the same Extension/Type system.

PHP namespace root: `NotificationX\` → `includes/` (PSR-style classmap autoload via Composer; see [composer.json](composer.json)).

## Commands

All JS/asset builds use `@wordpress/scripts` (wp-scripts). Node version pinned in [.nvmrc](.nvmrc).

- `npm run start` — watch admin (`nxdev/`) **and** frontend bundles in parallel.
- `npm run admin-watch` / `npm run admin` — admin React app only ([webpack.config.js](webpack.config.js), entry [nxdev/index.tsx](nxdev/index.tsx)).
- `npm run frontend-watch` / `npm run frontend` — frontend popup/bar bundles ([webpack.frontend.config.js](webpack.frontend.config.js)).
- `npm run blocks` / `npm run bb` — Gutenberg blocks ([webpack.blocks.config.js](webpack.blocks.config.js), source in [blocks/](blocks/)).
- `npm run build` — admin + frontend production build (does NOT build blocks; use `npm run bb` separately, or `npm run release` which does both + POT).
- `npm run release` — `build` + `bb` + `pot`. `npm run zip` adds `wp dist-archive` for distribution.
- `npm run pot` — regenerate `languages/notificationx.pot` (excludes `nxbuild/`).
- `npm run up` — reinstall the `quickbuilder` dependency from the `notificationx` branch on GitHub. The `quickbuilder` package is sourced from `github:WPDevelopers/quickbuilder#notificationx` — do not bump it from npm.

PHP / tests:
- `composer install` — installs PHP libs (`lib-settings`, `query-builder`, `wp-notice`) from VCS repos declared in [composer.json](composer.json).
- `vendor/bin/phpunit` — runs the suite in [tests/](tests/) (config: [phpunit.xml.dist](phpunit.xml.dist), bootstrap: [tests/bootstrap.php](tests/bootstrap.php)). `tests/test-sample.php` is excluded.
- `vendor/bin/phpcs --standard=phpcs.xml` — coding standards ([phpcs.xml](phpcs.xml), stricter dist version: [.phpcs.xml.dist](.phpcs.xml.dist)).

Build outputs land in `nxbuild/` (referenced via the `NOTIFICATIONX_DEV_ASSETS` constant defined in [notificationx.php](notificationx.php)). `assets/` is committed source/static assets, not the build target.

## Architecture

### Bootstrap chain
[notificationx.php](notificationx.php) defines path constants and loads `vendor/autoload.php`, then instantiates `\NotificationX\NotificationX` ([includes/NotificationX.php](includes/NotificationX.php)). That singleton wires up everything else: `Settings`, `Upgrader`, `Admin`, `FrontEnd`, `REST`, `Cron`, `QuickBuild`, `TypeFactory`, `ExtensionFactory`, plus third-party shims (WPML, VisualPortfolio). Most subsystems use the `GetInstance` trait ([includes/GetInstance.php](includes/GetInstance.php)) — call `Foo::get_instance()` rather than `new Foo()`.

Settings storage is delegated to the `wpdeveloper/lib-settings` package; `key=notificationx`, store=`options`, `auto_commit=true`.

### The Type ↔ Extension model (the core abstraction)
A NotificationX notification is a (Type, Extension) pair:

- **Types** ([includes/Types/](includes/Types/)) describe *what kind of notification* it is — Sales (`Conversions`), `Reviews`, `Comments`, `EmailSubscription`, `NotificationBar`, `Popup`, `ExitIntent`, `FlashingTab`, etc. Registered via `TypesFactory`. Each type extends `Types` and declares its display templates and field schema.
- **Extensions** ([includes/Extensions/](includes/Extensions/)) are *data sources / integrations* for a Type — e.g. `WooCommerce`, `EDD`, `Freemius`, `MailChimp`, `Zapier`, `BitIntegrations`, `CF7`, `FluentForm`. Registered via `ExtensionFactory`. Each extends [Extensions/Extension.php](includes/Extensions/Extension.php) and declares which `$types` (Type IDs) and `$module` (settings key) it belongs to.

When adding a new integration: pick the matching Type, create a class under `includes/Extensions/<Name>/<Name><Type>.php` extending `Extension`, set `$types`, `$module`, `$id`, implement `init_extension()` (UI/popup config) and `get_data()` (data fetching). Module activation is gated by the settings key in `$module` — disabled modules are not loaded. See existing extensions like [includes/Extensions/WooCommerce/](includes/Extensions/WooCommerce/) for the canonical pattern; [docs/new-notification-type.md](docs/new-notification-type.md) walks through adding a brand-new Type.

`GlobalFields` ([includes/Extensions/GlobalFields.php](includes/Extensions/GlobalFields.php)) holds the cross-extension form field registry that the QuickBuilder UI consumes.

### Storage
Notifications are a custom post type (`nx_bar` / handled by [includes/Core/PostType.php](includes/Core/PostType.php)) plus a custom analytics/entries table ([includes/Core/Database.php](includes/Core/Database.php), [includes/Admin/Entries.php](includes/Admin/Entries.php)). [includes/Core/Migration.php](includes/Core/Migration.php) and [includes/Core/Upgrader.php](includes/Core/Upgrader.php) handle schema/version transitions — bump version in [notificationx.php](notificationx.php) (`NOTIFICATIONX_VERSION`) and `package.json` together when shipping.

### REST + Admin UI
REST endpoints register through [includes/Core/REST.php](includes/Core/REST.php) and `includes/Core/Rest/`. The admin SPA is React + TypeScript in [nxdev/notificationx/](nxdev/notificationx/) (entry [nxdev/index.tsx](nxdev/index.tsx)) using `react-router` v5, the `quickbuilder` form engine from WPDevelopers, and `@wordpress/components`. Admin views consumed by PHP live in [includes/Admin/views/](includes/Admin/views/).

### Frontend
[includes/FrontEnd/FrontEnd.php](includes/FrontEnd/FrontEnd.php) renders/enqueues the popup runtime; the React-driven popup/bar/exit-intent runtime is built from [nxdev/notificationx/frontend/](nxdev/notificationx/frontend/) via `webpack.frontend.config.js`. [Preview.php](includes/FrontEnd/Preview.php) powers the in-builder preview.

### Frontend templating quirk
There are two frontend builds in this repo (admin + frontend webpack configs) and three runtime contexts (admin builder, frontend popup runtime, Gutenberg blocks). Changes to popup display logic frequently need updates in both `nxdev/notificationx/frontend/` and the corresponding PHP Type class — design field changes only update one without the other will silently desync.

## Conventions worth knowing

- Singletons via the `GetInstance` trait — never instantiate core classes directly.
- Module/extension activation is settings-driven (`modules_*` keys). A module turned off in settings will not register, and nothing will run for it.
- Pro features live in the separate `notificationx-pro` plugin and integrate via the same Extension system + WP filters/actions exposed from the free plugin (e.g. `nx_pro_alert_popup`). Don't add Pro-only logic into this repo.
- The constant `NOTIFICATIONX_DEV_ASSETS` points at `nxbuild/` — when wiring up new bundles, register handles against this path, not `assets/`.
- Distribution exclusions live in [.distignore](.distignore); `.gitattributes` controls `git archive`. Update both if you add top-level dev-only files.
- WPML strings are declared in [wpml-config.xml](wpml-config.xml).

## Reference docs in-repo
- [docs/new-notification-type.md](docs/new-notification-type.md) — adding a new Type end-to-end.
- [docs/exit-intent-popup.md](docs/exit-intent-popup.md), [docs/exit-intent-add-new-design.md](docs/exit-intent-add-new-design.md) — Exit Intent specifics.
- [@todo.md](@todo.md) — author's running TODO; not authoritative roadmap.
