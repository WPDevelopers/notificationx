# IFTTT Extension (`modules_ifttt`)

> Connects NotificationX to **IFTTT** (If This Then That) applets as an
> Email Subscription data source. In the free plugin this class only
> registers `ifttt` as a selectable `source` and returns a stub from
> `get_data()`; the real webhook-ingestion logic lives in the `notificationx-pro`
> sibling plugin's subclass — see [Data flow](#data-flow).

## At a glance

| | |
|---|---|
| **Integration** | IFTTT (If This Then That) |
| **Directory** | [`includes/Extensions/IFTTT/`](../../includes/Extensions/IFTTT/) |
| **Module key(s) (`$module`)** | `modules_ifttt` |
| **Feeds Types** | `email_subscription` |
| **Extension classes** | `IFTTT` (`email_subscription`, id `ifttt`) |
| **Depends on** | No third-party PHP plugin/class dependency — `is_active()`'s `$class`/`$function`/`$constant` checks are all left empty (unset), so the free class is always considered "active" once its module is enabled. The real integration is the external **IFTTT** service, which talks to this site over a webhook REST endpoint (see [Data flow](#data-flow)). |

## What it does

There is exactly one PHP class in this directory: [`IFTTT.php`](../../includes/Extensions/IFTTT/IFTTT.php),
`NotificationX\Extensions\IFTTT\IFTTT`, pairing with the `email_subscription`
Type. `init_extension()` only sets `$this->title` / `$this->module_title` to
`"IFTTT"`. `get_data()` is a stub — it returns the literal string
`'Hello From IFTTT'` and does not fetch or store any real entries.

**Important — not currently registered**: `_TODO: verify_` at runtime, but from
static inspection, `ifttt` does **not** appear as a key in
`ExtensionFactory::$extension_classes` in either
[`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php)
(free) or `notificationx-pro/includes/Extensions/ExtensionFactory.php` (which
only extends the free map and has its `nx_extension_classes` filter hook
commented out, adding nothing). `grep` across both plugin directories found no
other call site that instantiates `IFTTT::get_instance()` or
`NotificationXPro\Extensions\IFTTT\IFTTT::get_instance()`. This means, as
checked out, neither class appears to be wired into the Extension registry
that `ExtensionFactory::register_extensions()` iterates — so the module/source
would not actually be offered to `Modules`/the builder UI via that path. This
is consistent with `ifttt` being explicitly commented out of several
`source`-rules lists in [`GlobalFields.php`](../../includes/Extensions/GlobalFields.php)
(e.g. the `show_notification_image` field's `Rules::includes('source', [...])`
list has `// "ifttt",`). Treat this integration as dormant/legacy code unless
you find a registration path this review missed.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`IFTTT.php`](../../includes/Extensions/IFTTT/IFTTT.php) | `email_subscription` | `ifttt` | Stub — returns the literal string `'Hello From IFTTT'`; no real fetch logic in the free class |

## Data flow

The free class has no `init_fields()`, `save_post()`, or webhook hook — it is
a bare stub. The real ingestion logic exists only in the pro plugin's
`NotificationXPro\Extensions\IFTTT\IFTTT` (in
`notificationx-pro/includes/Extensions/IFTTT/IFTTT.php`, which `extends`
this free class as `IFTTTFree`). From reading that pro subclass (present in
this checkout's sibling `notificationx-pro` directory, included here only for
context since this doc scopes to the free plugin's `includes/Extensions/IFTTT/`):

- `public_actions()` hooks `add_action('nx_api_response_success', [$this, 'get_response'])` —
  the **generic** (not per-source-suffixed) action fired by
  [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php)'s
  `save_response()`, which backs the REST route
  `POST /wp-json/notificationx/v1/notification/(?P<id>[\d]+)` (also available
  under the legacy `notificationx` namespace). That endpoint validates the
  request's `api_key` against `md5(home_url())` before firing
  `nx_api_response_success_{$post['source']}` and the generic
  `nx_api_response_success`.
- `get_response()` (pro) checks the incoming payload for `$response['data']['actionFields']`
  (the shape IFTTT applets POST), builds a notification entry (`entry_id`/`timestamp`
  = `time()`, `nx_id` = `notification_id` from the payload), strips `api_key`/
  `notification_id`/`site_url`, and calls the inherited
  `Extension::update_notification()` to persist it via
  `Entries::insert_entry()`.
- `doc()` (pro) renders the "IFTTT setup Instructions" panel shown in the
  builder, listing the API Key (`md5(home_url())`), Notification Id (post ID),
  and Site URL the user must configure in their IFTTT applet.

`_TODO: verify_` whether this pro-side wiring actually takes effect at runtime
given the registration gap noted above.

## Fields & settings

- The free class adds no distinctive fields beyond the inherited
  `Extension::__init_fields()` behaviour (themes/sources/templates
  registration) and does not reference
  [`GlobalFields`](../../includes/Extensions/GlobalFields.php) (only comments
  mentioning `// "ifttt",` were found there, in unrelated `source`-rules
  lists for other fields).
- The pro subclass adds `$ifttt_fields` (fifteen `tag_*` merge-tag labels —
  Email, First Parameter … Fifteenth Parameter) and an `ifttt_template_new`
  entry in `$this->templates`, plus an `ifttt_settings` section
  (`ifttt_api_key`, read-only, default `md5(home_url())`) registered via the
  `nx_settings_tab_api_integration` filter.

## Dependency & detection

- The free `IFTTT` class leaves `$class`, `$function`, and `$constant` all at
  their default empty-string values, so `Extension::is_active()` /
  `class_exists()` never fail that check — the extension is only gated by
  whether the `modules_ifttt` module is enabled (and, per the note above,
  whether it is registered in `ExtensionFactory::$extension_classes` at all).
- There is no local PHP plugin/class to detect: IFTTT is an external SaaS
  service that reaches this site over the REST webhook route in
  [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php),
  authenticated by an `api_key` matching `md5(home_url())` rather than by any
  `class_exists()`/`function_exists()` check.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/IFTTT/IFTTT.php`](../../includes/Extensions/IFTTT/IFTTT.php) |
| Registration (map where `ifttt` is absent) | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) |
| Shared fields | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (only commented-out `ifttt` references found) |
| Generic webhook/connect REST routes | [`includes/Core/Rest/Integration.php`](../../includes/Core/Rest/Integration.php) |

## Testing notes & gotchas

- **Registration gap**: `ifttt` is not present in
  `ExtensionFactory::$extension_classes` (free or pro) and no other
  `get_instance()` call site was found for either the free or pro `IFTTT`
  class. Before relying on this integration, verify at runtime (e.g. via
  `ExtensionFactory::get_instance()->get_all()`) whether it is actually loaded.
- **Stub `get_data()`**: the free class's `get_data()` returns a hardcoded
  placeholder string, matching the same stub pattern seen in `BitIntegrations`
  and `Zapier` — do not assume it returns usable notification data.
- Real ingestion (if the registration gap above is resolved) happens through
  the generic `nx_api_response_success` action, not a per-`$id` suffixed one —
  double-check this doesn't fire for unrelated sources' webhook payloads that
  also happen to include an `actionFields` key.
- No tests under `tests/` reference `ifttt` (`_TODO: verify_` if this changes).

## Related docs

- [Adding a New Notification Type](../new-notification-type.md)
- Related Type docs under [../types/](../types/) (none yet exist for
  `email_subscription` at time of writing — `_TODO: verify_`)
