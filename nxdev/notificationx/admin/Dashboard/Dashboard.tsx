import React, { useEffect, useState } from "react";
import withDocumentTitle from "../../core/withDocumentTitle";
import GetStarted from "./GetStarted";
import AnalyticsOverview from "./AnalyticsOverview";
import Notice from "./Notice";
import Integration from "./Integration";
import { useNotificationXContext } from "../../hooks";
import NotificationTypeResource from "./NotificationTypeResource";
import Docs from "./Docs";
import AnalyticsDashboard from "./Analytics";
import { BuilderProvider, useBuilder } from "quickbuilder";
import { WrapperWithLoader } from "../../components";
import DashboardHeader from "./DashboardHeader";
// @ts-ignore
import { __ } from "@wordpress/i18n";
import InitialPopup from "./InitialPopup";

export type NotificationTotals = {
    all: number;
    enabled: number;
    disabled: number;
};

const Dashboard = (props) => {
    const builderContext = useNotificationXContext();
    const builder = useBuilder(notificationxTabs.quick_build);
    const [isLoading, setIsLoading] = useState(true);
    const [title, setTitle] = useState("");
    const [showInitialPopup, setShowInitialPopup] = useState(true);
    const [totals, setTotals] = useState<NotificationTotals>({ all: 0, enabled: 0, disabled: 0 });

    useEffect(() => {
        setIsLoading(false);
        const shouldShowPopup = builderContext?.show_initial_popup && !builderContext?.is_pro_active ? true: false;
        setShowInitialPopup(shouldShowPopup);
    }, [builderContext]);

    const handleDismissPopup = () => {
        setShowInitialPopup(false);
    };

    return (
        <BuilderProvider
            value={{ ...builder, isLoading, setIsLoading, title, setTitle }}
        >
            <WrapperWithLoader isLoading={isLoading}>
                <div className="nx-dash">
                    <DashboardHeader context={builderContext} />
                    <main className="nx-dash__body">
                        <Notice props={props} context={builderContext} />
                        <GetStarted props={props} context={builderContext} />
                        <AnalyticsOverview props={props} context={builderContext} totals={totals} />
                        <Integration props={props} context={builderContext} onTotalsChange={setTotals} />
                        <AnalyticsDashboard props={props} context={builderContext} />
                        <NotificationTypeResource props={props} context={builderContext} />
                        <Docs props={props} context={builderContext} />
                    </main>
                    {showInitialPopup && <InitialPopup onDismiss={handleDismissPopup} />}
                </div>
            </WrapperWithLoader>
        </BuilderProvider>
    );
};

export default withDocumentTitle(
    Dashboard,
    __("NotificationX", "notificationx")
);
