import React, { useEffect, useState } from "react";
import { BuilderProvider, useBuilder } from "quickbuilder";
import { Header, WrapperWithLoader } from "../../components";
import QuickBuild from "./QuickBuild";
// @ts-ignore
import { __experimentalGetSettings, date } from "@wordpress/date";
import moment from "moment";
import withDocumentTitle from "../../core/withDocumentTitle";
import { sprintf, __ } from "@wordpress/i18n";

const QuickBuildWrapper = (props) => {
    const builder = useBuilder(notificationxTabs.quick_build);
    const [isLoading, setIsLoading] = useState(true);
    const [title, setTitle] = useState("");
    const settings: any = __experimentalGetSettings();

    useEffect(() => {
        setIsLoading(false);
    }, []);

    useEffect(() => {
        const type = builder?.values?.type;
        const title = builder.types_title?.[type];
        const _value = moment.utc().utcOffset(+settings?.timezone?.offset); //
        const _date = date(settings.formats.date, _value, undefined);
        // translators: Title of the new Notification Alert. %1$s: title, %2$s: current date.
        setTitle(
            sprintf(
                __("NotificationX - %1$s - %2$s", "notificationx"),
                title,
                _date
            )
        );
    }, [builder?.values?.type]);

    return (
        <BuilderProvider
            value={{ ...builder, isLoading, setIsLoading, title, setTitle }}
        >
            <Header addNew={true} />
            <WrapperWithLoader isLoading={isLoading}>
                <QuickBuild isLoading={isLoading} />
            </WrapperWithLoader>
        </BuilderProvider>
    );
};
export default withDocumentTitle(
    QuickBuildWrapper,
    __("Quick Builder", "notificationx")
);
