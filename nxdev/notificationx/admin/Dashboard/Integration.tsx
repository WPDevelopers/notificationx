import React, { Fragment, useEffect, useState } from 'react'
import nxHelper from '../../core/functions';
import { isArray } from '../../frontend/core/functions';
import { __, sprintf } from '@wordpress/i18n';
import SingleNotificationX from '../SingleNotificationX';
import { Link } from 'react-router-dom';
import NxIcon from '../../components/NxIcon';
import type { NotificationTotals } from './Dashboard';

// Recent notifications card.
const Integration = ({props, context, onTotalsChange}: { props: any; context: any; onTotalsChange?: (totals: NotificationTotals) => void }) => {
  const [notificationx, setNotificationx] = useState([]);
  const [isLoaded, setIsLoaded] = useState(false);
  const [totalItems, setTotalItems] = useState<NotificationTotals>({
    all: 0,
    enabled: 0,
    disabled: 0,
  });
  const [reload, setReload] = useState();

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
          }).finally(() => {
              if (!controller?.signal?.aborted) {
                  setIsLoaded(true);
              }
          });
      return () => controller?.abort();
  }, [reload]);

  useEffect(() => {
      onTotalsChange?.(totalItems);
  }, [totalItems]);

  const canCreate = !context?.createRedirect;

  return (
    <section className='nx-card nx-dash-recent notificationx-items'>
        <header className='nx-card__header'>
          <div>
            <h2 className="nx-card__title">{ __('Recent Notifications', 'notificationx') }</h2>
            <p className="nx-card__subtitle">
              { totalItems.all > 0
                // translators: %1$s: number of active notifications, %2$s: total number of notifications.
                ? sprintf(__('%1$s active of %2$s notifications', 'notificationx'), totalItems.enabled, totalItems.all)
                : __('Your latest notifications appear here.', 'notificationx') }
            </p>
          </div>
          { totalItems.all > 0 &&
            <Link className="nx-button nx-button--ghost nx-dashboard-view-all" to={ { pathname: "/admin.php", search: `?page=nx-admin`} }>
              { __('View All Notifications', 'notificationx') }
              <NxIcon name="arrowRight" size={15} />
            </Link>
          }
        </header>

        { notificationx?.length > 0 &&
          <div className="nx-admin-items">
            <div className="nx-list-table-wrapper">
              <table className="notificationx-list nx-dash-table">
                <Fragment>
                  <thead>
                    <tr>
                      <td className="nx-dash-table__select"></td>
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
                      return <SingleNotificationX updateNotice={setNotificationx} totalItems={totalItems} setTotalItems={setTotalItems} i={i} key={`nx-${item.nx_id}`} setReload={setReload} {...item} />
                    } ) }
                  </tbody>
                </Fragment>
              </table>
            </div>
          </div>
        }

        { isLoaded && notificationx?.length <= 0 &&
          <div className='nx-dash-empty'>
            <span className="nx-dash-empty__icon">
              <NxIcon name="bell" size={26} />
            </span>
            <h3>{ __('No notifications yet', 'notificationx') }</h3>
            <p>{ __('Create your first notification to start showing social proof on your site.', 'notificationx') }</p>
            { canCreate &&
              <Link className="nx-button nx-button--primary" to={ { pathname: "/admin.php", search: `?page=nx-edit`} }>
                <NxIcon name="plus" size={16} strokeWidth={2.25} />
                { __('Add New', 'notificationx') }
              </Link>
            }
          </div>
        }

        { !isLoaded &&
          <div className="nx-dash-skeleton" aria-hidden="true">
            <span></span><span></span><span></span>
          </div>
        }
    </section>
  )
}

export default Integration
