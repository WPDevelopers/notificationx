import React, { CSSProperties } from "react";
import useNotificationContext from "./NotificationProvider";
import nxHelper from "./functions";

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


const Analytics = ({config, ...rest}) => {
    const frontendContext = useNotificationContext();
    const restUrl = nxHelper.getPath(frontendContext.rest, `analytics/`);
    const data:any = rest.data || {}
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

    // Configure link
    let link;
    let link_text;
    switch (config.link_type) {
        case 'yt_video_link':
            link = data?.yt_video_link;
            link_text = config?.link_button_text_video;
            break;
        case 'yt_channel_link':
            link = data?.yt_channel_link;
            link_text = config?.link_button_text_channel;
            break;
        default:
            link = data?.link
            link_text = config?.link_button_text;
            break;
    }
    
    return (
         <>
            { link && 
             <a
                {...rest}
                href={ link }
                style={styles}
                target={config?.link_open ? "_blank" : ""}
                onClick={e => analyticsOnClick(e, restUrl, config, frontendContext.rest.omit_credentials)}
            >{ config.link_button ? link_text: '' }</a> }
         </>
    );
};

export default Analytics;
