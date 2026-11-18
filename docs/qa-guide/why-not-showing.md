# QA: Why isn't it showing?

**Version:** Free x.x.x and Pro x.x.x (to be set). Free branch `why-not-showing`; Pro branch `why-not-showing` for T11 and T12.

**What changed:** All NotificationX → a row's ⋯ menu has **"Why isn't it showing?"**. It opens a modal listing the reasons the notification may not appear, each with a fix link. Feature doc: [../features/why-not-showing.md](../features/why-not-showing.md).

**Must not change:** the rest of the row menu, the list, the builder (except that it opens on the tab a fix link names), and the front end.

## Setup

- An admin. A second browser or private window that is **not** logged in.
- A few notifications: one off, one Sales notification with orders older than a week, one with no data.

## Cases

**T1 — The menu item.** Open a row's ⋯ menu.
Expected: "Why isn't it showing?" (question-mark icon) right after Edit; the other items as before.

**T2 — Switched off.** Open it on a disabled notification.
Expected: "N things stop it from showing"; first "It's switched off" with "Open disabled notifications →", which opens the Disabled list.

**T3 — No data yet.** A notification with nothing saved, such as a new Comments notification on a site without comments.
Expected: "There's no data to show yet", with a link to the Source tab.

**T4 — The chosen period.** A Sales notification whose orders are older than "Display From The Last" (set it to 1 day).
Expected: "Nothing in the chosen period", with the entry count, the newest date and the start of the period; "Change 'Display From The Last' →" opens the builder on Customize.

**T5 — Show On.** Show On Selected, with no location picked.
Expected: blocked, "…but none are selected"; "Change 'Show On' →" opens the builder on the **Display** tab. With a location picked: "It shows only on selected pages" and the location names.

**T6 — Audience and devices.** Display For Logged In User, Target Audience Editor, Mobile off under Notification Visibility, and Notification Reappearance "Don't show the Notification again" for 3 days.
Expected: "Nothing stops it, but it shows only for some visitors…". Each setting is listed with its link and the details: "Target audience: Editor.", "Not on mobile.", "…for 3 days."

**T7 — Closed in this browser.** Close that notification on the site in the same browser as the admin (or set the cookie `notificationx_<id>=true`), then open the modal.
Expected: "You closed it in this browser" at the top of the middle group. It's absent in a fresh browser.

**T8 — Notification Bar.** A bar with a custom schedule that has ended, and a countdown in the past with "close after expire".
Expected: "It's outside its schedule right now" (with the dates) and "Its countdown has ended", both blocked; no data check.

**T9 — A source this site doesn't have.** A notification whose source isn't registered, e.g. one made on a Pro branch that isn't installed here.
Expected: "Its source isn't available on this site", with the source id; no "no data" line.

**T10 — Healthy.** An enabled notification that shows.
Expected: no blocked reasons. "Good to know" names the others at the same position (untitled ones by number) and says a page cache is on when there is one. "Checked and fine" is collapsed and opens.

**T11 — Pro sources.** With Pro:
- a MailChimp notification without the API key gives "MailChimp isn't connected", linked to API Integrations;
- a Flashing Tab gives "It shows only in the browser tab's title";
- a Growth Alert with no location gives blocked;
- a Zapier notification without data says the data comes only when the Zap sends it.

**T12 — Without Pro.** Deactivate Pro, then open the modal on a Pro source.
Expected: "It needs NotificationX Pro", linked to Plugins.

**T13 — Phones and keyboard.** At 390 px the modal opens as a bottom sheet without scrolling sideways. Escape and × close it; focus starts inside it.

**T14 — Admin only.** In the logged-out browser, open `/wp-json/notificationx/v1/why-not-showing/<id>`.
Expected: 401. As a subscriber: 403. The admin's answer carries `Cache-Control: no-store`.

**T15 — Nothing changes.** Open the modal on several notifications, then compare the notifications, their entries and the site's options (apart from transients) with before.
Expected: unchanged.
