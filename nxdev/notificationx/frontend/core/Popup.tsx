import React, { useEffect, useState } from 'react'
import classNames from 'classnames';
import { isAdminBar } from './utils';
import CloseIcon from '../../icons/Close';
import useNotificationContext from "./NotificationProvider";
import 'animate.css';
import { isObject } from "../core/functions";
import { __ } from '@wordpress/i18n';

const useMediaQuery = (query: string) => {
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

const Popup = (props: any) => {
    const { position, nxPopup, dispatch } = props;
    const { config: settings, data: content } = nxPopup;
    const frontEndContext = useNotificationContext();
    const [isVisible, setIsVisible] = useState(true);
    const [animation, setAnimation] = useState(false);
    const isMobile = useMediaQuery("(max-width: 480px)");
    const isTablet = useMediaQuery("(max-width: 768px)");
    const [notificationSize, setNotificationSize] = useState();
    const is_pro = frontEndContext?.state?.is_pro ?? false;

    // Dynamic styles based on settings
    let mainBGColor = {};
    let titleColorFont = {};
    let descColorFont = {};
    let buttonStyles = {};
    let overlayStyles = {};

    if (settings?.advance_edit) {
        mainBGColor = {
            backgroundColor: settings?.popup_bg_color,
            borderRadius: settings?.popup_border_radius ? `${settings.popup_border_radius}px` : '8px',
        };
        titleColorFont = {
            color: settings?.popup_title_color,
            fontSize: settings?.popup_title_font_size ? `${settings.popup_title_font_size}px` : '24px',
        };
        descColorFont = {
            color: settings?.popup_desc_color,
            fontSize: settings?.popup_desc_font_size ? `${settings.popup_desc_font_size}px` : '16px',
        };
        buttonStyles = {
            backgroundColor: settings?.popup_button_bg_color,
            color: settings?.popup_button_text_color,
            borderColor: settings?.popup_button_border_color,
            borderRadius: settings?.popup_button_border_radius ? `${settings.popup_button_border_radius}px` : '4px',
            padding: settings?.popup_button_padding || '12px 24px',
            fontSize: settings?.popup_button_font_size ? `${settings.popup_button_font_size}px` : '16px',
            border: `1px solid ${settings?.popup_button_border_color || settings?.popup_button_bg_color}`,
        };
        overlayStyles = {
            backgroundColor: settings?.overlay_color || 'rgba(0, 0, 0, 0.5)',
        };
    }

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

    const handleClose = () => {
        setAnimation(true);
        setTimeout(() => {
            setIsVisible(false);
            if (dispatch) {
                dispatch({
                    type: "REMOVE_NOTIFICATION",
                    payload: nxPopup.id,
                });
            }
        }, 300);
    };

    const handleOverlayClick = (e: any) => {
        if (e.target === e.currentTarget && settings?.close_on_overlay_click) {
            handleClose();
        }
    };

    const handleButtonClick = () => {
        if (settings?.popup_button_url) {
            window.open(settings.popup_button_url, settings?.open_in_new_tab ? '_blank' : '_self');
        }
        if (settings?.close_on_button_click) {
            handleClose();
        }
    };

    if (!isVisible) {
        return null;
    }

    const getAnimationStyles = () => {
        switch (settings.animation_notification_hide) {
            case 'animate__fadeOut':
                return {
                    opacity: !animation ? 1 : 0,
                    transition: '300ms',
                };
            case 'animate__zoomOut':
                return {
                    transform: !animation ? 'scale(1)' : 'scale(0)',
                    transition: '300ms',
                };
            case 'animate__slideOutUp':
                return {
                    transform: !animation ? 'translateY(0)' : 'translateY(-100%)',
                    transition: '300ms',
                };
            case 'animate__slideOutDown':
                return {
                    transform: !animation ? 'translateY(0)' : 'translateY(100%)',
                    transition: '300ms',
                };
            default:
                return {
                    opacity: !animation ? 1 : 0,
                    transition: '300ms',
                };
        }
    };

    const componentCSS: any = {};
    const componentStyle: any = {
        ...componentCSS,
        maxWidth: `${notificationSize}px`,
        ...getAnimationStyles(),
        ...mainBGColor,
    };

    if (settings?.advance_edit && settings?.popup_width) {
        componentStyle.maxWidth = `${settings.popup_width}px`;
        componentStyle.width = `${settings.popup_width}px`;
    }

    let baseClasses = [
        `nx-popup`,
        `nx-popup-${position}`,
        settings?.themes || 'popup-theme-one',
        settings?.popup_theme ? `popup-${settings?.popup_theme}` : 'popup-default',
        `nx-popup-${settings.nx_id}`,
        {
            "nx-popup-center": settings?.popup_position === 'center',
            "nx-popup-top": settings?.popup_position === 'top',
            "nx-popup-bottom": settings?.popup_position === 'bottom',
            "nx-admin": isAdminBar(),
            "nx-popup-mobile": isMobile,
            "nx-popup-tablet": isTablet,
        }
    ];

    let componentClasses: string;
    let animationStyle = 'fadeIn 300ms';

    if ((is_pro && settings?.animation_notification_show !== 'default') || (is_pro && settings?.animation_notification_hide !== 'default')) {
        let animate_effect: string;
        if (settings?.animation_notification_hide !== 'default' && settings?.animation_notification_show === 'default') {
            if (animation) {
                animate_effect = settings?.animation_notification_hide;
            } else {
                componentStyle.animation = animationStyle;
            }
        } else if (settings?.animation_notification_show !== 'default' && settings?.animation_notification_hide === 'default') {
            if (animation) {
                componentStyle.animation = animationStyle;
            } else {
                animate_effect = settings?.animation_notification_show;
            }
        } else {
            animate_effect = animation ? settings?.animation_notification_hide : settings?.animation_notification_show;
        }

        componentClasses = classNames(
            "animate__animated",
            animate_effect,
            "animate__faster",
            ...baseClasses
        );
    } else {
        componentClasses = classNames(...baseClasses);
        componentStyle.animation = animationStyle;
    }

    console.log('popup_title',settings);
    

    return (
        <div className="nx-popup-overlay" style={overlayStyles} onClick={handleOverlayClick}>
            <div
                id={`nx-popup-${settings.nx_id}`}
                className={componentClasses}
                style={componentStyle}
                onClick={(e) => e.stopPropagation()}
            >
                <div className="nx-popup-container">
                    {/* Close Button */}
                    {settings?.show_close_button !== false && (
                        <button
                            className={`nx-popup-close ${settings?.close_button_position || 'top-right'}`}
                            onClick={handleClose}
                            aria-label="Close popup"
                        >
                            {settings?.close_button_icon ? (
                                <span dangerouslySetInnerHTML={{ __html: settings.close_button_icon }} />
                            ) : (
                                <CloseIcon />
                            )}
                        </button>
                    )}

                    {/* Header Section */}
                    {(settings?.popup_title) && (
                        <div className="nx-popup-header">
                            {settings?.popup_title && (
                                <h3 className="nx-popup-title" style={titleColorFont}>
                                    {settings.popup_title}
                                </h3>
                            )}
                        </div>
                    )}

                    {/* Content Section */}
                    <div className="nx-popup-content">
                        {(settings?.popup_content && !["popup_notification_theme-three", "popup_notification_theme-four"].some(t => settings.theme.includes(t) ) ) && (
                            <div className="nx-popup-description" style={descColorFont}>
                                {settings?.popup_content && (
                                    <div dangerouslySetInnerHTML={{ __html: settings.popup_content }} />
                                )}
                                {content && !settings?.popup_content && (
                                    <div dangerouslySetInnerHTML={{ __html: content }} />
                                )}
                            </div>
                        )}
                        { settings.theme.includes("popup_notification_theme-three") && (
                            <div className="nx-popup-description" style={descColorFont}>
                                {settings.popup_content_repeater.map((item: any, index: number) => (
                                    <div key={index}>
                                        <h3>{item.repeater_title}</h3>
                                        <p>{item.repeater_subtitle}</p>
                                    </div>
                                ))}
                            </div>
                        ) }
                        { ["popup_notification_theme-four", "popup_notification_theme-five"]
                            .some(theme => settings.theme.includes(theme)) && (
                                <div className="nx-popup-textarea">
                                    <textarea
                                        placeholder={settings?.popup_message}
                                    />
                                </div>
                        ) }
                        { ["popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                            .some(theme => settings.theme.includes(theme)) && (
                                <div className="nx-popup-email">
                                    <input type="email" placeholder={settings?.popup_email_placeholder} />
                                </div>
                        ) }
                        {/* Action Buttons */}
                        {(settings?.popup_button_text) && (
                            <div className="nx-popup-actions">
                                <button
                                    className="nx-popup-button nx-popup-primary-button"
                                    style={buttonStyles}
                                    onClick={handleButtonClick}
                                >
                                    {settings?.popup_button_text}
                                </button>
                            </div>
                        )}

                    </div>

                    {/* Footer Section */}
                    { (!settings?.disable_powered_by) && (
                        <div className="nx-popup-footer">
                            {!settings?.disable_powered_by && (
                                <div className="nx-popup-branding">
                                    <span>{ __('Powered by NotificationX', 'notificationx') }</span>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default Popup
