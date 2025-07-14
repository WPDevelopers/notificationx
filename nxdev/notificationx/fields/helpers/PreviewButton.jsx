import React from "react";

// Helper function to get the complete icon URL
const getIconUrl = (iconValue, iconPrefix = '') => {
    if (!iconValue) return '';

    // Check if it's already a complete URL (starts with http/https or data:)
    if (/^(https?:\/\/|data:)/.test(iconValue)) {
        return iconValue;
    }

    // Convert admin URL to public URL if needed
    let prefix = iconPrefix;
    if (prefix && prefix.includes('/wp-admin/')) {
        // Convert admin URL to public URL
        prefix = prefix.replace('/wp-admin/', '/wp-content/plugins/notificationx/assets/admin/');
        prefix = prefix.replace('/images/icons/', 'images/icons/');
    }

    // Default to NotificationX public icons directory if no prefix
    if (!prefix) {
        const baseUrl = (typeof window !== 'undefined' && window.location)
            ? window.location.origin
            : '';
        prefix = baseUrl + '/wp-content/plugins/notificationx/assets/admin/images/icons/';
    }

    return prefix + iconValue;
};

const PreviewButton = ({config, children = null, href = null, data = {}, ...rest}) => {
    const styles = {};
    if (config.advance_edit) {
        if(config.link_button_bg_color) {
            styles.backgroundColor = config.link_button_bg_color;
        }
        if(config.link_button_text_color) {
            styles.color = config.link_button_text_color;
        }
        if(config.link_button_font_size) {
            styles.fontSize = config.link_button_font_size;
        }
        // Add Border Radius
        if(config.nx_bar_border_radius_left || config.nx_bar_border_radius_right || config.nx_bar_border_radius_top || config.nx_bar_border_radius_bottom) {
            styles.borderRadius = `${config.nx_bar_border_radius_top || 0}px ${config.nx_bar_border_radius_right || 0}px ${config.nx_bar_border_radius_bottom || 0}px ${config.nx_bar_border_radius_left || 0}px`;
        }
    }    

    // Configure link
    let link = href;
    if( data?.link ) {
        link = data.link;
    }
    let link_text = config?.link_button_text;
    const iconUrl = getIconUrl(config.button_icon);
    
    if( config.source == 'press_bar' ) {
        return (
            <>
                <div 
                    className="notificationx-link-wrapper"
                    style={styles}
                >
                    <a
                        href={ link }
                        target={config?.link_open ? "_blank" : ""}
                        {...rest}
                    >
                        {config?.button_icon && (
                            <img
                                src={iconUrl}
                                alt="Button Icon"
                                style={{ width: 24, height: 24, marginRight: 8 }}
                                onError={(e) => {
                                    e.target.style.display = 'none';
                                }}
                                onLoad={(e) => {
                                    e.target.style.display = 'block';
                                }}
                            />
                        )}
                        { config.link_text ? link_text : '' } {children}
                    </a>
                </div>
            </>
        );
    }
};

export default PreviewButton;
