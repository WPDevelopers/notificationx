# MailChimp Extension (`mailchimp`)

> Registers MailChimp as a data source for the **Email Subscription** notification
> Type. In the free plugin this class is a thin stub (module registration, docs
> link, image); the actual MailChimp API integration — connecting an account,
> listing audiences/lists, and pulling subscribers — is implemented in the
> sibling `notificationx-pro` plugin, which extends this class.

## At a glance

| | |
|---|---|
| **Integration** | MailChimp |
| **Directory** | [`includes/Extensions/MailChimp/`](../../includes/Extensions/MailChimp/) |
| **Module key(s) (`$module`)** | `modules_mailchimp` |
| **Feeds Types** | `email_subscription` (see [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php)) |
| **Extension classes** | `MailChimp.php` → `(types: email_subscription, id: mailchimp)` |
| **Depends on** | A MailChimp account/API key — but detection/connection logic lives in `notificationx-pro`, not in this plugin (see below) |

## What it does

From the source in this (free) plugin: `MailChimp` extends the base `Extension`
class and only does three things (`includes/Extensions/MailChimp/MailChimp.php`):

- Registers itself as a source for the `email_subscription` Type (`$types = 'email_subscription'`) under the `modules_mailchimp` module, marked `$is_pro = true`, `$priority = 5`, `$module_priority = 14`.
- Sets the admin display title/icon/doc link (`init_extension()`), and provides the "how to connect" copy shown in the builder (`doc()`), which points users to MailChimp's API key help page, NotificationX's MailChimp docs, a video tutorial, and a couple of promo blog links.
- `get_data()` returns the literal string `'Hello From MailChimp'` — it is a stub, not a real data source. _(Verified: `includes/Extensions/MailChimp/MailChimp.php` lines 53-55.)_

There is no `class_exists`/`function_exists`/`option`-based dependency check in
this class (see [Dependency & detection](#dependency--detection)), and no
`init_fields()`/`content_fields()` override, so the free plugin does not add any
MailChimp-specific builder fields (e.g. list selection) itself.

The real integration — API key settings, a "Connect" button, MailChimp
audience/list listing (`get_lists()`, backed by the `nxpro_mailchimp_lists`
option), fetching subscribers via `Helper::get_members()`, and a recurring cron
job (`nx_mailchimp_interval`) that refreshes entries — is implemented by
`NotificationXPro\Extensions\MailChimp\MailChimp`, which `extends
NotificationX\Extensions\MailChimp\MailChimp` (this class). This mirrors the
"Pro features live in the separate `notificationx-pro` plugin and integrate via
the same Extension/Type system" pattern described in the plugin's `CLAUDE.md`.
_(Verified by reading `notificationx-pro/includes/Extensions/MailChimp/MailChimp.php`
on disk; that file is outside this plugin's tree and outside the scope of this doc.)_

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`MailChimp.php`](../../includes/Extensions/MailChimp/MailChimp.php) | `email_subscription` | `mailchimp` | Stub only — returns the hardcoded string `'Hello From MailChimp'`. No real fetch happens in this plugin. |

Only one class exists in the directory; it is not further subclassed (e.g. no
`MailChimpInline.php`) within the free plugin.

## Data flow

In the free plugin there is no real data flow to trace — `get_data()` is a stub
and `MailChimp.php` defines no `save_post`/`saved_post`/cron hooks, so
`Extension::init()`'s conditional wiring (`nx_save_post_mailchimp`,
`nx_saved_post_mailchimp`, `add_cron_job`) never attaches extra behavior beyond
the base class defaults.

_TODO: verify_ — the pro subclass sets `$cron_schedule = 'nx_mailchimp_interval'`
and `$meta_key = 'mailchimp_content'`, hooks `saved_post()` → `update_data()` (runs
immediately after the builder is saved) and `nx_cron_update_data_mailchimp` →
`update_data()` (runs on the recurring cron). `update_data()` calls
`get_members()` (which validates `mailchimp_list`, `display_last`, the
`nxpro_mailchimp_lists` option, and `$this->api_key` before calling
`Helper::get_members($api_key, $list_id, $limit)`), deletes old entries for the
post, and re-inserts fresh ones via `Extension::update_notifications()` into the
Entries table. From there the standard pipeline (Entries table → `FrontEnd.php`
→ REST → React `useNotificationX.ts`/`utils.ts` `normalize()`) applies. That
whole pipeline lives in `notificationx-pro` and is out of scope for this doc.

## Fields & settings

- No MailChimp-specific fields are registered by this class (no `init_fields()`
  override).
- `includes/Extensions/GlobalFields.php` references the `mailchimp` source
  string in shared/global fields only: the `show_notification_image` field's
  rule list defaults MailChimp's image display to `gravatar` (~line 205, and
  again at ~1584, ~1656), and the "Notification Template" source-includes list
  (~line 2227). These are shared rules other extensions also appear in, not
  MailChimp-only field definitions.
