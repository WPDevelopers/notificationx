# Growth Alert / Inline Notification Type (`inline`)

> Renders a small "growth alert" message directly **inline** inside a page's content
> (e.g. on a WooCommerce/LearnDash/Tutor/LearnPress/EDD product or course page,
> right before the Add to Cart button) rather than as a floating popup or bar —
> e.g. "99 people purchased this in the last 7 days" or "Only 10 left in stock".

## At a glance

| | |
|---|---|
| **Type ID** | `inline` |
| **Class** | [`includes/Types/Inline.php`](../../includes/Types/Inline.php), extends [`Types`](../../includes/Types/Types.php) |
| **Trait** | none — no `use ... as Traits...` in the class, and it does not `use` any file from `includes/Types/Traits/` |
| **Priority** | `50` (`$priority`) |
| **Pro-only** | Yes — `$is_pro = true` |
| **Default source** | `woo_inline` (`$default_source`) |
| **Default theme** | Not set on the Type class (`$default_theme` is inherited empty from `Types`) and not set on any compatible Extension either — confirmed: `grep -n "default_theme"` across `WooInline`/`EDDInline`/`TutorInline`/`LearnDashInline`/`LearnPressInline`/`FluentCartInline` returns nothing. |
| **Link type** | `-1` (`$link_type`) — a sentinel `Extension::get_link_type()` ([`includes/Extensions/Extension.php:204-206`](../../includes/Extensions/Extension.php#L204-L206)) treats as "no link type field", distinct from the literal string `'none'` used by other types |
| **Module gate (`$module`)** | `['modules_woocommerce']` — only `modules_woocommerce` is declared on the Type itself. Note some compatible extensions gate on their own module instead (see table below); a disabled `modules_woocommerce` does not disable, e.g., the LearnDash inline extension |
| **Registered in** | [`TypesFactory.php`](../../includes/Types/TypesFactory.php#L38): `'inline' => 'NotificationX\Types\Inline'` |
| **Compatible extensions** | See [Compatible extensions](#compatible-extensions) below |

## What it does

`Inline` is the Type (the "what kind of notification") behind NotificationX's **Growth Alert** feature — confirmed by `init()` setting `$this->title = __('Growth Alert 🚀', 'notificationx')`. Instead of floating in a corner of the viewport like a popup/bar, an `inline` notification is injected directly into the page markup at a theme-chosen hook location (the `inline_location` setting — an array of one or more integration-specific action/filter hook names, e.g. `woocommerce_before_add_to_cart_form`, `learndash_content`, `tutor_course/loop/after_title`, `fluentcart_single`).

The class itself is small and only adds three behaviors on top of the `Types` base:

- `init()` — sets `title`/`dashboard_title` and a `$popup` upsell config (`nx_pro_alert_popup` filter payload — a video + "Upgrade to PRO" modal shown when Pro is inactive, per the `Extension::get_data()` / `GlobalFields.php` `popup` wiring).
- `init_fields()` — hooks `show_on_exclude()` to the `nx_show_on_exclude` filter.
- `show_on_exclude( $exclude, $settings )` — forces a notification to be excluded from the normal popup/bar "show on" visibility path whenever it has a non-empty `inline_location` and `$settings['type'] === 'inline'`. Comment: _"Making sure inline notice don't show as normal notice if pro is disabled."_
- `is_stock_theme( $theme )` / `nx_can_entry( $result, $entry, $settings )` — a stock-specific hard filter: entries whose `themes` setting is `woocommerce_sales_inline_stock-theme-one` or `woocommerce_sales_inline_stock-theme-two` are always rejected (`nx_can_entry` returns `false`) for this Type's `nx_can_entry_*` hook. **Note:** despite the `woocommerce_sales_inline_` prefix in those literal strings, this method is wired up from the `woo_inline` Extension (`WooCommerce::admin_actions()` does `add_filter("nx_can_entry_{$this->id}", array($this->get_type(), 'nx_can_entry'), 10, 3)`, and `$this->id` there is `woo_inline`, not `woocommerce_sales_inline`). This check can **never** match for the `inline` Type's own extensions: `Extension::get_themes()` prefixes an extension's declared `$themes` with `$this->id . "_"` ([`includes/Extensions/Extension.php:555`](../../includes/Extensions/Extension.php#L555)), so a `woo_inline` notification's stored `themes` value is `woo_inline_stock-theme-one`, never the `woocommerce_sales_inline_`-prefixed string `is_stock_theme()` checks ([`includes/Types/Inline.php:85`](../../includes/Types/Inline.php#L85)). Those prefixed ids only ever appear on the sibling `woocommerce_sales_inline` source (a different Type) — so for this Type the filter is effectively dead/defensive.

The Type does **not** itself declare `$themes` — theme entries are declared per-Extension (e.g. `WooInline::init_extension()` sets `$this->themes` on the extension instance), which is the same pattern used by other multi-source-extension types.

## Compatible extensions

Found via `grep -rl "'inline'" includes/Extensions` (excluding `GlobalFields.php`, which only references the `inline` type id inside a `Rules::includes('type', [...])` visibility rule, not a `$types` declaration) and confirmed by reading each hit's `$types`/`$module`:

| Extension class | `$id` | `$types` | `$module` | Notes |
|---|---|---|---|---|
| [`WooInline`](../../includes/Extensions/WooCommerce/WooInline.php) | `woo_inline` | `'inline'` | `modules_woocommerce` | Also the Type's `$default_source`. `$is_pro = true`. |
| [`EDDInline`](../../includes/Extensions/EDD/EDInline.php) | `edd_inline` | `'inline'` | `modules_edd` | |
| [`TutorInline`](../../includes/Extensions/Tutor/TutorInline.php) | `tutor_inline` | `'inline'` | none declared on the subclass — inherited from `Tutor` base (`modules_tutor`) | |
| [`LearnDashInline`](../../includes/Extensions/LearnDash/LearnDashInline.php) | `learndash_inline` | `'inline'` | `modules_learndash` | |
| [`LearnPressInline`](../../includes/Extensions/LearnPress/LearnPressInline.php) | `learnpress_inline` | `'inline'` | none declared on the subclass — inherited from `LearnPress` base (`modules_learnpress`) | |
| [`FluentCartInline`](../../includes/Extensions/FluentCart/FluentCartInline.php) | `fluentcart_inline` | `'inline'` | `modules_fluentcart` | |

All six are registered in [`ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) (lines ~32–36 and ~81).

**Important naming trap:** [`WooCommerceSalesInline`](../../includes/Extensions/WooCommerce/WooCommerceSalesInline.php) (`$id = 'woocommerce_sales_inline'`) *extends* `WooInline` and reuses the same `inline_location` mechanics, but overrides `$types = 'woocommerce_sales'` — it belongs to the **`woocommerce_sales` Type**, not this one. See [woocommerce_sales.md](woocommerce_sales.md) for that extension. Don't confuse the two "inline" families.

## Data flow

1. An enabled notification post with `type = inline` and a non-empty `inline_location` is fetched by `PostType::get_posts(['is_inline' => true, ...])` — `is_inline` is a derived flag set in [`Core/PostType.php:174`](../../includes/Core/PostType.php#L174): `'is_inline' => ! empty($data['inline_location'])`.
2. [`Features/Inline.php`](../../includes/Features/Inline.php) (`NotificationX\Core\Inline`, a separate helper class from this Type, despite the shared name) — `get_notifications_data()` pulls those posts and calls `FrontEnd::get_instance()->get_notifications_data(['shortcode' => [...nx_ids], 'inline_shortcode' => true])` to assemble entry data for them.
3. `Features/Inline.php::get_template()` builds the plain-text/HTML template string for the notification (param substitution, including the `stock-theme-one`/`stock-theme-two` case at line 243-246 that renders only `second_param`…`fifth_param`, no `first_param`).
4. In [`FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php):
   - Line 353 — inside the shortcode-post-assembly loop, `'inline' === $settings['type']` (alongside `show_on === 'only_shortcode'` and `'woocommerce_sales_inline' == $settings['source']`) causes the notification to be treated as shortcode/inline-only and skipped from the normal floating popup/global queue (`continue`).
   - Line 835 — `filtered_data()` excludes sources `woo_inline`, `edd_inline`, `tutor_inline`, `learndash_inline`, `fluentcart_inline` (alongside `google`, `google_reviews`, `youtube`, `woocommerce_sales_inline`) from the "display last N entries" popup slicing behavior.
   - Lines 926/929 — `filtered_post()` strips the internal `inline_location` and `is_inline` keys from the post payload sent to the frontend runtime (unless `params['inline_shortcode']` is set), i.e. the raw hook-name array isn't needed client-side once routing has happened server-side.
5. The actual `add_action($hook, ...)` that echoes the notification markup into the theme template at the chosen `inline_location` hook (e.g. `woocommerce_before_add_to_cart_form`) was **not found in this (free) plugin** — the admin field for `inline_location` (a `select`/`multiple` field populated by `apply_filters('nx_inline_hook_options', [])`) is defined in the **Pro** plugin's override, [`notificationx-pro/includes/Types/Inline.php`](../../../notificationx-pro/includes/Types/Inline.php) (`NotificationXPro\Types\Inline extends InlineFree`), which is consistent with `$is_pro = true` on this Type. **Verified (Pro):** the Pro `Types/Inline.php` only adds the builder UI — its `nx_tabs()` (hooked on `nx_metabox_tabs`) injects a `visibility-hooks` section with the `inline_location` `select` (options from `apply_filters('nx_inline_hook_options', [])`) and a `hide_on_mobile` checkbox. The actual `add_action(...)`-that-echoes-markup wiring lives **per data-source Extension, not on the Type**. Each Pro inline extension (`WooInline`, `EDDInline`, `TutorInline`, `LearnDashInline`, `LearnPressInline`, `FluentCartInline`) in its `init_extension()` builds a `$this->hooks` map of integration-hook-name → label and calls `add_action($hook, [$this, '<render callback>'], $priority)` for each entry, plus hooks `nx_inline_hook_options` to feed that same map into the `inline_location` dropdown. When a hook fires, the callback (e.g. `WooInline::before_add_to_cart_form()`) resolves the context (product/course id), calls `Inline::get_instance()->get_notifications_data($this->id, $product_id, $settings)`, `continue`s past any notification whose stored `inline_location` does not include the firing `current_action()`, builds the string via `Inline::get_template()`, substitutes `{{tags}}`, and echoes a `<div class='{theme}'>…</div>`. So there is no single Pro-side hook — output is fanned out across the six extensions' `$hooks` registrations.

## Fields & settings schema

- No type-specific `init_fields()` additions beyond the `nx_show_on_exclude` filter (see [What it does](#what-it-does)) — this Type relies entirely on `GlobalFields.php` for its builder UI, plus whatever the Pro override (`notificationx-pro/includes/Types/Inline.php`) layers on top (confirmed there: a `visibility-hooks` section with the `inline_location` `select` field and a `hide_on_mobile` checkbox, added via the `nx_metabox_tabs` filter).
- `inline_location` (array of hook-name strings) is the pivotal settings key — every compatible Extension's themes declare a default value for it (e.g. `WooInline`'s `conv-theme-seven` theme → `['woocommerce_before_add_to_cart_form']`).
- `GlobalFields.php` line 328 includes `inline` in a `Rules::includes('type', [...])` list (alongside `notification_bar`, `flashing_tab`, `sales_inline`, `offer_announcement`, `custom`) that gates the "For Mobile" responsive-themes tab — i.e. this Type does not get a separate mobile-theme tab the way popup/bar types do.
- Themes (per-Extension `$themes`, e.g. in `WooInline::init_extension()`) declare per-theme `template` param maps (`first_param`…`fifth_param`) consumed by `Features/Inline.php::get_template()`.

## Themes / templates

The Type class itself declares no `$themes`/`$res_themes`/`$templates` — these live on each compatible Extension. Confirmed example, [`WooInline::init_extension()`](../../includes/Extensions/WooCommerce/WooInline.php#L69-L158):

- `conv-theme-seven` — sales-count theme (`tag_sales_count`, product title, "in last 7 days"), template group `woo_template_sales_count`.
- `stock-theme-one` / `stock-theme-two` — low-stock themes (`tag_stock_count`, "left in stock" / "left … on our site!"), template group `inline_stock_template`. `Inline::nx_can_entry()` nominally targets these (see [What it does](#what-it-does)) but cannot actually match them: `WooInline` declares its own non-empty `$themes`, so `Extension::get_themes()` prefixes them with `woo_inline_` ([`Extension.php:555`](../../includes/Extensions/Extension.php#L555)) — a `woo_inline` entry's `themes` is `woo_inline_stock-theme-one`, not the `woocommerce_sales_inline_stock-theme-one` the filter checks. The exclusion is therefore effectively dead for this Type (it only bites the sibling `woocommerce_sales_inline` source).

Other compatible extensions (`EDDInline`, `TutorInline`, `LearnDashInline`, `LearnPressInline`, `FluentCartInline`) each declare their own `$themes` with extension-specific `inline_location` defaults — not enumerated here; read each file directly for its current theme list.

## Key files

| Layer | File(s) |
|---|---|
| Type class | [`includes/Types/Inline.php`](../../includes/Types/Inline.php) |
| Base class | [`includes/Types/Types.php`](../../includes/Types/Types.php) |
| Factory registration | [`includes/Types/TypesFactory.php`](../../includes/Types/TypesFactory.php#L38), [`includes/Extensions/ExtensionFactory.php`](../../includes/Extensions/ExtensionFactory.php) |
| Extensions (data sources) | [`WooInline.php`](../../includes/Extensions/WooCommerce/WooInline.php), [`EDInline.php`](../../includes/Extensions/EDD/EDInline.php), [`TutorInline.php`](../../includes/Extensions/Tutor/TutorInline.php), [`LearnDashInline.php`](../../includes/Extensions/LearnDash/LearnDashInline.php), [`LearnPressInline.php`](../../includes/Extensions/LearnPress/LearnPressInline.php), [`FluentCartInline.php`](../../includes/Extensions/FluentCart/FluentCartInline.php) |
| Rendering/template helper | [`includes/Features/Inline.php`](../../includes/Features/Inline.php) (`NotificationX\Core\Inline` — shared with other types, not `inline`-exclusive) |
| Shared field registry | [`includes/Extensions/GlobalFields.php`](../../includes/Extensions/GlobalFields.php) (line ~328) |
| PHP frontend routing | [`includes/FrontEnd/FrontEnd.php`](../../includes/FrontEnd/FrontEnd.php) (lines ~353, ~835, ~926–929) |
| Post-type derived flag | [`includes/Core/PostType.php`](../../includes/Core/PostType.php#L174) (`is_inline`) |
| Pro-only field/hook wiring | `notificationx-pro/includes/Types/Inline.php` (sibling plugin, outside this repo) |

## Dependencies

None at the Type level — dependency is entirely per-Extension: **WooCommerce** (`woo_inline`), **Easy Digital Downloads** (`edd_inline`), **Tutor LMS** (`tutor_inline`), **LearnDash** (`learndash_inline`), **LearnPress** (`learnpress_inline`), or **FluentCart** (`fluentcart_inline`) must be installed/active for the corresponding Extension to load (each is gated by its own `modules_*` setting).

## Testing notes & gotchas

- This whole Type is `$is_pro = true` — the actual DOM-injection (`add_action($hook, ...)`) logic lives in the sibling `notificationx-pro` plugin, not here. Changes to `inline_location` field behavior or hook lists require checking the Pro repo too.
- `show_on_exclude()` deliberately routes `inline` notifications away from the normal popup "show on" visibility logic — if an inline notification is unexpectedly appearing/not appearing as a floating popup, check this filter first.
- The `nx_can_entry()` stock-theme exclusion uses theme-id strings prefixed `woocommerce_sales_inline_*`. `$settings['themes']` for a `woo_inline` entry is normalized to the extension-id-prefixed form `woo_inline_stock-theme-one` (`Extension::get_themes()` → `array_add_prefix($this->themes, $this->id . "_")`, [`Extension.php:555`](../../includes/Extensions/Extension.php#L555)), so it never equals the `woocommerce_sales_inline_`-prefixed literals — confirmed dead/defensive code for this Type; the check only matches the sibling `woocommerce_sales_inline` source.
- No PHPUnit tests specific to this Type exist under `tests/` — confirmed: `grep -rli "inline" tests/` returns no hits.
- Don't confuse this Type's extensions (`woo_inline`, etc.) with `WooCommerceSalesInline` (`woocommerce_sales_inline`), which belongs to the separate `woocommerce_sales` Type — see [Compatible extensions](#compatible-extensions).

## Related docs

- [Adding a New Notification Type](../development/adding-a-notification-type.md)
- [WooCommerce Sales Notification Type](woocommerce_sales.md) — covers the confusingly-named sibling `woocommerce_sales_inline` extension
