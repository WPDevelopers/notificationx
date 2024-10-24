import React, {useEffect, useState} from 'react'
import Loader from './Loader';
import { useBuilderContext } from 'quickbuilder';
const WrapperWithLoader: React.FC<{ isLoading?: boolean, classes?: string, div?: boolean }> = ({ children, isLoading = true, classes = "nx-admin-wrapper", div = true }) => {
    const [ selectedType, setSelectedType ] = useState( false );
    const [contentHeight, setContentHeight] = useState(0);

    if (div) {
        const builderContext = useBuilderContext();
        useEffect(() => {
            if( builderContext.values.type !== undefined ) {
                setSelectedType( true );
                if( selectedType ) {
                    setContentHeight(document.documentElement.scrollHeight);
                }
            }
            builderContext.setFieldValue( "themes_tab", 'for_desktop' );
        }, [builderContext.values.type])

        useEffect(() => {
            setTimeout(() => {
                window.scrollTo({
                    top: contentHeight,
                    behavior: 'smooth',
                });
            }, 300);
        }, [contentHeight]);

        return (
            <div className={classes}>
                {isLoading && <Loader />}
                {!isLoading && children}
            </div>
        )
    }

    return (
        <>
            {isLoading && <Loader />}
            {!isLoading && children}
        </>
    )
}

export default WrapperWithLoader;