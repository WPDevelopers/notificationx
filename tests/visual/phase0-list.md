# Card size presets (84708): Phase 0 list

Measured on dev's `frontend.css` (before any card-size code) with
`node tests/visual/screens.js measure`, in Chrome, at 1440px and 390px, credit on
and off, for the 30 in-scope themes (`tests/js/frontend/card-size-fixtures.js`).
Re-run the measure command to refresh it; it writes the full tables to `phase0.md`.

## 1. What zoom on `.notificationx-inner` covers

- In all 30 themes the root (`.notification-item`) has exactly one child,
  `.notificationx-inner`. The image, content, rows, credit and close button are
  all inside it, so all of them scale with the card.
- No in-scope theme has root padding or a root border.
- `theme-five`, `comments_theme-six-free` and `review-comment` have a root 15–50px
  taller than the inner part at 1440px. That space comes from the inner part's own
  layout, so it scales too.

## 2. What stays on the root, unscaled (for P4–P6 to decide per family)

| Root style | Themes | Effect in Compact / Large |
|---|---|---|
| `border-radius: 5px` | sales `theme-one`, downloads `7day-download`, `actively_using` | Stays 5px (4px / 6px would be exact). Barely visible. |
| `border-radius: 10px` | sales `conv-theme-twelve`, `-thirteen`, `-fifteen` | Stays 10px (8px / 12px exact). |
| `border-radius: 100px` (pill) | sales `theme-two`; comments `theme-two`, `-seven-free`, `-eight-free`; reviews `reviewed`, `review_saying`, `review-comment-2`, `-3`; downloads `total-download` | None: still a pill. |
| `box-shadow` | most themes: `0 0 10px rgba(0,0,0,.1)` or `0 4px 18px rgba(19,27,46,.12)` | Stays the same size. Barely visible. |
| `background-color` | on the root (white) in the themes that have one | Follows the card, since the root wraps the zoomed part. |
| `min-width: 300px` | every theme except the three below | Needs the scaled `min-width` (P3). |
| `min-width: 0` | sales `conv-theme-twelve`, `-thirteen`, `-fifteen` | **Must keep `0`.** The P3 rule `min-width: calc(300px * var(--nx-scale))` would add a minimum these themes don't have. P3 excludes them (`:not(.themes-conv-theme-twelve)` etc.), or P4 resets them. |

## 3. Text under 12.5px (renders under 10px in Compact)

| Selector, from `.notificationx-inner` | Size | Themes |
|---|---|---|
| `.notificationx-content .nx-third-row .nx-branding .nx-byline` (the credit's "by") | 8.33px | every theme except the three below and `total-download` |
| same | 10px | sales `conv-theme-twelve`, `-thirteen`, `-fifteen` |
| same | 10.83px | downloads `total-download` |
| `.notificationx-content .nx-third-row > span > span` (time, "2 hours ago") | 10px | sales `theme-one`, `-two`, `-three`, `-five`; all six comments themes; downloads `today-download`, `7day-download` |
| `.notificationx-content .nx-first-row > span > span` (time) | 11px | sales `conv-theme-twelve`, `-thirteen`, `-fifteen` |
| `.notificationx-content .nx-second-row > span > span` | 11px | downloads `today-download`, `7day-download` |
| `.notificationx-content .nx-second-row > span > span` (post title) | 12px | all six comments themes; reviews `review-comment`, `-2`, `-3` |

The floor (P4–P6): in Compact these selectors get `calc(12.5px / …)` so that they render at
10px or more. The credit's "by" today is 8.33px even at Default; Compact should not shrink
it further, so it gets the floor too.

## 4. Measurement notes

- Root boxes at 1440px: 300–500px wide, 86–174px tall (typical 355×124).
- At 390px every `min-width: 300px` theme is exactly 300px wide; `conv-theme-twelve` is
  340px, `-fifteen` 360px.
