import React, { Fragment, useState } from 'react';
import ChevronRight from '../../../icons/ChevronRight';
import ChevronDown from '../../../icons/ChevronDown';
import { __ } from '@wordpress/i18n';

const AccordionItem = ({
  itemKey,
  title,
  description,
  isAlwaysActive,
  cookiesList,
  isCollapsed,
  toggleItem,
  isEnabled,
  toggleEnable,
  settings,
}) => (
  <div className="nx_gdpr-cookies-list-main-item">
    <div className="nx_gdpr-cookies-list-header">
      <div className="nx_gdpr-cookies-list-header-title" onClick={() => toggleItem(itemKey)}>
        { settings?.cookie_list_show_banner && (
          <Fragment>{isCollapsed ? <ChevronDown /> : <ChevronRight />}</Fragment>
        )}
        <h3>{title}</h3>
      </div>
      <div className="nx_gdpr-cookies-list-header-active">
        {isAlwaysActive ? (
          <span>{ settings.cookie_list_active_label ? settings.cookie_list_active_label : __('Always Active', 'notificationx') }</span>
        ) : (
          <label className="nx_gdpr-toggle">
            <input
              type="checkbox"
              checked={isEnabled}
              onChange={() => toggleEnable(itemKey)}
            />
            <span className="nx_gdpr-toggle-slider"></span>
          </label>
        )}
      </div>
    </div>
    <div className="nx_gdpr-cookies-list-content">
      <p>{description}</p>
    </div>
    {(isCollapsed && settings?.cookie_list_show_banner ) && (
      <div className="nx_gdpr-cookies-list-wrapper">
        {cookiesList?.map((cookie, index) => (
          <div className="nx_gdpr-cookies-list-wrapper-item" key={index}>
            <div className="nx_gdpr-cookies-list-wrapper-item-value">
              <span>{__('Cookie', 'notificationx')}</span>
              <p>{cookie?.cookies_id}</p>
            </div>
            <div className="nx_gdpr-cookies-list-wrapper-item-value">
              <span>{__('Description', 'notificationx')}</span>
              <p>{cookie?.description}</p>
            </div>
          </div>
        ))}
        { cookiesList?.length < 1 ? (<span>{ settings?.cookie_list_no_cookies_label }</span>) : '' }
      </div>
    )}
  </div>
);

const CookiesAccordion = ({ settings, onEnableCookiesItem }) => {
  const [collapsedItems, setCollapsedItems] = useState([]);
  const [enabledItems, setEnabledItems] = useState({
    necessary    : true,
    functional   : false,
    analytics    : false,
    performance  : false,
    uncategorized: false,
  });

  const toggleItem = (itemKey) => {
    setCollapsedItems((prev) =>
      prev.includes(itemKey) ? prev.filter((key) => key !== itemKey) : [...prev, itemKey]
    );
  };

  const toggleEnable = (itemKey) => {
    const updatedEnabledItems = {
      ...enabledItems,
      [itemKey]: !enabledItems[itemKey],
    };
    setEnabledItems(updatedEnabledItems);
    onEnableCookiesItem(updatedEnabledItems); // Pass the updated state to the parent component
  };

  const accordionItems = [
    {
      key: 'necessary',
      title: __('Necessary', 'notificationx'),
      description: __(
        'Necessary cookies are required to enable the basic features of this site, such as providing secure log-in or adjusting your consent preferences. These cookies do not store any personally identifiable data.',
        'notificationx'
      ),
      isAlwaysActive: true,
      cookiesList: settings?.necessary_cookie_lists,
    },
    {
      key: 'functional',
      title: __('Functional', 'notificationx'),
      description: __(
        'Functional cookies help perform certain functionalities like sharing the content of the website on social media platforms, collecting feedback, and other third-party features.',
        'notificationx'
      ),
      isAlwaysActive: false,
      cookiesList: settings?.functional_cookie_lists,
    },
    {
      key: 'analytics',
      title: __('Analytics', 'notificationx'),
      description: __(
        'Analytics cookies help analyze website usage to improve user experience.',
        'notificationx'
      ),
      isAlwaysActive: false,
      cookiesList: settings?.analytics_cookie_lists,
    },
    {
      key: 'performance',
      title: __('Performance', 'notificationx'),
      description: __(
        'Performance cookies help analyze website usage to improve user experience.',
        'notificationx'
      ),
      isAlwaysActive: false,
      cookiesList: settings?.performance_cookie_lists,
    },
    {
      key: 'uncategorized',
      title: __('Uncategorized', 'notificationx'),
      description: __(
        'Uncategorized cookies help analyze website usage to improve user experience.',
        'notificationx'
      ),
      isAlwaysActive: false,
      cookiesList: settings?.uncategorized_cookie_lists,
    },
  ];

  return (
    <div className="nx_gdpr-cookies-list-main-wrapper">
      {accordionItems.map((item) => (
        <AccordionItem
          key={item.key}
          itemKey={item.key}
          title={item.title}
          description={item.description}
          isAlwaysActive={item.isAlwaysActive}
          cookiesList={item.cookiesList}
          isCollapsed={collapsedItems.includes(item.key)}
          toggleItem={toggleItem}
          isEnabled={enabledItems[item.key]}
          toggleEnable={toggleEnable}
          settings={settings}
        />
      ))}
    </div>
  );
};

export default CookiesAccordion;
