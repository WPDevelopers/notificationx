/**
 * Font and icon stylesheets the notification themes use, bundled in
 * assets/public/vendor/ (WordPress.org Guideline 8, issue #184).
 *
 * frontend.css no longer `@import`s them. On WordPress pages PHP enqueues them
 * as their own non-render-blocking style handles (see
 * FrontEnd::get_external_styles()). Cross-domain embeds have no WordPress
 * enqueue, so the runtime adds them there.
 *
 * Snippets generated before 3.3.3 carry no `external_styles` list. Those fall
 * back to the bundled copies, found next to `common_assets` (the plugin's
 * assets/common/ URL). Without it nothing is added: the themes fall back to
 * sans-serif, and no third-party URL is ever used.
 *
 * @param commonAssets `notificationX.common_assets`.
 */
export const defaultExternalStyles = (commonAssets?: unknown): Record<string, string> => {
    if (typeof commonAssets !== 'string' || !/\/common\/?$/.test(commonAssets)) {
        return {};
    }
    const vendor = commonAssets.replace(/common\/?$/, 'public/vendor/');
    return {
        'notificationx-open-sans': vendor + 'open-sans/css/open-sans.css',
        'notificationx-fontawesome-4': vendor + 'font-awesome/css/font-awesome.min.css',
    };
};

/**
 * Switch the stylesheets PHP deferred to `all`.
 *
 * FrontEnd::non_blocking_style_tag() prints them as `media="print"` with an
 * inline `onload` that switches them, marked `data-nx-style`. An optimizer can
 * strip that handler and a Content Security Policy can block it, which would
 * leave the notifications unstyled, so the runtime switches them too. Safe to
 * call more than once.
 */
export const applyDeferredStyles = () => {
    document.querySelectorAll<HTMLLinkElement>('link[data-nx-style]').forEach(applyLink);
};

// Links switched while still loading stay `data-nx-pending` until they load or
// fail, so whenStyled() knows to wait for them.
const markPending = (link: HTMLLinkElement) => {
    link.dataset.nxPending = '1';
    const settle = () => { delete link.dataset.nxPending; };
    link.addEventListener('load', settle, { once: true });
    link.addEventListener('error', settle, { once: true });
};

const applyLink = (link: HTMLLinkElement) => {
    if (link.media !== 'print') {
        return;
    }
    // A sheet that has already loaded fires no second load event when its
    // media changes; it applies straight away.
    if (!link.sheet) {
        markPending(link);
    }
    link.media = 'all';
};

/**
 * Whether a stylesheet's rules apply, read from its `.nx-style-probe` rule
 * (`--nx-frontend: 1` in frontend.css, `--nx-gdpr-modal: 1` in gdpr-modal.css).
 *
 * @param probe Custom property name without the `--nx-` prefix.
 */
export const styleApplies = (probe: string): boolean => {
    if (!document.body) {
        return false;
    }
    const el = document.createElement('div');
    el.className = 'nx-style-probe';
    el.style.display = 'none';
    document.body.appendChild(el);
    const value = getComputedStyle(el).getPropertyValue('--nx-' + probe).trim();
    el.remove();
    return '1' === value;
};

const whenLoaded = (): Promise<void> => new Promise((resolve) => {
    if (document.readyState === 'complete') {
        resolve();
        return;
    }
    window.addEventListener('load', () => resolve(), { once: true });
});

/** Where to get a stylesheet again when its link is not on the page. */
export type StyleFallback = { href: string, probe: string };

/**
 * Resolve once the stylesheet with this id applies to the page.
 *
 * PHP prints `notificationx-public` as `media="print"` and flips it to `all`
 * on load, so it does not block the first paint (see
 * FrontEnd::non_blocking_style_tag()). Rendering before that would show
 * notifications unstyled for a moment. The link is switched here as well, so
 * a stripped or blocked inline handler cannot leave it unapplied.
 *
 * With a `fallback`, a link that is not on the page (an optimizer combined it
 * into a bundle) is checked with the probe rule: when the bundle does not
 * apply (a print-only bundle), the stylesheet is added again, after the page
 * has loaded so a bundle that is still loading is not duplicated. A sheet
 * that is missing without a fallback (cross-domain embeds) or fails to load
 * does not hold the render back.
 *
 * @param id       Element id of the <link>, e.g. `notificationx-public-css`.
 * @param fallback URL and probe name for re-adding the stylesheet.
 */
export const whenStyled = (id: string, fallback?: StyleFallback): Promise<void> => {
    const link = document.getElementById(id) as HTMLLinkElement | null;
    if (link && link.tagName === 'LINK') {
        applyLink(link);
        return waitFor(link);
    }
    if (link || !fallback?.href || styleApplies(fallback.probe)) {
        return Promise.resolve();
    }
    return whenLoaded().then(() => {
        const again = document.getElementById(id) as HTMLLinkElement | null;
        if (again && again.tagName === 'LINK') {
            return waitFor(again);
        }
        if (again || styleApplies(fallback.probe)) {
            return;
        }
        const added = document.createElement('link');
        added.id = id;
        added.rel = 'stylesheet';
        added.href = fallback.href;
        markPending(added);
        document.head.appendChild(added);
        return waitFor(added);
    });
};

const waitFor = (link: HTMLLinkElement): Promise<void> => new Promise((resolve) => {
    if (!link.dataset.nxPending) {
        resolve();
        return;
    }
    link.addEventListener('load', () => resolve(), { once: true });
    link.addEventListener('error', () => resolve(), { once: true });
});

/**
 * Add a <link> for each style handle => URL that is not on the page yet.
 *
 * The ids match what WordPress prints (`{handle}-css`), so a stylesheet that is
 * already enqueued, or was added by an earlier call, is not added twice.
 *
 * @param styles Handle => URL map from the localized config, if any.
 */
export const loadExternalStyles = (styles?: Record<string, string> | unknown[] | null, commonAssets?: unknown) => {
    // PHP encodes an empty list as [], which adds nothing.
    const list = (styles && typeof styles === 'object' ? styles : defaultExternalStyles(commonAssets)) as Record<string, unknown>;
    Object.keys(list).forEach((handle) => {
        const href = list[handle];
        const id = handle + '-css';
        if (typeof href !== 'string' || !href || document.getElementById(id)) {
            return;
        }
        const link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    });
};
