# Test Plan: Free 3.3.4 + Pro 3.2.4 (Pro code removed from free)

**Audience:** QA tester running the NotificationX 3.3.4 and NotificationX Pro 3.2.4 release candidates.
**Release under test:** free 3.3.4 with Pro 3.2.4. Some parts also use Pro 3.2.3, Pro 3.2.2, and free without Pro.

## What changed

Free no longer contains working Pro code (WordPress.org Guideline 5). Flashing Tab, Cart Peek, Inline (Growth Alert and the `[notificationx_inline]` shortcode) and the Discount Alert designs now run only from NotificationX Pro. Steps 5 and 7 of the code separation ship together.

| Area | Change | What you can see |
|---|---|---|
| Free + Pro 3.2.4 | Pro renders Discount Alert, Cart Peek, Inline and Flashing Tab itself | **Nothing.** Everything must look and act as on free 3.3.3 + Pro 3.2.3, except the two fixes below |
| Discount Alert button | Clicking the button now closes the notification | Fix |
| Cart Peek rows | Cart Peek designs show their own rows (count, product, time) | Same as before with Pro 3.2.3 |
| Cross Domain Notice | Free's `frontend.js` loads Pro's `frontend-themes.js` on the other site | Discount Alert and Cart Peek keep their badge, button and rows on non-WordPress sites |
| Free without Pro | Cart Peek is no longer in the notification type list | Other Pro upsells stay locked as before |
| Old Pro (3.2.3 or older) | Warning notice, cannot be dismissed. Some Pro notifications stop showing | No white screen, no PHP fatal error |

Developer reference: [../api/frontend-js-hooks.md](../api/frontend-js-hooks.md) ("Why these hooks exist", "Cross Domain Notice").

## Setup

1. Two sites with the same notifications:
   - **Test site:** free **3.3.4** + Pro **3.2.4** (release candidates).
   - **Comparison site:** free **3.3.3** + Pro **3.2.3**.
2. On both sites: WooCommerce with at least 3 products, one completed order, and one product in a cart from another browser (for Cart Peek).
3. In **NotificationX → Settings → Modules**, turn on WooCommerce, Discount Alert, Flashing Tab, Notification Bar and Growth Alert.
4. Make sure `NX_DEBUG` is not defined. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
5. Keep the DevTools **Console** and **Network** tabs open on every frontend check. **Any red Console error that mentions NotificationX, `wp.hooks` or React is a failure.** Check `wp-content/debug.log` after each part: **any PHP fatal error, warning or notice from `notificationx` is a failure.**
6. Create these notifications on the comparison site **before** you update it to the test versions (they test old saved data), and the same set new on the test site:
   - Discount Alert: one each of theme 1, 2, 12, 13, 14, 15, with an end date 5 days ahead, a discount, a button text and a button link.
   - Cart Peek: one with "Theme Fourteen" and one with "Theme One".
   - Flashing Tab: one each of theme 1 to 4.
   - Growth Alert (WooCommerce inline) on the product page.
   - One page with the `[notificationx_inline id="<growth alert id>"]` shortcode.

---

## Part A: Free 3.3.4 + Pro 3.2.4

**T1. Discount Alert theme 1 and 2.** Open a page that shows them. Expected: the discount badge is on the left with the discount and "OFF", the colors from the design tab apply, and the text matches the comparison site. On a site in another language (for example French with a translation for "OFF"), the label is translated.

**T2. Discount Alert theme 12 to 15.** Expected: themes 13, 14 and 15 show the button (theme 13 with a play icon before the text, theme 14 at the end of the text, theme 15 after the text). The time reads "5 days remaining", not "5 days ago". Click the button: the link opens and **the notification closes**. A click on the card outside the button does not navigate.

**T3. Discount Alert on mobile.** Open a Discount Alert page at 375 px width (DevTools device mode). Expected: same layout as on the comparison site (desktop layout, not the compact mobile card).

**T4. Cart Peek.** Open the product that is in a cart. Expected: "Theme Fourteen" shows two rows (shoppers count, product). "Theme One" shows three rows (count, product, time). No "purchased" word from the Sales design. Same as the comparison site.

