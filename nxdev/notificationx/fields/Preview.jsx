import React from 'react'
import { useBuilderContext } from 'quickbuilder';
import PressbarAdminPreview from './helpers/PressbarAdminPreview';
import PopupAdminPreview from './helpers/PopupAdminPreview';

const Preview = () => {
    const builderContext = useBuilderContext();
    const nxBar = {
        config : builderContext?.values,
        data : "",
    }

    const nxPopup = {
        config : builderContext?.values,
        data : "",
    }

    // Check if this is a popup notification
    const isPopup = builderContext?.values?.type === 'popup_notification';

    return (
        <div>
            {isPopup ? (
                <PopupAdminPreview
                    key={`popup-${builderContext?.values?.nx_id}`}
                    nxPopup={nxPopup}
                    dispatch={"frontendContext.dispatch"}
                />
            ) : (
                <PressbarAdminPreview
                    key={`pressbar-${builderContext?.values?.nx_id}`}
                    position={'top'}
                    nxBar={nxBar}
                    dispatch={"frontendContext.dispatch"}
                />
            )}
        </div>
    )
}

export default Preview
