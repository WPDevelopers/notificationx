import { __ } from '@wordpress/i18n'
import React from 'react'
import { assetsURL } from '../../core/functions'

const HelpReviewSection = ({ props, context }) => {
  return (
    <div className='nx-admin-help-review-wrapper'>
        <div className='nx-admin-help-content-wrapper'>
            <div className='nx-admin-help-content'>
                <h4>{ __('Need Help?', 'notificationx') }</h4>
                <p>{ __('Our dedicated support team is here to assist you with all your inquiries, anytime you need.', 'notificationx') }</p>
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
                    <h4>{ __('Love NotificationX?', 'notificationx') }</h4>
                    <p>{ __('Your quick feedback helps us grow and build more awesome features for you!', 'notificationx') } </p>
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
