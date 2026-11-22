import { __ } from '@wordpress/i18n';
import React from 'react'
import withDocumentTitle from '../core/withDocumentTitle';
import { useNotificationXContext } from '../hooks';
import DashboardHeader from './Dashboard/DashboardHeader';
import NotificationXItems from './NotificationXItems';

// All NotificationX: same header as the Dashboard and the builder.
const Admin = (props) => {
    const builderContext = useNotificationXContext();

    return (
        <div className="nx-dash nx-list-page">
            <DashboardHeader context={builderContext} sidebarPage="nx-admin" />
            <main className="nx-dash__body">
                <NotificationXItems />
            </main>
        </div>
    )
}
export default withDocumentTitle(Admin, __("All NotificationX", 'notificationx'));
