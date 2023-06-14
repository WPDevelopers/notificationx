import React, { useEffect, useRef, useState } from "react";
import { MediaUpload } from '@wordpress/media-utils';
import { useBuilderContext, withLabel, Input } from "quickbuilder"
import Icon from "./helpers/Icon";

const FlashingThemeThree = (props) => {
    // const builderContext = useBuilderContext();
    // const [value, setValue]         = useState<FlashingTab>(props.value || {});
    // console.log(props.name, props.value);

    // useEffect(() => {
    //     if(props.value !== value){
    //         setValue(props.value);
    //     }
    // }, [props.value]);

    // useEffect(() => {

    //     props.onChange({
    //         target: {
    //             type: "advanced-template",
    //             value: value,
    //             name: props.name,
    //         },
    //     });
    // }, [value])



    // const onTextUpdate = (event) => {
    //     setValue((value) => {
    //         return {
    //             ...value,
    //             'message': event.target.value,
    //         }
    //     });
    // }

    // const onChangeIcon = (event) => {
    //     setValue((value) => {
    //         return {
    //             ...value,
    //             [event.target.name]: event.target.value,
    //         }
    //     });
    // }

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
            <Icon name="icon" value={props.value?.icon} onChange={handleOnChange} options={props.options} />
            <Input name="message" type="text" value={props.value?.message} onChange={handleOnChange} description={props?.description} placeholder={props.placeholder} />
        </div>
    );
};

export const GenericFlashingThemeThree = FlashingThemeThree;
export default withLabel(FlashingThemeThree);
