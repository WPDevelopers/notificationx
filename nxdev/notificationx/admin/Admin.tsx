import { __ } from '@wordpress/i18n';
import React from 'react'
import { Header } from '../components'
import withDocumentTitle from '../core/withDocumentTitle';
import { useNotificationXContext } from '../hooks';
import { AnalyticsHeader } from './Analytics';
import NotificationXItems from './NotificationXItems';
import NewAdmin from './NewAdmin';

// const useQuery = () => new URLSearchParams(useLocation().search.replace(/^\?/, ''));

const Admin = (props) => {
    // const query = useQuery();
    const builderContext = useNotificationXContext();

    return (
        <div>
            {/* <Header />
            <AnalyticsHeader assetsURL={builderContext.assets} />
            <NotificationXItems /> */}
            <NewAdmin />
        </div>
    )
}
export default withDocumentTitle(Admin, __("All NotificationX", 'notificationx'));