---
name: nx-extension-author
description: Use when adding or modifying a NotificationX Extension (a data source / integration for an existing notification Type — e.g. a new WooCommerce/EDD/CRM integration, a new "source" in the builder dropdown). Triggers on phrases like "add a new source", "new integration", "add an extension to NotificationX", "register a notification source", or when editing files under includes/Extensions/.
---

# Authoring a NotificationX Extension

You're about to add or modify an **Extension** — a data source that feeds an existing notification **Type** (Sales, Reviews, NotificationBar, etc.).

> **Adding a brand-new Type** (a new display kind) is different work — load `nx-type-author` instead.
> **Adding a theme/design** to an existing Type is different work — see `docs/recipes/add-frontend-design.md`.

## Read these first (in order)

1. **`docs/recipes/add-extension.md`** — the canonical step-by-step. Follow it.
2. **`CLAUDE.md` § Anti-patterns** — the seven ways Extensions silently break.
3. **`includes/Extensions/WooCommerce/WooCommerceSales.php`** — the gold-standard reference Extension. Copy its structure, not its specifics.

## Invariants you MUST respect

These come from the codebase, not opinion. Violating any of them produces a silently-broken Extension.

1. **`$id` is unique across all Extensions.** Empty or duplicated `$id` causes `ExtensionFactory::$extensions[$id]` to silently overwrite another Extension.
2. **`$types` matches a registered Type ID.** Cross-check against `includes/Types/TypesFactory.php` (`$types` array, lines 23–42). An invalid Type ID makes `get_type()` return `false` and the Extension registers but does nothing.
3. **`$module = 'modules_<slug>'` follows the convention.** This is the settings key that gates registration. See `includes/Core/Modules.php:66`.
4. **The class is registered in `ExtensionFactory::$extension_classes`** (`includes/Extensions/ExtensionFactory.php` lines 31–87). Forgetting this is the #1 reason an Extension never appears in the source dropdown.
5. **Use `GetInstance` trait, never `new`.** See ADR 0002.
6. **`get_data()` returns the shape the Type expects.** For `conversions`, that's an array with `tag_name`, `tag_product_title`, `tag_time`, etc. Cross-check against the Type class's template fields.

## Quick mental check before you start

- [ ] Which Type does this Extension feed? (e.g. `conversions`, `reviews`, `contact_form`)
- [ ] Is there already an Extension for this data source? Check `ExtensionFactory::$extension_classes`.
- [ ] Is the data event-driven (hook into source plugin) or polled (cron)? Different patterns — see recipe § 6.
- [ ] Is this free or Pro? Pro doesn't go in this repo — see `docs/integrations/pro-hooks.md`.

## Common pitfalls (in order of frequency)

1. **Forgot to register in `ExtensionFactory`** → source missing from dropdown.
2. **Wrong `$types` value** → silently inert.
3. **Changed display logic in PHP without updating `nxdev/notificationx/frontend/`** → silent desync. See `docs/architecture.md § dual frontend runtimes`.
4. **Reusing another Extension's `$id`** → registry collision.
5. **Forgot `npm run pot`** after adding translatable strings.

## When you finish

- Test with the module enabled in **NotificationX → Settings → Modules**.
- Create a notification using this Extension; verify entries land in `wp_nx_entries` with `source = '<your $id>'`.
- Visit a frontend page; verify the popup renders with real data.
- Run `vendor/bin/phpcs --standard=phpcs.xml` on your file.

If you scaffolded with `/nx-scaffold-extension`, most of the skeleton is already correct — focus on `get_data()` and the source-plugin hook wiring.
