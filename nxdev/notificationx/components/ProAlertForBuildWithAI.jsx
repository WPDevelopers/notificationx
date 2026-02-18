import React from 'react'
import ProCrownIcon from '../icons/pro-crown-icon'
import { __ } from '@wordpress/i18n'
import Crown from '../icons/Crown'

const ProAlertForBuildWithAI = () => {
  return (
    <div className='nx-bar_build_with_ai-pro-alert'>
        <ProCrownIcon/>
        <h3>{ __('Build with AI Available for PRO Users Only', 'notificationx') }</h3>
        <span>{ __('The import option is a premium feature. Please upgrade to a PRO plan to access template importing.', 'notificationx') }</span>
        <div className="nx-bar_build_with_ai-pro-alert-button">
            <a href="https://notificationx.com/#pricing" target="_blank">{ __('Cancel', 'notificationx') }</a>
            <a href="https://notificationx.com/#pricing" target="_blank">
                <Crown/>
             { __('Go Premium', 'notificationx') }</a>
        </div>
    </div>
  )
}

export default ProAlertForBuildWithAI
