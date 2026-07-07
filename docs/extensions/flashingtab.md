# Flashing Tab Extension (`modules_flashing`)

> A NotificationX PRO-only, self-contained notification: it flashes the browser tab
> title/favicon (via GIF-style theme icons and a rotating message) to try to win back
> a visitor whose tab has lost focus. It does not pull data from any third-party
> plugin — its "data" is just the theme configuration (icons/messages) set in the
> builder.

## At a glance

| | |
|---|---|
| **Integration** | `Flashing Tab` |
| **Directory** | [`includes/Extensions/FlashingTab/`](../../includes/Extensions/FlashingTab/) |
| **Module key(s) (`$module`)** | `modules_flashing` |
| **Feeds Types** | `flashing_tab` (see [`includes/Types/FlashingTab.php`](../../includes/Types/FlashingTab.php)) |
| **Extension classes** | `FlashingTab.php` → pairs `(types: flashing_tab, id: flashing_tab)` |
| **Depends on** | NotificationX **PRO** plugin (`$is_pro = true`); detected via `NotificationX::is_pro()` → `class_exists('\NotificationXPro\NotificationX')` |

## What it does

Flashing Tab is a single-purpose Type+Extension pair, both keyed `flashing_tab`, that
ship in the free plugin as PRO-gated. There is no external service to connect — the
extension only defines four visual "themes" (icon + message combinations) that the
frontend runtime uses to flash the browser tab's title and favicon when a visitor
switches away from the tab, aiming to recapture their attention. Because `$is_pro` is
`true` on both the `Types\FlashingTab` and `Extensions\FlashingTab\FlashingTab`
classes, the feature is visible in the builder (module `modules_flashing`) but
usage is blocked/shown as an upgrade prompt unless the sibling `notificationx-pro`
plugin is active.

## Extension classes & pairings

| Class | Pairs with Type | `$id` | Data source (`get_data()`) |
|---|---|---|---|
| [`FlashingTab.php`](../../includes/Extensions/FlashingTab/FlashingTab.php) | `flashing_tab` | `flashing_tab` | _None — no `get_data()`/`fallback_data()` method is defined on this class or the base `Extension`._ `init_extension()` only registers 4 static `$this->themes` entries (`theme-1`..`theme-4`), each an array of `defaults` (icon URLs + message strings, e.g. `ft_theme_one_icons`, `ft_theme_one_message`, `ft_theme_three_line_one/two`, `ft_theme_four_line_two`) consumed by the builder/frontend as field defaults. |

## Data flow

There is no source-event → `get_data()` → entries pipeline for this extension (unlike
data-driven extensions such as WooCommerce). The builder saves the chosen theme and
its field values (icon URLs, messages) onto the `nx_bar` post as usual via
`Extension::init()` (`save_post`/`saved_post` filters, inherited, not overridden
here). At runtime the frontend popup/bar runtime reads that config and flashes the
document title/favicon. The frontend runtime that implements this is
[`nxdev/notificationx/frontend/flashing-tab.ts`](../../nxdev/notificationx/frontend/flashing-tab.ts),
which reads its config from `window.nx_flashing_tab` and imports
`flashing/favloader.ts` (favicon swap) and `flashing/webWorker.ts` (the rotate
interval).

`init_fields()` adds a `nx_metabox_tabs` filter (`nx_tabs()`) that gates the
`display_tab` and `customize_tab` builder tabs to only show when
`Rules::is('source', 'flashing_tab', true, ...)` — i.e. those tabs only render when
the notification's source is Flashing Tab.

## Fields & settings

- No calls to `GlobalFields` were found in `FlashingTab.php`; it does not appear to
  reuse the shared cross-extension field registry.
- Its only bespoke fields are the theme `defaults` set in `init_extension()`:
  `ft_theme_one_icons`, `ft_theme_one_message`, `ft_theme_three_line_one`,
  `ft_theme_three_line_two`, `ft_theme_four_line_two` (with `default`/`alternative`
  sub-variants gated by `is-show-empty`).
- `nx_tabs()` (hooked to `nx_metabox_tabs`) restricts the `display_tab` and
  `customize_tab` metabox tabs to sources matching `flashing_tab`.

## Dependency & detection

- Required: the sibling **NotificationX PRO** plugin (`notificationx-pro`), not a
  third-party/external service.
- Detection: `$is_pro = true` on the class, combined with
  `NotificationX::is_pro()` (in [`includes/NotificationX.php`](../../includes/NotificationX.php)),
  which does `class_exists('\NotificationXPro\NotificationX')`.
- When absent: the module/type/theme still register and show in the builder
  (gated only by the `modules_flashing` module toggle, not by PRO status), but
  `Extension::__is_pro_sources()` marks the source `is_pro`, and theme entries with
  `is_pro => true` get `'is_pro' => $theme['is_pro'] && ! NotificationX::is_pro()`
  (see `Extension.php` lines ~250, 287, 450, 625) — i.e. the UI shows an
  upgrade/lock prompt instead of allowing full use. `Types\FlashingTab` also defines
  a `$popup` (upgrade nag) shown via the standard PRO-popup mechanism.

## Key files

| Purpose | File |
|---|---|
| Extension class | [`includes/Extensions/FlashingTab/FlashingTab.php`](../../includes/Extensions/FlashingTab/FlashingTab.php) |
| Paired Type | [`includes/Types/FlashingTab.php`](../../includes/Types/FlashingTab.php) |
| Registration | [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (`'flashing_tab' => 'NotificationX\Extensions\FlashingTab\FlashingTab'`) and [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php) (`'flashing_tab' => 'NotificationX\Types\FlashingTab'`) |
| Base class | [`includes/Extensions/Extension.php`](../../includes/Extensions/Extension.php) |
| Shared fields (not used here) | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) |
| PRO detection | [`includes/NotificationX.php`](../../includes/NotificationX.php) (`is_pro()`) |
| Preview theme references | [`includes/FrontEnd/Preview.php`](../../includes/FrontEnd/Preview.php) (lists `flashing_tab_theme-1..4`) |

## Testing notes & gotchas

- This extension has no `get_data()` — do not assume it behaves like a
  data-source integration (WooCommerce/EDD/etc.) when modifying it; there are no
  entries/analytics rows fetched here beyond what the generic `nx_bar` post save
  produces.
- Because both the Type and Extension share the `flashing_tab` id/`is_pro = true`,
  confirm PRO-gating behavior (locked vs. functional) in both places if changing
  either class — a change to one without the other can desync the upgrade-prompt
  logic.
- The browser-tab-flashing JS implementation (favicon/title swap, rotate interval)
  lives in the frontend bundle:
  [`nxdev/notificationx/frontend/flashing-tab.ts`](../../nxdev/notificationx/frontend/flashing-tab.ts)
  plus `flashing/favloader.ts` and `flashing/webWorker.ts` — check there before
  assuming runtime behavior.
- No dedicated tests for Flashing Tab exist under `tests/`; the `flashing_tab` source
  is exercised generically by
  [`tests/test-extension-factory.php`](../../tests/test-extension-factory.php).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
