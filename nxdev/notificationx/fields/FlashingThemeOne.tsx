
import React, { useEffect, useRef, useState } from "react";
import { useBuilderContext, withLabel, GenericInput as Input } from "quickbuilder"
import Icon from "./helpers/Icon";

const FlashingThemeOne = (props) => {
    // const builderContext = useBuilderContext();
    // const [value, setValue] = useState<FlashingThemeOne>(props.value || {});
    // console.log(props.name, props.value);

    // useEffect(() => {
    //     if(props.value !== value){
    //         setValue(props.value);
    //     }
    // }, [props.value]);

    // useEffect(() => {

    //     props.onChange({
    //         target: {
    //             type: "flashing-tab",
    //             value: value,
    //             name: props.name,
    //         },
    //     });
    // }, [value])


    const onChange = (event) => {
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
        // setValue((value) => {
        //     return {
        //         ...value,
        //         [event.target.name]: event.target.value,
        //     }
        // });
    }

    return (
        <>
            <Icon name="icon-one" count="1" iconPrefix={props.iconPrefix} value={props.value?.['icon-one']} onChange={onChange} options={props['icons-one']} />
            <Icon name="icon-two" count="2" iconPrefix={props.iconPrefix} value={props.value?.['icon-two']} onChange={onChange} options={props['icons-two']} />
        </>
    );
};

export default withLabel(FlashingThemeOne);
