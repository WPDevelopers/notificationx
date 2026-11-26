import React from "react";
import { __, sprintf } from "@wordpress/i18n";

/**
 * Verified Cards: the stars of Scorecard and Ratings. Not the shared Star.tsx,
 * which uses fixed ids and colours; these use currentColor (the CSS sets amber
 * and the light grey of the empty part) and carry one translatable label.
 *
 * Partial fill, as Google shows it: each star is a grey star with an amber copy
 * on top, clipped to the star's share of the rating (4.7 = four full stars and
 * the fifth 70% filled, not rounded). The amber part starts at the inline
 * start, so in RTL it fills from the right (CSS).
 */

const STAR_PATH = "M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9z";

/**
 * The rating in a slot ('<span>rating::4.4</span>' once Theme has filled
 * {{rating}}), clamped to 0-5, or null when there is no number.
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

/** Fill of star `i` (1-5) in percent, 0-100, rounded to 1 decimal place. */
export const vcStarFill = (rating: number, i: number) =>
    Math.round(Math.min(1, Math.max(0, rating - (i - 1))) * 1000) / 10;

const StarSvg = () => (
    <svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
        <path fill="currentColor" d={STAR_PATH} />
    </svg>
);

const VcStars = ({ rating }: { rating: number }) => {
    const label = sprintf(
        /* translators: %s: the review rating, e.g. 4.5. */
        __("%s out of 5", "notificationx"),
        formatVcRating(rating)
    );
    const stars = [];
    for (let i = 1; i <= 5; i++) {
        const fill = vcStarFill(rating, i);
        stars.push(
            <span
                key={i}
                className={`nx-vc-star ${fill >= 100 ? "is-on" : fill <= 0 ? "is-off" : "is-part"}`}
            >
                <span className="nx-vc-star-bg">
                    <StarSvg />
                </span>
                {fill > 0 && (
                    <span className="nx-vc-star-fg" style={{ width: `${fill}%` }}>
                        <StarSvg />
                    </span>
                )}
            </span>
        );
    }
    return (
        <span className="nx-vc-stars" role="img" aria-label={label}>
            {stars}
        </span>
    );
};

export default VcStars;
