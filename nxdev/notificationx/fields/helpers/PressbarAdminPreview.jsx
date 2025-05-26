import { __ } from "@wordpress/i18n";
import classNames from "classnames";
import moment from "moment";
import React, { Fragment, useEffect, useRef, useState } from "react";
import cookie from "js-cookie"; // Assuming this is used
import BarCoupon from "../../frontend/core/helper/BarCoupon";
import PreviewButton from './PreviewButton';
import { ReactComponent as DesktopIcon } from "../../icons/responsive/desktop.svg";
import { ReactComponent as TabletIcon } from "../../icons/responsive/tablet.svg";
import { ReactComponent as MobileIcon } from "../../icons/responsive/mobile.svg";


const getUnixTime = (value) => moment.utc(value).unix() * 1000;

const calculateCountdown = (currentTime, expiredTime) => {
    const diff = Math.max(0, Math.round((expiredTime - currentTime) / 1000));
    const expired = diff <= 0;

    const seconds = diff % 60;
    const minutes = Math.floor(diff / 60) % 60;
    const hours = Math.floor(diff / 3600) % 24;
    const days = Math.floor(diff / 86400);

    const pad = (n) => (n < 10 ? "0" + n : n);
    return {
        days: pad(days),
        hours: pad(hours),
        minutes: pad(minutes),
        seconds: pad(seconds),
        expired,
    };
};

const resolveLinkInfo = (settings, data) => {
    let link = settings?.button_url;
    let link_text = settings?.button_text;
    let show_default_subscribe = false;
    switch (settings?.link_type) {
        case 'yt_video_link':
            link = data?.yt_video_link;
            link_text = settings?.link_button_text || settings?.link_button_text_video;
            break;
        case 'yt_channel_link':
            show_default_subscribe = true;
            link_text = settings?.link_button_text_channel;
            break;
        case 'announcements_link':
            show_default_subscribe = true;
            link_text = settings?.announcement_link_button_text;
            break;
        default:
            break;
    }
    return { link, link_text, show_default_subscribe };
};

