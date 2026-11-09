# Overhang visual tests (opt-in)

Parts of a card can stick out of its box: the link button above it, a badge, a scaled image,
a branding row below it. Each one declares how far on the card (`--nx-overhang-top`,
`--nx-overhang-bottom`, see `nx-overhang` in `nxdev/notificationx/frontend/scss/_mixins.scss`),
and the four corner containers add that to the card's margins (`_container.scss`). So in a stack,
and at the screen edge, the spacing starts at the outermost part, in both spacing modes.

`tests/js/overhang-css.test.js` (run by `npm run test:js`) checks the built CSS. These scripts
check the layout in headless Chrome. They need a running WordPress site with NotificationX and
NotificationX Pro active and Google Chrome. They are not part of `npm run test:js`.

Nothing is saved on the site. Every card is the site's own response to the builder's preview
request (`nx-preview`), rendered by the built JS and CSS. Other POST requests are answered
with `{}` and other hosts are blocked.

## Setup

```sh
export NX_VISUAL_SITE=http://storefy.local/          # default
export NX_VISUAL_OUT=/tmp/nx-visual-overhang         # default: <tmp>/nx-visual-overhang
export NX_VISUAL_CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"   # default
php tests/visual/overhang/themes.php /path/to/wp-load.php > "$NX_VISUAL_OUT/themes.json"
```

With Local, add `-d mysqli.default_socket="$HOME/Library/Application Support/Local/run/<site id>/mysql/mysqld.sock"`
to the `php` command.

Every script takes `--slice=i/n` to share a run between processes, for example
`for i in 0 1 2 3; do node tests/visual/overhang/overhang.mjs measure --slice=$i/4 & done; wait`.

## The tests

1. `overhang.mjs measure`, then `overhang.mjs check`. Every corner theme (Button off and on,
   1400px and 390px, and every Pro mobile card at 390px): what sticks out is measured from the
   elements and from the pixels drawn (shadows off). An overhang of 3px or more must be declared
   (measured at most declared + 1px), and nothing may be declared that isn't there.
2. `stacks.mjs measure`, then `stacks.mjs check`. Three cards in one corner, in all four corners,
   at 1400px and 390px, in both spacing modes, with and without the admin bar (top corners):
   - nothing overlaps;
   - the gap between what the cards draw equals the plain stack's gap (cards with nothing
     sticking out), 4px less at most for parts under 3px;
   - in the top corners nothing reaches past the plain stack's edge spacing, and in the bottom
     corners nothing reaches below it.
   Glide: the mixed stack with the Rise and Pop presets. The middle card is closed, the others
   must glide and end with the same gaps.
3. `baseline.mjs capture before` on the old build and `baseline.mjs capture after` on the new
   one, then `baseline.mjs diff before after`. Full-page screenshots of every single card and
   stack from the same stored HTML: cards that declare no overhang must have 0 different pixels.
   Some preview avatars are animated GIFs and can be caught on another frame (a few dozen
   pixels in the image area): capture that case again before treating it as a change.

Results are written to `$NX_VISUAL_OUT` (`overhang/report.json`, `stacks/report.json`,
`diff-before-after.json`).

## Adding a theme

Declare the overhang on the card next to the rule that moves the part out, from the same SCSS
variable, for example:

```scss
&.themes-my-theme {
    $badge-top: 8px;
    @include nx-overhang($top: $badge-top);
    .badge { top: -$badge-top; }
}
```

A link button that floats outside the card uses `@include nx-link-overhang($top)`, which only
applies when the card shows a link. Add-ons set the same custom properties on their cards.
