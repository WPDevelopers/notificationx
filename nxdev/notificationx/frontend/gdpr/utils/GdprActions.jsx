import React, { useState, Fragment } from 'react';
import ReactModal from "react-modal";
import { modalStyle } from '../../../core/constants';
import { __ } from '@wordpress/i18n';
import Customization from '../Customization';
import CloseIcon from '../../../icons/Close';
import { loadScripts, setDynamicCookie } from './helper';
import nxHelper from '../../../core/functions';

const GdprActions = ({ settings, onConsentGiven, setIsVisible }) => {
    const themesWithCloseBtn = [
        'gdpr_theme-light-one',
        'gdpr_theme-light-three',
        'gdpr_theme-dark-one',
        'gdpr_theme-dark-three',
        'gdpr_theme-banner-light-two',
        'gdpr_theme-banner-dark-two',
    ];
    const initialSavePreference = {
        necessary: true,
        functional: false,
        analytics: false,
        performance: false,
        uncategorized: false,
    };

    const [isOpenCustomizationModal, setIsOpenCustomizationModal] = useState(false);
    const [enabledItem, setEnabledItem] = useState(initialSavePreference);

    const COOKIE_EXPIRY_DAYS = settings?.gdpr_consent_expiry;
    const isCloseBtnVisible = themesWithCloseBtn.includes(settings?.theme);

    const getButtonStyles = (type) => {
        const styles = settings?.advance_edit
            ? {
                  accept: {
                      backgroundColor: settings?.gdpr_accept_btn_bg_color,
                      color: settings?.gdpr_accept_btn_text_color,
                      fontSize: settings?.gdpr_accept_btn_font_size,
                      border: `1px solid ${settings?.gdpr_accept_btn_border_color}`,
                  },
                  customize: {
                      backgroundColor: settings?.gdpr_customize_btn_bg_color,
                      color: settings?.gdpr_customize_btn_text_color,
                      fontSize: settings?.gdpr_customize_btn_font_size,
                      border: `1px solid ${settings?.gdpr_customize_btn_border_color}`,
                  },
                  reject: {
                      backgroundColor: settings?.gdpr_reject_btn_bg_color,
                      color: settings?.gdpr_reject_btn_text_color,
                      fontSize: settings?.gdpr_reject_btn_font_size,
                      border: `1px solid ${settings?.gdpr_reject_btn_border_color}`,
                  },
              }
            : {};
        return styles[type] || {};
    };

    const handleConsent = (consent) => {
        Object.entries(consent).forEach(([type, value]) =>
            setDynamicCookie(type, value, COOKIE_EXPIRY_DAYS)
        );
        if (settings?.gdpr_force_reload) location.reload();
        if (settings?.animation_notification_hide === 'default' && settings?.animation_notification_show === 'default') {
            setIsVisible(false);
        }
        setIsOpenCustomizationModal(false);
        onConsentGiven();
    };
    
    const handleCookieAccept = () => {
        handleConsent({
            necessary    : true,
            functional   : true,
            analytics    : true,
            performance  : true,
            uncategorized: true,
        });
        loadScripts(settings?.functional_cookie_lists);
        loadScripts(settings?.analytics_cookie_lists);
        loadScripts(settings?.performance_cookie_lists);
        loadScripts(settings?.uncategorized_cookie_lists);
    };

    const handleCookieReject = () => {
        if (settings?.gdpr_cookie_removal) deleteCookies();
        handleConsent(initialSavePreference);
    };

    const deleteCookies = async () => {
        try {
            await nxHelper.get(`index.php?rest_route=/notificationx/v1/delete-cookies/`);
        } catch (err) {
            console.error("Error deleting cookies: ", err);
        }
    };

    const handleCustomizedConsent = () => {
        handleConsent(enabledItem);
        loadScripts(settings?.necessary_cookie_lists);
        if (enabledItem.functional) loadScripts(settings?.functional_cookie_lists);
        if (enabledItem.analytics) loadScripts(settings?.analytics_cookie_lists);
        if (enabledItem.performance) loadScripts(settings?.performance_cookie_lists);
        if (enabledItem.uncategorized) loadScripts(settings?.uncategorized_cookie_lists);
    };

    const toggleCustomizationModal = () => {
        setIsOpenCustomizationModal((prev) => !prev);
        document.querySelectorAll('.nx-gdpr').forEach((el) => (el.style.display = 'none'));
    };

    return (
        <Fragment>
            <div className="nx-gdpr-actions">
                <div className="button-group">
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={handleCookieAccept}
                        style={getButtonStyles('accept')}
                    >
                        {settings?.gdpr_accept_btn}
                    </button>
                    <button
                        type="button"
                        className="btn btn-secondary"
                        onClick={toggleCustomizationModal}
                        style={getButtonStyles('customize')}
                    >
                        {settings?.gdpr_customize_btn}
                    </button>
                </div>
                {!isCloseBtnVisible && (
                    <div className="button-single">
                        <button
                            type="button"
                            className="btn btn-danger"
                            onClick={handleCookieReject}
                            style={getButtonStyles('reject')}
                        >
                            {settings?.gdpr_reject_btn}
                        </button>
                    </div>
                )}
                <ReactModal
                    isOpen={isOpenCustomizationModal}
                    className="nx-gdpr-customization"
                    style={modalStyle}
                    ariaHideApp={false}
                >
                    <Customization
                        settings={settings}
                        onEnableCookiesItem={setEnabledItem}
                        onHandleAccept={handleCookieAccept}
                        onSaveConsent={handleCustomizedConsent}
                        onHandleReject={handleCookieReject}
                        setIsOpenGdprCustomizationModal={setIsOpenCustomizationModal}
                    />
                </ReactModal>
            </div>
            {isCloseBtnVisible && (
                <button
                    style={{
                        color: settings?.close_btn_color,
                        fontSize: settings?.close_btn_size,
                    }}
                    type="button"
                    className="nx-gdpr-close"
                    aria-label="Close"
                    onClick={handleCookieReject}
                >
                    <CloseIcon />
                </button>
            )}
        </Fragment>
    );
};

export default GdprActions;
