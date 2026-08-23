import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useBuilderContext, withLabel } from 'quickbuilder';
import nxHelper from '../core/functions';
import nxToast from '../core/ToasterMsg';

type Page = {
    page_id: string;
    page_name: string;
    picture: string;
    link: string;
    status: string;
    rating_overall: number | null;
    rating_count: number | null;
    connected_at?: string;
    last_synced_at?: string;
    last_error?: string;
};

type State = {
    configured: boolean;
    https: boolean;
    redirect_uri: string;
    pages: Page[];
};

type FieldValue = { page_id: string; page_name: string } | null;

const RETURN_PARAMS = ['nx_fb_status', 'nx_fb_error'];

const ERROR_TEXT: Record<string, string> = {
    facebook_oauth_denied: __('Facebook login was cancelled or denied.', 'notificationx'),
    facebook_oauth_invalid_code: __('Facebook rejected the login. Check the App ID, App Secret and Redirect URI in your Meta app.', 'notificationx'),
    facebook_permission_denied: __('Facebook denied the required permissions. Please try again and grant access to your Pages.', 'notificationx'),
    facebook_network_error: __('Facebook could not be reached. Please try again.', 'notificationx'),
    facebook_connection_expired: __('The Facebook connection has expired. Please reconnect.', 'notificationx'),
    facebook_page_unavailable: __('The Facebook Page is no longer accessible with this connection.', 'notificationx'),
    facebook_rate_limited: __('Facebook is rate limiting requests. Please try again later.', 'notificationx'),
    facebook_no_pages: __('Facebook returned no Pages. Make sure your account manages a Page and you granted access during login.', 'notificationx'),
    facebook_not_configured: __('Save your Meta App ID and App Secret first.', 'notificationx'),
    insecure_site: __('Facebook requires an HTTPS site. Please enable HTTPS and try again.', 'notificationx'),
    invalid_state: __('The Facebook login session expired. Please try again.', 'notificationx'),
};

const errorMessage = (err: any, fallback?: string) =>
    ERROR_TEXT[err?.code] || ERROR_TEXT[err?.data?.code] || err?.message || fallback || __('Something went wrong. Please try again.', 'notificationx');

const currentUrlWithout = (params: string[]) => {
    const url = new URL(window.location.href);
    params.forEach((p) => url.searchParams.delete(p));
    return url.toString();
};

const statusLabel = (status: string) => {
    switch (status) {
        case 'active':
            return __('Connected', 'notificationx');
        case 'expired':
            return __('Expired — reconnect', 'notificationx');
        case 'permission_denied':
            return __('Permission denied — reconnect', 'notificationx');
        case 'page_unavailable':
            return __('Page unavailable', 'notificationx');
        default:
            return status;
    }
};

/**
 * Facebook Page connection (site owner's own Meta app).
 *
 * mode="settings" — API Integrations tab: connect with Facebook, list every
 *                   Page the login granted, refresh / disconnect.
 * mode="builder"  — Content step: pick the Page for this campaign. Stores
 *                   {page_id, page_name} as the field value.
 *
 * The login is a plain redirect: POST facebook-reviews/oauth-start returns the
 * Facebook dialog URL, the browser goes there and comes back through
 * admin-post.php to the same admin page with ?nx_fb_status=ok|error.
 */
