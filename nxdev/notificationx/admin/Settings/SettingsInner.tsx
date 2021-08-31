import React, { useCallback, useEffect, useState } from 'react'
import { Redirect, useLocation } from 'react-router-dom';
import { useBuilderContext, FormBuilder } from 'quickbuilder';
import { Header } from '../../components'
import nxHelper, { proAlert } from '../../core/functions';
import { AnalyticsHeader } from '../Analytics';
import { Documentation } from '.';
import { InfoIcon } from '../../icons';
import { useNotificationXContext } from '../../hooks';
import nxToast, { ToastAlert} from "../../core/ToasterMsg";

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
                    nxToast.info( `Changes Saved Successfully.` );
                }
                else {
                    throw new Error("Something went wrong.");
                }
            }).catch(err => {
                nxToast.error( `Oops, Something went wrong. Please try again.` );
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
        builder.registerAlert('toast', ToastAlert);
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