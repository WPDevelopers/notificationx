import React, { useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom'
import { applyFilters } from '@wordpress/hooks'
import Logo from './Logo';
import NxIcon, { iconForPage } from './NxIcon';
import { useNotificationXContext } from '../hooks';

type MenuItem = { slug: string; title: string };

// Used when the localized menu is missing, e.g. after a REST refresh of the context.
const FALLBACK_MENU: MenuItem[] = [
    { slug: 'nx-dashboard', title: __('Dashboard', 'notificationx') },
    { slug: 'nx-admin', title: __('All NotificationX', 'notificationx') },
    { slug: 'nx-analytics', title: __('Analytics', 'notificationx') },
    { slug: 'nx-builder', title: __('Quick Builder', 'notificationx') },
    { slug: 'nx-settings', title: __('Settings', 'notificationx') },
];

// Shorter labels for the top bar; other pages keep their sidebar title.
const NAV_LABELS: Record<string, string> = {
    'nx-admin': __('Notifications', 'notificationx'),
};

// "Add New" is the primary button, not a tab.
const HIDDEN_FROM_NAV = ['nx-edit'];

const Version = ({ version }) => {
    return <span className="nx-topbar__version" title={__("NotificationX version", 'notificationx')}>v{version}</span>
}

const isInternalPage = (slug: string) => !/\.php|:\/\//.test(slug);

// Keep the WordPress sidebar highlight in step with in-app navigation.
export const syncSidebar = (page: string) => {
    document.querySelectorAll('#toplevel_page_nx-admin .wp-submenu li').forEach((li) => {
        const link = li.querySelector('a');
        if (!link) return;
        const match = (link.getAttribute('href') || '').match(/[?&]page=([^&#]+)/);
        const current = !!match && match[1] === page;
        li.classList.toggle('current', current);
        link.classList.toggle('current', current);
    });
};

// `addNew` and `context` are accepted for backwards compatibility; the bar
// decides from the current page whether to show "Add New".
const Header = (_props: { addNew?: boolean; context?: any }) => {
    const builderContext = useNotificationXContext();
    const location = useLocation();
    const page = new URLSearchParams(location.search).get('page') || 'nx-admin';
    const activeSlug = page === 'nx-edit' ? 'nx-admin' : page;
    const pro_version = builderContext?.pro_version;
    const version = builderContext?.version;
    const isPro = !!builderContext?.is_pro_active;

    const menu: MenuItem[] = (Array.isArray(builderContext?.admin_menu) && builderContext.admin_menu.length
        ? builderContext.admin_menu
        : FALLBACK_MENU
    ).filter((item: MenuItem) => !HIDDEN_FROM_NAV.includes(item.slug));

    const showAddNew = !builderContext?.createRedirect && page !== 'nx-edit';

    useEffect(() => {
        syncSidebar(page);
    }, [page]);

    return (
        <header className="nx-topbar">
            <div className="nx-topbar__inner">
                <Link className="nx-topbar__brand" to={{ pathname: '/admin.php', search: '?page=nx-dashboard' }} aria-label={__('NotificationX Dashboard', 'notificationx')}>
                    <Logo width="148px" />
                </Link>

                <nav className="nx-topbar__nav" aria-label={__('NotificationX menu', 'notificationx')}>
                    {menu.map((item) => {
                        const isActive = item.slug === activeSlug;
                        const label = NAV_LABELS[item.slug] || item.title;
                        const content = (
                            <>
                                <NxIcon name={iconForPage(item.slug)} size={16} />
                                <span>{label}</span>
                            </>
                        );
                        const className = `nx-topbar__link${isActive ? ' is-active' : ''}`;
                        return isInternalPage(item.slug) ? (
                            <Link
                                key={item.slug}
                                className={className}
                                aria-current={isActive ? 'page' : undefined}
                                to={{ pathname: '/admin.php', search: `?page=${item.slug}` }}
                            >
                                {content}
                            </Link>
                        ) : (
                            <a key={item.slug} className={className} href={item.slug}>
                                {content}
                            </a>
                        );
                    })}
                </nav>

                <div className="nx-topbar__actions">
                    <div className="nx-topbar__meta">
                        {applyFilters('notificationx_header', <Version version={version} />)}
                        {typeof pro_version === 'string' && (
                            <span className="nx-topbar__version is-pro" title={__("NotificationX Pro version", 'notificationx')}>
                                {__('Pro', 'notificationx')} v{pro_version}
                            </span>
                        )}
                    </div>
                    <a
                        className="nx-topbar__icon-btn"
                        href="https://notificationx.com/docs/"
                        target="_blank"
                        rel="noopener noreferrer"
                        title={__('Documentation', 'notificationx')}
                        aria-label={__('Documentation', 'notificationx')}
                    >
                        <NxIcon name="help" size={18} />
                    </a>
                    {!isPro && (
                        <a
                            className="nx-button nx-button--upgrade"
                            href="https://notificationx.com/#pricing"
                            title={__('Upgrade to Pro', 'notificationx')}
                            aria-label={__('Upgrade to Pro', 'notificationx')}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <NxIcon name="crown" size={16} />
                            <span>{__('Upgrade', 'notificationx')}</span>
                        </a>
                    )}
                    {showAddNew && (
                        <Link className="nx-button nx-button--primary nx-add-new-btn" to={{ pathname: '/admin.php', search: '?page=nx-edit' }}>
                            <NxIcon name="plus" size={16} strokeWidth={2.25} />
                            <span>{__('Add New', 'notificationx')}</span>
                        </Link>
                    )}
                </div>
            </div>
        </header>
    )
}
export default Header;
