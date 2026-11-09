import { __, sprintf } from '@wordpress/i18n'
import React, { Fragment, useState } from 'react'
import { GET_STARTED_DESC, GET_STARTED_TXT } from '../../core/constants'
import { Link } from 'react-router-dom';
import ReactModal from "react-modal";
import NxIcon from '../../components/NxIcon';

const GetStarted = ({props, context}) => {
    const [isOpenGetStartedModal, setIsOpenGetStartedModal] = useState(false);
    const adminAssets = context?.assets?.admin || '';

    return (
        <Fragment>
            <section className="nx-card nx-dash-hero">
                <div className='nx-dash-hero__content'>
                    <span className="nx-dash-eyebrow">
                        <NxIcon name="sparkles" size={14} />
                        { __('Get started', 'notificationx') }
                    </span>
                    <h1 className="nx-dash-hero__title">{ sprintf( '%s', GET_STARTED_TXT ) }</h1>
                    <p className="nx-dash-hero__desc">{ sprintf( '%s', GET_STARTED_DESC ) }</p>
                    <div className="nx-dash-hero__actions">
                        <Link className="nx-button nx-button--primary nx-button--lg" to={ { pathname: "/admin.php", search: `?page=nx-builder`} }>
                            <NxIcon name="zap" size={16} />
                            { __('Launch Setup Wizard', 'notificationx') }
                        </Link>
                        <a className='nx-button nx-button--secondary nx-button--lg' target='_blank' rel="noopener noreferrer" href={sprintf('%s','https://www.youtube.com/playlist?list=PLWHp1xKHCfxAj4AAs3kmzmDZKvjv6eycK')}>
                            { __('Watch Tutorials', 'notificationx') }
                            <NxIcon name="external" size={15} />
                        </a>
                    </div>
                </div>
                <button
                    type="button"
                    className='nx-dash-hero__media'
                    onClick={ () => setIsOpenGetStartedModal(true) }
                    aria-label={ __('Play the NotificationX introduction video', 'notificationx') }
                >
                    <img src={ `${adminAssets}/images/new-img/video-widget.png` } alt="" />
                </button>
            </section>
            <ReactModal
                isOpen={isOpenGetStartedModal}
                onRequestClose={() => setIsOpenGetStartedModal(false)}
                className='nx-get-started-modal nx-dash-video-modal'
                overlayClassName='nx-dash-video-overlay'
                contentLabel={ __('NotificationX introduction video', 'notificationx') }
            >
                <button type="button" className="nx-dash-video-modal__close" onClick={() => setIsOpenGetStartedModal(false)} aria-label={ __('Close', 'notificationx') }>
                    <NxIcon name="close" size={18} />
                </button>
                <div className="nx-dash-video-modal__frame">
                    <iframe id="email_subscription_video" allowFullScreen title={ __('NotificationX introduction video', 'notificationx') } src="https://www.youtube.com/embed/6A4OLJjRW_g?si=xhHcE5qIYw9qtRWf"></iframe>
                </div>
            </ReactModal>
        </Fragment>
    )
}

export default GetStarted
