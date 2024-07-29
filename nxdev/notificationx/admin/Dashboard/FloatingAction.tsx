import React, { Fragment, useState } from 'react'
import Crown from '../../icons/Crown'
import GetSupport from '../../icons/getSupport'
import LightOn from '../../icons/lightOn'
import JoinCommunity from '../../icons/joinCommunity'

const FloatingAction = () => {
    const [showAction, setShowAction] = useState(false);
    return (
        <Fragment>
            <div className='notification--wrapper'>
                {showAction &&
                    <div className='nx-dashboard-floating-actions'>
                        <ul>
                            <li>
                                <a href="">Unlock pro Features</a>
                                <span className='nx-icons--wrap'>
                                    <Crown />
                                </span>
                            </li>
                            <li>
                                <a href="">Get Support</a>
                                <span className='nx-icons--wrap'>
                                    <GetSupport />
                                </span>
                            </li>
                            <li>
                                <a href="">Suggest a Feature</a>
                                <span className='nx-icons--wrap'>
                                    <LightOn />
                                </span>
                            </li>
                            <li>
                                <a href="">Join Our Community</a>
                                <span className='nx-icons--wrap'>
                                    <JoinCommunity />
                                </span>
                            </li>
                        </ul>
                    </div>
                }
                <img src="/wp-content/plugins/notificationx/assets/admin/images/new-img/notification.svg" alt="NX-Img" onClick={() => setShowAction(!showAction)} />
            </div>
        </Fragment>
    )
}

export default FloatingAction