import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react'
import { useNotificationXContext } from '../../hooks';
import AnalyticsCard from './AnalyticsCard'

const AnalyticsHeader = ({ analytics, assetsURL }) => {
    const builderContext = useNotificationXContext();
    if(builderContext?.analyticsRedirect){
        return <></>;
    }
    return (
        <div className="nx-analytics-counter-wrapper">
            <AnalyticsCard
                type="views"
                icon={`${assetsURL.admin}images/analytics/views-icon.png`}
                title={__("Total Views", 'notificationx')} count={analytics?.totalViews} url=""
            />
            <AnalyticsCard
                type="clicks"
                icon={`${assetsURL.admin}images/analytics/clicks-icon.png`}
                title={__("Total Clicks", 'notificationx')} count={analytics?.totalClicks} url=""
            />
            <AnalyticsCard
                type="ctr"
                icon={`${assetsURL.admin}images/analytics/ctr-icon.png`}
                title={__("Click-Through-Rate", 'notificationx')} count={analytics?.totalCtr + "%"} url=""
            />
        </div>
    )
}

export default React.memo(AnalyticsHeader);