import React from "react";
import { __ } from "@wordpress/i18n";
import Analytics from "../Analytics/Analytics";
import { assetsURL } from "../../core/functions";
import { Link } from "react-router-dom";

const AnalyticsDashboard = ({ props, context}) => {

    return (
        <div className='nx-analytics-integration-wrapper'>
            <div className='nx-analytics-graph-main-wrapper nx-admin-content-wrapper'>
                <Analytics isDashboard={true} />
            </div>
            <div className='nx-integration-wrapper nx-admin-content-wrapper'>
                <div className='nx-integrations-header nx-content-details header'>
                    <h4>{ __('Integrations', 'notificationx') }</h4>
                    <Link className="nx-secondary-btn" to={ { pathname: "/admin.php", search: `?page=nx-settings`} }>
                        { __('View All Integrations', 'notificationx') }
                    </Link>
                </div>
                <div className='nx-integrations-body'>
                    <img src={ assetsURL('/images/new-img/integration.png') } alt="icon" />
                </div>
            </div>
        </div>
    );
};
export default AnalyticsDashboard;
