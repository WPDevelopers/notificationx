# Plugin Lifecycle

> **Status:** stub — outline only.

How NotificationX boots, activates, and shuts down.

## Bootstrap chain
_TODO — [../../notificationx.php](../../notificationx.php) defines path constants, loads `vendor/autoload.php`, instantiates `\NotificationX\NotificationX` ([../../includes/NotificationX.php](../../includes/NotificationX.php)), which wires up Settings, Upgrader, Admin, FrontEnd, REST, Cron, QuickBuild, TypeFactory, ExtensionFactory + third-party shims (WPML, VisualPortfolio)._

## Singletons: the GetInstance trait
_TODO — most subsystems use [../../includes/GetInstance.php](../../includes/GetInstance.php); call `Foo::get_instance()`, never `new Foo()`._

## Activation / deactivation / uninstall
_TODO — hooks, default option seeding, cron scheduling, cleanup._

## Version & upgrade path
_TODO — `NOTIFICATIONX_VERSION` in [../../notificationx.php](../../notificationx.php) must move with `package.json`; Upgrader/Migration run on version bump (see [data-storage.md](data-storage.md))._

## Source
- [../../includes/NotificationX.php](../../includes/NotificationX.php)
- [../../includes/GetInstance.php](../../includes/GetInstance.php)
- [../../includes/Admin/Cron.php](../../includes/Admin/Cron.php)
