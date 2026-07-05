# Sales Notification Type (`conversions`)

> Shows a live feed / count of recent purchases (WooCommerce, EDD, Envato, Freemius,
> etc.) as small "toast" popups to build social proof ("FOMO") on a site. Internally
> this is called the **Conversions** type; in the admin UI it is labelled
> **"Sales Notification"**.

## At a glance

| | |
|---|---|
| **Type ID** | `conversions` |
| **Class** | [`includes/Types/Conversions.php`](../../includes/Types/Conversions.php) |
| **Trait** | [`includes/Types/Traits/Conversions.php`](../../includes/Types/Traits/Conversions.php) |
| **Priority** | `5` |
| **Default source** | `woocommerce` (`$default_source`) |
| **Default theme** | `conversions_theme-one` (`$default_theme`); default responsive theme `conversions_res-theme-one` |
| **Link type** | `product_page` (`$link_type`) |
| **Module gate (`$module`)** | Declares `['modules_edd', 'modules_custom_notification', 'modules_zapier', 'modules_bitintegration', 'modules_freemius', 'modules_envato']` on the Type object. **Verified:** `TypeFactory::register_types()` ([includes/Types/TypesFactory.php](../../includes/Types/TypesFactory.php)) does not read this property at all — it is not what gates loading. What actually gates each *extension* is the extension's own `$module` string checked in `ExtensionFactory::register_extensions()` via `Modules::get_instance()->is_enabled($obj->module)` ([includes/Extensions/ExtensionFactory.php](../../includes/Extensions/ExtensionFactory.php)). See "Compatible extensions" below for the real per-extension module keys. _TODO: verify_ what (if anything) reads `Types::$module` elsewhere (e.g. admin JS/REST serialization). |
| **Compatible extensions** | Extensions whose `$types === 'conversions'` — see table below. |

Note a naming split documented in [../sales-notification-add-new-design.md](../sales-notification-add-new-design.md): the **WooCommerce** source is actually handled by a *separate* Type class, [`WooCommerceSales`](../../includes/Types/WooCommerceSales.php) (type id `woocommerce_sales`), which duplicates its own `$themes` / `$res_themes` / `$templates` in lockstep with `Conversions.php`. The `WooCommerce` *extension* class itself (`includes/Extensions/WooCommerce/WooCommerce.php`) still declares `$types = 'conversions'`, so it is registered under this Conversions type too — its `nx_can_entry` filter is wired to `Conversions::get_type()->nx_can_entry` (see below). `_TODO: verify_` the exact runtime relationship between the `WooCommerce` extension (type `conversions`) and the `WooCommerceSales` type — both appear live in source.

## What it does

