# Facebook Reviews (Recommendations) — Implementation Plan

> **Status:** Phases 1–6 complete, verified against a live Apify run and in a real
> browser. Not shipped: the version bump, and the placeholder artwork noted below.
> Scope: add Facebook Recommendations as a data source for the existing `reviews` Type,
> sourced through the Apify `apify/facebook-reviews-scraper` actor. Free ships a working
> integration; `notificationx-pro` unlocks translation, higher caps, and filtering.

## Why an Apify actor, not the Graph API

Facebook's Graph API no longer exposes Page ratings/recommendations to third parties, so
there is no first-party endpoint to call. The public Apify actor is a **run-based async
API** — that is the one architectural difference from Google Reviews, whose
[`update_data()`](../../../notificationx-pro/includes/Extensions/Google/GoogleReviews.php)
is a single synchronous GET. Everything else (Type, themes, entries, cron, settings
section, Free→Pro subclassing) reuses the Google Reviews shape.

Facebook has no star ratings either — a recommendation is a boolean
(`isRecommended`). That removes the "average rating" concept and drives the theme and
template-tag choices below.

## Phase 0 decisions (locked)

| Decision | Value | Rationale |
| --- | --- | --- |
| Freemium line | **Free-functional with caps**, `$is_pro = false` | [`SingleNotificationX.tsx`](../../nxdev/notificationx/admin/SingleNotificationX.tsx) disables the campaign toggle for free users whenever `is_pro_sources[source]` is true — a Pro-flagged source cannot function in Free. Fetch logic therefore lives in **this** plugin; Pro carries only the delta. |
| `$id` | `facebook_reviews` | Matches the `<vendor>_reviews` convention already used by `wp_reviews`, `woo_reviews`, `zapier_reviews`, `freemius_reviews`, `bitintegrations_reviews`, `google_reviews`. Leaves the bare `facebook` id free for a future Ads/Conversions source. |
| `$module` | `modules_facebook_reviews` | |
| Namespace / file | `NotificationX\Extensions\Facebook` → `includes/Extensions/Facebook/FacebookReviews.php` | |
| Priorities | `$priority = 6`, `$module_priority = 21` | Directly after Google Reviews (`5` / `20`). |
| Themes | `reviewed`, `review-comment`, `review-comment-2`, `review-comment-3` | Suffixes **must** match the existing review themes: [`getThemeName()`](../../nxdev/notificationx/frontend/core/functions.ts) strips the source prefix when building the CSS class, so reusing these names means zero new SCSS. |
| `total-rated` theme | **not declared** | Recommendations have no star average, so the theme is simply absent rather than hidden by a rule. |
| Free cap | `resultsLimit` ≤ **10**, cache **fixed 720 min** | See the budget math below. |
| Pro cap | `resultsLimit` 1–100 (default 20), cache configurable, min 60 / default 360 | |

### The Free cap is set by Apify's free plan, not by taste

The actor costs **$1.40 per 1,000 reviews** and Apify's free plan covers roughly
**2,000 reviews/month**. Re-scraping the same page on a short interval burns that budget
for data that barely changes:

```
60-minute refresh  × 10 reviews = 24 × 30 × 10 = 7,200 reviews/month   ✗ 3.6× over the free plan
720-minute refresh × 10 reviews =  2 × 30 × 10 =   600 reviews/month   ✓ headroom for ~3 campaigns
```

So Free is pinned to a 12-hour refresh, which costs a merchant **$0** and loses nothing
functionally — Facebook recommendations are low-velocity. The settings description should
show the estimate: `(43200 ÷ cache_minutes) × resultsLimit × $0.0014`.

## Actor contract (verified against the live actor page)

```jsonc
// input
{
  "startUrls": [ { "url": "https://www.facebook.com/<page>/reviews" } ],  // objects, not strings
  "resultsLimit": 10
}
```

```jsonc
// one dataset item
{
  "id": "…", "legacyId": "…", "url": "…",          // `url` is the review URL
  "facebookUrl": "…", "inputUrl": "…",
  "date": "…", "isRecommended": true,
  "text": "…", "likesCount": 0, "commentsCount": 0,
  "tags": [], "pageName": "…", "facebookId": "…",
  "user": { "id": "…", "name": "…", "profileUrl": "…", "profilePic": "…" }   // nested
}
```

