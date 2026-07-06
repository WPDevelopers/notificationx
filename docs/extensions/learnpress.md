# LearnPress Extension (`modules_learnpress`)

> Connects NotificationX to [LearnPress](https://wordpress.org/plugins/learnpress/) LMS,
> generating "Someone just enrolled" popup notifications (`elearning` Type) whenever a
> student enrolls in a course, plus a Pro-only inline enrollment-count widget
> (`inline` Type).

## At a glance

| | |
|---|---|
| **Integration** | LearnPress |
| **Directory** | [`includes/Extensions/LearnPress/`](../../includes/Extensions/LearnPress/) |
| **Module key(s) (`$module`)** | `modules_learnpress` (both classes share this module key — `LearnPressInline` inherits it from `LearnPress` rather than redeclaring it) |
| **Feeds Types** | `elearning` (`LearnPress`), `inline` (`LearnPressInline`) |
| **Extension classes** | `LearnPress.php` (class `LearnPress`) → id `learnpress`, type `elearning`; `LearnPressInline.php` (class `LearnPressInline`, extends `LearnPress`) → id `learnpress_inline`, type `inline` |
| **Depends on** | LearnPress plugin — detected via `function_exists('LP')` (`$this->function = 'LP'`) |

## What it does

From the user's perspective: install & activate LearnPress, then enable the
"LearnPress" module inside NotificationX. Two things become available:

- **Course-enrollment popup notifications** (`learnpress`, type `elearning`) — every
  time a user enrolls in a LearnPress course, a new entry is written and rendered via
  the shared `elearning` Type's themes ("Someone just enrolled in course X" style
  popups).
- **Inline enrollment-count widget** (`learnpress_inline`, type `inline`, Pro-only,
  `$is_pro = true`) — an inline "N people enrolled ... in last {{day:7}}" line,
  rendered via the `conv-theme-seven` / `conv-theme-eight` themes at the
  `learn-press/after-course-buttons` and
  `learn-press/list-courses/layout/item/section/bottom` inline locations.

Unlike the LearnDash integration (which is a Pro-gated shell in the free plugin),
`LearnPress` implements real enrollment tracking in the free plugin:
- `public_actions()` hooks `learn-press/added-order-item-data` → `do_enroll()`, which
  builds a notification entry directly from the current logged-in user
  (`get_current_user_id()`) and course/order item data, then calls
  `Extension::update_notification()`.
- The class also carries `WooCommerce`/`Easy Digital Downloads` "monetize by"
  branches (`save_new_enrollment()` via `woocommerce_new_order_item`,
  `save_new_enroll_payment_status()` via `edd_update_payment_status`), but
  `$monetize_by` is hardcoded to `'free'` in `public_actions()` (the code that would
  read a real `monetize_by` option is commented out), so those two hooks are never
  registered in the current wiring — only the `learn-press/added-order-item-data`
  path (free-course enrollment) is actually live. See "Testing notes & gotchas".
