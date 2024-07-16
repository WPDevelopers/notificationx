import { __ } from '@wordpress/i18n'
import React from 'react'
import { assetsURL } from '../../core/functions'
import { NavLink } from 'react-router-dom'

const AnalyticsOverview = ({ props, context }) => {
  return (
    <div className='nx-analytics-wrapper'>
        <NavLink className={'nx-analytics-content-wrapper'} to={ { pathname: '/admin.php', search  : "?page=nx-analytics&comparison=views"} }>
          <img src={ assetsURL('/images/analytics/views-icon.png') } alt={ __('Total Views', 'notificationx') } />
          <div className='analytics-counter'>
            <span className="nx-counter-label">{ __('Total Views', 'notificationx') }</span>
            <h3 className="nx-counter-number">{ context?.analytics?.totalViews }</h3>
          </div>
        </NavLink>
        <NavLink className={'nx-analytics-content-wrapper'} to={ { pathname: '/admin.php', search  : "?page=nx-analytics&comparison=clicks"} }>
          <img src={ assetsURL('/images/analytics/clicks-icon.png') } alt={ __('Total Clicks', 'notificationx') } />
          <div className='analytics-counter'>
            <span className="nx-counter-label">{ __('Total Clicks', 'notificationx') }</span>
            <h3 className="nx-counter-number">{ context?.analytics?.totalClicks }</h3>
          </div>
        </NavLink>
        <NavLink className={'nx-analytics-content-wrapper'} to={ { pathname: '/admin.php', search  : "?page=nx-analytics&comparison=ctr"} }>
          <div><img src={ assetsURL('/images/analytics/clicks-icon.png') } alt={ __('Click-Through-Rate', 'notificationx') } /></div>
          <div className='analytics-counter'>
            <span className="nx-counter-label">{ __('Click-Through-Rate', 'notificationx') }</span>
            <h3 className="nx-counter-number">{ context?.analytics?.totalCtr }</h3>
          </div>
        </NavLink>
    </div>
  )
}

export default AnalyticsOverview