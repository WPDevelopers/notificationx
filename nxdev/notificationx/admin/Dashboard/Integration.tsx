import React, { Fragment, useEffect, useState } from 'react'
import nxHelper, { assetsURL } from '../../core/functions';
import { isArray } from '../../frontend/core/functions';
import { __, sprintf } from '@wordpress/i18n';
import SingleNotificationX from '../SingleNotificationX';
import { NOT_FOUND_DESC, NOT_FOUND_TITLE } from '../../core/constants';
import { Link } from 'react-router-dom';

const Integration = ({props, context}) => {
  const [notificationx, setNotificationx] = useState([]);
  const [totalItems, setTotalItems] = useState({
    all: 0,
    enabled: 0,
    disabled: 0,
  });

  useEffect(() => {
      const controller = typeof AbortController === 'undefined' ? undefined : new AbortController();
      nxHelper
          .get(`nx?&per_page=3`,
              { signal: controller?.signal }
          )
          .then((res: any) => {
              if(controller?.signal?.aborted){
                  return;
              }
              if ( isArray(res?.posts) ) {
                  setNotificationx(res?.posts);
              }
              if (res?.total) {
                setTotalItems({
                    all: res?.total || 0,
                    enabled: res?.enabled || 0,
                    disabled: res?.disabled || 0,
                });
            }
          }).catch(err => {
              console.error(__('NotificationX Fetch Error: ', 'notificationx'), err);
          });
  }, []);    
  
  return (
    <div className='nx-admin-content-wrapper nx-notifications-wrapper notificationx-items'>
        <div className='nx-integrations-details nx-content-details header'>
          <h4>{ __('Notifications', 'notificationx') }</h4>
          <Link className="nx-primary-btn" to={ { pathname: "/admin.php", search: `?page=nx-admin`} }>
              { __('View All Notifications', 'notificationx') }
          </Link>
        </div>
        <div className="nx-admin-items">
          <div className="nx-list-table-wrapper">
              <table className="wp-list-table widefat fixed striped notificationx-list">
                 { notificationx?.length > 0 &&
                    <Fragment>
                      <thead>
                        <tr>
                        <td>
                        </td>
                            <td>{__("NotificationX Title", 'notificationx')}</td>
                            <td>{__("Preview", 'notificationx')}</td>
                            <td>{__("Status", 'notificationx')}</td>
                            <td>{__("Type", 'notificationx')}</td>
                            <td>{__("Stats", 'notificationx')}</td>
                            <td>{__("Date", 'notificationx')}</td>
                            <td>{__("Action", 'notificationx')}</td>
                        </tr>
                    </thead>
                    <tbody>
                      { notificationx.map((item, i) => {
                        return <SingleNotificationX updateNotice={setNotificationx} totalItems={totalItems} setTotalItems={setTotalItems} i={i} key={`nx-${item.nx_id}`} {...item} />
                      } ) }
                    </tbody>
                  </Fragment>
                 }
                { notificationx?.length <= 0 &&
                  <div className='notifications-not-found nx-content-details'>
                    <img src={ assetsURL('/images/new-img/not-found.svg') } alt="icon" />
                    <h5>{ sprintf( '%s', NOT_FOUND_TITLE  ) }</h5>
                    <p>{ sprintf( '%s', NOT_FOUND_DESC  ) }</p>
                    <Link className="nx-primary-btn" to={ { pathname: "/admin.php", search: `?page=nx-edit`} }>
                      { __('Add New', 'notificationx') }
                    </Link>
                  </div>
                }
              </table>
          </div>
        </div>
    </div>
  )
}

export default Integration