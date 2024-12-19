import React, { Fragment, useEffect, useState } from 'react'
import { Icon } from '@wordpress/components';
import { useInstanceId } from "@wordpress/compose";
import { GenericField, useBuilderContext } from 'quickbuilder';
import threeDots from '../../icons/three-dots.svg';
import EditIconNew from '../../icons/EditIconNew';
import { __ } from '@wordpress/i18n';
import TrashIcon from '../../icons/TrashIcon';


const BetterRepeaterValueShow = (props) => {
    const builderContext = useBuilderContext();
    const [action, setAction] = useState(false);
    const [isModalOpen, setIsModalOpen] = useState(props.isCollapsed);
    const { fields, onChange, index, parent, visible_fields, setIsOpen } = props;    
    // @ts-ignore 
    const fieldsArray = Object.values(fields).filter(field => visible_fields.includes(field?.name));

    const onClone = (event:Event) => {
        event?.stopPropagation();
        props.clone(props.index);
    }
    const onDelete = (event:Event) => {
        event?.stopPropagation();
        props.remove(props.index);
    }

    useEffect(() => {
        if( isModalOpen ) {
            setIsOpen(isModalOpen)
        }
    }, [isModalOpen])    

    return (
        <div className="wprf-repeater-field">
            <div className="wprf-repeater-inner-field">
                {fieldsArray.map((field, fieldIndex) => {
                    return <div className='wprf-repeater-inner-field-item'>
                        {/* @ts-ignore  */}
                        <span>{ field?.label }</span>
                        {/* @ts-ignore  */}
                        <p>{ builderContext.values?.[parent]?.[index]?.[field?.name] }</p>
                    </div>
                })}
                <div className="nx-action-toggle-wrapper">
                    <a
                        className="nx-admin-three-dots"
                        onClick={ () => setAction(!action) }
                    >   
                        <img src={threeDots} alt={'three-dots'} />
                    </a>
                    { action && 
                        <div className="nx-cookies-list-action">
                            {/*  || item?.elementor_id */}
                            <ul id="nx-admin-actions-ul">
                                <li onClick={() => setIsModalOpen(!isModalOpen)}> 
                                    <EditIconNew/> { __('Edit Cookies','notificationx') }
                                </li>
                                {/* @ts-ignore  */}
                                <li onClick={(event) => onDelete(event)}>  <TrashIcon/> { __('Delete Cookies', 'notificationx') } </li>
                            </ul>
                        </div>  
                    }
                </div>
            </div>
            
        </div>
    )
}

export default BetterRepeaterValueShow;
