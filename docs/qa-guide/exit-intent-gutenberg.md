# QA: Exit Intent "Build With Gutenberg"

**Version:** Free 3.4.2 / Pro 3.3.2. All the code is in Free; Pro has no change.

**What changed.** Exit Intent → Design → Themes → Custom can now build the popup with the block editor:
- four starting designs;
- Edit With Gutenberg and Remove buttons;
- the design shows inside NotificationX's popup when the visitor moves to leave.

Elementor popups and the Notification Bar's Gutenberg option must behave exactly as before.

How it works: [../features/exit-intent/gutenberg/README.md](../features/exit-intent/gutenberg/README.md).

## Setup
- WordPress 6.x or 7.x, Free on this branch, Pro installed or not (cases say which).
- A classic theme (for example Hello Elementor) for most cases; a block theme (for example Twenty Twenty-Five) for T15.
- Elementor active for T10–T12.
- A logged-out browser (or a private window) for the live cases.
- **Triggering Exit Intent on a desktop:** move the mouse out through the top edge of the window.
- **Showing it again:** clear the site's sessionStorage and the `notificationx_exit_intent_*` cookies.

## Cases

**T1. Custom tab, nothing linked.** New Exit Intent campaign → Design → Themes → Custom.
- Expected:
  - "Create with Your Preferred Builder/Editor";
  - **Build With Elementor** and **Build With Gutenberg** (Elementor active);
  - with Elementor inactive: Install/Activate Elementor and Build With Gutenberg.

**T2. Choose and import.** Build With Gutenberg.
- Expected:
  - the "Choose your design" modal with 4 designs (Coupon code, Flash sale with countdown, Image and text, Free shipping announcement);
  - Import shows "Design imported", then Next goes to Display;
  - Update saves.
- Fail: an error toast, or no design linked after reopening.

**T3. Linked state.** Reopen the campaign → Custom.
- Expected:
  - the chosen design's preview, **Edit With Gutenberg** and **Remove**;
  - no Build buttons;
  - the Content step and content sections are hidden.
- Customize still has the Exit Intent Settings (position, overlay colour, close button, sensitivity, cookie days, hide on mobile, hide after end).

**T4. Block editor.** Edit With Gutenberg.
- Expected:
  - the block editor opens in a new tab, titled "Design for NotificationX Exit Intent - {campaign title}";
  - no "This block contains unexpected content" warning on any block;
  - change a heading and Save.

**T5. Live popup, desktop 1440 px.** Enable the campaign, open the home page logged out, trigger Exit Intent.
- Expected:
  - the design as in the editor (colours, layout, image, countdown ticking for design 2), inside the dark overlay;
  - the × on the card's top corner;
  - the edited heading from T4.
- Fail: unstyled blocks, a missing image, the countdown at 00.

**T6. Close and frequency.**
- Steps: close with the ×, then reload and trigger again; repeat with a backdrop click and with Escape.
- Expected:
  - each closes;
  - the popup doesn't show again in this session, nor for the cookie days (7 by default);
  - with cookie days 0, it shows again in a new session.

**T7. `#nx-close` link and keyboard.**
- Design 1's "No thanks, I'll pay full price" closes the popup without leaving the page.
- With the keyboard: focus starts on the ×, Tab cycles inside the popup, Escape closes, and focus returns to the page.

**T8. Phone width (390 px), long content.**
- Steps: in a desktop browser at 390 px wide (not a touch device), trigger.
- Expected:
  - the popup is the screen width minus 32 px;
  - the × sits inside the card's top corner, fully visible, about 44 px;
  - design 3 stacks the image on top.
- Long content: add blocks until the design is taller than the window. Expected: it scrolls inside the popup; the page behind doesn't scroll.

**T9. Touch device.**
- On a phone or touch emulation, with "Disable on mobile" on (the default): no popup.
- Same as Elementor designs; no touch trigger exists.

**T10. Switching builders.**
- Steps: Remove (Gutenberg) → Build With Elementor → import → Remove → Build With Gutenberg.
- Expected:
  - each Remove deletes its design and restores the Custom tab's empty state;
  - only one builder's buttons show at a time.

**T11. Elementor popup unchanged.**
- Steps: an Exit Intent campaign built with Elementor (new, or existing from before this version).
- Expected:
  - the builder Custom tab, the Elementor editor and the live popup look and behave exactly as before (× outside the corner, backdrop closes, Escape does nothing new);
  - compare with screenshots from the previous version.

**T12. Notification Bar Gutenberg unchanged.**
- Steps: a Notification Bar with Build With Gutenberg.
- Expected: the modal, editor and live bar as before.

**T13. Builder Preview and share link.** Exit Intent campaign with a block design → Preview.
- Expected:
  - the design shows styled (desktop, tablet, phone buttons), also after "Page" picks another page and in "Open in new tab";
  - a share link (Pro) shows it to a logged-out visitor.

**T14. Cached page.**
- Steps: with a page cache (for example xSpeed), visit the page logged out so it gets cached; then link a block design (or change it) and visit the cached page again.
- Expected: the popup shows styled after a short delay (assets fetched from the design's page); never unstyled.

**T15. Block theme.**
- Steps: switch to Twenty Twenty-Five, then repeat T5 and T8 for designs 1–4.
- Expected: the same look as in the classic theme (colours are fixed values); fonts follow the theme.
- Switch the theme back afterwards.

**T16. RTL.**
- Steps: set the site (or your user) language to Arabic or Hebrew and trigger.
- Expected:
  - the × on the top-left;
  - design 3's image on the right;
  - text right-aligned where the design isn't centred.

**T17. Duplicate, delete, rename.**
- **Duplicate** the campaign from All NotificationX. Expected: the copy gets its own design (Edit With Gutenberg opens a different post); Remove on the copy leaves the original working.
- **Rename** a campaign and Update. Expected: the design's title follows.
- **Delete** a campaign. Expected: its design is gone (not in Templately's list or the editor).

**T18. A deleted design.**
- Steps: delete the design post by hand (or trash it), then reopen the campaign.
- Expected:
  - the Custom tab offers Build again;
  - the live site shows the built-in popup until a design is linked;
  - no PHP or console errors.

**T19. Export / import.**
- Steps: export the campaign (Settings → Import/Export), delete it, and import the file.
- Expected: the campaign comes back with its own new design, same content.
- Importing a file without the design (edit the JSON to drop `exit_intent_designs`): the campaign comes back with no design.

**T20. Build With AI (Pro).**
- With a block design linked, the Build With AI tab says to remove it first.
- After Remove, Build With AI works as before.

**T21. Roles.**
- A user with NotificationX rights but without `edit_posts`: sees the note "This popup uses a block design. Ask a site editor to open it in the block editor." instead of Edit With Gutenberg.
- Subscribers can't call the import or remove routes.

**T22. Classic Editor plugin.**
- Steps: with Classic Editor set to disable the block editor for all post types.
- Expected: no Build With Gutenberg button; an existing block design still shows on the live site.
