import { createHooks } from '@wordpress/hooks';

/**
 * Installs a fresh registry at window.wp.hooks, the way the `wp-hooks` script
 * does on a WordPress page, and returns it.
 */
export const installHooks = () => {
	const hooks = createHooks();
	window.wp = { hooks };
	return hooks;
};
