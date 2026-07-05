# Easy Digital Downloads Extension (`modules_edd`)

> Connects NotificationX to [Easy Digital Downloads](https://wordpress.org/plugins/easy-digital-downloads/)
> (EDD), pulling completed/published EDD payments (orders) to drive Sales
> (`conversions`) notifications, plus an inline "N people purchased this" widget
> shown on EDD product/archive pages.

## At a glance

| | |
|---|---|
| **Integration** | Easy Digital Downloads |
| **Directory** | [`includes/Extensions/EDD/`](../../includes/Extensions/EDD/) |
| **Module key(s) (`$module`)** | `modules_edd` (both classes share this module key) |
| **Feeds Types** | `conversions` (`EDD`), `inline` (`EDDInline`) |
| **Extension classes** | `EDD.php` → id `edd`, type `conversions`; `EDInline.php` (class `EDDInline`) → id `edd_inline`, type `inline` |
| **Depends on** | Easy Digital Downloads plugin — detected via `class_exists('Easy_Digital_Downloads')` |

## What it does

From the user's perspective: install & activate Easy Digital Downloads, then enable
the "Easy Digital Downloads" module inside NotificationX. Two things become
available:

- **Sales notifications** (`edd`, type `conversions`) — a popup showing recent EDD
  purchases ("John D. purchased Product X"), sourced from completed/published EDD
  payments.
- **Inline sales-count widget** (`edd_inline`, type `inline`, Pro-only —
  `$is_pro = true`) — an inline "Someone purchased ... in last 7 days" line
  rendered on EDD single/archive pages via the `conv-theme-seven` theme.

Real events that drive data:
- `edd_update_payment_status` action (hooked in `EDD::init()`) — fires on every EDD
  payment status change; when the new status is `publish` or `complete`, the
  extension builds notification entries for that single payment and upserts them
  immediately (near-real-time).
- Manual/initial backfill — when a notification is first saved (`saved_post()`),
  `get_notification_ready()` queries a batch of recent EDD payments
  (`edd_get_payments()`) bounded by the notification's configured "display last N
  days / N items" settings, and (re)populates entries for it.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| `EDD.php` (class `EDD`) | `conversions` | `edd` | No `get_data()` method exists; data flows through `get_orders($data)` → `get_payments($days, $amount)` (`edd_get_payments()` with `status = ['publish','complete']` and a date-query lower bound) → `ordered_products()` → `single_order($payment_id)`. `single_order()` builds one notification row per cart line item on an `EDD_Payment`: buyer name/IP/timestamp from `$payment->user_info`, plus `title`, `link` (`get_permalink()`), `product_id`, and a composite `key` (`{payment->key}-{download_id}`). Rows belonging to a product that is also a Tutor LMS course (`tutor_utils()->product_belongs_with_course()`, only if Tutor is active) are skipped. |
| `EDInline.php` (class `EDDInline`, extends `EDD`) | `inline` | `edd_inline` | Reuses `EDD`'s data pipeline (no override of `get_orders`/`single_order`); `init_extension()` instead defines `$this->themes` (`conv-theme-seven`, Pro, `inline_location: ['edd_single']`) and `$this->templates` (`woo_template_sales_count` — first/third/fourth template params for sales-count tag, product-title tag, and 1/7/30-day window tags). Adds `show_on_exclude` filter to only show the inline widget on `edd_single`/`edd_archive` hooks. |

_Note: the template calls for `get_data()`; this integration does not use that
method name — the equivalent data-fetch entry points are `get_orders()` /
`single_order()` (initial backfill) and `update_payment_status()` (real-time), as
described above._

## Data flow

1. **Real-time**: EDD fires `edd_update_payment_status` → `EDD::update_payment_status($payment_id, $new_status, $old_status)`. If `$new_status` is `publish` or `complete`, it calls `single_order($payment_id, $offset)` and, for each resulting row, `Extension::update_notification()` (inherited) → `Entries::insert_entry()`.
2. **Backfill on save**: When an `edd`-sourced notification post is saved, the `nx_saved_post_edd` filter runs `EDD::saved_post()` → deletes existing entries for that `nx_id` (`delete_notification()`) then `get_notification_ready($data)` → `get_orders()` → `update_notifications()` (bulk insert, inherited from `Extension`).
3. Entries land in the custom entries table (`Entries` / `Database::$table_entries`, per plugin architecture) and are later surfaced through `FrontEnd.php` → REST → the React popup runtime, same as other extensions.
4. `EDD::multiorder_combine()` (hooked on `nx_filtered_data_edd`) optionally merges multiple line items from the same payment into one entry with a "& N more products" title, gated by the `combine_multiorder` setting.
5. `EDD::notification_image()` (hooked on `nx_notification_image_edd`) swaps in the download's featured image when `show_notification_image === 'featured_image'`.
6. `EDD::fallback_data()` supplies an `anonymous_title` fallback and normalizes `product_title` from the saved `title` field.

## Fields & settings

- `EDD::link_types()` (hooked on `nx_link_types`) adds a `product_image` → "Product Page" option to the shared Link Type field, via `GlobalFields::get_instance()->normalize_fields()` scoped with `Rules::is('source', 'edd')` — i.e. only shown/applies when the `edd` source is selected.
- `combine_multiorder` / `combine_multiorder_text` settings (consumed in `multiorder_combine()`) — not defined in this file; presumably registered elsewhere in the settings/fields UI (`_TODO: verify_` exact field-registration location).
- `EDDInline` defines its own `$themes` / `$templates` arrays (see table above) rather than pulling from `GlobalFields`.

## Dependency & detection

- Required plugin: **Easy Digital Downloads**. Both classes set `public $class = 'Easy_Digital_Downloads'`; the base `Extension::is_active()` / `Extension::class_exists()` check `class_exists('Easy_Digital_Downloads')`.
- When absent: `Extension::is_active()` returns `false`, so `init()`, `admin_actions()`, `public_actions()`, and field registration never run for this extension (module effectively inert). Separately, `EDD::source_error_message()` (hooked on `source_error_message`) surfaces an admin error message — "You have to install Easy Digital Downloads plugin first." with a link to install it — scoped via `Rules::is('source', $this->id)` — whenever `!$this->class_exists()`.
- Registration itself (`ExtensionFactory::$extension_classes`) is unconditional — both `edd` → `NotificationX\Extensions\EDD\EDD` and `edd_inline` → `NotificationX\Extensions\EDD\EDDInline` are always in the factory's class map; gating happens at `is_active()`/module-enabled time, not at registration time.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/EDD/EDD.php`](../../includes/Extensions/EDD/EDD.php), [`includes/Extensions/EDD/EDInline.php`](../../includes/Extensions/EDD/EDInline.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`edd`, `edd_inline` entries in `$extension_classes`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Bundled theme presets (not read by these classes at runtime — `_TODO: verify_` consumer) | `includes/Extensions/EDD/theme-1.json`, `theme-2.json`, `theme-3.json` |

## Testing notes & gotchas

- `EDD::single_order()` instantiates `\EDD_Payment` directly and reads `EDD_VERSION` to branch GMT-offset handling for EDD < 3.x vs 3.x+ — verify against the installed EDD major version when debugging timestamp drift.
- Tutor LMS interop: if `tutor_utils()` exists, download line items that are also Tutor courses are silently excluded from EDD sale notifications (to avoid double-counting with the Tutor extension) — easy to mistake for a data bug.
- `EDDInline` is Pro-only (`$is_pro = true`); it will show as locked/upsell in the free plugin per the standard `is_pro && !NotificationX::is_pro()` pattern in `Extension::__nx_sources()` / `register_module()`.
- No dedicated PHPUnit tests found under `tests/` for this integration — `_TODO: verify_` if EDD-specific test coverage exists elsewhere (e.g. in `notificationx-pro`).

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- Related Type docs under [../types/](../types/) (Sales/Conversions type — `_TODO: verify_` exact filename once written)
