# Facebook Reviews Extension (`modules_facebook_reviews`)

> Connects NotificationX to a **Facebook Page** through the site owner's own Meta
> app and surfaces the Page's aggregate rating ("128 people rated ★4.8 on
> Facebook") **and its individual recommendations** ("Someone recommends
> Example Page — *Excellent service!*") as `reviews` notifications. Facebook
> never shares who wrote a recommendation, so reviewers are shown as "Someone".

## At a glance

| | |
|---|---|
| **Integration** | Facebook Reviews |
| **Directory** | [`includes/Extensions/Facebook/`](../../includes/Extensions/Facebook/) (free), `notificationx-pro/includes/Extensions/Facebook/` (pro) |
| **Module key(s) (`$module`)** | `modules_facebook_reviews` |
| **Feeds Types** | `reviews` |
| **Extension classes** | free `FacebookReviews.php` (`reviews`, `facebook_reviews`) — Pro-gated stub; pro `FacebookReviews.php` extends it with OAuth, Graph API, cron |
| **Depends on** | A Meta app (type *Business*) owned by the site owner — App ID + App Secret entered in NotificationX settings; the site must be served over **HTTPS** (Meta rejects `http://` redirect URIs). No local plugin dependency. |

## Meta API status (verified live 2026-08-23)

| Question | Answer |
|---|---|
| Is the reviewer's name/photo obtainable? | **No — ruled out empirically (2026-08-24).** `reviewer{id,name,picture}` is silently omitted from every row; bare `fields=reviewer` returns `{"data":[]}` (rows collapse because the field is empty); `open_graph_story{id,from{...}}` returns rows with `from` absent; `/{page}/visitor_posts` is unavailable on New Pages. The docs still list `reviewer` (User) on the Recommendation node, but since the v3.0 (Apr 2018) platform lockdown a User node resolves only for people who authorized *this* app — reviewers never did. No permission or App Review changes this, so reviews are attributed to "Someone". |
| Can individual Page reviews/recommendations be read? | **Yes, in practice.** The v22.0 changelog says `GET /{page-id}/ratings` is deprecated (error 12), but a Page token that carries **`pages_read_user_content`** still returns data on v26.0 (verified in the Graph API Explorer with a real Page). Without that scope Meta answers `#283`. Treat this as at-risk: `GraphClient` maps a future error 12 to `facebook_reviews_unavailable` and the UI degrades to the rating-only theme. |
| What does a review contain? | `review_text` (may be empty — `has_review:false`), `recommendation_type` (`positive` / `negative`), `created_time`, `open_graph_story.id` (used as the stable id). |
| What is **not** available? | The **reviewer** (name, picture) — Meta only returns it for users who authorized the app themselves; **star rating** (`has_rating:false` since Facebook moved to recommendations in 2018); a per-review **permalink** (`permalink_url` does not exist on the story). |
| Aggregate data | Page fields `overall_star_rating` (1–5, omitted for few ratings) and `rating_count`. **Pages that migrated to recommendations report `rating_count: 0`** even with recommendations present, and the edge supports neither `summary=total_count` (times out) nor `summary=true` (no `summary` key), so `GraphClient::count_ratings()` pages through `/ratings` (100 per request, max 10 pages) and the result is stored as `recommendation_count`. The `total-rated` theme uses `rating_count` when non-zero, otherwise `recommendation_count`; when both are 0 no entry is written (a `0` would render as an empty string anyway). |
| Permissions | `pages_show_list`, `pages_read_engagement`, `pages_read_user_content`. |
| App Review | **Not required** for the site owner's own app in development mode (they are its admin). Publishing an app for third parties would need App Review for all three permissions. |
| Tokens | short-lived user token → `fb_exchange_token` (~60 days) → `GET /me/accounts` → Page tokens without expiry (invalidated on password change, role removal, permission revocation). Granted permissions are recorded per Page (`scopes`); Pages connected before `pages_read_user_content` was requested show "reconnect to enable reviews". |

## What it does

1. **Settings → API Integrations → Facebook Reviews** (pro): the user enters
   the Meta **App ID** and **App Secret**, copies the **OAuth Redirect URI**
   (`admin-post.php?action=nx_facebook_reviews_callback`) into the Meta app,
   clicks **Validate & Save** (credentials are verified with a
   `client_credentials` token request), then **Connect with Facebook**.
