import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import { applyFilters } from '@wordpress/hooks';
import Logo from '../../components/Logo';
import NxIcon from '../../components/NxIcon';
import { syncSidebar } from '../../components/Header';

// Dashboard header: logo and plugin name. Other pages use the menu bar in `Header`.
const DashboardHeader = ({ context }) => {
    const version = context?.version;
    const proVersion = typeof context?.pro_version === 'string' && context.pro_version ? context.pro_version : null;

    useEffect(() => {
        syncSidebar('nx-dashboard');
    }, []);

    return (
        <header className="nx-dash-header">
            <div className="nx-dash-header__intro">
                <span className="nx-dash-header__mark">
                    <Logo mark width="30px" />
                </span>
                <h1 className="nx-dash-header__title">{ __('NotificationX', 'notificationx') }</h1>
            </div>

            <div className="nx-dash-header__actions">
                <div className={`nx-dash-header__versions${proVersion ? ' has-pro' : ''}`}>
                    <span className="nx-dash-header__version" title={ __('NotificationX version', 'notificationx') }>
                        { proVersion && <span className="nx-dash-header__version-label">{ __('Free', 'notificationx') }</span> }
                        { applyFilters('notificationx_header', <span>v{ version }</span>) }
                    </span>
                    { proVersion && (
                        <span className="nx-dash-header__version is-pro" title={ __('NotificationX Pro version', 'notificationx') }>
                            <NxIcon name="crown" size={13} strokeWidth={2} />
                            <span className="nx-dash-header__version-label">{ __('Pro', 'notificationx') }</span>
                            <span>v{ proVersion }</span>
                        </span>
                    ) }
                </div>
                <a
                    className="nx-button nx-button--secondary"
                    href="https://notificationx.com/docs/"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <NxIcon name="book" size={16} />
                    { __('Documentation', 'notificationx') }
                </a>
                { !context?.createRedirect && (
                    <Link className="nx-button nx-button--primary nx-add-new-btn" to={ { pathname: '/admin.php', search: '?page=nx-edit' } }>
                        <NxIcon name="plus" size={16} strokeWidth={2.25} />
                        { __('Add New', 'notificationx') }
                    </Link>
                ) }
            </div>
        </header>
    );
};

export default DashboardHeader;
