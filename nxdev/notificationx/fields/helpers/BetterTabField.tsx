import React, { useEffect, useState } from 'react'
import { Icon } from '@wordpress/components';
import { useInstanceId } from "@wordpress/compose";
import { GenericField, useBuilderContext } from 'quickbuilder';

const BetterTabField = (props) => {
    const builderContext = useBuilderContext();
    const { fields, onChange, index, parent } = props;
    const fieldsArray = Object.values(fields);
    
    const [isCollapsed, setIsCollapsed] = useState(props.isCollapsed);
    const instanceId = useInstanceId(BetterTabField);
    // onClick={() => setIsCollapse(!isCollapse)}
    const values = builderContext.values?.[parent]?.[index];
    const title = values?.title;
    
    return (
        <div className="wprf-repeater-field">
            <div className="wprf-repeater-field-title">
                <h4><Icon icon="move"/>{props.index+1}: {title}</h4>
            </div>
            <div className="wprf-better-tab-fields">
                {fieldsArray.map((field, fieldIndex) => {
                    return <GenericField
                        key={`field-${index}-${fieldIndex}`}
                        // @ts-ignore 
                        {...field}
                        id={`field-${instanceId}-${index}-${fieldIndex}`}
                        index={index}
                        parenttype='repeater'
                        parent={parent}
                        onChange={(event) => onChange(event, index)}
                    />
                })}
            </div>
        </div>
    )
}

export default BetterTabField;
