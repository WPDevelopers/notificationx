import React, { Fragment } from 'react'
import CookiesAccordion from './utils/CookiesAccordion'

const Customization = ({ settings }) => {
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
        <div className="wprf-modal-table-wrapper wprf-gdpr-modal-content">
            <CookiesAccordion/>
        </div>
        <div className="wprf-modal-preview-footer">
            Click Me
        </div>
    </Fragment>
  )
}

export default Customization