There is **no `lang` field and no translation or sort input**. Consequences: sorting is
done post-fetch on our side, and Pro's translation must auto-detect the source language.

## Field mapping

| Entry key | From | Notes |
| --- | --- | --- |
| `entry_key` | `md5($page_id . $item['id'])` | stable, gives idempotent re-inserts |
| `username` | `user.name` | |
| `profile_photo_url` | `user.profilePic` | |
| `place_name` | `pageName` | rendered by `tag_place_name`. **This is the page slug** (`copperkettleyqr`), not a display name — the actor exposes no display name at all. Phase 4 should add an optional "Page Display Name" builder field that falls back to this. |
| `place_review` | `text` | rendered by `tag_place_review` |
| `rating` | `isRecommended ? 5 : 1` | synthesized so star themes still work |
| `recommendation` | localized "Recommends" / "Doesn't recommend" | `tag_recommendation` renders `{{recommendation}}` verbatim, so store the **display string**, not the raw enum |
| `is_recommended` | `(bool) isRecommended` | machine field kept separately for Pro filtering |
| `url` | `url` ?: `facebookUrl` | `link_type = review_page` |
| `timestamp` | `strtotime($item['date'])` | |

## Phases

**Phase 1 — registration & UI (no network).** New extension class (properties, themes,
templates, image options, `preview_entry()`, `doc()`), one line in
[`ExtensionFactory`](../../includes/Extensions/ExtensionFactory.php), and
`composer dump-autoload` — autoloading is a **classmap**, so a new class is invisible
until it is dumped. Verify: Modules card appears, the source shows in the builder under
Reviews, all four themes preview correctly.

**Phase 2 — settings section & `connect()` (done).** Section
`facebook_reviews_settings_section` on the `nx_settings_tab_api_integration` filter, with
`facebook_reviews_apify_token`, `facebook_reviews_cache_duration`, and a Validate button.
Validation calls `GET https://api.apify.com/v2/users/me?token=…`, which **spends no actor
credits** — never validate by starting a run, and never persist the token until the call
succeeds. [`Helper::remote_post()`](../../includes/Core/Helper.php) was added alongside
the existing `remote_get()` for the Phase 3 run calls.

