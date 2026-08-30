# Facebook Reviews

Facebook Page reviews as a `reviews`-type source, delivered through the managed
**NotificationX API** (`api.notificationx.com/facebook-reviews/v1`). Functional
in the free plugin; Pro adds review filters and a configurable refresh interval.

| | |
|---|---|
| **Module key** | `modules_facebook_reviews` |
| **Source id** | `facebook_reviews` |
| **Feeds Types** | `reviews` |
| **Extension classes** | Free: [`includes/Extensions/Facebook/FacebookReviews.php`](../../includes/Extensions/Facebook/FacebookReviews.php) · API client: [`FacebookReviewsManaged.php`](../../includes/Extensions/Facebook/FacebookReviewsManaged.php) · REST: [`includes/Core/Rest/FacebookReviews.php`](../../includes/Core/Rest/FacebookReviews.php) · Pro: `notificationx-pro/includes/Extensions/Facebook/FacebookReviews.php` |
| **Builder field** | [`nxdev/notificationx/fields/FacebookReviewsConnection.tsx`](../../nxdev/notificationx/fields/FacebookReviewsConnection.tsx) (`type: facebook-reviews-connection`, `mode: builder|settings`) |
| **Depends on** | Nothing on the site. The NotificationX API owns the Meta app and every Facebook token. |

## Where connecting happens

All of it lives in **Settings → API Integrations** (`mode: settings`). The
Content step of a campaign (`mode: builder`) is a dropdown of the Pages already
connected and nothing else: every connect path navigates away from the builder
(OAuth to Facebook, or a lookup round trip) and an unsaved campaign would not
survive it.

A campaign whose stored `connection_id` is no longer connected says so, and
points at the settings tab rather than offering to reconnect in place.

## Connecting a Page

Two ways. Which are offered comes from the API (`connect_modes` on
`facebook-reviews/connections`), not from an assumption here — a deployment
whose API has no Meta app would otherwise advertise a login that 404s.

* **`oauth`** — the Facebook login, below. Meta asserts the person manages the
  Page. Strongest, but needs a Meta app with App Review and Business
  Verification behind it.
* **`attested`** — the admin pastes the Page address and proves control of it:
  either the Page already lists this site as its website, or they add a one-time
  `nx-verify-…` code to it. No Meta app, works immediately.
* **`open`** — the admin names the Page and it connects. No proof at all, the
  same shape EmbedPress ships for Google Reviews. Lowest friction; the trade is
  that nothing stops a site naming a Page it has nothing to do with.

### `open` mode: what the admin actually does

```
[ Connect ] pressed
      │
      ├─ the plugin looks for a Page the site already advertises
      │  (Yoast / Rank Math social settings → theme mods → nav menus → homepage HTML)
      │
      ├─ found  → "Found on your site: facebook.com/YourPage"  ← one click, no typing
      └─ not    → paste box
      │
      ▼
POST facebook-reviews/page-preview  → Anna's Bakery ⭐4.8 · 212 ratings
      ▼
POST facebook-reviews/page-connect  → connected
```

Best case one click, worst case one paste and one click.

Discovery (`FacebookPageFinder`) is local — option reads plus at most one request
to the site's own homepage. It is a **suggestion, never a claim**: what turns up
may be a partner's Page, an employee's profile or a stale link, so it is always
offered for the admin to confirm and never connected on its own. It filters out
Facebook's own plumbing, which litters every site's markup — `sharer.php`, the
`/tr` pixel, `/plugins/like.php`, the SDK — because otherwise "your Page" comes
back as the share button.

The preview step exists for the same reason: a pasted URL is opaque, and seeing
the name and rating before committing is what stops a typo becoming a live
connection that only reveals itself when the wrong reviews appear.

```
POST /notificationx/v1/facebook-reviews/attest-start  {page_url} → {page, token, methods}
     (the UI tries verify straight away — a Page that already lists this site
      needs nothing from the admin, and instructions they do not need are their
      own kind of failure)
POST /notificationx/v1/facebook-reviews/attest-verify {page_url} → {connection}
```

An attested connection comes back with `connect_mode: attested` and behaves
identically from here on: same entries, same themes, same webhook, same pull.
The API holds no Facebook token for it, because there is none.

The API's rejection message is passed through verbatim — it names the code to
add or the domain to set, so replacing it with something generic would leave the
admin with no way forward.

## How it works (OAuth)

```
Settings → API Integrations, "Connect Facebook Page"
  → POST /notificationx/v1/facebook-reviews/oauth-start   (site registers with the API on first use)
  → browser → Facebook login (Meta) → API callback
  → back to the same admin URL with ?nx_fb_session=…&nx_fb_status=ok
  → GET  /notificationx/v1/facebook-reviews/pages?session_id=…   → Page picker
  → POST /notificationx/v1/facebook-reviews/pages-connect        → {connection}

Builder → Content → "Facebook Page" dropdown
  → campaign stores  facebook_reviews_connection = {connection_id, page_id, page_name}
```

Data reaching the site:

* **Page summary** (`overall_star_rating`, `rating_count`) — pulled from the API on
  save and on the `nx_facebook_reviews_interval` cron; stored as one entry per
  campaign (`entry_key = summary_{page_id}`, data: `place_name, rated, rating, url`).
  Rendered by the `total-rated` theme (`tag_rated` / `tag_place_name` / `tag_rating`).
* **Individual reviews** — stored with
  `entry_key = md5('facebook_reviews|page_id|review_id')`, so the same review is never
  inserted twice. Rendered by the `review-comment*` / `reviewed` themes.

