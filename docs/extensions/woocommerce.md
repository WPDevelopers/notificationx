# WooCommerce Extension (`modules_woocommerce`)

> Connects NotificationX to [WooCommerce](https://wordpress.org/plugins/woocommerce/),
> pulling completed/processing WooCommerce orders to drive Sales (`conversions`)
> and "Growth Alert" (`woocommerce_sales`) notifications, product review comments
> to drive Reviews notifications, and inline "N people purchased" / stock-count
> widgets shown on single product / cart pages.

## At a glance

| | |
|---|---|
| **Integration** | WooCommerce |
| **Directory** | [`includes/Extensions/WooCommerce/`](../../includes/Extensions/WooCommerce/) |
| **Module key(s) (`$module`)** | All seven classes in this directory set `$module = 'modules_woocommerce'` |
| **Feeds Types** | `conversions` (`WooCommerce`), `inline` (`WooInline`), `reviews` (`WooReviews`), `woocommerce_sales` (`WooCommerceSales`, `WooCommerceSalesInline`, `WooCommerceSalesReviews`) |
| **Extension classes** | `WooCommerce.php` → id `woocommerce`, type `conversions`; `WooInline.php` → id `woo_inline`, type `inline`; `WOOReviews.php` (class `WooReviews`) → id `woo_reviews`, type `reviews`; `WooCommerceSales.php` → id `woocommerce_sales`, type `woocommerce_sales`; `WooCommerceSalesInline.php` → id `woocommerce_sales_inline`, type `woocommerce_sales`; `WooCommerceSalesReviews.php` → id `woocommerce_sales_reviews`, type `woocommerce_sales`; `Woo.php` is a shared trait (no `$id`/`$module` of its own) |
| **Depends on** | WooCommerce plugin — detected via `class_exists('\WooCommerce')` (`$class = '\WooCommerce'` on every concrete class) |

## What it does

From the user's perspective: install & activate WooCommerce, then enable the
"WooCommerce" module inside NotificationX. Several notification sources become
available, split across two Types:

- **Sales notifications** (`woocommerce`, type `conversions`) — a popup showing
  recent WooCommerce orders ("John D. purchased Product X"), sourced from orders
  transitioning into `wc-completed`/`wc-processing` status.
- **Reviews notifications** (`woo_reviews`, type `reviews`) — a popup built from
  WooCommerce product review comments (WordPress comments of
  `comment_type === 'review'` on `product` posts).
- **Inline widget** (`woo_inline`, type `inline`, Pro-only — `$is_pro = true`) —
  an inline "N people purchased ... in last 7 days" line, or a "Only N left in
  stock" line, rendered on product/cart pages via `woocommerce_before_add_to_cart_form`
  / `woocommerce_after_cart_item_name` hooks.
- **"Growth Alert" bundle** (type `woocommerce_sales`) — three extensions that pair
  the same Type with WooCommerce data via inheritance: `WooCommerceSales` (extends
  `WooCommerce`, reuses its order pipeline), `WooCommerceSalesInline` (extends
  `WooInline`, reuses the inline sales-count/stock themes), and
  `WooCommerceSalesReviews` (extends `WooReviews`, reuses the review pipeline). See
  `includes/Types/WooCommerceSales.php` for the Type that groups these three
  (documented in [`../types/woocommerce_sales.md`](../types/woocommerce_sales.md);
  full Type-level detail is out of scope for this Extension doc).

Real events that drive data:
- `woocommerce_order_status_changed` action (hooked in `WooCommerce::init()`) —
  fires on every WooCommerce order status transition; when the order moves from a
  non-"done" status into `wc-completed`/`wc-processing` (or the configured
  `order_status` setting), the extension builds notification entries for that
  order's line items and upserts them immediately (near-real-time). The reverse
  transition deletes the entry.
- `woocommerce_new_order_item` action — saves a notification entry as soon as a new
  order line item is created (covers orders created directly in a "done" status).
- `woocommerce_process_shop_order_meta` action (`manual_order()`) — backfills
  entries when an order is manually saved/edited in wp-admin.
- `comment_post` / `trash_comment` / `deleted_comment` / `transition_comment_status`
  actions (hooked in `WooReviews::init()`) — keep review-sourced entries in sync as
  WooCommerce product review comments are approved, trashed, deleted, or have their
  approval status changed.
- Manual/initial backfill — when a notification is first saved (`saved_post()`),
  `get_notification_ready()` queries a batch of recent orders (`wc_get_orders()`) or
  comments (`get_comments()`) bounded by the notification's configured "display
  last N days / N items" settings, and (re)populates entries for it.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`WooCommerce.php`](../../includes/Extensions/WooCommerce/WooCommerce.php) (class `WooCommerce`) | `conversions` | `woocommerce` | No `get_data()` method exists; data flows through `get_orders($data)` (`wc_get_orders()` filtered by `order_status` — default `['wc-completed','wc-processing']` — and a date-query lower bound from `Helper::generate_time_string()`) → `ordered_product($item_id, $item, $order)` per `WC_Order_Item_Product`. `ordered_product()` builds one row per line item: `status`, billing `country`/`state`/`city`, customer `ip`, `order_id`, `product_id` (`var_product_id` for variations), `title`/`link` (via `ready_product_data()` → `wc_get_product()` + `get_permalink()`), `timestamp`, and buyer `first_name`/`last_name`/`name`/`email` (via `buyer()`). Line items belonging to a Tutor LMS course product (`tutor_utils()->product_belongs_with_course()`, only if Tutor is active) are skipped. |
| [`WooInline.php`](../../includes/Extensions/WooCommerce/WooInline.php) (class `WooInline`, extends `WooCommerce`) | `inline` | `woo_inline` | Reuses `WooCommerce`'s order pipeline (no override of `get_orders`/`ordered_product`); `init_extension()` instead defines `$this->themes` (`conv-theme-seven` — sales-count, Pro, `inline_location: ['woocommerce_before_add_to_cart_form']`; `stock-theme-one`/`stock-theme-two` — stock-count, Pro, on `woocommerce_before_add_to_cart_form`/`woocommerce_after_cart_item_name`) and `$this->templates` (`woo_template_sales_count`, `inline_stock_template`). `show_on_exclude()` restricts rendering to the theme's configured `inline_location` hook. |
| [`WOOReviews.php`](../../includes/Extensions/WooCommerce/WOOReviews.php) (class `WooReviews`) | `reviews` | `woo_reviews` | No `get_data()` method exists; data flows through `get_comments($data)` (`get_comments()` with `status = 'approve'`, `post_type = 'product'`, date-query lower bound) → `add($comment)` per `WP_Comment`, filtered to `comment_type === 'review'`. `add()` builds one row per review comment: `product_id`, `content`, `link`/`post_link`, `title` (product title), `timestamp`, `rating` (comment meta), and reviewer `username`/`first_name`/`last_name`/`name`/`email` (from `WP_User` if logged in, else parsed from the comment author name). |
| [`WooCommerceSales.php`](../../includes/Extensions/WooCommerce/WooCommerceSales.php) (class `WooCommerceSales`, extends `WooCommerce`) | `woocommerce_sales` | `woocommerce_sales` | Reuses `WooCommerce`'s full order pipeline unmodified (`get_orders`/`ordered_product`/`get_notification_ready` all inherited); `init_extension()` only sets `$title`/`$module_title` to "Sales Notification". `$img` is empty and `$default_theme = 'woocommerce_sales_theme-one'`. |
| [`WooCommerceSalesInline.php`](../../includes/Extensions/WooCommerce/WooCommerceSalesInline.php) (class `WooCommerceSalesInline`, extends `WooInline`) | `woocommerce_sales` | `woocommerce_sales_inline` | Reuses `WooInline`'s (i.e. `WooCommerce`'s) order pipeline unmodified; `init_extension()` redefines the same `conv-theme-seven`/`stock-theme-one`/`stock-theme-two` themes and `woo_template_sales_count`/`inline_stock_template` templates as `WooInline`, plus sets `$title`/`$module_title` to "Growth Alert" and a `$popup` upsell block. `$is_pro = true`. |
| [`WooCommerceSalesReviews.php`](../../includes/Extensions/WooCommerce/WooCommerceSalesReviews.php) (class `WooCommerceSalesReviews`, extends `WooReviews`) | `woocommerce_sales` | `woocommerce_sales_reviews` | Reuses `WooReviews`'s comment pipeline unmodified (`get_comments`/`add`/`get_notification_ready` all inherited); `init_extension()` redefines the same theme set as `WooReviews` (plus `$res_themes` responsive variants) under `woocommerce_sales_reviews_*` names, and sets `$title`/`$module_title` to "Reviews". `$default_theme = 'woocommerce_sales_reviews_total-rated'`. |
| [`Woo.php`](../../includes/Extensions/WooCommerce/Woo.php) (trait `Woo`, used by `WooCommerce` and `WooReviews`) | n/a — shared trait, not a registered Extension | n/a | Not a data source itself; supplies `_init_fields()` (registers `nx_conversion_product_list` → `products()` and `nx_conversion_category_list` → `categories()` filters), `categories()` (WooCommerce `product_cat` terms via `get_terms()`), `products()` (product titles via `Helper::get_post_titles_by_search('product')`), and `restResponse()` (product search-as-you-type for the builder UI). All three normalize through `GlobalFields::get_instance()->normalize_fields()`. |

