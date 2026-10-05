/**
 * Event beacon for the Analytics Audience reports (see Core\Tracker in PHP).
 *
 * Works for every notification type, including add-on ones, without the
 * components calling it: it watches the DOM for elements carrying
 * `data-nx-id` (the root of each notification) and reports
 *   - view:   at least half of the notification on screen for a second
 *             (once per notification per page load);
 *   - click:  a link or button inside it, or the whole card;
 *   - close:  its close button (once per page load);
 *   - submit: a form inside it;
 *   - hover:  the mouse rested on it for half a second (once per page;
 *             skipped on touch screens, where a tap fakes a hover).
 * With `ga` on, every event is also sent to Google Analytics 4 (gtag) or
 * Google Tag Manager (dataLayer) as nx_view, nx_click, ….
 * Events are batched and sent with sendBeacon/keepalive fetch, so nothing
 * here delays the page. Respects Global Privacy Control.
 *
 * Frontend import boundary: dependency-free.
 */

export type TrackConfig = { url: string; token: string; ga?: boolean };
export type TrackEvent = 'view' | 'click' | 'close' | 'submit' | 'hover';

const VIEW_RATIO = 0.5;
const VIEW_DWELL_MS = 1000;
const FLUSH_DELAY_MS = 2000;
const MAX_BATCH = 20;
const CLICK_DEDUPE_MS = 400;
const HOVER_DWELL_MS = 500;

export const CLOSE_SELECTOR = '.notificationx-close, .nx-close, .nx-popup-close, .nx-exit-intent-close, .nx-gdpr-close';
const IGNORE_CLICK_SELECTOR = '.nx-powered-by, input, textarea, select, label';

let config: TrackConfig | null = null;
let queue: { n: number; e: TrackEvent }[] = [];
let flushTimer: ReturnType<typeof setTimeout> | null = null;
let io: IntersectionObserver | null = null;
const once = new Set<string>();
const lastClick = new Map<number, number>();
const dwell = new Map<Element, ReturnType<typeof setTimeout>>();
let mo: MutationObserver | null = null;

const nxIdOf = (el: Element | null): number => {
    const v = el ? parseInt(el.getAttribute('data-nx-id') || '', 10) : NaN;
    return v > 0 ? v : 0;
};

/**
 * Queue one event. Safe to call before tracking starts (it is then dropped)
 * and from add-ons through window.nxFrontendRuntime.
 */
export const trackEvent = (nxId: number, event: TrackEvent) => {
    nxId = Number(nxId);
    if (!config || !(nxId > 0)) return;
    if (event === 'view' || event === 'close' || event === 'hover') {
        const key = `${event}:${nxId}`;
        if (once.has(key)) return;
        once.add(key);
    }
    if (event === 'click') {
        // A CTA can be reported by its own handler and by the delegated
        // listener below; count one click.
        const now = Date.now();
        if (now - (lastClick.get(nxId) || 0) < CLICK_DEDUPE_MS) return;
        lastClick.set(nxId, now);
    }
    queue.push({ n: nxId, e: event });
    if (config.ga) forwardToGa(nxId, event);
    if (queue.length >= MAX_BATCH) {
        flush();
    } else if (!flushTimer) {
        flushTimer = setTimeout(() => flush(), FLUSH_DELAY_MS);
    }
};

export const flush = (unloading = false) => {
    if (flushTimer) {
        clearTimeout(flushTimer);
        flushTimer = null;
    }
    if (!config || !queue.length) return;
    const batch = queue.splice(0, MAX_BATCH);
    const body = JSON.stringify({
        t: config.token,
        p: window.location.href,
        r: document.referrer,
        w: window.innerWidth,
        ev: batch,
    });
    let sent = false;
    if (unloading && navigator.sendBeacon) {
        try {
            sent = navigator.sendBeacon(config.url, new Blob([body], { type: 'application/json' }));
        } catch (e) {
            sent = false;
        }
    }
    if (!sent && typeof fetch === 'function') {
        // No credentials: the endpoint is anonymous and must stay cacheable-safe.
        fetch(config.url, { method: 'POST', body, keepalive: true, credentials: 'omit', headers: { 'Content-Type': 'application/json' } }).catch(() => undefined);
    }
    if (queue.length) flush(unloading);
};

/** Send the event to the site's own Google Analytics, if it is on the page. */
const forwardToGa = (nxId: number, event: TrackEvent) => {
    const w = window as any;
    const params = { notification_id: nxId, event_category: 'NotificationX' };
    try {
        if (typeof w.gtag === 'function') {
            w.gtag('event', `nx_${event}`, params);
        } else if (Array.isArray(w.dataLayer)) {
            w.dataLayer.push({ event: `nx_${event}`, ...params });
        }
    } catch (e) {
        // Never let a broken analytics snippet break the notification.
    }
};

const hoverTimers = new Map<Element, ReturnType<typeof setTimeout>>();
const canHover = () => !(window.matchMedia && window.matchMedia('(hover: none)').matches);

const onMouseOver = (e: MouseEvent) => {
    const root = (e.target as Element | null)?.closest?.('[data-nx-id]');
    if (!root || hoverTimers.has(root) || !canHover()) return;
    const nxId = nxIdOf(root);
    if (!nxId || once.has(`hover:${nxId}`)) return;
    hoverTimers.set(root, setTimeout(() => {
        hoverTimers.delete(root);
        trackEvent(nxId, 'hover');
    }, HOVER_DWELL_MS));
};