2. The browser is sent to the Facebook Login dialog. On return the plugin
   exchanges the code, lists the user's Pages and stores every granted Page
   with its Page token **encrypted** (`nx_facebook_reviews_pages` option).
3. In a campaign (**Source: Facebook Reviews**) the user picks one connected
   Page and a theme. On save, and then every *Cache Duration* minutes via
   WP-Cron, `update_data()` runs:
   - **`total-rated`** theme → Page summary (+ recommendation count when
     `rating_count` is 0) → one entry `fb_page_{id}`, replaced on every run
     because the count and rating change.
   - **`reviewed` / `review-comment` / `review-comment-2`** → newest N
     recommendations (`facebook_reviews_limit`, default 25) filtered by
     `facebook_reviews_filter` (recommended only / all / not recommended) and
     `facebook_reviews_require_text` → one entry per review `fb_review_{story_id}`
     with `reviewer = "Someone"`, `review_content`, `recommendation`, `timestamp = created_time`.
     Entry reconciliation is **idempotent** (`sync_entries()`: delete what is no
     longer wanted, insert only what is missing) — review text never changes, and
     a plain delete-then-insert let a cron run racing a campaign save duplicate
     every notification.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| free `Facebook/FacebookReviews.php` | `reviews` | `facebook_reviews` | none — registers the source, themes `total-rated`, `reviewed`, `review-comment`, `review-comment-2`, template tags (`tag_reviewer`, `tag_rated`, `tag_page_name`, `tag_review_content`, `tag_rating`, `tag_recommendation`, `tag_time`), preview data and the Pro upsell. |
| pro `Facebook/FacebookReviews.php` | `reviews` | `facebook_reviews` | `update_data()` → `sync_page()` (aggregate) and, for review themes, `sync_reviews()` → `GraphClient::page_ratings()` → filtered entries. `conversion_data()` trims text and turns `recommendation` into the `recommends::1|0` marker the frontend renders as a 👍/👎 badge. |
| pro `classes/GraphClient.php` | — | — | Graph API v26.0 client: `appsecret_proof` on every call, pagination for `/me/accounts` and `/ratings`, `granted_permissions()`, error mapping (190 → `facebook_connection_expired`, 283 → `facebook_reviews_scope_missing`, 12 → `facebook_reviews_unavailable`, 10/200-299 → `facebook_permission_denied`, 100/803 → `facebook_page_unavailable`, 4/17/32/613 → `facebook_rate_limited`). |
| pro `classes/OAuth.php` | — | — | `start()` creates a single-use `state` transient (10 min, bound to the current user); `callback()` (`admin_post_nx_facebook_reviews_callback`) verifies it, exchanges the code, stores Pages, redirects back with `?nx_fb_status=ok|error[&nx_fb_error=code]`. |
| pro `classes/TokenStore.php` | — | — | Option `nx_facebook_reviews_pages`; tokens encrypted with libsodium secretbox (`s1:`) or AES-256-GCM fallback (`o1:`), key derived from `AUTH_KEY`/`SECURE_AUTH_KEY`. `public_view()` is the only shape the UI sees — never a token. |

## Data flow

```
Settings (React: facebook-reviews-connection, mode=settings)
  POST /notificationx/v1/facebook-reviews/oauth-start {return_url}
      → OAuth::start → {authorize_url}
  browser → facebook.com/v26.0/dialog/oauth → admin-post.php?action=nx_facebook_reviews_callback
      → OAuth::callback → GraphClient::exchange_code → my_pages → TokenStore::save_pages
      → 302 return_url?nx_fb_status=ok
  GET  /notificationx/v1/facebook-reviews/pages        → {configured, https, redirect_uri, pages[]}
  POST /notificationx/v1/facebook-reviews/refresh      {page_id?} → sync_page()
  POST /notificationx/v1/facebook-reviews/disconnect   {page_id?} → TokenStore::remove / remove_all

Campaign save  → saved_post → update_data(nx_id, settings)
WP-Cron        → nx_cron_update_data_facebook_reviews → update_data
                 → sync_page → GraphClient::page_summary → TokenStore::update
                 → (review themes) sync_reviews → GraphClient::page_ratings → filter
                 → delete_notification + update_notifications([entries])
FrontEnd       → nx_filtered_entry_facebook_reviews (conversion_data) → tags; `recommends::1|0`
                 rendered by frontend/themes/helpers/Content.tsx as .nx-recommendation badge
```

