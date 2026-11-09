import { __ } from '@wordpress/i18n';
import React, { useState } from 'react'
import nxHelper from '../../core/functions';
import NxIcon from '../../components/NxIcon';

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
        <div className='nx-dash-announce' role="status">
            <span className="nx-dash-announce__badge">
                <NxIcon name="sparkles" size={14} />
                { __('New', 'notificationx') }
            </span>
            <p className="nx-dash-announce__text">
                <strong>{ __('Introducing Build with AI in NotificationX', 'notificationx') }</strong>
                <span>{ __(' - Create Notification bar with prompt', 'notificationx') }</span>
            </p>
            <a className="nx-dash-announce__link" href="https://notificationx.com/changelog" target="_blank" rel="noopener noreferrer">
                { __('Changelog', 'notificationx') }
                <NxIcon name="arrowRight" size={14} />
            </a>
            <button type="button" className='nx-dash-announce__close' onClick={ handleNoticeClose } aria-label={ __('Dismiss', 'notificationx') }>
                <NxIcon name="close" size={16} />
            </button>
        </div>
    )
}

export default Notice
