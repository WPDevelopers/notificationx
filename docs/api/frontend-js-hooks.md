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
| `nx_frontend_animation` | `Notification.tsx`, `GDPR.tsx`, `Popup.tsx`, before the class list is built | `undefined` | `{ settings, animation, animationStyle, is_pro }` (`animation` is true while hiding; `animationStyle` is the built-in entrance) | `{ classNames, style }`: classes put before the base classes, style merged into the element style. Without a claim the built-in entrance (`SlideTop 300ms`, popups `fadeIn 300ms`) is used. |
| `nx_frontend_notification_sound` | `Notification.tsx`, first child of the notification | `null` | `{ settings, assets, is_pro }` | A React node (Pro: `<audio>` that plays on mount). |
| `nx_frontend_mobile_notification` | `NotificationContainer.tsx`, before `<Notification>`, only on mobile for `is_mobile_responsive` notifications that are not in `nx_frontend_no_mobile_design_sources` | `undefined` | `{ notice, assets, dispatch, is_pro }` | A keyed React element that replaces `<Notification>` (Pro: the compact mobile layout). |
| `nx_popup_form_fields` | `Popup.tsx`, before the message field | `null` | `{ settings, formData, handleInputChange, setFocusedField, isSubmitting, validationErrors, getInputStyles, is_pro, labels }` | A React node (Pro: name and email inputs, themes four to seven). |
| `nx_popup_validate_fields` | `Popup.tsx`, `validateForm()` | `{}` | `{ settings, formData, is_pro, messages }` | Errors to merge (`{ name?, email? }`), translated texts from `messages`. |
| `nx_popup_submission_fields` | `Popup.tsx`, submitted data | `{ name: '', email: '' }` | `{ settings, formData, is_pro }` | `{ name, email }`. |
| `nx_exit_intent_form_fields` | `ExitIntentPopup.tsx` theme one, before the message field | `null` | `{ s, name, email, setName, setEmail, inputStyle, namePlaceholder, emailPlaceholder, is_pro }` | A React node (Pro: name and email inputs). |
| `nx_exit_intent_submission_fields` | `ExitIntentPopup.tsx`, submitted data | `{}` | `{ s, name, email, is_pro }` | `{ name?, email? }` merged into the payload. |
| `nx_exit_intent_theme` | `ExitIntentPopup.tsx`, after the Elementor branch, before the built-in themes (free 3.3.5) | `undefined` | `{ theme, settings, is_pro, overlayClass, showClose, closeStyle, handleClose, renderCta, submitEntry }` (`theme` is `theme-two` etc.; `submitEntry(payload)` posts to `popup-submit` and resolves `false` when the page has no REST URL) | A React element that replaces the whole popup (Pro: themes two, five, six and seven). |
| `nx_frontend_link_widget` | [Analytics.tsx](../../nxdev/notificationx/frontend/core/Analytics.tsx), where the link is rendered, after the press-bar branch (free 3.3.5) | `undefined` | `{ config, data, styles, show_default_subscribe }` | A React element that replaces the link (Pro: the YouTube subscribe button, which loads Google's `platform.js`). |

`nx_frontend_template` also runs in the admin builder, because the admin bundle imports `GetTemplate` for the `nx_adv_template_default` filter ([AddEditNx/index.ts](../../nxdev/notificationx/admin/AddEditNx/index.ts)). An add-on must register that filter in the admin builder as well as on the frontend.

### Verified Cards (`vc-*`)

The Verified Card themes (`conversions_vc-receipt`, `woocommerce_sales_vc-receipt`, `conversions_vc-showcase`, `woocommerce_sales_vc-showcase`, `comments_vc-thread`, the `*_vc-scorecard` and `*_vc-ratings` review keys (including Google), `download_stats_vc-counter`, `donation_vc-pledge`, `elearning_vc-enrol`, `form_vc-submission` and `download_stats_vc-installs`; the full list is `GlobalFields::vc_theme_keys()`) render through their own card, [themes/vc/VerifiedCard.tsx](../../nxdev/notificationx/frontend/themes/vc/VerifiedCard.tsx), on desktop, tablet and the slim phone layout. That card replaces the generic Image/Content/Close markup, so for these keys:

- `nx_theme_before_content`, `nx_theme_after_content` and `nx_content_append` **do not run**. NotificationX Pro uses them only for its Discount Alert (`announcements_*`) themes ([register.tsx](../../../notificationx-pro/nxdev/frontend-themes/register.tsx)); a third-party add-on that relies on them does not reach vc cards.
- `nx_frontend_image` still runs: the card renders the image through `Image`.
- `nx_frontend_template` runs before the vc case. For a vc key, `GetTemplate` returns **slots** (a fixed-length array the card reads by index), not display rows; see [add-new-design.md §8](../features/sales-notification/add-new-design.md#8-verified-cards-vc-). An add-on should not claim vc keys, or must return the same slot shape.
- `nx_frontend_time_is_countdown` runs as for every theme (the `{{time}}` substitution in Theme.tsx).
- NotificationX Pro's mobile card (`nx_frontend_mobile_notification`) renders Theme with `is_mobile`; vc keys then take the generic path with compact rows, so the content hooks above run there as before.

The vc option C line scroll ([useVcLineScroll.ts](../../nxdev/notificationx/frontend/themes/vc/useVcLineScroll.ts)) uses no hooks and imports only React. [tests/js/frontend-no-wp-hooks.test.js](../../tests/js/frontend-no-wp-hooks.test.js) fails if any file reachable from the frontend entries imports `@wordpress/hooks`.

The admin `nx_adv_template_default` callback in [AddEditNx/index.ts](../../nxdev/notificationx/admin/AddEditNx/index.ts) returns compact rows for vc keys (`vcCompactRows(getThemeName(v), GetTemplate(v))`) and `GetTemplate(v)` for every other key.

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
| `Theme`, `Analytics` | Free's theme and analytics components (free 3.3.4+), used by the Pro compact mobile layout. |
| `resolveNotificationLink`, `isObject`, `calculateAnimationStartTime`, `getResThemeName`, `getThemeName` | Helpers used by the same layout (free 3.3.4+). |

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
- Pro-only behaviour that free shipped locked behind `is_pro` (WordPress.org Guideline 5, issue #184), moved to NotificationX Pro 3.2.4 (`nxdev/frontend-themes/features/`) behind the hooks above: notification show/hide animations (and animate.css; free keeps only the `fadeIn` keyframe its popups use), notification sound, the compact mobile layout (`core/NotificationForMobile.tsx`), and the Popup / Exit Intent name and email fields, their validation and submitted values. The feedback CSV export gets its Name and Email Address columns from Pro (`nx_feedback_entries_csv_headers`, `nx_feedback_entries_csv_cells`).
- Assets: `assets/public/js/flashing-tab.js`, `assets/public/image/flashing-tab/`, `scss/_themes/_announcement.scss` (moved to notificationx-pro `nxdev/scss/frontend/themes/_announcements-base.scss`, imported first so the cascade is unchanged).

Kept in free: the upsell stubs (Cart Peek, Flashing Tab and Discount Alert sources, `Types/FlashingTab.php`, `Types/OfferAnnouncement.php`, theme preview images). The Flashing Tab stub stores bare icon file names, which Pro resolves against its own icons. `announcementCSS` is still built in `Theme.tsx`, because the render hooks pass it to add-ons.

**The rule that keeps the removal fatal-free:** delete a Type class and its `TypesFactory::$types` entry in the same change. `TypeFactory::register_types()` and `get()` have no `class_exists()` guard, so an entry whose class is missing is the one fatal path. `ExtensionFactory` skips an extension whose Type is not registered, so the free Cart Peek stub registers only when Pro registers the Cart Peek Type, and `WooCommerce::admin_actions()` hooks the Type's `nx_can_entry` only when the Type exists.

### Free 3.3.5 (issue #184)

Free 3.3.5 moves more Pro-only code to NotificationX Pro 3.2.5 and raises `Admin::MIN_PRO_VERSION` to `3.2.5`. **Release Pro 3.2.5 before free 3.3.5.**

- Exit Intent themes two, five, six and seven: their markup, countdown and theme-seven email form (`ExitIntentPopup.tsx`) and their styles (`scss/_themes/_exit-intent.scss`) are in notificationx-pro `nxdev/frontend-themes/exit-intent/` and `nxdev/scss/frontend/themes/_exit-intent.scss`, rendered through `nx_exit_intent_theme`. `NotificationX::PRO_RENDERED_THEMES` lists them; `NotificationX::is_theme_unrenderable()` makes `FrontEnd::get_notifications_ids()` and `get_notifications()` skip such a notification without a current Pro, unless it is linked to an Elementor design (`elementor_id`).
- The YouTube subscribe button and Google's `platform.js` (`Analytics.tsx`) are in notificationx-pro `nxdev/frontend-themes/features/ytSubscribe.tsx`, rendered through `nx_frontend_link_widget`. Free never loads `platform.js`.
- Free's `Types\Inline` keeps only its upsell fields and `show_on_exclude()`; the stock-theme entry filter is Pro's.

### Without NotificationX Pro, or with an older Pro

No fatal error with any Pro version: Pro 3.2.3 and older check `class_exists( 'NotificationX\Core\Inline' )` before using the free inline classes, and nothing in them extends a deleted class at load time. Free 3.3.4 handles what they cannot render:

| Pro | Flashing Tab | Growth Alert, `[notificationx_inline]` | Cart Peek | Discount Alert |
| --- | --- | --- | --- | --- |
| none | No (upsell) | Nothing shown | Nothing shown | Nothing shown |
| 3.2.2 and older | No | Nothing shown | Nothing shown | Nothing shown |
| 3.2.3 | Yes | Nothing shown | Nothing shown | Nothing shown |
| 3.2.4+ | Yes | Yes | Yes | Yes |

How free does it:

- **Frontend.** `NotificationX::PRO_RENDERED_TYPES` lists `inline`, `woocommerce_cart_peek` and `offer_announcement`. `NotificationX::is_type_unrenderable()` is true for them when Pro is not active or `Admin::pro_needs_update()`. `FrontEnd::get_notifications_ids()` and `FrontEnd::get_notifications()` skip such notifications, so the frontend never renders them without their theme code. Before this, Growth Alert showed as a floating card with Pro 3.2.3 (the free `Types\Inline::show_on_exclude` filter is added only when the Type is registered), and Discount Alert logged `Please select a theme` in the Console.
- **`[notificationx_inline]`.** `FrontEnd::register_inline_shortcode_fallback()` (`init` priority 999, also in admin) registers an empty handler when no plugin registered the shortcode. Pro 3.2.4+ registers the real one earlier, and its `add_shortcode()` would replace the fallback anyway. Without it, the raw shortcode text was shown to visitors.
- **Pro 3.2.2 Flashing Tab script.** Pro 3.2.2 enqueues `notificationx-public-flashing-tab` from free's deleted `assets/public/js/flashing-tab.js` (a 404). `FrontEnd::dequeue_removed_scripts()` (`wp_enqueue_scripts` priority 100) dequeues a handle in `FrontEnd::REMOVED_SCRIPTS` only when its source is free's copy and the file is missing. Pro 3.2.3+ uses the same handle with its own file, which is kept.
- **Builder.** A Type is registered only when an active extension uses it. Without Pro the Cart Peek Type is missing; with Pro 3.2.3 the Growth Alert (`inline`) Type is missing, because Pro 3.2.3's inline extensions return early without the free `Core\Inline` class. The builder cannot show a missing Type and opens the notification as the first registered Type (WooCommerce Sales), so Update used to rewrite `type`, `source` and `theme`. Now `GET /nx/{id}` returns `type_unavailable: true` for such a notification (never stored, `PostType::save_post()` drops it). The builder shows an error notice and disables **Update**. `POST /nx/{id}` refuses to change the type of a notification whose saved Type is not registered (`PostType::check_type_change()`, `409 nx_type_unavailable`). Saving with the same type, the status toggle and Delete still work. The same applies when a module is turned off. The type picker still lists the missing Pro-rendered types as locked (`GlobalFields::get_type_options()`, data in `GlobalFields::pro_rendered_type_stubs()`): without Pro, Cart Peek shows with its upsell popup, as in free 3.3.3, while the WooCommerce module is on; with an outdated Pro, all three show the update popup. The All NotificationX list labels such a notification from the same data ("Cart Peek", not `woocommerce_cart_peek`).

The non-dismissible [Pro version notice](#pro-version-notice) asks these sites to update.

## Cross Domain Notice

A Cross Domain Notice site is not a WordPress site. The snippet that users paste once (notificationx-pro `Admin/XSS.php`) loads only free's `frontend.js` and the CSS, so there is no `wp.hooks` and no Pro `frontend-themes.js`. Changing the snippet would reach only sites that copy it again, so the fix is in `frontend.js`, which the snippet loads from the plugin URL on every page view.

[core/addons.ts](../../nxdev/notificationx/frontend/core/addons.ts):

1. When the page has no hooks registry, the runtime sends `addon_scripts: true` with its `notice` REST request ([useNotificationX.ts](../../nxdev/notificationx/frontend/core/useNotificationX.ts)).
2. `FrontEnd::get_addon_scripts()` ([FrontEnd.php:824](../../includes/FrontEnd/FrontEnd.php#L824)) returns the list from the PHP filter `nx_frontend_addon_scripts` as `addon_scripts` in the response. Each item has a `handle`, an absolute http(s) `src` and an optional `data` object. Invalid items are dropped. Without the request flag the key is not in the response.
3. Before the notifications are set into state, `loadAddonScripts()` creates a small registry at `window.wp.hooks` when there is none (`addFilter`, `applyFilters`, `removeFilter`, `hasFilter` and the action equivalents; callbacks run by priority, then in the order they were added), sets each `data` object on `window`, and loads the scripts in list order. A script that fails or takes longer than 5 seconds is skipped, so a blocked URL never stops the notifications. Each script loads once per page. When the page has several `notificationXArr` configs, each one asks for the list (a registry the runtime created does not count as a WordPress registry) and waits for every add-on load that another config started, so no config renders before the add-on filters exist.

NotificationX Pro 3.2.4 adds `frontend-themes.js` to the list when the site has an enabled Discount Alert or Cart Peek notification (`FrontEnd::frontend_addon_scripts()`), with `nxProFrontendThemes` as its data. On cross-domain sites the "OFF" badge uses Pro's translation only, because free's script translations are not available in a REST request.

Fonts: a snippet copied before Pro 3.2.4 has no DM Sans (Discount Alert badge) in its `external_styles`, so free loads only its default fonts. `nxProFrontendThemes.styles` carries Pro's font stylesheets (`FrontEnd::get_external_styles_pro()`, opt-out filter `notificationx_pro_load_dm_sans`), and `frontend-themes.js` adds each one that is not on the page yet (notificationx-pro `nxdev/frontend-themes/styles.ts`). Free 3.3.3 does not ask for add-on scripts, so free 3.3.3 + Pro 3.2.4 with an old snippet stays without DM Sans until free is updated.

An existing registry is never replaced. On a WordPress page that dequeued `wp-hooks`, the runtime also asks for the add-on scripts; Pro's own enqueue does not print there, because it depends on `wp-hooks`.

### Pro version notice

`Admin::pro_version_notice()` ([Admin.php:253](../../includes/Admin/Admin.php#L253)) shows a warning that users cannot dismiss when NotificationX Pro is older than `Admin::MIN_PRO_VERSION` (`3.2.4`, [Admin.php:59](../../includes/Admin/Admin.php#L59)). Only users with `update_plugins` see it. The notice uses `all_admin_notices` because `hide_others_plugin_admin_notice()` removes `admin_notices` callbacks on NotificationX screens.

## Tests

- `npm run test:js`: Jest tests in [../../tests/js/](../../tests/js/). They cover each hook's default path (no Discount Alert or Cart Peek rendering in free) and claimed path, the no-registry fallback, a throwing filter, the runtime object, and the add-on loader and registry ([frontend/addons.test.js](../../tests/js/frontend/addons.test.js)).
- `vendor/bin/phpunit --filter Test_Pro_Separation_Seams` ([../../tests/test-pro-separation-seams.php](../../tests/test-pro-separation-seams.php)): the script dependencies and the Pro version notice.
- `vendor/bin/phpunit --filter Test_Pro_Code_Removed` ([../../tests/test-pro-code-removed.php](../../tests/test-pro-code-removed.php)): the removed classes, files and Type entry, the Flashing Tab stub icons, the Cart Peek stub without its Type, and the `addon_scripts` list in the `notice` response.
- `vendor/bin/phpunit --filter Test_Old_Pro_Compat` ([../../tests/test-old-pro-compat.php](../../tests/test-old-pro-compat.php)): free without Pro, the builder type guard and `type_unavailable`, the skipped Pro-rendered types, the empty `[notificationx_inline]` and the dequeued Pro 3.2.2 Flashing Tab script.
- notificationx-pro `tests/test-pro-code-separation.php`: Pro registers the Cart Peek Type, returns `frontend-themes.js` with its fonts as an add-on script, keeps its `[notificationx_inline]` handler, and free treats the Pro types as available.

## Source

- [../../nxdev/notificationx/frontend/core/hooks.ts](../../nxdev/notificationx/frontend/core/hooks.ts)
- [../../nxdev/notificationx/frontend/core/runtime.ts](../../nxdev/notificationx/frontend/core/runtime.ts)
- [../../nxdev/notificationx/frontend/core/addons.ts](../../nxdev/notificationx/frontend/core/addons.ts)
- [../../includes/FrontEnd/FrontEnd.php](../../includes/FrontEnd/FrontEnd.php)
- [../../includes/Admin/Admin.php](../../includes/Admin/Admin.php)
