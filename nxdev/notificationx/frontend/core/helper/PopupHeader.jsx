import React from "react";

const PopupHeader = ({ settings, iconUrl, titleColorFont, descColorFont, content }) => {
    const isThemeSeven = ["popup_notification_theme-seven"].some(theme =>
        settings?.theme?.includes(theme)
    );

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

                <div className="nx-popup-header-content">
                    {settings?.popup_title && (
                        <h3 className="nx-popup-title" style={titleColorFont}>
                            {settings.popup_title}
                        </h3>
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
