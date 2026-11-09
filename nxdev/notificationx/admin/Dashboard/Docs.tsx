import { __ } from '@wordpress/i18n'
import React from 'react'
import NxIcon from '../../components/NxIcon'

type HelpLink = {
  key: string;
  icon: string;
  title: string;
  desc: string;
  action: string;
  url: string;
};

// Help & Community panel: support on the left, resources on the right.
const Docs = ({ props, context }) => {
  const links: HelpLink[] = [
    {
      key   : 'docs',
      icon  : 'book',
      title : __('Explore Our Knowledge Base', 'notificationx'),
      desc  : __('Step-by-step guides for every notification type and integration.', 'notificationx'),
      action: __('Documentation', 'notificationx'),
      url   : 'https://notificationx.com/docs/',
    },
    {
      key   : 'community',
      icon  : 'users',
      title : __('Join Our Community', 'notificationx'),
      desc  : __('Share ideas and get tips from other NotificationX users on Facebook.', 'notificationx'),
      action: __('Join Our Community', 'notificationx'),
      url   : 'https://www.facebook.com/groups/NotificationX.Community/',
    },
    {
      key   : 'love',
      icon  : 'heart',
      title : __('Show Your Love', 'notificationx'),
      desc  : __('Enjoying NotificationX? A quick review helps us keep improving it.', 'notificationx'),
      action: __('Leave a Review', 'notificationx'),
      url   : 'https://wpdeveloper.com/review-notificationx',
    },
  ];

  return (
    <section className='nx-card nx-dash-help' aria-labelledby="nx-dash-help-title">
      <div className="nx-dash-help__support">
        <span className="nx-dash-help__support-icon" aria-hidden="true">
          <NxIcon name="lifebuoy" size={22} />
        </span>
        <h2 id="nx-dash-help-title">{ __('Need Any Help?', 'notificationx') }</h2>
        <p>{ __('Our support team is here to help with setup, questions and issues, anytime you need.', 'notificationx') }</p>
        <div className="nx-dash-help__support-actions">
          <a className="nx-button nx-button--primary" href="https://notificationx.com/support/?support=chat" target="_blank" rel="noopener noreferrer">
            <NxIcon name="message" size={16} />
            { __('Contact Us', 'notificationx') }
          </a>
          <a className="nx-button nx-button--secondary" href="https://wpdeveloper.com/support/new-ticket/" target="_blank" rel="noopener noreferrer">
            <NxIcon name="bug" size={16} />
            { __('Report a Bug', 'notificationx') }
          </a>
        </div>
      </div>

      <ul className="nx-dash-help__links">
        { links.map((link) => (
          <li key={link.key}>
            <a className="nx-dash-help__link" href={link.url} target="_blank" rel="noopener noreferrer">
              <span className={`nx-dash-help__icon is-${link.key}`} aria-hidden="true">
                <NxIcon name={link.icon} size={20} />
              </span>
              <span className="nx-dash-help__text">
                <strong>{ link.title }</strong>
                <span>{ link.desc }</span>
              </span>
              <span className="nx-dash-help__action">
                { link.action }
                <NxIcon name="arrowRight" size={15} />
              </span>
            </a>
          </li>
        )) }
      </ul>
    </section>
  )
}

export default Docs
