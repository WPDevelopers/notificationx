import React, { useEffect, useState } from 'react'
import nxHelper, { assetsURL } from '../../core/functions';
import { isArray } from '../../frontend/core/functions';
import { __, sprintf } from '@wordpress/i18n';
import SingleNotificationX from '../SingleNotificationX';
import { NOT_FOUND_DESC, NOT_FOUND_TITLE } from '../../core/constants';

const Integration = ({props, context}) => {
  const [notificationx, setNotificationx] = useState([]);
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
          }).catch(err => {
              console.error(__('NotificationX Fetch Error: ', 'notificationx'), err);
          });
  }, []);    
  
  return (
    <div className='nx-admin-content-wrapper nx-notifications-wrapper notificationx-items'>
        <div className='nx-integrations-details nx-content-details header'>
          <h4>{ __('Integrations', 'notificationx') }</h4>
          <button className='nx-primary-btn'>{ __('View All Notifications', 'notificationx') }</button>
        </div>
        <div className="nx-admin-items">
          <div className="nx-list-table-wrapper">
              <table className="wp-list-table widefat fixed striped notificationx-list">
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
                    return <SingleNotificationX i={i} key={`nx-${item.nx_id}`} {...item} />
                  } ) }
                  { notificationx?.length <= 0 &&
                    <div className='notifications-not-found nx-content-details'>
                      <img src={ assetsURL('/images/new-img/not-found.svg') } alt="icon" />
                      <h5>{ sprintf( '%s', NOT_FOUND_TITLE  ) }</h5>
                      <p>{ sprintf( '%s', NOT_FOUND_DESC  ) }</p>
                      <button className='nx-primary-btn'> { __('Add New', 'notificationx') } <img src={assetsURL('/images/new-img/add.svg')} alt="icon"/></button>
                    </div>
                  }
                  </tbody>
              </table>
          </div>
        </div>
    </div>
  )
}

export default Integration