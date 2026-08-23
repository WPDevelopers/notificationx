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

## How it works

```
Builder "Connect Facebook Page"
  → POST /notificationx/v1/facebook-reviews/oauth-start   (site registers with the API on first use)
  → browser → Facebook login (Meta) → API callback
  → back to the same admin URL with ?nx_fb_session=…&nx_fb_status=ok
  → GET  /notificationx/v1/facebook-reviews/pages?session_id=…   → Page picker
  → POST /notificationx/v1/facebook-reviews/pages-connect        → {connection}
  → campaign stores  facebook_reviews_connection = {connection_id, page_id, page_name}
```

Data reaching the site:

* **Page summary** (`overall_star_rating`, `rating_count`) — pulled from the API on
  save and on the `nx_facebook_reviews_interval` cron; stored as one entry per
  campaign (`entry_key = summary_{page_id}`, data: `place_name, rated, rating, url`).
  Rendered by the `total-rated` theme (`tag_rated` / `tag_place_name` / `tag_rating`).
* **Individual reviews** — pushed by the API to
  `POST /wp-json/notificationx/v1/social-review` (see below) and stored with
  `entry_key = md5('facebook_reviews|page_id|review_id')`, so the same review is never
  inserted twice. Rendered by the `review-comment*` / `reviewed` themes.

### Meta limitation (verified 2026-08-23)

Meta deprecated the Page Recommendations API in Graph API v22.0: individual Page
reviews cannot be read through any supported endpoint. The API's Facebook provider
therefore only supplies the page summary and reports `individual_reviews: false`
(shown in the builder as "Individual reviews: not provided by Facebook"). Individual
reviews start flowing automatically once the API gains a provider that can deliver
them — nothing changes on the WordPress side. Sources: Meta Graph API changelog
v22.0, Page object reference (`overall_star_rating`, `rating_count`).

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
`{event: social_review.created, connection_id, review_id, page_id, page_name, reviewer{name,avatar}, rating, recommendation_type, content, review_url, created_at}`.
Entries are created in every campaign whose `facebook_reviews_connection.connection_id`
matches; Pro filters run through `nx_can_entry_facebook_reviews`.

## Pro

* `facebook_reviews_min_rating` (1/4/5), `facebook_reviews_text_only`, `facebook_reviews_min_length` — applied to webhook reviews, never to the summary entry.
* `settings.facebook_reviews_refresh_interval` (minutes, min 30; free fixed at 720).
* Sends the licence key via the `nx_facebook_reviews_license_key` filter so the API can grant Pro limits.

## Local development

Point the plugin at a local API with
`add_filter('nx_facebook_reviews_managed_endpoint', fn() => 'http://127.0.0.1:8788/facebook-reviews/v1');`
(plain http is only honoured for localhost / `.test` / `.local` hosts). The API
service README documents its own setup and a signed-webhook example.
