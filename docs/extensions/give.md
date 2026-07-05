# GiveWP Extension (`modules_give`)

> Connects NotificationX to [GiveWP](https://wordpress.org/plugins/give/) donations,
> pulling completed Give payments to drive Donation (`donation`) notifications
> ("John D. recently donated for Fundraising Camp").

## At a glance

| | |
|---|---|
| **Integration** | GiveWP |
| **Directory** | [`includes/Extensions/Give/`](../../includes/Extensions/Give/) |
| **Module key(s) (`$module`)** | `modules_give` |
| **Feeds Types** | `donation` (`Give`) |
| **Extension classes** | `Give.php` (class `Give`) → id `give`, type `donation` |
| **Depends on** | GiveWP plugin — detected via `class_exists('\Give')` |

## What it does

From the user's perspective: install & activate GiveWP, then enable the "GiveWP"
module inside NotificationX. This makes a Donation notification (type `donation`,
source `give`) available, showing a popup like "Someone recently donated for
Fundraising Camp".

Real events that drive data:
- `give_complete_donation` action (hooked in `Give::public_actions()`) — fires when
  a Give donation completes; `Give::save_new_donation($payment_id)` queries that
  single payment via `\Give_Payments_Query` and immediately upserts one notification
  entry (near-real-time).
- Manual/initial backfill — when a `give`-sourced notification post is saved,
  `Give::saved_post()` deletes existing entries for that `nx_id`
  (`delete_notification()`) then `get_notification_ready($data)` →
  `get_give_donations($data)` (bulk `\Give_Payments_Query` bounded by the
  notification's `display_from` days setting) → `update_notifications()`
  (bulk insert, inherited from `Extension`).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| `Give.php` (class `Give`) | `donation` | `give` | No `get_data()` method exists. Two entry points instead: `save_new_donation($payment_id)` (real-time, hooked on `give_complete_donation`) and `get_give_donations($data)` (backfill, called from `get_notification_ready()`). Both wrap `\Give_Payments_Query` (bailing to `[]`/`null` if `\Give_Payments_Query` isn't loaded) and build a row per payment via `array_merge()` of payment fields (`id`, `title` = form title, `amount` = `$result->total . ' for'`, `link` from `_give_current_url` payment meta, `give_form_id`, `give_page_id` from `_give_current_page_id` payment meta, `timestamp`) with donor fields from `get_donor($donation)` (`first_name`, `last_name`, computed `name`, `email`, `country`/`city` from `$donation->address`, `ip` via `give_get_payment_user_ip()`). Entry key is `"{payment ID}-{form_id}"`. |

## Data flow

1. **Real-time**: GiveWP fires `give_complete_donation($payment_id)` →
   `Give::save_new_donation()` builds one donation row and calls
   `Extension::update_notification()` (inherited) → `Entries::insert_entry()`.
2. **Backfill on save**: When a `give`-sourced notification post is saved, the
   `nx_saved_post_give` filter runs `Give::saved_post()` → deletes existing entries
   for that `nx_id` (`delete_notification()`) then `get_notification_ready($data)` →
   `get_give_donations($data)` → `update_notifications()` (bulk insert, inherited
   from `Extension`).
3. Entries land in the custom entries table (`Entries` / `Database::$table_entries`,
   per plugin architecture) and are later surfaced through `FrontEnd.php` → REST →
   the React popup runtime, same as other extensions.
4. `Give::conversion_data()` (hooked on `nx_filtered_entry_give`) formats the saved
   `amount` through `give_currency_filter()` (if available) for display.
5. `Give::notification_image()` (hooked on `nx_notification_image_give`) swaps in
   the donation form's (or fallback donation page's) featured image when
   `show_notification_image === 'featured_image'` and `show_default_image` is falsy.
6. `Give::fallback_data()` supplies `name`/`first_name`/`last_name` = "Someone",
   `anonymous_title` = "Anonymous Product", and `sometime` = "Some time ago"
   fallbacks.
7. `Give::limit_by_selected_form()` (hooked on `nx_can_entry_give`) can suppress an
   entry from being saved/counted if the notification's `give_forms_control`
   setting is `give_form` and the entry's `give_form_id` isn't in the configured
   `give_form_list`.

## Fields & settings

- `give_forms_control` / `give_form_list` — settings consumed by
  `Give::limit_by_selected_form()` to restrict notifications to specific Give
  forms; also referenced in `Core/PostType.php`, `Core/Migration.php`, and
  `FrontEnd/FrontEnd.php`. The field UI definitions were not found in
  `GlobalFields.php` in this repo — `_TODO: verify_` exact field-registration
  location (may live in a metabox/JSON config or `notificationx-pro`).
- `Types\Donations` (the `donation` Type) supplies the shared theme/template
  scaffolding (`donation_template_new`, `donation_template_sales_count`) and its
  `first_param` uses `GlobalFields::get_instance()->common_name_fields()`; `Give`
  itself does not define its own `$this->themes`/`$this->templates` (falls back to
  the Type's, per `Extension::get_themes()`/`get_templates()`).
- `Give::doc()` supplies the "Instructions" panel copy (setup/video links) shown in
  the builder UI, hooked via the base `Extension::nx_instructions()`.

## Dependency & detection

- Required plugin: **GiveWP**. `Give::$class = '\Give'`; the base
  `Extension::is_active()` / `Extension::class_exists()` check
  `class_exists('\Give')`. The real-time save path (`save_new_donation()`) and the
  backfill path (`get_give_donations()`) separately guard on
  `class_exists('\Give_Payments_Query')`, returning early / `[]` when absent.
- When absent: `Extension::is_active()` returns `false`, so `init()`,
  `admin_actions()`, `public_actions()`, and field registration never run for this
  extension (module effectively inert). Separately, `Give::source_error_message()`
  (hooked on `source_error_message`) surfaces an admin error message — "You have to
  install GiveWP Donation plugin first." with a link to install it — scoped via
  `Rules::is('source', $this->id)` — whenever `!$this->class_exists()`.
- Registration itself (`ExtensionFactory::$extension_classes`) is unconditional —
  `'give' => 'NotificationX\Extensions\Give\Give'` is always in the factory's class
  map; gating happens at `is_active()`/module-enabled time, not at registration
  time.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/Give/Give.php`](../../includes/Extensions/Give/Give.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`give` entry in `$extension_classes`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Paired Type | [`includes/Types/Donations.php`](../../includes/Types/Donations.php) (`donation` Type — themes, templates, link type) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |

## Testing notes & gotchas

- `save_new_donation()` and `get_give_donations()` both instantiate
  `\Give_Payments_Query` directly and read fields like `form_title`, `total`,
  `payment_meta`, `address` off the returned payment objects — verify against the
  installed GiveWP version if these shapes change.
- `get_donor()` calls `give_get_payment_user_ip($donation->ID)` unconditionally
  (not guarded by a function_exists check) — relies on GiveWP being fully loaded
  whenever this runs.
- Entry key is `"{payment ID}-{form_id}"`, matching between the real-time and
  backfill paths, so re-saving a notification after a live donation shouldn't
  duplicate entries.
- No dedicated PHPUnit tests found under `tests/` for this integration —
  `_TODO: verify_` if GiveWP-specific test coverage exists elsewhere (e.g. in
  `notificationx-pro`).

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- Related Type docs under [../types/](../types/) (Donation type —
  `_TODO: verify_` exact filename once written)
