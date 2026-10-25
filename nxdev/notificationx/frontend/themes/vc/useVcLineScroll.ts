import { useEffect } from "react";
import { VC_SCROLL, vcScrollPlan } from "./vcScrollPlan";

/**
 * Option C for the slim (≤480px) Verified Card: when line 1 does not fit, it
 * scrolls once to show its hidden end and stays there.
 *
 *  1. Bail (CSS "…" stays) when matchMedia or Element.animate is missing, or
 *     when the visitor prefers reduced motion.
 *  2. WAIT 800ms (an identity step, so it can be paused like the others).
 *  3. Only then measure: fits = stop; hidden > 232px = stop with "…".
 *  4. Add `is-run` to the message and translate the inner span by -d (LTR)
 *     or +d (RTL), linear, at the speed from vcScrollPlan.
 *  5. HOLD 800ms and stay at the end: no scroll back, no loop.
 *
 * Pointer/touch on the notification ROOT pauses and resumes it (including the
 * waits): the root also receives the events that land on the Analytics link
 * overlay. Reduced motion turned on mid-run stops it and restores the "…".
 * Only `transform` is animated. Runs once per mount (deps []), so the ~25ms
 * re-renders of Notification never restart it; unmount cleans up.
 */
const RM_QUERY = "(prefers-reduced-motion: reduce)";
const HOLD_ON = ["pointerenter", "touchstart"];
const HOLD_OFF = ["pointerleave", "touchend", "touchcancel"];

const useVcLineScroll = (msgRef: { current: any }, innerRef: { current: any }) => {
    useEffect(() => {
        const msg = msgRef.current;
        const inner = innerRef.current;
        if (!msg || !inner) return;
        if (typeof window === "undefined" || typeof window.matchMedia !== "function") return;
        if (typeof inner.animate !== "function") return;

        let rm: any = null;
        try {
            rm = window.matchMedia(RM_QUERY);
        } catch (e) {
            return;
        }
        if (rm && rm.matches) return;

        const root = (typeof msg.closest === "function" && msg.closest(".nx-notification")) || msg;

        let token = 0;
        let held = false;
        let cur: any = null;
        let anims: any[] = [];

        const stop = () => {
            token++;
            anims.forEach((a) => {
                try {
                    a.cancel();
                } catch (e) {
                    // already gone
                }
            });
            anims = [];
            cur = null;
            msg.classList.remove("is-run");
        };

        // One linear, fill-forwards step. A step created while held starts
        // paused. Returns its `finished` promise, or null (and stops) when the
        // browser has no Animation.finished.
        const step = (keyframes, duration) => {
            const a = inner.animate(keyframes, { duration, easing: "linear", fill: "forwards" });
            if (!a) {
                stop();
                return null;
            }
            anims.push(a);
            cur = a;
            if (held) {
                try {
                    a.pause();
                } catch (e) {
                    // ignore
                }
            }
            if (!a.finished || typeof a.finished.then !== "function") {
                stop();
                return null;
            }
            return a.finished;
        };

        const hold = (on: boolean) => {
            held = on;
            if (!cur) return;
            try {
                if (on && cur.playState === "running") cur.pause();
                else if (!on && cur.playState === "paused") cur.play();
            } catch (e) {
                // ignore
            }
        };
        const onHold = () => hold(true);
        const onRelease = () => hold(false);
        HOLD_ON.forEach((type) => root.addEventListener(type, onHold, { passive: true }));
        HOLD_OFF.forEach((type) => root.addEventListener(type, onRelease, { passive: true }));

        const onMotionChange = () => {
            if (rm && rm.matches) stop();
        };
        if (rm) {
            if (typeof rm.addEventListener === "function") rm.addEventListener("change", onMotionChange);
            else if (typeof rm.addListener === "function") rm.addListener(onMotionChange);
        }

        const me = token;
        const Z = "translateX(0)";
        const wait = step([{ transform: Z }, { transform: Z }], VC_SCROLL.WAIT);
        if (wait) {
            wait.then(() => {
                if (me !== token) return;
                // Measure only now: a late web font can change the width.
                if (msg.scrollWidth <= msg.clientWidth) {
                    stop();
                    return;
                }
                const d = Math.max(
                    Math.ceil(inner.getBoundingClientRect().width - msg.getBoundingClientRect().width),
                    msg.scrollWidth - msg.clientWidth
                );
                const plan = vcScrollPlan(d);
                if (!plan) {
                    stop();
                    return;
                }
                const rtl = window.getComputedStyle(msg).direction === "rtl";
                const X = `translateX(${rtl ? d : -d}px)`;
                msg.classList.add("is-run");
                const move = step([{ transform: Z }, { transform: X }], plan.t);
                if (!move) return;
                return move.then(() => {
                    if (me !== token) return;
                    return step([{ transform: X }, { transform: X }], VC_SCROLL.HOLD) || undefined;
                });
            }).then(
                () => {
                    if (me === token) cur = null;
                },
                () => {
                    // A cancelled step rejects `finished`; nothing to do.
                }
            );
        }

        return () => {
            HOLD_ON.forEach((type) => root.removeEventListener(type, onHold));
            HOLD_OFF.forEach((type) => root.removeEventListener(type, onRelease));
            if (rm) {
                if (typeof rm.removeEventListener === "function") rm.removeEventListener("change", onMotionChange);
                else if (typeof rm.removeListener === "function") rm.removeListener(onMotionChange);
            }
            stop();
        };
    }, []);
};

export default useVcLineScroll;