const PressbarAdminPreview = ({ position, nxBar, dispatch }) => {
    const { config: settings, data } = nxBar;
    const hasContent = settings?.press_content;
    const [timeConfig, setTimeConfig] = useState({ days: '00', hours: '00', minutes: '00', seconds: '00', expired: false });
    const [isTimeBetween, setIsTimeBetween] = useState(false);
    const [styles, setStyles] = useState({});
    const countdownRef = useRef(null);
    const [previewType, setPreviewType] = useState("desktop");

    const getTime = () => {
        const currentTime = Date.now();
        let expiredTime = 0;

        const countRand = settings?.countdown_rand ? `-${settings.countdown_rand}` : '';

        if (settings?.evergreen_timer) {
            let startedAt = cookie.get(`pressbar-evergreen-started-at-${settings.nx_id}${countRand}`);
            if (!startedAt) {
                startedAt = currentTime;
                const expires = new Date();
                expires.setDate(expires.getDate() + (settings?.time_reset ? 1 : 365));
                cookie.set(`pressbar-evergreen-started-at-${settings.nx_id}${countRand}`, startedAt, { expires });
            }

            if (settings?.time_randomize) {
                let timeBetween = cookie.get(`pressbar-evergreen-random-expire-${settings.nx_id}${countRand}`);
                if (!timeBetween) {
                    const { start_time, end_time } = settings?.time_randomize_between || {};
                    timeBetween = Math.round(Math.random() * (end_time - start_time) + start_time);
                    const expires = new Date();
                    expires.setDate(expires.getDate() + (settings?.time_reset ? 1 : 365));
                    cookie.set(`pressbar-evergreen-random-expire-${settings.nx_id}${countRand}`, timeBetween, { expires });
                }
                expiredTime = parseInt(startedAt) + timeBetween * 3600000;
            } else {
                expiredTime = parseInt(startedAt) + settings?.time_rotation * 3600000;
            }
        } else {
            const startTime = getUnixTime(settings.countdown_start_date);
            expiredTime = getUnixTime(settings.countdown_end_date);
            if (startTime < currentTime) setIsTimeBetween(true);
        }

        return { currentTime, expiredTime };
    };

    useEffect(() => {
        const id = `nx-bar-${settings.nx_id}`;
        const el = document.getElementById(id);
        if (!el) return;

        const height = el.offsetHeight;
        const componentCSS = {};
        const buttonCSS = {};
        const counterCSS = {};
        const closeButtonCSS = {};

        if (settings?.advance_edit) {
            // Get transition style and speed from settings
            const transitionStyle = settings.bar_transition_style || 'ease';
            const transitionSpeed = settings.bar_transition_speed || 500;
            const transitionValue = `all ${transitionSpeed}ms ${transitionStyle}`;

            Object.assign(componentCSS, {
                backgroundColor: settings.bar_bg_color,
                color: settings.bar_text_color,
                fontSize: settings.bar_font_size,
                backgroundImage: settings.bar_bg_image?.url ? `url('${settings.bar_bg_image.url}')` : undefined,
                transition: transitionValue,
            });

            Object.assign(buttonCSS, {
                backgroundColor: settings.bar_btn_bg,
                color: settings.bar_btn_text_color,
                transition: transitionValue,
            });

            Object.assign(counterCSS, {
                backgroundColor: settings.bar_counter_bg,
                color: settings.bar_counter_text_color,
                transition: transitionValue,
            });

            Object.assign(closeButtonCSS, {
                fill: settings.bar_close_color,
            });
        }

        document.body.classList.add("has-nx-bar");
        if (settings?.sticky_bar) document.body.classList.add("nx-sticky-bar");
        if (settings?.pressbar_body) document.body.classList.add("nx-overlapping-bar");

        if (position === 'top') {
            document.body.classList.add("nx-position-top");
            const adminBar = document.getElementById("wpadminbar");
            if (adminBar?.offsetHeight) componentCSS.top = adminBar.offsetHeight;
            if (!settings?.pressbar_body) document.body.style.paddingTop = `${height}px`;
        } else {
            if (!settings?.pressbar_body) document.body.style.paddingBottom = `${height}px`;
        }

        setStyles({ componentCSS, buttonCSS, counterCSS, closeButtonCSS });

        return () => {
            document.body.classList.remove("has-nx-bar", "nx-sticky-bar", "nx-overlapping-bar", "nx-position-top");
            if (position === 'top') document.body.style.paddingTop = null;
            else document.body.style.paddingBottom = null;
        };
    }, [settings, position]);

    const [currentSlide, setCurrentSlide] = useState(0);
    const slidingContent = settings?.sliding_content || [];
    const direction = 'right' // fallback to 'left'
    // const direction = settings?.sliding_direction || 'left'; // fallback to 'left'
    const slideInterval = settings?.sliding_interval || 3000; // default 3s
    const transitionSpeed = settings?.bar_transition_speed || 500; // default 500ms

    useEffect(() => {
        if (!slidingContent.length) return;

        const interval = setInterval(() => {
            setCurrentSlide((prevIndex) => (prevIndex + 1) % slidingContent.length);
        }, slideInterval);

        return () => clearInterval(interval);
    }, [slidingContent, slideInterval]);

    useEffect(() => {
        if (!settings?.enable_countdown) return;
        const updateCountdown = () => {
            const { currentTime, expiredTime } = getTime();
            setTimeConfig(calculateCountdown(currentTime, expiredTime));
        };

        updateCountdown();
        countdownRef.current = setInterval(updateCountdown, 1000);
        return () => clearInterval(countdownRef.current);
    }, [settings]);

    const { link, link_text } = resolveLinkInfo(settings, data);    

    return (
        <Fragment>
            <div className="nx-bar-responsive">
                <div class="nx-admin-modal-head">
                    <button 
                        class={`nx-admin-modal-preview-button ${previewType == "desktop" ? "active" : ""}`}
                        onClick={() => setPreviewType("desktop")}
                    >
                         <DesktopIcon style={{ width: 20 }} />
                    </button>
                    <button
                        onClick={() => setPreviewType("tablet")}
                        class={`nx-admin-modal-preview-button ${previewType == "tablet" ? "active" : ""}`}
                    >
                         <TabletIcon style={{ width: 20 }} />
                    </button>
                    <button
                        onClick={() => setPreviewType("phone")}
                        class={`nx-admin-modal-preview-button ${previewType == "phone" ? "active" : ""}`}
                    >
                         <MobileIcon style={{ width: 20 }} />
                    </button>
                </div>
            </div>
            <div
                id={`nx-bar-${settings.nx_id}`}
                className={classNames(
                    "nx-bar",
                    settings.themes,
                    `nx-bar-${settings.nx_id}`,
                    'nx-bar-section-preview',
                    `nx-bar-${previewType}`,
                    {
                        "nx-position-top": settings?.position === "top",
                        "nx-position-bottom": settings?.position === "bottom",
                        [`nx-close-${settings?.bar_close_position}`]: settings?.bar_close_position,
                        "nx-sticky-bar": settings?.sticky_bar,
                    }
                )}
                style={{...styles?.componentCSS}}
            >
                <div className="nx-bar-inner">
                    <div className="nx-bar-content-wrap">
                        {settings?.enable_countdown && (
                            <div className="nx-countdown-wrapper">
                                {!timeConfig.expired && settings?.countdown_text && (
                                    <div className="nx-countdown-text">
                                        {__(settings.countdown_text, "notificationx")}
                                    </div>
                                )}
                                <div className="nx-countdown" style={styles.counterCSS}>
                                    {( (!timeConfig.expired && isTimeBetween) || (!timeConfig.expired && settings?.evergreen_timer)) ? (
                                        ['days', 'hours', 'minutes', 'seconds'].map((key) => (
                                            <div key={key} className="nx-time-section" style={styles.counterCSS}>
                                                <span className={`nx-${key}`}>{timeConfig[key]}</span>
                                                <span className="nx-countdown-time-text">{__(key.charAt(0).toUpperCase() + key.slice(1), "notificationx")}</span>
                                            </div>
                                        ))
                                    ) : (
                                        <span className={classNames("nx-expired-text", { "nx-countdown-expired": timeConfig.expired })} style={styles.counterCSS}>
                                            {timeConfig.expired && __(settings?.countdown_expired_text, "notificationx")}
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}
                        <div className="nx-inner-content-wrapper">
                            {( settings?.bar_content_type == 'static' && hasContent) && (
                                <div className="nx-bar-content" dangerouslySetInnerHTML={{ __html: settings?.press_content }}></div>
                            )}
                            {settings?.bar_content_type === 'sliding' && slidingContent.length > 0 && (
                                <div className={classNames("nx-bar-content nx-bar-slide-wrapper", `slide-direction-${direction}`)}>
                                    {slidingContent.map((item, index) => {
                                    const isActive = index === currentSlide;
                                    const isPrevious = (index === currentSlide - 1) || (currentSlide === 0 && index === slidingContent.length - 1);
        
                                    return (
                                        <div
                                            key={index}
                                            className={classNames("nx-bar-slide", {
                                                'active': isActive,
                                                'previous': isPrevious,
                                                'left-exit': isPrevious,
                                                'right-exit': isPrevious
                                            })}
                                            style={{
                                                transition: `all ${transitionSpeed}ms ease-in-out`
                                            }}
                                            dangerouslySetInnerHTML={{ __html: item.title }}
                                        />
                                    );
                                    })}
                                </div>
                            )}
        
                            { (!hasContent && slidingContent?.length == 0 ) && (
                                <div className="nx-bar-content">
                                    {__(
                                        "You should setup NX Bar properly",
                                        "notificationx"
                                    )}
                                </div>
                            )}

                            {(settings?.button_url && settings?.button_text) && (
                                <PreviewButton
                                    style={styles?.buttonCSS}
                                    className="nx-bar-button"
                                    href={settings?.button_url}
                                    config={settings}
                                >
                                    {settings?.button_text}
                                </PreviewButton>
                            )}
        
                            {settings?.coupon_text && settings?.enable_coupon && (
                                <BarCoupon settings={settings} />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </Fragment>
        
    );
};

export default PressbarAdminPreview;
