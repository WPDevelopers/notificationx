# Architecture Overview

> **Status:** stub — outline only. Fill in following [../development/documentation-conventions.md](../development/documentation-conventions.md).

The big picture of how NotificationX is assembled and how a notification flows from data source to on-screen popup. This is the entry point for the [architecture/](./) section.

## What NotificationX is
_TODO — one-paragraph product framing (FOMO / social proof / sales popup / notification bar); free plugin here, Pro in the sibling `notificationx-pro`._

## The Type ↔ Extension model
_TODO — the core abstraction: a notification = (Type, Extension) + theme. Types = display categories ([../types/](../types/)); Extensions = data sources ([../extensions/](../extensions/)). Registered via `TypesFactory` / `ExtensionFactory`._

## End-to-end data flow
_TODO — data source → Extension `get_data()` → entries table → REST → admin builder / frontend runtime → rendered popup._

## Subsystem map
_TODO — Settings, Upgrader, Admin, FrontEnd, REST, Cron, QuickBuild, TypeFactory, ExtensionFactory. Link each to its deep-dive doc below._

## Where to go next
- [plugin-lifecycle.md](plugin-lifecycle.md) — bootstrap chain & singletons
- [folder-reference.md](folder-reference.md) — what lives where
- [data-storage.md](data-storage.md) — CPT, analytics table, migrations
- [admin-spa.md](admin-spa.md) — the React admin app
- [frontend-runtime.md](frontend-runtime.md) — popup/bar rendering

## Source
- [../../includes/NotificationX.php](../../includes/NotificationX.php) — the root singleton that wires everything
- [../../notificationx.php](../../notificationx.php) — entry point & constants
- [../../CLAUDE.md](../../CLAUDE.md) — architecture summary
