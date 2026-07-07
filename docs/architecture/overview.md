# Architecture Overview

The big picture of how NotificationX is assembled and how a notification flows from data source to on-screen popup. This is the entry point for the [architecture/](./) section.

## What NotificationX is

NotificationX is a WordPress plugin for FOMO / social proof marketing — recent-sales popups, review and comment notifications, email-subscription alerts, a sticky notification bar, popups, exit-intent offers, and more. It is built by WPDeveloper (text domain and slug `notificationx`, entry [../../notificationx.php](../../notificationx.php)).

This repository is the **free** plugin. Paid features live in a separate `notificationx-pro` plugin that loads alongside this one and hooks into the same Type/Extension system and WP filters (e.g. `nx_pro_alert_popup`). `NotificationX::is_pro()` ([../../includes/NotificationX.php](../../includes/NotificationX.php)) checks whether the Pro class exists, and the `GetInstance` trait transparently swaps in a `NotificationXPro\…` subclass when one is present. Do not add Pro-only logic to this repo.

## The Type ↔ Extension model

The core abstraction is that **a notification is a (Type, Extension) pair plus a theme.**

- **Types** ([../../includes/Types/](../../includes/Types/)) describe *what kind* of notification it is — the display category. Each extends `Types` ([../../includes/Types/Types.php](../../includes/Types/Types.php)) and declares a unique `$id`, its themes/templates, a `$module` settings key, and a `$default_source`. Free Types include `Conversions` (Sales), `Reviews`, `Comments`, `EmailSubscription`, `DownloadStats`, `NotificationBar`, `Popup`, `ExitIntent`, `FlashingTab`, `ContactForm`, `GDPR`, `PageAnalytics`, `CustomNotification`, and others. Registered via `TypesFactory` ([../../includes/Types/TypesFactory.php](../../includes/Types/TypesFactory.php)). See [../types/](../types/).
- **Extensions** ([../../includes/Extensions/](../../includes/Extensions/)) are the *data sources / integrations* that feed a Type — e.g. `WooCommerce`, `EDD`, `Freemius`, `MailChimp`, `ConvertKit`, `Envato`, `Zapier`, `CF7`, `FluentForm`. Each extends `Extension` ([../../includes/Extensions/Extension.php](../../includes/Extensions/Extension.php)) and declares which `$types` (Type IDs) and `$module` (settings key) it belongs to, plus a unique `$id` that becomes the stored `source`. Registered via `ExtensionFactory` ([../../includes/Extensions/ExtensionFactory.php](../../includes/Extensions/ExtensionFactory.php)). See [../extensions/](../extensions/).

Module activation is settings-driven: an Extension whose `$module` key is disabled in settings is not loaded, and nothing runs for it. The cross-extension form-field registry that the admin builder consumes lives in `GlobalFields` ([../../includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php)).

Adding a whole new Type end-to-end is documented in [../development/adding-a-notification-type.md](../development/adding-a-notification-type.md).

## End-to-end data flow

```
data source (WooCommerce order, review, form submit, cron fetch, Zapier webhook)
        ↓  Extension::get_data() / update_notification()
{prefix}nx_entries  +  {prefix}nx_posts   (custom tables, not a CPT)
        ↓
FrontEnd::get_notifications_ids()  → localized into window.notificationXArr
        ↓
REST  POST /notificationx/v1/notice  → FrontEnd::get_notifications_data()
        ↓
React runtime (nxdev/notificationx/frontend) → useNotificationX → NotificationContainer
        ↓
rendered popup / bar / exit-intent overlay
```

The admin builder writes notification config to the `nx_posts` table via `PostType::save_post()`; entry data (individual sales, reviews, submissions) lands in `nx_entries`; view/click analytics accrue in `nx_stats`. See [data-storage.md](data-storage.md).

## Subsystem map

Everything is wired up in the root singleton `NotificationX` ([../../includes/NotificationX.php](../../includes/NotificationX.php)). Key subsystems, each reachable via `::get_instance()`:

| Subsystem | Class | Role | Deep-dive |
| --- | --- | --- | --- |
| Settings | `Admin\Settings` (via `wpdeveloper/lib-settings`) | Options-backed settings store (`key=notificationx`). | [data-storage.md](data-storage.md) |
| Upgrader | `Core\Upgrader` | Runs DB create + migrations on version change. | [plugin-lifecycle.md](plugin-lifecycle.md) |
| Migration | `Core\Migration` | One-time migration of legacy CPT data into custom tables. | [data-storage.md](data-storage.md) |
| PostType | `Core\PostType` | Read/write of the `nx_posts` notification records + admin menu. | [data-storage.md](data-storage.md) |
| Database | `Core\Database` | Custom-table schema + query helpers. | [data-storage.md](data-storage.md) |
| REST | `Core\REST` + `Core\Rest\*` | All REST endpoints. | [../api/rest-endpoints.md](../api/rest-endpoints.md) |
| Admin | `Admin\Admin` | Admin pages, screens, notices. | [admin-spa.md](admin-spa.md) |
| FrontEnd | `FrontEnd\FrontEnd` + `FrontEnd\Preview` | Enqueues the popup runtime, serves notification data. | [frontend-runtime.md](frontend-runtime.md) |
| Cron | `Admin\Cron` | Scheduled data refreshes for pull-based sources. | [plugin-lifecycle.md](plugin-lifecycle.md) |
| QuickBuild | `Core\QuickBuild` | Server side of the QuickBuilder form engine. | [admin-spa.md](admin-spa.md) |
| TypeFactory / ExtensionFactory | `Types\TypesFactory` / `Extensions\ExtensionFactory` | Register and resolve Types and Extensions. | this doc |

Third-party shims (WPML, VisualPortfolio, Elementor) and `Blocks` (Gutenberg) are also instantiated from the root singleton.

## Where to go next
- [plugin-lifecycle.md](plugin-lifecycle.md) — bootstrap chain & singletons
- [folder-reference.md](folder-reference.md) — what lives where
- [data-storage.md](data-storage.md) — custom tables, analytics, migrations
- [admin-spa.md](admin-spa.md) — the React admin app
- [frontend-runtime.md](frontend-runtime.md) — popup/bar rendering

## Source
- [../../includes/NotificationX.php](../../includes/NotificationX.php) — the root singleton that wires everything
- [../../notificationx.php](../../notificationx.php) — entry point & constants
- [../../CLAUDE.md](../../CLAUDE.md) — architecture summary
