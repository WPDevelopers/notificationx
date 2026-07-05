# Reviews Notification Type (`reviews`)

> Shows a live feed of reviews/ratings pulled from a plugin's data source (WordPress.org
> plugin reviews, WooCommerce product reviews, ReviewX, Google Reviews, Freemius, Zapier,
> or BitIntegrations) as social-proof popups — e.g. "John rated 5 stars" or "Someone just
> reviewed <plugin/product>".

## At a glance

| | |
|---|---|
| **Type ID** | `reviews` |
| **Class** | [`includes/Types/Reviews.php`](../../includes/Types/Reviews.php) |
| **Trait** | [`includes/Types/Traits/Reviews.php`](../../includes/Types/Traits/Reviews.php) |
| **Priority** | `20` |
| **Default source** | `wp_reviews` |
| **Default theme** | `reviews_total-rated` |
| **Link type** | `review_page` (added via the `Reviews` trait's `link_types()`, filtering `nx_link_types`) |
| **Module gate (`$module`)** | Declares `['modules_wordpress', 'modules_woocommerce', 'modules_reviewx', 'modules_zapier', 'modules_freemius']` on the Type class. _TODO: verify_ — in the code read for this doc, actual type registration gating (`TypeFactory::register_types()`) is triggered from each **Extension**'s own `$module` check in [`Extension.php`](../../includes/Extensions/Extension.php) (`__construct`), not from this array directly; no call site reading `Types::$module` was found. Treat this list as documentation of "which modules commonly bring in this type" rather than confirmed enforced gating logic. |
| **Compatible extensions** | See table below (data sources) |

## What it does

The Reviews type renders a notification bubble announcing that someone rated or reviewed
a plugin/product/site — e.g. "Someone rated NotificationX ★★★★★" or "John Doe just
reviewed... 'Excellent plugin'". The exact wording and layout depend on the selected
theme (see below). Data comes from whichever **Extension** (data source) the notification
is configured to use — WordPress.org plugin reviews, WooCommerce product reviews, ReviewX,
Google Reviews, Freemius, Zapier, or BitIntegrations — each extension's `get_data()`
fetches/normalizes reviews into the common entry shape the Types/FrontEnd layer expects.

Clicking a notification links to the reviewed page via the `review_page` link type
(labelled "Product Page" in the admin, added by
[`Traits/Reviews::link_types()`](../../includes/Types/Traits/Reviews.php)).

## Data flow

Reviews notifications go through the **standard/generic** entries pipeline (same as
Comments, Conversions, etc.) — there is no dedicated bucket like `exit_intent` or `popup`
get in [`FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php):

1. The active Extension's `get_data()` populates entries for the notification (per
   `$default_source`, e.g. `wp_reviews`, `woo_reviews`, `reviewx`, `google_reviews`,
   `freemius_reviews`, `zapier_reviews`, `bitintegrations_reviews`).
2. `FrontEnd::get_notifications_data()` merges these into the `global`/`active` buckets
   and calls `get_entries()` (see `FrontEnd.php` around line ~294).
3. For each entry, the filter chain runs in this order (`FrontEnd.php` ~line 338):
   `nx_filtered_entry_{$type}` → `nx_filtered_entry_{$source}` → `nx_filtered_entry`.
   `Reviews::init()` hooks `nx_filtered_entry_reviews` (priority 11) to
   `Reviews::conversion_data()` — via the `Traits\Reviews` trait's
   `conversion_data()` method — which:
   - Falls back `content` from `plugin_review` if empty.
   - Trims `plugin_review`/`content` to 100 chars (80 for `reviews_review-comment-3`),
     wraps in quotes for the `reviews_review-comment-2` theme.
   - Falls back `title` from `post_title` if empty.
4. `link_url()` then resolves the `review_page` link type to build the click-through URL.
5. On the React side, entries reach the runtime through the standard `normalize()` shape
   (`{ post, entries: [...] }`) — not `normalizePressBar` — same as other multi-entry
   types (see [`docs/new-notification-type.md`](../new-notification-type.md) for the
   `normalize` vs `normalizePressBar` distinction).

## Fields & settings schema

- **Content trim length dependency** — `Traits\Reviews::content_trim_length_dependency()`
  hooks `nx_content_trim_length_dependency` to register these theme ids (across sources)
  as ones whose content-trim-length field should be shown/dependent:
  `reviews_review-comment`, `reviews_review-comment-2`, `reviews_review-comment-3`,
  `woo_reviews_review-comment{,-2,-3}`, `woocommerce_sales_reviews_review-comment{,-2,-3}`,
  `reviewx_review-comment{,-2,-3}`.
- **`review_fourth_param` field** — `Traits\Reviews::review_templates()` filters
  `nx_notification_template` (priority 7, hooked from `Reviews::init_fields()`) to add a
  text field `review_fourth_param` (default: "About"), shown only when
  `Rules::includes('themes', 'reviews_review_saying')` — i.e. only for the
  `review_saying` theme.
- **Link type field** — `Traits\Reviews::link_types()` adds the `review_page` option
  ("Product Page") to the Content tab's Link Type field via the `nx_link_types` filter.
- Everything else (source selection, design/customize tabs, global fields) comes from
  `Types::init_fields()` / `GlobalFields` and each Extension's own field registration —
  not specific to this Type class. _TODO: verify_ extension-specific field details by
  reading each Extension file individually if deeper accuracy is needed.

## Themes / templates

**`$themes`** (source: `NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/wporg/...'`):

| Theme id | Image shape | Notes |
|---|---|---|
| `total-rated` (default) | square | "X people rated {plugin}" + rating tag |
| `reviewed` | circle | "{username} just reviewed {plugin}" + rating tag |
| `review_saying` | circle | "{username} saying '{title}' about {plugin}" + custom CTA (adds `review_fourth_param`) |
| `review-comment` | rounded | "{username} just reviewed" + review text + rating |
| `review-comment-2` | (none set) | same as above, review text wrapped in quotes |
| `review-comment-3` | circle | same as above, uses `tag_time` instead of `tag_rating` |

**`$res_themes`** (source: `.../themes/res_reviews/...`, all `is_pro => true`):
`res-theme-one`, `res-theme-two`, `res-theme-three`, `rating-res-theme-four`,
`rating-res-theme-five`, `rating-res-theme-six` — all square, tokenized with
`res_first_param` / `res_second_param` / `res_third_param` (username or rated-count,
static "just reviewed"/"people rated" text, plugin name or rating).

**`$templates`** (template-tag registries consumed by the notification template field):
- `wp_reviews_template_new` — tags for `tag_username`/`tag_rated` (first param),
  `tag_plugin_name`/`tag_plugin_review`/`tag_anonymous_title` (third param),
  `tag_rating`/`tag_time` (fourth param); applies to the `total-rated`, `reviewed`,
  `review-comment`, `review-comment-2`, `review-comment-3` themes.
- `review_saying_template_new` — tags for `tag_username` (first param),
  `tag_title`/`tag_anonymous_title` (third param), `tag_plugin_name` (fifth param);
  applies to the `review_saying` theme.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/Reviews.php`](../../includes/Types/Reviews.php) |
| Trait | [`includes/Types/Traits/Reviews.php`](../../includes/Types/Traits/Reviews.php) |
| Base class | [`includes/Types/Types.php`](../../includes/Types/Types.php) |
| Extensions (data sources) | see table below |
| Frontend runtime | `nxdev/notificationx/frontend/...` _TODO: verify_ exact component (not inspected in this pass) |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) — generic entries pipeline (`get_notifications_data()`, `nx_filtered_entry_reviews`, `link_url()`) |

### Compatible extensions (data sources, `$types === 'reviews'`)

| Extension | `$id` (source) | `$module` | File |
|---|---|---|---|
| WordPress.org reviews | `wp_reviews` | `modules_wordpress` | [`includes/Extensions/WordPress/WPOrgReview.php`](../../includes/Extensions/WordPress/WPOrgReview.php) |
| WooCommerce reviews | `woo_reviews` | `modules_woocommerce` | [`includes/Extensions/WooCommerce/WOOReviews.php`](../../includes/Extensions/WooCommerce/WOOReviews.php) |
| ReviewX | `reviewx` | `modules_reviewx` | [`includes/Extensions/ReviewX/ReviewX.php`](../../includes/Extensions/ReviewX/ReviewX.php) — extends `WooReviews` |
| Google Reviews | `google_reviews` | `modules_google_reviews` | [`includes/Extensions/Google/GoogleReviews.php`](../../includes/Extensions/Google/GoogleReviews.php) |
| Zapier | `zapier_reviews` | `modules_zapier` | [`includes/Extensions/Zapier/ZapierReviews.php`](../../includes/Extensions/Zapier/ZapierReviews.php) |
| Freemius | `freemius_reviews` | `modules_freemius` | [`includes/Extensions/Freemius/FreemiusReviews.php`](../../includes/Extensions/Freemius/FreemiusReviews.php) |
| BitIntegrations | `bitintegrations_reviews` | `modules_bitintegrations` | [`includes/Extensions/BitIntegrations/BitIntegrationsReviews.php`](../../includes/Extensions/BitIntegrations/BitIntegrationsReviews.php) |

Note: `modules_google_reviews` and `modules_bitintegrations` are **not** listed in
`Reviews::$module` on the Type class even though their extensions declare
`$types = 'reviews'` — consistent with the finding above that the Type's `$module`
array does not appear to be the actual gating mechanism. _TODO: verify_.

## Dependencies

None required for the default source (`wp_reviews` pulls from the WordPress.org plugin
API). Other sources require their respective plugin/service: WooCommerce (for
`woo_reviews`/`reviewx`), ReviewX plugin, a Google Reviews connection, Freemius, Zapier,
or BitIntegrations — gated by their own `modules_*` settings key.

## Testing notes & gotchas

- `Traits\Reviews::conversion_data()` mutates `content`/`plugin_review`/`title` — if you
  add a new review-comment-style theme, remember to add its id(s) to
  `content_trim_length_dependency()` and check the trim-length special-case (80 chars for
  `review-comment-3`) if you want consistent behavior; note the existing check compares
  `$settings['themes'] == 'reviews_review-comment-3'` twice (looks like a possible
  copy-paste bug — verify against `WOOReviews`/`ReviewX` for a parallel `-2` check).
  _TODO: verify_ whether this is intentional.
- `review_fourth_param` only appears for the `review_saying` theme (`Rules::includes`) —
  test that dependency when adding new params to other review themes.
- Since Reviews uses the generic `active`/`global` entry pipeline (not a special bucket),
  changes to `FrontEnd.php`'s generic entries loop can affect this type as a side effect —
  test against Comments/Conversions too when touching that shared code path.
- No dedicated PHPUnit tests for this type were found under `tests/` in this pass.
  _TODO: verify_.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
