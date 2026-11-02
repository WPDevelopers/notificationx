# Frontend Runtime

How notifications are rendered on the public site.

## Enqueue & render

`FrontEnd` ([../../includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php)) owns the public side. On a non-admin request it hooks `wp_enqueue_scripts → enqueue_scripts()`:

1. It registers the runtime bundle `notificationx-public` (`public/js/frontend.js`) and stylesheet (`public/css/frontend.css`), resolved through `Helper::file()`. Asset resolution is version-aware: the production build outputs to `assets/` and the dev build to `nxbuild/`, and `Helper::file()` prefers `NOTIFICATIONX_DEV_ASSETS` (`nxbuild/`) when `NX_DEBUG` is defined and the file exists there, otherwise falls back to `NOTIFICATIONX_ASSETS` (`assets/`).
2. It computes which notifications apply to the current request via `get_notifications_ids()` and stores the result (bucketed as `global`, `active`, `pressbar`, `gdpr`, `popup`, `exit_intent`, … plus a `total`) so the runtime knows what to ask for.
3. Only if `total > 0` does it actually enqueue the script/style (plus `dashicons`, an optional moment locale bundle, and inline custom CSS built by `generate_custom_css()`). The bucketed IDs are localized to the page as `window.notificationXArr` / `notificationxPublic`.
4. It adds a `has-notificationx` body class and prints footer scripts.

The runtime is a filter-driven data pipeline: `nx_before_enqueue_scripts` can short-circuit rendering (e.g. in the widgets screen or during preview), and `nx_frontend_localize_data`, `nx_fallback_data`, `nx_filtered_data`, and `nx_filtered_post` shape the payload.

## The popup/bar/exit-intent runtime

The runtime is a React app built from [../../nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/) via [../../webpack.frontend.config.js](../../webpack.frontend.config.js) (entry `frontend`, plus `crossSite` and `gdpr-modal`; the `flashing-tab` bundle moved to NotificationX Pro in free 3.3.4). Its entry [../../nxdev/notificationx/frontend/index.tsx](../../nxdev/notificationx/frontend/index.tsx) reads the localized `notificationX` config, sets up i18n/moment locale, appends a container div to `document.body`, and renders `<NotificationXFrontEnd config={…} />` from `frontend/core`.

From there:

- `useNotificationX` POSTs the bucketed IDs to `POST /notificationx/v1/notice` (handled by `REST::notice()` → `FrontEnd::get_notifications_data()`), receives the resolved notification data, normalizes it (`normalizeResponse` / `normalize` / `normalizePressBar` in `frontend/core/utils.ts`), and holds it in a `useReducer` store.
- `dispatchNotification` pushes `{ id, data, config }` entries into the store at the right moment (interval-timed for feed-style notices, event-triggered for exit-intent on `mouseleave`, immediate for popups).
- `NotificationContainer` routes each notice to the correct component by `config.type` / `config.position`.

The full data-shape contract and the seven touch-points involved in adding a Type are documented in [../development/adding-a-notification-type.md](../development/adding-a-notification-type.md).

## Import boundary

`frontend.js` loads on every page that shows a notification, and optimizers may load it before the page is interactive. Frontend code under `nxdev/notificationx/frontend/` may import only from `frontend/`, `shared/` and `icons/`. Code the admin and the frontend both need lives in the dependency-free [../../nxdev/notificationx/shared/helpers.ts](../../nxdev/notificationx/shared/helpers.ts), which the admin modules re-export.

Importing one helper from the admin `core/functions.ts` pulled ~900 KB of admin libraries into 3.3.1's bundle. `npm run test:js` (the import-boundary test) and `npm run check:bundle` now catch that. See [../features/frontend-performance/03-guardrails.md](../features/frontend-performance/03-guardrails.md).

## Extension points for add-ons

The runtime reads JavaScript filters from `window.wp.hooks`, so `notificationx-public` depends on `wp-hooks`. The filters let `notificationx-pro` supply template rows, theme parts, link buttons and layout lists for its own notifications. [index.tsx](../../nxdev/notificationx/frontend/index.tsx) also exposes the bundle's React copy as `window.nxFrontendRuntime`, so add-on components can use React hooks. All filters are called through [core/hooks.ts](../../nxdev/notificationx/frontend/core/hooks.ts), never through an import of `@wordpress/hooks`. The reason, and the full hook list, are in [../api/frontend-js-hooks.md](../api/frontend-js-hooks.md).

## In-builder preview

`Preview` ([../../includes/FrontEnd/Preview.php](../../includes/FrontEnd/Preview.php)) powers the live preview shown inside the admin builder and Elementor/Gutenberg editors. It hooks `nx_before_enqueue_scripts` to force a single synthetic notification (`total => 1`, `nxPreview => true`) so the same public runtime renders in an isolated preview context, hides the admin bar, and quiets Query Monitor during preview. This means the preview and the live site render through the *same* React runtime, not a separate mock.

The builder sends the notification's settings as base64 JSON in `$_POST['nx-preview']` to a front-end URL loaded in an iframe. Those settings are rendered server-side (the bar's `press_content` goes through `do_shortcode()`, its `gutenberg_id` through `do_blocks()`), so the payload is accepted only when `Preview::is_authorized_preview_request()` passes: a valid nonce for the `nx-preview` action in `$_POST['nx-preview-nonce']`, and the `edit_notificationx` capability.

- The builder gets the nonce as `notificationxTabs.preview_nonce` from `PostType::get_localize_scripts()`. Don't put it in the `GlobalFields::tabs()` config: that is cached for all users in the `nx_builder_fields` transient.
- Anything else that POSTs `nx-preview` (for example Pro's Build with AI preset preview) must send the same nonce.
- `get_settings()` checks the request itself, so the `nx_is_preview` filter can switch preview mode on (the Inline block does, for one shortcode call in a block template) without making the request payload readable.
- The checks run on `plugins_loaded` (priority 0), not in the constructor: the current user and `wp_verify_nonce()` don't exist while plugins are being included.
- Gutenberg bars render only from bar posts (`nx_bar_eb`, or `wp_block` for old bars) that are published without a password, unless the user can edit the post (`PressBar::can_render_block_bar()`). Elementor bars render only from published `nx_bar` posts.

Never read `$_POST['nx-preview']` anywhere else.

[../../tests/e2e/builder-preview.js](../../tests/e2e/builder-preview.js) checks the whole flow in a browser, and [../../tests/test-security-preview-gate.php](../../tests/test-security-preview-gate.php) covers each check.

## The dual-build desync trap

There are **two webpack builds** in this repo — admin ([../../webpack.config.js](../../webpack.config.js)) and frontend ([../../webpack.frontend.config.js](../../webpack.frontend.config.js)) — and **three runtime contexts**: the admin builder, the public popup runtime, and Gutenberg blocks ([../../webpack.blocks.config.js](../../webpack.blocks.config.js)).

Popup display logic is expressed in two places at once: the React components/SCSS under [../../nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/), and the corresponding PHP Type class under [../../includes/Types/](../../includes/Types/) that declares the field schema and template tokens. A design or field change made in only one of them will build cleanly but silently desync — the builder will offer a field the runtime ignores, or the runtime will expect data the Type never emits. When you touch popup display, expect to edit **both** the React side and the matching PHP Type (and rebuild the relevant bundle: `npm run frontend`, `npm run admin`, or `npm run bb`).

## Source
- [../../includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php)
- [../../includes/FrontEnd/Preview.php](../../includes/FrontEnd/Preview.php)
- [../../nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/)
