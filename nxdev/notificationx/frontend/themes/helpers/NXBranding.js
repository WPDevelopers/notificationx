import React from "react";
import { __ } from "@wordpress/i18n";
import { Logo, NotificationText, BrandLogo } from ".";

// Newer Sales (Conversions) themes whose branding byline shows the full
// NotificationX brand logo instead of the plain "NotificationX" wordmark.
// Add new theme slugs here so future Sales themes inherit the same branding.
const BRAND_LOGO_THEMES = [
    "conv-theme-twelve",
    "conv-theme-thirteen",
    "conv-theme-fourteen",
    "conv-theme-fifteen",
    "conv-theme-sixteen",
];

export const NXBranding = (props) => {
    const theme = props?.config?.themes || "";
    const useBrandLogo = BRAND_LOGO_THEMES.some((slug) => theme.includes(slug));
    return (
        <small className="nx-branding">
            <Logo />
            <span className="nx-byline">{__("by", "notificationx")}</span>
            <a
                href={props?.config?.affiliate_link}
                rel="nofollow"
                target="_blank"
                className="nx-powered-by"
            >
                {useBrandLogo ? <BrandLogo /> : <NotificationText {...props} />}
            </a>
        </small>
    );
};

export default NXBranding;