## Data flow

1. **Real-time (orders)**: WooCommerce fires `woocommerce_order_status_changed` →
   `WooCommerce::status_transition($id, $from, $to, $order)`. For each active
   `woocommerce`/`woocommerce_sales`-sourced notification post, if the transition
   moves an order from its "done" statuses into a non-done status, matching entries
   are deleted (`delete_notification()`); if it moves from non-done into done,
   `ordered_product()` builds a row and `Extension::update_notification()`
   (inherited) upserts it. `woocommerce_new_order_item` (`save_new_orders()`) and
   `woocommerce_process_shop_order_meta` (`manual_order()`) cover order-creation and
   manual-edit paths the same way.
2. **Real-time (reviews)**: WordPress fires `comment_post` → `WooReviews::post_comment()`
   inserts an entry for approved review comments; `transition_comment_status()` /
   `trash_comment` / `deleted_comment` keep entries in sync as approval state
   changes.
3. **Backfill on save**: When a `woocommerce`/`woo_reviews`/`woocommerce_sales*`-sourced
   notification post is saved, the `nx_saved_post_{$this->id}` filter runs
   `saved_post()` → deletes existing entries for that `nx_id` then
   `get_notification_ready($data)` → `get_orders()`/`get_comments()` →
   `update_notifications()` (bulk insert, inherited from `Extension`).
