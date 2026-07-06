# Folder Reference

> **Status:** stub — outline only.

What lives where in the repository.

## Top-level layout
_TODO — table of top-level dirs and their role:_

| Path | Role |
| --- | --- |
| `includes/` | All PHP; namespace root `NotificationX\` (PSR classmap autoload). |
| `includes/Types/` | Notification **Types**. |
| `includes/Extensions/` | Data-source **Extensions**. |
| `includes/Core/` | REST, PostType, Database, Migration, Upgrader, Cron. |
| `includes/Admin/` | Admin PHP + `views/`. |
| `includes/FrontEnd/` | Popup runtime enqueue + Preview. |
| `nxdev/` | React/TypeScript source (admin SPA + frontend runtime). |
| `blocks/` | Gutenberg blocks source. |
| `assets/` | Committed static/source assets. |
| `nxbuild/` | Build output (`NOTIFICATIONX_DEV_ASSETS`); not `assets/`. |
| `docs/` | This documentation. |

## Autoloading
_TODO — Composer classmap, namespace→path mapping, the vendor libs (`lib-settings`, `query-builder`, `wp-notice`)._

## Build outputs vs source
_TODO — why `nxbuild/` is the enqueue target, not `assets/`; the three runtime contexts._

## Source
- [../../composer.json](../../composer.json)
- [../../CLAUDE.md](../../CLAUDE.md)
