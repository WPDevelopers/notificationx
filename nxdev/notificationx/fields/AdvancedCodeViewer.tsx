import { Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import copy from "copy-to-clipboard";
import React, { useCallback, useEffect, useState } from "react";
import { withLabel, validFieldProps } from "quickbuilder";
import { defaultCustomCSSValue } from "../core/functions";

const AdvancedCodeViewer = (props) => {
    const [defaultVal, setDefaultVal] = useState(props?.value);	
    const validProps = validFieldProps(props, [
        "is_pro",
        "visible",
        "trigger",
        "disable",
        "parentIndex",
        "context",
        "copyOnClick",
    ]);

    const handleChange = useCallback(
        (event) => {
            const newValue = event.target.value;
            setDefaultVal(newValue);
            validProps.onChange(event, { isPro: !!props.is_pro });
        },
        [validProps, props.is_pro]
    );

    let extraProps = { onChange: handleChange, rows: 5 };

    if (!props.is_pro && props?.copyOnClick && props?.value) {
        extraProps["onClick"] = () => {
            const successText = props?.success_text ? props.success_text : __(`Copied to Clipboard.`, "notificationx");
            copy(props.value, {
                format: 'text/plain',
                onCopy: () => {
                    props.context.alerts.toast("success", successText);
                },
            });
        };
    }

    useEffect(() => {
        if (props?.context?.values?.source === 'press_bar' && !props?.value) {
            const bar = defaultCustomCSSValue('bar');
            setDefaultVal(bar);
        } else if (props?.context?.values?.source === 'gdpr_notification' && !props?.value) {
            const gdpr = defaultCustomCSSValue('gdpr');
            setDefaultVal(gdpr);
        }
        else if(!props?.value) {
            const popup = defaultCustomCSSValue('');
            setDefaultVal(popup);
        }
    }, [props?.context?.values?.source]);

    const ButtonText = props?.button_text ? props.button_text : __("Click to Copy", "notificationx");

    return (
        <span className="wprf-code-viewer">
            <textarea {...validProps} {...extraProps} value={defaultVal} />
            <Button className="wprf-copy-button">{ButtonText}</Button>
        </span>
    );
};

export const GenericInput = React.memo(AdvancedCodeViewer);
export default withLabel(React.memo(AdvancedCodeViewer));
