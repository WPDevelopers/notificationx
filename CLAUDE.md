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
Notifications are a custom post type slug `notificationx` ([includes/Core/PostType.php](includes/Core/PostType.php)) plus **three** custom tables defined in [includes/Core/Database.php](includes/Core/Database.php): `{$prefix}nx_posts` (notification records), `{$prefix}nx_entries` (source-fetched data rows), and `{$prefix}nx_stats` (per-day views/clicks). [includes/Core/Migration.php](includes/Core/Migration.php) and [includes/Core/Upgrader.php](includes/Core/Upgrader.php) handle schema/version transitions — bump version in [notificationx.php](notificationx.php) (`NOTIFICATIONX_VERSION`) and `package.json` together when shipping.

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

## Glossary

- **Type** — display kind of a notification (Sales/Conversions, Reviews, Comments, NotificationBar, Popup, ExitIntent, FlashingTab, etc.). Code lives in [includes/Types/](includes/Types/). Registered via [TypesFactory](includes/Types/TypesFactory.php).
- **Extension** — data source / integration *for a Type* (WooCommerce, EDD, FluentForm, Zapier, BitIntegrations, etc.). Code lives in [includes/Extensions/](includes/Extensions/). Registered via [ExtensionFactory](includes/Extensions/ExtensionFactory.php).
- **Source** — user-facing term for what the code calls *Extension*. Don't be confused: UI "Source" = code `Extension`.
- **Conversions** — legacy code name for the **Sales** Type. UI says "Sales", code says `Conversions` / `conversions`.
- **Module** — a settings-driven toggle (`settings.modules['modules_<name>']`, checked in [includes/Core/Modules.php](includes/Core/Modules.php)) that gates whether an Extension is registered at all. Defaults to enabled when the key is absent.
- **QuickBuilder** — the in-house React form engine pinned to `github:WPDevelopers/quickbuilder#notificationx`. Bump via `npm run up`, never from npm.
- **`nxbuild/`** — webpack build output; referenced by `NOTIFICATIONX_DEV_ASSETS`. **Generated — do not edit.**
- **`assets/`** — committed static assets (icons, images, hand-authored CSS). Not a build target.
- **Post type slug** — `notificationx` (see [includes/Core/PostType.php](includes/Core/PostType.php)). Custom tables are `nx_posts`, `nx_entries`, `nx_stats`.
- **Free vs Pro** — free plugin lives in this directory; Pro lives in a sibling `notificationx-pro` plugin and hooks in via the same Extension system + the filters `nx_extension_classes` / `nx_types_classes` and per-source `nx_*_{$source}` filters.

## Anti-patterns (don't do these)

- ❌ `new Foo()` on classes that use the `GetInstance` trait — always `Foo::get_instance()`. The trait does not enforce this; the codebase assumes you respect it.
- ❌ Editing files under [nxbuild/](nxbuild/) — they're generated; your changes get blown away on next build.
- ❌ Bumping `quickbuilder` from npm — it's pinned to a GitHub branch. Use `npm run up`.
- ❌ Adding a new **Type** without registering it in both [TypesFactory](includes/Types/TypesFactory.php) **and** the frontend Type registry — the dual-build desync silently breaks display.
- ❌ Adding Pro-only logic into this repo. Pro lives in `notificationx-pro` and hooks in via filters. If you need a new hook for Pro, **expose** one here, don't **implement** Pro behavior here.
- ❌ Bumping `NOTIFICATIONX_VERSION` in [notificationx.php](notificationx.php) without also bumping `package.json` `version` (and the `Version:` header). Asset cache busting and the `nxbuild/` URL both rely on this matching.
- ❌ Leaving Extension `$id` empty or duplicated across two Extension classes — they share `ExtensionFactory::$extensions[$id]` and the second one silently overwrites the first.
- ❌ Setting `$types` on an Extension to a Type ID that doesn't exist — `get_type()` returns `false` and the Extension silently registers without working. Verify the Type ID against [TypesFactory::$types](includes/Types/TypesFactory.php).
- ❌ Changing popup display logic in PHP without updating `nxdev/notificationx/frontend/` (and vice versa) — both runtimes render the same Type, divergence is silent.

## Reference docs in-repo

- **Start here:** [docs/README.md](docs/README.md) — index of all docs.
- [docs/architecture.md](docs/architecture.md) — bootstrap, Type↔Extension model, storage, runtimes.
- [docs/recipes/add-extension.md](docs/recipes/add-extension.md) — add a data source for an existing Type (most common task).
- [docs/recipes/add-type.md](docs/recipes/add-type.md) — add a brand-new Type end-to-end.
- [docs/rest-api.md](docs/rest-api.md) — REST namespace, routes, auth.
- [docs/integrations/pro-hooks.md](docs/integrations/pro-hooks.md) — filter/action surface exposed to `notificationx-pro`.
- [docs/testing.md](docs/testing.md), [docs/release.md](docs/release.md), [docs/debugging.md](docs/debugging.md).
- [docs/decisions/](docs/decisions/) — ADRs explaining the *why* behind core choices.
- [docs/TASKS.md](docs/TASKS.md) — documentation backlog/tracker.
- [@todo.md](@todo.md) — author's running TODO; not authoritative roadmap.
