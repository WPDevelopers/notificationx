import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ReactSortable } from "react-sortablejs";
import { RepeaterField } from 'quickbuilder/src/fields/helpers';
import { executeChange, useBuilderContext } from 'quickbuilder';
import { v4 } from "uuid";
import AdvancedRepeaterField from './helpers/AdvancedRepeaterField';
import Pagination from 'rc-pagination';
import { SelectControl } from "@wordpress/components";
import { __ } from '@wordpress/i18n';
import localeInfo from 'rc-pagination/es/locale/en_US';
import { chunkArray } from '../core/functions';

const AdvancedRepeater = (props) => {
    const { name: fieldName, value: fieldValue, button, field } = props;
    const [currentPage, setCurrentPage] = useState(1);
    const [itemsPerPage, setItemsPerPage] = useState(5);
    const [selectedField, setSelectedField] = useState([]);
    const builderContext = useBuilderContext();
    const [localMemoizedValue, setLocalMemoizedValue] = useState(builderContext.values?.[fieldName]);

    useEffect(() => {
        if (builderContext.values?.[fieldName] !== undefined) {
            setLocalMemoizedValue(builderContext.values?.[fieldName]);
        }
    }, [builderContext.values?.[fieldName]]);

    useEffect(() => {
        if (localMemoizedValue === undefined || localMemoizedValue === '') {
            setLocalMemoizedValue([{ index: v4() }]);
        } else {
            setLocalMemoizedValue((items) => items.map((item) => {
                return { ...item, index: v4() };
            }));
        }
    }, []);

    const handleSort = (value) => {
        builderContext.setFieldValue(fieldName, value);
    };

    const handleChange = (event, index) => {
        if (event.persist) {
            event.persist();
        }
        const { field, val: value } = executeChange(event);
        builderContext.setFieldValue([fieldName, index, field], value);
    };

    const handleRemove = useCallback((index) => {
        let lValue = [...localMemoizedValue];
        lValue.splice(index, 1);
        builderContext.setFieldValue(fieldName, lValue);
    }, [localMemoizedValue]);

    const handleClone = useCallback((index) => {
        let lValue = [...localMemoizedValue];
        if (lValue.length > 0) {
            let indexedCopy = lValue?.[index] || {};
            if (indexedCopy?.title) {
                indexedCopy = { ...indexedCopy, title: (indexedCopy.title + ' - Copy') };
            }
            if (indexedCopy?.post_title) {
                indexedCopy = { ...indexedCopy, post_title: (indexedCopy.post_title + ' - Copy') };
            }
            if (indexedCopy?.username) {
                indexedCopy = { ...indexedCopy, username: (indexedCopy.username + ' - Copy') };
            }
            if (indexedCopy?.plugin_theme_name) {
                indexedCopy = { ...indexedCopy, plugin_theme_name: (indexedCopy.plugin_theme_name + ' - Copy') };
            }
            indexedCopy = { ...indexedCopy, index: v4(), isCollapsed: false };
            builderContext.setFieldValue([fieldName, localMemoizedValue.length], indexedCopy);
        }
    }, [localMemoizedValue]);

    const paginatedItems = useMemo(() => {
        return chunkArray(localMemoizedValue || [], itemsPerPage);
    }, [localMemoizedValue, itemsPerPage]);

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const handleItemsPerPageChange = (value) => {
        setItemsPerPage(parseInt(value));
        setCurrentPage(1); // Reset to first page when items per page change
    };

    const handleChangeCollapseState = (event, index) => {
        handleChange(event, index);
    };

    const onChecked = (index) => {
        const _fields = [...selectedField];
        const itemIndexInSelected = _fields.indexOf(index);
        if (itemIndexInSelected > -1) {
            _fields.splice(itemIndexInSelected, 1);
        } else {
            _fields.push(index);
        }
        setSelectedField(_fields);
    };

    const checkAll = (event) => {
        const isChecked = event.target.checked;
        if (isChecked) {
            const allIndices = localMemoizedValue.map(item => item.index);
            setSelectedField(allIndices);
        } else {
            setSelectedField([]);
        }
    };


    const bulkDelete = () => {
        const bulkDeleteFromLocal = localMemoizedValue.filter(item => !selectedField.includes(item.index));
        builderContext.setFieldValue(fieldName, bulkDeleteFromLocal);
        setSelectedField([]);
    }

    const totalItems = localMemoizedValue?.length || 0;
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, totalItems);
    const currentPageItems = paginatedItems[currentPage - 1] || [];

    return (
        <div className="wprf-repeater-control wprf-advanced-repeater-control">
            <div className="wprf-advanced-repeater-header">
                <div className="nx-all-selector">
                    <input id="nx-advanced-repeater-all-checkbox" type="checkbox" checked={selectedField?.length == localMemoizedValue?.length ? true : false} onChange={(event) => checkAll(event)} />
                    <label htmlFor="nx-advanced-repeater-all-checkbox">{__('Select All', 'notificationx')}</label>
                </div>
                <div className="wprf-repeater-label">
                    <button
                        className="wprf-repeater-button add-new"
                        onClick={() => builderContext.setFieldValue(fieldName, [...localMemoizedValue, { index: v4() }])}
                        disabled={totalItems >= 100 ? true : false}
                    >
                        {button?.label}
                    </button>
                    <button
                        className='wprf-repeater-button bulk-edit'
                    >
                        {__('Bulk Edit', 'notificationx')}
                    </button>
                    <button
                        className="wprf-repeater-button bulk-delete"
                        onClick={() => bulkDelete()}
                    >
                        {__('Bulk Delete', 'notificationx')}
                    </button>
                </div>
            </div>
            {localMemoizedValue && localMemoizedValue.length > 0 && (
                <>
                    <ReactSortable
                        className="wprf-repeater-content wprf-advanced-repeater-content"
                        list={localMemoizedValue}
                        setList={handleSort}
                        handle={'.wprf-repeater-field-title'}
                        filter={'.wprf-repeater-field-controls'}
                        forceFallback={true}
                    >
                        {currentPageItems.map((value, index) => (
                            <AdvancedRepeaterField
                                isCollapsed={true}
                                key={value?.index || (index + (currentPage - 1) * itemsPerPage)}
                                fields={field}
                                index={index + (currentPage - 1) * itemsPerPage}
                                __index={value?.index}
                                parent={fieldName}
                                clone={handleClone}
                                remove={handleRemove}
                                checked={selectedField.findIndex(element => element == value.index) != -1 ? true : false}
                                onChange={(event) => handleChangeCollapseState(event, index + (currentPage - 1) * itemsPerPage)}
                                onChecked={onChecked}
                            />
                        ))}
                    </ReactSortable>
                    <div className="nx-admin-items-footer">
                        <div className="items-per-page-wrapper">
                            <SelectControl
                                // label={__('Item Per Page', 'notificationx')}
                                options={[
                                    { value: "5", label: __("5") },
                                    { value: "10", label: __("10") },
                                    { value: "20", label: __("20") },
                                    { value: "50", label: __("50") },
                                    { value: "100", label: __("100") },
                                ]}
                                value={itemsPerPage.toString()}
                                onChange={(value) => handleItemsPerPageChange(value)}
                            />
                            <label htmlFor="">{__('Items Per Page')}</label>
                        </div>
                        <div className='pagination-wrapper'>
                            <div className="pagination-info">
                                {`Displaying ${startIndex}-${endIndex} of ${totalItems}`}
                            </div>
                            {/* @ts-ignore  */}
                            <Pagination
                                current={currentPage}
                                onChange={handlePageChange}
                                total={localMemoizedValue.length}
                                pageSize={itemsPerPage}
                                showTitle={false}
                                locale={localeInfo}
                            />
                        </div>
                    </div>
                </>
            )}
        </div>
    );
};

export default AdvancedRepeater;
