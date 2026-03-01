import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { assetsURL } from '../../core/functions'
import nxHelper from '../../core/functions'

const InitialPopup = ({ onDismiss }) => {
  const [isLoading, setIsLoading] = useState(false)
    const today = new Date();
    const endDate = new Date('2025-12-04T23:59:59'); // Show until end of Dec 4
        
    const handleDismiss = () => {
      setIsLoading(true);

      nxHelper.post('miscellaneous', { action: 'dismiss_initial_popup' })
          .then(response => {
              if (response?.success && onDismiss) onDismiss();
              else console.error('Failed to dismiss popup:', response);
          })
          .catch(err => {})
          .finally(() => setIsLoading(false));
  };


  return (
      <div className="nx-pop-up">
        <div className="nx-flex nx-pop-up-content">
          <div className="nx-pop-up-left-content">
            { (today <= endDate) && (
                <div className="nx-black-friday-deal">
                  <a href={'https://notificationx.com/bfcm2025-admin-notice'} target='_blank'>
                    <img
                      src={ assetsURL('image/reports/black-friday-small.webp', false) }
                      alt={__('Black Friday Deal', 'notificationx')}
                    />
                  </a>
                </div>
            )}
            <span className="nx-premium-tag">{ __('Premium','notificationx') }</span>
            <h2 className="nx-font-xl nx-pop-up-header">{ __('Want to maximize clicks and sales? Upgrade to PRO for advanced alerts.','notificationx') }</h2>
            <ul className="nx-premium-features-list">
              <li className="nx-font-m nx-premium-features-list-item">{ __('Location-specific notifications','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Create custom popup notifications','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Highlight promotional offers with coupons','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Display sales counts & low-stock alerts','notificationx') }</li>
              <li className="nx-font-m nx-premium-features-list-item">{ __('Grab instant attention with flashing tabs','notificationx') }</li>
            </ul>
            <a target="_blank" href="https://notificationx.com/#pricing" className="nx-btn nx-btn-primary nx-pop-up-btn">
              <svg width="17" height="13" viewBox="0 0 17 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1.03347 9.30151C0.691298 7.07739 0.349129 4.85332 0.00695999 2.6292C-0.0689213 2.13616 0.49206 1.7999 0.891097 2.09921C1.95715 2.89875 3.02314 3.69823 4.08919 4.49776C4.44019 4.76101 4.94025 4.67535 5.18359 4.31028L7.84607 0.316533C8.12743 -0.105511 8.74753 -0.105511 9.0289 0.316533L11.6914 4.31028C11.9347 4.67535 12.4348 4.76096 12.7858 4.49776C13.8518 3.69823 14.9178 2.89875 15.9839 2.09921C16.3829 1.7999 16.9439 2.13616 16.8681 2.6292C16.5259 4.85332 16.1837 7.07739 15.8416 9.30151H1.03347Z" fill="white"/>
                <path d="M15.0673 13.0031H1.80328C1.37691 13.0031 1.03125 12.6575 1.03125 12.2311V10.5352H15.8393V12.2311C15.8393 12.6575 15.4936 13.0031 15.0673 13.0031Z" fill="white"/>
              </svg>
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
          <button
            className='nx-dismiss'
            onClick={handleDismiss}
            disabled={isLoading}
          >
            <span className="nx-cancel-button">
              {isLoading ? __('Dismissing...', 'notificationx') : __('Dismiss', 'notificationx')}
            </span>
          </button>
        </div>
      </div>
  )
}

export default InitialPopup