`Conversions::init()` ([includes/Types/Conversions.php:59](../../includes/Types/Conversions.php#L59)) sets the admin title to "Sales Notification" and registers a large theme catalogue (see below) built from a shared field-set:

```php
$common_fields = [
    'first_param'  => 'tag_name',        // buyer name tag
    'second_param' => 'just purchased',  // action verb (static text)
    'third_param'  => 'tag_product_title',
    'fourth_param' => 'tag_time',
];
```

Each theme entry can override `template` (e.g. `conv-theme-thirteen` overrides `second_param` to `"Bought"`), set `image_shape` (`square`/`circle`/`rounded`), mark itself `is_pro`, and set `defaults` such as `link_button` + `link_button_text` (e.g. "Buy now" / "Purchase now"). A small subset of Pro themes (`conv-theme-six/seven/eight/nine/fourteen/sixteen`) are "sales-count" cards — their content template is assigned by the Pro `Conversions` type override, not this free class (see `woo_template_sales_count` below).

Two type-level filtering hooks (product exclude/include lists) let a notification exclude or restrict which purchased products can generate an entry:
- `_excludes_product( $product, $settings )` — honours `$settings['product_exclude_by']` (`none` / `product_category` / `manual_selection`) against `$settings['exclude_categories']` / `$settings['exclude_products']`.
- `_show_purchaseof( $product, $settings )` — honours `$settings['product_control']` (`none` / `product_category` / `manual_selection`) against `$settings['category_list']` / `$settings['product_list']`.

Both are combined in `nx_can_entry( $return, $entry, $settings )` ([Conversions.php:346](../../includes/Types/Conversions.php#L346)), which the `WooCommerce` extension hooks in via `add_filter("nx_can_entry_{$this->id}", array($this->get_type(), 'nx_can_entry'), 10, 3)` ([includes/Extensions/WooCommerce/WooCommerce.php:111](../../includes/Extensions/WooCommerce/WooCommerce.php#L111)), and the `ReviewX` extension hooks the same method directly off `Conversions::get_instance()` ([includes/Extensions/ReviewX/ReviewX.php:201](../../includes/Extensions/ReviewX/ReviewX.php#L201)).

There is also a second, near-duplicate implementation of the same exclude/include logic in the `Conversions` trait (`excludes_product`, `show_purchaseof` in [Traits/Conversions.php](../../includes/Types/Traits/Conversions.php)) with slightly different `array_diff` argument order than the class's own `_excludes_product`/`_show_purchaseof`. `_TODO: verify_` whether the trait methods are still called anywhere live (no call sites were found in this pass) — they may be legacy, kept for BC.

## Data flow

Trace for a WooCommerce-sourced entry:

1. `WooCommerce` extension (`includes/Extensions/WooCommerce/WooCommerce.php`, `$types = 'conversions'`, `$module = 'modules_woocommerce'`) fetches order data in `get_data()`, filtered through `nx_can_entry_woocommerce` → `Conversions::nx_can_entry()` (product exclude/include) and `check_order_status`.
2. Entries flow through `FrontEnd.php` ([includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php)) which builds `$this->notificationXArr` for the frontend runtime; `apply_filters('nx_filtered_entry' | 'nx_filtered_data' | 'nx_filtered_notice', ...)` are the generic (non-type-specific) filter points a Type/Extension can hook.
3. The notification link is built from `$post['link_type']` at [FrontEnd.php:738](../../includes/FrontEnd/FrontEnd.php#L738) — Conversions declares `link_type = 'product_page'`, so unless it's `'none'` a link is attached. `_TODO: verify_` exactly where/how `link_type` is copied from the Type onto the stored `$post`/settings (no direct assignment site was found in this pass).
4. React runtime renders rows generically — Sales themes share one renderer; see the deep-dive doc below for the exact `GetTemplate.ts` / `Notification.tsx` mechanics.

## Fields & settings schema

Distinctive settings this type relies on (consumed by `_excludes_product` / `_show_purchaseof` / `nx_can_entry`, not declared as literal field arrays in this file — the builder fields themselves come from `GlobalFields` and Extension-level `init_extension()`):
- `product_exclude_by` — `none` | `product_category` | `manual_selection`
- `exclude_categories`, `exclude_products` — used when the above is set
- `product_control` — `none` | `product_category` | `manual_selection`
- `category_list`, `product_list` — used when the above is set
- `source` — extension id; `_excludes_product`/`_show_purchaseof` special-case `edd`/`edd_inline` to look up `download_category` terms instead of `product_cat`.

`init_fields()` itself just calls `parent::init_fields()` (no type-specific field registration beyond what `Types::init_fields()`/hook consumers add).

## Themes / templates

`$this->themes` (free class) registers, among others: `theme-one` … `theme-five` (free/base — `theme-four`/`theme-five` pro), and the numbered `conv-theme-six` … `conv-theme-sixteen` family (all pro except where noted). Two content templates group them:
- **`woo_template_new`** — the standard 3-row layout (name/action/product/time) used by `theme-one` … `theme-five`, `conv-theme-ten/eleven/twelve/thirteen/fifteen` (and their `woocommerce_sales_*` counterparts).
- **`woo_template_sales_count`** — the "N buyers purchased" sales-count card layout used by `conv-theme-six/seven/eight/nine/fourteen/sixteen` (and `woocommerce_sales_*` counterparts); no `fourth_param`/time field.

`$this->res_themes` registers 11 responsive themes (`res-theme-one` … `res-theme-eleven`), all `is_pro => true`, each tagged with a `_template` of `woo_template_new`, `maps_template_new`, or `woo_template_sales_count`.

`$conversions_count` (public property) lists the fully-qualified theme names (`conversions_conv-theme-seven`, `woocommerce_sales_conv-theme-seven`, etc.) that belong to the sales-count family — consumed by `CustomNotification::get_themes_for_type('conversions_count')` ([includes/Extensions/CustomNotification/CustomNotification.php](../../includes/Extensions/CustomNotification/CustomNotification.php)) to separate sales-count themes from regular ones when building the Custom Notification theme picker.

For the full mechanics of how a theme's content rows are rendered (React) and the naming conventions for adding a new theme, see the dedicated doc:
**[docs/sales-notification-add-new-design.md](../sales-notification-add-new-design.md)**.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [includes/Types/Conversions.php](../../includes/Types/Conversions.php) |
| Trait | [includes/Types/Traits/Conversions.php](../../includes/Types/Traits/Conversions.php) |
| Sibling type (WooCommerce source) | [includes/Types/WooCommerceSales.php](../../includes/Types/WooCommerceSales.php) |
| Base class | [includes/Types/Types.php](../../includes/Types/Types.php) |
| Extensions | `includes/Extensions/{WooCommerce,SureCart,EDD,Envato,Freemius,BitIntegrations,Zapier,CustomNotification,FluentCart}/...` — see table below |
| PHP frontend routing | [includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php) |
| Frontend runtime (rendering mechanics) | see [docs/sales-notification-add-new-design.md](../sales-notification-add-new-design.md) §1/§3 for the exact `nxdev/notificationx/frontend/...` files |

### Compatible extensions (`$types === 'conversions'`)

Verified via `grep -rl "'conversions'" includes/Extensions`:

| Extension class | `$id` | `$module` |
|---|---|---|
| [WooCommerce](../../includes/Extensions/WooCommerce/WooCommerce.php) | `woocommerce` | `modules_woocommerce` |
| [SureCart](../../includes/Extensions/SureCart/SureCart.php) | `surecart` | `modules_surecart` |
| [ZapierConversions](../../includes/Extensions/Zapier/ZapierConversions.php) | `zapier_conversions` | `modules_zapier` |
| [EDD](../../includes/Extensions/EDD/EDD.php) | `edd` | `modules_edd` |
| [BitIntegrationsConversions](../../includes/Extensions/BitIntegrations/BitIntegrationsConversions.php) | `bitintegrations_conversions` | `modules_bitintegrations` |
| [FreemiusConversions](../../includes/Extensions/Freemius/FreemiusConversions.php) | `freemius_conversions` | `modules_freemius` |
| [Envato](../../includes/Extensions/Envato/Envato.php) | `envato` | `modules_envato` |
| [CustomNotificationConversions](../../includes/Extensions/CustomNotification/CustomNotificationConversions.php) | `custom_notification_conversions` | `modules_custom_notification` |
| [FluentCart](../../includes/Extensions/FluentCart/FluentCart.php) | `fluentcart` | `modules_fluentcart` |

Note the extension module key is `modules_bitintegrations` (plural) while `Conversions::$module` lists `modules_bitintegration` (singular) — a discrepancy in the (apparently unused-for-gating) `Types::$module` list; do not assume it is authoritative. `includes/Extensions/CustomNotification/CustomNotification.php` (`$id = 'custom_notification'`) also references `'conversions'` in its source but declares `$types = 'custom'`, so it is **not** registered under this type — it merely borrows this type's theme catalogue (`get_themes_for_type('conversions')`) for its own "Custom Notification" (`custom`) type UI.

## Dependencies

Depends on whichever data-source extension is active: WooCommerce, Easy Digital Downloads (EDD), SureCart, FluentCart, Envato, Freemius, Zapier (as a relay), or BitIntegrations. `_TODO: verify_` whether the free plugin ships all of the above always-available or whether some are Pro-only gated elsewhere (several theme entries are `is_pro => true`, but extension availability itself is controlled by the `modules_*` settings key, not proven Pro-only in this pass).

## Testing notes & gotchas

- Adding/editing a theme here almost always requires a mirrored edit in [`WooCommerceSales.php`](../../includes/Types/WooCommerceSales.php) — see [../sales-notification-add-new-design.md](../sales-notification-add-new-design.md) for the full checklist (PHP registry + `GetTemplate.ts` + SCSS + preview image).
- The `Types::$module` array on this class does not appear to gate anything by itself (see "At a glance" above) — don't rely on editing it to enable/disable the type; the real gate is each extension's own `$module` key plus the `modules_*` settings toggle.
- `_excludes_product`/`_show_purchaseof` special-case EDD (`source == 'edd' || 'edd_inline'`) to read `download_category` terms instead of `product_cat` — a new source extension with its own custom taxonomy would need the same special-casing or a filter added.
- The trait's `excludes_product`/`show_purchaseof` duplicate similar logic to the class's `_excludes_product`/`_show_purchaseof` with different `array_diff` argument ordering; no call site was found for the trait methods in this pass — `_TODO: verify_` before assuming they're dead code.
- `conv-theme-fourteen`/`conv-theme-sixteen` (sales-count cards) depend on a Pro-side sales-count aggregation feature per an in-source code comment ("Pro: the count is aggregated by SalesFeatures... in the Pro Conversions type") — `_TODO: verify_` against the `notificationx-pro` repo, not present here.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [Adding a New Design (Theme) to the Sales Notification](../sales-notification-add-new-design.md) — the authoritative deep-dive for this type's theme system
- [docs/types/_TEMPLATE.md](_TEMPLATE.md) — template this doc was generated from
