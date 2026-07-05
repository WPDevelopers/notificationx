# Email Subscription Notification Type (`email_subscription`)

> Shows a social-proof popup announcing that someone subscribed to an email/newsletter
> list — e.g. "Someone just subscribed to Anonymous Title" — sourced from an email
> marketing/automation integration (MailChimp, ConvertKit, ActiveCampaign, Zapier,
> BitIntegrations, IFTTT).

## At a glance

| | |
|---|---|
| **Type ID** | `email_subscription` |
| **Class** | [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php) |
| **Trait** | none — `EmailSubscription` only uses the generic `GetInstance` trait, no `includes/Types/Traits/EmailSubscription.php` exists |
| **Priority** | `65` |
| **Type-level `is_pro`** | `true` (see [`EmailSubscription.php:27`](../../includes/Types/EmailSubscription.php#L27)) |
| **Default source** | `mailchimp` |
| **Default theme** | not set (`$default_theme` is inherited from `Types` base and left at its default `''`) — _TODO: verify_ what the admin UI falls back to |
| **Link type** | not overridden — inherits `Types::$link_type = 'none'` |
| **Module gate (`$module`)** | `['modules_mailchimp', 'modules_convertkit', 'modules_mailchimp', 'modules_zapier']` (note: `modules_mailchimp` is listed twice; `modules_activecampaign`, `modules_ifttt`, `modules_bitintegration` are **not** in this array even though their extensions declare `$types = 'email_subscription'` — as with `Reviews::$module` (see [`docs/types/reviews.md`](reviews.md)), no call site was found that reads `Types::$module` directly for gating, so treat this list as documentation rather than confirmed enforced logic. _TODO: verify_) |
| **Compatible extensions** | See table below (data sources) |

## What it does

The type declares three "static" desktop themes (`theme-one`, `theme-two`, `theme-three`)
plus a `maps_theme`, all built from a shared `$common_fields` template: first param is the
subscriber's first name (`tag_first_name`, fallback "Someone"), a fixed second-param string
"just subscribed to", a third param that is the list/campaign title (`tag_title`, fallback
"Anonymous Title"), and a fourth param that is a relative/definite time (`tag_time`,
fallback "Some time ago"). Responsive/mobile equivalents live in `$res_themes`.

Because `$is_pro = true` at the Type level and every compatible Extension's `get_data()` in
this free-plugin repo is a hard-coded stub (see below), this type is **not functional in
the free plugin** — its real data fetching/rendering lives in the paid `notificationx-pro`
plugin. `EmailSubscription::init()` sets `$this->popup` (an "Upgrade to PRO" SweetAlert
config with a link to `notificationx.com/#pricing` and a demo video) which is surfaced via
the `nx_pro_alert_popup` filter (see [`Extension.php:451`](../../includes/Extensions/Extension.php#L451)
and [`GlobalFields.php:113`](../../includes/Extensions/GlobalFields.php#L113)) when a user
without Pro tries to use this source in the admin builder.

`EmailSubscription::preview_entry()` overrides the admin builder's live-preview entry:
title becomes `"NotificationX Pro"`, and (unless the selected theme is
`email_subscription_maps_theme`) the preview image is forced to a static
`pink-face-looped.gif` icon.

## Data flow

Email Subscription uses the **standard/generic** entries pipeline (same as Comments,
Reviews, Conversions) — there is no dedicated bucket like `exit_intent` or `popup` in
[`FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php):

1. The active Extension's `get_data()` would populate entries per the configured source
   (`mailchimp`, `convertkit`, `ActiveCampaign`, `zapier_email_subscription`,
   `bitintegrations_email_subscription`). In this free-plugin repo every one of these
   `get_data()` methods is a stub returning a literal string (e.g. `'Hello From MailChimp'`)
   — actual API integration is Pro-only.
2. `FrontEnd::get_notifications_data()` merges entries into the generic `global`/`active`
   buckets and calls `get_entries()` (~`FrontEnd.php` line 294 area, per the pattern
   documented in [`docs/types/comments.md`](comments.md) and [`docs/types/reviews.md`](reviews.md)).
3. The filter chain `nx_filtered_entry_email_subscription` → `nx_filtered_entry_{$source}` →
   `nx_filtered_entry` runs per entry ([`FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php)
   ~line 338). No `nx_filtered_entry_email_subscription` hook was found registered by
   `EmailSubscription` itself in this repo — _TODO: verify_ whether it exists in
   `notificationx-pro`.
4. On the React side, entries would reach the runtime through the standard `normalize()`
   shape (`{ post, entries: [...] }`), not `normalizePressBar` — same as other multi-entry
   types (see [`docs/new-notification-type.md`](../new-notification-type.md)).

## Fields & settings schema

`EmailSubscription` does not override `init_fields()` — it relies entirely on the base
`Types` class and `GlobalFields` for Content/Design/Customize tab fields. The only
type-specific pieces declared in `init()` are the `$themes`, `$res_themes`, `$templates`,
and `$popup` (Pro-upsell) properties described above and below. Per-source fields (API
key inputs, list selection, etc.) would be registered by each Extension's own
`init_fields()` — every compatible extension in this repo (`MailChimp`, `ConvertKit`,
`ActiveCampaign`, `ZapierEmailSubscription`, `BitIntegrationsEmailSubscription`, `IFTTT`)
only implements `init_extension()` (title/module_title) and a stub `get_data()`; none of
them register `nx_content_fields`/`nx_design_tab_fields`/`nx_customize_fields` filters in
this free-plugin repo. _TODO: verify_ — the real per-source settings fields likely live in
`notificationx-pro`.

## Themes / templates

`$this->themes` (admin theme picker, `email_subscription_*` ids, source images under
[`assets/admin/images/extensions/themes/subscriptions/`](../../assets/admin/images/extensions/themes/subscriptions/)):

| Theme key | Image shape | Notes |
|---|---|---|
| `theme-one` | rounded | uses `$common_fields` |
| `theme-two` | circle | uses `$common_fields` |
| `theme-three` | square | uses `$common_fields` |
| `maps_theme` | square | `show_notification_image: 'maps_image'`; no `template` key set on the theme entry itself (falls back to the `mailchimp_template_new` template group below) |

`$this->res_themes` (responsive/mobile themes, source images under
[`assets/admin/images/extensions/themes/res_subscriptions/`](../../assets/admin/images/extensions/themes/res_subscriptions/)),
all `is_pro: true`:

| Theme key | `_template` |
|---|---|
| `res-theme-one` | `mailchimp_template_new` |
| `res-theme-two` | `mailchimp_template_new` |
| `res-theme-three` | `mailchimp_template_new` |
| `subscriptions-res-theme-four` | `maps_template_new` |

`$this->templates` defines one template group:

- `mailchimp_template_new` — `first_param` = `GlobalFields::common_name_fields()` (Full
  Name / First Name / Last Name); `third_param` = `tag_title` ("List Title"); `fourth_param`
  = `tag_time` ("Definite Time") / `tag_sometime` ("Some time ago"). Its `_themes` array
  references `email_subscription_theme-one`, `-two`, `-three`, and `-maps_theme`.
  A `maps_template_new` template (used by `subscriptions-res-theme-four`) is **not**
  defined in this file — _TODO: verify_, it may be a shared/global template registered
  elsewhere (e.g. by `CustomNotification` or a Pro-side class).

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/EmailSubscription.php`](../../includes/Types/EmailSubscription.php) |
| Base class | [`includes/Types/Types.php`](../../includes/Types/Types.php) |
| Trait | none |
| Extensions (data sources) | see table below |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'email_subscription' => 'NotificationX\Types\EmailSubscription'`), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) |
| Frontend runtime | `nxdev/notificationx/frontend/...` — _TODO: verify_ exact component; not inspected in this pass |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) — generic entries pipeline (no dedicated bucket) |

### Compatible extensions (data sources, `$types === 'email_subscription'`)

| Extension | `$id` (source) | `$module` | `is_pro` | File |
|---|---|---|---|---|
| MailChimp | `mailchimp` | `modules_mailchimp` | `true` | [`includes/Extensions/MailChimp/MailChimp.php`](../../includes/Extensions/MailChimp/MailChimp.php) |
| ConvertKit | `convertkit` | `modules_convertkit` | `true` | [`includes/Extensions/ConvertKit/ConvertKit.php`](../../includes/Extensions/ConvertKit/ConvertKit.php) |
| ActiveCampaign | `ActiveCampaign` | `modules_activecampaign` | `true` | [`includes/Extensions/ActiveCampaign/ActiveCampaign.php`](../../includes/Extensions/ActiveCampaign/ActiveCampaign.php) |
| Zapier | `zapier_email_subscription` | `modules_zapier` | `true` | [`includes/Extensions/Zapier/ZapierEmailSubscription.php`](../../includes/Extensions/Zapier/ZapierEmailSubscription.php) — uses the shared `Zapier` trait |
| BitIntegrations | `bitintegrations_email_subscription` | `modules_bitintegration` | not set on the class (defaults to `false` on `Extension`, but functionally gated by `$class = 'BitApps\Integrations\Config'` existence check) | [`includes/Extensions/BitIntegrations/BitIntegrationsEmailSubscription.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsEmailSubscription.php) |
| IFTTT | `ifttt` | `modules_ifttt` | `true` | [`includes/Extensions/IFTTT/IFTTT.php`](../../includes/Extensions/IFTTT/IFTTT.php) — **not registered** in `ExtensionFactory::$extension_classes` in this repo; the class exists but appears dead/unwired in the free plugin. _TODO: verify_ whether it's registered via the `nx_extension_classes` filter from `notificationx-pro`. |

Every `get_data()` above is a one-line stub (e.g. `return 'Hello From MailChimp';`) in this
free-plugin repo — none perform real API calls here.

## Dependencies

None of the compatible extensions require another WordPress plugin — they are all
external-service integrations (MailChimp, ConvertKit, ActiveCampaign, Zapier, IFTTT
accounts), except **BitIntegrations**, which requires the separate "Bit Integrations"
WordPress plugin to be installed (checked via `class_exists('BitApps\Integrations\Config')`
in `BitIntegrationsEmailSubscription::source_error_message()`). Real API credential
handling/config fields are not present in this free-plugin repo — see `notificationx-pro`.

## Testing notes & gotchas

- This entire type is Pro-gated (`$is_pro = true` on the Type, `is_pro = true` on nearly
  every extension, all `get_data()` are stubs) — do not expect working subscription
  notifications when testing against this repo alone; verify against `notificationx-pro`
  for real behaviour.
- `EmailSubscription::$module` lists `modules_mailchimp` twice and omits
  `modules_activecampaign` / `modules_ifttt` / `modules_bitintegration` — if you rely on
  this array for gating logic, confirm first whether anything actually reads
  `Types::$module` (no call site was found in this pass; see the `Reviews` type doc for
  the same open question).
- `IFTTT` extension is not present in `ExtensionFactory::$extension_classes` in this repo —
  confirm whether it's wired up via a filter in `notificationx-pro` before assuming it's
  reachable from the admin UI.
- `maps_theme` / `subscriptions-res-theme-four` reference a `maps_template_new` template
  that isn't defined in `EmailSubscription.php` itself — verify where it's actually
  registered before changing/removing it.
- No dedicated PHPUnit tests for this type were found under `tests/` in this pass.
  _TODO: verify_.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [Comments Notification Type](comments.md) and [Reviews Notification Type](reviews.md) —
  sibling types using the same generic entries pipeline