4. Entries land in the custom entries table (`Entries` / `Database::$table_entries`,
   per plugin architecture) and are later surfaced through `FrontEnd.php` → REST →
   the React popup/inline runtime, same as other extensions.
5. `WooCommerce::multiorder_combine()` (hooked on `nx_filtered_data_woocommerce`)
   optionally merges multiple line items from the same order into one entry with a
   "& N more products" title, gated by the `combine_multiorder` setting.
6. `WooCommerce::notification_image()` / `WooReviews::notification_image()` (hooked
   on `nx_notification_image_{$this->id}`) swap in the product's featured image
   when `show_notification_image === 'featured_image'`.
7. `WooCommerce::fallback_data()` / `WooReviews::fallback_data()` supply placeholder
   text (buyer name, anonymous product title, dummy review content/rating) used in
   the builder preview.
8. `WooCommerce::wpml_translate()` / `WooCommerce::product_link()` (hooked via
   `wpml_actions()`, only when WPML String Translation is loaded) re-resolve the
   translated product ID/title and permalink for the current language.

## Fields & settings

- `Woo::_init_fields()` (used by both `WooCommerce` and `WooReviews`) registers
  `nx_conversion_product_list` (product picker, via `Helper::get_post_titles_by_search('product')`)
  and `nx_conversion_category_list` (WooCommerce category picker, via `get_terms('product_cat')`)
  builder fields, both normalized with `GlobalFields::get_instance()->normalize_fields()`.
  `restResponse()` backs the product search-as-you-type endpoint.
  `WooReviews::init_fields()` also calls `Woo::_init_fields()`.
- `WooCommerce::order_status()` (hooked on `nx_woo_order_status`) exposes the
  WooCommerce order-status list (`wc_get_order_statuses()`) as builder field
  options, consumed by `check_order_status()` / `status_transition()` as the
  configurable `order_status` setting.
- `WooCommerce::link_types()` (hooked on `nx_link_types`) adds a `product_page` →
  "Product Page" option to the shared Link Type field, scoped with
  `Rules::is('source', 'woocommerce')`.
- `combine_multiorder` / `combine_multiorder_text` settings (consumed in
  `multiorder_combine()`) — not defined in this directory. The `combine_multiorder`
  checkbox is registered in [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php)
  (in the shared `content` section, ~line 957, scoped via `Rules::includes('source', ['woocommerce','edd','woocommerce_sales'])`);
  the `combine_multiorder_text` field is Pro-only and registered in
  `notificationx-pro/includes/Extensions/GlobalFields.php` (gated by the
  `nx_combine_multiorder_text_dependency` filter, to which the Pro
  `WooCommerce`/`EDD` classes add their source ids).
- `wpml_included = ['sales_count', 'donation_count']` on `WooCommerce` and
  `WooCommerceSales` — declared but not read anywhere in the free or Pro plugin (no
  consumer found); it appears to be an unused/reserved property.
