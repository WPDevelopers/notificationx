import { __, sprintf } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { GET_STARTED_DESC, GET_STARTED_TXT } from '../../core/constants'
import { assetsURL } from '../../core/functions';
import { Link } from 'react-router-dom';

const GetStarted = ({props, context}) => {
    console.log('context',context);
    
  return (
    <Fragment>
        <div className="nx-admin-header">
            <img src={ assetsURL('/images/new-img/main-logo.svg') } alt={__('NotificationX Logo', 'notificationx') } />
            <Link className="nx-add-new-btn" to={ { pathname: "/admin.php", search: `?page=nx-edit`} }>
                { __('Add New', 'notificationx') }
                <img src={ assetsURL('/images/new-img/add.svg') } alt={__('add icon', 'notificationx') } />
            </Link>
        </div>

        <div className="nx-admin-content-wrapper nx-started">
            <div className='nx-started-wrapper'>
            <div className='nx-video-widget'>
                <a href="#">
                    <img src={ assetsURL('/images/new-img/video-widget.png') } alt={ __('video-widget', 'notificationx') } />
                </a>
            </div>
            <div className='nx-started-content nx-content-details'>
                <h2>{ sprintf( __('%s', 'notificationx'), GET_STARTED_TXT ) }</h2>
                <p>{ sprintf( __('%s', 'notificationx'), GET_STARTED_DESC ) }</p>
                <Link className="nx-primary-btn" to={ { pathname: "/admin.php", search: `?page=nx-edit`} }>{ __('Launch Setup Wizard', 'notificationx') }</Link>
                <a className='nx-resource-link' href="#">
                    { __('Read Starter Guide', 'notificationx') }
                    <img src={assetsURL('/images/new-img/link.svg')} alt={ __('link-icon', 'notificationx') } />
                </a>
            </div>
            </div>
        </div>
    </Fragment>
  )
}

export default GetStarted