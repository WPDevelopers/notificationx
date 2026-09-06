# Brevo Extension (`modules_brevo`)

> Connects NotificationX to [Brevo](https://www.brevo.com/) (formerly Sendinblue) and
> surfaces recent contacts of a chosen Brevo contact list as Email Subscription
> notifications — "Jane just subscribed to Newsletter".

## At a glance

| | |
|---|---|
| **Integration** | Brevo (formerly Sendinblue) |
| **Directory** | [`includes/Extensions/Brevo/`](../../includes/Extensions/Brevo/) |
| **Module key(s) (`$module`)** | `modules_brevo` |
| **Feeds Types** | `email_subscription` |
| **Extension classes** | `Brevo.php` → `(email_subscription, brevo)` |
| **Depends on** | A Brevo account and a v3 API key. No WordPress plugin required. |
| **Pro** | `$is_pro = true` — this class is the upsell stub; the working implementation lives in `notificationx-pro` ([doc](../../../notificationx-pro/docs/extensions/brevo.md)). |

## What it does

This file is the **Free-side stub**. It registers `brevo` in the Notification Source
picker under the Email Subscription type, carries the icon, doc link and module key,
and marks itself `is_pro`, so a Free user sees the source and the upgrade prompt.
All fetching, settings and webhook handling live in the Pro subclass
`NotificationXPro\Extensions\Brevo\Brevo`, which `GetInstance` substitutes
automatically when Pro is active.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| `Brevo.php` | `email_subscription` | `brevo` | Stub — returns a placeholder string. Real data comes from the Pro subclass. |

## Theme exclusions

Unlike the other Email Subscription sources, Brevo **hides two themes** it cannot
fill. A Brevo contact carries no geo data (no `location` object the way MailChimp
returns one, and no lat/lon attribute), so the Maps theme would render an empty map
for every entry:

| Excluded | Where |
|---|---|
| `email_subscription_maps_theme` | `Brevo::UNSUPPORTED_THEMES`, applied in `get_themes()` |
| `email_subscription_subscriptions-res-theme-four` | `Brevo::UNSUPPORTED_RES_THEMES`, applied in `get_res_themes()` |

The mechanism is `Extension::__nx_themes()`: it appends the current source to each
returned theme's `includes source` rule, so a theme the class never returns simply
never lists `brevo` and stays out of the picker **for this source only**. Every other
Email Subscription source keeps both themes.

## Fields & settings

Registered by the Pro subclass, not here:

| Key | Where | Purpose |
|---|---|---|
| `brevo_list` | Content tab (`nx_content_fields`) | List picker, options cached in `nxpro_brevo_lists` |
| `brevo_api_key` | Settings → API Integrations | v3 API key. Listed in `Settings::get_secret_settings_keys()` so it is redacted from REST/export. |
| `brevo_cache_duration` | Settings → API Integrations | Poll interval in minutes, drives `nx_brevo_interval` |
| `brevo_enable_webhook` / `brevo_webhook_url` | Settings → API Integrations | Optional real-time refresh |

`brevo_list` is also added to:
- [`Core/QuickBuild.php`](../../includes/Core/QuickBuild.php) `show` allowlist — otherwise
  the Quick Builder hides the field.
- [`FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) `$ignore_props` — so the
  configured list id is not shipped to the browser.

## Key files

| Purpose | File |
|---|---|
| Free stub | `includes/Extensions/Brevo/Brevo.php` |
| Pro implementation | `../notificationx-pro/includes/Extensions/Brevo/Brevo.php` |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) |
| Secret settings | [`includes/Admin/Settings.php`](../../includes/Admin/Settings.php) |
| Quick Builder allowlist | [`includes/Core/QuickBuild.php`](../../includes/Core/QuickBuild.php) |
| Frontend prop stripping | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) |
| Shared fields / source rules | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Icon | `assets/admin/images/extensions/sources/brevo.png` |

## Testing notes & gotchas

- Adding a new source means touching **six** free-side files, not one — see Key files.
  Missing the QuickBuild allowlist or the `$ignore_props` entry both fail silently.
- After adding the class, the Composer **classmap** must be updated
  (`vendor/composer/autoload_classmap.php` + `autoload_static.php`). Running a bare
  `composer dump-autoload` on a dev machine can rewrite unrelated boilerplate — add
  the two lines by hand, as the ActiveCampaign commit did.
- Verify the theme exclusion did not leak: `mailchimp` and `convertkit` must still
  offer `maps_theme`.

## Related docs

- [Pro implementation doc](../../../notificationx-pro/docs/extensions/brevo.md)
- [MailChimp](mailchimp.md), [ConvertKit](convertkit.md), [ActiveCampaign](activecampaign.md) — the sibling Email Subscription sources
- [Adding a New Notification Type](../development/adding-a-notification-type.md)
