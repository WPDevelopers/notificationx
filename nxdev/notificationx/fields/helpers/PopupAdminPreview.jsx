import { __ } from "@wordpress/i18n";
import classNames from "classnames";
import React, { Fragment, useEffect, useState } from "react";
import { ReactComponent as DesktopIcon } from "../../icons/responsive/desktop.svg";
import { ReactComponent as TabletIcon } from "../../icons/responsive/tablet.svg";
import { ReactComponent as MobileIcon } from "../../icons/responsive/mobile.svg";
import { useNotificationXContext } from "../../hooks";

const PopupAdminPreview = ({ nxPopup, dispatch }) => {
    const { config: settings } = nxPopup;
    const [previewType, setPreviewType] = useState("desktop");
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        message: ''
    });
    const [validationErrors, setValidationErrors] = useState({
        name: '',
        email: '',
        message: ''
    });
    const { assets } = useNotificationXContext();

    // Handle input changes
    const handleInputChange = (field, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));

        // Clear validation error for this field when user starts typing
        if (validationErrors[field]) {
            setValidationErrors(prev => ({
                ...prev,
                [field]: ''
            }));
        }
    };

    // Close Icon Component
    const CloseIcon = () => (
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path d="M11 1L1 11M1 1L11 11" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
    );

    // Get popup styles
    const getPopupStyles = () => {
        const overlayStyles = {
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: settings?.overlay_color || 'rgba(0, 0, 0, 0.5)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 9999,
            padding: '20px',
        };

        const popupStyles = {
            backgroundColor: settings?.popup_bg_color || '#ffffff',
            borderRadius: `${settings?.popup_border_radius || 8}px`,
            padding: settings?.popup_padding || '30px',
            maxWidth: `${settings?.popup_width || 500}px`,
            width: '100%',
            maxHeight: '90vh',
            overflow: 'auto',
            position: 'relative',
            boxShadow: '0 10px 25px rgba(0, 0, 0, 0.2)',
        };

        return { overlayStyles, popupStyles };
    };

    // Get typography styles
    const getTitleStyles = () => ({
        color: settings?.popup_title_color || '#333333',
        fontSize: `${settings?.popup_title_font_size || 24}px`,
        fontWeight: settings?.popup_title_font_weight || '600',
        margin: '0 0 16px 0',
        lineHeight: '1.4',
    });

    const getSubtitleStyles = () => ({
        color: settings?.popup_subtitle_color || '#666666',
        fontSize: `${settings?.popup_subtitle_font_size || 18}px`,
        margin: '0 0 16px 0',
        lineHeight: '1.4',
    });

    const getContentStyles = () => ({
        color: settings?.popup_content_color || '#666666',
        fontSize: `${settings?.popup_content_font_size || 16}px`,
        margin: '0 0 20px 0',
        lineHeight: '1.6',
    });

    const getButtonStyles = () => ({
        backgroundColor: settings?.popup_button_bg_color || '#007cba',
        color: settings?.popup_button_text_color || '#ffffff',
        border: `${settings?.popup_button_border_width || 1}px solid ${settings?.popup_button_border_color || '#007cba'}`,
        borderRadius: `${settings?.popup_button_border_radius || 4}px`,
        padding: settings?.popup_button_padding || '12px 24px',
        fontSize: `${settings?.popup_button_font_size || 16}px`,
        fontWeight: settings?.popup_button_font_weight || '500',
        cursor: 'pointer',
        textDecoration: 'none',
        display: 'inline-flex',
        alignItems: 'center',
        gap: '8px',
        width: settings?.popup_button_width === '100%' ? '100%' : 
               settings?.popup_button_width === 'custom' ? `${settings?.popup_button_custom_width || 200}px` : 'auto',
        justifyContent: 'center',
        transition: 'all 0.3s ease',
    });

    const getInputStyles = () => ({
        backgroundColor: settings?.popup_email_bg_color || '#ffffff',
        color: settings?.popup_email_text_color || '#333333',
        border: `${settings?.popup_email_border_width || 1}px solid ${settings?.popup_email_border_color || '#dddddd'}`,
        borderRadius: `${settings?.popup_email_border_radius || 4}px`,
        padding: settings?.popup_email_padding || '12px 16px',
        fontSize: `${settings?.popup_email_font_size || 16}px`,
        height: `${settings?.popup_email_height || 48}px`,
        width: '100%',
        marginBottom: '16px',
        outline: 'none',
        transition: 'border-color 0.3s ease',
    });

    const getCloseButtonStyles = () => ({
        position: 'absolute',
        top: '15px',
        right: '15px',
        background: 'none',
        border: 'none',
        color: settings?.close_btn_color || '#999999',
        fontSize: `${settings?.close_btn_size || 20}px`,
        cursor: 'pointer',
        padding: '5px',
        lineHeight: '1',
        zIndex: 1,
    });

    const { overlayStyles, popupStyles } = getPopupStyles();

    return (
        <Fragment>
            <div className="nx-popup-responsive">
                <div className="nx-admin-modal-head">
                    <button
                        className={`nx-admin-modal-preview-button ${previewType == "desktop" ? "active" : ""}`}
                        onClick={() => setPreviewType("desktop")}
                    >
                         <DesktopIcon style={{ width: 20 }} />
                    </button>
                    <button
                        onClick={() => setPreviewType("tablet")}
                        className={`nx-admin-modal-preview-button ${previewType == "tablet" ? "active" : ""}`}
                    >
                         <TabletIcon style={{ width: 20 }} />
                    </button>
                    <button
                        onClick={() => setPreviewType("phone")}
                        className={`nx-admin-modal-preview-button ${previewType == "phone" ? "active" : ""}`}
                    >
                         <MobileIcon style={{ width: 20 }} />
                    </button>
                </div>
            </div>

            <div
                className={classNames(
                    "nx-popup-preview-container",
                    `nx-popup-${previewType}`,
                    settings?.theme || 'popup_notification_theme-one'
                )}
                style={{
                    position: 'relative',
                    minHeight: '400px',
                    backgroundColor: '#f0f0f1',
                    borderRadius: '8px',
                    overflow: 'hidden'
                }}
            >
                <div className="nx-popup-overlay" style={overlayStyles}>
                    <div
                        className={classNames(
                            "nx-popup-container",
                            settings?.theme || 'popup_notification_theme-one'
                        )}
                        style={popupStyles}
                    >
                        {/* Close Button */}
                        <button
                            className="nx-popup-close"
                            style={getCloseButtonStyles()}
                            aria-label="Close popup"
                        >
                            <CloseIcon />
                        </button>

                        {/* Popup Icon - only for theme-seven */}
                        {settings?.theme?.includes('theme-seven') && settings?.popup_icon && (
                            <div className="nx-popup-icon" style={{ textAlign: 'center', marginBottom: '16px' }}>
                                <img
                                    src={`${assets?.admin || ''}/images/icons/${settings.popup_icon}`}
                                    alt="Popup Icon"
                                    style={{ width: '48px', height: '48px' }}
                                />
                            </div>
                        )}

                        {/* Title */}
                        {settings?.popup_title && (
                            <h2 className="nx-popup-title" style={getTitleStyles()}>
                                {settings.popup_title}
                            </h2>
                        )}

                        {/* Subtitle - only for theme-seven */}
                        {settings?.theme?.includes('theme-seven') && settings?.popup_subtitle && (
                            <p className="nx-popup-subtitle" style={getSubtitleStyles()}>
                                {settings.popup_subtitle}
                            </p>
                        )}

                        {/* Content - for themes that show content */}
                        {settings?.popup_content && !["popup_notification_theme-three", "popup_notification_theme-four", "popup_notification_theme-seven"].some(t => settings.theme?.includes(t)) && (
                            <div className="nx-popup-content" style={getContentStyles()}>
                                <div dangerouslySetInnerHTML={{ __html: settings.popup_content }} />
                            </div>
                        )}

                        {/* Repeater Content - only for theme-three */}
                        {settings?.theme?.includes('theme-three') && settings?.popup_content_repeater && (
                            <div className="nx-popup-repeater-content" style={{ marginBottom: '20px' }}>
                                {settings.popup_content_repeater.map((item, index) => (
                                    <div
                                        key={index}
                                        className="nx-popup-repeater-item"
                                        style={{
                                            backgroundColor: settings?.popup_repeater_item_bg_color || '#f8f9fa',
                                            borderRadius: `${settings?.popup_repeater_item_border_radius || 6}px`,
                                            padding: settings?.popup_repeater_item_padding || '16px',
                                            marginBottom: `${settings?.popup_repeater_item_spacing || 12}px`,
                                        }}
                                    >
                                        {item.repeater_highlight_text && (
                                            <span
                                                className="nx-popup-highlight"
                                                style={{
                                                    color: settings?.popup_repeater_highlight_color || '#FF6B1B',
                                                    fontWeight: '600',
                                                    marginRight: '8px',
                                                }}
                                            >
                                                {item.repeater_highlight_text}
                                            </span>
                                        )}
                                        {item.repeater_title && (
                                            <span
                                                className="nx-popup-repeater-title"
                                                style={{
                                                    color: settings?.popup_repeater_title_color || '#333333',
                                                    fontSize: `${settings?.popup_repeater_title_font_size || 18}px`,
                                                    fontWeight: settings?.popup_repeater_title_font_weight || '600',
                                                    display: 'block',
                                                }}
                                            >
                                                {item.repeater_title}
                                            </span>
                                        )}
                                        {item.repeater_subtitle && (
                                            <span
                                                className="nx-popup-repeater-subtitle"
                                                style={{
                                                    color: settings?.popup_repeater_subtitle_color || '#666666',
                                                    fontSize: `${settings?.popup_repeater_subtitle_font_size || 14}px`,
                                                    display: 'block',
                                                    marginTop: '4px',
                                                }}
                                            >
                                                {item.repeater_subtitle}
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Form Fields - Show based on conditions */}
                        <div className="nx-popup-form">
                            {/* Name Field - Show if enabled for form themes */}
                            {["popup_notification_theme-four", "popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                                .some(theme => settings?.theme?.includes(theme)) && settings?.popup_show_name_field && (
                                    <div className="nx-popup-name">
                                        <input
                                            type="text"
                                            placeholder={settings?.popup_name_placeholder || __('Enter your name', 'notificationx')}
                                            value={formData.name}
                                            onChange={(e) => handleInputChange('name', e.target.value)}
                                            style={getInputStyles()}
                                        />
                                        {validationErrors.name && (
                                            <div className="nx-popup-error-message" style={{ color: '#dc3545', fontSize: '14px', marginTop: '-12px', marginBottom: '12px' }}>
                                                {validationErrors.name}
                                            </div>
                                        )}
                                    </div>
                            )}

                            {/* Email Field - Show if enabled for form themes */}
                            {["popup_notification_theme-four", "popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                                .some(theme => settings?.theme?.includes(theme)) && settings?.popup_show_email_field && (
                                    <div className="nx-popup-email">
                                        <input
                                            type="email"
                                            placeholder={settings?.popup_email_placeholder || __('Enter your email address', 'notificationx')}
                                            value={formData.email}
                                            onChange={(e) => handleInputChange('email', e.target.value)}
                                            style={getInputStyles()}
                                        />
                                        {validationErrors.email && (
                                            <div className="nx-popup-error-message" style={{ color: '#dc3545', fontSize: '14px', marginTop: '-12px', marginBottom: '12px' }}>
                                                {validationErrors.email}
                                            </div>
                                        )}
                                    </div>
                            )}

                            {/* Message Field - Show if enabled for form themes */}
                            {["popup_notification_theme-four", "popup_notification_theme-five", "popup_notification_theme-six", "popup_notification_theme-seven"]
                                .some(theme => settings?.theme?.includes(theme)) && settings?.popup_show_message_field && (
                                    <div className="nx-popup-textarea">
                                        <textarea
                                            placeholder={settings?.popup_message_placeholder || __('Enter your message...', 'notificationx')}
                                            value={formData.message}
                                            onChange={(e) => handleInputChange('message', e.target.value)}
                                            style={{
                                                ...getInputStyles(),
                                                height: '100px',
                                                resize: 'vertical',
                                                fontFamily: 'inherit',
                                            }}
                                        />
                                        {validationErrors.message && (
                                            <div className="nx-popup-error-message" style={{ color: '#dc3545', fontSize: '14px', marginTop: '-12px', marginBottom: '12px' }}>
                                                {validationErrors.message}
                                            </div>
                                        )}
                                    </div>
                            )}

                            {/* Button */}
                            {settings?.popup_button_text && (
                                <div className="nx-popup-button-container" style={{ textAlign: 'center' }}>
                                    <button
                                        className="nx-popup-button"
                                        style={getButtonStyles()}
                                        type="button"
                                    >
                                        {/* Button Icon - for theme-three and theme-seven */}
                                        {(settings?.theme?.includes('theme-three') || settings?.theme?.includes('theme-seven')) && settings?.popup_button_icon && (
                                            <img
                                                src={`${assets?.admin || ''}/images/icons/${settings.popup_button_icon}`}
                                                alt="Button Icon"
                                                style={{ width: '16px', height: '16px' }}
                                            />
                                        )}
                                        {settings.popup_button_text}
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </Fragment>
    );
};

export default PopupAdminPreview;
