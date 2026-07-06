# BitIntegrations Extension (`modules_bitintegrations` / `modules_bitintegration`)

> Connects NotificationX to the third-party **Bit Integrations** plugin (by BitApps).
> It registers one Extension per notification Type (Sales/Conversions, Email
> Subscription, Reviews) so Bit Integrations can be picked as a `source` in the
> builder, but the actual data-fetch logic (`get_data()`) is currently a stub —
> see [Data flow](#data-flow) below.

## At a glance

| | |
|---|---|
| **Integration** | Bit Integrations (BitApps) |
| **Directory** | [`includes/Extensions/BitIntegrations/`](../../includes/Extensions/BitIntegrations/) |
| **Module key(s) (`$module`)** | `modules_bitintegrations` (Conversions, Reviews classes) and `modules_bitintegration` (Email Subscription class — note the singular, see [Gotchas](#testing-notes--gotchas)) |
| **Feeds Types** | `conversions`, `email_subscription`, `reviews` |
| **Extension classes** | `BitIntegrationsConversions` (`conversions`, id `bitintegrations_conversions`), `BitIntegrationsEmailSubscription` (`email_subscription`, id `bitintegrations_email_subscription`), `BitIntegrtionsReviews` (`reviews`, id `bitintegrations_reviews`) |
| **Depends on** | The **Bit Integrations** plugin — detected via `class_exists('BitApps\Integrations\Config')` (all three classes set `$class = 'BitApps\Integrations\Config'`, checked by the inherited `Extension::is_active()` / `class_exists()`) |

## What it does

From the user's perspective: install/activate the **Bit Integrations** plugin
(by BitApps), enable the corresponding NotificationX module, then pick
"Bit Integrations" as the `source` for a Sales, Email Subscription, or Reviews
notification. Each class only adds itself to the builder's source list, the
"Notification Template" merge-tag documentation (`doc()`), and an install-nudge
error message (`source_error_message()`) shown when the Bit Integrations plugin
class isn't present. None of the three classes implement `init_fields()`,
`save_post()`, or any hook that pulls real data in — `get_data()` on all three
returns a hardcoded placeholder string (`'Hello From Bit Integrations'` /
`'Hello From BitIntegrations'`). See [Data flow](#data-flow).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`BitIntegrationsConversions.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsConversions.php) | `conversions` | `bitintegrations_conversions` | Stub — returns the literal string `'Hello From Bit Integrations'`; no real fetch logic implemented |
| [`BitIntegrationsEmailSubscription.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsEmailSubscription.php) | `email_subscription` | `bitintegrations_email_subscription` | Stub — returns the literal string `'Hello From BitIntegrations'`; no real fetch logic implemented |
| [`BitIntegrationsReviews.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsReviews.php) (class name in file: `BitIntegrtionsReviews`) | `reviews` | `bitintegrations_reviews` | Stub — returns the literal string `'Hello From BitIntegrations'`; no real fetch logic implemented |

[`BitIntegrations.php`](../../includes/Extensions/BitIntegrations/BitIntegrations.php) is an empty `BitIntegrations` trait (used by the Email Subscription and Reviews classes via `use BitIntegrations;`) — currently declares no shared methods.

## Data flow

_TODO: verify_ — Unlike most Extensions in this codebase, none of the three
BitIntegrations classes override `init_fields()`, implement `save_post()`, hook
into `nx_api_response_success_{id}`/`nx_api_response_success` (the generic
webhook actions fired from [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php)'s
`save_response()`), or implement a `connect()` method used by
`Integration::api_connect()`. `get_data()` on all three simply returns a
hardcoded placeholder string rather than fetching or storing entries via
`Extension::update_notification()` / `save()`. This mirrors the same stub
pattern found in the sibling `Zapier` extensions
([`includes/Extensions/Zapier/`](../../includes/Extensions/Zapier/)). Whether
real ingestion happens elsewhere (e.g. inside the Bit Integrations plugin
itself, or in `notificationx-pro`, which was not present in this checkout) is
unverified from this codebase alone.

## Fields & settings

None of the three classes add distinctive builder fields — no `init_fields()`
override, and no reference to [`GlobalFields`](../../includes/Extensions/GlobalFields.php)
was found (`grep` for `bitintegrations`/`BitIntegrations` in `GlobalFields.php`
returned no matches). Each class only wires the shared behaviour inherited from
`Extension::__init_fields()` (themes/sources/templates registration) plus:
- `doc()` — merge-tag reference table shown in "Notification Template" instructions (field keys differ per type, e.g. `sales_count`/`email`/`title` for Conversions, `rated`/`plugin_name` for Reviews).
- `source_error_message()` — install-nudge error message (links to `plugin-install.php?s=bit+integrations`) shown via the `source_error_message` filter when `class_exists()` is false.
- `BitIntegrtionsReviews::init_extension()` additionally sets a `$popup` (upsell dialog) config.

## Dependency & detection

- Required plugin: **Bit Integrations** (BitApps), which provides the class `BitApps\Integrations\Config`.
- Detection: each class sets `public $class = 'BitApps\Integrations\Config';`. The inherited `Extension::is_active()` and `Extension::class_exists()` (in [`Extension.php`](../../includes/Extensions/Extension.php)) call `class_exists($this->class)`.
- When absent: `is_active()` returns `false`, so `initialize()` skips `init()`/`admin_actions()`/`public_actions()`/`init_fields()` for that Extension (module still registers/shows in the sidebar via `register_module()`, but the source itself is inert), and `source_error_message()` surfaces an admin error prompting the user to install the plugin.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/BitIntegrations/BitIntegrationsConversions.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsConversions.php), [`BitIntegrationsEmailSubscription.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsEmailSubscription.php), [`BitIntegrationsReviews.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsReviews.php), [`BitIntegrations.php`](../../includes/Extensions/BitIntegrations/BitIntegrations.php) (shared trait) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`$extension_classes` map, keys `bitintegrations_conversions`, `bitintegrations_email_subscription`, `bitintegrations_reviews`) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (not currently referenced by this integration) |
| Generic webhook/connect REST routes | [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php) |

## Testing notes & gotchas

- **Module key mismatch**: `BitIntegrationsConversions` and `BitIntegrtionsReviews` both use `$module = 'modules_bitintegrations'` (plural), while `BitIntegrationsEmailSubscription` uses `$module = 'modules_bitintegration'` (singular). Separately, `Types\Conversions::$module` (the list of module keys the Conversions Type advertises) lists `'modules_bitintegration'` (singular) — not the plural key that `BitIntegrationsConversions` actually registers/checks via `Modules::is_enabled()`. `Types::$module` did not turn up any functional read site in `includes/Types/` or `includes/Core/` beyond the declaration, so the practical impact of this mismatch is `_TODO: verify_`.
- **Class name typo**: the Reviews class is named `BitIntegrtionsReviews` (missing the second "a") in `BitIntegrationsReviews.php`; `ExtensionFactory.php` references it with the same typo, so this is internally consistent, just easy to mistype when grepping.
- **`get_data()` is a placeholder** on all three classes — do not assume it returns usable notification data; verify the true data path before relying on it (see [Data flow](#data-flow)).
- No tests under `tests/` reference BitIntegrations (`_TODO: verify_` if this changes).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs under [../types/](../types/) (none yet exist for `conversions` / `email_subscription` / `reviews` at time of writing — `_TODO: verify_`)
