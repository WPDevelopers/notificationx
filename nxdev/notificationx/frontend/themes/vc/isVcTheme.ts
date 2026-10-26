import { getThemeName } from "../../core/functions";
import { nxApplyListFilter } from "../../core/hooks";

/**
 * Verified Cards (vc-*): the one list of the built-in theme slugs that render
 * through VerifiedCard. A slug is the theme key without its `<type>_` /
 * `<source>_` prefix (see getThemeName), e.g. `conversions_vc-receipt` ->
 * `vc-receipt`.
 *
 * Add a slug here only together with its GetTemplate case, its VerifiedCard
 * layout and its SCSS block. A vc key whose slug is not listed (e.g. a PHP
 * theme entry shipped before its JS) takes the generic theme path and gets
 * no `nx-vc` root class. Add-ons add their own slugs through
 * `nx_frontend_vc_slugs` (see getVcSlugs).
 */
export const VC_SLUGS: string[] = [
    "vc-receipt",
    "vc-showcase",
    "vc-thread",
    "vc-scorecard",
    "vc-counter",
    "vc-pledge",
    "vc-enrol",
    "vc-submission",
    "vc-ratings",
    "vc-installs",
];

// The full key must end in `_vc-<word>`, anchored, so no existing key (and no
// key that merely contains "vc-") can match.
const VC_KEY_RE = /^[a-z0-9_]+_vc-[a-z]+$/;

// An add-on slug has the same shape as a built-in one.
const VC_SLUG_RE = /^vc-[a-z]+$/;

/**
 * The built-in slugs plus the ones add-ons register through the
 * `nx_frontend_vc_slugs` filter (free x.x.x+). The filter gets a copy of
 * VC_SLUGS and returns the list with its own slugs appended. An add-on cannot
 * remove a built-in slug, and an entry that is not a `vc-<word>` string is
 * dropped. An add-on that adds a slug renders its card through
 * `nx_frontend_vc_card` (VerifiedCard.tsx).
 */
export const getVcSlugs = (): string[] => {
    const filtered = nxApplyListFilter("nx_frontend_vc_slugs", VC_SLUGS.slice());
    const slugs = VC_SLUGS.slice();
    filtered.forEach((slug) => {
        if (typeof slug === "string" && VC_SLUG_RE.test(slug) && slugs.indexOf(slug) === -1) {
            slugs.push(slug);
        }
    });
    return slugs;
};

/** The vc slug of a notification's settings, or null for every other theme. */
export const getVcSlug = (settings): string | null => {
    const key = settings?.themes;
    if (typeof key !== "string" || !VC_KEY_RE.test(key)) {
        return null;
    }
    const slug = getThemeName(settings);
    if (VC_SLUGS.indexOf(slug) !== -1) {
        return slug;
    }
    return getVcSlugs().indexOf(slug) !== -1 ? slug : null;
};

/** True for a built-in slug, false for an add-on slug or any other value. */
export const isBuiltInVcSlug = (slug: string): boolean => VC_SLUGS.indexOf(slug) !== -1;

/** True only for the whitelisted Verified Card theme keys. */
export const isVcTheme = (settings): boolean => getVcSlug(settings) !== null;

export default isVcTheme;
