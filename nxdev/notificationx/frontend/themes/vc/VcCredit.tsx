import React from "react";
import { __ } from "@wordpress/i18n";
import VerifiedCheck from "./VerifiedCheck";
import { VcBell, VcWordmark } from "./VcBrandMark";

/**
 * The Verified Card credit: "✓ Verified by [bell] NotificationX".
 *
 * Same link as the existing byline (NXBranding): the affiliate URL,
 * rel="nofollow", target="_blank". The link has an aria-label because its
 * content is two decorative SVGs. Shown unless vcShowsCredit() says no.
 * `slim` only adds a class: the ≤480px card styles it as credit A.
 */

/**
 * Whether a Verified Card shows its credit line. The line is part of the
 * card's design, so it follows the notification's own "Verified by
 * NotificationX" switch (vc_show_credit, Design tab, on by default), not
 * Settings > Disable Powered By, which stays the opt-in for the other themes.
 * A notification saved before the switch existed has no value: shown.
 */
export const vcShowsCredit = (config: any): boolean => {
    const v = config?.vc_show_credit;
    return !(v === false || v === 0 || v === "0" || v === "false" || v === "");
};

const VcCredit = ({ config, slim = false }: { config: any; slim?: boolean }) => {
    if (!vcShowsCredit(config)) {
        return null;
    }
    return (
        <small
            className={`nx-branding nx-branding-verified nx-vc-credit${slim ? " is-slim" : ""}`}
        >
            <VerifiedCheck />
            <span className="nx-byline">{__("Verified by", "notificationx")}</span>
            <a
                href={config?.affiliate_link}
                rel="nofollow"
                target="_blank"
                className="nx-powered-by"
                aria-label="NotificationX"
            >
                <VcBell />
                <VcWordmark />
            </a>
        </small>
    );
};

export default VcCredit;
