# WooCommerce Sales Notification Type (`woocommerce_sales`)

> Shows a live feed of recent WooCommerce purchases (and, via sibling extensions,
> WooCommerce product reviews / an inline "growth alert" bar) to build FOMO / social
> proof on a WooCommerce store.

## At a glance

| | |
|---|---|
| **Type ID** | `woocommerce_sales` |
| **Class** | [`includes/Types/WooCommerceSales.php`](../../includes/Types/WooCommerceSales.php), extends [`Types`](../../includes/Types/Types.php) |
| **Traits** | [`includes/Types/Traits/Reviews.php`](../../includes/Types/Traits/Reviews.php), [`includes/Types/Traits/Conversions.php`](../../includes/Types/Traits/Conversions.php) |
| **Priority** | `5` |
| **Default source** | `woocommerce_sales` (`$default_source`) |
| **Default theme** | Not set on the Type class — the line is commented out (`// public $default_theme = 'woocommerce_theme-one';`). The *Extension* class `WooCommerceSales` (see below) sets `$default_theme = 'woocommerce_sales_theme-one'`. |
| **Link type** | `product_page` (`$link_type`) |
| **Module gate (`$module`)** | `modules_woocommerce`, `modules_woocommerce_sales_reviews`, `modules_woocommerce_sales_inline` |
| **Compatible extensions** | `WooCommerceSales`, `WooCommerceSalesInline`, `WooCommerceSalesReviews` — the only extensions whose `$types` equals `'woocommerce_sales'` (verified via `grep` + [`TypesFactory`](../../includes/Types/TypesFactory.php#L25), which maps type id `woocommerce_sales` → `NotificationX\Types\WooCommerceSales`) |

Registered in [`TypesFactory.php`](../../includes/Types/TypesFactory.php#L25):
```php
'woocommerce_sales'  => 'NotificationX\Types\WooCommerceSales',
```

> **Naming note:** this is a distinct Type from `conversions` (`includes/Types/Conversions.php`), which is the sibling "Sales Notification" family for EDD/Freemius/SureCart/FluentCart/Envato/Zapier/etc. sources. The two classes keep **separate but parallel** `$themes` / `$res_themes` / `$templates` registries that are manually kept in lockstep — see [Adding a New Design (Theme) to the Sales Notification](../features/sales-notification/add-new-design.md) for the full mechanics and the naming conventions that tie them together.

## What it does

`WooCommerceSales` is the Type (the "what kind of notification") for WooCommerce order/review data. Three Extensions feed it data, gated by three separate module settings but all declaring `$types = 'woocommerce_sales'`:

- **`WooCommerceSales`** (`includes/Extensions/WooCommerce/WooCommerceSales.php`, id `woocommerce_sales`, module `modules_woocommerce`) — the main "someone just purchased X" feed, sourced from WooCommerce orders.
- **`WooCommerceSalesReviews`** (`includes/Extensions/WooCommerce/WooCommerceSalesReviews.php`, id `woocommerce_sales_reviews`, module `modules_woocommerce`) — product reviews.
- **`WooCommerceSalesInline`** (`includes/Extensions/WooCommerce/WooCommerceSalesInline.php`, id `woocommerce_sales_inline`, module `modules_woocommerce`, Pro) — an inline "growth alert" variant rendered via shortcode/embed rather than the floating popup (see the `'inline'`/`show_on === 'only_shortcode'` branch in `FrontEnd.php` below). It declares its own `$themes`/`$templates` (it does not extend its parent's), so designs added to `WooInline` must be added here too — including the Google Analytics **`live-viewers`** design, gated on `modules_google_analytics`, whose count is resolved at render time from realtime GA4 data rather than from orders (`nx_can_entry` returns `false`, `nx_filtered_notice` injects a placeholder). See [extensions/woocommerce.md](../extensions/woocommerce.md#the-live-viewers-design-google-analytics).

All three extensions call `parent::__construct()` from `WooCommerce` / `WooInline` / `WooReviews` (`includes/Extensions/WooCommerce/WooCommerce.php`, `WooInline.php`, `WOOReviews.php`), which is where the actual WooCommerce order/review fetching (`get_orders()`, `ordered_product()`, `status_transition()`, etc.) lives — that's Extension-layer logic, out of scope for this Type doc.

Product filtering (which products are eligible to appear) is implemented **on the Type class itself**, not the Extension:

- `_excludes_product( $product, $settings )` — honors `product_exclude_by` (`none` / `product_category` / `manual_selection`) to drop excluded categories/products.
- `_show_purchaseof( $product, $settings )` — honors `product_control` (`none` / `product_category` / `manual_selection`) to restrict to an allow-list.
- `nx_can_entry( $return, $entry, $settings )` — combines both checks for a single entry. Marked `@todo remove in the future` in the source. It is wired up by the **Extension**, not this Type: `WooCommerce::__construct()` does `add_filter("nx_can_entry_{$this->id}", array($this->get_type(), 'nx_can_entry'), 10, 3)` (`includes/Extensions/WooCommerce/WooCommerce.php:111`), where `get_type()` resolves back to this `WooCommerceSales` Type instance.
- `show_exclude_product( $data, $settings )` — the same per-product filtering applied to an array of products. **Verified:** it has no caller in the free plugin, but it *is* consumed from `notificationx-pro`, where the Pro WooCommerce/WooCommerceSales/EDD extensions wire it up via `add_filter("nx_filtered_data_{$this->id}", array($this->get_type(), 'show_exclude_product'), 11, 2)`.

The `Conversions` trait ([`includes/Types/Traits/Conversions.php`](../../includes/Types/Traits/Conversions.php)) declares near-duplicate `excludes_product()` / `show_purchaseof()` methods (operating over an array), but nothing in `WooCommerceSales.php` calls the trait versions — the class's own `_excludes_product` / `_show_purchaseof` (single-entry) are what's actually wired up via `nx_can_entry`. **Verified:** the trait's `excludes_product` / `show_purchaseof` have no call sites in either the free or the Pro plugin — legacy/BC dead code.

The `Reviews` trait ([`includes/Types/Traits/Reviews.php`](../../includes/Types/Traits/Reviews.php)) supplies:
- `review_templates()` — adds a `review_fourth_param` field (default `"About"`) to the notification template, hooked to `nx_notification_template` in `init_fields()`.
- `content_trim_length_dependency()` — registers `woocommerce_sales_reviews_review-comment`, `-2`, `-3` theme ids (plus the non-WooCommerce review theme ids) as themes that use the content-trim-length setting.
- `conversion_data()` — trims/quotes review content (`plugin_review`) and falls back `title` to `post_title`. Hooked in `init()` as `add_filter("nx_filtered_entry_{$this->id}", array($this, 'conversion_data'), 11, 2)`, i.e. `nx_filtered_entry_woocommerce_sales`, so it runs for every entry of this Type (not just reviews) — it's a no-op when `$saved_data['content']` / `plugin_review` aren't set.

## Data flow

`WooCommerceSales|WooCommerceSalesReviews|WooCommerceSalesInline::get_data()` (Extension layer, not in this file) → stored entries → `FrontEnd.php` assembles the list, calling:
- `apply_filters("nx_filtered_entry_{$type}", $entry, $settings)` → hits `WooCommerceSales::conversion_data()` (from the `Reviews` trait) since `$type` = `woocommerce_sales`.
- `apply_filters("nx_can_entry_{$extension_id}", ...)` (per-extension, e.g. `nx_can_entry_woocommerce_sales`) → hits `WooCommerceSales::nx_can_entry()` for product include/exclude rules.

Type-specific `FrontEnd.php` touch-points (`includes/FrontEnd/FrontEnd.php`):
- Line 353 — `'woocommerce_sales_inline' == $settings['source']` is treated like an `inline`-type notification (shortcode-only rendering; skipped from the floating/global queue).
- Line 835 — `woocommerce_sales_inline` (alongside `woo_inline`, `edd_inline`, `fluentcart_inline`, etc.) is excluded from the "display last" popup behavior.

From there it's REST → the shared React runtime (`nxdev/notificationx/frontend/`) — this Type has **no bespoke React component**; see [Themes / templates](#themes--templates) below, it reuses the generic Sales renderer.

## Fields & settings schema

- `init_fields()` adds two filters (beyond whatever `Types::init_fields()` / the generic builder wires up):
  - `nx_notification_template` → `review_templates()` (adds `review_fourth_param`).
  - `nx_content_trim_length_dependency` → `content_trim_length_dependency()`.
- Product scoping settings consumed by `_excludes_product()` / `_show_purchaseof()`: `product_exclude_by`, `exclude_categories`, `exclude_products`, `product_control`, `category_list`, `product_list`. Their field definitions live in `GlobalFields.php` (`product_control` / `product_exclude_by`, around lines 793–920), scoped via `Rules::includes('source', [...])` to a long list of sources including `woocommerce_sales`, `woocommerce_sales_reviews`, `woocommerce_sales_inline`.
- Common per-theme template placeholders (`$common_fields` in `init()`): `first_param` (`tag_name`), `second_param` (static text, e.g. "just purchased" / "Bought"), `third_param` (`tag_product_title`), `fourth_param` (`tag_time`).
- `$templates` declares two named field-templates, each scoping which theme ids get which placeholder options (`first_param`/`third_param`/`fourth_param` field choices) via the `_themes` key:
  - `woo_template_new` — used by `theme-one`…`theme-five`, `conv-theme-ten/eleven/twelve/thirteen/fifteen`. Includes a `fourth_param` choice of `tag_time` ("Definite Time").
  - `woo_template_sales_count` — used by the "sales count" themes `conv-theme-six/seven/eight/nine/fourteen/sixteen`. No `fourth_param` choice (commented out — no time field for these card-style themes).

## Themes / templates

`$themes` (free + Pro) and `$res_themes` (all Pro, responsive) are declared in `init()`. Full theme list, `image_shape`, and Pro gating are visible directly in [`WooCommerceSales.php`](../../includes/Types/WooCommerceSales.php#L72-L264) — don't duplicate the whole array here, it changes as themes are added. Notable structural points confirmed in source:

- Theme keys mix an old naming scheme (`theme-one`…`theme-five`) with a newer numbered scheme (`conv-theme-six`…`conv-theme-sixteen`), and a comment in the source states the declaration order is deliberately kept in sync with `includes/Types/Conversions.php`.
- `conv-theme-twelve`, `-thirteen`, `-fifteen` are annotated in source as Figma-sourced "theme-one/two/four" redesigns with a green "Verified by NotificationX" badge and a `link_button` default of `"Buy now"`.
- `conv-theme-fourteen` / `-sixteen` are "sales-count card" themes (aggregate purchase count + "Purchase now" button), Pro-gated, mapped to the `woo_template_sales_count` template.
- All `res-theme-*` responsive themes are Pro and map to one of `woo_template_new`, `maps_template_new`, or `woo_template_sales_count` via their `_template` key (not `$this->templates` — a different, string-only field).

For **how a theme actually renders** (container classes, the generic 3-row/2-row content renderer, split layouts, SCSS conventions) and the step-by-step process for **adding a new theme**, see the dedicated guide: **[Adding a New Design (Theme) to the Sales Notification](../features/sales-notification/add-new-design.md)**. That doc also covers the `conversions_*` vs `woocommerce_sales_*` fully-qualified theme-name lockstep in detail — this doc does not repeat it.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/WooCommerceSales.php`](../../includes/Types/WooCommerceSales.php) |
| Traits | [`includes/Types/Traits/Reviews.php`](../../includes/Types/Traits/Reviews.php), [`includes/Types/Traits/Conversions.php`](../../includes/Types/Traits/Conversions.php) |
| Base class | [`includes/Types/Types.php`](../../includes/Types/Types.php) |
| Sibling Type (non-WooCommerce sources) | [`includes/Types/Conversions.php`](../../includes/Types/Conversions.php) |
| Extensions (data sources) | [`includes/Extensions/WooCommerce/WooCommerceSales.php`](../../includes/Extensions/WooCommerce/WooCommerceSales.php), [`WooCommerceSalesReviews.php`](../../includes/Extensions/WooCommerce/WooCommerceSalesReviews.php), [`WooCommerceSalesInline.php`](../../includes/Extensions/WooCommerce/WooCommerceSalesInline.php), base [`WooCommerce.php`](../../includes/Extensions/WooCommerce/WooCommerce.php) / [`WooInline.php`](../../includes/Extensions/WooCommerce/WooInline.php) / [`WOOReviews.php`](../../includes/Extensions/WooCommerce/WOOReviews.php) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php#L25), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php#L60-L62) |
| Shared field registry | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| PHP frontend routing | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (lines ~353, ~835) |
| Frontend runtime (theme rendering) | `nxdev/notificationx/frontend/` — see [Adding a New Design](../features/sales-notification/add-new-design.md) for exact files |

## Dependencies

**WooCommerce** (the `woocommerce/woocommerce` plugin) must be installed and active. Confirmed by `WooCommerceSales` (Extension)::`doc()`: _"Make sure that you have WooCommerce installed & activated to use this campaign."_ `$class = '\WooCommerce'` is declared on all three Extensions as the dependency-check class.

## Testing notes & gotchas

- Changing `$themes` / `$res_themes` / `$templates` here **must** be mirrored in `includes/Types/Conversions.php` if the theme should also be available to non-WooCommerce sources — the two registries are hand-kept in lockstep (see the "naming note" above and the linked design-add guide).
- `nx_can_entry()` on this Type is only invoked because the `WooCommerceSales` **Extension** wires it up via `get_type()` — if you're tracing "why doesn't my product exclusion work," check both files.
- Three separate `modules_*` settings gate this one Type (`modules_woocommerce`, `modules_woocommerce_sales_reviews`, `modules_woocommerce_sales_inline`) — turning off one module disables only that source family (e.g. reviews), not the whole Type.
- `woocommerce_sales_inline` is treated specially in `FrontEnd.php` as an inline/shortcode-only notification — don't assume all three sources render through the same floating-popup path.
- No PHPUnit tests specific to this Type exist. **Verified:** the free `tests/` suite covers only the factories, migration/upgrader, and REST (`test-types-factory.php`, `test-extension-factory.php`, `test-migration-upgrader.php`, `test-rest.php`); the Pro `tests/` suite is likewise generic (`test-pro-types.php`, `test-pro-engine.php`, `test-pro-extension-factory.php`, `test-smoke.php`) — none exercise this Type in isolation.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Adding a New Design (Theme) to the Sales Notification](../features/sales-notification/add-new-design.md)
