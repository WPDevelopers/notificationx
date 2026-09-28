# Frontend JS Hooks

JavaScript extension points in the frontend runtime (`notificationx-public`, built from [../../nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/)). `notificationx-pro` uses them to render its own notifications without code in the free plugin. PHP hooks are in [hooks-filters.md](hooks-filters.md).

Added in free 3.4.0 as step 4 of the free/Pro code separation (see [Why these hooks exist](#why-these-hooks-exist)).

## How the runtime reads filters

Filters live on the global WordPress registry, `window.wp.hooks`. Register them with `wp.hooks.addFilter()` from a script that loads on the same page.

- `FrontEnd::get_script_dependencies()` ([FrontEnd.php:85](../../includes/FrontEnd/FrontEnd.php#L85)) makes `notificationx-public` depend on `wp-hooks`, so the registry is always present on WordPress pages.
- The runtime calls filters through `nxApplyFilters()` / `nxApplyListFilter()` in [core/hooks.ts](../../nxdev/notificationx/frontend/core/hooks.ts). They read `window.wp.hooks` each time they run.
- When `window.wp.hooks` is missing, the helpers return the default value and the built-in code renders. This happens on non-WordPress sites: the Pro Cross Domain Notice snippet ([notificationx-pro `Admin/XSS.php`](../../../notificationx-pro/includes/Admin/XSS.php)) loads `frontend.js` there without `wp-hooks`, and the legacy `crossSite.js` runs there too.
- When a filter throws, the helpers log the error and return the default value. A broken add-on falls back to the built-in rendering instead of unmounting the notification tree.

> **Do not import `@wordpress/hooks` in the frontend runtime, and do not add it to `externals` in [webpack.frontend.config.js](../../webpack.frontend.config.js).** An import bundles a second, private registry, because the build runs with `--webpack-no-externals`, so add-on filters never reach it. An external breaks the Cross Domain Notice, because `frontend.js` (and the legacy `crossSite.js`) also run on non-WordPress sites, where `window.wp` does not exist. Always call `nxApplyFilters()`.

### The render-hook contract

- For render hooks, the default value is `undefined`.
- A filter returns a value only for the notifications it owns. For all other notifications it returns the value it received.
- The call site uses the built-in branch when the result is `undefined` or `null` (`??`). A notification that a filter claims therefore never renders twice.
- Render filters run while React renders. Return an element (`<MyPart {...props} />`) and call React hooks only inside that component, never in the filter callback itself. A hook called in the callback breaks React's hook order as soon as the filter returns early for another notification.
- List hooks (`nx_frontend_no_*`) receive the built-in list. If a filter returns something that is not an array, the runtime ignores it and keeps the built-in list.

## Hook reference

| Filter | Where | Default value | Extra args | Return to claim |
| --- | --- | --- | --- | --- |
| `nx_frontend_template` | [GetTemplate.ts:91](../../nxdev/notificationx/frontend/themes/GetTemplate.ts#L91), before the theme `switch` | `undefined` | `settings`, `params` (the template params, already escaped and wrapped in `<span>`) | `string[]`, one entry per row. Any other value is ignored. |
| `nx_frontend_time_is_countdown` | [Theme.tsx:61](../../nxdev/notificationx/frontend/themes/Theme.tsx#L61), `{{time}}` tag | `true` for source `announcements` | `post` (config), `entry` | `true` renders "5 days remaining"; `false` renders "5 days ago". |
| `nx_frontend_image` | [Image.js:54](../../nxdev/notificationx/frontend/themes/helpers/Image.js#L54), after the `image_data` check | `undefined` | `{ themeName, data, config, id, style, componentClasses, isSplit, isSplitCss, announcementCSS }` | A React node that replaces the image slot. |
| `nx_theme_before_content` | [Theme.tsx:176](../../nxdev/notificationx/frontend/themes/Theme.tsx#L176), between image and content | `undefined` | Theme props plus `announcementCSS` | A React node. |
| `nx_theme_after_content` | [Theme.tsx:198](../../nxdev/notificationx/frontend/themes/Theme.tsx#L198), between content and close button | `undefined` | Theme props plus `announcementCSS` | A React node. |
| `nx_content_append` | [Content.tsx:83](../../nxdev/notificationx/frontend/themes/helpers/Content.tsx#L83), end of `.notificationx-content` | `undefined` | Content props | A React node. |
| `nx_frontend_no_entry_link_types` | [Analytics.tsx:58](../../nxdev/notificationx/frontend/core/Analytics.tsx#L58), `resolveNotificationLink()` | `['none', 'yt_channel_link', 'announcements_link']` | `config`, `data` | The list with your link type added. A whole-card click on these types does not navigate to `data.link`. |
| `nx_frontend_link_button` | [Analytics.tsx:125](../../nxdev/notificationx/frontend/core/Analytics.tsx#L125), CTA button | `undefined` | `config`, `data` | `{ link_text, show_default_subscribe? }` |
| `nx_frontend_no_mobile_design_sources` | [NotificationContainer.tsx:36](../../nxdev/notificationx/frontend/core/NotificationContainer.tsx#L36) | `['announcements', 'custom_notification', 'inline', 'gdpr_notification']` | — | The list with your source added. These sources keep the desktop layout on mobile. |

`nx_frontend_template` also runs in the admin builder, because the admin bundle imports `GetTemplate` for the `nx_adv_template_default` filter ([AddEditNx/index.ts](../../nxdev/notificationx/admin/AddEditNx/index.ts)). An add-on must register that filter in the admin builder as well as on the frontend.

## `window.nxFrontendRuntime`

The frontend bundle contains its own copy of React. A component from another bundle that uses React hooks (`useState`, `useContext`, `lazy`/`Suspense`) must use the same copy, or React throws "Invalid hook call". [core/runtime.ts](../../nxdev/notificationx/frontend/core/runtime.ts) exposes that copy. [index.tsx](../../nxdev/notificationx/frontend/index.tsx) creates the object when the bundle loads, before `domReady` renders anything.

| Member | What it is |
| --- | --- |
| `version` | `1`. Changes only for a breaking change to this object. |
| `React` | The React copy that renders the notifications. |
| `useNotificationContext` | Hook that returns the frontend context (`rest`, `getTime`, …). |
| `analyticsOnClick(event, restUrl, config, dispatch, credentials)` | Records a CTA click and closes the notification. |
| `recordAnalyticsClick(restUrl, config, omitCredentials)` | Records a click without closing the notification. |
| `getPath(rest, path, query?)` | Builds a REST URL, for example `getPath(ctx.rest, 'analytics/')`. |

The object is frozen. If the bundle loads twice, the first object stays.

> The built-in [Button.js](../../nxdev/notificationx/frontend/themes/helpers/Button.js) passes `omit_credentials` in the `dispatch` position of `analyticsOnClick()`. Do not copy that call into an add-on. Pass `(event, restUrl, config, dispatch, credentials)` as listed above.

## Wiring an add-on script

1. Register the add-on script with `notificationx-public` and `wp-hooks` as dependencies. The script then runs after the runtime exists and before `domReady` renders notifications.
2. In the add-on webpack config, map React to the runtime:

   ```js
   externals: {
       react: 'window.nxFrontendRuntime.React',
       '@wordpress/hooks': 'window.wp.hooks',
       '@wordpress/i18n': 'window.wp.i18n',
   },
   ```

   Only the add-on bundle uses these externals. The free frontend bundle must not use them (see the warning above).
3. If `window.nxFrontendRuntime` is missing (free older than 3.4.0), register no filters. The built-in code in free then keeps rendering.
4. Claim only your own notifications:

   ```js
   const { React } = window.nxFrontendRuntime;
   const OWN = [ 'announcements_theme-13' ];

   wp.hooks.addFilter( 'nx_theme_before_content', 'notificationx-pro', ( node, props ) =>
       OWN.includes( props?.config?.themes ) ? <CtaButton { ...props } icon /> : node
   );
   wp.hooks.addFilter( 'nx_frontend_no_mobile_design_sources', 'notificationx-pro', ( list ) =>
       list.includes( 'announcements' ) ? list : [ ...list, 'announcements' ]
   );
   ```

A PHP filter, `nx_frontend_script_deps`, adds handles to the dependencies of `notificationx-public`. `wp-hooks` stays in the list even if a filter removes it. Use this filter only when the runtime must wait for your script. Otherwise use step 1.

## Why these hooks exist

WordPress.org Guideline 5 does not allow working Pro-only code in the free plugin. The separation plan moves Flashing Tab, Cart Peek, Inline and the Discount Alert (announcements) themes into `notificationx-pro`:

| Step | Release | Content |
| --- | --- | --- |
| 1–3 | Pro 3.2.3 | Pro registers its own Cart Peek Type, Inline classes and Flashing Tab bundle. |
| 4 | **Free 3.4.0** | These hooks, the shared runtime, the `wp-hooks` dependency, and the Pro version notice. Nothing is removed. |
| 5 | Pro | Pro claims the announcements themes and the Cart Peek template through these hooks. |
| 6 | — | Wait 4–6 weeks for Pro updates. |
| 7 | Free | Delete the built-in branches that each hook wraps, and the Pro code that is still in free. |

Free 3.4.0 does not change behavior: with no filters registered, every hook returns its default value. In step 7 the default values stay the same, except that `announcements` is removed from the three built-in lists and the countdown default becomes `false`.

### Pro version notice

`Admin::pro_version_notice()` ([Admin.php:249](../../includes/Admin/Admin.php#L249)) shows a warning that users cannot dismiss when NotificationX Pro is older than `Admin::MIN_PRO_VERSION` (`3.2.3`, [Admin.php:55](../../includes/Admin/Admin.php#L55)). Only users with `update_plugins` see it. The notice uses `all_admin_notices` because `hide_others_plugin_admin_notice()` removes `admin_notices` callbacks on NotificationX screens.

## Tests

- `npm run test:js`: Jest tests in [../../tests/js/](../../tests/js/). They cover each hook's built-in path and claimed path, the no-registry fallback, a throwing filter, and the runtime object.
- `vendor/bin/phpunit --filter Test_Pro_Separation_Seams` ([../../tests/test-pro-separation-seams.php](../../tests/test-pro-separation-seams.php)): the script dependencies and the Pro version notice.

## Source

- [../../nxdev/notificationx/frontend/core/hooks.ts](../../nxdev/notificationx/frontend/core/hooks.ts)
- [../../nxdev/notificationx/frontend/core/runtime.ts](../../nxdev/notificationx/frontend/core/runtime.ts)
- [../../includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php)
- [../../includes/Admin/Admin.php](../../includes/Admin/Admin.php)
