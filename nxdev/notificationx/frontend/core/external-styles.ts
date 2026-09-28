/**
 * Third-party font and icon stylesheets the notification themes use.
 *
 * frontend.css no longer `@import`s them. On WordPress pages PHP enqueues them
 * as their own non-render-blocking style handles (see
 * FrontEnd::get_external_styles()). Cross-domain embeds have no WordPress
 * enqueue, so the runtime adds them there.
 *
 * Snippets generated before the change carry no `external_styles` list, so
 * those fall back to the defaults below (the old `@import`s).
 */
export const DEFAULT_EXTERNAL_STYLES: Record<string, string> = {
    'notificationx-open-sans': 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap',
    'notificationx-fontawesome-4': 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
};

/**
 * Resolve once the stylesheet with this id applies to the page.
 *
 * PHP prints `notificationx-public` as `media="print"` and flips it to `all`
 * on load, so it does not block the first paint (see
 * FrontEnd::non_blocking_style_tag()). Rendering before that would show
 * notifications unstyled for a moment. A sheet that is missing (cross-domain
 * embeds, an optimizer that renamed it) or fails to load does not hold the
 * render back.
 *
 * @param id Element id of the <link>, e.g. `notificationx-public-css`.
 */
export const whenStyled = (id: string): Promise<void> => new Promise((resolve) => {
    const link = document.getElementById(id) as HTMLLinkElement | null;
    if (!link || link.tagName !== 'LINK' || link.media !== 'print') {
        resolve();
        return;
    }
    const done = () => resolve();
    link.addEventListener('load', done, { once: true });
    link.addEventListener('error', done, { once: true });
    // The sheet may have finished between parsing and this call.
    if (link.sheet) {
        resolve();
    }
});

/**
 * Add a <link> for each style handle => URL that is not on the page yet.
 *
 * The ids match what WordPress prints (`{handle}-css`), so a stylesheet that is
 * already enqueued, or was added by an earlier call, is not added twice.
 *
 * @param styles Handle => URL map from the localized config, if any.
 */
export const loadExternalStyles = (styles?: Record<string, string> | unknown[] | null) => {
    // PHP encodes an empty list as [], which adds nothing.
    const list = (styles && typeof styles === 'object' ? styles : DEFAULT_EXTERNAL_STYLES) as Record<string, unknown>;
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
