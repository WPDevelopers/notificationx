import React, { useEffect, useRef, useState } from 'react'
import usePortal from '../hooks/usePortal';
import classNames from 'classnames';
import { isAdminBar } from './utils';
import { createPortal } from 'react-dom';
import GdprActions from '../gdpr/utils/GdprActions';
import GdprFooter from '../gdpr/utils/GdprFooter';
import CloseIcon from '../../icons/Close';
import { getDynamicCookie, loadScripts } from '../gdpr/utils/helper';
import useNotificationContext from "./NotificationProvider";
import 'animate.css';
import { getThemeName, isObject, calculateAnimationStartTime, getResThemeName } from "../core/functions";

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

const GDPR = (props) => {
    const { position, gdpr, dispatch } = props;
    const target = usePortal(`nx-gdpr-${position}`, position == 'cookie_notice_bottom_left', true);
    const { config: settings, data: content } = gdpr; 
    const frontEndContext = useNotificationContext();
    const [isVisible, setIsVisible] = useState(false); 
    const isMobile = useMediaQuery("(max-width: 480px)");
    const isTablet = useMediaQuery("(max-width: 768px)"); 
    const [notificationSize, setNotificationSize] = useState();
    const [animation, setAnimation] = useState(false);
    const is_pro = frontEndContext?.state?.is_pro ?? false;
    let mainBGColor = {};
    let titleColorFont = {};
    let descColorFont = {};

    if ( settings?.advance_edit ) {
        mainBGColor = {
            backgroundColor: settings?.gdpr_design_bg_color,
        };
        titleColorFont = {
            color: settings?.title_text_color,
            fontSize: settings?.title_font_size,
        };
        descColorFont = {
            color: settings?.description_text_color,
            fontSize: settings?.description_text_size,
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

    const handleGDPRBanner = () => {
        const areCookiesSet = document.cookie.split(';').some(cookie => cookie.trim().startsWith(`nx_cookie_manager=`));
        if (areCookiesSet) {
            setIsVisible(false);
        } else {
            setIsVisible(true);
        }
    };
     // Check consent state on mount
     useEffect(() => {
        const consent = {
            necessary: getDynamicCookie('necessary') ?? true,
            functional: getDynamicCookie('functional') ?? false,
            analytics: getDynamicCookie('analytics') ?? false,
            performance: getDynamicCookie('performance') ?? false,
            uncategorized: getDynamicCookie('uncategorized') ?? false,
        };

        // Show banner only if consent is incomplete
        const isConsentIncomplete = Object.values(consent).some((value) => value === false);
        setIsVisible(isConsentIncomplete);
        handleGDPRBanner();
        if (consent.necessary) loadScripts(settings?.necessary_cookie_lists);
        if (consent.functional) loadScripts(settings?.functional_cookie_lists);
        if (consent.analytics) loadScripts(settings?.analytics_cookie_lists);
        if (consent.performance) loadScripts(settings?.performance_cookie_lists);
        if (consent.uncategorized) loadScripts(settings?.uncategorized_cookie_lists);
    }, []);

    const handleConsentGiven = () => {
        setAnimation(true);
    };

    if (!isVisible) {
        return null; // Hide GDPR banner if consent is complete
    }

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
    const componentCSS: any = {};
    const componentStyle: any = {
        ...componentCSS,
        maxWidth: `${notificationSize}px`,
        ...getAnimationStyles(),
    };
    if (settings?.advance_edit && settings?.conversion_size) {
        componentStyle.maxWidth = settings?.conversion_size;
    }

    let baseClasses = [
        `nx-gdpr`,
        position,
        settings?.gdpr_banner_position,
        settings.themes,
        settings?.themes?.includes('banner') ? `banner-gdpr banner-gdpr-${settings?.gdpr_theme}` : `card-gdpr card-gdpr-${settings?.gdpr_theme}`,
        settings?.gdpr_theme ? 'dark' : 'light',
        `nx-gdpr-${settings.nx_id}`,

        {
            "nx-position-top": "top" == settings?.position,
            "nx-position-bottom":
                "bottom" == settings?.position,
            // exit: exit,
            [`nx-close-${settings?.bar_close_position}`]: settings?.bar_close_position,
            "nx-admin": isAdminBar(),
            "nx-sticky-bar": settings?.sticky_bar,
            "nx-gdpr-has-elementor": settings?.elementor_id,
            "nx-gdpr-has-gutenberg": settings?.gutenberg_id,
        }
    ];

    let componentClasses;
    let animationStyle = 'SlideTop 300ms';
    if ( (is_pro && settings?.animation_notification_show !== 'default') || (is_pro && settings?.animation_notification_hide !== 'default') ) {
        let animate_effect;
        if( settings?.animation_notification_hide !== 'default' && settings?.animation_notification_show === 'default' ) {
            if( animation ) {
                animate_effect = settings?.animation_notification_hide;
            }else{
                componentStyle.animation = animationStyle
            }
        }else if( settings?.animation_notification_show !== 'default' && settings?.animation_notification_hide === 'default' ) {
            if( animation ) {
                componentStyle.animation = animationStyle;
            }else {
                animate_effect = settings?.animation_notification_show;
            }
        }else {            
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
    const wrapper = (
        // @todo advanced style.
        <div
            id={`nx-gdpr-${settings.nx_id}`}
            className={componentClasses}
        >
            <div className="nx-gdpr">
                <div className={`nx-gdpr-card ${settings?.disable_powered_by ? 'no-branding' : '' }`}>
                
                    {/* Header Section */}
                    <div className="nx-gdpr-card-header" style={mainBGColor}>
                        { settings?.gdpr_custom_logo &&
                            <img src={settings?.gdpr_custom_logo?.url} alt={settings?.gdpr_custom_logo?.title} className="nx-gdpr-logo" />
                        }
                        <h3 className="nx-gdpr-title" style={titleColorFont}>{settings?.gdpr_title}</h3>
                    </div>

                    {/* Content Section */}
                    <div className="nx-gdpr-card-body" style={mainBGColor}>
                        <p className="nx-gdpr-description" style={descColorFont}>
                            {settings?.gdpr_message}
                            { settings?.gdpr_cookies_policy_toggle && settings?.gdpr_cookies_policy_link_url &&
                                <a href={settings?.gdpr_cookies_policy_link_url} target='_blank' className="nx-gdpr-link">{ settings?.gdpr_cookies_policy_link_text }</a>
                            }
                        </p>
                        {/* @ts-ignore  */}
                        <GdprActions settings={settings} onConsentGiven={handleConsentGiven} setIsVisible={setIsVisible} />
                    </div>
                   <GdprFooter settings={settings} />
                </div>
            </div>
        </div>
    );
    return createPortal(wrapper, target);
}

export default GDPR