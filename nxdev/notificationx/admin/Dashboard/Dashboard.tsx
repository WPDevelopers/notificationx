import React, { useEffect, useState } from "react";
import withDocumentTitle from "../../core/withDocumentTitle";
import GetStarted from "./GetStarted";
import AnalyticsOverview from "./AnalyticsOverview";
import Integration from "./Integration";
import { useNotificationXContext } from "../../hooks";
import NotificationTypeResource from "./NotificationTypeResource";
import Docs from "./Docs";
import AnalyticsDashboard from "./Analytics";
import FloatingAction from "./FloatingAction";
import { BuilderProvider, useBuilder } from "quickbuilder";
import { WrapperWithLoader } from "../../components";
// @ts-ignore
import { __ } from "@wordpress/i18n";
import { useLocation } from "react-router";
import nxHelper from "../../core/functions";


const Dashboard = (props) => {
    const builderContext = useNotificationXContext();
    const builder = useBuilder(notificationxTabs.quick_build);
    const [isLoading, setIsLoading] = useState(true);
    const [title, setTitle] = useState("");
    const location = useLocation();

    const getParam = (param, d?) => {
        const query = nxHelper.useQuery(location.search);
        return query.get(param) || d;
    };

    useEffect(() => {
        setIsLoading(false);
        const section = getParam("section", '');
        if( section == 'resource' ) {
            setTimeout(() => {
                const section = document.getElementById('nx-other-details-wrapper');
                if (section) {
                    section.scrollIntoView({ behavior: 'smooth' });
                }
            }, 500);
        }
    }, []);    

    return (
        <BuilderProvider
            value={{ ...builder, isLoading, setIsLoading, title, setTitle }}
        >
            <WrapperWithLoader isLoading={isLoading}>
                <div className="nx-admin-wrapper nx-admin-new-wrapper">
                    <GetStarted props={props} context={builderContext}/>
                    <AnalyticsOverview props={props} context={builderContext} />
                    <Integration props={props} context={builderContext}/>
                    <AnalyticsDashboard props={props} context={builderContext} />
                    <NotificationTypeResource props={props} context={builderContext} />
                    <Docs props={props} context={builderContext}  />
                </div>
            </WrapperWithLoader>
        </BuilderProvider>
    );
};

export default withDocumentTitle(
    Dashboard,
    __("Dashboard", "notificationx")
);
