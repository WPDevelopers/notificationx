import { __ } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { assetsURL } from '../../core/functions'

const InitialPopup = () => {
  return (
      <div className="nx-pop-up">
        <div className="nx-flex nx-pop-up-content">
          <div className="nx-pop-up-left-content">
            <div className="nx-black-friday-deal">
                <img src={ assetsURL('image/reports/black-friday-small.png', false) } alt="Black Friday Deal" />
            </div>
            <span className="nx-premium-tag">{ __('Premium','notificationx') }</span>
            <h2 className="nx-font-xl nx-pop-up-header">{ __('Want to maximize clicks and sales? Upgrade to PRO for advanced alerts.','notificationx') }</h2>
            <ul className="nx-premium-features-list">
              <li className="nx-font-m nx-premium-features-list-item">{ __('Highlight promotional offers','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Display sales count','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Grab instant attention','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Display low-stock alerts','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Location-specific notifications','notificationx') }</li>
            </ul>
            <a target="_blank" href="https://notificationx.com/#pricing" className="nx-btn nx-btn-primary nx-pop-up-btn">
              <span className="nx-line-height-0 nx-mr-4 nx-pop-up-btn-icon">
                <img src={ assetsURL('image/icons/crown-2.svg', false) } alt="Crown" />
              </span>
              <span>{ __('Unlock All Features','notificationx') }</span>
            </a>
            <div className="nx-guarantee">
              <span className="nx-line-height-0">
                <svg width={11} height={13} viewBox="0 0 11 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M10.9477 3.34726C10.9411 2.99628 10.9347 2.66471 10.9347 2.34394C10.9347 2.09265 10.731 1.88891 10.4797 1.88891C8.53363 1.88891 7.05198 1.32965 5.81696 0.128843C5.64029 -0.0429779 5.35914 -0.0429172 5.18252 0.128843C3.94762 1.32965 2.46622 1.88891 0.520311 1.88891C0.269012 1.88891 0.065278 2.09265 0.065278 2.34394C0.065278 2.66477 0.0589682 2.99646 0.0522337 3.34751C-0.0101362 6.6138 -0.0955608 11.0871 5.3507 12.9749C5.399 12.9917 5.44935 13 5.49971 13C5.55007 13 5.60049 12.9917 5.64872 12.9749C11.0954 11.0871 11.0101 6.61361 10.9477 3.34726ZM5.49977 12.0621C0.828885 10.3653 0.899506 6.64832 0.962179 3.36486C0.965941 3.1678 0.969581 2.97681 0.972129 2.78957C2.79469 2.71264 4.25213 2.16035 5.49977 1.07349C6.74753 2.16035 8.20522 2.7127 10.0279 2.78957C10.0304 2.97674 10.0341 3.16762 10.0378 3.36455C10.1005 6.64814 10.171 10.3653 5.49977 12.0621Z" fill="#666666" />
                  <path d="M7.06673 4.91926L4.8705 7.11537L3.93331 6.17819C3.75561 6.00054 3.46748 6.00054 3.28983 6.17819C3.11213 6.35595 3.11213 6.64402 3.28983 6.82172L4.54876 8.08065C4.63758 8.16947 4.75407 8.21388 4.8705 8.21388C4.98693 8.21388 5.10342 8.16947 5.19224 8.08065L7.71015 5.5628C7.88792 5.38509 7.88792 5.09697 7.71021 4.91932C7.53256 4.74161 7.24444 4.74155 7.06673 4.91926Z" fill="#666666" />
                </svg>
              </span>
              <span>{ __('No risk 14-day money-back guarantee included.','notificationx') }</span>
            </div>
          </div>
          <div className="nx-pop-up-right-content">
            <div className="nx-img-wrapper">
              <img src={ assetsURL('image/reports/popup-banner.png', false) } alt="Premium Features Image" />
            </div>
          </div>
        </div>
      </div>
  )
}

export default InitialPopup
