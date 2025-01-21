import React, { useCallback, useMemo, useEffect, useState } from 'react'
import { ReactSortable } from "react-sortablejs";
import { v4 } from "uuid";
import { executeChange, useBuilderContext, GenericField, isArray } from 'quickbuilder';
import BetterRepeaterField from './helpers/BetterRepeaterField';
import ReactModal from "react-modal";
import BetterRepeaterValueShow from './helpers/BetterRepeaterValueShow';
import CloseIcon from '../icons/Close';
import { __ } from '@wordpress/i18n';
import { modalStyle } from '../core/constants';
import EditIcon from '../icons/EditIcon';


const BetterRepeater = (props) => {
    const { name: fieldName, value: fieldValue, button, _fields, visible_fields } = props;
    const [isOpen, setIsOpen] = useState(false);
    const [isIntegrationModalOpen, setIsIntegrationModalOpen] = useState(false);
    const [isEditNecessaryModalOpen, setIsEditCookieInfoModalOpen] = useState(false);
    const builderContext = useBuilderContext();
    const [localMemoizedValue, setLocalMemoizedValue] = useState(builderContext.values?.[fieldName]);
    const [activeEditItem, setActiveEditItem] = useState("");

    useEffect(() => {
        if (builderContext.values?.[fieldName] != undefined) {
            setLocalMemoizedValue(builderContext.values?.[fieldName]);
        }
    }, [builderContext.values?.[fieldName]]);

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
    

    useEffect(() => {
        if (localMemoizedValue == undefined || localMemoizedValue == '') {
            setLocalMemoizedValue([]);
        } else{
            setLocalMemoizedValue((items) => items.map((item) => {
                return {...item, index: v4()};
            }))
        }
    }, [])    
    
    const handleButtonClick = () => {
        builderContext.setFieldValue(fieldName, [...localMemoizedValue, {index: v4()}]);
        setIsOpen(true);
    }

    const _handleButtonClick = () => {
        setIsIntegrationModalOpen(true);
    }

    useEffect(() => {
      if( !isOpen && isArray(localMemoizedValue) ) {
        const updatedData = localMemoizedValue.filter(item => 
            (item.cookies_id && item.cookies_id.trim() !== "")
        );
        if( isArray(updatedData) ) {
            setLocalMemoizedValue(updatedData)
        }
    }
    }, [isOpen]);

    return (
        <div className="wprf-repeater-control">
            { button?.position == 'top' && 
                <div className="wprf-repeater-label">
                    <button className="wprf-repeater-button"
                        onClick={handleButtonClick}>
                        {button?.label}
                    </button>
                </div>
            }
            {
                localMemoizedValue && localMemoizedValue?.length > 0 &&
                <div className="wprf-repeater-content">
                    {
                        localMemoizedValue.map((value, index) => {
                            return <BetterRepeaterValueShow
                                isDefault={value?.default ? true : false}
                                isCollapsed={value?.isCollapsed}
                                key={value?.index || index}
                                fields={_fields}
                                index={index}
                                parent={fieldName}
                                clone={handleClone}
                                remove={handleRemove}
                                onChange={(event: any) => handleChange(event, index)}
                                visible_fields={visible_fields}
                                setIsOpen={() => {
                                    setIsOpen(true);
                                    setActiveEditItem(value?.index);
                                }}
                            />
                        })
                    }
                </div>
            }
            { button?.position == 'bottom' && 
                <div className="wprf-repeater-label">
                    <button className="wprf-repeater-button"
                        onClick={_handleButtonClick}>
                        {button?.label}
                    </button>
                </div>
            }
            <ReactModal
                isOpen={isOpen}
                ariaHideApp={false}
                overlayClassName={`nx-custom-notification-edit`}
                style={{
                    ...modalStyle,
                    overlay: {
                        ...modalStyle.overlay,
                        zIndex: 99999,
                    }
                }}
            >
                <>
                    <div className="wprf-modal-preview-header">
                        <span>{ activeEditItem ? __( 'Edit Custom Cookie','notificationx' ) : __( 'Add Custom Cookies','notificationx' ) }</span>
                        <button onClick={() => {
                                setIsOpen(false);
                                setActiveEditItem("");
                            }}>
                            <CloseIcon />
                        </button>
                    </div>
                    <div className="wprf-modal-table-wrapper wpsp-better-repeater-fields">
                        { localMemoizedValue && localMemoizedValue?.length > 0 &&
                            <div className="wprf-repeater-content">
                                { activeEditItem &&
                                    localMemoizedValue.map((value, index) => {
                                        if(  value?.index == activeEditItem ) {
                                            return <BetterRepeaterField
                                                isCollapsed={value?.isCollapsed}
                                                key={value?.index || index}
                                                fields={_fields}
                                                index={index}
                                                parent={fieldName}
                                                clone={handleClone}
                                                remove={handleRemove}
                                                onChange={(event: any) => handleChange(event, index)}
                                            />
                                        }
                                    })
                                }
                                { !activeEditItem &&
                                    localMemoizedValue.map((value, index) => {                                        
                                        if(  localMemoizedValue?.length == (index + 1) ) {
                                            return <BetterRepeaterField
                                                isCollapsed={value?.isCollapsed}
                                                key={value?.index || index}
                                                fields={_fields}
                                                index={index}
                                                parent={fieldName}
                                                clone={handleClone}
                                                remove={handleRemove}
                                                onChange={(event: any) => handleChange(event, index)}
                                            />
                                        }
                                    })
                                }
                            </div>
                        }
                    </div>
                    <div className="wprf-modal-preview-footer">
                        <button className='wpsp-btn wpsp-btn-preview-update' onClick={() => setIsOpen(false)}> { activeEditItem ? __( 'Update','notificationx' ) : __( 'Save','notificationx' ) }</button>
                    </div>
                </>
            </ReactModal>
            <ReactModal
                isOpen={isIntegrationModalOpen}
                onRequestClose={() => setIsIntegrationModalOpen(false)}
                ariaHideApp={false}
                overlayClassName={`nx-cookies-list-integrations`}
                style={modalStyle}
            >
                    <>
                    <div className="wprf-modal-preview-header">
                        <span>{ __( 'Integrations','notificationx' ) }</span>
                        <div className="wprf-repeater-label">
                            <button className="wprf-repeater-button" onClick={handleButtonClick}>
                                { __('Custom Cookies','notificationx') }
                            </button>
                        </div>
                    </div>
                    <div className="wprf-modal-table-wrapper wpsp-better-repeater-fields">
                        
                    </div>
                    <div className="wprf-modal-preview-footer">
                        <button className='wpsp-btn wpsp-btn-preview-update' onClick={() => setIsOpen(false)}>{__('Save', 'notificationx')}</button>
                    </div>
                </>
            </ReactModal>
            <ReactModal
                isOpen={isEditNecessaryModalOpen}
                onRequestClose={() => setIsEditCookieInfoModalOpen(false)}
                ariaHideApp={false}
                overlayClassName={`nx-custom-notification-edit`}
                style={{
                    ...modalStyle,
                    overlay: {
                        ...modalStyle.overlay,
                        zIndex: 99999,
                    }
                }}                
            >
                <>
                    <div className="wprf-modal-preview-header">
                        <span>{ __( 'Edit Category','notificationx' ) }</span>
                        <button onClick={() => setIsEditCookieInfoModalOpen(false)}>
                            <CloseIcon />
                        </button>
                    </div>
                    <div className="wprf-modal-preview-footer">
                        <button className='wpsp-btn wpsp-btn-preview-update' onClick={() => setIsEditCookieInfoModalOpen(false)}>{__('Save', 'notificationx')}</button>
                    </div>
                </>
            </ReactModal>
        </div>
    )
}

export default BetterRepeater;