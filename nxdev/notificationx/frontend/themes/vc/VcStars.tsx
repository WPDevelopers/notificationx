import React from "react";
import { __, sprintf } from "@wordpress/i18n";

/**
 * Verified Cards: the Scorecard stars. Not the shared Star.tsx, which uses
 * fixed ids and colours; these use currentColor (the CSS sets amber and the
 * light grey of the "off" stars) and carry one translatable label.
 */

const STAR_PATH = "M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z";

/**
 * The rating in a Scorecard slot ('<span>rating::4.4</span>' once Theme has
 * filled {{rating}}), clamped to 0-5, or null when there is no number.
 */
export const parseVcRating = (html?: string): number | null => {
    const match = /rating::(\d+(?:\.\d+)?)/.exec(typeof html === "string" ? html : "");
    if (!match) {
        return null;
    }
    const value = parseFloat(match[1]);
    if (!isFinite(value)) {
        return null;
    }
    return Math.min(5, Math.max(0, value));
};

/** The rating as shown next to the stars: "4.4", "5.0". */
export const formatVcRating = (rating: number) => rating.toFixed(1);

const VcStars = ({ rating }: { rating: number }) => {
    const on = Math.round(rating);
    const label = sprintf(
        /* translators: %s: the review rating, e.g. 4.5. */
        __("%s out of 5", "notificationx"),
        formatVcRating(rating)
    );
    const stars = [];
    for (let i = 1; i <= 5; i++) {
        stars.push(
            <svg
                key={i}
                className={i <= on ? "is-on" : "is-off"}
                viewBox="0 0 20 20"
                aria-hidden="true"
                focusable="false"
            >
                <path fill="currentColor" d={STAR_PATH} />
            </svg>
        );
    }
    return (
        <span className="nx-vc-stars" role="img" aria-label={label}>
            {stars}
        </span>
    );
};

export default VcStars;
