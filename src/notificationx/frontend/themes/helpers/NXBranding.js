import React from "react";
import { __ } from "@wordpress/i18n";
import { Logo, NotificationText } from ".";

export const NXBranding = (props) => {
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
                <NotificationText {...props} />
            </a>
        </small>
    );
};

export default NXBranding;
