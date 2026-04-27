import { __ } from '@wordpress/i18n'
import React from 'react'

const BuildWithAIVersionCompare = () => {
  return (
    <div className='nx-bar_build_with_ai-version-compare'>
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g clip-path="url(#clip0_7962_5123)">
        <path d="M10 7.5V10.8333" stroke="#F2870D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8.63574 2.99268L1.88074 14.271C1.74149 14.5122 1.6678 14.7856 1.667 15.064C1.6662 15.3425 1.73832 15.6163 1.87619 15.8583C2.01407 16.1002 2.21288 16.3019 2.45286 16.4431C2.69284 16.5844 2.96562 16.6604 3.24407 16.6635H16.7557C17.0341 16.6603 17.3067 16.5843 17.5466 16.4431C17.7864 16.3019 17.9852 16.1003 18.123 15.8585C18.2609 15.6167 18.333 15.343 18.3323 15.0646C18.3316 14.7863 18.2581 14.513 18.1191 14.2718L11.3641 2.99184C11.222 2.75726 11.0217 2.5633 10.7828 2.42868C10.5438 2.29405 10.2742 2.22333 9.99991 2.22333C9.72563 2.22333 9.45599 2.29405 9.21703 2.42868C8.97807 2.5633 8.77786 2.75726 8.63574 2.99184" stroke="#F2870D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10 13.3333H10.0083" stroke="#F2870D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </g>
        <defs>
        <clipPath id="clip0_7962_5123">
        <rect width="20" height="20" fill="white"/>
        </clipPath>
        </defs>
        </svg>
        <p>{ __('Please update to the latest version of NotificationX Pro to use this feature.', 'notificationx-pro') }</p> 
    </div>
  )
}

export default BuildWithAIVersionCompare
