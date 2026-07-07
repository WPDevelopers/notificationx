# LearnDash Extension (`modules_learndash`)

> Connects NotificationX to [LearnDash](https://www.learndash.com/) LMS, registering
> course-enrollment "N people just enrolled" popup notifications (`elearning` Type)
> and a Pro-only inline enrollment-count widget (`inline` Type). Both classes here
> are registration shells only — the actual enrollment data fetching
> (`get_data()`-equivalent logic) lives entirely in `notificationx-pro`, since this
> whole integration is Pro-gated (`$is_pro = true`).

## At a glance

| | |
|---|---|
| **Integration** | LearnDash |
| **Directory** | [`includes/Extensions/LearnDash/`](../../includes/Extensions/LearnDash/) |
| **Module key(s) (`$module`)** | `modules_learndash` (both classes share this module key) |
| **Feeds Types** | `elearning` (`LearnDash`), `inline` (`LearnDashInline`) |
| **Extension classes** | `LearnDash.php` → id `learndash`, type `elearning`; `LearnDashInline.php` (class `LearnDashInline`, extends `LearnDash`) → id `learndash_inline`, type `inline` |
| **Depends on** | LearnDash plugin — detected via `class_exists('\LDLMS_Post_Types')` |

## What it does

From the user's perspective: install & activate LearnDash, then enable the
"LearnDash" module inside NotificationX. Two things become available:

- **Course-enrollment popup notifications** (`learndash`, type `elearning`) — shown
  via the shared `elearning` Type's themes ("Someone just enrolled" style popups).
  `LearnDash` itself is entirely Pro (`$is_pro = true`), so this source is
  locked/upsell in the free plugin.
- **Inline enrollment-count widget** (`learndash_inline`, type `inline`, also
  Pro-only) — an inline "N people enrolled in last {{day:7}}" line, rendered via the
  `conv-theme-seven` theme at the `learndash_content` inline location.

In the free `notificationx` plugin, **neither class implements a data-fetch method**
(no `get_data()`, and no override of `init()`/`save_post()`/`saved_post()` beyond
what `Extension` provides). Both classes only:
- set metadata (`$id`, `$module`, `$types`, `$class`, popup/doc copy) in
  `init_extension()`,
- register the LearnDash-install error message (`source_error_message()`) and docs
  link (`doc()`) on the `LearnDash` base class,
- (for `LearnDashInline`) define `$this->themes` / `$this->templates` for the inline
  widget and a `show_on_exclude()` filter.

The real event wiring and data pulling happens in the Pro plugin's
`NotificationXPro\Extensions\LearnDash\LearnDash` class (extends this free
`LearnDash` class), which hooks `learndash_update_course_access` →
`save_new_enrollment($user_id, $course_id)` and implements the enrollment-fetch logic
via the `_LearnDash` trait (`notificationx-pro/includes/Extensions/LearnDash/_LearnDash.php`)
— out of scope for this free-plugin directory.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`LearnDash.php`](../../includes/Extensions/LearnDash/LearnDash.php) (class `LearnDash`) | `elearning` | `learndash` | No `get_data()` (or any data-fetch method) in the free plugin. `init_extension()` only sets `$this->title`, `$this->module_title`, and `$this->popup` (upsell popup copy/links). `source_error_message()` adds an admin error when LearnDash isn't installed; `doc()` returns instructional HTML for the field/help panel. Real enrollment-data logic lives in the Pro override of this class. |
| [`LearnDashInline.php`](../../includes/Extensions/LearnDash/LearnDashInline.php) (class `LearnDashInline`, extends `LearnDash`) | `inline` | `learndash_inline` | Also no `get_data()`. `init_extension()` defines `$this->themes` (`conv-theme-seven` — Pro, `image_shape: rounded`, `inline_location: ['learndash_content']`, template mapping sales-count/course-title/day-window tags) and `$this->templates` (`learndash_inline_template_sales_count` — first/third/fourth template param options, scoped via `_themes` to `learndash_inline_conv-theme-seven`). Also defines `show_on_exclude()` (checks `settings['type'] === 'inline' && settings['source'] === $this->id`, diffs against a `[ 'tutor_course/loop/after_title' ]` hook list — confirmed a copy-paste artifact from the Tutor inline sibling: the theme's own `inline_location` is `learndash_content`, not that Tutor hook, so this diff never matches LearnDash's actual location). Overrides `get_instance()` to prefer a `NotificationXPro\...` class if it exists. |

## Data flow

