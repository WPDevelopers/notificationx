import React, { Fragment, useState } from 'react'
import CookiesAccordion from './utils/CookiesAccordion'
import NXBranding from '../themes/helpers/NXBranding'

const Customization = ({ settings, onEnableCookiesItem }) => {
    
  return (
    <Fragment>
        <div className="wprf-modal-table-wrapper nx-gdpr-modal-header">
            {settings?.preference_title &&
                <h3>{settings?.preference_title}</h3>
            }
            {settings?.preference_overview &&
                <p>{settings?.preference_overview}</p>
            }
        </div>
        <div className="wprf-modal-table-wrapper wprf-gdpr-modal-frontend-content">
            <CookiesAccordion settings={settings} onEnableCookiesItem={onEnableCookiesItem} />
        </div>
        <div className="wprf-modal-preview-footer">
            <div className="nx_gdpr-action-button">
                <button>Accept All</button>
                <button>Customize</button>
            </div>
            <div className="wprf-modal-preview-copyright">
                <NXBranding/>
            </div>
        </div>
    </Fragment>
  )
}

export default Customization