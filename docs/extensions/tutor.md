# Tutor LMS Extension (`modules_tutor`)

> Connects NotificationX to [Tutor LMS](https://wordpress.org/plugins/tutor/),
> pulling course enrollments (free, WooCommerce-monetized, or Easy Digital
> Downloads-monetized) to drive E-Learning (`elearning`) notifications, plus a
> Pro-only inline "N people enrolled" widget on course pages.

## At a glance

| | |
|---|---|
| **Integration** | Tutor LMS |
| **Directory** | [`includes/Extensions/Tutor/`](../../includes/Extensions/Tutor/) |
| **Module key(s) (`$module`)** | `modules_tutor` (set on `Tutor`; `TutorInline` extends `Tutor` and inherits it — it does not redeclare `$module`) |
| **Feeds Types** | `elearning` (`Tutor`, shared with `LearnDash`/`LearnPress` — see [`includes/Types/ELearning.php`](../../includes/Types/ELearning.php)), `inline` (`TutorInline`) |
| **Extension classes** | `Tutor.php` (class `Tutor`) → id `tutor`, type `elearning`; `TutorInline.php` (class `TutorInline`, extends `Tutor`) → id `tutor_inline`, type `inline`, `$is_pro = true` |
| **Depends on** | Tutor LMS plugin — detected via `function_exists('tutor_lms')` (`$function = 'tutor_lms'`, checked by the inherited `Extension::class_exists()`) |

There is also an empty, unused file `includes/Extensions/Tutor/__Tutor.php` (0
bytes) in this directory — `_TODO: verify_` why it exists / whether it is dead
weight safe to remove.

## What it does

From the user's perspective: install & activate Tutor LMS, then enable the
"Tutor LMS" module inside NotificationX. Two things become available:

- **E-Learning notifications** (`tutor`, type `elearning`) — a popup showing
  recent course enrollments ("John D. enrolled in Course X"). How an enrollment
  is captured depends on Tutor's monetization mode (`tutils()->get_option('monetize_by')`):
  - `free` (or the configured payment plugin isn't active) — hooks
    `tutor_after_enroll`.
  - `wc` (WooCommerce monetization) — hooks `woocommerce_new_order_item` (create)
    and `woocommerce_order_status_changed` (status transitions, incl. cleanup of
    entries on refund/cancel), gated by `class_exists('WooCommerce')`.
  - `edd` (Easy Digital Downloads monetization) — hooks
    `edd_update_payment_status`, gated by `class_exists('Easy_Digital_Downloads')`.
- **Inline enrollment-count widget** (`tutor_inline`, type `inline`, Pro-only —
  `$is_pro = true`) — an inline "N people enrolled in last {{day}}" line, shown via
  `conv-theme-seven` (on `tutor/course/single/entry-box/free`) or
  `conv-theme-eight` (on `tutor_course/loop/after_title`).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| `Tutor.php` (class `Tutor`) | `elearning` | `tutor` | No `get_data()` method — this extension follows the hook-driven pattern (like `WooCommerce`/`EDD`), not a polling `get_data()`. Real-time: `save_new_enrollment()` (WC order item created), `save_new_enroll_payment_status()` (EDD payment published — reads `$payment->cart_details`/`user_info` via `EDD_Payment`), `do_enroll()` (free enrollment via `tutor_after_enroll` — reads the current `WP_User`), and `status_transition()` (WC order status change — adds/removes entries per line item). Backfill: `saved_post()` → `get_notification_ready($data)` → `get_purchased_course($data)`, which queries `get_posts(['post_type' => 'tutor_enrolled', 'post_status' => 'completed', ...])` bounded by the notification's configured date range (`Helper::generate_time_string($data)`), then `ready_enrolled_data()` builds each row (course title/link/product_id from `post_parent`, buyer info from `user_data_by()`). |
| `TutorInline.php` (class `TutorInline`, extends `Tutor`) | `inline` | `tutor_inline` | Reuses `Tutor`'s data pipeline unchanged (no override of the enrollment-capture or backfill methods); `init_extension()` instead defines `$this->themes` (`conv-theme-seven`, `conv-theme-eight`, both Pro) and `$this->templates` (`tutor_inline_template_sales_count` — sales-count tag, course-title tag, and 1/7/30-day window tags). Adds a `nx_show_on_exclude` filter (`show_on_exclude()`) restricting display to the two `inline_location` hooks. |

## Data flow

Trace one free enrollment:

1. Student enrolls → Tutor LMS fires `tutor_after_enroll( $course_id, $isEnrolled )`
   (only hooked when `monetize_by === 'free'`, or when the configured payment
   plugin — WooCommerce/EDD — isn't actually active).
2. `Tutor::do_enroll()` reads the current user (`get_userdata`), the course title
   (`get_the_title`)/permalink, IP, and `time()`, then calls the inherited
   `Extension::update_notification()` with `source = 'tutor'` and a composite
   `entry_key` (`{course_id}-{isEnrolled}`).
3. `update_notification()`/`update_notifications()` (inherited from `Extension`)
   write into the plugin's custom entries storage.
4. On initial notification creation/save, `saved_post()` deletes any stale entries
   for that `nx_id` and calls `get_notification_ready()` to backfill from existing
   `tutor_enrolled` posts within the configured date window.
5. Entries are later surfaced through `FrontEnd.php` → REST → the React popup
   runtime, same as other extensions (see
   [`docs/new-notification-type.md`](../new-notification-type.md)).

WooCommerce- and EDD-monetized enrollments follow the same
`update_notification()`/backfill shape but source their buyer/order data from
`WC_Order` / `EDD_Payment` objects instead of the current `WP_User`, and also
cross-check that the purchased product actually maps to a course via
`tutor_utils()->product_belongs_with_course()`.

## Fields & settings

- No `init_fields()` override / custom `nx_content_fields` / `nx_design_tab_fields`
  filters found in `Tutor.php` — this extension does not appear to register its
  own settings-tab fields beyond what `Extension`'s defaults and the `elearning`
  Type provide. `_TODO: verify_` whether course-selection or other elearning-type
  fields (e.g. `nx_elearning_course_list`, defined in
  [`includes/Types/ELearning.php`](../../includes/Types/ELearning.php)) are
  populated by Tutor elsewhere, since `Tutor.php` does not hook that filter
  itself.
- `TutorInline` defines its own `$themes` / `$templates` arrays (see table above)
  rather than pulling shared field definitions from `GlobalFields`.
- `Tutor::notification_image()` (hooked on `nx_notification_image_tutor`) swaps in
  the course's featured image or the enrolling user's Gravatar, based on the
  `show_notification_image` setting.
- `Tutor::fallback_data()` supplies `name`/`first_name`/`last_name` ("Someone"),
  `anonymous_title`, and `course_title` fallbacks for the notification template.

## Dependency & detection

- Required plugin: **Tutor LMS**. `Tutor::$function = 'tutor_lms'`; the inherited
  `Extension::class_exists()` checks `function_exists('tutor_lms')` (falls back to
  checking `$class`/`$constant` only if those properties were set, which they are
  not here).
- When absent: `Extension::is_active()` (used by `Extension::__construct()` /
  `initialize()`) still runs based on the module being enabled and
  `$constant`/`$class` checks — but `Tutor::class_exists()` returning `false`
  drives `source_error_message()`, which surfaces an admin error — "You have to
  install Tutor LMS plugin first." with a link to the plugin install screen —
  scoped via `Rules::is('source', $this->id)`.
- Optional dependencies are also individually detected before their hooks are
  wired in `public_actions()` / `admin_actions()`: `class_exists('WooCommerce')`
  and `class_exists('Easy_Digital_Downloads')`, each combined with Tutor's own
  `monetize_by` option (`tutils()->get_option('monetize_by')`).
- Registration itself (`ExtensionFactory::$extension_classes`) is unconditional —
  both `tutor` → `NotificationX\Extensions\Tutor\Tutor` and `tutor_inline` →
  `NotificationX\Extensions\Tutor\TutorInline` are always in the factory's class
  map; gating happens at `is_active()`/module-enabled/`class_exists()` time, not
  at registration time.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/Tutor/Tutor.php`](../../includes/Extensions/Tutor/Tutor.php), [`includes/Extensions/Tutor/TutorInline.php`](../../includes/Extensions/Tutor/TutorInline.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`tutor`, `tutor_inline` entries in `$extension_classes`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Type definition | [`includes/Types/ELearning.php`](../../includes/Types/ELearning.php) |

## Testing notes & gotchas

- The `elearning` Type is shared by three extensions (`Tutor`, `LearnDash`,
  `LearnPress`) — changes to `ELearning.php` themes/templates affect all three;
  verify Tutor-specific themes/fields still resolve after any shared-type change.
- Monetization-mode branching (`free`/`wc`/`edd`) means the actual hook wired at
  runtime depends on both Tutor's own settings and which commerce plugin is
  active — test each of the three paths independently after changes, including
  the WooCommerce order-status transition (enroll on `completed`/`processing`,
  remove entry on refund/cancel/failed).
- `status_transition()` and `ordered_product()` assume WooCommerce types
  (`WC_Order`, `WC_Order_Item_Product`, `WC_Countries`) are available — these run
  only inside the `class_exists('WooCommerce')`-gated branch, but confirm no
  fatal occurs if WooCommerce is deactivated mid-request.
- The empty `__Tutor.php` file in this directory is unreferenced by
  `ExtensionFactory.php` — `_TODO: verify_` its purpose before deleting.
- No dedicated tests for this integration were found under `tests/` —
  `_TODO: verify_`.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [`elearning` Type doc](../types/elearning.md)
- [`inline` Type doc](../types/inline.md)
