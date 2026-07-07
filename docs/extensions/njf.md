# Ninja Forms Extension (`modules_njf`)

> Connects NotificationX to [Ninja Forms](https://wordpress.org/plugins/ninja-forms/) and
> turns form submissions into `form` (Contact Form) type notifications, both in real time
> (new submissions) and, at notification-save time, backfilled from existing submissions.

## At a glance

| | |
|---|---|
| **Integration** | Ninja Forms |
| **Directory** | [`includes/Extensions/NJF/`](../../includes/Extensions/NJF/) |
| **Module key(s) (`$module`)** | `modules_njf` |
| **Feeds Types** | `form` (Contact Form — [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php), `$id = 'form'`). This Type is shared with the other form-plugin integrations (CF7 `modules_cf7`, WPForms `modules_wpf`, Gravity Forms `modules_grvf`) |
| **Extension classes** | `NinjaForms.php` → registers `$id = 'njf'` against `$types = 'form'` |
| **Depends on** | Ninja Forms plugin — detected via `class_exists('Ninja_Forms')` (`$class = 'Ninja_Forms'` on the base `Extension`) |

## What it does

The user installs & activates Ninja Forms, builds one or more forms (stored in the
`{prefix}nf3_forms` table), and enables the "Ninja Forms" module in NotificationX settings
(`modules_njf`). In the NX builder they pick the `form` notification Type with source `njf`,
optionally restrict to one **Select a Form** (`form_list` setting, populated via the
`nx_form_list` filter), and choose which submitted field to show.

Two separate paths populate entries:
1. **Real time** — `NinjaForms::save_new_records()` is hooked on Ninja Forms' own
   `ninja_forms_after_submission` action and fires the moment any form on the site is
   submitted, regardless of which notification is being built.
2. **Backfill on save** — when a notification using this source is saved, `saved_post()`
   clears its existing entries and calls `get_notification_ready()`, which pulls the
   *selected* form's existing submissions (via Ninja Forms' own `Ninja_Forms()->form($id)`
   API), filtered to a configurable "display from last N days" window (default 30).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| [`NinjaForms.php`](../../includes/Extensions/NJF/NinjaForms.php) | `form` | `njf` | No `get_data()`. Real-time: `save_new_records()` hooked on `ninja_forms_after_submission`. Backfill: `get_notification_ready()` → `get_submissions()`, which reads `Ninja_Forms()->form($form_id)->get_subs()` / `->get_fields()` |

`NinjaForms` does not implement `get_data()` (that pattern is used by pull/webhook-style
integrations such as Zapier — see e.g.
[`includes/Extensions/Zapier/ZapierConversions.php`](../../includes/Extensions/Zapier/ZapierConversions.php)).
Instead it relies on the base `Extension::save()` / `update_notification()` /
`update_notifications()` plumbing ([`Extension.php`](../../includes/Extensions/Extension.php))
to fan built entries out to enabled `nx_id` posts using this source.

## Data flow

**Real-time submission:**
1. Visitor submits any Ninja Forms form → Ninja Forms fires
   `ninja_forms_after_submission( $form_data )`.
2. `NinjaForms::save_new_records( $form_data )` iterates `$form_data['fields']`, renaming
   each field key via `Helper::rename_contactform_key_names()`, and assembles `$data`
   (per-field values, `title` from `$form_data['settings']['title']`, `timestamp`).
3. Builds `entry_key = $this->key($form_data['form_id'])` (i.e. `njf_<form_id>`) and calls
   `$this->save(['source' => 'njf', 'entry_key' => $key, 'data' => $data])`.
4. `Extension::save()` looks up every enabled `nx_id` post whose source is `njf` and calls
   `update_notification()` for each, which (after the `nx_can_entry_njf` filter — see
   `can_entry()` below) inserts the row via `Entries::get_instance()->insert_entry()`
   ([`Core/Database.php`](../../includes/Core/Database.php)).

**Backfill when a notification is saved** (hooked as `nx_saved_post_njf` via
`Extension::init()`, since `method_exists($this, 'saved_post')`):
1. `saved_post($post, $data, $nx_id)` calls `delete_notification(null, $nx_id)` then
   `get_notification_ready($data)`.
2. `get_notification_ready()` reads the selected form ID out of
   `$data['__form_list']['value']` / `$data['form_list']['value']` (format
   `njf_<form_id>`, split on `_`), and calls `get_submissions($form_id, $data)`.
