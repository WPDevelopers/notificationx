import { __, sprintf } from '@wordpress/i18n'
import React, { Fragment, useState } from 'react'
import { GET_STARTED_DESC, GET_STARTED_TXT } from '../../core/constants'
import { assetsURL } from '../../core/functions';
import { Link } from 'react-router-dom';
import ReactModal from "react-modal";

const GetStarted = ({props, context}) => {
    const [isOpenGetStartedModal, setIsOpenGetStartedModal] = useState(false);
    const addNewRedirect = () => {
        context.setRedirect({
            page: `nx-edit`,
            state: { type: 'comments', source: 'wp_reviews', timestamp: new Date().getTime() }
        });
    }
    return (
        <Fragment>
            <div className="nx-admin-header">
                <img src={ assetsURL('/images/new-img/main-logo.svg') } alt={__('NotificationX Logo', 'notificationx') } />
                <a className="nx-add-new-btn" onClick={ addNewRedirect }>
                    { __('Add New', 'notificationx') }
                    <img src={ assetsURL('/images/new-img/add.svg') } alt={__('add icon', 'notificationx') } />
                </a>
            </div>

            <div className="nx-admin-content-wrapper nx-started">
                <div className='nx-started-wrapper'>
                <div className='nx-video-widget'>
                    <a href="#" onClick={ () => setIsOpenGetStartedModal(true) }>
                        <img src={ assetsURL('/images/new-img/video-widget.png') } alt={ __('video-widget', 'notificationx') } />
                    </a>
                </div>
                <div className='nx-started-content nx-content-details'>
                    <h2>{ sprintf( '%s', GET_STARTED_TXT ) }</h2>
                    <p>{ sprintf( '%s', GET_STARTED_DESC ) }</p>
                    <Link className="nx-primary-btn" to={ { pathname: "/admin.php", search: `?page=nx-builder`} }>{ __('Launch Setup Wizard', 'notificationx') }</Link>
                    <a className='nx-resource-link' target='_blank' href={sprintf('%s','https://www.youtube.com/playlist?list=PLWHp1xKHCfxAj4AAs3kmzmDZKvjv6eycK')}>
                        { __('Watch Tutorials', 'notificationx') }
                        <img src={assetsURL('/images/new-img/link.svg')} alt={ __('link-icon', 'notificationx') } />
                    </a>
                </div>
                </div>
            </div>
            <ReactModal
                isOpen={isOpenGetStartedModal}
                onRequestClose={() => setIsOpenGetStartedModal(false)}
                className='nx-get-started-modal'
                style={{
                    overlay: {
                        position: "fixed",
                        display: "flex",
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        backgroundColor: "rgba(3, 6, 60, 0.7)",
                        zIndex: 9999,
                        padding: "60px 15px",
                    },
                    content: {
                        position: "static",
                        margin: "auto",
                        border: "0px solid #5414D0",
                        background: "#5414D0",
                        overflow: "auto",
                        WebkitOverflowScrolling: "touch",
                        borderRadius: "4px",
                        outline: "none",
                        padding: "0px",
                    },
                }}
            >
                <>
                    <iframe id="email_subscription_video" allowFullScreen width="750" height="500" src="https://www.youtube.com/embed/0uANsOSFmtw"></iframe>
                </>
            </ReactModal>
        </Fragment>
    )
}

export default GetStarted