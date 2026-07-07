# ActiveCampaign Extension (`modules_activecampaign`)

> Registers ActiveCampaign as a data source for the **Email Subscription** notification
> type. In the free plugin this class only registers the source/module and provides a
> placeholder `get_data()` — the real ActiveCampaign API integration (fetching contacts,
> connecting via API URL/key) ships in the sibling `notificationx-pro` plugin, which
> extends this class.

## At a glance

| | |
|---|---|
| **Integration** | `ActiveCampaign` |
| **Directory** | [`includes/Extensions/ActiveCampaign/`](../../includes/Extensions/ActiveCampaign/) |
| **Module key(s) (`$module`)** | `modules_activecampaign` |
| **Feeds Types** | `email_subscription` (see [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php)) |
| **Extension classes** | `ActiveCampaign.php` → pairs `(email_subscription, ActiveCampaign)` |
| **Depends on** | ActiveCampaign account (API URL + API key). In this (free) class, no `class_exists`/`function_exists`/`constant` check is set, so `is_active()` never gates on a third-party presence check here — see [Dependency & detection](#dependency--detection). |

## What it does

From the user's perspective, ActiveCampaign is a **Pro** (`$is_pro = true`) email-subscription
source: once the `modules_activecampaign` module is enabled and the user connects an
ActiveCampaign account, NotificationX can show "X just subscribed" style alerts. In the
free plugin this class only:

- registers the `ActiveCampaign` source under the `email_subscription` Type (via
  `ExtensionFactory` / `Extension::__nx_sources`),
- registers the `modules_activecampaign` module (label "ActiveCampaign"),
- surfaces the `doc()` instructions block shown in the admin UI popup, and
- returns a hard-coded placeholder from `get_data()` (`'Hello From ActiveCampaign'`).

The actual API URL/key settings, contact-fetch logic, and cron-driven data refresh are
implemented in `NotificationXPro\Extensions\ActiveCampaign\ActiveCampaign`
(`wp-content/plugins/notificationx-pro/includes/Extensions/ActiveCampaign/ActiveCampaign.php`,
outside this plugin's directory), which extends the free class shown here and overrides
`init()`, `admin_actions()`, `public_actions()`, and adds `source_error_message()`,
reading `settings.activecampaign_api_key` / `settings.activecampaign_api_url` from
`Settings`. The pro override drives entries through `update_data()` (hooked from
`saved_post()` and the `nx_cron_update_data_ActiveCampaign` cron) rather than
`get_data()`; `update_data()` → `get_member()` → `get_members()` calls the
ActiveCampaign REST API (`/api/3/contacts?listid=…`). Full pro behaviour is out of
scope for this (free-plugin) doc.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`ActiveCampaign.php`](../../includes/Extensions/ActiveCampaign/ActiveCampaign.php) | `email_subscription` (`$types = 'email_subscription'`) | `ActiveCampaign` | Placeholder only — `return 'Hello From ActiveCampaign';`. Real data fetching lives in the `notificationx-pro` override. |

Other class properties (from [`ActiveCampaign.php`](../../includes/Extensions/ActiveCampaign/ActiveCampaign.php)):

- `$priority = 15`, `$module_priority = 20`
- `$module = 'modules_activecampaign'`
- `$is_pro = true`
- `$doc_link = 'https://notificationx.com/docs/ActiveCampaign-email-subscription-alert/'`
- `init_extension()` sets `$this->title` and `$this->module_title` to `__('ActiveCampaign', 'notificationx')`.
- `doc()` returns the HTML instructions block (setup link, docs link, integration page, blog link) shown via the `nx_instructions` filter (see `Extension::nx_instructions()`).

## Data flow

In this (free) plugin, there is no real data flow to trace — `get_data()` is a stub and
no `save_post`/`fallback_data`/`notification_image` methods are defined on this class, so
none of the corresponding hooks in `Extension::init()` / `public_actions()` are wired up
here. The Type/Extension registration flow (source event → `get_data()` → entries/storage
→ FrontEnd → REST → React) described in
[docs/new-notification-type.md](../development/adding-a-notification-type.md) applies once the pro
override supplies real data (confirmed: the pro subclass sets
`$cron_schedule = 'nx_activecampaign_interval'` and `$meta_key = 'activecampaign_content'`,
and refreshes entries via `update_data()` on save + cron).

## Fields & settings

No extension-specific fields are declared inside `includes/Extensions/ActiveCampaign/ActiveCampaign.php`
itself (no `$templates`, `$themes`, or custom field arrays are set — they fall back to
the `email_subscription` Type's shared themes/templates via `Extension::get_themes()` /
`get_templates()`). `ActiveCampaign` is referenced as a valid `source` value in the shared
`activecampaign_form`-style rules inside
[`GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (see the `Rules::includes('source', [...])`
list that includes `'ActiveCampaign'` alongside the other email-subscription/conversions
sources), and `activecampaign_form` is listed as an allowed Quick Builder field name in
[`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php). The
`activecampaign_form` select is rendered on the builder's **Content** tab by the pro
subclass's `content_fields()` (a `nx_content_fields` filter; options come from the
`nxpro_activecampaign_forms` option). The API URL/key settings fields are declared in
the pro subclass's `api_integration_settings()` — an `activecampaign_settings_section`
(`activecampaign_api_key`, `activecampaign_api_url`, cache duration, Connect button)
on the **API Integrations** settings tab. Both live in `notificationx-pro`, out of
scope here.

