import React from "react";
import { __ } from "@wordpress/i18n";
import Analytics from "../Analytics/Analytics";

const AnalyticsDashboard = (props) => {
    return (
        <div className='nx-analytics-integration-wrapper'>
            <div className='nx-analytics-graph-wrapper nx-admin-content-wrapper'>
                <Analytics isDashboard={true} />
            </div>
            <div className='nx-integration-wrapper nx-admin-content-wrapper'>
                <div className='nx-integrations-header nx-content-details header'>
                    <h4>Integrations</h4>
                    <button className='nx-secondary-btn'>View all Integration</button>
                </div>
                <div className='nx-integrations-body'>
                    <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/integration.png" alt="icon" />
                </div>
            </div>
        </div>
    );
};
export default AnalyticsDashboard;
