import classNames from "classnames";
import React, { useEffect, useRef, useState } from "react";
import { getThemeName, isObject, calculateAnimationStartTime, getResThemeName } from "../core/functions";
import { Theme } from "../themes";
import Analytics from "./Analytics";
import useNotificationContext from "./NotificationProvider";
import 'animate.css';

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

const NotificationForMobile = (props) => {
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
                    bottom: !animation ? '30px' : '0',
                    left: !animation ? '30px' : '30px',
                    right: !animation ? '30px' : '30px',
                    transition: '300ms',
                };
            case 'animate__slideOutLeft':
                return {
                    left: !animation ? '30px' : '0',
                    bottom: !animation ? '30px' : '30px',
                    right: !animation ? '30px' : '30px',
                    transition: '300ms',
                };
            case 'animate__slideOutRight':
                return {
                    right: !animation ? '30px' : '0',
                    left: !animation ? '30px' : '30px',
                    bottom: !animation ? '30px' : '30px',
                    transition: '300ms',
                };
            case 'animate__slideOutUp':
                return {
                    right: !animation ? '30px' : '0',
                    left: !animation ? '30px' : '30px',
                    bottom: !animation ? '30px' : '30px',
                    transition: '300ms',
                };
            default:
                return {
                    bottom: '30px',
                    left: '30px',
                    right: '0',
                };
        }
    };

    // Close notification
    useEffect(() => {
        if (width >= calculateAnimationStartTime(settings?.display_for, settings.animation_notification_hide)) {
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
            }, 500)
        }
        // return () => {
        //     handlePauseTimer();
        // };
    }, [width]);

    const audioRef = useRef(null);

    useEffect(() => {
        if (audioRef.current && is_pro) {
            audioRef.current.volume = parseInt(settings.volume || 1) / 100;
            audioRef.current.muted = false;
            audioRef.current.play().then(res => {
                // console.log('Playing Audio Sound for NX Notice');
            }).catch(err => console.error("NX SoundError: ", err))
        }
        handleStartTimer();
        return () => {
            handlePauseTimer();
        };
    }, []);

    const themeName = getResThemeName(settings);


    const { advance_edit } = settings;

    let baseClasses = [
        "notification-res-item nx-res-notification",
        `source-res-${settings.source}`,
        `position-res-${settings.position}`,
        `type-res-${settings.type}`,
        `themes-res-${themeName}`,
        `res-notification`,
        `res-type-${settings.type}`,
        `themes-${settings.responsive_themes}`,
        `notificationx-res-${settings.nx_id}`,
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

    const componentStyle: any = {
        maxWidth: `${notificationSize}px`,
        ...getAnimationStyles()
    };
    if (settings?.advance_edit && settings?.conversion_size) {
        componentStyle.maxWidth = settings?.conversion_size;
    }

    let componentClasses;
    let animationStyle = 'SlideTop 300ms';
    if ((is_pro && settings?.animation_notification_show !== 'default') || (is_pro && settings?.animation_notification_hide !== 'default')) {
        let animate_effect;
        if (settings?.animation_notification_hide !== 'default' && settings?.animation_notification_show === 'default') {
            if (animation) {
                animate_effect = settings?.animation_notification_hide;
            } else {
                componentStyle.animation = animationStyle
            }
        } else if (settings?.animation_notification_show !== 'default' && settings?.animation_notification_hide === 'default') {
            if (animation) {
                componentStyle.animation = animationStyle;
            } else {
                animate_effect = settings?.animation_notification_show;
            }
        } else {
            animate_effect = animation ? settings?.animation_notification_hide : settings?.animation_notification_show
        }
        componentClasses = classNames(
            "animate__animated",
            animate_effect,
            // settings?.animation_notification_duration,
            "animate__faster",
            ...baseClasses
        );
    } else {
        componentClasses = classNames(
            ...baseClasses
        );
        componentStyle.animation = animationStyle
    }
    const splitThemes = ['res-theme-three'];
    return (
        <div
            // onMouseEnter={handlePauseTimer}
            // onMouseLeave={handleStartTimer}
            className={componentClasses}
            style={componentStyle}
        >
            {
                is_pro && settings?.sound && settings?.sound != 'none' && settings.sound.length > 0 && props.assets?.pro &&
                <audio
                    ref={audioRef}
                    autoPlay={true}
                    src={`${props.assets.pro}sounds/${settings.sound}.mp3`}
                />
            }
            <Theme {...props} is_mobile={true} splitThemes={splitThemes} />
            <Analytics
                className="notificationx-link"
                config={settings}
                data={props.data}
            />
        </div>
    );
};

export default NotificationForMobile;
