# QA: Click-open popup

**Version:** Free x.x.x (to be set). Branch `click-open-popup`.

**What changed:** an Announcement popup can open when a visitor clicks a chosen button or link (Customize → When to Open). Feature doc: [../features/click-open-popup.md](../features/click-open-popup.md).

**Must not change:** popups that open automatically (the delay, Convert to Exit Intent, closing for the session or for good), and every other notification.

## Setup

- Two Announcement popups:
  - **A**: When to Open "Only when a visitor clicks", Click Target `.open-offer`, Show On: All pages;
  - **B**: "Automatically", delay 1 s.
- A page with:
  - `<button class="open-offer">`;
  - a link `<a href="#nx-popup-<A's id>">`;
  - `<span class="open-offer" role="button" tabindex="0">`.
- A browser that is not logged in.

## Cases

**T1 — Builder.** Open A → Customize.
Expected:
- "When to Open" sits just above Exit Intent Popup, with Open and Click Target and the help text.
- Click Target hides with "Automatically".
- Other sources don't have the section.

**T2 — Not on its own.** Open the page and wait.
Expected: A doesn't open.

**T3 — Click.** Click the button.
Expected: A opens at once, and focus is in the popup.

**T4 — Esc.** Press Esc.
Expected: it closes, and focus is back on the button.

**T5 — Closed before.** Close it with ×, reload, click again. Then set "Don't show the Notification again" on A, close it, reload, and click.
Expected: it opens every time.

**T6 — Link.** Click the `#nx-popup-<id>` link.
Expected: it opens, and the page doesn't jump.

**T7 — Added later and keyboard.** In DevTools, add `<button class="open-offer">` to the page and click it. Then Tab to the span and press Enter, then Space.
Expected: it opens each time, never twice at once.

**T8 — Show On.** Set A to Show On: a different page, and click a `.open-offer` element on this page.
Expected: nothing opens.

**T9 — Automatic popup unchanged.** B opens after 1 s; after closing it, it doesn't come back in this session. With Convert to Exit Intent on, it waits for the exit, as before.

**T10 — Both.** A set to "Automatically and when a visitor clicks".
Expected: it opens after the delay. After closing it, it doesn't open on its own again, but a click opens it.

**T11 — Preview.** In the builder, Preview A.
Expected: it shows, even with "Only when a visitor clicks".

**T12 — Phones.** At 390 px, a tap opens it.
Known (not this change): the theme's × sits partly outside the right edge, as for automatic popups.

**T13 — Bad targets.** Enter `a[href], body > div, .ok` and save.
Expected: only `.ok` is kept.
