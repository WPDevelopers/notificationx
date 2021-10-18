import React, { CSSProperties, useEffect, useRef, useState } from "react";
import classNames from "classnames";
import { Theme } from "../themes";
import Analytics from "./Analytics";
import { getThemeName } from "../../core/functions";

/** @ts-ignore */
const { is_pro } = window?.notificationX;

const Notification = (props) => {
    const [exit, setExit] = useState(false);
    const [width, setWidth] = useState(0);
    const [intervalID, setIntervalID] = useState(null);

    const { config: settings } = props;

    const incrementValue = 0.5;
    const displayFor = ((settings?.display_for || 5) * 1000);
    const isMin = displayFor * (incrementValue / 100)

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
    const componentStyle: CSSProperties = {
        maxWidth: `${settings?.size}px`
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
            {props.data?.link && <a
                className="notificationx-link"
                href={props.data.link}
                target={settings?.link_open ? "_blank" : ""}
                onClick={e => Analytics(e, props.data.link, settings)}
            />}
        </div>
    );
};

export default Notification;
