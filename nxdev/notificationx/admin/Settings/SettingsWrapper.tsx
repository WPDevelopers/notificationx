import React from 'react'
import { Redirect, useLocation, useParams } from 'react-router-dom';
import { BuilderProvider, useBuilder } from '../../../form-builder/src/core/hooks';
import withDocumentTitle from '../../core/withDocumentTitle';
import { useNotificationXContext } from '../../hooks';
import SettingsInner from './SettingsInner';

const SettingsWrapper = (props) => {
    const builder = useBuilder(notificationxTabs.settings);

    const location = useLocation();

    console.log(location);
    let params = useParams();
    console.log(params);
    return (
        <BuilderProvider value={builder}>
            {/* <Redirect to="/" /> */}
            <SettingsInner />
        </BuilderProvider>
    )
}
export default withDocumentTitle(SettingsWrapper, "Settings");