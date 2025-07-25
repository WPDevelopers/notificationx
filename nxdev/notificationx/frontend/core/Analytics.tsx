import React, { CSSProperties, ReactNode, useEffect } from "react";
import useNotificationContext from "./NotificationProvider";
import nxHelper, { handleCloseNotification } from "./functions";
import { getIconUrl } from "../../core/functions";

export const analyticsOnClick = (event, restUrl, config, dispatch, credentials = true) => {
    const nx_id = config?.nx_id;
    const enable_analytics = config?.enable_analytics;
    handleCloseNotification(config, nx_id, dispatch);

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

type AnalyticsProps = {
    config: any;
    children?: ReactNode;
    href?: string;
    data?: {[key: string]: any;};
    [key: string]: any;
};

const Analytics = ({config, children = null, href = null, data = {}, dispatch = null, ...rest}: AnalyticsProps) => {
    const frontendContext = useNotificationContext();
    const restUrl = nxHelper.getPath(frontendContext.rest, `analytics/`);
    const styles:CSSProperties = {};

    if (config.advance_edit) {
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
    let link = href;
    if( data?.link ) {
        link = data.link;
    }

    let link_text;
    let show_default_subscribe = false;

    switch (config.link_type) {
        case 'yt_video_link':
            link = data?.yt_video_link;
            if( config?.link_button_text ) {
                link_text = config.link_button_text;
            }else if( config?.link_button_text_video ) {
                link_text = config?.link_button_text_video;
            }
            break;
        case 'yt_channel_link':
            show_default_subscribe = true;
            link_text = config?.link_button_text_channel;
            break;
        case 'announcements_link':
            show_default_subscribe = true;
            link_text = config?.announcement_link_button_text;
            break;
        default:
            link_text = config?.link_button_text;
            break;
    }

    useEffect(() => {
        const script = document.createElement('script');
        script.src = 'https://apis.google.com/js/platform.js';
        script.async = true;
        document.body.appendChild(script);
        return () => {
          document.body.removeChild(script);
        };
    }, []);
    const iconUrl = getIconUrl(config.button_icon);

    if( config.source == 'press_bar' ) {
        return (
            <>
                <div  className="notificationx-link-wrapper" style={styles}>
                    <a
                        href={ link }
                        target={config?.link_open ? "_blank" : ""}
                        onClick={e => analyticsOnClick(e, restUrl, config, dispatch, frontendContext.rest.omit_credentials)}
                        {...rest}
                    >
                        { (config?.button_icon && config?.button_icon !== 'none') && (
                            <img
                                src={iconUrl}
                                style={{ width: 24, height: 24, marginRight: 8 }}
                            />
                        )}
                        { config.link_text ? link_text : '' } {children}
                    </a>
                </div>
            </>
        );
    }

    return (
        <>
           { ( data?.id && config?.nx_subscribe_button_type === 'yt_default' && show_default_subscribe && config.link_button ) ?
            <div className="yt-notificationx-link" >
                <div
                    style={styles}
                    className="g-ytsubscribe"
                    data-channelid={ data.id }
                    data-layout="default"
                    data-count="default">
                </div>
            </div> : (link && ( config.link_type !== 'none' ) ) &&
            <div  className="notificationx-link-wrapper">
                <a
                    href={ link }
                    style={styles}
                    target={config?.link_open ? "_blank" : ""}
                    onClick={e => analyticsOnClick(e, restUrl, config, frontendContext.rest.omit_credentials)}
                    {...rest}
                >
                    { config.link_button ? link_text : '' } {children}
                </a>
                { (config.link_button && config.link_type === 'yt_channel_link' && data?.yt_subscribers ) && <span> { data.yt_subscribers } </span> }
            </div>
           }
        </>
    );
};

export default Analytics;
