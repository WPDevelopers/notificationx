# NotificationX — AGENTS.md

Agent guidance for working on the NotificationX WordPress plugin (v3.2.6).

---

## Project Overview

NotificationX is a WordPress plugin for social-proof notifications (sales popups, review alerts, subscriber counts, notification bars, etc.). It integrates with 35+ sources (WooCommerce, EDD, Mailchimp, etc.) and supports 22+ display types.

- **Plugin entry point:** `notificationx.php`
- **PHP namespace root:** `NotificationX\`
- **Admin UI:** React 17 + TypeScript (source in `nxdev/`)
- **REST API namespace:** `notificationx/v1`
- **License:** GPL-3.0+

---

## Repository Layout

```
notificationx/
├── notificationx.php          # Plugin entry, constants, autoloader bootstrap
├── includes/                  # All PHP backend code (141 files)
│   ├── Admin/                 # Admin pages, settings, cron, reports, scanner
│   ├── Core/                  # Database, REST, analytics, migration, rules
│   │   └── Rest/              # REST endpoint controllers
│   ├── Types/                 # 22 notification type implementations
│   ├── Extensions/            # 35+ source/integration implementations
│   ├── FrontEnd/              # Frontend rendering and preview
│   ├── Blocks/                # Gutenberg block registration
│   └── ThirdParty/            # WPML, VisualPortfolio support
├── nxdev/                     # TypeScript/React admin source
│   └── notificationx/
│       ├── admin/             # Admin page components
│       ├── components/        # Shared UI components
│       ├── fields/            # Form field type components
│       ├── frontend/          # Frontend JS logic
│       ├── icons/             # SVG icon components
│       ├── hooks/             # Custom React hooks
│       └── scss/              # Styling source
├── assets/                    # Compiled/production assets (do not edit directly)
├── nxbuild/                   # Dev build output
├── blocks/                    # Gutenberg block registration PHP
├── languages/                 # Translation files (.pot, .po, .mo)
├── tests/                     # PHPUnit bootstrap
├── vendor/                    # Composer dependencies (do not edit)
├── webpack.config.js           # Admin bundle
├── webpack.frontend.config.js  # Frontend bundle
├── webpack.blocks.config.js    # Blocks bundle
├── package.json
└── composer.json
```

---

## Key Constants (defined in `notificationx.php`)

| Constant | Value |
|---|---|
| `NOTIFICATIONX_VERSION` | `3.2.6` |
| `NOTIFICATIONX_PATH` | Absolute path to plugin root |
| `NOTIFICATIONX_URL` | Plugin URL |
| `NOTIFICATIONX_ASSETS` | URL to `/assets` |
| `NOTIFICATIONX_INCLUDES` | Path to `/includes` |

---

## PHP Architecture

### Singleton Pattern
All major classes use the `GetInstance` trait. Access them via `ClassName::get_instance()`, never instantiate directly with `new`.

### Class Responsibilities

| Namespace | Class | Role |
|---|---|---|
| `NotificationX` | `NotificationX` | Plugin bootstrap, registers all singletons |
| `NotificationX\Admin` | `Admin` | Admin menu, assets, page routing |
| `NotificationX\Admin` | `Settings` | Options management |
| `NotificationX\Admin` | `Cron` | Scheduled background jobs (`nx_cron` hook) |
| `NotificationX\Admin` | `Entries` | Entry CRUD |
| `NotificationX\Admin` | `Reports` | Analytics reports |
| `NotificationX\Core` | `Database` | Custom DB tables, QueryBuilder wrapper |
| `NotificationX\Core` | `REST` | Registers all REST routes |
| `NotificationX\Core` | `Migration` | Version upgrade transforms |
| `NotificationX\Core` | `Rules` | Conditional display logic engine |
| `NotificationX\Core` | `Analytics` | Click/impression tracking |
| `NotificationX\Types` | `TypeFactory` | Resolves the correct `Types` subclass |
| `NotificationX\Extensions` | `ExtensionFactory` | Resolves the correct `Extension` subclass |
| `NotificationX\FrontEnd` | `FrontEnd` | Enqueues frontend assets, renders notifications |

### Database Tables

| Table | Purpose |
|---|---|
| `wp_nx_entries` | Raw notification data pulled from sources |
| `wp_nx_posts` | Notification post/campaign configuration |
| `wp_nx_stats` | Click and impression statistics |

DB version is tracked in `Database::$version` (`2.1`). Schema changes must go through `Migration.php` and `Upgrader.php`.

### Adding a New Notification Type
1. Create `includes/Types/YourType.php` extending `NotificationX\Types\Types`.
2. Register it inside `TypeFactory`.
3. Add any required frontend template handling.

### Adding a New Extension/Source
1. Create `includes/Extensions/YourExtension.php` extending `NotificationX\Extensions\Extension`.
2. Register it inside `ExtensionFactory`.
3. Implement `get_notification_ready_data()` to feed entries into `wp_nx_entries`.

---

## REST API

**Base:** `notificationx/v1`

| Controller | File | Auth |
|---|---|---|
| `Posts` | `Core/Rest/Posts.php` | Required |
| `Integration` | `Core/Rest/Integration.php` | Required |
| `Entries` | `Core/Rest/Entries.php` | Required |
| `Analytics` | `Core/Rest/Analytics.php` | Public |
| `BulkAction` | `Core/Rest/BulkAction.php` | Required |
| `Popup` | `Core/Rest/Popup.php` | Public |

Public endpoints (no nonce/auth): `/notice`, `/analytics`, `/delete-cookies`, `/send-rating`.

---

## Frontend (React/TypeScript)

Source lives in `nxdev/`. **Never edit files in `assets/` or `nxbuild/` directly** — they are build artifacts.

### Build Commands

```bash
# Development (watch mode)
npm run start                  # admin + frontend watchers in parallel
npm run admin-watch            # admin only
npm run frontend-watch         # frontend only

