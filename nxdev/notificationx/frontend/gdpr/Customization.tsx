import React, { Fragment, useState } from 'react'
import CookiesAccordion from './utils/CookiesAccordion'
import NXBranding from '../themes/helpers/NXBranding'
import { __ } from '@wordpress/i18n'

const Customization = ({ settings, onEnableCookiesItem, onHandleAccept, onSaveConsent }) => {
    const [isExpanded, setIsExpanded] = useState(false);
    let showMoreText = settings?.preference_more_btn ? settings?.preference_more_btn : "Show more";
    let showLessText = settings?.preference_less_btn ? settings?.preference_less_btn : "Show ess";
    const toggleText = () => {
        setIsExpanded(!isExpanded);
    };
  return (
    <Fragment>
        <div className="wprf-modal-table-wrapper nx-gdpr-modal-header">
            {settings?.preference_title && <h3>{settings?.preference_title}</h3> }
            {settings?.preference_overview && settings?.preference_overview?.length > 300 ? (
                <>
                    <p>
                        {isExpanded ? settings?.preference_overview : `${settings?.preference_overview.substring(0, 200)}...`}
                        {!isExpanded && (
                            <button onClick={toggleText} className="show-more-btn">
                                {showMoreText}
                            </button>
                        )}
                    </p>
                    {isExpanded && (
                        <button onClick={toggleText} className="show-less-btn">
                            {showLessText}
                        </button>
                    )}
                </>
            ) : (
                <p>{settings?.preference_overview}</p>
            )}

            {settings?.preference_google && settings?.preference_google_message && <p className='preference_google_message'>{`${settings?.preference_google_message} `}{settings?.preference_google_Link_text && settings?.preference_google_Link_url && <a href={settings?.preference_google_Link_url} target='_blank'>{`${settings?.preference_google_Link_text}`}</a>}</p> }
        </div>
        <div className="wprf-modal-table-wrapper wprf-gdpr-modal-frontend-content">
            <CookiesAccordion settings={settings} onEnableCookiesItem={onEnableCookiesItem} />
        </div>
        <div className="wprf-modal-preview-footer">
            <div className="nx_gdpr-action-button">
                <button onClick={ onHandleAccept }>{ __("Accept All", "notificationx") }</button>
                <button onClick={ onSaveConsent }>{ settings?.preference_btn ? settings?.preference_btn : __("Customize", "notificationx") }</button>
            </div>
            <div className="wprf-modal-preview-copyright">
                <NXBranding/>
            </div>
        </div>
    </Fragment>
  )
}

export default Customization