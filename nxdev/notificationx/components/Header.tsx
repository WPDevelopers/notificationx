import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom'
import { applyFilters } from '@wordpress/hooks'
import Logo from './Logo';
import { useNotificationXContext } from '../hooks';
import nxHelper from '../core/functions';

const Version = ({ version }) => {
    return <span>NotificationX: <strong>{version}</strong></span>
}

const Header = ({ addNew = false, context = {} }) => {
    const builderContext = useNotificationXContext();
    const pro_version = builderContext.pro_version;
    const version = builderContext.version;
    return (
        <div className="nx-settings-header">
            <div className="nx-header-left">
                <div className="nx-admin-header">
                    <Logo />
                    {!builderContext?.createRedirect && !addNew && <Link className="nx-add-new-btn" to={nxHelper.getRedirect({page: `nx-edit`})}>{__('Add New', 'notificationx')}</Link>}
                </div>
            </div>
            <div className="nx-header-right">
                {applyFilters('notificationx_header', <Version version={version} />)}
                {typeof pro_version === 'string' && <span>NotificationX Pro: <strong>{pro_version}</strong></span>}
            </div>
        </div >
    )
}
export default Header;