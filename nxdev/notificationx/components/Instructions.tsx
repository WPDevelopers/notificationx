import React, { useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n';

const Instructions = (props) => {
    const [instruction, setInstruction] = useState();

    useEffect(() => {
        let instruction = props.instructions?.[props.values?.type]?.[props.values?.source];
        setInstruction(instruction);
    }, [props.values?.source, props.values?.type])

    return (
        <div className="notificationx-instruction sidebar-widget nx-widget">
            <div className="nx-widget-title"> <h4>{__('NotificationX Instructions', 'notificationx')}</h4></div>
            <div className="nx-widget-content">
                <div className={`nxins-type ${props?.values?.type}`}>
                    <div
                        className={`nxins-type-${props?.values?.type} ${props?.values?.source}`}
                        dangerouslySetInnerHTML={{ __html: instruction }}>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default Instructions;
