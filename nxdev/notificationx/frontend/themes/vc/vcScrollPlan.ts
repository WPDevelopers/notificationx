/**
 * Option C timing for the slim (≤480px) Verified Card line 1. Pure: no imports.
 *
 * One pass: wait WAIT ms, scroll the hidden part `d` (px) at VMIN px/s, raised
 * so the scroll fits in WIN ms and capped at VMAX px/s, hold HOLD ms, then stay
 * at the end. More than DMAX px hidden (VMAX * WIN) means no scroll at all:
 * the line keeps its "…". Total ≤ WAIT + WIN + HOLD = 4.5s (plus paused time).
 */
export const VC_SCROLL = {
    WAIT: 800,
    HOLD: 800,
    VMIN: 40,
    VMAX: 80,
    WIN: 2900,
    DMAX: 232,
};

export interface VcScrollPlan {
    /** Hidden width to scroll, px. */
    d: number;
    /** Speed, px/s. */
    v: number;
    /** Scroll duration, ms. */
    t: number;
    /** WAIT + t + HOLD, ms. */
    total: number;
}

export const vcScrollPlan = (d: number): VcScrollPlan | null => {
    if (!(d >= 1) || d > VC_SCROLL.DMAX) {
        return null;
    }
    const v = Math.min(VC_SCROLL.VMAX, Math.max(VC_SCROLL.VMIN, (d * 1000) / VC_SCROLL.WIN));
    const t = Math.round((d / v) * 1000);
    return { d, v, t, total: VC_SCROLL.WAIT + t + VC_SCROLL.HOLD };
};

export default vcScrollPlan;
