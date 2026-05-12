# Pro Plugin Integration Hooks

The contract surface between the free **NotificationX** plugin and the **notificationx-pro** plugin. If you're working in `notificationx-pro` and want to extend behavior, this is the menu of hooks already exposed. If you're working in free and need to expose new behavior to Pro, **add a filter/action here and document it in this file in the same PR.**

> **Stability promise:** Hooks documented in this file are intended to be stable. Hooks **not** in this file should be treated as internal and may change without notice.

## Mental model

Pro never patches free code. It hooks in via three mechanisms:

1. **Filter `nx_extension_classes`** — register a new Extension class, or *replace* a free one with a Pro-extended subclass.
2. **Filter `nx_types_classes`** — register a new Type class, or replace a free one.
3. **Per-source filters `nx_<event>_{$source_id}`** — modify behavior of a specific Extension at well-defined points (save, preview, fallback data, image, link).

Everything else is a tactical filter/action for a narrower concern (themes, settings tabs, builder schema, cron intervals, frontend data transform).

## The registry filters

| Filter | Args | File:line | Purpose |
|---|---|---|---|
| `nx_extension_classes` | `array $classes` (id → FQCN) | [ExtensionFactory.php:98](../../includes/Extensions/ExtensionFactory.php) | Add or replace Extensions. Pro merges its own map in here. |
| `nx_types_classes` | `array $classes` (id → FQCN) | [TypesFactory.php:51](../../includes/Types/TypesFactory.php) | Add or replace Types. |
| `nx_themes` | `array $themes` | [Extension.php:141](../../includes/Extensions/Extension.php) | Register new visual themes/designs available to a Type. |
| `nx_sources` | `array $sources` | [Extension.php:143](../../includes/Extensions/Extension.php) | Modify the source listing surfaced to the admin UI. |

### Example: Pro replacing a free Extension

```php
add_filter( 'nx_extension_classes', function( $classes ) {
    $classes['woocommerce_sales'] = \NotificationXPro\Extensions\WooCommerce\WooCommerceSalesPro::class;
    return $classes;
}, 20 );
```

The Pro class extends the free `WooCommerceSales` and overrides whatever methods it needs.

## Per-source extension points

These filters fire **per Extension `$id`** and let Pro (or any code) tap into specific lifecycle moments without subclassing.

| Filter / Action | Fired in | Purpose |
|---|---|---|
| `nx_save_post_{$source}` | [PostType.php:107](../../includes/Core/PostType.php) | Mutate notification data right before save. |
| `nx_saved_post_{$source}` | [PostType.php:118](../../includes/Core/PostType.php) | Side-effect hook after a notification is saved (e.g. schedule cron). |
| `nx_can_entry_{$source}` | [Extension.php:654](../../includes/Extensions/Extension.php) | Validate / accept-or-reject an inbound entry before storage. |
| `nx_preview_entry_{$source}` | [Extension.php:113](../../includes/Extensions/Extension.php) | Customize the builder-preview entry. |
| `nx_preview_settings_{$source}` | [Extension.php:116](../../includes/Extensions/Extension.php) | Customize the builder-preview settings payload. |
| `nx_fallback_data_{$source}` | [Extension.php:175](../../includes/Extensions/Extension.php) | Provide fallback data when no real entries exist. |
| `nx_notification_image_{$source}` | `includes/FrontEnd/FrontEnd.php` | Override the image rendered for this source. |
| `nx_notification_link_{$source}` | `includes/FrontEnd/FrontEnd.php` | Override the click-through link. |
| `nx_filtered_data_{$source}` | `includes/FrontEnd/FrontEnd.php` | Transform frontend payload (tag replacement, formatting). |
| `nx_filtered_entry_{$source}` | [FrontEnd/Preview.php](../../includes/FrontEnd/Preview.php) | Transform a single entry in the preview path. |

The `{$source}` placeholder is the Extension's `$id` — e.g. `nx_filtered_data_woocommerce_sales`.

## Cross-cutting hooks

| Hook | File:line | Purpose |
|---|---|---|
| `nx_pro_alert_popup` | [NotificationX.php:72](../../includes/NotificationX.php) | Gate where Pro renders its upsell/alert UI. |
| `nx_cron_schedules` | `includes/Admin/Cron.php` | Register custom WP-Cron intervals. |
| `nx_settings_page_settings` | `includes/Admin/Settings.php` | Merge/modify settings defaults. |
| `nx_settings_tab` | `includes/Admin/Settings.php` | Add a settings tab. |
| `nx_builder_configs` | [PostType.php:224](../../includes/Core/PostType.php) | Inject field schema into the notification builder. |
| `nx_quick_builder_tabs` | `includes/Core/QuickBuild.php` | Add tabs to the QuickBuilder UI. |

## Pro's responsibilities (do these, don't break these)

- **Always merge, never replace wholesale.** When filtering `nx_extension_classes`, modify the array — don't return a fresh map that drops free Extensions.
- **Respect singletons.** Pro subclasses of singleton-trait classes should use `get_instance()` consistently. See [decisions/0002-singleton-getinstance.md](../decisions/0002-singleton-getinstance.md).
- **Don't duplicate hook names.** If you need a new hook, add it to free *first* (with this file updated) and consume it from Pro.
- **Match the Type's data shape.** When a Pro Extension feeds a free Type, the data shape must match the Type's expected tag keys — don't invent new tags without also adding them via `nx_filtered_data_{$source}`.

## When to add a new hook (free → Pro contract)

Add a new filter/action in free when, **and only when**:

1. Pro needs to alter free behavior at a point where no existing hook fires.
2. The need is recurring (not a one-off — those should be solved by subclassing).
3. You can describe the hook's contract in one sentence (when it fires, args, expected return shape).

Naming convention: `nx_<area>_<event>` for global, `nx_<event>_{$source}` for per-source. **Update this file in the same PR.**

## Anti-patterns

- ❌ Pro adding `add_action('plugins_loaded', ...)` to monkey-patch free internals via `Closure::bind` or reflection. Use the registry filters instead.
- ❌ Pro hard-depending on a hook that isn't listed in this file — it may disappear.
- ❌ Free adding a hook *because* Pro asked for it, without writing down the contract here. Untracked hooks become permanent accidental API.

## How to enumerate hooks yourself

If you suspect this file is out of date:

```sh
grep -rn "apply_filters\(\s*['\"]nx_" includes/
grep -rn "do_action\(\s*['\"]nx_" includes/
```

If you find a hook not listed here, either (a) it's intentionally internal — leave it, or (b) it should be promoted to stable — add it to this file with a one-sentence purpose.
