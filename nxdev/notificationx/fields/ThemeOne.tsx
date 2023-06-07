
import React, { useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel, GenericInput as Input } from "quickbuilder"
import Icon from "./helpers/Icon";

const ThemeOne = (props) => {
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

    

    return (
        <>
            <Icon value={value} setValue={setValue} options={props.options} />
            <Icon value={value} setValue={setValue} options={props.options} />

        </>
    );
};

export default withLabel(ThemeOne);
