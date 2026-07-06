# Contact Form Notification Type (`form`)

> Shows a popup notification when a visitor submits a contact/lead-gen form built with
> a supported form plugin (Contact Form 7, WPForms, Ninja Forms, Gravity Forms, Fluent
> Forms, Elementor Forms) — e.g. "Someone recently contacted via [Form Title]" — to
> build social proof around form engagement/lead volume.

## At a glance

| | |
|---|---|
| **Type ID** | `form` |
| **Class** | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) |
| **Trait** | none — no `includes/Types/Traits/ContactForm.php` exists; `ContactForm` only uses the generic `GetInstance` trait |
| **Priority** | `25` |
| **Default source** | `cf7` |
| **Default theme** | `form_theme-one` |
| **Link type** | `none` (`$link_type = 'none'` — the notification popup is not clickable by default; see `FrontEnd::link_url()`) |
| **Module gate (`$module`)** | Declares `['modules_cf7', 'modules_wpf', 'modules_njf', 'modules_grvf']` on the `ContactForm` class itself. _TODO: verify_ — no code path was found in this repo that reads `Types::$module`; each compatible **extension** gates itself independently via its own `$module` key (see below), so this list on the Type appears descriptive/legacy rather than functionally enforced. |
| **Compatible extensions** | See table below — 6 extensions declare `$types = 'form'` |

## What it does

When a visitor submits a form via one of the compatible form plugins, that plugin's
NotificationX extension turns the submission into a notification entry (form title,
form id, timestamp, and — for CF7 — the raw submitted field values). NotificationX then
shows a themed popup to other visitors: a name/avatar area, "recently contacted via",
the form title, and a relative time — the standard four-tag template
(`first_param` / `second_param` / `third_param` / `fourth_param`).

The admin builder's Content tab lets the site owner restrict a notification to one
specific form via the **Select a Form** (`form_list`) field, which is only shown when
the notification's type `is` `form` (`Rules::includes('type', $this->id)` on the field,
gated further per-extension — e.g. CF7's `can_entry()` filters out entries that don't
match the selected `form_list` id).

## Data flow

1. **Capture** — each compatible extension has its own submission hook and entry
   shape; only `CF7` (id `cf7`) was fully read in this pass:
   - `CF7::save_new_records()` runs on `wpcf7_mail_sent`, scans the submitted form's
     tags via `WPCF7_Submission::get_posted_data()`, builds a `data` array keyed by
     tag name (plus a normalized `email` key if any tag name contains "email"),
     `title` (form title), `id` (form post id), `timestamp`. Stored via
     `Extension::save()` with `entry_key = "cf7_{$form_id}"`.
   - WPForms/Ninja Forms/Gravity Forms/Fluent Forms/Elementor Forms extensions were
     located (see table below) but their save/capture logic was not read in this pass —
     `_TODO: verify_` for their exact entry shape.
2. **Store** — entries persist via `Extension::update_notification(s)` (inherited from
   [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php)).
3. **Ajax tag discovery** — `ContactForm::notification_template()` wires the Content
   tab's `first_param` dropdown to `POST /notificationx/v1/get-data` with
   `type: "ContactForm"`, `form_type: "@source"`, `form_id: "@form_list"` (only `is`
   `type` `form`). `ContactForm::restResponse()` (static) resolves the chosen
   extension via `ExtensionFactory::get_instance()->get($args['form_type'])`, checks
   `is_active(false)`, then delegates to that extension's own `restResponse()` — for
   CF7 this returns the list of merge-tag keys parsed out of the form's shortcode body
   (`CF7::keys_generator()` → `tag_<field_name>` options).
4. **Routing (`FrontEnd.php`)** — `form`-sourced notifications use `source` values like
   `cf7`/`wpf`/`njf`/`grvf` (the extension id, not the type id `form`), none of which
   match the special-cased sources (`press_bar`, `gdpr_notification`,
   `popup_notification`, `exit_intent_custom`) in `get_notifications_ids()` — so, like
   `comments`, form notifications fall into the generic **`active`** bucket
   (see [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php)).
   `FrontEnd::filtered_post()` strips the admin-only `form_list` field (and its
   `__form_list` shadow) before a post is sent to the frontend.
5. **Frontend render** — the React runtime resolves the theme template string via
   `nxdev/notificationx/frontend/themes/GetTemplate.ts`; no `form`-specific branch was
   found there, so it renders through the same generic tag-substitution path used by
   other standard types (e.g. `comments`) rather than a dedicated `ContactForm`
   component.

## Fields & settings schema

`ContactForm::init_fields()` adds two type-specific filters beyond the parent's field
registration:

- `nx_content_fields` → `add_form_fields()` adds a `content.fields.form_list` field:
  `select-async`, label "Select a Form", visible only via
  `Rules::includes('type', $this->id)`, populated by the `nx_form_list` filter
  (each compatible extension hooks its own forms into this list, e.g.
  `CF7::nx_form_list()`) and by an ajax lookup to `/notificationx/v1/get-data` with
  `type: "@type"`, `source: "@source"`, `field: "form_list"`.
- `nx_notification_template` → `notification_template()` wires the `first_param`
  dropdown's ajax lookup (see Data flow step 3 above).