**T5. Growth Alert and inline shortcode.** Open the product page and the shortcode page. Expected: the Growth Alert text shows below the price, and the shortcode renders the same text once (not twice).

**T6. Flashing Tab.** For each theme: open the page, switch to another browser tab and wait for the delay. Expected: the tab title and icon alternate. Check both the notifications created before the update and the new ones. In the builder, the icon pickers show the icons. In Network, no request to `notificationx/assets/public/image/flashing-tab/` or `notificationx/assets/public/js/flashing-tab.js` (those files no longer exist); the icons and script come from `notificationx-pro/`.

**T7. Builder preview.** Edit each Discount Alert and Cart Peek notification and open **Preview**. Expected: same look as on the frontend (T1 to T4).

**T8. Script only where needed.** On a site with no enabled Discount Alert or Cart Peek notification, open the frontend. Expected: no `frontend-themes.js` request. Enable one Discount Alert: `frontend-themes.js` loads once.

**T9. Regression.** Compare a Sales popup, a Review, a Notification Bar, a Popup, the GDPR notice and an Exit Intent with the comparison site. Expected: no difference.

## Part B: Cross Domain Notice

**T10.** In **NotificationX → Settings → Cross Domain Notice**, add the test origin and copy the snippet. Paste it into a plain `.html` file on a **non-WordPress** server (for example `python3 -m http.server 8765`, then `http://localhost:8765/`; not `file://`). Expected:
- Discount Alert themes 1, 2, 13, 14, 15 show their badge and button as on the WordPress site, and the time says "remaining".
- Cart Peek shows its own rows.
- Network: the `notice` request body contains `"addon_scripts":true`, the response contains `addon_scripts` with `frontend-themes.js`, and `frontend-themes.js` loads once.
- Console: no errors.

**T11.** Use a snippet that was copied from the comparison site **before** the update (the old snippet). Expected: same as T10. Users do not need to copy the snippet again.

**T12.** Disable all Discount Alert and Cart Peek notifications and reload the T10 page. Expected: no `frontend-themes.js` request, other notifications still show.

**T13.** If the site has a legacy `crossSite.js` snippet (`window.nxCrossSite`), test it too. Expected: same as T10, plus the existing "old version of cross-domain scripts" warning in the Console.

## Part C: Free 3.3.4 without Pro

Deactivate NotificationX Pro.

**T14.** Open the dashboard, **NotificationX → All NotificationX**, **Add New** and Settings. Expected: no fatal error, no PHP warnings in `debug.log`.

**T15.** In **Add New**, look at the notification types. Expected: Cart Peek is not in the list. Flashing Tab, Discount Alert and Growth Alert still show as Pro (locked) as before.

**T16.** The Cart Peek, Discount Alert and Flashing Tab notifications created with Pro still exist in the list. Open one in the builder. Expected: no fatal error. Frontend: no Console errors. (Pro notifications do not need to render without Pro.)

## Part D: Free 3.3.4 with an older Pro

**T17. Pro 3.2.3.** Install Pro 3.2.3 on the test site. Expected:
- No fatal error anywhere (admin, frontend, builder, shortcode page).
- The yellow notice "You are using NotificationX Pro 3.2.3. Please update NotificationX Pro to version 3.2.4 or later…" shows on every admin screen, including NotificationX screens, only for administrators. It has no dismiss button.
- Flashing Tab works. Growth Alert and the inline shortcode show nothing. Discount Alert shows without badge and button. Cart Peek shows the generic Sales layout.

**T18. Pro 3.2.2.** Same as T17, no fatal error. Flashing Tab and Cart Peek also stop working.

**T19.** Update Pro to 3.2.4 from **Plugins → Updates**. Expected: the notice disappears and T1 to T6 pass without other changes.

## Part E: Update order

**T20.** On a copy of the comparison site, update **Pro first** (3.2.4, still free 3.3.3), check T1 to T6, then update free to 3.3.4 and check again. Expected: all pass at both points.

**T21.** On another copy, update **free first** (3.3.4, still Pro 3.2.3). Expected: T17 behavior, then T19 after the Pro update.

---

## Reporting

List each case id with pass or fail. For a fail, add the page URL, a screenshot, the Console error and any `debug.log` line.
