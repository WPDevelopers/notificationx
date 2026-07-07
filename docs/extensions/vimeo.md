# Vimeo Extension (`vimeo`)

> A minimal `video`-Type source registration: it adds "Vimeo" as a selectable icon/label
> in the builder's source picker for `video` notifications, but implements no module,
> no themes, and no data fetching. There is no working Vimeo data pipeline in this
> plugin (or in the sibling `notificationx-pro` plugin — checked, no matches).

## At a glance

| | |
|---|---|
| **Integration** | `Vimeo` |
| **Directory** | [`includes/Extensions/Vimeo/`](../../includes/Extensions/Vimeo/) |
| **Module key(s) (`$module`)** | None — `$module` is left at its inherited default (`''`) because `$show_on_module = false`, so `register_module()` never runs and no `modules_*` key is created for Vimeo |
| **Feeds Types** | `video` ([`Types\Video`](../../includes/Types/Video.php)) — but only nominally; see below |
| **Extension classes** | `Vimeo.php` → `$id = 'vimeo'`, `$types = 'video'` |
| **Depends on** | The Vimeo service, in principle — but the class sets no `$class`/`$function`/`$constant`, so `Extension::is_active()` does no presence/API-key check at all; there is nothing to detect |

## What it does

`Vimeo` extends the base [`Extension`](../../includes/Extensions/Extension.php) and pairs
with the `video` Type ([`Types\Video`](../../includes/Types/Video.php), which is itself
`$is_pro = true` and whose real default source is `youtube`). `Vimeo::init_extension()`
only sets `$this->title = __('Vimeo', 'notificationx')` — nothing else.

Because `$show_on_module = false` and `$show_on_type = false`:
- `register_module()` is skipped in the constructor, so Vimeo never appears in the
  Modules list and gates nothing.
- `TypeFactory::register_types($this->types)` is never called by this class — the
  `video` Type is registered by whichever sibling extension does set
  `show_on_type = true` (e.g. `YouTube`, see [`google.md`](google.md)), not by Vimeo.

What Vimeo *does* do unconditionally (inherited from `Extension::initialize()`, which
hooks `__init_fields` regardless of `is_active()`): it registers itself as a `video`
source via the `nx_sources` filter (`__nx_sources()` in the base class), contributing an
`{ label: 'Vimeo', icon: <vimeo.png>, value: 'vimeo', rules: ['is','type','video'] }`
entry that the builder's source-picker UI can render. It has no themes (`$themes = []`),
so `get_themes()`/`get_templates()` fall back to whatever `Types\Video` defines.

The only other "Vimeo" references in the codebase are unrelated to this class: a generic
help string in [`ExitIntentNotification.php`](../../includes/Extensions/ExitIntent/ExitIntentNotification.php)
("Paste a YouTube, Vimeo, or other video platform URL") for a free-text Video URL field,
and a `vimeo.com/(\d+)` regex in
[`ExitIntentPopup.tsx`](../../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) that
converts a pasted Vimeo URL into a `player.vimeo.com` embed iframe. Neither of those touch
`includes/Extensions/Vimeo/Vimeo.php` or its `$id`/`$module`.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`Vimeo.php`](../../includes/Extensions/Vimeo/Vimeo.php) | `video` | `vimeo` | No `get_data()` defined anywhere in the class. `init_extension()` only sets `$this->title`. There is no cron schedule, no `save_post`/`saved_post` override, and no REST-response override (only the inherited `Extension::restResponse()`, which `error_log()`s params) |

## Data flow

There is no working data flow to trace: no code path fetches Vimeo data, stores an entry,
or serves it via REST for this source. The class exists solely so `vimeo` is a legal
`value` in the `nx_sources` list and the `video`-Type source dropdown; selecting it as an
active source produces no notification content unless something else populates entries
tagged `source = 'vimeo'`. Confirmed: `grep` across `notificationx-pro` found **no**
`Vimeo` references, so the Pro plugin does not fill this in either — the class is
free-only with no implementation anywhere in this checkout.

## Fields & settings

None. `init_fields()` / `init_settings_fields()` are left as the no-op stubs inherited from
`Extension`; `$themes` / `$res_themes` / `$templates` are all empty, so
[`GlobalFields.php`](../../includes/Extensions/GlobalFields.php) is not touched by this
class and there is no Vimeo-specific entry there (confirmed by grep).

## Dependency & detection

- **Required service:** Vimeo (implied by the name/icon only).
- **Detection:** none. `$class`, `$function`, and `$constant` are all left unset on this
  class, so `Extension::is_active()`'s presence checks are all skipped (they only trigger
  when those properties are non-empty) — there is no `class_exists`/`function_exists`/
  option check for Vimeo anywhere in this directory.
- **When absent:** not applicable — since nothing detects "presence" of Vimeo, the source
  entry always appears in the `nx_sources` list (gated only by the generic
  `Modules::is_enabled('')` check in `ExtensionFactory::register_extensions()`, which
  returns `true` when the module key is empty/unregistered).

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/Vimeo/Vimeo.php`](../../includes/Extensions/Vimeo/Vimeo.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type fed | [`includes/Types/Video.php`](../../includes/Types/Video.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'vimeo' => 'NotificationX\Extensions\Vimeo\Vimeo'`) |
| Sibling minimal "source-only" extension (same pattern) | [`includes/Extensions/Wistia/Wistia.php`](../../includes/Extensions/Wistia/Wistia.php) |
| Sibling functional video source (contrast) | [`includes/Extensions/Google/YouTube.php`](../../includes/Extensions/Google/YouTube.php), documented in [google.md](google.md) |
| Unrelated Vimeo-URL handling (Exit Intent, not this Extension) | [`includes/Extensions/ExitIntent/ExitIntentNotification.php`](../../includes/Extensions/ExitIntent/ExitIntentNotification.php), [`nxdev/notificationx/frontend/core/ExitIntentPopup.tsx`](../../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) |

## Testing notes & gotchas

- Do not expect selecting "Vimeo" as a source to produce any live notification data — there
  is no `get_data()`, no cron, and no REST handler for it in this repo, and none were found
  in `notificationx-pro` either (searched, no matches).
- `Wistia.php` is byte-for-byte the same pattern (`show_on_module = false`,
  `show_on_type = false`, title-only `init_extension()`) — if fixing/extending one, check
  whether the other needs the same treatment.
- Because `$show_on_type = false`, Vimeo relies on another extension (currently `YouTube`)
  registering the `video` Type via `TypeFactory::register_types()`. If that extension's
  module were ever disabled/removed without another `video`-type extension taking over
  registration, the `video` Type itself might not register even though `Vimeo` still
  contributes a (non-functional) source entry. (Confirmed in source: `Vimeo.php` sets
  `$show_on_type = false`, so it never calls `TypeFactory::register_types('video')`;
  only `YouTube` — `$show_on_type = true` — registers the Type.)
- No tests under `tests/` reference Vimeo beyond an exempt-list comment in
  [`tests/test-extension-factory.php`](../../tests/test-extension-factory.php); that
  generic suite still exercises its registration/type-resolution since `vimeo` is a
  registered extension.

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [Google Extension](google.md) — same free-stub pattern for `YouTube` (the functional
  sibling under the `video` Type), with more detail on how Pro is expected to fill in real
  API calls
