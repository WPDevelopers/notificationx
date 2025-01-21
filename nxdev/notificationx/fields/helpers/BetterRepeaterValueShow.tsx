import React, { Fragment, useEffect, useState } from 'react'
import { Icon } from '@wordpress/components';
import { useInstanceId } from "@wordpress/compose";
import { GenericField, useBuilderContext } from 'quickbuilder';
import threeDots from '../../icons/three-dots.svg';
import EditIconNew from '../../icons/EditIconNew';
import { __, _n, sprintf } from '@wordpress/i18n';
import TrashIcon from '../../icons/TrashIcon';
import nxHelper from '../../core/functions';

const BetterRepeaterValueShow = (props) => {
    const builderContext = useBuilderContext();
    const [action, setAction] = useState(false);
    const { fields, onChange, index, parent, visible_fields, setIsOpen, isDefault } = props;  
    // @ts-ignore 
    const fieldsArray = Object.values(fields).filter(field => visible_fields.includes(field?.name));

    const onClone = (event:Event) => {
        event?.stopPropagation();
        props.clone(props.index);
    }
    const onDelete = (event:Event) => {
        const binIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
                            <path d="M20.5001 6.5H3.5" stroke="#D92D21" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M18.8346 9L18.3747 15.8991C18.1977 18.554 18.1092 19.8815 17.2442 20.6907C16.3792 21.5 15.0488 21.5 12.388 21.5H11.6146C8.95382 21.5 7.62342 21.5 6.75841 20.6907C5.8934 19.8815 5.8049 18.554 5.62791 15.8991L5.16797 9" stroke="#D92D21" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M9.17188 4.5C9.58371 3.33481 10.695 2.5 12.0012 2.5C13.3074 2.5 14.4186 3.33481 14.8305 4.5" stroke="#D92D21" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>`;
        nxHelper.swal({
            html: `<div class="nx-gdpr-cookies-delete-modal">
                    ${binIcon} 
                    <h2>${ __("Are you sure you want to delete this Cookie?", 'notificationx') }</h2>
                    <p>The cookie <strong>${builderContext.values?.[parent]?.[index]?.cookies_id}</strong> will be permanently deleted. This cookie will no longer be displayed on your cookie list nor be blocked prior to receiving user consent.</p>
                </div>`,
            showCancelButton: true,
            confirmButtonText: __("Delete", 'notificationx'),
            cancelButtonText: __("Cancel", 'notificationx'),
            reverseButtons: true,
            customClass: { actions: "nx-delete-actions" },
            confirmedCallback: () => {
                event?.stopPropagation();
                props.remove(props.index);
            },
            completeAction: (result) => { },
            completeArgs: (result?) => { },
            afterComplete: () => { },
        });
    }

    return (
        <div className="wprf-repeater-field">
            <div className="wprf-repeater-inner-field">
                {fieldsArray.map((field, fieldIndex) => {
                    return <div className='wprf-repeater-inner-field-item' key={'wprf-repeater-inner-field' + fieldIndex}>
                        {/* @ts-ignore  */}
                        <span>{ field?.label }</span>
                        {/* @ts-ignore  */}
                        <p>{ builderContext.values?.[parent]?.[index]?.[field?.name] }</p>
                    </div>
                })}
                {/* {!isDefault && ( */}
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
                                <li onClick={() => {
                                    setIsOpen(true);
                                    setAction(false);
                                }}> 
                                    <EditIconNew/> { __('Edit Cookie','notificationx') }
                                </li>
                                {/* @ts-ignore  */}
                                <li onClick={(event) => onDelete(event)}>  <TrashIcon/> { __('Delete Cookie', 'notificationx') } </li>
                            </ul>
                        </div>
                    }
                </div>
                {/* )} */}
            </div>
            
        </div>
    )
}

export default BetterRepeaterValueShow;
