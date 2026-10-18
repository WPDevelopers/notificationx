# Analytics dashboard

The Analytics page (`admin.php?page=nx-analytics`) is split between free and NotificationX Pro, following WordPress.org guideline 5: free ships no Pro-gated report code.

## What free owns

- **Collecting.** [Core/Tracker.php](../../../includes/Core/Tracker.php) and `POST /track` ([Rest/Track.php](../../../includes/Core/Rest/Track.php)) record seen, click, close, submit and hover events from the frontend tracker ([frontend/core/tracker.ts](../../../nxdev/notificationx/frontend/core/tracker.ts)), on free too. The hourly `nx_analytics_rollup` cron rolls them into `nx_stats_daily`.
- **Retention.** Free keeps 90 days of `nx_stats_daily`, so a site that upgrades to Pro already has history. Raw events are kept 30 days. The legacy `nx_stats` table is never purged.
- **Reset.** `DELETE /analytics/report/data` ([Rest/AnalyticsReports.php](../../../includes/Core/Rest/AnalyticsReports.php)) deletes the views, clicks and tracked data of one notification (`nx_id`) or of all. Leads (entries) are kept.
- **The page shell.** [admin/AnalyticsV2/AnalyticsApp.tsx](../../../nxdev/notificationx/admin/AnalyticsV2/AnalyticsApp.tsx): the all-time cards, the side menu, the theme toggle, the sticky bar and the reset dialog. Without Pro every view shows a preview ([ProScreen.tsx](../../../nxdev/notificationx/admin/AnalyticsV2/ProScreen.tsx)).

## What Pro adds

`NotificationXPro\Core\Rest\AnalyticsReports` extends free's class (swapped in by the `GetInstance` trait) and registers the report routes in `register_routes()`: `summary`, `notifications`, `notification/{id}`, `audience`, `leads` and `export`. Free has no report routes, so they return 404 without Pro.

Pro renders the views, the drawer and the filter bar through the admin filters below.

## Hooks

### Admin JS (`@wordpress/hooks`)

| Filter | Returns | Args |
| --- | --- | --- |
| `nx_analytics_reports` | `true` when the reports are registered. Without it the page shows previews; with an active Pro that returns `false` (an older Pro) the preview asks to update Pro. | `false` |
| `nx_analytics_controls` | The filter bar (type, notification, range, compare, export) before the theme toggle. | `null`, `{ view, range, setRange, nxId, setNxId, type, setType, compare, setCompare, options, setOptions, reloadKey, slot }` |
| `nx_analytics_view` | The current view (`overview`, `audience`, `notifications`, `leads`). | `null`, `{ view, range, nxId, type, compare, isPro, reloadKey, onOpen }` |
| `nx_analytics_drawer` | The per-notification drawer, rendered while `open` is set. | `null`, `{ id, range, reloadKey, onClose, onReset }` |

`options` (set by the controls) gives the reset dialog the notification titles. `slot` is an empty element below the bar; the controls portal the custom date panel into it. `reloadKey` changes after a reset; views refetch when it does.

### PHP

| Filter | Modifies | Args |
| --- | --- | --- |
| `nx_analytics_retention_days` | Days of `nx_stats_daily` to keep; `0` keeps everything. Pro returns the `analytics_retention` setting. | `90` |
| `nx_pro_analytics_features` | `true` when the active Pro applies `analytics_retention`. Free unlocks that setting only then, so an older Pro doesn't show a setting it ignores. | `false` |
| `nx_analytics_tracking_enabled` | Whether events are collected. | setting `enable_analytics` |
| `nx_analytics_channel` | The channel of a visit (`ai`, `search`, `social`, …). | `$channel`, `$host`, `$referrer` |
| `nx_analytics_country` | The visitor's country code. | `$code` |

## Mixed versions

Free auto-updates from WordPress.org and Pro through the licensed channel, so new free often runs with old Pro:

- **New free + old Pro:** previews with "Update NotificationX Pro", and the retention setting stays locked. Nothing breaks.
- **Old free + new Pro:** the old free page never calls Pro's filters, and Pro's `AnalyticsReports` is loaded only through free's `AnalyticsReports::get_instance()`, which old free doesn't have. Pro must never reference that class directly.
