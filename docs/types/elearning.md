# eLearning Notification Type (`elearning`)

> Shows a live/recent feed of course enrollments (e.g. "Someone just enrolled in
> PHP Beginners") to build social proof for online courses sold via Tutor LMS,
> LearnDash, or LearnPress.

## At a glance

| | |
|---|---|
| **Type ID** | `elearning` |
| **Class** | [`includes/Types/ELearning.php`](../../includes/Types/ELearning.php) |
| **Trait** | none — only the generic [`GetInstance`](../../includes/GetInstance.php) trait is used |
| **Priority** | `10` |
| **Default source** | `tutor` |
| **Default theme** | `elearning_theme-one` |
| **Link type** | `course_page` (registered via `nx_link_types`; default per-type link target, not a `link_type` field default) |
| **Module gate (`$module`)** | Type declares `['modules_tutor', 'modules_learndash']` — see [Testing notes](#testing-notes--gotchas) for a discrepancy with the LearnPress extension |
| **Compatible extensions** | [`Tutor`](../../includes/Extensions/Tutor/Tutor.php) (`$id = 'tutor'`, `$module = 'modules_tutor'`), [`LearnDash`](../../includes/Extensions/LearnDash/LearnDash.php) (`$id = 'learndash'`, `$module = 'modules_learndash'`), [`LearnPress`](../../includes/Extensions/LearnPress/LearnPress.php) (`$id = 'learnpress'`, `$module = 'modules_learnpress'`) — all three declare `$types = 'elearning'` |

## What it does

Renders a social-proof popup/toast telling visitors that someone recently enrolled
in a course — e.g. "Someone just enrolled" + course title + a relative time. Data
comes from one of three eLearning plugins (Tutor LMS, LearnDash, or LearnPress),
selected by the notification's `source` setting. Each extension listens to its own
plugin's enrollment/order hooks (e.g. Tutor's `tutor_after_enroll`, WooCommerce/EDD
order-completion hooks when a course is sold as a paid product) and writes an entry
via `Extension::update_notification()` / `update_notifications()`.

The Content tab additionally lets the admin restrict the feed to specific courses
via `ld_product_control` (`All` vs `By Course`) and an async multi-select
`ld_course_list` (option label is generic/legacy — it is used across all three
eLearning sources, not just LearnDash) populated through the
`/notificationx/v1/get-data` REST endpoint filtered by `nx_elearning_course_list`.

## Data flow

This is a **standard "entries" type** (not config-only like Popup/Exit Intent), so
it does not get a special branch in `FrontEnd.php` — it flows through the generic
`active` notifications bucket that `get_notifications_ids()` / `get_notifications_data()`
already handle for all non-popup/non-bar types:

`Tutor::do_enroll()` / `LearnDash`/`LearnPress` order hooks → `Extension::update_notification()`
(writes to the entries table) → `FrontEnd::get_notifications_ids()` (`active` bucket,
keyed by `nx_id`) → `FrontEnd::get_notifications_data()` (`{ post, entries: [...] }` shape)
→ REST response → `normalize()` in `utils.ts` (per
[docs/new-notification-type.md](../development/adding-a-notification-type.md#choosing-normalize-vs-normalizepressbar),
multi-entry types use `normalize`, not `normalizePressBar`) → React runtime renders
the standard `<Notification>` component (no dedicated eLearning React component).

Link handling: `FrontEnd::link_url()` keeps `entry['link']` (set by the extension to
`get_permalink($course_id)`) unless `post['link_type']` is empty or `'none'` — the
`course_page` link type registered by `ELearning::link_types()` allows the link
through.

`ELearning::conversion_data()` (hooked to `nx_filtered_entry_elearning`) strips tags
and decodes HTML entities from `course_title` before it reaches the front end.

## Fields & settings schema

Declared in `ELearning::content_fields()` (hooked via `init_fields()` →
`nx_content_fields`):

- `ld_product_control` — `select`, options `none` (All) / `ld_course` (By Course).
  Default `none`. Gated to `Rules::is('type', 'elearning')`.
- `ld_course_list` — `select-async`, multi-select, shown only when
  `ld_product_control == 'ld_course'`. Fetches options from
  `/notificationx/v1/get-data` with `field: 'ld_course_list'`, filterable via
  `apply_filters('nx_elearning_course_list', ...)`.

`ELearning::link_types()` (hooked via `init_fields()` → `nx_link_types`) adds the
`course_page` option to the global Link Type field, gated to this type.

No dedicated `Traits/ELearning.php` exists — extra fields beyond the above two are
either global (`GlobalFields`) or belong to the individual extensions
(Tutor/LearnDash/LearnPress), not the Type class itself.

## Themes / templates

Free/basic themes (`$themes`), all template-driven from `elearning_template_new`
unless noted:

| Theme key | Notes |
|---|---|
| `theme-one` | Default theme (`elearning_theme-one`). Circle image shape. Template: "Someone just enrolled" |
| `theme-two` | Circle image shape. Template: "Someone recently enrolled" |
| `theme-three` | Square image shape. Template: "Someone recently enrolled" |
| `theme-four` | `is_pro: true`. Circle image shape |
| `theme-five` | `is_pro: true`. Circle image shape |
| `conv-theme-six` | `is_pro: true`. Circle image shape |
| `conv-theme-seven` | `is_pro: true`. Rounded image shape. Maps to `elearning_template_sales_count` |
| `conv-theme-eight` | `is_pro: true`. Circle image shape. Maps to `elearning_template_sales_count` |
| `conv-theme-nine` | `is_pro: true`. Circle image shape. Maps to `elearning_template_sales_count` |
| `maps_theme` | `is_pro: true`. Square image shape, uses `show_notification_image: 'maps_image'` |

Responsive themes (`$res_themes`), all `is_pro: true`:

| Res-theme key | `_template` |
|---|---|
| `res-theme-one` … `res-theme-four`, `elearning-res-theme-five` | `elearning_template_new` |
| `elearning-res-theme-six`, `elearning-res-theme-ten` | `maps_template_new` |
| `elearning-res-theme-seven`, `elearning-res-theme-eight`, `res-theme-nine` | `elearning_template_sales_count` |

Templates (`$templates`):

- `elearning_template_new` — `first_param` from `GlobalFields::common_name_fields()`,
  `third_param: tag_course_title`, `fourth_param: tag_time`. Used by
  `elearning_theme-one` through `-five`.
- `elearning_template_sales_count` — same `first_param`/`third_param`, `fourth_param`
  commented out (no time tag). Used by `conv-theme-seven/eight/nine`.

**Verified:** `maps_template_new`, referenced by two res-themes above, is not defined in
`ELearning::$templates`; it is registered once, by the Pro Maps feature
(`notificationx-pro/includes/Features/Maps.php`) — the single place `maps_template_new` is
defined as a template key — and shared across all the Types that reference it
(`WooCommerceSales`, `Comments`, `EmailSubscription`, `Donations`, `Conversions`, etc.).

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/ELearning.php`](../../includes/Types/ELearning.php) |
| Base class | [`includes/Types/Types.php`](../../includes/Types/Types.php) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'elearning' => 'NotificationX\Types\ELearning'`, class name inside the file is `TypeFactory`) |
| Extensions | [`includes/Extensions/Tutor/Tutor.php`](../../includes/Extensions/Tutor/Tutor.php), [`includes/Extensions/LearnDash/LearnDash.php`](../../includes/Extensions/LearnDash/LearnDash.php), [`includes/Extensions/LearnDash/LearnDashInline.php`](../../includes/Extensions/LearnDash/LearnDashInline.php), [`includes/Extensions/LearnPress/LearnPress.php`](../../includes/Extensions/LearnPress/LearnPress.php) |
| Extension base | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) — `register_module()` / `register_types()` is what actually gates Type registration on the module setting, per-Extension (see gotcha below) |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) — generic `active` bucket, no eLearning-specific branch |
| Frontend runtime | Generic renderer [`nxdev/notificationx/frontend/themes/Theme.tsx`](../../nxdev/notificationx/frontend/themes/Theme.tsx) — no eLearning-specific React component (only `announcements/` has per-theme `.tsx` files) |

