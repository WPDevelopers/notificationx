import classNames from "classnames";
import React, { useEffect, useState } from "react";
import { getThemeName, isObject, calculateAnimationStartTime, getResThemeName } from "../core/functions";
import { Theme } from "../themes";
import Analytics, { resolveNotificationLink, recordAnalyticsClick } from "./Analytics";
import useNotificationContext from "./NotificationProvider";
import nxHelper from "./functions";
import { nxApplyFilters } from "./hooks";

const useMediaQuery = (query) => {
    const mediaQuery = window.matchMedia(query);
    const [match, setMatch] = useState(!!mediaQuery.matches);

    useEffect(() => {
        const handler = () => setMatch(!!mediaQuery.matches);
        mediaQuery.addEventListener("change", handler);
        return () => mediaQuery.removeEventListener("change", handler);
    }, []);

    if (
        typeof window === "undefined" ||
        typeof window.matchMedia === "undefined"
    )
        return false;

    return match;
};

const Notification = (props) => {
    const [exit, setExit] = useState(false);
    const [animation, setAnimation] = useState(false);
    const [width, setWidth] = useState(0);
    const [intervalID, setIntervalID] = useState(null);

    const { config: settings } = props;
    const frontEndContext = useNotificationContext();
    const is_pro = frontEndContext?.state?.is_pro ?? false;
    const incrementValue = 0.5;
    const displayFor = ((settings?.display_for || 5) * 1000);
    const isMin = displayFor * (incrementValue / 100)

    const isMobile = useMediaQuery("(max-width: 480px)");
    const isTablet = useMediaQuery("(max-width: 768px)");
    const [notificationSize, setNotificationSize] = useState();

    useEffect(() => {
        if (settings?.size) {
            if (isObject(settings?.size)) {
                setNotificationSize(
                    isMobile
                        ? settings?.size.mobile
                        : isTablet
                        ? settings?.size.tablet
                        : settings?.size.desktop
                );
            } else setNotificationSize(settings?.size);
        }
    }, [isMobile, isTablet, settings?.size]);

    const handleStartTimer = () => {
        let startTime = Date.now();
        const id = setInterval(() => {
            const dateNow = Date.now();
            const diffTime = dateNow - startTime;
            startTime = dateNow;
            const incrementValue = (100 * diffTime / displayFor);

            setWidth((prev) => {
                if (prev < 100) {
                    return prev + incrementValue;
                }
                clearInterval(id);
                return prev;
            });
        }, isMin); // 25 = 5sec is for how much time notice will display
        setIntervalID(id);
    };

    const handlePauseTimer = () => {
        clearInterval(intervalID);
    };

    const handleCloseNotification = () => {
        setExit(true);
    };

    const getAnimationStyles = () => {
        switch (settings.animation_notification_hide) {
          case 'animate__slideOutDown': 
            return {
                bottom: !animation ? '30px': '0',
                left  : !animation ? '30px': '30px',
                right : !animation ? '30px': '30px',
                transition  : '300ms',
            };
          case 'animate__slideOutLeft': 
            return {
                left  : !animation ? '30px': '0',
                bottom: !animation ? '30px': '30px',
                right : !animation ? '30px': '30px',
                transition  : '300ms',
            };
          case 'animate__slideOutRight': 
            return {
                right : !animation ? '30px': '0',
                left  : !animation ? '30px': '30px',
                bottom: !animation ? '30px': '30px',
                transition  : '300ms',
            };
          case 'animate__slideOutUp': 
            return {
                right     : !animation ? '30px': '0',
                left      : !animation ? '30px': '30px',
                bottom    : !animation ? '30px': '30px',
                transition: '300ms',
            };
          default: 
            return {
                bottom: '30px',
                left  : '30px',
                right : '0',
            };
        }
      };

    // Close notification
    useEffect(() => {    
        if( width >= calculateAnimationStartTime( settings?.display_for, settings.animation_notification_hide ) ) { 
            setAnimation(true);
        }
        if (width >= 99.5) {
            handleCloseNotification();
            setTimeout(() => {
                handlePauseTimer();
                props.dispatch({
                    type: "REMOVE_NOTIFICATION",
                    payload: props.id,
                });
                setAnimation(false);
            }, 500 )
        }
        // return () => {
        //     handlePauseTimer();
        // };
    }, [width]);

    useEffect(() => {
        handleStartTimer();
        return () => {
            handlePauseTimer();
        };
    }, []);

    const themeName = getThemeName(settings);

    // Whole-card click: corner popups must be clickable even when the theme
    // hides the CTA button (e.g. Cart Peek), so the click and its CTR still
    // register. Bars / exit-intent / gdpr render through their own components.
    const restUrl = nxHelper.getPath(frontEndContext.rest, `analytics/`);
    const cardLink = resolveNotificationLink(settings, props.data);
    const handleCardClick = (e) => {
        if (settings?.source === 'press_bar' || settings?.type === 'notification_bar') return;
        // Let genuinely interactive children handle their own clicks (the CTA
        // link, close button, powered-by link, subscribe widget, form fields).
        if (e.target?.closest?.('a, button, input, textarea, select, label, .notificationx-close, .nx-close, .nx-powered-by, .g-ytsubscribe')) {
            return;
        }
        if (!cardLink) return;
        recordAnalyticsClick(restUrl, settings, frontEndContext?.rest?.omit_credentials);
        if (settings?.link_open) {
            window.open(cardLink, '_blank', 'noopener');
        } else {
            window.location.href = cardLink;
        }
    };

    const { advance_edit } = settings;
    
    let baseClasses = [
        "notification-item nx-notification",
        `source-${settings.source}`,
        `position-${settings.position}`,
        `type-${settings.type}`,
        `themes-${themeName}`,
        `themes-${settings.themes}`,
        `notificationx-${settings.nx_id}`,
        props.config.link_button ? `button-link` : '',
        {
            [`type-${settings?.custom_type}`]: settings?.custom_type,
            exit: exit,
            "has-close-btn": settings?.close_button,
            "has-no-image": settings?.image_data === false,
            // Advanced Edit
            [`custom-style-${settings?.id}`]: advance_edit,
            [`img-position-${settings?.image_position}`]: advance_edit,
            "flex-reverse": advance_edit && settings?.image_position === "right",
        }
    ];
    let splitThemes = [
        "theme-five",
        "theme-six-free",
        "conv-theme-nine",
        "review-comment",
        "page_analytics_pa-theme-two",
    ];
    splitThemes = splitThemes.concat(props?.splitThemes);

    const componentCSS: any = {};
    const resThemeName = getResThemeName(settings) || null;
    const isSplit = splitThemes.includes(themeName) || splitThemes.includes(resThemeName);
    
    if (props?.config?.advance_edit) {
        if (props?.config?.bg_color && !isSplit ) componentCSS.backgroundColor = props?.config?.bg_color;
        if (props?.config?.text_color) componentCSS.color = props?.config?.text_color;
        if (+props?.config?.border && +props?.config?.border_size) {
            componentCSS.borderWidth = props?.config?.border_size;
            if (props?.config?.border_style) componentCSS.borderStyle = props?.config?.border_style;
            if (props?.config?.border_color) componentCSS.borderColor = props?.config?.border_color;
        }
    }
    
    const componentStyle: any = {
        ...componentCSS,
        maxWidth: `${notificationSize}px`,
        ...getAnimationStyles(),
    };
    if (settings?.advance_edit && settings?.conversion_size) {
        componentStyle.maxWidth = settings?.conversion_size;
    }
    
    let componentClasses;
    let animationStyle = 'SlideTop 300ms';
    // Notification animations are rendered by NotificationX Pro (filter
    // `nx_frontend_animation`): it returns the animate.css classes and the
    // style for the current show/hide state. Without it, the built-in
    // `SlideTop` entrance is used.
    const proAnimation = nxApplyFilters<{ classNames: any[]; style?: Record<string, any> } | undefined>(
        'nx_frontend_animation',
        undefined,
        { settings, animation, animationStyle, is_pro }
    );
    if (proAnimation) {
        componentClasses = classNames(
            ...proAnimation.classNames,
            ...baseClasses
        );
        Object.assign(componentStyle, proAnimation.style || {});
    } else {
        componentClasses = classNames(
            ...baseClasses
        );
        componentStyle.animation = animationStyle
    }

    return (
        <div
            // onMouseEnter={handlePauseTimer}
            // onMouseLeave={handleStartTimer}
            className={componentClasses}
            style={cardLink ? { ...componentStyle, cursor: 'pointer' } : componentStyle}
            id={`notificationx-${settings.nx_id}`}
            onClick={handleCardClick}
        >
            {
                // Notification sound: rendered by NotificationX Pro.
                nxApplyFilters('nx_frontend_notification_sound', null, { settings, assets: props.assets, is_pro })
            }
            <Theme {...props} />
            <Analytics
                className="notificationx-link"
                config={settings}
                data={props.data}
                dispatch={props.dispatch}
            />
        </div>
    );
};

export default Notification;
