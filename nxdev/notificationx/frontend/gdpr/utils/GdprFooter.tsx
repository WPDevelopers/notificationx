import React from 'react'
import NXBranding from '../../themes/helpers/NXBranding'
import { __ } from '@wordpress/i18n'

const GdprFooter = ({ settings }) => {

  let footerBGColor = {};
  if ( settings?.advance_edit ) {
    footerBGColor = {
        backgroundColor: settings?.gdpr_design_ft_bg_color,
    };
  }
  return (
    !settings?.disable_powered_by && (
    <div className={`nx-gdpr-card-footer ${settings?.gdpr_theme}`} style={footerBGColor}>
      <p className="nx-gdpr-powered">{ __('Powered by','notificationx') }</p>
      <NXBranding/>
    </div>
    )
  )
}

export default GdprFooter