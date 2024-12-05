import React, { useState, useEffect } from 'react';
import ReactModal from "react-modal";
import { modalStyle } from '../../../core/constants';
import { __ } from '@wordpress/i18n';
import Customization from '../Customization';
import CloseIcon from '../../../icons/Close';

const GdprActions = ({ settings }) => {
    const [isOpenCustomizationModal, setIsOpenGdprCustomizationModal] = useState(false);
    const [enabledItem, setEnabledItem] = useState([]);
    const [consent, setConsent] = useState({
        necessary: true,
        functional: false,
        analytics: false,
        performance: false,
        uncategorized: false,
    });

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

    // Example cookie lists
    const necessaryCookieList = [
        { cookies_id: "csrf_token", domain: "secure.example.com", duration: "2 Hour", description: "Prevents CSRF attacks", enabled: true },
    ];

    const functionalCookieList = [
        { cookies_id: "user_session", domain: "example.com", duration: "30 Days", description: "User session cookie" },
    ];

    const analyticsCookieList = [
        { 
            cookies_id: "ga_tracking", 
            domain: "example.com", 
            duration: "365 Days", 
            description: "Google Analytics tracking cookie", 
            script_url_pattern: "https://www.google-analytics.com/analytics.js" 
        },
    ];

    const performanceCookieList = [
        { cookies_id: "perf_tracking", domain: "example.com", duration: "30 Days", description: "Performance tracking cookie" },
    ];

    const uncategorizedCookieList = [
        { cookies_id: "unknown_id", domain: "example.com", duration: "7 Days", description: "Uncategorized cookie" },
    ];

    // Helper: Set a browser cookie
    const setCookie = (name, value, days) => {
        const expires = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString();
        document.cookie = `${name}=${value}; expires=${expires}; path=/;`;
    };

    // Helper: Parse duration
    const parseDuration = (duration) => {
        const [value, unit] = duration.split(" ");
        return unit.toLowerCase() === "days" ? parseInt(value, 10) : parseInt(value, 10) / 24;
    };

    // Helper: Initialize cookies
    const initializeCookies = (cookieList) => {
        cookieList.forEach(cookie => {
            setCookie(cookie.cookies_id, cookie.description, parseDuration(cookie.duration));
        });
    };

    // Helper: Dynamically load scripts
    const loadScripts = (cookieList) => {
        cookieList.forEach(cookie => {
            if (cookie.script_url_pattern) {
                const script = document.createElement('script');
                script.src = cookie.script_url_pattern;
                script.async = true;
                document.head.appendChild(script);
            }
        });
    };

    // Handle full cookie acceptance
    const handleCookieAccept = () => {
        const newConsent = {
            necessary: true,
            functional: true,
            analytics: true,
            performance: true,
            uncategorized: true,
        };
        setConsent(newConsent);

        // Initialize and load all cookies and scripts
        initializeCookies(necessaryCookieList);
        initializeCookies(functionalCookieList);
        initializeCookies(analyticsCookieList);
        initializeCookies(performanceCookieList);
        initializeCookies(uncategorizedCookieList);

        loadScripts(analyticsCookieList); // Dynamically load analytics scripts

        setCookie('wpconsent', JSON.stringify(newConsent), 365); // Save consent to wpconsent cookie
        alert("All cookies accepted.");
    };

    // Handle cookie rejection
    const handleCookieReject = () => {
        const newConsent = {
            necessary: true,
            functional: false,
            analytics: false,
            performance: false,
            uncategorized: false,
        };
        setConsent(newConsent);

        // Initialize only necessary cookies
        initializeCookies(necessaryCookieList);

        setCookie('wpconsent', JSON.stringify(newConsent), 365); // Save consent to wpconsent cookie
        alert("Only necessary cookies enabled.");
    };

    // Handle custom consent
    const handleCustomizedConsent = (customConsent) => {
        setConsent(customConsent);

        // Initialize and load cookies based on consent
        initializeCookies(necessaryCookieList);
        if (customConsent.functional) initializeCookies(functionalCookieList);
        if (customConsent.analytics) {
            initializeCookies(analyticsCookieList);
            loadScripts(analyticsCookieList); // Load analytics scripts if consented
        }
        if (customConsent.performance) initializeCookies(performanceCookieList);
        if (customConsent.uncategorized) initializeCookies(uncategorizedCookieList);

        setCookie('wpconsent', JSON.stringify(customConsent), 365); // Save custom consent to wpconsent cookie
        alert("Custom cookie preferences saved.");
    };

    return (
        <div className="nx-gdpr-actions">
            <div className="button-group">
                <button
                    type="button"
                    className="btn btn-primary"
                    onClick={handleCookieAccept}
                >
                    {settings?.gdpr_accept_btn}
                </button>
                <button
                    type="button"
                    onClick={() => setIsOpenGdprCustomizationModal(!isOpenCustomizationModal)}
                    className="btn btn-secondary"
                >
                    {settings?.gdpr_customize_btn}
                </button>
            </div>
            <div className="button-single">
                <button
                    type="button"
                    className="btn btn-danger"
                    onClick={handleCookieReject}
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
    );
};

export default GdprActions;
