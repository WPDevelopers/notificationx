import React, { useCallback } from "react";
import {
    useBuilderContext,
    withLabel,
    Input,
    executeChange,
} from "quickbuilder";
import { GenericFlashingThemeThree as FlashingThemeThree } from "./FlashingThemeThree";

const FlashingThemeFour = (props) => {
    const builderContext = useBuilderContext();

    const handleChange = useCallback((event, index) => {
        const { field, val: value } = executeChange(event);
        builderContext.setFieldValue([props.name, field], value);
    }, [props.value]);

    return (
        <div className="nx-field-wrapper">
            <FlashingThemeThree
                name="default"
                iconPrefix={props.iconPrefix}
                value={props.value?.default || {}}
                onChange={handleChange}
                options={props.options}
                description={props["qnt-description"]}
            />
            <div className="nx-field has-bottom-gap no-wrap">
                <Input
                    name="is-show-empty"
                    type="checkbox"
                    value={props.value?.["is-show-empty"]}
                    onChange={handleChange}
                    description={props["chk-description"]}
                />
            </div>
            {props.value?.["is-show-empty"] && (
                <FlashingThemeThree
                    name="alternative"
                    iconPrefix={props.iconPrefix}
                    value={props.value?.alternative || {}}
                    onChange={handleChange}
                    options={props.options}
                    placeholder="Alternative text"
                />
            )}
        </div>
    );
};

export default withLabel(FlashingThemeFour);
