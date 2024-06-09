import React, { useEffect, useState } from 'react'
import { Icon } from '@wordpress/components';
import { useInstanceId } from "@wordpress/compose";
import { useBuilderContext } from 'quickbuilder';
import { GenericField } from 'quickbuilder';


const BulkEditField = (props) => {
    const builderContext = useBuilderContext();
    const { fields, onChange, index, parent, onChecked, checked, __index } = props;
    const instanceId = useInstanceId(BulkEditField);
    const values = builderContext.values?.[parent]?.[index];
    const title = values?.title || values?.post_title || values?.username || values?.plugin_theme_name;
    const _title = title ? ((title.length < 40 ? title : title.substr(0, 40) + "...")) : '';
    let fieldsArray = Object.values(fields);
    const onDelete = (event:Event) => {
        event?.stopPropagation();
        props.remove(props.index);
    }

    fieldsArray = fieldsArray.filter(item => {
        // @ts-ignore 
        return ['title', 'first_name', 'last_name', 'timestamp', 'plugin_name', 'post_title','username','sales_count','today','last_week','all_time','active_installs','rated'].includes(item?.name);
    });
    
    return (
        <div className="wprf-repeater-field">
            <div className="wprf-repeater-field-title">
                <h4><Icon icon="move"/>{props.index+1}: {_title}</h4>
                <div className="wprf-repeater-field-controls">
                    <Icon onClick={onDelete} icon="trash" />
                </div>
            </div>
            <div className="wprf-repeater-inner-field">
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

export default BulkEditField;
