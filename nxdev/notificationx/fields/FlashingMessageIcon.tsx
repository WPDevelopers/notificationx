import React, { useEffect, useRef, useState } from "react";
import { useBuilderContext, withLabel, GenericInput as Input } from "quickbuilder";

const FlashingMessageIcon = (props) => {
    const builderContext = useBuilderContext();
    const [value, setValue] = useState(props.value || {});

    const onTextUpdate = (event) => {
        const _value = {...value, 'message': event.val}
        props.onChange({
            target: {
                type: "advanced-template",
                value: _value,
                name: props.name,
            },
        });
    }

    return (
        <>
            <h1>Hello</h1>
            <Input type="text" onChange={onTextUpdate} />
        </>
    );
};

export const GenericInput = React.memo(FlashingMessageIcon);
export default withLabel(React.memo(FlashingMessageIcon));
