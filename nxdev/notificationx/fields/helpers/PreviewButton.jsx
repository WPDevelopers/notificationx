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
    }

    // Configure link
    let link = href;
    if( data?.link ) {
        link = data.link;
    }
    let link_text = config?.link_button_text;
    console.log('styles',styles);
    console.log('config',config);
    
    if( config.source == 'press_bar' ) {
        return (
            <>
                <div  className="notificationx-link-wrapper">
                    <a
                        href={ link }
                        style={{
                            backgroundColor: config?.coupon_bg_color || "#f9f9f9",
                            color          : config?.coupon_text_color || "#000",
                            fontSize       : '28px',
                        }}
                        target={config?.link_open ? "_blank" : ""}
                        {...rest}
                    >
                        <span>Hello</span>
                        { config.link_text ? link_text : '' } {children}
                    </a>
                </div>
            </>
        );
    }
};

export default PreviewButton;
