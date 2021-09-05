import React, { useCallback, useEffect, useState } from 'react'
import { Redirect, useHistory, useLocation } from 'react-router-dom';
import FormBuilder from '../../../form-builder';
import { useBuilderContext } from '../../../form-builder/src/core/hooks';
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
    const [redirect, setRedirect] = useState<string|object>();
    const [Location, setLocation] = useState(location.search);
    let history = useHistory();
    history.listen(() => {
        setLocation(location.search);
    });
    const getParam = (param, d?) => {
        const query = nxHelper.useQuery(location.search);
        return query.get(param) || d;
    };

    useEffect(() => {
        const qTab = getParam('tab');
        if(qTab){
            builder.setActiveTab(qTab);
        }
    }, [Location])

    useEffect(() => {
        const tab = builder.config.active;
        setRedirect({
            pathname: '/admin.php',
            search  : `?page=nx-settings&tab=${tab}`,
        });
    }, [builder.config.active]);

    useEffect(() => {
        notificationxContext.setOptions('refresh', true);
        if(builder?.settingsRedirect){
            // user don't have permission.
            setRedirect("/");
        }
    }, [])


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
            {redirect && <Redirect to={redirect} />}
            <Header addNew={true} />
            {builder?.analytics && <AnalyticsHeader assetsURL={builder.assets} analytics={...builder?.analytics} />}
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