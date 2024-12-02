import React from 'react'
import NXBranding from '../../themes/helpers/NXBranding'

const GdprFooter = ({ settings }) => {
    
  return (
    <div className={`nx-gdpr-card-footer ${settings?.gdpr_theme}`}>
        <p className="nx-gdpr-powered">Powered by</p>
        <NXBranding/>
    </div>
  )
}

export default GdprFooter