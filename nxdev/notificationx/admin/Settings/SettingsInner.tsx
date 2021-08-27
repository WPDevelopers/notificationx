import React, { useCallback, useEffect, useState } from 'react'
import { Redirect, useLocation } from 'react-router-dom';
import FormBuilder from '../../../form-builder';
import { useBuilderContext } from '../../../form-builder/src/core/hooks';
import { Header } from '../../components'
import nxHelper, { proAlert, toastAlert } from '../../core/functions';
import { AnalyticsHeader } from '../Analytics';
import { Documentation } from '.';
import { InfoIcon } from '../../icons';
import { useNotificationXContext } from '../../hooks';
import { toast } from "react-toastify";
import ConnectedToastIcon from "../../icons/ConnectedSuccessful";
import ErrorToastIcon from "../../icons/Error";
import { toastDefaultArgs } from '../../core/ToasterMsg';

const useQuery = () => new URLSearchParams(useLocation().search);

const SettingsInner = (props) => {
    const builder = useBuilderContext();
    const notificationxContext = useNotificationXContext();

    useEffect(() => {
        notificationxContext.setOptions('refresh', true);
    }, [])


    const [redirect, setRedirect] = useState(builder?.settingsRedirect);

    builder.submit.onSubmit = useCallback(
        (event, context) => {
        context.setSubmitting(true);
        nxHelper.post('settings', { ...context.values }).then((res: any) => {
                if (res?.success) {
                    const SuccessMsg = <div className="nx-toast-wrapper">
                        <ConnectedToastIcon />
                        <p>Changes Saved Successfully.</p>
                    </div>
                    toast.info( SuccessMsg, toastDefaultArgs );
                }
                else {
                    throw new Error("Something went wrong.");
                }
            }).catch(err => {
                const ErrorMsg = <div className="nx-toast-wrapper">
                    <ErrorToastIcon />
                    <p>Oops, Something went wrong. Please try again.</p>
                </div>
                toast.error( ErrorMsg, toastDefaultArgs );
            })
    },
    [],
    );

    useEffect(() => {
        // addFilter('notificationx_header', 'notificationx', (version) => {
        //     return <>
        //         {version}
        //         <span>Notification Pro: <strong>2.0.0</strong></span>
        //     </>
        // })
        builder.registerIcons('link', <InfoIcon />);
        builder.registerAlert('pro_alert', proAlert());
        builder.registerAlert('toast', toastAlert());
    }, []);

    return (
        <div>
            {redirect && <Redirect to="/" />}
            <Header addNew={true} />
            {builder?.analytics && <AnalyticsHeader assetsURL={builder.assets} analytics={...builder?.analytics} />}
            <div className="nx-settings">
                <div className="nx-settings-content">
                    <div className="nx-settings-form-wrapper">
                        <FormBuilder {...builder} />
                    </div>
                </div>
                <Documentation assetsUrl={builder.assets} />
            </div>
        </div>
    )
}
export default SettingsInner;