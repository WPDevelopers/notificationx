import React, { useEffect, useState } from 'react'
import FormBuilder, { useBuilderContext } from '../../../form-builder';
import { Content } from '../../components';
import { proAlert } from '../../core/functions';
import { useNotificationXContext } from '../../hooks';

const QuickBuild = (props) => {
    const builderContext = useBuilderContext();
    const notificationxContext = useNotificationXContext();

    useEffect(() => {
        if(builderContext?.redirect){
            // user don't have permission.
            notificationxContext.setRedirect({
                page  : `nx-admin`,
            });
            return;
        }
        builderContext.registerAlert('pro_alert', proAlert());
    }, [])

    return (
        <div className="nx-quick-builder-wrapper">
            <Content>
                <FormBuilder {...builderContext} />
            </Content>
        </div>
    )
}

export default QuickBuild;