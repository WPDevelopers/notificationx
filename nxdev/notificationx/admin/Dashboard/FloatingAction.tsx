import React, { useEffect, useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useLocation } from 'react-router-dom'
import NxIcon from '../../components/NxIcon'

// NotificationX bell drawn for the purple launcher: white bell, brand-purple
// inner arc, green check. CSS rings the bell and draws the check, like the
// old animated GIF (which loaded from notificationx.com).
const BellMark = () => (
    <svg className="nx-help-fab__mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 150" width="30" height="30" aria-hidden="true" focusable="false">
        <g className="nx-help-fab__bell">
            <path className="nx-help-fab__bell-body" d="M51.7 136h43.2c-.8 5-10.6 11.4-18.9 12.3-9.7 1-21.3-4.8-24.3-12.3zM141.8 115.9c-.9-.1-1.8 0-2.7 0h-6.9V112c0-17.7.1-35.5-.1-53.2 0-4-.4-8.1-1.2-12-6.6-32.7-39.5-53.3-71.8-45.1-25.8 6.5-44.4 30-44.3 56.8.1 16.1 0 32.2 0 48.4v9c-2 0-7.6 0-9.6.1-2.9.2-4.9 2.4-4.9 5.4 0 2.8 1.9 4.9 4.8 5.1.8.1 1.6.1 2.4.1h131.9c.8 0 1.6 0 2.4-.1 2.8-.3 4.8-2.4 4.8-5.1.1-2.9-2-5.2-4.8-5.5z" />
            <circle className="nx-help-fab__bell-accent" cx="107.3" cy="97" r="5.9" />
            <path className="nx-help-fab__bell-accent" d="M112.6 52.9c0-.6-.1-1.1-.2-1.6-3.6-17.7-18.8-30.7-36.7-31.7h-.1c-.8 0-1.5-.1-2.3-.1h-.3c-.8 0-1.5 0-2.3.1h-.1C52.8 20.7 37.6 33.7 34 51.3c-.1.5-.2 1-.2 1.5l.1.1c-.3 2.1-.5 4.2-.5 6.4v42.9l12.2 13.2V59.3c0-12.4 8.7-23.6 20.9-26.7 1-.3 2-.4 3-.6.2 0 .3 0 .5-.1.4 0 .8-.1 1.2-.1.6 0 1.1-.1 1.7-.1h.6c.6 0 1.1 0 1.7.1.4 0 .8.1 1.2.1.2 0 .3 0 .5.1 1 .1 2 .3 3 .6 12.1 3.1 20.9 14.3 20.9 26.7v27.1l12.2-8.2V59.3c.2-2.1 0-4.3-.4-6.4z" />
        </g>
        <path className="nx-help-fab__check" pathLength={1} d="M26.5 82.5 51.5 109.5 141.5 49" />
    </svg>
);

type HelpLink = {
    key: string;
    icon: string;
    title: string;
    desc: string;
    url: string;
};

// Floating help button, shown on every NotificationX page except the builder.
const FloatingAction = ({ isPro }) => {
    const location = useLocation();
    const containsNxEdit = location?.search?.includes("page=nx-edit");
    const [showAction, setShowAction] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const buttonRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        if (!showAction) return;
        const onClick = (event: MouseEvent) => {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setShowAction(false);
            }
        };
        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setShowAction(false);
                buttonRef.current?.focus();
            }
        };
        document.addEventListener('mousedown', onClick);
        document.addEventListener('keydown', onKey);
        return () => {
            document.removeEventListener('mousedown', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [showAction]);

    if (containsNxEdit) {
        return null;
    }

    const links: HelpLink[] = [
        ...(!isPro ? [{
            key  : 'pro',
            icon : 'crown',
            title: __('Unlock Pro Features', 'notificationx'),
            desc : __('Get every notification type and advanced analytics.', 'notificationx'),
            url  : 'https://notificationx.com/#pricing',
        }] : []),
        {
            key  : 'support',
            icon : 'lifebuoy',
            title: __('Get Support', 'notificationx'),
            desc : __('Chat with our support team.', 'notificationx'),
            url  : 'https://notificationx.com/support/?support=chat',
        },
        {
            key  : 'feature',
            icon : 'bulb',
            title: __('Suggest a Feature', 'notificationx'),
            desc : __('Tell us what would make NotificationX better.', 'notificationx'),
            url  : 'https://wpdeveloper.com/support/new-ticket/',
        },
        {
            key  : 'community',
            icon : 'users',
            title: __('Join Our Community', 'notificationx'),
            desc : __('Connect with other users on Facebook.', 'notificationx'),
            url  : 'https://www.facebook.com/groups/NotificationX.Community/',
        },
    ];

    return (
        <div className={`nx-help-fab${showAction ? ' is-open' : ''}`} ref={wrapperRef}>
            <div
                id="nx-help-fab-panel"
                className="nx-help-fab__panel"
                role="dialog"
                aria-label={ __('Help & resources', 'notificationx') }
                hidden={!showAction}
            >
                <div className="nx-help-fab__header">
                    <strong>{ __('Need a hand?', 'notificationx') }</strong>
                    <span>{ __('Resources and support for NotificationX.', 'notificationx') }</span>
                </div>
                <ul className="nx-help-fab__list">
                    { links.map((link) => (
                        <li key={link.key}>
                            <a className={`nx-help-fab__item is-${link.key}`} href={link.url} target="_blank" rel="noopener noreferrer">
                                <span className="nx-help-fab__icon"><NxIcon name={link.icon} size={18} /></span>
                                <span className="nx-help-fab__text">
                                    <strong>{ link.title }</strong>
                                    <span>{ link.desc }</span>
                                </span>
                                <NxIcon name="external" size={14} className="nx-help-fab__external" />
                            </a>
                        </li>
                    )) }
                </ul>
            </div>

            <button
                type="button"
                ref={buttonRef}
                className="nx-help-fab__toggle"
                aria-expanded={showAction}
                aria-controls="nx-help-fab-panel"
                aria-label={ showAction ? __('Close help menu', 'notificationx') : __('Open help menu', 'notificationx') }
                title={ showAction ? __('Close', 'notificationx') : __('Help & resources', 'notificationx') }
                onClick={() => setShowAction(!showAction)}
            >
                <span className="nx-help-fab__open-icon"><BellMark /></span>
                <NxIcon name="close" size={22} strokeWidth={2.25} className="nx-help-fab__close-icon" />
            </button>
        </div>
    )
}

export default FloatingAction
