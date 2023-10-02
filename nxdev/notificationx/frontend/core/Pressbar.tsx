import { __ } from "@wordpress/i18n";
import classNames from "classnames";
import delegate from "delegate";
import React, { useCallback, useEffect, useRef, useState } from "react";
import cookie from "react-cookies";
import { createPortal } from "react-dom";
import usePortal from "../hooks/usePortal";
import { Close } from "../themes/helpers";
import Analytics, { analyticsOnClick } from "./Analytics";
import useNotificationContext from "./NotificationProvider";
import nxHelper from "./functions";

/**
 * @example
 * <Portal id="modal">
 *   <p>Thinking with portals</p>
 * </Portal>
 */
const Pressbar = ({ position, nxBar, dispatch }) => {
    let innerContent;
    // @todo isShortcode
    const isShortcode = false;
    const target = usePortal(`nx-bar-${position}`, position == 'top');
    const { config: settings, data: content } = nxBar;
    const [timeConfig, setTimeConfig] = useState<timeConfig>({ days: '00', hours: '00', minutes: '00', seconds: '00', expired: false });
    const hasContent = content?.replace(/<[^>]+>|[\n\r]/g, '')?.trim() != '';
    const [styles, setStyles] = useState<{ [key: string]: any }>({});
    const [closed, setClosed] = useState(false);
    const [isTimeBetween,setIsTimeBetween] = useState(false);
    let elementorRef = useRef();
    const frontendContext = useNotificationContext();

    const consentCallback = useCallback( (event) => {
        setClosed(true);
    }, []
    );

    const analyticCallback = useCallback( (event) => {
        const restUrl = nxHelper.getPath(frontendContext.rest, `analytics/`);
        analyticsOnClick(event, restUrl, settings, frontendContext.rest.omit_credentials);
    }, []
    );

    const getTime = (settings) => {
        let currentTime = new Date().getTime();
        let expiredTime: number;
        let countRand = settings?.countdown_rand ? `-${settings.countdown_rand}` : '';
        if (settings?.evergreen_timer) {
            let startedAt = cookie.load(`pressbar-evergreen-started-at-${settings.nx_id}${countRand}`);
            if (!startedAt) {
                startedAt = currentTime;
                const expires = new Date();
                expires.setDate(expires.getDate() + (settings?.time_reset ? 1 : 365));
                cookie.save(`pressbar-evergreen-started-at-${settings.nx_id}${countRand}`, currentTime, { path: '/', expires })
            }

            if (settings?.time_randomize) {
                let timeBetween = cookie.load(`pressbar-evergreen-random-expire-${settings.nx_id}${countRand}`);

                if (!timeBetween) {
                    const start_time = settings?.time_randomize_between?.start_time;
                    const end_time = settings?.time_randomize_between?.end_time;
                    timeBetween = Math.round((Math.random() * (end_time - start_time) + start_time));
                    const expires = new Date();
                    expires.setDate(expires.getDate() + (settings?.time_reset ? 1 : 365));
                    cookie.save(`pressbar-evergreen-random-expire-${settings.nx_id}${countRand}`, timeBetween, { path: '/', expires })
                }

                expiredTime = parseInt(startedAt) + timeBetween * 60 * 60 * 1000;
            }
            else {
                expiredTime = parseInt(startedAt) + settings?.time_rotation * 60 * 60 * 1000;
            }
        }
        else {
            expiredTime = frontendContext.getTime(settings.countdown_end_date || undefined).unix() * 1000;
            const startTime = frontendContext.getTime(settings.countdown_start_date || undefined).unix() * 1000;
            if( startTime < Date.now() ) {
                setIsTimeBetween(true);
            }
        }
        return { currentTime, expiredTime };
    }

    useEffect(() => {
        if (settings?.elementor_id && timeConfig.expired) {
            setClosed(true);
        }
    }, [timeConfig.expired])

    useEffect(() => {
        let countdownInterval;
        if (settings?.enable_countdown) {
            countdownInterval = setInterval(function () {
                setTimeConfig(countdown(getTime(settings)));
                // if (countdownInterval && timeConfig.time <= 0) {
                //     clearInterval(countdownInterval);
                // }
            }, 1000);
            setTimeConfig(countdown(getTime(settings)));
        }

        // @ts-ignore
        if (elementorRef?.current && elementorFrontend?.elementsHandler?.runReadyTrigger) {
            // @ts-ignore
            const elements = elementorRef?.current?.getElementsByClassName('elementor-element');
            for (const element of elements) {
                // @ts-ignore
                elementorFrontend.elementsHandler.runReadyTrigger(element);
            }
        }


        const componentCSS: any = {};
        const buttonCSS: any = {};
        const counterCSS: any = {};
        const closeButtonCSS: any = {};
        if (settings?.advance_edit) {
            if (settings?.bar_bg_color) componentCSS.backgroundColor = settings.bar_bg_color;
            if (settings?.bar_text_color) componentCSS.color = settings.bar_text_color;
            if (settings?.bar_font_size) componentCSS.fontSize = settings.bar_font_size;
            if (settings?.bar_btn_bg) buttonCSS.backgroundColor = settings.bar_btn_bg;
            if (settings?.bar_btn_text_color) buttonCSS.color = settings.bar_btn_text_color;
            if (settings?.bar_counter_bg) counterCSS.backgroundColor = settings.bar_counter_bg;
            if (settings?.bar_counter_text_color) counterCSS.color = settings.bar_counter_text_color;
            if (settings?.bar_close_color) closeButtonCSS.fill = settings.bar_close_color;
        }
        const barHeight = document.getElementById(`nx-bar-${settings.nx_id}`).offsetHeight;
        document.body.classList.add("has-nx-bar");
        if(settings?.sticky_bar){
            document.body.classList.add("nx-sticky-bar");
        }
        if(settings?.pressbar_body){
            document.body.classList.add("nx-overlapping-bar");
        }
        if (position == 'top') {
            document.body.classList.add("nx-position-top")
            const xAdminBar = document.getElementById("wpadminbar");
            if (xAdminBar?.offsetHeight) componentCSS.top = xAdminBar.offsetHeight;
            if (!settings?.pressbar_body) {
                document.body.style.paddingTop = `${barHeight}px`;
            }
        }
        else {
            if (!settings?.pressbar_body) {
                document.body.style.paddingBottom = `${barHeight}px`;
            }

        }

        setStyles({
            componentCSS,
            buttonCSS,
            counterCSS,
            closeButtonCSS,
        })

        //@ts-ignore
        let analyticDelegation;
        let consentDelegation;
        if(elementorRef?.current){
            analyticDelegation = delegate(elementorRef?.current, 'a', 'click', analyticCallback);
            consentDelegation  = delegate(elementorRef?.current, '#nx-consent-accept', 'click', consentCallback);
        }
        return () => {
            countdownInterval && clearInterval(countdownInterval);
            document.body.classList.remove("has-nx-bar");
            document.body.classList.remove("nx-sticky-bar");
            document.body.classList.remove("nx-overlapping-bar");
            document.body.classList.remove("nx-position-top");

            if(analyticDelegation){
                analyticDelegation.destroy();
            }
            if(consentDelegation){
                consentDelegation.destroy();
            }

            if (position == 'top') {
                document.body.style.paddingTop = null;
            }
            else {
                document.body.style.paddingBottom = null;
            }
        };
    }, [])


    // debugger;
    if (settings?.elementor_id) {
        innerContent = (
            <div
                ref={elementorRef}
                className="nx-bar-content-wrap"
                dangerouslySetInnerHTML={{ __html: content }}
            ></div>
        );
    } else {
        innerContent = (
            <div className="nx-bar-content-wrap">
                {settings?.enable_countdown && (
                    <div className="nx-countdown-wrapper">
                        {!timeConfig.expired && settings?.countdown_text && (
                            <div className="nx-countdown-text">
                                {__(settings?.countdown_text, "notificationx")}
                            </div>
                        )}
                        <div className="nx-countdown" style={styles?.counterCSS}>
                            { (!timeConfig.expired && isTimeBetween) &&
                                <>
                                    <div className="nx-time-section" style={styles?.counterCSS}>
                                        <span className="nx-days">
                                            {timeConfig.days}
                                        </span>
                                        <span className="nx-countdown-time-text">
                                            {__("Days", "notificationx")}
                                        </span>
                                    </div>
                                    <div className="nx-time-section" style={styles?.counterCSS}>
                                        <span className="nx-hours">
                                            {timeConfig.hours}
                                        </span>
                                        <span className="nx-countdown-time-text">
                                            {__("Hrs", "notificationx")}
                                        </span>
                                    </div>
                                    <div className="nx-time-section" style={styles?.counterCSS}>
                                        <span className="nx-minutes">
                                            {timeConfig.minutes}
                                        </span>
                                        <span className="nx-countdown-time-text">
                                            {__("Mins", "notificationx")}
                                        </span>
                                    </div>
                                    <div className="nx-time-section" style={styles?.counterCSS}>
                                        <span className="nx-seconds">
                                            {timeConfig.seconds}
                                        </span>
                                        <span className="nx-countdown-time-text">
                                            {__("Secs", "notificationx")}
                                        </span>
                                    </div>
                                </>}
                            <span className={classNames("nx-expired-text", { "nx-countdown-expired": timeConfig.expired })} style={styles?.counterCSS}>
                                {timeConfig.expired && __(
                                    settings?.countdown_expired_text,
                                    "notificationx"
                                )}
                            </span>
                        </div>
                    </div>
                )}

                <div className="nx-inner-content-wrapper">
                    {hasContent && (
                        <div className="nx-bar-content" dangerouslySetInnerHTML={{ __html: content }}></div>
                    )}
                    {!hasContent && (
                        <div className="nx-bar-content">
                            {__(
                                "You should setup NX Bar properly",
                                "notificationx"
                            )}
                        </div>
                    )}

                    {(settings?.button_url && settings?.button_text) && (
                        <Analytics
                            style={styles?.buttonCSS}
                            className="nx-bar-button"
                            href={settings?.button_url}
                            config={settings}
                        >
                            {settings?.button_text}
                        </Analytics>
                    )}
                </div>
                {/* @todo close button &&& filters */}
            </div>
        );
    }

    const wrapper = (
        // @todo advanced style.
        <div
            id={`nx-bar-${settings.nx_id}`}
            className={classNames(
                `nx-bar`,
                settings.themes,
                `nx-bar-${settings.nx_id}`,

                {
                    ["nx-bar-shortcode nx-bar-visible"]: isShortcode,
                    "nx-position-top": "top" == settings?.position,
                    "nx-position-bottom":
                        "bottom" == settings?.position,
                    [`nx-close-${settings?.bar_close_position}`]: settings?.bar_close_position,
                    "nx-admin": isAdminBar(),
                    "nx-sticky-bar": settings?.sticky_bar,
                    "nx-bar-has-elementor": settings?.elementor_id,
                }
            )}
            style={styles?.componentCSS}
        >
            <div className="nx-bar-inner">
                {innerContent}
                <Close {...nxBar} dispatch={dispatch} style={styles?.closeButtonCSS} closed={closed} />
            </div>
        </div>
    );

    return createPortal(wrapper, target);
};

const isAdminBar = () => {
    const adminBar = document.getElementById("wpadminbar");
    if (adminBar) {
        return true;
    }
    return false;
};

const countdown = ({ currentTime, expiredTime }) => {
    let expired = false;
    let time = Math.round((expiredTime - currentTime) / 1000);
    if (time <= 0) {
        time = 0;
        expired = true;
        // if (args.evergreen_timer) {
        //     // @todo set cookie
        // }
    }

    var days,
        hours,
        minutes,
        seconds;

    seconds = time % 60;
    time = (time - seconds) / 60;
    minutes = time % 60;
    time = (time - minutes) / 60;
    hours = time % 24;
    days = (time - hours) / 24;

    days = (days < 10 ? "0" : "") + days;
    hours = (hours < 10 ? "0" : "") + hours;
    minutes = (minutes < 10 ? "0" : "") + minutes;
    seconds = (seconds < 10 ? "0" : "") + seconds;

    return { days, hours, minutes, seconds, expired };
};

export default Pressbar;
