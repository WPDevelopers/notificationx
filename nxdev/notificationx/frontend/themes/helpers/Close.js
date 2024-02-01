import classNames from "classnames";
import React, { useEffect } from "react";
import cookie from "react-cookies";

function Close({id, config, dispatch, style, closed}) {
    if (!config?.close_button) return null;

    const handleCloseNotification = (e) => {
        let date = new Date();
        let options = {
            path: "/",
        };
        if (config?.close_forever) {
            const expired_timestamp = date.getTime() + 2 * 30 * 24 * 60 * 60 * 1000;
            options.expires = new Date(expired_timestamp);
        }
        else{
            // PressBar
            if(config?.evergreen_timer && config?.time_reset){
                const expired_timestamp = date.getTime() + 24 * 60 * 60 * 1000;
                options.expires = new Date(expired_timestamp);
            }
            // else{
            //     expired_timestamp = date.getTime() + 2 * 30 * 24 * 60 * 60 * 1000;
            // }
        }
        let countRand = config?.countdown_rand ? `-${config.countdown_rand}` : '';

        cookie.save("notificationx_" + config?.nx_id + countRand, true, options);

        dispatch({
            type: "REMOVE_NOTIFICATION",
            payload: id,
        });
        document.body.style.paddingTop = `0px`;
    };

    useEffect(() => {
        closed && handleCloseNotification();
    }, [closed])
    
    let componentClasses = ['notificationx-close'];
    if( config?.source === 'press_bar' ) {
        componentClasses.push('pressbar');
        if( config?.close_icon_position === 'top_left' ) {
            componentClasses.push('position-top-left');
        }else{
            componentClasses.push('position-top-right');
        }
    }

    return (
        <div className={ classNames(componentClasses) } style={style} onClick={handleCloseNotification}>
            <svg width="10px" height="10px" viewBox="0 0 48 48">
                <g stroke="none">
                    <g>
                        <path d="M28.228 23.986L47.092 5.122a2.998 2.998 0 000-4.242 2.998 2.998 0 00-4.242 0L23.986 19.744 5.121.88a2.998 2.998 0 00-4.242 0 2.998 2.998 0 000 4.242l18.865 18.864L.879 42.85a2.998 2.998 0 104.242 4.241l18.865-18.864L42.85 47.091a2.991 2.991 0 002.121.879 2.998 2.998 0 002.121-5.121L28.228 23.986z"></path>
                    </g>
                </g>
            </svg>
        </div>
    );
}

export default Close;
