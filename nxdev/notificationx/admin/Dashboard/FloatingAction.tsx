import React, { Fragment, useState } from 'react'
import Crown from '../../icons/Crown'
import GetSupport from '../../icons/GetSupport'
import LightOn from '../../icons/LightOn'
import JoinCommunity from '../../icons/JoinCommunity'
import { assetsURL } from '../../core/functions'
import { __ } from '@wordpress/i18n'

const FloatingAction = () => {
    const [showAction, setShowAction] = useState(false);
    return (
        <Fragment>
            <div className={`notification--wrapper${showAction ? ' open' : ''}`}>
                <div className='nx-dashboard-floating-actions'>
                    <a href={'https://notificationx.com/#pricing'} target='_blank' className='floating-item item-1'>
                        <span className='nx-items--details'>{ __('Unlock pro Features','notificationx') }</span>
                        <span className='nx-items--icon'>
                            <Crown />
                        </span>
                    </a>
                    <a href={'https://wpdeveloper.com/support/'} target='_blank' className='floating-item item-2'>
                        <span className='nx-items--details'>{ __('Get Support', 'notificationx') }</span>
                        <span className='nx-items--icon'>
                            <GetSupport />
                        </span>
                    </a>
                    <a href={'https://wordpress.org/support/plugin/notificationx/'} target='_blank' className='floating-item item-3'>
                        <span className='nx-items--details'>{ __('Suggest a Feature', 'notificationx') }</span>
                        <span className='nx-items--icon'>
                            <LightOn />
                        </span>
                    </a>
                    <a href={'https://www.facebook.com/TheNotificationX'} target='_blank' className='floating-item item-4'>
                        <span className='nx-items--details'>{ __('Join Our Community', 'notificationx') }</span>
                        <span className='nx-items--icon'>
                            <JoinCommunity />
                        </span>
                    </a>
                    <div className='floating-item item-5'>
                        <span className='nx-close' onClick={() => setShowAction(false)}>
                            <img src={assetsURL('/images/new-img/notification-close.svg')} alt={__('NX-Close-Img', 'notificationx')} />
                        </span>
                    </div>
                </div>
                <img src={assetsURL('/images/new-img/notification.svg')} alt="NX-Img" onClick={() => setShowAction(!showAction)} />
            </div>
        </Fragment>
    )
}

export default FloatingAction