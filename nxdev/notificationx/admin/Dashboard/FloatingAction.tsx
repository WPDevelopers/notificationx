import React, { Fragment, useState } from 'react'
import Crown from '../../icons/Crown'
import GetSupport from '../../icons/getSupport'
import LightOn from '../../icons/lightOn'
import JoinCommunity from '../../icons/joinCommunity'
import { assetsURL } from '../../core/functions'

const FloatingAction = () => {
    const [showAction, setShowAction] = useState(false);
    return (
        <Fragment>
            <div className={`notification--wrapper${showAction ? ' open' : ''}`}>
                <div className='nx-dashboard-floating-actions'>

                    <a href="#" className='floating-item item-1'>
                        <span className='nx-items--details'>Unlock pro Features</span>
                        <span className='nx-items--icon'>
                            <Crown />
                        </span>
                    </a>
                    <a href="#" className='floating-item item-2'>
                        <span className='nx-items--details'>Get Support</span>
                        <span className='nx-items--icon'>
                            <GetSupport />
                        </span>
                    </a>
                    <a href="#" className='floating-item item-3'>
                        <span className='nx-items--details'>Suggest a Feature</span>
                        <span className='nx-items--icon'>
                            <LightOn />
                        </span>
                    </a>
                    <a href="#" className='floating-item item-4'>
                        <span className='nx-items--details'>Join Our Community</span>
                        <span className='nx-items--icon'>
                            <JoinCommunity />
                        </span>
                    </a>
                    <div className='floating-item item-5'>
                        <span className='nx-close' onClick={() => setShowAction(false)}>
                            <img src={assetsURL('/images/new-img/notification-close.svg')} alt="NX-Close-Img" />
                        </span>
                    </div>
                </div>
                <img src={assetsURL('/images/new-img/notification.svg')} alt="NX-Img" onClick={() => setShowAction(!showAction)} />
            </div>
        </Fragment>
    )
}

export default FloatingAction