- Legacy migration code (`includes/Core/Migration.php`, `case 'mailchimp':`
  around line 894) reads old single-plugin-era meta keys (`_nx_meta_mailchimp_list`,
  `_nx_meta_mailchimp_content`, `_nx_meta_mailchimp_theme`, plus color/border/font
  meta) into post fields — notably `mailchimp_list` — and re-inserts entries via
  `MailChimp::update_notifications()`. This confirms a `mailchimp_list` field
  name is expected on the post (also referenced as a known field name in
  `includes/Core/QuickBuild.php` line 171 and stripped in
  `includes/FrontEnd/FrontEnd.php` line 932's frontend-filtering list), but the
  field's UI definition (`content_fields()` → `mailchimp_list` select, populated
  from `get_lists()`) is added by the pro subclass, not by this file.

## Dependency & detection

- **Required service:** A MailChimp account (and, per the pro subclass, an API
  Key entered under NotificationX → Settings → API Integrations →
  `mailchimp_settings_section`).
- **Detection in this plugin:** none. `Extension::is_active()` only checks
  `$this->class`, `$this->function`, `$this->constant` (all unset on
  `MailChimp`), so — module enabled aside — the class reports itself active
  regardless of whether a MailChimp account is actually connected.
- **Gating that does exist:** the `modules_mailchimp` module toggle (via
  `Modules::is_enabled()` in the base `Extension::__construct()`) and `$is_pro`
  (hides/locks the source in the UI on the free plugin, per
  `NotificationX::is_pro()` checks in `Extension::__nx_sources()`).
- _TODO: verify_ — in the pro subclass, `source_error_message()` shows an admin
  error ("You have to setup your API Key for MailChimp") when
  `settings.mailchimp_api_key` is empty, and `get_members()` silently returns no
  members if the list ID, the `nxpro_mailchimp_lists` option, or the API key are
  missing/empty. That is the plugin's only real "presence" check, and it lives
  outside this repo's tree.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/MailChimp/MailChimp.php`](../../includes/Extensions/MailChimp/MailChimp.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'mailchimp' => 'NotificationX\Extensions\MailChimp\MailChimp'`) |
| Paired Type | [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php) |
| Shared fields referencing `mailchimp` | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Legacy migration handling | [`includes/Core/Migration.php`](../../includes/Core/Migration.php) (`case 'mailchimp':`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |

## Testing notes & gotchas

- Because `get_data()` is a stub in this plugin, testing real MailChimp data
  flow requires the `notificationx-pro` plugin active; the free plugin alone
  cannot fetch or display real subscribers.
- `EmailSubscription` (the paired Type) lists four modules
  (`modules_mailchimp`, `modules_convertkit`, `modules_mailchimp`,
  `modules_zapier` — `modules_mailchimp` appears twice in the array,
  _TODO: verify_ whether that duplicate is intentional) and is itself
  `$is_pro = true`, and its `$default_source = 'mailchimp'` — so MailChimp is
  the default Email Subscription source, and the whole Type is pro-gated
  independent of the MailChimp module.
- No dedicated tests for this extension were found under `tests/`.
  _TODO: verify_ if pro-side tests exist in `notificationx-pro`.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type: [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php) (no dedicated Type doc under `docs/types/` was found — _TODO: verify_)
