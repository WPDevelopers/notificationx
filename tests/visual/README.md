# Card size screenshot tool (84708)

Screenshots the in-scope notification themes with the real frontend code and
compares two builds of `frontend.css` pixel by pixel. It proves that card-size
work leaves Default cards exactly as they were (0 different pixels), and later
gives the Compact and Large screenshots for review.

It is a developer tool: `tests/` is excluded from the release zip (`.distignore`),
and it isn't part of `npm run test:js`. It needs local Google Chrome or Chromium
(set `CHROME_BIN` if it isn't found) and nothing beyond Node.

## Files

| File | What it does |
|---|---|
| `cards.render.js` (+ `jest.config.js`) | Step 1: renders every in-scope theme, credit on and off, with `Notification`, and writes the markup to a JSON file. |
| `screens.js` | Step 2: `compare` screenshots the cards with two stylesheets and compares them; `measure` writes each card's root box, styles and text sizes. |
| `png.js` | Minimal PNG decode and encode (Node `zlib` only). |
| `phase0-list.md` | What the measure step found on dev: the root styles zoom can't scale and the small text. |

The themes and their sample notifications are in `tests/js/frontend/card-size-fixtures.js`,
shared with the golden snapshots (`tests/js/frontend/card-size-golden.test.js`).

## Default proof: 0 different pixels

From the plugin folder, with `OUT` any scratch folder outside the plugin:

```sh
OUT=/path/to/scratch

# 1. The old stylesheet: the build before the change (here: dev's committed file).
git show origin/dev:assets/public/css/frontend.css > "$OUT/old.css"

# 2. Build the new one: npm run frontend (writes assets/public/css/frontend.css).

# 3. Render the cards (Default).
NX_VISUAL_CARDS="$OUT/cards.json" npx wp-scripts test-unit-js --config tests/visual/jest.config.js

# 4. Compare: 1440px and 390px, LTR and RTL, credit on and off. Any different pixel fails.
node tests/visual/screens.js compare --cards "$OUT/cards.json" \
  --a "$OUT/old.css" --b assets/public/css/frontend.css --out "$OUT/default"

# 5. The compiled CSS may only gain card-size rules: at the end of frontend.css, and
#    where the theme CSS ends in crossSite.css (which bundles more CSS after it).
node bin/check-css-append.js --at-end "$OUT/old.css" assets/public/css/frontend.css
git show origin/dev:assets/public/css/crossSite.css > "$OUT/old-crossSite.css"
node bin/check-css-append.js "$OUT/old-crossSite.css" assets/public/css/crossSite.css
```

`compare` prints one line per viewport, direction and credit state, and names each card that
changed. It writes both screenshots (`*-a.png`, `*-b.png`), a `*-diff.png` (differences in red)
when something changed, and `report.json`. It also fails a card that shows nothing, so a
stylesheet that didn't load can't pass as "no difference".

How it works: each card is a `srcdoc` iframe at the viewport width, mounted the way the
frontend mounts it (`#notificationx-frontend > .nx-container.nxc-bottom_left`), with Open Sans
from `assets/public/vendor/open-sans/` and a fixed test image. The entrance animation is
switched off on both sides, so the screenshot is the card at rest. Relative `url()`s in either
stylesheet are pointed at `assets/public/css/` (`--base` to change), so the old copy can live
anywhere. Chrome's headless window is at least 500px wide, which is why narrow viewports are
iframes.

## Compact and Large (from P4)

Render with `NX_VISUAL_SIZES=default,compact,large`, then pass `--size compact` or
`--size large` to `compare` or `measure`.

## Measure: the Phase 0 list

```sh
node tests/visual/screens.js measure --cards "$OUT/cards.json" --css "$OUT/old.css" --out "$OUT/measure"
```

Writes `measure.json` (per card: the root and inner boxes and styles, the close button, every
text element's path and font size) and `phase0.md` (the tables behind `phase0-list.md`).

## Checking the tool itself

- **Deterministic:** comparing a stylesheet with itself must give 0 different pixels.
- **Sensitive:** appending a 1px rule for one theme, e.g.
  `.notification-item.themes-theme-three .notificationx-inner{padding-left:1px}`, must fail and
  name exactly that theme's cards.
