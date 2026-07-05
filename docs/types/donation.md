# Donations Notification Type (`donation`)

> Shows a live feed / static list of recent donations (donor name, amount, campaign/form
> title) made through a donation plugin, to build social proof around a fundraising or
> donation form page. Currently the only shipped data source is [GiveWP](https://wordpress.org/plugins/give/).

## At a glance

| | |
|---|---|
| **Type ID** | `donation` |
| **Class** | [`includes/Types/Donations.php`](../../includes/Types/Donations.php) |
| **Trait** | none — `Donations` only uses the generic `GetInstance` trait, there is no `includes/Types/Traits/Donations.php` |
| **Priority** | `40` |
| **Default source** | `give` (matches [`Give` extension](../../includes/Extensions/Give/Give.php) `$id`) |
| **Default theme** | `donation_theme-one` |
| **Module gate (`$module`)** | `modules_give` |
| **Compatible extensions** | [`Give`](../../includes/Extensions/Give/Give.php) (`$types = 'donation'`, `$module = 'modules_give'`) — the only extension found registering against this type (`grep -rl "'donation'" includes/Extensions` → only `Give.php`) |

## What it does

A `donation` notification displays entries generated from GiveWP donation payments: donor
name (via `get_donor()`), donation amount (`give_currency_filter()`-formatted, see
`Give::conversion_data()`), the donation form title, and a timestamp. New donations create
an entry live via the `give_complete_donation` action (`Give::save_new_donation()`); on
initial notification setup, existing/past donations are pulled in bulk via
`Give::get_notification_ready()` → `get_give_donations()` (bounded by the notification's
`display_from` days setting).

The `link_type` is `donation_page` (added to the Content tab's Link Type field via
`Donations::link_types()`), letting the admin choose to link the notification to the
donation form/page.

Optionally, entries can be restricted to specific GiveWP forms via
`give_forms_control` / `give_form_list` settings, enforced in
`Give::limit_by_selected_form()` (hooked to `nx_can_entry_give`). _TODO: verify_ exactly
where the `give_forms_control` UI field is declared (not found in PHP `content_fields`;
likely defined in the QuickBuilder JSON/React config under `nxdev/`).

## Data flow

Donation notifications are **not** popup/pressbar/exit-intent/GDPR types, so they flow
through the standard "active" (or "global", if `global_queue` is set) bucket in
[`FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php):

- `FrontEnd::get_notifications_ids()` — since `source` is `give` (not `press_bar`,
  `gdpr_notification`, `popup_notification`, or `exit_intent_custom`), it falls into the
  generic `else` branch and lands in `$active_notifications` (or `$global_notifications`
  if `global_queue` + Pro).
- `FrontEnd::get_notifications_data()` — entries are gathered via `get_entries()` and
  pushed into `$result['active'][$nx_id]['entries'][]` (shape: `{ post, entries: [...] }`),
  the same shape used by Sales/Reviews/Comments — i.e. this type uses `normalize()` (not
  `normalizePressBar()`) on the React side per `docs/new-notification-type.md`'s
  "Choosing `normalize` vs `normalizePressBar`" table. _TODO: verify_ the exact React-side
  file/line since it wasn't traced in this pass — see `nxdev/notificationx/frontend/core/utils.ts`.
- No `donation`-specific string appears anywhere in `FrontEnd.php` — it is handled entirely
  by the generic/default code paths shared with other "standard" notification types.

## Fields & settings schema

`Donations::init_fields()` only adds one type-specific piece of UI: a `donation_page` option
on the Link Type field (`nx_link_types` filter → `link_types()`). All other builder fields
come from the base `Types`/`Extension` field pipeline plus `GlobalFields` (e.g.
`common_name_fields()` used for the `first_param` template slot).

Give-extension-specific settings (stored as post meta, referenced in code but not declared
inline in `Give.php`):
- `give_forms_control`, `give_form_list` — restrict entries to selected GiveWP forms
  (enforced in `Give::limit_by_selected_form()`).

Template param defaults declared in `Donations::init()` (`$common_fields`, used as the
default `template` for `theme-one`/`theme-two`/`theme-three`):
`first_param` = `tag_name`, `second_param` = "recently donated for", `third_param` =
`tag_none` (amount), `fourth_param` = `tag_title`, `fifth_param` = `tag_time`.

## Themes / templates

`$this->themes` (desktop themes, [`Donations.php`](../../includes/Types/Donations.php) lines 58-112):

| Theme id | Image shape | Notes |
|---|---|---|
| `theme-one` | circle | free, uses `$common_fields` template |
| `theme-two` | circle | free, uses `$common_fields` template |
| `theme-three` | square | free, uses `$common_fields` template |
| `theme-four` | circle | `is_pro` |
| `theme-five` | circle | `is_pro` |
| `conv-theme-six` | circle | `is_pro`, no explicit `template` key |
| `maps_theme` | square | `is_pro`, `show_notification_image = 'maps_image'` |
| `conv-theme-seven` | rounded | `is_pro` |
| `conv-theme-eight` | circle | `is_pro` |
| `conv-theme-nine` | square | `is_pro` |

`$this->res_themes` (responsive themes, all `is_pro => true`): `res-theme-one` … `res-theme-six`
map to `_template: donation_template_new`; `res-theme-seven` maps to `maps_template_new`;
`res-theme-eight`/`nine`/`ten` map to `donation_template_sales_count`.

`$this->templates` defines two template schemas:
- `donation_template_new` — used by `donation_theme-one` … `donation_theme-five` and
  `donation_maps_theme`; fields: `first_param` (common name fields), `third_param`
  (`tag_amount`/`tag_none`), `fourth_param` (`tag_title`), `fifth_param` (`tag_time`).
- `donation_template_sales_count` — used by `donation_conv-theme-six` … `conv-theme-nine`;
  fields: `first_param` (common name fields), `third_param` (`tag_title`), `fourth_param`
  (empty — `@todo` in source referencing pro).

`preview_entry()` overrides the admin preview title to `"Fundraising Camp for Health"`.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/Donations.php`](../../includes/Types/Donations.php) |
| Trait | none |
| Extensions | [`includes/Extensions/Give/Give.php`](../../includes/Extensions/Give/Give.php) |
| Frontend runtime | `nxdev/notificationx/frontend/...` — _TODO: verify_ exact component (not traced in this pass; likely shares the generic `Notification`/normalize path with other standard types rather than a dedicated component) |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) — generic `active`/`global` bucket routing, no donation-specific branches |

## Dependencies

[GiveWP](https://wordpress.org/plugins/give/) (`\Give` class, `Give_Payments_Query`) — the
only registered data source for this type. `Give::source_error_message()` shows an admin
notice prompting install if the GiveWP plugin class doesn't exist.

## Testing notes & gotchas

- Module-gated: if `modules_give` is disabled in Settings, the whole `donation` type and
  the `Give` extension are not registered/loaded.
- `Give::get_give_donations()` bails to `[]` if `\Give_Payments_Query` doesn't exist (GiveWP
  not active) — verify the "install GiveWP" admin notice path when testing without GiveWP.
- New donations are captured live via the `give_complete_donation` action; past donations are
  backfilled only when a donation-type notification is first saved (`saved_post()` →
  `get_notification_ready()`), bounded by the notification's `display_from` (days) setting.
- Because this type shares the generic "active"/"global" pipeline with Sales/Reviews/etc.
  (no dedicated branches in `FrontEnd.php`), regressions here are more likely to come from
  shared code (e.g. `get_entries()`, `apply_defaults()`) than from donation-specific logic.
- _TODO: verify_ whether any PHPUnit tests under `tests/` cover `Donations`/`Give` — none
  were located by name in this pass.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
