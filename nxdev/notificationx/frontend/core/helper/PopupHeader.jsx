import React from "react";

const PopupHeader = ({ settings, iconUrl, titleColorFont, subtitleColorFont, descColorFont, content }) => {
    const isThemeSeven = ["popup_notification_theme-seven"].some(theme =>
        settings?.theme?.includes(theme)
    );

    // Festive/seasonal coupon popup — shows an eyebrow tagline above the title.
    const isFestive = settings?.themes?.includes("popup_notification_theme-eleven");
    // Multi-tier discount popup — gift hero + two-tone heading (title + accent).
    const isMultiCoupon = settings?.themes?.includes("popup_notification_theme-thirteen");

    const hasIcon = settings?.popup_icon && settings.popup_icon !== "none";

    if (!settings?.popup_title) return null;

    return (
        <div className="nx-popup-header">
            <div className="nx-popup-header-wrapper">
                {/* Popup Icon - Show only for theme-seven and when icon is set */}
                {isThemeSeven && hasIcon && (
                    <div className="nx-popup-header-icon">
                        <img src={iconUrl} alt="Popup Icon" />
                    </div>
                )}

                {/* Gift hero for the multi-tier discount popup */}
                {isMultiCoupon && (
                    <div className="nx-popup-gift" aria-hidden="true">
                        <span className="nx-popup-gift-emoji">🎁</span>
                    </div>
                )}

                <div className="nx-popup-header-content">
                    {isFestive && settings?.popup_subtitle && (
                        <span className="nx-popup-eyebrow" style={subtitleColorFont}>
                            {settings.popup_subtitle}
                        </span>
                    )}
                    {settings?.popup_title && (
                        <h3 className="nx-popup-title" style={titleColorFont}>
                            {settings.popup_title}
                        </h3>
                    )}
                    {isMultiCoupon && settings?.popup_subtitle && (
                        <span className="nx-popup-title-accent">
                            {settings.popup_subtitle}
                        </span>
                    )}
                    {settings?.popup_content && isThemeSeven && (
                        <div className="nx-popup-description" style={descColorFont}>
                            {settings?.popup_content ? (
                                <div
                                    dangerouslySetInnerHTML={{
                                        __html: settings.popup_content,
                                    }}
                                />
                            ) : (
                                content && (
                                    <div
                                        dangerouslySetInnerHTML={{
                                            __html: content,
                                        }}
                                    />
                                )
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default PopupHeader;
