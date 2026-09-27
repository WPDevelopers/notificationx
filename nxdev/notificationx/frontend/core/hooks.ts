/**
 * Frontend extension points.
 *
 * Add-ons (notificationx-pro) register filters on the global WordPress hooks
 * registry, `window.wp.hooks`, which FrontEnd.php loads through the `wp-hooks`
 * script dependency. The registry is read at call time on purpose:
 *
 * - Importing `@wordpress/hooks` here would bundle a second, private registry
 *   (the frontend build runs with --webpack-no-externals), and filters that
 *   add-ons add to `window.wp.hooks` would never reach it.
 * - Declaring `@wordpress/hooks` as a webpack external would break crossSite.js,
 *   which is built by the same config and runs on non-WordPress sites where
 *   `window.wp` does not exist.
 *
 * When the registry is missing (crossSite.js, or a page that dequeued
 * wp-hooks), the default value is returned, so built-in rendering continues.
 *
 * Contract for the render hooks: the default value is `undefined`. A filter
 * returns something only for the notifications it owns and passes the value
 * through otherwise. Call sites fall back to the built-in branch with `??`, so
 * a claimed notification never renders twice.
 *
 * A filter that throws is logged and ignored, so a broken add-on falls back to
 * built-in rendering instead of unmounting the whole notification tree.
 *
 * See docs/api/frontend-js-hooks.md.
 */
export const nxApplyFilters = <T = any>(hookName: string, value: T, ...args: any[]): T => {
    const hooks = typeof window !== "undefined" ? (window as any)?.wp?.hooks : undefined;
    if (!hooks || typeof hooks.applyFilters !== "function") {
        return value;
    }
    try {
        return hooks.applyFilters(hookName, value, ...args) as T;
    } catch (error) {
        console.error(`NotificationX: filter "${hookName}" failed.`, error);
        return value;
    }
};

/**
 * Filters a list of ids (sources, link types). A filter that returns anything
 * other than an array is ignored and the built-in list is kept.
 */
export const nxApplyListFilter = (hookName: string, list: string[], ...args: any[]): string[] => {
    const filtered = nxApplyFilters<unknown>(hookName, list, ...args);
    return Array.isArray(filtered) ? (filtered as string[]) : list;
};

export default nxApplyFilters;