# Production build
npm run build                  # admin + frontend
npm run bb                     # Gutenberg blocks
npm run release                # full: build + blocks + POT file

# Distribution
npm run zip                    # release + wp dist-archive (creates installable ZIP)

# i18n
npm run pot                    # regenerate notificationx.pot
```

### Key Admin Entry Points

| Path | Purpose |
|---|---|
| `nxdev/notificationx/admin/` | Page-level admin views |
| `nxdev/notificationx/components/` | Shared UI components |
| `nxdev/notificationx/fields/` | Settings field types |
| `nxdev/notificationx/frontend/` | Frontend-side JS logic |
| `nxdev/notificationx/hooks/` | Custom React hooks |

---

## PHP Backend Commands

```bash
# Install PHP dependencies
composer install

# Autoload dump after adding a new class
composer dump-autoload
```

---

## Testing

### PHP (PHPUnit)
- Config: `phpunit.xml.dist`
- Bootstrap: `tests/bootstrap.php` (loads WP test environment)
- Run tests: `phpunit` (requires WordPress test suite to be installed)
- Current coverage: minimal — bootstrap + sample fixture only.

### JavaScript
No Jest config is currently set up. JS testing is not active.

---

## Important WordPress Hooks

| Hook | Location | Purpose |
|---|---|---|
| `nx_cron` | `Admin\Cron` | Background data refresh |
| `nx_type_trigger` | `Types\Types` | Fires when a notification type triggers |
| `nx_builder_configs` | Admin | Modifies Quick Builder config |
| `nx_preview_entry_{type}` | FrontEnd | Returns preview entry for a type |
| `nx_rest_miscellaneous` | `Core\REST` | Adds misc REST actions |
| `jwt_auth_whitelist` | `Core\REST` | Whitelists public REST routes |
| `wp_consent_api_registered_notificationx` | ThirdParty | GDPR consent API |

---

## Coding Conventions

- **Autoloading:** PSR-4 via Composer. Namespace `NotificationX\` maps to `includes/`. Any new class must follow this mapping.
- **Singletons:** Use the `GetInstance` trait. Do not use `new ClassName()` for plugin classes.
- **DB queries:** Use `Database::get_instance()` and the bundled QueryBuilder. Do not write raw `$wpdb` queries unless absolutely necessary.
- **REST responses:** Return `WP_REST_Response` or `WP_Error`. Follow existing controller patterns in `Core/Rest/`.
- **Settings:** Use `Admin\Settings::get_instance()` to read/write options — never call `get_option('notificationx_...')` directly from arbitrary code.
- **Assets:** Register and enqueue in the appropriate `Admin` or `FrontEnd` class hooks. Do not use `wp_enqueue_*` outside of those classes.
- **PHP version:** Minimum PHP 7.4.
- **WordPress version:** Minimum WP 5.0.

---

## Release Checklist

1. Bump `NOTIFICATIONX_VERSION` in `notificationx.php`.
2. Update `Database::$version` if schema changed and add migration in `Migration.php`.
3. Run `npm run release` to build all assets and regenerate `.pot`.
4. Run `phpunit` — all tests must pass.
5. Run `npm run zip` to produce the distributable archive.
6. Tag the release in version control.
