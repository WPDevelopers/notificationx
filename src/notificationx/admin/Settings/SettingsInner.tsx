import React, { useCallback, useEffect, useState } from 'react'
import { Redirect, useLocation } from 'react-router-dom';
import FormBuilder from '../../../form-builder';
import { useBuilderContext } from '../../../form-builder/src/core/hooks';
import { Header } from '../../components'
import nxHelper, { proAlert } from '../../core/functions';
import { AnalyticsHeader } from '../Analytics';
import { Documentation } from '.';
import Swal from 'sweetalert2';
import { InfoIcon } from '../../icons';
import { useNotificationXContext } from '../../hooks';

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
                    Swal.fire({
                        text: 'Changes Saved!',
                        title: 'Successful',
                        icon: 'success',
                        timer: 2000,
                    });
                }
                else {
                    throw new Error("Something went wrong.");
                }
            }).catch(err => {
                Swal.fire({
                    text: 'Something went wrong.',
                    title: '!!!',
                    icon: 'error',
                    timer: 2000,
                });
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