A campaign shows **one or the other, never both**. `wants_summary()` decides from
the template: `first_param = tag_rated` means the campaign is the aggregate kind.
Mixing them would drop a nameless, wordless summary entry into a per-review
rotation, where it renders as "  just reviewed Example Business".

### Two delivery paths

Individual reviews arrive two ways, and both produce identical entries because
both run through `FacebookReviews::map_review()`:

* **Push** — the API POSTs each review to
  `/wp-json/notificationx/v1/social-review` as it is collected. Fast, and the
  normal path.
* **Pull** — `sync_reviews()` reads `GET reviews.php` on the same cron that
  refreshes the page rating. This is not merely a fallback: a large share of
  installs are simply not reachable from the public internet (local and staging
  sites, anything behind basic auth, an IP allowlist or a firewall) and for those
  push can never arrive. It also repairs what push cannot — a restored backup, a
  migrated install, or a campaign created after the reviews were already
  collected. Responses are ETagged, so the usual "nothing new" answer costs one
  conditional request; it walks newest-first and stops as soon as a page adds
  nothing new, so the steady-state cost is one request.

"Sync now" (`POST /notificationx/v1/facebook-reviews/sync`) asks the API to
collect the Page again *and* pulls whatever it already holds, so the button does
something visible immediately even though collection is asynchronous.

### Handling ragged data

Facebook reviews are ragged by nature, and none of these are errors:

| Missing | What renders |
|---|---|
| Reviewer name (withheld by their privacy settings) | "A Facebook user" |
| Reviewer photo (ditto), or the aggregate entry | the Facebook mark — an imageless card in an avatar layout reads as broken |
| Review text (a bare thumbs-up) | the Page name, so the sentence stays true |
| Star rating (every current review — Facebook uses Recommends / Doesn't recommend) | `tag_recommendation`, not an invented five stars |
| Permalink | the Page's reviews tab |
| An exact date (most reviews — Facebook renders "2 weeks ago") | the source's own label via `time_label`, rather than a computed relative time claiming precision we do not have |
| Any date at all | the collection time, never "now" — a years-old review must not read as "a few seconds ago" |

Reviews are also exempt from the age-based display controls (`display_from` /
`display_last` are hidden for this source, as they are for Google Reviews). A Page
may collect a handful of reviews a year, so a "last N days" window would routinely
render nothing.

### Meta limitation (verified 2026-08-23)

Meta deprecated the Page Recommendations API in Graph API v22.0: individual Page
reviews cannot be read through any supported endpoint. Identity, ownership and
the aggregate rating still come from Graph; the individual reviews come from a
**collector** on the API side, which is configured per deployment and off by
default. The builder reflects whatever the API reports in
`capabilities.individual_reviews`, so it tells the truth for the deployment the
site is actually talking to. Nothing on the WordPress side changes when the
collection strategy changes. Sources: Meta Graph API changelog v22.0, Page object
reference (`overall_star_rating`, `rating_count`).

### Site identity

`FacebookReviewsManaged::connect()` registers the site (`site_url`, install
fingerprint, plugin/WP version, tier, Pro licence key) and stores the returned
Bearer token in option `nx_facebook_reviews_managed_auth` (autoload off). Every
later call carries `Authorization: Bearer`, `X-NotificationX-Token`,
`X-NotificationX-Fingerprint`, `X-NotificationX-Site`. On
`invalid_token | site_mismatch | fingerprint_mismatch` the token is dropped and the
next "Connect Facebook Page" click re-registers. Settings → API Integrations lists
connected Pages and can disconnect a Page or the whole site.

### Webhook (API → site)

`POST /wp-json/notificationx/v1/social-review`, public route authenticated by
HMAC: `X-NX-Signature: sha256=HEX(HMAC_SHA256(site_token, "{X-NX-Timestamp}.{X-NX-Delivery-Id}.{raw_body}"))`,
timestamp within ±300 s. Replays (same delivery id within 24 h) answer **409**,
bad/expired signatures **401**, malformed payloads **422**. The payload is
`{event, connection_id, review_id, page_id, page_name, page_rating{overall,count},
reviewer{name,avatar,url}, rating, recommendation_type, content, review_url,
created_at, updated_at, meta{}}`. `meta` is an open bag of source-specific
extras (tags, photos, engagement counts, the Page's own reply, provenance,
`relative_time`, `date_is_approximate`); unknown keys are ignored, so the API can
add one without needing a plugin release.
Entries are created in every campaign whose `facebook_reviews_connection.connection_id`
matches; Pro filters run through `nx_can_entry_facebook_reviews`.

## Pro

* `facebook_reviews_recommendation` (all / only "Recommends") — the filter that
  applies to current Facebook data, which is binary rather than starred.
* `facebook_reviews_min_rating` (1/4/5) — only bites on the older star-rated
  reviews some Pages still carry. A recommendation has no stars, and treating its
  absent rating as a zero would silently hide every current review the moment
  someone set a minimum.
* `facebook_reviews_text_only`, `facebook_reviews_min_length` — applied to
  individual reviews, never to the summary entry.
* `settings.facebook_reviews_refresh_interval` (minutes, min 30; free fixed at 720).
* Sends the licence key via the `nx_facebook_reviews_license_key` filter so the API can grant Pro limits.

## Tests

[`tests/test-facebook-reviews.php`](../../tests/test-facebook-reviews.php) covers
the mapping (including every ragged case above), hostile input, entry-key
stability, the aggregate/individual split and webhook signature verification.

## Local development

Point the plugin at a local API with
`add_filter('nx_facebook_reviews_managed_endpoint', fn() => 'http://127.0.0.1:8788/facebook-reviews/v1');`
(plain http is only honoured for localhost / `.test` / `.local` hosts). The API
service README documents its own setup and a signed-webhook example.