## Dependency & detection

- **Required:** an ActiveCampaign account with API access (API URL + API key), per the
  `doc()` text linking to ActiveCampaign's own "Getting started with the API" article.
- **Detection in this class:** `Extension::is_active()` checks `$this->class`,
  `$this->function`, and `$this->constant` (via `class_exists()`/`function_exists()`/`defined()`)
  — none of these three properties are set on `ActiveCampaign.php`, so that check is a
  no-op here; `is_active()` only falls through to the "is this source in the active
  notification items" check. There is no `class_exists`/`function_exists` guard for a
  third-party ActiveCampaign SDK because ActiveCampaign is a remote SaaS API, not a
  WordPress plugin — detection is necessarily about whether API credentials are
  configured, not whether a local dependency is installed.
- **What happens when absent:** module registration and source listing always occur
  (gated only by `modules_activecampaign` being enabled, per
  `Extension::register_module()` / `Modules::is_enabled()`), regardless of whether an
  API key is set. The pro-plugin override adds a `source_error_message()` method that
  shows an admin-facing error ("You have to setup your API Key for ActiveCampaign …")
  when `settings.activecampaign_api_key` is empty — that gating happens outside this
  plugin's directory. The free-plugin stub's `get_data()` is never invoked in a real
  notification flow: with Pro active, `GetInstance` substitutes the pro subclass
  (which populates entries via `update_data()`, never calling `get_data()`); with Pro
  inactive, the `$is_pro` source is locked in the UI, so no flow reaches it.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/ActiveCampaign/ActiveCampaign.php`](../../includes/Extensions/ActiveCampaign/ActiveCampaign.php) |
| Base class (inherited behaviour) | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'ActiveCampaign' => 'NotificationX\Extensions\ActiveCampaign\ActiveCampaign'`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Paired Type | [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php) |
| Quick Builder field allowlist | [`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php) (`activecampaign_form`) |

## Testing notes & gotchas

- This class alone does nothing user-visible beyond registering the source/module and
  showing setup instructions — do not expect real subscriber data without the
  `notificationx-pro` override active and Pro enabled (`is_pro = true`).
- If modifying this class, remember `notificationx-pro`'s `ActiveCampaign` class
  `extends` it (`NotificationXPro\Extensions\ActiveCampaign\ActiveCampaign extends
  NotificationX\Extensions\ActiveCampaign\ActiveCampaign`) — changing method
  signatures or removing `init_extension()`/`doc()` here will affect the pro subclass.
- No PHPUnit tests reference this extension; `tests/test-extension-factory.php` does
  not name `ActiveCampaign`, and `notificationx-pro` ships no `tests/` suite.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type doc: [Email Subscription](../types/email_subscription.md)
