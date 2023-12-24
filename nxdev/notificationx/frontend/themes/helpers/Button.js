import React from "react";
import { __ } from "@wordpress/i18n";
import { analyticsOnClick } from "../../core/Analytics";
import nxHelper from "../../core/functions";
import { useNotificationContext } from "../../core";

const Button = ({ data,config }) => {
    const { themes, link, announcement_link_button, announcement_link_button_text } = config;
    if( !announcement_link_button && ![ "announcements_theme-15", "announcements_theme-14" ].includes(themes) ) {
        return (<></>);
    }
    const frontendContext = useNotificationContext();
    const restUrl = nxHelper.getPath(frontendContext.rest, `analytics/`);
    return (
        <a
                href={ link }
                target={config?.link_open ? "_blank" : ""}
                onClick={e => analyticsOnClick(e, restUrl, config, frontendContext.rest.omit_credentials)}
            >
            { announcement_link_button_text }
        </a>
    )
};

export default Button;
