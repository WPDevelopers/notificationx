import React, { Fragment, useState } from 'react'
import Crown from '../../icons/Crown'
import GetSupport from '../../icons/GetSupport'
import LightOn from '../../icons/LightOn'
import JoinCommunity from '../../icons/JoinCommunity'
import nxHelper, { assetsURL } from '../../core/functions'
import { __ } from '@wordpress/i18n'
import { Link } from 'react-router-dom'
import { useNotificationXContext } from '../../hooks'
import WhatsNew from '../../icons/whatsNew'

const FloatingAction = ({isPro}) => {
    const [showAction, setShowAction] = useState(false);
    const [hideNewFeatureBadge, setHideNewFeatureBadge] = useState(true);
    const nxContext = useNotificationXContext();
    
    const handleFloatingActionButton = () => {
        setShowAction(!showAction)
        nxHelper.post('settings', { notification_alert_version : 1 } ).then((res: any) => {
            setHideNewFeatureBadge(false);
        })
    }
    return (
        <Fragment>
            <div className={`notification--wrapper${showAction ? ' open' : ''}`}>
                <div className='nx-dashboard-floating-actions'>
                    {!isPro &&
                        <a href={'https://notificationx.com/#pricing'} target='_blank' className='floating-item item-1'>
                            <span className='nx-items--details'>{ __('Unlock pro Features','notificationx') }</span>
                            <span className='nx-items--icon'>
                                <Crown />
                            </span>
                        </a>
                    }
                    <a href={'https://notificationx.com/support/'} target='_blank' className='floating-item item-2'>
                        <span className='nx-items--details'>{ __('Get Support', 'notificationx') }</span>
                        <span className='nx-items--icon'>
                            <GetSupport />
                        </span>
                    </a>
                    <a href={'https://wpdeveloper.com/support/new-ticket/'} target='_blank' className='floating-item item-3'>
                        <span className='nx-items--details'>{ __('Suggest a Feature', 'notificationx') }</span>
                        <span className='nx-items--icon'>
                            <LightOn />
                        </span>
                    </a>
                    <a href={'https://www.facebook.com/groups/NotificationX.Community/'} target='_blank' className='floating-item item-4'>
                        <span className='nx-items--details'>{ __('Join Our Community', 'notificationx') }</span>
                        <span className='nx-items--icon'>
                            <JoinCommunity />
                        </span>
                    </a>
                    <Link to={ { pathname: "/admin.php", search: `?page=nx-dashboard&section=resource`} } className={`floating-item item-5 ${ nxContext?.notification_alert_version > nxContext?.settings?.savedValues?.notification_alert_version ? 'active' : '' }`}>
                        <span className='nx-items--details'>{ __('What\'s New', 'notificationx') }</span>
                        <span className='nx-items--icon'>
                            <WhatsNew/>
                        </span>
                    </Link>
                    <div className='floating-item item-5'>
                        <span className='nx-close' onClick={() => setShowAction(false)}>
                            <img src={assetsURL('/images/new-img/notification-close.svg')} alt={__('NX-Close-Img', 'notificationx')} />
                        </span>
                    </div>
                </div>
                <div className='nx-floating-icon'  onClick={handleFloatingActionButton}>
                    <img src={ 'https://notificationx.com/wp-content/uploads/2024/09/main.gif' } alt="NX-Img" />
                    { (hideNewFeatureBadge && nxContext?.notification_alert_version > nxContext?.settings?.savedValues?.notification_alert_version) &&
                        <span className='nx-new-feature-badge'>1</span>
                    }
                </div>
            </div>
        </Fragment>
    )
}

export default FloatingAction