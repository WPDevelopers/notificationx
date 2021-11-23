import React from 'react'
import { __ } from '@wordpress/i18n'

const Documentation = ({ assetsUrl }) => {
    return (
        <div className="nx-settings-documentation">
            <div className="nx-settings-row">
                <div className="nx-admin-block nx-admin-block-docs">
                    <header className="nx-admin-block-header">
                        <div className="nx-admin-block-header-icon">
                            <img src={`${assetsUrl.admin}images/icons/icon-documentation.svg`} alt="notificationx-documentation" />
                        </div>
                        <h4 className="nx-admin-title">{__('Documentation', 'notificationx')}</h4>
                    </header>
                    <div className="nx-admin-block-content">
                        <p>{__('Get started by spending some time with the documentation to get familiar with NotificationX. Build awesome websites for you or your clients with ease.', 'notificationx')}</p>
                        <a rel="nofollow" href="https://notificationx.com/docs/" className="nx-button" target="_blank">{__('Documentation', 'notificationx')}</a>
                    </div>
                </div>
                <div className="nx-admin-block nx-admin-block-contribute">
                    <header className="nx-admin-block-header">
                        <div className="nx-admin-block-header-icon">
                            <img src={`${assetsUrl.admin}images/icons/icon-contribute.svg`} alt="notificationx-contribute" />
                        </div>
                        <h4 className="nx-admin-title">{__('Contribute to NotificationX', 'notificationx')}</h4>
                    </header>
                    <div className="nx-admin-block-content">
                        <p>{__('You can contribute to make NotificationX better reporting bugs, creating issues, pull requests at', 'notificationx')} <a rel="nofollow" target="_blank" href="https://github.com/WPDevelopers/notificationx">GitHub.</a></p>
                        <a rel="nofollow" href="https://github.com/WPDevelopers/notificationx/issues/new" className="nx-button" target="_blank">{__('Report a bug', 'notificationx')}</a>
                    </div>
                </div>
                <div className="nx-admin-block nx-admin-block-need-help">
                    <header className="nx-admin-block-header">
                        <div className="nx-admin-block-header-icon">
                            <img src={`${assetsUrl.admin}images/icons/icon-need-help.svg`} alt="notificationx-help" />
                        </div>
                        <h4 className="nx-admin-title">{__('Need Help?', 'notificationx')}</h4>
                    </header>
                    <div className="nx-admin-block-content">
                        <p>{__('Stuck with something? Get help from live chat or support ticket.', 'notificationx')}</p>
                        <a rel="nofollow" href="https://wpdeveloper.com" className="nx-button" target="_blank">{__('Initiate a Chat', 'notificationx')}</a>
                    </div>
                </div>
                <div className="nx-admin-block nx-admin-block-community">
                    <header className="nx-admin-block-header">
                        <div className="nx-admin-block-header-icon">
                            <img src={`${assetsUrl.admin}images/icons/icon-show-love.svg`} alt="notificationx-commuinity" />
                        </div>
                        <h4 className="nx-admin-title">{__('Show Your Love', 'notificationx')}</h4>
                    </header>
                    <div className="nx-admin-block-content">
                        <p>{__('We love to have you in NotificationX family. We are making it more awesome everyday. Take your 2 minutes to review the plugin and spread the love to encourage us to keep it going.', 'notificationx')}</p>
                        <a rel="nofollow" href="https://wpdeveloper.com/review-notificationx" className="nx-button" target="_blank">{__('Leave a Review', 'notificationx')}</a>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default Documentation;