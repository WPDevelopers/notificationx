import { __ } from '@wordpress/i18n'
import React from 'react'

const ProWidget = () => {
  // @ts-ignore 
  const isProActive = notificationxTabs?.is_pro_active;    
  if( isProActive ) {
      return;
  }
  return (
    <div className='notificationx-pro-widget sidebar-widget nx-widget'>
        <div className="nx-widget-content">
            <h4>{__('Upgrade to Pro', 'notificationx')}</h4>
            <p>{ __('Lorem ipsum dolor sit amet consectetur. Vitae tellus pretium','notificationx') } </p>
            <a href="https://notificationx.com/#pricing">Go Premium</a>
        </div>
    </div>
  )
}

export default ProWidget