- `get_notification_ready()` / `get_purchased_course()` back-fill existing
  `lp-completed` orders (via `get_posts(['post_type' => 'lp_order', 'post_status' =>
  'lp-completed', ...])`) when a notification post is first saved
  (`saved_post()` hook, inherited wiring from `Extension::init()`).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`LearnPress.php`](../../includes/Extensions/LearnPress/LearnPress.php) (class `LearnPress`) | `elearning` | `learnpress` | No `get_data()` method (this integration doesn't use the on-demand `get_data()` pattern; it writes entries directly). `public_actions()` hooks `learn-press/added-order-item-data` → `do_enroll($course_id, $item, $id)`, which reads `get_current_user_id()` / `get_userdata()`, `get_the_title($item['item_id'])`, `get_permalink($item['item_id'])`, builds `{ip, first_name, last_name, name, email, title, link, product_id, timestamp}`, and calls `update_notification()` keyed by `"{item_id}-{id}"`. `get_purchased_course()` back-fills from `lp_order` posts with status `lp-completed` via `get_notification_ready()` (hooked as `saved_post()` in `Extension::init()`), calling `learn_press_get_order()` + `ready_enrolled_data()` per order. |
| [`LearnPressInline.php`](../../includes/Extensions/LearnPress/LearnPressInline.php) (class `LearnPressInline`, extends `LearnPress`) | `inline` | `learnpress_inline` | Also no `get_data()`. `init_extension()` defines `$this->themes` (`conv-theme-seven` at hook `learn-press/after-course-buttons`, `conv-theme-eight` at hook `learn-press/list-courses/layout/item/section/bottom` — both Pro, `image_shape: rounded`, template mapping sales-count/course-title/day-window tags) and `$this->templates` (`learnpress_inline_template_sales_count`, scoped via `_themes` to both theme keys). Defines `show_on_exclude()` gating inline display to those two hooks. Overrides `get_instance()` to prefer a `NotificationXPro\...` class if it exists. Inherits `LearnPress`'s enrollment-writing logic (`do_enroll`, etc.) since it extends the class rather than reimplementing data fetch. |

## Data flow

Real event → entry, traced from the live code path:

1. LearnPress fires `learn-press/added-order-item-data` when an order item is
   recorded for a course purchase/free-enrollment.
2. `LearnPress::do_enroll()` assembles a data array from the current user and the
   item/course, then calls `Extension::update_notification(['source' => $this->id,
   'entry_key' => "{item_id}-{id}", 'data' => $data])`.
3. `update_notification()` (base `Extension`) resolves the target notification post
   (`PostType::get_post`), runs it through the `nx_can_entry_{$this->id}` filter, then
   calls `Entries::insert_entry()` — writing into the custom entries table
   ([`includes/Core/Database.php`](../../includes/Core/Database.php) /
   [`includes/Admin/Entries.php`](../../includes/Admin/Entries.php)).
4. On backfill (when a `learnpress`-sourced notification post is first saved),
   `saved_post()` (inherited hook wiring, `nx_saved_post_{$this->id}` in
   `Extension::init()`) calls `get_notification_ready()` →
   `get_purchased_course()`, which queries `lp_order` posts (`post_status =>
   'lp-completed'`) newer than a configurable "display from" window
   (`Helper::generate_time_string($data)`), resolves each via
   `learn_press_get_order()` + `ready_enrolled_data()`, and bulk-writes via
   `update_notifications()`.
5. From the entries table, [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php)
   → REST → the React popup runtime renders the notification (standard `elearning`
   Type pipeline — `_TODO: verify_` exact FrontEnd bucket/normalize function, not
   traced in this pass).

This is a realtime/hook-driven pipeline, not a polling one — there is no cron/API
call involved for the free-course path.

## Fields & settings

- Neither class registers extension-specific `content_fields()` /
  `design_fields()` / `customize_fields()` filters; both rely on the shared
  `elearning` Type's fields ([`includes/Types/ELearning.php`](../../includes/Types/ELearning.php)).
- `LearnPressInline` defines its own `$themes` / `$templates` arrays (see table
  above) rather than pulling from `GlobalFields`.
- `GlobalFields.php` references `learnpress` only as an entry in a shared
  `Rules::includes('source', [...])` list (line ~2227, gating a common field shown
  across many conversion-style sources) — no LearnPress-specific field definitions
  of its own.

## Dependency & detection

- Required plugin: **LearnPress** (LMS). `$this->function = 'LP'` on the base
  `LearnPress` class (inherited by `LearnPressInline`); `Extension::is_active()` /
  `Extension::class_exists()` check `function_exists('LP')` (LearnPress's global
  helper function).
- When absent: `Extension::is_active()` returns `false`, so `init()`,
  `admin_actions()`, `public_actions()`, and field registration never run (the
  enrollment hooks are never attached). The extension class is still registered in
  `ExtensionFactory` and shown in the source list, but inert.
  `LearnPress::source_error_message()` (hooked on the `source_error_message` filter)
  surfaces an admin error — "You have to install LearnPress plugin first." with a
  link to install it — scoped via `Rules::is('source', $this->id)`, shown whenever
  `!$this->class_exists()`.
- Registration in `ExtensionFactory::$extension_classes` is unconditional — both
  `learnpress` → `NotificationX\Extensions\LearnPress\LearnPress` and
  `learnpress_inline` → `NotificationX\Extensions\LearnPress\LearnPressInline` are
  always in the factory's class map; gating happens at `is_active()`/module-enabled
  time, not at registration time (`ExtensionFactory::register_extensions()` still
  checks `Modules::is_enabled($obj->module)` before adding it to the active list).

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/LearnPress/LearnPress.php`](../../includes/Extensions/LearnPress/LearnPress.php), [`includes/Extensions/LearnPress/LearnPressInline.php`](../../includes/Extensions/LearnPress/LearnPressInline.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`learnpress`, `learnpress_inline` entries in `$extension_classes`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Paired Type | [`includes/Types/ELearning.php`](../../includes/Types/ELearning.php) (`elearning`), [`includes/Types/Inline.php`](../../includes/Types/Inline.php) (`inline`) |

## Testing notes & gotchas

- **This file is a near-verbatim adaptation of
  [`includes/Extensions/Tutor/Tutor.php`](../../includes/Extensions/Tutor/Tutor.php)**
  (same structure, method names, and comments, with LearnPress-specific hook/data
  swapped in) — several Tutor-specific calls were left over: `ordered_product()`,
  `status_transition()`, and `save_new_enroll_payment_status()` all call
  `tutor_utils()->product_belongs_with_course(...)`, a **Tutor LMS** function, not a
  LearnPress one. In the current wiring these three methods are only reachable via
  hooks that are commented out or dead in `public_actions()`/`admin_actions()`
  (the WooCommerce/EDD "monetize by" branches, since `$monetize_by` is hardcoded to
  `'free'`), so this doesn't break the live free-enrollment path — but it means
  those code paths would fatal-error (undefined function) if ever re-enabled without
  Tutor LMS also active. `_TODO: verify_` whether this is intentional shared logic or
  a copy-paste artifact that needs fixing before those branches are turned on.
- `get_purchased_course()` calls `learn_press_get_order()` (a genuine LearnPress
  function) — confirm this exists in the target LearnPress version before relying on
  the backfill path.
- `LearnPressInline::show_on_exclude()` is Pro-only (`$is_pro = true` on the class);
  verify inline widget behavior requires an active Pro license.
- No dedicated PHPUnit tests found under `tests/` for this integration —
  `_TODO: verify_` if LearnPress-specific test coverage exists elsewhere.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs under [../types/](../types/) (eLearning / Inline — `_TODO: verify_` exact filenames once written)
