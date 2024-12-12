// import React, { useEffect, useState } from "react";
// import classNames from "classnames";
// // import { Fields, TabMenuConfig } from "../../types/Tabs";
// import { isString, isObject, isVisible, Fields, TabMenuConfig } from "quickbuilder";
// import { __ } from '@wordpress/i18n';

// const Menu: React.FC<TabMenuConfig> = (props) => {
//     if (props.fields === undefined) {
//         throw new Error(__("There are no tabs defined!", 'notificationx'));
//     }

//     const { active, setActive, fields: tabs, context } = props;
// console.log(tabs)
//     const [tabsFields, setTabsFields] = useState<Fields>([]);

//     useEffect(() => {

//         const filteredTabs = tabs.filter(tab => isVisible(context?.values, tab));
//         setTabsFields(filteredTabs);

//     }, [tabs, context?.values?.source])

//     const componentClasses = classNames(
//         "wprf-tab-menu-wrapper",
//         props?.className,
//         { "wprf-tab-menu-sidebar": props?.sidebar },
//         context?.values?.source
//     );

//     const currentTabIndex = tabsFields.findIndex((tab: any) => tab.id === active);

//     return (
//         <div className={componentClasses}>
//             <ul className="wprf-tab-nav">
//                 {
//                     tabsFields.map((tab, index) => (
//                         <li
//                             className={classNames("wprf-tab-nav-item", {
//                                 [`${tab.classes}`]: tab.classes,
//                                 "wprf-active-nav": active === tab.id,
//                                 "wprf-tab-complete": props?.completionTrack ? (index <= currentTabIndex) : false
//                             })}
//                             data-key={tab.id}
//                             key={tab.id}
//                             onClick={() => (props?.clickable ?? true) && setActive(tab.id)}
//                         >
//                             {
//                                 tab?.icon && (
//                                     (isString(tab.icon) && !isObject(tab.icon)) ? <img src={tab.icon} alt={tab?.label} /> : (isObject(tab.icon) ? context?.icons?.[tab?.icon?.type]?.[tab?.icon?.name] : '')
//                                 )
//                             }
//                             <span>{tab.label}</span>
//                         </li>
//                     ))
//                 }
//             </ul>
//         </div >
//     );
// };

// export default Menu;