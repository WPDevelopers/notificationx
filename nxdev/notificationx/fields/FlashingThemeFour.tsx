
import React, { useCallback, useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel, Input, executeChange } from "quickbuilder"
import Icon from "./helpers/Icon";
import {GenericFlashingThemeThree as FlashingThemeThree} from "./FlashingThemeThree";

const FlashingThemeFour = (props) => {
    const builderContext = useBuilderContext();
    console.log(props.name, props.value);

    const handleChange = (event, index) => {
        const { field, val: value } = executeChange(event);
        console.log(props.name, field, value);
        props.onChange({
            target: {
                type: "flashing-tab",
                name: props.name,
                value: {...(props.value ?? {}), [field]: value},
            },
        });
        // if (event.persist) {
        //     event.persist();
        // }
        // builderContext.setFieldValue([props.name], {...props.value, [field]: value});
    }

    return (
        <div className="nx-field-wrapper">
            <FlashingThemeThree name="default" value={props.value?.default || {}} onChange={handleChange} options={props.options} description={props['qnt-description']}  />
            <div className="nx-field has-bottom-gap no-wrap">
                <Input name="is-show-empty" type="checkbox" value={props.value?.['is-show-empty']} onChange={handleChange} description={props['chk-description']} />
            </div>
            {props.value?.['is-show-empty'] &&
                <FlashingThemeThree name="alternative" value={props.value?.alternative || {}} onChange={handleChange} options={props.options} placeholder="Alternative text" />
            }
        </div>
    );
};

export default withLabel(FlashingThemeFour);
