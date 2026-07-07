# Admin SPA

The React + TypeScript admin application.

## Stack & entry point

The admin app is React + TypeScript, sourced from [../../nxdev/](../../nxdev/) with entry [../../nxdev/index.tsx](../../nxdev/index.tsx). It builds via [../../webpack.config.js](../../webpack.config.js) (`@wordpress/scripts`), output as `admin/js/admin.js` + `admin/css/admin.css` into `assets/` (production) or `nxbuild/` (dev).

`index.tsx` registers a few `@wordpress/hooks` filters (`wprf_tab_content`, `nxpro_preloader`, `custom_field`) so the Pro plugin and QuickBuilder can extend the UI, then `ReactDOM.render(<NotificationX />)` into the `#notificationx` mount point. That mount div is printed by the server-rendered admin view. Key libraries: `react-router` (v5-style), the `quickbuilder` form engine from WPDevelopers, `@wordpress/components`, and `@wordpress/hooks`.

The app is enqueued by `PostType::admin_enqueue_scripts()` ([../../includes/Core/PostType.php](../../includes/Core/PostType.php)), but only on NotificationX admin screens (`toplevel_page_nx-admin`, `nx-edit`, `nx-settings`, `nx-analytics`, `nx-dashboard`, `nx-builder`, `nx-feedback-entries`, `nx-setup-wizard`). It localizes the whole builder field schema as `notificationxTabs` (from `GlobalFields::tabs()`, normalized by `NotificationX::normalize()`).

## QuickBuilder form engine

The notification builder UI is not hand-coded per Type — it is generated from a field schema. Each Type and Extension contributes fields (via `nx_content_fields`, `nx_design_tab_fields`, `nx_customize_fields`, gated with `Rules::is('source', …)`), which `GlobalFields` ([../../includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php)) assembles into a tabbed schema. That schema is cached in the `nx_builder_fields` transient (cleared by `Upgrader::clear_transient()` on version change) and handed to the front end via the `notificationxTabs` localize and the `GET /notificationx/v1/builder` endpoint.

On the client, the `quickbuilder` package — sourced from `github:WPDevelopers/quickbuilder#notificationx`, refreshed with `npm run up`, **not** bumped from npm — turns that schema into the live builder form (fields, conditional visibility, repeaters, the design/preview tabs). The server side of the engine is `Core\QuickBuild`.

## Routing & views

Client routing lives in [../../nxdev/notificationx/Route.tsx](../../nxdev/notificationx/Route.tsx). Rather than URL paths, it matches against the WordPress admin `?page=` (and `id`) query params using `react-router`'s `matchPath` / `useHistory`, and swaps in the matching screen component. The route → component map:

| `page` | Component |
| --- | --- |
| `nx-dashboard` | `Dashboard` |
| `nx-admin` | `Admin` (list table) |
| `nx-edit` | `AddNewNotification` |
| `nx-edit/:edit` | `EditNotification` |
| `nx-settings` | `Settings` |
| `nx-analytics` | `Analytics` |
| `nx-feedback-entries` | `FeedbackEntries` |
| `nx-builder` | `QuickBuild` |
| `nx-setup-wizard` | `SetupWizard` |

It also intercepts clicks on the WordPress submenu (`#toplevel_page_nx-admin a`) so navigation happens client-side without full page reloads. The thin server-rendered PHP views that host the mount point live in [../../includes/Admin/views/](../../includes/Admin/views/) (`main.views.php`, `analytics.views.php`), registered by `Admin` and `PostType::menu()`.

## Build

- `npm run admin` / `npm run admin-watch` — build/watch just the admin app ([../../webpack.config.js](../../webpack.config.js)).
- `npm run start` — watch admin **and** frontend in parallel.
- `npm run build` — production admin + frontend.
- `npm run up` — reinstall the `quickbuilder` dependency from its GitHub `notificationx` branch.

See [../development/building-assets.md](../development/building-assets.md) for the full build matrix.

## Source
- [../../nxdev/index.tsx](../../nxdev/index.tsx)
- [../../nxdev/notificationx/Route.tsx](../../nxdev/notificationx/Route.tsx)
- [../../includes/Extensions/GlobalFields.php](../../includes/Extensions/GlobalFields.php)
- [../../includes/Admin/](../../includes/Admin/)
