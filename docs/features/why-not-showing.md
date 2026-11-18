# Why isn't it showing?

> For a notification that doesn't appear on the site, lists the reasons, each with a fix and, where possible, a link to the setting that fixes it.
>
> Available from Free 3.4.1.

## Where

**All NotificationX → a row's ⋯ menu → "Why isn't it showing?"**, right after Edit. It works for every notification, whether on or off. It opens a modal. No other screen changes.

The modal shows:
- **A verdict:** "N things stop it from showing", "Nothing stops it, but it shows only for some visitors, pages or times", or "Nothing stops it from showing".
- **The reasons in groups:** Stops it from showing (`blocked`), Shows only sometimes or somewhere (`limited`), Good to know (`note`), and Checked and fine (`ok`, collapsed).
- **A fix link on each reason that has one.** It opens the builder on the tab with the setting (`admin.php?page=nx-edit&id=<id>&tab=<tab id>`), or opens Plugins, Settings → Modules, the disabled list, or API Integrations (Pro). The edit screen reads `tab` only when it's one of `source_tab`, `design_tab`, `content_tab`, `display_tab`, `customize_tab`. Without it, the edit screen behaves as before.
- **"You closed it in this browser":** the modal reads this browser's close markers for that notification: the cookies `notificationx_<id>[-<countdown_rand>]`, `nx_bar_<id>` and `nx_exit_intent_<id>`, the localStorage key, and the sessionStorage keys. These are the keys the front end writes when the notification is closed. They are only read, never changed or sent.

It's admin-only (`edit_notificationx`) and read-only. No visitor data is read. Reading a notification fills NotificationX's builder-fields transient, as the list itself does.

## The checks (`includes/Core/WhyNotShowing.php`)

| Key | Level | When |
|---|---|---|
| `enabled` | blocked | Switched off. Links to the Disabled list. |
| `pro` | blocked | One of: a Pro source without Pro; a type or theme only a current Pro renders (`is_type_unrenderable` / `is_theme_unrenderable`); a source this site doesn't register (from Pro, a newer Pro, or a feature that's off). |
| `module` | blocked | The source's module is off (Settings → General → Modules). |
| `plugin` | blocked | The source's plugin isn't active (`Extension::class_exists()`), e.g. WooCommerce. |
| `schedule` | blocked / limited | One of: saved with a time in the future; a Notification Bar outside its daily, weekly or custom schedule (the same check as `NotificationBar::show_on_exclude`, without its cookie part); a bar with a schedule (limited). |
| `countdown` | blocked | A bar whose countdown ended (not evergreen) and that closes when it ends. |
| `data` | blocked / ok | Through the same pipeline as the public `/notice` request (`FrontEnd::get_notifications_data`). The results are: no entries at all; entries, but none since "Display From The Last", with the newest date; entries that its settings filter out; or "N entries to show". It's skipped for sources without entries (`nx_why_not_showing_without_entries`) and while `pro`, `module` or `plugin` blocks. While switched off, it shows only the count. |
| `pages` | blocked / limited | Show On Selected with no location (blocked), the selected or hidden locations, or shortcode only. |
| `audience` / `roles` / `countries` | limited | Display For logged in or out, the target user roles (including guests), country targeting (visitors whose country can't be found still see it). |
| `devices` | blocked / limited | Hidden on some devices, or on all three. It follows `FrontEnd::get_notifications()`: an empty `hide_on_<device>` means hidden there. |
| `seen` | limited / note | The reappearance rule: hidden for N days after closing, until the next visit, or only on that page. Popups stay closed for the browser session. Exit Intent waits N days. |
| `timing` / `loop` | limited / note | A delay before the first notification of more than 30 s; Loop off. |
| `slot` | note | Other enabled notifications at the same position, or in the global queue, which take turns. Each is named, or numbered when untitled. |
| `cache` | note | A page cache (`WP_CACHE` or `advanced-cache.php`). |

Add-ons add their own reasons on `nx_why_not_showing_reasons` (see [../api/hooks-filters.md](../api/hooks-filters.md)). Pro adds:
- Flashing Tab: it shows only in the tab's title;
- Growth Alert: no location picked, or where to look;
- API sources without their key in API Integrations (only whether one is set, never the key);
- Zapier and IFTTT: data arrives only when the Zap or applet sends it.

## REST

`GET notificationx/v1/why-not-showing/<id>`: see [../api/rest-endpoints.md](../api/rest-endpoints.md).

## Files

| Purpose | File |
|---|---|
| Checks and route | `includes/Core/WhyNotShowing.php` (booted from `includes/Core/REST.php`) |
| Modal | `nxdev/notificationx/admin/WhyNotShowing/WhyNotShowingModal.tsx`, `logic.ts`, `nxdev/notificationx/scss/nx_new/_why_not_showing.scss` |
| Menu item | `nxdev/notificationx/admin/SingleNotificationAction.tsx` |
| Tab link | `nxdev/notificationx/admin/AddEditNx/EditNotification.tsx` |
| Tests | `tests/test-why-not-showing.php`, `tests/js/admin/why-not-showing.test.js`; manual: [../qa-guide/why-not-showing.md](../qa-guide/why-not-showing.md) |

## Not in v1

- Testing a specific page URL against Show On, which would need a simulated front-end request.
- One-click fixes, such as switching it on or clearing the close cookie. The checks stay read-only.
- Anything per visitor.
- Google Analytics' connection (OAuth).
- Pro's A/B testing and Order Soon reasons: those branches can add them through the filter once they merge.
