import React, { useCallback, useMemo, useEffect, useState } from 'react'
import { executeChange, useBuilderContext } from 'quickbuilder';
import SimpleRepeaterField from './helpers/SimpleRepeaterField';
import { ReactSortable } from "react-sortablejs";
import { v4 } from "uuid";


const SimpleRepeater = (props) => {
    const { name: fieldName, value: fieldValue, button, _fields } = props;
    const builderContext = useBuilderContext();
    const [localMemoizedValue, setLocalMemoizedValue] = useState(builderContext.values?.[fieldName])

    // const localMemoizedValue = useMemo(() => {
    //     let localS = builderContext.values?.[fieldName];
    //     return localS;
    // }, [builderContext.values?.[fieldName], refresh])

    useEffect(() => {
        if (builderContext.values?.[fieldName] != undefined) {
            setLocalMemoizedValue(builderContext.values?.[fieldName]);
        }
    }, [builderContext.values?.[fieldName]])


    const handleSort = (value) => {
        builderContext.setFieldValue(fieldName, value);
    }

    const handleChange = (event, index) => {
        if (event.persist) {
            event.persist();
        }
        const { field, val: value } = executeChange(event);
        builderContext.setFieldValue([fieldName, index, field], value);
    }

    const handleRemove = useCallback((index) => {
        let lValue = [...localMemoizedValue];
        lValue.splice(index, 1)
        builderContext.setFieldValue(fieldName, lValue);
    }, [localMemoizedValue])

    const handleClone = useCallback((index) => {
        let lValue = [...localMemoizedValue];
        if (lValue.length > 0) {
            let indexedCopy = lValue?.[index] || {};
            if(indexedCopy?.title){
                indexedCopy = {...indexedCopy, title: (indexedCopy.title + ' - Copy')}
            }
            if(indexedCopy?.post_title){
                indexedCopy = {...indexedCopy, post_title: (indexedCopy.post_title + ' - Copy')}
            }
            if(indexedCopy?.username){
                indexedCopy = {...indexedCopy, username: (indexedCopy.username + ' - Copy')}
            }
            if(indexedCopy?.plugin_theme_name){
                indexedCopy = {...indexedCopy, plugin_theme_name: (indexedCopy.plugin_theme_name + ' - Copy')}
            }
            indexedCopy = {...indexedCopy, index: v4(), isCollapsed: false};
            builderContext.setFieldValue([fieldName, localMemoizedValue.length], indexedCopy);
        }
    }, [localMemoizedValue])

    const handleAddNewItem = useCallback(() => {
        const collapsedItems = (localMemoizedValue || []).map(item => ({
            ...item,
            isCollapsed: true,
        }));

        const newItem = {
            index: v4(),
            isCollapsed: false,
        };

        const updatedList = [...collapsedItems, newItem];

        builderContext.setFieldValue(fieldName, updatedList);
        setLocalMemoizedValue(updatedList);
    }, [localMemoizedValue, builderContext, fieldName]);


    useEffect(() => {
        if (!Array.isArray(localMemoizedValue) || localMemoizedValue.length === 0) {
            setLocalMemoizedValue([{ index: v4(), isCollapsed: false }]);
        } else {
            const updated = localMemoizedValue.map((item, i, arr) => ({
                ...item,
                index: v4(),
                isCollapsed: i !== arr.length - 1, // last item = false, others = true
            }));
            setLocalMemoizedValue(updated);
            builderContext.setFieldValue(fieldName, updated); // sync with form context
        }
    }, []);

    return (
        <div className="wprf-repeater-control">
            {
            localMemoizedValue && localMemoizedValue?.length > 0 &&
            <ReactSortable className="wprf-repeater-content" list={localMemoizedValue} setList={handleSort} handle={'.wprf-repeater-field-title'} filter={'.wprf-repeater-field-controls'} forceFallback={true}>
                {
                    localMemoizedValue.map((value, index) => {
                        return <SimpleRepeaterField
                            isCollapsed={value?.isCollapsed}
                            key={value?.index || index}
                            fields={_fields}
                            index={index}
                            parent={fieldName}
                            clone={handleClone}
                            remove={handleRemove}
                            onChange={(event) => handleChange(event, index)}
                        />
                    })
                }
            </ReactSortable>
            }
            <div className="wprf-repeater-label">
                <button
                    className="wprf-repeater-button"
                    onClick={handleAddNewItem}
                >
                    {button?.label}
                </button>
            </div>
        </div>
    )
}

export default SimpleRepeater;