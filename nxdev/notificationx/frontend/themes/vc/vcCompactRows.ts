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
        // [0] name, [1] action, [2] comment, [3] time (as comments theme-one)
        case "vc-thread":
            return [`${at(0)} ${at(1)}`, `${at(2)}`, `${at(3)}`];
        // [0] reviewer, [1] rating, [2] title, [3] text, [4] "on", [5] subject,
        // [6] time: "reviewer on subject", the stars row (Content renders
        // 'rating::N' as stars), the time.
        case "vc-scorecard":
            return [`${at(0)} ${at(4)} ${at(5)}`, `${at(1)}`, `${at(6)}`];
        // [0] name, [1] lead, [2] count, [3] call to action (as 7day-download)
        case "vc-counter":
            return [`${at(0)}`, `${at(1)} ${at(2)}`, `${at(3)}`];
        // [0] donor, [1] action, [2] amount, [3] campaign, [4] time
        // (as donation theme-one)
        case "vc-pledge":
            return [`${at(0)} ${at(1)} ${at(2)}`, `${at(3)}`, `${at(4)}`];
        // [0] name, [1] action, [2] course or form title, [3] time
        case "vc-enrol":
        case "vc-submission":
            return [`${at(0)} ${at(1)}`, `${at(2)}`, `${at(3)}`];
        // [0] count, [1] label, [2] subject, [3] Google's average: then the
        // stars row (Content renders 'rating::N' as stars), as total-rated:
        // Google's average, five stars elsewhere.
        case "vc-ratings":
            return [
                `${at(0)} ${at(1)}`,
                `${at(2)}`,
                /rating::\d/.test(at(3)) ? at(3) : "<span>rating::5</span>",
            ];
        // [0] count, [1] lead, [2] name (as actively_using)
        case "vc-installs":
            return [`${at(0)} ${at(1)}`, `${at(2)}`];
        default:
            return s;
    }
};

export default vcCompactRows;
