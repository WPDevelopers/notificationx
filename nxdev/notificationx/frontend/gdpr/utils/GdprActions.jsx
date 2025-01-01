import React, { useState, useEffect, Fragment } from 'react';
import ReactModal from "react-modal";
import { modalStyle } from '../../../core/constants';
import { __ } from '@wordpress/i18n';
import Customization from '../Customization';
import CloseIcon from '../../../icons/Close';
import { loadScripts, setDynamicCookie } from './helper';
import nxHelper from '../../../core/functions';

const GdprActions = ({ settings, onConsentGiven, setIsVisible }) => {
    const themesWithCloseBtn = ['gdpr_theme-light-one', 'gdpr_theme-light-three', 'gdpr_theme-dark-one', 'gdpr_theme-dark-three', 'gdpr_theme-banner-light-two', 'gdpr_theme-banner-dark-two'];
    const isCloseBtnVisible = themesWithCloseBtn.includes(settings?.theme);
    const [isOpenCustomizationModal, setIsOpenGdprCustomizationModal] = useState(false);
    const [enabledItem, setEnabledItem] = useState([]);
    const COOKIE_EXPIRY_DAYS = settings?.gdpr_consent_expiry;
    let acceptBtnStyles = {};
    let customizeBtnStyles = {};
    let rejectBtnStyles = {};
    let closeBtnStyle = {
        color: settings?.close_btn_color,
        fontSize: settings?.close_btn_size,
    };
    
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

    const handleCookieAccept = () => {        
        const newConsent = {
            necessary    : true,
            functional   : true,
            analytics    : true,
            performance  : true,
            uncategorized: true,
        };
        // Save consent for each type
        Object.entries(newConsent).forEach(([type, value]) => {
            setDynamicCookie(type, value, COOKIE_EXPIRY_DAYS);
        });

        // Initialize and load cookies/scripts based on custom consent
        loadScripts(settings?.analytics_cookie_lists);
        loadScripts(settings?.performance_cookie_lists);
        loadScripts(settings?.uncategorized_cookie_lists);
        // Notify parent to hide popup
        onConsentGiven();
        if( settings?.gdpr_force_reload ) {
            // Reloads the current page
            location.reload();
        }
        setIsVisible(false);
        setIsOpenGdprCustomizationModal(false);
    };
    
    const handleCookieReject = () => {
        if( settings?.gdpr_cookie_removal ) {
            delete_cookies();
        }
        const newConsent = {
            necessary    : true,
            functional   : true,
            analytics    : false,
            performance  : false,
            uncategorized: false,
        };
        setTimeout(() => {
            Object.entries(newConsent).forEach(([type, value]) => {
                setDynamicCookie(type, value, COOKIE_EXPIRY_DAYS);
            });
        }, 500);
        // Notify parent to hide popup
        onConsentGiven();
        setIsVisible(false);
        setIsOpenGdprCustomizationModal(false);
    };

    const delete_cookies = async () => {
        nxHelper
        .get(`index.php?rest_route=/notificationx/v1/delete-cookies/`)
        .catch((err) => console.error("Fetch Error: ", err));
    }

    const handleCustomizedConsent = (customConsent) => {    
        // Save consent for each type
        Object.entries(enabledItem).forEach(([type, value]) => {
            setDynamicCookie(type, value, COOKIE_EXPIRY_DAYS);
        });
    
        // Initialize and load cookies/scripts based on custom consent
        loadScripts(settings?.necessary_cookie_lists);
        loadScripts(settings?.functional_cookie_lists);
        if (customConsent.analytics) loadScripts(settings?.analytics_cookie_lists);
        if (customConsent.performance) loadScripts(settings?.performance_cookie_lists);
        if (customConsent.uncategorized) loadScripts(settings?.uncategorized_cookie_lists);
        if( settings?.gdpr_force_reload ) {
            // Reloads the current page
            location.reload();
        }
        setIsOpenGdprCustomizationModal(false);
        setIsVisible(false);
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
                {!isCloseBtnVisible &&
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
                }
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
                        onHandleAccept={handleCookieAccept}
                        onSaveConsent={handleCustomizedConsent}
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
            {isCloseBtnVisible &&
                <button style={closeBtnStyle} type="button" className="nx-gdpr-close" aria-label="Close" onClick={() => handleCookieReject()}>
                    <CloseIcon/>
                </button>
            }
        </Fragment>
        
    );
};

export default GdprActions;
