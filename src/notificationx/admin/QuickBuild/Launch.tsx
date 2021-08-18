import React, { useState } from "react";
import { useBuilderContext } from "quickbuilder";
import { Redirect } from "react-router-dom";
import { Button } from "@wordpress/components";
import nxHelper from "../../core/functions";
import { __ } from "@wordpress/i18n";

const Launch = (props) => {
    const builderContext = useBuilderContext();
    const [isSubmitt, setIsSubmitt] = useState(false);
    const { title } = builderContext;
    const [isCreated, setIsCreated] = useState(false);

    const handleSubmit = (event) => {
        builderContext.setSubmitting(true);
        setIsSubmitt(true);
        nxHelper
            .post('nx', {
                ...builderContext.values,
                title,
                currentTab: 'source_tab',
            })
            .then((res: any) => {
                if (res?.nx_id) {
                    setIsCreated(true);
                }
            })
            .catch((err) => {
                console.error("QuickBuilder Error: ", err);
                setIsSubmitt(false);
            });
    };

    return (
        <>
            {isCreated && <Redirect push to="/" />}
            <Button
                disabled={isSubmitt}
                className="wprf-btn wprf-step-btn-publish"
                onClick={handleSubmit}
            >
                {isSubmitt && __('Publishing...', 'notificationx')}
                {!isSubmitt && __('Publish', 'notificationx')}
            </Button>
        </>
    );
}

export default Launch;
