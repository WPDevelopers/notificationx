import React from 'react'
import { useBuilderContext } from 'quickbuilder';
import PressbarAdminPreview from './helpers/PressbarAdminPreview';
const Preview = () => {
    const builderContext = useBuilderContext();
    const nxBar = {
        config : builderContext?.values,
        data : "",
    }    
    return (
        <div>
            <PressbarAdminPreview
                key={`pressbar-${builderContext?.values?.nx_id}`}
                position={'top'}
                nxBar={nxBar}
                dispatch={"frontendContext.dispatch"} 
            />
        </div>
    )
}

export default Preview
