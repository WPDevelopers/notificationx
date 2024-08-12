import React, { useCallback, useEffect, useState } from 'react'
import { useHistory } from 'react-router-dom';
import { useBuilderContext, FormBuilder } from 'quickbuilder';
import { Header } from '../../components'
import nxHelper, { proAlert } from '../../core/functions';
import { AnalyticsHeader } from '../Analytics';
import { Documentation } from '.';
import { InfoIcon } from '../../icons';
import { useNotificationXContext } from '../../hooks';
import nxToast, { ToastAlert } from "../../core/ToasterMsg";
import { __ } from '@wordpress/i18n';


const SettingsInner = (props) => {
    const builder = useBuilderContext();
    const notificationxContext = useNotificationXContext();
    const [Location, setLocation] = useState(location.search);
    let history = useHistory();
    history.listen(() => {
        setLocation(location.search);
    });

    useEffect(() => {
        const qTab = nxHelper.getParam('tab');
        if (qTab) {
            builder.setActiveTab(qTab);
        }
        props?.setIsLoading(false);
    }, [Location])

    useEffect(() => {
        const tab = builder.config.active;
        notificationxContext.setRedirect({
            page: `nx-settings`,
            tab: tab,
            keepHash: true,
        });
        if (location.hash) {
            setTimeout(() => {
                var element = document.getElementById(location.hash.replace('#', ''));
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth' });
                }
            }, 1000);
        }
    }, [builder.config.active]);

    useEffect(() => {
        notificationxContext.setOptions('refresh', true);
        if (builder?.settingsRedirect) {
            // user don't have permission.
            notificationxContext.setRedirect({
                page: `nx-admin`,
            });
        }
    }, [])


    builder.submit.onSubmit = useCallback(
        (event, context) => {
            context.setSubmitting(true);
            nxHelper.post('settings', { ...context.values, xss_code: null }).then((res: any) => {
                if (res?.success) {
                    nxToast.info(__(`Changes Saved Successfully.`, 'notificationx'));
                }
                else {
                    throw new Error(__("Something went wrong.", 'notificationx'));
                }
            }).catch(err => {
                nxToast.error(__(`Oops, Something went wrong. Please try again.`, 'notificationx'));
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
        builder.registerAlert('pro_alert', proAlert);
        builder.registerAlert('toast', ToastAlert);
    }, []);

    return (
        <div>
            <Header addNew={true} />
            {builder?.analytics && <AnalyticsHeader assetsURL={builder.assets} />}
            <div className="nx-settings">
                <div className="nx-settings-content">
                    <div className="nx-settings-form-wrapper">
                        <FormBuilder {...builder} useQuery={true} />
                    </div>
                </div>
                <Documentation assetsUrl={builder.assets} />
            </div>
        </div>
    )
}
export default SettingsInner;