import classNames from "classnames";
import React, { useEffect, useRef, useState } from "react";
import { getThemeName, isObject, calculateAnimationStartTime, getResThemeName, getAnimationTiming } from "../core/functions";
import { Theme } from "../themes";
import Analytics, { resolveNotificationLink, recordAnalyticsClick } from "./Analytics";
import useNotificationContext from "./NotificationProvider";
import nxHelper from "./functions";
import { nxApplyFilters, nxApplyListFilter } from "./hooks";

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

// Longest delay + duration among the card's entrance animations (CSS times such
// as "0.5s, 300ms"), plus 500ms, and at least 3s: the CTA fallback's wait.
export const getCtaFallbackDelay = (style) => {
    const toMs = (list) => String(list || '').split(',').map((t) => {
        const v = parseFloat(t);
        if (!Number.isFinite(v)) return 0;
        return /ms\s*$/.test(t) ? v : v * 1000;
    });
    const durations = toMs(style?.animationDuration);
    const delays = toMs(style?.animationDelay);
    let longest = 0;
    durations.forEach((d, i) => {
        longest = Math.max(longest, d + (delays[i] ?? delays[delays.length - 1] ?? 0));
    });
    return Math.max(3000, Math.ceil(longest) + 500);
};

const Notification = (props) => {
    const [exit, setExit] = useState(false);
    const [animation, setAnimation] = useState(false);
    const [width, setWidth] = useState(0);
    const [intervalID, setIntervalID] = useState(null);
    const [ctaIn, setCtaIn] = useState(false);
    const cardRef = useRef(null);

    const { config: settings } = props;
    const frontEndContext = useNotificationContext();
    const is_pro = frontEndContext?.state?.is_pro ?? false;
    // Show/hide timing; add-ons can change it (filter `nx_frontend_animation_timing`).
    const animationTiming = getAnimationTiming(settings, is_pro);
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
                bottom: '30px',
                left  : !animation ? '30px': '30px',
                right : !animation ? '30px': '30px',
                transition  : '300ms',
            };
          case 'animate__slideOutLeft': 
            return {
                left  : '30px',
                bottom: !animation ? '30px': '30px',
                right : !animation ? '30px': '30px',
                transition  : '300ms',
            };
          case 'animate__slideOutRight': 
            return {
                right : '30px',
                left  : !animation ? '30px': '30px',
                bottom: !animation ? '30px': '30px',
                transition  : '300ms',
            };
          case 'animate__slideOutUp': 
            return {
                right     : '30px',
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
            }, animationTiming.exitDelay )
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

    // CTA tab: it peeks out (`cta-in`) once the card's own entrance animation
    // has ended, whatever its duration, or at once when the card has none.
    // The timeout covers an entrance that is cancelled and never ends: the
    // entrance's own delay + duration (Animation Duration can make it up to
    // 3s), plus a margin, and never less than 3s.
    useEffect(() => {
        const style = cardRef.current && window.getComputedStyle?.(cardRef.current);
        const name = style?.animationName;
        if (!name || name === 'none') {
            setCtaIn(true);
            return;
        }
        const id = setTimeout(() => setCtaIn(true), getCtaFallbackDelay(style));
        return () => clearTimeout(id);
    }, []);
    const handleAnimationEnd = (e) => {
        if (e.target === e.currentTarget) {
            setCtaIn(true);
        }
    };
    // It tucks back (`cta-tuck`) 200ms before the card starts hiding. Only a
    // class: the timer, the hide and the removal above are unchanged.
    const ctaTuck = !!props.config.link_button &&
        width >= calculateAnimationStartTime(settings?.display_for, settings.animation_notification_hide) - (200 / displayFor) * 100;

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
            "cta-in": ctaIn,
            "cta-tuck": ctaTuck,
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
        ...animationTiming.style,
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
        { settings, animation, animationStyle, defaultClass: 'nx-anim-default-in', is_pro }
    );
    if (proAnimation) {
        componentClasses = classNames(
            ...proAnimation.classNames,
            ...baseClasses
        );
        Object.assign(componentStyle, proAnimation.style || {});
    } else {
        componentClasses = classNames('nx-anim-default-in', ...baseClasses);
    }
    // Extra card classes from add-ons (filter `nx_frontend_card_classes`), plus
    // the timing class.
    componentClasses = classNames(
        componentClasses,
        animationTiming.className,
        ...nxApplyListFilter('nx_frontend_card_classes', [], { settings, is_pro })
    );

    return (
        <div
            // onMouseEnter={handlePauseTimer}
            // onMouseLeave={handleStartTimer}
            className={componentClasses}
            style={cardLink ? { ...componentStyle, cursor: 'pointer' } : componentStyle}
            id={`notificationx-${settings.nx_id}`}
            ref={cardRef}
            data-nx-key={props.id} // 84710: lets StackGlide track this card across stack changes
            onClick={handleCardClick}
            onAnimationEnd={handleAnimationEnd}
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
