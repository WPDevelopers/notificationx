# Live Visitors Extension (`modules_live_visitors`)

> A built-in live visitor count for Page Analytics ("12 people are looking at this
> page right now") that needs no Google Analytics account. Free ships the source card,
> its module and generic hooks; NotificationX Pro counts the visitors.

## At a glance

| | |
|---|---|
| **Integration** | Built-in (no third-party service) |
| **Directory** | [`includes/Extensions/LiveVisitors/`](../../includes/Extensions/LiveVisitors/) |
| **Module key (`$module`)** | `modules_live_visitors` (on by default, like every module) |
| **Feeds Types** | `page_analytics` |
| **Extension classes** | `LiveVisitors.php`: (`page_analytics`, `live_visitors`) |
| **Depends on** | NotificationX Pro that returns `true` from `nx_live_visitors_supported` |
| **Since** | x.x.x |

## What it does

Free registers a Pro-locked source, "Built-in (no Google Analytics)", next to Google
Analytics under Page Analytics. The source offers only the live designs
(`page_analytics_pa-theme-three`, and add-on designs via `nx_live_visitors_themes`),
and hides the "Total Site View" template option.

Free does no counting. Nothing runs on a site until a notification uses this source:
no route, no script, no table and no cron come from Free.

| Site | What the source card does |
|---|---|
| Free only | Locked, with the usual upsell popup. Its notifications are skipped on the frontend, like every Pro source. |
| Pro that counts visitors | Selectable; Pro adds the fields, the counter and the frontend script. |
| Older Pro (no `nx_live_visitors_supported`) | Locked with an "update NotificationX Pro" popup (`forced`, shown although Pro is active). Its notifications are kept off the page through `nx_show_on_exclude`. |

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| `LiveVisitors.php` | `page_analytics` | `live_visitors` | Stub. The count comes from NotificationX Pro. |

## Data flow

Free only provides the seams Pro uses:

1. **Builder:** the source card (`__nx_sources()`), the live designs (`get_themes()`,
   `get_res_themes()`) and the template options (`__notification_template()`).
   Page Analytics selects this source by default when the Google Analytics module is
   off and this module is on (`PageAnalytics::type_trigger()`).
2. **Growth Alert:** the WooCommerce "live viewers" design is offered when
   `LiveVisitors::live_viewers_available()`: the Google Analytics module is on, or this
   module is on and Pro counts visitors.
3. **REST:** Pro registers its public ping route itself and lists it in
   `nx_public_rest_routes`, so it passes the "Enable REST API" restriction (as a
   logged-out visitor), BuddyBoss and JWT Auth like `notice`. Only names in
   `notificationx/v1` are accepted, and the route must check its own input and rate
   limits. See [../api/hooks-filters.md](../api/hooks-filters.md#live-visitors-built-in-live-count).
4. **Frontend:** Pro's placeholder entry carries `nx_awaiting_data: true`, which
   `FrontEnd::filtered_data()` keeps. Free renders such an entry only after the JS
   filter `nx_frontend_entry` returns it with the count and the mark cleared, so if
   Pro's script fails to load the notification does not show at all, never with an
   empty number. Pro asks for a re-render with the `notificationx:refresh` window
   event.
   See [../api/frontend-js-hooks.md](../api/frontend-js-hooks.md).

## Fields & settings

None in Free. Pro adds the scope (site / this page), the minimum count, the admin
exclusion and the consent rule.

## Dependency & detection

- **Pro detects Free:** `defined( 'NOTIFICATIONX_LIVE_API' )` in PHP and
  `window.nxFrontendRuntime.liveApi` in JS.
- **Free detects Pro:** `LiveVisitors::is_supported()`, which is
  `NotificationX::is_pro()` and the `nx_live_visitors_supported` filter. Pro must add
  that filter before `init`, when the source card and the Growth Alert designs are built.
- No version numbers are compared.

## Key files

| Purpose | File |
|---|---|
| Extension class | `includes/Extensions/LiveVisitors/LiveVisitors.php` |
| Registration | `includes/Extensions/ExtensionFactory.php` |
| Default source of the type | `includes/Types/PageAnalytics.php` (`type_trigger()`) |
| Growth Alert gate | `includes/Extensions/WooCommerce/WooInline.php`, `WooCommerceSalesInline.php` |
| Public route allowlists | `includes/Core/REST.php` (`public_route_paths()`) |
| JS entry filter and refresh event | `nxdev/notificationx/frontend/core/NotificationContainer.tsx` |
| Runtime marker | `nxdev/notificationx/frontend/core/runtime.ts` (`liveApi`) |
| Marker constant | `notificationx.php` (`NOTIFICATIONX_LIVE_API`) |

## Testing notes & gotchas

- `tests/test-live-visitors-seams.php` and `tests/js/frontend/entry-filter.test.js`.
- Without the new hooks used, existing Google Analytics notifications must not
  change: Page Analytics still selects `google` while the GA module is on, and the
  live-viewers design still follows the GA module.
- A filter on `nx_frontend_entry` that returns something other than an object or
  `null` is ignored. Return `null` only before a notification shows: returning it
  for a notification already on screen unmounts it before its own timer removes it.
