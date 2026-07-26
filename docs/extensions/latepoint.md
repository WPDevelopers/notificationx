# LatePoint Extension (`modules_latepoint`)

> Connects NotificationX to [LatePoint](https://wordpress.org/plugins/latepoint/), a
> booking/appointment plugin, surfacing confirmed appointments as Sales
> (`conversions`) social-proof notifications — "Sarah C. just booked a Deep Tissue
> Massage".

This is NotificationX's first booking-plugin integration. LatePoint stores bookings,
services and customers in its own database tables rather than as WordPress posts, and
several of its deletion paths fire no usable hook — both facts shape the design below.

## At a glance

| | |
|---|---|
| **Integration** | LatePoint |
| **Directory** | [`includes/Extensions/LatePoint/`](../../includes/Extensions/LatePoint/) |
| **Module key (`$module`)** | `modules_latepoint` |
| **Feeds Types** | `conversions` |
| **Extension classes** | `LatePointConversions.php` → (`conversions`, `latepoint`) |
| **Depends on** | LatePoint plugin — `class_exists('LatePoint')` |

## What it does

With LatePoint active and the module enabled, "LatePoint" appears as a source for Sales
notifications. Confirmed bookings then drive popups showing the customer's masked name,
the service booked, and how long ago it happened.

Only bookings whose status is in the campaign's allowlist are captured — **Approved and
Completed** by default. Pending, payment-pending, cancelled and no-show bookings are
never announced, and neither are bookings on hidden services.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| `LatePointConversions.php` | `conversions` | `latepoint` | Realtime capture from LatePoint's booking hooks, plus a bounded backfill on campaign save |

## Data flow

**Capture is buffered and anchored to the order, not the booking.**
`latepoint_booking_created` fires once per booking with no cap — a recurring booking
expands to one event per occurrence (a weekly-for-a-year recurrence is ~52 events in a
single request) and a cart fires one per line item. Writing directly in that handler
would emit N popups for one customer.

1. `latepoint_booking_created` (priority 20) → `buffer_booking()` stores the booking in
   a request-scoped buffer. Nothing is written.
2. `latepoint_order_created` **and** `latepoint_order_updated` (priority 20) →
   `flush_order()` drains the buffer and writes **one** entry, stopping at the first
   *eligible* booking. Both order hooks are needed: LatePoint fires `order_updated`
   (not `order_created`) when a booking is added to an existing order.
3. `shutdown` (priority 20) → `flush_order()` again, as a fallback. LatePoint's
   bundle-scheduling branch in `steps_helper::prepare_step_confirmation()` fires
   `latepoint_booking_created` and then returns without ever reaching
   `convert_to_order()`, so no order hook fires at all and the buffer would otherwise
   be filled and discarded. It is a no-op whenever the buffer is empty, which is every
   normal request.
4. `build_entry_data()` produces the payload; `nx_can_entry_latepoint` then applies the
   eligibility allowlist at **capture** time, so a rejected booking is never stored.
5. `store_entry()` writes via `Extension::save()` with
   `entry_key = latepoint_{booking_id}`.

Priority 20 is deliberate — LatePoint core registers its own handlers at 10, 12 and 15.

**Every write is delete-then-reinsert.** `nx_entries` writes are INSERT-only with no
unique constraint, so re-saving an `entry_key` would duplicate rather than update.
`store_entry()` therefore retracts the booking's existing entries before inserting,
which makes the write idempotent — a booking that is both buffered and status-changed
inside one request still ends up as a single row.

**Thumbnails are resolved at capture time.** `build_entry_data()` stores
`service_image` and `customer_avatar` on the entry, and `notification_image()` only
reads them. Resolving them at display time meant instantiating `OsServiceModel` and
`OsBookingModel` for every cached entry (up to `cache_limit`, 100) on **every** frontend
pageview, before `display_last` slicing — hundreds of uncached queries per request.
SureCart and FluentCart store the URL on the entry for the same reason. The tradeoff is
that changing a service's image does not refresh existing entries until Regenerate.

**Retraction** listens on `latepoint_booking_will_be_deleted`, not
`latepoint_booking_deleted`: deleting an order fires the former for each of its bookings
but never the latter.

## Reconciliation cron (`nx_latepoint_reconcile`)

A daily job that drops entries whose booking is gone or no longer eligible. **This is a
correctness and compliance requirement, not a safety net** — hooks alone cannot keep the
data correct:

- Deleting a **customer** cascades to all their bookings with **no hooks at all**. This
  is the GDPR erasure path: without reconciliation, an erased customer's name would keep
  appearing in public popups indefinitely.
- LatePoint's **abilities / MCP-AI layer** creates, changes and deletes bookings while
  firing no hooks whatsoever.
- General backstop for a status that drifted out of the allowlist without an event.
- A service **hidden after** the booking was captured. `build_entry_data()` rejects
  hidden services at capture time; `reconcile()` applies the same test to entries that
  are already live, so hiding a service retracts the popups advertising it.

Order deletion is *not* in that list — it does fire `latepoint_booking_will_be_deleted`,
which the realtime path already handles.

The **handler** is registered from the constructor, gated on `Modules::is_enabled()`
rather than on LatePoint being active, so that when LatePoint is removed `reconcile()`
still runs once and unschedules itself. The **event** is only (re)created while
`class_exists('LatePoint')` — scheduling it unconditionally would have made that
self-unscheduling pointless, since the next pageload would immediately recreate it.

## Fields & settings

Added to [`GlobalFields`](../../includes/Extensions/GlobalFields.php), all gated with
`Rules::includes('source', ['latepoint'])`. **None are Pro-gated** — this is a free
integration (note the adjacent `surecart_order_status` / `fluentcart_order_status`
fields *are* `is_pro`; do not copy that).

| Key | Type | Default | Purpose |
|---|---|---|---|
| `latepoint_booking_status` | multi-select | `approved`, `completed` | Which statuses are captured. Capture-time, so widening it does not recover past bookings — use Regenerate. |
| `latepoint_hide_service_name` | toggle | off | Shows "an appointment" instead of the service name, **and** suppresses the service image. |
| `latepoint_booking_page_url` | text | empty | Where the notification links to. Only applied when **Link Type** is set to *Booking Page*. |

The status list is an **allowlist, never a denylist** — LatePoint lets admins define
arbitrary custom statuses, so anything unrecognised must not display.

The extension also registers a `booking_page` option on the shared `nx_link_types`
filter, so *Booking Page* is selectable in the Content tab's **Link Type** dropdown.
Without it the dropdown offers only *None*, and `FrontEnd::link_url()` blanks the link
before `booking_link()` ever runs.

## Privacy

Appointments are materially more sensitive than purchases — clinics, legal, counselling.
LatePoint never exposes customer names publicly, so this integration's popup is the
first place that data becomes public. Accordingly:

- Names are masked to first name + last initial.
- **"Hide Service Name"** degrades the popup to "Sarah C. just booked an appointment",
  and also suppresses the service's own image — publishing the artwork identifies the
  service just as plainly as its name would.
- Hidden and inactive services are excluded outright, both at capture and on reconcile.
- Bookings with a blank customer name are skipped rather than rendered blank.
- The reconciliation cron honours erasure.

**Never call `$booking->get_data_vars()` or `get_first_level_data_vars()` in this
integration.** They serialize the customer's email and phone, the free-text
`customer_comment`, and `manage_booking_for_customer` / `manage_booking_for_agent` URLs
— which are *bearer credentials* letting anyone view, reschedule or cancel a booking
with no login. The DTO in `build_entry_data()` is hand-written for exactly this reason.

## Dependency & detection

Detected with `public $class = 'LatePoint'` — the plugin's main class, declared at file
load in `latepoint.php`, and the same `class_exists()` convention every other extension
in `includes/Extensions/` uses.

When LatePoint is absent, `Extension::is_active(false)` is false, so `init()`,
`admin_actions()`, `public_actions()` and `init_fields()` never run, and
`source_error_message()` renders an admin notice pointing at the plugin installer.

## Key files

| Purpose | File |
|---|---|
| Extension class | `includes/Extensions/LatePoint/LatePointConversions.php` |
| Registration | `includes/Extensions/ExtensionFactory.php` |
| Builder fields | `includes/Extensions/GlobalFields.php` |
| Source icon | `assets/admin/images/extensions/sources/latepoint.png` |

## Testing notes & gotchas

LatePoint is not installed in CI, so the PHPUnit suite only covers registration wiring
(`tests/test-extension-factory.php`). Behaviour must be verified on a site with LatePoint
active. The scenarios that matter, each mapping to a real upstream defect:

| Scenario | Expected |
|---|---|
| Cart with 3 bookings | **one** popup, not three |
| Recurring booking | one popup, not N |
| `latepoint_booking_updated` fired twice | still one row (`store_entry()` retracts before inserting) |
| Booking added to an **existing** order | still notifies (fires `order_updated`, not `order_created`) |
| Bundle scheduling (no order hook fires) | still notifies, via the `shutdown` flush |
| pending → approved → cancelled | appears, then retracts |
| Delete the **customer**, run `nx_latepoint_reconcile` | popup disappears |
| Hide a service, run `nx_latepoint_reconcile` | its existing popups disappear |
| Guest booking with name fields disabled | skipped, not blank |
| Booking on a hidden service | never shown |
| Campaign with `display_last` = 1 | backfills one booking, not zero (`get_results_as_models()` returns a bare model, not an array, when the limit is 1) |
| **Link Type** left at *None* | popup is not clickable, even with a Booking Page URL saved |
| **Show Default Image** on | the chosen default image wins over the service image |

Other gotchas:

- **Display-time filters must be registered in `public_actions()`, not `init_fields()`.**
  `init_fields()` is hooked to `nx_before_metabox_load`, which only fires from
  `GlobalFields::tabs()` in the admin builder — a display filter registered there is
  dead on the public site.
- `is_hidden()` reads the `visibility` column, not an `is_hidden` field.
- LatePoint's related-model accessors return **empty models, never null**, so guard on
  `->id` rather than the object.
- `get_nice_created_at()` calls `setTimezone()` on the return of
  `date_create_from_format()` without checking it, so a null or malformed `created_at`
  is a fatal; `format_created_datetime_rfc3339()` routes through
  `OsTimeHelper::date_from_db()`, which silently substitutes "now" for anything it
  cannot parse. Parse the raw `created_at` column (it is stored UTC) instead, and
  return `false` so the row can be rejected.
- `get_avatar_url()` never returns empty — it falls back to a bundled
  `default-avatar.jpg`, so an unguarded call puts the same grey silhouette on every
  popup. `get_selection_image_url()` behaves the same way with `service-image.png`.
- `get_results_as_models()` **un-arrays its own return** when the limit is 1, so
  `is_array($results) ? $results : []` silently discards the single row.
- `add_action()`'s `$accepted_args` is per-callback: another listener registering
  `latepoint_booking_updated` with 3 args does not mean this one receives 3.
