import React, { useEffect, useState } from 'react'
import { Icon } from '@wordpress/components';
import { useInstanceId } from "@wordpress/compose";
import { GenericField, useBuilderContext } from 'quickbuilder';


const SimpleRepeaterField = (props) => {
    const builderContext = useBuilderContext();
    const { fields, onChange, index, parent } = props;
    const [isCollapsed, setIsCollapsed] = useState(props.isCollapsed);
    const instanceId = useInstanceId(SimpleRepeaterField);
    // onClick={() => setIsCollapse(!isCollapse)}
    const values = builderContext.values?.[parent]?.[index];
    const title = values?.title || values?.post_title || values?.username || values?.plugin_theme_name;
    const _title = title ? ((title.length < 40 ? title : title.substr(0, 40) + "...")) : '';

    const onClone = (event) => {
        event?.stopPropagation();
        props.clone(props.index);
    }
    const onDelete = (event) => {
        event?.stopPropagation();
        props.remove(props.index);
    }

    useEffect(() => {
        builderContext.setFieldValue([parent, index, 'isCollapsed'], isCollapsed);
    }, [isCollapsed])

    const getCleanTitle = (html) => {
        if (typeof window !== 'undefined') {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            return doc.body.textContent || '';
        }
        return '';
    };

    return (
        <div className="wprf-repeater-field">
            <div className="wprf-repeater-field-title" onClick={() => setIsCollapsed(!isCollapsed)} >
                <h4><Icon icon="move"/>{props.index+1}: {getCleanTitle(title)}</h4>
                <div className="wprf-repeater-field-controls">
                    <Icon onClick={onClone} icon="admin-page" />
                    <Icon onClick={onDelete} icon="trash" />
                </div>
            </div>
            { !isCollapsed &&
                <div className="wprf-repeater-inner-field">
                    {fields.map((field, fieldIndex) => {
                        return <GenericField
                            key={`field-${index}-${fieldIndex}`}
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

export default SimpleRepeaterField;
