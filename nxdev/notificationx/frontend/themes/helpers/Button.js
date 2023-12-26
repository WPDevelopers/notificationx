import React from "react";
import { __ } from "@wordpress/i18n";
import { analyticsOnClick } from "../../core/Analytics";
import nxHelper from "../../core/functions";
import { useNotificationContext } from "../../core";

const Button = ({ data, config, announcementCSS = '', icon = false }) => {
    const { themes, link, announcement_link_button, announcement_link_button_text } = config;
    if (!announcement_link_button && !["announcements_theme-15", "announcements_theme-14"].includes(themes)) {
        return (<></>);
    }
    const frontendContext = useNotificationContext();
    const restUrl = nxHelper.getPath(frontendContext.rest, `analytics/`);
    return (
        <a
            href={link}
            target={config?.link_open ? "_blank" : ""}
            onClick={e => analyticsOnClick(e, restUrl, config, frontendContext.rest.omit_credentials)}
            style={{
                ...(announcementCSS?.linkButtonTextColor && { color: announcementCSS.linkButtonTextColor }),
                ...(announcementCSS?.linkButtonBgColor && { backgroundColor: announcementCSS.linkButtonBgColor }),
                ...(announcementCSS?.linkButtonFontSize && { fontSize: announcementCSS.linkButtonFontSize }),
            }}
        >
            { icon && 
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect width="16" height="16" rx="8" fill={ announcementCSS?.linkButtonTextColor ? announcementCSS?.linkButtonTextColor : "white" } />
                <path d="M11 8L6.5 10.5981L6.5 5.40192L11 8Z"  fill={ announcementCSS?.linkButtonBgColor ? announcementCSS?.linkButtonBgColor : "#FF0000" } />
            </svg>
            }
            {announcement_link_button_text}
        </a>
    )
};

export default Button;
