import { __ } from '@wordpress/i18n';
import React, { useState } from 'react'
import { BuilderProvider, useBuilder } from 'quickbuilder';
import withDocumentTitle from '../../core/withDocumentTitle';
import SettingsInner from './SettingsInner';
import { WrapperWithLoader } from '../../components';

const SettingsWrapper = (props) => {
    const builder = useBuilder(notificationxTabs.settings);
    const [isLoading, setIsLoading] = useState(false);

    return (
        <BuilderProvider value={builder}>
            <div>
                <WrapperWithLoader isLoading={isLoading}>
                    <SettingsInner setIsLoading={setIsLoading} props={props} />
                </WrapperWithLoader>
            </div>
        </BuilderProvider>
    )
}
export default withDocumentTitle(SettingsWrapper, __("Settings", 'notificationx'));