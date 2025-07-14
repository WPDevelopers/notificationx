import React from "react";

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
                        { config.link_text ? link_text : '' } {children}
                    </a>
                </div>
            </>
        );
    }
};

export default PreviewButton;
