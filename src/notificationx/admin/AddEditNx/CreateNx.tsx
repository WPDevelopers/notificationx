import React, { useCallback, useEffect, useState } from 'react'
import FormBuilder, { useBuilderContext } from '../../../form-builder';
import { Content, PublishWidget, Sidebar, Instructions } from '../../components';
import { proAlert } from '../../core/functions';
import { SourceIcon, DesignIcon, ContentIcon, DisplayIcon, CustomizeIcon } from '../../icons'

const CreateNx = ({ setIsCreated, setIsLoading, title, setTitle }) => {
    const builderContext = useBuilderContext();

    useEffect(() => {
        let iconLists = {};
        iconLists['source'] = <SourceIcon />
        iconLists['design'] = <DesignIcon />
        iconLists['content'] = <ContentIcon />
        iconLists['display'] = <DisplayIcon />
        iconLists['customize'] = <CustomizeIcon />
        builderContext.registerIcons('tabs', iconLists);

        builderContext.registerAlert('pro_alert', proAlert());
    }, [])


    return (
        <>
            <Content>
                <input
                    className="widefat nx-title"
                    type="text"
                    name="title"
                    id="nx-title"
                    placeholder="NotificationX Title"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                />
                <FormBuilder {...builderContext} />
            </Content>
            <Sidebar>
                <PublishWidget
                    title={title}
                    isEdit={false}
                    setIsCreated={setIsCreated}
                    setIsLoading={setIsLoading}
                    context={builderContext}
                />
                <Instructions  {...builderContext} />
            </Sidebar>
        </>
    )
}

export default CreateNx;