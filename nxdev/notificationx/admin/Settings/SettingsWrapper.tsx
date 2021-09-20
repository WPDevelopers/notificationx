import { __ } from '@wordpress/i18n';
import React from 'react'
import { BuilderProvider, useBuilder } from '../../../form-builder/src/core/hooks';
import withDocumentTitle from '../../core/withDocumentTitle';
import SettingsInner from './SettingsInner';

const SettingsWrapper = (props) => {
    const builder = useBuilder(notificationxTabs.settings);

    return (
        <BuilderProvider value={builder}>
            <SettingsInner props={props} />
        </BuilderProvider>
    )
}
export default withDocumentTitle(SettingsWrapper, __("Settings", 'notificationx'));