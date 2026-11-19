import React from 'react';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import NxIcon from './NxIcon';

// Builder page head: breadcrumb back to the list and the notification title.
const BuilderTitle = ({ title, setTitle, isEdit = false }) => (
    <div className="nx-builder-head">
        <nav className="nx-builder-head__crumbs" aria-label={__('Breadcrumb', 'notificationx')}>
            <Link to={{ pathname: '/admin.php', search: '?page=nx-admin' }}>
                {__('Notifications', 'notificationx')}
            </Link>
            <NxIcon name="chevron-right" size={14} strokeWidth={2} />
            <span aria-current="page">{isEdit ? __('Edit', 'notificationx') : __('Add New', 'notificationx')}</span>
        </nav>
        <div className="nx-builder-head__title">
            <input
                className="widefat nx-title"
                type="text"
                name="title"
                id="nx-title"
                placeholder={__("NotificationX Title", 'notificationx')}
                aria-label={__("NotificationX Title", 'notificationx')}
                value={title}
                onChange={(e) => setTitle(e.target.value)}
            />
            <NxIcon name="pencil" size={18} />
        </div>
    </div>
);

export default BuilderTitle;
