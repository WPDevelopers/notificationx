import React, { useEffect, useState } from 'react';
import ReactModal from 'react-modal';
import { __, _n, sprintf } from '@wordpress/i18n';
import nxHelper from '../../core/functions';
import { closedHere, groupReasons, Reason, verdict } from './logic';

/**
 * "Why isn't it showing?" for one notification (All NotificationX → row menu).
 *
 * Read-only: the server lists the reasons; this modal adds what it finds in
 * the admin's own browser (close markers) and changes nothing.
 */
const GROUP_TITLES = {
    blocked: __('Stops it from showing', 'notificationx'),
    limited: __('Shows only sometimes or somewhere', 'notificationx'),
    note: __('Good to know', 'notificationx'),
    ok: __('Checked and fine', 'notificationx'),
};

const ReasonItem = ({ reason }: { reason: Reason }) => (
    <li className={`nx-wns-reason nx-wns-reason--${reason.level}`} data-key={reason.key}>
        <span className="nx-wns-reason__icon" aria-hidden="true" />
        <div className="nx-wns-reason__body">
            <strong className="nx-wns-reason__title">{reason.title}</strong>
            {reason.detail ? <p className="nx-wns-reason__detail">{reason.detail}</p> : null}
            {reason.fix && reason.fix.url ? (
                <a className="nx-wns-reason__fix" href={reason.fix.url}>
                    {reason.fix.label}
                    <span aria-hidden="true"> →</span>
                </a>
            ) : null}
        </div>
    </li>
);

const WhyNotShowingModal = ({ id, title = '', onClose, fetcher = (path) => nxHelper.get(path), env = null }) => {
    const [data, setData] = useState(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        let live = true;
        Promise.resolve(fetcher(`why-not-showing/${id}`)).then((res: any) => {
            if (!live) {
                return;
            }
            if (res && Array.isArray(res.reasons)) {
                setData(res);
            } else {
                setFailed(true);
            }
        });
        return () => {
            live = false;
        };
    }, [id]);

    const browserEnv = env || {
        cookie: typeof document !== 'undefined' ? document.cookie : '',
        local: typeof window !== 'undefined' ? window.localStorage : null,
        session: typeof window !== 'undefined' ? window.sessionStorage : null,
    };
    const groups = groupReasons(data?.reasons);
    const closed = data ? closedHere(data.browser, browserEnv) : [];
    if (closed.length) {
        groups.limited.unshift({
            key: 'browser',
            level: 'limited',
            title: __('You closed it in this browser', 'notificationx'),
            detail: __('It stays hidden for you here; visitors who didn’t close it still see it. To see it again, open the site in a private window.', 'notificationx'),
            fix: null,
        });
    }
    const state = data ? verdict(groups) : null;
    const name = data?.title || title;
    const summary = {
        blocked: sprintf(_n('%d thing stops it from showing', '%d things stop it from showing', groups.blocked.length, 'notificationx'), groups.blocked.length),
        limited: __('Nothing stops it, but it shows only for some visitors, pages or times', 'notificationx'),
        ok: __('Nothing stops it from showing', 'notificationx'),
    };
    const appElement = typeof document !== 'undefined' ? document.getElementById('notificationx') : null;

    return (
        <ReactModal
            isOpen={true}
            onRequestClose={onClose}
            className="nx-wns-modal"
            overlayClassName="nx-wns-overlay"
            contentLabel={__('Why isn’t it showing?', 'notificationx')}
            aria={{ labelledby: 'nx-wns-heading' }}
            {...(appElement ? { appElement } : { ariaHideApp: false })}
        >
            <div className="nx-wns-header">
                <div>
                    <h2 id="nx-wns-heading">{__('Why isn’t it showing?', 'notificationx')}</h2>
                    <p className="nx-wns-subtitle">{name ? `#${id} · ${name}` : `#${id}`}</p>
                </div>
                <button type="button" className="nx-wns-close" onClick={onClose} aria-label={__('Close', 'notificationx')}>
                    ×
                </button>
            </div>
            <div className="nx-wns-content">
                {!data && !failed ? <p className="nx-wns-loading">{__('Checking…', 'notificationx')}</p> : null}
                {failed ? <p className="nx-wns-error" role="alert">{__('The checks couldn’t run. Reload the page and try again.', 'notificationx')}</p> : null}
                {data ? (
                    <>
                        <p className={`nx-wns-verdict nx-wns-verdict--${state}`} role="status">
                            {summary[state]}
                        </p>
                        {(['blocked', 'limited', 'note'] as const).map((level) =>
                            groups[level].length ? (
                                <section key={level} className={`nx-wns-group nx-wns-group--${level}`}>
                                    <h3>{GROUP_TITLES[level]}</h3>
                                    <ul>
                                        {groups[level].map((reason) => (
                                            <ReasonItem key={`${reason.key}-${reason.title}`} reason={reason} />
                                        ))}
                                    </ul>
                                </section>
                            ) : null
                        )}
                        {groups.ok.length ? (
                            <details className="nx-wns-group nx-wns-group--ok">
                                <summary>
                                    {GROUP_TITLES.ok} <span className="nx-wns-count">{groups.ok.length}</span>
                                </summary>
                                <ul>
                                    {groups.ok.map((reason) => (
                                        <ReasonItem key={`${reason.key}-${reason.title}`} reason={reason} />
                                    ))}
                                </ul>
                            </details>
                        ) : null}
                    </>
                ) : null}
            </div>
            <div className="nx-wns-footer">
                <span>{__('These checks only read settings; nothing is changed.', 'notificationx')}</span>
                <button type="button" className="nx-wns-button" onClick={onClose}>
                    {__('Close', 'notificationx')}
                </button>
            </div>
        </ReactModal>
    );
};

export default WhyNotShowingModal;
