# Exit Intent: Build buttons, "Do Not Show Again" and animations

**Version:** available from Free 3.4.2. All the code is in Free; Pro has no change. The code says `@since x.x.x`.

Three changes to the Exit Intent type:
1. The two Build buttons on the Custom tab sit side by side.
2. "Do Not Show Again For" gets a toggle in front of it, off for new campaigns.
3. Show and Hide animations for the popup (NotificationX Pro, like the other types), "None" by default.

Saved campaigns behave and look exactly as before.

Manual test plan: [../../qa-guide/exit-intent-improvements.md](../../qa-guide/exit-intent-improvements.md).

## 1. Build buttons side by side

**Where:** Design → Themes → Custom, with nothing linked.

**Layout:**
- **Build With Elementor** (or **Install / Activate Elementor**) and **Build With Gutenberg** share one row.
- Each button is 240px wide, so the two are the same size.
- When the row can't hold both (narrow screens), they stack.

**Why they used to stack.** The tab is a wrapping flex row. The hidden state fields between the two buttons (`is_elementor`, `elementor_id`, `is_confirmed`) render as empty `<span>`s with `flex: 1 0 100%`, so each one forced a line break. Only the spans between the two Build buttons are now taken out of the row.

**Unchanged:**
- The spans after the buttons, which keep the space under them.
- The linked states (Edit / Remove).

**Scope.** The rules are in `nxdev/notificationx/scss/nx_new/_notificationx_add_item.scss`, scoped to the Exit Intent Custom tab. The Notification Bar's buttons are unchanged.

## 2. "Do Not Show Again" toggle

Customize → Exit Intent Settings:

| Field | Type | Default | Notes |
|---|---|---|---|
| `exit_intent_cookie_enabled` | toggle | off | "Do Not Show Again". Right before the days. |
| `exit_intent_cookie_days` | number | 7 | "Do Not Show Again For" (days). Shown only when the toggle is on. |

**On the front end:**
- **Closing always** hides the popup for the rest of the browser session (the `sessionStorage` key, as before).
- **With the toggle on,** closing also writes the `notificationx_exit_intent_{nx_id}_{theme}` cookie for the days. Only then does an existing cookie keep the popup hidden.
- **With the toggle off,** a cookie left from an earlier visit is ignored, so the popup can show again in a new session.

The code: [exitIntentDismiss.ts](../../../nxdev/notificationx/frontend/core/exitIntentDismiss.ts), `ExitIntentPopup.handleClose()`, and `isDismissed()` in `useNotificationX.ts`.

### Saved campaigns
Before the toggle, the days alone decided: more than 0 wrote the cookie. To keep that:
- **On read** (`nx_get_post`, so the builder, the `notice` payload, the Preview and export), `ExitIntentNotification::dismiss_toggle_on_read()` sets the toggle from the days for a campaign saved before the toggle: on when the days are more than 0. Nothing is stored by reading.
  - `normalize_post()` fills every field's default on read, so a missing toggle can't be told from a saved "off". That's why every save now also stores `exit_intent_cookie_toggle_saved`.
- **On save** (`nx_save_post`), `dismiss_toggle_on_save()` stores the toggle and the marker. A save without the toggle takes it from the days. That covers an export from before the toggle, and a REST or MCP call that only sets the days.
- **On the front end,** a payload without the key behaves as before.

**Other types.** The days field belongs to Exit Intent only. The Announcement's "Convert to Exit Intent" has its own `exit_intent_cookie_duration` ("Cookie Duration"), which is unchanged.

## 3. Show and Hide animations

**The section.** Customize → **Animation** (`exit_intent_animation`, priority 16) has two fields:
- **Show Animation** (`exit_intent_animation_show`);
- **Hide Animation** (`exit_intent_animation_hide`).

**Options.** They come from the other types' Animation section (`animation_notification_show` / `_hide` in GlobalFields), copied when the fields are built. So they are the same list, with the same Pro "disabled" flags and class. Exit Intent shows `default` as **None**: no animation, as every Exit Intent popup was before. The other types still show "Default".

