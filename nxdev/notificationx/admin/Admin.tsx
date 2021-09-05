import React from 'react'
import { useLocation, useParams, useRouteMatch } from 'react-router-dom';
import { useBuilderContext } from '../../form-builder/src/core/hooks';
import { Header } from '../components'
import nxHelper from '../core/functions';
import withDocumentTitle from '../core/withDocumentTitle';
import { useNotificationXContext } from '../hooks';
import { AnalyticsHeader } from './Analytics';
import NotificationXItems from './NotificationXItems';

// const useQuery = () => new URLSearchParams(useLocation().search.replace(/^\?/, ''));

const Admin = (props) => {
    // const query = useQuery();
    const builderContext = useNotificationXContext();

    // const location = useLocation();
    // const location = useRouteMatch();

    let params = useParams();
    console.log(params);

    console.log(props);
    // return;
    return (
        <div>
            <Header />
            <AnalyticsHeader analytics={...builderContext?.analytics} assetsURL={builderContext.assets} />
            <NotificationXItems />
        </div>
    )
}
export default withDocumentTitle(Admin, "All NotificationX");