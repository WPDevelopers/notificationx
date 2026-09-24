# MCP Server & the WordPress Abilities API

NotificationX ships an MCP (Model Context Protocol) server so AI assistants can read and manage notifications, entries, analytics and settings. Everything it exposes is modelled as an **ability**: one class per operation, registered in a single registry and then surfaced over two surfaces — NotificationX's own MCP transport, and (when the WordPress version provides it) core's Abilities API.

> **Status:** covers ability registration and the Abilities API bridge. The transport, OAuth pairing and settings panel are not documented yet — read [Manager.php](../../../includes/MCP/Manager.php), [Server.php](../../../includes/MCP/Server.php) and [OAuth.php](../../../includes/MCP/OAuth.php).

## Boot order

| Step | Where |
| --- | --- |
| The engine calls the MCP bootstrap | [NotificationX.php:113](../../../includes/NotificationX.php#L113) |
| PHP capability gate (PHP ≥ 7.0, `random_bytes`, `hash_equals`), filterable via `nx_mcp_is_supported` | [Bootstrap.php:35](../../../includes/MCP/Bootstrap.php#L35) |
| `Manager::init()` boots the registry, then the REST routes and settings tab | [Manager.php:38](../../../includes/MCP/Manager.php#L38) |
| `Registrar::boot()` instantiates the abilities and registers the core hooks | [Registrar.php:58](../../../includes/Abilities/Registrar.php#L58) |

Abilities are registered whenever the module boots, regardless of the `enable_mcp` setting — each one is permission-checked individually (administrator-only) and the transport is gated separately.

## Adding an ability

1. Add a class under `includes/Abilities/Read/` or `includes/Abilities/Manage/` extending [AbilityBase](../../../includes/Abilities/AbilityBase.php) with an `$id` of the form `notificationx/<tool-name>`, a label, a description, JSON-Schema input/output and a `run()`.
2. Add it to the array in `Registrar::boot()` — or, from Pro / a third-party plugin, append an instance through the `nx_register_abilities` filter ([Registrar.php:93](../../../includes/Abilities/Registrar.php#L93)).

Nothing else is needed: the registry keys abilities by id, exposes them as MCP tools through [Tools.php](../../../includes/MCP/Tools.php), and mirrors them into core.

## The Abilities API bridge

WordPress 6.9 added the Abilities API (`wp_register_ability()`). When those functions exist, the registry mirrors every ability into core so the same tools are available to any Abilities-API consumer with no further work.

Two rules govern that mirror, and breaking either one makes every NotificationX ability disappear from `wp_get_abilities()` and the `wp-abilities/v1` REST index:

- **An ability's category must be registered first.** Core validates `category` on registration and rejects the ability — with `_doing_it_wrong()`, one notice per ability — when the category is unknown: *"Ability category "notificationx" is not registered."* NotificationX registers its `notificationx` category in `register_ability_category()` ([Registrar.php:165](../../../includes/Abilities/Registrar.php#L165)).
- **Each registration has exactly one valid action.** Categories may only be registered on `wp_abilities_api_categories_init`, abilities only on `wp_abilities_api_init`. Core fires the categories action first (instantiating the category registry before the ability registry), so hooking both in `boot()` is enough — see [Registrar.php:104-105](../../../includes/Abilities/Registrar.php#L104).

Both hooks are added together, guarded by `function_exists( 'wp_register_ability' )`, so older WordPress versions are unaffected. If the module ever boots *after* `wp_abilities_api_categories_init` has fired, the category can no longer be registered; `register_with_wp_abilities()` then bails instead of emitting a notice per ability ([Registrar.php:191](../../../includes/Abilities/Registrar.php#L191)). NotificationX's own registry — and therefore the MCP transport — keeps working in that case.

The category id lives in one place, `Registrar::CATEGORY` ([Registrar.php:34](../../../includes/Abilities/Registrar.php#L34)); use that constant rather than the literal string.

## Headless creates and theme defaults

The admin builder fills several settings from the selected theme through the `nx_themes_trigger` system. A headless create (MCP, REST, WP-CLI) never fires those UI triggers, so [CreateNotification](../../../includes/Abilities/Manage/CreateNotification.php) backfills them from the same trigger data through [BuilderInfo](../../../includes/Abilities/BuilderInfo.php):

| Setting | Helper | Without it |
| --- | --- | --- |
| `notification-template` | `BuilderInfo::default_template_for_theme()` | Data-driven types save but render blank. |
| `inline_location` | `BuilderInfo::default_inline_location_for_theme()` | Inline (Growth Alert) notifications save but render nowhere. |

A value the caller supplies always wins, but its JSON type is coerced on save and on read. For example, `custom_ids: [12, 34]` is stored as `"12,34"`, and a string `notification-template` becomes `[]`. See [architecture/data-storage.md](../../architecture/data-storage.md#field-types-in-data).

## Verifying

```bash
# Every ability present, under our category, and no _doing_it_wrong() notices:
wp eval 'echo count( wp_get_abilities( array( "category" => "notificationx" ) ) ), "\n";'
```

With `WP_DEBUG` on, a broken category shows up as a notice from `WP_Abilities_Registry::register` on any request that touches the Abilities API — including unrelated ones, such as a Templately template import.

Automated coverage: [tests/test-abilities-category.php](../../../tests/test-abilities-category.php) (`--group abilities`), which skips itself on WordPress versions without the Abilities API.
