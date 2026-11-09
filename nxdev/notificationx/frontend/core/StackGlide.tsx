import React from "react";

// 84710: stacked cards glide (FLIP) to their new place when a card in the same corner
// appears or leaves, instead of jumping.

export const GLIDE_MS = 250;
export const GLIDE_EASING = "cubic-bezier(.22,.61,.36,1)";

/**
 * Cards that are in the stack both before and after the change and moved by at least
 * 1px. New cards (own entrance) and removed cards (own exit) are skipped, and so is
 * any move larger than `maxDelta`, which can only come from a stale measurement.
 */
export const computeGlides = (
    before: Map<string, number>,
    after: Map<string, number>,
    maxDelta: number
): { key: string; dy: number }[] => {
    const glides = [];
    after.forEach((y, key) => {
        if (!before.has(key)) {
            return;
        }
        const dy = before.get(key) - y;
        if (Math.abs(dy) >= 1 && Math.abs(dy) <= maxDelta) {
            glides.push({ key, dy });
        }
    });
    return glides;
};

/**
 * The glide adds to the card's own transform (entrance, exit, slide-outs) through
 * `composite: 'add'`. Without that support, or with reduced motion, cards jump as before.
 */
export const canGlide = (win: any = typeof window !== "undefined" ? window : undefined): boolean => {
    if (!win || typeof win.Element?.prototype?.animate !== "function" || typeof win.KeyframeEffect !== "function") {
        return false;
    }
    if (!("composite" in win.KeyframeEffect.prototype)) {
        return false;
    }
    return !win.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches;
};

/**
 * Viewport y of each card, keyed by data-nx-key: the container's top plus the card's
 * offsetTop. offsetTop ignores transforms, so a card mid-entrance or mid-exit reads its
 * real slot. The corner containers are position: fixed, so they are the offsetParent.
 */
export const readPositions = (container: HTMLElement | null): Map<string, number> => {
    const positions = new Map<string, number>();
    if (!container) {
        return positions;
    }
    const top = container.getBoundingClientRect().top;
    Array.from(container.children).forEach((el: any) => {
        const key = el?.dataset?.nxKey;
        if (key) {
            positions.set(key, top + el.offsetTop);
        }
    });
    return positions;
};

type Props = {
    // Ids of the cards in this corner, in order; a change here means the stack changed.
    itemKeys: string;
    enabled: boolean;
    // Renders the corner container and attaches the ref to it. No extra DOM.
    children: (ref: React.RefObject<HTMLDivElement>) => React.ReactNode;
};

/**
 * A class component because getSnapshotBeforeUpdate is the React API that reads the DOM
 * after render but before the update is applied ("First"), in every React version and
 * rendering mode. componentDidUpdate then measures the new layout ("Last") and plays the
 * difference ("Invert" + "Play").
 */
export default class StackGlide extends React.Component<Props> {
    container = React.createRef<HTMLDivElement>();

    getSnapshotBeforeUpdate(prevProps: Props): Map<string, number> | null {
        if (!this.props.enabled || prevProps.itemKeys === this.props.itemKeys) {
            return null;
        }
        return readPositions(this.container.current);
    }

    componentDidUpdate(prevProps: Props, prevState: unknown, before: Map<string, number> | null) {
        if (!before || before.size === 0 || !canGlide()) {
            return;
        }
        const container = this.container.current;
        const glides = computeGlides(before, readPositions(container), window.innerHeight);
        glides.forEach(({ key, dy }) => {
            const el = Array.from(container.children).find((child: any) => child?.dataset?.nxKey === key) as HTMLElement;
            el?.animate(
                [{ transform: `translateY(${dy}px)` }, { transform: "none" }],
                { duration: GLIDE_MS, easing: GLIDE_EASING, composite: "add" } as KeyframeAnimationOptions
            );
        });
    }

    render() {
        return this.props.children(this.container);
    }
}
