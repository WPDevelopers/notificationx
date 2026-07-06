# ReviewX Extension (`modules_reviewx`)

> Connects NotificationX to [ReviewX](https://wordpress.org/plugins/reviewx/) (a
> WooCommerce product-review plugin), pulling approved ReviewX comments to drive
> Reviews (`reviews`) notifications ("Someone just reviewed Product X — ★★★★★").

## At a glance

| | |
|---|---|
| **Integration** | ReviewX |
| **Directory** | [`includes/Extensions/ReviewX/`](../../includes/Extensions/ReviewX/) |
| **Module key(s) (`$module`)** | `modules_reviewx` |
| **Feeds Types** | `reviews` (`Types\Reviews`, which lists `modules_reviewx` as one of its gating modules alongside `modules_wordpress`, `modules_woocommerce`, `modules_zapier`, `modules_freemius`) |
| **Extension classes** | `ReviewX.php` (class `ReviewX`, extends `WooReviews`) → id `reviewx`, type `reviews` |
| **Depends on** | ReviewX plugin — detected via `function_exists('rvx_wpdrill_init')` (no `$class` is set); ReviewX itself requires WooCommerce, and the review comments queried are scoped to `post_type => 'product'` |

## What it does

From the user's perspective: install & activate WooCommerce **and** ReviewX, then
enable the "ReviewX" module inside NotificationX. This makes a Reviews notification
(type `reviews`, source `reviewx`) available, e.g. "Someone just reviewed Product X"
with a star rating.

`ReviewX` extends `WooReviews`
([`includes/Extensions/WooCommerce/WOOReviews.php`](../../includes/Extensions/WooCommerce/WOOReviews.php))
and only overrides a handful of things — `$id`/`$module`/`$function`/`$default_theme`,
a "reviewx_"-prefixed theme set in `init_extension()`, `admin_actions()`,
`source_error_message()`, `add()`, and `doc()`. Everything else (hooks, bulk backfill,
comment lifecycle handling) is inherited unchanged from `WooReviews`.

Real events that drive data (all inherited from `WooReviews::init()`):
- `comment_post` action → `WooReviews::post_comment()` — when a new comment is
  approved (`$comment_approved === 1`), builds one entry via `$this->add($comment_ID)`
  (polymorphically resolves to `ReviewX::add()` for this extension) and calls
  `Extension::update_notification()` (near-real-time).
- `trash_comment` / `deleted_comment` actions → `WooReviews::delete_comment()` →
  `Extension::delete_notification()`.
- `transition_comment_status` action → `WooReviews::transition_comment_status()` —
  moving to `unapproved` deletes the notification entry; moving to `approved`
  deletes then re-adds it.
- Manual/initial backfill — when a `reviewx`-sourced notification post is saved,
  `WooReviews::saved_post()` deletes existing entries for that `nx_id` then calls
  `get_notification_ready($data)` → `get_comments($data)` (bulk `get_comments()` WP
  query, `post_type => 'product'`, `status => 'approve'`, bounded by the
  notification's `display_from`/`display_last` settings) → `update_notifications()`
  (bulk insert, inherited from `Extension`).

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`ReviewX.php`](../../includes/Extensions/ReviewX/ReviewX.php) (class `ReviewX extends WooReviews`) | `reviews` | `reviewx` | No `get_data()` method exists. Data flows through the `WooReviews`/`Extension` comment pipeline instead (see "What it does"). The one meaningfully overridden method is `add($comment)`: given a `WP_Comment` (or comment ID) whose `comment_type === 'review'`, it builds an entry with `id`, `product_id` (= `comment_post_ID`), `content`, `link` (comment permalink), `post_title` (product title via `get_the_title()`), `post_link` (product permalink), `timestamp` (GMT comment date), `rating` (comment meta `rating`), and `title` (comment meta `reviewx_title` — the ReviewX-specific review-title field, absent from `WooReviews::add()`). Author fields come from the registered WP user if `$comment->user_id` is set (`first_name`, `last_name`, `username` = display name, computed `name`), else parsed from `get_comment_author()`; `email` from `get_comment_author_email()`. |

## Data flow

1. **Real-time**: a review comment is posted/approved → WordPress fires
   `comment_post` → `WooReviews::post_comment()` (inherited) → `$this->add()`
   resolves to `ReviewX::add()` → `Extension::update_notification()` → inserted into
   the entries table.
2. **Backfill on save**: when a `reviewx`-sourced notification post is saved, the
   `nx_saved_post_reviewx` filter runs `WooReviews::saved_post()` → deletes existing
   entries for that `nx_id` → `get_notification_ready($data)` →
   `get_comments($data)` (inherited, still calls `$this->add()` per row) →
   `update_notifications()` (bulk insert).
3. Entries land in the custom entries table (`Entries` / `Database::$table_entries`,
   per plugin architecture) and are later surfaced through `FrontEnd.php` → REST →
   the React popup runtime, same as other extensions.
4. `ReviewX::admin_actions()` additionally hooks `nx_can_entry_reviewx` to
   `Conversions::nx_can_entry()` — but **only when `NotificationX::is_pro()`** —
   so entry-level filtering/limiting by Pro rules only applies on Pro installs.
