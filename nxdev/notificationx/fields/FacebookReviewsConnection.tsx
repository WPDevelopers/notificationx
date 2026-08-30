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
    connect_mode?: string;
};

type Preview = { handle: string; url: string; name: string; rating_overall: number | null; rating_count: number | null };
type Discovered = { url: string; handle: string; source: string };

type Attestation = {
    page: { handle: string; url: string };
    token: string;
    methods: { id: string; label: string; description: string; value?: string }[];
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
    const [modes, setModes] = useState<string[]>(['oauth']);
    const [pageUrl, setPageUrl] = useState('');
    const [attestation, setAttestation] = useState<Attestation | null>(null);
    const [attestError, setAttestError] = useState('');
    const [preview, setPreview] = useState<Preview | null>(null);
    const [discovered, setDiscovered] = useState<Discovered[]>([]);

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
            if (Array.isArray(res?.connect_modes) && res.connect_modes.length) {
                setModes(res.connect_modes);
                if (res.connect_modes.includes('open')) {
                    // Look for a Page the site already advertises, so the common
                    // case is a button to press rather than a field to fill.
                    nxHelper.get('facebook-reviews/discover')
                        .then((d: any) => setDiscovered(Array.isArray(d?.pages) ? d.pages : []))
                        .catch(() => undefined);
                }
            }
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

    /**
     * Owner-attested connect, for an API with no Meta app behind it.
     *
     * Two steps on purpose. The first asks the API for a challenge and shows the
     * customer what to do; the second checks the Page. Collapsing them into one
     * button would mean the first click always fails for anyone who has not
     * already set their Page's website, which reads as broken rather than as
     * "here is what to do next".
     */
    const startAttestation = async () => {
        if (!pageUrl.trim()) return;
        setBusy('attest-start');
        setAttestError('');
        try {
            const res: any = await nxHelper.post('facebook-reviews/attest-start', { page_url: pageUrl.trim() }, { get_error: true });
            if (res?.token) {
                setAttestation(res);
                // Try straight away: a Page that already lists this site needs
                // nothing from the customer, and making them read instructions
                // they do not need is its own kind of failure.
                await runAttestation(pageUrl.trim(), true);
            } else {
                setAttestError(errorMessage(res));
            }
        } catch (err: any) {
            setAttestError(errorMessage(err));
        }
        setBusy('');
    };

    const runAttestation = async (url: string, quiet = false) => {
        if (!quiet) setBusy('attest-verify');
        try {
            const res: any = await nxHelper.post('facebook-reviews/attest-verify', { page_url: url }, { get_error: true });
            if (res?.connection?.connection_id) {
                nxToast.connected(sprintf(/* translators: %s: page name */ __('%s connected.', 'notificationx'), res.connection.page_name || res.connection.page_id));
                if (mode === 'builder') setValue(res.connection);
                setAttestation(null);
                setPageUrl('');
                setAttestError('');
                await load(true);
            } else if (!quiet) {
                setAttestError(errorMessage(res));
            }
        } catch (err: any) {
            if (!quiet) setAttestError(errorMessage(err));
        }
        if (!quiet) setBusy('');
    };

    /**
     * `open` mode: look the Page up, show what we found, then connect on confirm.
     *
     * Split in two because a pasted URL is opaque — seeing the name and rating
     * before committing is what stops a personal profile, a partner's Page or a
     * typo becoming a live connection that only reveals itself when the wrong
     * reviews start appearing on the site.
     */
    const lookUpPage = async (url?: string) => {
        const target = (url ?? pageUrl).trim();
        if (!target) return;
        setPageUrl(target);
        setBusy('preview');
        setAttestError('');
        setPreview(null);
        try {
            const res: any = await nxHelper.post('facebook-reviews/page-preview', { page_url: target }, { get_error: true });
            if (res?.preview?.handle) {
                setPreview(res.preview);
            } else {
                setAttestError(errorMessage(res));
            }
        } catch (err: any) {
            setAttestError(errorMessage(err));
        }
        setBusy('');
    };

    const connectPreviewed = async () => {
        if (!preview) return;
        setBusy('page-connect');
        try {
            const res: any = await nxHelper.post('facebook-reviews/page-connect', { page_url: preview.url }, { get_error: true });
            if (res?.connection?.connection_id) {
                nxToast.connected(sprintf(/* translators: %s: page name */ __('%s connected.', 'notificationx'), res.connection.page_name || res.connection.page_id));
                if (mode === 'builder') setValue(res.connection);
                setPreview(null);
                setPageUrl('');
                await load(true);
            } else {
                setAttestError(errorMessage(res));
            }
        } catch (err: any) {
            setAttestError(errorMessage(err));
        }
        setBusy('');
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

            {attestation && (
                <div className="nx-fbr-attest">
                    <p className="nx-fbr-picker__title">
                        {sprintf(/* translators: %s: facebook page handle */ __('Confirm you manage "%s"', 'notificationx'), attestation.page.handle)}
                    </p>
                    <p className="nx-fbr-hint">
                        {__('Do either of these on your Facebook Page, then verify. Facebook can take a minute to publish the change.', 'notificationx')}
                    </p>
                    <ol className="nx-fbr-attest__methods">
                        {attestation.methods.map((m) => (
                            <li key={m.id}>
                                <strong>{m.label}</strong>
                                <span>{m.description}</span>
                                {m.value ? <code className="nx-fbr-attest__code">{m.value}</code> : null}
                            </li>
                        ))}
                    </ol>
                    {attestError ? <p className="nx-fbr-hint nx-fbr-hint--warning">{attestError}</p> : null}
                    <button type="button" className="wprf-btn nx-fbr-btn" disabled={!!busy} onClick={() => runAttestation(pageUrl.trim())}>
                        {busy === 'attest-verify' ? __('Checking your Page…', 'notificationx') : __('Verify and connect', 'notificationx')}
                    </button>
                    <button type="button" className="wprf-btn nx-fbr-btn is-link" disabled={!!busy} onClick={() => { setAttestation(null); setAttestError(''); }}>
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

            {preview && (
                <div className="nx-fbr-preview">
                    <div className="nx-fbr-preview__body">
                        <strong className="nx-fbr-preview__name">{preview.name}</strong>
                        <span className="nx-fbr-preview__meta">
                            {preview.rating_overall !== null
                                ? sprintf(
                                      /* translators: 1: star rating, 2: number of ratings */
                                      __('%1$s out of 5 · %2$s ratings', 'notificationx'),
                                      String(preview.rating_overall),
                                      String(preview.rating_count ?? 0)
                                  )
                                : __('Facebook does not show a public rating for this Page.', 'notificationx')}
                        </span>
                        <a className="nx-fbr-preview__link" href={preview.url} target="_blank" rel="noreferrer noopener">{preview.url}</a>
                    </div>
                    <div className="nx-fbr-preview__actions">
                        <button type="button" className="wprf-btn nx-fbr-btn" disabled={!!busy} onClick={connectPreviewed}>
                            {busy === 'page-connect' ? __('Connecting…', 'notificationx') : __('Connect this Page', 'notificationx')}
                        </button>
                        <button type="button" className="wprf-btn nx-fbr-btn is-link" disabled={!!busy} onClick={() => { setPreview(null); setAttestError(''); }}>
                            {__('Not this one', 'notificationx')}
                        </button>
                    </div>
                </div>
            )}

            {(modes.includes('open') || modes.includes('attested')) && !attestation && !preview && !pages && (
                <div className="nx-fbr-attest__start">
                    <label className="nx-fbr-attest__label" htmlFor={`nx-fbr-url-${mode}`}>
                        {modes.includes('oauth')
                            ? __('…or enter your Facebook Page address', 'notificationx')
                            : __('Your Facebook Page address', 'notificationx')}
                    </label>

                    {/* A Page the site already links to. Offered, never assumed:
                        the link might be a partner's Page or an employee's. */}
                    {modes.includes('open') && discovered.length > 0 && (
                        <div className="nx-fbr-found">
                            <span className="nx-fbr-found__label">{__('Found on your site:', 'notificationx')}</span>
                            {discovered.slice(0, 3).map((d) => (
                                <button
                                    key={d.handle}
                                    type="button"
                                    className="wprf-btn nx-fbr-btn is-link nx-fbr-found__item"
                                    disabled={!!busy}
                                    onClick={() => lookUpPage(d.url)}
                                >
                                    facebook.com/{d.handle}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="nx-fbr-attest__row">
                        <input
                            id={`nx-fbr-url-${mode}`}
                            type="text"
                            value={pageUrl}
                            placeholder="https://www.facebook.com/yourpage"
                            onChange={(e) => { setPageUrl(e.target.value); setAttestError(''); }}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    modes.includes('open') ? lookUpPage() : startAttestation();
                                }
                            }}
                        />
                        <button
                            type="button"
                            className="wprf-btn nx-fbr-btn"
                            disabled={!pageUrl.trim() || !!busy}
                            onClick={() => (modes.includes('open') ? lookUpPage() : startAttestation())}
                        >
                            {busy === 'preview' || busy === 'attest-start' ? __('Checking…', 'notificationx') : __('Continue', 'notificationx')}
                        </button>
                    </div>
                    {attestError ? <p className="nx-fbr-hint nx-fbr-hint--warning">{attestError}</p> : null}
                    <p className="nx-fbr-hint">
                        {modes.includes('open')
                            ? __('Enter the address of a Facebook Page you manage. We will show you what we found before connecting it.', 'notificationx')
                            : __('You will be asked to confirm you manage the Page — either it already lists this site as its website, or you add a short code to it.', 'notificationx')}
                    </p>
                </div>
            )}

            <div className="nx-fbr-actions">
                {modes.includes('oauth') && (
                    <button type="button" className="wprf-btn nx-fbr-btn nx-fbr-btn--facebook" disabled={!!busy} onClick={startLogin}>
                        {busy === 'login' ? __('Redirecting to Facebook...', 'notificationx') : __('Connect Facebook Page', 'notificationx')}
                    </button>
                )}
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
                        : __('Connecting registers this site with the NotificationX API (site URL + an anonymous install fingerprint).', 'notificationx')}
                </p>
            )}
        </div>
    );
};

export default withLabel(FacebookReviewsConnection);
