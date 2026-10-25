/**
 * Turns a Verified Card slot array (GetTemplate's vc cases) into at most three
 * classic rows for the generic Image/Content/Close path. Used where the vc
 * card itself is not rendered:
 *  - NotificationX Pro's mobile card (Theme with is_mobile = true);
 *  - the admin Advanced Template default (nx_adv_template_default).
 *
 * The rows follow theme-one: "name + action", "product", "time". Unknown
 * slugs get their slots back unchanged.
 */
const vcCompactRows = (themeName: string, slots: string[]): string[] => {
    const s = Array.isArray(slots) ? slots : [];
    const at = (i: number) => s[i] || "";
    switch (themeName) {
        // [0] name, [1] action, [2] product, [3] time, [4] Freemius plan · price
        case "vc-receipt":
            return [`${at(0)} ${at(1)}`, `${at(2)} ${at(4)}`, `${at(3)}`];
        // [0] action, [1] product, [2] name, [3] {{city_country}}, [4] time
        case "vc-showcase":
            return [`${at(2)} ${at(0)}`, `${at(1)}`, `${at(4)}`];
        default:
            return s;
    }
};

export default vcCompactRows;
