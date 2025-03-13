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
            <h4>{__('Want to explore more?', 'notificationx')}</h4>
            <p>{ __('Dive in and discover all the premium features','notificationx') } </p>
            <a target='_blank' href="https://notificationx.com/#pricing">{__('Upgrade To PRO', 'notificationx')}</a>
        </div>
    </div>
  )
}

export default ProWidget