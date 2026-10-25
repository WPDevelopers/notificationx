import { getThemeName } from "../../core/functions";

/**
 * Verified Cards (vc-*): the one list of theme slugs that render through
 * VerifiedCard. A slug is the theme key without its `<type>_` / `<source>_`
 * prefix (see getThemeName), e.g. `conversions_vc-receipt` -> `vc-receipt`.
 *
 * Add a slug here only together with its GetTemplate case, its VerifiedCard
 * layout and its SCSS root selector. A vc key whose slug is not listed (e.g. a
 * PHP theme entry shipped before its JS) takes the generic theme path and gets
 * no `nx-vc` root class.
 */
export const VC_SLUGS: string[] = ["vc-receipt", "vc-showcase"];

// The full key must end in `_vc-<word>`, anchored, so no existing key (and no
// key that merely contains "vc-") can match.
const VC_KEY_RE = /^[a-z0-9_]+_vc-[a-z]+$/;

/** The vc slug of a notification's settings, or null for every other theme. */
export const getVcSlug = (settings): string | null => {
    const key = settings?.themes;
    if (typeof key !== "string" || !VC_KEY_RE.test(key)) {
        return null;
    }
    const slug = getThemeName(settings);
    return VC_SLUGS.indexOf(slug) !== -1 ? slug : null;
};

/** True only for the whitelisted Verified Card theme keys. */
export const isVcTheme = (settings): boolean => getVcSlug(settings) !== null;

export default isVcTheme;
