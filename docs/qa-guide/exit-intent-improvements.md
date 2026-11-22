# QA: Exit Intent Build buttons, "Do Not Show Again" toggle and animations

**Version:** available from Free 3.4.2. All the code is in Free; Pro has no change, but the animations need NotificationX Pro (as for every other type).

**What changed:**
1. Design → Themes → Custom: **Build With Elementor** and **Build With Gutenberg** are side by side.
2. Customize → Exit Intent Settings: a **Do Not Show Again** toggle in front of **Do Not Show Again For**, off for new campaigns.
3. Customize → **Animation**: **Show Animation** and **Hide Animation** for the popup, **None** by default.

Saved campaigns must behave and look exactly as before.

How it works: [../features/exit-intent/dismiss-and-animation.md](../features/exit-intent/dismiss-and-animation.md).

## Setup
- **Plugins:** Free on this branch, Pro active (and without Pro where a case says so). Elementor active.
- **Saved campaigns from the previous version:** before updating, keep one Exit Intent campaign with a built-in theme, one with an Elementor design and one with a Gutenberg design, each with "Do Not Show Again For" at 7.
- **Browser:** a logged-out window for the live cases. Trigger by moving the mouse out through the top of the window.
- **Showing it again:** clear the site's sessionStorage and the `notificationx_exit_intent_*` cookies.

## Build buttons

**T1. Side by side.** New Exit Intent campaign → Design → Themes → Custom, at 1440px.
- Expected: Build With Elementor and Build With Gutenberg on one row, the same width (240px), centred, the same height.
- With Elementor inactive: Install / Activate Elementor and Build With Gutenberg side by side.

**T2. Narrow screen.** The same at 390px wide (or a narrow window).
- Expected: the two buttons stack, the same width.

**T3. Linked states unchanged.** A campaign with a Gutenberg design, then one with an Elementor design.
- Expected: Edit With … and Remove as before, with the same space under them.

**T4. Notification Bar unchanged.** A Notification Bar → Design → Custom.
- Expected: its Build With Elementor / Gutenberg buttons exactly as before.

## "Do Not Show Again"

**T5. New campaign.** Customize → Exit Intent Settings.
- Expected: **Do Not Show Again** is off and **Do Not Show Again For** is hidden.
- Turn it on: the days field appears with 7.

**T6. Off on the live site.** Toggle off, enable, trigger and close.
- Expected:
  - no `notificationx_exit_intent_*` cookie, and no popup again in this session;
  - in a new session (new private window), the popup shows again.

**T7. On.** Toggle on, 7 days; trigger and close.
- Expected: the cookie is written for 7 days, and the popup doesn't show in a new session either.

**T8. Turned off later.** Close with the toggle on (cookie written), then turn it off and Update.
- Expected: in a new session the popup shows again; the old cookie no longer counts.

**T9. Saved campaign from before (built-in, Elementor, Gutenberg).** Open each in the builder without changing anything.
- Expected:
  - the toggle is **on** and the days show 7;
  - live, closing writes the 7-day cookie as before;
  - after Update the same.
- A saved campaign with 0 days: the toggle is off (no cookie, as before).

**T10. Export / import.** Export a saved campaign from before the update, then import the file.
- Expected: toggle on, 7 days.

**T11. Announcement unchanged.** An Announcement with "Convert to Exit Intent Popup".
- Expected: its own Cookie Duration as before, and no new toggle.

## Animations (Pro active)

**T12. Section.** Customize, for a built-in theme (one, three or four), an Elementor design and a Gutenberg design.
- Expected:
  - an **Animation** section with Show Animation and Hide Animation;
  - both on **None**;
  - the lists are the other types' options (Rise, Pop, Fade, Light Speed, Zoom, Slide) with **None** first.

**T13. Pro themes.** Theme two, five, six or seven.
- Expected: no Animation section.

**T14. Saved campaigns look as before.** The saved campaigns from Setup, live.
- Expected: no animation, the popup appears and closes at once, exactly as before.

**T15. Entrance.** For each design (built-in, Elementor, Gutenberg), set Show = Zoom In (or Pop, Slide In Down) and trigger.
- Expected: the card animates in and the dark backdrop fades in with it.

**T16. Exit, every way of closing.** Set Hide = Zoom Out (or Pop out, Fade Out Down, Slide Out Up), then close with:
- the ×;
- a backdrop click;
- Escape (Gutenberg designs);
- design 1's "No thanks, I'll pay full price" (`#nx-close`, Gutenberg);
- a submitted form (theme one or an Elementor form).

Expected:
- the card animates out and the backdrop fades out, then the popup is gone (about half a second);
- the page doesn't navigate on "No thanks";
- the close cookie or session key is written as for T6 / T7;
- clicking again while it leaves does nothing.

**T17. Countdown.** Gutenberg design 2 (Flash sale) with Show and Hide set.
- Expected: the timer keeps ticking while the popup enters and leaves.

**T18. Show only / Hide only.**
- Show set, Hide None: it animates in and closes at once.
- Show None, Hide set: it appears at once and animates out.

**T19. Reduced motion.** Turn on the system "Reduce motion" setting (macOS: Accessibility → Display → Reduce motion; or DevTools → Rendering → Emulate prefers-reduced-motion) and repeat T15 / T16.
- Expected: no visible animation; the popup closes at once.

**T20. Another notification on the page.** With a sales notification (or any other notification type) cycling on the same page, open the Exit Intent popup and wait while the other notification appears and goes.
- Expected:
  - the popup doesn't play its entrance again;
  - closing during the cycle still animates out and the popup stays closed.

**T21. Without Pro.** Deactivate Pro.
- Expected: the Animation options are greyed out (as for the other types), and popups never animate, even with a saved animation.

**T22. RTL and 390px.** Repeat T15 / T16 with RTL and at 390px wide (not a touch device).
- Expected: the same, with the × in place.

## Other notifications on the page

**T23. Typed text and focus stay.** Built-in theme one with "Show Message" on (it has a text box), and a sales notification (or any corner notification) on the same page.
- Steps: open the Exit Intent popup, type in the box and keep the cursor there while the other notification appears and goes.
- Expected:
  - the text stays and the cursor stays in the box;
  - the popup doesn't flicker or play its entrance again;
  - the other notification shows and hides as before.

## Block designs on tablets and phones

**T24. Starting designs 1–4 at 1440, 1024, 768 and 390px** (desktop browser, not a touch device).
- Expected at 1440, 1024 and 768:
  - the same side-by-side layout (design 3: image beside the text);
  - design 4's two buttons side by side.
- Expected at 390:
  - everything stacked (design 3: image on top);
  - buttons one per row, full width.
- At every width: nothing cut off, no horizontal scroll, the × fully on screen.
