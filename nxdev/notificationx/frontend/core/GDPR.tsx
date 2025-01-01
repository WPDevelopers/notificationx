import React, { useEffect, useState } from 'react'
import usePortal from '../hooks/usePortal';
import classNames from 'classnames';
import { isAdminBar } from './utils';
import { createPortal } from 'react-dom';
import GdprActions from '../gdpr/utils/GdprActions';
import GdprFooter from '../gdpr/utils/GdprFooter';
import CloseIcon from '../../icons/Close';
import { getDynamicCookie, loadScripts } from '../gdpr/utils/helper';

const GDPR = ({ position, gdpr, dispatch }) => {
    const target = usePortal(`nx-gdpr-${position}`, position == 'bottom_left', true);
    const { config: settings, data: content } = gdpr; 
    const [isVisible, setIsVisible] = useState(false);
       
    
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
            functional: getDynamicCookie('functional') ?? true,
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
        setIsVisible(false); // Hide GDPR popup
    };

    if (!isVisible) {
        return null; // Hide GDPR banner if consent is complete
    }

    const wrapper = (
        // @todo advanced style.
        <div
            id={`nx-gdpr-${settings.nx_id}`}
            className={classNames(
                `nx-gdpr`,
                settings.themes,
                settings?.themes?.includes('banner') ? `banner-gdpr banner-gdpr-${settings?.gdpr_theme}` : `card-gdpr card-gdpr-${settings?.gdpr_theme}`,
                settings?.gdpr_theme ? 'dark' : 'light',
                `nx-gdpr-${settings.nx_id}`,

                {
                    "nx-position-top": "top" == settings?.position,
                    "nx-position-bottom":
                        "bottom" == settings?.position,
                    [`nx-close-${settings?.bar_close_position}`]: settings?.bar_close_position,
                    "nx-admin": isAdminBar(),
                    "nx-sticky-bar": settings?.sticky_bar,
                    "nx-gdpr-has-elementor": settings?.elementor_id,
                    "nx-gdpr-has-gutenberg": settings?.gutenberg_id,
                }
            )}
        >
            <div className="nx-gdpr">
                <div className="nx-gdpr-card">
                    {/* Header Section */}
                    <div className="nx-gdpr-card-header">
                        { settings?.gdpr_custom_logo &&
                            <img src={settings?.gdpr_custom_logo?.url} alt={settings?.gdpr_custom_logo?.title} className="nx-gdpr-logo" />
                        }
                        <h3 className="nx-gdpr-title">{settings?.gdpr_title}</h3>
                    </div>

                    {/* Content Section */}
                    <div className="nx-gdpr-card-body">
                        <p className="nx-gdpr-description">
                            {settings?.gdpr_message}
                            { settings?.gdpr_cookies_policy_toggle &&
                                <a href={settings?.gdpr_cookies_policy_link_url} target='_blank' className="nx-gdpr-link">{ settings?.gdpr_cookies_policy_link_text }</a>
                            }
                        </p>
                        {/* @ts-ignore  */}
                        <GdprActions settings={settings} onConsentGiven={handleConsentGiven} setIsVisible={setIsVisible} />
                    </div>
                   <GdprFooter settings={settings} />

                    {/* Close Icon */}
                    
                </div>
            </div>
        </div>
    );
    return createPortal(wrapper, target);
}

export default GDPR