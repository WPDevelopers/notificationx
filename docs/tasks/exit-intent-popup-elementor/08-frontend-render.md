# Task 08 — Frontend rendering of an Elementor-built Exit Intent

## Why

This is the only piece of the Notification Bar pattern that doesn't translate 1:1. PressBar bypasses React entirely — it dumps Elementor HTML into `wp_head` via `print_bar_notice()` and the user's site CSS does the rest. Exit Intent **must** keep the React shell (`ExitIntentPopup.tsx`) because that's what owns the overlay, the close button, the `mouseleave` trigger, the sessionStorage dismiss flag, and the cookie persistence.

So the strategy is: keep React as the shell, inject Elementor's pre-rendered HTML as the popup *body*.

## What

### PHP side

1. In [`FrontEnd::get_notifications_data()`](../../../includes/FrontEnd/FrontEnd.php), when serializing exit-intent campaigns:
   - If `elementor_id` is a published `nx_exit_intent` post and Elementor is active, call `\Elementor\Plugin::$instance->frontend->get_builder_content_for_display($id, false)` and stash the HTML on the payload as `elementor_html`.
   - Also flip a payload-level `mode` field to `'elementor'`.
2. Make sure Elementor's `wp_enqueue_scripts`-time CSS/JS for that `$id` is enqueued on every page where exit intent might fire. The simplest hook is `\Elementor\Plugin::$instance->frontend->enqueue_styles()` for the post — investigate whether `get_builder_content_for_display` already registers them and, if not, prime them via `Elementor\Core\Files\CSS\Post::create($id)->enqueue()` from `FrontEnd::enqueue_scripts()`.

### React side

1. [`ExitIntentPopup.tsx`](../../../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx) — at the top of the component, branch on `settings.mode === 'elementor'`. When true, render only:
   - The overlay div (keeps `exit_intent_overlay_color` styling).
   - The close button (keeps `show_close_button`, `exit_intent_close_color`, `exit_intent_close_size`).
   - A single `<div className="nx-exit-intent-elementor-body" dangerouslySetInnerHTML={{ __html: settings.elementor_html }} />`.
   - Skip every per-theme branch.
2. SCSS: add a `.nx-exit-intent-elementor-body` wrapper rule that resets `max-width` / `width` so Elementor's own container styling wins.

### Dismissal behaviour

Persistence (`sessionStorage notificationx_exit_intent_{nx_id}_{theme}`, `exit_intent_cookie_days` cookie) keeps working unchanged — those keys are computed from the campaign config, not from the body content. Verify the `_theme` segment of the key: when `mode === 'elementor'`, use the literal `'elementor'` as `theme` so the key remains stable across re-imports.

## Acceptance

- Imported Elementor popup appears inside the React overlay when the user moves the cursor to the top of the viewport.
- Close button dismisses it and respects `exit_intent_cookie_days`.
- Elementor widgets inside the popup (buttons, forms, countdown widget, etc.) still work — their JS executes.

## Files touched

- [`includes/FrontEnd/FrontEnd.php`](../../../includes/FrontEnd/FrontEnd.php).
- [`nxdev/notificationx/frontend/core/ExitIntentPopup.tsx`](../../../nxdev/notificationx/frontend/core/ExitIntentPopup.tsx).
- [`nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss`](../../../nxdev/notificationx/frontend/scss/_themes/_exit-intent.scss).
- Rebuild with `npm run frontend`.

## Depends on

Tasks 01, 02, 03, 04, 05.

## Open questions

- Should we offer an "Elementor controls overlay too" mode (where the user lays out the dim background themselves inside Elementor)? Probably no for v1; revisit if requested.
