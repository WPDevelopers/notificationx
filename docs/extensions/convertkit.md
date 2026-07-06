# ConvertKit Extension (`convertkit`)

> Registers ConvertKit as a data source for the **Email Subscription** notification
> Type. In the free plugin this class is a thin stub (module registration, docs
> link, image); the actual ConvertKit API integration — connecting an account,
> listing forms, and pulling subscribers — is implemented in the sibling
> `notificationx-pro` plugin, which extends this class.

## At a glance

| | |
|---|---|
| **Integration** | ConvertKit |
| **Directory** | [`includes/Extensions/ConvertKit/`](../../includes/Extensions/ConvertKit/) |
| **Module key(s) (`$module`)** | `modules_convertkit` |
| **Feeds Types** | `email_subscription` (see [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php)) |
| **Extension classes** | `ConvertKit.php` → `(types: email_subscription, id: convertkit)` |
| **Depends on** | A ConvertKit account/API key — but detection/connection logic lives in `notificationx-pro`, not in this plugin (see below) |

## What it does

From the source in this (free) plugin: `ConvertKit` extends the base `Extension`
class and only does three things (`includes/Extensions/ConvertKit/ConvertKit.php`):

- Registers itself as a source for the `email_subscription` Type (`$types = 'email_subscription'`) under the `modules_convertkit` module, marked `$is_pro = true`.
- Sets the admin display title/icon/doc link (`init_extension()`), and provides the "how to connect" copy shown in the builder (`doc()`), which points users to the ConvertKit login and NotificationX's ConvertKit docs.
- `get_data()` returns the literal string `'Hello From ConvertKit'` — it is a stub, not a real data source. _(Verified: `includes/Extensions/ConvertKit/ConvertKit.php` lines 53-55.)_

There is no `class_exists`/`function_exists`/`option`-based dependency check in this
class (see [Dependency & detection](#dependency--detection)), and no
`init_fields()`/`content_fields()` override, so the free plugin does not add any
ConvertKit-specific builder fields (e.g. form selection) itself.

The real integration — API key/secret settings, a "Connect" button, form listing,
and fetching subscribers from `https://api.convertkit.com/v3/forms/...` — is
implemented by `NotificationXPro\Extensions\ConvertKit\ConvertKit`, which `extends
NotificationX\Extensions\ConvertKit\ConvertKit` (this class). This mirrors the
"Pro features live in the separate `notificationx-pro` plugin and integrate via
the same Extension/Type system" pattern described in the plugin's `CLAUDE.md`.
_(Verified by reading `notificationx-pro/includes/Extensions/ConvertKit/ConvertKit.php`
on disk; that file is outside this plugin's tree and outside the scope of this doc.)_

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`ConvertKit.php`](../../includes/Extensions/ConvertKit/ConvertKit.php) | `email_subscription` | `convertkit` | Stub only — returns the hardcoded string `'Hello From ConvertKit'`. No real fetch happens in this plugin. |

Only one class exists in the directory; it is not further subclassed (e.g. no
`ConvertKitInline.php`) within the free plugin.

## Data flow

In the free plugin there is no real data flow to trace — `get_data()` is a stub
and `ConvertKit.php` defines no `save_post`/`saved_post`/cron hooks, so
`Extension::init()`'s conditional wiring (`nx_save_post_convertkit`,
`nx_saved_post_convertkit`, `add_cron_job`) never attaches extra behavior beyond
the base class defaults.

_TODO: verify_ — the pro subclass adds a `cron_schedule = 'nx_convertkit_interval'`,
an `admin_actions()` hook (`nx_cron_update_data_{$this->id}` → `update_data()`),
and a `saved_post()` hook that calls `update_data()` immediately on save. That
pipeline (API call → `update_notifications()` → `Entries` table → `FrontEnd.php` →
REST → React) lives entirely in `notificationx-pro` and is out of scope for this
doc.

## Fields & settings

- No ConvertKit-specific fields are registered by this class (no `init_fields()`
  override).
- `includes/Extensions/GlobalFields.php` references the `convertkit` source string
  in two **shared** fields it can appear on: the `show_notification_image` field's
  rule list defaults ConvertKit's image display to `gravatar` (lines ~199-201,
  ~1584-1585, ~1656-1657), and the "Notification Template" source-includes list
  (line ~2227). These are shared/global rules, not ConvertKit-only fields.
- Legacy migration code (`includes/Core/Migration.php`, around the `case 'convertkit':`
  block) reads old single-plugin-era meta keys `_nx_meta_convertkit_form` /
  `_nx_meta_convertkit_content` into a `convertkit_form` post field and re-inserts
  entries via `ConvertKit::update_notifications()`. This confirms a `convertkit_form`
  field name is expected on the post, but the field's UI definition
  (`content_fields()`) is added by the pro subclass, not by this file.
- `convertkit_form` also appears in `includes/Core/QuickBuild.php` (list of known
  field names) and `includes/FrontEnd/FrontEnd.php`'s `filtered_post()` ignore-list
  (props stripped before sending to the frontend) — both treat it as an opaque,
  pre-existing field name rather than defining it.

## Dependency & detection

- **Required service:** A ConvertKit account (and, per the pro subclass, an API
  Key + API Secret entered under NotificationX → Settings → API Integrations).
- **Detection in this plugin:** none. `Extension::is_active()` only checks
  `$this->class`, `$this->function`, `$this->constant` (all unset on
  `ConvertKit`), so — module enabled aside — the class reports itself active
  regardless of whether a ConvertKit account is actually connected.
- **Gating that does exist:** the `modules_convertkit` module toggle (via
  `Modules::is_enabled()` in the base `Extension::__construct()`) and `$is_pro`
  (hides/locks the source in the UI on the free plugin, per
  `NotificationX::is_pro()` checks in `Extension::__nx_sources()`).
- _TODO: verify_ — in the pro subclass, `source_error_message()` shows an admin
  error ("You have to setup your API Key for ConvertKit") when
  `settings.convertkit_api_key` / `settings.convertkit_api_secret` are empty, and
  `get_member()` silently returns no members if the API secret, form, or form
  list option (`nxpro_convertkit_forms`) are missing. That is the plugin's only
  real "presence" check, and it lives outside this repo's tree.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/ConvertKit/ConvertKit.php`](../../includes/Extensions/ConvertKit/ConvertKit.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'convertkit' => 'NotificationX\Extensions\ConvertKit\ConvertKit'`) |
| Paired Type | [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php) |
| Shared fields referencing `convertkit` | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Legacy migration handling | [`includes/Core/Migration.php`](../../includes/Core/Migration.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |

## Testing notes & gotchas

- Because `get_data()` is a stub in this plugin, testing real ConvertKit data
  flow requires the `notificationx-pro` plugin active; the free plugin alone
  cannot fetch or display real subscribers.
- `EmailSubscription` (the paired Type) lists three modules
  (`modules_mailchimp`, `modules_convertkit`, `modules_zapier` — `modules_mailchimp`
  appears twice in the array, _TODO: verify_ whether that duplicate is
  intentional) and is itself `$is_pro = true`, so the whole Email Subscription
  Type is pro-gated independent of the ConvertKit module.
- No dedicated tests for this extension were found under `tests/`.
  _TODO: verify_ if pro-side tests exist in `notificationx-pro`.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type: [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php) (no dedicated Type doc under `docs/types/` was found — _TODO: verify_)
