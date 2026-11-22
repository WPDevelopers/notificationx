# Test Plan: Black Friday / Cyber Monday 2026 offer notice and pointer

**Audience:** QA tester running the NotificationX (free) release candidate that carries the BFCM 2026 campaign.
**Release under test:** the next free release built from branch `feat/bfcm2026-admin-notice`. Rename this file to `free-<version>-bfcm-2026-offer-campaign.md` once the version number is fixed. No NotificationX Pro change.

## What changed

| Surface | What you can see |
|---|---|
| Offer notice | On the WordPress Dashboard: NotificationX logo, "🛍️ **Black Friday Sale:** Turn holiday traffic into customers with 15+ real-time social proof notifications, AI-powered features & more – **now up to 40% OFF!**", a purple **Upgrade To Pro Now** button and an **I Want To Pay Full Price** link |
| Offer pointer | On NotificationX's own pages only, a WordPress pointer beside the **NotificationX** menu item: blue header "Black Friday Is On!", one line of copy, a **View Deals** button and **Dismiss** |

Both run from **15 November 2026 00:00 UTC to 14 December 2026 23:59:59 UTC**, both link to `https://notificationx.com/bfcm2026/admin-notice`, and neither shows while NotificationX Pro is active.

How it works: [../features/offer-campaign.md](../features/offer-campaign.md). Automated coverage: `tests/test-offer-campaign.php` (PHPUnit), `tests/js/admin/offer-pointer.test.js` (Jest), `tests/e2e/offer-campaign.js` (browser).

## Setup

1. Test site with the free release candidate. Turn on `WP_DEBUG` and `WP_DEBUG_LOG`.
2. **Deactivate NotificationX Pro** (it may stay installed).
3. **Deactivate the other WPDeveloper plugins** for Parts 1 and 2: Essential Addons, Essential Blocks, EmbedPress, BetterDocs, Better Payment, SchedulePress, Templately and so on. Only one WPDeveloper notice and one WPDeveloper pointer show at a time, and another plugin's can take the place of the one under test. Part 4 turns one back on.
4. Users: two **administrators** (A and B) and one **editor**.
5. The site's date must be inside the campaign. To test outside it, see T19.
6. Keep the DevTools **Console** open on every check. After each part, check `wp-content/debug.log`: **any PHP fatal error, warning or notice from `notificationx` is a failure.**

To see a notice or the pointer again after dismissing it (WP-CLI, test sites only):

```bash
# The notice: this user's dismissal, and the cooldown that follows any dismissal
wp user meta delete <user> notificationx_nx_bfcm_2026_notice_dismissed
wp transient delete wpnotice_priority_time_expired

# The pointer: this user's dismissal
wp user meta delete <user> _nx_offer_pointer_bfcm2026_dismissed
```

## Part 1: the offer notice

**T1. Shown on the Dashboard.** As administrator A, open **Dashboard → Home**.
Expected: the notice shows above the widgets, with a purple left border, the NotificationX logo on the left, the text and under it the button and the link on one row. The text reads exactly as in "What changed". "Black Friday Sale:" and "now up to 40% OFF!" are bold.
Fails if: the notice is missing, has no purple border or button (unstyled), or the logo is a broken image.

**T2. Emoji.** Look at the shopping bags emoji before "Black Friday Sale:".
Expected: a normal colour emoji, the same height as the text, on the text line.
Fails if: it is a tiny image, sits above or below the line, or shows as an empty box.

**T3. Upgrade button.** Click **Upgrade To Pro Now**.
Expected: `https://notificationx.com/bfcm2026/admin-notice` opens in a new tab. The notice is still there in the first tab.

**T4. Only on the Dashboard.** Open **Plugins**, **Posts**, **Settings → General** and **NotificationX → All NotificationX**.
Expected: the notice is on none of them.

**T5. Dismiss with the link.** On the Dashboard click **I Want To Pay Full Price**.
Expected: the notice disappears at once. Reload: it stays gone. Log in as administrator B: B does not see it either for now (the cooldown after a dismissal). Run the two `wp` commands above for user A, reload: it is back.

**T6. Dismiss with the ×.** Click the **×** at the top right of the notice.
Expected: same as T5.

**T7. Narrow window.** Restore the notice, then make the browser window narrower than 575px (or use the device toolbar).
Expected: the notice is hidden. Between 575px and 782px it shows with a smaller logo and no overlap.

