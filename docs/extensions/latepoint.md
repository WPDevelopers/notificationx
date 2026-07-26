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
   `flush_order()` drains the buffer and writes **one** entry, using the first
   *eligible* booking and recording `booking_count` when more than one qualified.
   Both order hooks are needed: LatePoint fires `order_updated` (not `order_created`)
   when a booking is added to an existing order.
3. `build_entry_data()` produces the payload; `nx_can_entry_latepoint` then applies the
   eligibility allowlist at **capture** time, so a rejected booking is never stored.
4. `store_entry()` writes via `Extension::save()` with
   `entry_key = latepoint_{booking_id}`.

Priority 20 is deliberate — LatePoint core registers its own handlers at 10, 12 and 15.

**Updates are delete-then-reinsert.** `nx_entries` writes are INSERT-only with no unique
constraint, so re-saving an `entry_key` would duplicate rather than update.
`handle_booking_updated()` retracts first, then re-stores.

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

Order deletion is *not* in that list — it does fire `latepoint_booking_will_be_deleted`,
which the realtime path already handles.

The job is registered from the constructor, gated on `Modules::is_enabled()` rather than
on LatePoint being active, so that when LatePoint is removed `reconcile()` still runs
once and unschedules itself.

## Fields & settings

Added to [`GlobalFields`](../../includes/Extensions/GlobalFields.php), all gated with
`Rules::includes('source', ['latepoint'])`. **None are Pro-gated** — this is a free
integration (note the adjacent `surecart_order_status` / `fluentcart_order_status`
fields *are* `is_pro`; do not copy that).

| Key | Type | Default | Purpose |
|---|---|---|---|
| `latepoint_booking_status` | multi-select | `approved`, `completed` | Which statuses are captured. Capture-time, so widening it does not recover past bookings — use Regenerate. |
| `latepoint_hide_service_name` | toggle | off | Shows "an appointment" instead of the service name. |
| `latepoint_booking_page_url` | text | empty | Where the notification links to. |

The status list is an **allowlist, never a denylist** — LatePoint lets admins define
arbitrary custom statuses, so anything unrecognised must not display.

## Privacy

Appointments are materially more sensitive than purchases — clinics, legal, counselling.
LatePoint never exposes customer names publicly, so this integration's popup is the
first place that data becomes public. Accordingly:

- Names are masked to first name + last initial.
- **"Hide Service Name"** degrades the popup to "Sarah C. just booked an appointment".
- Hidden and inactive services are excluded outright.
- Bookings with a blank customer name are skipped rather than rendered blank.
- The reconciliation cron honours erasure.

**Never call `$booking->get_data_vars()` or `get_first_level_data_vars()` in this
integration.** They serialize the customer's email and phone, the free-text
`customer_comment`, and `manage_booking_for_customer` / `manage_booking_for_agent` URLs
— which are *bearer credentials* letting anyone view, reschedule or cancel a booking
with no login. The DTO in `build_entry_data()` is hand-written for exactly this reason.

## Dependency & detection

Detected with `public $class = 'LatePoint'`. The `LatePoint` class is declared at file
load, whereas `LATEPOINT_VERSION` is only defined during LatePoint's init — so the
constant is not a safe presence probe.

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
| `latepoint_booking_updated` fired twice | still one row (LatePoint double-fires it via `change_booking_status()`) |
| Booking added to an **existing** order | still notifies (fires `order_updated`, not `order_created`) |
| pending → approved → cancelled | appears, then retracts |
| Delete the **customer**, run `nx_latepoint_reconcile` | popup disappears |
| Guest booking with name fields disabled | skipped, not blank |
| Booking on a hidden service | never shown |
| Booking at 00:00 | renders (`start_datetime_utc` is NULL at midnight, because `start_time` is minutes-from-midnight and `empty(0)` is true) |

Other gotchas:

- **Display-time filters must be registered in `public_actions()`, not `init_fields()`.**
  `init_fields()` is hooked to `nx_before_metabox_load`, which only fires from
  `GlobalFields::tabs()` in the admin builder — a display filter registered there is
  dead on the public site.
- `is_hidden()` reads the `visibility` column, not an `is_hidden` field.
- LatePoint's related-model accessors return **empty models, never null**, so guard on
  `->id` rather than the object.
- `get_nice_created_at()` fatals on PHP 8 when `created_at` is null, and
  `format_created_datetime_rfc3339()` silently substitutes "now" for bad data. Parse the
  raw `created_at` column (it is stored UTC) instead.
- `get_avatar_url()` never returns empty — it falls back to a bundled
  `default-avatar.jpg`, so an unguarded call puts the same grey silhouette on every
  popup.
