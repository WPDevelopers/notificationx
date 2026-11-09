import { __, sprintf } from '@wordpress/i18n'
import React from 'react'
import { Link } from 'react-router-dom'
import NxIcon from '../../components/NxIcon'
import type { NotificationTotals } from './Dashboard'

type Stat = {
  key: string;
  icon: string;
  label: string;
  value: React.ReactNode;
  caption: string;
  action: string;
  search: string;
  linkable: boolean;
};

const AnalyticsOverview = ({ props, context, totals }: { props: any; context: any; totals: NotificationTotals }) => {
  const analytics = context?.analytics || {};
  const canSeeAnalytics = !context?.analyticsRedirect;

  const stats: Stat[] = [
    {
      key     : 'notifications',
      icon    : 'layers',
      label   : __('Active Notifications', 'notificationx'),
      value   : totals?.enabled ?? 0,
      // translators: %s: total number of notifications.
      caption : sprintf(__('of %s notifications', 'notificationx'), totals?.all ?? 0),
      action  : __('Manage notifications', 'notificationx'),
      search  : '?page=nx-admin',
      linkable: true,
    },
    {
      key     : 'views',
      icon    : 'eye',
      label   : __('Total Views', 'notificationx'),
      value   : analytics?.totalViews ?? 0,
      caption : __('All time', 'notificationx'),
      action  : __('View report', 'notificationx'),
      search  : '?page=nx-analytics&comparison=views',
      linkable: canSeeAnalytics,
    },
    {
      key     : 'clicks',
      icon    : 'pointer',
      label   : __('Total Clicks', 'notificationx'),
      value   : analytics?.totalClicks ?? 0,
      caption : __('All time', 'notificationx'),
      action  : __('View report', 'notificationx'),
      search  : '?page=nx-analytics&comparison=clicks',
      linkable: canSeeAnalytics,
    },
    {
      key     : 'ctr',
      icon    : 'percent',
      label   : __('Click-Through-Rate', 'notificationx'),
      value   : <>{analytics?.totalCtr ?? 0}<small>%</small></>,
      caption : __('Clicks per view', 'notificationx'),
      action  : __('View report', 'notificationx'),
      search  : '?page=nx-analytics&comparison=ctr',
      linkable: canSeeAnalytics,
    },
  ];

  return (
    <section className='nx-dash-stats' aria-label={ __('Overview', 'notificationx') }>
      { stats.map((stat) => {
        const body = (
          <>
            <span className="nx-dash-stat__icon"><NxIcon name={stat.icon} size={20} /></span>
            <span className="nx-dash-stat__body">
              <span className="nx-dash-stat__label">{ stat.label }</span>
              <strong className="nx-dash-stat__value">{ stat.value }</strong>
              <span className="nx-dash-stat__caption">{ stat.caption }</span>
            </span>
            { stat.linkable && (
              <span className="nx-dash-stat__arrow" aria-hidden="true"><NxIcon name="arrowRight" size={14} /></span>
            ) }
          </>
        );
        return stat.linkable ? (
          <Link
            key={stat.key}
            className={`nx-card nx-dash-stat is-link is-${stat.key}`}
            to={ { pathname: '/admin.php', search: stat.search } }
            title={ stat.action }
          >
            { body }
          </Link>
        ) : (
          <div key={stat.key} className={`nx-card nx-dash-stat is-${stat.key}`}>{ body }</div>
        );
      }) }
    </section>
  )
}

export default AnalyticsOverview
