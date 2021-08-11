import React, { useEffect } from 'react'
import { useNotificationXContext } from '../hooks';
import useDocumentTitle from './useDocumentTitle';

const withDocumentTitle = (WrappedComponent, title) => {
    const WithDocumentTitle = (props) => {
        const builderContext = useNotificationXContext();

        useEffect(() => {
            //FIXME: fix me please.
            useDocumentTitle({ title: title + builderContext.title });
        }, [])
        return <WrappedComponent {...props} />
    }
    return WithDocumentTitle;
}

export default withDocumentTitle;