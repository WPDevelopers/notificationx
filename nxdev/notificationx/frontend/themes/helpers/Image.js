import React from "react";
import classNames from "classnames";

const Image = ({ data, config, id, style, isSplitCss }) => {
    if (!data?.image_data) {
        return null;
    }

    const { advance_edit, image_position, themes } = config;
    let { image_shape } = config;
    const custom_image_shape = image_shape == 'custom' ? config?.custom_image_shape : false;
    if(!advance_edit && config?.image_shape_default) {
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

    let newStyle ={}
    let newStyleForSecond = {}
    if ( style && isSplitCss ) {
      newStyle = {...style, right: -style?.borderWidth, top: `calc( 100% + ${style?.borderWidth}px)`, borderTopWidth: `${13 + style?.borderWidth}px`,borderLeftWidth: `${23 + (style?.borderWidth*1.75)}px`}
      console.log(newStyle)
      newStyleForSecond = {...style, borderColor: style?.backgroundColor}
    }

    let imgRadius = {}
    if (custom_image_shape) {
        let radiusValue = custom_image_shape.trim()
        let radiusValueFloat = parseFloat(radiusValue);
        if (radiusValue == radiusValueFloat) {
            radiusValue += "px"
        }
        imgRadius = {...imgRadius, borderRadius: radiusValue}
    }

    return (
        <div className={componentClasses} {...data?.image_data?.attr} style={style}>
            <img src={data?.image_data?.url} alt={data?.image_data?.alt} style={imgRadius} />
            <span className="notificationx-image-badge" style={newStyle}></span>
            <span className="notificationx-image-badge second-span" style={newStyleForSecond}></span>
        </div>
    );
};

export default Image;
