import { __ } from '@wordpress/i18n';
import React, { useState } from 'react'
import nxHelper from '../../core/functions';

const Notice = ( { props, context } ) => {
    const [isNoticeClose, setIsNoticeClose] = useState( context?.nx_admin_notice_close || false);
    const handleNoticeClose = () => {
        setIsNoticeClose(true);

        nxHelper.post('admin-notice-close', {})
        .then(response => {
            // @ts-ignore 
            if (response?.success) {
                setIsNoticeClose(true);
            }
        })
        .catch(err => {
            console.log(err);
        });
    }

    if( isNoticeClose ) {
        return null;
    }
    
    
    return (
        <div className='nx-dashboard-notice-wrapper'>
            <span>✨ { __('Introducing Build with AI in NotificationX - Create Notification bar with prompt', 'notificationx') }</span> 
            <a href="https://notificationx.com/changelog" target="_blank">Changelog</a>
            <button className='close-button' onClick={ handleNoticeClose }>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M6.85414 5.33107L12.1852 0L13.7083 1.52315L8.37727 6.85421L13.7083 12.1852L12.1852 13.7083L6.85414 8.37735L1.52315 13.7083L0 12.1852L5.33102 6.85421L0 1.52315L1.52315 0L6.85414 5.33107Z" fill="white"/>
                </svg>
            </button>
        </div>
    )
}

export default Notice
