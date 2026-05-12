# 0002 — All core classes use the `GetInstance` singleton trait

**Date:** 2024-01-01 *(approximate — original decision predates this ADR)*
**Status:** Accepted

## Context

WordPress plugins frequently need a single shared instance of subsystems (settings, REST, frontend renderer, factories) accessible from anywhere. The alternatives are: pass instances around (verbose, doesn't fit WP's global-function hook style), use the global `$GLOBALS` array (ugly), or use a singleton pattern.

NotificationX has ~15 core subsystems plus ~50 Extension classes that all need to be addressable from hook callbacks scattered across the codebase.

## Decision

Define a [`GetInstance` trait](../../includes/GetInstance.php) that provides a `::get_instance()` static method backed by a lazy static `$instance` property. Apply it to every core class and every Extension. Forbid (by convention, not enforcement) instantiating these classes with `new`.

## Consequences

- ✅ Every subsystem is reachable from anywhere via `Foo::get_instance()` — no dependency injection plumbing.
- ✅ State (caches, settings, registries) is naturally shared without globals.
- ✅ Hook callbacks can be registered as `array( Foo::get_instance(), 'method' )` from any context.
- ❌ **The trait does not actively prevent `new`** — there's no `private __construct` or `final` modifier. The convention is enforced by code review, not the compiler. A misbehaving Extension that does `new WooCommerceSales()` will create a second instance and break the singleton invariant silently.
- ❌ Testing requires explicit handling: `WP_UnitTestCase` resets DB rows on `tearDown` but **not** PHP static properties. Tests that mutate singleton state must reset it manually, or test only via inputs/outputs.
- ❌ Hard to mock for unit testing — you can't substitute a fake instance without resetting the static. Most NotificationX tests are integration-style (real WP, real DB) for this reason.

**Don't:**
- Use `new` on any class that has the `GetInstance` trait. Always `::get_instance()`.
- Build new core subsystems without the trait — consistency matters more than purity at this point.

**Considered but rejected:**
- Dependency injection container (overkill for a WP plugin, friction with the hook system).
- Removing the trait in favor of stateless modules (would require rewriting every Extension).
