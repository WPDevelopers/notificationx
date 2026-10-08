# Frontend JS Hooks

JavaScript extension points in the frontend runtime (`notificationx-public`, built from [../../nxdev/notificationx/frontend/](../../nxdev/notificationx/frontend/)). `notificationx-pro` uses them to render its own notifications without code in the free plugin. PHP hooks are in [hooks-filters.md](hooks-filters.md).

Added in free 3.3.3 as step 4 of the free/Pro code separation (see [Why these hooks exist](#why-these-hooks-exist)). Since free 3.3.4 (step 7) free has no built-in Discount Alert or Cart Peek rendering: NotificationX Pro 3.2.4+ renders them through these hooks.

## How the runtime reads filters

Filters live on the global WordPress registry, `window.wp.hooks`. Register them with `wp.hooks.addFilter()` from a script that loads on the same page.

- `FrontEnd::get_script_dependencies()` ([FrontEnd.php:195](../../includes/FrontEnd/FrontEnd.php#L195)) makes `notificationx-public` depend on `wp-hooks`, so the registry is always present on WordPress pages.
- The runtime calls filters through `nxApplyFilters()` / `nxApplyListFilter()` in [core/hooks.ts](../../nxdev/notificationx/frontend/core/hooks.ts). They read `window.wp.hooks` each time they run.
- When `window.wp.hooks` is missing, the helpers return the default value and the built-in code renders. This happens on non-WordPress sites: the Pro Cross Domain Notice snippet ([notificationx-pro `Admin/XSS.php`](../../../notificationx-pro/includes/Admin/XSS.php)) loads `frontend.js` there without `wp-hooks`, and the legacy `crossSite.js` runs there too. On those pages the runtime creates a small registry and loads the add-on scripts itself before the first render; see [Cross Domain Notice](#cross-domain-notice).
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
| `nx_frontend_template` | [GetTemplate.ts:92](../../nxdev/notificationx/frontend/themes/GetTemplate.ts#L92), before the theme `switch` | `undefined` | `settings`, `params` (the template params, already escaped and wrapped in `<span>`) | `string[]`, one entry per row. Any other value is ignored. |
| `nx_frontend_time_is_countdown` | [Theme.tsx:61](../../nxdev/notificationx/frontend/themes/Theme.tsx#L61), `{{time}}` tag | `false` (free 3.3.3: `true` for source `announcements`) | `post` (config), `entry` | `true` renders "5 days remaining"; `false` renders "5 days ago". |
| `nx_frontend_image` | [Image.js:53](../../nxdev/notificationx/frontend/themes/helpers/Image.js#L53), after the `image_data` check | `undefined` | `{ themeName, data, config, id, style, componentClasses, isSplit, isSplitCss, announcementCSS }` | A React node that replaces the image slot. |
| `nx_theme_before_content` | [Theme.tsx:177](../../nxdev/notificationx/frontend/themes/Theme.tsx#L177), between image and content | `undefined` | Theme props plus `announcementCSS` | A React node. |
| `nx_theme_after_content` | [Theme.tsx:191](../../nxdev/notificationx/frontend/themes/Theme.tsx#L191), between content and close button | `undefined` | Theme props plus `announcementCSS` | A React node. |
| `nx_content_append` | [Content.tsx:82](../../nxdev/notificationx/frontend/themes/helpers/Content.tsx#L82), end of `.notificationx-content` | `undefined` | Content props | A React node. |
| `nx_frontend_no_entry_link_types` | [Analytics.tsx:58](../../nxdev/notificationx/frontend/core/Analytics.tsx#L58), `resolveNotificationLink()` | `['none', 'yt_channel_link']` (free 3.3.3 also had `announcements_link`) | `config`, `data` | The list with your link type added. A whole-card click on these types does not navigate to `data.link`. |
| `nx_frontend_link_button` | [Analytics.tsx:124](../../nxdev/notificationx/frontend/core/Analytics.tsx#L124), CTA button | `undefined` | `config`, `data` | `{ link_text, show_default_subscribe? }` |
| `nx_frontend_no_mobile_design_sources` | [NotificationContainer.tsx:36](../../nxdev/notificationx/frontend/core/NotificationContainer.tsx#L36) | `['custom_notification', 'inline', 'gdpr_notification']` (free 3.3.3 also had `announcements`) | — | The list with your source added. These sources keep the desktop layout on mobile. |

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
3. If `window.nxFrontendRuntime` is missing (free older than 3.3.3), register no filters. The built-in code in free then keeps rendering.
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
5. To reach Cross Domain Notice sites, also return the script from the PHP filter `nx_frontend_addon_scripts` (see [Cross Domain Notice](#cross-domain-notice)). Do not depend on `wp.i18n` there; pass translated strings in the `data` object.

A PHP filter, `nx_frontend_script_deps`, adds handles to the dependencies of `notificationx-public`. `wp-hooks` stays in the list even if a filter removes it. Use this filter only when the runtime must wait for your script. Otherwise use step 1.

## Why these hooks exist

WordPress.org Guideline 5 does not allow working Pro-only code in the free plugin. The separation plan moved Flashing Tab, Cart Peek, Inline and the Discount Alert (announcements) themes into `notificationx-pro`:

| Step | Release | Content |
| --- | --- | --- |
| 1–3 | Pro 3.2.3 | Pro registers its own Cart Peek Type, Inline classes and Flashing Tab bundle. |
| 4 | **Free 3.3.3** | These hooks, the shared runtime, the `wp-hooks` dependency, and the Pro version notice. Nothing is removed. |
| 5 | **Pro 3.2.4** | Pro claims the Discount Alert (announcements) themes and the Cart Peek rows through these hooks (`notificationx-pro` [nxdev/frontend-themes/](../../../notificationx-pro/nxdev/frontend-themes/)), runs its Inline classes without the free copies, and registers the Cart Peek Type from its engine bootstrap. |
| 7 | **Free 3.3.4** | Free deletes the Pro code and the built-in branches each hook wrapped, and loads add-on scripts on Cross Domain Notice sites. `MIN_PRO_VERSION` is `3.2.4`. |

Steps 5 and 7 ship together, without the planned 4–6 week wait (step 6). **Release Pro 3.2.4 before free 3.3.4.**

What free 3.3.4 removed:

- PHP: `Types/WooCommerceCartPeek.php` and its `TypesFactory` entry (same change, see the rule below), `Features/Inline.php` (`NotificationX\Core\Inline`), `Features/ShortcodeInline.php` and its boot in `NotificationX.php`.
- JS: `themes/announcements/`, `themes/helpers/Button.js`, the announcements and Cart Peek layouts in `GetTemplate.ts`, the built-in branches behind `nx_theme_before_content`, `nx_theme_after_content`, `nx_content_append`, `nx_frontend_image` and `nx_frontend_link_button`, the unused `announcementCSS` in `core/Notification.tsx`, `flashing-tab.ts`, `flashing/` and its webpack entry.
- Assets: `assets/public/js/flashing-tab.js`, `assets/public/image/flashing-tab/`, `scss/_themes/_announcement.scss` (moved to notificationx-pro `nxdev/scss/frontend/themes/_announcements-base.scss`, imported first so the cascade is unchanged).

Kept in free: the upsell stubs (Cart Peek, Flashing Tab and Discount Alert sources, `Types/FlashingTab.php`, `Types/OfferAnnouncement.php`, theme preview images). The Flashing Tab stub stores bare icon file names, which Pro resolves against its own icons. `announcementCSS` is still built in `Theme.tsx`, because the render hooks pass it to add-ons.

**The rule that keeps the removal fatal-free:** delete a Type class and its `TypesFactory::$types` entry in the same change. `TypeFactory::register_types()` and `get()` have no `class_exists()` guard, so an entry whose class is missing is the one fatal path. `ExtensionFactory` skips an extension whose Type is not registered, so the free Cart Peek stub registers only when Pro registers the Cart Peek Type, and `WooCommerce::admin_actions()` hooks the Type's `nx_can_entry` only when the Type exists.

### With an older NotificationX Pro

No fatal error with any Pro version: Pro 3.2.3 and older check `class_exists( 'NotificationX\Core\Inline' )` before using the free inline classes, and nothing in them extends a deleted class at load time. They lose these notifications instead:

| Pro | Flashing Tab | Inline / `[notificationx_inline]` | Cart Peek | Discount Alert |
| --- | --- | --- | --- | --- |
| 3.2.2 and older | No (loaded free's script) | No | No | Generic layout, no badge or button |
| 3.2.3 | Yes | No | Type and data work; rows use the generic Sales layout | Generic layout, no badge or button |
| 3.2.4+ | Yes | Yes | Yes | Yes |

The non-dismissible [Pro version notice](#pro-version-notice) asks these sites to update.

## Cross Domain Notice

A Cross Domain Notice site is not a WordPress site. The snippet that users paste once (notificationx-pro `Admin/XSS.php`) loads only free's `frontend.js` and the CSS, so there is no `wp.hooks` and no Pro `frontend-themes.js`. Changing the snippet would reach only sites that copy it again, so the fix is in `frontend.js`, which the snippet loads from the plugin URL on every page view.

[core/addons.ts](../../nxdev/notificationx/frontend/core/addons.ts):

1. When the page has no hooks registry, the runtime sends `addon_scripts: true` with its `notice` REST request ([useNotificationX.ts](../../nxdev/notificationx/frontend/core/useNotificationX.ts)).
2. `FrontEnd::get_addon_scripts()` ([FrontEnd.php:763](../../includes/FrontEnd/FrontEnd.php#L763)) returns the list from the PHP filter `nx_frontend_addon_scripts` as `addon_scripts` in the response. Each item has a `handle`, an absolute http(s) `src` and an optional `data` object. Invalid items are dropped. Without the request flag the key is not in the response.
3. Before the notifications are set into state, `loadAddonScripts()` creates a small registry at `window.wp.hooks` when there is none (`addFilter`, `applyFilters`, `removeFilter`, `hasFilter` and the action equivalents; callbacks run by priority, then in the order they were added), sets each `data` object on `window`, and loads the scripts in list order. A script that fails or takes longer than 5 seconds is skipped, so a blocked URL never stops the notifications. Each script loads once per page. When the page has several `notificationXArr` configs, each one asks for the list (a registry the runtime created does not count as a WordPress registry) and waits for every add-on load that another config started, so no config renders before the add-on filters exist.

NotificationX Pro 3.2.4 adds `frontend-themes.js` to the list when the site has an enabled Discount Alert or Cart Peek notification (`FrontEnd::frontend_addon_scripts()`), with `nxProFrontendThemes` as its data. On cross-domain sites the "OFF" badge uses Pro's translation only, because free's script translations are not available in a REST request.

An existing registry is never replaced. On a WordPress page that dequeued `wp-hooks`, the runtime also asks for the add-on scripts; Pro's own enqueue does not print there, because it depends on `wp-hooks`.

### Pro version notice

`Admin::pro_version_notice()` ([Admin.php:253](../../includes/Admin/Admin.php#L253)) shows a warning that users cannot dismiss when NotificationX Pro is older than `Admin::MIN_PRO_VERSION` (`3.2.4`, [Admin.php:59](../../includes/Admin/Admin.php#L59)). Only users with `update_plugins` see it. The notice uses `all_admin_notices` because `hide_others_plugin_admin_notice()` removes `admin_notices` callbacks on NotificationX screens.

## Tests

- `npm run test:js`: Jest tests in [../../tests/js/](../../tests/js/). They cover each hook's default path (no Discount Alert or Cart Peek rendering in free) and claimed path, the no-registry fallback, a throwing filter, the runtime object, and the add-on loader and registry ([frontend/addons.test.js](../../tests/js/frontend/addons.test.js)).
- `vendor/bin/phpunit --filter Test_Pro_Separation_Seams` ([../../tests/test-pro-separation-seams.php](../../tests/test-pro-separation-seams.php)): the script dependencies and the Pro version notice.
- `vendor/bin/phpunit --filter Test_Pro_Code_Removed` ([../../tests/test-pro-code-removed.php](../../tests/test-pro-code-removed.php)): the removed classes, files and Type entry, the Flashing Tab stub icons, the Cart Peek stub without its Type, and the `addon_scripts` list in the `notice` response.
- notificationx-pro `tests/test-pro-code-separation.php`: Pro registers the Cart Peek Type and returns `frontend-themes.js` as an add-on script.

## Source

- [../../nxdev/notificationx/frontend/core/hooks.ts](../../nxdev/notificationx/frontend/core/hooks.ts)
- [../../nxdev/notificationx/frontend/core/runtime.ts](../../nxdev/notificationx/frontend/core/runtime.ts)
- [../../nxdev/notificationx/frontend/core/addons.ts](../../nxdev/notificationx/frontend/core/addons.ts)
- [../../includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php)
- [../../includes/Admin/Admin.php](../../includes/Admin/Admin.php)