Because both free-plugin classes are metadata/theme-registration shells with no
data-fetch or save-post override, the actual "source event → entry" pipeline is not
present in `includes/Extensions/LearnDash/`. What's confirmed from the Pro plugin
(`notificationx-pro/includes/Extensions/LearnDash/LearnDash.php`, read for context
only — not part of this doc's scope):
- Pro's `LearnDash` class hooks `learndash_update_course_access` → `save_new_enrollment()`,
  and adds `nx_can_entry_{$this->id}` filtered through the `ELearning` Type's
  `show_purchase_of()`.
- From there, entries would flow through the standard `Extension::update_notification()`
  / `Entries::insert_entry()` pipeline (inherited, per plugin architecture) into the
  custom entries table, then FrontEnd → REST → the React popup runtime.

The exact enrollment-fetch logic lives in the Pro `_LearnDash` trait
(`notificationx-pro/includes/Extensions/LearnDash/_LearnDash.php`): backfill runs
through `get_notification_ready()` → `get_course_enrollments()`, which queries the
`{$wpdb->prefix}learndash_user_activity` table (JOINed to `posts`,
`activity_type = "access"`, `LIMIT 50`); live capture runs through
`save_new_enrollment()`, merging `get_enrolled_course()` + `get_enrolled_user()`.
Outside this repo's documented scope.

## Fields & settings

- No extension-specific field registration (`init_fields()`, `content_fields()`,
  etc.) exists on either free-plugin class; both rely entirely on inherited
  `Extension` behavior plus the shared `elearning` Type's fields
  ([`includes/Types/ELearning.php`](../../includes/Types/ELearning.php)) — e.g.
  `ld_product_control` (show notifications for all courses vs. a selected list) and
  `ld_course_list` (async course picker via `/notificationx/v1/get-data`,
  `field: ld_course_list`).
- `LearnDashInline` defines its own `$themes` / `$templates` arrays (see table
  above) rather than pulling from `GlobalFields`.
- `GlobalFields.php` references `learndash` only as an entry in shared `rules`
  arrays (e.g. `show_notification_image` options, source-trigger defaults for
  `show_notification_image: featured_image`) — no LearnDash-specific field
  definitions of its own.

## Dependency & detection

- Required plugin: **LearnDash** (LMS). Both classes set
  `public $class = '\LDLMS_Post_Types'`; the base `Extension::is_active()` /
  `Extension::class_exists()` check `class_exists('\LDLMS_Post_Types')`.
- When absent: `Extension::is_active()` returns `false`, so `init()`,
  `admin_actions()`, `public_actions()`, and field registration never run for this
  extension (module effectively inert, though the extension class is still
  registered/instantiated — see below). `LearnDash::source_error_message()` (hooked
  on `source_error_message`) surfaces an admin error — "You have to install
  LearnDash plugin first." — scoped via `Rules::is('source', $this->id)`, shown
  whenever `!$this->class_exists()`.
- Registration in `ExtensionFactory::$extension_classes` is unconditional — both
  `learndash` → `NotificationX\Extensions\LearnDash\LearnDash` and
  `learndash_inline` → `NotificationX\Extensions\LearnDash\LearnDashInline` are
  always in the factory's class map; gating happens at `is_active()`/module-enabled
  time, not at registration time.
- Both classes are Pro-only (`$is_pro = true`) independent of the LearnDash-plugin
  detection above — in the free `notificationx` plugin they show as locked/upsell
  per the standard `is_pro && !NotificationX::is_pro()` pattern in
  `Extension::__nx_sources()` / `register_module()`, even when LearnDash itself is
  installed.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/LearnDash/LearnDash.php`](../../includes/Extensions/LearnDash/LearnDash.php), [`includes/Extensions/LearnDash/LearnDashInline.php`](../../includes/Extensions/LearnDash/LearnDashInline.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`learndash`, `learndash_inline` entries in `$extension_classes`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Paired Type | [`includes/Types/ELearning.php`](../../includes/Types/ELearning.php) (`elearning`), [`includes/Types/Inline.php`](../../includes/Types/Inline.php) (`inline`) |
| Pro override (data logic, out of scope) | `notificationx-pro/includes/Extensions/LearnDash/LearnDash.php` (+ `LearnDashInline.php`), `_LearnDash.php` trait — sibling plugin |

## Testing notes & gotchas

- This entire integration is Pro-gated (`$is_pro = true` on both classes); on the
  free plugin alone there is no working enrollment-data pipeline to test — verifying
  behavior requires `notificationx-pro` active.
- `LearnDashInline::show_on_exclude()` diffs against a `tutor_course/loop/after_title`
  hook list rather than a LearnDash-specific hook — confirmed a copy-paste artifact
  from the Tutor inline class (the theme's `inline_location` is `learndash_content`,
  which never appears in that Tutor hook list).
- `LearnDashInline::get_instance()` swaps in a `NotificationXPro\...` class if it
  exists (`class_exists($pro_class)`) — when debugging, confirm which class
  (`NotificationX\...` vs `NotificationXPro\...`) is actually instantiated.
- No dedicated PHPUnit tests reference this integration; the only extension test,
  `tests/test-extension-factory.php`, does not name `learndash`/`learndash_inline`.
  (`notificationx-pro` ships no `tests/` suite of its own.)

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs: [eLearning](../types/elearning.md), [Inline](../types/inline.md)
