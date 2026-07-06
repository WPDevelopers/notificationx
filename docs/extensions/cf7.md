# Contact Form 7 Extension (`modules_cf7`)

> Connects NotificationX to [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) and
> turns real-time form submissions into `form` (Contact Form) type notifications — no polling,
> the entry is written the moment a visitor's message is mailed.

## At a glance

| | |
|---|---|
| **Integration** | Contact Form 7 (CF7) |
| **Directory** | [`includes/Extensions/CF7/`](../../includes/Extensions/CF7/) |
| **Module key(s) (`$module`)** | `modules_cf7` |
| **Feeds Types** | `form` (Contact Form — [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php), `$id = 'form'`). This Type is shared with the other form-plugin integrations (WPForms `modules_wpf`, Ninja Forms `modules_njf`, Gravity Forms `modules_grvf`) |
| **Extension classes** | `CF7.php` → registers `$id = 'cf7'` against `$types = 'form'` |
| **Depends on** | Contact Form 7 plugin — detected via `class_exists('WPCF7_ContactForm')` |

## What it does

The user installs & activates Contact Form 7, creates one or more forms (CPT
`wpcf7_contact_form`), and enables the "Contact Form 7" module in NotificationX
settings (`modules_cf7`). In the NX builder they pick the `form` notification Type
with source `cf7`, optionally restrict to one **Select a Form** (`form_list`
setting), and choose which submitted field to show (via the "Select a Tag" /
`nx_form_list` field, populated from `GlobalFields`).

The real event is CF7's own `wpcf7_mail_sent` action, which only fires after a
submission passes validation and the mail is sent. `CF7::save_new_records()` is
hooked there — it reads the tag values straight out of `WPCF7_Submission`, tags
the entry with `title`, `id` (form ID), `timestamp`, and any field whose tag name
contains `email` (copied into a normalized `email` key), then calls
`Extension::save()` to insert an entry for every active `nx_id` post configured
with source `cf7`.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| [`CF7.php`](../../includes/Extensions/CF7/CF7.php) | `form` | `cf7` | No `get_data()` — real-time via `wpcf7_mail_sent` hook → `save_new_records()`, which builds the entry from `WPCF7_Submission::get_instance()->get_posted_data()` for each of the form's scanned tags |

`CF7` does not implement `get_data()` (that pattern is used by pull/webhook-style
integrations such as Zapier/IFTTT — see e.g.
[`includes/Extensions/Zapier/ZapierConversions.php`](../../includes/Extensions/Zapier/ZapierConversions.php)).
Instead it relies on the base `Extension::save()` / `update_notification()` /
`update_notifications()` plumbing ([`Extension.php`](../../includes/Extensions/Extension.php))
which fans a single built entry array out to every enabled `nx_id` post using
this source.

## Data flow

1. Visitor submits a CF7 form → CF7 core validates and mails it → fires
   `wpcf7_mail_sent( $contact_form )`.
2. `CF7::save_new_records( $contact_form )` scans the form's tags
   (`$contact_form->scan_form_tags()`), pulls each tag's submitted value from
   `WPCF7_Submission::get_instance()->get_posted_data()`, and assembles
   `$data` (`title`, `id`, `timestamp`, per-tag fields, `email` alias).
3. Builds `entry_key = $this->key($contact_form->id())` (i.e. `cf7_<form_id>`)
   and calls `$this->save(['source' => 'cf7', 'entry_key' => $key, 'data' => $data])`.
4. `Extension::save()` looks up every enabled `nx_id` post whose source is `cf7`
   and calls `update_notification()` for each, which (after the
   `nx_can_entry_cf7` filter — see `can_entry()` below) inserts the row via
   `Entries::get_instance()->insert_entry()` into the entries table
   ([`Core/Database.php`](../../includes/Core/Database.php)).
5. `Extension::add_cron_job()` fires on `nx_saved_post_cf7` but `CF7` does not
   set `$cron_schedule`, so no cron job is scheduled for this source.
6. From there the standard FrontEnd → REST → React pipeline
   (see [new-notification-type.md](../development/adding-a-notification-type.md)) renders the
   entry using the `form` Type's themes/templates.

## Fields & settings

- **Select a Form** (`form_list`) — populated by `nx_form_list()`, which calls
  `GlobalFields::get_instance()->normalize_fields()` over `get_forms()`
  (`Helper::get_post_titles_by_search('wpcf7_contact_form', ...)`, prefixed with
  `cf7_`). Used both to render the field's options and, at save time, by
  `can_entry()` to reject entries whose `entry_key` (form ID) doesn't match the
  selected form.
- **Select a Tag** — tag/field picker for the notification content, resolved via
  `restResponse()`: given a `form_id`, it loads the form post, runs
  `keys_generator()` (regex over the CF7 shortcode content, stripping the
  `[submit ...]` tag) to list available field tags as `tag_<name>` options; given
  an `inputValue` instead, it searches form titles for the async-select dropdown.
- Uses `GlobalFields::normalize_fields()` for both listings — no CF7-specific
  field-registry additions beyond that.

## Dependency & detection

- **Required plugin:** Contact Form 7 (`contact-form-7`).
- **Detection:** `$class = 'WPCF7_ContactForm'` on the `Extension` base — checked
  in `is_active()` / `class_exists()` via `class_exists('WPCF7_ContactForm')`.
- **When absent:** the module still registers in Settings, but `is_active(false)`
  returns `false`, so `init()`/`admin_actions()`/`public_actions()`/`init_fields()`
  never run (no hook on `wpcf7_mail_sent`, no builder fields). `source_error_message()`
  additionally injects an admin error message ("You have to install Contact Form 7
  plugin first.") with a link to install it, scoped via `Rules::is('source', 'cf7')`.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/CF7/CF7.php`](../../includes/Extensions/CF7/CF7.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type it feeds | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'cf7' => 'NotificationX\Extensions\CF7\CF7'`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (`normalize_fields()`, `common_name_fields()`) |

## Testing notes & gotchas

- Because the write path is a live hook (`wpcf7_mail_sent`), there is no
  cron/polling to account for — verify by submitting a real CF7 form and
  checking the entries table, not by waiting on a schedule.
- `keys_generator()` parses tags with a regex assuming a literal `submit` tag
  exists in the form's shortcode content (`substr(..., strpos(..., 'submit') - 2)`);
  forms without a `[submit]` tag should be spot-checked.
- Any tag whose name contains the substring `email` (case-insensitive) gets
  copied into a normalized `email` data key — relevant if a form uses a
  non-standard tag name containing "email" for something else.
- `can_entry()` only filters by `form_list`; if that setting is empty, entries
  from **all** CF7 forms pass through to that notification.
- _TODO: verify_ whether `notificationx-pro` adds any CF7-specific extensions/fields
  on top of this free-plugin class (out of scope for this repo per CLAUDE.md).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
