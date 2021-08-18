import React, { useEffect, useState } from 'react'
import { FormBuilder, useBuilderContext } from 'quickbuilder';
import { Content } from '../../components';
import { proAlert } from '../../core/functions';
import { Redirect } from "react-router";

const QuickBuild = (props) => {
    const builderContext = useBuilderContext();
    const [redirect, setRedirect] = useState(builderContext?.redirect);

    useEffect(() => {
        builderContext.registerAlert('pro_alert', proAlert());
    }, [])

    return (
        <div className="nx-quick-builder-wrapper">
            {redirect && <Redirect to="/" />}
            <Content>
                <FormBuilder {...builderContext} />
            </Content>
        </div>
    )
}

export default QuickBuild;