## Dependencies

One of: [Tutor LMS](https://wordpress.org/plugins/tutor/), [LearnDash](https://www.learndash.com/),
or [LearnPress](https://wordpress.org/plugins/learnpress/) — plus optionally
WooCommerce or Easy Digital Downloads if the course is monetized through one of
those (Tutor/LearnPress extensions branch on `monetize_by` to listen to the right
order-completion hooks instead of the plugin's own free-enrollment hook).

## Testing notes & gotchas

- **Module-gate mismatch**: `ELearning::$module` only lists `modules_tutor` and
  `modules_learndash` — it omits `modules_learnpress`, even though the `LearnPress`
  extension (`$module = 'modules_learnpress'`) also declares `$types = 'elearning'`
  and can register this Type. In practice, Type registration is driven by each
  *Extension's* `register_module()`/`register_types()` call (see
  [`Extension.php`](../../includes/Extensions/Extension.php) lines ~73-78), not by
  `Types::$module` directly, so this appears to be a documentation/declaration gap
  on the Type class rather than a functional bug. **Verified:** `Types::$module` is not
  read anywhere in the free or Pro plugin (only *Extension* `->module` is read for gating),
  so the missing key has no runtime effect.
- `ld_product_control` / `ld_course_list` field names are LearnDash-flavored
  (`ld_*`) but are shared across all three sources (Tutor, LearnDash, LearnPress) —
  don't assume they're LearnDash-only when reading the field list.
- Monetization branching (Tutor, LearnPress): if the course is sold via WooCommerce
  or EDD, the extension hooks the store's order-status/completion action instead of
  the LMS's native "free enroll" action — verify which path is active before
  debugging a missing notification (check `tutils()->get_option('monetize_by')` /
  the LearnPress equivalent).
- `conversion_data()` (via `nx_filtered_entry_elearning`) only sanitizes
  `course_title`; other fields pass through unfiltered.
- No dedicated tests for this type were found under `tests/`.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