const onMouseOut = (e: MouseEvent) => {
    const root = (e.target as Element | null)?.closest?.('[data-nx-id]');
    if (!root) return;
    // Still inside the same notification (moving between its children).
    if (e.relatedTarget instanceof Node && root.contains(e.relatedTarget)) return;
    const t = hoverTimers.get(root);
    if (t) {
        clearTimeout(t);
        hoverTimers.delete(root);
    }
};

/** Hidden by CSS even though it is in the viewport (opacity 0, visibility hidden). */
const isHidden = (el: Element): boolean => {
    for (let node: Element | null = el; node && node !== document.body; node = node.parentElement) {
        const cs = window.getComputedStyle(node);
        if (cs.visibility === 'hidden' || cs.display === 'none' || parseFloat(cs.opacity) < 0.1) return true;
    }
    return false;
};

const onIntersect = (entries: IntersectionObserverEntry[]) => {
    entries.forEach((entry) => {
        const el = entry.target;
        const vh = window.innerHeight || document.documentElement.clientHeight;
        // Elements taller than the screen can never be half visible; count
        // them once they fill half of it.
        const enough = entry.isIntersecting && (entry.intersectionRatio >= VIEW_RATIO || entry.intersectionRect.height >= vh * VIEW_RATIO);
        const pending = dwell.get(el);
        if (enough && !pending) {
            dwell.set(el, setTimeout(() => {
                dwell.delete(el);
                if (!el.isConnected || document.visibilityState !== 'visible' || isHidden(el)) return;
                trackEvent(nxIdOf(el), 'view');
                io?.unobserve(el);
            }, VIEW_DWELL_MS));
        } else if (!enough && pending) {
            clearTimeout(pending);
            dwell.delete(el);
        }
    });
};

const observe = (root: Element) => {
    const els: Element[] = root.matches?.('[data-nx-id]') ? [root] : [];
    root.querySelectorAll?.('[data-nx-id]').forEach((el) => els.push(el));
    els.forEach((el) => {
        if ((el as any).__nxTracked) return;
        (el as any).__nxTracked = true;
        if (!once.has(`view:${nxIdOf(el)}`)) io?.observe(el);
    });
};

const onClick = (e: MouseEvent) => {
    const target = e.target as Element | null;
    const root = target?.closest?.('[data-nx-id]');
    const nxId = nxIdOf(root || null);
    if (!nxId) return;
    if (target.closest(CLOSE_SELECTOR) || (target === root && root.classList.contains('nx-exit-intent-overlay'))) {
        trackEvent(nxId, 'close');
        return;
    }
    if (target.closest(IGNORE_CLICK_SELECTOR)) return;
    const control = target.closest('a[href], button, [role="button"]');
    // Submit buttons are reported by the submit listener.
    if (control && control.tagName === 'BUTTON' && control.closest('form') && (control as HTMLButtonElement).type !== 'button') return;
    if (control) trackEvent(nxId, 'click');
    // Whole-card clicks are reported by the card's own handler (recordAnalyticsClick).
};

const onSubmit = (e: Event) => {
    const nxId = nxIdOf((e.target as Element | null)?.closest?.('[data-nx-id]') || null);
    if (nxId) trackEvent(nxId, 'submit');
};

const onVisibility = () => document.visibilityState === 'hidden' && flush(true);
const onPageHide = () => flush(true);

/**
 * Start once per page. Does nothing without a config (analytics off,
 * cross-domain embed), with Global Privacy Control on, or in a browser
 * without the observers.
 */
export const startTracking = (cfg?: Partial<TrackConfig> | null) => {
    if (config || !cfg?.url || !cfg?.token) return;
    if ((navigator as any).globalPrivacyControl === true) return;
    if (typeof IntersectionObserver === 'undefined' || typeof MutationObserver === 'undefined' || !document.body) return;
    config = { url: cfg.url, token: cfg.token, ga: !!cfg.ga };
    io = new IntersectionObserver(onIntersect, { threshold: [0, 0.25, VIEW_RATIO, 0.75, 1] });
    observe(document.body);
    mo = new MutationObserver((mutations) => {
        mutations.forEach((m) => m.addedNodes.forEach((node) => node.nodeType === 1 && observe(node as Element)));
    });
    mo.observe(document.body, { childList: true, subtree: true });
    document.addEventListener('click', onClick, true);
    document.addEventListener('submit', onSubmit, true);
    document.addEventListener('mouseover', onMouseOver, true);
    document.addEventListener('mouseout', onMouseOut, true);
    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('pagehide', onPageHide);
};

/** Test helper. */
export const __resetTracking = () => {
    config = null;
    queue = [];
    once.clear();
    lastClick.clear();
    dwell.forEach((t) => clearTimeout(t));
    dwell.clear();
    io?.disconnect();
    io = null;
    mo?.disconnect();
    mo = null;
    document.removeEventListener('click', onClick, true);
    document.removeEventListener('submit', onSubmit, true);
    document.removeEventListener('mouseover', onMouseOver, true);
    document.removeEventListener('mouseout', onMouseOut, true);
    hoverTimers.forEach((t) => clearTimeout(t));
    hoverTimers.clear();
    document.removeEventListener('visibilitychange', onVisibility);
    window.removeEventListener('pagehide', onPageHide);
    if (flushTimer) clearTimeout(flushTimer);
    flushTimer = null;
};
