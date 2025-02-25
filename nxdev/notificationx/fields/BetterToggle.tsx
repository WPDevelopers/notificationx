import classNames from 'classnames';
import React, { useEffect, useMemo, useState } from 'react'
import { GenericInput, isObject, isString, Label,withLabel } from 'quickbuilder'

export const BetterToggle = (props) => {
    const { style: prevStyles } = props;
    const { toggle_label, ...props_data } = props;

    let styles = {
        type: "", // card
        label: {
            position: "right",
        },
        column: 4,
        ...prevStyles,
    };

    const isChecked = useMemo(() => {
        let _isChecked = false;
        if (props?.checked && isObject(props.checked) && isString(props?.value)) {
            _isChecked = props.checked[props.value]
        } else {
            if (!isString(props.value)) {
                _isChecked = props.value;
            }
        }
        return _isChecked;
    }, [props?.checked, props.value])


    const componentClasses = classNames(
        "wprf-toggle-wrap",
        {
            [`wprf-${styles?.type}`]: styles?.type.length > 0,
            "wprf-checked": Boolean(isChecked),
            [`wprf-label-position-${styles?.label?.position}`]: styles?.label
                ?.position,
        },
        props?.classes
    );

    return (
        <div className={componentClasses}>
            {toggle_label && toggle_label?.toggle_label_1 &&
                <span className='toggle_label_1'>{toggle_label?.toggle_label_1}</span>
            }
            <GenericInput {...{ ...props_data, type: 'checkbox', placeholder: undefined }} />
            <Label htmlFor={props.id} />
            {toggle_label && toggle_label?.toggle_label_2 &&
                <span className='toggle_label_2'>{toggle_label?.toggle_label_2}</span>
            }
        </div>
    );
}

export default withLabel(BetterToggle);