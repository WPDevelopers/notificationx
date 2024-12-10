import React, { useEffect, useState } from 'react'
// import Menu from './tabs/Menu'
// import Content from './tabs/Content'
// import { TabConfig } from '../types/Tabs';
// import { BuilderProvider } from '../core/hooks/useBuilderContext';
// import useBuilder from '../core/hooks/useBuilder';
// import '../scss/index.scss';
import { useBuilderContext, TabConfig } from 'quickbuilder';
import classNames from 'classnames';
import Menu from './helpers/Menu';
import Content from './helpers/Content';


const BetterTab: React.FC<TabConfig> = (props) => {
    // const builderContextState = useBuilder(props);
    const builderContext = useBuilderContext();
    const [activeTab, setActiveTab] = useState(props.value || props.active);

	const componentClasses = classNames(
		"wp-react-form wprf-tabs-wrapper",
		props?.className,
		{
			"wprf-tab-menu-as-sidebar": props?.sidebar,
		}
	);

    console.log(props.value, props);

    useEffect(() => {
        if(props.value !== activeTab){
            setActiveTab(props.value);
        }
    }, [props.value])

    useEffect(() => {
        if(props.value !== activeTab){
            props.onChange({
                target: {
                    type: 'button',
                    name: props.name,
                    value: activeTab
                }
            });
        }
    }, [activeTab])

    return (
        <div className={componentClasses}>
            {/* <BuilderProvider value={builderContextState}> */}
            <Menu
                {...props}
                active={activeTab}
                setActive={(tabId) => setActiveTab(tabId)}
                fields={props.fields}
                context={builderContext}
            />

            {/*<Content
                {...props}
                fields={props.fields}
                active={activeTab}
                setActive={(tabId) => setActiveTab(tabId)}
                submit={props?.submit}
            /> */}
            {/* </BuilderProvider> */}
        </div>
    )
}

export default BetterTab;