3. `get_submissions()` loads `Ninja_Forms()->form($form_id)->get_subs()` and
   `->get_fields()`, skips submissions older than `display_from` days (default 30,
   from `$data['display_from']`), builds a labeled value array per submission
   (with special handling for `repeater` field types via
   `Ninja_Forms()->fieldsetRepeater`), and returns them.
4. Each submission becomes an entry (`source => 'njf'`, `entry_key => njf_<form_id>`) and
   the batch is passed to `Extension::update_notifications()`, which applies the
   `nx_can_entry_njf` filter per entry and bulk-inserts via
   `Entries::get_instance()->insert_entries()`.
5. `Extension::add_cron_job()` fires on `nx_saved_post_njf` but `NinjaForms` does not set
   `$cron_schedule`, so no cron job is scheduled for this source.
6. From there the standard FrontEnd → REST → React pipeline
   (see [new-notification-type.md](../development/adding-a-notification-type.md)) renders the entry using
   the `form` Type's themes/templates.

## Fields & settings

- **Select a Form** (`form_list`) — populated by `nx_form_list()`, which merges
  `get_forms()` (a direct query of `{prefix}nf3_forms`, keys prefixed `njf_`) through
  `GlobalFields::get_instance()->normalize_fields()`. Used both to render the field's
  options and, at save time, by `can_entry()` to reject entries whose `entry_key`
  (form ID) doesn't match the selected form.
- **Select a Tag** — field/tag picker for notification content, resolved via
  `restResponse()`: given a `form_id`, it queries `{prefix}nf3_form_meta` for the
  `formContentData` meta value, unserializes it, and runs `keys_generator()` (which
  walks each field — including nested `cells[0].fields[0]` rows — through
  `Helper::filter_contactform_key_names()` / `Helper::rename_contactform_key_names()`)
  to list available field tags as `tag_<name>` options; given an `inputValue` instead,
  it does a `LIKE` search over form titles for the async-select dropdown.
- Uses `GlobalFields::normalize_fields()` for both listings — no NJF-specific
  field-registry additions beyond that.
- `display_from` (days) — read in `get_submissions()` to cap how far back the backfill
  looks (default 30 days); passed in via `$data` at save time.

## Dependency & detection

- **Required plugin:** Ninja Forms (`ninja-forms`).
- **Detection:** `$class = 'Ninja_Forms'` on the `Extension` base — checked in
  `is_active()` / `class_exists()` via `class_exists('Ninja_Forms')`. `get_forms()` and
  `restResponse()` additionally guard with their own `class_exists('Ninja_Forms')` check.
- **When absent:** the module still registers in Settings, but `is_active(false)` returns
  `false`, so `init()` / `admin_actions()` / `public_actions()` / `init_fields()` never
  run (no hook on `ninja_forms_after_submission`, no builder fields). `source_error_message()`
  additionally injects an admin error message ("You have to install Ninja Forms plugin
  first.") with a link to install it, scoped via `Rules::is('source', 'njf')`.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/NJF/NinjaForms.php`](../../includes/Extensions/NJF/NinjaForms.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type it feeds | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'njf' => 'NotificationX\Extensions\NJF\NinjaForms'`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (`normalize_fields()`) |
| Shared helpers | [`includes/Core/Helper.php`](../../includes/Core/Helper.php) (`filter_contactform_key_names()`, `rename_contactform_key_names()`) |

## Testing notes & gotchas

- There are two independent write paths (live hook + save-time backfill) — verify both:
  submit a real Ninja Forms form and check the entries table updates immediately, *and*
  re-save a notification and confirm existing submissions within the `display_from`
  window are pulled in.
- `get_submissions()` has non-trivial repeater-field handling (`fieldsetRepeater`); forms
  using Ninja Forms' repeater/fieldset addon should be spot-checked separately from plain
  forms.
- `can_entry()` only filters by `form_list`; if that setting is empty, entries from
  **all** Ninja Forms forms pass through to that notification.
- `keys_generator()` depends on the raw serialized `formContentData` post meta shape;
  changes to how Ninja Forms stores form structure could break tag extraction.
- `notificationx-pro` adds **no dedicated Ninja Forms extension class** (there is no
  `notificationx-pro/includes/Extensions/NJF/` directory) and no NJF-specific field
  registry. The only Pro reference to `njf` is its Google Maps feature
  (`notificationx-pro/includes/Features/Maps.php`) listing `njf` among sources
  eligible for the shared map field. So the free-plugin class above is the whole
  Ninja Forms integration.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Contact Form 7 Extension](cf7.md) — sibling integration on the same `form` Type
