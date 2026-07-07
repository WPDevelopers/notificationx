# Gravity Forms Extension (`grvf`)

> Connects NotificationX's Contact Form (`form`) notification type to Gravity Forms
> submissions, showing "someone just submitted this form" popups. The data-fetching
> logic (the `gform_after_submission` hook and `GFAPI` calls) lives in the **Pro**
> plugin — the free-plugin class documented here only registers the module/source
> shell (title, popup, error messaging, doc links).

## At a glance

| | |
|---|---|
| **Integration** | Gravity Forms |
| **Directory** | [`includes/Extensions/GRVF/`](../../includes/Extensions/GRVF/) |
| **Module key(s) (`$module`)** | `modules_grvf` |
| **Feeds Types** | `form` (Contact Form) |
| **Extension classes** | `GravityForms.php` → `(type: form, id: grvf)` |
| **Depends on** | Gravity Forms plugin (class `\GFForms`); free-plugin class is pro-gated (`$is_pro = true`) |

## What it does

From the user's perspective: install & activate the Gravity Forms plugin, then
enable the "Gravity Forms" module (pro feature) under a Contact Form NotificationX
campaign. Once enabled, real form submissions on the site drive "X just submitted
Y form" popups.

In the **free** plugin, `includes/Extensions/GRVF/GravityForms.php` only:
- Registers the `grvf` source/module (`modules_grvf`) so it appears in the admin UI, gated pro (`$is_pro = true`).
- Declares `$types = 'form'` and `$class = '\GFForms'` (used by the base `Extension::is_active()` / `class_exists()` checks).
- Supplies the upsell popup content (`init_extension()`), the "install Gravity Forms first" error message (`source_error_message()`), and the documentation blurb (`doc()`).
- Does **not** implement `get_data()`, `save_post()`, `saved_post()`, or any submission hook — there is no free-tier data pipeline for this source.

The **Pro** plugin (`notificationx-pro/includes/Extensions/GRVF/GravityForms.php`,
outside this plugin root — documented here only for context, not verified against
this repo's own tests) extends the free class and adds the actual behaviour:
hooking `gform_after_submission` to capture new entries (`save_new_records()`),
backfilling historical entries via `\GFAPI::get_form()` / `\GFAPI::get_entries()`
in `get_notification_ready()`, and populating the "Select a Form" dropdown via
`nx_form_list` (`get_forms()`, querying the `{prefix}gf_form` table directly).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`GravityForms.php`](../../includes/Extensions/GRVF/GravityForms.php) | `form` (`ContactForm`) | `grvf` | _None in free plugin_ — no `get_data()`/submission hook is implemented here; see Pro plugin notes above. |

## Data flow

Not implemented in the free plugin. Per the Pro-plugin class (context only,
confirmed against `notificationx-pro/includes/Extensions/GRVF/GravityForms.php`):

1. `gform_after_submission` fires on a real Gravity Forms submission → `save_new_records($entry, $form)` maps field inputs/labels into a flat `data` array (email detection by label substring match) and calls the base `Extension::save()`, which writes one entry per active `nx_id` post via `Entries::insert_entry()`.
2. On first activation / re-save of the notification post, `saved_post()` calls `get_notification_ready()`, which backfills existing entries for the selected form (`form_list` setting) using `\GFAPI::get_entries()`.
3. `can_entry_grvf` filter (`can_entry()`) restricts saved entries to the form selected in the notification's `form_list` setting.
4. From there, storage/FrontEnd/REST/React is the same shared pipeline as every other Type (see [Adding a New Notification Type](../development/adding-a-notification-type.md)).

## Fields & settings

- `form_list` (select-async "Select a Form") is added at the **Type** level in [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) (`add_form_fields()`), shared by all `form`-type sources (`cf7`, `njf`, `wpf`, `grvf`) — not defined in the GRVF class itself.
- `grvf` is included as a valid `source` in several shared, source-gated fields inside [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (e.g. "Featured Image", "Gravatar" visibility rules around lines ~217, ~1570–1642) — no GRVF-specific fields are declared there.
- No `init_fields()` override exists in the free `GravityForms` class, so it inherits the base `Extension::init_fields()` (no-op) plus whatever `__init_fields()` wires generically (themes, sources, error messages, doc/instructions — see `Extension.php`).

## Dependency & detection

- Required plugin: **Gravity Forms** (`https://www.gravityforms.com/`).
- Detection: `$class = '\GFForms'` on the class, checked via the base `Extension::is_active()` / `class_exists()` (`class_exists('\GFForms')`).
- When absent: `source_error_message()` adds an admin error ("You have to install Gravity Forms plugin first.") scoped to `Rules::is('source', 'grvf')`, and `is_active()` returns `false` so the extension's `init()`/hooks never register.
- Additionally the whole extension is Pro-only (`$is_pro = true`); on the free plugin it is presented as an upsell (`$popup`) regardless of whether Gravity Forms itself is installed.

## Key files

| Purpose | File |
|---|---|
| Extension class (free, shell only) | [`includes/Extensions/GRVF/GravityForms.php`](../../includes/Extensions/GRVF/GravityForms.php) |
| Extension base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type it pairs with | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'grvf' => 'NotificationX\Extensions\GRVF\GravityForms'`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Pro implementation (context only, separate plugin) | `notificationx-pro/includes/Extensions/GRVF/GravityForms.php` |

## Testing notes & gotchas

- Because the entire data pipeline lives in the Pro plugin, testing "does Gravity Forms actually create a notification" requires `notificationx-pro` active with a real Gravity Forms install — the free plugin alone cannot produce entries for this source.
- If Gravity Forms is deactivated after being configured, `is_active()` will start returning `false` and the extension's actions stop registering; existing stored entries are not automatically cleaned up by this class.
- No automated tests reference `grvf` or `GravityForms` (`tests/test-extension-factory.php` does not name them, and `notificationx-pro` ships no `tests/` suite); the Pro-class behaviour above is from reading `notificationx-pro/includes/Extensions/GRVF/GravityForms.php` directly, not from running it.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Contact Form Type doc](../types/form.md)
