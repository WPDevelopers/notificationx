import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { useBuilderContext, withLabel } from 'quickbuilder';
import nxHelper from '../core/functions';
import nxToast from '../core/ToasterMsg';

type Connection = {
    connection_id: string;
    provider: string;
    page_id: string;
    page_name: string;
    status: string;
    rating_overall: number | null;
    rating_count: number | null;
    individual_reviews: boolean;
    last_synced_at?: string | null;
    last_sync_error?: string | null;
};

type FieldValue = { connection_id: string; page_id: string; page_name: string } | null;

const RETURN_PARAMS = ['nx_fb_session', 'nx_fb_status', 'nx_fb_error'];

const ERROR_TEXT: Record<string, string> = {
    facebook_oauth_denied: __('Facebook login was cancelled or denied.', 'notificationx'),
    facebook_oauth_invalid_code: __('Facebook login could not be completed. Please try again.', 'notificationx'),
    facebook_permission_denied: __('Facebook denied the required permissions. Please try again and grant access to the Page.', 'notificationx'),
    facebook_network_error: __('Facebook could not be reached. Please try again.', 'notificationx'),
    facebook_connection_expired: __('The Facebook connection has expired. Please reconnect the Page.', 'notificationx'),
    facebook_page_unavailable: __('The Facebook Page is no longer accessible with this connection.', 'notificationx'),
};

const errorMessage = (err: any, fallback?: string) =>
    ERROR_TEXT[err?.data?.code] || err?.message || fallback || __('Something went wrong. Please try again.', 'notificationx');

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
 * Facebook Page connection.
 *
 * mode="builder"  — Content step: pick (or connect) the Page for this campaign.
 *                   Stores {connection_id, page_id, page_name} as the field value.
 * mode="settings" — API Integrations tab: manage every connected Page.
 *
 * The Facebook login runs on the NotificationX API: we ask our own REST route
 * for an authorize URL, send the browser there, and come back with
 * ?nx_fb_session=…&nx_fb_status=ok, after which the Page picker appears.
 */
