# QA: builder Preview on another page and in a new tab

**Version:** available from Free 3.4.0.

**What changed:** the builder's Preview popup has a page picker and an "Open in new tab" button on the right of its top bar; add-ons can add controls there (Pro: Share); preview clicks are never counted in analytics. Feature doc: [../features/builder-preview.md](../features/builder-preview.md).

**Nothing else may change:** the Preview button, the default page, the device buttons, the close button and the frame must look and behave as before. The preview access gate ([preview-access-gate.md](preview-access-gate.md)) must still pass in full.

## Setup

- A site with at least one published page, one published post, a draft page and, with WooCommerce, a product.
- An administrator, and an Author who has been given the NotificationX "edit" role (Settings → Advanced → Roles), with one draft of their own.
- A saved notification whose type shows the Preview button (for example a Sales Notification). Enable Analytics on.

## Cases

**T1 — Today's popup unchanged.** Open the notification, click Preview at a 1440px wide window.
Expected: the same popup as before: the notification on the same default page; three device buttons centred; the close button at the top right. On the right of the bar: "Page: Home ▾" and a new-tab icon, at least 16px before the close button.
Fails if anything else in the bar or the frame moved or changed.

**T2 — Device buttons.** Click tablet, then phone, then desktop.
Expected: the frame resizes as before, without reloading. With tablet the page button shows only the name; with phone, only icons. Nothing overlaps the device buttons or the close button.

**T3 — 820px window.** Make the browser 820px wide and open Preview.
Expected: the page button shows only the page name (long names end with "…"); everything fits on one line with a clear gap before the close button.

**T4 — Pick a page.** Click the page button.
Expected: a panel with a search box focused, "Pages", "Posts" and "Products" groups, the current page ticked, the draft page with a "Draft" badge.

**T5 — Search.** Type part of a product's name.
Expected: after a short pause, only matching items. Clearing the search shows the list again.

**T6 — Preview on the chosen page.** Choose the product.
Expected: the panel closes, the frame reloads on the product page with the notification, the button says the product's name.

**T7 — Draft page.** Choose the draft page.
Expected: the frame shows the draft page (as its preview) with the notification.

**T8 — Back to default.** Open the picker, click "Back to default".
Expected: the frame shows the default page again; the button says "Page: Home".

**T9 — Closing the panel.** Open the picker; press Escape. Open it again; click into the frame. Open it again; click the empty top bar.
Expected: each time the panel closes and the popup stays open. Escape on a closed panel closes the popup, as before.

**T10 — Open in new tab.** With the product chosen, click the new-tab icon.
Expected: a new tab on the product page with the notification, no admin bar. Reloading asks to resend the form.

**T11 — Author.** Log in as the Author, open Preview, open the picker.
Expected: published pages, the Author's own draft, but not the administrator's drafts or private pages.

**T12 — The gate on another page.** Copy the page URL of T6 into a private window (not logged in) and open it.
Expected: the page as a visitor sees it, no preview. Run `tests/e2e/builder-preview.js` and `tests/e2e/builder-preview-pages.js`: all checks pass.

**T13 — Preview clicks aren't counted.** Use a notification with a link. In the Preview, click the notification. Then open NotificationX → Analytics for today.
Expected: no new click (and no row for a random id in `wp_nx_stats`). The notification closes as before. Then click the same notification on the live site: the click is counted.

**T14 — Inline sources.** Preview an inline notification (Growth Alert) on a site without products.
Expected: the "no product" message as before. Choosing a product page in the picker shows the frame on that product.

**T15 — Free without Pro.** Deactivate Pro, repeat T1, T4, T10.
Expected: the same, with only the page button and the new-tab icon.

**T16 — No console errors.** During all cases, the browser console shows no new errors.