**T8. With Pro.** Restore the notice. Activate NotificationX Pro and open the Dashboard.
Expected: no offer notice. Deactivate Pro again: the notice is back.

## Part 2: the offer pointer

**T9. Shown beside the menu item.** As administrator A, open **NotificationX → All NotificationX**.
Expected: a pointer sits to the right of the admin menu, level with **NotificationX**, with an arrow pointing at it. Blue header with a white round icon and "Black Friday Is On!", white body with "Up to 40% off NotificationX Pro. Unlock real-time sales and conversion notifications that turn visitors into customers.", a blue **View Deals** button on the left and a blue **Dismiss** link with a circled × on the right.
Fails if: it points at another menu item, the button and Dismiss are stacked or swapped, or there is a script error in the Console.

**T10. NotificationX pages.** Open **NotificationX → Add New**, **Settings** and **Analytics**.
Expected: the pointer shows on each, level with the **NotificationX** menu item (not the submenu), and the page under it still works.

**T11. Other screens.** Open the **Dashboard**, **Plugins**, **Posts**, **Users** and **Settings → General**.
Expected: no pointer. The Dashboard shows only the offer notice.

**T12. Scrolling.** On a NotificationX page, make the window short enough to scroll and scroll to the bottom and back.
Expected: the pointer stays beside the **NotificationX** menu item the whole time.
Fails if: it scrolls away with the page or jumps.

**T13. Folded menu and narrow window.** Click **Collapse Menu**, then expand it. Then narrow the window below 783px, open the menu with the admin bar button, and close it.
Expected: the pointer follows the menu item when the menu is folded and expanded. Below 783px it is hidden while the menu is closed and shows beside the item while the menu is open. No pointer floats over the page on its own.

**T14. View Deals.** Click **View Deals**.
Expected: `https://notificationx.com/bfcm2026/admin-notice` opens in a new tab. The pointer is still there in the first tab and after a reload.

**T15. Dismiss.** Click **Dismiss**.
Expected: the pointer closes. Reload, and open a NotificationX page: it stays gone for administrator A. Log in as administrator B: B still sees it. Dismissing as B does not bring it back for A.

**T16. Admin colour scheme.** Restore the pointer for A. In **Users → Profile** choose the **Sunrise** (or any other) admin colour scheme and open **NotificationX → All NotificationX**.
Expected: the pointer keeps its blue header, blue button and blue Dismiss on white. Set the scheme back to Default.

**T17. Editor.** Log in as the editor and open a NotificationX page.
Expected: no pointer.

**T18. With Pro.** As administrator B (who has not dismissed it), activate NotificationX Pro and open the Dashboard and a NotificationX page.
Expected: no pointer. Deactivate Pro: it is back.

## Part 3: dates

**T19. Outside the campaign.** Skip this part if the site's clock cannot be changed. Set the server date to 14 November 2026 23:50 UTC and open the Dashboard and **NotificationX → All NotificationX** as administrator B, then to 15 December 2026 00:10 UTC.
Expected: on both dates neither the notice nor the pointer shows. On 15 November 00:10 UTC and 14 December 23:50 UTC both show.

## Part 4: other WPDeveloper plugins

**T20. Another plugin's pointer first.** With the pointer showing for administrator B, activate **Essential Blocks** (free, without its Pro) and open **NotificationX → All NotificationX**.
Expected: at most one WPDeveloper pointer shows. If Essential Blocks has a pointer of its own due, it shows first and the NotificationX pointer waits; after it is dismissed, a reload shows the NotificationX pointer. If Essential Blocks has none due, the NotificationX pointer shows as before.
Fails if: two pointers show at once, or the NotificationX pointer never comes back.

**T21. Another plugin's notice first.** Activate **EmbedPress** and open the Dashboard.
Expected: one WPDeveloper notice shows. It may be EmbedPress's own; the NotificationX offer notice then shows once that one is dismissed and the cooldown is cleared (the `wp transient delete` command above). The pointer is not affected.

## Part 5: regression

**T22. NotificationX admin.** With the pointer showing, create a notification in the builder, save it, edit it and open **Settings**.
Expected: everything works as before; the pointer does not block the builder's buttons after it is dismissed, and there is no script error.

**T23. Front end.** Open the site's front end logged out.
Expected: no change. No `offer-pointer.js` request and no pointer.

## Reporting

Report the result of each case by id (`T1 pass`, `T9 fail: …`) with the WordPress version, the browser and a screenshot for any failure.
