import React, { Fragment, useEffect, useState } from "react";
import withDocumentTitle from "../../core/withDocumentTitle";
import GetStarted from "./GetStarted";
import AnalyticsOverview from "./AnalyticsOverview";
import Integration from "./Integration";
import { useNotificationXContext } from "../../hooks";
import NotificationTypeResource from "./NotificationTypeResource";
import Docs from "./Docs";
import AnalyticsDashboard from "./Analytics";
import NewAdmin from "../NewAdmin";
import FloatingAction from "./FloatingAction";

const Dashboard = (props) => {
    const builderContext = useNotificationXContext();
    
    return (
        <div className="nx-admin-wrapper nx-admin-new-wrapper">
            <GetStarted props={props} context={builderContext}/>
            <AnalyticsOverview props={props} context={builderContext} />
            <Integration props={props} context={builderContext}/>
            <AnalyticsDashboard/>
            <NotificationTypeResource props={props} context={builderContext} />
            <Docs props={props} context={builderContext}  />
            <FloatingAction/>
        </div>
    );
};

export default withDocumentTitle(Dashboard, "Dashboard");
