import React from 'react'
import NXBranding from '../../themes/helpers/NXBranding'
import { __ } from '@wordpress/i18n'

const GdprFooter = ({ settings }) => {
    
  return (
    <div className={`nx-gdpr-card-footer ${settings?.gdpr_theme}`}>
        <p className="nx-gdpr-powered">{ __('Powered by','notificationx') }</p>
        <NXBranding/>
    </div>
  )
}

export default GdprFooter