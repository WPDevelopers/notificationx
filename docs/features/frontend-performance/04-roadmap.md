# Frontend Performance: Roadmap (B2, B3)

> **Status:** planned; nothing in this doc is implemented yet.

Run the E2E comparison ([tests/e2e/](../../../tests/e2e/)) before and after every step. For CSS work the pixel diff is the main safety net.

## B2: CSS (minor release)

`assets/public/css/frontend.css` is 926 KB (55 KB gzip) and render-blocking on every page with a notification.

### B2.1 Extract the GDPR modal styles; stop importing admin `_modal.scss`

- [frontend/scss/theme.scss](../../../nxdev/notificationx/frontend/scss/theme.scss) line 13 imports the whole admin [scss/_modal.scss](../../../nxdev/notificationx/scss/_modal.scss). Its nested comma selectors expand into single selectors up to 26 KB, and ~450 KB of the CSS carries admin `wprf-*` selectors.
- The frontend needs only the `.nx-gdpr-modal-wrapper` rules: the shared selector block around `_modal.scss:332`, and the generic `.ReactModal__Overlay` block around line 1414.
- Copy those rules as flat selectors into `frontend/scss/_themes/_gdpr-modal.scss` and remove the import. The admin keeps importing `_modal.scss` from [scss/index.scss](../../../nxdev/notificationx/scss/index.scss).
- **Deleting the import without extracting the rules breaks the cookie preferences modal.**
- Verify: the `gdpr-modal` E2E scenario (0 pixels different), every modal tab and accordion, the mobile width, and the builder preview.

### B2.2 FontAwesome: load conditionally, don't delete it

- `font-family: "FontAwesome"` draws icons in `_theme-seven.scss`, `_theme-eight.scss`, `_customizable.scss` and `_res-woocommerce-theme.scss`. It's imported from cdnjs in `_common.scss` and `_customizable.scss`.
- Remove the `@import`s. Enqueue FontAwesome as its own stylesheet only when an active notification uses one of those themes; PHP knows through `get_notifications_ids( true )`.
- Later: replace those glyphs with inline SVG.

### B2.3 Open Sans and DM Sans: self-host

- Open Sans is `@import`ed from Google Fonts in `_container.scss` and `_notification-bar-common.scss`. Pro's `_announcements.scss` imports DM Sans the same way.
- Serve the same files from the plugin with `font-display: swap`. There's no visual change, and it stops sending visitor IPs to Google, which German courts have ruled a GDPR violation.

### B2.4 Pressbar CLS: measure first

- The bar sets `document.body.style.paddingTop` after it renders ([Pressbar.tsx](../../../nxdev/notificationx/frontend/core/Pressbar.tsx)).
- Get GTmetrix's CLS element list before changing anything. Bar layout has many theme variants.

## B3: consent loader split (major release)

The goal: on pages with a GDPR notice, only a small consent script loads eagerly; the notification engine loads later.

1. **New `gdpr` webpack entry** (`gdpr.js` plus `gdpr.css`), built from the existing components (`core/GDPR.tsx`, `gdpr/utils/*`) so behaviour stays the same. Alias `react`/`react-dom` to `preact/compat` for this entry only; `react-dom` alone is 119 KB. Target is at most 30 KB gzip, per xSpeed's request.
2. **Inline the GDPR config.** Render the GDPR notice's settings and content into the footer data so the banner doesn't wait for the POST to `/notice/`.
3. **Remove GDPR rendering from `frontend.js`.** Enqueue `gdpr.js` only when `gdpr` is non-empty.
4. **Then defer `/notice/`** for other notification types to `load` plus `requestIdleCallback`. Their default 5 s `delay_before` hides it.
5. **A stable target for optimizers:** a fixed handle (`notificationx-consent`) and attribute (`data-nx-consent="1"`). Agree on these with xSpeed and update [01-optimizer-compatibility.md](01-optimizer-compatibility.md).

Must keep working:

- Pro show and hide animations (`is_pro`)
- Builder preview (`nxPreview`)
- WP Consent API sync (`handleConsentAPI`)
- Elementor and Gutenberg GDPR designs
- Cookie-scanner lists and `loadScripts`
- The `nx_cookie_manager` cookie format, so existing visitors keep their consent
- `delete-cookies`
- Cross-domain snippets that load `frontend.js` by URL
- Custom CSS from `generate_custom_css()`

## Later

- Replace `moment` (172 KB) with dayjs. Its API is similar (`fromNow`), and its locale strings are ported from moment. Test non-English locales, because "time ago" wording may change.
- Notification images: always set `width`/`height`, and consider a static fallback when wordpress.org only offers an animated GIF icon.
