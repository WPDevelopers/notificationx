# Facebook Extension (`modules_facebook_reviews`)

> Surfaces a Facebook Page's **Recommendations** ("recommends" / "doesn't recommend") as
> Reviews notifications. Facebook's Graph API no longer exposes page reviews to third
> parties, so the data is collected through the public Apify actor
> `apify/facebook-reviews-scraper`. Unlike Google Reviews, this source is **fully
> functional in the free plugin**; `notificationx-pro` adds translation, filtering, and
> higher caps.

## At a glance

| | |
|---|---|
| **Integration** | Facebook Reviews (Recommendations), via Apify |
| **Directory** | [`includes/Extensions/Facebook/`](../../includes/Extensions/Facebook/) |
| **Module key(s) (`$module`)** | `modules_facebook_reviews` |
| **Feeds Types** | `reviews` ([`Types\Reviews`](../../includes/Types/Reviews.php)) |
| **Extension classes** | `FacebookReviews.php` → `$id = 'facebook_reviews'`, `$types = 'reviews'` |
| **Depends on** | An Apify account + API token (`facebook_reviews_apify_token`). No `$class`/`$function`/`$constant` guard — there is no local plugin to detect; the token check drives `source_error_message()` instead. |

## What it does

The merchant pastes a Facebook Page **reviews URL**
(`https://www.facebook.com/<page>/reviews`) into the campaign and an **Apify API token**
into *Settings → API Integrations → Facebook Reviews Settings*. NotificationX then runs
the Apify actor on a schedule and turns each returned recommendation into one entry,
displayed with the shared `reviews` themes.

Facebook has no star ratings — a recommendation is a boolean. The extension therefore
ships no star-average theme, adds a `tag_recommendation` template tag rendering a
localized "Recommends" / "Doesn't recommend" badge, and synthesizes `rating = 5|1` so the
existing star markup still works for merchants who want it.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source |
|---|---|---|---|---|
| [`FacebookReviews.php`](../../includes/Extensions/Facebook/FacebookReviews.php) | `reviews` | `facebook_reviews` | `modules_facebook_reviews` | `update_data()` drives the Apify run; no `get_data()` |
| `notificationx-pro/includes/Extensions/Facebook/FacebookReviews.php` | (same) | (same) | (same) | Mirror subclass adding translation + filtering. **Not** registered in `ExtensionFactory` — picked up by the [`GetInstance`](../../includes/GetInstance.php) free→pro swap |

## Data flow

Apify is a **run-based async API**, which is the one structural difference from Google
Reviews' single synchronous request. `update_data()` is a state machine that performs at
most one step per call and returns:

1. **Start** — `POST /v2/acts/apify~facebook-reviews-scraper/runs` with
   `{ startUrls: [{ url }], resultsLimit }`. The run id and dataset id are stored in the
   option `nx_facebook_reviews_run_{nx_id}`.
2. **Poll** — `GET /v2/actor-runs/{id}`. `RUNNING`/`READY` reschedules; `SUCCEEDED`
   continues; anything else clears the run and logs.
3. **Fetch** — `GET /v2/datasets/{id}/items`, mapped to entries, then
   `delete_notification()` + `update_notifications()` → [`Entries`](../../includes/Admin/Entries.php).

Because the refresh interval is measured in hours but a run finishes in seconds to
minutes, the extension schedules its own single event `nx_facebook_reviews_poll`
(30s → 120s backoff, `MAX_POLLS = 20`) to poll a run to completion.
[`Cron::set_cron_single()`](../../includes/Admin/Cron.php) cannot be reused: it no-ops
while a recurring event exists for the same post.

Three invariants worth preserving:

- Run state lives in an **option**, not the campaign row — the row's `data` blob is
  rewritten wholesale on save, which would drop an in-flight run handle and cause a
  second, paid-for scrape. It is deleted on `nx_delete_post`.
- Existing entries are deleted **only after** a successful, non-empty fetch, so a failed
  scrape never empties a live popup.
