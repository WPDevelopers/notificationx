import React, { Fragment, useEffect, useState } from "react";
import nxHelper from "../../core/functions";
import withDocumentTitle from "../../core/withDocumentTitle";
import GetStarted from "./GetStarted";
import AnalyticsOverview from "./AnalyticsOverview";
import Integration from "./Integration";
import { useNotificationXContext } from "../../hooks";

const Dashboard = (props) => {
    const builderContext = useNotificationXContext();

    return (
        <div className="nx-admin-wrapper">
            <GetStarted props={props} context={builderContext}/>
            <AnalyticsOverview props={props} context={builderContext} />
            <Integration props={props} context={builderContext}/>
        </div>
    );
};

export default withDocumentTitle(Dashboard, "Dashboard");
