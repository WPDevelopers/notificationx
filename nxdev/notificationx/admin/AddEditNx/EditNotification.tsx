import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react'
import { Redirect } from 'react-router-dom';
import { EditNx } from '.';
import { BuilderProvider, useBuilder } from 'quickbuilder';
import { Header, WrapperWithLoader } from '../../components';
import nxHelper from '../../core/functions';
import withDocumentTitle from '../../core/withDocumentTitle';
import { useNotificationXContext } from '../../hooks';
import Notice from './Notice';

const EditNotification = (props) => {
    const builderTabs = { ...notificationxTabs };
    delete builderTabs.settings;
    const builder = useBuilder(builderTabs);
    const notificationxContext = useNotificationXContext();

    const [title, setTitle] = useState<string>(undefined)
    const [isUpdated, setIsUpdated] = useState('')
    const [isLoading, setIsLoading] = useState(true);

    const isEdit = !!props?.match?.params?.edit;
    const ID = isEdit ? parseInt(props?.match?.params?.edit) : null;


    useEffect(() => {
        if (ID === null) {
            notificationxContext.setRedirect({
                page  : `nx-edit`,
            });
            return;
        }

        if (props?.location?.state?.published) {
            setIsUpdated('published');
        }

        nxHelper.get(`nx/${ID}`).then((res: any) => {
            if (res) {
                builder.setValues(res);
                builder.setSavedValues(res);
                let currentTab = res?.currentTab;
                if (currentTab == 'finalize_tab') {
                    currentTab = 'source_tab';
                }
                builder.setActiveTab(currentTab || 'source_tab');
                setIsLoading(false);
            } else {
                // If response is not valid than redirect to all.
                notificationxContext.setRedirect({
                    page  : `nx-admin`,
                });
            }
        })
    }, [])

    useEffect(() => {
        if (isUpdated) {
            setTimeout(() => {
                setIsUpdated('');
            }, 5000)
        }
    }, [isUpdated])

    return (
        <BuilderProvider value={builder}>
            <div>
                <Header addNew={true} />
                {isUpdated === 'saved' && !isLoading && <Notice message={__("Successfully Updated.", 'notificationx')} />}
                {isUpdated === 'published' && !isLoading && <Notice message={__("Successfully Created.", 'notificationx')} />}
                <WrapperWithLoader isLoading={isLoading}>
                    <EditNx id={ID} setIsUpdated={setIsUpdated} setIsLoading={setIsLoading} title={title} setTitle={setTitle} />
                </WrapperWithLoader>
            </div>
        </BuilderProvider>
    )
}

export default withDocumentTitle(EditNotification, __("Edit", 'notificationx'));