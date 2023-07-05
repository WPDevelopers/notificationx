import React, { CSSProperties } from "react";
import nxHelper from "./functions";
import useNotificationContext from "./NotificationProvider";

export const analyticsOnClick = (event, restUrl, config, credentials = true) => {
    const nx_id = config?.nx_id;
    const enable_analytics = config?.enable_analytics;

    if (!event.target?.href && (!event.delegateTarget || !event.delegateTarget.href)) {
        event.preventDefault();
        return false;
    }
    if(!enable_analytics){
        return;
    }

    const args: {[key: string]: any} = {};

    if(!credentials){
        args.credentials = 'same-origin';
    }

    nxHelper
        .post(restUrl, {
            nx_id,
            // entry_id,
            // link,
            // referrer: window.location.toString( ),
        }, args)
        .then((response) => {
            // console.log("response: ", response);
        })
        .catch((err) => console.error("Fetch Error: ", err));
}


const Analytics = ({config, children, ...rest}) => {
    const frontendContext = useNotificationContext();
    const restUrl = nxHelper.getPath(frontendContext.rest, `analytics/`);

    const styles:CSSProperties = {};

    if (config.link_button && config.advance_edit) {
        if(config.link_button_bg_color) {
            styles.backgroundColor = config.link_button_bg_color;
        }
        if(config.link_button_text_color) {
            styles.color = config.link_button_text_color;
        }
        if(config.link_button_font_size) {
            styles.fontSize = config.link_button_font_size;
        }
    }

    return (
        <a
            {...rest}
            style={styles}
            target={config?.link_open ? "_blank" : ""}
            onClick={e => analyticsOnClick(e, restUrl, config, frontendContext.rest.omit_credentials)}
        >{children}</a>
    );
};

export default Analytics;
