import React from "react";
import { __ } from "@wordpress/i18n";
import VerifiedCheck from "./VerifiedCheck";
import { VcBell, VcWordmark } from "./VcBrandMark";

/**
 * The Verified Card credit: "✓ Verified by [bell] NotificationX".
 *
 * Same link as the existing byline (NXBranding): the affiliate URL,
 * rel="nofollow", target="_blank". The link has an aria-label because its
 * content is two decorative SVGs. Not rendered when "Powered by" is disabled.
 * `slim` only adds a class: the ≤480px card styles it as credit A.
 */
const VcCredit = ({ config, slim = false }: { config: any; slim?: boolean }) => {
    if (config?.disable_powered_by) {
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
