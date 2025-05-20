import React, { useEffect, useState } from "react";
import withDocumentTitle from "../../core/withDocumentTitle";
import GetStarted from "./GetStarted";
import AnalyticsOverview from "./AnalyticsOverview";
import Integration from "./Integration";
import { useNotificationXContext } from "../../hooks";
import NotificationTypeResource from "./NotificationTypeResource";
import Docs from "./Docs";
import AnalyticsDashboard from "./Analytics";
import HelpReviewSection from "./HelpReviewSection";
import { BuilderProvider, useBuilder } from "quickbuilder";
import { WrapperWithLoader } from "../../components";
// @ts-ignore
import { __ } from "@wordpress/i18n";

const Dashboard = (props) => {
    const builderContext = useNotificationXContext();
    const builder = useBuilder(notificationxTabs.quick_build);
    const [isLoading, setIsLoading] = useState(true);
    const [title, setTitle] = useState("");
    useEffect(() => {
        setIsLoading(false);
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
                    <HelpReviewSection props={props} context={builderContext} />
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
    __("NotificationX", "notificationx")
);
