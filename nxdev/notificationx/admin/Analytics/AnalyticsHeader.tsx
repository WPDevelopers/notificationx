import { __ } from '@wordpress/i18n';
import React from 'react'
import { useNotificationXContext } from '../../hooks';
import AnalyticsCard from './AnalyticsCard'

const AnalyticsHeader = ({ assetsURL }) => {
    const builderContext = useNotificationXContext();
    if(builderContext?.analyticsRedirect){
        return <></>;
    }
    const analytics = builderContext?.state?.analytics;
    const is_pro    = builderContext?.is_pro_active;
    return (
        <div className="nx-analytics-counter-wrapper">
            <AnalyticsCard
                type="views"
                icon={`${assetsURL.admin}images/analytics/views-icon.png`}
                title={__("Total Views", 'notificationx')} count={analytics?.totalViews} url=""
                is_pro={is_pro}
            />
            <AnalyticsCard
                type="clicks"
                icon={`${assetsURL.admin}images/analytics/clicks-icon.png`}
                title={__("Total Clicks", 'notificationx')} count={analytics?.totalClicks} url=""
                is_pro={is_pro}
            />
            <AnalyticsCard
                type="ctr"
                icon={`${assetsURL.admin}images/analytics/ctr-icon.png`}
                title={__("Click-Through-Rate", 'notificationx')} count={analytics?.totalCtr + "%"} url=""
                is_pro={is_pro}
            />
        </div>
    )
}

export default React.memo(AnalyticsHeader);