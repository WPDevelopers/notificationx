# Facebook Reviews Extension (`modules_facebook_reviews`)

> Connects NotificationX to a **Facebook Page** through the site owner's own Meta
> app and surfaces the Page's aggregate rating ("128 people rated ★4.8 on
> Facebook — Example Business") as a `reviews` notification. Meta no longer
> exposes individual reviews, so this integration is rating-only by design.

## At a glance

| | |
|---|---|
| **Integration** | Facebook Reviews |
| **Directory** | [`includes/Extensions/Facebook/`](../../includes/Extensions/Facebook/) (free), `notificationx-pro/includes/Extensions/Facebook/` (pro) |
| **Module key(s) (`$module`)** | `modules_facebook_reviews` |
| **Feeds Types** | `reviews` |
| **Extension classes** | free `FacebookReviews.php` (`reviews`, `facebook_reviews`) — Pro-gated stub; pro `FacebookReviews.php` extends it with OAuth, Graph API, cron |
| **Depends on** | A Meta app (type *Business*) owned by the site owner — App ID + App Secret entered in NotificationX settings; the site must be served over **HTTPS** (Meta rejects `http://` redirect URIs). No local plugin dependency. |

## Meta API status (verified 2026-08-23)

| Question | Answer |
|---|---|
| Can individual Page reviews/recommendations be read? | **No.** Deprecated from Graph API **v22.0** — `GET /{page-id}/ratings` returns error code 12; no replacement through v26.0. Versions < v22.0 are rejected by Meta since 2025-09-09. |
| What is available? | Page fields `overall_star_rating` (1–5, omitted when the Page has too few ratings) and `rating_count`. |
| Permissions | `pages_show_list` (list Pages, obtain Page tokens via `/me/accounts`) and `pages_read_engagement` (read the rating fields). `pages_read_user_content` is **not** requested. |
| App Review | **Not required.** The site owner is an admin of their own app, and an app in *development mode* (Standard Access) serves everyone who holds a role on it. |
| Tokens | short-lived user token → `fb_exchange_token` (~60 days) → `GET /me/accounts` → Page tokens without expiry (invalidated on password change, role removal, permission revocation). |

Because of this the extension writes exactly **one entry per campaign** (the
`total-rated` theme). `GraphClient` never calls `/ratings`.

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
   Page. On save, and then every *Cache Duration* minutes via WP-Cron, the
   Page summary is fetched and the single `total-rated` entry is rewritten.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source |
|---|---|---|---|
| free `Facebook/FacebookReviews.php` | `reviews` | `facebook_reviews` | none — registers the source, theme `facebook_reviews_total-rated`, template tags (`tag_rated`, `tag_page_name`, `tag_rating`, `tag_time`), preview and the Pro upsell. |
| pro `Facebook/FacebookReviews.php` | `reviews` | `facebook_reviews` | `update_data()` → `GraphClient::page_summary()` → one entry `{page_id, page_name, rated, rating, url, page_picture, timestamp}` keyed `fb_page_{id}`. |
| pro `classes/GraphClient.php` | — | — | Graph API v26.0 client: `appsecret_proof` on every call, pagination for `/me/accounts`, error mapping (190 → `facebook_connection_expired`, 10/200-299 → `facebook_permission_denied`, 100/803 → `facebook_page_unavailable`, 4/17/32/613 → `facebook_rate_limited`). |
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
                 → delete_notification + update_notifications([one entry])
FrontEnd       → tag_rated / tag_page_name / tag_rating (stars) ; link_type facebook_page → entry.url
```

All REST routes require `edit_notificationx_settings`. A rate-limit response
sets a 1-hour back-off transient (`nx_facebook_reviews_backoff`).

## Fields & settings

| Key | Where | Purpose |
|---|---|---|
| `settings.facebook_reviews_app_id`, `settings.facebook_reviews_app_secret` | Settings | Meta app credentials (saved by **Validate & Save**). |
| `settings.facebook_reviews_cache_duration` | Settings | Cron interval in minutes (min 30). |
| `facebook_reviews_page` | Campaign → Content | `{page_id, page_name}` of the connected Page. |
| `show_notification_image` | Campaign → Display | `fbreview_icon` (Facebook "f") or `fbreview_picture` (Page profile picture). |
| `link_type` | Campaign → Content | `facebook_page` → the Page URL returned by Meta. |

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
- `tests/test-pro-facebook-reviews.php` mocks every HTTP call through
  `pre_http_request`; it needs the WP test suite (`WP_TESTS_DIR`).
