import React, { useState } from "react";
import { useBuilderContext } from "../../../form-builder";
import { Button } from "@wordpress/components";
import nxHelper from "../../core/functions";
import { __ } from "@wordpress/i18n";
import { useNotificationXContext } from "../../hooks";

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
            })
            .then((res: any) => {
                if (res?.nx_id) {
                    notificationxContext.setRedirect({
                        page  : `nx-admin`,
                    });
                }
            })
            .catch((err) => {
                console.error(__("QuickBuilder Error: ", 'notificationx'), err);
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
