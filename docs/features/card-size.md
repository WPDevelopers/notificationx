# Card size presets

A per-notification **Card Size** setting: **Compact** (0.8×), **Default** and **Large** (1.2×). The whole card scales together (text, image, spacing), while its position, its stacking and its animations stay as they are. This is all Free code; Pro's own themes join through a filter (see [Add-ons and Pro themes](#add-ons-and-pro-themes)).

## The setting

- **Where:** builder → **Customize** → **Appearance** → **Card Size**, next to Notification Size ([GlobalFields.php:1982](../../includes/Extensions/GlobalFields.php#L1982)). Not in Quick Builder.
- **Stored as:** `card_size` in the notification's settings: `compact`, `default` (the field default) or `large`.
- **Shown only for supported themes.** The field's rule is `Rules::includes( 'themes', CardSize::supported_themes() )`.

## Supported themes

Free's list is `CardSize::FREE_THEMES` ([CardSize.php:67](../../includes/Core/CardSize.php#L67)): 48 keys, as saved in `themes`.

| Family | Keys |
| --- | --- |
| Sales | `conversions_` and `woocommerce_sales_` × `theme-one`, `theme-two`, `theme-three`, `theme-five`, `conv-theme-twelve`, `conv-theme-thirteen`, `conv-theme-fifteen` |
| Comments | `comments_theme-one`, `-two`, `-three`, `-six-free`, `-seven-free`, `-eight-free` |
| Reviews | `reviews_` (WordPress.org), `woo_reviews_`, `reviewx_`, `woocommerce_sales_reviews_` × `total-rated`, `reviewed`, `review_saying`, `review-comment`, `review-comment-2`, `review-comment-3` |
| Download stats | `download_stats_today-download`, `-7day-download`, `-actively_using`, `-total-download` (Pro's Freemius stats use the same keys) |

The field is hidden, and the value is ignored, for every other theme:
- Pro-locked themes and Google Reviews: Pro adds them later.
- Verified Cards: keys containing `_vc-` are never supported.
- The live layouts, bars, popups, GDPR, Exit Intent, inline notifications and Pro's mobile one-line card.

`tests/test-card-size.php` checks that the list is exactly the free themes of those types and sources.

## How it works

1. **Server guard.** `CardSize::normalize()` ([CardSize.php:188](../../includes/Core/CardSize.php#L188)) keeps `card_size` only when it is `compact` or `large` and the theme is supported. Otherwise it removes the key. It runs on `nx_filtered_post` (the frontend, Cross Domain Notice and shortcodes) and in `Preview::preview_settings()` ([Preview.php:429](../../includes/FrontEnd/Preview.php#L429)).
2. **Frontend class.** `getCardSize()` ([functions.ts:71](../../nxdev/notificationx/frontend/core/functions.ts#L71)) checks the value again; `Notification.tsx` then adds `nx-card-size-compact` or `nx-card-size-large` and `--nx-scale: 0.8 | 1.2` to the card root ([Notification.tsx:181](../../nxdev/notificationx/frontend/core/Notification.tsx#L181)).
3. **CSS.** [`_card-size.scss`](../../nxdev/notificationx/frontend/scss/_themes/_card-size.scss), imported last in `theme.scss`:
   - **Zoom:** `zoom: var(--nx-scale)` on `.notificationx-inner`, all inside `@supports (zoom: 1)`.
   - **Large** applies from 575px only; below that a Large card shows at Default.
   - **Phones:** Compact keeps the phone layout's minimum width (`min-width: min(300px, 100%)` from the container rules), so its text can wrap onto fewer lines there.
   - **Full size at every preset:**
     - The close button (35×30) and the Button tab, which renders outside `.notificationx-inner` with its `--nx-overhang-top` unchanged.
     - The room a theme keeps for them, e.g. theme 12's in-card link and theme 15's corner pill.
   - **Small text in Compact:** text of 10px or more at Default renders at 10px or more. Text already under 10px at Default (the credit's 8.33px "by") keeps its Default size: `calc(1em / var(--nx-scale))`.
   - **Root styles that zoom can't reach** are scaled per theme: corner radius, and the 360px width limit of themes 12, 13 and 15.

## What Default guarantees

- **No new markup.** Default, a missing key or an unexpected value adds no class, no style and no variable, so the card root is byte-identical. Golden snapshots: `tests/js/frontend/card-size-golden.test.js`.
- **No CSS applies.** Every card-size selector starts with `.notification-item.nx-card-size-`. The compiled rules are appended to the end of `frontend.css` (and where the theme CSS ends in `crossSite.css`). `bin/check-css-append.js` checks this.
- **0 different pixels.** `tests/visual/` screenshots every supported theme at Default against the previous stylesheet; see its README.
- **Nothing is migrated or written.** A notification saved before the setting existed reads back as `card_size = 'default'`, because `NotificationX::normalize_post()` fills field defaults on read. It renders as Default and its row is never written.
- **Browsers without `zoom`** (Firefox before 126) show Default.
- **Taking a theme off the list** returns its cards to Default; the saved value is kept for later.

## Add-ons and Pro themes

Add theme keys (as saved in `themes`) through **`nx_card_size_themes`** ([CardSize.php:157](../../includes/Core/CardSize.php#L157)). Keys containing `_vc-` are ignored.

```php
add_filter( 'nx_card_size_themes', function ( $themes ) {
    $themes[] = 'conversions_conv-theme-seven';
    return $themes;
} );
```

Add a theme only after its cards look right at Compact and Large, and keep its fixes in your own stylesheet:

- **Scope** every rule as `.notification-item.nx-card-size-compact.themes-{key} …` (or `-large`, inside `@media (min-width: 575px)`). Use `var(--nx-scale)` so nothing applies without the class.
- **Root styles:** zoom scales only `.notificationx-inner`. Scale root-level sizes yourself, e.g. `border-radius: calc(10px * var(--nx-scale))`. Reset a `min-width: 0` theme: Free's scaled `min-width` excludes only its own themes.
- **Parts outside the zoom:** if a part sticks out of the card from inside the zoomed area (a badge), scale its overhang too: `--nx-overhang-top: calc(10px * var(--nx-scale))`.
- **Small text:** follow the same Compact floor: 10px, and Default size for text already under 10px.
- **Older Free:** with a Free version that has no card size, the filter is never called and the scoped rules never match.

## Tests and tools

- **PHP:** `tests/test-card-size.php`. Covers the field, the supported list, `normalize()`, the hooks, existing notifications and the preview.
- **JS:**
  - `tests/js/frontend/card-size.test.js`: `getCardSize()` and the root classes.
  - `tests/js/frontend/card-size-golden.test.js`: Default markup snapshots.
- **CSS:** `bin/check-css-append.js` and its test `tests/js/check-css-append.test.js`.
- **Screenshots:** `tests/visual/`, a developer tool run by hand and not shipped. See its README.
