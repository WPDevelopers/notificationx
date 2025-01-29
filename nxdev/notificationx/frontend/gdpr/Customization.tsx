import React, { Fragment, useState } from 'react'
import CookiesAccordion from './utils/CookiesAccordion'
import NXBranding from '../themes/helpers/NXBranding'
import { __ } from '@wordpress/i18n'
import CloseIcon from '../../icons/Close';

const Customization = ({ settings, onEnableCookiesItem, onHandleAccept, onSaveConsent, onHandleReject, setIsOpenGdprCustomizationModal }) => {
    const [isExpanded, setIsExpanded] = useState(false);
    let showMoreText = settings?.preference_more_btn ? settings?.preference_more_btn : __("Show more",'notificationx');
    let showLessText = settings?.preference_less_btn ? settings?.preference_less_btn : __("Show less",'notificationx');
    const toggleText = () => {
        setIsExpanded(!isExpanded);
    };
  return (
    <Fragment>
        <div className="wprf-modal-table-wrapper nx-gdpr-modal-header">
            {settings?.preference_title && <h3>{settings?.preference_title}</h3>}
            <button
                type="button"
                onClick={() => {
                    setIsOpenGdprCustomizationModal(false)
                    const elements = document.getElementsByClassName('nx-gdpr');
                    for (let i = 0; i < elements.length; i++) {
                        // @ts-ignore
                        elements[i].style.display = 'block';
                    }
                }}
                className="nx-gdpr-customization-close"
                aria-label="Close"
            >
                <CloseIcon />
            </button>
        </div>
        <div className="wprf-modal-table-wrapper wprf-gdpr-modal-frontend-content">
            <div className="wprf-modal-table-content-top">
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
            <CookiesAccordion settings={settings} onEnableCookiesItem={onEnableCookiesItem} />
        </div>
        <div className="wprf-modal-preview-footer">
            <div className="nx_gdpr-action-button">
                <button onClick={ onHandleAccept }>{settings?.gdpr_accept_btn || __('Accept All', 'notificationx')}</button>
                <button onClick={ onSaveConsent }>{ settings?.preference_btn ? settings?.preference_btn : __("Save My Preferences", "notificationx") }</button>
                <button onClick={ onHandleReject }>{settings?.gdpr_reject_btn || __('Reject All', 'notificationx')}</button>
            </div>
            {!settings?.disable_powered_by && 
                <div className="wprf-modal-preview-copyright">
                    <NXBranding/>
                </div>
            }
        </div>
    </Fragment>
  )
}

export default Customization