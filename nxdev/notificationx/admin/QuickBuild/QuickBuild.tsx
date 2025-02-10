import React, { useEffect } from 'react'
import { FormBuilder, useBuilderContext } from 'quickbuilder';
import { Content } from '../../components';
import { permissionAlert, proAlert } from '../../core/functions';
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
        builderContext.registerAlert('pro_alert', proAlert);
        builderContext.registerAlert('has_permission_alert', permissionAlert);
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