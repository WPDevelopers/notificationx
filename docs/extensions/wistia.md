# Wistia Extension (no dedicated module)

> Registers **Wistia** as a selectable video-source icon under the `video` Type. The
> class is a bare stub — it sets no themes, no fields, no `get_data()`, and has no
> module toggle of its own; it only exists so "Wistia" appears in the video-source
> picker (alongside `youtube` and `vimeo`).

## At a glance

| | |
|---|---|
| **Integration** | Wistia |
| **Directory** | [`includes/Extensions/Wistia/`](../../includes/Extensions/Wistia/) |
| **Module key(s) (`$module`)** | None — `$module` is left at the base-class default (`''`) and `$show_on_module = false`, so `register_module()` never registers a toggle for it (see [Dependency & detection](#dependency--detection)) |
| **Feeds Types** | `video` ([`Types\Video`](../../includes/Types/Video.php)) |
| **Extension classes** | `Wistia.php` → `$id = 'wistia'`, `$types = 'video'` |
| **Depends on** | No third-party plugin/service check in code. Wistia video embeds/URLs are presumably entered manually by the admin; nothing in this class calls a Wistia API. `_TODO: verify_` whether `notificationx-pro` or the React admin (`nxdev/`) does anything Wistia-specific beyond showing this icon |

## What it does

`Wistia` extends the base [`Extension`](../../includes/Extensions/Extension.php) and does
almost nothing beyond identifying itself:

- `$id = 'wistia'`, `$types = 'video'`, `$priority = 15`, `$img` points at
  `assets/admin/images/extensions/sources/wistia.png`.
- `$show_on_module = false` and `$show_on_type = false` — it does **not** register the
  `video` Type itself (that happens via [`Google\YouTube`](../../includes/Extensions/Google/YouTube.php),
  the only `video`-type extension that leaves `$show_on_type` at its default `true`) and
  it does **not** add its own entry to the Modules registry.
- `init_extension()` only sets `$this->title = __('Wistia', 'notificationx')`.
- No themes (`$this->themes`), no templates, no `doc()`, no `fallback_data()`, no
  `get_data()` are defined anywhere in this class.

Functionally this mirrors [`Vimeo\Vimeo`](../../includes/Extensions/Vimeo/Vimeo.php)
exactly (same shape, same flags) — both are minimal "extra icon" registrations riding on
the `video` Type that `YouTube` actually drives.

`$doc_link` is set to
`https://notificationx.com/docs/google-reviews-with-notificationx/` — this is the same
URL used by `Vimeo`, and it points at the Google Reviews doc, not anything Wistia- or
video-related. `_TODO: verify_` — looks like a copy-paste artifact rather than an
intentional link.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | `$module` | Data source (`get_data()`) |
|---|---|---|---|---|
| [`Wistia.php`](../../includes/Extensions/Wistia/Wistia.php) | `video` | `wistia` | _(none — `$show_on_module = false`, no module registered)_ | No `get_data()` defined in this class or in the base `Extension`/`GlobalFields`. `_TODO: verify_` whether `notificationx-pro` adds one |

## Data flow

There is no fetch-and-store data flow in this class:

- `Extension::__construct()` only calls `$this->initialize()` when
  `Modules::is_enabled($module_name)` is true; since `register_module()` returns nothing
  (`show_on_module = false`), `$module_name` is `null`, and
  [`Modules::is_enabled(null)`](../../includes/Core/Modules.php) coerces that to the empty
  string, which is not a registered module key — so `is_enabled()` returns `true` by
  default (unless something else populates `settings.modules['']`). In practice this
  means `Wistia` always initializes.
- Because `$show_on_type = false`, `Wistia` never calls
  `TypeFactory::register_types('video')` itself — the `video` Type only becomes active
  when [`YouTube`](../../includes/Extensions/Google/YouTube.php) (module
  `modules_google_youtube`) registers it. So in practice Wistia's source icon only shows
  up once the YouTube module is enabled.
- The only runtime effect of this class is `Extension::__nx_sources()` (inherited,
  unmodified) adding `{ value: 'wistia', label: 'Wistia', icon: <wistia.png>, rules: [is
  type video] }` to the `nx_sources` filter that the builder UI reads — i.e. it's purely
  a source-picker entry. No cron (`$cron_schedule` unset), no `save_post`/`saved_post`
  override, no REST case for `source=wistia` was found in
  [`REST.php`](../../includes/Core/REST.php).

## Fields & settings

No Wistia-specific entries were found in
[`GlobalFields.php`](../../includes/Extensions/GlobalFields.php) or in this class
(`init_fields()` / `init_settings_fields()` are both left as no-ops, inherited from
`Extension`). Themes/templates for anything built on the `video` Type come from
`Types\Video` / `YouTube` instead (see [`Extension::get_themes()`](../../includes/Extensions/Extension.php)
fallback-to-Type behavior).

## Dependency & detection

- **Required plugin/service:** none enforced in code. `Wistia` sets none of `$class`,
  `$function`, or `$constant` on the base `Extension`, so
  [`Extension::is_active()`](../../includes/Extensions/Extension.php) only checks whether
  `wistia` has been selected as an active notification source — there is no
  presence/API-key check for an actual Wistia account or plugin.
- **When "absent":** there is nothing to detect absence of — the icon is always available
  in the source picker (gated only by the `video` Type/`modules_google_youtube` being
  enabled, per above), regardless of whether the site actually uses Wistia.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/Wistia/Wistia.php`](../../includes/Extensions/Wistia/Wistia.php) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Type fed | [`includes/Types/Video.php`](../../includes/Types/Video.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'wistia' => 'NotificationX\Extensions\Wistia\Wistia'`) |
| Comparable sibling | [`includes/Extensions/Vimeo/Vimeo.php`](../../includes/Extensions/Vimeo/Vimeo.php) (same stub pattern) |
| Real video data source | [`includes/Extensions/Google/YouTube.php`](../../includes/Extensions/Google/YouTube.php) — see [google.md](google.md) |

## Testing notes & gotchas

- Do not expect any Wistia API calls, cron jobs, or stored video stats from this class —
  it is icon/label registration only, in both this free plugin and (as far as this repo
  shows) unverified in `notificationx-pro`. `_TODO: verify_` Pro.
- The `video` Type's own module gate is `modules_google_youtube` (see
  [`Types\Video`](../../includes/Types/Video.php)) — disabling the YouTube module hides
  the whole `video` Type, including the Wistia and Vimeo source options, even though
  neither has a module toggle of its own.
- `$doc_link` pointing at the Google Reviews doc page looks like a copy-paste bug
  inherited from/shared with `Vimeo` — don't treat it as the real Wistia documentation
  link without checking the live site.

## Related docs

- [Google Extension](google.md) — the `YouTube` class that actually drives the `video`
  Type and its module gate
- [Vimeo Extension](vimeo.md) — identical stub pattern _(doc not yet written; see
  `includes/Extensions/Vimeo/Vimeo.php`)_
- [Adding a New Notification Type](../development/adding-a-notification-type.md)
