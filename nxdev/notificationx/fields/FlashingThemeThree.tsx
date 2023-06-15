import React, { useEffect, useRef, useState } from "react";
import { withLabel, Input } from "quickbuilder"
import Icon from "./helpers/Icon";

const FlashingThemeThree = (props) => {

    const handleOnChange = (event) => {
        props.onChange({
            target: {
                type: "flashing-tab",
                name: props.name,
                value: {
                    ...(props.value ?? {}),
                    [event.target.name]: event.target.value,
                },
            },
        });
    }

    return (
        <div className={`nx-field ${props.wrapperClass ?? ''}`}>
            <Icon name="icon" iconPrefix={props.iconPrefix} value={props.value?.icon} onChange={handleOnChange} options={props.options} />
            <Input name="message" type="text" value={props.value?.message} onChange={handleOnChange} description={props?.description} placeholder={props.placeholder} />
        </div>
    );
};

export const GenericFlashingThemeThree = FlashingThemeThree;
export default withLabel(FlashingThemeThree);
