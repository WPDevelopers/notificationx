import classNames from "classnames";
import React, { useEffect, useRef, useState } from "react";
import { getThemeName, isObject } from "../core/functions";
import { Theme } from "../themes";
import Analytics from "./Analytics";
import useNotificationContext from "./NotificationProvider";

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

    // Close notification
    useEffect(() => {
        if (width >= 99.5) {
            handleCloseNotification();
            setTimeout(() => {
                handlePauseTimer();
                props.dispatch({
                    type: "REMOVE_NOTIFICATION",
                    payload: props.id,
                });
            }, 500)
        }
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
    }, []);

    const themeName = getThemeName(settings);


    const { advance_edit } = settings;

    const componentClasses = classNames(
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
    );

    const componentStyle: any = {
        maxWidth: `${notificationSize}px`,
    };
    if (settings?.advance_edit && settings?.conversion_size) {
        componentStyle.maxWidth = settings?.conversion_size;
    }

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
            <Theme {...props} />
            <Analytics
                className="notificationx-link"
                config={settings}
                data={props.data}
            />
        </div>
    );
};

export default Notification;
