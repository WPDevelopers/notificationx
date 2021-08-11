import React, { useEffect, useState } from 'react'
import AnalyticsCard from './AnalyticsCard'

const AnalyticsHeader = ({ analytics, assetsURL }) => {
    return (
        <div className="nx-analytics-counter-wrapper">
            <AnalyticsCard
                type="views"
                icon={`${assetsURL.admin}images/analytics/views-icon.png`}
                title="Total Views" count={analytics?.totalViews} url=""
            />
            <AnalyticsCard
                type="clicks"
                icon={`${assetsURL.admin}images/analytics/clicks-icon.png`}
                title="Total Clicks" count={analytics?.totalClicks} url=""
            />
            <AnalyticsCard
                type="ctr"
                icon={`${assetsURL.admin}images/analytics/ctr-icon.png`}
                title="Click-Through-Rate" count={analytics?.totalCtr} url=""
            />
        </div>
    )
}

export default React.memo(AnalyticsHeader);