const FacebookReviewsConnection = (props) => {
    const builderContext = useBuilderContext();
    const mode: 'builder' | 'settings' = props?.mode === 'settings' ? 'settings' : 'builder';
    const fieldName: string = props?.name;

    const [connections, setConnections] = useState<Connection[]>([]);
    const [site, setSite] = useState<any>({});
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState('');
    const [pages, setPages] = useState<{ id: string; name: string }[] | null>(null);
    const [sessionId, setSessionId] = useState('');
    const [selectedPage, setSelectedPage] = useState('');

    const value: FieldValue = useMemo(() => {
        const v = mode === 'builder' ? builderContext?.values?.[fieldName] : null;
        return v && typeof v === 'object' && v.connection_id ? v : null;
    }, [builderContext?.values?.[fieldName], mode]);

    const setValue = (conn: Connection | null) => {
        if (mode !== 'builder') return;
        builderContext.setFieldValue(
            fieldName,
            conn ? { connection_id: conn.connection_id, page_id: conn.page_id, page_name: conn.page_name } : null
        );
    };

    const load = useCallback(async (fresh = false) => {
        setLoading(true);
        try {
            const res: any = await nxHelper.get(`facebook-reviews/connections${fresh ? '?fresh=1' : ''}`);
            setConnections(Array.isArray(res?.connections) ? res.connections : []);
            setSite(res?.site || {});
        } catch (err) {
            nxToast.error(errorMessage(err));
        } finally {
            setLoading(false);
        }
    }, []);

    // Returning from Facebook?
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const session = params.get('nx_fb_session');
        const status = params.get('nx_fb_status');
        if (session && status) {
            window.history.replaceState({}, '', currentUrlWithout(RETURN_PARAMS));
            if (status === 'ok') {
                setSessionId(session);
                setBusy('pages');
                nxHelper
                    .get(`facebook-reviews/pages?session_id=${encodeURIComponent(session)}`)
                    .then((res: any) => {
                        if (res?.pages) {
                            setPages(res.pages);
                            if (res.pages.length === 1) setSelectedPage(res.pages[0].id);
                        } else {
                            nxToast.error(__('The Facebook login session has expired. Please try again.', 'notificationx'));
                        }
                    })
                    .finally(() => setBusy(''));
            } else {
                nxToast.error(ERROR_TEXT[params.get('nx_fb_error') || ''] || __('Facebook login failed. Please try again.', 'notificationx'));
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

    const connectPage = async () => {
        if (!selectedPage || busy) return;
        setBusy('connect');
        try {
            const res: any = await nxHelper.post('facebook-reviews/pages-connect', { session_id: sessionId, page_id: selectedPage }, { get_error: true });
            if (res?.connection) {
                setPages(null);
                setSessionId('');
                setValue(res.connection);
                nxToast.connected(sprintf(/* translators: %s: page name */ __('%s connected.', 'notificationx'), res.connection.page_name));
                await load(true);
            } else {
                nxToast.error(errorMessage(res));
            }
        } catch (err) {
            nxToast.error(errorMessage(err));
        } finally {
            setBusy('');
        }
    };

    const disconnectPage = async (conn: Connection) => {
        if (busy || !window.confirm(sprintf(/* translators: %s: page name */ __('Disconnect %s? Campaigns using it will stop receiving updates.', 'notificationx'), conn.page_name))) return;
        setBusy('disconnect:' + conn.connection_id);
        try {
            const res: any = await nxHelper.post('facebook-reviews/disconnect-page', { connection_id: conn.connection_id }, { get_error: true });
            if (res?.ok) {
                if (value?.connection_id === conn.connection_id) setValue(null);
                nxToast.connected(__('Page disconnected.', 'notificationx'));
                await load(true);
            } else {
                nxToast.error(errorMessage(res));
            }
        } catch (err) {
            nxToast.error(errorMessage(err));
        } finally {
            setBusy('');
        }
    };

    const disconnectSite = async () => {
        if (busy || !window.confirm(__('Disconnect this site from the NotificationX API? All connected Facebook Pages will be removed.', 'notificationx'))) return;
        setBusy('site');
        try {
            const res: any = await nxHelper.post('api-connect', { source: props?.source || 'facebook_reviews', action: 'disconnect' }, { get_error: true });
            if (res?.status === 'success') {
                nxToast.connected(res.message);
                await load(true);
            } else {
                nxToast.error(errorMessage(res));
            }
        } finally {
            setBusy('');
        }
    };

    const renderConnection = (conn: Connection) => {
        const selected = value?.connection_id === conn.connection_id;
        const isActive = conn.status === 'active';
        return (
            <li key={conn.connection_id} className={`nx-fbr-page ${selected ? 'is-selected' : ''} ${isActive ? '' : 'is-inactive'}`}>
                {mode === 'builder' && (
                    <input
                        type="radio"
                        name={`${fieldName}_pick`}
                        checked={selected}
                        disabled={!isActive}
                        onChange={() => setValue(conn)}
                    />
                )}
                <div className="nx-fbr-page__body">
                    <strong className="nx-fbr-page__name">{conn.page_name || conn.page_id}</strong>
                    <span className={`nx-fbr-page__status is-${conn.status}`}>{statusLabel(conn.status)}</span>
                    <span className="nx-fbr-page__meta">
                        {conn.rating_count !== null
                            ? sprintf(/* translators: 1: rating, 2: count */ __('%1$s ★ · %2$s ratings', 'notificationx'), conn.rating_overall ?? '–', conn.rating_count)
                            : __('No public rating yet', 'notificationx')}
                        {' · '}
                        {conn.individual_reviews
                            ? __('Individual reviews: available', 'notificationx')
                            : __('Individual reviews: not provided by Facebook', 'notificationx')}
                    </span>
                </div>
                {mode === 'settings' && (
                    <button type="button" className="wprf-btn nx-fbr-btn is-secondary" disabled={!!busy} onClick={() => disconnectPage(conn)}>
                        {busy === 'disconnect:' + conn.connection_id ? __('Disconnecting...', 'notificationx') : __('Disconnect', 'notificationx')}
                    </button>
                )}
            </li>
        );
    };

    return (
        <div className={`nx-fbr nx-fbr--${mode}`}>
            {pages && (
                <div className="nx-fbr-picker">
                    <p className="nx-fbr-picker__title">{__('Choose the Facebook Page to connect', 'notificationx')}</p>
                    {pages.length === 0 ? (
                        <p className="nx-fbr-hint">{__('Facebook returned no Pages for this account. Make sure you are an admin of the Page and granted access during login.', 'notificationx')}</p>
                    ) : (
                        <>
                            <select value={selectedPage} onChange={(e) => setSelectedPage(e.target.value)}>
                                <option value="">{__('Select a Page…', 'notificationx')}</option>
                                {pages.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name || p.id}</option>
                                ))}
                            </select>
                            <button type="button" className="wprf-btn nx-fbr-btn" disabled={!selectedPage || !!busy} onClick={connectPage}>
                                {busy === 'connect' ? __('Connecting...', 'notificationx') : __('Connect Page', 'notificationx')}
                            </button>
                        </>
                    )}
                    <button type="button" className="wprf-btn nx-fbr-btn is-link" disabled={!!busy} onClick={() => { setPages(null); setSessionId(''); }}>
                        {__('Cancel', 'notificationx')}
                    </button>
                </div>
            )}

            {loading ? (
                <p className="nx-fbr-hint">{__('Loading connections…', 'notificationx')}</p>
            ) : connections.length > 0 ? (
                <ul className="nx-fbr-pages">{connections.map(renderConnection)}</ul>
            ) : (
                <p className="nx-fbr-hint">{__('No Facebook Page connected yet.', 'notificationx')}</p>
            )}

            {mode === 'builder' && value && !connections.some((c) => c.connection_id === value.connection_id) && !loading && (
                <p className="nx-fbr-hint nx-fbr-hint--warning">
                    {sprintf(/* translators: %s: page name */ __('This campaign is linked to "%s", which is no longer connected. Connect a Page again.', 'notificationx'), value.page_name || value.page_id)}
                </p>
            )}

            <div className="nx-fbr-actions">
                <button type="button" className="wprf-btn nx-fbr-btn nx-fbr-btn--facebook" disabled={!!busy} onClick={startLogin}>
                    {busy === 'login' ? __('Redirecting to Facebook...', 'notificationx') : __('Connect Facebook Page', 'notificationx')}
                </button>
                {mode === 'settings' && site?.connected && (
                    <button type="button" className="wprf-btn nx-fbr-btn is-link" disabled={!!busy} onClick={disconnectSite}>
                        {busy === 'site' ? __('Disconnecting...', 'notificationx') : __('Disconnect from NotificationX API', 'notificationx')}
                    </button>
                )}
            </div>
            {mode === 'settings' && (
                <p className="nx-fbr-hint">
                    {site?.connected
                        ? sprintf(/* translators: %s: site id */ __('Site registered with the NotificationX API (ID %s). Facebook tokens are stored on the API, never on this site.', 'notificationx'), site.site_id)
                        : __('Clicking "Connect Facebook Page" registers this site with the NotificationX API (site URL + an anonymous install fingerprint), then opens the Facebook login.', 'notificationx')}
                </p>
            )}
        </div>
    );
};

export default withLabel(FacebookReviewsConnection);