5. `notification_image()` and `fallback_data()` are not overridden in `ReviewX.php`
   and are inherited as-is from `WooReviews`.

## Fields & settings

- `init_fields()` is not overridden on `ReviewX`; it inherits
  `WooReviews::init_fields()`, which calls `$this->_init_fields()` from the shared
  `Woo` trait ([`includes/Extensions/WooCommerce/Woo.php`](../../includes/Extensions/WooCommerce/Woo.php)).
  That registers `nx_conversion_product_list` / `nx_conversion_category_list`
  filters (`products()` / `categories()`), each normalized through
  `GlobalFields::get_instance()->normalize_fields(..., 'source', $this->id, ...)`
  scoped to `$this->id` = `reviewx`.
- `ReviewX::init_extension()` defines its own `$this->themes` (theme keys prefixed
  `reviewx_*`: `total-rated`, `reviewed`, `review_saying`, `review-comment`,
  `review-comment-2`, `review-comment-3`) and `$this->templates`
  (`reviewx_template_new`, `reviewx_saying_template_new`) — a near-duplicate of
  `WooReviews`'s own theme/template set, just namespaced under the `reviewx_`
  extension id instead of `woo_reviews_`.
- `ReviewX::doc()` supplies the "Instructions" panel copy (requires both
  WooCommerce and ReviewX, links to docs/blog), hooked via the base
  `Extension::nx_instructions()` (registered in `Extension::__init_fields()` when
  `method_exists($this, 'doc')`).

## Dependency & detection

- Required plugin: **ReviewX** (which itself depends on WooCommerce). Unlike most
  Extension classes, `ReviewX` does **not** set `$class`; it sets
  `public $function = 'rvx_wpdrill_init'` instead (the `$class` assignment is
  present but commented out in source: `// public $class = '\ReviewX';`). The base
  `Extension::is_active()` / `Extension::class_exists()` therefore falls through to
  `function_exists('rvx_wpdrill_init')`.
- When absent: `Extension::is_active()` returns `false`, so `init()`,
  `admin_actions()`, `public_actions()`, and field registration never run for this
  extension. Separately, `ReviewX::source_error_message()` (hooked on
  `source_error_message`) surfaces an admin error — "You have to install ReviewX
  plugin." with an install link — scoped via `Rules::is('source', $this->id)` —
  whenever `!$this->class_exists()`.
- Registration itself (`ExtensionFactory::$extension_classes`) is unconditional —
  `'reviewx' => 'NotificationX\Extensions\ReviewX\ReviewX'` is always in the
  factory's class map; gating happens at `is_active()` / module-enabled time, not at
  registration time (see `ExtensionFactory::register_extensions()`, which only
  calls `add()` if `Modules::get_instance()->is_enabled($obj->module)`).
- `get_instance()` on `ReviewX` follows the standard Pro-swap pattern: if a
  `NotificationXPro\...\ReviewX` class exists, it is instantiated instead of the
  free-plugin class.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/ReviewX/ReviewX.php`](../../includes/Extensions/ReviewX/ReviewX.php) |
| Parent class (most behaviour inherited from here) | [`includes/Extensions/WooCommerce/WOOReviews.php`](../../includes/Extensions/WooCommerce/WOOReviews.php) |
| Shared WooCommerce fields trait | [`includes/Extensions/WooCommerce/Woo.php`](../../includes/Extensions/WooCommerce/Woo.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`reviewx` entry in `$extension_classes`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Paired Type | [`includes/Types/Reviews.php`](../../includes/Types/Reviews.php) (`reviews` Type — lists `modules_reviewx` in its `$module` gate array) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |

## Testing notes & gotchas

- `ReviewX::add()` requires `$comment->comment_type === 'review'` — comments not
  created by ReviewX (plain WooCommerce/WordPress comments) are silently skipped
  (`add()` returns `null` in that branch, and callers don't guard against that
  return value being used downstream — verify behaviour if a non-review comment ID
  reaches `add()`).
- The bulk backfill path (`WooReviews::get_comments()`, inherited) always queries
  `post_type => 'product'` — ReviewX reviews are expected on WooCommerce products
  only; this is consistent with `ReviewX::doc()` stating both WooCommerce and
  ReviewX are required.
- `admin_actions()` only wires the Pro `nx_can_entry_reviewx` filter when
  `NotificationX::get_instance()->is_pro()` is true — Pro-only entry limiting logic,
  don't expect it to run on free installs.
- No `get_data()` method exists anywhere in this class or its parent — if you are
  looking for the canonical "fetch data" entry point per the general Extension
  pattern, use `add()` / `get_comments()` / `get_notification_ready()` instead for
  this integration.
- No dedicated PHPUnit tests found under `tests/` referencing ReviewX —
  `_TODO: verify_` if ReviewX-specific test coverage exists elsewhere (e.g. in
  `notificationx-pro`).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- Related Type docs under [../types/](../types/) (Reviews type —
  `_TODO: verify_` exact filename once written)
