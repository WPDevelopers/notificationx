# Exit Intent Popup: Build With Gutenberg

**Version:** Free 3.4.2 / Pro 3.3.2. The code says `@since x.x.x`.

An Exit Intent popup can be designed in the block editor, as an alternative to a built-in theme or an Elementor design. It mirrors the Notification Bar's Gutenberg option without changing the bar's code. Exit Intent's Elementor option is also unchanged.

Manual test plan: [../../../qa-guide/exit-intent-gutenberg.md](../../../qa-guide/exit-intent-gutenberg.md).

## What the admin sees

1. **Exit Intent → Design → Themes → Custom.** With the block editor available, the empty state reads "Create with Your Preferred Builder/Editor". The tab shows **Build With Elementor** and **Build With Gutenberg**.
2. **Build With Gutenberg** opens "Choose your design" with four starting designs, made of core blocks and the NotificationX countdown only:

   | Key | Design | Width |
   |---|---|---|
   | `theme-one` | Coupon code ("Wait! Here's 10% off", coupon box, button, "No thanks" link) | 520 px |
   | `theme-two` | Flash sale with the NotificationX countdown (evergreen 11:59, no days) | 560 px |
   | `theme-three` | Image and text (Free's bundled `exit-intend-theme-six.jpg`) | 760 px |
   | `theme-four` | Free shipping announcement (two buttons) | 560 px |

3. **Import** creates the design. **Next** goes to the Display step.
4. The Custom tab then shows the design's preview, **Edit With Gutenberg** and **Remove**.
   - Edit With Gutenberg opens the core block editor in a new tab.
   - A user who can't edit the design sees a note instead of the link.
   - Remove deletes the design immediately.
5. While a block design is linked:
   - the content sections and the Content step are hidden (as for Elementor designs);
   - the Customize popup settings stay (position, overlay colour, close button, sensitivity, cookie days, hide on mobile, hide after end date);
   - Build With AI says to remove the block design first.

**One builder at a time.** Build With Elementor and Install Elementor hide while a block design is linked. Build With Gutenberg hides while an Elementor design is linked, and when the block editor is off for designs (for example with the Classic Editor plugin).

## How it works

All of it is in Free: [`includes/Extensions/ExitIntent/GutenbergDesign.php`](../../../../includes/Extensions/ExitIntent/GutenbergDesign.php), booted from `ExitIntentNotification::__construct()`.

### Storage
- **Post type `nx_exit_intent_eb`.** Same set-up as the bar's `nx_bar_eb`:
  - `show_in_rest` with its own REST base, `nx-exit-intent-designs`, and `WP_REST_Blocks_Controller`;
  - capabilities from core's `wp_block` map;
  - not in the admin menu;
  - `publicly_queryable`, because the runtime's asset fallback fetches the permalink, with a `noindex` robots header.
- **Campaign fields.** The campaign stores `gutenberg_id`, `gutenberg_edit_link` and `gutenberg_exit_theme`. `is_gutenberg` and `is_gb_confirmed` are builder-only and aren't saved. These are the bar's field names, scoped to `exit_intent_custom`.
- **Starting designs.** `includes/Extensions/ExitIntent/jsons-gb/theme-*.json` (`title`, `content`). The token `{{NX_IMG}}` is replaced by Free's `assets/public/image/exit-intent` URL. Preview images are in `assets/admin/images/extensions/themes/exit-intent-gutenberg/`.
- **Countdown styling.** The countdown's look in `theme-two` is set through the block's own attributes, and its `blockStyles` were produced by the block editor. The block rebuilds `blockStyles` from its attributes when the editor loads.

### REST

| Route | Permission | What it does |
|---|---|---|
| `POST /notificationx/v1/exit-intent/gutenberg/import` (`theme_id`) | `edit_notificationx` | Creates a published design from a starting design. Returns `{success, data: {context: {themes: null, gutenberg_id, gutenberg_edit_link}}}`. Only the four keys are accepted, so no path is ever built from input. Anything else returns a 400 error, which the builder shows. |
| `POST /notificationx/v1/exit-intent/gutenberg/remove` (`gutenberg_id`) | `edit_notificationx` | Deletes the design and its WPML translations. Only ever deletes `nx_exit_intent_eb` posts. |

### Front end

**`notice` payload.** `GutenbergDesign::inject_gutenberg_html()` runs on `nx_filtered_post` at priority 11, after `ExitIntentNotification::inject_elementor_html()`:
- It sets `mode: 'gutenberg'`, `gutenberg_html`, `gutenberg_layout` (`width` and `radius` from the outer Group block's Content width and border radius; 600 px and no radius without them) and `gutenberg_url` (the permalink).
- It renders only an `nx_exit_intent_eb` post that is published with no password (or a draft its editor is viewing). Anything else keeps the built-in popup.
- If an Elementor design applies, Elementor wins.
- It removes builder-only keys (edit link, modal state) from Exit Intent payloads.

**Render.** `do_blocks`, then embeds, `do_shortcode` and `wp_filter_content_tags`. It doesn't run the `the_content` filter, so other plugins' additions (share buttons, related posts) stay out of the popup.

**Block assets.** In `FrontEnd::get_notifications_ids()`, the `exit_intent_custom` branch calls `GutenbergDesign::enqueue_assets()` for a campaign shown on the page:
- During `wp_enqueue_scripts`, it renders the design into a discarded buffer, so each block enqueues its own CSS and JS.
- It adds `nx-block-assets-{id}` to `<body>`.
- Essential Blocks designs wait until EB has written their CSS files.

**Popup.** [`ExitIntentGutenberg.tsx`](../../../../nxdev/notificationx/frontend/core/ExitIntentGutenberg.tsx) is reached from `ExitIntentPopup.tsx` only for `mode === 'gutenberg'`.
- **Missing assets.** Without the body class (cached page, builder Preview, share link), it copies the assets from `gutenberg_url` with `LoadAssets.ts` and stays hidden until then, 8 s at most.
- **Dialog.** `role="dialog"`, `aria-modal`, labelled with the campaign title. Focus moves to the × (or the dialog) and back on close; Tab stays inside; Escape closes.
- **`#nx-close` links.** A link to `#nx-close` inside the design (like design 1's "No thanks") closes the popup like the ×.
- **Long designs** scroll inside the popup (max height = screen minus 32 px).
- **The ×** overhangs the corner on desktop. Below 480 px it sits inside the card as a 44 px target. It uses logical properties, so it moves to the left in RTL.
- **Unchanged from the other popups:** close keys (`notificationx_exit_intent_{nx_id}_` in sessionStorage and the cookie), cookie days, and hide after end date.

**WP Rocket.** Remove Unused CSS keeps the stylesheets of the blocks that active designs use (`rocket_rucss_safelist`).

### Lifecycle

| Event | What happens |
|---|---|
| Save | An ID that isn't an `nx_exit_intent_eb` design is dropped. A design another campaign already uses (a duplicated campaign) is copied. The design is named "Design for NotificationX Exit Intent - {campaign title}". |
| Open in the builder | The edit link is refreshed for the current user (empty when they can't edit). A deleted or trashed design is dropped, so Build is offered again. |
| Delete | The design is deleted, unless another campaign still links it. |
| Export / import | Export carries each design's title and blocks (`exit_intent_designs`). Import re-creates the design and relinks it, or drops the link when the file has no design. Content goes through the normal save, so kses applies to users without `unfiltered_html`. |
| Templately | The type is labelled "NX Exit Intent". |

## Limits
- **Touch devices.** Exit Intent is off on touch devices by default, and there is no touch trigger; the same applies to every Exit Intent design.
- **Countdown date.** A countdown block keeps its own date, separate from the campaign's "Stop Showing After End Date". Starting design 2 uses an evergreen timer.
- **"After a form submit"** isn't offered for block designs. The form-success detection knows Elementor and NotificationX forms, not other plugins' form blocks.
- **Inline scripts** inside block HTML don't run; block scripts load as enqueued files.
- **Editing rights.** Editing a design needs WordPress `edit_posts`, the same as the bar's designs. A role with only NotificationX rights sees the note.
- **Editor "changed" state.** The NotificationX countdown block marks the editor as changed when it loads, although the saved markup is identical.
- **Older WordPress.** The starting designs keep older block attribute shapes. WordPress 7.1 migrates them silently, so the blocks stay valid, and older WordPress versions read them directly.

## Tests
- **PHP:**
  - `tests/test-exit-intent-gutenberg-design.php` (post type, seeds, routes);
  - `test-exit-intent-gutenberg-builder.php` (builder fields, rules fixture);
  - `test-exit-intent-gutenberg-frontend.php` (payload, render, layout, assets, RUCSS);
  - `test-exit-intent-gutenberg-lifecycle.php`;
  - the new route's data sets in `test-security-post-deletion.php`.
- **Jest:**
  - `tests/js/admin/exit-intent-builder-rules.test.js` (quickbuilder's rule engine on `tests/js/fixtures/exit-intent-builder-rules.json`);
  - `tests/js/admin/exit-intent-preset.test.js`;
  - `tests/js/frontend/exit-intent-gutenberg.test.js`;
  - `tests/js/frontend/exit-intent-elementor-unchanged.test.js` (snapshots of the Elementor popup written before this feature);
  - `tests/js/exit-intent-gutenberg-css.test.js`.
