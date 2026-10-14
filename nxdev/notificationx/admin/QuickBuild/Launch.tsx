import React, { useState } from "react";
import { useBuilderContext } from "quickbuilder";
import { Button } from "@wordpress/components";
import nxHelper from "../../core/functions";
import { __ } from "@wordpress/i18n";
import { useNotificationXContext } from "../../hooks";
import nxToast from "../../core/ToasterMsg";

const Launch = (props) => {
    const builderContext = useBuilderContext();
    const notificationxContext = useNotificationXContext();
    const [isSubmit, setIsSubmit] = useState(false);
    const { title } = builderContext;

    const handleSubmit = (event) => {
        builderContext.setSubmitting(true);
        setIsSubmit(true);
        nxHelper
            .post('nx', {
                ...builderContext.values,
                title,
                currentTab: 'source_tab',
            }, { get_error: true })
            .then((res: any) => {
                if (res?.nx_id) {
                    notificationxContext.setRedirect({
                        page: `nx-admin`,
                    });
                } else {
                    builderContext.setSubmitting(false);
                    setIsSubmit(false);
                    if (res?.code && res?.message) {
                        // WP_Error from the REST API, e.g. nx_type_needs_pro_update.
                        nxToast.error(res.message);
                    }
                }
            })
            .catch((err) => {
                console.error(__("QuickBuilder Error: ", 'notificationx'), err);
                builderContext.setSubmitting(false);
                setIsSubmit(false);
            });
    };

    return (
        <>
            <Button
                disabled={isSubmit}
                className="wprf-btn wprf-step-btn-publish"
                onClick={handleSubmit}
            >
                {isSubmit && __('Publishing...', 'notificationx')}
                {!isSubmit && __('Publish', 'notificationx')}
            </Button>
        </>
    );
}

export default Launch;
