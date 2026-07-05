# Comments Notification Type (`comments`)

> Shows a popup notification when someone leaves a (approved) comment on a WordPress
> post — e.g. "John commented on Hello World" — to build social proof around blog/site
> engagement.

## At a glance

| | |
|---|---|
| **Type ID** | `comments` |
| **Class** | [`includes/Types/Comments.php`](../../includes/Types/Comments.php) |
| **Trait** | none — `Comments` only uses the generic `GetInstance` trait, no `includes/Types/Traits/Comments.php` exists |
| **Priority** | `35` |
| **Default source** | `wp_comments` |
| **Default theme** | `comments_theme-one` |
| **Module gate (`$module`)** | `modules_wordpress` |
| **Compatible extensions** | [`WPComments`](../../includes/Extensions/WordPress/WPComments.php) (`$id = 'wp_comments'`, `$types = 'comments'`) — the only extension found declaring `$types = 'comments'` |

## What it does

When a comment is posted on the site and approved, `WPComments` (the `comments` type's
only data-source extension) turns it into a notification entry: commenter name,
comment content, the post it was left on, a link, and a timestamp. NotificationX then
shows a themed popup (visitor's name/avatar, "commented on", post title or a snippet of
the comment text, and a relative time) to other visitors as social proof.

Comment moderation is respected: `WPComments::init()` hooks `comment_post`,
`trash_comment`, `deleted_comment`, and `transition_comment_status` so a comment only
produces/keeps a notification entry while it is in the `approve` status
(see [`includes/Extensions/WordPress/WPComments.php`](../../includes/Extensions/WordPress/WPComments.php) lines 52-188).

`Comments::link_types()` registers `comment_url` (the direct link to the comment) as an
option for the type's "Link Type" content-tab field.

## Data flow

1. **Capture** — `WPComments::post_comment()` (on `comment_post`, approved) or
   `WPComments::get_notification_ready()` (on initial notification save, backfilling
   recent comments via `get_comments()`) builds a comment-data array via
   `WPComments::add()`: `id`, `link`, `post_title`, `post_comment`, `post_link`,
   `timestamp`, `ip`, `name`/`first_name`/`last_name`/`display_name` (from the
   registered user if logged in, otherwise from the anonymous commenter fields), and
   `email`.
2. **Store** — entries are persisted via `Extension::update_notification(s)` (inherited
   from [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php)),
   keyed by `entry_key = comment['id']`.
3. **Routing (`FrontEnd.php`)** — `wp_comments` is not one of the special-cased sources
   (`press_bar`, `gdpr_notification`, `popup_notification`, `exit_intent_custom`) in
   `get_notifications_ids()`, so comment notifications fall into the generic **`active`**
   bucket (see [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php)
   lines 487-611). In `get_notifications_data()` the `active` bucket is populated with
   the `{ post, entries: [...] }` shape (lines 243-383) — i.e. the standard "multiple
   entries per notification" path (`normalize`, not `normalizePressBar`, on the React
   side per [`docs/new-notification-type.md`](../new-notification-type.md)).
4. **Filtering** — `WPComments::public_actions()` adds `nx_filtered_entry_{$this->id}`
   (`conversion_data()`), which trims `post_comment` to 100 chars (80 for
   `comments_theme-seven-free`/`comments_theme-eight-free`) and wraps it in quotes for
   `theme-seven-free`.
5. **Frontend render** — REST exposes the `active` group; the React runtime
   (`nxdev/notificationx/frontend/`) resolves the theme template string via
   `GetTemplate.ts` (comment-specific template cases at
   `nxdev/notificationx/frontend/themes/GetTemplate.ts` lines ~282-296) — there is no
   dedicated `Comments` React component, it renders through the shared/generic
   notification template component like other standard types.

## Fields & settings schema

`Comments::init_fields()` adds one type-specific filter beyond the parent's field
registration:

- `nx_content_trim_length_dependency` → `content_trim_length_dependency()` appends
  `comments_theme-six-free`, `comments_theme-seven-free`, `comments_theme-eight-free`
  to the dependency list. _TODO: verify_ — no `apply_filters('nx_content_trim_length_dependency', ...)`
  call was found in this repo (free plugin); the consumer may live in the paid
  `notificationx-pro` plugin.
