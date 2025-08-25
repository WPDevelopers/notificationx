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
                const forcedDesktopValues = [ 'woocommerce_sales_inline', 'announcements', 'gdpr', 'flashing_tab', 'woo_inline', 'edd_inline', 'tutor_inline', 'learndash_inline', 'learnpress_inline', 'custom_notification'];
                const nx_type = builderContext.values.type;
                const builderValues = builderContext?.values;
                const isBuildWithBuilder =  (builderValues?.elementor_id && builderValues?.is_elementor) || (builderValues?.is_gutenberg && builderValues?.gutenberg_id);                
                const themeTabValue = isBuildWithBuilder
                    ? 'nxbar_custom'
                    : (forcedDesktopValues.includes(nx_type)
                        ? 'for_desktop'
                        : (builderContext?.values?.themes_tab || 'for_desktop'));

                builderContext.setFieldValue("themes_tab", themeTabValue);
            }
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