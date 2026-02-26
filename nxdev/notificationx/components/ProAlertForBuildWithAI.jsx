import React from 'react'
import ProCrownIcon from '../icons/pro-crown-icon'
import { __ } from '@wordpress/i18n'
import Crown from '../icons/Crown'
import BgImg from '../frontend/assets/img/build-ai-bg.png'

const ProAlertForBuildWithAI = () => {
  return (
    <div className='nx-bar_build_with_ai-pro-alert'>
        <div className='nx-bar_build_with_ai-pro-alert-info'>
          <ProCrownIcon/>
          <h3>{ __('Build with AI Available for PRO Users Only', 'notificationx') }</h3>
          <span>{ __('The import option is a premium feature. Please upgrade to a PRO plan to access template importing.', 'notificationx') }</span>
        </div>
        <div className="nx-bar_build_with_ai-pro-alert-button">
            <a href="https://notificationx.com/#pricing" target="_blank">{ __('Cancel', 'notificationx') }</a>
            <a href="https://notificationx.com/#pricing" target="_blank">
              <svg width="17" height="13" viewBox="0 0 17 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.03347 9.30151C0.691298 7.07739 0.349129 4.85332 0.00695999 2.6292C-0.0689213 2.13616 0.49206 1.7999 0.891097 2.09921C1.95715 2.89875 3.02314 3.69823 4.08919 4.49776C4.44019 4.76101 4.94025 4.67535 5.18359 4.31028L7.84607 0.316533C8.12743 -0.105511 8.74753 -0.105511 9.0289 0.316533L11.6914 4.31028C11.9347 4.67535 12.4348 4.76096 12.7858 4.49776C13.8518 3.69823 14.9178 2.89875 15.9839 2.09921C16.3829 1.7999 16.9439 2.13616 16.8681 2.6292C16.5259 4.85332 16.1837 7.07739 15.8416 9.30151H1.03347Z" fill="white"/>
                <path d="M15.0673 13.0031H1.80328C1.37691 13.0031 1.03125 12.6575 1.03125 12.2311V10.5352H15.8393V12.2311C15.8393 12.6575 15.4936 13.0031 15.0673 13.0031Z" fill="white"/>
              </svg>
             { __('Go Premium', 'notificationx') }</a>
        </div>
    </div>
  )
}

export default ProAlertForBuildWithAI