- `WooInline` / `WooCommerceSalesInline` define their own `$themes` / `$templates`
  arrays (inline sales-count and stock-count widgets) rather than pulling from
  `GlobalFields`.

## The `live-viewers` design (Google Analytics)

`WooInline` **and** `WooCommerceSalesInline` each carry a fourth Growth Alert design,
**`live-viewers`**, rendering "N people are viewing this product right now" from
**realtime Google Analytics** rather than from orders. The two are separate registrations
of the same design — one per Type (`inline` and `woocommerce_sales` respectively) — because
`WooCommerceSalesInline` overrides `init_extension()` wholesale rather than extending its
parent's arrays, and Pro's `WooCommerceSalesInline` extends the *Free* `WooInline` chain,
not Pro's `WooInline`. Everything below is implemented identically in both, with
`{$this->id}` resolving to `woo_inline` or `woocommerce_sales_inline`:

- **Registered conditionally.** `init_extension()` only adds the theme (and its
  `woo_live_viewers_template` tag group) when `Modules::is_enabled('modules_google_analytics')`
  — the number comes from a connected GA4 property, so without that module the design could
  never render anything.
- **Writes no entries.** `nx_can_entry_woo_inline` returns `false` for it, exactly as for
  the low-stock designs, and `nx_filtered_notice` injects a placeholder so the campaign
  still reaches the render loop. The count is resolved in `before_add_to_cart_form()`.
- **Its own error message.** `source_error_message()` adds a second entry keyed
  `{$this->id}_ga`, scoped with `Rules::includes('themes', ["{$this->id}_live-viewers"])`, so
  the "connect Google Analytics" / "needs a GA4 property" notices appear only when this
  design is selected — never for merchants using the sales-count or stock designs.
  `WooCommerceSalesInline` inherits this method unchanged; `$this->id` does the rest.
- **Threshold.** Pro adds a `ga_min_viewers` field (default `2`, same theme scoping). The
  default is deliberately 2: the copy has no singular form, so a value of 1 would render
  "1 people are viewing". Both extensions register **the same field key** into the one
  schema `nx_content_fields` builds, so each `ga_content_fields()` *extends* whichever
  definition landed first rather than replacing it — passing the existing field back through
  `Rules::includes('themes', ...)` merges the second theme id into the existing rule
  (`Rule::can_add()` only merges `includes`, never `!includes`). Registration order is
  therefore irrelevant.

- **It colours itself.** `Core\Inline::get_template()` only wraps params in `<span>` while
  previewing, so on the frontend a Growth Alert is one flat text run with nothing to style —
  which is why the other designs render uncoloured on a live page even though their
  thumbnails and builder previews show green/red. `before_add_to_cart_form()` therefore
  applies its own highlighting (`.nx-live-viewers-count`, `.nx-live-viewers-tail`) and emits
  the matching CSS inline. Colours (`#5D9733`, `#E5554D`) are sampled from the artwork, and
  the inline CSS is the single source of truth — there is deliberately no duplicate rule in
  `Preview.php`.

  The artwork highlights the count **and** the noun after it ("3 people"), but those sit in
  two different params (`first_param` / `second_param`). So the leading word of
  `second_param` is marked with `GA_HL_*` placeholders **before** `get_template()` composes
  the string, and the placeholders are swapped for real spans afterwards. Doing it
  pre-composition is what makes the frontend and the preview identical: in preview the
  markers simply end up nested inside the preview's own param spans. The trailing phrase is
  marked the other way round — `{{right_now}}` is wrapped in `GA_TAIL_*` *after* composition,
  since it is a placeholder rather than merchant copy; the pre-composition marking of
  `custom_fourth_param` remains for merchants who switched the select to "Custom". All of it
  is gated on `is_ga_theme()`, so no other design's markup changes.

- **The trailing phrase is a tag, not custom text.** `fourth_param` is `tag_right_now`
  (declared in the `woo_live_viewers_template` group and therefore scoped to these two
  themes), and the word itself comes from `fallback_data()['right_now']` — the same
  arrangement the low-stock designs use for "left in stock" / "order soon".

  This is deliberate and worth not undoing: `custom_fourth_param` is **one text field shared
  by every design in the builder**. An earlier version set `fourth_param => 'tag_custom'` and
  seeded `custom_fourth_param => 'right now'`, hiding the (then one-choice) select so the
  merchant typed directly into the box. But Quickbuilder's `useDefaults` writes
  `savedValue || triggerValue`, and a field flipping hidden→visible also writes its existing
  form value back — so switching in from `conv-theme-seven` could leave that design's
  "in last {{day:7}}" text sitting in the box. A tag cannot go stale. The select now carries
  two real options ("right now" + "Custom"), so merchants who want their own wording pick
  Custom and get the text box; the theme still seeds `custom_fourth_param` with "right now"
  for that path.

