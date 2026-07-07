# Zapier Extension (`modules_zapier`)

> Connects NotificationX to **Zapier** (Zaps that POST into NotificationX as the
> "Action App") as a data source for three notification Types: Sales
> (`conversions`), `reviews`, and `email_subscription`. In the free plugin these
> three classes only register `zapier_conversions` / `zapier_reviews` /
> `zapier_email_subscription` as selectable sources and return a stub from
> `get_data()`; the real webhook-ingestion logic lives in the
> `notificationx-pro` sibling plugin's subclasses — see [Data flow](#data-flow).

## At a glance

| | |
|---|---|
| **Integration** | Zapier |
| **Directory** | [`includes/Extensions/Zapier/`](../../includes/Extensions/Zapier/) |
| **Module key(s) (`$module`)** | `modules_zapier` (all three classes) |
| **Feeds Types** | `conversions`, `reviews`, `email_subscription` |
| **Extension classes** | `ZapierConversions` (`conversions`, id `zapier_conversions`), `ZapierReviews` (`reviews`, id `zapier_reviews`), `ZapierEmailSubscription` (`email_subscription`, id `zapier_email_subscription`) |
| **Depends on** | No third-party PHP plugin/class dependency — `is_active()`'s `$class`/`$function`/`$constant` checks are all left empty (unset) on every class, so the free classes are always considered "active" once `modules_zapier` is enabled. The real integration is the external **Zapier** service, which talks to this site over the same webhook REST endpoint used by IFTTT (see [Data flow](#data-flow)). All three classes are `$is_pro = true`. |

## What it does

There are four PHP files in this directory:

- [`Zapier.php`](../../includes/Extensions/Zapier/Zapier.php) — a `trait Zapier` with **no methods at all**; it exists purely so the three extension classes (and their `notificationx-pro` subclasses) share a common `use Zapier;` extension point.
- [`ZapierConversions.php`](../../includes/Extensions/Zapier/ZapierConversions.php) — pairs with the `conversions` Type. `init_extension()` sets `$this->title`/`$this->module_title` to "Zapier" and a pro-upsell `$this->popup` (with a YouTube embed). `get_data()` is a stub — it returns the literal string `'Hello From Zapier'`.
- [`ZapierReviews.php`](../../includes/Extensions/Zapier/ZapierReviews.php) — pairs with the `reviews` Type. Same shape as above (its own upsell `$this->popup` text, no video embed). `get_data()` returns the same stub string.
- [`ZapierEmailSubscription.php`](../../includes/Extensions/Zapier/ZapierEmailSubscription.php) — pairs with the `email_subscription` Type. No `$this->popup` is set. `get_data()` returns the same stub string.

