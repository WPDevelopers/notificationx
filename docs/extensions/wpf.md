# WPForms Extension (`modules_wpf`)

> Connects NotificationX to [WPForms](https://wordpress.org/plugins/wpforms-lite/) and turns
> form submissions into `form` (Contact Form) type notifications, plus (WPForms Pro only) a
> one-time backfill of a form's existing entries when a new notification is first saved.

## At a glance

| | |
|---|---|
| **Integration** | WPForms |
| **Directory** | [`includes/Extensions/WPF/`](../../includes/Extensions/WPF/) |
| **Module key(s) (`$module`)** | `modules_wpf` |
| **Feeds Types** | `form` (Contact Form — [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php), `$id = 'form'`). Shared with the other form-plugin integrations (CF7 `modules_cf7`, Ninja Forms `modules_njf`, Gravity Forms `modules_grvf`) |
| **Extension classes** | `WPForms.php` → registers `$id = 'wpf'` against `$types = 'form'` |
| **Depends on** | WPForms plugin — detected via `class_exists('\WPForms_Form_Handler')`; the historical-entries backfill additionally requires `wpforms()->is_pro()` |

## What it does

The user installs & activates WPForms, creates one or more forms (CPT `wpforms`),
and enables the "WPForms" module in NotificationX settings (`modules_wpf`). In the
NX builder they pick the `form` notification Type with source `wpf`, optionally
restrict to one **Select a Form** (`form_list` setting, populated via the
`nx_form_list` filter), and choose which submitted field to show (a tag picker
resolved through `restResponse()`).

The real event is WPForms' own `wpforms_process_complete` action, fired after a
submission passes validation. `WPForms::save_new_records()` is hooked there — it
walks the submitted `$fields`, tags the entry with `title` (form title), `id`
(form ID), `timestamp`, and a normalized `email` key for any `email`-type field,
then calls `Extension::save()` to insert an entry for every active `nx_id` post
configured with source `wpf`.

Separately, `WPForms::get_notification_ready()` runs once via the `saved_post`
hook the first time a notification using this source is saved. If WPForms Pro is
active, it pulls the form's *existing* stored entries (`wpforms()->entry->get_entries()`)
and backfills them as notification entries (skipping any already recorded via
`is_entry_exists()`). This step is a no-op on WPForms Lite, since
`wpforms()->is_pro()` gates it.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`WPForms.php`](../../includes/Extensions/WPF/WPForms.php) | `form` | `wpf` | No `get_data()` — real-time via `wpforms_process_complete` hook → `save_new_records()`; plus a one-time Pro-only backfill via `get_notification_ready()` → `wpforms()->entry->get_entries()`, hooked on `saved_post` |

`WPForms` does not implement `get_data()` (that pattern is used by pull/webhook-style
integrations such as Zapier/IFTTT — see e.g.
[`includes/Extensions/Zapier/ZapierConversions.php`](../../includes/Extensions/Zapier/ZapierConversions.php)).
Instead it relies on the base `Extension::save()` / `update_notification()` /
`update_notifications()` plumbing ([`Extension.php`](../../includes/Extensions/Extension.php))
to fan built entries out to enabled `nx_id` posts using this source.

## Data flow

1. Visitor submits a WPForms form → WPForms core validates and fires
   `wpforms_process_complete( $fields, $entry, $form_data, $entry_id )`.
2. `WPForms::save_new_records()` iterates `$fields` (skipping `checkbox` type),
   builds per-field keys `<field_id>_<type>` (plus `<field_id>_<subkey>_name` for
   `name`-type fields, and a normalized `email` key for `email`-type fields),
   and adds `title` (`$form_data['settings']['form_title']`), `timestamp`, and
   `id` (`$form_data['id']`).
3. Builds `entry_key = $this->key($form_data['id'])` (i.e. `wpf_<form_id>`) and
   calls `$this->save(['source' => 'wpf', 'entry_key' => $key, 'data' => $data])`.
4. `Extension::save()` looks up every enabled `nx_id` post whose source is `wpf`
   and calls `update_notification()` for each, which (after the `nx_can_entry_wpf`
   filter — see `can_entry()` below) inserts the row via
   `Entries::get_instance()->insert_entry()` into the entries table
   ([`Core/Database.php`](../../includes/Core/Database.php)).
