import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ReactSortable } from "react-sortablejs";
import { RepeaterField } from 'quickbuilder/src/fields/helpers';
import { executeChange, useBuilderContext } from 'quickbuilder';
import { v4 } from "uuid";
import AdvancedRepeaterField from './helpers/AdvancedRepeaterField';
import Pagination from 'rc-pagination';
import { SelectControl } from "@wordpress/components";
import { __ } from '@wordpress/i18n';
import localeInfo from 'rc-pagination/es/locale/en_US';
import nxHelper, { chunkArray, dateConvertToHumanReadable } from '../core/functions';
import ReactModal from "react-modal";
import BulkEditField from './helpers/PreviewField';
import CloseIcon from '../icons/Close';
import EditIcon from '../icons/EditIcon';
import TrashIcon from '../icons/TrashIcon';
import EyeIcon from '../icons/EyeIcon';
import Swal from 'sweetalert2';
import { useNotificationXContext } from '../hooks';

const AdvancedRepeater = (props) => {
    const { name: fieldName, value: fieldValue, button, field } = props;
    const [currentPage, setCurrentPage]     = useState(1);
    const [itemsPerPage, setItemsPerPage]   = useState(5);
    const [selectedField, setSelectedField] = useState([]);
    const builderContext                    = useBuilderContext();
    const nxContext                         = useNotificationXContext();

    const [localMemoizedValue, setLocalMemoizedValue] = useState(builderContext.values?.[fieldName]);
    const [isOpen, setIsOpen] = useState(false);
    const [isPreview, setIsPreview] = useState(false);
    const [previewCurrentPage, setPreviewCurrentPage] = useState(1);
    const [previewItemsPerPage, setPreviewItemsPerPage] = useState(5);

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
            builderContext.setFieldValue([fieldName, localMemoizedValue?.length], indexedCopy);
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
        Swal.fire({
            title: __("Are you sure?", "notificationx"),
            text: __("Do you really want to delete the selected items? This process cannot be undone.", "notificationx"),
            icon: "error",
            showCancelButton: true,
            confirmButtonText: __("Yes, Delete Them", "notificationx"),
            cancelButtonText: __("No, Keep Them", "notificationx"),
            customClass: { actions: "nx-delete-actions" },
        }).then((result) => {
            if (result.isConfirmed) {
                const bulkDeleteFromLocal = localMemoizedValue.filter(item => !selectedField.includes(item.index));
                builderContext.setFieldValue(fieldName, bulkDeleteFromLocal);
                setSelectedField([]);
            }
        });
    };
    

    const totalItems       = localMemoizedValue?.length || 0;
    const startIndex       = (currentPage - 1) * itemsPerPage + 1;
    const endIndex         = Math.min(currentPage * itemsPerPage, totalItems);
    const currentPageItems = paginatedItems[currentPage - 1] || [];

    // Headers to exclude
    const excludedHeaders   = ["index", "chosen", "selected", "isCollapsed",'iscollapsed', 'id'];
    const customHeaderOrder = ["first_name", "last_name", "post_title", "timestamp", "image"];
    // Generate headers
    const headers = useMemo(() => {
        if (localMemoizedValue?.length > 0) {
            const allHeaders     = Object.keys(localMemoizedValue[0]).filter(header => !excludedHeaders.includes(header));
            const orderedHeaders = customHeaderOrder.filter(header => allHeaders.includes(header));
            const otherHeaders   = allHeaders.filter(header => !customHeaderOrder.includes(header));
            return [...orderedHeaders.slice(0, 3), ...otherHeaders, ...orderedHeaders.slice(3)];
        }
        return [];
    }, [localMemoizedValue, excludedHeaders, customHeaderOrder]);
    
    // selected bulk items
    const bulkSelectedItems = localMemoizedValue?.filter(obj => selectedField.includes(obj.index));
    const previewPaginatedItems = useMemo(() => {
        return chunkArray(localMemoizedValue || [], previewItemsPerPage);
    }, [localMemoizedValue, previewItemsPerPage]);
    
    const handlePreviewPageChange = (page) => {
        setPreviewCurrentPage(page);
    };
    
    const handlePreviewItemsPerPageChange = (value) => {
        setPreviewItemsPerPage(parseInt(value));
        setPreviewCurrentPage(1);
    };
    
    const previewStartIndex       = (previewCurrentPage - 1) * previewItemsPerPage + 1;
    const previewEndIndex         = Math.min(previewCurrentPage * previewItemsPerPage, totalItems);
    const previewCurrentPageItems = previewPaginatedItems[previewCurrentPage - 1] || [];
    const csv_upload_loader = nxContext?.state?.csv_upload_loader?.csv_upload_loader;
    
    return (
        <div className={`wprf-repeater-control wprf-advanced-repeater-control ${ csv_upload_loader ? 'loading' : 'loading' }`}>
            { csv_upload_loader && 
                <div className="loader-wrapper">
                    <img src={ `${nxContext.assets.admin}images/logos/logo-preloader.gif` } />
                </div>
            }
            <div className="wprf-advanced-repeater-heading">
                <span>{__('Custom Notification')}</span>
                <div className="wprf-advanced-repeater-header-action">
                    { localMemoizedValue?.length > 9 &&
                        <button className='wprf-repeater-button preview' onClick={ () => setIsPreview(true) }>
                            <EyeIcon /> {__('Preview', 'notificationx')}
                        </button>
                    }
                    <button
                        className="wprf-repeater-button add-new"
                        onClick={() => builderContext.setFieldValue(fieldName, [...localMemoizedValue, { index: v4() }])}
                        disabled={totalItems >= 100 ? true : false}
                    >
                        {button?.label}
                    </button>
                </div>
            </div>
            <div className="wprf-advanced-repeater-header">
                <div className="nx-all-selector">
                    <input id="nx-advanced-repeater-all-checkbox" type="checkbox" checked={selectedField?.length == localMemoizedValue?.length ? true : false} onChange={(event) => checkAll(event)} />
                    <label htmlFor="nx-advanced-repeater-all-checkbox">{__('Select All', 'notificationx')}</label>
                </div>
                {totalItems > 1 &&
                    <div className="wprf-repeater-label">
                        <button
                            className='wprf-repeater-button bulk-edit'
                            onClick={() => setIsOpen(true)}
                            disabled={ bulkSelectedItems?.length < 2 ? true : false }
                        >
                            <EditIcon /> {__('Edit', 'notificationx')}
                        </button>
                        <button
                            className="wprf-repeater-button bulk-delete"
                            onClick={() => bulkDelete()}
                            disabled={ bulkSelectedItems?.length < 2 ? true : false }
                        >
                            <TrashIcon /> {__('Delete', 'notificationx')}
                        </button>
                    </div>
                }
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
            <ReactModal
                isOpen={isOpen}
                onRequestClose={() => setIsOpen(false)}
                ariaHideApp={false}
                style={{
                    overlay: {
                        position: "fixed",
                        display: "flex",
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        backgroundColor: "rgba(3, 6, 60, 0.7)",
                        zIndex: 9999,
                        padding: "60px 15px",
                    },
                    content: {
                        position: "static",
                        width: '900px',
                        margin: "auto",
                        border: "0px solid #5414D0",
                        // background: "#5414D0",
                        overflow: "auto",
                        WebkitOverflowScrolling: "touch",
                        borderRadius: "4px",
                        outline: "none",
                        padding: "15px",
                    },
                }}
            >
                <>
                    <div className="wprf-modal-preview-header">
                        <span>{ __( 'Edit','notificationx' ) }</span>
                        <button onClick={() => setIsOpen(false)}>
                            <CloseIcon />
                        </button>
                    </div>
                    <div className="wprf-modal-table-wrapper wpsp-bulk-edit-fields">
                        { bulkSelectedItems && bulkSelectedItems.map((value, index) => (
                            <BulkEditField
                                isCollapsed={true}
                                key={value?.index}
                                fields={field}
                                index={localMemoizedValue.findIndex(ele => ele.index == value?.index)}
                                __index={value?.index}
                                parent={fieldName}
                                remove={handleRemove}
                                onChange={(event) => handleChangeCollapseState(event, localMemoizedValue.findIndex(ele => ele.index == value?.index))}
                            />
                        ))}
                    </div>
                    <div className="wprf-modal-preview-footer">
                        <button className='wpsp-btn wpsp-btn-preview-update' onClick={() => setIsOpen(false)}>{__('Update', 'notificationx')}</button>
                    </div>
                </>
            </ReactModal>
            <ReactModal
                isOpen={isPreview}
                onRequestClose={() => setIsPreview(false)}
                ariaHideApp={false}
                style={{
                    overlay: {
                        position: "fixed",
                        display: "flex",
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        backgroundColor: "rgba(3, 6, 60, 0.7)",
                        zIndex: 9999,
                        padding: "60px 15px",
                    },
                    content: {
                        position: "static",
                        width: '900px',
                        margin: "auto",
                        border: "0px solid #5414D0",
                        // background: "#5414D0",
                        overflow: "auto",
                        WebkitOverflowScrolling: "touch",
                        borderRadius: "4px",
                        outline: "none",
                        padding: "15px",
                    },
                }}
            >
                <>
                    {headers.length > 0 && (
                        <>
                            <div className="wprf-modal-preview-header">
                                <span>{ __( 'Custom Notification Preview','notificationx' ) }</span>
                                <button onClick={() => setIsPreview(false)}>
                                    <CloseIcon />
                                </button>
                            </div>
                            <div className='wprf-modal-table-wrapper'>
                                <table className="table table-striped">
                                    <thead>
                                        <tr>
                                            {headers.map(header => (
                                                <th key={header}>{header}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {previewCurrentPageItems && previewCurrentPageItems.map((row, index) => (
                                            <tr key={index}>
                                                {headers.map(header => {
                                                    const cellData = row[header];
                                                    if (header === 'timestamp' && cellData) {
                                                        return (
                                                            <td key={header}>
                                                                {dateConvertToHumanReadable(cellData)}
                                                            </td>
                                                        );
                                                    }
                                                    if (header === 'image' && cellData) {
                                                        return (
                                                            <td key={header} className='wprf-preview-img'>
                                                                <div className="image-container">
                                                                    <img src={cellData} style={{ maxWidth: '100px', height: 'auto' }} className="image" />
                                                                    <div className="popup-image">
                                                                        <img src={cellData} />
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        );
                                                    }
                                                    if (typeof cellData === 'object' && cellData !== null) {
                                                        return (
                                                            <td key={header}>
                                                                {JSON.stringify(cellData)}
                                                            </td>
                                                        );
                                                    }
                                                    return (
                                                        <td key={header}>
                                                            {cellData !== undefined ? cellData : ''}
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <div className="wprf-modal-preview-footer">
                                <div className="items-per-page-wrapper">
                                    <SelectControl
                                        className="items-per-page"
                                        value={previewItemsPerPage.toString()}
                                        options={[
                                            { label: '5', value: '5' },
                                            { label: '10', value: '10' },
                                            { label: '15', value: '15' },
                                            { label: '20', value: '20' },
                                        ]}
                                        onChange={(value) => handlePreviewItemsPerPageChange(value)}
                                    />
                                    <label>{ __('Items Per Page', 'notificationx') }</label>
                                </div>
                                <div className='pagination-wrapper'>
                                    <div className="pagination-info">
                                        {`${previewStartIndex} - ${previewEndIndex} of ${totalItems} items`}
                                    </div>
                                    <Pagination
                                        current={previewCurrentPage}
                                        total={totalItems}
                                        pageSize={previewItemsPerPage}
                                        onChange={handlePreviewPageChange}
                                        locale={localeInfo}
                                    />
                                </div>
                            </div>
                        </>
                    )}
                </>
            </ReactModal>
        </div>
    );
};

export default AdvancedRepeater;