> **The "API Integrations" tab does not exist in the free plugin.** Both the tab and the
> `nx_settings_tab_api_integration` filter are created by
> [`NotificationXPro\Admin\Settings::settings_tab()`](../../../notificationx-pro/includes/Admin/Settings.php);
> the free `Settings` ships only General, Advanced, and Analytics. Since this source is
> Free-functional, `FacebookReviews::settings_tab()` stands the tab up itself, guarded by
> `isset($tabs['api_integrations_tab'])` so Pro's richer definition always wins when both
> plugins are active. Filter order does not matter: whichever runs second either skips
> (ours) or overwrites with a definition that applies the same inner filter (Pro's).
>
> This is a stopgap. The moment a **second** free source needs API credentials, move the
> tab scaffold into the free `Admin\Settings` rather than leaving it owned by an
> extension.

The cache-duration field is plan-aware: free installs get `default = min = 720` and the
input disabled; Pro gets `default 360 / min 60`. `get_cache_duration()` re-applies the
floor server-side so a crafted request cannot buy a cheaper interval.

**Phase 3 — fetch pipeline (done).** Async state machine driven by `update_data()`,
which does at most one thing per call and returns: start run
(`POST /v2/acts/apify~facebook-reviews-scraper/runs`) → poll (`GET /v2/actor-runs/{id}`)
→ fetch (`GET /v2/datasets/{id}/items`). Four rules, all implemented:

1. Run state lives in an **option** (`nx_facebook_reviews_run_{nx_id}`), not in the
   campaign row — the row's `data` blob is rewritten wholesale on every builder save,
   which would drop an in-flight run handle and make us pay for the same scrape twice.
   Cleared on `nx_delete_post`.
2. `MAX_POLLS` (20) plus a `started_at` stamp bound a stuck run.
3. `delete_notification()` runs **only after** a successful, non-empty fetch. Google
   deletes first; copying that would empty a live popup whenever the scraper fails.
4. A `signature` (page URL + limit) and a `last_success` stamp throttle refetches: a save
   and a cron tick landing together cannot buy the same reviews twice, while changing the
   URL or count deliberately invalidates the signature and refetches.

Because the refresh interval is measured in hours but a run finishes in seconds to
minutes, the extension schedules its **own** single event (`nx_facebook_reviews_poll`,
30s → 120s backoff) to poll a run to completion. `Cron::set_cron_single()` cannot be
reused for this: it no-ops when a recurring event for the same post already exists.

Register the cron interval through the `nx_cron_schedules` filter
([`Cron::cron_schedule()`](../../includes/Admin/Cron.php)) rather than editing `Cron.php`
— the Pro override calls `parent::cron_schedule()`, so one filter covers both plugins.

> **Do not copy Google's key mismatch.** Pro's `Cron.php` reads
> `settings.nx_google_review_cache_duration` while `GoogleReviews::connect()` writes
> `settings.google_review_cache_duration`, so that interval is permanently stuck at its
> 30-minute default. Read and write `settings.facebook_reviews_cache_duration`.

**Phase 4 — builder fields & display (done).** `facebook_reviews_page_url` and `_count`
shipped with Phase 3 because the fetch cannot run without them. Added here:

- `facebook_reviews_page_label` — optional **Page Display Name**. The actor returns only
  the page slug (`copperkettleyqr`) and no display name at all, so without this the
  `reviewed` theme shows a slug. Applied at render, so changing it costs no Apify run.
- `facebook_reviews_sort` — Newest / Oldest / Most Liked, applied post-fetch since the
  actor neither sorts nor accepts a sort input. "Most Liked" uses `likesCount` (now
  stored, along with `commentsCount`) and breaks ties by recency so ordering is stable.
- `conversion_data()` on `nx_filtered_entry_{$id}` — trims the review to 100 chars (80 on
  `review-comment-2` / `-3`), wraps `-2` in quotes, and **re-derives the recommendation
  label from `is_recommended`** so it follows the site's current language instead of the
  copy frozen at collection time. Trimming honours `nx_text_trim_length`, which is where
  Pro's `content_trim_length` control plugs in; our comment themes are registered on
  `nx_content_trim_length_dependency` so that control appears for them.

> **"Display From" had to be disabled for this source.** Recommendations are routinely
> years old — the live test returned reviews from 2024 — and
> [`FrontEnd`](../../includes/FrontEnd/FrontEnd.php) drops entries older than the
> Display From window, so **nothing would have rendered**. The source now returns false
> on `nx_entry_display_{$id}` (as Google Reviews does) and hides the `display_from` /
> `display_last` controls, which are meaningless for reviews pulled wholesale from a page.

**Phase 5 — Pro delta (done).** `notificationx-pro/includes/Extensions/Facebook/FacebookReviews.php`
mirror-subclasses the free class and is picked up automatically by the `GetInstance`
free→pro swap — it is **not** registered in either `ExtensionFactory`. It adds:

- `facebook_reviews_lang` (`no_translation` / `site_language` / `custom_language`) plus
  `facebook_reviews_custom_language`. Translation runs **once, at collection time**, and
  stores `text_translated` / `translated_to` next to the original, so display costs
  nothing. It reuses Pro's existing OpenAI credentials
  (`openai_access_token` / `openai_model` / `openai_max_tokens` from the API Integrations
  tab) and sends one batched request keyed by index. Any failure — no token, API error,
  unparseable reply — logs and returns nothing, leaving **every** entry on its original
  text rather than showing a half-translated set. At render, a missing translation always
  falls back to the original.
- Filtering: `facebook_reviews_recommendation_filter` (all / recommended only / not
  recommended only), `facebook_reviews_tags` (comma separated, matches Facebook's own
  review tags), and `facebook_reviews_min_length`.
- Higher caps need no Pro code: `get_results_limit()` and `get_cache_duration()` in the
  free class already branch on `NotificationX::is_pro()`, and the responsive themes come
  from the Type.

> Filtering is applied **after** the scrape, because the actor accepts no filter input —
> a filtered-out recommendation has already been paid for. Merchants who want fewer
> results should lower the review count rather than rely on filters.

The free class's `prepare_entries()` takes the whole `$settings` array (not just the sort
key) precisely so this subclass can read its filter and language options from the same
seam.

