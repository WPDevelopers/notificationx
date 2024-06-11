import React, { useEffect, useState } from 'react'
import { Icon } from '@wordpress/components';
import { useInstanceId } from "@wordpress/compose";
import { useBuilderContext } from 'quickbuilder';
import { GenericField } from 'quickbuilder';
import DuplicateIcon from '../../icons/DuplicateIcon';
import TrashIconTwo from '../../icons/TrashIconTwo';


const AdvancedRepeaterField = (props) => {
    const builderContext = useBuilderContext();
    const { fields, onChange, index, parent, onChecked, checked, __index } = props;
    const [isCollapsed, setIsCollapsed] = useState(props.isCollapsed);
    const instanceId = useInstanceId(AdvancedRepeaterField);
    const values = builderContext.values?.[parent]?.[index];
    const title = values?.post_title || values?.title  || values?.username || values?.plugin_theme_name;
    const _title = title ? ((title.length < 40 ? title : title.substr(0, 40) + "...")) : '';
    const fieldsArray = Object.values(fields);

    const onClone = (event:Event) => {
        event?.stopPropagation();
        props.clone(props.index);
    }
    const onDelete = (event:Event) => {
        event?.stopPropagation();
        props.remove(props.index);
    }

    useEffect(() => {
        builderContext.setFieldValue([parent, index, 'isCollapsed'], isCollapsed);        
    }, [isCollapsed])    

    return (
        <div className="wprf-repeater-field">
            <div className="wprf-repeater-field-title">
                <input type="checkbox" name={`check-${index}`} id={`check-${index}`} checked={ checked || false } onChange={ () => onChecked( __index ) } />
                <div className="wprf-repeater-field-content" onClick={() => setIsCollapsed(!isCollapsed)}>
                    <h4><Icon icon="move"/>{props.index+1}: {_title}</h4>
                    <div className="wprf-repeater-field-controls">
                        {/* @ts-ignore  */}
                        <div style={ { lineHeight: 0 } } onClick={ (event) => onClone(event) }>
                            <DuplicateIcon/>
                        </div>
                        {/* @ts-ignore  */}
                        <div  style={ { lineHeight: 0 } } onClick={ (event) => onDelete(event) }>
                            <TrashIconTwo/> 
                        </div>
                    </div>
                </div>
            </div>
            { !isCollapsed &&
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
            }
        </div>
    )
}

export default AdvancedRepeaterField;
