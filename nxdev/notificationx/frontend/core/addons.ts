/**
 * Add-on scripts on pages without WordPress script loading.
 *
 * On WordPress pages, add-ons (notificationx-pro) enqueue their frontend
 * scripts with PHP and register filters on `window.wp.hooks` before the first
 * render. A Cross Domain Notice site is not a WordPress site: the pasted
 * snippet loads only frontend.js, so there is no `wp.hooks` and no add-on
 * script, and the notifications that an add-on renders (Discount Alert, Cart
 * Peek) would render without their parts.
 *
 * On those pages the runtime asks the `notice` REST endpoint for the add-on
 * scripts (`addon_scripts` in the request). The response lists them in
 * `addon_scripts` (FrontEnd::get_addon_scripts(), PHP filter
 * `nx_frontend_addon_scripts`). Before the notifications are set into state,
 * the runtime:
 *
 * 1. creates a minimal hooks registry at `window.wp.hooks` when there is none,
 * 2. sets each script's data object on `window`,
 * 3. loads the scripts in order and waits until each has run or failed.
 *
 * The snippet is pasted once and never updated, so this has to live in
 * frontend.js, which the snippet loads from the plugin URL on every page view.
 *
 * See docs/api/frontend-js-hooks.md ("Cross Domain Notice").
 */

export type AddonScript = {
    /** Script handle. The tag gets the id `<handle>-js`, like a WordPress enqueue. */
    handle: string;
    /** Script URL. */
    src: string;
    /** Optional data object, set as `window[data.name] = data.value` before the script runs. */
    data?: { name: string; value: unknown } | null;
};

/** Longest wait for one script, so a blocked URL cannot stop the notifications. */
export const ADDON_SCRIPT_TIMEOUT = 5000;

type Callback = { namespace: string; callback: (...args: any[]) => any; priority: number };

/**
 * A small hooks registry with the `@wordpress/hooks` methods add-ons use.
 *
 * Not `@wordpress/hooks` itself: importing it would add a second registry to
 * the bundle on WordPress pages too (see core/hooks.ts). Callbacks run by
 * priority, then in the order they were added, as in `@wordpress/hooks`.
 */
export const createHooksRegistry = () => {
    const filters: Record<string, Callback[]> = {};
    const actions: Record<string, Callback[]> = {};

    const add = (store: Record<string, Callback[]>) =>
        (hookName: string, namespace: string, callback: (...args: any[]) => any, priority = 10) => {
            if (typeof callback !== 'function') {
                return;
            }
            const list = store[hookName] || (store[hookName] = []);
            // Insert after every callback with the same or a lower priority.
            let index = list.length;
            while (index > 0 && list[index - 1].priority > priority) {
                index--;
            }
            list.splice(index, 0, { namespace, callback, priority });
        };

    const remove = (store: Record<string, Callback[]>) =>
        (hookName: string, namespace: string): number => {
            const list = store[hookName];
            if (!list) {
                return 0;
            }
            const before = list.length;
            store[hookName] = list.filter((item) => item.namespace !== namespace);
            return before - store[hookName].length;
        };

    const has = (store: Record<string, Callback[]>) =>
        (hookName: string, namespace?: string): boolean => {
            const list = store[hookName] || [];
            return namespace === undefined
                ? list.length > 0
                : list.some((item) => item.namespace === namespace);
        };

    return {
        addFilter: add(filters),
        removeFilter: remove(filters),
        hasFilter: has(filters),
        applyFilters: (hookName: string, value: any, ...args: any[]) =>
            (filters[hookName] || []).slice().reduce(
                (current, item) => item.callback(current, ...args),
                value
            ),
        addAction: add(actions),
        removeAction: remove(actions),
        hasAction: has(actions),
        doAction: (hookName: string, ...args: any[]) => {
            (actions[hookName] || []).slice().forEach((item) => item.callback(...args));
        },
    };
};

/** Whether the page has a hooks registry (WordPress `wp-hooks`, or one created here). */
export const hasHooksRegistry = (): boolean =>
    typeof (window as any)?.wp?.hooks?.applyFilters === 'function';

// True once this runtime created `window.wp.hooks` itself.
let ownRegistry = false;

// In-flight and finished loads, by handle. Every caller waits for the same
// load, so a second notificationX config on the page cannot render before an
// add-on script that the first one started has run.
const loads: Record<string, Promise<void>> = {};

/**
 * Whether the `notice` request must ask for the add-on scripts: the page has
 * no WordPress hooks registry. A registry this runtime created does not count,
 * so a second config on the same page still gets the list (and waits for it).
 */
export const needsAddonScripts = (): boolean => ownRegistry || !hasHooksRegistry();

/**
 * Create `window.wp.hooks` when the page has none. An existing registry is
 * never replaced: add-on filters may already be on it.
 */
export const ensureHooksRegistry = () => {
    const w = window as any;
    if (!hasHooksRegistry()) {
        w.wp = w.wp || {};
        w.wp.hooks = createHooksRegistry();
        ownRegistry = true;
    }
    return w.wp.hooks;
};

/** Test helper: forget the registry and loads this module created. */
export const resetAddonState = () => {
    ownRegistry = false;
    Object.keys(loads).forEach((handle) => delete loads[handle]);
};

const isAddonScript = (item: any): item is AddonScript =>
    !!item &&
    typeof item.handle === 'string' && item.handle !== '' &&
    typeof item.src === 'string' && /^(https?:)?\/\//.test(item.src);

const loadScript = (addon: AddonScript): Promise<void> => {
    if (!loads[addon.handle]) {
        loads[addon.handle] = injectScript(addon);
    }
    return loads[addon.handle];
};

const injectScript = (addon: AddonScript): Promise<void> => new Promise((resolve) => {
    const id = `${addon.handle}-js`;
    // Printed by WordPress or by the page itself: it has run or will run in order.
    if (document.getElementById(id)) {
        resolve();
        return;
    }
    const w = window as any;
    if (addon.data && typeof addon.data.name === 'string' && addon.data.name && w[addon.data.name] === undefined) {
        w[addon.data.name] = addon.data.value;
    }
    const script = document.createElement('script');
    script.id = id;
    script.src = addon.src;
    // Keep the order of the list, as WordPress prints dependencies in order.
    script.async = false;
    const timer = setTimeout(done, ADDON_SCRIPT_TIMEOUT);
    function done() {
        clearTimeout(timer);
        resolve();
    }
    script.addEventListener('load', done, { once: true });
    script.addEventListener('error', () => {
        console.error(`NotificationX: could not load "${addon.src}".`);
        done();
    }, { once: true });
    document.head.appendChild(script);
});

/**
 * Load the add-on scripts from a `notice` response. Resolves when every
 * script has run, failed or timed out; never rejects, so a missing add-on
 * leaves the built-in rendering instead of no notifications.
 */
export const loadAddonScripts = (addons: unknown): Promise<void> => {
    const list = Array.isArray(addons) ? addons.filter(isAddonScript) : [];
    if (list.length) {
        ensureHooksRegistry();
    }
    return list
        .reduce((chain, addon) => chain.then(() => loadScript(addon)), Promise.resolve())
        // Also wait for loads another config on this page started.
        .then(() => Promise.all(Object.values(loads)))
        .then(() => undefined)
        .catch(() => undefined);
};