- A `signature` (page URL + count) plus a `last_success` stamp throttle refetches, so a
  save landing next to a cron tick cannot buy the same reviews twice.

Entries also opt out of the age filter via `nx_entry_display_{$id}` → `__return_false`:
recommendations are routinely years old and the "Display From" window would otherwise
drop every one of them. The `display_from` / `display_last` controls are hidden for this
source for the same reason.

## Fields & settings

**Settings → API Integrations → `facebook_reviews_settings_section`**

| Key | Notes |
|---|---|
| `facebook_reviews_apify_token` | Validated by `connect()` against `GET /v2/users/me`, which spends **no** actor credits. Nothing is persisted unless validation succeeds. |
| `facebook_reviews_cache_duration` | Free: fixed 720 min (input disabled). Pro: min 60 / default 360. `get_cache_duration()` re-applies the floor server-side. |

> The free plugin has no API Integrations tab — it is created by
> [`NotificationXPro\Admin\Settings`](../../../notificationx-pro/includes/Admin/Settings.php).
> `FacebookReviews::settings_tab()` stands one up when Pro is absent, guarded so Pro's
> richer definition always wins. Move this scaffold into the free `Admin\Settings` as
> soon as a second free source needs credentials.

**Content tab** (all gated `Rules::is('source', 'facebook_reviews')`)

| Key | Tier | Notes |
|---|---|---|
| `facebook_reviews_page_url` | Free | The page's reviews URL |
| `facebook_reviews_page_label` | Free | Optional display name — the actor returns only the page **slug** |
| `facebook_reviews_count` | Free ≤ 10 · Pro ≤ 100 | `resultsLimit`; clamped by `get_results_limit()` |
| `facebook_reviews_sort` | Free | Newest / Oldest / Most Liked, applied post-fetch — the actor neither sorts nor accepts a sort input |
| `facebook_reviews_lang` (+ `_custom_language`) | Pro | Translation at collection time; stores `text_translated` alongside the original |
| `facebook_reviews_recommendation_filter`, `_tags`, `_min_length` | Pro | Applied post-fetch |

Image options `fbreview_avatar` / `fbreview_icon` are added to `show_notification_image`,
plus a `facebook-f-icon.png` default avatar.

## Dependency & detection

There is no local plugin to detect. The gate is the Apify token: when it is empty,
`source_error_message()` renders an admin notice deep-linking the settings section.
Module gating works as usual — `modules_facebook_reviews` off means nothing registers.

## Key files

| Purpose | File |
|---|---|
| Extension class (free) | `includes/Extensions/Facebook/FacebookReviews.php` |
| Extension class (pro) | `notificationx-pro/includes/Extensions/Facebook/FacebookReviews.php` |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) |
| HTTP helpers | [`includes/Core/Helper.php`](../../includes/Core/Helper.php) (`remote_get()` / `remote_post()`) |
| Implementation plan & decisions | [../development/facebook-reviews-implementation-plan.md](../development/facebook-reviews-implementation-plan.md) |

## Testing notes & gotchas

- **Every run costs money** (~$1.40 per 1,000 reviews). Validate tokens with
  `/v2/users/me`, never by starting a run, and keep `resultsLimit` small when testing.
- `$default_theme` **must** be the first entry of `$themes`. When they disagree the
  builder's theme cards flicker endlessly — see
  [adding-an-extension.md](../development/adding-an-extension.md).
- Actor output is nested and easy to get wrong: reviewer fields live under `user`
  (`user.name`, `user.profilePic`), the review URL is `url` (not `reviewUrl`), and
  `pageName` is the page **slug**. There is no language field, so Pro's translation must
  auto-detect.
- Scrapers break when Facebook changes markup. A `FAILED` run or an empty dataset must
  leave the previous entries in place.
- Pro filtering runs after the scrape — filtered-out reviews have already been paid for.

## Related docs

- [Implementation plan](../development/facebook-reviews-implementation-plan.md)
- [Adding an Extension](../development/adding-an-extension.md)
- [Google Extension](./google.md) — the integration this one is modelled on
- [Reviews Type](../types/) docs
