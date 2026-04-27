import React from "react";
import { getIconUrl } from "../../core/functions";

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
        // Prevent inline style conflicts by applying computed styles only to the anchor
        const { style: incomingStyle, ...otherRest } = rest || {};
        // Ensure config-derived styles take precedence over incoming styles
        const anchorStyle = Object.assign({}, incomingStyle || {}, styles);

        return (
            <>
                <div className="notificationx-link-wrapper">
                    <a
                        href={ link }
                        target={config?.link_open ? "_blank" : ""}
                        style={anchorStyle}
                        {...otherRest}
                    >
                        { (config?.button_icon && config?.button_icon !== 'none') && (
                            <img
                                src={iconUrl}
                                style={{ width: 24, height: 24, marginRight: 8 }}
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
