import React, { useEffect, useState } from 'react'
import classNames from 'classnames';
import { isAdminBar } from './utils';
import CloseIcon from '../../icons/Close';
import useNotificationContext from "./NotificationProvider";
import 'animate.css';
import { isObject, handleClosePopup } from "../core/functions";
import { getIconUrl } from "../../core/functions";

import { __ } from '@wordpress/i18n';
import nxHelper from './functions';
import NXBranding from '../themes/helpers/NXBranding';
import PopupHeader from './helper/PopupHeader';

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
    const [isButtonHovered, setIsButtonHovered] = useState(false);
    const [focusedField, setFocusedField] = useState<string | null>(null);

    // Form state
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        message: ''
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitSuccess, setSubmitSuccess] = useState(false);
    const [validationErrors, setValidationErrors] = useState({
        name: '',
        email: '',
        message: ''
    });

    // Check if popup was closed in this session
    useEffect(() => {
        const cookieKey = "notificationx_popup_" + settings?.nx_id;
        const wasClosed = sessionStorage.getItem(cookieKey);

        if (wasClosed === 'closed') {
            // Popup was closed in this session, don't show it
            setIsVisible(false);
            if (dispatch) {
                dispatch({
                    type: "REMOVE_NOTIFICATION",
                    payload: nxPopup.id,
                });
            }
        }
    }, [settings?.nx_id, dispatch, nxPopup.id]);

    // Dynamic styles based on settings
    let mainBGColor = {};
    let titleColorFont = {};
    let subtitleColorFont = {};
    let descColorFont = {};
    let buttonStyles = {};
    let overlayStyles = {};
    let inputStyles = {};
    let textareaStyles = {};

    if (settings?.advance_edit) {
        mainBGColor = {
            backgroundColor: settings?.popup_bg_color,
            borderRadius: settings?.popup_border_radius ? `${settings.popup_border_radius}px` : '',
            padding: settings?.popup_padding || '',
        };
        titleColorFont = {
            color: settings?.popup_title_color,
            fontSize: settings?.popup_title_font_size ? `${settings.popup_title_font_size}px` : '',
            fontWeight: settings?.popup_title_font_weight ? `${settings.popup_title_font_weight}` : '',
        };
        subtitleColorFont = {
            color: settings?.popup_subtitle_color,
            fontSize: settings?.popup_subtitle_font_size ? `${settings.popup_subtitle_font_size}px` : '',
        };
        descColorFont = {
            color: settings?.popup_content_color,
            fontSize: settings?.popup_content_font_size ? `${settings.popup_content_font_size}px` : '',
        };
        buttonStyles = {
            background: settings?.popup_button_bg_color,
            color: settings?.popup_button_text_color,
            borderColor: settings?.popup_button_border_color,
            borderRadius: settings?.popup_button_border_radius ? `${settings.popup_button_border_radius}px` : '',
            padding: settings?.popup_button_padding || '',
            fontSize: settings?.popup_button_font_size ? `${settings.popup_button_font_size}px` : '',
            fontWeight: settings?.popup_button_font_weight || '',
            border: `${settings?.popup_button_border_width || 1}px solid ${settings?.popup_button_border_color || settings?.popup_button_bg_color}`,
            width: settings?.popup_button_width === 'custom' ? `${settings?.popup_button_custom_width || 200}px` :
                   settings?.popup_button_width === '100%' ? '100%' : 'auto',
        };
        overlayStyles = {
            backgroundColor: settings?.overlay_color || '',
        };

        // Input field styles
        inputStyles = {
            backgroundColor: settings?.popup_email_bg_color || '',
            color: settings?.popup_email_text_color || '',
            borderColor: settings?.popup_email_border_color || '',
            borderWidth: settings?.popup_email_border_width ? `${settings.popup_email_border_width}px` : '',
            borderRadius: settings?.popup_email_border_radius ? `${settings.popup_email_border_radius}px` : '',
            padding: settings?.popup_email_padding || '',
            fontSize: settings?.popup_email_font_size ? `${settings.popup_email_font_size}px` : '',
            height: settings?.popup_email_height ? `${settings.popup_email_height}px` : '',
            border: `${settings?.popup_email_border_width || ''}px solid ${settings?.popup_email_border_color || ''}`,
            // CSS custom properties for placeholder styling
            '--placeholder-color': settings?.popup_email_placeholder_color || '',
        } as React.CSSProperties & { [key: string]: any };

        // Textarea styles (same as input but can be extended)
        textareaStyles = {
            ...inputStyles,
            height: 'auto', // Override height for textarea
            minHeight: '80px',
            resize: 'vertical',
        } as React.CSSProperties & { [key: string]: any };
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
        // Store in session storage to prevent showing again in this session
        handleClosePopup(settings, nxPopup.id, dispatch);

        setAnimation(true);
        setTimeout(() => {
            setIsVisible(false);
        }, 300);
    };

    const handleOverlayClick = (e: any) => {
        if (e.target === e.currentTarget && settings?.close_on_overlay_click) {
            handleClose();
        }
    };

    // Validation functions
    const validateEmail = (email: string) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    const validateForm = () => {
        const errors = {
            name: '',
            email: '',
            message: ''
        };

        // Validate name field if it's shown and required
        if (settings.popup_show_name_field && !formData.name.trim()) {
            errors.name = __('Please enter your name.', 'notificationx');
        }

        // Validate email field if it's shown
        if (settings.popup_show_email_field) {
            if (!formData.email.trim()) {
                errors.email = __('Please enter your email address.', 'notificationx');
            } else if (!validateEmail(formData.email)) {
                errors.email = __('Please enter a valid email address.', 'notificationx');
            }
        }

        // Validate message field if it's shown and required
        if (settings.popup_show_message_field && !formData.message.trim()) {
            errors.message = __('Please enter your message.', 'notificationx');
        }

        setValidationErrors(errors);
        return !errors.name && !errors.email && !errors.message;
    };

    const handleFormSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        // Check if this is an email collection theme
        const isEmailTheme = ["popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
            .some(theme => settings.themes.includes(theme));

        // Check if this is a message theme
        const isMessageTheme = ["popup_notification_theme-four", "popup_notification_theme-five"]
            .some(theme => settings.themes.includes(theme));

        if (!isEmailTheme && !isMessageTheme) {
            // For non-form themes, just handle button click
            handleButtonClick();
            return;
        }

        // Validate form before submission
        if (!validateForm()) {
            return;
        }

        setIsSubmitting(true);

        try {
            const submissionData = {
                nx_id: settings.nx_id,
                name: settings.popup_show_name_field ? formData.name : '',
                email: settings.popup_show_email_field ? formData.email : '',
                message: settings.popup_show_message_field ? formData.message : '',
                theme: settings.themes,
                title: settings.popup_title || '',
                timestamp: Math.floor(Date.now() / 1000)
            };

            // Submit to REST API
            const response = await nxHelper.post(
                nxHelper.getPath(frontEndContext?.rest, 'popup-submit'),
                submissionData
            );

            if (response && response.success) {
                setSubmitSuccess(true);
                // Store in session storage to prevent showing again in this session
                const cookieKey = "notificationx_popup_" + settings?.nx_id;
                sessionStorage.setItem(cookieKey, 'closed');

                // Close popup after successful submission
                setTimeout(() => {
                    handleClose();
                }, 1500);
            }
        } catch (error) {
            console.error('Form submission error:', error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleButtonClick = () => {
        if (settings?.popup_button_url) {
            window.open(settings.popup_button_url, settings?.open_in_new_tab ? '_blank' : '_self');
        }
        if (settings?.close_on_button_click) {
            // Store in session storage to prevent showing again in this session
            const cookieKey = "notificationx_popup_" + settings?.nx_id;
            sessionStorage.setItem(cookieKey, 'closed');
            handleClose();
        }
    };

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));

        // Clear validation error for this field when user starts typing
        if (validationErrors[field as keyof typeof validationErrors]) {
            setValidationErrors(prev => ({
                ...prev,
                [field]: ''
            }));
        }
    };

    // Get input styles with focus state
    const getInputStyles = (fieldName: string) => {
        if (!settings?.advance_edit) return {};

        const isFocused = focusedField === fieldName;
        return {
            ...inputStyles,
            borderColor: isFocused ? (settings?.popup_email_focus_border_color || settings?.popup_email_border_color) : (settings?.popup_email_border_color || '#dddddd'),
            border: `${settings?.popup_email_border_width || ''}px solid ${isFocused ? (settings?.popup_email_focus_border_color || settings?.popup_email_border_color) : (settings?.popup_email_border_color || '#dddddd')}`,
        };
    };

    // Get textarea styles with focus state
    const getTextareaStyles = (fieldName: string) => {
        if (!settings?.advance_edit) return {};

        const isFocused = focusedField === fieldName;
        return {
            ...textareaStyles,
            borderColor: isFocused ? (settings?.popup_email_focus_border_color || settings?.popup_email_border_color) : (settings?.popup_email_border_color || '#dddddd'),
            border: `${settings?.popup_email_border_width || 1}px solid ${isFocused ? (settings?.popup_email_focus_border_color || settings?.popup_email_border_color) : (settings?.popup_email_border_color || '#dddddd')}`,
        };
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
    const iconUrl = getIconUrl(settings?.popup_icon);
    const buttonIconUrl = getIconUrl(settings?.popup_button_icon);

    const finalButtonStyles = {
    ...buttonStyles,
    ...(isButtonHovered && {
        backgroundColor: settings?.popup_button_hover_bg_color || '',
        color: settings?.popup_button_hover_text_color || '',
        borderColor: settings?.popup_button_border_hover_color || '',
    })
};


    return (
        <>
            {/* Dynamic CSS for placeholder colors */}
            {settings?.advance_edit && settings?.popup_email_placeholder_color && (
                <style>
                    {`
                        #nx-popup-${settings.nx_id} input::placeholder,
                        #nx-popup-${settings.nx_id} textarea::placeholder {
                            color: ${settings.popup_email_placeholder_color} !important;
                        }
                    `}
                </style>
            )}
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
                            style={settings?.advance_edit ? {
                                color: settings?.close_btn_color || '',
                                fontSize: settings?.close_btn_size ? `${settings.close_btn_size}px` : '',
                                width: settings?.close_btn_size ? `${settings.close_btn_size}px` : '',
                                height: settings?.close_btn_size ? `${settings.close_btn_size}px` : '',
                            } : {}}
                        >
                            {settings?.close_button_icon ? (
                                <span dangerouslySetInnerHTML={{ __html: settings.close_button_icon }} />
                            ) : (
                                <CloseIcon />
                            )}
                        </button>
                    )}

                    {/* Header Section */}
                    {!["popup_notification_theme-two"].some(theme => settings.themes.includes(theme)) && (
                        <PopupHeader
                            settings={settings}
                            iconUrl={iconUrl}
                            titleColorFont={titleColorFont}
                            subtitleColorFont={subtitleColorFont}
                            descColorFont={descColorFont}
                            content={content}
                        />
                    )}

                    {/* Content Section */}
                    <div className="nx-popup-content">
                        {(settings?.popup_content && !["popup_notification_theme-three", "popup_notification_theme-four", "popup_notification_theme-seven"].some(t => settings.themes.includes(t) ) ) && (
                            <div className="nx-popup-description" style={descColorFont}>
                                {settings?.popup_content && (
                                    <div dangerouslySetInnerHTML={{ __html: settings.popup_content }} />
                                )}
                                {content && !settings?.popup_content && (
                                    <div dangerouslySetInnerHTML={{ __html: content }} />
                                )}
                            </div>
                        )}

                        {["popup_notification_theme-two"].some(theme => settings.themes.includes(theme)) && (
                            <PopupHeader
                                settings={settings}
                                iconUrl={iconUrl}
                                titleColorFont={titleColorFont}
                                subtitleColorFont={subtitleColorFont}
                                descColorFont={descColorFont}
                                content={content}
                            />
                        )}
                        { settings.themes.includes("popup_notification_theme-three") && (
                            <div className="nx-popup-description" style={descColorFont}>
                                {(settings.popup_content_repeater && Array.isArray(settings.popup_content_repeater) && settings.popup_content_repeater.length > 0) ? (
                                    settings.popup_content_repeater.map((item: any, index: number) => {
                                        // Provide default highlight text for existing items that don't have it
                                        const defaultHighlightTexts = ['30% OFF', '50% OFF', 'LIMITED'];
                                        const highlightText = item.repeater_highlight_text || defaultHighlightTexts[index] || '30% OFF';

                                        return (
                                            <div
                                                className='des-item-wrap'
                                                key={index}
                                                style={settings?.advance_edit ? {
                                                    backgroundColor: settings?.popup_repeater_item_bg_color || '',
                                                    borderRadius: settings?.popup_repeater_item_border_radius ? `${settings.popup_repeater_item_border_radius}px` : '',
                                                    padding: settings?.popup_repeater_item_padding || '',
                                                    marginBottom: settings?.popup_repeater_item_spacing ? `${settings.popup_repeater_item_spacing}px` : '',
                                                } : {}}
                                            >
                                                <div className="nx-popup-title-wrapper">
                                                    {highlightText && highlightText.trim() !== '' && (
                                                        <span
                                                            className="nx-popup-highlight-text"
                                                            style={settings?.advance_edit ? {
                                                                color: settings?.popup_repeater_highlight_color || '',
                                                                fontSize: settings?.popup_repeater_title_font_size ? `${settings.popup_repeater_title_font_size}px` : '',
                                                                fontWeight: settings?.popup_repeater_title_font_weight || '',
                                                            } : {}}
                                                        >
                                                            {highlightText}
                                                        </span>
                                                    )}
                                                    <h3 style={settings?.advance_edit ? {
                                                        color: settings?.popup_repeater_title_color || '',
                                                        fontSize: settings?.popup_repeater_title_font_size ? `${settings.popup_repeater_title_font_size}px` : '',
                                                        fontWeight: settings?.popup_repeater_title_font_weight || '',
                                                    } : {}}>{item.repeater_title}</h3>
                                                </div>
                                                <p style={settings?.advance_edit ? {
                                                    color: settings?.popup_repeater_subtitle_color || '',
                                                    fontSize: settings?.popup_repeater_subtitle_font_size ? `${settings.popup_repeater_subtitle_font_size}px` : '',
                                                } : {}}>{item.repeater_subtitle}</p>
                                            </div>
                                        );
                                    })
                                ) : (
                                    // Fallback content if repeater is empty or not set
                                    <div
                                        className='des-item-wrap'
                                        style={settings?.advance_edit ? {
                                            backgroundColor: settings?.popup_repeater_item_bg_color || '',
                                            borderRadius: settings?.popup_repeater_item_border_radius ? `${settings.popup_repeater_item_border_radius}px` : '',
                                            padding: settings?.popup_repeater_item_padding || '',
                                            marginBottom: settings?.popup_repeater_item_spacing ? `${settings.popup_repeater_item_spacing}px` : '',
                                        } : {}}
                                    >
                                        <span
                                            className="nx-popup-highlight-text"
                                            style={settings?.advance_edit ? {
                                                color: settings?.popup_repeater_highlight_color || '#FF6B1B',
                                                fontSize: settings?.popup_repeater_title_font_size ? `${settings.popup_repeater_title_font_size}px` : '24px',
                                                fontWeight: settings?.popup_repeater_title_font_weight || 'bold',
                                                display: 'block',
                                                marginBottom: '8px',
                                                lineHeight: '1.2'
                                            } : {
                                                color: '#FF6B1B',
                                                fontSize: '24px',
                                                fontWeight: 'bold',
                                                display: 'block',
                                                marginBottom: '8px',
                                                lineHeight: '1.2'
                                            }}
                                        >
                                            30% OFF
                                        </span>
                                        <h3 style={settings?.advance_edit ? {
                                            color: settings?.popup_repeater_title_color || '',
                                            fontSize: settings?.popup_repeater_title_font_size ? `${settings.popup_repeater_title_font_size}px` : '',
                                            fontWeight: settings?.popup_repeater_title_font_weight || '',
                                            margin: '0 0 4px 0'
                                        } : { margin: '0 0 4px 0' }}>on all products!</h3>
                                        <p style={settings?.advance_edit ? {
                                            color: settings?.popup_repeater_subtitle_color || '',
                                            fontSize: settings?.popup_repeater_subtitle_font_size ? `${settings.popup_repeater_subtitle_font_size}px` : '',
                                            margin: '0 0 16px 0'
                                        } : { margin: '0 0 16px 0' }}>Limited time offer - don't miss out!</p>
                                    </div>
                                )}
                            </div>
                        ) }
                        {/* Name Field - Show if enabled for form themes */}
                        { ["popup_notification_theme-four", "popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                            .some(theme => settings.themes.includes(theme)) && settings.popup_show_name_field && (
                                <div className="nx-popup-name">
                                    <input
                                        type="text"
                                        placeholder={settings?.popup_name_placeholder || __('Enter your name', 'notificationx')}
                                        value={formData.name}
                                        onChange={(e) => handleInputChange('name', e.target.value)}
                                        onFocus={() => setFocusedField('name')}
                                        onBlur={() => setFocusedField(null)}
                                        disabled={isSubmitting}
                                        className={validationErrors.name ? 'nx-popup-input-error' : ''}
                                        style={getInputStyles('name')}
                                    />
                                    {validationErrors.name && (
                                        <div className="nx-popup-error-message">
                                            {validationErrors.name}
                                        </div>
                                    )}
                                </div>
                        ) }

                        {/* Email Field - Show if enabled for form themes */}
                        { ["popup_notification_theme-four", "popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                            .some(theme => settings.themes.includes(theme)) && settings.popup_show_email_field && (
                                <div className="nx-popup-email">
                                    <input
                                        type="email"
                                        placeholder={settings?.popup_email_placeholder || __('Enter your email address', 'notificationx')}
                                        value={formData.email}
                                        onChange={(e) => handleInputChange('email', e.target.value)}
                                        onFocus={() => setFocusedField('email')}
                                        onBlur={() => setFocusedField(null)}
                                        disabled={isSubmitting}
                                        className={validationErrors.email ? 'nx-popup-input-error' : ''}
                                        style={getInputStyles('email')}
                                        required
                                    />
                                    {validationErrors.email && (
                                        <div className="nx-popup-error-message">
                                            {validationErrors.email}
                                        </div>
                                    )}
                                </div>
                        ) }

                        {/* Message Field - Show if enabled for form themes */}
                        { ["popup_notification_theme-four", "popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                            .some(theme => settings.themes.includes(theme)) && settings.popup_show_message_field && (
                                <div className="nx-popup-textarea">
                                    <textarea
                                        placeholder={settings?.popup_message_placeholder || __('Enter your message...', 'notificationx')}
                                        value={formData.message}
                                        onChange={(e) => handleInputChange('message', e.target.value)}
                                        onFocus={() => setFocusedField('message')}
                                        onBlur={() => setFocusedField(null)}
                                        disabled={isSubmitting}
                                        className={validationErrors.message ? 'nx-popup-input-error' : ''}
                                        style={getTextareaStyles('message')}
                                    />
                                    {validationErrors.message && (
                                        <div className="nx-popup-error-message">
                                            {validationErrors.message}
                                        </div>
                                    )}
                                </div>
                        ) }
                        {/* Action Buttons - Show for all themes as originally designed */}
                        {(settings?.popup_button_text) && (
                            <div className="nx-popup-actions">
                                {/* Check if this is a form theme */}
                                {(["popup_notification_theme-four", "popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                                    .some(theme => settings.themes.includes(theme))) ? (
                                    <form onSubmit={handleFormSubmit}>
                                        <button
                                            type="submit"
                                            className="nx-popup-button nx-popup-primary-button"
                                            style={finalButtonStyles}
                                            onMouseEnter={() => setIsButtonHovered(true)}
                                            onMouseLeave={() => setIsButtonHovered(false)}
                                            disabled={isSubmitting}
                                        >
                                            {isSubmitting ? __('Submitting...', 'notificationx') :
                                             submitSuccess ? __('Success!', 'notificationx') :
                                             settings?.popup_button_text}
                                        </button>
                                    </form>
                                ) : (
                                    <button
                                        className="nx-popup-button nx-popup-primary-button"
                                        style={finalButtonStyles}
                                        onMouseEnter={() => setIsButtonHovered(true)}
                                        onMouseLeave={() => setIsButtonHovered(false)}
                                        onClick={handleButtonClick}
                                    >
                                        {settings?.popup_button_text}
                                    </button>
                                )}
                            </div>
                        )}

                    </div>

                    {/* Footer Section */}
                    { (!settings?.disable_powered_by) && (
                        <div className="nx-popup-footer">
                            {!settings?.disable_powered_by && (
                                <div className="nx-popup-branding">
                                    <span>{ __('Powered by ', 'notificationx') }</span>
                                     <NXBranding />
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>

                {/* Additional External Button for theme-three and theme-seven only */}
                {["popup_notification_theme-three", "popup_notification_theme-seven"].some(theme => settings.themes.includes(theme)) && (
                    <div className="nx-popup-external-button">
                        <form onSubmit={handleFormSubmit}>
                            <button
                                disabled={isSubmitting}
                                className="nx-popup-button nx-popup-primary-button nx-popup-external"
                            >
                                {settings?.popup_button_icon && settings.popup_button_icon !== 'none' && (
                                    <img src={buttonIconUrl} alt="Button Icon" />
                                )}
                                {isSubmitting ? __('Submitting...', 'notificationx') :
                                submitSuccess ? __('Success!', 'notificationx') :
                                settings?.popup_button_text}
                            </button>
                        </form>
                    </div>
                )}
            </div>
        </>
    );
}

export default Popup
