# Folder Reference

What lives where in the repository.

## Top-level layout

| Path | Role |
| --- | --- |
| `includes/` | All PHP; namespace root `NotificationX\` (Composer classmap autoload). |
| `includes/Types/` | Notification **Types** (`Conversions`, `Reviews`, `Comments`, `NotificationBar`, `Popup`, `ExitIntent`, `FlashingTab`, …) + `TypesFactory.php`, base `Types.php`. |
| `includes/Extensions/` | Data-source **Extensions** (`WooCommerce/`, `EDD/`, `MailChimp/`, `PressBar/`, `ExitIntent/`, …), base `Extension.php`, `ExtensionFactory.php`, `GlobalFields.php`. |
| `includes/Core/` | REST (`REST.php` + `Rest/`), `PostType.php`, `Database.php`, `Migration.php`, `Upgrader.php`, `Analytics.php`, `Helper.php`, `QuickBuild.php`, and other engine classes. |
| `includes/Admin/` | Admin PHP — `Admin.php`, `Settings.php`, `Cron.php`, `Entries.php`, dashboard/reports/scanner, and `views/`. |
| `includes/FrontEnd/` | Public popup runtime enqueue (`FrontEnd.php`) + in-builder `Preview.php`. |
| `includes/Features/` | Discrete feature modules. |
| `includes/ThirdParty/` | Integrations/shims for other plugins (WPML, VisualPortfolio, …). |
| `nxdev/` | React/TypeScript source — admin SPA (`index.tsx`, `notificationx/`) and public runtime (`notificationx/frontend/`). |
| `blocks/` | Gutenberg blocks source (`Blocks.php`, `notificationx/`, `countdown/`, `controls/`, `style-handler/`). |
| `assets/` | Committed static/source assets **and** the production build output (`admin/`, `public/`, `common/`, `images/`). |
| `nxbuild/` | Dev build output (`NOTIFICATIONX_DEV_ASSETS`), used when `NX_DEBUG` is on. |
| `languages/` | `.pot` / translation files. |
| `vendor/` | Composer dependencies + generated autoloader (do not hand-edit — wiped on `composer install`). |
| `tests/` | PHPUnit suite (`bootstrap.php`, config `phpunit.xml.dist`). |
| `docs/` | This documentation. |

Top-level config files: [../../notificationx.php](../../notificationx.php) (entry), [../../composer.json](../../composer.json), [../../package.json](../../package.json), the four webpack configs (`webpack.config.js`, `webpack.frontend.config.js`, `webpack.blocks.config.js`, `webpack.countdown.config.js`), `phpcs.xml`, `uninstall.php`, `wpml-config.xml`, and `.distignore`/`.gitattributes` (distribution/archive exclusions).

## Autoloading

Autoloading is a **Composer classmap**, declared in [../../composer.json](../../composer.json):

```json
"autoload": {
  "exclude-from-classmap": ["blocks/controls"],
  "classmap": ["includes", "blocks"]
}
```

The whole `includes/` and `blocks/` trees are scanned and mapped in `vendor/composer/autoload_classmap.php` (+ `autoload_static.php`). Because it is a classmap, **new classes are not discovered automatically** — after adding a class you must run `composer dump-autoload` (or, when Composer is unavailable, add the entry manually to both files, as [../development/adding-a-notification-type.md](../development/adding-a-notification-type.md) describes). The namespace root `NotificationX\` maps to `includes/`.

Vendor libraries are pulled from WPDeveloper VCS repos (not packagist): `wpdeveloper/lib-settings` (the settings store), `wpdeveloper/query-builder` (the fluent DB query builder used by `Database::query()`), and `priyomukul/wp-notice` (admin notices). See [plugin-lifecycle.md](plugin-lifecycle.md) for how they are wired in.

## Build outputs vs source

`assets/` is dual-purpose: it holds committed static assets (images, common CSS) **and** is the target for production JS/CSS builds. The dev builds instead write to `nxbuild/`. `Helper::file()` picks between them: it uses `NOTIFICATIONX_DEV_ASSETS` (`nxbuild/`) when `NX_DEBUG` is defined and the requested file exists there, otherwise `NOTIFICATIONX_ASSETS` (`assets/`). When registering a new bundle, register handles through `Helper::file()` / the `NOTIFICATIONX_DEV_ASSETS` constant rather than hardcoding `assets/`.

There are three runtime contexts, each with its own webpack config and output:

| Context | Source | Config | Output |
| --- | --- | --- | --- |
| Admin SPA | `nxdev/index.tsx` | `webpack.config.js` | `admin/js/admin.js`, `admin/css/admin.css` |
| Public runtime | `nxdev/notificationx/frontend/` | `webpack.frontend.config.js` | `public/js/frontend.js`, `public/css/frontend.css` |
| Gutenberg blocks | `blocks/` | `webpack.blocks.config.js` | block bundles |

See [frontend-runtime.md](frontend-runtime.md) and [admin-spa.md](admin-spa.md) for how each is enqueued and the desync trap between them.

## Source
- [../../composer.json](../../composer.json)
- [../../CLAUDE.md](../../CLAUDE.md)