All REST routes require `edit_notificationx_settings`. A rate-limit response
sets a 1-hour back-off transient (`nx_facebook_reviews_backoff`).

## Fields & settings

| Key | Where | Purpose |
|---|---|---|
| `settings.facebook_reviews_app_id`, `settings.facebook_reviews_app_secret` | Settings | Meta app credentials (saved by **Validate & Save**). |
| `settings.facebook_reviews_cache_duration` | Settings | Cron interval in minutes (min 30). |
| `facebook_reviews_page` | Campaign → Content | `{page_id, page_name}` of the connected Page. |
| `facebook_reviews_filter`, `facebook_reviews_require_text`, `facebook_reviews_limit` | Campaign → Content (review themes only) | Which recommendations become entries. |
| `show_notification_image` | Campaign → Display | `fbreview_icon` (Facebook "f") or `fbreview_picture` (Page profile picture). |
| `link_type` | Campaign → Content | `facebook_page` → the Page URL; `facebook_reviews` → `https://www.facebook.com/{page_id}/reviews`. |

## Setting up the Meta app (user documentation)

1. Go to <https://developers.facebook.com/apps/> → **Create App** → *Other* → type **Business**.
2. Add the **Facebook Login** (or *Facebook Login for Business*) product.
3. Facebook Login → **Settings** → *Valid OAuth Redirect URIs*: paste the
   **OAuth Redirect URI** shown in NotificationX settings.
4. **App settings → Basic**: copy *App ID* and *App Secret* into NotificationX.
5. Keep the app in **development mode** — you are its admin, no App Review is
   needed. Anyone else who should connect must be added under *App Roles*.
6. NotificationX → **Validate & Save** → **Connect with Facebook** → choose the
   Pages → done.

## Key files

| Purpose | File |
|---|---|
| Free extension (source, theme, upsell) | `includes/Extensions/Facebook/FacebookReviews.php` |
| Pro extension (settings, REST, cron, entries) | `notificationx-pro/includes/Extensions/Facebook/FacebookReviews.php` |
| Graph client / OAuth / token storage | `notificationx-pro/includes/Extensions/Facebook/classes/` |
| Connection UI (settings + builder) | `nxdev/notificationx/fields/FacebookReviewsConnection.tsx`, `scss/nx_new/_facebook_reviews_connection.scss` |
| Registration | `includes/Extensions/ExtensionFactory.php` (`facebook_reviews`) |
| Tests | `notificationx-pro/tests/test-pro-facebook-reviews.php` |

## Testing notes & gotchas

- `composer dump-autoload` is classmap-based in both plugins: new classes must
  be present in `vendor/composer/autoload_classmap.php` / `autoload_static.php`.
- Rebuild the admin bundle after touching the TSX: `NODE_OPTIONS=--max-old-space-size=8192 npm run admin`.
- Facebook refuses `http://` redirect URIs (only `localhost` is exempt in dev
  mode); local testing needs an HTTPS site (e.g. Herd `herd secure`).
- `overall_star_rating` is absent for Pages with few ratings — `rating` is then
  an empty string and the theme shows only the count.
- A token that stops working marks the Page `expired` / `permission_denied` /
  `page_unavailable`; campaigns keep their last entry but are no longer
  refreshed until the user reconnects from settings.
- `/{page}/visitor_posts`, and probably `/feed` + `/tagged`, answer `#200`
  subcode `2069033` "Unavailable Feature On New Page Experience" — that edge is
  gone for New Pages, so recommendations cannot be read through it either.
- Old recommendations (2017/2018) are common; `display_from` / `display_last`
  are hidden for this source so they are not filtered out by age.
- `tests/test-pro-facebook-reviews.php` mocks every HTTP call through
  `pre_http_request`; it needs the WP test suite (`WP_TESTS_DIR`).