- `customize_fields()` (adds a `Rules::is('type', $this->id, true, ...)` rule to the
  Customize tab's `link_open` field) exists on the class but is commented out at the
  `add_filter('nx_customize_fields', ...)` call site — **not currently active**.

`form_template_new` (`$this->templates`) defines the Content-tab dropdown options:
`first_param` starts with a disabled placeholder "Select A Tag" (real options are
populated dynamically via the ajax call above); `third_param` offers "Form Title"
(`tag_title`); `fourth_param` offers "Definite Time" (`tag_time`).

## Themes / templates

`$this->themes` (admin theme picker, `form_theme-*` ids — all free, none marked `is_pro`):

| Theme key | Image shape | Notes |
|---|---|---|
| `theme-one` (default) | circle | |
| `theme-two` | circle | |
| `theme-three` | square | |

All three share the same default template values: `first_param: select_a_tag` /
`second_param: "recently contacted via"` / `third_param: tag_title` /
`fourth_param: tag_time`.

`$this->res_themes` (responsive/mobile themes, all `is_pro: true`): `res-theme-one`,
`res-theme-two`, `res-theme-three` — all circle, default template
`res_second_param: "just contacted via"` / `res_third_param: tag_title`.

`$this->templates['form_template_new']._themes` lists `form_theme-one`,
`form_theme-two`, `form_theme-three` as the themes backed by this single template
group (there is only one template group for this type).

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/ContactForm.php`](../../includes/Types/ContactForm.php) |
| Trait | none |
| Extensions (data sources) | [`includes/Extensions/CF7/CF7.php`](../../includes/Extensions/CF7/CF7.php) (`cf7`), [`includes/Extensions/WPF/WPForms.php`](../../includes/Extensions/WPF/WPForms.php) (`wpf`), [`includes/Extensions/NJF/NinjaForms.php`](../../includes/Extensions/NJF/NinjaForms.php) (`njf`), [`includes/Extensions/GRVF/GravityForms.php`](../../includes/Extensions/GRVF/GravityForms.php) (`grvf`, pro), [`includes/Extensions/FluentForm/FluentForm.php`](../../includes/Extensions/FluentForm/FluentForm.php) (`fluentform`), [`includes/Extensions/Elementor/From.php`](../../includes/Extensions/Elementor/From.php) (`elementor_form`, pro) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'form' => 'NotificationX\Types\ContactForm'`) |
| Frontend runtime | [`nxdev/notificationx/frontend/themes/GetTemplate.ts`](../../nxdev/notificationx/frontend/themes/GetTemplate.ts) — generic tag-substitution, no dedicated form component |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (`get_notifications_ids()`/`get_notifications_data()` fall through to the generic `active` bucket; `filtered_post()` strips `form_list`/`__form_list`; `link_url()` honors `link_type: 'none'`) |

## Compatible extensions

| Extension id | Class | `$module` | `$is_pro` | Data source plugin |
|---|---|---|---|---|
| `cf7` | [`CF7`](../../includes/Extensions/CF7/CF7.php) | `modules_cf7` | no | Contact Form 7 (checks `class_exists('WPCF7_ContactForm')`) |
| `wpf` | [`WPForms`](../../includes/Extensions/WPF/WPForms.php) | `modules_wpf` | _TODO: verify_ | WPForms (checks `\WPForms_Form_Handler`) |
| `njf` | [`NinjaForms`](../../includes/Extensions/NJF/NinjaForms.php) | `modules_njf` | _TODO: verify_ | Ninja Forms (checks `Ninja_Forms`) |
| `grvf` | [`GravityForms`](../../includes/Extensions/GRVF/GravityForms.php) | `modules_grvf` | yes | Gravity Forms (checks `\GFForms`) |
| `fluentform` | [`FluentForm`](../../includes/Extensions/FluentForm/FluentForm.php) | `modules_fluentform` | _TODO: verify_ | Fluent Forms (checks `FLUENTFORM_VERSION` constant) |
| `elementor_form` | [`From`](../../includes/Extensions/Elementor/From.php) | `elementor_form` | yes | Elementor Pro Forms (checks `ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository`) |

Note: `fluentform` and `elementor_form` are not present in `ContactForm::$module`
(only `modules_cf7`/`modules_wpf`/`modules_njf`/`modules_grvf` are listed there) — see
the `_TODO: verify_` note in "At a glance" above about whether that list is enforced
anywhere.

## Dependencies

At least one of: Contact Form 7, WPForms, Ninja Forms, Gravity Forms (pro), Fluent
Forms, or Elementor Pro (for its Forms widget) — the type itself has no bundled data
source; every entry originates from one of the extensions above.

## Testing notes & gotchas

- Each extension gates independently on its own `class`/`function`/`constant` check
  plus its own `modules_*` settings key (`Extension::is_active()`), not on
  `ContactForm::$module` — disabling e.g. `modules_cf7` in Settings should stop CF7
  entries without affecting WPForms/Ninja Forms/etc.
- `form_list` (the "Select a Form" field) is an admin-only selection field —
  `FrontEnd::filtered_post()` strips it (and `__form_list`) before sending post data to
  the frontend; don't expect to see it in the rendered popup's settings.
- `link_type` defaults to `none` for this type, so by default the popup is not a
  clickable link — verify this if adding link support for a specific form extension.
- `ContactForm::customize_fields()` exists but its `add_filter('nx_customize_fields', ...)`
  registration is commented out — it currently has no effect; confirm before relying on
  it or re-enabling it.
- Only `CF7`'s capture path (`save_new_records()`/`keys_generator()`) was traced in
  full in this pass. `_TODO: verify_` the equivalent capture/tag-parsing logic for
  WPForms, Ninja Forms, Gravity Forms, Fluent Forms, and Elementor Forms before relying
  on parity between sources.
- No dedicated tests for this type were found under `tests/`. `_TODO: verify_` if any
  exist elsewhere in the suite.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Comments Notification Type](comments.md) — sibling type with a similar generic
  `active`-bucket data flow and no dedicated frontend component
