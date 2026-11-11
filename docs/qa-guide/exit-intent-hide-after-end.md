# Exit Intent: Stop Showing After End Date

Free issue [#211](https://github.com/WPDevelopers/notificationx/issues/211), Pro issue [#65](https://github.com/WPDevelopers/notificationx-pro/issues/65). Test the free and Pro branches `fix/exit-intent-hide-after-end` together, except where a case says otherwise.

## What changed

- **New settings.** Customize > *Exit Intent Settings* has two new fields:
  - **Stop Showing After End Date**: a toggle, off by default.
  - **End Date & Time**: visible when the toggle is on. It is the same value as the "Sale End Date & Time" field of Theme Five and Theme Six, so a change in one place shows in the other.
- **Popup after the end date.** With the toggle on and the end date passed, the popup does not open, for every theme and for Elementor designs. A popup that is open at the end time closes. This close is not a dismissal: no "closed" cookie is written.
- **Default end date.** The date field now defaults to 7 days from now. Before, an empty date was saved as the time of saving, so the Theme Five / Six timer showed `00` right away.
- **Pro countdown.** The Theme Five / Six timer uses the end time computed by the server (`exit_intent_end_ts`), so every visitor's timer ends at the same moment, whatever their timezone.

Developer notes: [features/exit-intent/00-overview.md → End date](../features/exit-intent/00-overview.md#end-date-stop-showing-after-end-date).

## Known limits

- **Campaigns saved before this change.** A Theme Five / Six campaign saved without a picked date stored the time of saving. It keeps that date, so its timer still shows `00`. Pick a new date and save.
- **Elementor countdown widget.** The `nx-countdown-timer` widget in an Elementor design has its own date. It is not linked to the campaign end date. Set both to the same date.
- **New default.** The 7-day default comes from the builder field cache (`nx_builder_fields` transient, one day), so a new campaign gets a date 6–7 days ahead.

## Automated coverage

| Case | PHPUnit | Jest | Browser (e2e) |
| --- | --- | --- | --- |
| T1 toggle off | `is_past_end()` | helper | free `exit-intent-end.js` |
| T2 past end date | `is_past_end()`, frontend list, REST payload | helper | free `exit-intent-end.js` |
| T3 future end date | frontend list, REST payload | helper | free `exit-intent-end.js` |
| T4 open at end time | – | popup closes, no dismissal, long timers | free `exit-intent-end.js`; Pro `exit-intent-countdown.js` (Theme Six) |
| T5 cached page | – | – | free `exit-intent-end.js` |
| T6 Elementor design | – | – | free `exit-intent-end.js`, when Elementor is active |
| T7 7-day default | – | – | free `exit-intent-end.js` (builder save); Pro `exit-intent-countdown.js` (timer) |
| T8 one value | – | – | free `exit-intent-end.js` |
| T9 timezones | `get_end_timestamp()` | – | Pro `exit-intent-countdown.js` |
| T10 older free | – | – | manual only |
| T11 preview | – | – | free `exit-intent-end.js` |

- PHPUnit: [tests/test-exit-intent-hide-after-end.php](../../tests/test-exit-intent-hide-after-end.php).
- Jest: [tests/js/frontend/exit-intent-end.test.js](../../tests/js/frontend/exit-intent-end.test.js) (`npm run test:js`).
- Browser: [tests/e2e/exit-intent-end.js](../../tests/e2e/exit-intent-end.js) and Pro `tests/e2e/exit-intent-countdown.js`. How to run them: [tests/e2e/README.md](../../tests/e2e/README.md#exit-intent-end-date).

## Setup

- Install free and Pro from the branches above, with the frontend assets built.
- Enable the Exit Intent module.
- Use a desktop browser. Exit Intent is off on touch devices by default.
- To trigger the popup, move the mouse out of the top edge of the page.
- Between cases, clear the site cookies and session storage, or use a new private window. A dismissed popup stays hidden for "Do Not Show Again For" days.

## Cases

**T1. Toggle off (default): no change.**
1. Create an Exit Intent campaign with Theme Three. Leave "Stop Showing After End Date" off.
2. Trigger the popup.

Expected: the popup opens as before. Fail if it does not open.

**T2. Toggle on, end date in the past.**
1. On the T1 campaign, turn on "Stop Showing After End Date".
2. Set End Date & Time to a time 1 hour in the past. Save.
3. Open the site in a new private window and trigger the popup.

Expected: the popup does not open. Fail if it opens.

**T3. Toggle on, end date in the future.**
1. Set the end date to tomorrow. Save.
2. Trigger the popup.

Expected: the popup opens. Fail if it does not open.

**T4. Popup open at the end time.**
1. Set the end date to 2 minutes from now. Save.
2. Trigger the popup and leave it open.

Expected: the popup closes when the end time arrives. Reload the page and trigger again: the popup does not open. Fail if it stays open, or opens again after the end time.

**T5. Page loaded before the end time, exit after it.**
1. Set the end date to 2 minutes from now. Save.
2. Load the page, but do not trigger the popup.
3. Wait until the end time has passed, then trigger the popup.

Expected: the popup does not open. This is the page-cache case. Fail if it opens.

**T6. Elementor design.**
1. Create an Exit Intent campaign with an Elementor design that has a countdown widget.
2. In Customize > Exit Intent Settings, turn on the toggle and set the end date to the same date as the widget.
3. Repeat T2 and T4.

Expected: same results as T2 and T4. The setting is visible even though the Content tab is hidden for Elementor designs. Fail if the setting is missing or the popup ignores it. The countdown widget itself follows its own date and expiry action; see Known limits.

**T7. Theme Five / Six: new campaign default (Pro).**
1. Create a new campaign with Theme Five. Do not touch the "Sale End Date & Time" field. Save.
2. Trigger the popup.

Expected: the timer counts down from about 7 days. Fail if it shows `00 : 00 : 00 : 00`. Repeat with Theme Six.

**T8. Theme Five / Six: one value in two places (Pro).**
1. On a Theme Five campaign, change "Sale End Date & Time" in the Content tab.
2. Turn on "Stop Showing After End Date" in Customize.

Expected: End Date & Time in Customize shows the same date and time. Change it there, then go back to the Content tab: the Content tab shows the new value. Fail if the values differ.

**T9. Theme Five / Six: same end moment in every timezone (Pro).**
1. Set an end date on a Theme Five campaign.
2. Open the site in two browsers, or in one browser with a different system timezone (for example UTC+6 and UTC-5). Trigger the popup in both.

Expected: both timers show the same time left, to within a second. Fail if they differ by whole hours.

**T10. Theme Five / Six with an older free (Pro).**
1. Install the current free release (without this fix) and the Pro branch.
2. Repeat T7.

Expected: the timer still works. It falls back to reading the date string. The stop setting does not exist with this free version. Fail on a JavaScript error, or a blank or broken timer.

**T11. Admin preview.**
1. In the builder, turn on the toggle and set a past end date.
2. Open the preview.

Expected: the preview still shows the popup. The end date applies only on the site. Fail if the preview is empty.
