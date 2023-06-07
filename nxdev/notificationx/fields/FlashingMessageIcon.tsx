import React, { useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel, GenericInput as Input } from "quickbuilder";
import Icon from "./helpers/Icon";

const FlashingMessageIcon = (props) => {
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
        <>
            <Icon value={value} setValue={setValue} options={props.options} />
            <Input type="text" value={value.message} onChange={onTextUpdate} />

        </>
    );
};

export const GenericInput = React.memo(FlashingMessageIcon);
export default withLabel(React.memo(FlashingMessageIcon));
