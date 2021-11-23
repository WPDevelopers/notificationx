import React from 'react'
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const Sidebar = ({ assetsUrl, is_pro_active = false }) => {
    return (
        <div className="nx-settings-right">
            <div className="nx-sidebar">
                <div className="nx-sidebar-block">
                    <div className="nx-admin-sidebar-logo">
                        <img alt="NotificationX" src={`${assetsUrl.admin}images/logo.svg`} />
                    </div>
                    <div className="nx-admin-sidebar-cta">
                        {
                            is_pro_active ?
                                <a
                                    href="https://store.wpdeveloper.com"
                                    rel="nofollow"
                                    target="_blank"
                                >
                                    {__('Manage License', 'notificationx')}
                                </a> :
                                <a
                                    href="http://wpdeveloper.com/in/upgrade-notificationx"
                                    rel="nofollow"
                                    target="_blank"
                                >
                                    {__('Upgrade to Pro', 'notificationx')}
                                </a>
                        }
                    </div>
                </div>
                <div className="nx-sidebar-block nx-license-block">
                    {applyFilters('nx_licensing')}
                </div>
            </div>
        </div>
    )
}

export default Sidebar;