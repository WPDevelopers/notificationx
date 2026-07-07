# Flashing Tab Notification Type (`flashing_tab`)

> Rewrites the browser tab's `<title>` and favicon to an eye-catching alternating
> message/icon ("Comeback!", cart-item count, etc.) when a visitor switches away
> from the tab, trying to lure them back — a "recover lost visitor" alert. The
> entire feature is Pro-only (`$is_pro = true` on both the Type and its sole
> Extension).

## At a glance

| | |
|---|---|
| **Type ID** | `flashing_tab` |
| **Class** | [`includes/Types/FlashingTab.php`](../../includes/Types/FlashingTab.php) |
| **Trait** | none — no `use ... as Traits...` in the class, and there is no `includes/Types/Traits/FlashingTab.php` file |
| **Priority** | `45` (`$priority`) |
| **Is pro** | `true` (`$is_pro`) — gated behind NotificationX PRO |
| **Default source** | `flashing_tab` (`$default_source`) |
| **Default theme** | Not set on the Type class itself (`$themes` is never populated here). The **FlashingTab extension** declares `$default_theme = 'flashing_tab_theme-1'` ([`includes/Extensions/FlashingTab/FlashingTab.php:40`](../../includes/Extensions/FlashingTab/FlashingTab.php#L40)). |
| **Link type** | `-1` (`$link_type`) — same sentinel used by `notification_bar`/`inline`. `Extension::get_link_type()` ([`includes/Extensions/Extension.php:196-207`](../../includes/Extensions/Extension.php#L196-L207)) maps `'-1'` to `''`, so no `@link_type:` source-trigger is registered ([Extension.php:227-231](../../includes/Extensions/Extension.php#L227-L231)) and no Link Type field dependency is added — i.e. `-1` means "suppress the Link Type field entirely" (distinct from the literal `'none'`). |
| **Module gate (`$module`)** | `['modules_flashing']` on the Type class; the FlashingTab extension is separately gated by `$module = 'modules_flashing'` ([`includes/Extensions/FlashingTab/FlashingTab.php:38`](../../includes/Extensions/FlashingTab/FlashingTab.php#L38)), checked via `Modules::get_instance()->is_enabled($obj->module)` in [`ExtensionFactory::register_extensions()`](../../includes/Extensions/ExtensionFactory.php#L103). If `modules_flashing` is off, the extension never registers. |
| **Compatible extensions** | Exactly one: **FlashingTab** (`$id = 'flashing_tab'`, `$types = 'flashing_tab'`) — [`includes/Extensions/FlashingTab/FlashingTab.php`](../../includes/Extensions/FlashingTab/FlashingTab.php). Verified via `grep -rl "'flashing_tab'" includes/Extensions` (only hits: `GlobalFields.php`, `ExtensionFactory.php`, and `FlashingTab/FlashingTab.php` itself). |

## What it does

`FlashingTab::init()` ([`FlashingTab.php:40-55`](../../includes/Types/FlashingTab.php#L40-L55)) only sets the admin-facing title/dashboard title and the "upgrade to PRO" popup shown when the free plugin's builder offers this type without PRO active (`$this->popup`, with `denyButtonText`/`confirmButtonText` links to notificationx.com and a demo video). It declares no fields, themes, or behavioural logic itself — all of that lives on the **FlashingTab extension** (`includes/Extensions/FlashingTab/FlashingTab.php`), which is a distinct class in a distinct namespace (`NotificationX\Extensions\FlashingTab\FlashingTab` vs. `NotificationX\Types\FlashingTab`) despite sharing a class name.

The extension's `init_extension()` ([`FlashingTab.php:50-114`](../../includes/Extensions/FlashingTab/FlashingTab.php#L50-L114)) defines 4 built-in themes (`theme-1`…`theme-4`), each `is_pro => true`, each supplying default icon image pairs/messages (e.g. theme-1/2: two alternating `icon`+message via `ft_theme_one_icons` / `ft_theme_one_message`; theme-3: two labeled lines `ft_theme_three_line_one` / `ft_theme_three_line_two`; theme-4: a line plus a conditional "default vs. alternative" line `ft_theme_four_line_two`, e.g. `{quantity} items in your cart!`). The actual tab-flashing behaviour (setting `document.title`/favicon, alternating interval, restoring on tab focus) is not present in this PHP file — it lives in a standalone frontend bundle: [`nxdev/notificationx/frontend/flashing-tab.ts`](../../nxdev/notificationx/frontend/flashing-tab.ts) reads its config from `window.nx_flashing_tab` (per-theme icon/message keys), swaps `document.title` via a `changeTitle()` helper, and drives the favicon swap through [`flashing/favloader.ts`](../../nxdev/notificationx/frontend/flashing/favloader.ts) on an interval from [`flashing/webWorker.ts`](../../nxdev/notificationx/frontend/flashing/webWorker.ts).

`FlashingTab::nx_tabs()` ([`Extensions/FlashingTab/FlashingTab.php:128-132`](../../includes/Extensions/FlashingTab/FlashingTab.php#L128-L132)) hooks `nx_metabox_tabs` to restrict the admin builder's `display_tab` and `customize_tab` visibility via `Rules::is('source', $this->id, true, ...)` — i.e. those tabs only show when the notification's `source` is `flashing_tab`.

## Data flow

Trace one notification end-to-end:

1. `FrontEnd::get_notifications_ids()` ([`FrontEnd.php:487-613`](../../includes/FrontEnd/FrontEnd.php#L487-L613)) buckets every enabled `nx_bar` post by `source`. `flashing_tab` has no dedicated `elseif ($settings['source'] == 'flashing_tab')` branch (unlike `press_bar`, `gdpr_notification`, `popup_notification`, `exit_intent_custom`) — it falls through to the generic `active` bucket (or `global` if `global_queue` is enabled and NotificationX Pro is active), same as most React-rendered types.
2. `FrontEnd::get_notifications_data()` ([`FrontEnd.php:243-467`](../../includes/FrontEnd/FrontEnd.php#L243-L467)) processes `active`/`global` posts through the shared generic pipeline (entries lookup, default-value substitution, `nx_filtered_entry_*`/`nx_filtered_data_*` filters, link resolution) — there is no `flashing_tab`-specific branch in this method either.
3. Unlike other types, the tab-flash behaviour is **not** driven by the main React runtime (`window.notificationXArr` / `useNotificationX.ts`). It is a separate bundle, [`nxdev/notificationx/frontend/flashing-tab.ts`](../../nxdev/notificationx/frontend/flashing-tab.ts), which reads its own localized `window.nx_flashing_tab` object (theme id `flashing_tab_theme-1` … `-4` plus the per-theme icon/message keys) and performs the `document.title`/favicon swapping client-side.
4. `includes/FrontEnd/Preview.php:418` lists `flashing_tab_theme-1`…`-4` alongside other themes in a `Rules::includes('themes', [...])` check used to decide when a generic "content heading" preview error set applies — confirms these 4 theme ids are the canonical set recognized elsewhere in the codebase.

## Fields & settings schema

`Types\FlashingTab` declares no fields (`init_fields()` not overridden, inherited as a no-op from `Types`). All builder fields/behaviour come from the **extension**'s `init_fields()` ([`Extensions/FlashingTab/FlashingTab.php:116-120`](../../includes/Extensions/FlashingTab/FlashingTab.php#L116-L120)), which only adds the `nx_tabs()` filter described above — no other field-registration code is present in this file, so per-theme fields (`ft_theme_one_icons`, `ft_theme_one_message`, `ft_theme_three_line_one`, `ft_theme_three_line_two`, `ft_theme_four_line_two`) are declared as theme `defaults`, not via an explicit `content_fields()`/`design_tab_fields()` method the way `PressBar` does it. These keys are also listed in the Quick Builder field allow-list ([`includes/Core/QuickBuild.php:207-211`](../../includes/Core/QuickBuild.php#L207-L211)), but no field-array definition for them exists anywhere in this free repo (not in `GlobalFields.php`) — their editing UI is supplied by the Pro plugin, consistent with the type being Pro-gated (same pattern as `offer_announcement`'s content fields).

`GlobalFields.php:328` includes `flashing_tab` in a `Rules::includes('type', [...])` check (alongside `notification_bar`, `inline`, `sales_inline`, `offer_announcement`, `custom`) that gates a mobile-responsiveness icon/field — confirming `flashing_tab` opts into at least one shared global field group.

## Themes / templates

Defined entirely on the **extension**, not the Type class:

| Theme id | `defaults` fields | Notes |
|---|---|---|
| `theme-1` | `ft_theme_one_icons` (`icon-one`/`icon-two`), `ft_theme_one_message` (default: "Comeback!") | is_pro |
| `theme-2` | same field keys as theme-1, different default icons/message ("Comeback! We miss you.") | is_pro |
| `theme-3` | `ft_theme_three_line_one`, `ft_theme_three_line_two` — each `{icon, message}` | is_pro |
| `theme-4` | `ft_theme_three_line_one` (reused key) + `ft_theme_four_line_two` (`default` vs. `alternative` variants, e.g. cart-quantity message) | is_pro |

Each theme also has a `source` key pointing at a static preview GIF under `NOTIFICATIONX_ADMIN_URL . 'images/extensions/themes/pro/flashing-tab/theme-N.gif'`. There is no separate `$res_themes` (responsive themes) entry for this extension — confirmed: `grep -n "res_themes\|res-theme"` on `includes/Extensions/FlashingTab/FlashingTab.php` returns nothing.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/FlashingTab.php`](../../includes/Types/FlashingTab.php) |
| Trait | none |
| Extension | [`includes/Extensions/FlashingTab/FlashingTab.php`](../../includes/Extensions/FlashingTab/FlashingTab.php) |
| PHP frontend | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) — generic `active`/`global` bucket path (no type-specific branch found) |
| Preview | [`includes/FrontEnd/Preview.php`](../../includes/FrontEnd/Preview.php) (line 418 references the 4 theme ids) |
| Frontend runtime | [`nxdev/notificationx/frontend/flashing-tab.ts`](../../nxdev/notificationx/frontend/flashing-tab.ts) (entry; reads `window.nx_flashing_tab`, swaps `document.title`), [`flashing/favloader.ts`](../../nxdev/notificationx/frontend/flashing/favloader.ts) (favicon swap), [`flashing/webWorker.ts`](../../nxdev/notificationx/frontend/flashing/webWorker.ts) (interval) |

## Dependencies

None required for the notification mechanism itself (pure browser `document.title`/favicon manipulation) — core WordPress + NotificationX PRO only. The free-plugin frontend bundle ([`flashing-tab.ts`](../../nxdev/notificationx/frontend/flashing-tab.ts)) does **not** fetch cart data: its only network call (`sendAnalyticsRequest`) POSTs impression analytics (`nx_id`/`type`) to the REST url, and `ft_theme_four_line_two` is rendered as a literal string. So the theme-4 `{quantity} items in your cart!` default is a static placeholder here; any real cart-quantity substitution would be Pro/WooCommerce-side (not present in this repo).

## Testing notes & gotchas

- This entire type is Pro-gated (`Types\FlashingTab::$is_pro = true` and `Extensions\FlashingTab\FlashingTab::$is_pro = true`); in the free plugin it only exists to render the admin "Upgrade to PRO" popup/teaser, not to produce working front-end notifications — confirmed by `FrontEnd::get_notifications_ids()`'s early `if (!NotificationX::is_pro())` guard that skips any post whose extension `is_pro` is true.
- No `flashing_tab`-specific branch exists in `FrontEnd::get_notifications_ids()` or `get_notifications_data()` — it relies entirely on the generic React-rendered pipeline. If the actual tab-flash logic needs bespoke PHP handling later (e.g. a dedicated bucket like `pressbar`), none currently exists.
- The Type class name (`NotificationX\Types\FlashingTab`) and the Extension class name (`NotificationX\Extensions\FlashingTab\FlashingTab`) are distinct classes in distinct namespaces that happen to share a bare class name `FlashingTab` — don't confuse the two when searching/grepping.
- No dedicated test files for this Type exist under `tests/` — confirmed: `grep -rli "flashing" tests/` returns no hits (the suite's `test-types-factory.php` uses `popup` as its representative fixture, not `flashing_tab`).
- The `document.title` and `<link rel="icon">` swapping lives in [`flashing-tab.ts`](../../nxdev/notificationx/frontend/flashing-tab.ts) (title, via `changeTitle()`) and [`flashing/favloader.ts`](../../nxdev/notificationx/frontend/flashing/favloader.ts) (favicon), alternating between the two icon/message pairs on the interval supplied by [`flashing/webWorker.ts`](../../nxdev/notificationx/frontend/flashing/webWorker.ts).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [docs/types/notification_bar.md](notification_bar.md) — another Pro-adjacent, single-extension Type documented at similar depth, useful for comparing the `$link_type = '-1'` pattern
- [docs/types/_TEMPLATE.md](_TEMPLATE.md) — template this doc was generated from
