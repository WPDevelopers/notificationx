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
                {showAction &&
                    <div className='nx-dashboard-floating-actions'>

                        <div className='floating-item item-1'>
                            <a href="">Unlock pro Features</a>
                            <span className='nx-icons--wrap'>
                                <Crown />
                            </span>
                        </div>
                        <div className='floating-item item-2'>
                            <a href="">Get Support</a>
                            <span className='nx-icons--wrap'>
                                <GetSupport />
                            </span>
                        </div>
                        <div className='floating-item item-3'>
                            <a href="">Suggest a Feature</a>
                            <span className='nx-icons--wrap'>
                                <LightOn />
                            </span>
                        </div>
                        <div className='floating-item item-4'>
                            <a href="">Join Our Community</a>
                            <span className='nx-icons--wrap'>
                                <JoinCommunity />
                            </span>
                        </div>
                        <div className='floating-item item-5'>
                            <span className='nx-close' onClick={() => setShowAction(false)}>
                                <img src={assetsURL('/images/new-img/notification-close.svg')} alt="NX-Close-Img" />
                            </span>
                        </div>
                    </div>
                }
                <img src={assetsURL('/images/new-img/notification.svg')} alt="NX-Img" onClick={() => setShowAction(!showAction)} />
            </div>
        </Fragment>
    )
}

export default FloatingAction