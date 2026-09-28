import nxHelper from "./functions";

/**
 * Space reserved for the top bar in <head> (FrontEnd\BarSpace), so the page
 * does not shift down when the bar mounts. It is released once the bar has set
 * its own body padding, or when no bar is shown.
 */
const RESERVE_ID = "nx-bar-reserve";
const OFF_CLASS = "nx-bar-reserve-off";
// Viewport buckets — keep in sync with BarSpace::BUCKETS.
const BUCKETS: [string, number][] = [["xs", 480], ["sm", 768], ["md", 1024], ["lg", 1440]];

const bucket = (width: number) => (BUCKETS.find(([, max]) => width < max) || ["xl"])[0];

export const hasBarReserve = () =>
    !!document.getElementById(RESERVE_ID) &&
    !document.documentElement.classList.contains(OFF_CLASS);

export const releaseBarReserve = () => {
    document.documentElement.classList.add(OFF_CLASS);
};

let reportTimer: ReturnType<typeof setTimeout>;

/**
 * The bar's height once the page has loaded and fonts are in, or 0 if it is
 * still changing (block styles or images arriving) or the bar is hidden.
 */
const settledHeight = async (nxId): Promise<number> => {
    if (document.readyState !== "complete") {
        await new Promise((resolve) => window.addEventListener("load", resolve, { once: true }));
    }
    try {
        await (document as any).fonts?.ready;
    } catch (e) {}
    const bar = document.getElementById(`nx-bar-${nxId}`);
    const first = bar?.offsetHeight || 0;
    await new Promise((resolve) => setTimeout(resolve, 500));
    const second = bar?.offsetHeight || 0;
    return first === second ? second : 0;
};

/**
 * Send the bar's settled height so later page loads can reserve it. Debounced,
 * since the height is recalculated a few times while the bar loads; measured
 * again once the page has settled rather than trusting that first value;
 * skipped when the reservation already matches, and sent once per session per
 * width bucket. The server reserves the median of several reports.
 */
export const reportBarHeight = (rest, settings, height: number) => {
    clearTimeout(reportTimer);
    if (!rest?.root || !settings?.nx_id || !height || height > 400) return;
    try {
        // Only the site that owns the bar stores its height (not cross-site bars).
        if (new URL(rest.root).origin !== window.location.origin) return;
    } catch (e) {
        return;
    }

    reportTimer = setTimeout(async () => {
        const measured = await settledHeight(settings.nx_id);
        if (!measured || measured > 400) return;

        const width = window.innerWidth;
        const key = bucket(width);
        const el = document.getElementById(RESERVE_ID);
        let reserved = 0;
        if (el && el.dataset.nxId === String(settings.nx_id)) {
            try {
                reserved = +(JSON.parse(el.dataset.heights || "{}")[key] || 0);
            } catch (e) {}
        }
        if (Math.abs(reserved - measured) < 2) return;

        const sessionKey = `nx-bar-height-${settings.nx_id}-${key}`;
        try {
            if (window.sessionStorage.getItem(sessionKey)) return;
            window.sessionStorage.setItem(sessionKey, "1");
        } catch (e) {}

        nxHelper
            .post(nxHelper.getPath(rest, "bar-height/"), { nx_id: +settings.nx_id, width, height: measured })
            .catch(() => {});
    }, 1500);
};