The Google API side lives in the Google directory, not here — Pro's
`Google_Analytics\RealtimeViewers::get_viewers()` is the only entry point `WooInline`
touches. See [google.md](google.md).

## Dependency & detection

- Required plugin: **WooCommerce**. Every concrete class in this directory sets
  `public $class = '\WooCommerce'`; the base `Extension::is_active()` /
  `Extension::class_exists()` check `class_exists('\WooCommerce')`.
- When absent: `Extension::is_active()` returns `false`, so `init()`,
  `admin_actions()`, `public_actions()`, and field registration never run for the
  extension (module effectively inert). Separately, `source_error_message()`
  (implemented on `WooCommerce` and `WooReviews`, hooked on `source_error_message`)
  surfaces an admin error — "You have to install WooCommerce plugin first." with a
  link to install it — scoped via `Rules::is('source', $this->id)` — whenever
  `!$this->class_exists()`.
- Registration itself (`ExtensionFactory::$extension_classes`) is unconditional —
  all six concrete `id => class` pairs (`woocommerce`, `woo_inline`, `woo_reviews`,
  `woocommerce_sales`, `woocommerce_sales_inline`, `woocommerce_sales_reviews`) are
  always in the factory's class map; gating happens at `is_active()`/module-enabled
  time, not at registration time.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/WooCommerce/WooCommerce.php`](../../includes/Extensions/WooCommerce/WooCommerce.php), [`WooInline.php`](../../includes/Extensions/WooCommerce/WooInline.php), [`WOOReviews.php`](../../includes/Extensions/WooCommerce/WOOReviews.php), [`WooCommerceSales.php`](../../includes/Extensions/WooCommerce/WooCommerceSales.php), [`WooCommerceSalesInline.php`](../../includes/Extensions/WooCommerce/WooCommerceSalesInline.php), [`WooCommerceSalesReviews.php`](../../includes/Extensions/WooCommerce/WooCommerceSalesReviews.php) |
| Shared trait | [`includes/Extensions/WooCommerce/Woo.php`](../../includes/Extensions/WooCommerce/Woo.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`woocommerce`, `woo_inline`, `woo_reviews`, `woocommerce_sales`, `woocommerce_sales_inline`, `woocommerce_sales_reviews` entries in `$extension_classes`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Related Type (Growth Alert bundle) | `includes/Types/WooCommerceSales.php` |

## Testing notes & gotchas

- `WooCommerce`/`WooCommerceSales` are one directory feeding **two different
  Types** (`conversions` vs `woocommerce_sales`) through class inheritance rather
  than shared logic — a change to `ordered_product()`/`get_orders()` on the base
  `WooCommerce` class silently affects `WooCommerceSales`,
  `WooCommerceSalesInline`, and `WooCommerceSalesReviews` too since none of them
  override the data pipeline.
- Tutor LMS interop: if `tutor_utils()` exists, order line items / comments for
  products that are also Tutor courses are silently excluded (to avoid
  double-counting with the Tutor extension) — easy to mistake for a data bug.
- `WooInline` and `WooCommerceSalesInline` (and `WooReviews`/`WooCommerceSalesReviews`)
  duplicate their `$themes`/`$templates` arrays rather than sharing them — keep the
  two in sync when adding a new inline/review theme.
- `WooInline`, `WooCommerceSalesInline` are Pro-only (`$is_pro = true`); they will
  show as locked/upsell in the free plugin per the standard
  `is_pro && !NotificationX::is_pro()` pattern in `Extension::__nx_sources()` /
  `register_module()`.
- No dedicated PHPUnit tests exercise this integration's data pipeline. The free
  `tests/` suite is limited to factory/type/REST/migration smoke tests
  (`test-extension-factory.php`, `test-types-factory.php`, `test-rest.php`,
  `test-migration-upgrader.php`), and `notificationx-pro/tests/` only adds
  type/engine/smoke tests (`test-pro-types.php` references the `WooCommerceSales`
  Type name but does not test the WooCommerce data flow). No `ordered_product()` /
  `get_orders()` unit coverage exists in either plugin.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs: [Sales / Conversions](../types/conversions.md),
  [Reviews](../types/reviews.md), [Inline](../types/inline.md), and
  [Growth Alert (WooCommerce Sales)](../types/woocommerce_sales.md)
