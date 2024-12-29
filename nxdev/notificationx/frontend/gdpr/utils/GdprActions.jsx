import React, { useState, useEffect, Fragment } from 'react';
import ReactModal from "react-modal";
import { modalStyle } from '../../../core/constants';
import { __ } from '@wordpress/i18n';
import Customization from '../Customization';
import CloseIcon from '../../../icons/Close';
import { isValidScriptContent, loadScripts, processScriptContent, setDynamicCookie } from './helper';

const GdprActions = ({ settings, onConsentGiven }) => {
    const [isOpenCustomizationModal, setIsOpenGdprCustomizationModal] = useState(false);
    const [enabledItem, setEnabledItem] = useState([]);
    const [consent, setConsent] = useState({
        necessary: true,
        functional: false,
        analytics: false,
        performance: false,
        uncategorized: false,
    });

    let acceptBtnStyles = {};
    let customizeBtnStyles = {};
    let rejectBtnStyles = {};
    if ( settings?.advance_edit ) {
        acceptBtnStyles = {
            backgroundColor: settings?.gdpr_accept_btn_bg_color,
            color: settings?.gdpr_accept_btn_text_color,
            fontSize: settings?.gdpr_accept_btn_font_size,
            border: `1px solid ${settings?.gdpr_accept_btn_border_color}`,
        };

        customizeBtnStyles = {
            backgroundColor: settings?.gdpr_customize_btn_bg_color,
            color: settings?.gdpr_customize_btn_text_color,
            fontSize: settings?.gdpr_customize_btn_font_size,
            border: `1px solid ${settings?.gdpr_customize_btn_border_color}`,
        };

        rejectBtnStyles = {
            backgroundColor: settings?.gdpr_reject_btn_bg_color,
            color: settings?.gdpr_reject_btn_text_color,
            fontSize: settings?.gdpr_reject_btn_font_size,
            border: `1px solid ${settings?.gdpr_reject_btn_border_color}`,
        };
    }

    // Function to get WP Consent status from cookie
    const getWpConsentStatus = () => {
        // Check if WP Consent cookie exists
        const consentCookie = document.cookie.split('; ').find(row => row.startsWith('wpconsent='));
        if (!consentCookie) return null;

        const consentValue = consentCookie.split('=')[1];
        try {
            const parsedConsent = JSON.parse(decodeURIComponent(consentValue));
            return parsedConsent;
        } catch (error) {
            return null;
        }
    };

    // Use effect to initialize consent from WP Consent API (if available)
    useEffect(() => {
        const wpConsent = getWpConsentStatus();
        if (wpConsent) {
            setConsent({
                necessary: wpConsent.necessary || false,
                functional: wpConsent.functional || false,
                analytics: wpConsent.analytics || false,
                performance: wpConsent.performance || false,
                uncategorized: wpConsent.uncategorized || false,
            });
        }
    }, []);

    const handleCookieAccept = () => {
        const newConsent = {
            necessary    : true,
            functional   : true,
            analytics    : true,
            performance  : true,
            uncategorized: true,
        };
        setConsent(newConsent);
    
        // Save consent for each type
        Object.entries(newConsent).forEach(([type, value]) => {
            setDynamicCookie(type, value);
        });

        // Initialize and load cookies/scripts based on custom consent
        loadScripts(settings?.necessary_cookie_lists);
        loadScripts(settings?.functional_cookie_lists);
        loadScripts(settings?.analytics_cookie_lists);
        loadScripts(settings?.performance_cookie_lists);
        loadScripts(settings?.uncategorized_cookie_lists);
        // Notify parent to hide popup
        onConsentGiven();
    };
    
    const handleCookieReject = () => {
        const newConsent = {
            necessary    : false,
            functional   : false,
            analytics    : false,
            performance  : false,
            uncategorized: false,
        };
        setConsent(newConsent);
    
        Object.entries(newConsent).forEach(([type, value]) => {
            setDynamicCookie(type, value);
        });

        // Notify parent to hide popup
        onConsentGiven();
    };
    
    const handleCustomizedConsent = (customConsent) => {
        setConsent(customConsent);
    
        // Save consent for each type
        Object.entries(customConsent).forEach(([type, value]) => {
            setDynamicCookie(type, value);
        });
    
        // Initialize and load cookies/scripts based on custom consent
        loadScripts(settings?.necessary_cookie_lists);
        loadScripts(settings?.functional_cookie_lists);
        if (customConsent.analytics) loadScripts(settings?.analytics_cookie_lists);
        if (customConsent.performance) loadScripts(performance_cookie_lists);
        if (customConsent.uncategorized) loadScripts(settings?.uncategorized_cookie_lists);
    };
    
    return (
        <Fragment>
            <div className="nx-gdpr-actions">
                <div className="button-group">
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={handleCookieAccept}
                        style={acceptBtnStyles}
                    >
                        {settings?.gdpr_accept_btn}
                    </button>
                    <button
                        type="button"
                        onClick={() => setIsOpenGdprCustomizationModal(!isOpenCustomizationModal)}
                        className="btn btn-secondary"
                        style={customizeBtnStyles}
                    >
                        {settings?.gdpr_customize_btn}
                    </button>
                </div>
                <div className="button-single">
                    <button
                        type="button"
                        className="btn btn-danger"
                        onClick={handleCookieReject}
                        style={rejectBtnStyles}
                    >
                        {settings?.gdpr_reject_btn}
                    </button>
                </div>
                <ReactModal
                    isOpen={isOpenCustomizationModal}
                    onRequestClose={() => setIsOpenGdprCustomizationModal(false)}
                    className={`nx-gdpr-customization`}
                    style={modalStyle}
                    ariaHideApp={false}
                >
                    <Customization
                        settings={settings}
                        onEnableCookiesItem={setEnabledItem}
                        onSaveConsent={handleCustomizedConsent}
                        onHandleAccept={handleCookieAccept}
                        onHandleReject={handleCookieReject}
                    />
                    <button
                        type="button"
                        onClick={() => setIsOpenGdprCustomizationModal(false)}
                        className="nx-gdpr-customization-close"
                        aria-label="Close"
                    >
                        <CloseIcon />
                    </button>
                </ReactModal>
            </div>
        </Fragment>
        
    );
};

export default GdprActions;
