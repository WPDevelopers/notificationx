# Fluent Forms Extension (`modules_fluentform`)

> Connects NotificationX to [Fluent Forms](https://wordpress.org/plugins/fluentform/) and
> surfaces new form submissions as Contact Form (`form`) notifications, in real time on
> submission and backfilled from existing entries when a notification is first created.

## At a glance

| | |
|---|---|
| **Integration** | Fluent Forms |
| **Directory** | [`includes/Extensions/FluentForm/`](../../includes/Extensions/FluentForm/) |
| **Module key(s) (`$module`)** | `modules_fluentform` |
| **Feeds Types** | `form` (Contact Form — [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php)) |
| **Extension classes** | `FluentForm.php` → pairs with type `form`, `$id = 'fluentform'` |
| **Depends on** | Fluent Forms plugin; detected via `defined('FLUENTFORM_VERSION')` |

## What it does

The site owner installs and activates Fluent Forms, then creates a NotificationX
notification of type **Contact Form**, choosing "Fluent Forms" (`fluentform`) as the
source and picking one of their published forms via the "Select a Form" field. From
that point on:

- Every new Fluent Forms submission (`fluentform_submission_inserted` action) is saved
  immediately as a new NotificationX entry — no polling.
- When the notification is first saved in the builder, existing/past submissions for
  the selected form (within the configured display window/limit) are also pulled in as
  a one-time backfill so the notification isn't empty on creation.
- The builder's "Select a Form" and merge-tag ("first name", "email", etc.) lists are
  populated live from the site's Fluent Forms data via REST/AJAX.

## Extension classes & pairings

Only one class lives in this directory.

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| [`FluentForm.php`](../../includes/Extensions/FluentForm/FluentForm.php) | `form` (`$types = 'form'`) | `fluentform` | `{$wpdb->prefix}fluentform_forms` / `fluentform_submissions` / `fluentform_entry_details` tables (direct `$wpdb` / `wpFluent()` queries), plus Fluent Forms' own `FormFieldsParser` / `FormDataParser` classes to decode submission values |

`FluentForm` does **not** implement a `get_data()` method (there is no such abstract
method on the base `Extension` class — see [Data flow](#data-flow) below); it is driven
by a real-time WP action plus an on-save backfill instead.

## Data flow

Two independent paths populate entries for a `fluentform` notification:

1. **Real-time, per submission** — `init()` hooks
   `add_action('fluentform_submission_inserted', [$this, 'save_new_records'], 10, 3)`.
   `save_new_records()` looks up the just-inserted row from `fluentform_submissions`,
   parses its field values with Fluent Forms' `FormFieldsParser::getEntryInputs()` and
   `FormDataParser::parseFormEntry()`, builds a flat `$data` array (ip, timestamp,
   title, submission_id, one key per form field), and calls the base `Extension::save()`
   with `source => 'fluentform'` and `entry_key => "fluentform_{$form_id}"`.

2. **One-time backfill on notification creation** — the base `Extension::init()` wires
   `add_filter("nx_saved_post_{$this->id}", [$this, 'saved_post'], 10, 3)` whenever the
   extension class defines a `saved_post()` method (it does here). `saved_post()` calls
   `get_notification_ready($data)`, which reads the selected form from
   `data['__form_list']['value']`, queries `fluentform_submissions` for that form within
   the notification's configured time window/limit (`display_from`/`display_last`,
   version-aware `whereBetween` call for Fluent Forms >= 5.0.0 vs older), parses each
   row the same way as above, skips submissions already saved
   (`is_submission_exists()` checks `Entries::get_entries($nx_id)` for a matching
   `submission_id`), and pushes the rest through `Extension::update_notifications()`.

Both paths write into the shared `Entries`/notifications-entries store
([`includes/Admin/Entries.php`](../../includes/Admin/Entries.php)) that the standard
NotificationX FrontEnd/REST/React pipeline (see
[new-notification-type.md](../development/adding-a-notification-type.md)) reads from for rendering —
this extension only supplies data, it doesn't add a new Type or a new frontend
delivery path.

Two extra filters restrict entries to the form chosen in the builder:
- `nx_can_entry_{$this->id}` → `can_entry()` — rejects an entry if its `entry_key`
  (form id) doesn't match the selected `form_list` setting.
- `nx_filtered_data_{$this->id}` → `filter_by_form()` — same filtering applied to a
  data array in the public-facing fetch path.

## Fields & settings

- **`form_list`** ("Select a Form") is not defined in this extension — it's a
  Type-level shared field owned by
  [`Types/ContactForm.php`](../../includes/Types/ContactForm.php)
  (`add_form_fields()`), populated by merging every registered form-extension's
  options together through the `nx_form_list` filter. `FluentForm::init_fields()` hooks
  `add_filter('nx_form_list', [$this, 'nx_form_list'], 9)`, which calls
  `get_forms()` (queries the `fluentform_forms` table for `status = published`, limit
  10) and normalizes them with
  `GlobalFields::get_instance()->normalize_fields()`. CF7, WPForms (`WPF`), and
  Ninja Forms (`NJF`) do the equivalent for their own sources on the same filter.
- **`restResponse($args)`** backs the field's async search (`inputValue` → `LIKE` query
  against `fluentform_forms.title`) and also, given `form_id`, returns the list of
  usable merge tags for that form via `keys_generator()` →
  `fluentFormApi('forms')->form($form_id)->inputs()` →
  `extractVisibleFields()` (recursively walks repeater/nested fields, prefixing tag
  names with `tag_`, and specially prefixing `first_name`/`last_name` with `tag__`).
- No FluentForm-specific fields are added to the Design/Customize tabs beyond what
  `ContactForm` Type and `Extension` base already provide; `init_extension()` only sets
  `$this->title` / `$this->module_title` to "Fluent Forms".

## Dependency & detection

- **Required plugin:** [Fluent Forms](https://wordpress.org/plugins/fluentform/) (free).
- **Detection:** `$constant = 'FLUENTFORM_VERSION'` — the base `Extension::class_exists()`
  falls through to `defined('FLUENTFORM_VERSION')` since no `$class` or `$function`
  property is set on this extension.
- **When absent:** `get_forms()` and `restResponse()` short-circuit and return `[]`
  when `class_exists()` is false. `source_error_message()` is auto-hooked onto the
  `source_error_message` filter by the base `Extension::__init_fields()` (it detects
  the method via `method_exists($this, 'source_error_message')`) and surfaces an admin
  error prompting the user to install Fluent Forms, shown only when the `fluentform`
  source is selected (`Rules::is('source', $this->id)`). The realtime hook
  (`fluentform_submission_inserted`) simply never fires if Fluent Forms isn't loaded, so
  no data is captured — nothing crashes, the source is just inert.
- Module gating: `$module = 'modules_fluentform'` is registered dynamically via
  `Extension::register_module()` → `Modules::add()`; if this module is toggled off in
  NotificationX settings the extension isn't loaded at all. Note the **Type**-level
  `ContactForm::$module` list (`modules_cf7`, `modules_wpf`, `modules_njf`,
  `modules_grvf`) does not itself include `modules_fluentform`. This does not block the
  FluentForm integration: the extension registers its own `modules_fluentform`
  module independently via `Extension::register_module()` → `Modules::add()`, and
  its gating runs off that module's enabled state — not off the Type's `$module`
  array. The omission looks like an oversight in the Type list but has no functional
  effect on FluentForm's own module gate.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/FluentForm/FluentForm.php`](../../includes/Extensions/FluentForm/FluentForm.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'fluentform' => 'NotificationX\Extensions\FluentForm\FluentForm'`) |
| Paired Type | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) (`$id = 'form'`, owns the shared `form_list` field) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Entries storage | [`includes/Admin/Entries.php`](../../includes/Admin/Entries.php) |

## Testing notes & gotchas

- Verify both data paths independently: (1) submit a Fluent Forms form live and confirm
  a new entry appears without reloading the builder (`save_new_records`), and (2)
  create a brand-new `form`/`fluentform` notification against a form that already has
  submissions and confirm the backfill (`get_notification_ready`) picks up existing
  rows within the configured `display_from`/`display_last` window and doesn't
  duplicate on repeat saves (`is_submission_exists`).
- `get_notification_ready()` branches on `version_compare(FLUENTFORM_VERSION, '5.0.0', '>=')`
  for the `whereBetween()` call signature — check against both an old and new Fluent
  Forms version if touching this method.
- Repeater/nested fields and `first_name`/`last_name` sub-fields get special-cased key
  prefixing (`_first_name`/`_last_name` in entry data, `tag__first_name` in merge tags)
  in more than one place (`save_new_records`, `get_notification_ready`,
  `extractVisibleFields`) — keep these in sync if changing field-name handling.
- No dedicated tests cover this extension. The free `tests/` suite is limited to
  factory/type/REST/migration smoke tests and `notificationx-pro/tests/` only adds
  type/engine/smoke tests — neither references `FluentForm` (Fluent Forms has no Pro
  class either).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Contact Form (`form`) type](../types/form.md)