Each class also defines a **`_doc()`** method (note the leading underscore — this is *not* the `doc()` method name that `Extension::__init_fields()` looks for via `method_exists($this, 'doc')`) that renders an HTML `<ul>` of the merge-tag "Field Key" names available for that Type's templates (e.g. `conversions`: `name`, `first_name`, `sales_count`, `email`, `title`, `city_country`, …; `reviews`: `username`, `rated`, `plugin_name`, `rating`, …; `email_subscription`: `name`, `email`, `title`, `city_country`, …). See [`notificationx-pro`'s `doc()`](#data-flow) for how `_doc()` actually gets surfaced to the builder UI.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`ZapierConversions.php`](../../includes/Extensions/Zapier/ZapierConversions.php) | `conversions` | `zapier_conversions` | Stub — returns the literal string `'Hello From Zapier'`; no real fetch logic in the free class |
| [`ZapierReviews.php`](../../includes/Extensions/Zapier/ZapierReviews.php) | `reviews` | `zapier_reviews` | Stub — same literal string |
| [`ZapierEmailSubscription.php`](../../includes/Extensions/Zapier/ZapierEmailSubscription.php) | `email_subscription` | `zapier_email_subscription` | Stub — same literal string |

All three: `$module = 'modules_zapier'`, `$is_pro = true`, `$module_priority = 16`. Per-class `$priority` (source ordering) is `20` (conversions), `25` (reviews), `15` (email_subscription).

## Data flow

The free classes have no `init_fields()`, `save_post()`, or webhook hook — they
are bare stubs, same pattern as the `IFTTT` extension. The real ingestion logic
exists only in `notificationx-pro`'s subclasses (`NotificationXPro\Extensions\Zapier\ZapierConversions` /
`ZapierReviews` / `ZapierEmailSubscription`, each `extends` its free counterpart
and `use`s a pro-side `Zapier` trait — `notificationx-pro/includes/Extensions/Zapier/`,
present in this checkout, included here only for context since this doc scopes
to the free plugin's `includes/Extensions/Zapier/`):

- Unlike `IFTTT` (which is missing from `ExtensionFactory::$extension_classes`
  entirely), the three Zapier ids **are** registered in the free
  [`ExtensionFactory::$extension_classes`](../../includes/Extensions/ExtensionFactory.php)
  map, pointing at the free class names. However, `includes/GetInstance.php`'s
  `get_instance()` auto-upgrades: when resolving `NotificationX\Extensions\Zapier\ZapierConversions::get_instance()`,
  it checks whether `NotificationXPro\Extensions\Zapier\ZapierConversions` exists
  and `is_subclass_of` the free class, and if so instantiates the **pro** class
  instead. So with `notificationx-pro` active, `ExtensionFactory::register_extensions()`
  ends up holding pro-subclass instances even though its own map only names the
  free classes. Confirmed in [`GetInstance::get_instance()`](../../includes/GetInstance.php)
  source: it rewrites the `NotificationX\` prefix to `NotificationXPro\` and, when that
  subclass exists and `is_subclass_of` the free class, instantiates the Pro subclass.
- The pro `Zapier` trait's constructor adds
  `add_action("nx_api_response_success_{$this->id}", [$this, 'get_response'])` —
  a per-source-suffixed action fired by
  [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php)'s
  `save_response()`, which backs the REST route
  `POST /wp-json/notificationx/v1/notification/(?P<id>[\d]+)?api_key=...`
  (also duplicated, byte-for-byte, under the bare `notificationx` namespace —
  the code comments this second registration `// OLD Fallback for Zapier`).
  That endpoint validates `api_key` against `md5(home_url())` (http or https)
  before firing `nx_api_response_success_{$post['source']}` and the generic
  `nx_api_response_success`.
- Pro's `get_response($response)` reads `$response['id']` as the notification
  post id, builds `entry_key = "{$this->id}_{$nx_id}"`, defaults `timestamp` to
  `time()` if absent, and strips `rest_route`/`display_type`. If
  `display_type == 1` it treats `$response['products']` as a newline-delimited
  `product_id:…` / `name:…` text blob (via `extract_data()`) and inserts one
  entry per parsed product. If `display_type == 4` it merges
  `$response['custom_data']` into the top-level payload. Either way it calls
  the inherited `Extension::update_notification()` → `Entries::insert_entry()`
  to persist the entry.
- Pro's `doc()` wraps the free class's `_doc()` output (the merge-tag `<ul>`)
  with Zapier setup instructions (link to the public Zap invitation URL) —
  this is why the free classes name the method `_doc()` rather than `doc()`:
  the pro trait supplies the `doc()` that `Extension::nx_instructions()`
  actually hooks via `method_exists($this, 'doc')`.
- [`includes/Core/Migration.php`](../../includes/Core/Migration.php) normalizes
  legacy single-source `source == 'zapier'` entries (pre-dating the 3-way
  Type split) to `zapier_conversions` / `zapier_reviews` /
  `zapier_email_subscription` depending on which migration branch (Sales /
  Reviews / Email Subscription) is running, and `normalize_source()` strips a
  `zapier_` prefix to re-match legacy per-notification-post source overrides.

## Fields & settings

- The free classes add no distinctive builder fields beyond the inherited
  `Extension::__init_fields()` behaviour (themes/sources/templates
  registration). [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php)
  only references Zapier inside shared `Rules::includes('source', [...])`
  lists — e.g. the `show_notification_image` select field defaults to
  `gravatar` for the bare string `"zapier"` (GlobalFields.php ~line 202) — which does
  **not** match the actual `zapier_conversions`/`zapier_reviews`/`zapier_email_subscription`
  source ids, so this default is effectively dead for current sources (it only matched
  the pre-split legacy `source == 'zapier'`), and the `hour_minutes_section`
  display-window field's source list
  does correctly include all three real ids (`zapier_conversions`,
  `zapier_reviews`, `zapier_email_subscription`).
- The pro `Zapier` trait adds a `zapier_settings_section` (`API Integration`
  settings tab, gated by `Rules::is('modules.modules_zapier', true)`) with a
  single read-only `zapier_api_key` field defaulting to `md5(home_url('', 'http'))`
  — the same key value the free plugin's REST endpoint checks incoming
  webhook calls against. It also strips `zapier_api_key` back out of saved
  settings via `save_settings()`.

## Dependency & detection

- The free classes leave `$class`, `$function`, and `$constant` all at their
  default empty-string values, so `Extension::is_active()` /
  `class_exists()` never fail that check — each extension is only gated by
  whether the `modules_zapier` module is enabled.
- There is no local PHP plugin/class to detect: Zapier is an external SaaS
  service that reaches this site over the REST webhook route in
  [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php),
  authenticated by an `api_key` matching `md5(home_url())` rather than by any
  `class_exists()`/`function_exists()` check.

## Key files

| Purpose | File |
|---|---|
| Extension classes | [`includes/Extensions/Zapier/ZapierConversions.php`](../../includes/Extensions/Zapier/ZapierConversions.php), [`ZapierReviews.php`](../../includes/Extensions/Zapier/ZapierReviews.php), [`ZapierEmailSubscription.php`](../../includes/Extensions/Zapier/ZapierEmailSubscription.php) |
| Shared (empty) trait | [`includes/Extensions/Zapier/Zapier.php`](../../includes/Extensions/Zapier/Zapier.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) |
| Pro/free class auto-upgrade mechanism | [`includes/GetInstance.php`](../../includes/GetInstance.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| Generic webhook/connect REST routes | [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php) |
| Legacy `source == 'zapier'` migration | [`includes/Core/Migration.php`](../../includes/Core/Migration.php) |

## Testing notes & gotchas

- **Stub `get_data()`**: all three free classes return a hardcoded placeholder
  string, matching the same stub pattern seen in `IFTTT` and `BitIntegrations`
  — do not assume it returns usable notification data; the real payload comes
  in via the REST webhook, not `get_data()`.
- **Pro-class auto-upgrade**: unlike `IFTTT` (which is missing from the
  registry entirely), Zapier's three ids *are* present in
  `ExtensionFactory::$extension_classes`, but the objects actually stored in
  `ExtensionFactory::$extensions` at runtime depend on `GetInstance::get_instance()`'s
  free→pro namespace swap succeeding (`notificationx-pro` active and its
  subclass loaded). Before debugging a "why isn't my Zap doing anything"
  issue, verify with `notificationx-pro` active which class actually got
  instantiated.
- **`_doc()` vs `doc()` naming**: only the pro trait defines `doc()`; on a
  free-only install (or if the pro subclass somehow isn't picked up) the
  "Template Keys" instructions panel will not appear in the builder because
  `Extension::nx_instructions()` gates on `method_exists($this, 'doc')`.
- Real ingestion fires on the per-source action
  `nx_api_response_success_{$id}` (e.g. `nx_api_response_success_zapier_conversions`),
  not the generic `nx_api_response_success` used by `IFTTT` — double-check
  which one you're hooking into if extending this further.
- Legacy data: any notification still carrying `source == 'zapier'` (pre-Type-split)
  is remapped by `Migration.php`, not by the extension classes themselves.
- No tests under `tests/` reference `zapier` specifically; the three ids are
  registered, so they are exercised generically by
  [`tests/test-extension-factory.php`](../../tests/test-extension-factory.php).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [IFTTT Extension](ifttt.md) — closest sibling integration (same stub-in-free /
  real-logic-in-pro webhook pattern)
- Type docs for the Types this Extension feeds:
  [Sales/Conversions](../types/conversions.md), [Reviews](../types/reviews.md),
  [Email Subscription](../types/email_subscription.md)
