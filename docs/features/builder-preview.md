# Builder Preview: another page, a new tab, add-on controls

**Available from Free 3.3.9.**

The builder's **Preview** button opens the same popup as before: the notification, with the unsaved builder settings, in a frame showing the site's default page for that source, with the desktop / tablet / phone buttons and the close button. On the right of the popup's top bar there are now two controls:

- **Page** ("Page: Home ▾"): shows the preview on another page of the site.
- **Open in new tab**: the same preview in a real browser tab.

Add-ons can put more controls there (NotificationX Pro adds **Share**).

## Layout

The controls are absolutely positioned on the right of the top bar ([_preview-toolbar.scss](../../nxdev/notificationx/scss/_preview-toolbar.scss)). The device buttons stay centred and the close button keeps its size and place; the controls end at least 16px before it. Three sizes, from the popup's width ([fields/preview/size.ts](../../nxdev/notificationx/fields/preview/size.ts)):

| Size | When | Page button | Others |
| --- | --- | --- | --- |
| `wide` | popup 1000px or wider (desktop frame, wide window) | "Page: *name* ▾", long names cut with an ellipsis | icons, add-on labels |
| `narrow` | 720–999px (an 820px window, the tablet frame) | "*name* ▾" | icons |
| `compact` | narrower (the phone frame) | page icon | icons |

The styles are in their own partial, imported only by the admin stylesheet: `_modal.scss` is also part of the public `gdpr-modal.css`.

## Page picker

- Opens a panel under the button: a search box ("Search pages, posts, products") and the results grouped by post type. With no search: the front page first, then the most recently changed pages, posts and products.
- Data: `GET notificationx/v1/preview/pages` ([REST endpoints](../api/rest-endpoints.md)), `edit_notificationx`. Drafts, scheduled, pending and private posts are listed only when the user can read each one, with a badge (Draft, Scheduled, Pending, Private, Password); a draft opens through its preview link.
- Choosing a page posts **the same request** into the frame at that page: the base64 builder values and the per-user `nx-preview-nonce`. So the preview gate ([In-builder preview](../architecture/frontend-runtime.md#in-builder-preview)) decides exactly as before, for any page.
- **Back to default** returns to the default page, including per-source defaults (Pro's newest product for inline sources).
- The chosen page stays while the builder is open, also after closing and reopening the popup.
- Escape closes the panel, not the popup; so does a click outside it or into the frame.

## Open in new tab

Posts the same request with `target="_blank"` and `rel="noopener"` to the page the preview shows. The tab is the real browser window, so it has no device buttons. Reloading it makes the browser ask to send the form again (standard for a POST).

## Add-on controls

JS filter `nx_preview_toolbar_items`: see [Hooks & Filters](../api/hooks-filters.md#in-builder-preview). Nothing renders when no add-on uses it.

## Settings from the server

PHP filter `nx_preview_trusted_settings` lets an add-on render a preview from settings it stored itself, when there is no authorised preview POST (Pro's share links). The POST gate is unchanged: see [In-builder preview](../architecture/frontend-runtime.md#in-builder-preview).

## Analytics

A preview is never counted: the preview embeds `enable_analytics => false`, and the runtime skips the click request for notifications with `is_preview`. Before this, a click on a linked notification in a preview could add a stats row when Enable Analytics was on.

## Tests

- PHPUnit: [test-preview-pages.php](../../tests/test-preview-pages.php), [test-preview-trusted-settings.php](../../tests/test-preview-trusted-settings.php), [test-preview-analytics.php](../../tests/test-preview-analytics.php); the gate's own [test-security-preview-gate.php](../../tests/test-security-preview-gate.php) is unchanged.
- Jest: [tests/js/admin/preview-modal.test.js](../../tests/js/admin/preview-modal.test.js), [tests/js/frontend/Analytics.test.js](../../tests/js/frontend/Analytics.test.js).
- e2e: [tests/e2e/builder-preview-pages.js](../../tests/e2e/builder-preview-pages.js) (layout at 1440 / 820px and the phone frame, picker, new tab, the gate on another page).
- Manual: [QA guide](../qa-guide/builder-preview-pages.md).