**When the section shows:**
- built-in themes one, three and four;
- an Elementor design (`elementor_id` a number);
- a Gutenberg design (`gutenberg_id` a number).

Pro's own themes (two, five, six, seven) render their own card, so they don't take the classes. The section is hidden for them.

### How it runs
[exitIntentMotion.ts](../../../nxdev/notificationx/frontend/core/exitIntentMotion.ts), used by `ExitIntentPopup.tsx` and `ExitIntentGutenberg.tsx`.

- **The card classes** come from Pro's `nx_frontend_animation` filter. It's the same filter and stylesheet as the other types: animate.css and the `nx-anim-*` presets. Free maps the two fields onto `animation_notification_show` / `_hide` for the call and passes no `defaultClass` or `animationStyle`, so a "None" side adds nothing. Without Pro there's no filter answer and nothing changes. On read without Pro, the fields read `default` (`animation_without_pro()`), as `PostType` does for the other types.
- **The backdrop** fades in with an entrance (`nx-exit-intent-anim-in`, 0.3s) and out with an exit (`nx-exit-intent-anim-out`, 0.5s, `pointer-events: none`). These rules are in `_exit-intent.scss`, in `frontend.css` and `crossSite.css`.
- **Exit.** Every way of closing goes through `handleClose()`, which writes the close keys at once and then waits for the exit (500ms, animate.css's `animate__faster`) before removing the popup:
  - the ×, the backdrop and Escape;
  - a `#nx-close` link;
  - a form submit;
  - Pro's themes.

  A second click during the exit does nothing. The end-date close also plays the exit, without close keys.
- **Reduced motion.** With `prefers-reduced-motion: reduce` the popup closes at once, and `_reduced-motion.scss` shortens every animation in the overlay to 1ms.
- **Gutenberg designs** take the classes only once the design is shown, so an entrance never plays while the popup waits for its block assets.
- **Re-mounts.** `NotificationContainer` used to render the position groups by index, so another notification's group appearing or going (for example a sales notification) re-mounted an open popup. The Exit Intent group is now a keyed fragment, so the popup keeps its state (`tests/js/frontend/exit-intent-remount.test.js`). As a safety net, the popup also remembers per notice ID for the page view:
  - that its entrance has played, so a re-mount doesn't replay it (`exitIntentShown`);
  - that it is leaving, so a re-mount during the exit finishes it (`exitIntentLeaving`).

**With "None" for both** (the default, and every campaign saved before), the markup is byte for byte what it was. The snapshots in `tests/js/frontend/exit-intent-motion-none.test.js` were written before the change.

## Limits
- **Pro's themes** two, five, six and seven don't animate yet; the options are hidden for them.
- **No duration setting.** The Pro "Animation Duration" field (bars, announcements, flashing tab) doesn't apply; the exit waits 500ms.

## Tests
- **PHP:**
  - `tests/test-exit-intent-dismiss-toggle.php`: the fields, saved campaigns in the builder and the payload, re-saves, REST saves, other types;
  - `tests/test-exit-intent-animation.php`: the section, the options, the rules, saved campaigns, without Pro.
- **Jest:**
  - `tests/js/admin/exit-intent-custom-tab-css.test.js`: the Build buttons, and the earlier button rules against `tests/js/fixtures/admin-css-builder-buttons.json`;
  - `tests/js/frontend/exit-intent-dismiss.test.js`;
  - `tests/js/frontend/exit-intent-motion-none.test.js`: "None" unchanged, written before the change;
  - `tests/js/frontend/exit-intent-motion.test.js`: every close path, reduced motion, re-mounts, Pro themes, without Pro;
  - `tests/js/frontend/exit-intent-remount.test.js`: typed text, focus and the popup's DOM stay while other notifications come and go;
  - `tests/js/exit-intent-motion-css.test.js`.
