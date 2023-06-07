
import React, { useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel, Input } from "quickbuilder"
import Icon from "./helpers/Icon";
import {GenericInput} from "./FlashingMessageIcon";

const ThemeFour = (props) => {
    // const builderContext = useBuilderContext();
    const [value, setValue]         = useState<{icon?: string, image?: string, message?: string}>(props.value || {});

    useEffect(() => {
        if(props.value !== value){
            setValue(props.value);
        }
    }, [props.value]);

    useEffect(() => {

        props.onChange({
            target: {
                type: "advanced-template",
                value: value,
                name: props.name,
            },
        });
    }, [value])

    const onTextUpdate = (event) => {
        setValue((value) => {
            return {
                ...value,
                'message': event.target.value,
                
            }
        });
    }

    return (
        <div className="nx-field-wrapper">
            <GenericInput value={value} onChange={() => {}} name="name" options={props.options} description="Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quis, aliquid."  />
            <div className="nx-field has-bottom-gap">
                <Input type="checkbox" description="Lorem ipsum dolor, sit amet consectetur adipisicing elit. Quis, aliquid." />
            </div>
            {true && 
                <GenericInput value={value} onChange={() => {}} name="name" options={props.options}  />
            }
        </div>
    );
};

export default withLabel(ThemeFour);
