import { __ } from '@wordpress/i18n'
import React from 'react'
import { assetsURL } from '../../core/functions'

const HelpReviewSection = ({ props, context }) => {
  return (
    <div className='nx-admin-help-review-wrapper'>
        <div className='nx-admin-help-content-wrapper'>
            <div className='nx-admin-help-content'>
                <h4>{ __('Need Help?', 'notificationx') }</h4>
                <p>{ __('If you encounter issues or need assistance, we\'re here to help.', 'notificationx') }</p>
                <button>
                    <img src={ assetsURL('/images/new-img/contact-us.svg') } alt="" />
                    { __('Contact Us', 'notificationx') }
                </button>
            </div>
            <div className='nx-admin-help-content-banner'>
                <img src={ assetsURL('/images/new-img/need-help-banner.png') } alt="icon" />
            </div>
        </div>
        <div className='nx-admin-review-content-wrapper'>
            <div className='nx-admin-help-content-wrapper'>
                <div className='nx-admin-help-content'>
                    <h4>{ __('Show Your Love', 'notificationx') }</h4>
                    <p>{ __('We love having you in the NotificationX family. We are making it more.', 'notificationx') } </p>
                    <button>
                        <img src={ assetsURL('/images/new-img/message.svg') } alt="" />
                        { __('Leave a Review', 'notificationx') }
                    </button>
                </div>
                <div className='nx-admin-help-content-banner'>
                    <img src={ assetsURL('/images/new-img/review-banner.png') } alt="icon" />
                </div>
            </div>
        </div>

    </div>
  )
}

export default HelpReviewSection
