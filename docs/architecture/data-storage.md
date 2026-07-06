# Data & Storage

> **Status:** stub — outline only.

Where NotificationX keeps its data.

## Custom post type (`nx_bar`)
_TODO — the notification CPT handled by [../../includes/Core/PostType.php](../../includes/Core/PostType.php); post meta shape, statuses._

## Analytics / entries table
_TODO — custom table via [../../includes/Core/Database.php](../../includes/Core/Database.php); read/write path through [../../includes/Admin/Entries.php](../../includes/Admin/Entries.php)._

## Settings storage
_TODO — delegated to `wpdeveloper/lib-settings`; `key=notificationx`, store=`options`, `auto_commit=true`. Module activation is settings-driven (`modules_*` keys)._

## Migrations & upgrades
_TODO — [../../includes/Core/Migration.php](../../includes/Core/Migration.php) + [../../includes/Core/Upgrader.php](../../includes/Core/Upgrader.php); triggered on `NOTIFICATIONX_VERSION` bump. See [plugin-lifecycle.md](plugin-lifecycle.md)._

## Source
- [../../includes/Core/PostType.php](../../includes/Core/PostType.php)
- [../../includes/Core/Database.php](../../includes/Core/Database.php)
- [../../includes/Core/Migration.php](../../includes/Core/Migration.php)
- [../../includes/Core/Upgrader.php](../../includes/Core/Upgrader.php)