5. `Extension::add_cron_job()` fires on `nx_saved_post_wpf` but `WPForms` does not
   set `$cron_schedule`, so no cron job is scheduled for this source.
6. On the *first* save of a notification using this source, `saved_post()` →
   `get_notification_ready($data)` additionally backfills existing WPForms Pro
   entries for the selected form (via `wpforms()->entry->get_entries()`) into the
   entries table in bulk, using `Extension::update_notifications()`.
7. From there the standard FrontEnd → REST → React pipeline
   (see [new-notification-type.md](../new-notification-type.md)) renders the
   entry using the `form` Type's themes/templates.

## Fields & settings

- **Select a Form** (`form_list`) — populated by `nx_form_list()`, which calls
  `GlobalFields::get_instance()->normalize_fields()` over `get_forms()`
  (`Helper::get_post_titles_by_search('wpforms', ...)`, prefixed with `wpf_`).
  Used both to render the field's options and, at save time, by `can_entry()` /
  `filter_by_form()` to restrict entries to the selected form (matched against
  `entry_key`).
- **Select a Tag** — tag/field picker for the notification content, resolved via
  `restResponse()`: given a `form_id`, it loads the form post and runs
  `keys_generator()` (JSON-decodes `post_content`'s `fields` array) to list
  available field tags as `tag_<field_id_and_type>` options (with extra
  `<id>_<part>_name` entries for `name`-type fields split by their `format`);
  given an `inputValue` instead, it searches form titles for the async-select
  dropdown.
- Uses `GlobalFields::normalize_fields()` for both listings — no WPForms-specific
  field-registry additions beyond that.

## Dependency & detection

- **Required plugin:** WPForms (`wpforms-lite`, or WPForms Pro).
- **Detection:** `$class = '\WPForms_Form_Handler'` on the `Extension` base —
  checked in `is_active()` / `class_exists()` via `class_exists('\WPForms_Form_Handler')`.
- **When absent:** the module still registers in Settings, but `is_active(false)`
  returns `false`, so `init()`/`admin_actions()`/`public_actions()`/`init_fields()`
  never run (no hook on `wpforms_process_complete`, no builder fields).
  `source_error_message()` additionally injects an admin error message ("You have
  to install WP Forms plugin first.") with a link to install it, scoped via
  `Rules::is('source', 'wpf')`.
- **Historical-entries backfill is Pro-gated separately:** `get_notification_ready()`
  checks `wpforms()->is_pro()` and returns an empty array (no backfill) when the
  active WPForms install is Lite, even though live submissions via
  `wpforms_process_complete` still work on Lite.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/WPF/WPForms.php`](../../includes/Extensions/WPF/WPForms.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type it feeds | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'wpf' => 'NotificationX\Extensions\WPF\WPForms'`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (`normalize_fields()`) |

## Testing notes & gotchas

- Live submissions write immediately via `wpforms_process_complete` — verify by
  submitting a real WPForms form and checking the entries table, not by waiting
  on a schedule.
- The historical backfill (`get_notification_ready()`) only runs once, on the
  `saved_post` hook when a notification is first saved with source `wpf`, and
  only if WPForms Pro is active (`wpforms()->is_pro()`); on WPForms Lite it
  silently returns `[]` with no backfilled entries — only new live submissions
  will appear.
- `is_entry_exists()` de-dupes backfilled entries against already-stored ones by
  comparing `entry__id` (the WPForms entry ID), so re-saving the notification
  shouldn't duplicate historical rows.
- Any field of type `email` gets copied into a normalized `email` data key —
  relevant if a form's email field is misconfigured with a different type.
- `can_entry()` / `filter_by_form()` only filter by `form_list`; if that setting
  is empty, entries from **all** WPForms forms pass through to that notification.
- `keys_generator()` assumes `post_content` decodes to JSON with a `fields` key
  (WPForms' native form-builder schema); forms with malformed/missing content
  will yield no tag options.
- _TODO: verify_ whether `notificationx-pro` adds any WPForms-specific
  extensions/fields on top of this free-plugin class (out of scope for this repo
  per CLAUDE.md).

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
