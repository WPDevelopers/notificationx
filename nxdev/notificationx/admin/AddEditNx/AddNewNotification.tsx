import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react'
import { CreateNx } from '.';
import { BuilderProvider, useBuilder } from 'quickbuilder';
import { isArray } from 'quickbuilder';
import { Header, WrapperWithLoader } from '../../components';
import nxHelper from '../../core/functions';
import withDocumentTitle from '../../core/withDocumentTitle';
import { useNotificationXContext } from '../../hooks';

const AddNewNotification = (props) => {
    const builderTabs = { ...notificationxTabs };
    delete builderTabs.settings;
    const builder = useBuilder(builderTabs);
    const [title, setTitle] = useState('')
    const [isLoading, setIsLoading] = useState(false);
    const notificationxContext = useNotificationXContext();

    useEffect(() => {
        if(builder?.createRedirect){
            // user don't have permission.
            notificationxContext.setRedirect({
                page  : `nx-admin`,
            });
            return;
        }

        if (notificationxContext.getOptions('refresh')) {
            nxHelper.get('builder').then((res: any) => {
                if (isArray(res?.tabs) && res?.tabs.length > 0) {
                    builder.setFormField(null, res?.tabs);
                }
            });
        }


        if (props?.location?.state?.duplicate) {
            const ID = parseInt(props?.location?.state?._id);
            setIsLoading(true);
            nxHelper.get(`nx/${ID}`).then((res: any) => {
                if (res) {
                    // res.id = null;
                    delete res.id;
                    delete res.nx_id;
                    res.nx_id = null;
                    res.enabled = notificationxContext?.is_pro_active ? true : false;
                    builder.setValues(res);
                    builder.setSavedValues(res);
                    builder.setActiveTab(res?.currentTab);
                    // translators: Postfix for notice created by duplicate button.
                    setTitle(res?.title + __(" - Copy", 'notificationx'));
                    setIsLoading(false);
                }
            })
        }
        
    }, []);

    useEffect(() => {
      if( notificationxContext?.state?.redirect?.state?.type ) {
        builder.setValues( { type: notificationxContext?.state?.redirect?.state?.type, source : notificationxContext?.state?.redirect?.state?.source } );
      }
    }, [])
        

    return (
        <BuilderProvider value={builder}>
            <div>
                <Header addNew={true} />
                <WrapperWithLoader isLoading={isLoading}>
                    <CreateNx setIsLoading={setIsLoading} title={title} setTitle={setTitle} />
                </WrapperWithLoader>
            </div>
        </BuilderProvider>
    )
}

export default withDocumentTitle(AddNewNotification, __('Add New', 'notificationx'));
