/**
 * Click-open popups: an Announcement popup whose "When to Open" is "click"
 * or "both" opens when a visitor clicks one of the owner's elements (CSS
 * classes or IDs) or a link to #nx-popup-<id>.
 *
 * One delegated listener on the document, so elements added to the page
 * later work too. A click always opens the popup, even if the visitor closed
 * it before; the popup only exists on pages where `/notice` sent it, so Show
 * On and the audience still apply.
 */

export type OpenOn = 'auto' | 'click' | 'both';

export const openOn = (config: any): OpenOn => {
    const value = config?.popup_open_on;
    return value === 'click' || value === 'both' ? value : 'auto';
};

/** Opens without a click (today's behaviour): "auto" and "both". The builder preview always shows it. */
export const opensAutomatically = (config: any): boolean => openOn(config) !== 'click' || !!config?.previewType;

/** Listens for clicks: "click" and "both". */
export const opensOnClick = (config: any): boolean => openOn(config) !== 'auto' && !config?.previewType;

/**
 * The owner's targets as safe selectors: classes and IDs only (a bare name
 * is a class), plus the #nx-popup-<id> link. Mirrors
 * PopupNotification::click_selectors() on the server.
 */
export const clickSelectors = (config: any): string[] => {
    const out: string[] = [];
    const id = parseInt(config?.nx_id, 10);
    String(config?.popup_click_selector || '')
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean)
        .forEach((part) => {
            const selector = part[0] === '.' || part[0] === '#' ? part : `.${part}`;
            if (/^[.#]-?[A-Za-z_][A-Za-z0-9_-]*$/.test(selector) && !out.includes(selector)) {
                out.push(selector);
            }
        });
    if (id > 0) {
        out.push(`a[href$="#nx-popup-${id}"]`);
    }
    return out;
};

/**
 * The clicked element that opens this popup (the target itself or one of
 * its ancestors), or null. Clicks inside NotificationX's own popups never count.
 */
export const findTrigger = (target: EventTarget | null, selectors: string[]): Element | null => {
    if (!target || !(target as Element).closest || !selectors.length) {
        return null;
    }
    const el = target as Element;
    if (el.closest('.nx-popup-overlay')) {
        return null;
    }
    for (const selector of selectors) {
        try {
            const found = el.closest(selector);
            if (found) {
                return found;
            }
        } catch (e) {
            // An invalid selector never stops the others.
        }
    }
    return null;
};

/** Elements that already turn Enter/Space into a click themselves. */
const NATIVE = /^(A|BUTTON|INPUT|SELECT|TEXTAREA|SUMMARY)$/;

/**
 * Listens on the document for the popups' targets. `open(config, trigger)`
 * is called with the clicked element. Returns a function that stops listening.
 */
export const listenForClicks = (configs: any[], open: (config: any, trigger: Element) => void, doc: Document = document) => {
    const entries = configs.filter(opensOnClick).map((config) => ({ config, selectors: clickSelectors(config) }));
    if (!entries.length) {
        return () => {};
    }
    const handle = (event: Event) => {
        for (const { config, selectors } of entries) {
            const trigger = findTrigger(event.target, selectors);
            if (trigger) {
                event.preventDefault();
                open(config, trigger);
                return;
            }
        }
    };
    const onKey = (event: KeyboardEvent) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }
        const target = event.target as Element;
        // Links and buttons already send a click for Enter (and Space on buttons).
        if (!target || NATIVE.test(target.tagName || '')) {
            return;
        }
        handle(event);
    };
    doc.addEventListener('click', handle, true);
    doc.addEventListener('keydown', onKey, true);
    return () => {
        doc.removeEventListener('click', handle, true);
        doc.removeEventListener('keydown', onKey, true);
    };
};

/** Popups on screen now (by nx_id), so a click never opens a second copy. */
const onScreen: Record<string, number> = {};

export const markOpen = (nxId: any) => {
    const key = String(nxId);
    onScreen[key] = (onScreen[key] || 0) + 1;
    return () => {
        onScreen[key] = Math.max(0, (onScreen[key] || 0) - 1);
    };
};

export const isOnScreen = (nxId: any): boolean => (onScreen[String(nxId)] || 0) > 0;

/** The element that opened each click-opened popup, to give focus back on close. */
const openers: Record<string, Element> = {};

export const rememberOpener = (nxId: any, el: Element) => {
    openers[String(nxId)] = el;
};

export const returnFocus = (nxId: any) => {
    const el = openers[String(nxId)] as HTMLElement | undefined;
    delete openers[String(nxId)];
    if (el && typeof el.focus === 'function' && el.isConnected !== false) {
        el.focus();
    }
};
