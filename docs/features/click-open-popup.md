# Click-open popup

> An Announcement popup can open when a visitor clicks a chosen button or link, as a "When to Open" option next to its existing triggers (the delay, and Convert to Exit Intent).
>
> Available from Free 3.4.1.

## The setting

**Customize → When to Open** (Announcement only, just above Exit Intent Popup):

| Setting | Values |
|---|---|
| Open (`popup_open_on`) | **Automatically** (`auto`, default: after "Delay Before First Notification", or on exit intent), **Only when a visitor clicks** (`click`), **Automatically and when a visitor clicks** (`both`) |
| Click Target (`popup_click_selector`, while click or both) | CSS classes or IDs, separated by commas (`.open-offer, #promo-button`); a bare name is a class. Saved as classes and IDs only (`PopupNotification::click_selectors()`, at most 10). A link to `#nx-popup-<id>` always opens it too. |

Popups saved before the setting open automatically, as before.

**Why only the Announcement popup:**
- Exit Intent popups exist for the exit trigger.
- An Announcement that should open on exit and on click can use Convert to Exit Intent with "both".
- Notification Bars, GDPR and the corner notifications aren't popups.
- NotificationX Pro needs no change.

## How it works (`nxdev/notificationx/frontend/core/clickOpen.ts`)

**Listening**
- One delegated `click` and `keydown` listener on the document, so targets added to the page later work too.
- A click on a target, or inside one, opens the popup at once (`preventDefault`, so a `#nx-popup-<id>` link doesn't jump).
- Clicks inside a NotificationX popup never count, and a popup never opens twice.

**A click always opens it,** even if the visitor closed it before. The popup stays in play:
- on the server: `FrontEnd::get_notifications_ids()` keeps it despite the "don't show again" cookie (`PopupNotification::opens_on_click()`);
- in the browser: the response normaliser keeps it despite the session flag or cookie.

The opened popup is marked `nx_click_open`, so it skips the session check, the delay, the exit-intent wait and the container's reappearance storage check. Automatic opening still checks all of them.

**Show On and the audience still apply:** only popups that `/notice` sent for the page listen.

**Keyboard**
- Enter and Space on a non-button target open it; links and buttons click by themselves.
- Esc closes it.
- Focus moves into the popup (`role="dialog"`, `aria-modal`) and goes back to the clicked element on close.

**Other behaviour**
- "Only when a visitor clicks" stops the automatic opening; the builder Preview still shows the popup.
- Automatic popups are unchanged: no dialog role, no Esc, the same delay and session rules.

## Files

| Purpose | File |
|---|---|
| Setting, cleaning, `opens_on_click()` | `includes/Extensions/PopupNotification/PopupNotification.php` |
| Kept despite the cookie | `includes/FrontEnd/FrontEnd.php` (`get_notifications_ids`) |
| Listener, targets, focus | `nxdev/notificationx/frontend/core/clickOpen.ts` |
| Wiring | `useNotificationX.ts`, `Popup.tsx`, `NotificationContainer.tsx`, `utils.ts` (`normalizeResponse`) |
| Tests | `tests/test-popup-click-open.php`, `tests/js/frontend/click-open.test.js`; manual: [../qa-guide/click-open-popup.md](../qa-guide/click-open-popup.md) |

## Not in v1

- A focus trap inside the popup.
- Click triggers for Exit Intent popups and the corner notifications.