- `Comments::link_types()` (hooked to `nx_link_types` in the constructor) adds the
  `comment_url` option (via `GlobalFields::normalize_fields()`) to the Content tab's
  Link Type field.

All other builder fields (Content/Design/Customize tabs) come from the base `Types`
class and `GlobalFields` — Comments does not override `init_fields()` beyond the above.

## Themes / templates

`$this->themes` (admin theme picker, `comments_theme-*` ids):

| Theme key | Image shape | Notes |
|---|---|---|
| `theme-one` (default) | circle | free |
| `theme-two` | circle | free |
| `theme-three` | square | free |
| `theme-six-free` | rounded | uses `tag_post_comment` (shows comment text) |
| `theme-seven-free` | circle | uses `tag_post_comment`; content wrapped in quotes |
| `theme-eight-free` | circle | uses `tag_post_comment` |
| `theme-four` | circle | `is_pro: true` |
| `theme-five` | circle | `is_pro: true` |
| `maps_theme` | square | `is_pro: true`; `show_notification_image: 'maps_image'` — `@todo pro fix` in source |

`$this->res_themes` (responsive/mobile themes, all `is_pro: true`): `res-theme-one`
through `res-theme-eight` map to `_template: 'comments_template_new'` or
`'comments_template_with_comments'`; `res-theme-nine` maps to `'maps_template_new'`
(marked `@todo pro fix` in source).

`$this->templates` defines two template groups:
- `comments_template_new` — `third_param` = Post Title, `fourth_param` = Definite Time;
  backs themes `comments_theme-one` through `comments_theme-five`.
- `comments_template_with_comments` — adds `tag_post_comment` (Post Comment) to
  `third_param`; backs `comments_theme-six-free`, `comments_theme-seven-free`,
  `comments_theme-eight-free`.

Both templates' `first_param` comes from `GlobalFields::common_name_fields(true)`.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/Comments.php`](../../includes/Types/Comments.php) |
| Trait | none |
| Extension (data source) | [`includes/Extensions/WordPress/WPComments.php`](../../includes/Extensions/WordPress/WPComments.php) (uses shared [`WordPress`](../../includes/Extensions/WordPress/Wordpress.php) trait for `doc()`) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'comments' => 'NotificationX\Types\Comments'`), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'wp_comments' => 'NotificationX\Extensions\WordPress\WPComments'`) |
| Frontend runtime | [`nxdev/notificationx/frontend/themes/GetTemplate.ts`](../../nxdev/notificationx/frontend/themes/GetTemplate.ts) (comment theme template strings) — no dedicated Comments component |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (`get_notifications_ids()`/`get_notifications_data()` — falls through to the generic `active` bucket) |

## Dependencies

None — core WordPress only (`wp_comments`/`get_comments()`). The `WordPress` module
(`modules_wordpress`) also gates the Reviews and Download Stats types/extensions
(wordpress.org-account-based), but the Comments source itself only needs core comment
data; no wordpress.org account is required for this specific source.

## Testing notes & gotchas

- Comment moderation states matter: a comment must be in `approve` status to generate
  or keep a notification entry (`transition_comment_status()` deletes-then-recreates
  the entry on approve/unapprove transitions). Test: post a comment awaiting moderation,
  then approve/unapprove it, and confirm the notification appears/disappears.
- `comments_theme-seven-free`/`-eight-free`/`-six-free` depend on `post_comment` being
  present in the entry — verify `conversion_data()`'s trim logic (100/80 chars) still
  matches whatever `nx_content_trim_length` filter/field ends up controlling trim length
  in the admin UI (see the `_TODO: verify_` note above under Fields & settings schema).
- Anonymous (non-logged-in) commenters get their `name`/`first_name`/`last_name` parsed
  by splitting `get_comment_author()` on spaces — verify this holds for single-word or
  non-Latin names if debugging odd name display.
- No dedicated tests for this type were found under `tests/`. _TODO: verify_ if any
  exist elsewhere in the suite.

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- [Notification Bar Reference](../notification-bar-reference.md) (sibling type, different data shape)