const FacebookReviewsConnection = (props) => {
    const builderContext = useBuilderContext();
    const mode: 'builder' | 'settings' = props?.mode === 'settings' ? 'settings' : 'builder';
    const fieldName: string = props?.name;

    const [state, setState] = useState<State>({ configured: false, https: true, redirect_uri: '', pages: [] });
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState('');

    const value: FieldValue = useMemo(() => {
        const v = mode === 'builder' ? builderContext?.values?.[fieldName] : null;
        return v && typeof v === 'object' && v.page_id ? v : null;
    }, [builderContext?.values?.[fieldName], mode]);

    const setValue = (page: Page | null) => {
        if (mode !== 'builder') return;
        builderContext.setFieldValue(fieldName, page ? { page_id: page.page_id, page_name: page.page_name } : null);
    };

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res: any = await nxHelper.get('facebook-reviews/pages');
            if (res && Array.isArray(res.pages)) {
                setState(res);
            }
        } finally {
            setLoading(false);
        }
    }, []);

    // Returning from Facebook?
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const status = params.get('nx_fb_status');
        if (status) {
            const err = params.get('nx_fb_error') || '';
            window.history.replaceState({}, '', currentUrlWithout(RETURN_PARAMS));
            if (status === 'ok') {
                nxToast.connected(__('Facebook Pages connected.', 'notificationx'));
            } else {
                nxToast.error(ERROR_TEXT[err] || __('Facebook login failed. Please try again.', 'notificationx'));
            }
        }
        load();
    }, []);

    const startLogin = async () => {
        if (busy) return;
        if (mode === 'builder' && !window.confirm(__('You will be sent to Facebook to log in. Unsaved changes in this campaign will be lost — continue?', 'notificationx'))) {
            return;
        }
        setBusy('login');
        try {
            const res: any = await nxHelper.post('facebook-reviews/oauth-start', { return_url: currentUrlWithout(RETURN_PARAMS) }, { get_error: true });
            if (res?.authorize_url) {
                window.location.assign(res.authorize_url);
                return;
            }
            nxToast.error(errorMessage(res, __('Could not start the Facebook login.', 'notificationx')));
        } catch (err) {
            nxToast.error(errorMessage(err));
        }
        setBusy('');
    };

    const refreshPage = async (page: Page) => {
        if (busy) return;
        setBusy('refresh:' + page.page_id);
        try {
            const res: any = await nxHelper.post('facebook-reviews/refresh', { page_id: page.page_id }, { get_error: true });
            if (res?.ok) {
                setState((s) => ({ ...s, pages: res.pages }));
                nxToast.connected(__('Page rating refreshed.', 'notificationx'));
            } else {
                nxToast.error(errorMessage(res));
                await load();
            }
        } finally {
            setBusy('');
        }
    };

    const disconnect = async (page: Page | null) => {
        if (busy) return;
        const question = page
            ? sprintf(/* translators: %s: page name */ __('Disconnect %s? Campaigns using it will stop refreshing.', 'notificationx'), page.page_name)
            : __('Disconnect all Facebook Pages? Campaigns using them will stop refreshing.', 'notificationx');
        if (!window.confirm(question)) return;
        setBusy(page ? 'disconnect:' + page.page_id : 'disconnect-all');
        try {
            const res: any = await nxHelper.post('facebook-reviews/disconnect', page ? { page_id: page.page_id } : {}, { get_error: true });
            if (res?.ok) {
                setState((s) => ({ ...s, pages: res.pages }));
                if (!page || value?.page_id === page.page_id) setValue(null);
                nxToast.connected(page ? __('Page disconnected.', 'notificationx') : __('All Pages disconnected.', 'notificationx'));
            } else {
                nxToast.error(errorMessage(res));
            }
        } finally {
            setBusy('');
        }
    };

    const renderPage = (page: Page) => {
        const selected = value?.page_id === page.page_id;
        const isActive = page.status === 'active';
        return (
            <li key={page.page_id} className={`nx-fbr-page ${selected ? 'is-selected' : ''} ${isActive ? '' : 'is-inactive'}`}>
                {mode === 'builder' && (
                    <input type="radio" name={`${fieldName}_pick`} checked={selected} disabled={!isActive} onChange={() => setValue(page)} />
                )}
                {page.picture ? <img className="nx-fbr-page__picture" src={page.picture} alt="" /> : <span className="nx-fbr-page__picture is-empty" />}
                <div className="nx-fbr-page__body">
                    <strong className="nx-fbr-page__name">{page.page_name || page.page_id}</strong>
                    <span className={`nx-fbr-page__status is-${page.status}`}>{statusLabel(page.status)}</span>
                    <span className="nx-fbr-page__meta">
                        {page.rating_count
                            ? sprintf(/* translators: 1: rating, 2: count */ __('%1$s ★ · %2$s ratings', 'notificationx'), page.rating_overall ?? '–', page.rating_count)
                            : page.last_synced_at
                                ? __('No public rating yet', 'notificationx')
                                : __('Not refreshed yet', 'notificationx')}
                    </span>
                </div>
                {mode === 'settings' && (
                    <div className="nx-fbr-page__actions">
                        <button type="button" className="wprf-btn nx-fbr-btn is-secondary" disabled={!!busy} onClick={() => refreshPage(page)}>
                            {busy === 'refresh:' + page.page_id ? __('Refreshing...', 'notificationx') : __('Refresh', 'notificationx')}
                        </button>
                        <button type="button" className="wprf-btn nx-fbr-btn is-link" disabled={!!busy} onClick={() => disconnect(page)}>
                            {busy === 'disconnect:' + page.page_id ? __('Disconnecting...', 'notificationx') : __('Disconnect', 'notificationx')}
                        </button>
                    </div>
                )}
            </li>
        );
    };

    const canConnect = state.configured && state.https;

    return (
        <div className={`nx-fbr nx-fbr--${mode}`}>
            {loading ? (
                <p className="nx-fbr-hint">{__('Loading…', 'notificationx')}</p>
            ) : state.pages.length > 0 ? (
                <ul className="nx-fbr-pages">{state.pages.map(renderPage)}</ul>
            ) : (
                <p className="nx-fbr-hint">{__('No Facebook Page connected yet.', 'notificationx')}</p>
            )}

            {mode === 'builder' && value && !loading && !state.pages.some((p) => p.page_id === value.page_id) && (
                <p className="nx-fbr-hint nx-fbr-hint--warning">
                    {sprintf(/* translators: %s: page name */ __('This campaign is linked to "%s", which is no longer connected. Connect it again.', 'notificationx'), value.page_name || value.page_id)}
                </p>
            )}

            {!loading && !state.configured && (
                <p className="nx-fbr-hint nx-fbr-hint--warning">
                    {mode === 'settings'
                        ? __('Enter your Meta App ID and App Secret above and click "Validate & Save" before connecting.', 'notificationx')
                        : __('Set up your Meta app in Settings > API Integrations > Facebook Reviews first.', 'notificationx')}
                </p>
            )}
            {!loading && state.configured && !state.https && (
                <p className="nx-fbr-hint nx-fbr-hint--warning">{__('Facebook only accepts HTTPS redirect URLs. Enable HTTPS on this site to connect a Page.', 'notificationx')}</p>
            )}

            <div className="nx-fbr-actions">
                <button type="button" className="wprf-btn nx-fbr-btn nx-fbr-btn--facebook" disabled={!!busy || loading || !canConnect} onClick={startLogin}>
                    {busy === 'login'
                        ? __('Redirecting to Facebook...', 'notificationx')
                        : state.pages.length > 0
                            ? __('Reconnect with Facebook', 'notificationx')
                            : __('Connect with Facebook', 'notificationx')}
                </button>
                {mode === 'settings' && state.pages.length > 1 && (
                    <button type="button" className="wprf-btn nx-fbr-btn is-link" disabled={!!busy} onClick={() => disconnect(null)}>
                        {busy === 'disconnect-all' ? __('Disconnecting...', 'notificationx') : __('Disconnect all', 'notificationx')}
                    </button>
                )}
            </div>
            {mode === 'settings' && (
                <p className="nx-fbr-hint">
                    {__('Logging in grants access to every Page you choose in the Facebook dialog. Page access tokens are stored encrypted on this site; Facebook does not provide individual reviews, only the overall rating and count.', 'notificationx')}
                </p>
            )}
        </div>
    );
};

export default withLabel(FacebookReviewsConnection);
