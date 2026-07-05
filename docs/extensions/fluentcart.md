# FluentCart Extension (`modules_fluentcart`)

> Connects NotificationX to [FluentCart](https://wordpress.org/plugins/fluent-cart/),
> surfacing recent paid/processing orders as Sales (`conversions`) popup
> notifications, plus a Pro-only inline "N people purchased" / stock-count widget
> on FluentCart product pages.

## At a glance

| | |
|---|---|
| **Integration** | FluentCart |
| **Directory** | [`includes/Extensions/FluentCart/`](../../includes/Extensions/FluentCart/) |
| **Module key(s) (`$module`)** | `modules_fluentcart` (both classes share this module key) |
| **Feeds Types** | `conversions` (`FluentCart`), `inline` (`FluentCartInline`) |
| **Extension classes** | `FluentCart.php` (class `FluentCart`) → id `fluentcart`, type `conversions`; `FluentCartInline.php` (class `FluentCartInline`, extends `FluentCart`) → id `fluentcart_inline`, type `inline` |
| **Depends on** | FluentCart plugin — detected via `class_exists('\FluentCart\Framework\Foundation\App')` |

## What it does

From the user's perspective: install & activate FluentCart, then enable the
"FluentCart" module inside NotificationX. Two things become available:

- **Sales notifications** (`fluentcart`, type `conversions`) — a popup showing
  recent FluentCart orders ("John D. purchased Product X"), driven by real
  FluentCart order/payment events.
- **Inline widget** (`fluentcart_inline`, type `inline`, Pro-only themes —
  `is_pro => true`) — inline "Someone purchased ... in last 7 days" or
  "Only N left in stock" lines rendered on FluentCart single-product / cart-item
  contexts (`inline_location: ['fluentcart_single']` /
  `['fluentcart_after_cart_item_name']`), via the `conv-theme-seven`,
  `stock-theme-one`, and `stock-theme-two` themes.

Real events that drive data (`FluentCart::init()`):
- `fluent_cart/order_paid_done` and `fluent_cart/order_created` actions →
  `save_new_records($data)` — builds and saves one notification entry per order
  item immediately (near-real-time) when an order is created/paid.
- `fluent_cart/payment_status_changed` and `fluent_cart/order_status_changed`
  actions → `status_transition($data)` — finds existing entries for that order
  (by `entry_key` prefix `{order_id}_`) and rewrites their `payment_status` /
  `status` / `shipping_status`, deleting and re-inserting the entries.
- Manual/initial backfill — when a `fluentcart`-sourced notification is first
  saved (`saved_post()`), `get_notification_ready()` → `get_orders()` queries a
  batch of recent FluentCart orders (`\FluentCart\App\Models\Order`) bounded by
  the notification's configured "display last N days / N items" settings.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| `FluentCart.php` (class `FluentCart`) | `conversions` | `fluentcart` | No `get_data()` method exists. Data flows through `get_orders($post)` → `\FluentCart\App\Models\Order::with(['customer','order_items','billing_address','shipping_address'])->orderBy('created_at','desc')->limit($amount)->get()`, filtered by a `display_from`/`display_last`-derived date window and per-item product/category include/exclude rules (`_excludes_product()` / `_show_purchaseof()`), then `prepare_order_data($order_item, $customer, [], $order)` builds one row per order item: `product_title`/`product_id`/`permalink`/`image_url` from the order item, `price`/`quantity`, customer `first_name`/`last_name`/`email`/`full_name`, billing-or-shipping `address_fields` (`city`,`country`,`address_1`,`address_2`,`postcode`), and order-level `order_id`/`payment_status`/`status`/`shipping_status`/`fulfillment_status`/`fulfillment_type`/`timestamp`/`ip`. Entry key is `{order->id}_{order_item->id}`. |
| `FluentCartInline.php` (class `FluentCartInline`, extends `FluentCart`) | `inline` | `fluentcart_inline` | Reuses `FluentCart`'s data pipeline (no override of `get_orders`/`prepare_order_data`); `init_extension()` instead defines `$this->themes` (`conv-theme-seven`, `stock-theme-one`, `stock-theme-two` — all `is_pro => true`) and `$this->templates` (`woo_template_sales_count`, `inline_stock_template`) for the inline sales-count / stock-count widgets. |

_Note: the template calls for `get_data()`; this integration does not use that
method name — the equivalent data-fetch entry points are the `save_new_records()`
/ `status_transition()` action hooks (real-time) and `get_orders()` /
`prepare_order_data()` (initial backfill), as described above._

## Data flow

1. **Real-time (create/paid)**: FluentCart fires `fluent_cart/order_created` or
   `fluent_cart/order_paid_done` with `['order' => ..., 'customer' => ...]` →
   `FluentCart::save_new_records($data)` loads `order_items` on the order, and for
   each item calls `prepare_order_data()` then `Extension::save()` (inherited) →
   upserts one entry per order item keyed `{order_id}_{order_item_id}`.
2. **Real-time (status change)**: FluentCart fires `fluent_cart/payment_status_changed`
   or `fluent_cart/order_status_changed` → `FluentCart::status_transition($data)` finds
   all existing entries for that order (via `Entries::get_entries(['source' => 'fluentcart'])`
   filtered by `entry_key` prefix), patches `payment_status`/`status`/`shipping_status`
   in their `data`, deletes the old entries and calls `update_notifications()`
   (inherited) with the updated set.
3. **Backfill on save**: when a `fluentcart`-sourced notification post is saved, the
   `nx_saved_post_fluentcart` filter runs `FluentCart::saved_post()` → deletes
   existing entries for that `nx_id` (`delete_notification()`) then
   `get_notification_ready($post)` → `get_orders($post)` → `update_notifications()`
   (bulk insert, inherited from `Extension`).
4. Entries land in the custom entries table (`Entries` / `Database::$table_entries`,
   per plugin architecture) and are later surfaced through `FrontEnd.php` → REST →
   the React popup runtime, same as other extensions.
5. `FluentCart::nx_can_entry()` (hooked on `nx_can_entry_fluentcart`) gates whether a
   real-time entry is actually displayed: it checks the entry's
   `payment_status`/`status`/`shipping_status`/`fulfillment_status` against the
   campaign's allowed statuses (default `['paid', 'processing']`), and re-applies
   the campaign's Selected-Product / Exclude-Product / category rules against the
   live entry (matching the same logic `get_orders()` applies during backfill).
6. `FluentCart::notification_image()` (hooked as an image-data filter) swaps in the
   product's featured image when `show_notification_image === 'featured_image'`.
7. `FluentCart::fallback_data()` supplies `name`/`first_name`/`last_name` ("Someone")
   and `anonymous_title` ("Anonymous Product") fallbacks, and normalizes
   `product_title` from the saved `title` field when missing.

## Fields & settings

- `FluentCart::order_status()` (hooked on `nx_fluentcart_order_status`) builds the
  order/payment/shipping status options list — from
  `\FluentCart\App\Helpers\Status::getOrderStatuses()` /
  `getPaymentStatuses()` / `getShippingStatuses()` when the FluentCart `Status`
  helper class exists, else a hard-coded fallback list — via
  `GlobalFields::get_instance()->normalize_fields()`.
- `FluentCart::product_lists()` (hooked on `nx_conversion_product_list`) and
  `FluentCart::collections()` (hooked on `nx_conversion_category_list`) populate
  the Selected-Product and product-category (`product-categories` taxonomy)
  pickers used by the show/exclude-product settings, both normalized through
  `GlobalFields::get_instance()->normalize_fields()` scoped to `source =
  fluentcart`.
- `FluentCart::link_types()` (hooked on `nx_link_types`) adds a `product_page` →
  "Product Page" option to the shared Link Type field.
- `FluentCart::restResponse()` / `search_fluentcart_products()` power the async
  product-search field (`\FluentCart\App\Models\Product::query()` search over
  title/content/excerpt, falling back to `Helper::get_post_titles_by_search()`).
- Settings consumed at runtime (not registered as fields in this file —
  `_TODO: verify_` exact field-registration location): `fluentcart_order_status`,
  `product_control` / `product_list`, `product_exclude_by` / `exclude_products`,
  `category_list` / `exclude_categories`, `display_from` / `display_last`,
  `show_default_image` / `show_notification_image`.

## Dependency & detection

- Required plugin: **FluentCart**. `FluentCart::$class =
  '\FluentCart\Framework\Foundation\App'`; the base `Extension::is_active()` /
  `Extension::class_exists()` check `class_exists('\FluentCart\Framework\Foundation\App')`.
  `FluentCartInline` inherits this same `$class` check (it does not override
  `$class`).
- When absent: `Extension::is_active()` returns `false`, so `init()`,
  `admin_actions()`, `public_actions()`, and field registration never run for this
  extension (module effectively inert). Separately,
  `FluentCart::source_error_message()` (hooked on `source_error_message`) surfaces
  an admin error — "You have to install FluentCart plugin first." linking to the
  plugin-install search — scoped via `Rules::is('source', $this->id)` — whenever
  `!$this->class_exists()`.
- Registration itself (`ExtensionFactory::$extension_classes`) is unconditional —
  both `fluentcart` → `NotificationX\Extensions\FluentCart\FluentCart` and
  `fluentcart_inline` → `NotificationX\Extensions\FluentCart\FluentCartInline` are
  always in the factory's class map; gating happens at `is_active()`/module-enabled
  time, not at registration time.
- Several `\FluentCart\App\Models\*` / `\FluentCart\App\Helpers\Status` calls (e.g.
  in `product_lists()`, `order_status()`) are wrapped in their own
  `class_exists()` checks or `try/catch`, so partial FluentCart states (e.g. an
  older version missing a helper) degrade to hard-coded fallback lists rather than
  fatal.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/FluentCart/FluentCart.php`](../../includes/Extensions/FluentCart/FluentCart.php), [`includes/Extensions/FluentCart/FluentCartInline.php`](../../includes/Extensions/FluentCart/FluentCartInline.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`fluentcart`, `fluentcart_inline` entries in `$extension_classes`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Type doc | [`docs/types/conversions.md`](../types/conversions.md) — lists `FluentCart` in the `conversions` type's extension table |

## Testing notes & gotchas

- `FluentCart::nx_can_entry()` re-derives the product slug and category slugs from
  `$entry['data']['product_id']` via `get_post_field()` / `get_the_terms()` at
  display time, so real-time entries and backfilled entries are filtered by the
  *current* product/category assignment, not a snapshot taken when the order was
  placed — a product moved between categories after purchase can change whether
  older notifications still display.
- `status_transition()` searches all entries for the source via
  `Entries::get_entries(['source' => 'fluentcart'])` and matches by string-prefix
  on `entry_key` (`"{$order->id}_"`) — verify this doesn't collide for very large
  order IDs that could be numeric prefixes of other order IDs (`_TODO: verify_`
  whether entry_key collisions are possible in practice).
- `FluentCartInline` is Pro-only per its theme entries (`is_pro => true` on all
  three themes) — verify the free/Pro gating surfaces correctly in the builder UI.
- No dedicated PHPUnit tests found under `tests/` for this integration —
  `_TODO: verify_` if FluentCart-specific test coverage exists elsewhere.
- `save_new_records()` / `get_orders()` require both an `order` and `customer` to
  be present in the hook payload / relation; orders without a loaded customer are
  silently skipped.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [Sales / Conversions type](../types/conversions.md)
