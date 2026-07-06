# SureCart Extension (`surecart`)

> Connects NotificationX to [SureCart](https://wordpress.org/plugins/surecart/) — pulls
> completed checkouts/orders to drive Sales (`conversions`) notifications.

## At a glance

| | |
|---|---|
| **Integration** | SureCart |
| **Directory** | [`includes/Extensions/SureCart/`](../../includes/Extensions/SureCart/) |
| **Module key(s) (`$module`)** | `modules_surecart` |
| **Feeds Types** | `conversions` (Sales) |
| **Extension classes** | `SureCart.php` → `(types: conversions, id: surecart)` |
| **Depends on** | SureCart plugin — detected via `class_exists('\SureCart')` (`$class = '\SureCart'`) |

## What it does

From the user's perspective: install and configure the SureCart WordPress plugin, enable
the `modules_surecart` module, and create a Sales (`conversions`) notification with
source `surecart`. Two real events drive it:

- **New checkout** — the `surecart/checkout_confirmed` action fires when a customer
  completes checkout; `save_new_records()` builds one notification entry per line item
  and saves it immediately (real-time, webhook/action-driven, not polling).
- **Fulfillment/shipment status change** — the `surecart/models/fulfillment/created` and
  `surecart/models/fulfillment/updated` filters fire when SureCart updates a
  fulfillment; `status_transition()` updates the matching stored entries'
  `fulfillment_status` / `shipment_status`.

Additionally, when a `conversions` notification post using this source is saved,
`saved_post()` runs, which deletes previously stored entries for that `nx_id` and
re-pulls historical orders via `get_notification_ready()` → `get_orders()`, which calls
`\SureCart\Models\Order::with(...)->paginate(...)` directly against SureCart's own
models (not a REST/webhook call) filtered by the notification's `display_from` /
`display_last` settings and product/category include-exclude rules.

## Extension classes & pairings

There is a single extension class in this directory (no per-purpose split like some
other integrations).

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| [`SureCart.php`](../../includes/Extensions/SureCart/SureCart.php) | `conversions` | `surecart` | `\SureCart\Models\Order`, `\SureCart\Models\Product`, `\SureCart\Models\ProductCollection` (SureCart's own model classes), plus the `surecart/checkout_confirmed`, `surecart/models/fulfillment/created`, and `surecart/models/fulfillment/updated` hooks |

Note: this codebase's extensions do not implement a formally named `get_data()`
method — the template's `get_data()` step is generic guidance. For SureCart the
equivalent data-producing methods are `save_new_records()` (checkout webhook),
`status_transition()` (fulfillment webhook), and `get_orders()` /
`get_notification_ready()` (pull-based backfill triggered from `saved_post()`).

## Data flow

- **Real-time path**: `surecart/checkout_confirmed` → `save_new_records()` iterates
  `$checkout->line_items->data`, builds order data via `ordered_product()` →
  `prepare_order_data()` (product title/id/permalink/image, billing/shipping address,
  customer name/email, ip, order id, status, timestamp), then calls the inherited
  `Extension::save()` which writes one entry per currently-enabled `surecart`
  notification post (via `Entries`/`PostType`).
- **Fulfillment path**: `surecart/models/fulfillment/*` → `status_transition()` looks up
  existing entries by `entry_key` (order id) via `Entries::get_entries()`; if found it
  patches `fulfillment_status`/`shipment_status` and re-saves via
  `update_notifications()`; if not found it fetches the order fresh from
  `\SureCart\Models\Order::where(...)` and calls `save()`.
- **Backfill path**: `saved_post()` (fires on `nx_saved_post_surecart`, wired by the base
  `Extension::init()`) → `delete_notification()` then `get_notification_ready()` →
  `get_orders()` pages through `\SureCart\Models\Order` filtered by the notification's
  `display_from`/`display_last`/product-exclude/category-exclude settings, then
  `update_notifications()` stores the entries.
- From there entries flow through the standard NotificationX pipeline: `Entries`
  storage → `FrontEnd.php` → REST → React (`useNotificationX.ts` →
  `NotificationContainer.tsx`), same as other `conversions` sources.
- `check_order_status()` (hooked to `nx_can_entry_{$this->id}`) gates whether an entry
  is allowed to display, based on the notification's `surecart_order_status` setting
  matched against the entry's `status`/`fulfillment_status`/`shipment_status`.

## Fields & settings

- `product_lists()` (filter `nx_conversion_product_list`) — populates the product
  picker from `\SureCart\Models\Product::get()`, keyed by slug, via
  `GlobalFields::normalize_fields()`.
- `collections()` (filter `nx_conversion_category_list`) — populates the
  category/collection picker from `\SureCart\Models\ProductCollection::get()`.
- `order_status()` (filter `nx_surecart_order_status`) — the `surecart_order_status`
  option list: Processing, Unfulfilled, Fulfilled, Shipped, Delivered, Not Shipped.
- `link_types()` (filter `nx_link_types`) — adds a `product_page` link-type option;
  `product_link()` resolves it to the entry's stored `permalink`.
- `fallback_data()` — fallback display strings (`Someone`, `Anonymous Product`) when
  customer/product name data is missing.
- All of the above route through `GlobalFields::get_instance()->normalize_fields(...)`
  (see [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php)) — SureCart does
  not appear to add any dedicated custom field-builder UI beyond these option lists and
  the shared `conversions`/Sales fields.

## Dependency & detection

- **Required plugin**: [SureCart](https://wordpress.org/plugins/surecart/) (must be
  installed and connected).
- **Detection**: `$class = '\SureCart'`; the base `Extension::is_active()` /
  `class_exists()` check `class_exists('\SureCart')`. `Extension::__construct()` only
  registers the type and runs `initialize()` if the `modules_surecart` module is
  enabled; `initialize()` only calls `init()`/`admin_actions()`/`public_actions()` if
  `is_active(false)` passes (i.e. the `\SureCart` class exists).
- **When absent**: `source_error_message()` adds an admin error message (gated by
  `Rules::is('source', $this->id)`) prompting the user to install SureCart, with a link
  straight to `plugin-install.php?s=surecart`. The webhooks in `init()` never fire (no
  SureCart plugin means no `surecart/checkout_confirmed` action), so no data is
  collected while it's absent.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/SureCart/SureCart.php`](../../includes/Extensions/SureCart/SureCart.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'surecart' => 'NotificationX\Extensions\SureCart\SureCart'`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |

## Testing notes & gotchas

- `status_transition()` calls `Entries::get_instance()->get_entries()` — if the order
  wasn't previously saved via `save_new_records()` (e.g. a fulfillment created outside
  the checkout flow, or entries were deleted), it falls back to fetching the order fresh
  from `\SureCart\Models\Order::where(['order_ids' => [...]])`. Verify both paths after
  changing entry storage.
- `get_orders()` filters by `created_at` compared against `strtotime($dateFrom)` /
  `$dateTo` — `_TODO: verify_` whether `created_at` is already a timestamp or a string,
  since the comparison mixes `$createdAt` (raw) with `strtotime($createdAt)`.
- `_excludes_product()` / `_show_purchaseof()` implement product/category
  include-exclude logic — check both `product_exclude_by` and `product_control`
  settings combinations when testing product filtering.
- No dedicated tests for this extension were found under `tests/`; `_TODO: verify_` if
  any exist elsewhere.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs under [../types/](../types/)