**Phase 6 — docs & release prep (done, except the version bump).**
[`docs/extensions/facebook.md`](../extensions/facebook.md) written from
[`_TEMPLATE.md`](../extensions/_TEMPLATE.md) with a row added to
[`docs/extensions/README.md`](../extensions/README.md); the Apify disclosure added to
`readme.txt`; both POT files regenerated (free 1994 → 2034 strings, pro 484 → 497).

`wpml-config.xml` needs no change — it declares only translatable *settings* values
(affiliate link, reporting email/subject), and nothing this source adds is one.

**Version bump deliberately not done** — `NOTIFICATIONX_VERSION` in `notificationx.php`
and `package.json` must still be bumped together when this ships.

> Regenerating the POT OOMs at PHP's default 128 MB (`npm run pot` fatals inside Peast
> while parsing bundled JS). Run it as
> `php -d memory_limit=3G $(command -v wp) i18n make-pot . languages/notificationx.pot --exclude='nxbuild'`
> until the script itself is fixed.

> **Free-tier compliance requirement.** Because the Free plugin now calls
> `api.apify.com`, the `== External services ==` section of
> [`README.txt`](../../README.txt) must disclose Apify (it currently lists only
> ip-api.com). WP.org plugin review checks this.

## Builder flicker: `$default_theme` must be the first theme

While building a Facebook Reviews campaign the Design tab's theme cards and parts of the
Content tab flickered endlessly. Measured in a headless browser: **4,628 DOM mutations in
6 seconds**, with the `themes` value oscillating between `facebook_reviews_reviewed` and
`facebook_reviews_review-comment` 26 times in 3 seconds.

Cause: two mechanisms set `themes` independently. The builder falls back to the **first
rendered option** when no value is stored, while
[`Extension::__source_trigger()`](../../includes/Extensions/Extension.php) applies
`$default_theme`. `$default_theme` was `..._review-comment` while `reviewed` was first in
the `$themes` array, so each mechanism kept overwriting the other.

Checked across every registered extension: only three had `$default_theme !== ` the first
theme key — this one plus `announcements` and `exit_intent_custom`, whose defaults name
themes that do not exist in their lists at all (pre-existing, not investigated here).
Every healthy extension satisfies the invariant.

Fix: reorder `$themes` so `review-comment` comes first, keeping the text-forward default
from the Phase 0 decision. After the change: **0 mutations in 6 seconds, one stable
state**, and switching to another theme stays stable too. The invariant is now documented
in [adding-an-extension.md](adding-an-extension.md).

## Security notes

- The Apify token lives in NotificationX settings and is read server-side only. Note that
  [`Settings::get_form_data()`](../../includes/Admin/Settings.php) already ships the whole
  settings array to the settings screen, so the token is visible to users holding
  `edit_notificationx_settings` — the same posture as the existing Google API key. A
  genuinely masked input would need a new `password` field type in `quickbuilder`, which
  does not have one today; that is a separate task and not a blocker here.
- Never let the token reach entry data, frontend payloads, or `error_log()` (Apify takes
  it as a `?token=` query param, so log messages, never full URLs).
- Scraped `text`, `user.name`, and `tags` are untrusted third-party content — escape on
  output and sanitize the page URL (`esc_url_raw`) and count (`absint`) on save.
- Displaying reviewer names and photos is personal data; keep the avatar toggle and
  document the GDPR angle in the user-facing docs.

## Open items

- Source icon and default avatar are **placeholder** renders committed at
  `assets/admin/images/extensions/sources/facebook-reviews.png` and
  `assets/public/image/icons/facebook-f-icon.png` — replace with final artwork.
- `$doc_link` points at a docs URL that does not exist yet.
- Actor slug `apify/facebook-reviews-scraper` is pinned; a rename/deprecation needs a
  fallback plan.

## Source
- [adding-an-extension.md](adding-an-extension.md) — the general procedure this plan follows.
- [../extensions/google.md](../extensions/google.md) — the template integration.
- [../../includes/Extensions/Facebook/FacebookReviews.php](../../includes/Extensions/Facebook/FacebookReviews.php)
