import React from "react";
import classNames from "classnames";
import { nxApplyFilters } from "../../core/hooks";

const Image = ({ data, config, id, theme: themeName, style, isSplitCss, isSplit, announcementCSS = '' }) => {
    if (!data?.image_data) {
        return null;
    }

    const { advance_edit, image_position } = config;
    let { image_shape } = config;
    const custom_image_shape =
        image_shape == "custom" ? config?.custom_image_shape : false;
    if (!advance_edit && config?.image_shape_default) {
        image_shape = config.image_shape_default;
    }
    const componentClasses = classNames(
        "notificationx-image",
        data?.image_data?.classes,
        {
            [`image-${image_shape}`]: image_shape,
            [`position-${image_position}`]: image_position && advance_edit,
        }
    );

    let newStyle = {};
    let newStyleForSecond = {};
    if (style && isSplitCss) {
        newStyle = {
            ...style,
            right: -style?.borderWidth,
            top: `calc( 100% + ${style?.borderWidth}px)`,
            borderTopWidth: `${13 + style?.borderWidth}px`,
            borderLeftWidth: `${23 + style?.borderWidth * 1.75}px`,
        };
        newStyleForSecond = { ...style, borderColor: style?.backgroundColor };
    }

    let imgRadius = {};
    if (custom_image_shape) {
        let radiusValue = custom_image_shape.trim();
        let radiusValueFloat = parseFloat(radiusValue);
        if (radiusValue == radiusValueFloat) {
            radiusValue += "px";
        }
        imgRadius = { ...imgRadius, borderRadius: radiusValue };
    }
    
    // Discount Alert colors, read from `data` by add-on image parts.
    data.announcementCSS = announcementCSS;
    // Add-ons replace the whole image slot for the themes they own (Pro:
    // announcements_theme-1/2 discount badge). Anything but undefined/null wins.
    const filteredImage = nxApplyFilters("nx_frontend_image", undefined, {
        themeName, data, config, id, style, componentClasses, isSplit, isSplitCss, announcementCSS,
    });
    if (filteredImage !== undefined && filteredImage !== null) {
        return filteredImage;
    }

    return (
        <div
            className={componentClasses}
            {...data?.image_data?.attr}
            style={{
                backgroundColor: isSplit ? style?.backgroundColor : '',
              }}
        >
            <img
                src={data?.image_data?.url}
                alt={data?.image_data?.alt}
                style={imgRadius}
            />
            {isSplit && (
                <>
                    <span
                        className="notificationx-image-badge"
                        style={newStyle}
                    ></span>
                    <span
                        className="notificationx-image-badge second-span"
                        style={newStyleForSecond}
                    ></span>
                </>
            )}
        </div>
    );
};

export default Image;
